<?php
class CustomersController
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
        $inDepo = isset($_GET['in_depo']) && in_array($_GET['in_depo'], ['yes', 'no'], true) ? $_GET['in_depo'] : null;
        $warehouseId = isset($_GET['warehouse_id']) && $_GET['warehouse_id'] !== '' ? trim($_GET['warehouse_id']) : null;
        if (!array_key_exists('borc', $_GET)) {
            $debtFilter = null;
        } elseif (trim((string) $_GET['borc']) === '') {
            $debtFilter = null;
        } elseif (in_array($_GET['borc'], ['overdue', 'unpaid'], true)) {
            $debtFilter = $_GET['borc'];
        } else {
            $debtFilter = null;
        }
        $perPage = 50;
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $offset = ($page - 1) * $perPage;

        $warehouses = [];
        if ($companyId) {
            $warehouses = Warehouse::findAll($this->pdo, $companyId);
            $customersTotal = Customer::count($this->pdo, $companyId, $search, $inDepo, $warehouseId, $debtFilter);
            $customers = Customer::findAll($this->pdo, $companyId, $search, $perPage, $offset, $inDepo, $warehouseId, $debtFilter);
        } elseif (($user['role'] ?? '') === 'super_admin') {
            $warehouses = Warehouse::findAll($this->pdo, null);
            $customersTotal = Customer::count($this->pdo, null, $search, $inDepo, $warehouseId, $debtFilter);
            $customers = Customer::findAll($this->pdo, null, $search, $perPage, $offset, $inDepo, $warehouseId, $debtFilter);
        } else {
            $customersTotal = 0;
            $customers = [];
        }

        $totalPages = $customersTotal > 0 ? (int) ceil($customersTotal / $perPage) : 1;
        if ($page > $totalPages && $customersTotal > 0) {
            $params = $_GET;
            $params['page'] = $totalPages;
            header('Location: /musteriler?' . http_build_query($params));
            exit;
        }

        $duplicateFullNames = [];
        if (!empty($customers) || $customersTotal > 0) {
            $duplicateFullNames = Customer::getDuplicateFullNames($this->pdo, $companyId ?? null);
        }
        ['success' => $flashSuccess, 'error' => $flashError] = Auth::consumeFlash();
        require __DIR__ . '/../../views/customers/index.php';
    }

    public function bulkDelete(): void
    {
        Auth::requireStaff();
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        $ids = isset($_POST['ids']) && is_array($_POST['ids']) ? array_map('trim', $_POST['ids']) : [];
        $ids = array_filter($ids, fn($id) => $id !== '');
        if (empty($ids)) {
            Auth::setSession('flash_error', 'Silinecek müşteri seçin.');
            header('Location: /musteriler');
            exit;
        }
        $deleted = 0;
        foreach ($ids as $id) {
            $customer = Customer::findOne($this->pdo, $id);
            if (!$customer) {
                continue;
            }
            if ($companyId && ($customer['company_id'] ?? '') !== $companyId) {
                continue;
            }
            if (Customer::hardDelete($this->pdo, $id)) {
                $deleted++;
            }
        }
        if ($deleted > 0) {
            $actorName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
            Notification::createForCompany($this->pdo, $companyId, 'customer', 'Müşteri silindi', $deleted . ' müşteri silindi.', ['actor_name' => $actorName]);
            Auth::setSession('flash_success', $deleted . ' müşteri silindi.');
        } else {
            Auth::setSession('flash_error', 'Seçilen müşteriler silinemedi. Aktif depo sözleşmesi olan müşteriler önce sonlandırılmalıdır.');
        }
        $redirectParams = array_filter([
            'q' => isset($_GET['q']) ? trim($_GET['q']) : null,
            'in_depo' => isset($_GET['in_depo']) && in_array($_GET['in_depo'], ['yes', 'no'], true) ? $_GET['in_depo'] : null,
            'warehouse_id' => isset($_GET['warehouse_id']) && $_GET['warehouse_id'] !== '' ? trim($_GET['warehouse_id']) : null,
            'borc' => isset($_GET['borc']) && in_array($_GET['borc'], ['overdue', 'unpaid'], true) ? $_GET['borc'] : null,
        ]);
        header('Location: /musteriler' . ($redirectParams !== [] ? '?' . http_build_query($redirectParams) : ''));
        exit;
    }

    public function show(array $params): void
    {
        Auth::requireStaff();
        $id = $params['id'] ?? '';
        if (!$id) {
            header('Location: /musteriler');
            exit;
        }
        $customer = Customer::findOne($this->pdo, $id);
        if (!$customer) {
            Auth::setSession('flash_error', 'Müşteri bulunamadı.');
            header('Location: /musteriler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($customer['company_id'] ?? '') !== $companyId) {
            Auth::setSession('flash_error', 'Bu müşteriye erişim yetkiniz yok.');
            header('Location: /musteriler');
            exit;
        }
        $contracts = Contract::findByCustomerId($this->pdo, $id, $companyId);
        foreach ($contracts as $c) {
            if (!empty($c['id']) && empty($c['terminated_at'])) {
                Contract::ensurePaymentsForContract($this->pdo, $c['id']);
            }
        }
        $payments = Payment::findByCustomerId($this->pdo, $id, $companyId);
        $collectiblePayments = Payment::findCollectibleByCustomerId($this->pdo, $id, $companyId);
        $debt = Payment::sumUnpaidByCustomerId($this->pdo, $id, $companyId);
        $debtOverdue = Payment::sumUnpaidOverdueByCustomerId($this->pdo, $id, $companyId);
        $debtDueThisMonth = Payment::sumUnpaidDueThisMonthByCustomerId($this->pdo, $id, $companyId);
        $totalCollected = Payment::sumPaidByCustomerId($this->pdo, $id, $companyId);
        try {
            $debt += CustomerCharge::sumUnpaidByCustomerId($this->pdo, $id, $companyId);
            $totalCollected += CustomerCharge::sumPaidByCustomerId($this->pdo, $id, $companyId);
        } catch (Throwable $e) {
            // customer_charges tablosu yoksa yoksay
        }
        $charges = [];
        try {
            $charges = CustomerCharge::findByCustomerId($this->pdo, $id, $companyId);
        } catch (Throwable $e) {
            // customer_charges tablosu yoksa yoksay
        }
        $documents = [];
        try {
            $documents = CustomerDocument::findByCustomerId($this->pdo, $id, $companyId);
        } catch (Throwable $e) {
            // customer_documents tablosu yoksa yoksay
        }
        $lastPayment = null;
        foreach ($payments as $p) {
            if (($p['status'] ?? '') === 'paid' && !empty($p['paid_at'])) {
                if ($lastPayment === null || strtotime($p['paid_at']) > strtotime($lastPayment['paid_at'])) {
                    $lastPayment = $p;
                }
            }
        }
        $monthlyRent = 0;
        $primaryWarehouse = null;
        $exitDone = false;
        foreach ($contracts as $c) {
            if (!empty($c['is_active']) && empty($c['terminated_at'])) {
                $monthlyRent += (float) ($c['monthly_price'] ?? 0);
            }
            if ($primaryWarehouse === null && !empty($c['warehouse_name'])) {
                $primaryWarehouse = $c['warehouse_name'];
                $exitDone = !empty($c['terminated_at']);
            }
        }
        if ($monthlyRent == 0 && !empty($contracts[0])) {
            $monthlyRent = (float) ($contracts[0]['monthly_price'] ?? 0);
            if ($primaryWarehouse === null) {
                $primaryWarehouse = $contracts[0]['warehouse_name'] ?? null;
                $exitDone = !empty($contracts[0]['terminated_at']);
            }
        }
        $bankAccounts = [];
        if ($companyId) {
            $stmt = $this->pdo->prepare('SELECT * FROM bank_accounts WHERE company_id = ? AND deleted_at IS NULL AND is_active = 1 ORDER BY bank_name');
            $stmt->execute([$companyId]);
            $bankAccounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } elseif (($user['role'] ?? '') === 'super_admin') {
            $stmt = $this->pdo->query('SELECT * FROM bank_accounts WHERE deleted_at IS NULL AND is_active = 1 ORDER BY bank_name');
            $bankAccounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        $openAddContract = isset($_GET['addContract']) && $_GET['addContract'] !== '0';
        $warehouses = [];
        $contractRoomsJson = [];
        $custCompanyId = $customer['company_id'] ?? $companyId;
        if ($custCompanyId) {
            $warehouses = Warehouse::findAll($this->pdo, $custCompanyId);
        } elseif (($user['role'] ?? '') === 'super_admin') {
            $warehouses = Warehouse::findAll($this->pdo, null);
        }
        $rooms = Room::findAll($this->pdo, null);
        if ($custCompanyId) {
            $rooms = array_values(array_filter($rooms, fn($r) => ($r['company_id'] ?? '') === $custCompanyId));
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
        $pageTitle = 'Müşteri: ' . trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
        $bulkPaidIssues = Payment::findSuspiciousBulkPaidGroups($this->pdo, $id, $companyId);
        $bulkPaidExtraCount = array_sum(array_map(fn($g) => (int) ($g['excess'] ?? 0), $bulkPaidIssues));
        require __DIR__ . '/../../views/customers/detail.php';
    }

    /** Aynı anda yanlışlıkla ödendi işaretlenmiş taksitleri düzelt (yalnızca ilk vade kalır). */
    public function fixBulkPaid(array $params): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /musteriler');
            exit;
        }
        $id = $params['id'] ?? '';
        if (!$id) {
            header('Location: /musteriler');
            exit;
        }
        $customer = Customer::findOne($this->pdo, $id);
        if (!$customer) {
            Auth::setSession('flash_error', 'Müşteri bulunamadı.');
            header('Location: /musteriler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($customer['company_id'] ?? '') !== $companyId) {
            Auth::setSession('flash_error', 'Bu müşteriye erişim yetkiniz yok.');
            header('Location: /musteriler');
            exit;
        }
        $keepCount = max(1, (int) ($_POST['keep_count'] ?? 1));
        $reverted = Payment::revertBulkPaidKeepingEarliest($this->pdo, $id, $companyId, $keepCount);
        if ($reverted > 0) {
            Auth::setSession('flash_success', $reverted . ' taksit geri alındı. Yalnızca tahsil edilen ' . $keepCount . ' vade ödendi olarak kaldı.');
        } else {
            Auth::setSession('flash_error', 'Düzeltilecek toplu tahsilat kaydı bulunamadı.');
        }
        header('Location: /musteriler/' . $id);
        exit;
    }

    /** Müşteriye SMS gönder (Ayarlar > SMS ayarlarına göre). */
    public function sendSms(array $params): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /musteriler');
            exit;
        }
        $id = $params['id'] ?? '';
        if (!$id) {
            Auth::setSession('flash_error', 'Müşteri bulunamadı.');
            header('Location: /musteriler');
            exit;
        }
        $customer = Customer::findOne($this->pdo, $id);
        if (!$customer) {
            Auth::setSession('flash_error', 'Müşteri bulunamadı.');
            header('Location: /musteriler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($customer['company_id'] ?? '') !== $companyId) {
            Auth::setSession('flash_error', 'Bu müşteriye erişim yetkiniz yok.');
            header('Location: /musteriler');
            exit;
        }
        $phone = trim($customer['phone'] ?? '');
        if ($phone === '') {
            Auth::setSession('flash_error', 'Müşteride telefon numarası kayıtlı değil.');
            header('Location: /musteriler/' . $id);
            exit;
        }
        $message = trim($_POST['message'] ?? '');
        if ($message === '') {
            Auth::setSession('flash_error', 'Mesaj metni girin.');
            header('Location: /musteriler/' . $id);
            exit;
        }
        $result = SmsService::send($this->pdo, $customer['company_id'], $phone, $message);
        if ($result['success']) {
            Auth::setSession('flash_success', 'SMS gönderildi.');
        } else {
            Auth::setSession('flash_error', $result['error'] ?? 'SMS gönderilemedi.');
        }
        header('Location: /musteriler/' . $id);
        exit;
    }

    /** Borçlandır formu */
    public function borclandirForm(array $params): void
    {
        Auth::requireStaff();
        $id = $params['id'] ?? '';
        if (!$id) {
            header('Location: /musteriler');
            exit;
        }
        $customer = Customer::findOne($this->pdo, $id);
        if (!$customer) {
            Auth::setSession('flash_error', 'Müşteri bulunamadı.');
            header('Location: /musteriler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($customer['company_id'] ?? '') !== $companyId) {
            Auth::setSession('flash_error', 'Bu müşteriye erişim yetkiniz yok.');
            header('Location: /musteriler');
            exit;
        }
        $customerName = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
        $pageTitle = 'Borçlandır: ' . $customerName;
        $currentPage = 'musteriler';
        require __DIR__ . '/../../views/customers/borclandir.php';
    }

    /** Borçlandır – manuel borç kaydı oluştur */
    public function borclandir(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /musteriler');
            exit;
        }
        $customerId = trim($_POST['customer_id'] ?? '');
        $amount = isset($_POST['amount']) ? (float) str_replace(',', '.', $_POST['amount']) : 0;
        if (!$customerId || $amount <= 0) {
            Auth::setSession('flash_error', 'Müşteri ve tutar (0\'dan büyük) zorunludur.');
            header('Location: /musteriler/' . $customerId);
            exit;
        }
        $customer = Customer::findOne($this->pdo, $customerId);
        if (!$customer) {
            Auth::setSession('flash_error', 'Müşteri bulunamadı.');
            header('Location: /musteriler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($customer['company_id'] ?? '') !== $companyId) {
            Auth::setSession('flash_error', 'Bu müşteriye erişim yetkiniz yok.');
            header('Location: /musteriler');
            exit;
        }
        CustomerCharge::create($this->pdo, [
            'customer_id' => $customerId,
            'company_id' => $customer['company_id'],
            'amount' => $amount,
            'description' => trim($_POST['description'] ?? '') ?: null,
            'due_date' => trim($_POST['due_date'] ?? '') ?: null,
            'notes' => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Auth::setSession('flash_success', 'Borç kaydı eklendi.');
        header('Location: /musteriler/' . $customerId);
        exit;
    }

    /** Belge yükleme formu */
    public function documentUploadForm(array $params): void
    {
        Auth::requireStaff();
        $id = $params['id'] ?? '';
        if (!$id) {
            header('Location: /musteriler');
            exit;
        }
        $customer = Customer::findOne($this->pdo, $id);
        if (!$customer) {
            Auth::setSession('flash_error', 'Müşteri bulunamadı.');
            header('Location: /musteriler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($customer['company_id'] ?? '') !== $companyId) {
            Auth::setSession('flash_error', 'Bu müşteriye erişim yetkiniz yok.');
            header('Location: /musteriler');
            exit;
        }
        $customerName = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
        $pageTitle = 'Belge Ekle: ' . $customerName;
        $currentPage = 'musteriler';
        require __DIR__ . '/../../views/customers/belge_ekle.php';
    }

    /** Belge yükle – POST */
    public function documentUpload(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /musteriler');
            exit;
        }
        $customerId = trim($_POST['customer_id'] ?? '');
        if (!$customerId) {
            Auth::setSession('flash_error', 'Müşteri gerekli.');
            header('Location: /musteriler');
            exit;
        }
        $customer = Customer::findOne($this->pdo, $customerId);
        if (!$customer) {
            Auth::setSession('flash_error', 'Müşteri bulunamadı.');
            header('Location: /musteriler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($customer['company_id'] ?? '') !== $companyId) {
            Auth::setSession('flash_error', 'Bu müşteriye erişim yetkiniz yok.');
            header('Location: /musteriler');
            exit;
        }
        $file = $_FILES['document'] ?? null;
        $allowedExt = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'doc', 'docx'];
        $uploadError = validateUploadedDocument($file, $allowedExt);
        if ($uploadError !== null) {
            Auth::setSession('flash_error', $uploadError);
            header('Location: /musteriler/' . $customerId . '/belge-ekle');
            exit;
        }
        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        $uploadDir = defined('APP_ROOT') ? APP_ROOT . '/public/uploads/customer_documents' : __DIR__ . '/../../public/uploads/customer_documents';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }
        $filename = $customerId . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $filePath = $uploadDir . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            Auth::setSession('flash_error', 'Dosya kaydedilemedi.');
            header('Location: /musteriler/' . $customerId);
            exit;
        }
        $relativePath = '/uploads/customer_documents/' . $filename;
        CustomerDocument::create($this->pdo, [
            'customer_id' => $customerId,
            'company_id' => $customer['company_id'],
            'name' => trim($_POST['name'] ?? '') ?: pathinfo($file['name'], PATHINFO_FILENAME),
            'file_path' => $relativePath,
            'file_size' => $file['size'] ?? null,
            'mime_type' => $file['type'] ?? null,
            'notes' => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Auth::setSession('flash_success', 'Belge eklendi.');
        header('Location: /musteriler/' . $customerId);
        exit;
    }

    /** Belge parça yükle – nginx 413 önlemi (JSON yanıt) */
    public function documentUploadChunk(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['ok' => false, 'error' => 'Geçersiz istek.']);
            exit;
        }
        $uploadId = sanitizeUploadSessionId($_POST['upload_id'] ?? null);
        $chunkIndex = (int) ($_POST['chunk_index'] ?? -1);
        $totalChunks = (int) ($_POST['total_chunks'] ?? 0);
        $customerId = trim($_POST['customer_id'] ?? '');
        $originalName = trim($_POST['original_name'] ?? '');
        if (!$uploadId || $chunkIndex < 0 || $totalChunks < 1 || $chunkIndex >= $totalChunks || !$customerId || $originalName === '') {
            echo json_encode(['ok' => false, 'error' => 'Eksik yükleme bilgisi.']);
            exit;
        }
        $customer = Customer::findOne($this->pdo, $customerId);
        if (!$customer) {
            echo json_encode(['ok' => false, 'error' => 'Müşteri bulunamadı.']);
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($customer['company_id'] ?? '') !== $companyId) {
            echo json_encode(['ok' => false, 'error' => 'Bu müşteriye erişim yetkiniz yok.']);
            exit;
        }
        $allowedExt = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'doc', 'docx'];
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt, true)) {
            echo json_encode(['ok' => false, 'error' => 'İzin verilen formatlar: ' . implode(', ', $allowedExt)]);
            exit;
        }
        $chunk = $_FILES['chunk'] ?? null;
        if (!$chunk || ($chunk['error'] ?? 0) !== UPLOAD_ERR_OK || empty($chunk['tmp_name'])) {
            echo json_encode(['ok' => false, 'error' => 'Parça yüklenemedi.']);
            exit;
        }
        $chunkSize = (int) ($chunk['size'] ?? 0);
        if ($chunkSize <= 0 || $chunkSize > uploadChunkByteSize() + 65536) {
            echo json_encode(['ok' => false, 'error' => 'Geçersiz parça boyutu.']);
            exit;
        }
        if ($chunkIndex === 0) {
            $_SESSION['upload_meta'][$uploadId] = [
                'customer_id' => $customerId,
                'name' => trim($_POST['name'] ?? ''),
                'notes' => trim($_POST['notes'] ?? ''),
                'original_name' => $originalName,
                'total_chunks' => $totalChunks,
                'user_id' => $user['id'] ?? '',
                'started_at' => time(),
            ];
        }
        $meta = $_SESSION['upload_meta'][$uploadId] ?? null;
        if (!$meta || ($meta['customer_id'] ?? '') !== $customerId || ($meta['user_id'] ?? '') !== ($user['id'] ?? '')) {
            echo json_encode(['ok' => false, 'error' => 'Yükleme oturumu geçersiz. Sayfayı yenileyip tekrar deneyin.']);
            exit;
        }
        if ((int) ($meta['total_chunks'] ?? 0) !== $totalChunks) {
            echo json_encode(['ok' => false, 'error' => 'Parça sayısı uyuşmuyor.']);
            exit;
        }
        if (!saveUploadChunkPart($uploadId, $chunkIndex, $chunk['tmp_name'])) {
            echo json_encode(['ok' => false, 'error' => 'Parça kaydedilemedi.']);
            exit;
        }
        if ($chunkIndex < $totalChunks - 1) {
            echo json_encode(['ok' => true, 'done' => false, 'chunk' => $chunkIndex + 1, 'total' => $totalChunks]);
            exit;
        }
        $mergedPath = mergeUploadChunkParts($uploadId, $totalChunks);
        if (!$mergedPath || !is_file($mergedPath)) {
            removeUploadChunkDir($uploadId);
            unset($_SESSION['upload_meta'][$uploadId]);
            echo json_encode(['ok' => false, 'error' => 'Dosya birleştirilemedi.']);
            exit;
        }
        $fileSize = (int) filesize($mergedPath);
        if ($fileSize <= 0 || $fileSize > uploadMaxBytes()) {
            @unlink($mergedPath);
            removeUploadChunkDir($uploadId);
            unset($_SESSION['upload_meta'][$uploadId]);
            echo json_encode(['ok' => false, 'error' => 'Dosya boyutu ' . uploadMaxBytesLabel() . ' sınırını aşıyor.']);
            exit;
        }
        $uploadDir = defined('APP_ROOT') ? APP_ROOT . '/public/uploads/customer_documents' : __DIR__ . '/../../public/uploads/customer_documents';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }
        $filename = $customerId . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $filePath = $uploadDir . '/' . $filename;
        if (!@rename($mergedPath, $filePath)) {
            @unlink($mergedPath);
            removeUploadChunkDir($uploadId);
            unset($_SESSION['upload_meta'][$uploadId]);
            echo json_encode(['ok' => false, 'error' => 'Dosya kaydedilemedi.']);
            exit;
        }
        removeUploadChunkDir($uploadId);
        unset($_SESSION['upload_meta'][$uploadId]);
        $relativePath = '/uploads/customer_documents/' . $filename;
        $displayName = trim($meta['name'] ?? '') ?: pathinfo($originalName, PATHINFO_FILENAME);
        CustomerDocument::create($this->pdo, [
            'customer_id' => $customerId,
            'company_id' => $customer['company_id'],
            'name' => $displayName,
            'file_path' => $relativePath,
            'file_size' => $fileSize,
            'mime_type' => documentMimeFromExtension($ext),
            'notes' => trim($meta['notes'] ?? '') ?: null,
        ]);
        Auth::setSession('flash_success', 'Belge eklendi.');
        echo json_encode(['ok' => true, 'done' => true, 'redirect' => '/musteriler/' . $customerId]);
        exit;
    }

    /** Belge sil */
    public function documentDelete(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /musteriler');
            exit;
        }
        $docId = trim($_POST['id'] ?? '');
        $redirect = trim($_POST['redirect'] ?? '/musteriler');
        if (!$docId) {
            Auth::setSession('flash_error', 'Belge seçilmedi.');
            header('Location: ' . $redirect);
            exit;
        }
        $doc = CustomerDocument::findOne($this->pdo, $docId);
        if (!$doc) {
            Auth::setSession('flash_error', 'Belge bulunamadı.');
            header('Location: ' . $redirect);
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($doc['company_id'] ?? '') !== $companyId) {
            Auth::setSession('flash_error', 'Yetkisiz.');
            header('Location: /musteriler');
            exit;
        }
        CustomerDocument::softDelete($this->pdo, $docId);
        Auth::setSession('flash_success', 'Belge silindi.');
        header('Location: /musteriler/' . ($doc['customer_id'] ?? ''));
        exit;
    }

    /** Müşteri bilgilerini güncelle (ad, soyad, e-posta, telefon vb.) */
    public function update(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /musteriler');
            exit;
        }
        $id = trim($_POST['id'] ?? '');
        if (!$id) {
            Auth::setSession('flash_error', 'Müşteri bulunamadı.');
            header('Location: /musteriler');
            exit;
        }
        $customer = Customer::findOne($this->pdo, $id);
        if (!$customer) {
            Auth::setSession('flash_error', 'Müşteri bulunamadı.');
            header('Location: /musteriler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        $customerCompanyId = $customer['company_id'] ?? null;
        if ($companyId && $customerCompanyId !== $companyId) {
            Auth::setSession('flash_error', 'Bu müşteriye erişim yetkiniz yok.');
            header('Location: /musteriler');
            exit;
        }
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '') !== '' ? trim($_POST['email']) : '';
        $phone = trim($_POST['phone'] ?? '') !== '' ? trim($_POST['phone']) : null;
        $phone2 = trim($_POST['phone_2'] ?? '') !== '' ? trim($_POST['phone_2']) : null;
        $identityNumber = trim($_POST['identity_number'] ?? '') !== '' ? trim($_POST['identity_number']) : null;
        if ($firstName === '' || $lastName === '') {
            Auth::setSession('flash_error', 'Ad ve soyad zorunludur.');
            header('Location: /musteriler/' . $id);
            exit;
        }
        if ($phone !== null && !validatePhone($phone)) {
            Auth::setSession('flash_error', 'Telefon formatı geçersiz. 11 hane girin: 05xx xxx xx xx');
            header('Location: /musteriler/' . $id);
            exit;
        }
        if ($phone2 !== null && !validatePhone($phone2)) {
            Auth::setSession('flash_error', '2. telefon formatı geçersiz. 11 hane girin: 05xx xxx xx xx');
            header('Location: /musteriler/' . $id);
            exit;
        }
        if ($email !== '' && !validateEmail($email)) {
            Auth::setSession('flash_error', 'E-posta formatı geçersiz.');
            header('Location: /musteriler/' . $id);
            exit;
        }
        $phoneFormatted = $phone !== null ? formatPhoneInput($phone) : null;
        $phone2Formatted = $phone2 !== null ? formatPhoneInput($phone2) : null;
        $checkCompanyId = $customerCompanyId ?? $companyId;
        if ($checkCompanyId && $email !== '' && Customer::findByEmail($this->pdo, $checkCompanyId, $email, $id)) {
            Auth::setSession('flash_error', 'Bu e-posta adresi başka bir müşteride kayıtlı.');
            header('Location: /musteriler/' . $id);
            exit;
        }
        if ($checkCompanyId && $phoneFormatted !== null && Customer::findByPhoneOrPhone2($this->pdo, $checkCompanyId, $phoneFormatted, $id)) {
            Auth::setSession('flash_error', 'Bu telefon numarası başka bir müşteride kayıtlı. Aynı telefon numarasına ait başka bir müşteri olamaz.');
            header('Location: /musteriler/' . $id);
            exit;
        }
        if ($checkCompanyId && $phone2Formatted !== null && Customer::findByPhoneOrPhone2($this->pdo, $checkCompanyId, $phone2Formatted, $id)) {
            Auth::setSession('flash_error', 'Bu 2. telefon numarası başka bir müşteride kayıtlı. Aynı telefon numarasına ait başka bir müşteri olamaz.');
            header('Location: /musteriler/' . $id);
            exit;
        }
        $data = [
            'first_name'      => $firstName,
            'last_name'       => $lastName,
            'email'           => $email,
            'phone'           => $phoneFormatted,
            'phone_2'         => $phone2Formatted,
            'identity_number' => $identityNumber,
            'address'         => trim($_POST['address'] ?? '') ?: null,
            'notes'           => trim($_POST['notes'] ?? '') ?: null,
            'invoice_info'    => trim($_POST['invoice_info'] ?? '') ?: null,
            'is_active'       => isset($_POST['is_active']) ? 1 : 0,
        ];
        Customer::update($this->pdo, $id, $data);
        Auth::setSession('flash_success', 'Müşteri bilgileri güncellendi.');
        header('Location: /musteriler/' . $id);
        exit;
    }

    /** Bilgi notu güncelle */
    public function noteUpdate(array $params): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /musteriler');
            exit;
        }
        $id = $params['id'] ?? '';
        if (!$id) {
            header('Location: /musteriler');
            exit;
        }
        $customer = Customer::findOne($this->pdo, $id);
        if (!$customer) {
            Auth::setSession('flash_error', 'Müşteri bulunamadı.');
            header('Location: /musteriler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($customer['company_id'] ?? '') !== $companyId) {
            Auth::setSession('flash_error', 'Bu müşteriye erişim yetkiniz yok.');
            header('Location: /musteriler');
            exit;
        }
        $notes = trim($_POST['notes'] ?? '');
        Customer::update($this->pdo, $id, ['notes' => $notes]);
        Auth::setSession('flash_success', 'Not güncellendi.');
        header('Location: /musteriler/' . $id);
        exit;
    }

    /** Çıkış belgesi – müşterinin sözleşmelerini listele, her biri için çıkış belgesi linki */
    public function cikisBelgesiList(array $params): void
    {
        Auth::requireStaff();
        $id = $params['id'] ?? '';
        if (!$id) {
            header('Location: /musteriler');
            exit;
        }
        $customer = Customer::findOne($this->pdo, $id);
        if (!$customer) {
            Auth::setSession('flash_error', 'Müşteri bulunamadı.');
            header('Location: /musteriler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($customer['company_id'] ?? '') !== $companyId) {
            Auth::setSession('flash_error', 'Bu müşteriye erişim yetkiniz yok.');
            header('Location: /musteriler');
            exit;
        }
        $contracts = Contract::findByCustomerId($this->pdo, $id, $companyId);
        $customerName = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
        $customerId = $id;
        $pageTitle = 'Çıkış belgesi: ' . $customerName;
        $currentPage = 'musteriler';
        require __DIR__ . '/../../views/customers/cikis_belgesi_list.php';
    }

    /** Müşteri detay – özel yazdırma sayfası */
    public function printPage(array $params): void
    {
        Auth::requireStaff();
        $id = $params['id'] ?? '';
        if (!$id) {
            header('Location: /musteriler');
            exit;
        }
        $customer = Customer::findOne($this->pdo, $id);
        if (!$customer) {
            Auth::setSession('flash_error', 'Müşteri bulunamadı.');
            header('Location: /musteriler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($customer['company_id'] ?? '') !== $companyId) {
            Auth::setSession('flash_error', 'Bu müşteriye erişim yetkiniz yok.');
            header('Location: /musteriler');
            exit;
        }
        $contracts = Contract::findByCustomerId($this->pdo, $id, $companyId);
        $payments = Payment::findByCustomerId($this->pdo, $id, $companyId);
        $debt = Payment::sumUnpaidByCustomerId($this->pdo, $id, $companyId);
        $company = !empty($customer['company_id']) ? Company::findOne($this->pdo, $customer['company_id']) : null;
        require __DIR__ . '/../../views/customers/print.php';
    }

    /** Liste sayfasında satır genişletildiğinde gösterilecek HTML fragment (AJAX) */
    public function rowFragment(array $params): void
    {
        Auth::requireStaff();
        header('Content-Type: text/html; charset=utf-8');
        $id = $params['id'] ?? '';
        if (!$id) {
            echo '';
            return;
        }
        $customer = Customer::findOne($this->pdo, $id);
        if (!$customer) {
            echo '';
            return;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($customer['company_id'] ?? '') !== $companyId) {
            echo '';
            return;
        }
        $contracts = Contract::findByCustomerId($this->pdo, $id, $companyId);
        $payments = Payment::findByCustomerId($this->pdo, $id, $companyId);
        $debt = Payment::sumUnpaidByCustomerId($this->pdo, $id, $companyId);
        $debtOverdue = Payment::sumUnpaidOverdueByCustomerId($this->pdo, $id, $companyId);
        $debtDueThisMonth = Payment::sumUnpaidDueThisMonthByCustomerId($this->pdo, $id, $companyId);
        require __DIR__ . '/../../views/customers/_row_fragment.php';
    }

    /** Müşteri depo etiketi (barkod) – yazdırılabilir sayfa */
    public function barcode(array $params): void
    {
        Auth::requireStaff();
        $id = $params['id'] ?? '';
        if (!$id) {
            header('Location: /musteriler');
            exit;
        }
        $customer = Customer::findOne($this->pdo, $id);
        if (!$customer) {
            Auth::setSession('flash_error', 'Müşteri bulunamadı.');
            header('Location: /musteriler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($customer['company_id'] ?? '') !== $companyId) {
            Auth::setSession('flash_error', 'Bu müşteriye erişim yetkiniz yok.');
            header('Location: /musteriler');
            exit;
        }
        $items = Item::findByCustomerId($this->pdo, $id);
        $contracts = Contract::findByCustomerId($this->pdo, $id, $companyId);
        require __DIR__ . '/../../views/customers/barcode.php';
    }

    public function create(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /musteriler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if (!$companyId) {
            Auth::setSession('flash_error', 'Şirket bilgisi bulunamadı. Depo/müşteri eklemek için Ayarlar\'dan firma oluşturun veya yöneticiye şirket ataması yaptırın.');
            header('Location: /musteriler');
            exit;
        }
        if (!Company::findOne($this->pdo, $companyId)) {
            Auth::setSession('flash_error', 'Şirket kaydı veritabanında bulunamadı. Sunucuda "php php-app/seed.php" veya "php artisan migrate" çalıştırın; yönetici kullanıcınıza geçerli bir şirket atasın.');
            header('Location: /musteriler');
            exit;
        }
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        if ($firstName === '' || $lastName === '') {
            Auth::setSession('flash_error', 'Ad ve soyad zorunludur.');
            $redirectTo = $_POST['redirect_to'] ?? '';
            if ($redirectTo === 'new_sale') {
                header('Location: /girisler?newSale=1');
            } elseif ($redirectTo === 'new_job') {
                header('Location: /nakliye-isler');
            } else {
                header('Location: /musteriler');
            }
            exit;
        }
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '') ?: null;
        $phone2 = trim($_POST['phone_2'] ?? '') ?: null;
        $identityNumber = trim($_POST['identity_number'] ?? '') ?: null;
        if ($identityNumber !== null && !validateTcIdentity($identityNumber)) {
            Auth::setSession('flash_error', 'Müşteri numarası (TC Kimlik No) en fazla 11 haneli rakam olmalıdır.');
            $this->redirectAfterCreate($_POST['redirect_to'] ?? '', null);
            exit;
        }
        if ($phone !== null && !validatePhone($phone)) {
            Auth::setSession('flash_error', 'Telefon formatı geçersiz. 11 hane girin: 05xx xxx xx xx');
            $this->redirectAfterCreate($_POST['redirect_to'] ?? '', null);
            exit;
        }
        if ($phone2 !== null && !validatePhone($phone2)) {
            Auth::setSession('flash_error', '2. telefon formatı geçersiz. 11 hane girin: 05xx xxx xx xx');
            $this->redirectAfterCreate($_POST['redirect_to'] ?? '', null);
            exit;
        }
        if ($email !== '' && !validateEmail($email)) {
            Auth::setSession('flash_error', 'E-posta formatı geçersiz.');
            $this->redirectAfterCreate($_POST['redirect_to'] ?? '', null);
            exit;
        }
        $phoneFormatted = $phone !== null ? formatPhoneInput($phone) : null;
        $phone2Formatted = $phone2 !== null ? formatPhoneInput($phone2) : null;
        if ($email !== '' && Customer::findByEmail($this->pdo, $companyId, $email, null)) {
            Auth::setSession('flash_error', 'Bu e-posta adresi ile kayıtlı bir müşteri zaten var. Aynı e-posta ile müşteri kaydedilemez.');
            $this->redirectAfterCreate($_POST['redirect_to'] ?? '', null);
            exit;
        }
        if ($phoneFormatted !== null && Customer::findByPhoneOrPhone2($this->pdo, $companyId, $phoneFormatted, null)) {
            Auth::setSession('flash_error', 'Bu telefon numarası ile kayıtlı bir müşteri zaten var. Aynı telefon numarasına ait başka bir müşteri olamaz.');
            $this->redirectAfterCreate($_POST['redirect_to'] ?? '', null);
            exit;
        }
        if ($phone2Formatted !== null && Customer::findByPhoneOrPhone2($this->pdo, $companyId, $phone2Formatted, null)) {
            Auth::setSession('flash_error', 'Bu 2. telefon numarası ile kayıtlı bir müşteri zaten var. Aynı telefon numarasına ait başka bir müşteri olamaz.');
            $this->redirectAfterCreate($_POST['redirect_to'] ?? '', null);
            exit;
        }
        $data = [
            'company_id'      => $companyId,
            'first_name'      => $firstName,
            'last_name'       => $lastName,
            'email'           => $email,
            'phone'           => $phoneFormatted,
            'phone_2'         => $phone2Formatted,
            'identity_number' => $identityNumber,
            'address'         => trim($_POST['address'] ?? '') ?: null,
            'notes'           => trim($_POST['notes'] ?? '') ?: null,
            'invoice_info'    => trim($_POST['invoice_info'] ?? '') ?: null,
        ];
        $dedupeKey = hash('sha256', $companyId . '|' . mb_strtolower($firstName) . '|' . mb_strtolower($lastName) . '|' . ($phoneFormatted ?? '') . '|' . ($email ?? ''));
        $lastCreate = request_dedupe_hit('customer_create', $dedupeKey);
        if ($lastCreate !== null && !empty($lastCreate['id']) && Customer::findOne($this->pdo, (string) $lastCreate['id'])) {
            $this->redirectAfterCreate($_POST['redirect_to'] ?? '', ['id' => (string) $lastCreate['id']]);
        }
        try {
            $customer = Customer::create($this->pdo, $data);
        } catch (Throwable $e) {
            Auth::setSession('flash_error', 'Müşteri eklenemedi: ' . $e->getMessage());
            $this->redirectAfterCreate($_POST['redirect_to'] ?? '', null);
            exit;
        }
        $name = trim($firstName . ' ' . $lastName);
        $actorName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        Notification::createForCompany($this->pdo, $companyId, 'customer', 'Müşteri eklendi', $name . ' müşterisi eklendi.', ['customer_id' => $customer['id'], 'actor_name' => $actorName]);
        request_dedupe_store('customer_create', $dedupeKey, ['id' => $customer['id']]);
        $redirectTo = $_POST['redirect_to'] ?? '';
        if ($redirectTo === 'new_sale') {
            Auth::setSession('flash_success', 'Müşteri eklendi. Yeni satış formunda seçebilirsiniz.');
            header('Location: /girisler?newSale=1&newCustomerId=' . urlencode($customer['id']));
        } elseif ($redirectTo === 'new_job') {
            Auth::setSession('flash_success', 'Müşteri eklendi. Nakliye formunda seçebilirsiniz.');
            header('Location: /nakliye-isler?newCustomerId=' . urlencode($customer['id']));
        } else {
            Auth::setSession('flash_success', 'Müşteri eklendi.');
            header('Location: /musteriler');
        }
        exit;
    }

    private function redirectAfterCreate(string $redirectTo, ?array $customer): void
    {
        $customerId = $customer['id'] ?? null;
        if ($redirectTo === 'new_sale') {
            $url = '/girisler?newSale=1';
            if ($customerId) {
                $url .= '&newCustomerId=' . urlencode((string) $customerId);
            }
            header('Location: ' . $url);
        } elseif ($redirectTo === 'new_job') {
            $url = '/nakliye-isler';
            if ($customerId) {
                $url .= '?newCustomerId=' . urlencode((string) $customerId);
            }
            header('Location: ' . $url);
        } else {
            header('Location: /musteriler');
        }
        exit;
    }

    /** AJAX: müşteri arama (Yeni Satış vb. formlar için) */
    public function apiSearch(): void
    {
        Auth::requireStaff();
        header('Content-Type: application/json; charset=utf-8');
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        $searchCompanyId = $companyId;
        if ($searchCompanyId === null && ($user['role'] ?? '') !== 'super_admin') {
            echo json_encode(['data' => []]);
            return;
        }
        $id = trim($_GET['id'] ?? '');
        if ($id !== '') {
            $row = Customer::findOne($this->pdo, $id);
            if (!$row) {
                echo json_encode(['data' => null]);
                return;
            }
            if ($searchCompanyId !== null && ($row['company_id'] ?? '') !== $searchCompanyId) {
                echo json_encode(['data' => null]);
                return;
            }
            echo json_encode(['data' => [
                'id' => $row['id'],
                'name' => trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')),
                'phone' => $row['phone'] ?? null,
                'email' => $row['email'] ?? null,
            ]]);
            return;
        }
        $q = trim($_GET['q'] ?? '');
        $limit = min(100, max(1, (int) ($_GET['limit'] ?? 50)));
        $rows = Customer::findAll($this->pdo, $searchCompanyId, $q !== '' ? $q : null, $limit);
        $data = array_map(static function (array $row): array {
            return [
                'id' => $row['id'],
                'name' => trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')),
                'phone' => $row['phone'] ?? null,
                'email' => $row['email'] ?? null,
            ];
        }, $rows);
        echo json_encode(['data' => $data, 'total' => Customer::count($this->pdo, $searchCompanyId)]);
    }

    /** Excel (CSV) dışa aktar – mevcut filtreye göre müşteri listesini indirir */
    public function exportCsv(): void
    {
        Auth::requireStaff();
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        $search = isset($_GET['q']) ? trim($_GET['q']) : null;
        $inDepo = isset($_GET['in_depo']) && in_array($_GET['in_depo'], ['yes', 'no'], true) ? $_GET['in_depo'] : null;
        $warehouseId = isset($_GET['warehouse_id']) && $_GET['warehouse_id'] !== '' ? trim($_GET['warehouse_id']) : null;
        if (!array_key_exists('borc', $_GET)) {
            $debtFilter = null;
        } elseif (trim((string) $_GET['borc']) === '') {
            $debtFilter = null;
        } elseif (in_array($_GET['borc'], ['overdue', 'unpaid'], true)) {
            $debtFilter = $_GET['borc'];
        } else {
            $debtFilter = null;
        }
        if ($companyId) {
            $customers = Customer::findAll($this->pdo, $companyId, $search, null, 0, $inDepo, $warehouseId, $debtFilter);
        } elseif (($user['role'] ?? '') === 'super_admin') {
            $customers = Customer::findAll($this->pdo, null, $search, null, 0, $inDepo, $warehouseId, $debtFilter);
        } else {
            $customers = [];
        }

        $filename = 'musteriler_' . date('Y-m-d_His') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'wb');
        fprintf($out, "\xEF\xBB\xBF"); // UTF-8 BOM (Excel için)
        $headers = ['Müşteri ID', 'Ad', 'Soyad', 'E-posta', 'Telefon', 'TC Kimlik No', 'Adres', 'Notlar', 'Aktif'];
        if ($debtFilter === 'overdue') {
            $headers[] = 'Gecikmiş ödeme sayısı';
            $headers[] = 'Gecikmiş borç (₺)';
        } elseif ($debtFilter === 'unpaid') {
            $headers[] = 'Ödenmemiş ödeme sayısı';
            $headers[] = 'Ödenmemiş borç (₺)';
        }
        fputcsv($out, $headers, ';');

        foreach ($customers as $c) {
            $row = [
                $c['id'] ?? '',
                $c['first_name'] ?? '',
                $c['last_name'] ?? '',
                $c['email'] ?? '',
                $c['phone'] ?? '',
                $c['identity_number'] ?? '',
                $c['address'] ?? '',
                $c['notes'] ?? '',
                !empty($c['is_active']) ? 'Evet' : 'Hayır',
            ];
            if ($debtFilter === 'overdue') {
                $row[] = (int) ($c['overdue_payment_count'] ?? 0);
                $row[] = number_format((float) ($c['overdue_debt_total'] ?? 0), 2, ',', '.');
            } elseif ($debtFilter === 'unpaid') {
                $row[] = (int) ($c['unpaid_payment_count'] ?? 0);
                $row[] = number_format((float) ($c['unpaid_debt_total'] ?? 0), 2, ',', '.');
            }
            fputcsv($out, $row, ';');
        }
        fclose($out);
        exit;
    }

    /** Excel (CSV) şablonu indir – sadece başlık satırı */
    public function downloadTemplate(): void
    {
        Auth::requireStaff();
        $filename = 'musteri_sablonu.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        $out = fopen('php://output', 'wb');
        fprintf($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Müşteri ID', 'Ad', 'Soyad', 'E-posta', 'Telefon', 'TC Kimlik No', 'Adres', 'Notlar', 'Aktif'], ';');
        fputcsv($out, ['', 'Ahmet', 'Yılmaz', 'ahmet@ornek.com', '05551234567', '', 'İstanbul', 'Not', 'Evet'], ';');
        fputcsv($out, ['', 'Ayşe', 'Demir', 'ayse@ornek.com', '', '12345678901', 'Ankara', '', 'Evet'], ';');
        fclose($out);
        exit;
    }

    /** Excel (CSV) içe aktar – form göster */
    public function importForm(): void
    {
        Auth::requireStaff();
        ['success' => $flashSuccess, 'error' => $flashError] = Auth::consumeFlash();
        $customers = [];
        $currentPage = 'musteriler';
        $q = '';
        require __DIR__ . '/../../views/customers/import.php';
    }

    /** Excel (CSV) içe aktar – dosyayı işle */
    public function importCsv(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /musteriler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if (!$companyId) {
            Auth::setSession('flash_error', 'Şirket bilgisi bulunamadı.');
            header('Location: /musteriler/excel-ice-aktar');
            exit;
        }

        $file = $_FILES['csv_file'] ?? null;
        if (!$file || ($file['error'] ?? 0) !== UPLOAD_ERR_OK) {
            Auth::setSession('flash_error', 'Lütfen bir CSV dosyası seçin veya yükleme hatası oluştu.');
            header('Location: /musteriler/excel-ice-aktar');
            exit;
        }

        $tmp = $file['tmp_name'];
        $handle = @fopen($tmp, 'rb');
        if (!$handle) {
            Auth::setSession('flash_error', 'Dosya okunamadı.');
            header('Location: /musteriler/excel-ice-aktar');
            exit;
        }

        // İlk satır BOM veya başlık olabilir
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $first = fgetcsv($handle, 0, ';');
        if ($first === false) {
            $first = fgetcsv($handle, 0, ',');
            rewind($handle);
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($handle);
            }
            $first = fgetcsv($handle, 0, ',');
        }
        $delimiter = ';';
        if ($first !== false && count($first) === 1 && strpos($first[0], ',') !== false) {
            $delimiter = ',';
            rewind($handle);
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($handle);
            }
            $first = fgetcsv($handle, 0, ',');
        }

        $added = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        $batchSeen = [];

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $parsed = parseCustomerImportRow($row);
            if ($parsed === null) {
                continue;
            }

            $firstName = $parsed['first_name'];
            $lastName = $parsed['last_name'];
            $email = $parsed['email'];
            $phone = $parsed['phone'];
            $identityNumber = $parsed['identity_number'];
            $address = $parsed['address'];
            $notes = $parsed['notes'];
            $isActive = $parsed['is_active'];
            $customerId = $parsed['customer_id'];
            $externalId = $parsed['external_id'];

            if ($identityNumber !== null && !validateTcIdentity($identityNumber)) {
                $errors[] = $firstName . ' ' . $lastName . ': TC Kimlik No en fazla 11 haneli rakam olmalıdır.';
                $skipped++;
                continue;
            }
            if ($phone !== null && $phone !== '' && !validatePhone($phone)) {
                $errors[] = $firstName . ' ' . $lastName . ': Telefon formatı geçersiz (örn: 05551234567).';
                $skipped++;
                continue;
            }
            $phoneFormatted = ($phone !== null && $phone !== '') ? formatPhoneInput($phone) : null;

            $matchKey = customerImportMatchKey($customerId, $externalId, $email, $phoneFormatted, $identityNumber);
            $existing = null;
            if ($matchKey !== null && isset($batchSeen[$matchKey])) {
                $existing = Customer::findOneForCompany($this->pdo, $batchSeen[$matchKey], $companyId);
            }
            if (!$existing && $customerId !== null && $customerId !== '') {
                $existing = Customer::findOneForCompany($this->pdo, $customerId, $companyId);
            }
            if (!$existing && $externalId !== null && $externalId !== '') {
                $existing = Customer::findByExternalId($this->pdo, $companyId, $externalId);
            }
            if (!$existing && $email !== '') {
                $existing = Customer::findByEmail($this->pdo, $companyId, $email, null);
            }
            if (!$existing && $phoneFormatted !== null) {
                $existing = Customer::findByPhoneNormalized($this->pdo, $companyId, $phoneFormatted, null);
            }
            if (!$existing && $identityNumber !== null && $identityNumber !== '') {
                $existing = Customer::findByIdentityNumber($this->pdo, $companyId, $identityNumber, null);
            }

            try {
                $data = [
                    'company_id'      => $companyId,
                    'first_name'      => $firstName,
                    'last_name'       => $lastName,
                    'email'           => $email,
                    'phone'           => $phoneFormatted,
                    'identity_number' => $identityNumber,
                    'address'         => $address,
                    'notes'           => $notes,
                    'is_active'       => $isActive,
                    'external_id'     => $externalId,
                ];
                if ($existing) {
                    Customer::update($this->pdo, $existing['id'], $data);
                    $updated++;
                    if ($matchKey !== null) {
                        $batchSeen[$matchKey] = $existing['id'];
                    }
                } else {
                    $created = Customer::create($this->pdo, $data);
                    $added++;
                    if ($matchKey !== null && !empty($created['id'])) {
                        $batchSeen[$matchKey] = $created['id'];
                    }
                }
            } catch (Throwable $e) {
                $errors[] = $firstName . ' ' . $lastName . ': ' . $e->getMessage();
                $skipped++;
            }
        }
        fclose($handle);

        $processed = $added + $updated;
        if ($processed > 0) {
            $actorName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
            Notification::createForCompany($this->pdo, $companyId, 'customer', 'Toplu müşteri içe aktarma', $processed . ' müşteri Excel ile işlendi.' . ($skipped > 0 ? ' ' . $skipped . ' kayıt atlandı.' : ''), ['actor_name' => $actorName]);
            $successParts = [];
            if ($added > 0) {
                $successParts[] = $added . ' yeni eklendi';
            }
            if ($updated > 0) {
                $successParts[] = $updated . ' güncellendi';
            }
            $successMsg = implode(', ', $successParts) . '.';
            if ($skipped > 0) {
                $successMsg .= ' ' . $skipped . ' kayıt atlandı.';
            }
            Auth::setSession('flash_success', $successMsg);
        }
        if (!empty($errors)) {
            Auth::setSession('flash_error', implode(' ', array_slice($errors, 0, 3)) . (count($errors) > 3 ? ' …' : ''));
        } elseif ($processed === 0 && $skipped === 0) {
            Auth::setSession('flash_error', 'İşlenecek geçerli satır bulunamadı. CSV formatı: Müşteri ID; Ad; Soyad; E-posta; Telefon; TC Kimlik No; Adres; Notlar; Aktif (Müşteri ID boş bırakılabilir)');
        }

        header('Location: /musteriler/excel-ice-aktar');
        exit;
    }
}
