<?php
declare(strict_types=1);

$path = dirname(__DIR__) . '/config/github.local.php';
if (!is_file($path)) {
    fwrite(STDERR, "Missing config/github.local.php\n");
    exit(1);
}

$c = require $path;
echo json_encode($c, JSON_THROW_ON_ERROR);
