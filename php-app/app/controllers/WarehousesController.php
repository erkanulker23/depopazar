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
        $search = isset($_GET['q']) ? trim($_GET['q']) : null;
        $search = $search !== '' ? $search : null;
        if ($companyId) {
            $warehouses = Warehouse::findAll($this->pdo, $companyId, $search);
        } elseif (($user['role'] ?? '') === 'super_admin') {
            $warehouses = Warehouse::findAll($this->pdo, null, $search);
        } else {
            Auth::setSession('flash_error', 'Kullanıcı bir şirkete bağlı değil.');
            header('Location: /genel-bakis');
            exit;
        }
        ['success' => $flashSuccess, 'error' => $flashError] = Auth::consumeFlash();
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
            Auth::setSession('flash_error', 'Depo bulunamadı.');
            header('Location: /depolar');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($user['role'] !== 'super_admin' && $warehouse['company_id'] !== $companyId) {
            Auth::setSession('flash_error', 'Bu depoya erişim yetkiniz yok.');
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
        ['success' => $flashSuccess, 'error' => $flashError] = Auth::consumeFlash();
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
            Auth::setSession('flash_error', 'Kullanıcı bir şirkete bağlı değil. Depo eklemek için önce Ayarlar\'dan firma bilgilerini doldurun veya yöneticiye şirket ataması yaptırın.');
            header('Location: /depolar');
            exit;
        }
        if (!Company::findOne($this->pdo, $companyId)) {
            Auth::setSession('flash_error', 'Şirket kaydı bulunamadı. Hesabınız eski bir şirkete bağlı olabilir; yönetici ile iletişime geçin veya çıkış yapıp tekrar giriş yapın.');
            header('Location: /depolar');
            exit;
        }
        $name = trim($_POST['name'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $district = trim($_POST['district'] ?? '');
        if ($name === '') {
            Auth::setSession('flash_error', 'Depo adı gerekli.');
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
            Auth::setSession('flash_success', 'Depo eklendi.');
        } catch (Exception $e) {
            Auth::setSession('flash_error', 'Depo eklenemedi: ' . $e->getMessage());
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
            Auth::setSession('flash_error', 'Depo bulunamadı.');
            header('Location: /depolar');
            exit;
        }
        $warehouse = Warehouse::findOne($this->pdo, $id);
        if (!$warehouse) {
            Auth::setSession('flash_error', 'Depo bulunamadı.');
            header('Location: /depolar');
            exit;
        }
        $user = Auth::user();
        if ($user['role'] !== 'super_admin') {
            $companyId = Company::getCompanyIdForUser($this->pdo, $user);
            if (!$companyId || $warehouse['company_id'] !== $companyId) {
                Auth::setSession('flash_error', 'Bu depoya erişim yetkiniz yok.');
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
        Auth::setSession('flash_success', 'Depo güncellendi.');
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
            Auth::setSession('flash_error', 'Depo seçilmedi.');
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
            Auth::setSession('flash_error', 'Bazı depolar silinemedi (aktif sözleşme var): ' . implode(', ', $errors));
        }
        if ($deleted > 0) {
            Auth::setSession('flash_success', $deleted === 1 ? 'Depo silindi.' : $deleted . ' depo silindi.');
        } elseif (empty($errors)) {
            Auth::setSession('flash_error', 'Silinecek depo bulunamadı veya yetkiniz yok.');
        }
        header('Location: /depolar');
        exit;
    }

    /** Excel (CSV) dışa aktar – mevcut depoları indir */
    public function exportCsv(): void
    {
        Auth::requireStaff();
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId) {
            $warehouses = Warehouse::findAll($this->pdo, $companyId);
        } elseif (($user['role'] ?? '') === 'super_admin') {
            $warehouses = Warehouse::findAll($this->pdo, null);
        } else {
            $warehouses = [];
        }
        $filename = 'depolar_' . date('Y-m-d_His') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        $out = fopen('php://output', 'wb');
        fprintf($out, "\xEF\xBB\xBF");
        $headers = ['Depo Adı', 'Adres', 'İl', 'İlçe', 'Kat Sayısı', 'Açıklama', 'Aktif', 'Aylık Baz Ücret'];
        fputcsv($out, $headers, ';');
        foreach ($warehouses as $w) {
            fputcsv($out, [
                $w['name'] ?? '',
                $w['address'] ?? '',
                $w['city'] ?? '',
                $w['district'] ?? '',
                $w['total_floors'] ?? '',
                $w['description'] ?? '',
                !empty($w['is_active']) ? 'Evet' : 'Hayır',
                $w['monthly_base_fee'] !== null && $w['monthly_base_fee'] !== '' ? str_replace('.', ',', (string)$w['monthly_base_fee']) : '',
            ], ';');
        }
        fclose($out);
        exit;
    }

    /** Excel (CSV) şablonu indir */
    public function downloadTemplate(): void
    {
        Auth::requireStaff();
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="depo_sablonu.csv"');
        $out = fopen('php://output', 'wb');
        fprintf($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Depo Adı', 'Adres', 'İl', 'İlçe', 'Kat Sayısı', 'Açıklama', 'Aktif', 'Aylık Baz Ücret'], ';');
        fputcsv($out, ['KARTAL DEPO', 'Örnek Mah. Depo Sok. 1', 'İstanbul', 'Kartal', '2', '', 'Evet', '5000'], ';');
        fclose($out);
        exit;
    }

    /** Excel (CSV) içe aktar – form */
    public function importForm(): void
    {
        Auth::requireStaff();
        ['success' => $flashSuccess, 'error' => $flashError] = Auth::consumeFlash();
        $currentPage = 'depolar';
        require __DIR__ . '/../../views/warehouses/import.php';
    }

    /** Excel (CSV) içe aktar – işle */
    public function importCsv(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /depolar');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if (!$companyId) {
            Auth::setSession('flash_error', 'Şirket bilgisi bulunamadı.');
            header('Location: /depolar/excel-ice-aktar');
            exit;
        }
        $file = $_FILES['csv_file'] ?? null;
        if (!$file || ($file['error'] ?? 0) !== UPLOAD_ERR_OK) {
            Auth::setSession('flash_error', 'Lütfen bir CSV dosyası seçin.');
            header('Location: /depolar/excel-ice-aktar');
            exit;
        }
        $handle = @fopen($file['tmp_name'], 'rb');
        if (!$handle) {
            Auth::setSession('flash_error', 'Dosya okunamadı.');
            header('Location: /depolar/excel-ice-aktar');
            exit;
        }
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") rewind($handle);
        $first = fgetcsv($handle, 0, ';');
        if ($first === false) $first = fgetcsv($handle, 0, ',');
        $delimiter = (count($first ?? []) > 1) ? ';' : ',';
        if ($delimiter === ',') {
            rewind($handle);
            if ($bom !== "\xEF\xBB\xBF") rewind($handle);
            fgetcsv($handle, 0, ',');
        }
        $added = 0;
        $errors = [];
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $row = array_map('trim', $row);
            if (count($row) < 1) continue;
            $name = $row[0] ?? '';
            if ($name === '') continue;
            if (stripos($name, 'depo') !== false && stripos($name, 'adı') !== false) continue; // başlık
            $address = $row[1] ?? null;
            $city = $row[2] ?? null;
            $district = $row[3] ?? null;
            $totalFloors = isset($row[4]) && $row[4] !== '' ? (int)$row[4] : null;
            $description = $row[5] ?? null;
            $isActive = 1;
            if (isset($row[6]) && $row[6] !== '') {
                $v = mb_strtolower($row[6]);
                if (strpos($v, 'hayır') !== false || $v === '0' || $v === 'pasif') $isActive = 0;
            }
            $monthlyBaseFee = isset($row[7]) && $row[7] !== '' ? (float) str_replace(',', '.', $row[7]) : null;
            try {
                Warehouse::create($this->pdo, [
                    'name' => $name,
                    'company_id' => $companyId,
                    'address' => $address,
                    'city' => $city,
                    'district' => $district,
                    'total_floors' => $totalFloors,
                    'description' => $description,
                    'is_active' => $isActive,
                    'monthly_base_fee' => $monthlyBaseFee,
                ]);
                $added++;
            } catch (Throwable $e) {
                $errors[] = $name . ': ' . $e->getMessage();
            }
        }
        fclose($handle);
        if ($added > 0) Auth::setSession('flash_success', $added . ' depo eklendi.');
        if (!empty($errors)) Auth::setSession('flash_error', implode(' ', array_slice($errors, 0, 3)) . (count($errors) > 3 ? ' …' : ''));
        header('Location: /depolar/excel-ice-aktar');
        exit;
    }
}
