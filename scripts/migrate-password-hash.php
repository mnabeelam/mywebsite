<?php
declare(strict_types=1);

$path = __DIR__ . '/../config/local.php';
if (!is_file($path)) {
    fwrite(STDERR, "local.php not found\n");
    exit(1);
}

$config = require $path;
$password = (string) ($config['ADMIN_PASSWORD'] ?? '');
if ($password === '') {
    fwrite(STDERR, "ADMIN_PASSWORD is empty; nothing to migrate\n");
    exit(0);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$config['ADMIN_PASSWORD_HASH'] = $hash;
$config['ADMIN_PASSWORD'] = '';

if (!isset($config['CONTACT_EMAIL'])) {
    $config['CONTACT_EMAIL'] = 'mnabeelam@gmail.com';
}

$export = var_export($config, true);
$content = "<?php\nreturn {$export};\n";
file_put_contents($path, $content);

echo "Password hash applied. Plain ADMIN_PASSWORD cleared.\n";
