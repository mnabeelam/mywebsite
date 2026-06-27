<?php
declare(strict_types=1);

require __DIR__ . '/../php/lib/bootstrap.php';
require __DIR__ . '/../php/lib/config.php';
require __DIR__ . '/../php/lib/site-settings.php';
require __DIR__ . '/../php/lib/site-services.php';
require __DIR__ . '/../php/lib/shop-services.php';

echo "=== Portfolio local integration tests ===\n\n";

$contactId = saveContactMessage(
    'Test User',
    'test@example.com',
    'Automated local test message from scripts/test-forms-cli.php',
    'General Inquiry'
);
$contactFile = contactStorageDir() . $contactId;
echo '[contact] saved: ' . (is_file($contactFile) ? 'yes' : 'no') . " ({$contactId})\n";

$orderId = saveShopOrder(
    'prod_2423ab9cfede',
    1,
    'Test Buyer',
    'buyer@example.com',
    '03001234567',
    '123 Test Street, Gujranwala',
    'Local CLI test order'
);
$orderFile = shopOrdersDir() . $orderId . '.json';
echo '[shop] saved: ' . (is_file($orderFile) ? 'yes' : 'no') . " ({$orderId})\n";

$emailConfigured = adminNotificationEmails() !== [];
echo '[mail] admin recipients configured: ' . ($emailConfigured ? 'yes' : 'no') . "\n";
echo "[mail] PHP mail() on Windows often fails locally — orders/messages are still saved.\n";
echo "[mail] For production, ask hosting to enable mail() or configure SMTP in config/local.php.\n\n";

echo "Done.\n";
