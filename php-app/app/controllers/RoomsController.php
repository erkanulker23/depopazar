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
        $search = isset($_GET['q']) ? trim($_GET['q']) : null;
        $search = $search !== '' ? $search : null;
        $statusFilter = isset($_GET['status']) && in_array($_GET['status'], ['empty', 'occupied', 'reserved', 'locked'], true) ? $_GET['status'] : null;
        $hasContract = isset($_GET['has_contract']) && in_array($_GET['has_contract'], ['yes', 'no'], true) ? $_GET['has_contract'] : null;
        if ($companyId) {
            $warehouses = Warehouse::findAll($this->pdo, $companyId);
            $rooms = Room::findAll($this->pdo, $filterWarehouseId, $search, $statusFilter, $hasContract);
            $rooms = array_filter($rooms, fn($r) => ($r['company_id'] ?? '') === $companyId);
        } elseif (($user['role'] ?? '') === 'super_admin') {
            $warehouses = Warehouse::findAll($this->pdo, null);
            $rooms = Room::findAll($this->pdo, $filterWarehouseId, $search, $statusFilter, $hasContract);
        } else {
            $warehouses = [];
            $rooms = [];
        }
        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        // Oda başına aktif sözleşme sayısı (silinmemiş müşteriye ait, detay sayfasıyla aynı mantık)
        $activeContractCountByRoom = [];
        if (!empty($rooms)) {
            $roomIds = array_column($rooms, 'id');
            $placeholders = implode(',', array_fill(0, count($roomIds), '?'));
            $stmt = $this->pdo->prepare(
                "SELECT c.room_id, COUNT(*) AS cnt FROM contracts c
                 INNER JOIN customers cu ON cu.id = c.customer_id AND cu.deleted_at IS NULL
                 WHERE c.room_id IN ($placeholders) AND c.deleted_at IS NULL AND c.is_active = 1
                 GROUP BY c.room_id"
            );
            $stmt->execute($roomIds);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $activeContractCountByRoom[$row['room_id']] = (int) $row['cnt'];
            }
        }
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
        $contracts = [];
        $stmt = $this->pdo->prepare(
            'SELECT c.*, cu.first_name AS customer_first_name, cu.last_name AS customer_last_name, cu.id AS customer_id,
             (SELECT COALESCE(SUM(p.amount), 0) FROM payments p WHERE p.contract_id = c.id AND p.deleted_at IS NULL AND p.status IN (\'pending\', \'overdue\')) AS debt
             FROM contracts c
             INNER JOIN customers cu ON cu.id = c.customer_id AND cu.deleted_at IS NULL
             WHERE c.room_id = ? AND c.deleted_at IS NULL
             ORDER BY c.is_active DESC, c.start_date DESC'
        );
        $stmt->execute([$id]);
        $contracts = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            $actorName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
            Notification::createForCompany($this->pdo, $companyId, 'room', 'Oda eklendi', $roomNumber . ' numaralı oda ' . ($warehouse['name'] ?? '') . ' deposuna eklendi.', ['actor_name' => $actorName]);
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
        $actorName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        Notification::createForCompany($this->pdo, $room['company_id'] ?? null, 'room', 'Oda güncellendi', ($data['room_number'] ?? $room['room_number']) . ' oda bilgileri güncellendi.', ['actor_name' => $actorName]);
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
            $actorName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
            Notification::createForCompany($this->pdo, $room['company_id'] ?? null, 'room', 'Oda silindi', ($room['room_number'] ?? '') . ' numaralı oda silindi.', ['actor_name' => $actorName]);
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

    /** Excel (CSV) dışa aktar – mevcut odaları indir */
    public function exportCsv(): void
    {
        Auth::requireStaff();
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        $warehouseId = isset($_GET['warehouse_id']) && $_GET['warehouse_id'] !== '' ? trim($_GET['warehouse_id']) : null;
        $search = isset($_GET['q']) ? trim($_GET['q']) : null;
        $search = $search !== '' ? $search : null;
        $statusFilter = isset($_GET['status']) && in_array($_GET['status'], ['empty', 'occupied', 'reserved', 'locked'], true) ? $_GET['status'] : null;
        $hasContract = isset($_GET['has_contract']) && in_array($_GET['has_contract'], ['yes', 'no'], true) ? $_GET['has_contract'] : null;
        if ($companyId) {
            $rooms = Room::findAll($this->pdo, $warehouseId, $search, $statusFilter, $hasContract);
            $rooms = array_filter($rooms, fn($r) => ($r['company_id'] ?? '') === $companyId);
        } elseif (($user['role'] ?? '') === 'super_admin') {
            $rooms = Room::findAll($this->pdo, $warehouseId, $search, $statusFilter, $hasContract);
        } else {
            $rooms = [];
        }
        $filename = 'odalar_' . date('Y-m-d_His') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        $out = fopen('php://output', 'wb');
        fprintf($out, "\xEF\xBB\xBF");
        $headers = ['Depo Adı', 'Oda No', 'Alan (m²)', 'Aylık Fiyat', 'Durum', 'Kat', 'Blok', 'Koridor', 'Açıklama', 'Notlar'];
        fputcsv($out, $headers, ';');
        foreach ($rooms as $r) {
            $status = $r['status'] ?? 'empty';
            $statusLabel = $status === 'empty' ? 'Boş' : ($status === 'occupied' ? 'Dolu' : ($status === 'reserved' ? 'Rezerve' : 'Kilitli'));
            fputcsv($out, [
                $r['warehouse_name'] ?? '',
                $r['room_number'] ?? '',
                str_replace('.', ',', (string)($r['area_m2'] ?? '')),
                str_replace('.', ',', (string)($r['monthly_price'] ?? '')),
                $statusLabel,
                $r['floor'] ?? '',
                $r['block'] ?? '',
                $r['corridor'] ?? '',
                $r['description'] ?? '',
                $r['notes'] ?? '',
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
        header('Content-Disposition: attachment; filename="oda_sablonu.csv"');
        $out = fopen('php://output', 'wb');
        fprintf($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Depo Adı', 'Oda No', 'Alan (m²)', 'Aylık Fiyat', 'Durum', 'Kat', 'Blok', 'Koridor', 'Açıklama', 'Notlar'], ';');
        fputcsv($out, ['KARTAL DEPO', '101', '25', '5000', 'Boş', '', '', '', '', ''], ';');
        fputcsv($out, ['KARTAL DEPO', '102', '30', '6000', 'Boş', '1', 'A', '', '', ''], ';');
        fclose($out);
        exit;
    }

    /** Excel (CSV) içe aktar – form */
    public function importForm(): void
    {
        Auth::requireStaff();
        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        $currentPage = 'odalar';
        require __DIR__ . '/../../views/rooms/import.php';
    }

    /** Excel (CSV) içe aktar – işle */
    public function importCsv(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /odalar');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if (!$companyId) {
            $_SESSION['flash_error'] = 'Şirket bilgisi bulunamadı.';
            header('Location: /odalar/excel-ice-aktar');
            exit;
        }
        $file = $_FILES['csv_file'] ?? null;
        if (!$file || ($file['error'] ?? 0) !== UPLOAD_ERR_OK) {
            $_SESSION['flash_error'] = 'Lütfen bir CSV dosyası seçin.';
            header('Location: /odalar/excel-ice-aktar');
            exit;
        }
        $handle = @fopen($file['tmp_name'], 'rb');
        if (!$handle) {
            $_SESSION['flash_error'] = 'Dosya okunamadı.';
            header('Location: /odalar/excel-ice-aktar');
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
            $first = fgetcsv($handle, 0, ',');
        }
        $warehouses = Warehouse::findAll($this->pdo, $companyId);
        $warehouseByName = [];
        foreach ($warehouses as $w) {
            $warehouseByName[mb_strtoupper(trim($w['name'] ?? ''))] = $w;
        }
        $added = 0;
        $errors = [];
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $row = array_map('trim', $row);
            if (count($row) < 4) continue;
            $warehouseName = $row[0] ?? '';
            $roomNumber = $row[1] ?? '';
            $areaM2 = isset($row[2]) ? (float) str_replace(',', '.', $row[2]) : 0;
            $monthlyPrice = isset($row[3]) ? (float) str_replace(',', '.', $row[3]) : 0;
            if ($warehouseName === '' || $roomNumber === '') continue;
            if (stripos($warehouseName, 'depo') !== false && stripos($roomNumber, 'oda') !== false) continue; // başlık
            $status = 'empty';
            if (isset($row[4]) && $row[4] !== '') {
                $s = mb_strtolower($row[4]);
                if (strpos($s, 'dolu') !== false) $status = 'occupied';
                elseif (strpos($s, 'rezerve') !== false) $status = 'reserved';
                elseif (strpos($s, 'kilit') !== false) $status = 'locked';
            }
            $whKey = mb_strtoupper($warehouseName);
            $warehouse = $warehouseByName[$whKey] ?? null;
            if (!$warehouse) {
                try {
                    $newWh = Warehouse::create($this->pdo, ['name' => $warehouseName, 'company_id' => $companyId]);
                    $warehouse = $newWh;
                    $warehouseByName[$whKey] = $warehouse;
                } catch (Throwable $e) {
                    $errors[] = $roomNumber . ': Depo oluşturulamadı – ' . $e->getMessage();
                    continue;
                }
            }
            $warehouseId = $warehouse['id'];
            if ($areaM2 <= 0) $areaM2 = 1;
            if ($monthlyPrice < 0) $monthlyPrice = 0;
            try {
                Room::create($this->pdo, [
                    'room_number'   => $roomNumber,
                    'warehouse_id'  => $warehouseId,
                    'area_m2'       => $areaM2,
                    'monthly_price' => $monthlyPrice,
                    'status'        => $status,
                    'floor'         => $row[5] ?? null,
                    'block'         => $row[6] ?? null,
                    'corridor'      => $row[7] ?? null,
                    'description'   => $row[8] ?? null,
                    'notes'         => $row[9] ?? null,
                ]);
                $added++;
            } catch (Throwable $e) {
                $errors[] = $roomNumber . ': ' . $e->getMessage();
            }
        }
        fclose($handle);
        if ($added > 0) $_SESSION['flash_success'] = $added . ' oda eklendi.';
        if (!empty($errors)) $_SESSION['flash_error'] = implode(' ', array_slice($errors, 0, 3)) . (count($errors) > 3 ? ' …' : '');
        header('Location: /odalar/excel-ice-aktar');
        exit;
    }
}
