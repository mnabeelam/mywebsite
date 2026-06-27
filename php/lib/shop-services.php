<?php
declare(strict_types=1);

require_once __DIR__ . '/site-services.php';

function shopUsesDatabase(): bool
{
    static $ready = null;
    if ($ready === null) {
        require_once __DIR__ . '/database.php';
        $ready = databaseReady();
    }

    return $ready;
}

const SHOP_MAX_IMAGE_BYTES = 5 * 1024 * 1024;
const SHOP_MAX_IMAGES_PER_PRODUCT = 10;

function shopBrandOptions(): array
{
    return [
        'Dell',
        'HP',
        'Lenovo',
        'Apple',
        'Logitech',
        'Samsung',
        'TP-Link',
        'Cisco',
        'Ubiquiti',
        'Anker',
        'Microsoft',
        'Asus',
        'Acer',
        'Other',
    ];
}

function shopCategoryOptions(): array
{
    return [
        'Laptop',
        'Desktop',
        'Monitor',
        'Keyboard',
        'Mouse',
        'Headset',
        'Webcam',
        'USB Hub',
        'Storage',
        'Networking',
        'Cable & Adapter',
        'Accessory',
        'Other',
    ];
}

function shopProductImageDir(): string
{
    $dir = realpath(__DIR__ . '/../../assets/shop/products');
    if ($dir === false) {
        $target = __DIR__ . '/../../assets/shop/products';
        if (!is_dir($target) && !mkdir($target, 0755, true)) {
            throw new RuntimeException('Product image directory could not be created.');
        }
        $dir = realpath($target);
    }

    if ($dir === false) {
        throw new RuntimeException('Product image directory is not available.');
    }

    return rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
}

function shopProductImageWebPath(string $filename): string
{
    return 'assets/shop/products/' . basename($filename);
}

function allowedShopImageExtensions(): array
{
    return ['jpg', 'jpeg', 'png', 'webp'];
}

function allowedShopImageMimeTypes(): array
{
    return ['image/jpeg', 'image/png', 'image/webp'];
}

function inspectShopProductImage(array $file): array
{
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        throw new InvalidArgumentException('Product image upload failed. Choose a JPG, PNG, or WEBP file.');
    }

    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new InvalidArgumentException('No valid product image was uploaded.');
    }

    if (($file['size'] ?? 0) > SHOP_MAX_IMAGE_BYTES) {
        throw new InvalidArgumentException('Product image is too large. Maximum size is 5 MB.');
    }

    rejectDangerousUploadName((string) ($file['name'] ?? 'image.jpg'));
    $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    if (!in_array($extension, allowedShopImageExtensions(), true)) {
        throw new InvalidArgumentException('Product image must be JPG, PNG, or WEBP.');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo === false) {
        throw new InvalidArgumentException('Server cannot inspect uploaded images.');
    }
    $mime = finfo_file($finfo, $file['tmp_name']) ?: '';
    finfo_close($finfo);

    if (!in_array($mime, allowedShopImageMimeTypes(), true)) {
        throw new InvalidArgumentException('Product image must be JPG, PNG, or WEBP.');
    }

    $handle = fopen($file['tmp_name'], 'rb');
    $head = $handle ? fread($handle, 16) : '';
    if ($handle) {
        fclose($handle);
    }

    if ($mime === 'image/jpeg' && !str_starts_with((string) $head, "\xFF\xD8\xFF")) {
        throw new InvalidArgumentException('This is not a valid JPEG image.');
    }
    if ($mime === 'image/png' && !str_starts_with((string) $head, "\x89PNG\r\n\x1a\n")) {
        throw new InvalidArgumentException('This is not a valid PNG image.');
    }
    if ($mime === 'image/webp' && (!str_starts_with((string) $head, 'RIFF') || strlen((string) $head) < 12 || substr($head, 8, 4) !== 'WEBP')) {
        throw new InvalidArgumentException('This is not a valid WEBP image.');
    }

    return [
        'mime' => $mime,
        'extension' => $extension === 'jpeg' ? 'jpg' : $extension,
        'original_name' => sanitizeText((string) ($file['name'] ?? ''), 200),
    ];
}

function saveShopProductImage(array $file): string
{
    $inspected = inspectShopProductImage($file);
    $filename = 'product_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $inspected['extension'];
    $destination = shopProductImageDir() . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Could not save product image.');
    }

    return shopProductImageWebPath($filename);
}

function deleteShopProductImage(?string $imagePath): void
{
    $imagePath = sanitizeText((string) $imagePath, 300);
    if ($imagePath === '' || !str_starts_with($imagePath, 'assets/shop/products/')) {
        return;
    }

    $path = __DIR__ . '/../../' . str_replace('/', DIRECTORY_SEPARATOR, $imagePath);
    if (is_file($path)) {
        unlink($path);
    }
}

function isAllowedProductImagePath(string $path): bool
{
    return $path === 'assets/shop/placeholder.svg'
        || str_starts_with($path, 'assets/shop/products/');
}

