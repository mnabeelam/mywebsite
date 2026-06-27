<?php
declare(strict_types=1);

const CERT_MAX_BYTES = 5 * 1024 * 1024;

function certStorageDir(): string
{
    $dir = __DIR__ . '/../storage/certifications';
    if (!is_dir($dir)) {
        mkdir($dir, 0700, true);
    }

    return rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
}

function certCatalogPath(): string
{
    return certStorageDir() . 'catalog.json';
}

function certUploadDir(): string
{
    $dir = realpath(__DIR__ . '/../../uploads/certifications');
    if ($dir === false) {
        $target = __DIR__ . '/../../uploads/certifications';
        if (!is_dir($target) && !mkdir($target, 0750, true)) {
            throw new RuntimeException('Certification upload directory could not be created.');
        }
        $dir = realpath($target);
    }

    if ($dir === false) {
        throw new RuntimeException('Certification upload directory is not available.');
    }

    return rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
}

function defaultCertCatalog(): array
{
    $now = date('c');

    return [
        'certifications' => [
            [
                'id' => 'cert_default_oracle',
                'title' => 'Oracle Database 19c Administration',
                'issuer' => 'Oracle',
                'year' => '',
                'description' => '',
                'filename' => '',
                'file_type' => '',
                'original_name' => '',
                'active' => true,
                'sort_order' => 1,
                'created' => $now,
                'updated' => $now,
            ],
            [
                'id' => 'cert_default_aws_arch',
                'title' => 'AWS Cloud Architecture',
                'issuer' => 'Amazon Web Services',
                'year' => '',
                'description' => '',
                'filename' => '',
                'file_type' => '',
                'original_name' => '',
                'active' => true,
                'sort_order' => 2,
                'created' => $now,
                'updated' => $now,
            ],
            [
                'id' => 'cert_default_aws_found',
                'title' => 'AWS Cloud Foundations',
                'issuer' => 'Amazon Web Services',
                'year' => '',
                'description' => '',
                'filename' => '',
                'file_type' => '',
                'original_name' => '',
                'active' => true,
                'sort_order' => 3,
                'created' => $now,
                'updated' => $now,
            ],
            [
                'id' => 'cert_default_ccna',
                'title' => 'CCNA Training',
                'issuer' => 'Cisco',
                'year' => '',
                'description' => '',
                'filename' => '',
                'file_type' => '',
                'original_name' => '',
                'active' => true,
                'sort_order' => 4,
                'created' => $now,
                'updated' => $now,
            ],
        ],
        'updated' => $now,
    ];
}

function loadCertCatalog(): array
{
    require_once __DIR__ . '/database.php';
    if (databaseReady()) {
        require_once __DIR__ . '/db-records.php';
        $items = dbLoadCertificationRows();
        if ($items !== []) {
            return [
                'certifications' => $items,
                'updated' => date('c'),
            ];
        }
    }

    $path = certCatalogPath();
    if (!is_readable($path)) {
        $defaults = defaultCertCatalog();
        saveCertCatalog($defaults);
        return $defaults;
    }

    $data = json_decode((string) file_get_contents($path), true);
    if (!is_array($data) || !is_array($data['certifications'] ?? null)) {
        return defaultCertCatalog();
    }

    return array_merge(defaultCertCatalog(), $data);
}

function saveCertCatalog(array $catalog): void
{
    $catalog['updated'] = date('c');
    file_put_contents(certCatalogPath(), json_encode($catalog, JSON_PRETTY_PRINT), LOCK_EX);

    require_once __DIR__ . '/database.php';
    if (databaseReady()) {
        require_once __DIR__ . '/db-records.php';
        foreach ($catalog['certifications'] as $cert) {
            if (is_array($cert)) {
                dbSaveCertificationRecord($cert);
            }
        }
    }
}

function generateCertId(): string
{
    return 'cert_' . bin2hex(random_bytes(6));
}

function allowedCertExtensions(): array
{
    return ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
}

function allowedCertMimeTypes(): array
{
    return [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/webp',
    ];
}

function blockedCertMimeTypes(): array
{
    return [
        'application/x-php',
        'application/x-httpd-php',
        'application/php',
        'text/php',
        'text/x-php',
        'text/html',
        'application/xhtml+xml',
        'text/javascript',
        'application/javascript',
        'application/x-javascript',
        'application/x-msdownload',
        'application/x-msdos-program',
        'application/x-executable',
        'application/x-sh',
        'application/x-bat',
        'application/x-csh',
        'application/java-archive',
        'application/zip',
        'application/x-zip-compressed',
        'application/x-rar-compressed',
        'application/x-7z-compressed',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];
}

