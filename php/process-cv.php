<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/upload.php';
require_once __DIR__ . '/pdf-extractor.php';
require_once __DIR__ . '/lib/cv-parser.php';
require_once __DIR__ . '/lib/knowledge-builder.php';

function processCvFile(string $filename): array
{
    $path = resolveCvPath($filename);
    $text = extractDocumentText($path);

    if ($text === '') {
        throw new InvalidArgumentException('Could not read text from the uploaded file. Try saving the CV as PDF or DOCX.');
    }

    $parsed = parseCvText($text);
    saveKnowledgeFiles($parsed);

    return [
        'status' => 'success',
        'skills_found' => count($parsed['skills'] ?? []),
        'projects_found' => count($parsed['projects'] ?? []),
        'text_length' => strlen($text),
    ];
}

if (php_sapi_name() !== 'cli' && basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'process-cv.php') {
    requirePost();
    requireAdminAuth();
    requireCsrfFromRequest();

    $filename = basename((string) ($_POST['file'] ?? ''));
    if ($filename === '') {
        jsonResponse(['error' => 'File name is required.'], 400);
    }

    try {
        $result = processCvFile($filename);
        jsonResponse($result);
    } catch (InvalidArgumentException $e) {
        jsonResponse(['error' => $e->getMessage()], 400);
    } catch (Throwable $e) {
        jsonResponse(['error' => 'Processing failed.'], 500);
    }
}
