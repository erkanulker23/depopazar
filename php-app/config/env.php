<?php
/** .env dosyasını bir kez yükler (Forge Environment + lokal .env) */
if (defined('DEOPAZAR_ENV_LOADED')) {
    return;
}
define('DEOPAZAR_ENV_LOADED', true);

$projectRoot = file_exists(dirname(__DIR__, 2) . '/.env') ? dirname(__DIR__, 2) : dirname(__DIR__);
$envFile = $projectRoot . '/.env';
if (!is_file($envFile)) {
    return;
}
$lines = @file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#') {
        continue;
    }
    if (preg_match('/^([A-Za-z_][A-Za-z0-9_]*)=(.*)$/', $line, $m)) {
        $key = trim($m[1]);
        if (getenv($key) !== false) {
            continue;
        }
        putenv($key . '=' . trim(trim($m[2]), " \t\"'"));
    }
}
