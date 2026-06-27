<?php
declare(strict_types=1);

function shopLedgerPath(): string
{
    return shopStorageDir() . 'ledger.json';
}

function defaultShopLedger(): array
{
    return [
        'entries' => [],
        'updated' => date('c'),
    ];
}

function loadShopLedger(): array
{
    $path = shopLedgerPath();
    if (!is_readable($path)) {
        return defaultShopLedger();
    }

    $data = json_decode((string) file_get_contents($path), true);
    if (!is_array($data) || !is_array($data['entries'] ?? null)) {
        return defaultShopLedger();
    }

    return array_merge(defaultShopLedger(), $data);
}

function saveShopLedger(array $ledger): void
{
    $ledger['updated'] = date('c');
    file_put_contents(shopLedgerPath(), json_encode($ledger, JSON_PRETTY_PRINT), LOCK_EX);
}

function normalizeLedgerEntry(array $entry): array
{
    $quantity = max(0, (int) ($entry['quantity'] ?? 0));
    $unitCost = max(0, (float) ($entry['unit_cost'] ?? 0));
    $unitPrice = max(0, (float) ($entry['unit_price'] ?? 0));
    $totalCost = array_key_exists('total_cost', $entry)
        ? max(0, (float) $entry['total_cost'])
        : round($unitCost * $quantity, 2);
    $totalSale = array_key_exists('total_sale', $entry)
        ? max(0, (float) $entry['total_sale'])
        : round($unitPrice * $quantity, 2);
    $profit = array_key_exists('profit', $entry)
        ? (float) $entry['profit']
        : round($totalSale - $totalCost, 2);

    return [
        'id' => sanitizeText((string) ($entry['id'] ?? generateShopId('txn')), 40),
        'type' => sanitizeText((string) ($entry['type'] ?? 'adjustment'), 20),
        'product_id' => sanitizeText((string) ($entry['product_id'] ?? ''), 40),
        'brand' => sanitizeText((string) ($entry['brand'] ?? ''), 80),
        'product_name' => sanitizeText((string) ($entry['product_name'] ?? ''), 200),
        'quantity' => $quantity,
        'unit_cost' => $unitCost,
        'unit_price' => $unitPrice,
        'total_cost' => $totalCost,
        'total_sale' => $totalSale,
        'profit' => $profit,
        'currency' => sanitizeText((string) ($entry['currency'] ?? 'PKR'), 8),
        'reference' => sanitizeText((string) ($entry['reference'] ?? ''), 80),
        'note' => sanitizeText((string) ($entry['note'] ?? ''), 500),
        'created' => (string) ($entry['created'] ?? date('c')),
    ];
}

function appendShopLedgerEntry(array $entry): array
{
    $record = normalizeLedgerEntry($entry);
    $ledger = loadShopLedger();
    $ledger['entries'][] = $record;
    saveShopLedger($ledger);

    require_once __DIR__ . '/database.php';
    if (databaseReady()) {
        require_once __DIR__ . '/db-records.php';
        dbAppendLedgerEntry($record);
    }

    return $record;
}

function ledgerEntryTimestamp(array $entry): int
{
    $created = (string) ($entry['created'] ?? '');
    $time = strtotime($created);

    return $time !== false ? $time : 0;
}

function parseReportDateBoundary(?string $value, bool $endOfDay = false): ?int
{
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }

    $time = strtotime($value . ($endOfDay ? ' 23:59:59' : ' 00:00:00'));
    return $time !== false ? $time : null;
}

function filterLedgerEntries(array $entries, ?string $from = null, ?string $to = null, ?array $types = null): array
{
    $fromTs = parseReportDateBoundary($from, false);
    $toTs = parseReportDateBoundary($to, true);

    return array_values(array_filter($entries, static function (array $entry) use ($fromTs, $toTs, $types): bool {
        $ts = ledgerEntryTimestamp($entry);
        if ($fromTs !== null && $ts < $fromTs) {
            return false;
        }
        if ($toTs !== null && $ts > $toTs) {
            return false;
        }
        if ($types !== null && !in_array((string) ($entry['type'] ?? ''), $types, true)) {
            return false;
        }

        return true;
    }));
}

