<?php
declare(strict_types=1);

require_once __DIR__ . '/site-services.php';
require_once __DIR__ . '/site-settings.php';
require_once __DIR__ . '/database-backup.php';

const SITE_BACKUP_VERSION = 3;
const SITE_BACKUP_PREFIX = 'data/';
const SITE_BACKUP_MIN_PASSWORD_LENGTH = 8;

function backupZipAvailable(): bool
{
    return class_exists(ZipArchive::class);
}

function backupZipEncryptionAvailable(): bool
{
    return backupZipAvailable()
        && defined('ZipArchive::EM_AES_256')
        && method_exists(ZipArchive::class, 'setEncryptionName');
}

function normalizeBackupPassword(string $password): string
{
    return trim($password);
}

function validateBackupPassword(string $password): void
{
    $password = normalizeBackupPassword($password);
    if ($password === '') {
        throw new InvalidArgumentException('Enter a backup password.');
    }

    if (strlen($password) < SITE_BACKUP_MIN_PASSWORD_LENGTH) {
        throw new InvalidArgumentException('Backup password must be at least ' . SITE_BACKUP_MIN_PASSWORD_LENGTH . ' characters.');
    }
}

function beginBackupZipArchive(string $destinationPath, string $password): ZipArchive
{
    ensureZipArchiveAvailable();

    if (!backupZipEncryptionAvailable()) {
        throw new RuntimeException('Encrypted ZIP backups are not supported by this PHP installation.');
    }

    validateBackupPassword($password);

    $zip = new ZipArchive();
    if ($zip->open($destinationPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('Could not create backup archive.');
    }

    $zip->setPassword($password);

    return $zip;
}

function encryptBackupZipEntry(ZipArchive $zip, string $entryName): void
{
    if (!method_exists($zip, 'setEncryptionName')) {
        return;
    }

    $zip->setEncryptionName($entryName, ZipArchive::EM_AES_256);
}

function openBackupZipArchive(string $zipPath, string $password = ''): ZipArchive
{
    ensureZipArchiveAvailable();

    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== true) {
        throw new InvalidArgumentException('Backup file could not be opened.');
    }

    if ($password !== '') {
        $zip->setPassword($password);
    }

    return $zip;
}

function backupProjectRoot(): string
{
    $root = realpath(__DIR__ . '/../..');

    return $root !== false ? $root : (__DIR__ . '/../..');
}

function backupStorageDir(): string
{
    $dir = __DIR__ . '/../storage/backups';
    if (!is_dir($dir)) {
        mkdir($dir, 0700, true);
    }

    return rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
}

function backupRelativeDirectories(): array
{
    return [
        'php/storage/database',
        'php/storage/shop',
        'php/storage/certifications',
        'php/storage/contact-messages',
        'php/storage/visitors',
        'knowledge',
        'uploads/certifications',
        'uploads/cv',
        'assets/shop/products',
    ];
}

function backupRelativeFiles(): array
{
    return [
        'php/storage/site-settings.json',
        'php/storage/admin-auth.json',
        'php/storage/access-policy.json',
    ];
}

function backupSkipFilenames(): array
{
    return ['.gitkeep', '.htaccess', 'README.md'];
}

function ensureZipArchiveAvailable(): void
{
    if (!class_exists(ZipArchive::class)) {
        throw new RuntimeException('PHP ZipArchive extension is required for backup and restore.');
    }
}

function backupRelativePathFromRoot(string $root, string $absolutePath): string
{
    $root = rtrim(str_replace('\\', '/', $root), '/');
    $absolutePath = str_replace('\\', '/', $absolutePath);

    if (!str_starts_with($absolutePath, $root . '/')) {
        throw new InvalidArgumentException('Invalid backup path.');
    }

    return substr($absolutePath, strlen($root) + 1);
}

function backupZipEntryIsSafe(string $entryName): bool
{
    if ($entryName === '' || !str_starts_with($entryName, SITE_BACKUP_PREFIX)) {
        return false;
    }

    $relative = substr($entryName, strlen(SITE_BACKUP_PREFIX));
    if ($relative === '' || str_contains($relative, '..')) {
        return false;
    }

    return true;
}

