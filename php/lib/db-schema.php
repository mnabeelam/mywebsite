<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/site-settings.php';

function runDatabaseMigrations(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }

    createDatabaseTables($pdo);
    $version = (int) dbSetting($pdo, 'schema_version', '0');
    if ($version < 1) {
        importLegacyJsonData($pdo);
        dbSetSetting($pdo, 'schema_version', '1');
    }

    $done = true;
}

function createDatabaseTables(PDO $pdo): void
{
    $statements = [
        'CREATE TABLE IF NOT EXISTS site_settings (
            setting_key TEXT PRIMARY KEY,
            setting_value TEXT NOT NULL
        )',
        'CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            display_name TEXT NOT NULL DEFAULT "",
            email TEXT NOT NULL DEFAULT "",
            role TEXT NOT NULL DEFAULT "viewer",
            active INTEGER NOT NULL DEFAULT 1,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL,
            last_login_at TEXT
        )',
        'CREATE TABLE IF NOT EXISTS contact_messages (
            id TEXT PRIMARY KEY,
            name TEXT NOT NULL,
            email TEXT NOT NULL,
            service TEXT NOT NULL DEFAULT "",
            message TEXT NOT NULL,
            ip TEXT NOT NULL DEFAULT "",
            created_at TEXT NOT NULL
        )',
        'CREATE TABLE IF NOT EXISTS shop_products (
            id TEXT PRIMARY KEY,
            brand TEXT NOT NULL DEFAULT "",
            name TEXT NOT NULL,
            description TEXT NOT NULL DEFAULT "",
            price REAL NOT NULL DEFAULT 0,
            cost_price REAL NOT NULL DEFAULT 0,
            currency TEXT NOT NULL DEFAULT "PKR",
            category TEXT NOT NULL DEFAULT "",
            track_stock INTEGER NOT NULL DEFAULT 0,
            stock INTEGER NOT NULL DEFAULT 0,
            active INTEGER NOT NULL DEFAULT 0,
            images_json TEXT NOT NULL DEFAULT "[]",
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )',
        'CREATE TABLE IF NOT EXISTS shop_orders (
            id TEXT PRIMARY KEY,
            product_id TEXT NOT NULL,
            product_name TEXT NOT NULL DEFAULT "",
            quantity INTEGER NOT NULL DEFAULT 1,
            unit_price REAL NOT NULL DEFAULT 0,
            total REAL NOT NULL DEFAULT 0,
            currency TEXT NOT NULL DEFAULT "PKR",
            customer_name TEXT NOT NULL,
            customer_email TEXT NOT NULL,
            customer_phone TEXT NOT NULL DEFAULT "",
            address TEXT NOT NULL DEFAULT "",
            notes TEXT NOT NULL DEFAULT "",
            status TEXT NOT NULL DEFAULT "pending",
            ip TEXT NOT NULL DEFAULT "",
            created_at TEXT NOT NULL,
            updated_at TEXT
        )',
        'CREATE TABLE IF NOT EXISTS shop_ledger (
            id TEXT PRIMARY KEY,
            type TEXT NOT NULL,
            product_id TEXT NOT NULL DEFAULT "",
            brand TEXT NOT NULL DEFAULT "",
            product_name TEXT NOT NULL DEFAULT "",
            quantity INTEGER NOT NULL DEFAULT 0,
            unit_cost REAL NOT NULL DEFAULT 0,
            unit_price REAL NOT NULL DEFAULT 0,
            total_cost REAL NOT NULL DEFAULT 0,
            total_sale REAL NOT NULL DEFAULT 0,
            profit REAL NOT NULL DEFAULT 0,
            currency TEXT NOT NULL DEFAULT "PKR",
            reference TEXT NOT NULL DEFAULT "",
            note TEXT NOT NULL DEFAULT "",
            created_at TEXT NOT NULL
        )',
        'CREATE TABLE IF NOT EXISTS certifications (
            id TEXT PRIMARY KEY,
            title TEXT NOT NULL,
            issuer TEXT NOT NULL DEFAULT "",
            year TEXT NOT NULL DEFAULT "",
            knowledge TEXT NOT NULL DEFAULT "",
            description TEXT NOT NULL DEFAULT "",
            file_path TEXT NOT NULL DEFAULT "",
            active INTEGER NOT NULL DEFAULT 1,
            sort_order INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )',
        'CREATE INDEX IF NOT EXISTS idx_shop_orders_created ON shop_orders(created_at DESC)',
        'CREATE INDEX IF NOT EXISTS idx_contact_messages_created ON contact_messages(created_at DESC)',
        'CREATE INDEX IF NOT EXISTS idx_users_role ON users(role)',
    ];

    foreach ($statements as $sql) {
        $pdo->exec($sql);
    }

    seedInitialAdminUser($pdo);
}

