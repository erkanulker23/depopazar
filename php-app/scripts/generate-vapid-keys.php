#!/usr/bin/env php
<?php
/**
 * VAPID anahtarlarını üretir (Web Push için).
 * Kullanım: php scripts/generate-vapid-keys.php
 * veya proje kökünden: php php-app/scripts/generate-vapid-keys.php
 *
 * Çıktıyı config.php içine veya ortam değişkenlerine ekleyin:
 * vapid_public_key  => '...',
 * vapid_private_key => '...',
 */
$autoload = __DIR__ . '/../vendor/autoload.php';
if (!is_file($autoload)) {
    echo "Önce composer install çalıştırın: cd php-app && composer install\n";
    exit(1);
}
require $autoload;

use Minishlink\WebPush\VAPID;

$keys = VAPID::createVapidKeys();
echo "Aşağıdaki anahtarları config/config.php içine ekleyin veya ortam değişkeni olarak verin:\n\n";
echo "vapid_public_key  => '" . $keys['publicKey'] . "',\n";
echo "vapid_private_key => '" . $keys['privateKey'] . "',\n\n";
echo "Veya:\n";
echo "export VAPID_PUBLIC_KEY='" . $keys['publicKey'] . "'\n";
echo "export VAPID_PRIVATE_KEY='" . $keys['privateKey'] . "'\n";
