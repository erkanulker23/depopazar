#!/usr/bin/env php
<?php
/**
 * İlk kurulum seed: Super admin kullanıcı yoksa oluşturur.
 * Deploy sırasında deploy.sh tarafından çalıştırılır.
 *
 * Komut satırından (proje kökünden):
 *   php php-app/seed.php
 * veya php-app içinden:
 *   php seed.php
 */
if (php_sapi_name() !== 'cli') {
    die('Sadece CLI\'dan calistirilir.');
}

define('APP_ROOT', __DIR__);

if (!is_readable(APP_ROOT . '/config/db.php')) {
    echo "Seed atlandi: config/db.php veya db.local.php yok (once deploy calistirin).\n";
    exit(0);
}

$pdo = require APP_ROOT . '/config/db.php';

$seedEmail = 'erkanulker0@gmail.com';
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND deleted_at IS NULL');
$stmt->execute([$seedEmail]);
if ($stmt->fetch()) {
    echo "Seed: Super admin zaten mevcut ($seedEmail).\n";
    exit(0);
}

$id = 'a1b2c3d4-e5f6-7890-abcd-ef1234567890';
$hash = password_hash('password', PASSWORD_BCRYPT, ['cost' => 10]);
$stmt = $pdo->prepare(
    'INSERT INTO users (id, email, password, first_name, last_name, phone, role, company_id, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, NULL, 1)'
);
$stmt->execute([$id, $seedEmail, $hash, 'Erkan', 'Ülker', null, 'super_admin']);

echo "Seed: Super admin olusturuldu: $seedEmail (sifre: password)\n";