function addRelativePathToBackupZip(ZipArchive $zip, string $relativePath, string $root, array &$stats, bool $encryptEntries): void
{
    $relativePath = str_replace('\\', '/', $relativePath);
    $absolute = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

    if (!file_exists($absolute)) {
        return;
    }

    if (is_file($absolute)) {
        $zipPath = SITE_BACKUP_PREFIX . $relativePath;
        if (!$zip->addFile($absolute, $zipPath)) {
            throw new RuntimeException('Could not add file to backup: ' . $relativePath);
        }
        if ($encryptEntries) {
            encryptBackupZipEntry($zip, $zipPath);
        }
        $stats['files']++;
        $stats['bytes'] += filesize($absolute) ?: 0;
        return;
    }

    if (!is_dir($absolute)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($absolute, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $fileInfo) {
        if (!$fileInfo->isFile()) {
            continue;
        }

        $filename = $fileInfo->getFilename();
        if (in_array($filename, backupSkipFilenames(), true)) {
            continue;
        }

        $full = $fileInfo->getPathname();
        $entryRelative = backupRelativePathFromRoot($root, $full);
        $zipPath = SITE_BACKUP_PREFIX . $entryRelative;
        if (!$zip->addFile($full, $zipPath)) {
            throw new RuntimeException('Could not add file to backup: ' . $entryRelative);
        }
        if ($encryptEntries) {
            encryptBackupZipEntry($zip, $zipPath);
        }
        $stats['files']++;
        $stats['bytes'] += $fileInfo->getSize();
    }
}

function normalizeBackupScope(string $scope): string
{
    $scope = strtolower(trim($scope));
    if (!in_array($scope, ['site', 'database', 'both'], true)) {
        return 'site';
    }

    return $scope;
}

function backupScopeLabel(string $scope): string
{
    return match (normalizeBackupScope($scope)) {
        'database' => 'Database only',
        'both' => 'Site + database',
        default => 'Site files only',
    };
}

function backupFilenamePrefix(string $scope): string
{
    return match (normalizeBackupScope($scope)) {
        'database' => 'db-backup-',
        'both' => 'full-backup-',
        default => 'site-backup-',
    };
}

function backupRelativeDirectoriesForScope(string $scope): array
{
    $scope = normalizeBackupScope($scope);
    $dirs = backupRelativeDirectories();
    if ($scope === 'database') {
        return [];
    }
    if ($scope === 'both') {
        return array_values(array_filter($dirs, static fn(string $dir): bool => $dir !== 'php/storage/database'));
    }

    return $dirs;
}

function backupRelativeFilesForScope(string $scope): array
{
    if (normalizeBackupScope($scope) === 'database') {
        return [];
    }

    return backupRelativeFiles();
}

function addDatabaseBackupEntries(ZipArchive $zip, bool $encryptEntries): array
{
    if (!databaseBackupAvailable()) {
        throw new RuntimeException('Database is not available for backup.');
    }

    $stats = ['files' => 0, 'bytes' => 0];
    $dump = exportDatabaseToSql();
    if (!$zip->addFromString(DATABASE_BACKUP_ENTRY, $dump)) {
        throw new RuntimeException('Could not add database dump to backup.');
    }
    if ($encryptEntries) {
        encryptBackupZipEntry($zip, DATABASE_BACKUP_ENTRY);
    }
    $stats['files']++;
    $stats['bytes'] += strlen($dump);

    $driver = strtolower(trim(configValue('DB_DRIVER', 'sqlite'))) ?: 'sqlite';
    if ($driver !== 'mysql') {
        $sqlitePath = sqliteDatabasePath();
        if (is_readable($sqlitePath)) {
            if (!$zip->addFile($sqlitePath, DATABASE_SQLITE_ENTRY)) {
                throw new RuntimeException('Could not add SQLite database file to backup.');
            }
            if ($encryptEntries) {
                encryptBackupZipEntry($zip, DATABASE_SQLITE_ENTRY);
            }
            $stats['files']++;
            $stats['bytes'] += filesize($sqlitePath) ?: 0;
        }
    }

    return $stats;
}

function createBackupZip(string $scope = 'site', ?string $destinationPath = null, string $password = ''): array
{
    $scope = normalizeBackupScope($scope);
    $password = normalizeBackupPassword($password);
    $filename = backupFilenamePrefix($scope) . date('Ymd-His') . '.zip';
    $destinationPath = $destinationPath ?? (backupStorageDir() . $filename);

    $zip = beginBackupZipArchive($destinationPath, $password);
    $stats = ['files' => 0, 'bytes' => 0];

    foreach (backupRelativeFilesForScope($scope) as $relativeFile) {
        addRelativePathToBackupZip($zip, $relativeFile, backupProjectRoot(), $stats, true);
    }

    foreach (backupRelativeDirectoriesForScope($scope) as $relativeDir) {
        addRelativePathToBackupZip($zip, $relativeDir, backupProjectRoot(), $stats, true);
    }

    $databaseMeta = databaseBackupMeta();
    if ($scope === 'database' || $scope === 'both') {
        $dbStats = addDatabaseBackupEntries($zip, true);
        $stats['files'] += $dbStats['files'];
        $stats['bytes'] += $dbStats['bytes'];
    }

    $manifest = [
        'version' => SITE_BACKUP_VERSION,
        'created' => date('c'),
        'site' => 'Mirza Nabeel Ahmed Portfolio',
        'encrypted' => true,
        'backup_scope' => $scope,
        'backup_scope_label' => backupScopeLabel($scope),
        'database_driver' => $databaseMeta['driver'] ?? 'sqlite',
        'files' => $stats['files'],
        'bytes' => $stats['bytes'],
        'includes' => backupScopeIncludes($scope, $databaseMeta),
    ];

    if (!$zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT))) {
        $zip->close();
        throw new RuntimeException('Could not write backup manifest.');
    }
    encryptBackupZipEntry($zip, 'manifest.json');

    if (!$zip->close()) {
        throw new RuntimeException('Could not finalize backup archive.');
    }

    pruneStoredBackups(8);

    return [
        'filename' => basename($destinationPath),
        'path' => $destinationPath,
        'size' => filesize($destinationPath) ?: 0,
        'files' => $stats['files'],
        'created' => $manifest['created'],
        'encrypted' => true,
        'backup_scope' => $scope,
        'backup_scope_label' => backupScopeLabel($scope),
    ];
}

