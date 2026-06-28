<?php
declare(strict_types=1);

/**
 * CLI demo: shows what the admin runtime upgrade panel reports.
 * Usage: php scripts/demo-runtime-upgrade.php
 */
$root = dirname(__DIR__);
require_once $root . '/php/lib/bootstrap.php';
require_once $root . '/php/lib/config.php';
require_once $root . '/php/lib/runtime-upgrade.php';

echo "=== Runtime upgrade demo (admin panel preview) ===\n\n";

$plan = runtimeBuildUpgradePlan(true);

echo "Summary: " . ($plan['summary'] ?? '') . "\n";
echo "Updates available: " . (int) ($plan['updates_available'] ?? 0) . "\n";
echo "Overall site impact: " . ($plan['overall_site_impact'] ?? 'none') . "\n";
echo "Estimated downtime: " . ($plan['estimated_downtime'] ?? 'none') . "\n";
echo "Website code auto-changed: " . (($plan['website_code_changed'] ?? false) ? 'yes' : 'no') . "\n";
echo "Services to restart: " . implode(', ', $plan['services_to_restart'] ?? []) . "\n";
echo "Can run from admin: " . (($plan['execution']['allowed'] ?? false) ? 'YES' : 'NO') . "\n";

if (!empty($plan['execution']['reasons'])) {
    echo "Blocked reasons:\n";
    foreach ($plan['execution']['reasons'] as $reason) {
        echo "  - $reason\n";
    }
}

echo "\n--- Packages ---\n";
foreach ($plan['packages'] ?? [] as $pkg) {
    $flag = ($pkg['update_available'] ?? false) ? 'UPDATE' : 'OK';
    echo sprintf(
        "[%s] %s | installed: %s | latest: %s | impact: %s\n",
        $flag,
        $pkg['label'] ?? $pkg['name'] ?? '?',
        $pkg['installed'] ?? '—',
        $pkg['latest_stable'] ?? '—',
        $pkg['site_impact'] ?? '—'
    );
    if (($pkg['update_available'] ?? false) && !empty($pkg['will_change'])) {
        foreach ($pkg['will_change'] as $line) {
            echo "       • $line\n";
        }
    }
}

$impact = $plan['impact_summary'] ?? [];
echo "\n--- If you APPROVE & run updates ---\n";
foreach ($impact['approve']['points'] ?? [] as $line) {
    echo "  • $line\n";
}
echo "\n--- If you CANCEL (keep current versions) ---\n";
foreach ($impact['cancel']['points'] ?? [] as $line) {
    echo "  • $line\n";
}

$compat = runtimeVerifySiteCompatibility();
echo "\n--- Site compatibility (current PHP) ---\n";
foreach ($compat['checks'] ?? [] as $check) {
    $mark = ($check['ok'] ?? false) ? 'OK' : 'FAIL';
    $detail = $check['detail'] ?? '';
    echo "[$mark] " . ($check['label'] ?? '') . ($detail ? " — $detail" : '') . "\n";
}

$job = runtimeLoadUpgradeJob();
if ($job !== null) {
    echo "\n--- Active / last job ---\n";
    echo json_encode(runtimePublicJobSummary($job), JSON_PRETTY_PRINT) . "\n";
}

if ((int) ($plan['updates_available'] ?? 0) === 0) {
    echo "\n(Demo note: yellow banner is hidden when all packages are current — expected on your PC today.)\n";
} else {
    echo "\nAdmin would show: yellow banner → Review & approve → type APPROVE UPGRADE → Run.\n";
}

echo "\nDone.\n";
