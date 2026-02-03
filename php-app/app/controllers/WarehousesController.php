<?php
class WarehousesController
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
        if ($companyId) {
            $warehouses = Warehouse::findAll($this->pdo, $companyId);
        } elseif (($user['role'] ?? '') === 'super_admin') {
            $warehouses = Warehouse::findAll($this->pdo, null);
        } else {
            $_SESSION['flash_error'] = 'Kullanıcı bir şirkete bağlı değil.';
            header('Location: /genel-bakis');
            exit;
        }
        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        require __DIR__ . '/../../views/warehouses/index.php';
    }

    public function create(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /depolar');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if (!$companyId) {
            $_SESSION['flash_error'] = 'Kullanıcı bir şirkete bağlı değil.';
            header('Location: /depolar');
            exit;
        }
        $name = trim($_POST['name'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $district = trim($_POST['district'] ?? '');
        if ($name === '') {
            $_SESSION['flash_error'] = 'Depo adı gerekli.';
            header('Location: /depolar');
            exit;
        }
        $data = [
            'name'        => $name,
            'company_id'  => $companyId,
            'address'     => $address ?: null,
            'city'        => $city ?: null,
            'district'    => $district ?: null,
            'total_floors'=> isset($_POST['total_floors']) ? (int) $_POST['total_floors'] : null,
            'description'=> trim($_POST['description'] ?? '') ?: null,
            'is_active'   => 1,
        ];
        try {
            Warehouse::create($this->pdo, $data);
            Notification::createForCompany($this->pdo, $companyId, 'warehouse', 'Depo eklendi', $name . ' depo olarak eklendi.');
            $_SESSION['flash_success'] = 'Depo eklendi.';
        } catch (Exception $e) {
            $_SESSION['flash_error'] = 'Depo eklenemedi: ' . $e->getMessage();
        }
        header('Location: /depolar');
        exit;
    }

    public function update(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /depolar');
            exit;
        }
        $id = $_POST['id'] ?? '';
        if ($id === '') {
            $_SESSION['flash_error'] = 'Depo bulunamadı.';
            header('Location: /depolar');
            exit;
        }
        $warehouse = Warehouse::findOne($this->pdo, $id);
        if (!$warehouse) {
            $_SESSION['flash_error'] = 'Depo bulunamadı.';
            header('Location: /depolar');
            exit;
        }
        $user = Auth::user();
        if ($user['role'] !== 'super_admin') {
            $companyId = Company::getCompanyIdForUser($this->pdo, $user);
            if (!$companyId || $warehouse['company_id'] !== $companyId) {
                $_SESSION['flash_error'] = 'Bu depoya erişim yetkiniz yok.';
                header('Location: /depolar');
                exit;
            }
        }
        $data = [
            'name'        => trim($_POST['name'] ?? $warehouse['name']),
            'address'     => trim($_POST['address'] ?? '') ?: null,
            'city'        => trim($_POST['city'] ?? '') ?: null,
            'district'    => trim($_POST['district'] ?? '') ?: null,
            'total_floors'=> isset($_POST['total_floors']) && $_POST['total_floors'] !== '' ? (int) $_POST['total_floors'] : null,
            'description' => trim($_POST['description'] ?? '') ?: null,
            'is_active'   => isset($_POST['is_active']) ? 1 : 0,
        ];
        Warehouse::update($this->pdo, $id, $data);
        Notification::createForCompany($this->pdo, $warehouse['company_id'] ?? null, 'warehouse', 'Depo güncellendi', ($data['name'] ?? $warehouse['name']) . ' depo bilgileri güncellendi.');
        $_SESSION['flash_success'] = 'Depo güncellendi.';
        header('Location: /depolar');
        exit;
    }

    public function delete(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /depolar');
            exit;
        }
        $ids = isset($_POST['ids']) && is_array($_POST['ids']) ? array_filter(array_map('trim', $_POST['ids'])) : [];
        if (empty($ids)) {
            $id = trim($_POST['id'] ?? '');
            if ($id !== '') $ids = [$id];
        }
        if (empty($ids)) {
            $_SESSION['flash_error'] = 'Depo seçilmedi.';
            header('Location: /depolar');
            exit;
        }
        $user = Auth::user();
        $companyId = $user['role'] !== 'super_admin' ? Company::getCompanyIdForUser($this->pdo, $user) : null;
        $deleted = 0;
        $errors = [];
        foreach ($ids as $id) {
            $warehouse = Warehouse::findOne($this->pdo, $id);
            if (!$warehouse) continue;
            if ($companyId && $warehouse['company_id'] !== $companyId) continue;
            if (Warehouse::hasActiveContracts($this->pdo, $id)) {
                $errors[] = $warehouse['name'] ?? $id;
                continue;
            }
            Warehouse::remove($this->pdo, $id);
            Notification::createForCompany($this->pdo, $warehouse['company_id'] ?? null, 'warehouse', 'Depo silindi', ($warehouse['name'] ?? '') . ' depo silindi.');
            $deleted++;
        }
        if (!empty($errors)) {
            $_SESSION['flash_error'] = 'Bazı depolar silinemedi (aktif sözleşme var): ' . implode(', ', $errors);
        }
        if ($deleted > 0) {
            $_SESSION['flash_success'] = $deleted === 1 ? 'Depo silindi.' : $deleted . ' depo silindi.';
        } elseif (empty($errors)) {
            $_SESSION['flash_error'] = 'Silinecek depo bulunamadı veya yetkiniz yok.';
        }
        header('Location: /depolar');
        exit;
    }
}