function dbSetting(PDO $pdo, string $key, string $default = ''): string
{
    $stmt = $pdo->prepare('SELECT setting_value FROM site_settings WHERE setting_key = :key LIMIT 1');
    $stmt->execute(['key' => $key]);
    $value = $stmt->fetchColumn();

    return $value === false ? $default : (string) $value;
}

function dbSetSetting(PDO $pdo, string $key, string $value): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO site_settings (setting_key, setting_value) VALUES (:key, :value)
         ON CONFLICT(setting_key) DO UPDATE SET setting_value = excluded.setting_value'
    );
    $stmt->execute(['key' => $key, 'value' => $value]);
}

function seedInitialAdminUser(PDO $pdo): void
{
    $count = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    if ($count > 0) {
        return;
    }

    $username = '';
    $hash = '';

    $stored = loadStoredAdminAuth();
    if ($stored !== null) {
        $username = $stored['username'];
        $hash = $stored['password_hash'];
    } else {
        $username = trim(configValue('ADMIN_USERNAME'));
        $hash = trim(configValue('ADMIN_PASSWORD_HASH'));
        if ($hash === '') {
            $plain = configValue('ADMIN_PASSWORD');
            if ($username !== '' && $plain !== '') {
                $hash = password_hash($plain, PASSWORD_DEFAULT);
            }
        }
    }

    if ($username === '' || $hash === '') {
        return;
    }

    $now = date('c');
    $stmt = $pdo->prepare(
        'INSERT INTO users (username, password_hash, display_name, email, role, active, created_at, updated_at)
         VALUES (:username, :password_hash, :display_name, :email, :role, 1, :created_at, :updated_at)'
    );
    $stmt->execute([
        'username' => $username,
        'password_hash' => $hash,
        'display_name' => $username,
        'email' => trim(configValue('CONTACT_EMAIL')),
        'role' => 'super_admin',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    appLog('database: seeded initial super admin user "' . $username . '"');
}

function importLegacyJsonData(PDO $pdo): void
{
    importSiteSettingsJson($pdo);
    importContactMessagesJson($pdo);
    importShopCatalogJson($pdo);
    importShopOrdersJson($pdo);
    importShopLedgerJson($pdo);
    importCertificationsJson($pdo);
    appLog('database: legacy JSON data imported');
}

function importSiteSettingsJson(PDO $pdo): void
{
    $settings = loadSiteSettings();
    foreach ($settings as $key => $value) {
        if ($key === 'updated' || !is_scalar($value)) {
            continue;
        }
        dbSetSetting($pdo, (string) $key, (string) $value);
    }
}

function importContactMessagesJson(PDO $pdo): void
{
    $dir = __DIR__ . '/../storage/contact-messages';
    if (!is_dir($dir)) {
        return;
    }

    $files = glob($dir . '/msg_*.json') ?: [];
    $stmt = $pdo->prepare(
        'INSERT OR IGNORE INTO contact_messages (id, name, email, service, message, ip, created_at)
         VALUES (:id, :name, :email, :service, :message, :ip, :created_at)'
    );

    foreach ($files as $file) {
        $data = json_decode((string) file_get_contents($file), true);
        if (!is_array($data)) {
            continue;
        }
        $id = basename($file, '.json');
        $stmt->execute([
            'id' => $id,
            'name' => sanitizeText((string) ($data['name'] ?? ''), 120),
            'email' => sanitizeText((string) ($data['email'] ?? ''), 180),
            'service' => sanitizeText((string) ($data['service'] ?? ''), 120),
            'message' => sanitizeText((string) ($data['message'] ?? ''), 3000),
            'ip' => sanitizeText((string) ($data['ip'] ?? ''), 80),
            'created_at' => (string) ($data['created'] ?? date('c')),
        ]);
    }
}

function importShopCatalogJson(PDO $pdo): void
{
    $path = __DIR__ . '/../storage/shop/products.json';
    if (!is_readable($path)) {
        return;
    }

    $data = json_decode((string) file_get_contents($path), true);
    if (!is_array($data) || !is_array($data['products'] ?? null)) {
        return;
    }

    $stmt = $pdo->prepare(
        'INSERT OR IGNORE INTO shop_products
         (id, brand, name, description, price, cost_price, currency, category, track_stock, stock, active, images_json, created_at, updated_at)
         VALUES
         (:id, :brand, :name, :description, :price, :cost_price, :currency, :category, :track_stock, :stock, :active, :images_json, :created_at, :updated_at)'
    );

    foreach ($data['products'] as $product) {
        if (!is_array($product)) {
            continue;
        }
        $images = $product['images'] ?? [];
        if (!is_array($images)) {
            $images = [];
        }
        if (empty($images) && !empty($product['image'])) {
            $images = [(string) $product['image']];
        }

        $stmt->execute([
            'id' => (string) ($product['id'] ?? ''),
            'brand' => sanitizeText((string) ($product['brand'] ?? ''), 80),
            'name' => sanitizeText((string) ($product['name'] ?? ''), 200),
            'description' => sanitizeText((string) ($product['description'] ?? ''), 2000),
            'price' => (float) ($product['price'] ?? 0),
            'cost_price' => (float) ($product['cost_price'] ?? 0),
            'currency' => sanitizeText((string) ($product['currency'] ?? 'PKR'), 8),
            'category' => sanitizeText((string) ($product['category'] ?? ''), 80),
            'track_stock' => !empty($product['track_stock']) ? 1 : 0,
            'stock' => (int) ($product['stock'] ?? 0),
            'active' => !empty($product['active']) ? 1 : 0,
            'images_json' => json_encode(array_values($images)),
            'created_at' => (string) ($product['created'] ?? date('c')),
            'updated_at' => (string) ($product['updated'] ?? date('c')),
        ]);
    }
}

function importShopOrdersJson(PDO $pdo): void
{
    $dir = __DIR__ . '/../storage/shop/orders';
    if (!is_dir($dir)) {
        return;
    }

    $files = glob($dir . '/ord_*.json') ?: [];
    $stmt = $pdo->prepare(
        'INSERT OR IGNORE INTO shop_orders
         (id, product_id, product_name, quantity, unit_price, total, currency, customer_name, customer_email, customer_phone, address, notes, status, ip, created_at, updated_at)
         VALUES
         (:id, :product_id, :product_name, :quantity, :unit_price, :total, :currency, :customer_name, :customer_email, :customer_phone, :address, :notes, :status, :ip, :created_at, :updated_at)'
    );

    foreach ($files as $file) {
        $data = json_decode((string) file_get_contents($file), true);
        if (!is_array($data)) {
            continue;
        }
        $stmt->execute([
            'id' => (string) ($data['id'] ?? basename($file, '.json')),
            'product_id' => sanitizeText((string) ($data['product_id'] ?? ''), 40),
            'product_name' => sanitizeText((string) ($data['product_name'] ?? ''), 200),
            'quantity' => (int) ($data['quantity'] ?? 1),
            'unit_price' => (float) ($data['unit_price'] ?? 0),
            'total' => (float) ($data['total'] ?? 0),
            'currency' => sanitizeText((string) ($data['currency'] ?? 'PKR'), 8),
            'customer_name' => sanitizeText((string) ($data['customer_name'] ?? ''), 120),
            'customer_email' => sanitizeText((string) ($data['customer_email'] ?? ''), 180),
            'customer_phone' => sanitizeText((string) ($data['customer_phone'] ?? ''), 40),
            'address' => sanitizeText((string) ($data['address'] ?? ''), 500),
            'notes' => sanitizeText((string) ($data['notes'] ?? ''), 1000),
            'status' => sanitizeText((string) ($data['status'] ?? 'pending'), 20),
            'ip' => sanitizeText((string) ($data['ip'] ?? ''), 80),
            'created_at' => (string) ($data['created'] ?? date('c')),
            'updated_at' => (string) ($data['updated'] ?? ''),
        ]);
    }
}

function importShopLedgerJson(PDO $pdo): void
{
    $path = __DIR__ . '/../storage/shop/ledger.json';
    if (!is_readable($path)) {
        return;
    }

    $data = json_decode((string) file_get_contents($path), true);
    if (!is_array($data) || !is_array($data['entries'] ?? null)) {
        return;
    }

    $stmt = $pdo->prepare(
        'INSERT OR IGNORE INTO shop_ledger
         (id, type, product_id, brand, product_name, quantity, unit_cost, unit_price, total_cost, total_sale, profit, currency, reference, note, created_at)
         VALUES
         (:id, :type, :product_id, :brand, :product_name, :quantity, :unit_cost, :unit_price, :total_cost, :total_sale, :profit, :currency, :reference, :note, :created_at)'
    );

    foreach ($data['entries'] as $entry) {
        if (!is_array($entry)) {
            continue;
        }
        $stmt->execute([
            'id' => sanitizeText((string) ($entry['id'] ?? ''), 40),
            'type' => sanitizeText((string) ($entry['type'] ?? 'adjustment'), 20),
            'product_id' => sanitizeText((string) ($entry['product_id'] ?? ''), 40),
            'brand' => sanitizeText((string) ($entry['brand'] ?? ''), 80),
            'product_name' => sanitizeText((string) ($entry['product_name'] ?? ''), 200),
            'quantity' => (int) ($entry['quantity'] ?? 0),
            'unit_cost' => (float) ($entry['unit_cost'] ?? 0),
            'unit_price' => (float) ($entry['unit_price'] ?? 0),
            'total_cost' => (float) ($entry['total_cost'] ?? 0),
            'total_sale' => (float) ($entry['total_sale'] ?? 0),
            'profit' => (float) ($entry['profit'] ?? 0),
            'currency' => sanitizeText((string) ($entry['currency'] ?? 'PKR'), 8),
            'reference' => sanitizeText((string) ($entry['reference'] ?? ''), 80),
            'note' => sanitizeText((string) ($entry['note'] ?? ''), 500),
            'created_at' => (string) ($entry['created'] ?? date('c')),
        ]);
    }
}

function importCertificationsJson(PDO $pdo): void
{
    $path = __DIR__ . '/../storage/certifications/catalog.json';
    if (!is_readable($path)) {
        return;
    }

    $data = json_decode((string) file_get_contents($path), true);
    if (!is_array($data) || !is_array($data['certifications'] ?? null)) {
        return;
    }

    $stmt = $pdo->prepare(
        'INSERT OR IGNORE INTO certifications
         (id, title, issuer, year, knowledge, description, file_path, active, sort_order, created_at, updated_at)
         VALUES
         (:id, :title, :issuer, :year, :knowledge, :description, :file_path, :active, :sort_order, :created_at, :updated_at)'
    );

    foreach ($data['certifications'] as $cert) {
        if (!is_array($cert)) {
            continue;
        }
        $stmt->execute([
            'id' => sanitizeText((string) ($cert['id'] ?? ''), 40),
            'title' => sanitizeText((string) ($cert['title'] ?? ''), 200),
            'issuer' => sanitizeText((string) ($cert['issuer'] ?? ''), 200),
            'year' => sanitizeText((string) ($cert['year'] ?? ''), 20),
            'knowledge' => sanitizeText((string) ($cert['knowledge'] ?? ''), 1000),
            'description' => sanitizeText((string) ($cert['description'] ?? ''), 1000),
            'file_path' => sanitizeText((string) ($cert['file_path'] ?? ''), 300),
            'active' => !empty($cert['active']) ? 1 : 0,
            'sort_order' => (int) ($cert['sort_order'] ?? 0),
            'created_at' => (string) ($cert['created'] ?? date('c')),
            'updated_at' => (string) ($cert['updated'] ?? date('c')),
        ]);
    }
}
