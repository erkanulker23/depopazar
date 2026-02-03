<?php
class UsersController
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
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        $staff = User::findStaff($this->pdo, $companyId);
        if (($user['role'] ?? '') === 'super_admin' && !$companyId) {
            $staff = User::findStaff($this->pdo, null);
        }
        $roleLabels = [
            'super_admin' => 'Süper Admin',
            'company_owner' => 'Şirket Sahibi',
            'company_staff' => 'Personel',
            'data_entry' => 'Veri Girişi',
            'accounting' => 'Muhasebe',
            'customer' => 'Müşteri',
        ];
        $companies = [];
        if (($user['role'] ?? '') === 'super_admin') {
            $stmt = $this->pdo->query('SELECT id, name FROM companies WHERE deleted_at IS NULL ORDER BY name');
            $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        $canManageUsers = ($user['role'] ?? '') === 'super_admin' || ($user['role'] ?? '') === 'company_owner';
        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        require __DIR__ . '/../../views/users/index.php';
    }

    public function show(array $params): void
    {
        Auth::requireStaff();
        $id = $params['id'] ?? '';
        if (!$id) {
            header('Location: /kullanicilar');
            exit;
        }
        $profile = User::findOne($this->pdo, $id);
        if (!$profile) {
            $_SESSION['flash_error'] = 'Kullanıcı bulunamadı.';
            header('Location: /kullanicilar');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($profile['company_id'] ?? '') !== $companyId && ($profile['role'] ?? '') !== 'super_admin') {
            $_SESSION['flash_error'] = 'Bu kullanıcıya erişim yetkiniz yok.';
            header('Location: /kullanicilar');
            exit;
        }
        $roleLabels = [
            'super_admin' => 'Süper Admin',
            'company_owner' => 'Şirket Sahibi',
            'company_staff' => 'Personel',
            'data_entry' => 'Veri Girişi',
            'accounting' => 'Muhasebe',
            'customer' => 'Müşteri',
        ];
        $companyName = null;
        if (!empty($profile['company_id'])) {
            $c = Company::findOne($this->pdo, $profile['company_id']);
            $companyName = $c['name'] ?? null;
        }
        $pageTitle = 'Kullanıcı: ' . trim(($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? ''));
        require __DIR__ . '/../../views/users/detail.php';
    }

    public function editForm(array $params): void
    {
        Auth::requireStaff();
        $id = $params['id'] ?? '';
        if (!$id) {
            header('Location: /kullanicilar');
            exit;
        }
        $profile = User::findOne($this->pdo, $id);
        if (!$profile) {
            $_SESSION['flash_error'] = 'Kullanıcı bulunamadı.';
            header('Location: /kullanicilar');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        $canManage = ($user['role'] ?? '') === 'super_admin' || ($user['role'] ?? '') === 'company_owner';
        if (!$canManage || ($companyId && ($profile['company_id'] ?? '') !== $companyId && ($profile['role'] ?? '') !== 'super_admin')) {
            $_SESSION['flash_error'] = 'Bu kullanıcıyı düzenleyemezsiniz.';
            header('Location: /kullanicilar');
            exit;
        }
        $companies = [];
        if (($user['role'] ?? '') === 'super_admin') {
            $stmt = $this->pdo->query('SELECT id, name FROM companies WHERE deleted_at IS NULL ORDER BY name');
            $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        $roleLabels = [
            'super_admin' => 'Süper Admin',
            'company_owner' => 'Şirket Sahibi',
            'company_staff' => 'Personel',
            'data_entry' => 'Veri Girişi',
            'accounting' => 'Muhasebe',
            'customer' => 'Müşteri',
        ];
        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        require __DIR__ . '/../../views/users/edit.php';
    }

    public function create(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /kullanicilar');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        $canAdd = ($user['role'] ?? '') === 'super_admin' || ($user['role'] ?? '') === 'company_owner';
        if (!$canAdd) {
            $_SESSION['flash_error'] = 'Kullanıcı ekleme yetkiniz yok.';
            header('Location: /kullanicilar');
            exit;
        }
        $email = trim($_POST['email'] ?? '');
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        if ($email === '' || $firstName === '' || $lastName === '') {
            $_SESSION['flash_error'] = 'E-posta, ad ve soyad zorunludur.';
            header('Location: /kullanicilar');
            exit;
        }
        if (User::findByEmail($this->pdo, $email)) {
            $_SESSION['flash_error'] = 'Bu e-posta adresi zaten kayıtlı.';
            header('Location: /kullanicilar');
            exit;
        }
        $password = $_POST['password'] ?? '';
        if ($password === '') {
            $_SESSION['flash_error'] = 'Şifre girin.';
            header('Location: /kullanicilar');
            exit;
        }
        $role = $_POST['role'] ?? 'company_staff';
        $newCompanyId = null;
        if (($user['role'] ?? '') === 'super_admin') {
            $newCompanyId = trim($_POST['company_id'] ?? '') ?: null;
        } else {
            $newCompanyId = $companyId;
        }
        $data = [
            'email' => $email,
            'password' => $password,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => trim($_POST['phone'] ?? '') ?: null,
            'role' => $role,
            'company_id' => $newCompanyId,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];
        User::create($this->pdo, $data);
        $_SESSION['flash_success'] = 'Kullanıcı eklendi.';
        header('Location: /kullanicilar');
        exit;
    }

    public function update(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /kullanicilar');
            exit;
        }
        $id = trim($_POST['id'] ?? '');
        if (!$id) {
            header('Location: /kullanicilar');
            exit;
        }
        $profile = User::findOne($this->pdo, $id);
        if (!$profile) {
            $_SESSION['flash_error'] = 'Kullanıcı bulunamadı.';
            header('Location: /kullanicilar');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        $canManage = ($user['role'] ?? '') === 'super_admin' || ($user['role'] ?? '') === 'company_owner';
        if (!$canManage || ($companyId && ($profile['company_id'] ?? '') !== $companyId && ($profile['role'] ?? '') !== 'super_admin')) {
            $_SESSION['flash_error'] = 'Bu kullanıcıyı düzenleyemezsiniz.';
            header('Location: /kullanicilar');
            exit;
        }
        $email = trim($_POST['email'] ?? '');
        $existing = User::findByEmail($this->pdo, $email);
        if ($existing && ($existing['id'] ?? '') !== $id) {
            $_SESSION['flash_error'] = 'Bu e-posta adresi başka bir kullanıcıda kayıtlı.';
            header('Location: /kullanicilar/' . $id . '/duzenle');
            exit;
        }
        $data = [
            'first_name' => trim($_POST['first_name'] ?? ''),
            'last_name' => trim($_POST['last_name'] ?? ''),
            'email' => $email,
            'phone' => trim($_POST['phone'] ?? '') ?: null,
            'role' => $_POST['role'] ?? $profile['role'],
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];
        if (($user['role'] ?? '') === 'super_admin') {
            $data['company_id'] = trim($_POST['company_id'] ?? '') ?: null;
        }
        if (trim($_POST['password'] ?? '') !== '') {
            $data['password'] = $_POST['password'];
        }
        User::update($this->pdo, $id, $data);
        $_SESSION['flash_success'] = 'Kullanıcı güncellendi.';
        header('Location: /kullanicilar/' . $id);
        exit;
    }

    public function delete(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /kullanicilar');
            exit;
        }
        $id = trim($_POST['id'] ?? '');
        if (!$id) {
            header('Location: /kullanicilar');
            exit;
        }
        $profile = User::findOne($this->pdo, $id);
        if (!$profile) {
            $_SESSION['flash_error'] = 'Kullanıcı bulunamadı.';
            header('Location: /kullanicilar');
            exit;
        }
        $user = Auth::user();
        if ($id === ($user['id'] ?? '')) {
            $_SESSION['flash_error'] = 'Kendinizi silemezsiniz.';
            header('Location: /kullanicilar');
            exit;
        }
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        $canManage = ($user['role'] ?? '') === 'super_admin' || ($user['role'] ?? '') === 'company_owner';
        if (!$canManage || ($companyId && ($profile['company_id'] ?? '') !== $companyId)) {
            $_SESSION['flash_error'] = 'Bu kullanıcıyı silemezsiniz.';
            header('Location: /kullanicilar');
            exit;
        }
        User::remove($this->pdo, $id);
        $_SESSION['flash_success'] = 'Kullanıcı silindi.';
        header('Location: /kullanicilar');
        exit;
    }
}