function sortLedgerEntriesNewestFirst(array $entries): array
{
    usort($entries, static function (array $a, array $b): int {
        return ledgerEntryTimestamp($b) <=> ledgerEntryTimestamp($a);
    });

    return $entries;
}

function logShopStockIn(
    array $product,
    int $quantity,
    string $type,
    float $unitCost = 0.0,
    string $note = '',
    string $reference = ''
): ?array {
    if ($quantity <= 0) {
        return null;
    }

    return appendShopLedgerEntry([
        'type' => $type,
        'product_id' => (string) ($product['id'] ?? ''),
        'brand' => (string) ($product['brand'] ?? ''),
        'product_name' => (string) ($product['name'] ?? ''),
        'quantity' => $quantity,
        'unit_cost' => $unitCost,
        'unit_price' => (float) ($product['price'] ?? 0),
        'total_cost' => round($unitCost * $quantity, 2),
        'currency' => (string) ($product['currency'] ?? 'PKR'),
        'reference' => $reference,
        'note' => $note,
    ]);
}

function logShopStockOut(
    array $product,
    int $quantity,
    string $type,
    float $unitPrice = 0.0,
    float $unitCost = 0.0,
    string $note = '',
    string $reference = ''
): ?array {
    if ($quantity <= 0) {
        return null;
    }

    $totalSale = round($unitPrice * $quantity, 2);
    $totalCost = round($unitCost * $quantity, 2);

    return appendShopLedgerEntry([
        'type' => $type,
        'product_id' => (string) ($product['id'] ?? ''),
        'brand' => (string) ($product['brand'] ?? ''),
        'product_name' => (string) ($product['name'] ?? ''),
        'quantity' => $quantity,
        'unit_cost' => $unitCost,
        'unit_price' => $unitPrice,
        'total_cost' => $totalCost,
        'total_sale' => $totalSale,
        'profit' => round($totalSale - $totalCost, 2),
        'currency' => (string) ($product['currency'] ?? 'PKR'),
        'reference' => $reference,
        'note' => $note,
    ]);
}

function applyProductStockDelta(string $productId, int $delta): ?array
{
    $productId = sanitizeText($productId, 40);
    if ($productId === '' || $delta === 0) {
        return null;
    }

    $catalog = loadShopCatalog();
    $updated = null;

    foreach ($catalog['products'] as $index => $product) {
        if (!is_array($product) || ($product['id'] ?? '') !== $productId) {
            continue;
        }

        $product['track_stock'] = true;
        $product['stock'] = max(0, (int) ($product['stock'] ?? 0) + $delta);
        $product['updated'] = date('c');
        $catalog['products'][$index] = normalizeProductRecord($product);
        $updated = $catalog['products'][$index];
        break;
    }

    if ($updated === null) {
        return null;
    }

    saveShopCatalog($catalog);
    return $updated;
}

function recordShopPurchase(string $productId, int $quantity, float $unitCost, string $note = ''): array
{
    $product = findProductById($productId);
    if ($product === null) {
        throw new InvalidArgumentException('Product not found.');
    }

    $quantity = max(1, min(999, $quantity));
    $unitCost = max(0, $unitCost);

    $updated = applyProductStockDelta($productId, $quantity);
    if ($updated === null) {
        throw new InvalidArgumentException('Stock could not be updated.');
    }

    if ($unitCost > 0) {
        $catalog = loadShopCatalog();
        foreach ($catalog['products'] as $index => $item) {
            if (!is_array($item) || ($item['id'] ?? '') !== $productId) {
                continue;
            }
            $catalog['products'][$index]['cost_price'] = $unitCost;
            $catalog['products'][$index]['updated'] = date('c');
            $updated = normalizeProductRecord($catalog['products'][$index]);
            $catalog['products'][$index] = $updated;
            break;
        }
        saveShopCatalog($catalog);
    }

    $entry = logShopStockIn($updated, $quantity, 'purchase', $unitCost, $note !== '' ? $note : 'Manual purchase / stock in');

    return [
        'product' => $updated,
        'entry' => $entry,
    ];
}