function sanitizeProductImagePaths(array $paths): array
{
    $valid = [];
    foreach ($paths as $path) {
        $path = sanitizeText((string) $path, 300);
        if ($path === '' || !isAllowedProductImagePath($path)) {
            continue;
        }
        if (!in_array($path, $valid, true)) {
            $valid[] = $path;
        }
    }

    return $valid;
}

function productImageList(array $product): array
{
    $images = sanitizeProductImagePaths(is_array($product['images'] ?? null) ? $product['images'] : []);
    $cover = sanitizeText((string) ($product['image'] ?? ''), 300);

    if ($cover !== '' && isAllowedProductImagePath($cover) && !in_array($cover, $images, true)) {
        array_unshift($images, $cover);
    }

    if ($images === []) {
        $images[] = 'assets/shop/placeholder.svg';
    }

    return array_slice($images, 0, SHOP_MAX_IMAGES_PER_PRODUCT);
}

function productCoverImage(array $product): string
{
    $images = productImageList($product);

    return $images[0];
}

function parseKeepImagesInput(array $input, ?array $existing): array
{
    $kept = [];
    if (array_key_exists('keep_images', $input)) {
        $raw = $input['keep_images'];
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $kept = $decoded;
            }
        } elseif (is_array($raw)) {
            $kept = $raw;
        }
    } elseif ($existing !== null) {
        $kept = productImageList($existing);
    }

    return sanitizeProductImagePaths($kept);
}

