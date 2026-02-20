<?php
class ContractsController
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
        $statusFilter = isset($_GET['durum']) && in_array($_GET['durum'], ['active', 'inactive'], true) ? $_GET['durum'] : null;
        $debtFilter = isset($_GET['borc']) && in_array($_GET['borc'], ['with_debt', 'no_debt'], true) ? $_GET['borc'] : null;
        $perPage = 50;
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $offset = ($page - 1) * $perPage;

        if ($companyId) {
            $contractsTotal = Contract::count($this->pdo, $companyId, $statusFilter, $debtFilter);
            $contracts = Contract::findAll($this->pdo, $companyId, $statusFilter, $debtFilter, $perPage, $offset);
            $warehouses = Warehouse::findAll($this->pdo, $companyId);
            $customers = Customer::findAll($this->pdo, $companyId, null, 500);
        } elseif (($user['role'] ?? '') === 'super_admin') {
            $contractsTotal = Contract::count($this->pdo, null, $statusFilter, $debtFilter);
            $contracts = Contract::findAll($this->pdo, null, $statusFilter, $debtFilter, $perPage, $offset);
            $warehouses = Warehouse::findAll($this->pdo, null);
            $customers = Customer::findAll($this->pdo, null, null, 500);
        } else {
            $contractsTotal = 0;
            $contracts = $warehouses = $customers = [];
        }
        $rooms = Room::findAll($this->pdo, null);
        if ($companyId) {
            $rooms = array_filter($rooms, fn($r) => ($r['company_id'] ?? '') === $companyId);
        }
        $roomsEmpty = array_values(array_filter($rooms, fn($r) => ($r['status'] ?? '') === 'empty'));
        $vehicles = [];
        try {
            $this->pdo->query("SELECT 1 FROM vehicles LIMIT 1");
            $vehicles = Vehicle::findAll($this->pdo, $companyId ?? null);
        } catch (Throwable $e) {
            // vehicles tablosu yoksa boş bırak
        }
        $staff = [];
        $owners = [];
        if ($companyId) {
            $stmt = $this->pdo->prepare('SELECT id, first_name, last_name, email FROM users WHERE company_id = ? AND deleted_at IS NULL AND role IN (\'company_staff\', \'data_entry\', \'accounting\') ORDER BY first_name, last_name');
            $stmt->execute([$companyId]);
            $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt = $this->pdo->prepare('SELECT id, first_name, last_name, email FROM users WHERE company_id = ? AND deleted_at IS NULL AND role = \'company_owner\' ORDER BY first_name, last_name');
            $stmt->execute([$companyId]);
            $owners = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        $openNewSale = isset($_GET['newSale']) && $_GET['newSale'] !== '0';
        $newCustomerId = isset($_GET['newCustomerId']) ? trim($_GET['newCustomerId']) : '';
        $contractDebt = $this->getContractDebtCounts($contracts);
        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        $rooms = $roomsEmpty;
        require __DIR__ . '/../../views/contracts/index.php';
    }

    /** Birden fazla sözleşme için borç sayılarını tek sorguda alır (N+1 önleme) */
    private function getContractDebtCounts(array $contracts): array
    {
        if (empty($contracts)) {
            return [];
        }
        $ids = array_map(fn($c) => $c['id'] ?? '', array_filter($contracts, fn($c) => !empty($c['id'])));
        if (empty($ids)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT contract_id, SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) AS overdue_cnt, SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_cnt FROM payments WHERE contract_id IN ($placeholders) AND deleted_at IS NULL GROUP BY contract_id"
        );
        $stmt->execute($ids);
        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[$row['contract_id']] = ['overdue' => (int) ($row['overdue_cnt'] ?? 0), 'pending' => (int) ($row['pending_cnt'] ?? 0)];
        }
        foreach ($ids as $id) {
            if (!isset($result[$id])) {
                $result[$id] = ['overdue' => 0, 'pending' => 0];
            }
        }
        return $result;
    }

    public function create(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /girisler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if (!$companyId && ($user['role'] ?? '') !== 'super_admin') {
            $_SESSION['flash_error'] = 'Şirket bilgisi gerekli.';
            header('Location: /girisler');
            exit;
        }
        $customerId = trim($_POST['customer_id'] ?? '');
        $roomId = trim($_POST['room_id'] ?? '');
        $startDate = trim($_POST['start_date'] ?? '');
        $endDate = trim($_POST['end_date'] ?? '');
        if (!$customerId || !$roomId || !$startDate || !$endDate) {
            $_SESSION['flash_error'] = 'Müşteri, oda ve tarihler zorunludur.';
            header('Location: /girisler');
            exit;
        }
        $room = Room::findOne($this->pdo, $roomId);
        if (!$room) {
            $_SESSION['flash_error'] = 'Geçersiz oda.';
            header('Location: /girisler');
            exit;
        }
        $customer = null;
        $stmt = $this->pdo->prepare('SELECT id, company_id FROM customers WHERE id = ? AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([$customerId]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$customer) {
            $_SESSION['flash_error'] = 'Geçersiz müşteri.';
            header('Location: /girisler');
            exit;
        }
        if ($user['role'] !== 'super_admin' && $companyId && $room['company_id'] !== $companyId) {
            $_SESSION['flash_error'] = 'Bu odaya erişim yetkiniz yok.';
            header('Location: /girisler');
            exit;
        }
        $monthlyPrice = isset($_POST['monthly_price']) && $_POST['monthly_price'] !== '' ? (float) str_replace(',', '.', $_POST['monthly_price']) : (float) $room['monthly_price'];
        $transportationFee = isset($_POST['transportation_fee']) && $_POST['transportation_fee'] !== '' ? (float) str_replace(',', '.', $_POST['transportation_fee']) : 0;
        $discount = isset($_POST['discount']) && $_POST['discount'] !== '' ? (float) str_replace(',', '.', $_POST['discount']) : 0;
        $soldBy = trim($_POST['sold_by_user_id'] ?? '') ?: null;
        $vehiclePlate = trim($_POST['vehicle_plate'] ?? '');
        if ($vehiclePlate === '' && !empty($_POST['vehicle_id'])) {
            $vid = trim($_POST['vehicle_id'] ?? '');
            if ($vid) {
                $v = Vehicle::findById($this->pdo, $vid, $companyId);
                if ($v && !empty($v['plate'])) {
                    $vehiclePlate = $v['plate'];
                }
            }
        }
        $contractPdfUrl = null;
        if (!empty($_FILES['contract_pdf']['name']) && ($_FILES['contract_pdf']['error'] ?? 0) === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['contract_pdf']['name'], PATHINFO_EXTENSION));
            if ($ext === 'pdf') {
                $uploadDir = defined('APP_ROOT') ? APP_ROOT . '/public/uploads/contracts' : __DIR__ . '/../../public/uploads/contracts';
                if (!is_dir($uploadDir)) {
                    @mkdir($uploadDir, 0755, true);
                }
                $filename = 'contract_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.pdf';
                $path = $uploadDir . '/' . $filename;
                if (move_uploaded_file($_FILES['contract_pdf']['tmp_name'], $path)) {
                    $contractPdfUrl = '/uploads/contracts/' . $filename;
                }
            }
        }
        $data = [
            'customer_id' => $customerId,
            'room_id' => $roomId,
            'start_date' => $startDate . ' 00:00:00',
            'end_date' => $endDate . ' 23:59:59',
            'monthly_price' => $monthlyPrice,
            'sold_by_user_id' => $soldBy,
            'transportation_fee' => $transportationFee,
            'pickup_location' => trim($_POST['pickup_location'] ?? '') ?: null,
            'discount' => $discount,
            'driver_name' => trim($_POST['driver_name'] ?? '') ?: null,
            'driver_phone' => trim($_POST['driver_phone'] ?? '') ?: null,
            'vehicle_plate' => $vehiclePlate ?: null,
            'contract_pdf_url' => $contractPdfUrl,
            'notes' => trim($_POST['notes'] ?? '') ?: null,
        ];
        try {
            $created = Contract::create($this->pdo, $data);
            $contractId = $created['id'] ?? null;
            if ($contractId) {
                $staffIds = isset($_POST['staff_ids']) && is_array($_POST['staff_ids']) ? array_filter($_POST['staff_ids']) : [];
                foreach ($staffIds as $uid) {
                    $uid = trim($uid);
                    if ($uid === '') continue;
                    $stmtCs = $this->pdo->prepare('INSERT INTO contract_staff (id, contract_id, user_id) VALUES (?, ?, ?)');
                    $stmtCs->execute([self::uuid(), $contractId, $uid]);
                }
                $start = new DateTime($startDate);
                $end = new DateTime($endDate);
                $end->modify('last day of this month');
                $monthlyPricesPost = isset($_POST['monthly_prices']) && is_array($_POST['monthly_prices']) ? $_POST['monthly_prices'] : [];
                $stmtCmp = $this->pdo->prepare('INSERT INTO contract_monthly_prices (id, contract_id, month, price) VALUES (?, ?, ?, ?)');
                while ($start <= $end) {
                    $monthStr = $start->format('Y-m');
                    $priceForMonth = $monthlyPrice;
                    if (isset($monthlyPricesPost[$monthStr]) && $monthlyPricesPost[$monthStr] !== '') {
                        $priceForMonth = (float) str_replace(',', '.', $monthlyPricesPost[$monthStr]);
                    }
                    $dueDate = $monthStr . '-01 00:00:00';
                    $cmpId = self::uuid();
                    $stmtCmp->execute([$cmpId, $contractId, $monthStr, $priceForMonth]);
                    Payment::create($this->pdo, [
                        'contract_id' => $contractId,
                        'amount' => $priceForMonth,
                        'status' => 'pending',
                        'due_date' => $dueDate,
                    ]);
                    $start->modify('+1 month');
                }
                Room::update($this->pdo, $roomId, ['status' => 'occupied']);
            }
            $contractNumber = $created['contract_number'] ?? $contractId ?? '';
            $actorName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
            Notification::createForCompany($this->pdo, $room['company_id'] ?? $companyId, 'contract', 'Sözleşme oluşturuldu', 'Sözleşme ' . $contractNumber . ' oluşturuldu.', ['contract_id' => $contractId, 'actor_name' => $actorName]);
            $this->sendContractCreatedEmails($created);

            // Nakliye bilgisi işaretlendiyse nakliye işi oluştur (nakliye-isler listesine düşer)
            $hasTransportation = isset($_POST['has_transportation']) && $_POST['has_transportation'] === '1';
            $pickupLocation = trim($_POST['pickup_location'] ?? '');
            $deliveryLocation = trim($_POST['delivery_location'] ?? '');
            if ($hasTransportation && ($pickupLocation !== '' || $deliveryLocation !== '' || $transportationFee > 0)) {
                $warehouse = !empty($room['warehouse_id']) ? Warehouse::findOne($this->pdo, $room['warehouse_id']) : null;
                $deliveryAddress = $deliveryLocation;
                if ($deliveryAddress === '' && $warehouse) {
                    $parts = array_filter([$warehouse['name'] ?? '', $warehouse['address'] ?? '', $warehouse['city'] ?? '', $warehouse['district'] ?? '']);
                    $deliveryAddress = implode(', ', $parts);
                }
                if ($deliveryAddress === '' && !empty($room['warehouse_name'])) {
                    $deliveryAddress = $room['warehouse_name'];
                }
                try {
                    TransportationJob::create($this->pdo, [
                        'company_id' => $room['company_id'] ?? $companyId,
                        'customer_id' => $customerId,
                        'job_type' => 'Depo girişi nakliyesi',
                        'pickup_address' => $pickupLocation ?: null,
                        'delivery_address' => $deliveryAddress ?: null,
                        'price' => $transportationFee,
                        'job_date' => $startDate,
                        'status' => 'pending',
                        'vehicle_plate' => $vehiclePlate ?: null,
                        'notes' => 'Sözleşme: ' . ($contractNumber ?? $contractId),
                    ]);
                } catch (Throwable $e) {
                    // Nakliye işi oluşturulamazsa sözleşme yine başarılı sayılır
                }
            }

            $_SESSION['flash_success'] = 'Sözleşme oluşturuldu.';
        } catch (Exception $e) {
            $_SESSION['flash_error'] = 'Kayıt oluşturulamadı: ' . $e->getMessage();
        }
        header('Location: /girisler');
        exit;
    }

    public function edit(array $params): void
    {
        Auth::requireStaff();
        $id = $params['id'] ?? '';
        if (!$id) {
            header('Location: /girisler');
            exit;
        }
        $contract = Contract::findOne($this->pdo, $id);
        if (!$contract) {
            $_SESSION['flash_error'] = 'Sözleşme bulunamadı.';
            header('Location: /girisler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($contract['company_id'] ?? '') !== $companyId) {
            $_SESSION['flash_error'] = 'Bu sözleşmeye erişim yetkiniz yok.';
            header('Location: /girisler');
            exit;
        }
        $pageTitle = 'Sözleşme Düzenle: ' . ($contract['contract_number'] ?? '');
        require __DIR__ . '/../../views/contracts/edit.php';
    }

    public function update(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /girisler');
            exit;
        }
        $id = trim($_POST['contract_id'] ?? '');
        if (!$id) {
            $_SESSION['flash_error'] = 'Sözleşme belirtilmedi.';
            header('Location: /girisler');
            exit;
        }
        $contract = Contract::findOne($this->pdo, $id);
        if (!$contract) {
            $_SESSION['flash_error'] = 'Sözleşme bulunamadı.';
            header('Location: /girisler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($contract['company_id'] ?? '') !== $companyId) {
            $_SESSION['flash_error'] = 'Bu sözleşmeye erişim yetkiniz yok.';
            header('Location: /girisler');
            exit;
        }
        $startDate = trim($_POST['start_date'] ?? '') ?: null;
        $endDate = trim($_POST['end_date'] ?? '') ?: null;
        if ($startDate) $startDate .= ' 00:00:00';
        if ($endDate) $endDate .= ' 23:59:59';
        try {
            Contract::update($this->pdo, $id, [
                'start_date' => $startDate ?? $contract['start_date'],
                'end_date' => $endDate ?? $contract['end_date'],
                'transportation_fee' => isset($_POST['transportation_fee']) && $_POST['transportation_fee'] !== '' ? (float) str_replace(',', '.', $_POST['transportation_fee']) : 0,
                'pickup_location' => trim($_POST['pickup_location'] ?? '') ?: null,
                'discount' => isset($_POST['discount']) && $_POST['discount'] !== '' ? (float) str_replace(',', '.', $_POST['discount']) : 0,
                'driver_name' => trim($_POST['driver_name'] ?? '') ?: null,
                'driver_phone' => trim($_POST['driver_phone'] ?? '') ?: null,
                'vehicle_plate' => trim($_POST['vehicle_plate'] ?? '') ?: null,
                'notes' => trim($_POST['notes'] ?? '') ?: null,
            ]);
            $_SESSION['flash_success'] = 'Sözleşme güncellendi.';
        } catch (Exception $e) {
            $_SESSION['flash_error'] = 'Güncellenemedi: ' . $e->getMessage();
        }
        header('Location: /girisler/' . $id);
        exit;
    }

    public function show(array $params): void
    {
        Auth::requireStaff();
        $id = $params['id'] ?? '';
        if (!$id) {
            header('Location: /girisler');
            exit;
        }
        $contract = Contract::findOne($this->pdo, $id);
        if (!$contract) {
            $_SESSION['flash_error'] = 'Sözleşme bulunamadı.';
            header('Location: /girisler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($contract['company_id'] ?? '') !== $companyId) {
            $_SESSION['flash_error'] = 'Bu sözleşmeye erişim yetkiniz yok.';
            header('Location: /girisler');
            exit;
        }
        $payments = Payment::findByContractId($this->pdo, $id);
        $monthlyPrices = Contract::getMonthlyPricesByContractId($this->pdo, $id);
        $monthNames = ['01'=>'Ocak','02'=>'Şubat','03'=>'Mart','04'=>'Nisan','05'=>'Mayıs','06'=>'Haziran','07'=>'Temmuz','08'=>'Ağustos','09'=>'Eylül','10'=>'Ekim','11'=>'Kasım','12'=>'Aralık'];
        if (empty($monthlyPrices)) {
            foreach ($payments as $p) {
                $due = $p['due_date'] ?? '';
                if ($due) {
                    $m = date('m', strtotime($due));
                    $y = date('Y', strtotime($due));
                    $monthlyPrices[] = ['month' => ($monthNames[$m] ?? $m) . ' ' . $y, 'price' => $p['amount'] ?? $contract['monthly_price'] ?? 0];
                }
            }
        } else {
            foreach ($monthlyPrices as &$mp) {
                $ym = $mp['month'] ?? '';
                if (preg_match('/^(\d{4})-(\d{2})$/', $ym, $m)) {
                    $mp['month'] = ($monthNames[$m[2]] ?? $m[2]) . ' ' . $m[1];
                }
            }
            unset($mp);
        }
        $company = !empty($contract['company_id']) ? Company::findOne($this->pdo, $contract['company_id']) : null;
        $collectPayments = array_values(array_filter($payments, fn($p) => in_array($p['status'] ?? '', ['pending', 'overdue'])));
        $bankAccounts = [];
        $cid = $contract['company_id'] ?? $companyId;
        if ($cid) {
            $stmt = $this->pdo->prepare('SELECT * FROM bank_accounts WHERE company_id = ? AND deleted_at IS NULL AND is_active = 1 ORDER BY bank_name');
            $stmt->execute([$cid]);
            $bankAccounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } elseif (($user['role'] ?? '') === 'super_admin') {
            $stmt = $this->pdo->query('SELECT * FROM bank_accounts WHERE deleted_at IS NULL AND is_active = 1 ORDER BY bank_name');
            $bankAccounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        $pageTitle = 'Sözleşme: ' . ($contract['contract_number'] ?? $id);
        require __DIR__ . '/../../views/contracts/detail.php';
    }

    /** Sözleşme yazdır – barkod gibi özel yazdırma sayfası */
    public function printPage(array $params): void
    {
        Auth::requireStaff();
        $id = $params['id'] ?? '';
        if (!$id) {
            header('Location: /girisler');
            exit;
        }
        $contract = Contract::findOne($this->pdo, $id);
        if (!$contract) {
            $_SESSION['flash_error'] = 'Sözleşme bulunamadı.';
            header('Location: /girisler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($contract['company_id'] ?? '') !== $companyId) {
            $_SESSION['flash_error'] = 'Bu sözleşmeye erişim yetkiniz yok.';
            header('Location: /girisler');
            exit;
        }
        $payments = Payment::findByContractId($this->pdo, $id);
        $company = !empty($contract['company_id']) ? Company::findOne($this->pdo, $contract['company_id']) : null;
        $soldByName = trim(($contract['sold_by_first_name'] ?? '') . ' ' . ($contract['sold_by_last_name'] ?? '')) ?: '-';
        require __DIR__ . '/../../views/contracts/print.php';
    }

    public function terminate(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /girisler');
            exit;
        }
        $id = trim($_POST['id'] ?? '');
        $contract = $id ? Contract::findOne($this->pdo, $id) : null;
        if (!$contract) {
            $_SESSION['flash_error'] = 'Sözleşme bulunamadı.';
            header('Location: /girisler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($contract['company_id'] ?? '') !== $companyId) {
            $_SESSION['flash_error'] = 'Yetkisiz.';
            header('Location: /girisler');
            exit;
        }
        Contract::setActive($this->pdo, $id, 0);
        $roomId = $contract['room_id'] ?? null;
        if ($roomId) {
            Room::update($this->pdo, $roomId, ['status' => 'empty']);
        }
        $_SESSION['flash_success'] = 'Sözleşme sonlandırıldı.';
        header('Location: /girisler');
        exit;
    }

    /** Çıkış belgesi yazdır */
    public function exitDocumentPrint(array $params): void
    {
        Auth::requireStaff();
        $id = $params['id'] ?? '';
        if (!$id) {
            header('Location: /girisler');
            exit;
        }
        $contract = Contract::findOne($this->pdo, $id);
        if (!$contract) {
            $_SESSION['flash_error'] = 'Sözleşme bulunamadı.';
            header('Location: /girisler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($contract['company_id'] ?? '') !== $companyId) {
            $_SESSION['flash_error'] = 'Bu sözleşmeye erişim yetkiniz yok.';
            header('Location: /girisler');
            exit;
        }
        $company = !empty($contract['company_id']) ? Company::findOne($this->pdo, $contract['company_id']) : null;
        $customerName = trim(($contract['customer_first_name'] ?? '') . ' ' . ($contract['customer_last_name'] ?? ''));
        $contractPayments = Payment::findByContractId($this->pdo, $id);
        $pageTitle = 'Çıkış belgesi: ' . ($contract['contract_number'] ?? '');
        require __DIR__ . '/../../views/contracts/exit_document.php';
    }

    public function delete(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /girisler');
            exit;
        }
        $ids = isset($_POST['ids']) && is_array($_POST['ids']) ? array_filter(array_map('trim', $_POST['ids'])) : [];
        if (empty($ids)) {
            $id = trim($_POST['id'] ?? '');
            if ($id !== '') $ids = [$id];
        }
        if (empty($ids)) {
            $_SESSION['flash_error'] = 'Sözleşme seçilmedi.';
            header('Location: /girisler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        $deleted = 0;
        foreach ($ids as $id) {
            $contract = Contract::findOne($this->pdo, $id);
            if (!$contract) continue;
            if ($companyId && ($contract['company_id'] ?? '') !== $companyId) continue;
            $roomId = $contract['room_id'] ?? null;
            $contractNumber = $contract['contract_number'] ?? $id;
            Contract::softDelete($this->pdo, $id);
            if ($roomId) Room::update($this->pdo, $roomId, ['status' => 'empty']);
            Notification::createForCompany($this->pdo, $contract['company_id'] ?? null, 'contract', 'Sözleşme silindi', 'Sözleşme ' . $contractNumber . ' silindi.');
            $deleted++;
        }
        if ($deleted > 0) {
            $_SESSION['flash_success'] = $deleted === 1 ? 'Sözleşme silindi.' : $deleted . ' sözleşme silindi.';
        } else {
            $_SESSION['flash_error'] = 'Silinecek sözleşme bulunamadı veya yetkiniz yok.';
        }
        header('Location: /girisler');
        exit;
    }

    /**
     * Sözleşme oluşturuldu bildirim e-postaları (müşteri + yönetici). Modern HTML şablon kullanır.
     */
    private function sendContractCreatedEmails(array $contract): void
    {
        $companyId = $contract['company_id'] ?? null;
        if (!$companyId) {
            return;
        }
        $mail = Company::getMailSettings($this->pdo, $companyId);
        if (!$mail || empty($mail['smtp_host']) || empty($mail['is_active'])) {
            return;
        }
        $config = require defined('APP_ROOT') ? APP_ROOT . '/config/config.php' : __DIR__ . '/../../config/config.php';
        $appName = $config['app_name'] ?? 'Depo ve Nakliye Takip';
        $musteriAdi = trim(($contract['customer_first_name'] ?? '') . ' ' . ($contract['customer_last_name'] ?? ''));
        $sozlesmeNo = $contract['contract_number'] ?? '';
        $createdAt = $contract['created_at'] ?? null;
        $sozlesmeTarihi = $createdAt ? date('d.m.Y H:i', strtotime($createdAt)) : date('d.m.Y');
        $depoAdi = $contract['warehouse_name'] ?? '';
        $odaNo = $contract['room_number'] ?? '';
        $baslangicTarihi = !empty($contract['start_date']) ? date('d.m.Y', strtotime($contract['start_date'])) : '';
        $bitisTarihi = !empty($contract['end_date']) ? date('d.m.Y', strtotime($contract['end_date'])) : '';
        $aylikUcret = number_format((float) ($contract['monthly_price'] ?? $contract['room_monthly_price'] ?? 0), 2, ',', '.') . ' ₺';
        $defaultCustomer = "Sayın {musteri_adi},\n\nSözleşmeniz oluşturuldu. Sözleşme No: {sozlesme_no}\n\nİyi günler dileriz.";
        $defaultAdmin = "Yeni sözleşme bildirimi:\n\n{sozlesme_tarihi} tarihinde {sozlesme_no} numaralı sözleşme oluşturuldu.\nMüşteri: {musteri_adi}\nDepo: {depo_adi}\nOda: {oda_no}\nBaşlangıç: {baslangic_tarihi} – Bitiş: {bitis_tarihi}\nAylık ücret: {aylik_ucret}";
        $tplCustomer = !empty(trim($mail['contract_created_template'] ?? '')) ? $mail['contract_created_template'] : $defaultCustomer;
        $tplAdmin = !empty(trim($mail['admin_contract_created_template'] ?? '')) ? $mail['admin_contract_created_template'] : $defaultAdmin;
        $replace = [
            '{musteri_adi}' => $musteriAdi,
            '{sozlesme_no}' => $sozlesmeNo,
            '{sozlesme_tarihi}' => $sozlesmeTarihi,
            '{depo_adi}' => $depoAdi,
            '{oda_no}' => $odaNo,
            '{baslangic_tarihi}' => $baslangicTarihi,
            '{bitis_tarihi}' => $bitisTarihi,
            '{aylik_ucret}' => $aylikUcret,
        ];

        if (!empty($mail['notify_customer_on_contract'])) {
            $customerEmail = trim($contract['customer_email'] ?? '');
            if ($customerEmail !== '' && filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
                $bodyPlain = str_replace(array_keys($replace), array_values($replace), $tplCustomer);
                $bodyHtml = MailService::wrapInHtmlTemplate($appName, 'Sözleşme Oluşturuldu', $bodyPlain, 'Sözleşme No: ' . $sozlesmeNo);
                MailService::sendSmtp($mail, $customerEmail, $appName . ' – Sözleşme Oluşturuldu', $bodyPlain, $bodyHtml);
            }
        }

        if (!empty($mail['notify_admin_on_contract'])) {
            $staff = User::findStaff($this->pdo, $companyId);
            $adminBodyPlain = str_replace(array_keys($replace), array_values($replace), $tplAdmin);
            $adminBodyHtml = MailService::wrapInHtmlTemplate($appName, 'Yeni Sözleşme Bildirimi', $adminBodyPlain, $sozlesmeNo . ' – ' . $musteriAdi);
            foreach ($staff as $u) {
                $email = trim($u['email'] ?? '');
                if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    MailService::sendSmtp($mail, $email, $appName . ' – Yeni sözleşme: ' . $sozlesmeNo, $adminBodyPlain, $adminBodyHtml);
                }
            }
        }
    }

    private static function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
