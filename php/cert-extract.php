<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/rate-limit.php';
require_once __DIR__ . '/lib/cert-services.php';
require_once __DIR__ . '/lib/cert-parser.php';

requirePost();
requireAdminAuth();
requireCsrfFromRequest();
requireSameOrigin();
rateLimit('cert_extract', 30, 3600);

try {
    $file = certUploadFromRequest();
    $extracted = extractCertFieldsFromUpload($file);

    jsonResponse([
        'status' => 'ok',
        'message' => (string) ($extracted['message'] ?? 'Certificate details were read from the document. Review the fields, then click Save.'),
        'extracted' => [
            'title' => (string) ($extracted['title'] ?? ''),
            'issuer' => (string) ($extracted['issuer'] ?? ''),
            'year' => (string) ($extracted['year'] ?? ''),
            'description' => (string) ($extracted['description'] ?? ''),
        ],
        'source' => (string) ($extracted['source'] ?? 'unknown'),
    ]);
} catch (InvalidArgumentException $e) {
    jsonResponse(['error' => $e->getMessage()], 400);
} catch (Throwable $e) {
    appLog('cert-extract: ' . $e->getMessage());
    jsonResponse(['error' => 'Could not read the certificate file.'], 500);
}
