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
        Company::update($this->pdo, $companyId, $data);
        $company = Company::findOne($this->pdo, $companyId);
        $_SESSION['company_project_name'] = $company['project_name'] ?? $company['name'] ?? 'DepoPazar';
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