function restructureMultiFileUpload(array $files): array
{
    $uploads = [];
    if (!isset($files['name'])) {
        return $uploads;
    }

    if (!is_array($files['name'])) {
        if (($files['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $uploads[] = $files;
        }

        return $uploads;
    }

    foreach ($files['name'] as $index => $name) {
        if (($files['error'][$index] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            continue;
        }
        $uploads[] = [
            'name' => $name,
            'type' => $files['type'][$index] ?? '',
            'tmp_name' => $files['tmp_name'][$index] ?? '',
            'error' => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
            'size' => $files['size'][$index] ?? 0,
        ];
    }

    return $uploads;
}

function collectShopProductUploads(array $files): array
{
    $uploads = [];
    if (!empty($files['product_images'])) {
        $uploads = array_merge($uploads, restructureMultiFileUpload($files['product_images']));
    }
    if (!empty($files['product_image']['tmp_name'])) {
        $uploads[] = $files['product_image'];
    }

    return $uploads;
}

function saveShopProductImages(array $files, int $maxNew = SHOP_MAX_IMAGES_PER_PRODUCT): array
{
    $paths = [];
    foreach ($files as $file) {
        if (count($paths) >= $maxNew) {
            break;
        }
        if (empty($file['tmp_name'])) {
            continue;
        }
        $paths[] = saveShopProductImage($file);
    }

    return $paths;
}

function deleteRemovedProductImages(array $before, array $after): void
{
    foreach ($before as $imagePath) {
        if (!in_array($imagePath, $after, true)) {
            deleteShopProductImage($imagePath);
        }
    }
}

function mergeProductImagePaths(array $kept, array $newPaths): array
{
    $merged = sanitizeProductImagePaths(array_merge($kept, $newPaths));
    $merged = array_values(array_filter($merged, static function (string $path): bool {
        return $path !== 'assets/shop/placeholder.svg';
    }));

    if ($merged === []) {
        if ($newPaths !== []) {
            $merged = sanitizeProductImagePaths($newPaths);
        } else {
            $keptWithoutPlaceholder = array_values(array_filter($kept, static function (string $path): bool {
                return $path !== 'assets/shop/placeholder.svg';
            }));
            $merged = $keptWithoutPlaceholder !== [] ? $keptWithoutPlaceholder : ['assets/shop/placeholder.svg'];
        }
    }

    if (count($merged) > SHOP_MAX_IMAGES_PER_PRODUCT) {
        $overflow = array_slice($merged, SHOP_MAX_IMAGES_PER_PRODUCT);
        foreach ($overflow as $imagePath) {
            deleteShopProductImage($imagePath);
        }
        $merged = array_slice($merged, 0, SHOP_MAX_IMAGES_PER_PRODUCT);
    }

    return $merged;
}

function resolveProductImagesForSave(array $input, ?array $existing, array $uploads): array
{
    $kept = parseKeepImagesInput($input, $existing);
    $remaining = max(0, SHOP_MAX_IMAGES_PER_PRODUCT - count($kept));
    $newPaths = saveShopProductImages($uploads, $remaining);
    $images = mergeProductImagePaths($kept, $newPaths);

    if ($existing !== null) {
        deleteRemovedProductImages(productImageList($existing), $images);
    }

    return $images;
}

function appendUniqueProductImages(array $targetImages, array $incomingImages): array
{
    foreach ($incomingImages as $imagePath) {
        if ($imagePath === 'assets/shop/placeholder.svg') {
            continue;
        }
        if (!in_array($imagePath, $targetImages, true)) {
            $targetImages[] = $imagePath;
        }
    }

    return array_slice($targetImages, 0, SHOP_MAX_IMAGES_PER_PRODUCT);
}

function resolveShopSelectValue(string $selected, string $custom, array $allowed, string $fallback): string
{
    $selected = sanitizeText($selected, 80);
    $custom = sanitizeText($custom, 80);

    if ($selected === 'Other') {
        return $custom !== '' ? $custom : $fallback;
    }

    if ($selected !== '' && in_array($selected, $allowed, true)) {
        return $selected;
    }

    return $selected !== '' ? $selected : $fallback;
}

function productTracksStock(array $product): bool
{
    if (!empty($product['track_stock'])) {
        return true;
    }

    return (int) ($product['stock'] ?? 0) > 0;
}

function productIsInStock(array $product): bool
{
    if (!productTracksStock($product)) {
        return true;
    }

    return ((int) ($product['stock'] ?? 0)) > 0;
}

function productStockLabel(array $product): string
{
    if (!productTracksStock($product)) {
        return 'Available';
    }

    $stock = (int) ($product['stock'] ?? 0);
    if ($stock <= 0) {
        return 'Out of stock';
    }

    return $stock . ' available';
}

function shopStorageDir(): string
{
    $dir = __DIR__ . '/../storage/shop';
    if (!is_dir($dir)) {
        mkdir($dir, 0700, true);
    }

    return rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
}

function shopOrdersDir(): string
{
    $dir = shopStorageDir() . 'orders';
    if (!is_dir($dir)) {
        mkdir($dir, 0700, true);
    }

    return rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
}

function shopCatalogPath(): string
{
    return shopStorageDir() . 'products.json';
}

function defaultShopCatalog(): array
{
    return [
        'products' => [],
        'updated' => date('c'),
    ];
}

function loadShopCatalog(): array
{
    if (shopUsesDatabase()) {
        require_once __DIR__ . '/db-records.php';
        $products = dbLoadShopProducts();
        if ($products !== []) {
            return [
                'products' => $products,
                'updated' => date('c'),
            ];
        }
    }

    $path = shopCatalogPath();
    if (!is_readable($path)) {
        return defaultShopCatalog();
    }

    $data = json_decode((string) file_get_contents($path), true);
    if (!is_array($data) || !is_array($data['products'] ?? null)) {
        return defaultShopCatalog();
    }

    return array_merge(defaultShopCatalog(), $data);
}

function saveShopCatalog(array $catalog): void
{
    $catalog['updated'] = date('c');
    file_put_contents(shopCatalogPath(), json_encode($catalog, JSON_PRETTY_PRINT), LOCK_EX);

    if (shopUsesDatabase()) {
        require_once __DIR__ . '/db-records.php';
        foreach ($catalog['products'] as $product) {
            if (is_array($product)) {
                dbSaveShopProductRecord($product);
            }
        }
    }
}

function generateShopId(string $prefix): string
{
    return $prefix . '_' . bin2hex(random_bytes(6));
}

function normalizeProductRecord(array $product): array
{
    $trackStock = !empty($product['track_stock']);
    $stock = max(0, (int) ($product['stock'] ?? 0));
    $images = productImageList($product);

    return [
        'id' => sanitizeText((string) ($product['id'] ?? ''), 40),
        'brand' => sanitizeText((string) ($product['brand'] ?? ''), 80),
        'name' => sanitizeText((string) ($product['name'] ?? ''), 200),
        'description' => sanitizeText((string) ($product['description'] ?? ''), 2000),
        'price' => max(0, (float) ($product['price'] ?? 0)),
        'cost_price' => max(0, (float) ($product['cost_price'] ?? 0)),
        'currency' => sanitizeText((string) ($product['currency'] ?? 'PKR'), 8),
        'category' => sanitizeText((string) ($product['category'] ?? 'Accessory'), 80),
        'images' => $images,
        'image' => $images[0],
        'track_stock' => $trackStock,
        'stock' => $stock,
        'active' => !empty($product['active']),
        'created' => (string) ($product['created'] ?? date('c')),
        'updated' => date('c'),
    ];
}

function findProductById(string $id): ?array
{
    $catalog = loadShopCatalog();
    foreach ($catalog['products'] as $product) {
        if (!is_array($product)) {
            continue;
        }
        if (($product['id'] ?? '') === $id) {
            return $product;
        }
    }

    return null;
}

function listPublicShopProducts(): array
{
    $catalog = loadShopCatalog();
    $products = [];

    foreach ($catalog['products'] as $product) {
        if (!is_array($product) || empty($product['active'])) {
            continue;
        }

        $products[] = [
            'id' => (string) ($product['id'] ?? ''),
            'brand' => (string) ($product['brand'] ?? ''),
            'name' => (string) ($product['name'] ?? ''),
            'description' => (string) ($product['description'] ?? ''),
            'price' => (float) ($product['price'] ?? 0),
            'currency' => (string) ($product['currency'] ?? 'PKR'),
            'category' => (string) ($product['category'] ?? 'Accessory'),
            'images' => productImageList($product),
            'image' => productCoverImage($product),
            'in_stock' => productIsInStock($product),
            'stock_label' => productStockLabel($product),
            'available_stock' => empty($product['track_stock']) ? null : (int) ($product['stock'] ?? 0),
        ];
    }

    usort($products, static function (array $a, array $b): int {
        return strcasecmp($a['name'], $b['name']);
    });

    return $products;
}

function listAllShopProducts(): array
{
    $catalog = loadShopCatalog();
    $products = [];

    foreach ($catalog['products'] as $product) {
        if (is_array($product)) {
            $products[] = normalizeProductRecord($product);
        }
    }

    usort($products, static function (array $a, array $b): int {
        return strcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
    });

    return $products;
}

function shopLower(string $value): string
{
    if (function_exists('mb_strtolower')) {
        return mb_strtolower($value);
    }

    return strtolower($value);
}

function shopProductMatchKey(string $brand, string $name): string
{
    return shopLower(trim($brand) . "\0" . trim($name));
}

function findShopProductIndexByBrandAndName(array $products, string $brand, string $name, ?string $excludeId = null): ?int
{
    $key = shopProductMatchKey($brand, $name);
    foreach ($products as $index => $product) {
        if (!is_array($product)) {
            continue;
        }
        $id = (string) ($product['id'] ?? '');
        if ($excludeId !== null && $id === $excludeId) {
            continue;
        }
        if (shopProductMatchKey((string) ($product['brand'] ?? ''), (string) ($product['name'] ?? '')) === $key) {
            return $index;
        }
    }

    return null;
}

function saveShopProduct(array $input, array $uploads = []): array
{
    $id = sanitizeText((string) ($input['id'] ?? ''), 40);
    $catalog = loadShopCatalog();
    $products = $catalog['products'];
    $now = date('c');
    $existing = null;

    foreach ($products as $product) {
        if (is_array($product) && ($product['id'] ?? '') === $id) {
            $existing = $product;
            break;
        }
    }

    $trackStock = !empty($input['track_stock']);
    $stock = max(0, (int) ($input['stock'] ?? 0));
    if ($trackStock && $stock === 0 && $existing === null) {
        $stock = 1;
    }
    $previousStock = $existing !== null && !empty($existing['track_stock'])
        ? (int) ($existing['stock'] ?? 0)
        : 0;

    $record = normalizeProductRecord([
        'id' => $id !== '' ? $id : generateShopId('prod'),
        'brand' => resolveShopSelectValue(
            (string) ($input['brand'] ?? ''),
            (string) ($input['brand_custom'] ?? ''),
            shopBrandOptions(),
            'Other'
        ),
        'name' => $input['name'] ?? '',
        'description' => $input['description'] ?? '',
        'price' => $input['price'] ?? 0,
        'cost_price' => $input['cost_price'] ?? ($existing !== null ? ($existing['cost_price'] ?? 0) : 0),
        'currency' => $input['currency'] ?? 'PKR',
        'category' => resolveShopSelectValue(
            (string) ($input['category'] ?? ''),
            (string) ($input['category_custom'] ?? ''),
            shopCategoryOptions(),
            'Accessory'
        ),
        'images' => $existing !== null ? productImageList($existing) : ['assets/shop/placeholder.svg'],
        'image' => $existing !== null ? productCoverImage($existing) : 'assets/shop/placeholder.svg',
        'track_stock' => $trackStock,
        'stock' => $trackStock ? $stock : 0,
        'active' => !empty($input['active']),
        'created' => $existing['created'] ?? $now,
    ]);

    if ($record['name'] === '') {
        throw new InvalidArgumentException('Product name is required.');
    }

    $record['images'] = resolveProductImagesForSave($input, $existing, $uploads);
    $record['image'] = $record['images'][0];

    $duplicateIndex = findShopProductIndexByBrandAndName(
        $products,
        $record['brand'],
        $record['name'],
        $existing !== null ? (string) $record['id'] : null
    );

    if ($duplicateIndex !== null) {
        if ($existing !== null) {
            throw new InvalidArgumentException(
                'A product with this brand and model already exists. Edit the existing listing or use a different name.'
            );
        }

        $target = $products[$duplicateIndex];
        $targetHadStock = !empty($target['track_stock']);
        $baseStock = $targetHadStock ? (int) ($target['stock'] ?? 0) : 0;

        if ($trackStock) {
            $target['track_stock'] = true;
            $target['stock'] = $baseStock + $stock;
        }
        if ($record['cost_price'] > 0) {
            $target['cost_price'] = $record['cost_price'];
        }

        if ($record['price'] > 0) {
            $target['price'] = $record['price'];
        }
        if ($record['currency'] !== '') {
            $target['currency'] = $record['currency'];
        }
        if ($record['description'] !== '') {
            $target['description'] = $record['description'];
        }
        if ($record['category'] !== '') {
            $target['category'] = $record['category'];
        }
        if (!empty($input['active'])) {
            $target['active'] = true;
        }

        $target['images'] = appendUniqueProductImages(productImageList($target), $record['images']);
        $target['image'] = $target['images'][0];

        $target['updated'] = date('c');
        $products[$duplicateIndex] = normalizeProductRecord($target);
        if ($trackStock && $stock > 0) {
            logShopStockIn(
                $products[$duplicateIndex],
                $stock,
                'stock_in',
                (float) ($products[$duplicateIndex]['cost_price'] ?? 0),
                'Merged duplicate brand/model listing'
            );
        }
        $catalog['products'] = array_values($products);
        saveShopCatalog($catalog);

        return [
            'product' => $products[$duplicateIndex],
            'merged' => true,
        ];
    }

    $found = false;
    foreach ($products as $index => $product) {
        if (!is_array($product)) {
            continue;
        }
        if (($product['id'] ?? '') === $record['id']) {
            $products[$index] = $record;
            $found = true;
            break;
        }
    }

    if (!$found) {
        $products[] = $record;
    }

    $savedProduct = null;
    foreach ($products as $product) {
        if (is_array($product) && ($product['id'] ?? '') === $record['id']) {
            $savedProduct = $product;
            break;
        }
    }

    if ($savedProduct !== null && $trackStock) {
        $stockDelta = (int) ($savedProduct['stock'] ?? 0) - $previousStock;
        if ($stockDelta > 0) {
            logShopStockIn(
                $savedProduct,
                $stockDelta,
                $existing === null ? 'stock_in' : 'stock_in',
                (float) ($savedProduct['cost_price'] ?? 0),
                $existing === null ? 'Initial product stock' : 'Stock increased in admin'
            );
        } elseif ($stockDelta < 0) {
            logShopStockOut(
                $savedProduct,
                abs($stockDelta),
                'adjustment',
                0.0,
                (float) ($savedProduct['cost_price'] ?? 0),
                'Stock reduced in admin'
            );
        }
    }

    $catalog['products'] = array_values($products);
    saveShopCatalog($catalog);

    return [
        'product' => $record,
        'merged' => false,
    ];
}

function deleteShopProduct(string $id): bool
{
    $id = sanitizeText($id, 40);
    if ($id === '') {
        return false;
    }

    $catalog = loadShopCatalog();
    $before = count($catalog['products']);
    $removedImages = [];

    $catalog['products'] = array_values(array_filter(
        $catalog['products'],
        static function ($product) use ($id, &$removedImages): bool {
            if (!is_array($product) || ($product['id'] ?? '') !== $id) {
                return true;
            }
            $removedImages = productImageList($product);
            return false;
        }
    ));

    if (count($catalog['products']) === $before) {
        return false;
    }

    foreach ($removedImages as $imagePath) {
        deleteShopProductImage($imagePath);
    }
    saveShopCatalog($catalog);

    if (shopUsesDatabase()) {
        require_once __DIR__ . '/db-records.php';
        dbDeleteShopProduct($id);
    }

    return true;
}

function saveShopOrder(
    string $productId,
    int $quantity,
    string $customerName,
    string $customerEmail,
    string $customerPhone,
    string $address,
    string $notes
): string {
    requirePublicAccessAllowed();

    $product = findProductById($productId);
    if ($product === null || empty($product['active'])) {
        throw new InvalidArgumentException('Product is not available.');
    }

    if (!productIsInStock($product)) {
        throw new InvalidArgumentException('This product is currently out of stock.');
    }

    $trackStock = !empty($product['track_stock']);
    $stock = (int) ($product['stock'] ?? 0);
    if ($trackStock && $quantity > $stock) {
        throw new InvalidArgumentException('Requested quantity exceeds available stock.');
    }

    $quantity = max(1, min(99, $quantity));
    $unitPrice = (float) ($product['price'] ?? 0);
    $currency = (string) ($product['currency'] ?? 'PKR');
    $orderId = generateShopId('ord');
    $filename = $orderId . '.json';

    $payload = [
        'id' => $orderId,
        'product_id' => $productId,
        'product_name' => (string) ($product['name'] ?? ''),
        'quantity' => $quantity,
        'unit_price' => $unitPrice,
        'total' => round($unitPrice * $quantity, 2),
        'currency' => $currency,
        'customer_name' => $customerName,
        'customer_email' => $customerEmail,
        'customer_phone' => $customerPhone,
        'address' => $address,
        'notes' => $notes,
        'status' => 'pending',
        'ip' => clientIp(),
        'created' => date('c'),
    ];

    file_put_contents(
        shopOrdersDir() . $filename,
        json_encode($payload, JSON_PRETTY_PRINT),
        LOCK_EX
    );

    if (shopUsesDatabase()) {
        require_once __DIR__ . '/db-records.php';
        dbSaveShopOrderRecord($payload);
    }

    if ($trackStock && $stock > 0) {
        $catalog = loadShopCatalog();
        foreach ($catalog['products'] as $index => $item) {
            if (!is_array($item) || ($item['id'] ?? '') !== $productId) {
                continue;
            }
            $catalog['products'][$index]['stock'] = max(0, $stock - $quantity);
            $catalog['products'][$index]['updated'] = date('c');
            break;
        }
        saveShopCatalog($catalog);
    }

    logShopStockOut(
        $product,
        $quantity,
        'sale',
        $unitPrice,
        (float) ($product['cost_price'] ?? 0),
        'Online order',
        $orderId
    );

    return $orderId;
}

function saveShopCartOrders(
    array $items,
    string $customerName,
    string $customerEmail,
    string $customerPhone,
    string $address,
    string $notes
): array {
    requirePublicAccessAllowed();

    if ($customerName === '' || $customerEmail === '' || $customerPhone === '' || $address === '') {
        throw new InvalidArgumentException('Name, email, phone, and delivery address are required.');
    }

    if (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Please enter a valid email address.');
    }

    $normalized = [];
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }
        $productId = sanitizeText((string) ($item['product_id'] ?? ''), 40);
        $quantity = max(1, min(99, (int) ($item['quantity'] ?? 1)));
        if ($productId === '') {
            continue;
        }
        if (!isset($normalized[$productId])) {
            $normalized[$productId] = 0;
        }
        $normalized[$productId] += $quantity;
    }

    if ($normalized === []) {
        throw new InvalidArgumentException('Your cart is empty.');
    }

    $pending = [];
    foreach ($normalized as $productId => $quantity) {
        $product = findProductById($productId);
        if ($product === null || empty($product['active'])) {
            throw new InvalidArgumentException('One or more products in your cart are no longer available.');
        }
        if (!productIsInStock($product)) {
            throw new InvalidArgumentException((string) ($product['name'] ?? 'A product') . ' is out of stock.');
        }
        $trackStock = !empty($product['track_stock']);
        $stock = (int) ($product['stock'] ?? 0);
        if ($trackStock && $quantity > $stock) {
            throw new InvalidArgumentException(
                'Requested quantity for ' . (string) ($product['name'] ?? 'a product') . ' exceeds available stock.'
            );
        }
        $pending[] = [
            'product' => $product,
            'product_id' => $productId,
            'quantity' => $quantity,
        ];
    }

    $orderIds = [];
    foreach ($pending as $entry) {
        $orderIds[] = saveShopOrder(
            (string) $entry['product_id'],
            (int) $entry['quantity'],
            $customerName,
            $customerEmail,
            $customerPhone,
            $address,
            $notes
        );
    }

    return $orderIds;
}

function findShopOrderById(string $orderId): ?array
{
    $orderId = sanitizeText($orderId, 40);
    if ($orderId === '') {
        return null;
    }

    $path = shopOrdersDir() . $orderId . '.json';
    if (is_readable($path)) {
        $data = json_decode((string) file_get_contents($path), true);
        if (is_array($data)) {
            return $data;
        }
    }

    if (shopUsesDatabase()) {
        require_once __DIR__ . '/db-records.php';
        foreach (dbListShopOrders(500) as $order) {
            if (($order['id'] ?? '') === $orderId) {
                return $order;
            }
        }
    }

    return null;
}

function notifyAdminNewShopOrders(
    array $orderIds,
    string $customerName,
    string $customerEmail,
    string $customerPhone,
    string $address,
    string $notes
): bool {
    $orderIds = array_values(array_filter(array_map(
        static fn($id): string => sanitizeText((string) $id, 40),
        $orderIds
    )));
    if ($orderIds === []) {
        return false;
    }

    require_once __DIR__ . '/site-settings.php';

    $lines = [
        'A new shop order was placed on your portfolio website.',
        '',
        '--- Order details ---',
    ];
    $currency = 'PKR';
    $grandTotal = 0.0;
    $firstProduct = 'Product';

    foreach ($orderIds as $orderId) {
        $order = findShopOrderById($orderId);
        if ($order === null) {
            $lines[] = 'Order ID: ' . $orderId;
            continue;
        }

        $firstProduct = (string) ($order['product_name'] ?? $firstProduct);
        $currency = (string) ($order['currency'] ?? $currency);
        $total = (float) ($order['total'] ?? 0);
        $grandTotal += $total;

        $lines[] = sprintf(
            '%s — %s × %d = %s %s (%s)',
            $orderId,
            (string) ($order['product_name'] ?? 'Product'),
            (int) ($order['quantity'] ?? 1),
            $currency,
            number_format($total, 2),
            (string) ($order['status'] ?? 'pending')
        );
    }

    if (count($orderIds) > 1) {
        $lines[] = '';
        $lines[] = 'Cart total: ' . $currency . ' ' . number_format($grandTotal, 2);
    }

    $lines[] = '';
    $lines[] = '--- Customer ---';
    $lines[] = 'Name: ' . $customerName;
    $lines[] = 'Email: ' . $customerEmail;
    $lines[] = 'Phone: ' . $customerPhone;
    $lines[] = 'Address: ' . $address;
    if ($notes !== '') {
        $lines[] = '';
        $lines[] = 'Notes:';
        $lines[] = $notes;
    }

    $lines[] = '';
    $lines[] = 'Manage this order in Admin → Online Store (Orders section).';

    $subject = count($orderIds) > 1
        ? 'New shop cart order (' . count($orderIds) . ' items) — ' . $customerName
        : 'New shop order: ' . $firstProduct . ' — ' . $customerName;

    return sendAdminNotification($subject, implode("\n", $lines), $customerEmail);
}

function listShopOrders(int $limit = 50): array
{
    if (shopUsesDatabase()) {
        require_once __DIR__ . '/db-records.php';
        $orders = dbListShopOrders($limit);
        if ($orders !== []) {
            return $orders;
        }
    }

    $files = glob(shopOrdersDir() . 'ord_*.json') ?: [];
    rsort($files);
    $orders = [];

    foreach (array_slice($files, 0, $limit) as $file) {
        $data = json_decode((string) file_get_contents($file), true);
        if (is_array($data)) {
            $orders[] = $data;
        }
    }

    return $orders;
}

function shopOrderCount(): int
{
    if (shopUsesDatabase()) {
        require_once __DIR__ . '/db-records.php';
        $count = dbShopOrderCount();
        if ($count > 0) {
            return $count;
        }
    }

    return count(glob(shopOrdersDir() . 'ord_*.json') ?: []);
}

function updateShopOrderStatus(string $orderId, string $status): bool
{
    $orderId = sanitizeText($orderId, 40);
    $status = sanitizeText($status, 20);
    $allowed = ['pending', 'confirmed', 'shipped', 'completed', 'cancelled'];

    if ($orderId === '' || !in_array($status, $allowed, true)) {
        return false;
    }

    $path = shopOrdersDir() . $orderId . '.json';
    if (!is_readable($path)) {
        if (shopUsesDatabase()) {
            require_once __DIR__ . '/db-records.php';
            return dbUpdateShopOrderStatus($orderId, $status);
        }
        return false;
    }

    $data = json_decode((string) file_get_contents($path), true);
    if (!is_array($data)) {
        return false;
    }

    $data['status'] = $status;
    $data['updated'] = date('c');
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX);

    if (shopUsesDatabase()) {
        require_once __DIR__ . '/db-records.php';
        dbUpdateShopOrderStatus($orderId, $status);
    }

    return true;
}

