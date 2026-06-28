<?php
declare(strict_types=1);

/**
 * Runtime version checks for PHP and related local tools.
 * Does not install upgrades — reports status and runs compatibility checks.
 */

const RUNTIME_MIN_PHP = '8.1.0';
const RUNTIME_TARGET_PHP = '8.3.0';

/** Extensions required for this site (DB driver validated separately). */
function runtimeRequiredExtensions(): array
{
    return ['curl', 'openssl', 'mbstring', 'json', 'zip', 'fileinfo', 'pdo', 'dom', 'xml'];
}

function runtimeParseVersion(string $version): array
{
    $version = preg_replace('/^[vV]/', '', trim($version)) ?? '';
    if (preg_match('/^(\d+)\.(\d+)(?:\.(\d+))?/', $version, $m)) {
        return [(int) $m[1], (int) $m[2], (int) ($m[3] ?? 0)];
    }

    return [0, 0, 0];
}

function runtimeVersionCompare(string $installed, string $latest): int
{
    $a = runtimeParseVersion($installed);
    $b = runtimeParseVersion($latest);
    for ($i = 0; $i < 3; $i++) {
        if ($a[$i] !== $b[$i]) {
            return $a[$i] <=> $b[$i];
        }
    }

    return 0;
}

function runtimeHttpGet(string $url, int $timeoutSeconds = 8): ?string
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        if ($ch === false) {
            return null;
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => $timeoutSeconds,
            CURLOPT_TIMEOUT => $timeoutSeconds,
            CURLOPT_USERAGENT => 'MNA-Portfolio-Runtime-Check/1.0',
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($body === false || $code < 200 || $code >= 300) {
            return null;
        }

        return (string) $body;
    }

    if (!filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN)) {
        return null;
    }

    $context = stream_context_create([
        'http' => [
            'timeout' => $timeoutSeconds,
            'header' => 'User-Agent: MNA-Portfolio-Runtime-Check/1.0',
        ],
    ]);
    $body = @file_get_contents($url, false, $context);

    return $body !== false ? $body : null;
}

function runtimeFetchLatestPhp(): ?string
{
    $body = runtimeHttpGet('https://www.php.net/releases/index.php?json&version=8&max=1');
    if ($body === null) {
        return null;
    }
    $data = json_decode($body, true);
    if (!is_array($data) || !isset($data[0]['version'])) {
        return null;
    }

    return (string) $data[0]['version'];
}

function runtimeFetchLatestNode(): ?string
{
    $body = runtimeHttpGet('https://nodejs.org/dist/index.json');
    if ($body === null) {
        return null;
    }
    $data = json_decode($body, true);
    if (!is_array($data)) {
        return null;
    }
    foreach ($data as $entry) {
        if (!is_array($entry)) {
            continue;
        }
        if (($entry['lts'] ?? false) && isset($entry['version'])) {
            return ltrim((string) $entry['version'], 'v');
        }
    }
    if (isset($data[0]['version'])) {
        return ltrim((string) $data[0]['version'], 'v');
    }

    return null;
}

function runtimeFetchLatestGit(): ?string
{
    $body = runtimeHttpGet('https://api.github.com/repos/git-for-windows/git/releases/latest');
    if ($body === null) {
        return null;
    }
    $data = json_decode($body, true);
    if (!is_array($data) || !isset($data['tag_name'])) {
        return null;
    }

    return ltrim((string) $data['tag_name'], 'v');
}

function runtimeComponentRow(
    string $name,
    string $installed,
    ?string $latest,
    string $role,
    bool $requiredForSite
): array
{
    $status = 'unknown';
    $recommendation = 'Could not check latest version online.';

    if ($latest !== null && $latest !== '') {
        $cmp = runtimeVersionCompare($installed, $latest);
        if ($cmp >= 0) {
            $status = 'current';
            $recommendation = 'Up to date with latest stable release.';
        } else {
            $status = 'update_available';
            $recommendation = 'A newer stable version is available. Use scripts/upgrade-runtimes.ps1 on Windows after backup.';
        }
    }

    return [
        'name' => $name,
        'installed' => $installed,
        'latest_stable' => $latest,
        'status' => $status,
        'role' => $role,
        'required_for_site' => $requiredForSite,
        'recommendation' => $recommendation,
    ];
}

function runtimeInstalledPhp(): string
{
    return PHP_VERSION;
}

