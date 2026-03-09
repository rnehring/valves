<?php

namespace App\Services;

use App\Models\EpicorValve;
use Illuminate\Support\Facades\Log;

/**
 * EpicorService
 *
 * Handles all read/write operations against the Epicor ODBC database.
 * Uses PHP's ODBC functions to connect to the EpicorMSSQL DSN.
 *
 * PERFORMANCE NOTE:
 * Epicor UD01/UD02 tables have 200+ columns. Using SELECT * fetches all of them
 * over ODBC, causing 200+ second queries even for 100 rows. Always use the
 * VALVE_COLUMNS explicit column list. Never use SELECT * on these tables.
 *
 * ARCHITECTURE:
 * - List queries (valve list page, history) read from MySQL valve_cache table
 * - Single-record reads (edit/create) go direct to Epicor ODBC
 * - All writes go direct to Epicor ODBC, then update the cache row immediately
 * - SyncEpicorCache artisan command keeps the cache fresh on a schedule
 */
class EpicorService
{
    private string $dsn      = 'EpicorMSSQL';
    private string $username = 'epdb';
    private string $password = '112143xc99';
    private mixed  $connection = null;
    private bool   $connected  = false;
    private ?string $lastError = null;

    /**
     * The exact columns used by EpicorValve — all others ignored.
     * Explicitly listing these instead of SELECT * is the critical
     * performance fix (UD tables have 200+ columns; we only need 35).
     */
    public const VALVE_COLUMNS = [
        'Key1', 'Company',
        'Character01', 'Character02', 'Character03', 'Character05',
        'CheckBox01',  'CheckBox02',  'CheckBox03',  'CheckBox04',
        'Date01',      'Date02',
        'Number01',    'Number02',    'Number03',    'Number04',    'Number05',
        'Number06',    'Number10',    'Number11',
        'ShortChar01', 'ShortChar02', 'ShortChar03', 'ShortChar04', 'ShortChar05',
        'ShortChar07', 'ShortChar08', 'ShortChar09', 'ShortChar10', 'ShortChar11',
        'ShortChar12', 'ShortChar13', 'ShortChar15', 'ShortChar16', 'ShortChar18',
    ];

    private array $ignoredSerialNumbers = [
        '101005', '101006', '101008', '101010', '101011', '101012', '101014',
        '101015', '101016', '110220', '1110220', '110221', '888889',
    ];

    // =========================================================================
    // Connection management
    // =========================================================================

    private function connect(): bool
    {
        if ($this->connected) {
            return true;
        }
        try {
            $this->connection = @odbc_connect($this->dsn, $this->username, $this->password);
            if (!$this->connection) {
                $this->lastError = odbc_errormsg();
                Log::error("EpicorService: ODBC connect failed: {$this->lastError}");
                return false;
            }
            $this->connected = true;
            return true;
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            Log::error("EpicorService: Exception connecting: {$this->lastError}");
            return false;
        }
    }

