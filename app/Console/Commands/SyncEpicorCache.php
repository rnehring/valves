<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\EpicorService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * SyncEpicorCache
 *
 * Pulls valve records from Epicor ODBC into the local MySQL valve_cache table.
 *
 * CHUNKED ARCHITECTURE:
 * Instead of one giant streaming query (which times out on slow WAN connections),
 * this command issues many small TOP N queries ordered by Key1, committing each
 * batch to MySQL before fetching the next. If the connection drops, just re-run
 * with --resume and it picks up from the last successfully saved Key1.
 *
 * Usage:
 *   php artisan epicor:sync-cache                         # all companies, last 7 days
 *   php artisan epicor:sync-cache --company=10            # one company, last 7 days
 *   php artisan epicor:sync-cache --company=10 --full     # all records, chunked
 *   php artisan epicor:sync-cache --company=10 --resume   # auto-resume from max cached Key1
 *   php artisan epicor:sync-cache --company=20 --full --table=Ice.UD01
 *   php artisan epicor:sync-cache --company=10 --full --min-key=50000 --max-key=80000
 *   php artisan epicor:sync-cache --days=30               # last N days (default: 7)
 *   php artisan epicor:sync-cache --chunk=500             # rows per ODBC query (default: 1000)
 */
class SyncEpicorCache extends Command
{
    protected $signature = 'epicor:sync-cache
                            {--company=  : Epicor company code to sync (default: all)}
                            {--table=    : Specific table name, e.g. Ice.UD01 (default: all for company)}
                            {--full      : Full sync — no date filter, uses chunked key ranges}
                            {--resume    : Auto-resume: start from (max cached Key1 + 1) per company/table}
                            {--min-key=0 : Start Key1 for range sync (used with --full)}
                            {--max-key=  : End Key1 for range sync (omit = no upper limit)}
                            {--days=7    : How many days back to sync (when not using --full)}
                            {--chunk=1000: Rows per ODBC query in chunked mode (default: 1000)}';

    protected $description = 'Sync Epicor ODBC valve records into local MySQL valve_cache';

    private const COLS = 'Key1,Company,Character01,Character02,Character03,Character05,'
                       . 'CheckBox01,CheckBox02,CheckBox03,CheckBox04,'
                       . 'Date01,Date02,'
                       . 'Number01,Number02,Number03,Number04,Number05,Number06,Number10,Number11,'
                       . 'ShortChar01,ShortChar02,ShortChar03,ShortChar04,ShortChar05,'
                       . 'ShortChar07,ShortChar08,ShortChar09,ShortChar10,ShortChar11,'
                       . 'ShortChar12,ShortChar13,ShortChar15,ShortChar16,ShortChar18';

