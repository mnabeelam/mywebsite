<?php
declare(strict_types=1);

require_once __DIR__ . '/runtime-versions.php';

/** @return list<array<string, mixed>> */
function runtimeManagedPackages(): array
{
    return [
        [
            'key' => 'php',
            'name' => 'PHP',
            'label' => 'PHP 8.3',
            'winget_id' => 'PHP.PHP.8.3',
            'required_for_site' => true,
            'site_impact' => 'high',
            'impact_summary' => 'The website runs on PHP. Expect 1–3 minutes of downtime while packages install and Apache restarts.',
            'will_change' => [
                'PHP runtime binaries (patch/security release within PHP 8.3)',
                'Apache HTTP Server will be restarted',
                'Website source code is not modified automatically',
                'Run compatibility check after upgrade',
            ],
            'code_changed' => false,
            'services_restarted' => ['Apache HTTP Server'],
        ],
        [
            'key' => 'node',
            'name' => 'Node.js',
            'label' => 'Node.js LTS',
            'winget_id' => 'OpenJS.NodeJS.LTS',
            'required_for_site' => false,
            'site_impact' => 'low',
            'impact_summary' => 'Development tool only. The public PHP website does not require Node.js to run.',
            'will_change' => [
                'Node.js LTS runtime on this PC',
                'No change to live site pages or admin PHP code',
            ],
            'code_changed' => false,
            'services_restarted' => [],
        ],
        [
            'key' => 'git',
            'name' => 'Git',
            'label' => 'Git for Windows',
            'winget_id' => 'Git.Git',
            'required_for_site' => false,
            'site_impact' => 'none',
            'impact_summary' => 'Version control tool only. No impact on visitors or the running website.',
            'will_change' => [
                'Git client binaries',
            ],
            'code_changed' => false,
            'services_restarted' => [],
        ],
    ];
}

/**
 * @param list<array<string, mixed>> $packages
 * @return array{approve: array{title: string, points: list<string>}, cancel: array{title: string, points: list<string>}}
 */