function dangerousCertExtensions(): array
{
    return [
        'php', 'phtml', 'phar', 'php3', 'php4', 'php5', 'php7', 'php8',
        'exe', 'bat', 'cmd', 'com', 'scr', 'msi', 'dll', 'vbs', 'js',
        'html', 'htm', 'cgi', 'pl', 'py', 'sh', 'jar', 'zip', 'rar',
        '7z', 'gz', 'tar', 'svg', 'doc', 'docx', 'xls', 'xlsx', 'ppt',
    ];
}

function certExtensionForMime(string $mime): string
{
    return match ($mime) {
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        default => '',
    };
}

function validateCertUploadName(string $originalName): string
{
    $originalName = trim($originalName);
    if ($originalName === '' || strlen($originalName) > 200) {
        throw new InvalidArgumentException('Invalid file name.');
    }

    if (str_contains($originalName, "\0")) {
        throw new InvalidArgumentException('Invalid file name.');
    }

    rejectDangerousUploadName($originalName);

    $parts = array_values(array_filter(explode('.', strtolower($originalName)), static fn(string $part): bool => $part !== ''));
    if ($parts === []) {
        throw new InvalidArgumentException('Invalid file name.');
    }

    $extension = (string) end($parts);
    if (!in_array($extension, allowedCertExtensions(), true)) {
        throw new InvalidArgumentException('Only certificate PDF or image files are allowed (PDF, JPG, PNG, WEBP).');
    }

    if (count($parts) > 1) {
        foreach (array_slice($parts, 0, -1) as $part) {
            if (in_array($part, dangerousCertExtensions(), true)) {
                throw new InvalidArgumentException('This file name is not allowed.');
            }
        }
    }

    return $extension;
}

function validateCertFileMagic(string $path, string $mime): void
{
    $handle = fopen($path, 'rb');
    if ($handle === false) {
        throw new InvalidArgumentException('Could not inspect uploaded file.');
    }

    $head = fread($handle, 16);
    fclose($handle);

    if ($head === false || $head === '') {
        throw new InvalidArgumentException('Uploaded file is empty or unreadable.');
    }

    switch ($mime) {
        case 'application/pdf':
            if (!str_starts_with($head, '%PDF')) {
                throw new InvalidArgumentException('This file is not a valid PDF certificate.');
            }
            break;

        case 'image/jpeg':
            if (!str_starts_with($head, "\xFF\xD8\xFF")) {
                throw new InvalidArgumentException('This file is not a valid JPEG image.');
            }
            break;

        case 'image/png':
            if (!str_starts_with($head, "\x89PNG\r\n\x1a\n")) {
                throw new InvalidArgumentException('This file is not a valid PNG image.');
            }
            break;

        case 'image/webp':
            if (!str_starts_with($head, 'RIFF') || strlen($head) < 12 || substr($head, 8, 4) !== 'WEBP') {
                throw new InvalidArgumentException('This file is not a valid WEBP image.');
            }
            break;

        default:
            throw new InvalidArgumentException('File type is not allowed.');
    }
}

function certUploadErrorMessage(int $code): string
{
    return match ($code) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File is too large. Maximum size is 5 MB.',
        UPLOAD_ERR_PARTIAL => 'Upload was interrupted. Please try again.',
        UPLOAD_ERR_NO_FILE => 'Please choose a certificate file first.',
        UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE, UPLOAD_ERR_EXTENSION => 'Server upload error. Contact the administrator.',
        default => 'Upload failed. Please try again.',
    };
}

function certUploadFromRequest(): array
{
    if (!isset($_FILES['certificate'])) {
        throw new InvalidArgumentException('Please choose a certificate file first.');
    }

    $file = $_FILES['certificate'];
    if (!is_array($file)) {
        throw new InvalidArgumentException('Invalid upload request.');
    }

    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error !== UPLOAD_ERR_OK) {
        throw new InvalidArgumentException(certUploadErrorMessage($error));
    }

    return $file;
}

function inspectCertUpload(array $file): array
{
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        throw new InvalidArgumentException('Upload failed. Please choose a valid certificate file.');
    }

    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new InvalidArgumentException('No valid file was uploaded.');
    }

    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0) {
        throw new InvalidArgumentException('Uploaded file is empty.');
    }

    if ($size > CERT_MAX_BYTES) {
        throw new InvalidArgumentException('File is too large. Maximum size is 5 MB.');
    }

    $originalName = sanitizeText((string) ($file['name'] ?? ''), 200);
    $extension = validateCertUploadName($originalName);

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo === false) {
        throw new InvalidArgumentException('Server cannot inspect uploaded files. Enable the PHP fileinfo extension.');
    }

    $mime = finfo_file($finfo, $file['tmp_name']) ?: '';
    finfo_close($finfo);

    if ($mime === '' || in_array($mime, blockedCertMimeTypes(), true)) {
        throw new InvalidArgumentException('This file type is not allowed. Upload a certificate PDF or image only.');
    }

    if (!in_array($mime, allowedCertMimeTypes(), true)) {
        throw new InvalidArgumentException('Only certificate PDF or image files are allowed (PDF, JPG, PNG, WEBP).');
    }

    $expectedExtension = certExtensionForMime($mime);
    if ($expectedExtension === '' || ($extension !== $expectedExtension && !($extension === 'jpeg' && $expectedExtension === 'jpg'))) {
        throw new InvalidArgumentException('File extension does not match the actual file type.');
    }

    validateCertFileMagic($file['tmp_name'], $mime);

    return [
        'mime' => $mime,
        'extension' => $expectedExtension,
        'original_name' => $originalName,
        'size' => $size,
    ];
}