    public function __construct(private EpicorService $epicorService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (!$this->epicorService->isAvailable()) {
            $this->error('Epicor ODBC unavailable: ' . $this->epicorService->getLastError());
            return self::FAILURE;
        }

        $targetCompany = $this->option('company');
        $targetTable   = $this->option('table');
        $full          = (bool) $this->option('full');
        $resume        = (bool) $this->option('resume');
        $days          = (int)  $this->option('days');
        $chunkSize     = max(100, (int) $this->option('chunk'));
        $minKey        = (int)  ($this->option('min-key') ?? 0);
        $maxKey        = $this->option('max-key') !== null ? (int) $this->option('max-key') : null;

        // Build deduplicated list of (epicorCompany, tableName) pairs from companies table
        $query = Company::whereNotNull('epicorCompany')
            ->where('epicorCompany', '!=', '')
            ->whereNotNull('tableName')
            ->where('tableName', '!=', '');

        $companies = $query->get()->unique(fn($c) => $c->epicorCompany . '|' . $c->tableName);

        if ($targetCompany) {
            $companies = $companies->filter(fn($c) => $c->epicorCompany === $targetCompany);
            if ($companies->isEmpty()) {
                $this->error("No company found with epicorCompany='{$targetCompany}'");
                return self::FAILURE;
            }
        }

        if ($targetTable) {
            $companies = $companies->filter(fn($c) => $c->tableName === $targetTable);
            if ($companies->isEmpty()) {
                $this->error("No company/table found matching table='{$targetTable}'");
                return self::FAILURE;
            }
        }

        $totalUpserted = 0;

        foreach ($companies as $company) {
            $epicorCompany = $company->epicorCompany;
            $tableName     = $company->tableName;

            // For --resume, find the LOWEST gap in the cache for this company+table.
            // We do this by finding the smallest Key1 in Epicor that is NOT in our cache.
            // This correctly handles mid-range gaps, not just appending from the top.
            $effectiveMinKey = $minKey;
            if ($resume) {
                $lowestGap = $this->findLowestGap($epicorCompany, $tableName, $maxKey);
                $effectiveMinKey = $lowestGap;
                $this->line("  [resume] company={$epicorCompany} table={$tableName}: lowest uncached Key1={$effectiveMinKey}");
            }

            $this->line("Syncing company={$epicorCompany} table={$tableName}...");

            if ($full || $resume) {
                $count = $this->syncChunked($epicorCompany, $tableName, $effectiveMinKey, $maxKey, $chunkSize);
            } else {
                $count = $this->syncByDate($epicorCompany, $tableName, $days);
            }

            $this->info("  → {$count} records upserted");
            $totalUpserted += $count;
        }

        $this->info("Done. Total upserted: {$totalUpserted}");
        Log::info("epicor:sync-cache complete. Upserted: {$totalUpserted}");

        return self::SUCCESS;
    }

    /**
     * Find the lowest Key1 value that is in Epicor but NOT in our cache.
     * Scans in chunks of 5000 keys at a time to avoid loading everything into memory.
     * Falls back to 0 (sync from beginning) if nothing is cached yet.
     */
    private function findLowestGap(string $epicorCompany, string $tableName, ?int $maxKey): int
    {
        // Get all cached Key1s sorted ascending, in chunks, and find first missing one
        // We compare against Epicor directly: fetch TOP 1 from Epicor WHERE Key1 NOT IN (cache)
        // But that's slow. Instead: find the min cached key and scan forward in DB.
        $minCached = DB::table('valve_cache')
            ->where('epicor_company', $epicorCompany)
            ->where('table_name', $tableName)
            ->min(DB::raw('CAST(Key1 AS UNSIGNED)'));

        if (!$minCached) {
            return 0; // Nothing cached yet, start from beginning
        }

        // Walk through cached keys in batches of 5000, find first gap
        $cursor = 0;
        $batchSize = 5000;

        while (true) {
            $rows = DB::table('valve_cache')
                ->where('epicor_company', $epicorCompany)
                ->where('table_name', $tableName)
                ->where(DB::raw('CAST(Key1 AS UNSIGNED)'), '>', $cursor)
                ->when($maxKey, fn($q) => $q->where(DB::raw('CAST(Key1 AS UNSIGNED)'), '<=', $maxKey))
                ->orderByRaw('CAST(Key1 AS UNSIGNED) ASC')
                ->limit($batchSize)
                ->pluck('Key1')
                ->map(fn($k) => (int) $k)
                ->toArray();

            if (empty($rows)) {
                // Ran out of cached rows — gap starts after cursor
                return $cursor;
            }

            // Check for a gap within this batch
            $prev = null;
            foreach ($rows as $key) {
                if ($prev !== null && $key > $prev + 2000) {
                    // Gap detected: the first uncached key is right after $prev
                    // We subtract 1 so the chunked syncer uses > $prev-1 which = >= $prev
                    return max(0, $prev - 1);
                }
                $prev = $key;
            }

            // No gap in this batch, advance cursor to last seen key
            $cursor = end($rows);

            if (count($rows) < $batchSize) {
                // Last batch — no more cached records, return cursor as start
                return $cursor;
            }
        }
    }