function runtimeBuildImpactSummaries(
    int $updatesAvailable,
    string $maxImpact,
    array $servicesToRestart,
    array $packages
): array
{
    if ($updatesAvailable === 0) {
        return [
            'approve' => [
                'title' => 'If you approve & run updates',
                'website_summary' => 'Nothing happens — all tools are already up to date. Your website keeps working exactly as it does now.',
                'points' => [
                    'No packages will be installed (nothing newer is available).',
                    'Homepage, shop, contact form, and admin stay online with no interruption.',
                    'Approve button is disabled until a future update is detected.',
                ],
            ],
            'cancel' => [
                'title' => 'If you cancel (keep current versions)',
                'website_summary' => 'Same as today — visitors see no change. The site stays online on current PHP/Node/Git versions.',
                'points' => [
                    'No downtime and no Apache restart.',
                    'All pages, products, messages, and settings remain unchanged.',
                    'You can open this screen again anytime when updates become available.',
                ],
            ],
        ];
    }

    $pendingLabels = [];
    $hasPhp = false;
    $hasDevOnly = true;
    foreach ($packages as $pkg) {
        if (!($pkg['update_available'] ?? false)) {
            continue;
        }
        $pendingLabels[] = (string) ($pkg['label'] ?? $pkg['name'] ?? 'Package');
        if (($pkg['key'] ?? '') === 'php') {
            $hasPhp = true;
        }
        if (($pkg['required_for_site'] ?? false)) {
            $hasDevOnly = false;
        }
    }

    $approvePoints = [
        'Packages to upgrade: ' . implode(', ', $pendingLabels) . '.',
        'Your website files (PHP pages, CSS, JS, images) are NOT edited — only Windows installs newer tool versions.',
    ];

    if ($hasPhp) {
        $approveWebsite = 'Visitors may not reach the site for about 1–3 minutes while PHP updates and Apache restarts. After that, pages should work the same because your code files are unchanged.';
        $approvePoints[] = 'Homepage, shop, contact, and admin may show a connection error briefly during Apache restart.';
        $approvePoints[] = 'When finished, test login, shop order, and contact form.';
    } elseif (!$hasDevOnly) {
        $approveWebsite = 'Low impact on visitors — mostly background tool updates on this PC.';
        $approvePoints[] = 'Low impact on the live site — mainly local tool updates.';
    } else {
        $approveWebsite = 'No impact on visitors — only Git/Node on this PC update; the public PHP site does not use them.';
        $approvePoints[] = 'No impact on the public website (development tools only).';
    }

    $cancelWebsite = 'Visitors keep using the site normally with no interruption. You stay on current PHP/Node/Git versions and skip the new security patches until you upgrade later.';

    if ($servicesToRestart !== []) {
        $approvePoints[] = 'Services restarted after upgrade: ' . implode(', ', $servicesToRestart) . '.';
    }

    $approvePoints[] = 'A compatibility check runs automatically when the upgrade finishes.';
    if ($maxImpact === 'high') {
        $approvePoints[] = 'Overall site impact: high (PHP runtime change). Test admin login and forms after upgrade.';
    }

    $cancelPoints = [
        'Website stays online — no interruption for visitors.',
        'No Apache restart and no Windows service changes.',
        'Homepage, shop, contact, and admin continue on current versions.',
        'Security patches in the newer releases will NOT be applied until you upgrade later.',
        'No changes to your files, database, uploads, or admin settings.',
        'You can approve updates later from Backup & Data (backup recommended first).',
    ];

    return [
        'approve' => [
            'title' => 'If you approve & run updates',
            'website_summary' => $approveWebsite,
            'points' => $approvePoints,
        ],
        'cancel' => [
            'title' => 'If you cancel (keep current versions)',
            'website_summary' => $cancelWebsite,
            'points' => $cancelPoints,
        ],
    ];
}

function runtimeUpgradeJobPath(): string
{
    return __DIR__ . '/../storage/runtime-upgrade-job.json';
}

function runtimeUpgradePrefsPath(): string
{
    return __DIR__ . '/../storage/runtime-upgrade-prefs.json';
}

function runtimeUpgradeHistoryPath(): string
{
    return __DIR__ . '/../storage/runtime-upgrade-history.json';
}

function runtimeLoadPrefs(): array
{
    $path = runtimeUpgradePrefsPath();
    if (!is_readable($path)) {
        return [
            'banner_suppressed' => false,
            'suppress_mode' => '',
            'deferred_at' => null,
            'deferred_by' => '',
            'deferred_snapshot' => '',
        ];
    }

    $data = json_decode((string) file_get_contents($path), true);
    if (!is_array($data)) {
        return [
            'banner_suppressed' => false,
            'suppress_mode' => '',
            'deferred_at' => null,
            'deferred_by' => '',
            'deferred_snapshot' => '',
        ];
    }

    return [
        'banner_suppressed' => (bool) ($data['banner_suppressed'] ?? false),
        'suppress_mode' => trim((string) ($data['suppress_mode'] ?? '')),
        'deferred_at' => $data['deferred_at'] ?? null,
        'deferred_by' => trim((string) ($data['deferred_by'] ?? '')),
        'deferred_snapshot' => trim((string) ($data['deferred_snapshot'] ?? '')),
    ];
}

function runtimeSavePrefs(array $prefs): void
{
    $dir = dirname(runtimeUpgradePrefsPath());
    if (!is_dir($dir)) {
        mkdir($dir, 0700, true);
    }

    file_put_contents(runtimeUpgradePrefsPath(), json_encode($prefs, JSON_PRETTY_PRINT), LOCK_EX);
}

