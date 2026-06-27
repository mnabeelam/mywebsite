<?php
declare(strict_types=1);

const CV_MAX_BYTES = 5 * 1024 * 1024;

function cvUploadDir(): string
{
    $dir = realpath(__DIR__ . '/../../uploads/cv');
    if ($dir === false) {
        $target = __DIR__ . '/../../uploads/cv';
        if (!is_dir($target) && !mkdir($target, 0750, true)) {
            throw new RuntimeException('Upload directory could not be created.');
        }
        $dir = realpath($target);
    }

    if ($dir === false) {
        throw new RuntimeException('Upload directory is not available.');
    }

    return rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
}

function allowedCvExtensions(): array
{
    return ['pdf', 'doc', 'docx'];
}

function allowedCvMimeTypes(): array
{
    return [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];
}

function secureCvFilename(string $originalName): string
{
    rejectDangerousUploadName($originalName);

    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($ext, allowedCvExtensions(), true)) {
        throw invalidUploadException('Only PDF, DOC, and DOCX files are allowed.');
    }

    return 'cv_' . date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
}

function invalidUploadException(string $message): InvalidArgumentException
{
    return new InvalidArgumentException($message);
}

function saveUploadedCv(array $file): array
{
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        throw invalidUploadException('Upload failed. Please try again.');
    }

    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw invalidUploadException('No valid file was uploaded.');
    }

    if (($file['size'] ?? 0) > CV_MAX_BYTES) {
        throw invalidUploadException('File is too large. Maximum size is 5 MB.');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? finfo_file($finfo, $file['tmp_name']) : '';
    if ($finfo) {
        finfo_close($finfo);
    }

    if (!in_array($mime, allowedCvMimeTypes(), true)) {
        throw invalidUploadException('File type is not allowed.');
    }

    $filename = secureCvFilename($file['name'] ?? 'upload.pdf');
    $destination = cvUploadDir() . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Could not save uploaded file.');
    }

    chmod($destination, 0640);

    return [
        'filename' => $filename,
        'path' => $destination,
        'size' => (int) $file['size'],
    ];
}

function resolveCvPath(string $filename): string
{
    $filename = basename($filename);
    $path = cvUploadDir() . $filename;
    $real = realpath($path);

    if ($real === false || !is_file($real)) {
        throw invalidUploadException('Requested file was not found.');
    }

    $uploadRoot = realpath(cvUploadDir());
    if ($uploadRoot === false || strpos($real, $uploadRoot) !== 0) {
        throw invalidUploadException('Invalid file path.');
    }

    return $real;
}
