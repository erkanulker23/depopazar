<?php
class RoomsController
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
        $filterWarehouseId = isset($_GET['warehouse_id']) && $_GET['warehouse_id'] !== '' ? trim($_GET['warehouse_id']) : null;
        if ($companyId) {
            $warehouses = Warehouse::findAll($this->pdo, $companyId);
            $rooms = Room::findAll($this->pdo, $filterWarehouseId);
            $rooms = array_filter($rooms, fn($r) => ($r['company_id'] ?? '') === $companyId);
        } elseif (($user['role'] ?? '') === 'super_admin') {
            $warehouses = Warehouse::findAll($this->pdo, null);
            $rooms = Room::findAll($this->pdo, $filterWarehouseId);
        } else {
            $warehouses = [];
            $rooms = [];
        }
        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        require __DIR__ . '/../../views/rooms/index.php';
    }

    public function detail(array $params): void
    {
        Auth::requireStaff();
        $id = $params['id'] ?? '';
        $room = Room::findOne($this->pdo, $id);
        if (!$room) {
            $_SESSION['flash_error'] = 'Oda bulunamadı.';
            header('Location: /odalar');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($user['role'] !== 'super_admin' && $room['company_id'] !== $companyId) {
            $_SESSION['flash_error'] = 'Bu odaya erişim yetkiniz yok.';
            header('Location: /odalar');
            exit;
        }
        require __DIR__ . '/../../views/rooms/detail.php';
    }

    public function create(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /odalar');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if (!$companyId) {
            $_SESSION['flash_error'] = 'Kullanıcı bir şirkete bağlı değil.';
            header('Location: /odalar');
            exit;
        }
        $warehouseId = trim($_POST['warehouse_id'] ?? '');
        $warehouse = $warehouseId ? Warehouse::findOne($this->pdo, $warehouseId) : null;
        if (!$warehouse || $warehouse['company_id'] !== $companyId) {
            $_SESSION['flash_error'] = 'Geçerli bir depo seçin.';
            header('Location: /odalar');
            exit;
        }
        $roomNumber = trim($_POST['room_number'] ?? '');
        $areaM2 = isset($_POST['area_m2']) ? (float) str_replace(',', '.', $_POST['area_m2']) : 0;
        $monthlyPrice = isset($_POST['monthly_price']) ? (float) str_replace(',', '.', $_POST['monthly_price']) : 0;
        if ($roomNumber === '' || $areaM2 <= 0 || $monthlyPrice < 0) {
            $_SESSION['flash_error'] = 'Oda numarası, alan (m²) ve aylık fiyat gerekli.';
            header('Location: /odalar');
            exit;
        }
        $data = [
            'room_number'   => $roomNumber,
            'warehouse_id'  => $warehouseId,
            'area_m2'       => $areaM2,
            'monthly_price' => $monthlyPrice,
            'status'        => $_POST['status'] ?? 'empty',
            'floor'         => trim($_POST['floor'] ?? '') ?: null,
            'block'         => trim($_POST['block'] ?? '') ?: null,
            'corridor'      => trim($_POST['corridor'] ?? '') ?: null,
            'description'   => trim($_POST['description'] ?? '') ?: null,
            'notes'         => trim($_POST['notes'] ?? '') ?: null,
        ];
        try {
            Room::create($this->pdo, $data);
            Notification::createForCompany($this->pdo, $companyId, 'room', 'Oda eklendi', $roomNumber . ' numaralı oda ' . ($warehouse['name'] ?? '') . ' deposuna eklendi.');
            $_SESSION['flash_success'] = 'Oda eklendi.';
        } catch (Exception $e) {
            $_SESSION['flash_error'] = 'Oda eklenemedi: ' . $e->getMessage();
        }
        header('Location: /odalar');
        exit;
    }

    public function update(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /odalar');
            exit;
        }
        $id = $_POST['id'] ?? '';
        $room = $id ? Room::findOne($this->pdo, $id) : null;
        if (!$room) {
            $_SESSION['flash_error'] = 'Oda bulunamadı.';
            header('Location: /odalar');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($user['role'] !== 'super_admin' && $room['company_id'] !== $companyId) {
            $_SESSION['flash_error'] = 'Bu odaya erişim yetkiniz yok.';
            header('Location: /odalar');
            exit;
        }
        $warehouseId = trim($_POST['warehouse_id'] ?? '');
        if (!$warehouseId) {
            $_SESSION['flash_error'] = 'Depo seçimi zorunludur.';
            header('Location: /odalar');
            exit;
        }
        $warehouse = Warehouse::findOne($this->pdo, $warehouseId);
        if (!$warehouse || ($user['role'] !== 'super_admin' && ($warehouse['company_id'] ?? '') !== $companyId)) {
            $_SESSION['flash_error'] = 'Geçersiz depo.';
            header('Location: /odalar');
            exit;
        }
        $data = [
            'warehouse_id'  => $warehouseId,
            'room_number'   => trim($_POST['room_number'] ?? $room['room_number']),
            'area_m2'       => isset($_POST['area_m2']) ? (float) str_replace(',', '.', $_POST['area_m2']) : $room['area_m2'],
            'monthly_price' => isset($_POST['monthly_price']) ? (float) str_replace(',', '.', $_POST['monthly_price']) : $room['monthly_price'],
            'status'        => $_POST['status'] ?? $room['status'],
            'floor'         => trim($_POST['floor'] ?? '') ?: null,
            'block'         => trim($_POST['block'] ?? '') ?: null,
            'corridor'      => trim($_POST['corridor'] ?? '') ?: null,
            'description'   => trim($_POST['description'] ?? '') ?: null,
            'notes'         => trim($_POST['notes'] ?? '') ?: null,
        ];
        Room::update($this->pdo, $id, $data);
        Notification::createForCompany($this->pdo, $room['company_id'] ?? null, 'room', 'Oda güncellendi', ($data['room_number'] ?? $room['room_number']) . ' oda bilgileri güncellendi.');
        $_SESSION['flash_success'] = 'Oda güncellendi.';
        header('Location: /odalar');
        exit;
    }

    public function delete(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /odalar');
            exit;
        }
        $ids = isset($_POST['ids']) && is_array($_POST['ids']) ? array_filter(array_map('trim', $_POST['ids'])) : [];
        if (empty($ids)) {
            $id = trim($_POST['id'] ?? '');
            if ($id !== '') $ids = [$id];
        }
        if (empty($ids)) {
            $_SESSION['flash_error'] = 'Oda seçilmedi.';
            header('Location: /odalar');
            exit;
        }
        $user = Auth::user();
        $companyId = $user['role'] !== 'super_admin' ? Company::getCompanyIdForUser($this->pdo, $user) : null;
        $deleted = 0;
        $errors = [];
        foreach ($ids as $id) {
            $room = Room::findOne($this->pdo, $id);
            if (!$room) continue;
            if ($companyId && $room['company_id'] !== $companyId) continue;
            if (Room::hasActiveContract($this->pdo, $id)) {
                $errors[] = $room['room_number'] ?? $id;
                continue;
            }
            Room::remove($this->pdo, $id);
            Notification::createForCompany($this->pdo, $room['company_id'] ?? null, 'room', 'Oda silindi', ($room['room_number'] ?? '') . ' numaralı oda silindi.');
            $deleted++;
        }
        if (!empty($errors)) {
            $_SESSION['flash_error'] = 'Bazı odalar silinemedi (aktif sözleşme var): ' . implode(', ', $errors);
        }
        if ($deleted > 0) {
            $_SESSION['flash_success'] = $deleted === 1 ? 'Oda silindi.' : $deleted . ' oda silindi.';
        } elseif (empty($errors)) {
            $_SESSION['flash_error'] = 'Silinecek oda bulunamadı veya yetkiniz yok.';
        }
        header('Location: /odalar');
        exit;
    }
}