function runtimeMissingExtensions(): array
{
    $missing = [];
    foreach (runtimeRequiredExtensions() as $ext) {
        if (!extension_loaded($ext)) {
            $missing[] = $ext;
        }
    }

    return $missing;
}

function runtimeVerifySiteCompatibility(): array
{
    $missing = runtimeMissingExtensions();
    $phpOk = runtimeVersionCompare(PHP_VERSION, RUNTIME_MIN_PHP) >= 0 && $missing === [];

    $databaseOk = false;
    $databaseError = '';
    try {
        require_once __DIR__ . '/database.php';
        $databaseOk = databaseReady();
    } catch (Throwable $e) {
        $databaseError = $e->getMessage();
    }

    $knowledgeOk = false;
    $knowledgeEntries = 0;
  $profilePath = __DIR__ . '/../../knowledge/profile.json';
    try {
        require_once __DIR__ . '/knowledge.php';
        $index = loadKnowledgeSearchIndex();
        $knowledgeEntries = (int) ($index['entry_count'] ?? 0);
        $knowledgeOk = $knowledgeEntries > 0 || is_readable($profilePath);
    } catch (Throwable $e) {
        $knowledgeOk = is_readable($profilePath);
    }

    $checks = [
        [
            'id' => 'php_version',
            'label' => 'PHP version >= ' . RUNTIME_MIN_PHP,
            'ok' => runtimeVersionCompare(PHP_VERSION, RUNTIME_MIN_PHP) >= 0,
        ],
        [
            'id' => 'php_extensions',
            'label' => 'Required PHP extensions',
            'ok' => $missing === [],
            'detail' => $missing === [] ? '' : 'Missing: ' . implode(', ', $missing),
        ],
        [
            'id' => 'database',
            'label' => 'Database connection',
            'ok' => $databaseOk,
            'detail' => $databaseError,
        ],
        [
            'id' => 'knowledge',
            'label' => 'Knowledge base readable',
            'ok' => $knowledgeOk,
            'detail' => $knowledgeEntries > 0 ? (string) $knowledgeEntries . ' indexed entries' : '',
        ],
    ];

    $allOk = true;
    foreach ($checks as $check) {
        if (!$check['ok']) {
            $allOk = false;
            break;
        }
    }

    return [
        'ok' => $allOk,
        'checks' => $checks,
        'missing_extensions' => $missing,
        'php_version' => PHP_VERSION,
        'min_php' => RUNTIME_MIN_PHP,
        'target_php' => RUNTIME_TARGET_PHP,
    ];
}

function runtimeBuildReport(bool $includeOnline = true): array
{
    $components = [
        runtimeComponentRow(
            'PHP',
            runtimeInstalledPhp(),
            $includeOnline ? runtimeFetchLatestPhp() : null,
            'Runs the website and admin panel',
            true
        ),
        runtimeComponentRow(
            'Node.js',
            runtimeDetectInstalledVersion('node') ?? 'not detected',
            $includeOnline ? runtimeFetchLatestNode() : null,
            'Local development tool (not required for the live site)',
            false
        ),
        runtimeComponentRow(
            'Git',
            runtimeDetectInstalledVersion('git') ?? 'not detected',
            $includeOnline ? runtimeFetchLatestGit() : null,
            'Version control (not required for the live site)',
            false
        ),
    ];

    $policy = [
        'auto_os_upgrade' => true,
        'auto_code_rewrite' => false,
        'admin_approval_required' => true,
        'reason' => 'Upgrades run from the admin panel after you approve. OS packages update via winget; Apache restarts when PHP updates. Website source code is not auto-modified.',
        'how_to_upgrade' => [
            '1. Create a backup from Admin → Backup & Data.',
            '2. When a yellow banner appears, click Review & approve.',
            '3. Read impact details and type APPROVE UPGRADE.',
            '4. Wait for the job to finish (Apache may restart).',
            '5. Run compatibility check and test login, shop, and contact.',
        ],
        'winget_ids' => [
            'PHP' => 'PHP.PHP.8.3',
            'Node.js LTS' => 'OpenJS.NodeJS.LTS',
            'Git' => 'Git.Git',
        ],
    ];

    $compatibility = runtimeVerifySiteCompatibility();
    $updatesAvailable = 0;
    foreach ($components as $component) {
        if (($component['status'] ?? '') === 'update_available') {
            $updatesAvailable++;
        }
    }

    return [
        'generated_at' => date('c'),
        'components' => $components,
        'compatibility' => $compatibility,
        'updates_available' => $updatesAvailable,
        'policy' => $policy,
    ];
}
