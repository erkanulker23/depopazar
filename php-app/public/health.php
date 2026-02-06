<?php
/**
 * Sunucu sağlık kontrolü – 500 hatası ayıklama için.
 * Sorun giderildikten sonra bu dosyayı silebilir veya erişimi kapatabilirsiniz.
 */
header('Content-Type: text/html; charset=utf-8');
echo "<h1>DepoPazar – Sunucu Kontrolü</h1>\n<pre>\n";

$appRoot = dirname(__DIR__);

echo "PHP: " . PHP_VERSION . "\n";
echo "APP_ROOT: " . $appRoot . "\n\n";

// 1. config
echo "1. config/config.php: ";
if (!is_readable($appRoot . '/config/config.php')) {
    echo "BULUNAMADI\n";
    exit;
}
$config = require $appRoot . '/config/config.php';
echo "OK\n";

// 2. db.local.php var mı?
echo "2. config/db.local.php: ";
$dbLocal = $appRoot . '/config/db.local.php';
if (!file_exists($dbLocal)) {
    echo "YOK (Deploy script çalışmamış olabilir. Forge'da Deploy Now yapın veya .env + deploy.sh ile oluşturun.)\n";
    echo "\nOlası çözüm: Forge Environment'ta DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD tanımlı olmalı.\n";
    echo "Ardından 'Deploy Now' ile deploy.sh tekrar çalıştırılmalı.\n";
    exit;
}
echo "var\n";

// 3. Veritabanı bağlantısı
echo "3. Veritabanı: ";
try {
    $pdo = require $dbLocal;
    if (!$pdo instanceof PDO) {
        echo "db.local.php PDO döndürmüyor.\n";
        exit;
    }
    $pdo->query('SELECT 1');
    echo "OK\n";
} catch (Throwable $e) {
    echo "HATA: " . $e->getMessage() . "\n";
    echo "\nKontrol: .env (Forge Environment) içinde DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD doğru mu?\n";
    exit;
}

// 4. Session dizini (opsiyonel)
echo "4. session.save_path: " . ini_get('session.save_path') . "\n";

// 5. vendor/autoload.php (Composer - ÇOK ÖNEMLİ!)
echo "5. vendor/autoload.php: ";
$autoload = $appRoot . '/vendor/autoload.php';
if (!file_exists($autoload)) {
    echo "BULUNAMADI!\n";
    echo "\n→ Sunucuda 'composer install' çalıştırılmamış. Çözüm:\n";
    echo "  SSH ile sunucuya bağlanın: ssh forge@sunucu-ip\n";
    echo "  cd " . $appRoot . "\n";
    echo "  composer install --no-dev --optimize-autoloader\n";
    echo "\n  Veya Forge'da Deploy Now yapın (deploy.sh composer install çalıştırır).\n";
    exit;
}
echo "OK\n";

// 6. helpers.php
echo "6. app/helpers.php: ";
if (!is_readable($appRoot . '/app/helpers.php')) {
    echo "BULUNAMADI\n";
    exit;
}
echo "OK\n";

// 7. index.php bootstrap simülasyonu (hatayı yakalamak için)
echo "7. index.php bootstrap: ";
ini_set('display_errors', 1);
error_reporting(E_ALL);
try {
    require $autoload;
    require $appRoot . '/app/helpers.php';
    if (!class_exists('Auth')) {
        throw new Exception('Auth sınıfı yüklenemedi');
    }
    Auth::init();
    if (!class_exists('Router')) {
        throw new Exception('Router sınıfı yüklenemedi');
    }
    echo "OK\n";
} catch (Throwable $e) {
    echo "HATA!\n";
    echo "\nMesaj: " . $e->getMessage() . "\n";
    echo "Dosya: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    exit;
}

echo "\nTüm kontroller geçti. Ana uygulama (index.php) çalışmıyorsa:\n";
echo "- Nginx Web Directory: php-app/public olmalı\n";
echo "- PHP 8.0+ ve pdo_mysql extension yüklü olmalı\n";
echo "- Sunucu hata logları: Forge Logs veya /var/log/nginx/error.log\n";
echo "</pre>\n";