function certMimeToType(string $mime): string
{
    return match ($mime) {
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        default => '',
    };
}

function secureCertFilename(string $originalName): string
{
    rejectDangerousUploadName($originalName);

    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($ext, allowedCertExtensions(), true)) {
        throw new InvalidArgumentException('Only PDF, JPG, PNG, and WEBP files are allowed.');
    }

    return 'cert_' . date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
}

function saveCertUpload(array $file): array
{
    $inspected = inspectCertUpload($file);
    $mime = $inspected['mime'];

    $filename = secureCertFilename($file['name'] ?? 'certificate.pdf');
    $destination = certUploadDir() . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Could not save uploaded file.');
    }

    chmod($destination, 0640);
    validateCertFileMagic($destination, $mime);

    return [
        'filename' => $filename,
        'file_type' => certMimeToType($mime),
        'original_name' => $inspected['original_name'],
        'size' => $inspected['size'],
    ];
}

function deleteCertFile(?string $filename): void
{
    $filename = basename((string) $filename);
    if ($filename === '') {
        return;
    }

    $path = certUploadDir() . $filename;
    if (is_file($path)) {
        unlink($path);
    }
}

function resolveCertFilePath(string $filename): string
{
    $filename = basename($filename);
    $path = certUploadDir() . $filename;
    $real = realpath($path);

    if ($real === false || !is_file($real)) {
        throw new InvalidArgumentException('Certificate file was not found.');
    }

    $uploadRoot = realpath(certUploadDir());
    if ($uploadRoot === false || strpos($real, $uploadRoot) !== 0) {
        throw new InvalidArgumentException('Invalid certificate file path.');
    }

    return $real;
}

function findCertById(string $id): ?array
{
    $catalog = loadCertCatalog();
    foreach ($catalog['certifications'] as $cert) {
        if (is_array($cert) && ($cert['id'] ?? '') === $id) {
            return $cert;
        }
    }

    return null;
}

function normalizeCertRecord(array $cert): array
{
    return [
        'id' => sanitizeText((string) ($cert['id'] ?? ''), 40),
        'title' => sanitizeText((string) ($cert['title'] ?? ''), 200),
        'issuer' => sanitizeText((string) ($cert['issuer'] ?? ''), 120),
        'year' => sanitizeText((string) ($cert['year'] ?? ''), 12),
        'description' => sanitizePublicCertDescription(sanitizeText((string) ($cert['description'] ?? ''), 1000)),
        'filename' => sanitizeText((string) ($cert['filename'] ?? ''), 200),
        'file_type' => sanitizeText((string) ($cert['file_type'] ?? ''), 12),
        'original_name' => sanitizeText((string) ($cert['original_name'] ?? ''), 200),
        'active' => !empty($cert['active']),
        'sort_order' => max(0, (int) ($cert['sort_order'] ?? 0)),
        'created' => (string) ($cert['created'] ?? date('c')),
        'updated' => date('c'),
    ];
}

function publicCertPayload(array $cert): array
{
    $issuer = (string) ($cert['issuer'] ?? '');
    $year = (string) ($cert['year'] ?? '');
    $description = sanitizePublicCertDescription((string) ($cert['description'] ?? ''));
    $knowledgeParts = array_values(array_filter([$issuer, $year], static fn(string $part): bool => $part !== ''));

    return [
        'title' => (string) ($cert['title'] ?? ''),
        'knowledge' => implode(' · ', $knowledgeParts),
        'description' => $description,
    ];
}

function sanitizePublicCertDescription(string $description): string
{
    $description = trim($description);
    $blockedPhrases = [
        'Imported from file name:',
        'Certificate image uploaded.',
        'Details were inferred from the file name',
    ];

    foreach ($blockedPhrases as $phrase) {
        if (str_contains($description, $phrase)) {
            return '';
        }
    }

    return sanitizeText($description, 1000);
}