/** @return list<string> */
function backupScopeIncludes(string $scope, array $databaseMeta): array
{
    $scope = normalizeBackupScope($scope);
    $includes = [];

    if ($scope === 'site' || $scope === 'both') {
        $includes = array_merge($includes, [
            'Shop products, orders, stock ledger, and product images',
            'Certifications catalog and uploaded certificate files',
            'CV knowledge base files and search index',
            'Contact form messages and visitor statistics',
            'Site contact settings, access policy, and admin login JSON fallback',
            'Uploaded certification and CV files',
        ]);
        if ($scope === 'site' && (($databaseMeta['driver'] ?? 'sqlite') !== 'mysql')) {
            $includes[] = 'SQLite database file (php/storage/database/site.sqlite)';
        }
    }

    if ($scope === 'database' || $scope === 'both') {
        $includes[] = 'Database SQL export (users with hashed passwords, shop, certifications, messages, settings)';
        if (($databaseMeta['driver'] ?? 'sqlite') !== 'mysql') {
            $includes[] = 'SQLite database file copy (database/site.sqlite)';
        }
    }

    return $includes;
}

function createSiteBackupZip(?string $destinationPath = null, string $password = ''): array
{
    return createBackupZip('site', $destinationPath, $password);
}

function pruneStoredBackups(int $keep = 8): void
{
    $files = array_merge(
        glob(backupStorageDir() . 'site-backup-*.zip') ?: [],
        glob(backupStorageDir() . 'db-backup-*.zip') ?: [],
        glob(backupStorageDir() . 'full-backup-*.zip') ?: []
    );
    usort($files, static function (string $a, string $b): int {
        return filemtime($b) <=> filemtime($a);
    });

    foreach (array_slice($files, $keep) as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
}

function listStoredBackups(): array
{
    $files = array_merge(
        glob(backupStorageDir() . 'site-backup-*.zip') ?: [],
        glob(backupStorageDir() . 'db-backup-*.zip') ?: [],
        glob(backupStorageDir() . 'full-backup-*.zip') ?: []
    );
    usort($files, static function (string $a, string $b): int {
        return filemtime($b) <=> filemtime($a);
    });
    $items = [];

    foreach ($files as $file) {
        $scope = 'site';
        $basename = basename($file);
        if (str_starts_with($basename, 'db-backup-')) {
            $scope = 'database';
        } elseif (str_starts_with($basename, 'full-backup-')) {
            $scope = 'both';
        }

        $items[] = [
            'filename' => $basename,
            'size' => filesize($file) ?: 0,
            'created' => date('c', filemtime($file) ?: time()),
            'backup_scope' => $scope,
            'backup_scope_label' => backupScopeLabel($scope),
        ];
    }

    return $items;
}

function readBackupManifest(string $zipPath, string $password = ''): array
{
    $password = normalizeBackupPassword($password);
    $zip = openBackupZipArchive($zipPath, $password);

    $manifestRaw = $zip->getFromName('manifest.json');
    $zip->close();

    if ($manifestRaw === false) {
        if ($password === '') {
            throw new InvalidArgumentException('This backup is password protected. Enter the backup password.');
        }

        throw new InvalidArgumentException('Incorrect backup password or invalid backup file.');
    }

    $manifest = json_decode($manifestRaw, true);
    if (!is_array($manifest)) {
        throw new InvalidArgumentException('Backup manifest.json is invalid.');
    }

    $version = (int) ($manifest['version'] ?? 0);
    if (!in_array($version, [1, 2, SITE_BACKUP_VERSION], true)) {
        throw new InvalidArgumentException('This backup version is not supported.');
    }

    if (!empty($manifest['encrypted']) && $password === '') {
        throw new InvalidArgumentException('Enter the backup password.');
    }

    return $manifest;
}

function restoreSiteBackupZip(string $zipPath, string $password = ''): array
{
    $password = normalizeBackupPassword($password);
    $manifest = readBackupManifest($zipPath, $password);

    $root = backupProjectRoot();
    $zip = openBackupZipArchive($zipPath, $password);

    $restored = 0;
    $databaseDump = null;
    $databaseSqlite = null;
    for ($index = 0; $index < $zip->numFiles; $index++) {
        $entryName = $zip->getNameIndex($index);
        if ($entryName === false || $entryName === 'manifest.json') {
            continue;
        }

        if ($entryName === DATABASE_BACKUP_ENTRY) {
            $databaseDump = $zip->getFromIndex($index);
            continue;
        }

        if ($entryName === DATABASE_SQLITE_ENTRY) {
            $databaseSqlite = $zip->getFromIndex($index);
            continue;
        }

        if (!backupZipEntryIsSafe($entryName)) {
            continue;
        }

        if (str_ends_with($entryName, '/')) {
            continue;
        }

        $relative = substr($entryName, strlen(SITE_BACKUP_PREFIX));
        $target = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        $targetDir = dirname($target);
        if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true)) {
            $zip->close();
            throw new RuntimeException('Could not create restore directory.');
        }

        $contents = $zip->getFromIndex($index);
        if ($contents === false) {
            $zip->close();
            if (!empty($manifest['encrypted'])) {
                throw new InvalidArgumentException('Incorrect backup password or corrupted backup file.');
            }
            continue;
        }

        file_put_contents($target, $contents, LOCK_EX);
        $restored++;
    }

    $zip->close();

    $databaseRestored = false;
    if (is_string($databaseDump) && $databaseDump !== '' && databaseReady()) {
        importDatabaseSql($databaseDump);
        $databaseRestored = true;
    } elseif (is_string($databaseSqlite) && $databaseSqlite !== '') {
        $driver = strtolower(trim(configValue('DB_DRIVER', 'sqlite'))) ?: 'sqlite';
        if ($driver !== 'mysql') {
            $target = sqliteDatabasePath();
            $targetDir = dirname($target);
            if (!is_dir($targetDir) && !mkdir($targetDir, 0700, true)) {
                throw new RuntimeException('Could not prepare SQLite database directory.');
            }
            file_put_contents($target, $databaseSqlite, LOCK_EX);
            $databaseRestored = true;
        }
    }

    require_once __DIR__ . '/knowledge-builder.php';
    if (function_exists('rebuildKnowledgeSearchIndex')) {
        rebuildKnowledgeSearchIndex();
    }

    return [
        'restored_files' => $restored,
        'restored_database' => $databaseRestored,
        'backup_scope' => normalizeBackupScope((string) ($manifest['backup_scope'] ?? 'site')),
    ];
}

