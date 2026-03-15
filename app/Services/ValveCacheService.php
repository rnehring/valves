<?php

namespace App\Services;

use App\Models\EpicorValve;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ValveCacheService
 *
 * Serves valve list and history queries from the local MySQL valve_cache table
 * instead of going to Epicor ODBC over the WAN (which takes 80-225 seconds).
 *
 * Single-record reads (edit/create) still go direct to EpicorService.
 * All writes go to Epicor first, then immediately update the cache row here.
 *
 * The cache is also kept fresh by the epicor:sync-cache artisan command,
 * which should run every 5-15 minutes via Windows Task Scheduler.
 */
class ValveCacheService
{
    // =========================================================================
    // List queries (replaces EpicorService::selectValves for index pages)
    // =========================================================================

    /**
     * Select valves from MySQL cache with optional WHERE clauses.
     *
     * WHERE clauses are raw SQL fragments (same syntax as MySQL — LIKE, >=, etc.)
     * ORDER BY clauses are normalized: MSSQL CONVERT(int, Key1) → CAST(Key1 AS UNSIGNED)
     *
     * @param  string   $epicorCompany
     * @param  string   $tableName
     * @param  array    $where    Raw SQL WHERE fragments, e.g. ["ShortChar15 != ''"]
     * @param  array    $orderBy  Raw SQL ORDER BY fragments
     * @param  int      $limit
     * @return EpicorValve[]
     */
    public function selectValves(
        string $epicorCompany,
        string $tableName,
        array  $where   = [],
        array  $orderBy = [],
        int    $limit   = 100
    ): array {
        $query = DB::table('valve_cache')
            ->where('epicor_company', $epicorCompany)
            ->where('table_name', $tableName)
            ->where('Key1', '!=', '')
            ->where('Key1', '!=', '0')
            ->limit($limit);

        foreach ($where as $clause) {
            $query->whereRaw($this->normalizeWhere($clause));
        }

        if (!empty($orderBy)) {
            foreach ($orderBy as $clause) {
                $query->orderByRaw($this->normalizeOrderBy($clause));
            }
        } else {
            // Default: most recent first
            $query->orderBy('Date01', 'desc');
        }

        $rows = $query->get()->map(fn($row) => (array) $row)->toArray();
        return array_map(fn($row) => EpicorValve::fromArray($row), $rows);
    }

    /**
     * Get valve history for a user (today + yesterday) from cache.
     */
    public function selectValveHistory(string $epicorCompany, string $tableName, string $username): array
    {
        $today     = now()->format('Y-m-d');
        $yesterday = now()->subDay()->format('Y-m-d');

        $rows = DB::table('valve_cache')
            ->where('epicor_company', $epicorCompany)
            ->where('table_name', $tableName)
            ->where('ShortChar02', $username)
            ->whereIn('Date01', [$today, $yesterday])
            ->orderByRaw('CAST(Key1 AS UNSIGNED) DESC')
            ->limit(100)
            ->get()
            ->map(fn($row) => (array) $row)
            ->toArray();

        return array_map(fn($row) => EpicorValve::fromArray($row), $rows);
    }

    // =========================================================================
    // Paginated list queries
    // =========================================================================

    /**
     * Paginated version of selectValves — returns a LengthAwarePaginator.
     * Preserves the current request's query string in pagination links.
     *
     * @param int    $perPage  Rows per page. Pass 0 for "all records" (single page).
     * @param string $sortCol  MySQL column name to sort by (must be pre-validated by caller).
     * @param string $sortDir  'asc' or 'desc'
     */
    public function paginateValves(
        string $epicorCompany,
        string $tableName,
        array  $where   = [],
        array  $orderBy = [],
        int    $perPage = 25,
        int    $page    = 1,
        string $sortCol = '',
        string $sortDir = 'desc'
    ): LengthAwarePaginator {
        $query = DB::table('valve_cache')
            ->where('epicor_company', $epicorCompany)
            ->where('table_name', $tableName)
            ->where('Key1', '!=', '')
            ->where('Key1', '!=', '0');

        foreach ($where as $clause) {
            $query->whereRaw($this->normalizeWhere($clause));
        }

        $total = $query->count();

        // Apply ORDER BY — explicit sort takes precedence over $orderBy array
        $dir = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';
        if ($sortCol !== '') {
            // Numeric key columns need CAST for correct integer sort
            if (in_array($sortCol, ['Key1', 'Number01', 'Number06'], true)) {
                $query->orderByRaw("CAST({$sortCol} AS DECIMAL(18,3)) {$dir}");
            } else {
                $query->orderBy($sortCol, $dir);
            }
        } elseif (!empty($orderBy)) {
            foreach ($orderBy as $clause) {
                $query->orderByRaw($this->normalizeOrderBy($clause));
            }
        } else {
            $query->orderBy('Date01', 'desc');
        }

        // perPage = 0 means "show all" — single page, no pagination bar
        $effectivePerPage = $perPage > 0 ? $perPage : max($total, 1);
        $effectivePage    = $perPage > 0 ? $page : 1;

        $rows = $query
            ->offset(($effectivePage - 1) * $effectivePerPage)
            ->limit($effectivePerPage)
            ->get()
            ->map(fn($row) => EpicorValve::fromArray((array) $row))
            ->toArray();

        return new LengthAwarePaginator(
            $rows,
            $total,
            $effectivePerPage,
            $effectivePage,
            [
                'path'  => LengthAwarePaginator::resolveCurrentPath(),
                'query' => request()->query(),
            ]
        );
    }