/** @return list<array<string, mixed>> */
function runtimeListHistory(int $limit = 50): array
{
    $path = runtimeUpgradeHistoryPath();
    if (!is_readable($path)) {
        return [];
    }

    $data = json_decode((string) file_get_contents($path), true);
    if (!is_array($data)) {
        return [];
    }

    $rows = array_values(array_filter($data, static fn($row): bool => is_array($row)));
    if (count($rows) > $limit) {
        $rows = array_slice($rows, -$limit);
    }

    return array_reverse($rows);
}

function runtimeAppendHistory(array $entry): void
{
    $path = runtimeUpgradeHistoryPath();
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0700, true);
    }

    $rows = [];
    if (is_readable($path)) {
        $decoded = json_decode((string) file_get_contents($path), true);
        if (is_array($decoded)) {
            $rows = $decoded;
        }
    }

    $entry['id'] = bin2hex(random_bytes(8));
    if (!isset($entry['at'])) {
        $entry['at'] = date('c');
    }

    $rows[] = $entry;
    if (count($rows) > 200) {
        $rows = array_slice($rows, -200);
    }

    file_put_contents($path, json_encode($rows, JSON_PRETTY_PRINT), LOCK_EX);
}

function runtimePlanSnapshot(array $plan): string
{
    $parts = [];
    foreach ($plan['packages'] ?? [] as $pkg) {
        if (!is_array($pkg) || !($pkg['update_available'] ?? false)) {
            continue;
        }
        $parts[] = ($pkg['key'] ?? '') . '=' . ($pkg['latest_stable'] ?? '');
    }

    return implode('|', $parts);
}

function runtimeShouldSuppressBanner(array $plan, array $prefs): bool
{
    if (!($prefs['banner_suppressed'] ?? false)) {
        return false;
    }

    $snapshot = trim((string) ($prefs['deferred_snapshot'] ?? ''));
    if ($snapshot === '') {
        return true;
    }

    return runtimePlanSnapshot($plan) === $snapshot;
}

function runtimeArchiveFinishedJob(): void
{
    $job = runtimeLoadUpgradeJob();
    if ($job === null) {
        return;
    }

    $status = (string) ($job['status'] ?? '');
    if (!in_array($status, ['completed', 'failed'], true)) {
        return;
    }

    runtimeAppendHistory([
        'type' => $status === 'completed' ? 'upgrade_completed' : 'upgrade_failed',
        'at' => $job['finished_at'] ?? date('c'),
        'by' => (string) ($job['initiated_by'] ?? ''),
        'job_id' => (string) ($job['job_id'] ?? ''),
        'packages_pending' => runtimePendingPackageLabels($job['plan'] ?? []),
        'packages_upgraded' => $job['packages_upgraded'] ?? [],
        'services_restarted' => $job['services_restarted'] ?? [],
        'compatibility_ok' => (bool) ($job['compatibility_ok'] ?? false),
        'note' => (string) ($job['error'] ?? ''),
    ]);

    @unlink(runtimeUpgradeJobPath());
}

/** @param array<string, mixed> $plan */
function runtimePendingPackageLabels(array $plan): array
{
    $labels = [];
    foreach ($plan['packages'] ?? [] as $pkg) {
        if (is_array($pkg) && ($pkg['update_available'] ?? false)) {
            $labels[] = (string) ($pkg['label'] ?? $pkg['name'] ?? 'Package');
        }
    }

    return $labels;
}

/** @return array<string, mixed> */
function runtimeDeferUpgrade(string $username, string $mode, array $plan): array
{
    $mode = strtolower(trim($mode));
    if (!in_array($mode, ['remind_later', 'tab_only', 'ask_again'], true)) {
        throw new InvalidArgumentException('Invalid defer mode.');
    }

    $snapshot = runtimePlanSnapshot($plan);
    $prefs = [
        'banner_suppressed' => $mode !== 'ask_again',
        'suppress_mode' => $mode,
        'deferred_at' => date('c'),
        'deferred_by' => $username,
        'deferred_snapshot' => $snapshot,
    ];
    runtimeSavePrefs($prefs);

    $note = match ($mode) {
        'remind_later' => 'Admin chose to upgrade later. Banner hidden until pending updates change.',
        'tab_only' => 'Admin cancelled — pending updates saved in System Updates tab for later.',
        default => 'Admin closed prompt — banner will show again on next visit.',
    };

    runtimeAppendHistory([
        'type' => 'deferred',
        'at' => date('c'),
        'by' => $username,
        'mode' => $mode,
        'packages_pending' => runtimePendingPackageLabels($plan),
        'note' => $note,
    ]);

    appLog('runtime upgrade deferred (' . $mode . ') by "' . $username . '"');

    return $prefs;
}

