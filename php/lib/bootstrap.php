<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
        'cookie_secure' => $isHttps,
        'use_strict_mode' => true,
    ]);
}

function jsonResponse(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data);
    exit;
}

function requirePost(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'Method not allowed'], 405);
    }
}

function readJsonBody(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return [];
    }

    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function sanitizeText(string $value, int $maxLength = 2000): string
{
    $value = trim(strip_tags($value));
    if (strlen($value) > $maxLength) {
        $value = substr($value, 0, $maxLength);
    }
    return $value;
}

function clientIp(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    return preg_replace('/[^a-zA-Z0-9:.]/', '', $ip) ?: 'unknown';
}

require_once __DIR__ . '/security.php';
blockSuspiciousRequest();

function appLog(string $message): void
{
    $dir = __DIR__ . '/../storage/logs';
    if (!is_dir($dir)) {
        mkdir($dir, 0700, true);
    }

    $line = date('c') . ' ' . $message . PHP_EOL;
    file_put_contents($dir . '/app.log', $line, FILE_APPEND | LOCK_EX);
}

function requireSameOrigin(): void
{
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    if ($host === '') {
        return;
    }

    $origin = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');
    if ($origin !== '') {
        $originHost = parse_url($origin, PHP_URL_HOST);
        if (is_string($originHost) && strtolower($originHost) !== $host) {
            jsonResponse(['error' => 'Invalid request origin.'], 403);
        }
        return;
    }

    $referer = (string) ($_SERVER['HTTP_REFERER'] ?? '');
    if ($referer === '') {
        return;
    }

    $refererHost = parse_url($referer, PHP_URL_HOST);
    if (is_string($refererHost) && strtolower($refererHost) !== $host) {
        jsonResponse(['error' => 'Invalid request origin.'], 403);
    }
}
