<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

final class Database
{
    private static ?self $instance = null;
    private ?PDO $pdo = null;
    private bool $ready = false;

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function pdo(): PDO
    {
        if ($this->pdo === null) {
            $this->connect();
        }

        return $this->pdo;
    }

    public function isReady(): bool
    {
        if ($this->pdo === null) {
            try {
                $this->connect();
            } catch (Throwable $e) {
                appLog('database: ' . $e->getMessage());
                return false;
            }
        }

        return $this->ready;
    }

    private function connect(): void
    {
        $driver = strtolower(trim(configValue('DB_DRIVER', 'sqlite')));
        if ($driver === '') {
            $driver = 'sqlite';
        }

        if ($driver === 'mysql') {
            $host = configValue('DB_HOST', '127.0.0.1');
            $port = configValue('DB_PORT', '3306');
            $name = configValue('DB_NAME', 'portfolio');
            $user = configValue('DB_USER', '');
            $pass = configValue('DB_PASSWORD', '');
            if ($name === '' || $user === '') {
                throw new RuntimeException('MySQL requires DB_NAME and DB_USER in config/local.php.');
            }
            $dsn = 'mysql:host=' . $host . ';port=' . $port . ';dbname=' . $name . ';charset=utf8mb4';
            $this->pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } else {
            $path = trim(configValue('DB_PATH', ''));
            if ($path === '') {
                $dir = __DIR__ . '/../storage/database';
                if (!is_dir($dir)) {
                    mkdir($dir, 0700, true);
                }
                $path = $dir . '/site.sqlite';
            }
            $this->pdo = new PDO('sqlite:' . $path, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            $this->pdo->exec('PRAGMA foreign_keys = ON');
        }

        require_once __DIR__ . '/db-schema.php';
        runDatabaseMigrations($this->pdo);
        $this->ready = true;
    }
}

function database(): Database
{
    return Database::instance();
}

function db(): PDO
{
    return database()->pdo();
}

function databaseReady(): bool
{
    return database()->isReady();
}
