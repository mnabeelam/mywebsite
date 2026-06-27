<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/site-services.php';

requirePost();
requireAdminAuth();
requireCsrfFromRequest();
requireSameOrigin();
requireAdminPermission('network.edit');

$action = sanitizeText($_POST['action'] ?? '', 40);
$ip = sanitizeText($_POST['ip'] ?? '', 80);

switch ($action) {
    case 'block_ip':
        if ($ip === '' || !preg_match('/^[a-zA-Z0-9:.]+$/', $ip)) {
            jsonResponse(['error' => 'Valid IP address is required.'], 400);
        }
        blockVisitorIp($ip);
        jsonResponse(['status' => 'ok', 'message' => 'Visitor blocked.', 'access' => adminAccessSummary()]);
        break;

    case 'unblock_ip':
        if ($ip === '' || !preg_match('/^[a-zA-Z0-9:.]+$/', $ip)) {
            jsonResponse(['error' => 'Valid IP address is required.'], 400);
        }
        unblockVisitorIp($ip);
        jsonResponse(['status' => 'ok', 'message' => 'Visitor unblocked.', 'access' => adminAccessSummary()]);
        break;

    case 'save_whitelist':
        $raw = (string) ($_POST['whitelist'] ?? '');
        $ips = preg_split('/\r\n|\r|\n|,/', $raw) ?: [];
        saveAdminIpWhitelist($ips);
        jsonResponse([
            'status' => 'ok',
            'message' => 'Admin IP whitelist saved.',
            'access' => adminAccessSummary(),
        ]);
        break;

    case 'add_current_ip':
        $current = clientIp();
        $whitelist = getAdminIpWhitelist();
        $whitelist[] = $current;
        saveAdminIpWhitelist($whitelist);
        jsonResponse([
            'status' => 'ok',
            'message' => 'Your current IP was added to the admin whitelist.',
            'access' => adminAccessSummary(),
        ]);
        break;

    default:
        jsonResponse(['error' => 'Unknown action.'], 400);
}
