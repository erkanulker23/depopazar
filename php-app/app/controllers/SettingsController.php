<?php
class SettingsController
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
        if (!$companyId) {
            $_SESSION['flash_error'] = 'Şirket bilgisi bulunamadı. Ayarlar yalnızca şirket kullanıcıları için geçerlidir.';
            header('Location: /genel-bakis');
            exit;
        }
        $company = Company::findOne($this->pdo, $companyId);
        if (!$company) {
            $_SESSION['flash_error'] = 'Şirket bulunamadı.';
            header('Location: /genel-bakis');
            exit;
        }
        $bankAccounts = BankAccount::findAll($this->pdo, $companyId);
        $mailSettings = $this->getMailSettings($companyId);
        $paytrSettings = $this->getPaytrSettings($companyId);
        $activeTab = $_GET['tab'] ?? 'firma';
        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        $pageTitle = 'Ayarlar';
        require __DIR__ . '/../../views/settings/index.php';
    }

    public function updateCompany(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /ayarlar');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if (!$companyId) {
            $_SESSION['flash_error'] = 'Şirket bilgisi bulunamadı.';
            header('Location: /genel-bakis');
            exit;
        }
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'project_name' => trim($_POST['project_name'] ?? '') ?: null,
            'email' => trim($_POST['email'] ?? '') ?: null,
            'phone' => trim($_POST['phone'] ?? '') ?: null,
            'whatsapp_number' => trim($_POST['whatsapp_number'] ?? '') ?: null,
            'address' => trim($_POST['address'] ?? '') ?: null,
            'mersis_number' => trim($_POST['mersis_number'] ?? '') ?: null,
            'tax_office' => trim($_POST['tax_office'] ?? '') ?: null,
        ];
        $uploadDir = defined('APP_ROOT') ? APP_ROOT . '/public/uploads/company' : __DIR__ . '/../../public/uploads/company';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }
        if (!empty($_FILES['logo']['name']) && ($_FILES['logo']['error'] ?? 0) === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
                $filename = 'logo_' . $companyId . '_' . time() . '.' . $ext;
                $path = $uploadDir . '/' . $filename;
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $path)) {
                    $data['logo_url'] = '/uploads/company/' . $filename;
                }
            }
        }
        if (!empty($_FILES['contract_pdf']['name']) && ($_FILES['contract_pdf']['error'] ?? 0) === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['contract_pdf']['name'], PATHINFO_EXTENSION));
            if ($ext === 'pdf') {
                $filename = 'contract_' . $companyId . '_' . time() . '.pdf';
                $path = $uploadDir . '/' . $filename;
                if (move_uploaded_file($_FILES['contract_pdf']['tmp_name'], $path)) {
                    $data['contract_template_url'] = '/uploads/company/' . $filename;
                }
            }
        }
        Company::update($this->pdo, $companyId, $data);
        $company = Company::findOne($this->pdo, $companyId);
        $_SESSION['company_project_name'] = $company['project_name'] ?? $company['name'] ?? 'DepoPazar';
        $actorName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        Notification::createForCompany($this->pdo, $companyId, 'settings', 'Firma bilgileri güncellendi', 'Firma bilgileri ' . ($actorName ?: 'sistem') . ' tarafından güncellendi.', ['actor_name' => $actorName]);
        $_SESSION['flash_success'] = 'Firma bilgileri güncellendi.';
        header('Location: /ayarlar?tab=firma');
        exit;
    }

    public function createBankAccount(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /ayarlar?tab=banka');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if (!$companyId) {
            $_SESSION['flash_error'] = 'Şirket bilgisi bulunamadı.';
            header('Location: /genel-bakis');
            exit;
        }
        $bankName = trim($_POST['bank_name'] ?? '');
        $accountHolder = trim($_POST['account_holder_name'] ?? '');
        $accountNumber = trim($_POST['account_number'] ?? '');
        if ($bankName === '' || $accountHolder === '' || $accountNumber === '') {
            $_SESSION['flash_error'] = 'Banka adı, hesap sahibi ve hesap numarası zorunludur.';
            header('Location: /ayarlar?tab=banka');
            exit;
        }
        BankAccount::create($this->pdo, [
            'company_id' => $companyId,
            'bank_name' => $bankName,
            'account_holder_name' => $accountHolder,
            'account_number' => $accountNumber,
            'iban' => trim($_POST['iban'] ?? '') ?: null,
            'branch_name' => trim($_POST['branch_name'] ?? '') ?: null,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ]);
        $actorName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        Notification::createForCompany($this->pdo, $companyId, 'bank', 'Banka hesabı eklendi', $bankName . ' banka hesabı ' . ($actorName ?: 'sistem') . ' tarafından eklendi.', ['actor_name' => $actorName]);
        $_SESSION['flash_success'] = 'Banka hesabı eklendi.';
        header('Location: /ayarlar?tab=banka');
        exit;
    }

    public function updateBankAccount(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /ayarlar?tab=banka');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if (!$companyId) {
            $_SESSION['flash_error'] = 'Şirket bilgisi bulunamadı.';
            header('Location: /genel-bakis');
            exit;
        }
        $id = trim($_POST['id'] ?? '');
        if (!$id) {
            header('Location: /ayarlar?tab=banka');
            exit;
        }
        $data = [
            'bank_name' => trim($_POST['bank_name'] ?? ''),
            'account_holder_name' => trim($_POST['account_holder_name'] ?? ''),
            'account_number' => trim($_POST['account_number'] ?? ''),
            'iban' => trim($_POST['iban'] ?? '') ?: null,
            'branch_name' => trim($_POST['branch_name'] ?? '') ?: null,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];
        BankAccount::update($this->pdo, $id, $data, $companyId);
        $_SESSION['flash_success'] = 'Banka hesabı güncellendi.';
        header('Location: /ayarlar?tab=banka');
        exit;
    }

    public function deleteBankAccount(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /ayarlar?tab=banka');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if (!$companyId) {
            $_SESSION['flash_error'] = 'Şirket bilgisi bulunamadı.';
            header('Location: /genel-bakis');
            exit;
        }
        $id = trim($_POST['id'] ?? '');
        if ($id) {
            BankAccount::remove($this->pdo, $id, $companyId);
            $_SESSION['flash_success'] = 'Banka hesabı silindi.';
        }
        header('Location: /ayarlar?tab=banka');
        exit;
    }

    public function updateMailSettings(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /ayarlar?tab=eposta');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if (!$companyId) {
            $_SESSION['flash_error'] = 'Şirket bilgisi bulunamadı.';
            header('Location: /genel-bakis');
            exit;
        }
        $existing = $this->getMailSettings($companyId);
        $data = [
            'smtp_host' => trim($_POST['smtp_host'] ?? '') ?: null,
            'smtp_port' => ($p = (int)($_POST['smtp_port'] ?? 0)) > 0 ? $p : null,
            'smtp_secure' => isset($_POST['smtp_secure']) ? 1 : 0,
            'smtp_username' => trim($_POST['smtp_username'] ?? '') ?: null,
            'smtp_password' => trim($_POST['smtp_password'] ?? '') ?: null,
            'from_email' => trim($_POST['from_email'] ?? '') ?: null,
            'from_name' => trim($_POST['from_name'] ?? '') ?: null,
            'notify_customer_on_contract' => isset($_POST['notify_customer_on_contract']) ? 1 : 0,
            'notify_customer_on_payment' => isset($_POST['notify_customer_on_payment']) ? 1 : 0,
            'notify_customer_on_overdue' => isset($_POST['notify_customer_on_overdue']) ? 1 : 0,
            'notify_admin_on_contract' => isset($_POST['notify_admin_on_contract']) ? 1 : 0,
            'notify_admin_on_payment' => isset($_POST['notify_admin_on_payment']) ? 1 : 0,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];
        if (!empty($_POST['smtp_password']) && trim($_POST['smtp_password']) !== '') {
            $data['smtp_password'] = trim($_POST['smtp_password']);
        } elseif ($existing && !empty($existing['smtp_password'])) {
            $data['smtp_password'] = $existing['smtp_password'];
        }

        if ($existing) {
            $set = [];
            $params = [];
            foreach ($data as $k => $v) {
                $set[] = "`$k` = ?";
                $params[] = $v;
            }
            $params[] = $existing['id'];
            $this->pdo->prepare('UPDATE company_mail_settings SET ' . implode(', ', $set) . ' WHERE id = ?')->execute($params);
        } else {
            $id = $this->uuid();
            $this->pdo->prepare('INSERT INTO company_mail_settings (id, company_id, smtp_host, smtp_port, smtp_secure, smtp_username, smtp_password, from_email, from_name, notify_customer_on_contract, notify_customer_on_payment, notify_customer_on_overdue, notify_admin_on_contract, notify_admin_on_payment, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
                ->execute([
                    $id, $companyId,
                    $data['smtp_host'], $data['smtp_port'], $data['smtp_secure'],
                    $data['smtp_username'], $data['smtp_password'] ?? null,
                    $data['from_email'], $data['from_name'],
                    $data['notify_customer_on_contract'], $data['notify_customer_on_payment'], $data['notify_customer_on_overdue'],
                    $data['notify_admin_on_contract'], $data['notify_admin_on_payment'],
                    $data['is_active'],
                ]);
        }
        $_SESSION['flash_success'] = 'E-posta ayarları güncellendi.';
        header('Location: /ayarlar?tab=eposta');
        exit;
    }

    public function updatePaytrSettings(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /ayarlar?tab=paytr');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if (!$companyId) {
            $_SESSION['flash_error'] = 'Şirket bilgisi bulunamadı.';
            header('Location: /genel-bakis');
            exit;
        }
        $existing = $this->getPaytrSettings($companyId);
        $merchantId = trim($_POST['merchant_id'] ?? '') ?: null;
        $merchantKey = trim($_POST['merchant_key'] ?? '') ?: null;
        $merchantSalt = trim($_POST['merchant_salt'] ?? '') ?: null;
        if (!empty($_POST['merchant_key'])) $merchantKey = trim($_POST['merchant_key']);
        elseif ($existing && !empty($existing['merchant_key'])) $merchantKey = $existing['merchant_key'];
        if (!empty($_POST['merchant_salt'])) $merchantSalt = trim($_POST['merchant_salt']);
        elseif ($existing && !empty($existing['merchant_salt'])) $merchantSalt = $existing['merchant_salt'];

        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $testMode = isset($_POST['test_mode']) ? 1 : 0;

        if ($existing) {
            $this->pdo->prepare('UPDATE company_paytr_settings SET merchant_id = ?, merchant_key = ?, merchant_salt = ?, is_active = ?, test_mode = ? WHERE id = ?')
                ->execute([$merchantId, $merchantKey, $merchantSalt, $isActive, $testMode, $existing['id']]);
        } else {
            $id = $this->uuid();
            $this->pdo->prepare('INSERT INTO company_paytr_settings (id, company_id, merchant_id, merchant_key, merchant_salt, is_active, test_mode) VALUES (?, ?, ?, ?, ?, ?, ?)')
                ->execute([$id, $companyId, $merchantId, $merchantKey, $merchantSalt, $isActive, $testMode]);
        }
        $_SESSION['flash_success'] = 'PayTR ayarları güncellendi.';
        header('Location: /ayarlar?tab=paytr');
        exit;
    }

    private function uuid(): string
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    public function testEmail(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /ayarlar?tab=eposta');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if (!$companyId) {
            $_SESSION['flash_error'] = 'Şirket bilgisi bulunamadı.';
            header('Location: /ayarlar?tab=eposta');
            exit;
        }
        $to = trim($_POST['test_email'] ?? '');
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_error'] = 'Geçerli bir e-posta adresi girin.';
            header('Location: /ayarlar?tab=eposta');
            exit;
        }
        $mail = $this->getMailSettings($companyId);
        if (!$mail || empty($mail['smtp_host'])) {
            $_SESSION['flash_error'] = 'E-posta ayarları eksik. SMTP bilgilerini kaydedin.';
            header('Location: /ayarlar?tab=eposta');
            exit;
        }
        $subject = 'DepoPazar E-posta Testi';
        $body = "Bu bir test e-postasıdır.\n\nE-posta ayarlarınız çalışıyor.";
        $headers = "From: " . ($mail['from_name'] ?? 'DepoPazar') . " <" . ($mail['from_email'] ?? 'noreply@depopazar.com') . ">\r\nContent-Type: text/plain; charset=UTF-8";
        $sent = @mail($to, $subject, $body, $headers);
        if ($sent) {
            $_SESSION['flash_success'] = 'Test e-postası gönderildi: ' . $to;
        } else {
            $_SESSION['flash_error'] = 'E-posta gönderilemedi. SMTP sunucusu ve port ayarlarını kontrol edin. Bazı sunucularda PHP mail() SMTP kullanmaz.';
        }
        header('Location: /ayarlar?tab=eposta');
        exit;
    }

    public function updateEmailTemplates(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /ayarlar?tab=sablonlar');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if (!$companyId) {
            $_SESSION['flash_error'] = 'Şirket bilgisi bulunamadı.';
            header('Location: /ayarlar?tab=sablonlar');
            exit;
        }
        $mail = $this->getMailSettings($companyId);
        $templates = [];
        foreach (['contract_created_template', 'payment_received_template', 'payment_reminder_template', 'admin_contract_created_template', 'admin_payment_received_template'] as $k) {
            $v = trim($_POST[$k] ?? '');
            $templates[$k] = $v !== '' ? $v : null;
        }
        try {
            if ($mail) {
                $stmt = $this->pdo->prepare('UPDATE company_mail_settings SET contract_created_template = ?, payment_received_template = ?, payment_reminder_template = ?, admin_contract_created_template = ?, admin_payment_received_template = ? WHERE company_id = ?');
                $stmt->execute([
                    $templates['contract_created_template'] ?: null,
                    $templates['payment_received_template'] ?: null,
                    $templates['payment_reminder_template'] ?: null,
                    $templates['admin_contract_created_template'] ?: null,
                    $templates['admin_payment_received_template'] ?: null,
                    $companyId,
                ]);
            } else {
                $id = $this->uuid();
                $stmt = $this->pdo->prepare('INSERT INTO company_mail_settings (id, company_id, contract_created_template, payment_received_template, payment_reminder_template, admin_contract_created_template, admin_payment_received_template) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([
                    $id,
                    $companyId,
                    $templates['contract_created_template'] ?: null,
                    $templates['payment_received_template'] ?: null,
                    $templates['payment_reminder_template'] ?: null,
                    $templates['admin_contract_created_template'] ?: null,
                    $templates['admin_payment_received_template'] ?: null,
                ]);
            }
            $_SESSION['flash_success'] = 'E-posta şablonları kaydedildi.';
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = 'Kaydedilemedi: ' . $e->getMessage();
        }
        header('Location: /ayarlar?tab=sablonlar');
        exit;
    }

    private function getMailSettings(string $companyId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM company_mail_settings WHERE company_id = ? AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([$companyId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function getPaytrSettings(string $companyId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM company_paytr_settings WHERE company_id = ? AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([$companyId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
