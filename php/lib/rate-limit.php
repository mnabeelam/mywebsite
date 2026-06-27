<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function rateLimitStorageDir(): string
{
    $dir = __DIR__ . '/../storage/rate-limits';
    if (!is_dir($dir)) {
        mkdir($dir, 0700, true);
    }
    return $dir;
}

function rateLimit(string $bucket, int $maxRequests = 30, int $windowSeconds = 3600): void
{
    $now = time();
    $ip = clientIp();
    $safeBucket = preg_replace('/[^a-z0-9_-]/', '_', strtolower($bucket)) ?: 'default';
    $file = rateLimitStorageDir() . '/' . $safeBucket . '_' . sha1($ip) . '.json';

    $handle = fopen($file, 'c+');
    if ($handle === false) {
        return;
    }

    try {
        if (!flock($handle, LOCK_EX)) {
            return;
        }

        $raw = stream_get_contents($handle);
        $state = ['count' => 0, 'start' => $now];
        if ($raw !== false && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $state = $decoded;
            }
        }

        if (($now - (int) ($state['start'] ?? $now)) > $windowSeconds) {
            $state = ['count' => 0, 'start' => $now];
        }

        $state['count'] = (int) ($state['count'] ?? 0) + 1;

        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, json_encode($state));
        fflush($handle);
        flock($handle, LOCK_UN);

        if ($state['count'] > $maxRequests) {
            jsonResponse(['error' => 'Too many requests. Please try again later.'], 429);
        }
    } finally {
        fclose($handle);
    }
}