function runtimeClearDeferPrefs(): void
{
    runtimeSavePrefs([
        'banner_suppressed' => false,
        'suppress_mode' => '',
        'deferred_at' => null,
        'deferred_by' => '',
        'deferred_snapshot' => '',
    ]);
}

function runtimeUpgradeLogPath(): string
{
    $dir = __DIR__ . '/../storage/logs';
    if (!is_dir($dir)) {
        mkdir($dir, 0700, true);
    }

    return $dir . '/runtime-upgrade.log';
}

function runtimeUpgradeWorkerScript(): string
{
    return dirname(__DIR__, 2) . '/scripts/runtime-upgrade-worker.ps1';
}

function runtimeLoadUpgradeJob(): ?array
{
    $path = runtimeUpgradeJobPath();
    if (!is_readable($path)) {
        return null;
    }

    $data = json_decode((string) file_get_contents($path), true);
    return is_array($data) ? $data : null;
}

function runtimeSaveUpgradeJob(array $job): void
{
    $dir = dirname(runtimeUpgradeJobPath());
    if (!is_dir($dir)) {
        mkdir($dir, 0700, true);
    }

    file_put_contents(runtimeUpgradeJobPath(), json_encode($job, JSON_PRETTY_PRINT), LOCK_EX);
}

function runtimeCommandOutput(string $command): ?string
{
    if (!function_exists('proc_open')) {
        return null;
    }

    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open($command, $descriptors, $pipes, dirname(__DIR__, 2));
    if (!is_resource($process)) {
        return null;
    }

    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($process);

    $out = trim((string) $stdout);
    if ($out === '') {
        $out = trim((string) $stderr);
    }

    return $out !== '' ? $out : null;
}

function runtimeDetectInstalledVersion(string $key): ?string
{
    switch ($key) {
        case 'php':
            return PHP_VERSION;
        case 'node':
            $out = runtimeCommandOutput('cmd /C node -v 2>nul');
            if ($out === null) {
                return null;
            }
            return ltrim($out, 'vV');
        case 'git':
            $out = runtimeCommandOutput('cmd /C git --version 2>nul');
            if ($out === null || !preg_match('/git version ([0-9.]+)/i', $out, $m)) {
                return null;
            }
            return $m[1];
        default:
            return null;
    }
}

function runtimeFetchLatestForKey(string $key): ?string
{
    switch ($key) {
        case 'php':
            return runtimeFetchLatestPhp();
        case 'node':
            return runtimeFetchLatestNode();
        case 'git':
            return runtimeFetchLatestGit();
        default:
            return null;
    }
}

function runtimeUpgradeExecutionAllowed(): array
{
    $reasons = [];
    if (PHP_OS_FAMILY !== 'Windows') {
        $reasons[] = 'Automatic upgrades are only supported on Windows servers with winget.';
    }
    if (!is_file(runtimeUpgradeWorkerScript())) {
        $reasons[] = 'Upgrade worker script is missing (scripts/runtime-upgrade-worker.ps1).';
    }
    if (!function_exists('proc_open') && !function_exists('popen')) {
        $reasons[] = 'PHP cannot start background processes (proc_open/popen disabled).';
    }

    $winget = runtimeCommandOutput('cmd /C winget --version 2>nul');
    if ($winget === null) {
        $reasons[] = 'winget is not available. Install App Installer from Microsoft Store.';
    }

    return [
        'allowed' => $reasons === [],
        'reasons' => $reasons,
    ];
}