    /**
     * Paginated version of selectValveHistory.
     */
    public function paginateValveHistory(
        string $epicorCompany,
        string $tableName,
        string $username,
        int    $perPage = 25,
        int    $page    = 1,
        string $sortCol = 'Key1',
        string $sortDir = 'desc'
    ): LengthAwarePaginator {
        $today     = now()->format('Y-m-d');
        $yesterday = now()->subDay()->format('Y-m-d');

        $query = DB::table('valve_cache')
            ->where('epicor_company', $epicorCompany)
            ->where('table_name', $tableName)
            ->where('ShortChar02', $username)
            ->whereIn('Date01', [$today, $yesterday]);

        $total = $query->count();

        $dir = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';
        if (in_array($sortCol, ['Key1', 'Number01'], true)) {
            $query->orderByRaw("CAST({$sortCol} AS DECIMAL(18,3)) {$dir}");
        } else {
            $query->orderBy($sortCol, $dir);
        }

        $effectivePerPage = $perPage > 0 ? $perPage : max($total, 1);
        $effectivePage    = $perPage > 0 ? $page : 1;

        $rows = $query
            ->offset(($effectivePage - 1) * $effectivePerPage)
            ->limit($effectivePerPage)
            ->get()
            ->map(fn($row) => EpicorValve::fromArray((array) $row))
            ->toArray();

        return new LengthAwarePaginator(
            $rows,
            $total,
            $effectivePerPage,
            $effectivePage,
            [
                'path'  => LengthAwarePaginator::resolveCurrentPath(),
                'query' => request()->query(),
            ]
        );
    }

    // =========================================================================
    // Cache writes (called after every Epicor write)
    // =========================================================================

    /**
     * Upsert a valve record into the cache after a write to Epicor.
     * Pass the same $data array you sent to EpicorService::updateValve/insertValve.
     */
    public function upsertValve(string $epicorCompany, string $tableName, array $data): void
    {
        $key1 = (string) ($data['Key1'] ?? '');
        if ($key1 === '') {
            return;
        }

        // Fetch the current cache row (if any) so we only update changed fields
        $existing = DB::table('valve_cache')
            ->where('epicor_company', $epicorCompany)
            ->where('table_name', $tableName)
            ->where('Key1', $key1)
            ->first();

        $row = $existing ? (array) $existing : [
            'epicor_company' => $epicorCompany,
            'table_name'     => $tableName,
            'Key1'           => $key1,
            'Company'        => $epicorCompany,
        ];

        // Merge incoming data fields
        $mutable = [
            'Character01', 'Character02', 'Character03', 'Character05',
            'CheckBox01', 'CheckBox02', 'CheckBox03', 'CheckBox04',
            'Date01', 'Date02',
            'Number01', 'Number02', 'Number03', 'Number04', 'Number05',
            'Number06', 'Number10', 'Number11',
            'ShortChar01', 'ShortChar02', 'ShortChar03', 'ShortChar04', 'ShortChar05',
            'ShortChar07', 'ShortChar08', 'ShortChar09', 'ShortChar10', 'ShortChar11',
            'ShortChar12', 'ShortChar13', 'ShortChar15', 'ShortChar16', 'ShortChar18',
        ];

        foreach ($mutable as $col) {
            if (array_key_exists($col, $data)) {
                $row[$col] = $data[$col];
            }
        }

        $row['synced_at'] = now()->format('Y-m-d H:i:s');

        if ($existing) {
            DB::table('valve_cache')
                ->where('epicor_company', $epicorCompany)
                ->where('table_name', $tableName)
                ->where('Key1', $key1)
                ->update($row);
        } else {
            try {
                DB::table('valve_cache')->insert($row);
            } catch (\Throwable $e) {
                // Race condition: another request inserted it; fall back to update
                DB::table('valve_cache')
                    ->where('epicor_company', $epicorCompany)
                    ->where('table_name', $tableName)
                    ->where('Key1', $key1)
                    ->update($row);
            }
        }

        Log::debug("ValveCacheService: upserted Key1={$key1} company={$epicorCompany}");
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Normalize MSSQL-isms in WHERE clauses for MySQL.
     */
    private function normalizeWhere(string $clause): string
    {
        // MSSQL: CONVERT(int, Key1) → MySQL: CAST(Key1 AS UNSIGNED)
        $clause = preg_replace('/CONVERT\s*\(\s*int\s*,\s*Key1\s*\)/i', 'CAST(Key1 AS UNSIGNED)', $clause);
        return $clause;
    }

    /**
     * Normalize MSSQL-isms in ORDER BY clauses for MySQL.
     */
    private function normalizeOrderBy(string $clause): string
    {
        $clause = preg_replace('/CONVERT\s*\(\s*int\s*,\s*Key1\s*\)/i', 'CAST(Key1 AS UNSIGNED)', $clause);
        return $clause;
    }
}
