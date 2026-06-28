<?php
declare(strict_types=1);

/**
 * Creates the MySQL database/user from config/local.php and runs site migrations.
 * Usage: php scripts/setup-mysql.php
 */
$root = dirname(__DIR__);
require_once $root . '/php/lib/bootstrap.php';
require_once $root . '/php/lib/config.php';

$driver = strtolower(trim(configValue('DB_DRIVER', 'sqlite')));
if ($driver !== 'mysql') {
    fwrite(STDERR, "Set DB_DRIVER => 'mysql' in config/local.php first.\n");
    exit(1);
}

$host = configValue('DB_HOST', '127.0.0.1');
$port = configValue('DB_PORT', '3306');
$name = configValue('DB_NAME', 'portfolio');
$user = configValue('DB_USER', '');
$pass = configValue('DB_PASSWORD', '');
$adminUser = configValue('DB_SETUP_USER', 'root');
$adminPass = configValue('DB_SETUP_PASSWORD', '');

if ($name === '' || $user === '') {
    fwrite(STDERR, "Set DB_NAME and DB_USER in config/local.php.\n");
    exit(1);
}

echo "=== MySQL setup for portfolio site ===\n";
echo "Host: {$host}:{$port}\n";
echo "Database: {$name}\n";
echo "App user: {$user}\n\n";

try {
    $adminDsn = 'mysql:host=' . $host . ';port=' . $port . ';charset=utf8mb4';
    $admin = new PDO($adminDsn, $adminUser, $adminPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    $admin->exec(
        'CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '``', $name) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
    );
    echo "Database ensured: {$name}\n";

    $safeUser = str_replace("'", "''", $user);
    $safePass = str_replace("'", "''", $pass);
    $admin->exec("CREATE USER IF NOT EXISTS '{$safeUser}'@'localhost' IDENTIFIED BY '{$safePass}'");
    $admin->exec('GRANT ALL PRIVILEGES ON `' . str_replace('`', '``', $name) . '`.* TO \'' . $safeUser . '\'@\'localhost\'');
    $admin->exec('FLUSH PRIVILEGES');
    echo "User granted: {$user}@localhost\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Admin connection failed: " . $e->getMessage() . "\n");
    fwrite(STDERR, "Create the database manually with scripts/setup-mysql-database.sql or fix DB_SETUP_USER/DB_SETUP_PASSWORD.\n");
    exit(1);
}

require_once $root . '/php/lib/database.php';
require_once $root . '/php/lib/db-records.php';

if (!databaseReady()) {
    fwrite(STDERR, "Site database connection failed after setup. Check DB_USER/DB_PASSWORD.\n");
    exit(1);
}

$summary = dbDatabaseSummary();
echo "\nConnected successfully.\n";
echo 'Driver: ' . ($summary['driver'] ?? 'mysql') . "\n";
echo 'Users in database: ' . (int) ($summary['users'] ?? 0) . "\n";
echo "\nNext steps:\n";
echo "1. Log in to admin and change password (stored as hash in MySQL).\n";
echo "2. Remove ADMIN_PASSWORD from config/local.php when database login works.\n";
echo "3. Use Backup & Data → Site + database for full backups.\n";
echo "\nDone.\n";
