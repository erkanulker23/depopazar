<?php
if (file_exists(__DIR__ . '/db.local.php')) {
    return require __DIR__ . '/db.local.php';
}

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
