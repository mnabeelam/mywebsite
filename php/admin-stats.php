<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/site-services.php';
require_once __DIR__ . '/lib/shop-services.php';

requireAdminAuth();

jsonResponse([
    'status' => 'ok',
    'visitors' => publicVisitorStats(),
    'contact_messages' => contactMessageCount(),
    'shop_orders' => shopOrderCount(),
    'pending_shop_orders' => count(array_filter(
        listShopOrders(100),
        static fn(array $order): bool => ($order['status'] ?? 'pending') === 'pending'
    )),
    'recent_messages' => listContactMessages(10),
    'recent_shop_orders' => listShopOrders(8),
    'access' => adminAccessSummary(),
]);
