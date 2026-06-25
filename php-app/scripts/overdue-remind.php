#!/usr/bin/env php
<?php
/**
 * Vadesi geçmiş ödemeler için müşteri e-posta hatırlatması.
 * Kullanım: php php-app/scripts/overdue-remind.php
 * Cron: 0 9 * * * cd /path/to/site && php php-app/scripts/overdue-remind.php
 */
define('APP_ROOT', dirname(__DIR__));
$config = require APP_ROOT . '/config/config.php';
date_default_timezone_set($config['timezone'] ?? 'Europe/Istanbul');

require APP_ROOT . '/app/helpers.php';
require APP_ROOT . '/vendor/autoload.php';

$pdo = require APP_ROOT . '/config/db.php';

$result = OverdueReminderService::sendAll($pdo);

echo 'Gönderilen e-posta: ' . (int) ($result['sent'] ?? 0) . PHP_EOL;
echo 'Atlanan şirket (SMTP/kapalı): ' . (int) ($result['skipped_companies'] ?? 0) . PHP_EOL;
if (!empty($result['errors'])) {
    echo 'Hatalar:' . PHP_EOL;
    foreach ($result['errors'] as $err) {
        echo '  - ' . $err . PHP_EOL;
    }
}

exit(empty($result['errors']) ? 0 : 1);
