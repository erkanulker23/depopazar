<?php
class ProposalsController
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
        $statusFilter = isset($_GET['durum']) && $_GET['durum'] !== '' ? trim($_GET['durum']) : null;
        if ($companyId) {
            $proposals = Proposal::findAll($this->pdo, $companyId, $statusFilter);
        } elseif (($user['role'] ?? '') === 'super_admin') {
            $proposals = Proposal::findAll($this->pdo, null, $statusFilter);
        } else {
            $proposals = [];
        }
        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        require __DIR__ . '/../../views/proposals/index.php';
    }

    public function newForm(): void
    {
        Auth::requireStaff();
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId) {
            $customers = Customer::findAll($this->pdo, $companyId);
        } elseif (($user['role'] ?? '') === 'super_admin') {
            $customers = Customer::findAll($this->pdo, null);
        } else {
            $customers = [];
        }
        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        require __DIR__ . '/../../views/proposals/new.php';
    }

    public function create(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /teklifler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if (!$companyId && ($user['role'] ?? '') !== 'super_admin') {
            $_SESSION['flash_error'] = 'Şirket bilgisi gerekli.';
            header('Location: /teklifler');
            exit;
        }
        if (!$companyId) {
            $row = $this->pdo->query('SELECT id FROM companies WHERE deleted_at IS NULL LIMIT 1')->fetch(PDO::FETCH_ASSOC);
            $companyId = $row ? $row['id'] : null;
        }
        if (!$companyId) {
            $_SESSION['flash_error'] = 'Şirket bulunamadı.';
            header('Location: /teklifler');
            exit;
        }
        $title = trim($_POST['title'] ?? '') ?: 'Teklif';
        $customerId = trim($_POST['customer_id'] ?? '') ?: null;
        if ($customerId) {
            $c = Customer::findOne($this->pdo, $customerId);
            if (!$c || ($companyId && ($c['company_id'] ?? '') !== $companyId)) {
                $customerId = null;
            }
        }
        Proposal::create($this->pdo, [
            'company_id' => $companyId,
            'customer_id' => $customerId,
            'title' => $title,
            'status' => $_POST['status'] ?? 'draft',
            'total_amount' => isset($_POST['total_amount']) ? (float) str_replace(',', '.', $_POST['total_amount']) : 0,
            'valid_until' => trim($_POST['valid_until'] ?? '') ?: null,
            'notes' => trim($_POST['notes'] ?? '') ?: null,
        ]);
        $_SESSION['flash_success'] = 'Teklif oluşturuldu.';
        header('Location: /teklifler');
        exit;
    }

    public function updateStatus(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /teklifler'); exit; }
        $id = trim($_POST['id'] ?? '');
        $status = trim($_POST['status'] ?? '');
        $p = $id ? Proposal::findOne($this->pdo, $id) : null;
        if (!$p) { $_SESSION['flash_error'] = 'Teklif bulunamadı.'; header('Location: /teklifler'); exit; }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($p['company_id'] ?? '') !== $companyId) { header('Location: /teklifler'); exit; }
        Proposal::updateStatus($this->pdo, $id, $status);
        $_SESSION['flash_success'] = 'Teklif durumu güncellendi.';
        header('Location: /teklifler');
        exit;
    }

    public function delete(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /teklifler'); exit; }
        $id = trim($_POST['id'] ?? '');
        $p = $id ? Proposal::findOne($this->pdo, $id) : null;
        if (!$p) { $_SESSION['flash_error'] = 'Teklif bulunamadı.'; header('Location: /teklifler'); exit; }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($p['company_id'] ?? '') !== $companyId) { header('Location: /teklifler'); exit; }
        Proposal::softDelete($this->pdo, $id);
        $_SESSION['flash_success'] = 'Teklif silindi.';
        header('Location: /teklifler');
        exit;
    }

    public function editForm(array $params): void
    {
        Auth::requireStaff();
        $id = $params['id'] ?? '';
        $proposal = $id ? Proposal::findOne($this->pdo, $id) : null;
        if (!$proposal) {
            $_SESSION['flash_error'] = 'Teklif bulunamadı.';
            header('Location: /teklifler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($proposal['company_id'] ?? '') !== $companyId) {
            header('Location: /teklifler');
            exit;
        }
        if ($companyId) {
            $customers = Customer::findAll($this->pdo, $companyId);
        } else {
            $customers = Customer::findAll($this->pdo, null);
        }
        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        require __DIR__ . '/../../views/proposals/edit.php';
    }

    public function update(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /teklifler'); exit; }
        $id = trim($_POST['id'] ?? '');
        $proposal = $id ? Proposal::findOne($this->pdo, $id) : null;
        if (!$proposal) {
            $_SESSION['flash_error'] = 'Teklif bulunamadı.';
            header('Location: /teklifler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($proposal['company_id'] ?? '') !== $companyId) { header('Location: /teklifler'); exit; }
        $totalAmount = isset($_POST['total_amount']) ? (float) str_replace(',', '.', $_POST['total_amount']) : 0;
        Proposal::update($this->pdo, $id, [
            'title' => trim($_POST['title'] ?? ''),
            'customer_id' => trim($_POST['customer_id'] ?? '') ?: null,
            'status' => $_POST['status'] ?? 'draft',
            'total_amount' => $totalAmount,
            'valid_until' => trim($_POST['valid_until'] ?? '') ?: null,
            'notes' => trim($_POST['notes'] ?? '') ?: null,
        ]);
        $_SESSION['flash_success'] = 'Teklif güncellendi.';
        header('Location: /teklifler');
        exit;
    }
}
