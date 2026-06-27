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
        // Super admin her zaman tüm personeli görsün (şirket atanmamış kullanıcılar da dahil)
        $search = isset($_GET['q']) ? trim($_GET['q']) : null;
        $search = $search !== '' ? $search : null;
        $roleFilter = isset($_GET['role']) && $_GET['role'] !== '' ? trim($_GET['role']) : null;
        $activeFilter = isset($_GET['is_active']) && in_array($_GET['is_active'], ['0', '1'], true) ? $_GET['is_active'] : null;
        if (($user['role'] ?? '') === 'super_admin') {
            $staff = User::findStaff($this->pdo, null, $search, $roleFilter, $activeFilter);
        } else {
            $staff = User::findStaff($this->pdo, $companyId, $search, $roleFilter, $activeFilter);
        }
        $companies = [];
        if (($user['role'] ?? '') === 'super_admin') {
            $stmt = $this->pdo->query('SELECT id, name FROM companies WHERE deleted_at IS NULL ORDER BY name');
            $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        $canManageUsers = ($user['role'] ?? '') === 'super_admin' || ($user['role'] ?? '') === 'company_owner';
        $roleLabels = RolePermissions::roleLabels();
        $formRoleOptions = RolePermissions::formRoleOptions(($user['role'] ?? '') === 'super_admin');
        ['success' => $flashSuccess, 'error' => $flashError] = Auth::consumeFlash();
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
            Auth::setSession('flash_error', 'Kullanıcı bulunamadı.');
            header('Location: /kullanicilar');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if (($user['role'] ?? '') !== 'super_admin' && $companyId && ($profile['company_id'] ?? '') !== $companyId && ($profile['role'] ?? '') !== 'super_admin') {
            Auth::setSession('flash_error', 'Bu kullanıcıya erişim yetkiniz yok.');
            header('Location: /kullanicilar');
            exit;
        }
        $roleLabels = RolePermissions::roleLabels();
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
            Auth::setSession('flash_error', 'Kullanıcı bulunamadı.');
            header('Location: /kullanicilar');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        $canManage = ($user['role'] ?? '') === 'super_admin' || ($user['role'] ?? '') === 'company_owner';
        if (!$canManage) {
            Auth::setSession('flash_error', 'Bu kullanıcıyı düzenleyemezsiniz.');
            header('Location: /kullanicilar');
            exit;
        }
        if (($user['role'] ?? '') !== 'super_admin' && $companyId && ($profile['company_id'] ?? '') !== $companyId && ($profile['role'] ?? '') !== 'super_admin') {
            Auth::setSession('flash_error', 'Bu kullanıcıyı düzenleyemezsiniz.');
            header('Location: /kullanicilar');
            exit;
        }
        $companies = [];
        if (($user['role'] ?? '') === 'super_admin') {
            $stmt = $this->pdo->query('SELECT id, name FROM companies WHERE deleted_at IS NULL ORDER BY name');
            $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        $roleLabels = RolePermissions::roleLabels();
        $currentUserIsSuperAdmin = ($user['role'] ?? '') === 'super_admin';
        $formRoleOptions = RolePermissions::formRoleOptions($currentUserIsSuperAdmin || ($profile['role'] ?? '') === 'super_admin');
        ['success' => $flashSuccess, 'error' => $flashError] = Auth::consumeFlash();
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
            Auth::setSession('flash_error', 'Kullanıcı ekleme yetkiniz yok.');
            header('Location: /kullanicilar');
            exit;
        }
        $email = trim($_POST['email'] ?? '');
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        if ($email === '' || $firstName === '' || $lastName === '') {
            Auth::setSession('flash_error', 'E-posta, ad ve soyad zorunludur.');
            header('Location: /kullanicilar');
            exit;
        }
        if (User::findByEmail($this->pdo, $email)) {
            Auth::setSession('flash_error', 'Bu e-posta adresi zaten kayıtlı.');
            header('Location: /kullanicilar');
            exit;
        }
        $password = trim($_POST['password'] ?? '');
        $autoPassword = false;
        if ($password === '') {
            $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789!@#$%';
            $password = '';
            for ($i = 0; $i < 12; $i++) {
                $password .= $chars[random_int(0, strlen($chars) - 1)];
            }
            $autoPassword = true;
        }
        $role = $_POST['role'] ?? 'company_staff';
        if (!in_array($role, array_keys(RolePermissions::formRoleOptions(($user['role'] ?? '') === 'super_admin')), true)) {
            $role = 'company_staff';
        }
        $newCompanyId = null;
        if (($user['role'] ?? '') === 'super_admin') {
            $newCompanyId = trim($_POST['company_id'] ?? '') ?: null;
            if (!$newCompanyId) {
                Auth::setSession('flash_error', 'Kullanıcı eklerken şirket seçmelisiniz.');
                header('Location: /kullanicilar');
                exit;
            }
            if (!Company::findOne($this->pdo, $newCompanyId)) {
                Auth::setSession('flash_error', 'Seçilen şirket bulunamadı.');
                header('Location: /kullanicilar');
                exit;
            }
        } else {
            $newCompanyId = $companyId;
            if (!$newCompanyId) {
                Auth::setSession('flash_error', 'Şirket bilginiz tanımlı değil; kullanıcı eklenemedi.');
                header('Location: /kullanicilar');
                exit;
            }
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
        try {
            User::create($this->pdo, $data);
        } catch (PDOException $e) {
            $msg = ($e->getCode() === '23000' || str_contains($e->getMessage(), 'Duplicate'))
                ? 'Bu e-posta adresi zaten kayıtlı.'
                : 'Kullanıcı eklenemedi. Lütfen tekrar deneyin.';
            error_log('User create failed: ' . $e->getMessage());
            Auth::setSession('flash_error', $msg);
            header('Location: /kullanicilar');
            exit;
        } catch (Throwable $e) {
            error_log('User create failed: ' . $e->getMessage());
            Auth::setSession('flash_error', 'Kullanıcı eklenemedi. Lütfen tekrar deneyin.');
            header('Location: /kullanicilar');
            exit;
        }
        $fullName = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));
        $actorName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        try {
            Notification::createForCompany(
                $this->pdo,
                $newCompanyId,
                'user',
                'Kullanıcı eklendi',
                $fullName . ' kullanıcı olarak eklendi.',
                ['actor_name' => $actorName]
            );
        } catch (Throwable $e) {
            // Bildirim hatası kullanıcı eklemeyi bozmasın
        }
        $successMsg = $autoPassword
            ? 'Kullanıcı eklendi. Otomatik şifre: ' . $password
            : 'Kullanıcı eklendi.';
        Auth::setSession('flash_success', $successMsg);
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
            Auth::setSession('flash_error', 'Kullanıcı bulunamadı.');
            header('Location: /kullanicilar');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        $canManage = ($user['role'] ?? '') === 'super_admin' || ($user['role'] ?? '') === 'company_owner';
        if (!$canManage) {
            Auth::setSession('flash_error', 'Bu kullanıcıyı düzenleyemezsiniz.');
            header('Location: /kullanicilar');
            exit;
        }
        if (($user['role'] ?? '') !== 'super_admin' && $companyId && ($profile['company_id'] ?? '') !== $companyId && ($profile['role'] ?? '') !== 'super_admin') {
            Auth::setSession('flash_error', 'Bu kullanıcıyı düzenleyemezsiniz.');
            header('Location: /kullanicilar');
            exit;
        }
        $email = trim($_POST['email'] ?? '');
        $existing = User::findByEmail($this->pdo, $email);
        if ($existing && ($existing['id'] ?? '') !== $id) {
            Auth::setSession('flash_error', 'Bu e-posta adresi başka bir kullanıcıda kayıtlı.');
            header('Location: /kullanicilar/' . $id . '/duzenle');
            exit;
        }
        $allowedRoles = array_keys(RolePermissions::formRoleOptions(
            ($user['role'] ?? '') === 'super_admin' || ($profile['role'] ?? '') === 'super_admin'
        ));
        $submittedRole = $_POST['role'] ?? $profile['role'];
        if (!in_array($submittedRole, $allowedRoles, true)) {
            $submittedRole = $profile['role'];
        }
        $data = [
            'first_name' => trim($_POST['first_name'] ?? ''),
            'last_name' => trim($_POST['last_name'] ?? ''),
            'email' => $email,
            'phone' => trim($_POST['phone'] ?? '') ?: null,
            'role' => $submittedRole,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];
        if (($user['role'] ?? '') === 'super_admin') {
            $data['company_id'] = trim($_POST['company_id'] ?? '') ?: null;
        }
        if (trim($_POST['password'] ?? '') !== '') {
            $data['password'] = $_POST['password'];
        }
        User::update($this->pdo, $id, $data);
        // Güncellenen kullanıcı şu an giriş yapmışsa oturumu güncelle (e-posta, rol vb. hemen yansısın)
        if (($user['id'] ?? '') === $id) {
            $updated = User::findOne($this->pdo, $id);
            if ($updated) {
                $sessionUser = [
                    'id' => $updated['id'],
                    'email' => $updated['email'],
                    'first_name' => $updated['first_name'],
                    'last_name' => $updated['last_name'],
                    'role' => $updated['role'],
                    'company_id' => $updated['company_id'] ?? null,
                ];
                Auth::refreshUser($sessionUser);
            }
        }
        $fullName = trim(($data['first_name'] ?? $profile['first_name'] ?? '') . ' ' . ($data['last_name'] ?? $profile['last_name'] ?? ''));
        $actorName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        Notification::createForCompany($this->pdo, $profile['company_id'] ?? null, 'user', 'Kullanıcı güncellendi', $fullName . ' kullanıcı bilgileri güncellendi.', ['actor_name' => $actorName]);
        Auth::setSession('flash_success', 'Kullanıcı güncellendi.');
        header('Location: /kullanicilar/' . $id);
        exit;
    }

    public function changePassword(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /kullanicilar');
            exit;
        }
        $id = trim($_POST['id'] ?? '');
        $password = trim($_POST['password'] ?? '');
        if (!$id || !$password) {
            Auth::setSession('flash_error', 'Kullanıcı ve şifre gerekli.');
            header('Location: /kullanicilar');
            exit;
        }
        $profile = User::findOne($this->pdo, $id);
        if (!$profile) {
            Auth::setSession('flash_error', 'Kullanıcı bulunamadı.');
            header('Location: /kullanicilar');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        $canManage = ($user['role'] ?? '') === 'super_admin' || ($user['role'] ?? '') === 'company_owner';
        if (!$canManage) {
            Auth::setSession('flash_error', 'Bu kullanıcının şifresini değiştirme yetkiniz yok.');
            header('Location: /kullanicilar');
            exit;
        }
        if (($user['role'] ?? '') !== 'super_admin' && $companyId && ($profile['company_id'] ?? '') !== $companyId && ($profile['role'] ?? '') !== 'super_admin') {
            Auth::setSession('flash_error', 'Bu kullanıcının şifresini değiştirme yetkiniz yok.');
            header('Location: /kullanicilar');
            exit;
        }
        User::update($this->pdo, $id, ['password' => $password]);
        Auth::setSession('flash_success', 'Şifre güncellendi.');
        header('Location: /kullanicilar');
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
            Auth::setSession('flash_error', 'Kullanıcı bulunamadı.');
            header('Location: /kullanicilar');
            exit;
        }
        $user = Auth::user();
        if ($id === ($user['id'] ?? '')) {
            Auth::setSession('flash_error', 'Kendinizi silemezsiniz.');
            header('Location: /kullanicilar');
            exit;
        }
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        $canManage = ($user['role'] ?? '') === 'super_admin' || ($user['role'] ?? '') === 'company_owner';
        if (!$canManage) {
            Auth::setSession('flash_error', 'Bu kullanıcıyı silemezsiniz.');
            header('Location: /kullanicilar');
            exit;
        }
        if (($user['role'] ?? '') !== 'super_admin' && $companyId && ($profile['company_id'] ?? '') !== $companyId) {
            Auth::setSession('flash_error', 'Bu kullanıcıyı silemezsiniz.');
            header('Location: /kullanicilar');
            exit;
        }
        $fullName = trim(($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? ''));
        User::remove($this->pdo, $id);
        $actorName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        Notification::createForCompany($this->pdo, $profile['company_id'] ?? null, 'user', 'Kullanıcı silindi', $fullName . ' kullanıcı silindi.', ['actor_name' => $actorName]);
        Auth::setSession('flash_success', 'Kullanıcı silindi.');
        header('Location: /kullanicilar');
        exit;
    }
}
