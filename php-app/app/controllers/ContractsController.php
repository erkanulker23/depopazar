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
        if ($companyId) {
            $contracts = Contract::findAll($this->pdo, $companyId, $statusFilter, $debtFilter);
            $warehouses = Warehouse::findAll($this->pdo, $companyId);
            $customers = Customer::findAll($this->pdo, $companyId);
        } elseif (($user['role'] ?? '') === 'super_admin') {
            $contracts = Contract::findAll($this->pdo, null, $statusFilter, $debtFilter);
            $warehouses = Warehouse::findAll($this->pdo, null);
            $customers = Customer::findAll($this->pdo, null);
        } else {
            $contracts = $warehouses = $customers = [];
        }
        $rooms = Room::findAll($this->pdo, null);
        if ($companyId) {
            $rooms = array_filter($rooms, fn($r) => ($r['company_id'] ?? '') === $companyId);
        }
        // Yeni satışta sadece boş odalar listelensin
        $roomsEmpty = array_values(array_filter($rooms, fn($r) => ($r['status'] ?? '') === 'empty'));
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
        $contractDebt = [];
        foreach ($contracts as $c) {
            $cid = $c['id'] ?? '';
            $stmt = $this->pdo->prepare('SELECT SUM(CASE WHEN status = \'overdue\' THEN 1 ELSE 0 END) AS overdue_cnt, SUM(CASE WHEN status = \'pending\' THEN 1 ELSE 0 END) AS pending_cnt FROM payments WHERE contract_id = ? AND deleted_at IS NULL');
            $stmt->execute([$cid]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $contractDebt[$cid] = ['overdue' => (int)($row['overdue_cnt'] ?? 0), 'pending' => (int)($row['pending_cnt'] ?? 0)];
        }
        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        $rooms = $roomsEmpty; // modalda sadece boş odalar
        require __DIR__ . '/../../views/contracts/index.php';
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
            'vehicle_plate' => trim($_POST['vehicle_plate'] ?? '') ?: null,
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

    private static function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