function clearDirectoryContents(string $absoluteDir, bool $removeDir = false): void
{
    if (!is_dir($absoluteDir)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($absoluteDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $fileInfo) {
        $filename = $fileInfo->getFilename();
        if (in_array($filename, ['.gitkeep', '.htaccess'], true)) {
            continue;
        }

        if ($fileInfo->isDir()) {
            @rmdir($fileInfo->getPathname());
        } else {
            @unlink($fileInfo->getPathname());
        }
    }

    if ($removeDir) {
        @rmdir($absoluteDir);
    }
}

function resetAllSiteData(bool $keepAdminAuth = true, string $backupPassword = ''): array
{
    $root = backupProjectRoot();
    $autoBackup = databaseBackupAvailable()
        ? createBackupZip('both', null, $backupPassword)
        : createSiteBackupZip(null, $backupPassword);

    require_once __DIR__ . '/shop-services.php';
    require_once __DIR__ . '/cert-services.php';
    require_once __DIR__ . '/knowledge-builder.php';

    if (function_exists('saveShopCatalog')) {
        saveShopCatalog(defaultShopCatalog());
    }

    $shopOrdersDir = $root . '/php/storage/shop/orders';
    clearDirectoryContents($shopOrdersDir);

    file_put_contents(
        $root . '/php/storage/shop/ledger.json',
        json_encode(['entries' => [], 'updated' => date('c')], JSON_PRETTY_PRINT),
        LOCK_EX
    );

    file_put_contents(
        certCatalogPath(),
        json_encode(defaultCertCatalog(), JSON_PRETTY_PRINT),
        LOCK_EX
    );

    clearDirectoryContents(certUploadDir());
    clearDirectoryContents($root . '/uploads/cv');
    clearDirectoryContents($root . '/assets/shop/products');
    clearDirectoryContents(contactStorageDir());

    file_put_contents(
        visitorStoragePath(),
        json_encode(defaultVisitorData(), JSON_PRETTY_PRINT),
        LOCK_EX
    );

    saveAccessPolicy(defaultAccessPolicy());

    saveSiteSettings(defaultSiteSettings());

    $knowledgeDir = knowledgeDirectory();
    foreach (['profile.json', 'skills.json', 'projects.json', 'certifications.json', 'experience.json', 'search-index.json', 'raw-excerpt.txt'] as $file) {
        $path = $knowledgeDir . $file;
        if (is_file($path)) {
            unlink($path);
        }
    }

    seedDefaultKnowledgeFilesIfMissing();
    rebuildKnowledgeSearchIndex();

    if (databaseReady()) {
        require_once __DIR__ . '/db-records.php';
        dbResetSiteData($keepAdminAuth);
    }

    if (!$keepAdminAuth && is_file(adminAuthPath())) {
        unlink(adminAuthPath());
    }

    return [
        'auto_backup' => $autoBackup,
        'kept_admin_auth' => $keepAdminAuth,
    ];
}

function siteDataSummary(): array
{
    require_once __DIR__ . '/shop-services.php';
    require_once __DIR__ . '/cert-services.php';
    require_once __DIR__ . '/knowledge-builder.php';

    $knowledge = knowledgeStatus();
    $visitorCounts = publicVisitorStats();

    return [
        'shop_products' => count(listAllShopProducts()),
        'shop_orders' => shopOrderCount(),
        'certifications' => count(listAllCertifications()),
        'contact_messages' => contactMessageCount(),
        'visitor_total' => (int) ($visitorCounts['total'] ?? 0),
        'knowledge_entries' => (int) ($knowledge['entry_count'] ?? 0),
        'stored_backups' => count(listStoredBackups()),
    ];
}

function backupAdminSummary(): array
{
    $database = databaseBackupMeta();

    return [
        'summary' => siteDataSummary(),
        'database' => $database,
        'backups' => listStoredBackups(),
        'zip_available' => backupZipAvailable(),
        'encryption_available' => backupZipEncryptionAvailable(),
        'min_password_length' => SITE_BACKUP_MIN_PASSWORD_LENGTH,
        'backup_scopes' => [
            ['value' => 'site', 'label' => 'Site files only', 'description' => 'Shop, certifications, knowledge, uploads, JSON settings — no database export.'],
            ['value' => 'database', 'label' => 'Database only', 'description' => 'Users (hashed passwords), shop rows, certifications, messages, and settings tables.'],
            ['value' => 'both', 'label' => 'Site + database', 'description' => 'Recommended full backup — website files plus SQL database export.'],
        ],
        'includes' => backupScopeIncludes('both', $database),
        'excludes' => [
            [
                'title' => 'Server config (config/local.php)',
                'reason' => 'Contains server-only secrets and environment settings. It stays on this machine and is reconfigured separately after a move.',
            ],
            [
                'title' => 'Rate limit cache',
                'reason' => 'Temporary anti-abuse data that expires on its own and is not part of your website content.',
            ],
            [
                'title' => 'Application logs',
                'reason' => 'Debug and error history only. Restoring them would not bring back products, messages, or other site data.',
            ],
        ],
    ];
}

function sanitizeBackupFilename(string $filename): string
{
    $filename = basename($filename);
    if (!preg_match('/^(site|db|full)-backup-\d{8}-\d{6}\.zip$/', $filename)) {
        throw new InvalidArgumentException('Invalid backup filename.');
    }

    return $filename;
}

function storedBackupPath(string $filename): string
{
    $filename = sanitizeBackupFilename($filename);
    $path = backupStorageDir() . $filename;
    if (!is_readable($path)) {
        throw new InvalidArgumentException('Backup file not found.');
    }

    return $path;
}

function streamBackupFile(string $path): void
{
    sanitizeBackupFilename(basename($path));

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . str_replace('"', '', basename($path)) . '"');
    header('Content-Length: ' . (string) (filesize($path) ?: 0));
    header('Cache-Control: no-store');
    readfile($path);
    exit;
}