/** @return array<string, mixed> */
function runtimeBuildUpgradePlan(bool $includeOnline = true): array
{
    runtimeArchiveFinishedJob();

    $packages = [];
    $updatesAvailable = 0;
    $servicesToRestart = [];
    $maxImpact = 'none';
    $impactRank = ['none' => 0, 'low' => 1, 'medium' => 2, 'high' => 3];

    foreach (runtimeManagedPackages() as $meta) {
        $key = (string) $meta['key'];
        $installed = runtimeDetectInstalledVersion($key);
        $latest = $includeOnline ? runtimeFetchLatestForKey($key) : null;
        $updateAvailable = false;

        if ($installed !== null && $latest !== null && $latest !== '') {
            $updateAvailable = runtimeVersionCompare($installed, $latest) < 0;
        }

        if ($updateAvailable) {
            $updatesAvailable++;
            foreach ($meta['services_restarted'] as $service) {
                if (!in_array($service, $servicesToRestart, true)) {
                    $servicesToRestart[] = $service;
                }
            }
            $rank = $impactRank[$meta['site_impact']] ?? 0;
            if ($rank > ($impactRank[$maxImpact] ?? 0)) {
                $maxImpact = (string) $meta['site_impact'];
            }
        }

        $packages[] = array_merge($meta, [
            'installed' => $installed,
            'latest_stable' => $latest,
            'update_available' => $updateAvailable,
            'status' => $installed === null
                ? 'not_detected'
                : ($updateAvailable ? 'update_available' : ($latest === null ? 'unknown' : 'current')),
        ]);
    }

    $execution = runtimeUpgradeExecutionAllowed();
    $activeJob = runtimeLoadUpgradeJob();
    $jobBusy = $activeJob !== null && in_array((string) ($activeJob['status'] ?? ''), ['queued', 'running'], true);
    $overallImpact = $updatesAvailable > 0 ? $maxImpact : 'none';
    $impactSummary = runtimeBuildImpactSummaries($updatesAvailable, $overallImpact, $servicesToRestart, $packages);
    $prefs = runtimeLoadPrefs();
    $bannerSuppressed = runtimeShouldSuppressBanner([
        'packages' => $packages,
    ], $prefs);

    return [
        'generated_at' => date('c'),
        'platform' => PHP_OS_FAMILY,
        'updates_available' => $updatesAvailable,
        'overall_site_impact' => $overallImpact,
        'estimated_downtime' => $updatesAvailable > 0 ? '1–3 minutes (Apache restart)' : 'none',
        'website_code_changed' => false,
        'services_to_restart' => $servicesToRestart,
        'packages' => $packages,
        'execution' => $execution,
        'job_busy' => $jobBusy,
        'active_job' => $jobBusy ? runtimePublicJobSummary($activeJob) : null,
        'impact_summary' => $impactSummary,
        'banner_suppressed' => $bannerSuppressed,
        'prefs' => $prefs,
        'history' => runtimeListHistory(40),
        'pending_packages' => runtimePendingPackageLabels(['packages' => $packages]),
        'summary' => $updatesAvailable > 0
            ? $updatesAvailable . ' runtime update(s) available. Review approve vs cancel impact before confirming.'
            : 'All monitored runtimes are up to date. No upgrade needed — cancel or ignore keeps everything as-is.',
    ];
}

