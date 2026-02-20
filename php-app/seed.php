#!/usr/bin/env php
<?php
/**
 * İlk kurulum seed: En az bir şirket ve super admin kullanıcı oluşturur.
 * Super_admin ayarlar sayfasına girebilmek için en az bir şirket gerekir (getCompanyIdForUser ilk şirketi döner).
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

try {
    $pdo->query('SELECT 1 FROM companies LIMIT 1');
} catch (Throwable $e) {
    echo "HATA: companies tablosu yok veya veritabani baglantisi basarisiz.\n";
    echo "Once tablolari olusturun: proje kokunden  php artisan migrate --force\n";
    echo "Sonra tekrar: php php-app/seed.php\n";
    exit(1);
}

// 1) En az bir şirket yoksa varsayılan şirket oluştur (super_admin ayarlar sayfasına girebilsin diye)
$seedCompanyId = 'b2c3d4e5-f6a7-8901-bcde-f23456789012';
$stmt = $pdo->query('SELECT id FROM companies WHERE deleted_at IS NULL LIMIT 1');
$companyRow = $stmt->fetch();
if (!$companyRow) {
    $pdo->prepare(
        'INSERT INTO companies (id, name, slug, project_name, is_active) VALUES (?, ?, ?, ?, 1)'
    )->execute([$seedCompanyId, 'DepoPazar', 'depopazar', 'DepoPazar']);
    echo "Seed: Varsayilan sirket olusturuldu (DepoPazar).\n";
} else {
    $seedCompanyId = $companyRow['id'];
}

// 1b) Varsayılan masraf kategorileri (expense_categories tablosu varsa ve boşsa)
try {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM expense_categories WHERE company_id = ? AND deleted_at IS NULL');
    $stmt->execute([$seedCompanyId]);
    if ((int) $stmt->fetchColumn() === 0) {
        $categories = [
            ['Kira', 'Ofis/depo kirası'],
            ['Elektrik', 'Elektrik faturaları'],
            ['Yakıt', 'Araç yakıt masrafları'],
            ['Bakım', 'Bakım ve onarım'],
            ['Diğer', 'Diğer masraflar'],
        ];
        foreach ($categories as $i => $c) {
            $id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
            $pdo->prepare('INSERT INTO expense_categories (id, company_id, name, description, sort_order) VALUES (?, ?, ?, ?, ?)')
                ->execute([$id, $seedCompanyId, $c[0], $c[1], $i]);
        }
        echo "Seed: Varsayilan masraf kategorileri olusturuldu (" . count($categories) . " adet).\n";
    }
} catch (Throwable $e) {
    // expense_categories tablosu yoksa (migration çalışmamışsa) sessizce atla
}

// 2) Super admin kullanıcı: yoksa oluştur, varsa şifreyi bilinen değere sıfırla (giriş garantisi)
$seedEmail = 'erkanulker0@gmail.com';
$seedPassword = 'password';
$hash = password_hash($seedPassword, PASSWORD_BCRYPT, ['cost' => 10]);
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND deleted_at IS NULL');
$stmt->execute([$seedEmail]);
$existing = $stmt->fetch();
if ($existing) {
    $pdo->prepare('UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?')->execute([$hash, $existing['id']]);
    echo "Seed: Super admin zaten mevcut; sifre '$seedPassword' olarak guncellendi ($seedEmail).\n";
    exit(0);
}

$id = 'a1b2c3d4-e5f6-7890-abcd-ef1234567890';
$stmt = $pdo->prepare(
    'INSERT INTO users (id, email, password, first_name, last_name, phone, role, company_id, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, NULL, 1)'
);
$stmt->execute([$id, $seedEmail, $hash, 'Erkan', 'Ülker', null, 'super_admin']);

echo "Seed: Super admin olusturuldu: $seedEmail (sifre: $seedPassword)\n";
