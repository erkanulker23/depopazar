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
        $search = isset($_GET['q']) ? trim($_GET['q']) : null;
        $search = $search !== '' ? $search : null;
        $perPage = 50;
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $offset = ($page - 1) * $perPage;

        if ($companyId) {
            $contractsTotal = Contract::count($this->pdo, $companyId, $statusFilter, $debtFilter, $search);
            $contracts = Contract::findAll($this->pdo, $companyId, $statusFilter, $debtFilter, $perPage, $offset, $search);
            $warehouses = Warehouse::findAll($this->pdo, $companyId);
            $customers = Customer::findAll($this->pdo, $companyId, null, 500);
            $customersTotal = Customer::count($this->pdo, $companyId);
        } elseif (($user['role'] ?? '') === 'super_admin') {
            $contractsTotal = Contract::count($this->pdo, null, $statusFilter, $debtFilter, $search);
            $contracts = Contract::findAll($this->pdo, null, $statusFilter, $debtFilter, $perPage, $offset, $search);
            $warehouses = Warehouse::findAll($this->pdo, null);
            $customers = Customer::findAll($this->pdo, null, null, 500);
            $customersTotal = Customer::count($this->pdo, null);
        } else {
            $contractsTotal = 0;
            $contracts = $warehouses = $customers = [];
            $customersTotal = 0;
        }
        $totalPages = $contractsTotal > 0 ? (int) ceil($contractsTotal / $perPage) : 1;
        if ($page > $totalPages && $contractsTotal > 0) {
            $params = $_GET;
            $params['page'] = $totalPages;
            header('Location: /girisler?' . http_build_query($params));
            exit;
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
        $personnel = [];
        $owners = [];
        if ($companyId) {
            if (Personnel::tableExists($this->pdo)) {
                $personnel = Personnel::findActiveForCompany($this->pdo, $companyId);
            }
            $stmt = $this->pdo->prepare("SELECT id, first_name, last_name FROM users WHERE company_id = ? AND deleted_at IS NULL AND role = 'company_owner' AND is_active = 1 ORDER BY first_name, last_name");
            $stmt->execute([$companyId]);
            $owners = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        $openNewSale = isset($_GET['newSale']) && $_GET['newSale'] !== '0';
        $newCustomerId = isset($_GET['newCustomerId']) ? trim($_GET['newCustomerId']) : '';
        $contractDebt = $this->getContractDebtCounts($contracts);
        ['success' => $flashSuccess, 'error' => $flashError] = Auth::consumeFlash();
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
            "SELECT contract_id,
                SUM(CASE WHEN status IN ('pending','overdue') AND due_date IS NOT NULL AND DATE(due_date) < CURDATE() THEN 1 ELSE 0 END) AS overdue_cnt,
                SUM(CASE WHEN status IN ('pending','overdue') AND (due_date IS NULL OR DATE(due_date) >= CURDATE()) THEN 1 ELSE 0 END) AS pending_cnt
             FROM payments WHERE contract_id IN ($placeholders) AND deleted_at IS NULL GROUP BY contract_id"
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
            Auth::setSession('flash_error', 'Şirket bilgisi gerekli.');
            header('Location: /girisler');
            exit;
        }
        $customerId = trim($_POST['customer_id'] ?? '');
        $roomId = trim($_POST['room_id'] ?? '');
        $startDate = trim($_POST['start_date'] ?? '');
        $endDate = trim($_POST['end_date'] ?? '');
        if (!$customerId || !$roomId || !$startDate || !$endDate) {
            Auth::setSession('flash_error', 'Müşteri, oda ve tarihler zorunludur.');
            header('Location: /girisler');
            exit;
        }
        $room = Room::findOne($this->pdo, $roomId);
        if (!$room) {
            Auth::setSession('flash_error', 'Geçersiz oda.');
            header('Location: /girisler');
            exit;
        }
        $customer = null;
        $stmt = $this->pdo->prepare('SELECT id, company_id FROM customers WHERE id = ? AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([$customerId]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$customer) {
            Auth::setSession('flash_error', 'Geçersiz müşteri.');
            header('Location: /girisler');
            exit;
        }
        if ($user['role'] !== 'super_admin' && $companyId && $room['company_id'] !== $companyId) {
            Auth::setSession('flash_error', 'Bu odaya erişim yetkiniz yok.');
            header('Location: /girisler');
            exit;
        }
        $needsAdditionalRooms = isset($_POST['needs_additional_rooms']) && $_POST['needs_additional_rooms'] === '1';
        $additionalRoomIds = isset($_POST['additional_room_ids']) && is_array($_POST['additional_room_ids'])
            ? array_values(array_filter(array_map('trim', $_POST['additional_room_ids'])))
            : [];
        $additionalMonthlyPrices = isset($_POST['additional_monthly_prices']) && is_array($_POST['additional_monthly_prices'])
            ? array_values($_POST['additional_monthly_prices'])
            : [];
        $additionalRooms = [];
        if ($needsAdditionalRooms) {
            if ($additionalRoomIds === []) {
                Auth::setSession('flash_error', 'En az bir ek oda seçilmelidir.');
                header('Location: /girisler?newSale=1');
                exit;
            }
            if (count($additionalRoomIds) !== count($additionalMonthlyPrices)) {
                Auth::setSession('flash_error', 'Ek odalar için aylık ücret bilgisi eksik.');
                header('Location: /girisler?newSale=1');
                exit;
            }
            $allRoomIds = array_merge([$roomId], $additionalRoomIds);
            if (count($allRoomIds) !== count(array_unique($allRoomIds))) {
                Auth::setSession('flash_error', 'Aynı oda birden fazla kez seçilemez.');
                header('Location: /girisler?newSale=1');
                exit;
            }
            foreach ($additionalRoomIds as $i => $extraRoomId) {
                $extraRoom = Room::findOne($this->pdo, $extraRoomId);
                if (!$extraRoom) {
                    Auth::setSession('flash_error', 'Geçersiz ek oda seçimi.');
                    header('Location: /girisler?newSale=1');
                    exit;
                }
                if (($extraRoom['warehouse_id'] ?? '') !== ($room['warehouse_id'] ?? '')) {
                    Auth::setSession('flash_error', 'Ek odalar, seçilen depodaki odalardan olmalıdır.');
                    header('Location: /girisler?newSale=1');
                    exit;
                }
                if ($user['role'] !== 'super_admin' && $companyId && ($extraRoom['company_id'] ?? '') !== $companyId) {
                    Auth::setSession('flash_error', 'Seçilen ek odaya erişim yetkiniz yok.');
                    header('Location: /girisler');
                    exit;
                }
                $extraPriceRaw = $additionalMonthlyPrices[$i] ?? '';
                $extraMonthlyPrice = $extraPriceRaw !== ''
                    ? (float) str_replace(',', '.', (string) $extraPriceRaw)
                    : (float) ($extraRoom['monthly_price'] ?? 0);
                if ($extraMonthlyPrice <= 0) {
                    Auth::setSession('flash_error', 'Ek oda aylık ücreti geçerli olmalıdır.');
                    header('Location: /girisler?newSale=1');
                    exit;
                }
                $additionalRooms[] = [
                    'room' => $extraRoom,
                    'room_id' => $extraRoomId,
                    'monthly_price' => $extraMonthlyPrice,
                ];
            }
        }
        [$storedCondition, $storedConditionNote, $storedConditionError] = parseStoredItemsConditionFromRequest($_POST, true);
        if ($storedConditionError) {
            Auth::setSession('flash_error', $storedConditionError);
            header('Location: /girisler?newSale=1');
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
        $pickupLocation = $this->resolvePickupLocation($_POST);
        $monthlyPricesPost = isset($_POST['monthly_prices']) && is_array($_POST['monthly_prices']) ? $_POST['monthly_prices'] : [];
        $sharedContractFields = [
            'customer_id' => $customerId,
            'start_date' => $startDate . ' 00:00:00',
            'end_date' => $endDate . ' 23:59:59',
            'sold_by_user_id' => $soldBy,
            'pickup_location' => $pickupLocation,
            'driver_name' => trim($_POST['driver_name'] ?? '') ?: null,
            'driver_phone' => trim($_POST['driver_phone'] ?? '') ?: null,
            'vehicle_plate' => $vehiclePlate ?: null,
            'notes' => trim($_POST['notes'] ?? '') ?: null,
            'stored_items_condition' => $storedCondition,
            'stored_items_condition_note' => $storedConditionNote,
        ];
        try {
            $created = $this->createSingleSaleContract(
                $user,
                $companyId,
                $room,
                $roomId,
                $monthlyPrice,
                $monthlyPricesPost,
                $sharedContractFields,
                $transportationFee,
                $discount,
                $contractPdfUrl,
                true,
                $_POST
            );
            $createdAdditional = [];
            foreach ($additionalRooms as $extra) {
                $createdAdditional[] = $this->createSingleSaleContract(
                    $user,
                    $companyId,
                    $extra['room'],
                    $extra['room_id'],
                    $extra['monthly_price'],
                    [],
                    $sharedContractFields,
                    0,
                    0,
                    null,
                    false,
                    $_POST
                );
            }
            $contractId = $created['id'] ?? null;
            $contractNumber = $created['contract_number'] ?? $contractId ?? '';
            $actorName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
            $whId = $room['warehouse_id'] ?? null;
            Notification::createForCompanyAndWarehouse($this->pdo, $room['company_id'] ?? $companyId, $whId, 'contract', 'Sözleşme oluşturuldu', 'Sözleşme ' . $contractNumber . ' oluşturuldu.', ['contract_id' => $contractId, 'actor_name' => $actorName, 'warehouse_id' => $whId]);
            foreach ($createdAdditional as $extraCreated) {
                $numExtra = $extraCreated['contract_number'] ?? $extraCreated['id'] ?? '';
                $extraWhId = $extraCreated['warehouse_id'] ?? $whId;
                Notification::createForCompanyAndWarehouse($this->pdo, $room['company_id'] ?? $companyId, $extraWhId, 'contract', 'Sözleşme oluşturuldu', 'Sözleşme ' . $numExtra . ' oluşturuldu.', ['contract_id' => $extraCreated['id'] ?? null, 'actor_name' => $actorName, 'warehouse_id' => $extraWhId]);
            }
            $this->sendContractCreatedEmails($created);
            foreach ($createdAdditional as $extraCreated) {
                $this->sendContractCreatedEmails($extraCreated);
            }

            // Nakliye bilgisi işaretlendiyse nakliye işi oluştur (nakliye-isler listesine düşer)
            $hasTransportation = isset($_POST['has_transportation']) && $_POST['has_transportation'] === '1';
            $deliveryLocation = trim($_POST['delivery_location'] ?? '');
            if ($hasTransportation && ($pickupLocation !== null && $pickupLocation !== '' || $deliveryLocation !== '' || $transportationFee > 0)) {
                $warehouse = !empty($room['warehouse_id']) ? Warehouse::findOne($this->pdo, $room['warehouse_id']) : null;
                $deliveryAddress = $deliveryLocation;
                if ($deliveryAddress === '' && $warehouse) {
                    $parts = array_filter([$warehouse['name'] ?? '', $warehouse['address'] ?? '', $warehouse['city'] ?? '', $warehouse['district'] ?? '']);
                    $deliveryAddress = implode(', ', $parts);
                }
                if ($deliveryAddress === '' && !empty($room['warehouse_name'])) {
                    $deliveryAddress = $room['warehouse_name'];
                }
                $pickupType = trim($_POST['pickup_source_type'] ?? 'evden');
                $jobTypes = [
                    'evden' => 'Evden depo nakliyesi',
                    'ofisten' => 'Ofisten depo nakliyesi',
                    'depo' => 'Depodan depo nakliyesi',
                ];
                $transportNotes = 'Sözleşme: ' . $contractNumber;
                foreach ($createdAdditional as $extraCreated) {
                    $transportNotes .= ', ' . ($extraCreated['contract_number'] ?? $extraCreated['id'] ?? '');
                }
                try {
                    TransportationJob::create($this->pdo, [
                        'company_id' => $room['company_id'] ?? $companyId,
                        'customer_id' => $customerId,
                        'job_type' => $jobTypes[$pickupType] ?? 'Depo girişi nakliyesi',
                        'pickup_address' => $pickupLocation ?: null,
                        'delivery_address' => $deliveryAddress ?: null,
                        'price' => $transportationFee,
                        'job_date' => $startDate,
                        'status' => 'pending',
                        'vehicle_plate' => $vehiclePlate ?: null,
                        'notes' => $transportNotes,
                    ]);
                } catch (Throwable $e) {
                    // Nakliye işi oluşturulamazsa sözleşme yine başarılı sayılır
                }
            }

            $totalContracts = 1 + count($createdAdditional);
            if ($totalContracts > 1) {
                $numbers = [$contractNumber];
                foreach ($createdAdditional as $extraCreated) {
                    $numbers[] = $extraCreated['contract_number'] ?? $extraCreated['id'] ?? '';
                }
                Auth::setSession('flash_success', $totalContracts . ' sözleşme oluşturuldu (' . implode(', ', $numbers) . ').');
            } else {
                Auth::setSession('flash_success', 'Sözleşme oluşturuldu.');
            }
        } catch (Exception $e) {
            Auth::setSession('flash_error', 'Kayıt oluşturulamadı: ' . $e->getMessage());
        }
        header('Location: /girisler');
        exit;
    }

    /**
     * @param array<string, string|float|null> $sharedFields
     * @param array<string, string|float> $monthlyPricesPost
     */
    private function createSingleSaleContract(
        array $user,
        ?string $companyId,
        array $room,
        string $roomId,
        float $monthlyPrice,
        array $monthlyPricesPost,
        array $sharedFields,
        float $transportationFee,
        float $discount,
        ?string $contractPdfUrl,
        bool $attachStoredItems,
        array $post
    ): array {
        $data = array_merge($sharedFields, [
            'room_id' => $roomId,
            'monthly_price' => $monthlyPrice,
            'transportation_fee' => $transportationFee,
            'discount' => $discount,
            'contract_pdf_url' => $contractPdfUrl,
        ]);
        $created = Contract::create($this->pdo, $data);
        $contractId = $created['id'] ?? null;
        if (!$contractId) {
            throw new Exception('Sözleşme kaydı oluşturulamadı.');
        }
        $contractCompanyId = $room['company_id'] ?? $companyId;
        if (Personnel::tableExists($this->pdo) && $contractCompanyId) {
            $personnelIds = isset($post['personnel_ids']) && is_array($post['personnel_ids']) ? array_filter($post['personnel_ids']) : [];
            $personnelIds = Personnel::filterIdsForCompany($this->pdo, $personnelIds, $contractCompanyId);
            $stmtCp = $this->pdo->prepare('INSERT INTO contract_personnel (id, contract_id, personnel_id) VALUES (?, ?, ?)');
            foreach ($personnelIds as $pid) {
                $pid = trim($pid);
                if ($pid === '') {
                    continue;
                }
                $stmtCp->execute([self::uuid(), $contractId, $pid]);
            }
        }
        $startDate = substr((string) ($sharedFields['start_date'] ?? ''), 0, 10);
        $endDate = substr((string) ($sharedFields['end_date'] ?? ''), 0, 10);
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $end->modify('last day of this month');
        $stmtCmp = $this->pdo->prepare('INSERT INTO contract_monthly_prices (id, contract_id, month, price) VALUES (?, ?, ?, ?)');
        while ($start <= $end) {
            $monthStr = $start->format('Y-m');
            $priceForMonth = $monthlyPrice;
            if (isset($monthlyPricesPost[$monthStr]) && $monthlyPricesPost[$monthStr] !== '') {
                $priceForMonth = (float) str_replace(',', '.', (string) $monthlyPricesPost[$monthStr]);
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
        if ($attachStoredItems) {
            $contractItems = parseContractItemsFromRequest($post);
            if (!empty($contractItems)) {
                Item::syncForContract($this->pdo, $contractId, $roomId, $contractItems, $startDate . ' 00:00:00');
            }
        }
        return $created;
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
            Auth::setSession('flash_error', 'Sözleşme bulunamadı.');
            header('Location: /girisler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($contract['company_id'] ?? '') !== $companyId) {
            Auth::setSession('flash_error', 'Bu sözleşmeye erişim yetkiniz yok.');
            header('Location: /girisler');
            exit;
        }
        $pageTitle = 'Sözleşme Düzenle: ' . ($contract['contract_number'] ?? '');
        $items = Item::findByContractId($this->pdo, $id);
        $monthlyPricesByKey = [];
        foreach (Contract::getMonthlyPricesByContractId($this->pdo, $id) as $mp) {
            if (!empty($mp['month'])) {
                $monthlyPricesByKey[$mp['month']] = (float) ($mp['price'] ?? 0);
            }
        }
        $paidMonths = Payment::getPaidMonthsByContractId($this->pdo, $id);
        $paidAmountsByMonth = Payment::getPaidAmountsByMonthForContract($this->pdo, $id);
        foreach ($paidAmountsByMonth as $monthKey => $amount) {
            if (!isset($monthlyPricesByKey[$monthKey])) {
                $monthlyPricesByKey[$monthKey] = $amount;
            }
        }
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
            Auth::setSession('flash_error', 'Sözleşme belirtilmedi.');
            header('Location: /girisler');
            exit;
        }
        $contract = Contract::findOne($this->pdo, $id);
        if (!$contract) {
            Auth::setSession('flash_error', 'Sözleşme bulunamadı.');
            header('Location: /girisler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($contract['company_id'] ?? '') !== $companyId) {
            Auth::setSession('flash_error', 'Bu sözleşmeye erişim yetkiniz yok.');
            header('Location: /girisler');
            exit;
        }
        $startDate = trim($_POST['start_date'] ?? '') ?: null;
        $endDate = trim($_POST['end_date'] ?? '') ?: null;
        if ($startDate) $startDate .= ' 00:00:00';
        if ($endDate) $endDate .= ' 23:59:59';
        [$storedCondition, $storedConditionNote, $storedConditionError] = parseStoredItemsConditionFromRequest($_POST, true);
        if ($storedConditionError) {
            Auth::setSession('flash_error', $storedConditionError);
            header('Location: /girisler/' . $id . '/duzenle');
            exit;
        }
        $monthlyPrice = isset($_POST['monthly_price']) && $_POST['monthly_price'] !== ''
            ? (float) str_replace(',', '.', $_POST['monthly_price'])
            : (float) ($contract['monthly_price'] ?? 0);
        try {
            Contract::update($this->pdo, $id, [
                'start_date' => $startDate ?? $contract['start_date'],
                'end_date' => $endDate ?? $contract['end_date'],
                'monthly_price' => $monthlyPrice,
                'transportation_fee' => isset($_POST['transportation_fee']) && $_POST['transportation_fee'] !== '' ? (float) str_replace(',', '.', $_POST['transportation_fee']) : 0,
                'pickup_location' => trim($_POST['pickup_location'] ?? '') ?: null,
                'discount' => isset($_POST['discount']) && $_POST['discount'] !== '' ? (float) str_replace(',', '.', $_POST['discount']) : 0,
                'driver_name' => trim($_POST['driver_name'] ?? '') ?: null,
                'driver_phone' => trim($_POST['driver_phone'] ?? '') ?: null,
                'vehicle_plate' => trim($_POST['vehicle_plate'] ?? '') ?: null,
                'notes' => trim($_POST['notes'] ?? '') ?: null,
                'stored_items_condition' => $storedCondition,
                'stored_items_condition_note' => $storedConditionNote,
            ]);
            $monthlyPricesPost = isset($_POST['monthly_prices']) && is_array($_POST['monthly_prices']) ? $_POST['monthly_prices'] : [];
            $paidMonths = Payment::getPaidMonthsByContractId($this->pdo, $id);
            $paidAmountsByMonth = Payment::getPaidAmountsByMonthForContract($this->pdo, $id);
            $existingMonthlyByKey = [];
            foreach (Contract::getMonthlyPricesByContractId($this->pdo, $id) as $mp) {
                if (!empty($mp['month'])) {
                    $existingMonthlyByKey[$mp['month']] = (float) ($mp['price'] ?? 0);
                }
            }
            $monthLabels = ['01'=>'Ocak','02'=>'Şubat','03'=>'Mart','04'=>'Nisan','05'=>'Mayıs','06'=>'Haziran','07'=>'Temmuz','08'=>'Ağustos','09'=>'Eylül','10'=>'Ekim','11'=>'Kasım','12'=>'Aralık'];
            foreach ($paidMonths as $monthStr) {
                if (!array_key_exists($monthStr, $monthlyPricesPost)) {
                    continue;
                }
                $posted = trim((string) $monthlyPricesPost[$monthStr]);
                if ($posted === '') {
                    continue;
                }
                $newPrice = (float) str_replace(',', '.', $posted);
                $oldPrice = $existingMonthlyByKey[$monthStr]
                    ?? $paidAmountsByMonth[$monthStr]
                    ?? (float) ($contract['monthly_price'] ?? 0);
                if (abs($newPrice - $oldPrice) > 0.009) {
                    $parts = explode('-', $monthStr);
                    $label = (isset($parts[1], $monthLabels[$parts[1]]) ? $monthLabels[$parts[1]] . ' ' . ($parts[0] ?? '') : $monthStr);
                    Auth::setSession('flash_error', $label . ' ayı için ödeme alındığından oda fiyatı değiştirilemez.');
                    header('Location: /girisler/' . $id . '/duzenle');
                    exit;
                }
            }
            Contract::syncMonthlyPrices(
                $this->pdo,
                $id,
                $startDate ?? $contract['start_date'],
                $endDate ?? $contract['end_date'],
                $monthlyPrice,
                $monthlyPricesPost
            );
            $roomId = $contract['room_id'] ?? '';
            if ($roomId) {
                $storedAt = ($startDate ?? $contract['start_date'] ?? null) ?: date('Y-m-d H:i:s');
                Item::syncForContract($this->pdo, $id, $roomId, parseContractItemsFromRequest($_POST), $storedAt);
            }
            Auth::setSession('flash_success', 'Sözleşme güncellendi.');
        } catch (Exception $e) {
            Auth::setSession('flash_error', 'Güncellenemedi: ' . $e->getMessage());
        }
        header('Location: /girisler/' . $id);
        exit;
    }

    /** Sözleşme detayından eşya listesi güncelleme */
    public function updateItems(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /girisler');
            exit;
        }
        $id = trim($_POST['contract_id'] ?? '');
        if (!$id) {
            Auth::setSession('flash_error', 'Sözleşme belirtilmedi.');
            header('Location: /girisler');
            exit;
        }
        $contract = Contract::findOne($this->pdo, $id);
        if (!$contract) {
            Auth::setSession('flash_error', 'Sözleşme bulunamadı.');
            header('Location: /girisler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($contract['company_id'] ?? '') !== $companyId) {
            Auth::setSession('flash_error', 'Bu sözleşmeye erişim yetkiniz yok.');
            header('Location: /girisler');
            exit;
        }
        $roomId = $contract['room_id'] ?? '';
        if (!$roomId) {
            Auth::setSession('flash_error', 'Sözleşmeye bağlı oda bulunamadı.');
            header('Location: /girisler/' . $id);
            exit;
        }
        try {
            $storedAt = $contract['start_date'] ?? date('Y-m-d H:i:s');
            Item::syncForContract($this->pdo, $id, $roomId, parseContractItemsFromRequest($_POST), $storedAt);
            Auth::setSession('flash_success', 'Eşya listesi kaydedildi.');
        } catch (Exception $e) {
            Auth::setSession('flash_error', 'Eşya listesi kaydedilemedi: ' . $e->getMessage());
        }
        header('Location: /girisler/' . $id);
        exit;
    }

    /** Sözleşme detayından ödeme takvimi tutarı güncelle (AJAX) */
    public function updatePaymentAmount(): void
    {
        Auth::requireStaff();
        header('Content-Type: application/json; charset=utf-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'Geçersiz istek.']);
            exit;
        }
        $paymentId = trim($_POST['payment_id'] ?? '');
        $contractId = trim($_POST['contract_id'] ?? '');
        $amountRaw = trim(str_replace([' ', '₺'], '', $_POST['amount'] ?? ''));
        if (str_contains($amountRaw, ',')) {
            $amountRaw = str_replace('.', '', $amountRaw);
            $amountRaw = str_replace(',', '.', $amountRaw);
        }
        if ($paymentId === '' || $contractId === '') {
            echo json_encode(['ok' => false, 'error' => 'Eksik bilgi.']);
            exit;
        }
        if ($amountRaw === '' || !is_numeric($amountRaw)) {
            echo json_encode(['ok' => false, 'error' => 'Geçerli bir tutar girin.']);
            exit;
        }
        $amount = (float) $amountRaw;
        if ($amount <= 0) {
            echo json_encode(['ok' => false, 'error' => 'Tutar 0\'dan büyük olmalı.']);
            exit;
        }
        $contract = Contract::findOne($this->pdo, $contractId);
        if (!$contract) {
            echo json_encode(['ok' => false, 'error' => 'Sözleşme bulunamadı.']);
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($contract['company_id'] ?? '') !== $companyId) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Bu sözleşmeye erişim yetkiniz yok.']);
            exit;
        }
        $payment = Payment::findOne($this->pdo, $paymentId);
        if (!$payment || ($payment['contract_id'] ?? '') !== $contractId) {
            echo json_encode(['ok' => false, 'error' => 'Ödeme kaydı bulunamadı.']);
            exit;
        }
        if (($payment['status'] ?? '') === 'paid') {
            echo json_encode(['ok' => false, 'error' => 'Ödemesi alınmış ayların tutarı değiştirilemez.']);
            exit;
        }
        if (($payment['status'] ?? '') === 'cancelled') {
            echo json_encode(['ok' => false, 'error' => 'İptal edilmiş ödeme düzenlenemez.']);
            exit;
        }
        if (!Payment::updatePendingAmount($this->pdo, $paymentId, $amount)) {
            echo json_encode(['ok' => false, 'error' => 'Tutar güncellenemedi.']);
            exit;
        }
        $monthYm = !empty($payment['due_date']) ? date('Y-m', strtotime($payment['due_date'])) : '';
        if ($monthYm !== '') {
            Contract::setMonthlyPriceForMonth($this->pdo, $contractId, $monthYm, $amount);
        }
        echo json_encode([
            'ok' => true,
            'amount' => $amount,
            'formatted' => fmtPrice($amount),
            'month_key' => $monthYm,
        ]);
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
            Auth::setSession('flash_error', 'Sözleşme bulunamadı.');
            header('Location: /girisler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($contract['company_id'] ?? '') !== $companyId) {
            Auth::setSession('flash_error', 'Bu sözleşmeye erişim yetkiniz yok.');
            header('Location: /girisler');
            exit;
        }
        if (empty($contract['terminated_at'])) {
            Contract::ensurePaymentsForContract($this->pdo, $id);
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
                    $mp['month_key'] = $ym;
                    $mp['month'] = ($monthNames[$m[2]] ?? $m[2]) . ' ' . $m[1];
                }
            }
            unset($mp);
        }
        $company = !empty($contract['company_id']) ? Company::findOne($this->pdo, $contract['company_id']) : null;
        $collectPayments = array_values(array_filter($payments, fn($p) => paymentIsCollectible($p)));
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
        $items = Item::findByContractId($this->pdo, $id);
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
            Auth::setSession('flash_error', 'Sözleşme bulunamadı.');
            header('Location: /girisler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($contract['company_id'] ?? '') !== $companyId) {
            Auth::setSession('flash_error', 'Bu sözleşmeye erişim yetkiniz yok.');
            header('Location: /girisler');
            exit;
        }
        $payments = Payment::findByContractId($this->pdo, $id);
        $company = !empty($contract['company_id']) ? Company::findOne($this->pdo, $contract['company_id']) : null;
        $soldByName = trim(($contract['sold_by_first_name'] ?? '') . ' ' . ($contract['sold_by_last_name'] ?? '')) ?: '-';
        $items = Item::findByContractId($this->pdo, $id);
        require __DIR__ . '/../../views/contracts/print.php';
    }

    /** Sözleşme PDF indir – yüklenmiş PDF veya otomatik oluşturulan belge */
    public function downloadPdf(array $params): void
    {
        Auth::requireStaff();
        $id = $params['id'] ?? '';
        if (!$id) {
            header('Location: /girisler');
            exit;
        }
        $contract = Contract::findOne($this->pdo, $id);
        if (!$contract) {
            Auth::setSession('flash_error', 'Sözleşme bulunamadı.');
            header('Location: /girisler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($contract['company_id'] ?? '') !== $companyId) {
            Auth::setSession('flash_error', 'Bu sözleşmeye erişim yetkiniz yok.');
            header('Location: /girisler');
            exit;
        }
        ContractPdf::sendDownload($this->pdo, $contract);
        exit;
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
            Auth::setSession('flash_error', 'Sözleşme bulunamadı.');
            header('Location: /girisler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($contract['company_id'] ?? '') !== $companyId) {
            Auth::setSession('flash_error', 'Yetkisiz.');
            header('Location: /girisler');
            exit;
        }
        Contract::setActive($this->pdo, $id, 0);
        $roomId = $contract['room_id'] ?? null;
        if ($roomId) {
            Room::update($this->pdo, $roomId, ['status' => 'empty']);
        }
        $actorName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        $contractNumber = $contract['contract_number'] ?? $id;
        $whId = $contract['warehouse_id'] ?? null;
        Notification::createForCompanyAndWarehouse(
            $this->pdo,
            $contract['company_id'] ?? null,
            $whId,
            'contract',
            'Sözleşme sonlandırıldı',
            'Sözleşme ' . $contractNumber . ' sonlandırıldı.',
            ['contract_id' => $id, 'actor_name' => $actorName, 'warehouse_id' => $whId]
        );
        Auth::setSession('flash_success', 'Sözleşme sonlandırıldı.');
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
            Auth::setSession('flash_error', 'Sözleşme bulunamadı.');
            header('Location: /girisler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($contract['company_id'] ?? '') !== $companyId) {
            Auth::setSession('flash_error', 'Bu sözleşmeye erişim yetkiniz yok.');
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
            Auth::setSession('flash_error', 'Sözleşme seçilmedi.');
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
            $whId = $contract['warehouse_id'] ?? null;
            Contract::hardDelete($this->pdo, $id);
            if ($roomId) Room::update($this->pdo, $roomId, ['status' => 'empty']);
            Notification::createForCompanyAndWarehouse($this->pdo, $contract['company_id'] ?? null, $whId, 'contract', 'Sözleşme silindi', 'Sözleşme ' . $contractNumber . ' silindi.', ['contract_id' => $id, 'warehouse_id' => $whId]);
            $deleted++;
        }
        if ($deleted > 0) {
            Auth::setSession('flash_success', $deleted === 1 ? 'Sözleşme silindi.' : $deleted . ' sözleşme silindi.');
        } else {
            Auth::setSession('flash_error', 'Silinecek sözleşme bulunamadı veya yetkiniz yok.');
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

        $user = Auth::user();
        $actorName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        $emailContext = [
            'actor_name' => $actorName,
            'acted_at' => $createdAt ?? date('Y-m-d H:i:s'),
            'action_title' => 'Sözleşme oluşturuldu',
        ];

        if (!empty($mail['notify_customer_on_contract'])) {
            $customerEmail = trim($contract['customer_email'] ?? '');
            if ($customerEmail !== '' && filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
                $bodyPlain = str_replace(array_keys($replace), array_values($replace), $tplCustomer);
                MailService::sendTemplated(
                    $mail,
                    $customerEmail,
                    $appName . ' – Sözleşme Oluşturuldu',
                    'Sözleşme Oluşturuldu',
                    $bodyPlain,
                    'Sözleşme No: ' . $sozlesmeNo,
                    $emailContext
                );
            }
        }

        if (!empty($mail['notify_admin_on_contract'])) {
            $staff = User::findStaff($this->pdo, $companyId);
            $adminBodyPlain = str_replace(array_keys($replace), array_values($replace), $tplAdmin);
            $adminContext = array_merge($emailContext, ['action_title' => 'Yeni sözleşme bildirimi']);
            foreach ($staff as $u) {
                $email = trim($u['email'] ?? '');
                if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    MailService::sendTemplated(
                        $mail,
                        $email,
                        $appName . ' – Yeni sözleşme: ' . $sozlesmeNo,
                        'Yeni Sözleşme Bildirimi',
                        $adminBodyPlain,
                        $sozlesmeNo . ' – ' . $musteriAdi,
                        $adminContext
                    );
                }
            }
        }
    }

    private function resolvePickupLocation(array $post): ?string
    {
        $composed = trim($post['pickup_location'] ?? '');
        if ($composed !== '') {
            return $composed;
        }
        $type = trim($post['pickup_source_type'] ?? 'evden');
        if ($type === 'depo') {
            $whId = trim($post['pickup_warehouse_id'] ?? '');
            if ($whId === '') {
                return null;
            }
            $wh = Warehouse::findOne($this->pdo, $whId);
            if (!$wh) {
                return null;
            }
            $parts = array_filter([$wh['name'] ?? '', $wh['address'] ?? '', $wh['district'] ?? '', $wh['city'] ?? '']);
            return 'Depo: ' . implode(', ', $parts);
        }
        $detail = trim($post['pickup_address_detail'] ?? '');
        if ($detail === '') {
            return null;
        }
        $label = $type === 'ofisten' ? 'Ofisten' : 'Evden';
        return $label . ': ' . $detail;
    }

    private static function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
