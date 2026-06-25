#!/usr/bin/env php
<?php
/**
 * VAPID anahtarlarını üretir (Web Push / telefon bildirimleri).
 *
 * Kullanım (site kökünden):
 *   php artisan vapid:generate
 *   php php-app/scripts/generate-vapid-keys.php
 *
 * Çıktıyı Forge → Site → Environment dosyasına ekleyin (git'e koymayın).
 */
declare(strict_types=1);

function vapidBase64UrlEncode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function vapidCreateKeysOpenSsl(): array
{
    if (!function_exists('openssl_pkey_new')) {
        throw new RuntimeException('PHP openssl eklentisi yüklü değil.');
    }
    $keyResource = openssl_pkey_new([
        'curve_name' => 'prime256v1',
        'private_key_type' => OPENSSL_KEYTYPE_EC,
    ]);
    if ($keyResource === false) {
        throw new RuntimeException('EC anahtar oluşturulamadı: ' . (openssl_error_string() ?: 'bilinmeyen hata'));
    }
    $details = openssl_pkey_get_details($keyResource);
    if (!is_array($details) || empty($details['ec']['x']) || empty($details['ec']['y']) || empty($details['ec']['d'])) {
        throw new RuntimeException('EC anahtar detayları okunamadı.');
    }
    $publicKey = "\x04" . $details['ec']['x'] . $details['ec']['y'];
    return [
        'publicKey' => vapidBase64UrlEncode($publicKey),
        'privateKey' => vapidBase64UrlEncode($details['ec']['d']),
    ];
}

function vapidCreateKeys(): array
{
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (is_file($autoload)) {
        require_once $autoload;
        if (class_exists(\Minishlink\WebPush\VAPID::class)) {
            return \Minishlink\WebPush\VAPID::createVapidKeys();
        }
    }
    return vapidCreateKeysOpenSsl();
}

try {
    $keys = vapidCreateKeys();
} catch (Throwable $e) {
    fwrite(STDERR, "HATA: " . $e->getMessage() . "\n");
    fwrite(STDERR, "Dizin: " . getcwd() . "\n");
    exit(1);
}

$out = static function (string $line): void {
    echo $line . "\n";
};

$out('');
$out('=== VAPID anahtarları (Forge Environment\'a yapıştırın) ===');
$out('');
$out('VAPID_PUBLIC_KEY=' . $keys['publicKey']);
$out('VAPID_PRIVATE_KEY=' . $keys['privateKey']);
$out('PUSH_CONTACT_EMAIL=info@sizin-domain.com');
$out('');
$out('Forge: Site → Environment → yukarıdaki 3 satırı ekleyin → Save → Deploy');
$out('');

exit(0);
