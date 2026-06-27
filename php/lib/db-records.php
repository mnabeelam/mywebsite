<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';

function dbLoadSiteSettingsMap(): array
{
    if (!databaseReady()) {
        return [];
    }

    $rows = db()->query('SELECT setting_key, setting_value FROM site_settings')->fetchAll();
    $settings = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $settings[(string) $row['setting_key']] = (string) $row['setting_value'];
    }

    return $settings;
}

function dbSaveSiteSettingsMap(array $settings): void
{
    if (!databaseReady()) {
        return;
    }

    $stmt = db()->prepare(
        'INSERT INTO site_settings (setting_key, setting_value) VALUES (:key, :value)
         ON CONFLICT(setting_key) DO UPDATE SET setting_value = excluded.setting_value'
    );

    foreach ($settings as $key => $value) {
        if (!is_scalar($value)) {
            continue;
        }
        $stmt->execute([
            'key' => (string) $key,
            'value' => (string) $value,
        ]);
    }
}

function dbSaveContactMessageRecord(array $payload, string $id): void
{
    if (!databaseReady()) {
        return;
    }

    $stmt = db()->prepare(
        'INSERT INTO contact_messages (id, name, email, service, message, ip, created_at)
         VALUES (:id, :name, :email, :service, :message, :ip, :created_at)'
    );
    $stmt->execute([
        'id' => $id,
        'name' => sanitizeText((string) ($payload['name'] ?? ''), 120),
        'email' => sanitizeText((string) ($payload['email'] ?? ''), 180),
        'service' => sanitizeText((string) ($payload['service'] ?? ''), 120),
        'message' => sanitizeText((string) ($payload['message'] ?? ''), 3000),
        'ip' => sanitizeText((string) ($payload['ip'] ?? ''), 80),
        'created_at' => (string) ($payload['created'] ?? date('c')),
    ]);
}

function dbListContactMessages(int $limit = 20): array
{
    if (!databaseReady()) {
        return [];
    }

    $stmt = db()->prepare('SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT :limit');
    $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();
    $messages = [];

    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $messages[] = [
            'name' => (string) ($row['name'] ?? ''),
            'email' => (string) ($row['email'] ?? ''),
            'service' => (string) ($row['service'] ?? ''),
            'message' => (string) ($row['message'] ?? ''),
            'ip' => (string) ($row['ip'] ?? ''),
            'created' => (string) ($row['created_at'] ?? ''),
        ];
    }

    return $messages;
}

function dbContactMessageCount(): int
{
    if (!databaseReady()) {
        return 0;
    }

    return (int) db()->query('SELECT COUNT(*) FROM contact_messages')->fetchColumn();
}

function dbProductRowToRecord(array $row): array
{
    $images = json_decode((string) ($row['images_json'] ?? '[]'), true);
    if (!is_array($images)) {
        $images = [];
    }

    return [
        'id' => (string) ($row['id'] ?? ''),
        'brand' => (string) ($row['brand'] ?? ''),
        'name' => (string) ($row['name'] ?? ''),
        'description' => (string) ($row['description'] ?? ''),
        'price' => (float) ($row['price'] ?? 0),
        'cost_price' => (float) ($row['cost_price'] ?? 0),
        'currency' => (string) ($row['currency'] ?? 'PKR'),
        'category' => (string) ($row['category'] ?? ''),
        'track_stock' => !empty($row['track_stock']),
        'stock' => (int) ($row['stock'] ?? 0),
        'active' => !empty($row['active']),
        'images' => $images,
        'image' => $images[0] ?? 'assets/shop/placeholder.svg',
        'created' => (string) ($row['created_at'] ?? ''),
        'updated' => (string) ($row['updated_at'] ?? ''),
    ];
}

function dbLoadShopProducts(): array
{
    if (!databaseReady()) {
        return [];
    }

    $rows = db()->query('SELECT * FROM shop_products ORDER BY name ASC')->fetchAll();
    $products = [];
    foreach ($rows as $row) {
        if (is_array($row)) {
            $products[] = dbProductRowToRecord($row);
        }
    }

    return $products;
}

