<?php
header('Content-Type: text/plain; charset=utf-8');
echo 'curl loaded: ' . (function_exists('curl_init') ? 'YES' : 'NO') . "\n";
echo 'openssl loaded: ' . (extension_loaded('openssl') ? 'YES' : 'NO') . "\n";
echo 'allow_url_fopen: ' . (ini_get('allow_url_fopen') ? 'YES' : 'NO') . "\n";
echo 'openssl.cafile: ' . (ini_get('openssl.cafile') ?: 'not set') . "\n";
echo 'PHP version: ' . PHP_VERSION . "\n";