function recordShopStockOut(string $productId, int $quantity, string $note = ''): array
{
    $product = findProductById($productId);
    if ($product === null) {
        throw new InvalidArgumentException('Product not found.');
    }

    if (empty($product['track_stock'])) {
        throw new InvalidArgumentException('This product does not track stock.');
    }

    $quantity = max(1, min(999, $quantity));
    $stock = (int) ($product['stock'] ?? 0);
    if ($quantity > $stock) {
        throw new InvalidArgumentException('Stock out quantity exceeds available stock.');
    }

    $updated = applyProductStockDelta($productId, -$quantity);
    if ($updated === null) {
        throw new InvalidArgumentException('Stock could not be updated.');
    }

    $entry = logShopStockOut(
        $updated,
        $quantity,
        'stock_out',
        0.0,
        (float) ($updated['cost_price'] ?? 0),
        $note !== '' ? $note : 'Manual stock out'
    );

    return [
        'product' => $updated,
        'entry' => $entry,
    ];
}

function shopReportInventory(): array
{
    $rows = [];
    $totals = [
        'products' => 0,
        'units' => 0,
        'stock_value' => 0.0,
        'retail_value' => 0.0,
        'potential_profit' => 0.0,
        'currency' => 'PKR',
    ];

    foreach (listAllShopProducts() as $product) {
        $trackStock = productTracksStock($product);
        $stock = $trackStock ? (int) ($product['stock'] ?? 0) : null;
        $cost = (float) ($product['cost_price'] ?? 0);
        $price = (float) ($product['price'] ?? 0);
        $currency = (string) ($product['currency'] ?? 'PKR');
        $units = $trackStock ? $stock : 0;
        $stockValue = $trackStock ? round($cost * $stock, 2) : 0.0;
        $retailValue = $trackStock ? round($price * $stock, 2) : 0.0;

        $rows[] = [
            'product_id' => (string) ($product['id'] ?? ''),
            'brand' => (string) ($product['brand'] ?? ''),
            'product_name' => (string) ($product['name'] ?? ''),
            'category' => (string) ($product['category'] ?? ''),
            'track_stock' => $trackStock,
            'stock' => $stock,
            'stock_label' => productStockLabel($product),
            'unit_cost' => $cost,
            'unit_price' => $price,
            'stock_value' => $stockValue,
            'retail_value' => $retailValue,
            'potential_profit' => round($retailValue - $stockValue, 2),
            'currency' => $currency,
            'active' => !empty($product['active']),
        ];

        $totals['products']++;
        $totals['units'] += $units;
        $totals['stock_value'] += $stockValue;
        $totals['retail_value'] += $retailValue;
        $totals['potential_profit'] += round($retailValue - $stockValue, 2);
        $totals['currency'] = $currency;
    }

    return [
        'type' => 'inventory',
        'title' => 'Inventory Summary',
        'summary' => $totals,
        'rows' => $rows,
    ];
}

