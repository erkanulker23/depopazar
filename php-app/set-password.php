#!/usr/bin/env php
<?php
/**
 * Şifre sıfırlama (CLI). Kullanım: php set-password.php <email> <yeni_sifre>
 */
if (php_sapi_name() !== 'cli') {
    die('Sadece CLI\'dan calistirilir.');
}
if ($argc < 3) {
    echo "Kullanim: php set-password.php <email> <yeni_sifre>\n";
    exit(1);
}
$email = $argv[1];
$plain = $argv[2];

define('APP_ROOT', __DIR__);
$pdo = require APP_ROOT . '/config/db.php';
$hash = password_hash($plain, PASSWORD_BCRYPT, ['cost' => 10]);
$stmt = $pdo->prepare('UPDATE users SET password = ?, updated_at = NOW() WHERE email = ? AND deleted_at IS NULL');
$stmt->execute([$hash, $email]);
if ($stmt->rowCount() === 0) {
    echo "Kullanici bulunamadi veya guncellenmedi: $email\n";
    exit(1);
}
echo "OK. $email sifresi guncellendi.\n";
