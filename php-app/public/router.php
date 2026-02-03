<?php
// PHP built-in server: tüm istekleri index.php'ye yönlendir
if (php_sapi_name() === 'cli-server') {
    $uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
        return false; // statik dosya
    }
}
require __DIR__ . '/index.php';