function shopReportStockMovements(?string $from = null, ?string $to = null): array
{
    $entries = sortLedgerEntriesNewestFirst(filterLedgerEntries(
        loadShopLedger()['entries'],
        $from,
        $to,
        ['purchase', 'stock_in', 'stock_out', 'sale', 'adjustment']
    ));

    $summary = [
        'stock_in_qty' => 0,
        'stock_out_qty' => 0,
        'purchase_qty' => 0,
        'sale_qty' => 0,
        'movements' => count($entries),
    ];

    $rows = [];
    foreach ($entries as $entry) {
        $type = (string) ($entry['type'] ?? '');
        $qty = (int) ($entry['quantity'] ?? 0);
        $direction = in_array($type, ['purchase', 'stock_in'], true) ? 'IN' : 'OUT';

        if (in_array($type, ['purchase', 'stock_in'], true)) {
            $summary['stock_in_qty'] += $qty;
        } else {
            $summary['stock_out_qty'] += $qty;
        }
        if ($type === 'purchase') {
            $summary['purchase_qty'] += $qty;
        }
        if ($type === 'sale') {
            $summary['sale_qty'] += $qty;
        }

        $rows[] = [
            'created' => (string) ($entry['created'] ?? ''),
            'type' => $type,
            'direction' => $direction,
            'brand' => (string) ($entry['brand'] ?? ''),
            'product_name' => (string) ($entry['product_name'] ?? ''),
            'quantity' => $qty,
            'unit_cost' => (float) ($entry['unit_cost'] ?? 0),
            'unit_price' => (float) ($entry['unit_price'] ?? 0),
            'total_cost' => (float) ($entry['total_cost'] ?? 0),
            'total_sale' => (float) ($entry['total_sale'] ?? 0),
            'currency' => (string) ($entry['currency'] ?? 'PKR'),
            'reference' => (string) ($entry['reference'] ?? ''),
            'note' => (string) ($entry['note'] ?? ''),
        ];
    }

    return [
        'type' => 'stock',
        'title' => 'Stock In / Out Report',
        'summary' => $summary,
        'rows' => $rows,
    ];
}

function shopReportSales(?string $from = null, ?string $to = null): array
{
    $entries = sortLedgerEntriesNewestFirst(filterLedgerEntries(
        loadShopLedger()['entries'],
        $from,
        $to,
        ['sale']
    ));

    $summary = [
        'orders' => count($entries),
        'units_sold' => 0,
        'revenue' => 0.0,
        'currency' => 'PKR',
    ];

    $rows = [];
    foreach ($entries as $entry) {
        $qty = (int) ($entry['quantity'] ?? 0);
        $totalSale = (float) ($entry['total_sale'] ?? 0);
        $summary['units_sold'] += $qty;
        $summary['revenue'] += $totalSale;
        $summary['currency'] = (string) ($entry['currency'] ?? 'PKR');

        $rows[] = [
            'created' => (string) ($entry['created'] ?? ''),
            'reference' => (string) ($entry['reference'] ?? ''),
            'brand' => (string) ($entry['brand'] ?? ''),
            'product_name' => (string) ($entry['product_name'] ?? ''),
            'quantity' => $qty,
            'unit_price' => (float) ($entry['unit_price'] ?? 0),
            'total_sale' => $totalSale,
            'currency' => (string) ($entry['currency'] ?? 'PKR'),
            'note' => (string) ($entry['note'] ?? ''),
        ];
    }

    $summary['revenue'] = round($summary['revenue'], 2);

    return [
        'type' => 'sales',
        'title' => 'Sales Report',
        'summary' => $summary,
        'rows' => $rows,
    ];
}

function shopReportPurchases(?string $from = null, ?string $to = null): array
{
    $entries = sortLedgerEntriesNewestFirst(filterLedgerEntries(
        loadShopLedger()['entries'],
        $from,
        $to,
        ['purchase', 'stock_in']
    ));

    $summary = [
        'entries' => count($entries),
        'units_purchased' => 0,
        'purchase_cost' => 0.0,
        'currency' => 'PKR',
    ];

    $rows = [];
    foreach ($entries as $entry) {
        $qty = (int) ($entry['quantity'] ?? 0);
        $totalCost = (float) ($entry['total_cost'] ?? 0);
        $summary['units_purchased'] += $qty;
        $summary['purchase_cost'] += $totalCost;
        $summary['currency'] = (string) ($entry['currency'] ?? 'PKR');

        $rows[] = [
            'created' => (string) ($entry['created'] ?? ''),
            'type' => (string) ($entry['type'] ?? ''),
            'brand' => (string) ($entry['brand'] ?? ''),
            'product_name' => (string) ($entry['product_name'] ?? ''),
            'quantity' => $qty,
            'unit_cost' => (float) ($entry['unit_cost'] ?? 0),
            'total_cost' => $totalCost,
            'currency' => (string) ($entry['currency'] ?? 'PKR'),
            'reference' => (string) ($entry['reference'] ?? ''),
            'note' => (string) ($entry['note'] ?? ''),
        ];
    }

    $summary['purchase_cost'] = round($summary['purchase_cost'], 2);

    return [
        'type' => 'purchase',
        'title' => 'Purchase / Stock In Report',
        'summary' => $summary,
        'rows' => $rows,
    ];
}

