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
        $staffOptions = $this->loadContractStaffOptions($companyId ?: null);
        $personnel = $staffOptions['personnel'];
        $owners = $this->ensureSoldByInOwnersList($staffOptions['owners'], $user['id'] ?? null);
        $defaultSoldByUserId = $user['id'] ?? '';
        $openNewSale = ($_GET['newSale'] ?? '') === '1';
        $newCustomerId = isset($_GET['newCustomerId']) ? trim($_GET['newCustomerId']) : '';
        $contractDebt = $this->getContractDebtCounts($contracts);
        ['success' => $flashSuccess, 'error' => $flashError] = Auth::consumeFlash();
        $rooms = $roomsEmpty;
        require __DIR__ . '/../../views/contracts/index.php';
    }

    /** Birden fazla sözleşme için dönem bazlı borç sayıları (liste rozetleri). */
    private function getContractDebtCounts(array $contracts): array
    {
        if (empty($contracts)) {
            return [];
        }
        $result = computeDebtsForContracts($this->pdo, $contracts)['by_contract'];
        $counts = [];
        foreach ($contracts as $contract) {
            $id = (string) ($contract['id'] ?? '');
            if ($id === '') {
                continue;
            }
            $summary = $result[$id] ?? [];
            $overdueCnt = (int) ($summary['overdue_period_count'] ?? 0);
            $pendingCnt = max(0, (int) ($summary['unpaid_period_count'] ?? 0) - $overdueCnt);
            $counts[$id] = ['overdue' => $overdueCnt, 'pending' => $pendingCnt];
        }
        return $counts;
    }

    public function create(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /girisler');
            exit;
        }
        $redirectTargets = $this->contractCreateRedirectTargets();
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if (!$companyId && ($user['role'] ?? '') !== 'super_admin') {
            Auth::setSession('flash_error', 'Şirket bilgisi gerekli.');
            header('Location: ' . $redirectTargets['generic']);
            exit;
        }
        $customerId = trim($_POST['customer_id'] ?? '');
        $roomId = trim($_POST['room_id'] ?? '');
        $startDate = trim($_POST['start_date'] ?? '');
        $endDate = trim($_POST['end_date'] ?? '');
        if (!$customerId || !$roomId || !$startDate || !$endDate) {
            Auth::setSession('flash_error', 'Müşteri, oda ve tarihler zorunludur.');
            header('Location: ' . $redirectTargets['error']);
            exit;
        }
        $room = Room::findOne($this->pdo, $roomId);
        if (!$room) {
            Auth::setSession('flash_error', 'Geçersiz oda.');
            header('Location: ' . $redirectTargets['error']);
            exit;
        }
        $customer = null;
        $stmt = $this->pdo->prepare('SELECT id, company_id FROM customers WHERE id = ? AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([$customerId]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$customer) {
            Auth::setSession('flash_error', 'Geçersiz müşteri.');
            header('Location: ' . $redirectTargets['error']);
            exit;
        }
        if ($user['role'] !== 'super_admin' && $companyId && $room['company_id'] !== $companyId) {
            Auth::setSession('flash_error', 'Bu odaya erişim yetkiniz yok.');
            header('Location: ' . $redirectTargets['error']);
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
                header('Location: ' . $redirectTargets['error']);
                exit;
            }
            if (count($additionalRoomIds) !== count($additionalMonthlyPrices)) {
                Auth::setSession('flash_error', 'Ek odalar için aylık ücret bilgisi eksik.');
                header('Location: ' . $redirectTargets['error']);
                exit;
            }
            $allRoomIds = array_merge([$roomId], $additionalRoomIds);
            if (count($allRoomIds) !== count(array_unique($allRoomIds))) {
                Auth::setSession('flash_error', 'Aynı oda birden fazla kez seçilemez.');
                header('Location: ' . $redirectTargets['error']);
                exit;
            }
            foreach ($additionalRoomIds as $i => $extraRoomId) {
                $extraRoom = Room::findOne($this->pdo, $extraRoomId);
                if (!$extraRoom) {
                    Auth::setSession('flash_error', 'Geçersiz ek oda seçimi.');
                    header('Location: ' . $redirectTargets['error']);
                    exit;
                }
                if (($extraRoom['warehouse_id'] ?? '') !== ($room['warehouse_id'] ?? '')) {
                    Auth::setSession('flash_error', 'Ek odalar, seçilen depodaki odalardan olmalıdır.');
                    header('Location: ' . $redirectTargets['error']);
                    exit;
                }
                if ($user['role'] !== 'super_admin' && $companyId && ($extraRoom['company_id'] ?? '') !== $companyId) {
                    Auth::setSession('flash_error', 'Seçilen ek odaya erişim yetkiniz yok.');
                    header('Location: ' . $redirectTargets['error']);
                    exit;
                }
                $extraPriceRaw = $additionalMonthlyPrices[$i] ?? '';
                $extraMonthlyPrice = $extraPriceRaw !== ''
                    ? parseMoneyInput($extraPriceRaw)
                    : (float) ($extraRoom['monthly_price'] ?? 0);
                if ($extraMonthlyPrice <= 0) {
                    Auth::setSession('flash_error', 'Ek oda aylık ücreti geçerli olmalıdır.');
                    header('Location: ' . $redirectTargets['error']);
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
            header('Location: ' . $redirectTargets['error']);
            exit;
        }
        $monthlyPrice = isset($_POST['monthly_price']) && $_POST['monthly_price'] !== '' ? parseMoneyInput($_POST['monthly_price']) : (float) $room['monthly_price'];
        $transportationFee = isset($_POST['transportation_fee']) && $_POST['transportation_fee'] !== '' ? parseMoneyInput($_POST['transportation_fee']) : 0;
        $discount = isset($_POST['discount']) && $_POST['discount'] !== '' ? parseMoneyInput($_POST['discount']) : 0;
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
        $contractPdfUrl = storeContractPdfUpload($_FILES['contract_pdf'] ?? null);
        $pickupLocation = $this->resolvePickupLocation($_POST);
        $monthlyPricesPost = isset($_POST['monthly_prices']) && is_array($_POST['monthly_prices']) ? $_POST['monthly_prices'] : [];
        $campaignCode = null;
        $campaignError = $this->applyCampaignFromRequest($_POST, $startDate, $endDate, $monthlyPrice, $monthlyPricesPost, $campaignCode);
        if ($campaignError !== null) {
            Auth::setSession('flash_error', $campaignError);
            header('Location: ' . $redirectTargets['error']);
            exit;
        }
        $roomContractMode = trim($_POST['room_contract_mode'] ?? 'separate');
        if (!in_array($roomContractMode, ['separate', 'combined'], true)) {
            $roomContractMode = 'separate';
        }
        if (!$needsAdditionalRooms || $additionalRooms === []) {
            $roomContractMode = 'separate';
        }
        $roomKeyParts = array_merge([$roomId], array_column($additionalRooms, 'room_id'));
        sort($roomKeyParts, SORT_STRING);
        $contractDedupeKey = hash('sha256', ($companyId ?? '') . '|' . $customerId . '|' . implode(',', $roomKeyParts) . '|' . $startDate . '|' . $endDate . '|' . $roomContractMode);
        $contractLockName = 'contract_create:' . substr($contractDedupeKey, 0, 40);
        $contractLockAcquired = db_request_lock($this->pdo, $contractLockName, 10);
        $lastContractCreate = request_dedupe_hit('contract_create', $contractDedupeKey, 30);
        if ($lastContractCreate !== null && !empty($lastContractCreate['done'])) {
            if ($contractLockAcquired) {
                db_request_unlock($this->pdo, $contractLockName);
            }
            header('Location: ' . $redirectTargets['success']);
            exit;
        }
        request_dedupe_store('contract_create', $contractDedupeKey, ['pending' => true]);
        $userNotes = trim($_POST['notes'] ?? '') ?: null;
        if ($roomContractMode === 'combined') {
            $linkedNoteParts = [];
            foreach ($additionalRooms as $extra) {
                $rn = preg_replace('/\s*\([^)]*\)\s*$/', '', (string) ($extra['room']['room_number'] ?? ''));
                $linkedNoteParts[] = $rn !== '' ? $rn : ($extra['room_id'] ?? '');
            }
            if ($linkedNoteParts !== []) {
                $suffix = 'Bağlı odalar (tek sözleşme): ' . implode(', ', $linkedNoteParts);
                $userNotes = $userNotes ? ($userNotes . "\n" . $suffix) : $suffix;
            }
            $combinedMonthly = $monthlyPrice;
            foreach ($additionalRooms as $extra) {
                $combinedMonthly += (float) ($extra['monthly_price'] ?? 0);
            }
            $monthlyPrice = $combinedMonthly;
        }
        $sharedContractFields = [
            'customer_id' => $customerId,
            'start_date' => $startDate . ' 00:00:00',
            'end_date' => $endDate . ' 23:59:59',
            'sold_by_user_id' => $soldBy,
            'pickup_location' => $pickupLocation,
            'driver_name' => trim($_POST['driver_name'] ?? '') ?: null,
            'driver_phone' => trim($_POST['driver_phone'] ?? '') ?: null,
            'vehicle_plate' => $vehiclePlate ?: null,
            'notes' => $userNotes,
            'stored_items_condition' => $storedCondition,
            'stored_items_condition_note' => $storedConditionNote,
            'campaign_code' => $campaignCode,
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
            if ($roomContractMode === 'combined') {
                $contractId = $created['id'] ?? null;
                if ($contractId) {
                    $linkedRows = [];
                    foreach ($additionalRooms as $extra) {
                        $linkedRows[] = [
                            'room_id' => $extra['room_id'],
                            'monthly_price' => $extra['monthly_price'],
                        ];
                        Room::update($this->pdo, $extra['room_id'], ['status' => 'occupied']);
                    }
                    Contract::syncLinkedRooms($this->pdo, $contractId, $linkedRows);
                }
            } else {
                foreach ($additionalRooms as $extra) {
                    $extraFields = $sharedContractFields;
                    $extraFields['campaign_code'] = null;
                    $extraFields['notes'] = trim($_POST['notes'] ?? '') ?: null;
                    $createdAdditional[] = $this->createSingleSaleContract(
                        $user,
                        $companyId,
                        $extra['room'],
                        $extra['room_id'],
                        $extra['monthly_price'],
                        [],
                        $extraFields,
                        0,
                        0,
                        null,
                        false,
                        $_POST
                    );
                }
            }
            $contractId = $created['id'] ?? null;
            $contractNumber = $created['contract_number'] ?? $contractId ?? '';
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
            if ($roomContractMode === 'combined' && count($additionalRooms) > 0) {
                Auth::setSession('flash_success', 'Tek sözleşme oluşturuldu (' . $contractNumber . ') — ' . (1 + count($additionalRooms)) . ' oda.');
            } elseif ($totalContracts > 1) {
                $numbers = [$contractNumber];
                foreach ($createdAdditional as $extraCreated) {
                    $numbers[] = $extraCreated['contract_number'] ?? $extraCreated['id'] ?? '';
                }
                Auth::setSession('flash_success', $totalContracts . ' sözleşme oluşturuldu (' . implode(', ', $numbers) . ').');
            } else {
                Auth::setSession('flash_success', 'Sözleşme oluşturuldu.');
            }
            $actorName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
            $whId = $room['warehouse_id'] ?? null;
            try {
                Notification::createForCompanyAndWarehouse($this->pdo, $room['company_id'] ?? $companyId, $whId, 'contract', 'Sözleşme oluşturuldu', 'Sözleşme ' . $contractNumber . ' oluşturuldu.', ['contract_id' => $contractId, 'actor_name' => $actorName, 'warehouse_id' => $whId]);
                foreach ($createdAdditional as $extraCreated) {
                    $numExtra = $extraCreated['contract_number'] ?? $extraCreated['id'] ?? '';
                    $extraWhId = $extraCreated['warehouse_id'] ?? $whId;
                    Notification::createForCompanyAndWarehouse($this->pdo, $room['company_id'] ?? $companyId, $extraWhId, 'contract', 'Sözleşme oluşturuldu', 'Sözleşme ' . $numExtra . ' oluşturuldu.', ['contract_id' => $extraCreated['id'] ?? null, 'actor_name' => $actorName, 'warehouse_id' => $extraWhId]);
                }
            } catch (Throwable $notifyErr) {
                error_log('Contract create notification failed: ' . $notifyErr->getMessage());
            }
        } catch (Exception $e) {
            error_log('Contract create failed: ' . $e->getMessage());
            Auth::setSession('flash_error', 'Kayıt oluşturulamadı: ' . $e->getMessage());
            header('Location: ' . $redirectTargets['error']);
            exit;
        } finally {
            if ($contractLockAcquired) {
                db_request_unlock($this->pdo, $contractLockName);
            }
        }
        request_dedupe_store('contract_create', $contractDedupeKey, ['done' => true]);
        header('Location: ' . $redirectTargets['success']);
        exit;
    }

    /** @return array{success: string, error: string, generic: string} */
    private function contractCreateRedirectTargets(): array
    {
        $redirect = trim($_POST['redirect'] ?? '');
        $fromCustomer = $redirect !== '' && preg_match('#^/musteriler/[0-9a-f\-]{36}$#i', $redirect);
        if ($fromCustomer) {
            return [
                'success' => $redirect,
                'error' => $redirect . '?addContract=1',
                'generic' => $redirect . '?addContract=1',
            ];
        }

        return [
            'success' => '/girisler',
            'error' => '/girisler?newSale=1',
            'generic' => '/girisler',
        ];
    }

    /**
     * Kampanya seçildiyse bitiş tarihini ve aylık fiyatları ayarlar.
     *
     * @param array<string, mixed> $post
     * @param array<string, string|float> $monthlyPricesPost
     */
    private function applyCampaignFromRequest(
        array $post,
        string $startDate,
        string &$endDate,
        float $monthlyPrice,
        array &$monthlyPricesPost,
        ?string &$campaignCode
    ): ?string {
        $campaignCode = null;
        $useCampaign = isset($post['use_campaign']) && $post['use_campaign'] === '1';
        if (!$useCampaign) {
            return null;
        }
        $code = trim((string) ($post['campaign_code'] ?? ''));
        if (!ContractCampaign::isValid($code)) {
            return 'Kampanya kullanmak için geçerli bir kampanya seçin.';
        }
        $campaignCode = $code;
        $endDate = ContractCampaign::endDateForCampaign($startDate, $code);
        $monthlyPricesPost = ContractCampaign::applyToMonthlyPrices($startDate, $endDate, $code, $monthlyPrice, $monthlyPricesPost);
        if (!ContractCampaign::matchesPeriodCount($startDate, $endDate, $code)) {
            return 'Seçilen kampanya ile tarih aralığı uyuşmuyor.';
        }
        return null;
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
        if ($contractCompanyId && Personnel::tableExists($this->pdo)) {
            $personnelIds = isset($post['personnel_ids']) && is_array($post['personnel_ids']) ? array_filter($post['personnel_ids']) : [];
            $personnelIds = Personnel::filterIdsForCompany($this->pdo, $personnelIds, $contractCompanyId);
            Contract::syncPersonnel($this->pdo, $contractId, $personnelIds);
        }
        $startDate = substr((string) ($sharedFields['start_date'] ?? ''), 0, 10);
        $endDate = substr((string) ($sharedFields['end_date'] ?? ''), 0, 10);
        Contract::syncMonthlyPrices(
            $this->pdo,
            $contractId,
            $startDate,
            $endDate,
            $monthlyPrice,
            $monthlyPricesPost
        );
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
        $paidMonths = Payment::getPaidPeriodKeysByContractId($this->pdo, $id);
        $paidAmountsByMonth = Payment::getPaidAmountsByPeriodForContract($this->pdo, $id);
        $contractStart = substr((string) ($contract['start_date'] ?? ''), 0, 10);
        $contractEnd = substr((string) ($contract['end_date'] ?? ''), 0, 10);
        foreach (ContractBilling::periods($contractStart, $contractEnd) as $period) {
            $periodKey = $period['key'];
            if (isset($monthlyPricesByKey[$periodKey])) {
                continue;
            }
            $paidAmount = ContractBilling::paidAmountForPeriodKey($periodKey, $paidAmountsByMonth);
            if ($paidAmount !== null) {
                $monthlyPricesByKey[$periodKey] = $paidAmount;
            }
        }
        $warehouses = [];
        $contractRoomsJson = [];
        $contractCompanyId = $contract['company_id'] ?? $companyId;
        if ($contractCompanyId) {
            $warehouses = Warehouse::findAll($this->pdo, $contractCompanyId);
        } elseif (($user['role'] ?? '') === 'super_admin') {
            $warehouses = Warehouse::findAll($this->pdo, null);
        }
        $rooms = Room::findAll($this->pdo, null);
        if ($contractCompanyId) {
            $rooms = array_values(array_filter($rooms, fn($r) => ($r['company_id'] ?? '') === $contractCompanyId));
        }
        foreach ($rooms as $r) {
            $roomNum = preg_replace('/\s*\([^)]*\)\s*$/', '', (string) ($r['room_number'] ?? ''));
            $roomPrice = isset($r['monthly_price']) && $r['monthly_price'] !== null && $r['monthly_price'] !== ''
                ? (float) $r['monthly_price'] : null;
            $contractRoomsJson[] = [
                'id' => $r['id'] ?? '',
                'warehouse_id' => $r['warehouse_id'] ?? '',
                'room_number' => $roomNum,
                'monthly_price' => $roomPrice,
                'status' => $r['status'] ?? '',
            ];
        }
        $staffOptions = $this->loadContractStaffOptions($contractCompanyId);
        $owners = $staffOptions['owners'];
        $personnel = $staffOptions['personnel'];
        $owners = $this->ensureSoldByInOwnersList($owners, $contract['sold_by_user_id'] ?? null);
        $contractPersonnelIds = Contract::getPersonnelIdsByContractId($this->pdo, $id);
        $jobTypeLabels = Personnel::jobTypeLabels();
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
            ? parseMoneyInput($_POST['monthly_price'])
            : (float) ($contract['monthly_price'] ?? 0);
        $newRoomId = trim($_POST['room_id'] ?? '') ?: ($contract['room_id'] ?? '');
        if ($newRoomId === '') {
            Auth::setSession('flash_error', 'Oda seçimi zorunludur.');
            header('Location: /girisler/' . $id . '/duzenle');
            exit;
        }
        $roomChangeError = $this->validateContractRoomChange($contract, $newRoomId, $companyId, $user);
        if ($roomChangeError !== null) {
            Auth::setSession('flash_error', $roomChangeError);
            header('Location: /girisler/' . $id . '/duzenle');
            exit;
        }
        try {
            $contractStart = substr((string) ($startDate ?? $contract['start_date'] ?? ''), 0, 10);
            $contractEnd = substr((string) ($endDate ?? $contract['end_date'] ?? ''), 0, 10);
            $monthlyPricesPost = isset($_POST['monthly_prices']) && is_array($_POST['monthly_prices']) ? $_POST['monthly_prices'] : [];
            $campaignCode = $contract['campaign_code'] ?? null;
            $campaignError = $this->applyCampaignFromRequest($_POST, $contractStart, $contractEnd, $monthlyPrice, $monthlyPricesPost, $campaignCode);
            if ($campaignError !== null) {
                Auth::setSession('flash_error', $campaignError);
                header('Location: /girisler/' . $id . '/duzenle');
                exit;
            }
            $useCampaign = isset($_POST['use_campaign']) && $_POST['use_campaign'] === '1';
            if (!$useCampaign) {
                $campaignCode = null;
            }
            if ($startDate) {
                $startDate = $contractStart . ' 00:00:00';
            }
            if ($endDate) {
                $endDate = $contractEnd . ' 23:59:59';
            }
            Contract::update($this->pdo, $id, [
                'start_date' => $startDate ?? $contract['start_date'],
                'end_date' => $endDate ?? $contract['end_date'],
                'monthly_price' => $monthlyPrice,
                'transportation_fee' => isset($_POST['transportation_fee']) && $_POST['transportation_fee'] !== '' ? parseMoneyInput($_POST['transportation_fee']) : 0,
                'pickup_location' => trim($_POST['pickup_location'] ?? '') ?: null,
                'discount' => isset($_POST['discount']) && $_POST['discount'] !== '' ? parseMoneyInput($_POST['discount']) : 0,
                'driver_name' => trim($_POST['driver_name'] ?? '') ?: null,
                'driver_phone' => trim($_POST['driver_phone'] ?? '') ?: null,
                'vehicle_plate' => trim($_POST['vehicle_plate'] ?? '') ?: null,
                'notes' => trim($_POST['notes'] ?? '') ?: null,
                'stored_items_condition' => $storedCondition,
                'stored_items_condition_note' => $storedConditionNote,
                'sold_by_user_id' => trim($_POST['sold_by_user_id'] ?? '') ?: null,
                'campaign_code' => $campaignCode,
            ]);
            $contractCompanyId = $contract['company_id'] ?? $companyId;
            if ($contractCompanyId) {
                $personnelIds = isset($_POST['personnel_ids']) && is_array($_POST['personnel_ids'])
                    ? array_filter($_POST['personnel_ids'])
                    : [];
                $personnelIds = Personnel::filterIdsForCompany($this->pdo, $personnelIds, $contractCompanyId);
                Contract::syncPersonnel($this->pdo, $id, $personnelIds);
            }
            $paidMonths = Payment::getPaidPeriodKeysByContractId($this->pdo, $id);
            $paidAmountsByMonth = Payment::getPaidAmountsByPeriodForContract($this->pdo, $id);
            $existingMonthlyByKey = [];
            foreach (Contract::getMonthlyPricesByContractId($this->pdo, $id) as $mp) {
                if (!empty($mp['month'])) {
                    $existingMonthlyByKey[$mp['month']] = (float) ($mp['price'] ?? 0);
                }
            }
            foreach ($monthlyPricesPost as $postKey => $postedRaw) {
                if (!ContractBilling::isPaidPeriodKey((string) $postKey, $paidMonths)) {
                    continue;
                }
                $posted = trim((string) $postedRaw);
                if ($posted === '') {
                    continue;
                }
                $newPrice = parseMoneyInput($posted);
                $oldPrice = $existingMonthlyByKey[$postKey]
                    ?? ContractBilling::paidAmountForPeriodKey((string) $postKey, $paidAmountsByMonth)
                    ?? (float) ($contract['monthly_price'] ?? 0);
                if (abs($newPrice - $oldPrice) > 0.009) {
                    $label = ContractBilling::formatPeriodLabel((string) $postKey);
                    Auth::setSession('flash_error', $label . ' vadesi için ödeme alındığından oda fiyatı değiştirilemez.');
                    header('Location: /girisler/' . $id . '/duzenle');
                    exit;
                }
            }
            Contract::syncMonthlyPrices(
                $this->pdo,
                $id,
                $contractStart,
                $contractEnd,
                $monthlyPrice,
                $monthlyPricesPost
            );
            $oldRoomId = $contract['room_id'] ?? '';
            if ($newRoomId !== $oldRoomId) {
                Contract::updateRoomId($this->pdo, $id, $newRoomId);
                Item::updateRoomForContract($this->pdo, $id, $newRoomId);
                $this->syncRoomOccupancyAfterContractMove($oldRoomId, $newRoomId, !empty($contract['is_active']));
            }
            $roomId = $newRoomId;
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

    /** İmzalı sözleşme PDF yükle */
    public function uploadContractPdf(): void
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
        $uploadError = validateContractPdfUpload($_FILES['contract_pdf'] ?? null);
        if ($uploadError !== null) {
            Auth::setSession('flash_error', $uploadError);
            header('Location: /girisler/' . $id);
            exit;
        }
        $url = storeContractPdfUpload($_FILES['contract_pdf']);
        if (!$url) {
            Auth::setSession('flash_error', 'PDF kaydedilemedi.');
            header('Location: /girisler/' . $id);
            exit;
        }
        Contract::setContractPdfUrl($this->pdo, $id, $url);
        Auth::setSession('flash_success', 'Sözleşme PDF yüklendi.');
        header('Location: /girisler/' . $id);
        exit;
    }

    /** Yüklenmiş imzalı sözleşme PDF sil (sistem PDF’i kullanılır) */
    public function deleteContractPdf(): void
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
        Contract::setContractPdfUrl($this->pdo, $id, null);
        Auth::setSession('flash_success', 'Yüklenen sözleşme PDF kaldırıldı.');
        header('Location: /girisler/' . $id);
        exit;
    }

    /** Sözleşme yazdır sayfasından e-imza kaydet (müşteri + firma) */
    public function saveSignatures(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /girisler');
            exit;
        }
        $id = trim($_POST['contract_id'] ?? '');
        if ($id === '') {
            $this->jsonSignatureResponse(['ok' => false, 'error' => 'Sözleşme belirtilmedi.'], 400);
        }
        $contract = Contract::findOne($this->pdo, $id);
        if (!$contract) {
            $this->jsonSignatureResponse(['ok' => false, 'error' => 'Sözleşme bulunamadı.'], 404);
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($contract['company_id'] ?? '') !== $companyId) {
            $this->jsonSignatureResponse(['ok' => false, 'error' => 'Yetkisiz.'], 403);
        }
        $customerSig = trim($_POST['customer_signature'] ?? '');
        $companySig = trim($_POST['company_signature'] ?? '');
        if ($customerSig === '' && $companySig === '') {
            $this->jsonSignatureResponse(['ok' => false, 'error' => 'En az bir imza çizilmelidir.'], 422);
        }
        if ($customerSig !== '') {
            $url = storeContractSignatureDataUri($customerSig, $id, 'customer');
            if (!$url) {
                $this->jsonSignatureResponse(['ok' => false, 'error' => 'Müşteri imzası kaydedilemedi.'], 422);
            }
            Contract::setCustomerSignature($this->pdo, $id, $url);
        }
        if ($companySig !== '') {
            $url = storeContractSignatureDataUri($companySig, $id, 'company');
            if (!$url) {
                $this->jsonSignatureResponse(['ok' => false, 'error' => 'Firma imzası kaydedilemedi.'], 422);
            }
            Contract::setCompanySignature($this->pdo, $id, $url);
        }
        $contract = Contract::findOne($this->pdo, $id);
        $this->jsonSignatureResponse([
            'ok' => true,
            'customer_signature_url' => publicUploadHref($contract['customer_signature_url'] ?? null),
            'company_signature_url' => publicUploadHref($contract['company_signature_url'] ?? null),
            'customer_signed_at' => $contract['customer_signed_at'] ?? null,
            'company_signed_at' => $contract['company_signed_at'] ?? null,
        ]);
    }

    /** @param array<string, mixed> $payload */
    private function jsonSignatureResponse(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
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
        $monthYm = ContractBilling::periodKeyFromDueDate($payment['due_date'] ?? null);
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

    /** Sözleşme detayından vadesi gelmemiş ödenmemiş tüm tutarları toplu güncelle (AJAX) */
    public function bulkUpdatePaymentAmounts(): void
    {
        Auth::requireStaff();
        header('Content-Type: application/json; charset=utf-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'Geçersiz istek.']);
            exit;
        }
        $contractId = trim($_POST['contract_id'] ?? '');
        $mode = trim($_POST['mode'] ?? 'fixed');
        $amountRaw = trim(str_replace([' ', '₺'], '', $_POST['amount'] ?? ''));
        $percentRaw = trim(str_replace([' ', '%'], '', $_POST['percent'] ?? ''));
        if (str_contains($amountRaw, ',')) {
            $amountRaw = str_replace('.', '', $amountRaw);
            $amountRaw = str_replace(',', '.', $amountRaw);
        }
        if (str_contains($percentRaw, ',')) {
            $percentRaw = str_replace('.', '', $percentRaw);
            $percentRaw = str_replace(',', '.', $percentRaw);
        }
        if ($contractId === '') {
            echo json_encode(['ok' => false, 'error' => 'Eksik bilgi.']);
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
        if ($mode === 'percent') {
            if ($percentRaw === '' || !is_numeric($percentRaw)) {
                echo json_encode(['ok' => false, 'error' => 'Geçerli bir zam oranı girin.']);
                exit;
            }
            $percent = (float) $percentRaw;
            if ($percent <= 0) {
                echo json_encode(['ok' => false, 'error' => 'Zam oranı 0\'dan büyük olmalı.']);
                exit;
            }
        } else {
            $mode = 'fixed';
            if ($amountRaw === '' || !is_numeric($amountRaw)) {
                echo json_encode(['ok' => false, 'error' => 'Geçerli bir tutar girin.']);
                exit;
            }
            $fixedAmount = (float) $amountRaw;
            if ($fixedAmount <= 0) {
                echo json_encode(['ok' => false, 'error' => 'Tutar 0\'dan büyük olmalı.']);
                exit;
            }
        }
        $payments = Payment::findByContractId($this->pdo, $contractId);
        $payments = filterPaymentsToValidContractPeriods($payments, [$contract]);
        $eligible = array_values(array_filter($payments, static fn(array $p): bool => paymentIsBulkPriceUpdatable($p)));
        if ($eligible === []) {
            echo json_encode(['ok' => false, 'error' => 'Güncellenecek vadesi gelmemiş ödeme bulunamadı.']);
            exit;
        }
        $updates = [];
        $responseItems = [];
        foreach ($eligible as $payment) {
            $current = (float) ($payment['amount'] ?? 0);
            if ($mode === 'percent') {
                $newAmount = round($current * (1 + $percent / 100), 2);
            } else {
                $newAmount = round($fixedAmount, 2);
            }
            if ($newAmount <= 0) {
                continue;
            }
            if (abs($newAmount - $current) < 0.009) {
                continue;
            }
            $paymentId = (string) ($payment['id'] ?? '');
            $monthYm = ContractBilling::periodKeyFromDueDate($payment['due_date'] ?? null);
            $updates[] = ['payment_id' => $paymentId, 'amount' => $newAmount];
            $responseItems[] = [
                'payment_id' => $paymentId,
                'amount' => $newAmount,
                'formatted' => fmtPrice($newAmount),
                'month_key' => $monthYm,
            ];
        }
        if ($updates === []) {
            echo json_encode(['ok' => false, 'error' => 'Tutarlar zaten güncel veya geçerli bir değişiklik yok.']);
            exit;
        }
        try {
            $this->pdo->beginTransaction();
            $updated = Payment::bulkUpdatePendingAmounts($this->pdo, $contractId, $updates);
            if ($updated === 0) {
                $this->pdo->rollBack();
                echo json_encode(['ok' => false, 'error' => 'Tutarlar güncellenemedi.']);
                exit;
            }
            foreach ($responseItems as $item) {
                $monthYm = $item['month_key'] ?? '';
                if ($monthYm !== '') {
                    Contract::setMonthlyPriceForMonth($this->pdo, $contractId, $monthYm, (float) $item['amount']);
                }
            }
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log('bulkUpdatePaymentAmounts failed for ' . $contractId . ': ' . $e->getMessage());
            echo json_encode(['ok' => false, 'error' => 'Güncelleme sırasında bir hata oluştu.']);
            exit;
        }
        echo json_encode([
            'ok' => true,
            'updated_count' => $updated,
            'items' => $responseItems,
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
            try {
                Contract::normalizeContractPayments($this->pdo, $id);
                Contract::ensurePaymentsForContract($this->pdo, $id);
            } catch (Throwable $e) {
                error_log('Contract show payment sync failed for ' . $id . ': ' . $e->getMessage());
            }
        }
        $payments = Payment::findByContractId($this->pdo, $id);
        $payments = filterPaymentsToValidContractPeriods($payments, [$contract]);
        $monthlyPrices = Contract::getMonthlyPricesByContractId($this->pdo, $id);
        $monthNames = ['01'=>'Ocak','02'=>'Şubat','03'=>'Mart','04'=>'Nisan','05'=>'Mayıs','06'=>'Haziran','07'=>'Temmuz','08'=>'Ağustos','09'=>'Eylül','10'=>'Ekim','11'=>'Kasım','12'=>'Aralık'];
        if (empty($monthlyPrices)) {
            foreach ($payments as $p) {
                $due = $p['due_date'] ?? '';
                if ($due) {
                    $periodKey = ContractBilling::periodKeyFromDueDate($due);
                    $monthlyPrices[] = [
                        'month' => ContractBilling::formatPeriodLabel($periodKey) . ' vadesi',
                        'month_key' => $periodKey,
                        'price' => $p['amount'] ?? $contract['monthly_price'] ?? 0,
                    ];
                }
            }
        } else {
            foreach ($monthlyPrices as &$mp) {
                $key = $mp['month'] ?? '';
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $key)) {
                    $mp['month_key'] = $key;
                    $mp['month'] = ContractBilling::formatPeriodLabel($key) . ' vadesi';
                } elseif (preg_match('/^(\d{4})-(\d{2})$/', $key, $m)) {
                    $mp['month_key'] = $key;
                    $mp['month'] = ($monthNames[$m[2]] ?? $m[2]) . ' ' . $m[1];
                }
            }
            unset($mp);
        }
        $company = !empty($contract['company_id']) ? Company::findOne($this->pdo, $contract['company_id']) : null;
        $collectPayments = array_values(array_filter($payments, fn($p) => paymentIsCollectible($p)));
        $bulkPriceUpdatablePayments = array_values(array_filter($payments, fn($p) => paymentIsBulkPriceUpdatable($p)));
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
        $fromCustomer = ($_GET['fromCustomer'] ?? '') === '1';
        $monthlyPricesByKey = [];
        foreach ($monthlyPrices as $mp) {
            $key = $mp['month_key'] ?? $mp['month'] ?? '';
            if ($key !== '') {
                $monthlyPricesByKey[$key] = (float) ($mp['price'] ?? 0);
            }
        }
        $contractDebtSummary = computeContractDebtSummary($contract, $payments, $monthlyPricesByKey);
        $contractDebtTotal = $contractDebtSummary['total'];
        $contractDebtOverdue = $contractDebtSummary['overdue'];
        $contractDebtFuture = $contractDebtSummary['future'];
        $contractPaidTotal = $contractDebtSummary['paid'];
        $contractTotalValue = $contractDebtSummary['contract_total'];
        $contractPeriodCount = $contractDebtSummary['period_count'];
        $linkedContractRooms = Contract::findLinkedRoomsByContractId($this->pdo, $id);
        require __DIR__ . '/../../views/contracts/detail.php';
    }

    /** Sözleşme yazdır – barkod gibi özel yazdırma sayfası */
    public function printPage(array $params): void
    {
        $this->renderContractDocumentPage($params, false);
    }

    /** Sözleşme e-imza – yazdır/PDF ile aynı belge üzerinde imza */
    public function signPage(array $params): void
    {
        $this->renderContractDocumentPage($params, true);
    }

    private function renderContractDocumentPage(array $params, bool $signMode): void
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
        if ($company && !empty($company['logo_url'])) {
            $company['logo_url'] = publicUploadHref($company['logo_url']);
        }
        $soldByName = trim(($contract['sold_by_first_name'] ?? '') . ' ' . ($contract['sold_by_last_name'] ?? '')) ?: '-';
        $items = Item::findByContractId($this->pdo, $id);
        $customerSignatureHref = publicUploadHref($contract['customer_signature_url'] ?? null);
        $companySignatureHref = publicUploadHref($contract['company_signature_url'] ?? null);
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
        Contract::releaseLinkedRoomsForContract($this->pdo, $id);
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
        $reason = trim($_POST['deletion_reason'] ?? '');
        if (mb_strlen($reason) < 3) {
            Auth::setSession('flash_error', 'Sözleşmeyi silmek için neden belirtmelisiniz (en az 3 karakter).');
            header('Location: ' . $this->contractDeleteRedirect($_POST));
            exit;
        }
        $ids = isset($_POST['ids']) && is_array($_POST['ids']) ? array_filter(array_map('trim', $_POST['ids'])) : [];
        if (empty($ids)) {
            $id = trim($_POST['id'] ?? '');
            if ($id !== '') {
                $ids = [$id];
            }
        }
        if (empty($ids)) {
            Auth::setSession('flash_error', 'Sözleşme seçilmedi.');
            header('Location: /girisler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        $deletedByUserId = !empty($user['id']) ? (string) $user['id'] : null;
        $deleted = 0;
        foreach ($ids as $id) {
            $contract = Contract::findOne($this->pdo, $id);
            if (!$contract) {
                continue;
            }
            if ($companyId && ($contract['company_id'] ?? '') !== $companyId) {
                continue;
            }
            $contractNumber = $contract['contract_number'] ?? $id;
            $whId = $contract['warehouse_id'] ?? null;
            if (!Contract::deleteWithReason($this->pdo, $id, $reason, $deletedByUserId)) {
                continue;
            }
            $reasonShort = mb_strlen($reason) > 120 ? mb_substr($reason, 0, 117) . '…' : $reason;
            Notification::createForCompanyAndWarehouse(
                $this->pdo,
                $contract['company_id'] ?? null,
                $whId,
                'contract',
                'Sözleşme silindi',
                'Sözleşme ' . $contractNumber . ' silindi. Neden: ' . $reasonShort,
                ['contract_id' => $id, 'warehouse_id' => $whId, 'deletion_reason' => $reason]
            );
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

    /** Ödeme vadelerini sözleşme giriş tarihine göre yeniden hizalar */
    public function restructurePaymentDueDates(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /girisler');
            exit;
        }
        $id = trim($_POST['contract_id'] ?? '');
        if ($id === '') {
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
            header('Location: /girisler/' . $id);
            exit;
        }
        if (!empty($contract['terminated_at'])) {
            Auth::setSession('flash_error', 'Sonlandırılmış sözleşmede vade yapılandırması yapılamaz.');
            header('Location: /girisler/' . $id);
            exit;
        }
        $startDateRaw = trim($_POST['start_date'] ?? '');
        $endDateRaw = trim($_POST['end_date'] ?? '');
        if ($startDateRaw === '' || $endDateRaw === '') {
            Auth::setSession('flash_error', 'Depoya giriş ve çıkış tarihleri zorunludur.');
            header('Location: /girisler/' . $id);
            exit;
        }
        if ($endDateRaw < $startDateRaw) {
            Auth::setSession('flash_error', 'Çıkış tarihi giriş tarihinden önce olamaz.');
            header('Location: /girisler/' . $id);
            exit;
        }
        $startDate = $startDateRaw . ' 00:00:00';
        $endDate = $endDateRaw . ' 23:59:59';
        $successRedirect = $this->restructureDueDatesSuccessRedirect($id, $contract, $_POST);
        $failRedirect = '/girisler/' . $id;
        if (($contract['customer_id'] ?? '') !== '' && ($_POST['return_to_customer'] ?? '') === '1') {
            $failRedirect .= '?fromCustomer=1';
        }
        try {
            Contract::updateContractDates($this->pdo, $id, $startDate, $endDate);
            Contract::ensurePaymentsForContract($this->pdo, $id);
        } catch (Throwable $e) {
            error_log('restructurePaymentDueDates failed for contract ' . $id . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            Auth::setSession('flash_error', 'Vade yapılandırması sırasında bir hata oluştu. Lütfen tekrar deneyin veya destek ile iletişime geçin.');
            header('Location: ' . $failRedirect);
            exit;
        }
        Auth::setSession('flash_success', 'Ödeme vadeleri giriş/çıkış tarihlerine göre yeniden yapılandırıldı.');
        header('Location: ' . $successRedirect);
        exit;
    }

    private function contractDeleteRedirect(array $post): string
    {
        $redirect = trim($post['redirect'] ?? '');
        if ($redirect !== '' && preg_match('#^/girisler/[a-f0-9\-]+$#i', $redirect)) {
            return $redirect;
        }
        return '/girisler';
    }

    /** @param array<string, mixed> $contract */
    private function restructureDueDatesSuccessRedirect(string $contractId, array $contract, array $post): string
    {
        $redirect = trim($post['redirect_on_success'] ?? '');
        if ($redirect !== '' && preg_match('#^/musteriler/[a-f0-9\-]+$#i', $redirect)) {
            return $redirect;
        }
        $customerId = trim($contract['customer_id'] ?? '');
        if ($customerId !== '' && ($post['return_to_customer'] ?? '') === '1') {
            return '/musteriler/' . $customerId;
        }
        return '/girisler/' . $contractId;
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

    private function validateContractRoomChange(array $contract, string $newRoomId, ?string $companyId, array $user): ?string
    {
        $oldRoomId = $contract['room_id'] ?? '';
        if ($newRoomId === $oldRoomId) {
            return null;
        }
        $room = Room::findOne($this->pdo, $newRoomId);
        if (!$room) {
            return 'Geçersiz oda seçimi.';
        }
        if (($user['role'] ?? '') !== 'super_admin' && $companyId && ($room['company_id'] ?? '') !== $companyId) {
            return 'Bu odaya erişim yetkiniz yok.';
        }
        $contractId = $contract['id'] ?? '';
        if ($contractId !== '' && Room::hasActiveContractExcept($this->pdo, $newRoomId, $contractId)) {
            return 'Seçilen oda başka bir aktif sözleşmede kullanılıyor.';
        }
        return null;
    }

    private function syncRoomOccupancyAfterContractMove(string $oldRoomId, string $newRoomId, bool $contractActive): void
    {
        if ($oldRoomId !== '' && $oldRoomId !== $newRoomId) {
            Room::update($this->pdo, $oldRoomId, [
                'status' => Room::hasActiveContract($this->pdo, $oldRoomId) ? 'occupied' : 'empty',
            ]);
        }
        if ($newRoomId !== '' && $contractActive) {
            Room::update($this->pdo, $newRoomId, ['status' => 'occupied']);
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

    /** @return array{owners: array<int, array<string, mixed>>, personnel: array<int, array<string, mixed>>} */
    private function loadContractStaffOptions(?string $companyId): array
    {
        $personnel = [];
        $owners = [];
        if ($companyId) {
            if (Personnel::tableExists($this->pdo)) {
                $personnel = Personnel::findActiveForCompany($this->pdo, $companyId);
            }
            $owners = User::findStaff($this->pdo, $companyId, null, null, '1');
        }
        return ['owners' => $owners, 'personnel' => $personnel];
    }

    /** Kayıtlı satış yapan listede yoksa (pasif vb.) seçeneklere ekle */
    private function ensureSoldByInOwnersList(array $owners, ?string $soldByUserId): array
    {
        $soldByUserId = trim((string) $soldByUserId);
        if ($soldByUserId === '') {
            return $owners;
        }
        foreach ($owners as $owner) {
            if (($owner['id'] ?? '') === $soldByUserId) {
                return $owners;
            }
        }
        $stmt = $this->pdo->prepare(
            'SELECT id, first_name, last_name FROM users WHERE id = ? AND deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([$soldByUserId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $owners[] = $user;
        }
        return $owners;
    }

    private static function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
