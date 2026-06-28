<?php
header('Content-Type: text/plain');
echo 'pdo_sqlite: ' . (extension_loaded('pdo_sqlite') ? 'YES' : 'NO') . "\n";
echo 'pdo_mysql: ' . (extension_loaded('pdo_mysql') ? 'YES' : 'NO') . "\n";
if (function_exists('php_ini_loaded_file')) {
    echo 'ini: ' . php_ini_loaded_file() . "\n";
}
