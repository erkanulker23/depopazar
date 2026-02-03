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
}
