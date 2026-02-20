<?php
// Önce .env yükle (CLI'da lokal geliştirme için .env tercih edilsin)
$projectRoot = file_exists(dirname(__DIR__, 2) . '/.env') ? dirname(__DIR__, 2) : dirname(__DIR__);
$envFile = file_exists($projectRoot . '/.env') ? $projectRoot . '/.env' : null;
if ($envFile) {
    $lines = @file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (preg_match('/^([A-Za-z_][A-Za-z0-9_]*)=(.*)$/', $line, $m)) {
            putenv(trim($m[1]) . '=' . trim(trim($m[2]), " \t\"'"));
        }
    }
}

// CLI'da .env'de DB bilgisi varsa onu kullan (lokal: php php-app/seed.php); sunucuda db.local.php kullan
if (php_sapi_name() === 'cli' && getenv('DB_USERNAME') !== false && getenv('DB_USERNAME') !== '') {
    // .env yüklü ve DB_USERNAME set; db.local.php'e bakmadan aşağıdaki getenv() ile devam et
} elseif (file_exists(__DIR__ . '/db.local.php')) {
    return require __DIR__ . '/db.local.php';
}

// .env yüklendiyse getenv() artık doğru değerleri döner
$dbConfig = [
    'host'     => getenv('DB_HOST') ?: '127.0.0.1',
    'port'     => getenv('DB_PORT') ?: '3306',
    'dbname'   => getenv('DB_DATABASE') ?: 'depotakip',
    'username' => getenv('DB_USERNAME') ?: 'root',
    'password' => getenv('DB_PASSWORD') ?: '',
    'charset'  => 'utf8mb4',
];

$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
    $dbConfig['host'],
    $dbConfig['port'],
    $dbConfig['dbname'],
    $dbConfig['charset']
);

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $options);
} catch (PDOException $e) {
    if (php_sapi_name() === 'cli') {
        fwrite(STDERR, 'Veritabanı bağlantı hatası: ' . $e->getMessage() . "\n");
        exit(1);
    }
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    echo '<h1>Veritabanı bağlantı hatası</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>';
    exit;
}

return $pdo;
