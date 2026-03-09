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
 * Run on a schedule (every 5–15 min) or manually on-demand.
 *
 * Usage:
 *   php artisan epicor:sync-cache              # sync all companies, last 7 days
 *   php artisan epicor:sync-cache --company=10 # sync one epicor company code
 *   php artisan epicor:sync-cache --full       # full sync (all records, no date filter)
 *   php artisan epicor:sync-cache --days=30    # last N days (default: 7)
 */
class SyncEpicorCache extends Command
{
    protected $signature = 'epicor:sync-cache
                            {--company= : Epicor company code to sync (default: all)}
                            {--full     : Full sync, no date filter}
                            {--days=7   : How many days back to sync}';

    protected $description = 'Sync Epicor ODBC valve records into local MySQL cache';

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
        $full          = (bool) $this->option('full');
        $days          = (int) $this->option('days');

        // Build list of (epicorCompany, tableName) pairs — deduplicated
        $companies = Company::whereNotNull('epicorCompany')
            ->where('epicorCompany', '!=', '')
            ->whereNotNull('tableName')
            ->where('tableName', '!=', '')
            ->get()
            ->unique(fn($c) => $c->epicorCompany . '|' . $c->tableName);

        if ($targetCompany) {
            $companies = $companies->filter(fn($c) => $c->epicorCompany === $targetCompany);
            if ($companies->isEmpty()) {
                $this->error("No company found with epicorCompany='{$targetCompany}'");
                return self::FAILURE;
            }
        }

        $totalUpserted = 0;

        foreach ($companies as $company) {
            $epicorCompany = $company->epicorCompany;
            $tableName     = $company->tableName;

            $this->line("Syncing company={$epicorCompany} table={$tableName}...");
            $count = $this->syncCompany($epicorCompany, $tableName, $full, $days);
            $this->info("  → {$count} records upserted");
            $totalUpserted += $count;
        }

        $this->info("Done. Total upserted: {$totalUpserted}");
        Log::info("epicor:sync-cache complete. Upserted: {$totalUpserted}");

        return self::SUCCESS;
    }

    private function syncCompany(string $epicorCompany, string $tableName, bool $full, int $days): int
    {
        $conn = $this->epicorService->getRawConnection();
        if (!$conn) {
            $this->error("  Could not get ODBC connection.");
            return 0;
        }

        $table = "Epicor101.{$tableName}";
        $co    = str_replace("'", "''", $epicorCompany);
        $cols  = self::COLS;

        if ($full) {
            $sql = "SELECT {$cols} FROM {$table} WHERE Company='{$co}' AND Key1 != '' AND Key1 != '0'";
        } else {
            $cutoff = now()->subDays($days)->format('Y-m-d');
            $sql    = "SELECT {$cols} FROM {$table} WHERE Company='{$co}' AND Key1 != '' AND Key1 != '0' AND Date01 >= '{$cutoff}'";
        }

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
                $this->line("  ... {$count} so far");
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
        $updateCols = array_keys($batch[0]);
        $updateCols = array_values(array_filter(
            $updateCols,
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