function dbSaveShopProductRecord(array $product): void
{
    if (!databaseReady()) {
        return;
    }

    $images = $product['images'] ?? [];
    if (!is_array($images)) {
        $images = [];
    }
    if ($images === [] && !empty($product['image'])) {
        $images = [(string) $product['image']];
    }

    $stmt = db()->prepare(
        'INSERT INTO shop_products
         (id, brand, name, description, price, cost_price, currency, category, track_stock, stock, active, images_json, created_at, updated_at)
         VALUES
         (:id, :brand, :name, :description, :price, :cost_price, :currency, :category, :track_stock, :stock, :active, :images_json, :created_at, :updated_at)
         ON CONFLICT(id) DO UPDATE SET
           brand = excluded.brand,
           name = excluded.name,
           description = excluded.description,
           price = excluded.price,
           cost_price = excluded.cost_price,
           currency = excluded.currency,
           category = excluded.category,
           track_stock = excluded.track_stock,
           stock = excluded.stock,
           active = excluded.active,
           images_json = excluded.images_json,
           updated_at = excluded.updated_at'
    );

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

function dbDeleteShopProduct(string $productId): void
{
    if (!databaseReady() || $productId === '') {
        return;
    }

    $stmt = db()->prepare('DELETE FROM shop_products WHERE id = :id');
    $stmt->execute(['id' => $productId]);
}

function dbSaveShopOrderRecord(array $payload): void
{
    if (!databaseReady()) {
        return;
    }

    $stmt = db()->prepare(
        'INSERT INTO shop_orders
         (id, product_id, product_name, quantity, unit_price, total, currency, customer_name, customer_email, customer_phone, address, notes, status, ip, created_at, updated_at)
         VALUES
         (:id, :product_id, :product_name, :quantity, :unit_price, :total, :currency, :customer_name, :customer_email, :customer_phone, :address, :notes, :status, :ip, :created_at, :updated_at)
         ON CONFLICT(id) DO UPDATE SET status = excluded.status, updated_at = excluded.updated_at'
    );

    $stmt->execute([
        'id' => (string) ($payload['id'] ?? ''),
        'product_id' => sanitizeText((string) ($payload['product_id'] ?? ''), 40),
        'product_name' => sanitizeText((string) ($payload['product_name'] ?? ''), 200),
        'quantity' => (int) ($payload['quantity'] ?? 1),
        'unit_price' => (float) ($payload['unit_price'] ?? 0),
        'total' => (float) ($payload['total'] ?? 0),
        'currency' => sanitizeText((string) ($payload['currency'] ?? 'PKR'), 8),
        'customer_name' => sanitizeText((string) ($payload['customer_name'] ?? ''), 120),
        'customer_email' => sanitizeText((string) ($payload['customer_email'] ?? ''), 180),
        'customer_phone' => sanitizeText((string) ($payload['customer_phone'] ?? ''), 40),
        'address' => sanitizeText((string) ($payload['address'] ?? ''), 500),
        'notes' => sanitizeText((string) ($payload['notes'] ?? ''), 1000),
        'status' => sanitizeText((string) ($payload['status'] ?? 'pending'), 20),
        'ip' => sanitizeText((string) ($payload['ip'] ?? ''), 80),
        'created_at' => (string) ($payload['created'] ?? date('c')),
        'updated_at' => (string) ($payload['updated'] ?? date('c')),
    ]);
}

function dbListShopOrders(int $limit = 50): array
{
    if (!databaseReady()) {
        return [];
    }

    $stmt = db()->prepare('SELECT * FROM shop_orders ORDER BY created_at DESC LIMIT :limit');
    $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();
    $orders = [];

    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $orders[] = [
            'id' => (string) ($row['id'] ?? ''),
            'product_id' => (string) ($row['product_id'] ?? ''),
            'product_name' => (string) ($row['product_name'] ?? ''),
            'quantity' => (int) ($row['quantity'] ?? 1),
            'unit_price' => (float) ($row['unit_price'] ?? 0),
            'total' => (float) ($row['total'] ?? 0),
            'currency' => (string) ($row['currency'] ?? 'PKR'),
            'customer_name' => (string) ($row['customer_name'] ?? ''),
            'customer_email' => (string) ($row['customer_email'] ?? ''),
            'customer_phone' => (string) ($row['customer_phone'] ?? ''),
            'address' => (string) ($row['address'] ?? ''),
            'notes' => (string) ($row['notes'] ?? ''),
            'status' => (string) ($row['status'] ?? 'pending'),
            'ip' => (string) ($row['ip'] ?? ''),
            'created' => (string) ($row['created_at'] ?? ''),
            'updated' => (string) ($row['updated_at'] ?? ''),
        ];
    }

    return $orders;
}

function dbShopOrderCount(): int
{
    if (!databaseReady()) {
        return 0;
    }

    return (int) db()->query('SELECT COUNT(*) FROM shop_orders')->fetchColumn();
}

