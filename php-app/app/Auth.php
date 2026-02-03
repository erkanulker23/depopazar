<?php
class Auth
{
    private static ?array $user = null;

    public static function init(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $config = require __DIR__ . '/../config/config.php';
            session_name($config['session_name'] ?? 'app_session');
            session_start();
        }
        if (isset($_SESSION['user'])) self::$user = $_SESSION['user'];
    }

    public static function login(array $user): void
    {
        $_SESSION['user'] = $user;
        self::$user = $user;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        self::$user = null;
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
}
