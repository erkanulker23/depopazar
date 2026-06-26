<?php
class Auth
{
    private static ?array $user = null;
    /** @var array<string, mixed> */
    private static array $pendingSession = [];
    private static bool $shutdownRegistered = false;

    public static function init(): void
    {
        $isPost = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
        if (session_status() === PHP_SESSION_NONE) {
            $config = require __DIR__ . '/../config/config.php';
            session_name($config['session_name'] ?? 'app_session');
            if ($isPost) {
                session_start();
            } else {
                session_start(['read_and_close' => true]);
            }
        }
        if (isset($_SESSION['user'])) {
            self::$user = $_SESSION['user'];
        }
        if ($isPost && session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }

    public static function ensureSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $config = require __DIR__ . '/../config/config.php';
            session_name($config['session_name'] ?? 'app_session');
            session_start();
        }
    }

    public static function setSession(string $key, $value): void
    {
        self::$pendingSession[$key] = $value;
        if (!self::$shutdownRegistered) {
            self::$shutdownRegistered = true;
            register_shutdown_function([self::class, 'persistPendingSession']);
        }
    }

    public static function persistPendingSession(): void
    {
        if (self::$pendingSession === []) {
            return;
        }
        self::ensureSession();
        foreach (self::$pendingSession as $key => $value) {
            $_SESSION[$key] = $value;
        }
        self::$pendingSession = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        releaseHttpResponse();
    }

    public static function getSession(string $key, $default = null)
    {
        self::ensureSession();
        $value = $_SESSION[$key] ?? $default;
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        return $value;
    }

    /** @return array{success: ?string, error: ?string} */
    public static function consumeFlash(): array
    {
        self::ensureSession();
        $flash = [
            'success' => $_SESSION['flash_success'] ?? null,
            'error' => $_SESSION['flash_error'] ?? null,
        ];
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        return $flash;
    }

    public static function login(array $user): void
    {
        self::$user = $user;
        self::setSession('user', $user);
    }

    public static function refreshUser(array $user): void
    {
        self::$user = $user;
        self::setSession('user', $user);
    }

    public static function logout(): void
    {
        self::ensureSession();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        self::$user = null;
        self::$pendingSession = [];
    }

    public static function user(): ?array
    {
        return self::$user;
    }

    public static function isAuthenticated(): bool
    {
        return self::$user !== null;
    }

    public static function isCustomer(): bool
    {
        return (self::$user['role'] ?? '') === 'customer';
    }

    public static function isStaff(): bool
    {
        return in_array(self::$user['role'] ?? '', ['super_admin', 'company_owner', 'company_staff', 'data_entry', 'accounting'], true);
    }

    public static function requireLogin(): void
    {
        if (!self::isAuthenticated()) {
            header('Location: /giris');
            exit;
        }
    }

    public static function requireStaff(): void
    {
        self::requireLogin();
        if (!self::isStaff()) {
            header('Location: ' . (self::isCustomer() ? '/musteri/genel-bakis' : '/genel-bakis'));
            exit;
        }
    }

    public static function requireCustomer(): void
    {
        self::requireLogin();
        if (!self::isCustomer()) {
            header('Location: /genel-bakis');
            exit;
        }
    }

    public static function requireRoles(array $roles): void
    {
        self::requireStaff();
        $role = self::$user['role'] ?? '';
        if (!in_array($role, $roles, true)) {
            self::setSession('flash_error', 'Bu işlem için yetkiniz yok.');
            header('Location: /genel-bakis');
            exit;
        }
    }

    public static function canAccessNav(string $href): bool
    {
        $role = self::$user['role'] ?? '';
        return RolePermissions::canViewNav($role, $href);
    }

    public static function can(string $moduleId, string $action): bool
    {
        $role = self::$user['role'] ?? '';
        return RolePermissions::can($role, $moduleId, $action);
    }
}