function shopReportProfit(?string $from = null, ?string $to = null): array
{
    $entries = filterLedgerEntries(loadShopLedger()['entries'], $from, $to, ['sale']);
    $summary = [
        'sales_count' => count($entries),
        'units_sold' => 0,
        'revenue' => 0.0,
        'cost_of_goods' => 0.0,
        'gross_profit' => 0.0,
        'margin_percent' => 0.0,
        'currency' => 'PKR',
    ];

    $byProduct = [];
    foreach ($entries as $entry) {
        $qty = (int) ($entry['quantity'] ?? 0);
        $revenue = (float) ($entry['total_sale'] ?? 0);
        $cost = (float) ($entry['total_cost'] ?? 0);
        $profit = (float) ($entry['profit'] ?? ($revenue - $cost));
        $key = (string) ($entry['product_id'] ?? '') . '|' . (string) ($entry['product_name'] ?? '');

        $summary['units_sold'] += $qty;
        $summary['revenue'] += $revenue;
        $summary['cost_of_goods'] += $cost;
        $summary['gross_profit'] += $profit;
        $summary['currency'] = (string) ($entry['currency'] ?? 'PKR');

        if (!isset($byProduct[$key])) {
            $byProduct[$key] = [
                'brand' => (string) ($entry['brand'] ?? ''),
                'product_name' => (string) ($entry['product_name'] ?? ''),
                'quantity' => 0,
                'revenue' => 0.0,
                'cost' => 0.0,
                'profit' => 0.0,
                'currency' => (string) ($entry['currency'] ?? 'PKR'),
            ];
        }

        $byProduct[$key]['quantity'] += $qty;
        $byProduct[$key]['revenue'] += $revenue;
        $byProduct[$key]['cost'] += $cost;
        $byProduct[$key]['profit'] += $profit;
    }

    $summary['revenue'] = round($summary['revenue'], 2);
    $summary['cost_of_goods'] = round($summary['cost_of_goods'], 2);
    $summary['gross_profit'] = round($summary['gross_profit'], 2);
    if ($summary['revenue'] > 0) {
        $summary['margin_percent'] = round(($summary['gross_profit'] / $summary['revenue']) * 100, 2);
    }

    $rows = array_values(array_map(static function (array $row): array {
        $row['revenue'] = round($row['revenue'], 2);
        $row['cost'] = round($row['cost'], 2);
        $row['profit'] = round($row['profit'], 2);
        $row['margin_percent'] = $row['revenue'] > 0
            ? round(($row['profit'] / $row['revenue']) * 100, 2)
            : 0.0;
        return $row;
    }, $byProduct));

    usort($rows, static function (array $a, array $b): int {
        return ($b['profit'] <=> $a['profit']);
    });

    return [
        'type' => 'profit',
        'title' => 'Profit Report',
        'summary' => $summary,
        'rows' => $rows,
    ];
}

function shopBuildReport(string $type, ?string $from = null, ?string $to = null): array
{
    $type = sanitizeText($type, 20);

    return match ($type) {
        'inventory' => shopReportInventory(),
        'stock' => shopReportStockMovements($from, $to),
        'sales' => shopReportSales($from, $to),
        'purchase' => shopReportPurchases($from, $to),
        'profit' => shopReportProfit($from, $to),
        default => throw new InvalidArgumentException('Unknown report type.'),
    };
}