function shopMergedSelectOptions(string $field, array $defaults): array
{
    $values = $defaults;
    foreach (listAllShopProducts() as $product) {
        $value = sanitizeText((string) ($product[$field] ?? ''), 80);
        if ($value !== '' && !in_array($value, $values, true)) {
            $values[] = $value;
        }
    }

    if (!in_array('Other', $values, true)) {
        $values[] = 'Other';
    }

    return $values;
}

function shopAdminSummary(): array
{
    return [
        'product_count' => count(listAllShopProducts()),
        'active_product_count' => count(listPublicShopProducts()),
        'order_count' => shopOrderCount(),
        'products' => listAllShopProducts(),
        'orders' => listShopOrders(30),
        'options' => [
            'brands' => shopMergedSelectOptions('brand', shopBrandOptions()),
            'categories' => shopMergedSelectOptions('category', shopCategoryOptions()),
        ],
    ];
}

function shopAssistantKeywords(): array
{
    return [
        'shop',
        'store',
        'online store',
        'it shop',
        'product',
        'products',
        'gadget',
        'gadgets',
        'buy',
        'order',
        'purchase',
        'price',
        'cost',
        'stock',
        'available',
        'inventory',
        'lcd',
        'monitor',
        'laptop',
        'server',
        'keyboard',
        'mouse',
        'headset',
        'webcam',
        'hub',
        'storage',
        'networking',
        'accessory',
        'dell',
        'hp',
        'lenovo',
        'logitech',
    ];
}

