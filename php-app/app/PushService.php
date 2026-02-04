<?php
/**
 * Web Push bildirimleri: VAPID ile abonelere push gönderir.
 * Composer ile minishlink/web-push yüklü olmalı (composer install).
 * VAPID anahtarları config'de vapid_public_key ve vapid_private_key olarak tanımlanmalı.
 */
class PushService
{
    /**
     * Belirtilen kullanıcılara push bildirimi gönderir (cihazlara/telefonlara).
     * Composer veya VAPID yoksa sessizce atlanır.
     */
    public static function sendToUsers(PDO $pdo, array $userIds, string $title, string $message): void
    {
        $userIds = array_unique(array_filter($userIds));
        if (empty($userIds)) {
            return;
        }
        $config = require dirname(__DIR__) . '/config/config.php';
        $vapidPublic = $config['vapid_public_key'] ?? '';
        $vapidPrivate = $config['vapid_private_key'] ?? '';
        if ($vapidPublic === '' || $vapidPrivate === '') {
            return;
        }
        if (!is_file(dirname(__DIR__) . '/vendor/autoload.php')) {
            return;
        }
        require_once dirname(__DIR__) . '/vendor/autoload.php';
        if (!class_exists(\Minishlink\WebPush\WebPush::class)) {
            return;
        }

        $subscriptions = PushSubscription::getByUserIds($pdo, $userIds);
        if (empty($subscriptions)) {
            return;
        }

        $payload = json_encode([
            'title' => $title,
            'body'  => $message,
            'icon'  => '/favicon.ico',
        ], JSON_UNESCAPED_UNICODE);

        try {
            $auth = [
                'VAPID' => [
                    'subject'    => 'mailto:' . ($config['push_contact_email'] ?? 'noreply@depopazar.com'),
                    'publicKey'  => $vapidPublic,
                    'privateKey' => $vapidPrivate,
                ],
            ];
            $webPush = new \Minishlink\WebPush\WebPush($auth);
            $webPush->setReuseVAPIDHeaders(true);

            foreach ($subscriptions as $sub) {
                try {
                    $subscription = \Minishlink\WebPush\Subscription::create([
                        'endpoint' => $sub['endpoint'],
                        'keys'     => [
                            'p256dh' => $sub['p256dh_key'],
                            'auth'   => $sub['auth_key'],
                        ],
                    ]);
                    $webPush->queueNotification($subscription, $payload);
                } catch (Throwable $e) {
                    // Geçersiz abonelik olabilir, atla
                }
            }
            $reports = $webPush->flush();
            foreach ($reports as $report) {
                if (!$report->isSuccess() && $report->isSubscriptionExpired() && $report->getEndpoint()) {
                    PushSubscription::deleteByEndpoint($pdo, $report->getEndpoint());
                }
            }
        } catch (Throwable $e) {
            error_log('PushService::sendToUsers error: ' . $e->getMessage());
        }
    }
}
