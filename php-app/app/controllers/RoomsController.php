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
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        $filterWarehouseId = isset($_GET['warehouse_id']) && $_GET['warehouse_id'] !== '' ? trim((string) $_GET['warehouse_id']) : null;
        $search = isset($_GET['q']) ? trim((string) $_GET['q']) : null;
        $search = $search !== '' ? $search : null;
        $statusFilter = isset($_GET['status']) && in_array($_GET['status'], ['empty', 'occupied', 'reserved', 'locked'], true) ? $_GET['status'] : null;
        $hasContract = isset($_GET['has_contract']) && in_array($_GET['has_contract'], ['yes', 'no'], true) ? $_GET['has_contract'] : null;
        if ($companyId) {
            $warehouses = Warehouse::findAll($this->pdo, $companyId);
            if ($filterWarehouseId !== null) {
                $filterWh = Warehouse::findOne($this->pdo, $filterWarehouseId);
                if (!$filterWh || ($filterWh['company_id'] ?? '') !== $companyId) {
                    $filterWarehouseId = null;
                }
            }
            $rooms = Room::findAll($this->pdo, $filterWarehouseId, $search, $statusFilter, $hasContract, $companyId);
        } elseif (($user['role'] ?? '') === 'super_admin') {
            $warehouses = Warehouse::findAll($this->pdo, null);
            $rooms = Room::findAll($this->pdo, $filterWarehouseId, $search, $statusFilter, $hasContract, null);
        } else {
            $warehouses = [];
            $rooms = [];
        }
        $rooms = array_values($rooms);
        ['success' => $flashSuccess, 'error' => $flashError] = Auth::consumeFlash();
        $filterWarehouseMeta = null;
        $warehouseRoomStats = null;
        if ($filterWarehouseId !== null) {
            $stmtWh = $this->pdo->prepare('SELECT id, name, total_floors FROM warehouses WHERE id = ? AND deleted_at IS NULL LIMIT 1');
            $stmtWh->execute([$filterWarehouseId]);
            $filterWarehouseMeta = $stmtWh->fetch(PDO::FETCH_ASSOC) ?: null;
            if ($filterWarehouseMeta) {
                $warehouseRoomStats = Room::statsByWarehouseId($this->pdo, $filterWarehouseId, $companyId);
            }
        }
        $duplicateRoomKeys = [];
        $seenRoomKeys = [];
        foreach ($rooms as $r) {
            $roomKey = ($r['warehouse_id'] ?? '') . ':' . normalizeRoomNumberKey($r['room_number'] ?? '');
            if ($roomKey === ':' || $roomKey === '') {
                continue;
            }
            if (isset($seenRoomKeys[$roomKey])) {
                $duplicateRoomKeys[$roomKey] = true;
            }
            $seenRoomKeys[$roomKey] = true;
        }
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
            Auth::setSession('flash_error', 'Oda bulunamadı.');
            header('Location: /odalar');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($user['role'] !== 'super_admin' && $room['company_id'] !== $companyId) {
            Auth::setSession('flash_error', 'Bu odaya erişim yetkiniz yok.');
            header('Location: /odalar');
            exit;
        }
        $contracts = [];
        $stmt = $this->pdo->prepare(
            'SELECT c.*, cu.first_name AS customer_first_name, cu.last_name AS customer_last_name, cu.id AS customer_id
             FROM contracts c
             INNER JOIN customers cu ON cu.id = c.customer_id AND cu.deleted_at IS NULL
             WHERE c.room_id = ? AND c.deleted_at IS NULL
             ORDER BY c.is_active DESC, c.start_date DESC'
        );
        $stmt->execute([$id]);
        $contracts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $debtByContract = computeDebtsForContracts($this->pdo, $contracts)['by_contract'];
        foreach ($contracts as &$contractRow) {
            $contractId = (string) ($contractRow['id'] ?? '');
            $contractRow['debt'] = (float) (($debtByContract[$contractId]['total'] ?? 0));
        }
        unset($contractRow);
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
            Auth::setSession('flash_error', 'Kullanıcı bir şirkete bağlı değil.');
            header('Location: /odalar');
            exit;
        }
        $warehouseId = trim($_POST['warehouse_id'] ?? '');
        $warehouse = $warehouseId ? Warehouse::findOne($this->pdo, $warehouseId) : null;
        if (!$warehouse || $warehouse['company_id'] !== $companyId) {
            Auth::setSession('flash_error', 'Geçerli bir depo seçin.');
            header('Location: /odalar');
            exit;
        }
        $roomNumber = trim($_POST['room_number'] ?? '');
        $areaM2 = isset($_POST['area_m2']) ? (float) str_replace(',', '.', $_POST['area_m2']) : 0;
        $monthlyPrice = isset($_POST['monthly_price']) ? (float) str_replace(',', '.', $_POST['monthly_price']) : 0;
        if ($roomNumber === '' || $areaM2 <= 0 || $monthlyPrice < 0) {
            Auth::setSession('flash_error', 'Oda numarası, alan (m²) ve aylık fiyat gerekli.');
            $this->redirectToRoomsIndex($warehouseId, true);
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
            $created = Room::create($this->pdo, $data);
            $actorName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
            $roomId = is_array($created) ? ($created['id'] ?? null) : null;
            Notification::createForCompanyAndWarehouse(
                $this->pdo,
                $companyId,
                $warehouseId,
                'room',
                'Oda eklendi',
                ($roomNumber ?: 'Oda') . ' — ' . ($warehouse['name'] ?? 'depo') . ' deposuna eklendi.',
                ['actor_name' => $actorName, 'warehouse_id' => $warehouseId, 'room_id' => $roomId]
            );
            Auth::setSession('flash_success', 'Oda eklendi.');
            $this->redirectToRoomsIndex($warehouseId);
        } catch (InvalidArgumentException $e) {
            Auth::setSession('flash_error', $e->getMessage());
            $this->redirectToRoomsIndex($warehouseId, true);
        } catch (Exception $e) {
            Auth::setSession('flash_error', 'Oda eklenemedi: ' . $e->getMessage());
            $this->redirectToRoomsIndex($warehouseId, true);
        }
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
            Auth::setSession('flash_error', 'Oda bulunamadı.');
            header('Location: /odalar');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($user['role'] !== 'super_admin' && $room['company_id'] !== $companyId) {
            Auth::setSession('flash_error', 'Bu odaya erişim yetkiniz yok.');
            header('Location: /odalar');
            exit;
        }
        $warehouseId = trim($_POST['warehouse_id'] ?? '');
        if (!$warehouseId) {
            Auth::setSession('flash_error', 'Depo seçimi zorunludur.');
            header('Location: /odalar');
            exit;
        }
        $warehouse = Warehouse::findOne($this->pdo, $warehouseId);
        if (!$warehouse || ($user['role'] !== 'super_admin' && ($warehouse['company_id'] ?? '') !== $companyId)) {
            Auth::setSession('flash_error', 'Geçersiz depo.');
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
        try {
            Room::update($this->pdo, $id, $data);
        } catch (InvalidArgumentException $e) {
            Auth::setSession('flash_error', $e->getMessage());
            header('Location: /odalar' . $this->roomsIndexQuery());
            exit;
        }
        $actorName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        Notification::createForCompanyAndWarehouse(
            $this->pdo,
            $room['company_id'] ?? $companyId,
            $warehouseId,
            'room',
            'Oda güncellendi',
            ($data['room_number'] ?? $room['room_number'] ?? 'Oda') . ' güncellendi.',
            ['actor_name' => $actorName, 'warehouse_id' => $warehouseId, 'room_id' => $id]
        );
        Auth::setSession('flash_success', 'Oda güncellendi.');
        header('Location: /odalar' . $this->roomsIndexQuery());
        exit;
    }

    /** Hızlı durum değişimi (AJAX) — bildirim/e-posta yok, tam sayfa yenileme yok */
    public function updateStatus(): void
    {
        Auth::requireStaff();
        header('Content-Type: application/json; charset=utf-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'Geçersiz istek']);
            return;
        }
        $id = trim($_POST['id'] ?? '');
        $status = trim($_POST['status'] ?? '');
        $allowed = ['empty', 'occupied', 'reserved', 'locked'];
        if ($id === '' || !in_array($status, $allowed, true)) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Geçersiz oda veya durum']);
            return;
        }
        $room = Room::findOne($this->pdo, $id);
        if (!$room) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Oda bulunamadı']);
            return;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if (($user['role'] ?? '') !== 'super_admin' && ($room['company_id'] ?? '') !== $companyId) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Yetkisiz']);
            return;
        }
        if (!Room::patchStatus($this->pdo, $id, $status)) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'Güncellenemedi']);
            return;
        }
        $labels = ['empty' => 'Boş', 'occupied' => 'Dolu', 'reserved' => 'Rezerve', 'locked' => 'Kilitli'];
        echo json_encode(['ok' => true, 'status' => $status, 'label' => $labels[$status] ?? $status]);
    }

    private function roomsIndexQuery(): string
    {
        $keep = array_filter([
            'warehouse_id' => $_POST['_return_warehouse_id'] ?? '',
            'q' => $_POST['_return_q'] ?? '',
            'status' => $_POST['_return_status'] ?? '',
            'has_contract' => $_POST['_return_has_contract'] ?? '',
        ], static fn($v) => $v !== '');
        return $keep ? ('?' . http_build_query($keep)) : '';
    }

    private function redirectToRoomsIndex(?string $warehouseId = null, bool $openAddModal = false): never
    {
        $params = [];
        if ($warehouseId !== null && $warehouseId !== '') {
            $params['warehouse_id'] = $warehouseId;
        }
        if ($openAddModal) {
            $params['add'] = '1';
        }
        $qs = $params ? ('?' . http_build_query($params)) : '';
        header('Location: /odalar' . $qs);
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
            Auth::setSession('flash_error', 'Oda seçilmedi.');
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
            $whId = $room['warehouse_id'] ?? null;
            $roomNumber = $room['room_number'] ?? $id;
            $roomCompanyId = $room['company_id'] ?? null;
            Room::remove($this->pdo, $id);
            $actorName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
            Notification::createForCompanyAndWarehouse(
                $this->pdo,
                $roomCompanyId,
                $whId,
                'room',
                'Oda silindi',
                $roomNumber . ' odası silindi.',
                ['actor_name' => $actorName, 'warehouse_id' => $whId, 'room_id' => $id]
            );
            $deleted++;
        }
        if (!empty($errors)) {
            Auth::setSession('flash_error', 'Bazı odalar silinemedi (aktif sözleşme var): ' . implode(', ', $errors));
        }
        if ($deleted > 0) {
            Auth::setSession('flash_success', $deleted === 1 ? 'Oda silindi.' : $deleted . ' oda silindi.');
        } elseif (empty($errors)) {
            Auth::setSession('flash_error', 'Silinecek oda bulunamadı veya yetkiniz yok.');
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
            if ($warehouseId !== null) {
                $filterWh = Warehouse::findOne($this->pdo, $warehouseId);
                if (!$filterWh || ($filterWh['company_id'] ?? '') !== $companyId) {
                    $warehouseId = null;
                }
            }
            $rooms = Room::findAll($this->pdo, $warehouseId, $search, $statusFilter, $hasContract, $companyId);
        } elseif (($user['role'] ?? '') === 'super_admin') {
            $rooms = Room::findAll($this->pdo, $warehouseId, $search, $statusFilter, $hasContract, null);
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
        ['success' => $flashSuccess, 'error' => $flashError] = Auth::consumeFlash();
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
            Auth::setSession('flash_error', 'Şirket bilgisi bulunamadı.');
            header('Location: /odalar/excel-ice-aktar');
            exit;
        }
        $file = $_FILES['csv_file'] ?? null;
        if (!$file || ($file['error'] ?? 0) !== UPLOAD_ERR_OK) {
            Auth::setSession('flash_error', 'Lütfen bir CSV dosyası seçin.');
            header('Location: /odalar/excel-ice-aktar');
            exit;
        }
        $handle = @fopen($file['tmp_name'], 'rb');
        if (!$handle) {
            Auth::setSession('flash_error', 'Dosya okunamadı.');
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
        $skipped = 0;
        $errors = [];
        $batchSeen = [];
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
            $roomKey = $warehouseId . ':' . normalizeRoomNumberKey($roomNumber);
            try {
                if ($roomKey !== ':' && isset($batchSeen[$roomKey])) {
                    $errors[] = $warehouseName . ' / ' . $roomNumber . ': CSV dosyasında tekrar eden oda numarası.';
                    $skipped++;
                    continue;
                }
                $existing = Room::findByWarehouseAndNumber($this->pdo, $warehouseId, $roomNumber);
                if ($existing) {
                    $errors[] = $warehouseName . ' / ' . $roomNumber . ': ' . roomDuplicateMessage($roomNumber, $existing);
                    $skipped++;
                    continue;
                }
                $payload = [
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
                ];
                $created = Room::create($this->pdo, $payload);
                $added++;
                if ($roomKey !== ':' && !empty($created['id'])) {
                    $batchSeen[$roomKey] = $created['id'];
                }
            } catch (InvalidArgumentException $e) {
                $errors[] = $warehouseName . ' / ' . $roomNumber . ': ' . $e->getMessage();
                $skipped++;
            } catch (Throwable $e) {
                $errors[] = $roomNumber . ': ' . $e->getMessage();
            }
        }
        fclose($handle);
        if ($added > 0) {
            Auth::setSession('flash_success', $added . ' oda eklendi.');
        }
        if ($skipped > 0 && empty($errors)) {
            Auth::setSession('flash_error', $skipped . ' satır atlandı (aynı oda numarası).');
        }
        if (!empty($errors)) {
            $msg = implode(' ', array_slice($errors, 0, 3));
            if (count($errors) > 3) {
                $msg .= ' …';
            }
            if ($skipped > 3) {
                $msg .= ' (toplam ' . $skipped . ' tekrar)';
            }
            Auth::setSession('flash_error', $msg);
        }
        header('Location: /odalar/excel-ice-aktar');
        exit;
    }
}
