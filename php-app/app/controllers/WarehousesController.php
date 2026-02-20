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

    public function detail(array $params): void
    {
        Auth::requireStaff();
        $id = $params['id'] ?? '';
        if (!$id) {
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
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($user['role'] !== 'super_admin' && $warehouse['company_id'] !== $companyId) {
            $_SESSION['flash_error'] = 'Bu depoya erişim yetkiniz yok.';
            header('Location: /depolar');
            exit;
        }
        $rooms = Room::findAll($this->pdo, $id);
        $roomCustomerCounts = [];
        $roomCustomers = [];
        if (!empty($rooms)) {
            $roomIds = array_column($rooms, 'id');
            $placeholders = implode(',', array_fill(0, count($roomIds), '?'));
            $stmt = $this->pdo->prepare("SELECT room_id, COUNT(DISTINCT customer_id) AS customer_count FROM contracts WHERE room_id IN ($placeholders) AND deleted_at IS NULL AND is_active = 1 GROUP BY room_id");
            $stmt->execute($roomIds);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $roomCustomerCounts[$row['room_id']] = (int) $row['customer_count'];
            }
            $stmt2 = $this->pdo->prepare(
                "SELECT c.room_id, c.id AS contract_id, c.contract_number, c.customer_id, c.is_active,
                 cu.first_name AS customer_first_name, cu.last_name AS customer_last_name
                 FROM contracts c
                 INNER JOIN customers cu ON cu.id = c.customer_id AND cu.deleted_at IS NULL
                 WHERE c.room_id IN ($placeholders) AND c.deleted_at IS NULL AND c.is_active = 1
                 ORDER BY cu.last_name, cu.first_name"
            );
            $stmt2->execute($roomIds);
            while ($row = $stmt2->fetch(PDO::FETCH_ASSOC)) {
                $roomCustomers[$row['room_id']][] = $row;
            }
            $roomById = [];
            foreach ($rooms as $r) {
                $roomById[$r['id']] = $r;
            }
            $warehouseCustomers = [];
            foreach ($roomCustomers as $roomId => $list) {
                $room = $roomById[$roomId] ?? null;
                $roomNumber = $room['room_number'] ?? '-';
                foreach ($list as $cu) {
                    $cid = $cu['customer_id'];
                    if (!isset($warehouseCustomers[$cid])) {
                        $warehouseCustomers[$cid] = [
                            'customer_id'   => $cid,
                            'first_name'    => $cu['customer_first_name'] ?? '',
                            'last_name'     => $cu['customer_last_name'] ?? '',
                            'rooms'         => [],
                        ];
                    }
                    $warehouseCustomers[$cid]['rooms'][] = [
                        'room_id'         => $roomId,
                        'room_number'     => $roomNumber,
                        'contract_id'     => $cu['contract_id'] ?? null,
                        'contract_number' => $cu['contract_number'] ?? null,
                    ];
                }
            }
            $warehouseCustomers = array_values($warehouseCustomers);
            usort($warehouseCustomers, function ($a, $b) {
                return strcasecmp(trim($a['last_name'] . ' ' . $a['first_name']), trim($b['last_name'] . ' ' . $b['first_name']));
            });
        } else {
            $warehouseCustomers = [];
        }
        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        require __DIR__ . '/../../views/warehouses/detail.php';
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
            $_SESSION['flash_error'] = 'Kullanıcı bir şirkete bağlı değil. Depo eklemek için önce Ayarlar\'dan firma bilgilerini doldurun veya yöneticiye şirket ataması yaptırın.';
            header('Location: /depolar');
            exit;
        }
        if (!Company::findOne($this->pdo, $companyId)) {
            $_SESSION['flash_error'] = 'Şirket kaydı bulunamadı. Hesabınız eski bir şirkete bağlı olabilir; yönetici ile iletişime geçin veya çıkış yapıp tekrar giriş yapın.';
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
        $monthlyFee = trim($_POST['monthly_base_fee'] ?? '');
        $data = [
            'name'        => $name,
            'company_id'  => $companyId,
            'address'     => $address ?: null,
            'city'        => $city ?: null,
            'district'    => $district ?: null,
            'total_floors'=> isset($_POST['total_floors']) ? (int) $_POST['total_floors'] : null,
            'description'=> trim($_POST['description'] ?? '') ?: null,
            'is_active'   => 1,
            'monthly_base_fee' => $monthlyFee !== '' ? (float) str_replace(',', '.', $monthlyFee) : null,
        ];
        try {
            Warehouse::create($this->pdo, $data);
            $actorName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
            Notification::createForCompany($this->pdo, $companyId, 'warehouse', 'Depo eklendi', $name . ' depo olarak eklendi.', ['actor_name' => $actorName]);
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
        $monthlyFee = trim($_POST['monthly_base_fee'] ?? '');
        $data = [
            'name'        => trim($_POST['name'] ?? $warehouse['name']),
            'address'     => trim($_POST['address'] ?? '') ?: null,
            'city'        => trim($_POST['city'] ?? '') ?: null,
            'district'    => trim($_POST['district'] ?? '') ?: null,
            'total_floors'=> isset($_POST['total_floors']) && $_POST['total_floors'] !== '' ? (int) $_POST['total_floors'] : null,
            'description' => trim($_POST['description'] ?? '') ?: null,
            'is_active'   => isset($_POST['is_active']) ? 1 : 0,
            'monthly_base_fee' => $monthlyFee !== '' ? (float) str_replace(',', '.', $monthlyFee) : null,
        ];
        Warehouse::update($this->pdo, $id, $data);
        $actorName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        Notification::createForCompany($this->pdo, $warehouse['company_id'] ?? null, 'warehouse', 'Depo güncellendi', ($data['name'] ?? $warehouse['name']) . ' depo bilgileri güncellendi.', ['actor_name' => $actorName]);
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
            $actorName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
            Notification::createForCompany($this->pdo, $warehouse['company_id'] ?? null, 'warehouse', 'Depo silindi', ($warehouse['name'] ?? '') . ' depo silindi.', ['actor_name' => $actorName]);
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
