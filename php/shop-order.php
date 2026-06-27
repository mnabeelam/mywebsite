<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/rate-limit.php';
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/site-settings.php';
require_once __DIR__ . '/lib/shop-services.php';

requirePost();
requireSameOrigin();
rateLimit('shop_order', 12, 3600);

$name = sanitizeText($_POST['name'] ?? '', 120);
$email = sanitizeText($_POST['email'] ?? '', 180);
$phone = sanitizeText($_POST['phone'] ?? '', 40);
$address = sanitizeText($_POST['address'] ?? '', 500);
$notes = sanitizeText($_POST['notes'] ?? '', 1000);
$itemsRaw = trim((string) ($_POST['items'] ?? ''));

if ($name === '' || $email === '' || $phone === '' || $address === '') {
    jsonResponse(['error' => 'Name, email, phone, and delivery address are required.'], 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['error' => 'Please enter a valid email address.'], 400);
}

try {
    if ($itemsRaw !== '') {
        $items = json_decode($itemsRaw, true);
        if (!is_array($items) || $items === []) {
            jsonResponse(['error' => 'Your cart is empty.'], 400);
        }

        $orderIds = saveShopCartOrders($items, $name, $email, $phone, $address, $notes);
        $emailSent = notifyAdminNewShopOrders($orderIds, $name, $email, $phone, $address, $notes);

        jsonResponse([
            'status' => 'ok',
            'message' => $emailSent
                ? 'Thank you. Your order was received. I will contact you by email to confirm payment and delivery.'
                : 'Thank you. Your order was saved — I will confirm payment and delivery by email shortly.',
            'order_ids' => $orderIds,
            'email_notified' => $emailSent,
        ]);
    }

    $productId = sanitizeText($_POST['product_id'] ?? '', 40);
    $quantity = (int) ($_POST['quantity'] ?? 1);

    if ($productId === '') {
        jsonResponse(['error' => 'Product, name, email, phone, and delivery address are required.'], 400);
    }

    $orderId = saveShopOrder($productId, $quantity, $name, $email, $phone, $address, $notes);
    $emailSent = notifyAdminNewShopOrders([$orderId], $name, $email, $phone, $address, $notes);

    jsonResponse([
        'status' => 'ok',
        'message' => $emailSent
            ? 'Thank you. Your order was received. I will contact you by email to confirm payment and delivery.'
            : 'Thank you. Your order was saved — I will confirm payment and delivery by email shortly.',
        'order_id' => $orderId,
        'email_notified' => $emailSent,
    ]);
} catch (InvalidArgumentException $e) {
    jsonResponse(['error' => $e->getMessage()], 400);
} catch (Throwable $e) {
    appLog('shop-order: ' . $e->getMessage());
    jsonResponse(['error' => 'Could not place your order. Please try again later.'], 500);
}