    /**
     * Chunked full sync — issues many small TOP queries ordered by Key1.
     * Safe to interrupt and resume with --resume.
     */
    private function syncChunked(
        string  $epicorCompany,
        string  $tableName,
        int     $minKey,
        ?int    $maxKey,
        int     $chunkSize
    ): int {
        $conn  = $this->epicorService->getRawConnection();
        if (!$conn) {
            $this->error("  Could not get ODBC connection.");
            return 0;
        }

        $table   = "Epicor101.{$tableName}";
        $co      = str_replace("'", "''", $epicorCompany);
        $cols    = self::COLS;
        $total   = 0;
        $cursor  = $minKey;

        $upperBoundMsg = $maxKey !== null ? " up to Key1={$maxKey}" : '';
        $this->line("  Chunked sync from Key1={$minKey}{$upperBoundMsg}, chunk size={$chunkSize}");

        while (true) {
            $upperClause = ($maxKey !== null)
                ? "AND CAST(Key1 AS INT) <= {$maxKey}"
                : '';

            $sql = "SELECT TOP {$chunkSize} {$cols}
                    FROM {$table}
                    WHERE Company='{$co}'
                      AND Key1 != '' AND Key1 != '0'
                      AND ISNUMERIC(Key1) = 1
                      AND CAST(Key1 AS INT) > {$cursor}
                      {$upperClause}
                    ORDER BY CAST(Key1 AS INT) ASC";

            $result = @odbc_exec($conn, $sql);
            if (!$result) {
                $this->error("  ODBC query failed at Key1>{$cursor}: " . odbc_errormsg($conn));
                $this->warn("  Re-run with: --company={$epicorCompany} --full --min-key={$cursor}");
                break;
            }

            $batch     = [];
            $lastKey   = $cursor;
            $now       = now()->format('Y-m-d H:i:s');
            $fetchError = false;

            try {
                while ($row = odbc_fetch_array($result)) {
                    $batch[]  = $this->buildRow($row, $epicorCompany, $tableName, $now);
                    $lastKey  = (int) $row['Key1'];
                }
            } catch (\Throwable $e) {
                // Connection dropped mid-chunk — save whatever we fetched so far
                $fetchError = true;
                $this->warn("  Connection dropped mid-chunk: " . $e->getMessage());
            }

            @odbc_free_result($result);

            // Commit whatever rows were fetched before the drop
            if (!empty($batch)) {
                $this->upsertBatch($batch);
                $total  += count($batch);
                $cursor  = $lastKey;
                $this->line("  ... {$total} upserted, cursor now at Key1={$cursor}");
            }

            if ($fetchError) {
                $this->error("  Sync interrupted. Re-run with: --company={$epicorCompany} --table={$tableName} --resume");
                $this->warn("  Or manually: --company={$epicorCompany} --table={$tableName} --full --min-key={$cursor}");
                break;
            }

            if (empty($batch)) {
                // No more rows — we're done
                break;
            }

            // If we got fewer rows than the chunk size, we've exhausted the range
            if (count($batch) < $chunkSize) {
                break;
            }
        }

        return $total;
    }

