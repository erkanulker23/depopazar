<?php
class AuthController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function showLogin(): void
    {
        if (Auth::isAuthenticated()) {
            $this->redirectByRole();
            return;
        }
        $config = require __DIR__ . '/../../config/config.php';
        $brand = Company::getPublicBrand($this->pdo);
        $projectName = $brand['project_name'] ?? $config['app_name'];
        require __DIR__ . '/../../views/auth/login.php';
    }

    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /giris');
            exit;
        }
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        if ($email === '' || $password === '') {
            $this->showLoginWithError('E-posta ve şifre gerekli.');
            return;
        }
        $user = User::findByEmail($this->pdo, $email);
        if (!$user || !User::verifyPassword($password, $user['password'])) {
            $this->showLoginWithError('Geçersiz e-posta veya şifre.');
            return;
        }
        if (empty($user['is_active'])) {
            $this->showLoginWithError('Hesap pasif.');
            return;
        }
        User::updateLastLogin($this->pdo, $user['id']);
        Auth::login([
            'id' => $user['id'],
            'email' => $user['email'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'role' => $user['role'],
            'company_id' => $user['company_id'],
        ]);
        if (!empty($user['company_id'])) {
            $company = Company::findOne($this->pdo, $user['company_id']);
            $_SESSION['company_project_name'] = trim($company['project_name'] ?? '') !== '' ? $company['project_name'] : ($company['name'] ?? null);
            $_SESSION['company_name'] = trim($company['name'] ?? '') !== '' ? $company['name'] : null;
            $_SESSION['company_logo_url'] = $company['logo_url'] ?? null;
        } else {
            $brand = Company::getPublicBrand($this->pdo);
            $_SESSION['company_project_name'] = $brand['project_name'] ?? null;
            $_SESSION['company_name'] = (isset($brand['name']) && trim($brand['name']) !== '') ? $brand['name'] : null;
            $_SESSION['company_logo_url'] = $brand['logo_url'] ?? null;
        }
        $this->redirectByRole();
    }

    public function logout(): void
    {
        Auth::logout();
        header('Location: /giris');
        exit;
    }

    private function redirectByRole(): void
    {
        header('Location: ' . (Auth::isCustomer() ? '/musteri/genel-bakis' : '/genel-bakis'));
        exit;
    }

    private function showLoginWithError(string $message): void
    {
        $config = require __DIR__ . '/../../config/config.php';
        $brand = Company::getPublicBrand($this->pdo);
        $projectName = $brand['project_name'] ?? $config['app_name'];
        $error = $message;
        require __DIR__ . '/../../views/auth/login.php';
    }
}
