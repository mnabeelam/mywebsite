<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/upload.php';

requirePost();
requireAdminAuth();
requireCsrfFromRequest();

if (empty($_FILES['cv'])) {
    jsonResponse(['error' => 'No file uploaded.'], 400);
}

try {
    $saved = saveUploadedCv($_FILES['cv']);
    jsonResponse([
        'status' => 'uploaded',
        'file' => $saved['filename'],
        'size' => $saved['size'],
    ]);
} catch (InvalidArgumentException $e) {
    jsonResponse(['error' => $e->getMessage()], 400);
} catch (Throwable $e) {
    jsonResponse(['error' => 'Upload failed.'], 500);
}