/** @param array<string, mixed>|null $job */
function runtimePublicJobSummary(?array $job): ?array
{
    if ($job === null) {
        return null;
    }

    return [
        'job_id' => (string) ($job['job_id'] ?? ''),
        'status' => (string) ($job['status'] ?? ''),
        'step' => (string) ($job['step'] ?? ''),
        'started_at' => $job['started_at'] ?? null,
        'finished_at' => $job['finished_at'] ?? null,
        'initiated_by' => (string) ($job['initiated_by'] ?? ''),
        'packages_upgraded' => $job['packages_upgraded'] ?? [],
        'services_restarted' => $job['services_restarted'] ?? [],
        'compatibility_ok' => (bool) ($job['compatibility_ok'] ?? false),
        'error' => (string) ($job['error'] ?? ''),
        'log_tail' => runtimeTailLog((string) ($job['log_file'] ?? runtimeUpgradeLogPath()), 12),
    ];
}

function runtimeTailLog(string $path, int $lines = 20): string
{
    if (!is_readable($path)) {
        return '';
    }

    $content = file($path, FILE_IGNORE_NEW_LINES);
    if (!is_array($content)) {
        return '';
    }

    return implode(PHP_EOL, array_slice($content, -$lines));
}

function runtimeSpawnUpgradeWorker(string $jobId): bool
{
    $worker = runtimeUpgradeWorkerScript();
    if (!is_file($worker)) {
        return false;
    }

    $jobFile = runtimeUpgradeJobPath();
    $logFile = runtimeUpgradeLogPath();
    $root = dirname(__DIR__, 2);

    $args = [
        'powershell.exe',
        '-NoProfile',
        '-ExecutionPolicy',
        'Bypass',
        '-File',
        $worker,
        '-JobId',
        $jobId,
        '-JobFile',
        $jobFile,
        '-LogFile',
        $logFile,
        '-Root',
        $root,
    ];

    $quoted = array_map(static function (string $part): string {
        return '"' . str_replace('"', '', $part) . '"';
    }, $args);

    if (function_exists('popen')) {
        $cmd = 'cmd /C start "" /B ' . implode(' ', $quoted);
        $handle = popen($cmd, 'r');
        if (is_resource($handle)) {
            pclose($handle);
            return true;
        }
    }

    if (!function_exists('proc_open')) {
        return false;
    }

    $process = proc_open('cmd /C start "" /B ' . implode(' ', $quoted), [], $pipes, $root);
    if (!is_resource($process)) {
        return false;
    }

    proc_close($process);

    return true;
}

/** @return array<string, mixed> */
function runtimeStartUpgradeJob(string $username): array
{
    $execution = runtimeUpgradeExecutionAllowed();
    if (!$execution['allowed']) {
        throw new InvalidArgumentException(implode(' ', $execution['reasons']));
    }

    $plan = runtimeBuildUpgradePlan(true);
    if ((int) ($plan['updates_available'] ?? 0) === 0) {
        throw new InvalidArgumentException('No runtime updates are available right now.');
    }

    $active = runtimeLoadUpgradeJob();
    if ($active !== null && in_array((string) ($active['status'] ?? ''), ['queued', 'running'], true)) {
        throw new InvalidArgumentException('An upgrade is already running. Wait for it to finish.');
    }

    $jobId = bin2hex(random_bytes(8));
    $job = [
        'job_id' => $jobId,
        'status' => 'queued',
        'step' => 'queued',
        'initiated_by' => $username,
        'created_at' => date('c'),
        'started_at' => null,
        'finished_at' => null,
        'plan' => $plan,
        'packages_upgraded' => [],
        'services_restarted' => [],
        'compatibility_ok' => false,
        'error' => '',
        'log_file' => runtimeUpgradeLogPath(),
    ];

    runtimeSaveUpgradeJob($job);

    if (!runtimeSpawnUpgradeWorker($jobId)) {
        $job['status'] = 'failed';
        $job['error'] = 'Could not start background upgrade worker.';
        $job['finished_at'] = date('c');
        runtimeSaveUpgradeJob($job);
        runtimeArchiveFinishedJob();
        throw new RuntimeException($job['error']);
    }

    runtimeClearDeferPrefs();

    appLog('runtime upgrade queued by "' . $username . '" job=' . $jobId);

    return runtimePublicJobSummary($job) ?? [];
}
