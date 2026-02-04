<?php
class NotificationsController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function index(): void
    {
        Auth::requireStaff();
        $user = Auth::user();
        $userId = $user['id'] ?? '';
        if (!$userId) {
            header('Location: /genel-bakis');
            exit;
        }
        $notifications = Notification::findByUserId($this->pdo, $userId, 200, false);
        $pageTitle = 'Bildirimler';
        $currentPage = 'bildirimler';
        require __DIR__ . '/../../views/notifications/index.php';
    }

    /** JSON: dropdown için son bildirimler + okunmamış sayısı */
    public function apiList(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (!Auth::isAuthenticated()) {
            echo json_encode(['notifications' => [], 'unread_count' => 0]);
            return;
        }
        $user = Auth::user();
        $userId = $user['id'] ?? '';
        if (!$userId) {
            echo json_encode(['notifications' => [], 'unread_count' => 0]);
            return;
        }
        $notifications = Notification::findByUserId($this->pdo, $userId, 20, false);
        $unreadCount = Notification::countUnread($this->pdo, $userId);
        echo json_encode([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ], JSON_UNESCAPED_UNICODE);
    }

    public function markAllRead(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /bildirimler');
            exit;
        }
        $user = Auth::user();
        $userId = $user['id'] ?? '';
        if ($userId) {
            Notification::markAllRead($this->pdo, $userId);
        }
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => true]);
            return;
        }
        $_SESSION['flash_success'] = 'Tüm bildirimler okundu olarak işaretlendi.';
        header('Location: /bildirimler');
        exit;
    }

    public function deleteAll(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /bildirimler');
            exit;
        }
        $user = Auth::user();
        $userId = $user['id'] ?? '';
        if ($userId) {
            Notification::deleteAll($this->pdo, $userId);
        }
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => true]);
            return;
        }
        $_SESSION['flash_success'] = 'Tüm bildirimler silindi.';
        header('Location: /bildirimler');
        exit;
    }

    /** Web Push: VAPID public key (frontend abonelik için) */
    public function apiVapidPublic(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (!Auth::isAuthenticated()) {
            echo json_encode(['publicKey' => null]);
            return;
        }
        $config = require __DIR__ . '/../../config/config.php';
        echo json_encode(['publicKey' => $config['vapid_public_key'] ?? null]);
    }

    /** Web Push: Tarayıcı/cihaz aboneliğini kaydet */
    public function apiPushSubscribe(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
            return;
        }
        if (!Auth::isAuthenticated()) {
            echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
            return;
        }
        $user = Auth::user();
        $userId = $user['id'] ?? '';
        if (!$userId) {
            echo json_encode(['ok' => false, 'error' => 'User not found']);
            return;
        }
        $input = json_decode(file_get_contents('php://input') ?: '{}', true) ?: [];
        $subscription = $input['subscription'] ?? null;
        if (!$subscription || empty($subscription['endpoint']) || empty($subscription['keys']['p256dh']) || empty($subscription['keys']['auth'])) {
            echo json_encode(['ok' => false, 'error' => 'Invalid subscription']);
            return;
        }
        try {
            PushSubscription::save(
                $this->pdo,
                $userId,
                $subscription['endpoint'],
                $subscription['keys']['p256dh'],
                $subscription['keys']['auth'],
                $_SERVER['HTTP_USER_AGENT'] ?? null
            );
            echo json_encode(['ok' => true]);
        } catch (Throwable $e) {
            error_log('apiPushSubscribe: ' . $e->getMessage());
            echo json_encode(['ok' => false, 'error' => 'Save failed']);
        }
    }
}
