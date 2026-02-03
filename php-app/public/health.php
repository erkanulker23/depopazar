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

echo "\nTüm kontroller geçti. Ana uygulama (index.php) çalışmıyorsa:\n";
echo "- Nginx Web Directory: php-app/public olmalı\n";
echo "- PHP 8.0+ ve pdo_mysql extension yüklü olmalı\n";
echo "- Sunucu hata logları: Forge Logs veya /var/log/nginx/error.log\n";
echo "</pre>\n";
