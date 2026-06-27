<?php
declare(strict_types=1);

function visitorStorageDir(): string
{
    $dir = __DIR__ . '/../storage/visitors';
    if (!is_dir($dir)) {
        mkdir($dir, 0700, true);
    }

    return rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
}

function visitorStoragePath(): string
{
    return visitorStorageDir() . 'counts.json';
}

function accessPolicyPath(): string
{
    return __DIR__ . '/../storage/access-policy.json';
}

function defaultAccessPolicy(): array
{
    return [
        'blocked_ips' => [],
        'admin_ip_whitelist' => [],
        'visitor_log' => [],
    ];
}

function loadAccessPolicy(): array
{
    $path = accessPolicyPath();
    if (!is_readable($path)) {
        return defaultAccessPolicy();
    }

    $data = json_decode((string) file_get_contents($path), true);
    if (!is_array($data)) {
        return defaultAccessPolicy();
    }

    return array_merge(defaultAccessPolicy(), $data);
}

function saveAccessPolicy(array $policy): void
{
    $dir = dirname(accessPolicyPath());
    if (!is_dir($dir)) {
        mkdir($dir, 0700, true);
    }

    file_put_contents(accessPolicyPath(), json_encode($policy, JSON_PRETTY_PRINT), LOCK_EX);
}

function normalizeIpList(array $ips): array
{
    $result = [];
    foreach ($ips as $ip) {
        $ip = trim((string) $ip);
        if ($ip === '') {
            continue;
        }
        if (preg_match('/^[a-zA-Z0-9:.]+$/', $ip)) {
            $result[] = $ip;
        }
    }

    return array_values(array_unique($result));
}

function getAdminIpWhitelist(): array
{
    $policy = loadAccessPolicy();
    $stored = normalizeIpList($policy['admin_ip_whitelist'] ?? []);
    if ($stored !== []) {
        return $stored;
    }

    $configValue = configValue('ADMIN_IP_WHITELIST');
    if ($configValue === '') {
        return [];
    }

    return normalizeIpList(explode(',', $configValue));
}

function isAdminIpAllowed(?string $ip = null): bool
{
    $whitelist = getAdminIpWhitelist();
    if ($whitelist === []) {
        return true;
    }

    $ip = $ip ?? clientIp();
    return in_array($ip, $whitelist, true);
}

function requireAdminIpAllowed(): void
{
    if (isAdminIpAllowed()) {
        return;
    }

    logSecurityEvent('blocked admin access from ' . clientIp());

    if (wantsJsonResponse()) {
        jsonResponse(['error' => 'Admin access is restricted to approved IP addresses.'], 403);
    }

    redirectTo('index.php?error=' . urlencode('Admin access is restricted to approved IP addresses only.'));
}

function isIpBlocked(?string $ip = null): bool
{
    $ip = $ip ?? clientIp();
    $policy = loadAccessPolicy();
    $blocked = normalizeIpList($policy['blocked_ips'] ?? []);

    return in_array($ip, $blocked, true);
}

function requirePublicAccessAllowed(): void
{
    if (!isIpBlocked()) {
        return;
    }

    jsonResponse(['error' => 'Access denied.'], 403);
}

function blockVisitorIp(string $ip): void
{
    $policy = loadAccessPolicy();
    $blocked = normalizeIpList($policy['blocked_ips'] ?? []);
    if (!in_array($ip, $blocked, true)) {
        $blocked[] = $ip;
    }
    $policy['blocked_ips'] = $blocked;
    saveAccessPolicy($policy);
    logSecurityEvent('blocked visitor IP ' . $ip . ' by admin');
}

function unblockVisitorIp(string $ip): void
{
    $policy = loadAccessPolicy();
    $blocked = normalizeIpList($policy['blocked_ips'] ?? []);
    $policy['blocked_ips'] = array_values(array_filter(
        $blocked,
        static fn(string $item): bool => $item !== $ip
    ));
    saveAccessPolicy($policy);
    logSecurityEvent('unblocked visitor IP ' . $ip . ' by admin');
}

function saveAdminIpWhitelist(array $ips): void
{
    $policy = loadAccessPolicy();
    $policy['admin_ip_whitelist'] = normalizeIpList($ips);
    saveAccessPolicy($policy);
    logSecurityEvent('updated admin IP whitelist');
}

function parseReferrerLabel(?string $referrer): string
{
    $referrer = trim((string) $referrer);
    if ($referrer === '') {
        return 'Direct visit';
    }

    $host = parse_url($referrer, PHP_URL_HOST);
    if (is_string($host) && $host !== '') {
        return $host;
    }

    return $referrer;
}

function appendVisitorLog(array $entry): void
{
    $policy = loadAccessPolicy();
    $log = is_array($policy['visitor_log'] ?? null) ? $policy['visitor_log'] : [];

    array_unshift($log, $entry);
    $policy['visitor_log'] = array_slice($log, 0, 200);
    saveAccessPolicy($policy);
}

function listVisitorLog(int $limit = 50): array
{
    $policy = loadAccessPolicy();
    $log = is_array($policy['visitor_log'] ?? null) ? $policy['visitor_log'] : [];

    return array_slice($log, 0, $limit);
}