function shopQuestionHasStoreIntent(string $question): bool
{
    $question = shopLower(trim($question));
    if ($question === '') {
        return false;
    }

    foreach (shopAssistantKeywords() as $keyword) {
        if (strpos($question, shopLower($keyword)) !== false) {
            return true;
        }
    }

    if (preg_match('/\b(how much|do you sell|what is available|show me|list)\b/', $question)) {
        return true;
    }

    if (preg_match('/\b(do you have|what do you have)\b/', $question)) {
        return (bool) preg_match(
            '/\b(shop|store|product|gadget|mouse|monitor|server|lcd|buy|price|stock|cart|order)\b/',
            $question
        );
    }

    return false;
}

function formatShopProductLineForAssistant(array $product): string
{
    $label = trim(((string) ($product['brand'] ?? '')) . ' ' . ((string) ($product['name'] ?? 'Product')));
    $price = (string) ($product['currency'] ?? 'PKR') . ' ' . number_format((float) ($product['price'] ?? 0), 0);
    $stock = (string) ($product['stock_label'] ?? 'Available');

    return '• ' . $label . ' — ' . $price . ' (' . $stock . ')';
}

function formatShopProductDetailForAssistant(array $product): string
{
    $lines = [
        formatShopProductLineForAssistant($product),
    ];

    if (!empty($product['category'])) {
        $lines[] = 'Category: ' . (string) $product['category'];
    }
    if (!empty($product['description'])) {
        $lines[] = (string) $product['description'];
    }

    $lines[] = 'Browse the IT Gadgets Shop section on this page to add it to your cart and order online.';

    return implode("\n", $lines);
}

