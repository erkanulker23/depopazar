#!/usr/bin/env php
<?php
/**
 * Migrations çalıştırır. db.local.php veya .env'deki DB bilgilerini kullanır.
 * Kullanım: php php-app/scripts/run-migrations.php
 */
if (php_sapi_name() !== 'cli') {
    die('Sadece CLI\'dan calistirilir.');
}

define('APP_ROOT', dirname(__DIR__));
$migrationsDir = APP_ROOT . '/sql/migrations';

// DB bilgilerini al (db.local.php veya .env)
$host = '127.0.0.1';
$port = '3306';
$database = 'depotakip';
$username = 'root';
$password = '';

if (file_exists(APP_ROOT . '/config/db.local.php')) {
    $content = file_get_contents(APP_ROOT . '/config/db.local.php');
    if (preg_match('/host=([^;]+)/', $content, $m)) $host = trim($m[1], " '\"");
    if (preg_match('/port=(\d+)/', $content, $m)) $port = $m[1];
    if (preg_match('/dbname=([^;]+)/', $content, $m)) $database = trim($m[1], " '\"");
    if (preg_match('/new PDO\(\s*[^,]+,\s*[\'"]([^\'"]+)[\'"]/', $content, $m)) $username = $m[1];
    if (preg_match('/new PDO\(\s*[^,]+,\s*[^,]+,\s*[\'"]([^\'"]*)[\'"]/', $content, $m)) $password = $m[1];
}

if (file_exists(dirname(APP_ROOT) . '/.env')) {
    $env = parse_ini_file(dirname(APP_ROOT) . '/.env', false, INI_SCANNER_RAW);
    if (!empty($env['DB_HOST'])) $host = $env['DB_HOST'];
    if (!empty($env['DB_PORT'])) $port = $env['DB_PORT'];
    if (!empty($env['DB_DATABASE'])) $database = $env['DB_DATABASE'];
    if (!empty($env['DB_USERNAME'])) $username = $env['DB_USERNAME'];
    if (!empty($env['DB_PASSWORD'])) $password = $env['DB_PASSWORD'];
}

if (!is_dir($migrationsDir)) {
    echo "Migrations klasörü bulunamadı.\n";
    exit(0);
}

$files = glob($migrationsDir . '/*.sql');
sort($files);

$passArg = $password !== '' ? "-p" . escapeshellarg($password) : '';

foreach ($files as $f) {
    $name = basename($f);
    $cmd = sprintf(
        'mysql -h %s -P %s -u %s %s %s < %s 2>/dev/null',
        escapeshellarg($host),
        escapeshellarg($port),
        escapeshellarg($username),
        $passArg,
        escapeshellarg($database),
        escapeshellarg($f)
    );
    exec($cmd, $out, $code);
    if ($code === 0) {
        echo "  [OK] $name\n";
    } else {
        echo "  [atla/uyari] $name (exit $code)\n";
    }
}

echo "Migrations tamamlandi.\n";
