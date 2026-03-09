<?php
$conn = odbc_connect("EpicorMSSQL", "epdb", "112143xc99");
if (!$conn) { die("Connect failed: " . odbc_errormsg()); }

// 1. Total row count for company 10
$t = microtime(true);
$r = odbc_exec($conn, "SELECT COUNT(*) AS cnt FROM Epicor101.Ice.UD01 WHERE Company='10'");
$row = odbc_fetch_array($r);
echo "Row count (company=10): " . $row['cnt'] . "  Time: " . round(microtime(true)-$t,3) . "s\n";

// 2. Total rows in entire table
$t = microtime(true);
$r = odbc_exec($conn, "SELECT COUNT(*) AS cnt FROM Epicor101.Ice.UD01");
$row = odbc_fetch_array($r);
echo "Total rows in UD01: " . $row['cnt'] . "  Time: " . round(microtime(true)-$t,3) . "s\n";

// 3. What indexes exist on UD01?
$r = odbc_exec($conn, "
    SELECT i.name AS index_name, i.type_desc, i.is_unique,
           STRING_AGG(c.name, ', ') WITHIN GROUP (ORDER BY ic.key_ordinal) AS columns
    FROM sys.indexes i
    JOIN sys.index_columns ic ON i.object_id = ic.object_id AND i.index_id = ic.index_id
    JOIN sys.columns c ON ic.object_id = c.object_id AND ic.column_id = c.column_id
    JOIN sys.tables t ON i.object_id = t.object_id
    WHERE t.name = 'UD01'
    GROUP BY i.name, i.type_desc, i.is_unique
    ORDER BY i.type_desc DESC
");
echo "\nIndexes on UD01:\n";
while ($row = odbc_fetch_array($r)) {
    echo "  [{$row['type_desc']}] {$row['index_name']} ({$row['columns']})" . ($row['is_unique'] ? ' UNIQUE' : '') . "\n";
}

// 4. Test: query filtered by Key1 range (recent high serial numbers) - uses clustered index
$t = microtime(true);
$r = odbc_exec($conn, "SELECT MAX(CAST(Key1 AS bigint)) AS maxKey FROM Epicor101.Ice.UD01 WHERE Company='10' AND ISNUMERIC(Key1)=1");
$row = odbc_fetch_array($r);
$maxKey = intval($row['maxKey']);
echo "\nMax Key1 (numeric) for company 10: $maxKey  Time: " . round(microtime(true)-$t,3) . "s\n";

// 5. Query recent 100 records via Key1 range (clustered index friendly)
$minKey = $maxKey - 5000; // look at last 5000 IDs
$t = microtime(true);
$r = odbc_exec($conn, "SELECT TOP 100 * FROM Epicor101.Ice.UD01 WHERE Company='10' AND CAST(Key1 AS bigint) > $minKey ORDER BY CAST(Key1 AS bigint) DESC");
$c = 0; while (odbc_fetch_array($r)) $c++;
echo "Recent 100 via Key1 range ($minKey-$maxKey): Rows=$c  Time: " . round(microtime(true)-$t,3) . "s\n";

// 6. Can we create an index? (test permissions)
$r = @odbc_exec($conn, "
    IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name='IX_UD01_Company_Date01' AND object_id=OBJECT_ID('Epicor101.Ice.UD01'))
    CREATE NONCLUSTERED INDEX IX_UD01_Company_Date01 ON Epicor101.Ice.UD01 (Company, Date01 DESC)
");
if ($r) {
    echo "\nIndex IX_UD01_Company_Date01 created or already exists!\n";
} else {
    echo "\nCannot create index: " . odbc_errormsg($conn) . "\n";
}

// 7. Re-test query after potential index creation
$t = microtime(true);
$r = odbc_exec($conn, "SELECT TOP 100 * FROM Epicor101.Ice.UD01 WHERE Company='10' AND Key1 != '' AND Key1 != '0' ORDER BY Date01 DESC");
$c = 0; while (odbc_fetch_array($r)) $c++;
echo "\nAfter index attempt - Date01 ORDER BY: Rows=$c  Time: " . round(microtime(true)-$t,3) . "s\n";
