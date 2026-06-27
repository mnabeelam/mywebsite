<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/upload.php';

requirePost();
requireAdminAuth();
requireCsrfFromRequest();
requireSameOrigin();
rateLimit('admin_upload', 10, 3600);

if (empty($_FILES['cv'])) {
    jsonResponse(['error' => 'No file uploaded.'], 400);
}

try {
    $saved = saveUploadedCv($_FILES['cv']);

    require_once __DIR__ . '/process-cv.php';
    processCvFile($saved['filename']);

    jsonResponse([
        'status' => 'success',
        'message' => 'CV uploaded and knowledge base updated for the Quick Assistant.',
        'file' => $saved['filename'],
    ]);
} catch (InvalidArgumentException $e) {
    jsonResponse(['error' => $e->getMessage()], 400);
} catch (Throwable $e) {
    appLog('upload-and-process: ' . $e->getMessage());
    jsonResponse(['error' => 'Upload and processing failed.'], 500);
}
