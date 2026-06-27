<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/shop-services.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    requireAdminPermission('shop.view');
    $report = sanitizeText($_GET['report'] ?? '', 20);
    if ($report !== '') {
        try {
            jsonResponse([
                'status' => 'ok',
                'report' => shopBuildReport(
                    $report,
                    isset($_GET['from']) ? (string) $_GET['from'] : null,
                    isset($_GET['to']) ? (string) $_GET['to'] : null
                ),
            ]);
        } catch (InvalidArgumentException $e) {
            jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    jsonResponse([
        'status' => 'ok',
        'shop' => shopAdminSummary(),
    ]);
}

requirePost();
requireCsrfFromRequest();
requireSameOrigin();
requireAdminPermission('shop.edit');

$action = sanitizeText($_POST['action'] ?? '', 40);

switch ($action) {
    case 'save_product':
        try {
            $uploads = collectShopProductUploads($_FILES);

            $result = saveShopProduct([
                'id' => $_POST['id'] ?? '',
                'brand' => $_POST['brand'] ?? '',
                'brand_custom' => $_POST['brand_custom'] ?? '',
                'name' => $_POST['name'] ?? '',
                'description' => $_POST['description'] ?? '',
                'price' => $_POST['price'] ?? 0,
                'cost_price' => $_POST['cost_price'] ?? 0,
                'currency' => $_POST['currency'] ?? 'PKR',
                'category' => $_POST['category'] ?? '',
                'category_custom' => $_POST['category_custom'] ?? '',
                'keep_images' => $_POST['keep_images'] ?? '',
                'track_stock' => !empty($_POST['track_stock']),
                'stock' => $_POST['stock'] ?? 0,
                'active' => !empty($_POST['active']),
            ], $uploads);

            $product = $result['product'];
            $merged = !empty($result['merged']);
            $message = $merged
                ? 'Same brand and model already listed — stock merged (' . (int) ($product['stock'] ?? 0) . ' total).'
                : 'Product saved.';

            jsonResponse([
                'status' => 'ok',
                'message' => $message,
                'merged' => $merged,
                'product' => $product,
                'shop' => shopAdminSummary(),
            ]);
        } catch (InvalidArgumentException $e) {
            jsonResponse(['error' => $e->getMessage()], 400);
        } catch (Throwable $e) {
            appLog('shop-admin save_product: ' . $e->getMessage());
            jsonResponse(['error' => 'Product could not be saved. ' . $e->getMessage()], 500);
        }
        break;

    case 'record_purchase':
        try {
            $productId = sanitizeText($_POST['product_id'] ?? '', 40);
            $quantity = max(1, (int) ($_POST['quantity'] ?? 1));
            $unitCost = max(0, (float) ($_POST['unit_cost'] ?? 0));
            $note = sanitizeText($_POST['note'] ?? '', 500);
            $result = recordShopPurchase($productId, $quantity, $unitCost, $note);
            jsonResponse([
                'status' => 'ok',
                'message' => 'Purchase recorded and stock updated.',
                'product' => $result['product'],
                'entry' => $result['entry'],
                'shop' => shopAdminSummary(),
            ]);
        } catch (InvalidArgumentException $e) {
            jsonResponse(['error' => $e->getMessage()], 400);
        }
        break;

    case 'record_stock_out':
        try {
            $productId = sanitizeText($_POST['product_id'] ?? '', 40);
            $quantity = max(1, (int) ($_POST['quantity'] ?? 1));
            $note = sanitizeText($_POST['note'] ?? '', 500);
            $result = recordShopStockOut($productId, $quantity, $note);
            jsonResponse([
                'status' => 'ok',
                'message' => 'Stock out recorded.',
                'product' => $result['product'],
                'entry' => $result['entry'],
                'shop' => shopAdminSummary(),
            ]);
        } catch (InvalidArgumentException $e) {
            jsonResponse(['error' => $e->getMessage()], 400);
        }
        break;

    case 'delete_product':
        $id = sanitizeText($_POST['id'] ?? '', 40);
        if ($id === '' || !deleteShopProduct($id)) {
            jsonResponse(['error' => 'Product could not be deleted.'], 400);
        }
        jsonResponse([
            'status' => 'ok',
            'message' => 'Product deleted.',
            'shop' => shopAdminSummary(),
        ]);
        break;

    case 'update_order_status':
        $orderId = sanitizeText($_POST['order_id'] ?? '', 40);
        $status = sanitizeText($_POST['status'] ?? '', 20);
        if (!updateShopOrderStatus($orderId, $status)) {
            jsonResponse(['error' => 'Order status could not be updated.'], 400);
        }
        jsonResponse([
            'status' => 'ok',
            'message' => 'Order status updated.',
            'shop' => shopAdminSummary(),
        ]);
        break;

    default:
        jsonResponse(['error' => 'Unknown action.'], 400);
}