function formatShopCatalogForAssistant(array $products, string $heading = 'IT Gadgets Shop — available products:'): string
{
    if ($products === []) {
        return 'The IT online store has no products listed yet. Please check back soon or use the contact form to ask about availability.';
    }

    $lines = [$heading, ''];
    foreach (array_slice($products, 0, 8) as $product) {
        $lines[] = formatShopProductLineForAssistant($product);
    }

    if (count($products) > 8) {
        $lines[] = '• +' . (count($products) - 8) . ' more in the shop section';
    }

    $lines[] = '';
    $lines[] = 'Open the IT Shop section on this page to view pictures, add to cart, and place an order.';

    return implode("\n", $lines);
}

function scoreShopProductForQuestion(array $product, string $question, array $words): int
{
    $score = 0;
    $haystack = shopLower(implode(' ', [
        (string) ($product['brand'] ?? ''),
        (string) ($product['name'] ?? ''),
        (string) ($product['category'] ?? ''),
        (string) ($product['description'] ?? ''),
    ]));

    foreach ($words as $word) {
        $word = shopLower(trim((string) $word));
        if ($word === '' || in_array($word, ['the', 'and', 'for', 'what', 'how', 'you', 'your', 'are', 'can', 'any'], true)) {
            continue;
        }
        if (strlen($word) >= 2 && strpos($haystack, $word) !== false) {
            $score += 4;
        }
    }

    if (shopQuestionHasStoreIntent($question) && $score > 0) {
        $score += 2;
    }

    return $score;
}