function defaultVisitorData(): array
{
    return [
        'total' => 0,
        'today' => 0,
        'page_views' => 0,
        'date' => date('Y-m-d'),
        'updated' => date('c'),
    ];
}

function loadVisitorData(): array
{
    $path = visitorStoragePath();
    if (!is_readable($path)) {
        return defaultVisitorData();
    }

    $data = json_decode((string) file_get_contents($path), true);
    if (!is_array($data)) {
        return defaultVisitorData();
    }

    return array_merge(defaultVisitorData(), $data);
}

function publicVisitorStats(?array $data = null): array
{
    $data = $data ?? loadVisitorData();
    $today = date('Y-m-d');

    if (($data['date'] ?? '') !== $today) {
        $data['today'] = 0;
    }

    return [
        'total' => (int) ($data['total'] ?? 0),
        'today' => (int) ($data['today'] ?? 0),
        'page_views' => (int) ($data['page_views'] ?? 0),
        'updated' => $data['updated'] ?? null,
    ];
}

function trackVisit(?string $page = null): array
{
    requirePublicAccessAllowed();

    $page = sanitizeText($page ?? ($_POST['page'] ?? '/index.html'), 200);
    $referrer = sanitizeText($_SERVER['HTTP_REFERER'] ?? '', 500);
    $userAgent = sanitizeText($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 300);

    appendVisitorLog([
        'id' => bin2hex(random_bytes(6)),
        'ip' => clientIp(),
        'referrer' => parseReferrerLabel($referrer),
        'referrer_url' => $referrer,
        'user_agent' => $userAgent,
        'page' => $page,
        'time' => date('c'),
    ]);

    $path = visitorStoragePath();
    $handle = fopen($path, 'c+');
    if ($handle === false) {
        return publicVisitorStats();
    }

    try {
        if (!flock($handle, LOCK_EX)) {
            return publicVisitorStats();
        }

        $raw = stream_get_contents($handle);
        $data = defaultVisitorData();
        if ($raw !== false && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $data = array_merge($data, $decoded);
            }
        }

        $today = date('Y-m-d');
        if (($data['date'] ?? '') !== $today) {
            $data['today'] = 0;
            $data['date'] = $today;
        }

        $data['page_views'] = (int) ($data['page_views'] ?? 0) + 1;

        if (empty($_SESSION['visitor_counted'])) {
            $_SESSION['visitor_counted'] = true;
            $data['total'] = (int) ($data['total'] ?? 0) + 1;
            $data['today'] = (int) ($data['today'] ?? 0) + 1;
        }

        $data['updated'] = date('c');

        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, json_encode($data, JSON_PRETTY_PRINT));
        fflush($handle);
        flock($handle, LOCK_UN);

        return publicVisitorStats($data);
    } finally {
        fclose($handle);
    }
}

function contactStorageDir(): string
{
    $dir = __DIR__ . '/../storage/contact-messages';
    if (!is_dir($dir)) {
        mkdir($dir, 0700, true);
    }

    return rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
}

function saveContactMessage(string $name, string $email, string $message, string $service = ''): string
{
    requirePublicAccessAllowed();

    $filename = 'msg_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.json';
    $payload = [
        'name' => $name,
        'email' => $email,
        'service' => $service,
        'message' => $message,
        'ip' => clientIp(),
        'created' => date('c'),
    ];

    file_put_contents(
        contactStorageDir() . $filename,
        json_encode($payload, JSON_PRETTY_PRINT),
        LOCK_EX
    );

    require_once __DIR__ . '/db-records.php';
    dbSaveContactMessageRecord($payload, basename($filename, '.json'));

    return $filename;
}

function listContactMessages(int $limit = 20): array
{
    require_once __DIR__ . '/database.php';
    require_once __DIR__ . '/db-records.php';
    if (databaseReady()) {
        $messages = dbListContactMessages($limit);
        if ($messages !== []) {
            return $messages;
        }
    }

    $files = glob(contactStorageDir() . 'msg_*.json') ?: [];
    rsort($files);
    $messages = [];

    foreach (array_slice($files, 0, $limit) as $file) {
        $data = json_decode((string) file_get_contents($file), true);
        if (is_array($data)) {
            $messages[] = $data;
        }
    }

    return $messages;
}

function contactMessageCount(): int
{
    require_once __DIR__ . '/database.php';
    require_once __DIR__ . '/db-records.php';
    if (databaseReady()) {
        $count = dbContactMessageCount();
        if ($count > 0) {
            return $count;
        }
    }

    return count(glob(contactStorageDir() . 'msg_*.json') ?: []);
}

function logSecurityEvent(string $event): void
{
    appLog('security: ' . $event);
}

function adminAccessSummary(): array
{
    $policy = loadAccessPolicy();

    return [
        'current_ip' => clientIp(),
        'admin_ip_allowed' => isAdminIpAllowed(),
        'admin_ip_whitelist' => getAdminIpWhitelist(),
        'blocked_ips' => normalizeIpList($policy['blocked_ips'] ?? []),
        'visitor_log' => listVisitorLog(200),
    ];
}
