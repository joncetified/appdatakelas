<?php

declare(strict_types=1);

if ($argc < 3) {
    fwrite(STDERR, "Usage: php scripts/import_mysql_dump_to_sqlite.php <dump.sql> <database.sqlite>\n");
    exit(1);
}

$dumpPath = $argv[1];
$sqlitePath = $argv[2];

if (! is_file($dumpPath)) {
    fwrite(STDERR, "SQL dump not found: {$dumpPath}\n");
    exit(1);
}

if (! is_file($sqlitePath)) {
    fwrite(STDERR, "SQLite database not found: {$sqlitePath}\n");
    exit(1);
}

$skipTables = [
    'migrations' => true,
    'permissions' => true,
    'role_permissions' => true,
];

$pdo = new PDO('sqlite:' . $sqlitePath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$handle = fopen($dumpPath, 'rb');

if ($handle === false) {
    fwrite(STDERR, "Unable to open SQL dump: {$dumpPath}\n");
    exit(1);
}

$imported = 0;
$skipped = 0;
$statement = '';
$collectingInsert = false;
$clearedTables = [];

$pdo->exec('PRAGMA foreign_keys = OFF');
$pdo->beginTransaction();

try {
    while (($line = fgets($handle)) !== false) {
        $trimmed = ltrim($line);

        if (! $collectingInsert) {
            if (! str_starts_with($trimmed, 'INSERT INTO ')) {
                continue;
            }

            $statement = $line;
            $collectingInsert = true;
        } else {
            $statement .= $line;
        }

        if (! preg_match('/;\s*$/', rtrim($line))) {
            continue;
        }

        $collectingInsert = false;

        if (! preg_match('/INSERT INTO\s+`?([^`\s]+)`?/i', $statement, $matches)) {
            $statement = '';
            continue;
        }

        $table = strtolower($matches[1]);

        if (isset($skipTables[$table])) {
            $skipped++;
            $statement = '';
            continue;
        }

        if (! isset($clearedTables[$table])) {
            $pdo->exec(sprintf('DELETE FROM "%s"', str_replace('"', '""', $table)));
            $clearedTables[$table] = true;
        }

        $pdo->exec($statement);
        $imported++;
        $statement = '';
    }

    $pdo->commit();
    $pdo->exec('PRAGMA foreign_keys = ON');
} catch (Throwable $e) {
    $pdo->rollBack();
    $pdo->exec('PRAGMA foreign_keys = ON');
    fclose($handle);
    fwrite(STDERR, "Import failed: {$e->getMessage()}\n");
    exit(1);
}

fclose($handle);

fwrite(STDOUT, "Imported {$imported} INSERT statements, skipped {$skipped}.\n");
