<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/php/lib/bootstrap.php';
require_once $root . '/php/lib/database.php';
require_once $root . '/php/lib/db-records.php';
require_once $root . '/php/lib/database-backup.php';
require_once $root . '/php/lib/backup-services.php';

echo "=== Database & backup check ===\n\n";
echo 'PHP: ' . PHP_VERSION . "\n";
echo 'pdo_mysql: ' . (extension_loaded('pdo_mysql') ? 'YES' : 'NO') . "\n";
echo 'pdo_sqlite: ' . (extension_loaded('pdo_sqlite') ? 'YES' : 'NO') . "\n";
echo 'zip: ' . (extension_loaded('zip') ? 'YES' : 'NO') . "\n";
echo 'DB_DRIVER config: ' . configValue('DB_DRIVER', 'sqlite') . "\n";

$ready = databaseReady();
echo 'databaseReady(): ' . ($ready ? 'YES' : 'NO') . "\n";

if ($ready) {
    $s = dbDatabaseSummary();
    echo 'Driver: ' . ($s['driver'] ?? '') . "\n";
    echo 'Database name: ' . ($s['name'] ?? '') . "\n";
    echo 'Users: ' . (int) ($s['users'] ?? 0) . "\n";
    echo 'Products: ' . (int) ($s['products'] ?? 0) . "\n";
    echo 'Orders: ' . (int) ($s['orders'] ?? 0) . "\n";

    $user = db()->query('SELECT username, role, active FROM users LIMIT 3')->fetchAll(PDO::FETCH_ASSOC);
    echo 'Sample users: ' . json_encode($user) . "\n";

    $hash = db()->query('SELECT password_hash FROM users LIMIT 1')->fetchColumn();
    $isHash = is_string($hash) && str_starts_with($hash, '$');
    echo 'Password stored as hash: ' . ($isHash ? 'YES (bcrypt)' : 'NO') . "\n";

    $dumpLen = strlen(exportDatabaseToSql());
    echo 'SQL export test: OK (' . $dumpLen . ' bytes)' . "\n";
} else {
    echo "Database error — check php/storage/logs or enable pdo_sqlite / MySQL config.\n";
}

echo 'backupZipAvailable: ' . (backupZipAvailable() ? 'YES' : 'NO') . "\n";
echo 'databaseBackupAvailable: ' . (databaseBackupAvailable() ? 'YES' : 'NO') . "\n";

// MySQL connectivity probe (without switching driver)
$host = configValue('DB_HOST', '127.0.0.1');
$port = configValue('DB_PORT', '3306');
$name = configValue('DB_NAME', 'portfolio');
$user = configValue('DB_USER', '');
$pass = configValue('DB_PASSWORD', '');
if ($user !== '') {
    try {
        $pdo = new PDO(
            'mysql:host=' . $host . ';port=' . $port . ';dbname=' . $name . ';charset=utf8mb4',
            $user,
            $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        echo "MySQL direct connect ({$user}@{$host}/{$name}): YES\n";
    } catch (Throwable $e) {
        echo "MySQL direct connect: FAILED — " . $e->getMessage() . "\n";
    }
} else {
    echo "MySQL direct connect: skipped (DB_USER empty in config/local.php)\n";
    try {
        $pdo = new PDO('mysql:host=' . $host . ';port=' . $port . ';charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        echo "MySQL server reachable (root, no password): YES\n";
    } catch (Throwable $e) {
        echo "MySQL server probe: " . $e->getMessage() . "\n";
    }
}

echo "\nDone.\n";