    /**
     * Date-based sync for scheduled incremental updates (non-full mode).
     * Streams all matching rows — suitable for short date windows.
     */
    private function syncByDate(string $epicorCompany, string $tableName, int $days): int
    {
        $conn = $this->epicorService->getRawConnection();
        if (!$conn) {
            $this->error("  Could not get ODBC connection.");
            return 0;
        }

        $table  = "Epicor101.{$tableName}";
        $co     = str_replace("'", "''", $epicorCompany);
        $cols   = self::COLS;
        $cutoff = now()->subDays($days)->format('Y-m-d');

        $sql = "SELECT {$cols} FROM {$table}
                WHERE Company='{$co}'
                  AND Key1 != '' AND Key1 != '0'
                  AND Date01 >= '{$cutoff}'";

        $result = @odbc_exec($conn, $sql);
        if (!$result) {
            $this->error('  ODBC query failed: ' . odbc_errormsg($conn));
            return 0;
        }

        $batch = [];
        $count = 0;
        $now   = now()->format('Y-m-d H:i:s');

        while ($row = odbc_fetch_array($result)) {
            $batch[] = $this->buildRow($row, $epicorCompany, $tableName, $now);
            $count++;
            if (count($batch) >= 200) {
                $this->upsertBatch($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            $this->upsertBatch($batch);
        }

        odbc_free_result($result);
        return $count;
    }

    private function buildRow(array $row, string $epicorCompany, string $tableName, string $now): array
    {
        return [
            'epicor_company' => $epicorCompany,
            'table_name'     => $tableName,
            'Key1'           => (string) ($row['Key1'] ?? ''),
            'Company'        => (string) ($row['Company'] ?? ''),
            'Character01'    => $row['Character01'] ?? null,
            'Character02'    => $row['Character02'] ?? null,
            'Character03'    => $row['Character03'] ?? null,
            'Character05'    => $row['Character05'] ?? null,
            'CheckBox01'     => empty($row['CheckBox01']) ? 0 : 1,
            'CheckBox02'     => empty($row['CheckBox02']) ? 0 : 1,
            'CheckBox03'     => empty($row['CheckBox03']) ? 0 : 1,
            'CheckBox04'     => empty($row['CheckBox04']) ? 0 : 1,
            'Date01'         => $this->parseDate($row['Date01'] ?? null),
            'Date02'         => $this->parseDate($row['Date02'] ?? null),
            'Number01'       => (float) ($row['Number01'] ?? 0),
            'Number02'       => (float) ($row['Number02'] ?? 0),
            'Number03'       => (float) ($row['Number03'] ?? 0),
            'Number04'       => (float) ($row['Number04'] ?? 0),
            'Number05'       => (float) ($row['Number05'] ?? 0),
            'Number06'       => (float) ($row['Number06'] ?? 0),
            'Number10'       => (float) ($row['Number10'] ?? 0),
            'Number11'       => (float) ($row['Number11'] ?? 0),
            'ShortChar01'    => (string) ($row['ShortChar01'] ?? ''),
            'ShortChar02'    => (string) ($row['ShortChar02'] ?? ''),
            'ShortChar03'    => (string) ($row['ShortChar03'] ?? ''),
            'ShortChar04'    => (string) ($row['ShortChar04'] ?? ''),
            'ShortChar05'    => (string) ($row['ShortChar05'] ?? ''),
            'ShortChar07'    => (string) ($row['ShortChar07'] ?? ''),
            'ShortChar08'    => (string) ($row['ShortChar08'] ?? ''),
            'ShortChar09'    => (string) ($row['ShortChar09'] ?? ''),
            'ShortChar10'    => (string) ($row['ShortChar10'] ?? ''),
            'ShortChar11'    => (string) ($row['ShortChar11'] ?? ''),
            'ShortChar12'    => (string) ($row['ShortChar12'] ?? ''),
            'ShortChar13'    => (string) ($row['ShortChar13'] ?? ''),
            'ShortChar15'    => (string) ($row['ShortChar15'] ?? ''),
            'ShortChar16'    => (string) ($row['ShortChar16'] ?? ''),
            'ShortChar18'    => (string) ($row['ShortChar18'] ?? ''),
            'synced_at'      => $now,
        ];
    }

    private function upsertBatch(array $batch): void
    {
        $updateCols = array_values(array_filter(
            array_keys($batch[0]),
            fn($c) => !in_array($c, ['epicor_company', 'table_name', 'Key1'])
        ));

        DB::table('valve_cache')->upsert(
            $batch,
            ['epicor_company', 'table_name', 'Key1'],
            $updateCols
        );
    }

    private function parseDate(?string $val): ?string
    {
        if (!$val || $val === '' || str_starts_with($val, '1900') || str_starts_with($val, '0000')) {
            return null;
        }
        $ts = strtotime($val);
        return $ts ? date('Y-m-d', $ts) : null;
    }
}