function dbUpdateShopOrderStatus(string $orderId, string $status): bool
{
    if (!databaseReady()) {
        return false;
    }

    $stmt = db()->prepare('UPDATE shop_orders SET status = :status, updated_at = :updated_at WHERE id = :id');
    $stmt->execute([
        'status' => sanitizeText($status, 20),
        'updated_at' => date('c'),
        'id' => sanitizeText($orderId, 40),
    ]);

    return $stmt->rowCount() > 0;
}

function dbAppendLedgerEntry(array $entry): void
{
    if (!databaseReady()) {
        return;
    }

    $stmt = db()->prepare(
        'INSERT OR IGNORE INTO shop_ledger
         (id, type, product_id, brand, product_name, quantity, unit_cost, unit_price, total_cost, total_sale, profit, currency, reference, note, created_at)
         VALUES
         (:id, :type, :product_id, :brand, :product_name, :quantity, :unit_cost, :unit_price, :total_cost, :total_sale, :profit, :currency, :reference, :note, :created_at)'
    );

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

function dbLoadLedgerEntries(): array
{
    if (!databaseReady()) {
        return [];
    }

    $rows = db()->query('SELECT * FROM shop_ledger ORDER BY created_at ASC')->fetchAll();
    $entries = [];
    foreach ($rows as $row) {
        if (is_array($row)) {
            $entries[] = $row;
        }
    }

    return $entries;
}

function dbLoadCertificationRows(): array
{
    if (!databaseReady()) {
        return [];
    }

    $rows = db()->query('SELECT * FROM certifications ORDER BY sort_order ASC, title ASC')->fetchAll();
    $items = [];
    foreach ($rows as $row) {
        if (is_array($row)) {
            $items[] = [
                'id' => (string) ($row['id'] ?? ''),
                'title' => (string) ($row['title'] ?? ''),
                'issuer' => (string) ($row['issuer'] ?? ''),
                'year' => (string) ($row['year'] ?? ''),
                'knowledge' => (string) ($row['knowledge'] ?? ''),
                'description' => (string) ($row['description'] ?? ''),
                'file_path' => (string) ($row['file_path'] ?? ''),
                'active' => !empty($row['active']),
                'sort_order' => (int) ($row['sort_order'] ?? 0),
                'created' => (string) ($row['created_at'] ?? ''),
                'updated' => (string) ($row['updated_at'] ?? ''),
            ];
        }
    }

    return $items;
}

function dbSaveCertificationRecord(array $cert): void
{
    if (!databaseReady()) {
        return;
    }

    $stmt = db()->prepare(
        'INSERT INTO certifications
         (id, title, issuer, year, knowledge, description, file_path, active, sort_order, created_at, updated_at)
         VALUES
         (:id, :title, :issuer, :year, :knowledge, :description, :file_path, :active, :sort_order, :created_at, :updated_at)
         ON CONFLICT(id) DO UPDATE SET
           title = excluded.title,
           issuer = excluded.issuer,
           year = excluded.year,
           knowledge = excluded.knowledge,
           description = excluded.description,
           file_path = excluded.file_path,
           active = excluded.active,
           sort_order = excluded.sort_order,
           updated_at = excluded.updated_at'
    );

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

function dbDeleteCertification(string $certId): void
{
    if (!databaseReady() || $certId === '') {
        return;
    }

    $stmt = db()->prepare('DELETE FROM certifications WHERE id = :id');
    $stmt->execute(['id' => $certId]);
}

function dbResetSiteData(bool $keepUsers = true): void
{
    if (!databaseReady()) {
        return;
    }

    $pdo = db();
    foreach (['shop_ledger', 'shop_orders', 'shop_products', 'certifications', 'contact_messages'] as $table) {
        $pdo->exec('DELETE FROM ' . $table);
    }

    $pdo->exec('DELETE FROM site_settings');
    require_once __DIR__ . '/db-schema.php';
    importSiteSettingsJson($pdo);

    if (!$keepUsers) {
        $pdo->exec('DELETE FROM users');
        require_once __DIR__ . '/db-schema.php';
        seedInitialAdminUser($pdo);
    }
}

function dbDatabaseSummary(): array
{
    if (!databaseReady()) {
        return ['ready' => false];
    }

    return [
        'ready' => true,
        'driver' => strtolower(trim(configValue('DB_DRIVER', 'sqlite'))) ?: 'sqlite',
        'users' => (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn(),
        'products' => (int) db()->query('SELECT COUNT(*) FROM shop_products')->fetchColumn(),
        'orders' => (int) db()->query('SELECT COUNT(*) FROM shop_orders')->fetchColumn(),
        'contacts' => (int) db()->query('SELECT COUNT(*) FROM contact_messages')->fetchColumn(),
        'certifications' => (int) db()->query('SELECT COUNT(*) FROM certifications')->fetchColumn(),
    ];
}
