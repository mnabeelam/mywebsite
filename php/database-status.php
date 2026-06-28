<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/database.php';
require_once __DIR__ . '/lib/db-records.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$extensions = [
    'pdo_mysql' => extension_loaded('pdo_mysql'),
    'pdo_sqlite' => extension_loaded('pdo_sqlite'),
    'zip' => extension_loaded('zip'),
];

$ready = databaseReady();
$db = $ready ? dbDatabaseSummary() : ['ready' => false];
$passwordHashed = false;
if ($ready) {
    $hash = (string) db()->query('SELECT password_hash FROM users LIMIT 1')->fetchColumn();
    $passwordHashed = $hash !== '' && str_starts_with($hash, '$');
}

jsonResponse([
    'status' => 'ok',
    'php' => PHP_VERSION,
    'php_ini' => php_ini_loaded_file() ?: '',
    'extensions' => $extensions,
    'config_driver' => configValue('DB_DRIVER', 'sqlite'),
    'database_ready' => $ready,
    'database' => $db,
    'password_stored_as_hash' => $passwordHashed,
    'backup_zip' => class_exists('ZipArchive'),
    'mysql_configured' => trim(configValue('DB_USER', '')) !== '',
]);