function searchShopAssistantAnswer(string $question): ?string
{
    $normalized = shopLower(trim($question));
    if ($normalized === '') {
        return null;
    }

    $products = listPublicShopProducts();
    $shopIntent = shopQuestionHasStoreIntent($normalized);
    $words = preg_split('/\s+/', $normalized) ?: [];

    if ($products === []) {
        return $shopIntent
            ? 'The IT online store has no products listed yet. Please check back soon or use the contact form to ask about availability.'
            : null;
    }

    if ($shopIntent && preg_match('/\b(what|list|show|available|have|sell|offer|products?|items?|stock|shop|store)\b/', $normalized)) {
        $scored = [];
        foreach ($products as $product) {
            $score = scoreShopProductForQuestion($product, $normalized, $words);
            if ($score > 0) {
                $scored[] = ['score' => $score, 'product' => $product];
            }
        }

        usort($scored, static function (array $a, array $b): int {
            return ($b['score'] <=> $a['score']);
        });

        if ($scored !== []) {
            $matches = array_map(static function (array $row): array {
                return $row['product'];
            }, array_slice($scored, 0, 5));

            return formatShopCatalogForAssistant($matches, 'Matching IT shop products:');
        }

        return formatShopCatalogForAssistant($products);
    }

    $bestScore = 0;
    $bestProduct = null;
    $matches = [];

    foreach ($products as $product) {
        $score = scoreShopProductForQuestion($product, $normalized, $words);
        if ($score <= 0) {
            continue;
        }
        $matches[] = ['score' => $score, 'product' => $product];
        if ($score > $bestScore) {
            $bestScore = $score;
            $bestProduct = $product;
        }
    }

    if ($bestProduct === null) {
        return null;
    }

    if ($bestScore >= 4 && count($matches) === 1) {
        return formatShopProductDetailForAssistant($bestProduct);
    }

    usort($matches, static function (array $a, array $b): int {
        return ($b['score'] <=> $a['score']);
    });

    $topProducts = array_map(static function (array $row): array {
        return $row['product'];
    }, array_slice($matches, 0, 5));

    return formatShopCatalogForAssistant($topProducts, 'Matching IT shop products:');
}

function shopCatalogAssistantContext(): string
{
    $products = listPublicShopProducts();
    if ($products === []) {
        return 'IT online store: no products are currently listed.';
    }

    $lines = ['IT online store products:'];
    foreach (array_slice($products, 0, 12) as $product) {
        $lines[] = '- ' . trim(((string) ($product['brand'] ?? '')) . ' ' . ((string) ($product['name'] ?? 'Product')))
            . ' | ' . ((string) ($product['currency'] ?? 'PKR')) . ' ' . number_format((float) ($product['price'] ?? 0), 0)
            . ' | ' . ((string) ($product['stock_label'] ?? 'Available'))
            . (!empty($product['description']) ? ' | ' . (string) $product['description'] : '');
    }

    $lines[] = 'Visitors can order from the IT Shop section on the homepage.';

    return implode("\n", $lines);
}

require_once __DIR__ . '/shop-ledger.php';
