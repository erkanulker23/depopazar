#!/usr/bin/env php
<?php
/**
 * Tüm migration'ları sırayla çalıştırır (php-app/config/db veya .env kullanır).
 * Kullanım: php scripts/run-migrations.php   veya  cd php-app && php scripts/run-migrations.php
 */
$root = dirname(__DIR__);
if (!is_file($root . '/config/config.php')) {
    fwrite(STDERR, "Hata: php-app/config/config.php bulunamadı.\n");
    exit(1);
}

$config = require $root . '/config/config.php';
date_default_timezone_set($config['timezone'] ?? 'Europe/Istanbul');

$envFile = file_exists($root . '/.env') ? $root . '/.env' : (file_exists(dirname($root) . '/.env') ? dirname($root) . '/.env' : null);
if ($envFile) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (preg_match('/^([A-Za-z_][A-Za-z0-9_]*)=(.*)$/', $line, $m)) {
            putenv(trim($m[1]) . '=' . trim($m[2], " \t\"'"));
        }
    }
}

if (file_exists($root . '/config/db.local.php')) {
    $pdo = require $root . '/config/db.local.php';
} else {
    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $port = getenv('DB_PORT') ?: '3306';
    $db   = getenv('DB_DATABASE') ?: 'depotakip';
    $user = getenv('DB_USERNAME') ?: 'root';
    $pass = getenv('DB_PASSWORD') ?: '';
    $dsn  = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    try {
        $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    } catch (PDOException $e) {
        fwrite(STDERR, "Veritabanı bağlantı hatası: " . $e->getMessage() . "\n");
        exit(1);
    }
}

$dir = $root . '/sql/migrations';
if (!is_dir($dir)) {
    fwrite(STDERR, "Migrations dizini yok: $dir\n");
    exit(1);
}

$files = glob($dir . '/*.sql');
sort($files);
$ok = 0;
foreach ($files as $f) {
    $name = basename($f);
    $sql = file_get_contents($f);
    if (trim($sql) === '') continue;
    try {
        $pdo->exec($sql);
        echo "  Migration: $name\n";
        $ok++;
    } catch (PDOException $e) {
        fwrite(STDERR, "  HATA: $name - " . $e->getMessage() . "\n");
        exit(1);
    }
}

echo "Toplam $ok migration çalıştırıldı.\n";