function listPublicCertifications(): array
{
    $catalog = loadCertCatalog();
    $certs = [];

    foreach ($catalog['certifications'] as $cert) {
        if (!is_array($cert) || empty($cert['active'])) {
            continue;
        }
        $certs[] = $cert;
    }

    usort($certs, static function (array $a, array $b): int {
        return ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0);
    });

    return array_map('publicCertPayload', $certs);
}

function listAllCertifications(): array
{
    $catalog = loadCertCatalog();
    $items = [];

    foreach ($catalog['certifications'] as $cert) {
        if (is_array($cert)) {
            $items[] = $cert;
        }
    }

    usort($items, static function (array $a, array $b): int {
        return ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0);
    });

    return $items;
}

function saveCertification(array $input, ?array $upload = null): array
{
    $id = sanitizeText((string) ($input['id'] ?? ''), 40);
    $catalog = loadCertCatalog();
    $existing = null;

    foreach ($catalog['certifications'] as $cert) {
        if (is_array($cert) && ($cert['id'] ?? '') === $id) {
            $existing = $cert;
            break;
        }
    }

    $record = normalizeCertRecord([
        'id' => $id !== '' ? $id : generateCertId(),
        'title' => $input['title'] ?? '',
        'issuer' => $input['issuer'] ?? '',
        'year' => $input['year'] ?? '',
        'description' => $input['description'] ?? '',
        'filename' => $existing['filename'] ?? '',
        'file_type' => $existing['file_type'] ?? '',
        'original_name' => $existing['original_name'] ?? '',
        'active' => !empty($input['active']),
        'sort_order' => $input['sort_order'] ?? ($existing['sort_order'] ?? 0),
        'created' => $existing['created'] ?? date('c'),
    ]);

    if ($record['title'] === '') {
        throw new InvalidArgumentException('Certification title is required.');
    }

    if ($upload !== null) {
        if ($record['filename'] !== '') {
            deleteCertFile($record['filename']);
        }
        $saved = saveCertUpload($upload);
        $record['filename'] = $saved['filename'];
        $record['file_type'] = $saved['file_type'];
        $record['original_name'] = $saved['original_name'];
    }

    $found = false;
    foreach ($catalog['certifications'] as $index => $cert) {
        if (!is_array($cert)) {
            continue;
        }
        if (($cert['id'] ?? '') === $record['id']) {
            $catalog['certifications'][$index] = $record;
            $found = true;
            break;
        }
    }

    if (!$found) {
        $catalog['certifications'][] = $record;
    }

    saveCertCatalog($catalog);

    return $record;
}

function deleteCertification(string $id): bool
{
    $id = sanitizeText($id, 40);
    if ($id === '') {
        return false;
    }

    $catalog = loadCertCatalog();
    $before = count($catalog['certifications']);
    $removedFile = '';

    $catalog['certifications'] = array_values(array_filter(
        $catalog['certifications'],
        static function ($cert) use ($id, &$removedFile): bool {
            if (!is_array($cert) || ($cert['id'] ?? '') !== $id) {
                return true;
            }
            $removedFile = (string) ($cert['filename'] ?? '');
            return false;
        }
    ));

    if (count($catalog['certifications']) === $before) {
        return false;
    }

    deleteCertFile($removedFile);
    saveCertCatalog($catalog);

    require_once __DIR__ . '/database.php';
    if (databaseReady()) {
        require_once __DIR__ . '/db-records.php';
        dbDeleteCertification($id);
    }

    return true;
}

function certAdminSummary(): array
{
    $all = listAllCertifications();
    $withFiles = array_filter($all, static fn(array $cert): bool => ($cert['filename'] ?? '') !== '');

    return [
        'certification_count' => count($all),
        'active_certification_count' => count(listPublicCertifications()),
        'file_count' => count($withFiles),
        'certifications' => $all,
    ];
}

function certFileMimeType(string $fileType): string
{
    return match ($fileType) {
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        default => 'application/octet-stream',
    };
}

function streamCertificationFile(string $id): void
{
    $cert = findCertById($id);
    if ($cert === null || ($cert['filename'] ?? '') === '') {
        http_response_code(404);
        echo 'Certificate not found.';
        exit;
    }

    $path = resolveCertFilePath((string) $cert['filename']);
    $fileType = (string) ($cert['file_type'] ?? '');
    $mime = certFileMimeType($fileType);
    $downloadName = ($cert['original_name'] ?? '') !== ''
        ? (string) $cert['original_name']
        : basename($path);

    header('Content-Type: ' . $mime);
    header('Content-Length: ' . (string) filesize($path));
    header('X-Content-Type-Options: nosniff');
    header('Content-Disposition: inline; filename="' . str_replace('"', '', $downloadName) . '"');
    readfile($path);
    exit;
}