    public function isAvailable(): bool
    {
        return $this->connect();
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Return the raw ODBC connection handle.
     * Used by SyncEpicorCache to stream large result sets directly.
     */
    public function getRawConnection(): mixed
    {
        if (!$this->connect()) {
            return null;
        }
        return $this->connection;
    }

    // =========================================================================
    // Internal helpers
    // =========================================================================

    private function query(string $sql): array
    {
        if (!$this->connect()) {
            return [];
        }
        try {
            $result = odbc_exec($this->connection, $sql);
            if (!$result) {
                $this->lastError = odbc_errormsg($this->connection);
                Log::error("EpicorService query failed: {$this->lastError} | SQL: {$sql}");
                return [];
            }
            $rows = [];
            while ($row = odbc_fetch_array($result)) {
                $rows[] = $row;
            }
            odbc_free_result($result);
            return $rows;
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            Log::error("EpicorService query exception: {$this->lastError}");
            return [];
        }
    }

    private function queryOne(string $sql): ?array
    {
        $rows = $this->query($sql);
        return $rows[0] ?? null;
    }

    private function execute(string $sql): bool
    {
        if (!$this->connect()) {
            return false;
        }
        try {
            $result = odbc_exec($this->connection, $sql);
            if (!$result) {
                $this->lastError = odbc_errormsg($this->connection);
                Log::error("EpicorService execute failed: {$this->lastError} | SQL: {$sql}");
                return false;
            }
            return true;
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            Log::error("EpicorService execute exception: {$this->lastError}");
            return false;
        }
    }

    private function escape(string $value): string
    {
        return str_replace("'", "''", $value);
    }

    private function fullTableName(string $tableName): string
    {
        return "Epicor101.{$tableName}";
    }

    private function columnList(): string
    {
        return implode(', ', self::VALVE_COLUMNS);
    }

    private function ignoredSerialClause(): string
    {
        $quoted = array_map(fn($s) => "'{$s}'", $this->ignoredSerialNumbers);
        return 'Key1 NOT IN (' . implode(',', $quoted) . ')';
    }

    // =========================================================================
    // Public API — reads
    // =========================================================================

    /**
     * Select valves directly from Epicor ODBC.
     *
     * Use this for single-record lookups (edit/create).
     * For list pages use ValveCacheService::selectValves() instead.
     */
    public function selectValves(
        string $epicorCompany,
        string $tableName,
        ?int $serialNumber = null,
        array $where = []
    ): array|EpicorValve|null {
        $table   = $this->fullTableName($tableName);
        $company = $this->escape($epicorCompany);
        $cols    = $this->columnList();

        $conditions = [
            "Company='{$company}'",
            "Key1 != ''",
            "Key1 != '0'",
            $this->ignoredSerialClause(),
        ];

        if ($serialNumber !== null) {
            $conditions[] = "Key1='" . $this->escape((string) $serialNumber) . "'";
        }
        foreach ($where as $clause) {
            $conditions[] = $clause;
        }

        $whereStr = implode(' AND ', $conditions);

        if ($serialNumber !== null) {
            $sql = "SELECT TOP 1 {$cols} FROM {$table} WHERE {$whereStr}";
            Log::debug("EpicorService::selectValves (single): {$sql}");
            $row = $this->queryOne($sql);
            return $row ? EpicorValve::fromArray($row) : null;
        }

        $sql = "SELECT TOP 100 {$cols} FROM {$table} WHERE {$whereStr} ORDER BY Date01 DESC";
        Log::debug("EpicorService::selectValves (list): {$sql}");
        $rows = $this->query($sql);
        return array_map(fn($row) => EpicorValve::fromArray($row), $rows);
    }

    /**
     * Get valve history for a user (today + yesterday) directly from Epicor.
     * For history on the loading list page, prefer ValveCacheService::selectValveHistory().
     */
    public function selectValveHistory(string $epicorCompany, string $tableName, string $username): array
    {
        $table     = $this->fullTableName($tableName);
        $company   = $this->escape($epicorCompany);
        $user      = $this->escape($username);
        $today     = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $cols      = $this->columnList();

        $sql = "SELECT TOP 100 {$cols} FROM {$table}
                WHERE Company='{$company}'
                  AND ShortChar02='{$user}'
                  AND Date01 IN ('{$today}', '{$yesterday}')
                ORDER BY Key1 DESC";

        $rows = $this->query($sql);
        return array_map(fn($row) => EpicorValve::fromArray($row), $rows);
    }

    /**
     * Get the next available serial number.
     */
    public function nextSerialNumber(string $epicorCompany, string $tableName): int
    {
        $table      = $this->fullTableName($tableName);
        $company    = $this->escape($epicorCompany);
        $ignoreList = implode(',', array_map(fn($s) => "'{$s}'", $this->ignoredSerialNumbers));

        $sql    = "SELECT MAX(CAST(Key1 AS bigint)) AS maxKey
                   FROM {$table}
                   WHERE Company='{$company}'
                     AND Key1 NOT IN ({$ignoreList})
                     AND ISNUMERIC(Key1) = 1";
        $result = $this->queryOne($sql);
        $next   = intval($result['maxKey'] ?? 0) + 1;

        while (in_array((string) $next, $this->ignoredSerialNumbers)) {
            $next++;
        }

        return $next;
    }

    // =========================================================================
    // Public API — writes
    // =========================================================================

    /**
     * Insert a new valve record into Epicor.
     */
    public function insertValve(string $tableName, array $data): bool
    {
        $table   = $this->fullTableName($tableName);
        $columns = [];
        $values  = [];

        foreach ($data as $col => $val) {
            $columns[] = $col;
            if (stripos($col, 'Date') !== false) {
                $values[] = ($val !== null && $val !== '') ? "'" . $this->escape((string) $val) . "'" : 'NULL';
            } elseif (stripos($col, 'Number') !== false) {
                $v        = ($val !== null && $val !== '') ? $this->escape((string) $val) : '0';
                $values[] = "CONVERT(int, '{$v}')";
            } elseif ($val === null || $val === '') {
                $values[] = "''";
            } else {
                $values[] = "'" . $this->escape((string) $val) . "'";
            }
        }

        $sql = "INSERT INTO {$table} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ")";
        Log::debug("EpicorService::insertValve: {$sql}");
        return $this->execute($sql);
    }

