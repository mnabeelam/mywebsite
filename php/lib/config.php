<?php
declare(strict_types=1);

function loadLocalConfig(): array
{
    static $config = null;
    if ($config !== null) {
        return $config;
    }

    $path = __DIR__ . '/../../config/local.php';
    if (is_file($path)) {
        $loaded = require $path;
        $config = is_array($loaded) ? $loaded : [];
        return $config;
    }

    $config = [];
    return $config;
}

function configValue(string $key): string
{
    $value = getenv($key);
    if ($value !== false && $value !== '') {
        return $value;
    }

    $local = loadLocalConfig();
    return (string) ($local[$key] ?? '');
}
