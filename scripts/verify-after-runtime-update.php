<?php
declare(strict_types=1);

/**
 * Run after upgrading PHP or related runtimes. Exits 0 when site checks pass.
 */
$root = dirname(__DIR__);
require_once $root . '/php/lib/bootstrap.php';
require_once $root . '/php/lib/config.php';
require_once $root . '/php/lib/runtime-versions.php';

$result = runtimeVerifySiteCompatibility();
$ok = (bool) ($result['ok'] ?? false);

echo 'Site compatibility after runtime update' . PHP_EOL;
echo 'PHP: ' . PHP_VERSION . PHP_EOL;
foreach ($result['checks'] ?? [] as $check) {
    $mark = ($check['ok'] ?? false) ? 'OK' : 'FAIL';
    $line = '[' . $mark . '] ' . ($check['label'] ?? 'check');
    if (!empty($check['detail'])) {
        $line .= ' — ' . $check['detail'];
    }
    echo $line . PHP_EOL;
}

exit($ok ? 0 : 1);
