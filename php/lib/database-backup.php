<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/db-records.php';

const DATABASE_BACKUP_ENTRY = 'database/dump.sql';
const DATABASE_SQLITE_ENTRY = 'database/site.sqlite';

function databaseTableNames(PDO $pdo): array
{
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'mysql') {
        $rows = $pdo->query('SHOW FULL TABLES WHERE Table_type = \'BASE TABLE\'')->fetchAll(PDO::FETCH_NUM);

        return array_values(array_map(static fn(array $row): string => (string) ($row[0] ?? ''), $rows));
    }

    $stmt = $pdo->query(
        "SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name"
    );

    return array_values(array_filter(array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [])));
}

function quoteSqlIdentifier(string $name, string $driver): string
{
    if ($driver === 'mysql') {
        return '`' . str_replace('`', '``', $name) . '`';
    }

    return '"' . str_replace('"', '""', $name) . '"';
}

function buildSqlInsert(PDO $pdo, string $table, array $row): string
{
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $columns = array_keys($row);
    $quotedColumns = array_map(static fn(string $col): string => quoteSqlIdentifier($col, $driver), $columns);
    $values = [];
    foreach ($columns as $column) {
        $value = $row[$column];
        $values[] = $value === null ? 'NULL' : $pdo->quote((string) $value);
    }

    return 'INSERT INTO ' . quoteSqlIdentifier($table, $driver)
        . ' (' . implode(', ', $quotedColumns) . ') VALUES (' . implode(', ', $values) . ');';
}

function exportDatabaseToSql(?PDO $pdo = null): string
{
    $pdo ??= db();
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $lines = [
        '-- Portfolio database export',
        '-- Driver: ' . $driver,
        '-- Generated: ' . date('c'),
        '',
    ];

    if ($driver === 'mysql') {
        $lines[] = 'SET FOREIGN_KEY_CHECKS=0;';
        $lines[] = 'SET NAMES utf8mb4;';
        $lines[] = '';
    }

    foreach (databaseTableNames($pdo) as $table) {
        $lines[] = '-- TABLE: ' . $table;
        $lines[] = 'DROP TABLE IF EXISTS ' . quoteSqlIdentifier($table, $driver) . ';';

        if ($driver === 'mysql') {
            $create = $pdo->query('SHOW CREATE TABLE ' . quoteSqlIdentifier($table, $driver))->fetch(PDO::FETCH_NUM);
            $lines[] = (string) ($create[1] ?? '') . ';';
        } else {
            $create = $pdo->query(
                'SELECT sql FROM sqlite_master WHERE type = \'table\' AND name = ' . $pdo->quote($table)
            )->fetchColumn();
            $lines[] = (string) $create . ';';
        }

        $rows = $pdo->query('SELECT * FROM ' . quoteSqlIdentifier($table, $driver))->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            if (is_array($row)) {
                $lines[] = buildSqlInsert($pdo, $table, $row);
            }
        }
        $lines[] = '';
    }

    if ($driver === 'mysql') {
        $lines[] = 'SET FOREIGN_KEY_CHECKS=1;';
    }

    return implode("\n", $lines);
}

function splitSqlStatements(string $sql): array
{
    $statements = [];
    $buffer = '';
    $inString = false;
    $stringChar = '';
    $length = strlen($sql);

    for ($i = 0; $i < $length; $i++) {
        $char = $sql[$i];
        $prev = $i > 0 ? $sql[$i - 1] : '';

        if (!$inString && ($char === '"' || $char === "'")) {
            $inString = true;
            $stringChar = $char;
        } elseif ($inString && $char === $stringChar && $prev !== '\\') {
            $inString = false;
            $stringChar = '';
        }

        if (!$inString && $char === ';') {
            $statement = trim($buffer);
            if ($statement !== '' && !str_starts_with($statement, '--')) {
                $statements[] = $statement;
            }
            $buffer = '';
            continue;
        }

        $buffer .= $char;
    }

    $tail = trim($buffer);
    if ($tail !== '' && !str_starts_with($tail, '--')) {
        $statements[] = $tail;
    }

    return $statements;
}

function importDatabaseSql(string $sql): void
{
    $pdo = db();
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver === 'mysql') {
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        $pdo->exec('SET NAMES utf8mb4');
    }

    foreach (splitSqlStatements($sql) as $statement) {
        $pdo->exec($statement);
    }

    if ($driver === 'mysql') {
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    }
}

function sqliteDatabasePath(): string
{
    $path = trim(configValue('DB_PATH', ''));
    if ($path !== '') {
        return $path;
    }

    return __DIR__ . '/../storage/database/site.sqlite';
}

function databaseBackupAvailable(): bool
{
    return databaseReady();
}

function databaseBackupMeta(): array
{
    $summary = dbDatabaseSummary();
    $driver = (string) ($summary['driver'] ?? 'sqlite');

    return [
        'ready' => !empty($summary['ready']),
        'driver' => $driver,
        'driver_label' => $driver === 'mysql' ? 'MySQL' : 'SQLite',
        'users' => (int) ($summary['users'] ?? 0),
        'products' => (int) ($summary['products'] ?? 0),
        'orders' => (int) ($summary['orders'] ?? 0),
        'contacts' => (int) ($summary['contacts'] ?? 0),
        'certifications' => (int) ($summary['certifications'] ?? 0),
        'password_storage' => 'Passwords are stored as secure hashes in the database — never plain text.',
        'config_hint' => $driver === 'mysql'
            ? 'MySQL is active. Set DB_DRIVER => mysql and credentials in config/local.php.'
            : 'SQLite file is used by default. Switch to MySQL in config/local.php and run scripts/setup-mysql.php.',
    ];
}