    /**
     * Update a valve record in Epicor.
     */
    public function updateValve(string $tableName, array $data, string $serialNumber): bool
    {
        $table = $this->fullTableName($tableName);
        $sets  = [];

        foreach ($data as $col => $val) {
            if ($col === 'Key1' || $col === 'Company') continue;

            if (stripos($col, 'Date') !== false) {
                if ($val !== null && $val !== '') {
                    $sets[] = "{$col}='" . $this->escape((string) $val) . "'";
                }
            } elseif (stripos($col, 'Number') !== false) {
                if ($val !== null && $val !== '') {
                    $sets[] = "{$col}=CONVERT(decimal(8,3), '" . $this->escape((string) $val) . "')";
                }
            } elseif (is_bool($val)) {
                $sets[] = "{$col}=" . intval($val);
            } elseif (is_int($val)) {
                $sets[] = "{$col}={$val}";
            } else {
                $sets[] = "{$col}='" . $this->escape((string) $val) . "'";
            }
        }

        if (empty($sets)) {
            return true;
        }

        $serial  = $this->escape($serialNumber);
        $company = $this->escape($data['Company'] ?? '');
        $sql     = "UPDATE {$table} SET " . implode(', ', $sets) . " WHERE Key1='{$serial}' AND Company='{$company}'";
        Log::debug("EpicorService::updateValve: {$sql}");
        return $this->execute($sql);
    }

    /**
     * Update the Company table's valve ID tracker in Epicor.
     */
    public function updateCompanyValveId(string $epicorCompany, int $valveId): bool
    {
        $company = $this->escape($epicorCompany);
        return $this->execute("UPDATE Epicor101.Erp.Company_UD SET Number03='{$valveId}' WHERE Company='{$company}'");
    }

    /**
     * Sum charge weights for a given year/month.
     */
    public function sumChargeWeight(string $epicorCompany, int $year, int $month): float
    {
        $company = $this->escape($epicorCompany);
        $result  = $this->queryOne(
            "SELECT SUM(Number01) AS chargeWeight FROM Epicor101.Ice.UD02
             WHERE Company='{$company}' AND YEAR(Date01)='{$year}' AND MONTH(Date01)='{$month}'"
        );
        return floatval($result['chargeWeight'] ?? 0);
    }

    /**
     * Look up a job head record in Epicor.
     */
    public function getJobHead(string $jobNumber, string $epicorCompany): ?array
    {
        $company = $this->escape($epicorCompany);
        $job     = $this->escape($jobNumber);
        return $this->queryOne(
            "SELECT TOP 1 PartNum, ProdQty FROM Epicor101.Erp.JobHead WHERE Company='{$company}' AND JobNum='{$job}'"
        );
    }

    // =========================================================================
    // Lifecycle
    // =========================================================================

    public function disconnect(): void
    {
        if ($this->connection) {
            odbc_close($this->connection);
            $this->connection = null;
            $this->connected  = false;
        }
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}
