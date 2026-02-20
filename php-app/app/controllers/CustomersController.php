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
        $perPage = 50;
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $offset = ($page - 1) * $perPage;

        if ($companyId) {
            $customersTotal = Customer::count($this->pdo, $companyId, $search);
            $customers = Customer::findAll($this->pdo, $companyId, $search, $perPage, $offset);
        } elseif (($user['role'] ?? '') === 'super_admin') {
            $customersTotal = Customer::count($this->pdo, null, $search);
            $customers = Customer::findAll($this->pdo, null, $search, $perPage, $offset);
        } else {
            $customersTotal = 0;
            $customers = [];
        }

        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
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
            $_SESSION['flash_error'] = 'Silinecek müşteri seçin.';
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
            if (Customer::softDelete($this->pdo, $id)) {
                $deleted++;
            }
        }
        if ($deleted > 0) {
            $_SESSION['flash_success'] = $deleted . ' müşteri silindi.';
        } else {
            $_SESSION['flash_error'] = 'Seçilen müşteriler silinemedi veya yetkiniz yok.';
        }
        header('Location: /musteriler' . (isset($_GET['q']) && trim($_GET['q']) !== '' ? '?q=' . urlencode(trim($_GET['q'])) : ''));
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
            $_SESSION['flash_error'] = 'Müşteri bulunamadı.';
            header('Location: /musteriler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($customer['company_id'] ?? '') !== $companyId) {
            $_SESSION['flash_error'] = 'Bu müşteriye erişim yetkiniz yok.';
            header('Location: /musteriler');
            exit;
        }
        $contracts = Contract::findByCustomerId($this->pdo, $id, $companyId);
        $payments = Payment::findByCustomerId($this->pdo, $id, $companyId);
        $debt = Payment::sumUnpaidByCustomerId($this->pdo, $id, $companyId);
        try {
            $debt += CustomerCharge::sumUnpaidByCustomerId($this->pdo, $id, $companyId);
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
        $pageTitle = 'Müşteri: ' . trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
        require __DIR__ . '/../../views/customers/detail.php';
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
            $_SESSION['flash_error'] = 'Müşteri bulunamadı.';
            header('Location: /musteriler');
            exit;
        }
        $customer = Customer::findOne($this->pdo, $id);
        if (!$customer) {
            $_SESSION['flash_error'] = 'Müşteri bulunamadı.';
            header('Location: /musteriler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($customer['company_id'] ?? '') !== $companyId) {
            $_SESSION['flash_error'] = 'Bu müşteriye erişim yetkiniz yok.';
            header('Location: /musteriler');
            exit;
        }
        $phone = trim($customer['phone'] ?? '');
        if ($phone === '') {
            $_SESSION['flash_error'] = 'Müşteride telefon numarası kayıtlı değil.';
            header('Location: /musteriler/' . $id);
            exit;
        }
        $message = trim($_POST['message'] ?? '');
        if ($message === '') {
            $_SESSION['flash_error'] = 'Mesaj metni girin.';
            header('Location: /musteriler/' . $id);
            exit;
        }
        $result = SmsService::send($this->pdo, $customer['company_id'], $phone, $message);
        if ($result['success']) {
            $_SESSION['flash_success'] = 'SMS gönderildi.';
        } else {
            $_SESSION['flash_error'] = $result['error'] ?? 'SMS gönderilemedi.';
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
            $_SESSION['flash_error'] = 'Müşteri bulunamadı.';
            header('Location: /musteriler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($customer['company_id'] ?? '') !== $companyId) {
            $_SESSION['flash_error'] = 'Bu müşteriye erişim yetkiniz yok.';
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
            $_SESSION['flash_error'] = 'Müşteri ve tutar (0\'dan büyük) zorunludur.';
            header('Location: /musteriler/' . $customerId);
            exit;
        }
        $customer = Customer::findOne($this->pdo, $customerId);
        if (!$customer) {
            $_SESSION['flash_error'] = 'Müşteri bulunamadı.';
            header('Location: /musteriler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($customer['company_id'] ?? '') !== $companyId) {
            $_SESSION['flash_error'] = 'Bu müşteriye erişim yetkiniz yok.';
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
        $_SESSION['flash_success'] = 'Borç kaydı eklendi.';
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
            $_SESSION['flash_error'] = 'Müşteri bulunamadı.';
            header('Location: /musteriler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($customer['company_id'] ?? '') !== $companyId) {
            $_SESSION['flash_error'] = 'Bu müşteriye erişim yetkiniz yok.';
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
            $_SESSION['flash_error'] = 'Müşteri gerekli.';
            header('Location: /musteriler');
            exit;
        }
        $customer = Customer::findOne($this->pdo, $customerId);
        if (!$customer) {
            $_SESSION['flash_error'] = 'Müşteri bulunamadı.';
            header('Location: /musteriler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($customer['company_id'] ?? '') !== $companyId) {
            $_SESSION['flash_error'] = 'Bu müşteriye erişim yetkiniz yok.';
            header('Location: /musteriler');
            exit;
        }
        $file = $_FILES['document'] ?? null;
        if (!$file || ($file['error'] ?? 0) !== UPLOAD_ERR_OK) {
            $_SESSION['flash_error'] = 'Lütfen bir dosya seçin veya yükleme hatası oluştu.';
            header('Location: /musteriler/' . $customerId);
            exit;
        }
        $allowedExt = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'doc', 'docx'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt, true)) {
            $_SESSION['flash_error'] = 'İzin verilen formatlar: ' . implode(', ', $allowedExt);
            header('Location: /musteriler/' . $customerId);
            exit;
        }
        $uploadDir = defined('APP_ROOT') ? APP_ROOT . '/public/uploads/customer_documents' : __DIR__ . '/../../public/uploads/customer_documents';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }
        $filename = $customerId . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $filePath = $uploadDir . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            $_SESSION['flash_error'] = 'Dosya kaydedilemedi.';
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
        $_SESSION['flash_success'] = 'Belge eklendi.';
        header('Location: /musteriler/' . $customerId);
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
            $_SESSION['flash_error'] = 'Belge seçilmedi.';
            header('Location: ' . $redirect);
            exit;
        }
        $doc = CustomerDocument::findOne($this->pdo, $docId);
        if (!$doc) {
            $_SESSION['flash_error'] = 'Belge bulunamadı.';
            header('Location: ' . $redirect);
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($doc['company_id'] ?? '') !== $companyId) {
            $_SESSION['flash_error'] = 'Yetkisiz.';
            header('Location: /musteriler');
            exit;
        }
        CustomerDocument::softDelete($this->pdo, $docId);
        $_SESSION['flash_success'] = 'Belge silindi.';
        header('Location: /musteriler/' . ($doc['customer_id'] ?? ''));
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
            $_SESSION['flash_error'] = 'Müşteri bulunamadı.';
            header('Location: /musteriler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($customer['company_id'] ?? '') !== $companyId) {
            $_SESSION['flash_error'] = 'Bu müşteriye erişim yetkiniz yok.';
            header('Location: /musteriler');
            exit;
        }
        $notes = trim($_POST['notes'] ?? '');
        Customer::update($this->pdo, $id, ['notes' => $notes]);
        $_SESSION['flash_success'] = 'Not güncellendi.';
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
            $_SESSION['flash_error'] = 'Müşteri bulunamadı.';
            header('Location: /musteriler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($customer['company_id'] ?? '') !== $companyId) {
            $_SESSION['flash_error'] = 'Bu müşteriye erişim yetkiniz yok.';
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
            $_SESSION['flash_error'] = 'Müşteri bulunamadı.';
            header('Location: /musteriler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($customer['company_id'] ?? '') !== $companyId) {
            $_SESSION['flash_error'] = 'Bu müşteriye erişim yetkiniz yok.';
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
            $_SESSION['flash_error'] = 'Müşteri bulunamadı.';
            header('Location: /musteriler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($customer['company_id'] ?? '') !== $companyId) {
            $_SESSION['flash_error'] = 'Bu müşteriye erişim yetkiniz yok.';
            header('Location: /musteriler');
            exit;
        }
        $items = Item::findByCustomerId($this->pdo, $id);
        $payments = Payment::findByCustomerId($this->pdo, $id, $companyId);
        $paidMonths = [];
        foreach ($payments as $p) {
            if (($p['status'] ?? '') === 'paid' && !empty($p['due_date'])) {
                $paidMonths[date('Y-m', strtotime($p['due_date']))] = true;
            }
        }
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
            $_SESSION['flash_error'] = 'Şirket bilgisi bulunamadı.';
            header('Location: /musteriler');
            exit;
        }
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        if ($firstName === '' || $lastName === '') {
            $_SESSION['flash_error'] = 'Ad ve soyad zorunludur.';
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
        $identityNumber = trim($_POST['identity_number'] ?? '') ?: null;
        if ($identityNumber !== null && !validateTcIdentity($identityNumber)) {
            $_SESSION['flash_error'] = 'TC Kimlik No 11 haneli rakam olmalıdır.';
            $this->redirectAfterCreate($_POST['redirect_to'] ?? '', null);
            exit;
        }
        if ($phone !== null && !validatePhone($phone)) {
            $_SESSION['flash_error'] = 'Telefon formatı geçersiz. Örn: 05551234567';
            $this->redirectAfterCreate($_POST['redirect_to'] ?? '', null);
            exit;
        }
        if ($email !== '' && !validateEmail($email)) {
            $_SESSION['flash_error'] = 'E-posta formatı geçersiz.';
            $this->redirectAfterCreate($_POST['redirect_to'] ?? '', null);
            exit;
        }
        if ($email === '') {
            $email = 'musteri-' . bin2hex(random_bytes(4)) . '@depopazar.local';
        }
        $data = [
            'company_id'      => $companyId,
            'first_name'      => $firstName,
            'last_name'       => $lastName,
            'email'           => $email,
            'phone'           => formatPhoneInput($phone),
            'identity_number' => $identityNumber,
            'address'         => trim($_POST['address'] ?? '') ?: null,
            'notes'           => trim($_POST['notes'] ?? '') ?: null,
        ];
        try {
            $customer = Customer::create($this->pdo, $data);
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = 'Müşteri eklenemedi: ' . $e->getMessage();
            $this->redirectAfterCreate($_POST['redirect_to'] ?? '', null);
            exit;
        }
        $name = trim($firstName . ' ' . $lastName);
        $actorName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        Notification::createForCompany($this->pdo, $companyId, 'customer', 'Müşteri eklendi', $name . ' müşterisi eklendi.', ['customer_id' => $customer['id'], 'actor_name' => $actorName]);
        $redirectTo = $_POST['redirect_to'] ?? '';
        if ($redirectTo === 'new_sale') {
            $_SESSION['flash_success'] = 'Müşteri eklendi. Yeni satış formunda seçebilirsiniz.';
            header('Location: /girisler?newSale=1&newCustomerId=' . urlencode($customer['id']));
        } elseif ($redirectTo === 'new_job') {
            $_SESSION['flash_success'] = 'Müşteri eklendi. Nakliye formunda seçebilirsiniz.';
            header('Location: /nakliye-isler?newCustomerId=' . urlencode($customer['id']));
        } else {
            $_SESSION['flash_success'] = 'Müşteri eklendi.';
            header('Location: /musteriler');
        }
        exit;
    }

    private function redirectAfterCreate(string $redirectTo, ?array $customer): void
    {
        if ($redirectTo === 'new_sale') {
            header('Location: /girisler?newSale=1');
        } elseif ($redirectTo === 'new_job') {
            header('Location: /nakliye-isler');
        } else {
            header('Location: /musteriler');
        }
        exit;
    }

    /** Excel (CSV) dışa aktar – mevcut filtreye göre müşteri listesini indirir */
    public function exportCsv(): void
    {
        Auth::requireStaff();
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        $search = isset($_GET['q']) ? trim($_GET['q']) : null;
        if ($companyId) {
            $customers = Customer::findAll($this->pdo, $companyId, $search);
        } elseif (($user['role'] ?? '') === 'super_admin') {
            $customers = Customer::findAll($this->pdo, null, $search);
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
        $headers = ['Ad', 'Soyad', 'E-posta', 'Telefon', 'TC Kimlik No', 'Adres', 'Notlar', 'Aktif'];
        fputcsv($out, $headers, ';');

        foreach ($customers as $c) {
            fputcsv($out, [
                $c['first_name'] ?? '',
                $c['last_name'] ?? '',
                $c['email'] ?? '',
                $c['phone'] ?? '',
                $c['identity_number'] ?? '',
                $c['address'] ?? '',
                $c['notes'] ?? '',
                !empty($c['is_active']) ? 'Evet' : 'Hayır',
            ], ';');
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
        fputcsv($out, ['Ad', 'Soyad', 'E-posta', 'Telefon', 'TC Kimlik No', 'Adres', 'Notlar', 'Aktif'], ';');
        fputcsv($out, ['Ahmet', 'Yılmaz', 'ahmet@ornek.com', '05551234567', '', 'İstanbul', 'Not', 'Evet'], ';');
        fputcsv($out, ['Ayşe', 'Demir', 'ayse@ornek.com', '', '12345678901', 'Ankara', '', 'Evet'], ';');
        fclose($out);
        exit;
    }

    /** Excel (CSV) içe aktar – form göster */
    public function importForm(): void
    {
        Auth::requireStaff();
        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
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
            $_SESSION['flash_error'] = 'Şirket bilgisi bulunamadı.';
            header('Location: /musteriler/excel-ice-aktar');
            exit;
        }

        $file = $_FILES['csv_file'] ?? null;
        if (!$file || ($file['error'] ?? 0) !== UPLOAD_ERR_OK) {
            $_SESSION['flash_error'] = 'Lütfen bir CSV dosyası seçin veya yükleme hatası oluştu.';
            header('Location: /musteriler/excel-ice-aktar');
            exit;
        }

        $tmp = $file['tmp_name'];
        $handle = @fopen($tmp, 'rb');
        if (!$handle) {
            $_SESSION['flash_error'] = 'Dosya okunamadı.';
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
        $skipped = 0;
        $errors = [];

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $row = array_map('trim', $row);
            if (count($row) < 2) {
                continue;
            }
            $firstName = $row[0] ?? '';
            $lastName = $row[1] ?? '';
            if ($firstName === '' && $lastName === '') {
                continue;
            }
            if (stripos($firstName, 'ad') !== false && stripos($lastName, 'soyad') !== false) {
                continue; // başlık satırı
            }
            $email = $row[2] ?? '';
            $phone = isset($row[3]) ? trim($row[3]) : null;
            $identityNumber = isset($row[4]) && $row[4] !== '' ? trim($row[4]) : null;
            $address = isset($row[5]) && $row[5] !== '' ? trim($row[5]) : null;
            $notes = isset($row[6]) && $row[6] !== '' ? trim($row[6]) : null;
            $isActive = 1;
            if (isset($row[7])) {
                $v = trim($row[7]);
                if (stripos($v, 'hayır') !== false || $v === '0' || strtolower($v) === 'no') {
                    $isActive = 0;
                }
            }

            try {
                $data = [
                    'company_id'      => $companyId,
                    'first_name'      => $firstName,
                    'last_name'       => $lastName,
                    'email'           => $email,
                    'phone'           => $phone ?: null,
                    'identity_number' => $identityNumber,
                    'address'         => $address,
                    'notes'           => $notes,
                    'is_active'       => $isActive,
                ];
                Customer::create($this->pdo, $data);
                $added++;
            } catch (Throwable $e) {
                $errors[] = $firstName . ' ' . $lastName . ': ' . $e->getMessage();
                $skipped++;
            }
        }
        fclose($handle);

        if ($added > 0) {
            $actorName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
            Notification::createForCompany($this->pdo, $companyId, 'customer', 'Toplu müşteri ekleme', $added . ' müşteri Excel ile eklendi.' . ($skipped > 0 ? ' ' . $skipped . ' kayıt atlandı.' : ''), ['actor_name' => $actorName]);
            $_SESSION['flash_success'] = $added . ' müşteri eklendi.';
            if ($skipped > 0) {
                $_SESSION['flash_success'] .= ' ' . $skipped . ' kayıt atlandı.';
            }
        }
        if (!empty($errors)) {
            $_SESSION['flash_error'] = implode(' ', array_slice($errors, 0, 3)) . (count($errors) > 3 ? ' …' : '');
        } elseif ($added === 0 && $skipped === 0) {
            $_SESSION['flash_error'] = 'İşlenecek geçerli satır bulunamadı. CSV formatı: Ad; Soyad; E-posta; Telefon; TC Kimlik No; Adres; Notlar; Aktif';
        }

        header('Location: /musteriler/excel-ice-aktar');
        exit;
    }
}
