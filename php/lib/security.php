<?php
declare(strict_types=1);

/**
 * Shared request hardening for public PHP endpoints.
 */
function blockSuspiciousRequest(): void
{
    $query = (string) ($_SERVER['QUERY_STRING'] ?? '');
    if ($query === '') {
        return;
    }

    $patterns = [
        '/(<|%3C).*script/i',
        '/union\s+select/i',
        '/insert\s+into/i',
        '/drop\s+table/i',
        '/php:\/\//i',
        '/\.\.\//',
        '/base64_encode/i',
    ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $query)) {
            jsonResponse(['error' => 'Invalid request.'], 400);
        }
    }
}

function rejectDangerousUploadName(string $filename): void
{
    if (preg_match('/\.(php|phtml|phar|js|html|htm|cgi|pl|py|sh|exe|bat|cmd)$/i', $filename)) {
        throw new InvalidArgumentException('File type is not allowed.');
    }

    if (strpos($filename, '..') !== false || strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
        throw new InvalidArgumentException('Invalid file name.');
    }
}
