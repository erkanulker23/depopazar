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

    /** Seçili teklifleri yazdır / PDF çıktı */
    public function printPage(): void
    {
        Auth::requireStaff();
        $ids = isset($_GET['id']) ? (is_array($_GET['id']) ? $_GET['id'] : [$_GET['id']]) : [];
        $ids = array_filter(array_map('trim', $ids));
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        $proposals = [];
        foreach ($ids as $id) {
            $p = Proposal::findOne($this->pdo, $id);
            if (!$p) continue;
            if ($companyId && ($p['company_id'] ?? '') !== $companyId) continue;
            $p['items'] = ProposalItem::findByProposalId($this->pdo, $id);
            $proposals[] = $p;
        }
        $statusLabels = ['draft' => 'Taslak', 'sent' => 'Gönderildi', 'accepted' => 'Kabul', 'rejected' => 'Red'];
        require __DIR__ . '/../../views/proposals/print.php';
    }

    /** Tek teklif yazdır – barkod çıktısı gibi tek sayfa */
    public function printOne(array $params): void
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
            $_SESSION['flash_error'] = 'Bu teklife erişim yetkiniz yok.';
            header('Location: /teklifler');
            exit;
        }
        $proposal['items'] = ProposalItem::findByProposalId($this->pdo, $id);
        $company = !empty($proposal['company_id']) ? Company::findOne($this->pdo, $proposal['company_id']) : null;
        $statusLabels = ['draft' => 'Taslak', 'sent' => 'Gönderildi', 'accepted' => 'Kabul', 'rejected' => 'Red'];
        require __DIR__ . '/../../views/proposals/print_one.php';
    }

    public function newForm(): void
    {
        Auth::requireStaff();
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId) {
            $customers = Customer::findAll($this->pdo, $companyId);
            $services = Service::findAll($this->pdo, $companyId);
        } elseif (($user['role'] ?? '') === 'super_admin') {
            $customers = Customer::findAll($this->pdo, null);
            $services = Service::findAll($this->pdo, null);
        } else {
            $customers = [];
            $services = [];
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
        $items = [];
        if (!empty($_POST['item_name']) && is_array($_POST['item_name'])) {
            foreach ($_POST['item_name'] as $i => $name) {
                $name = trim($name);
                if ($name === '') continue;
                $items[] = [
                    'name' => $name,
                    'description' => trim($_POST['item_description'][$i] ?? '') ?: null,
                    'quantity' => isset($_POST['item_quantity'][$i]) ? (float) str_replace(',', '.', $_POST['item_quantity'][$i]) : 1,
                    'unit_price' => isset($_POST['item_unit_price'][$i]) ? (float) str_replace(',', '.', $_POST['item_unit_price'][$i]) : 0,
                    'service_id' => trim($_POST['item_service_id'][$i] ?? '') ?: null,
                ];
            }
        }
        $totalAmount = isset($_POST['total_amount']) ? (float) str_replace(',', '.', $_POST['total_amount']) : 0;
        if ($totalAmount == 0 && !empty($items)) {
            foreach ($items as $it) {
                $totalAmount += ((float)($it['quantity'] ?? 0)) * ((float)($it['unit_price'] ?? 0));
            }
        }
        Proposal::create($this->pdo, [
            'company_id' => $companyId,
            'customer_id' => $customerId,
            'title' => $title,
            'status' => $_POST['status'] ?? 'draft',
            'total_amount' => $totalAmount,
            'valid_until' => trim($_POST['valid_until'] ?? '') ?: null,
            'notes' => trim($_POST['notes'] ?? '') ?: null,
            'pickup_address' => trim($_POST['pickup_address'] ?? '') ?: null,
            'delivery_address' => trim($_POST['delivery_address'] ?? '') ?: null,
            'items' => $items,
        ]);
        Notification::createForCompany($this->pdo, $companyId, 'proposal', 'Teklif oluşturuldu', $title . ' teklifi oluşturuldu.');
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
        $ids = isset($_POST['ids']) && is_array($_POST['ids']) ? array_filter(array_map('trim', $_POST['ids'])) : [];
        if (empty($ids)) {
            $id = trim($_POST['id'] ?? '');
            if ($id !== '') $ids = [$id];
        }
        if (empty($ids)) { $_SESSION['flash_error'] = 'Teklif seçilmedi.'; header('Location: /teklifler'); exit; }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        $deleted = 0;
        foreach ($ids as $id) {
            $p = Proposal::findOne($this->pdo, $id);
            if (!$p) continue;
            if ($companyId && ($p['company_id'] ?? '') !== $companyId) continue;
            Proposal::softDelete($this->pdo, $id);
            Notification::createForCompany($this->pdo, $p['company_id'] ?? null, 'proposal', 'Teklif silindi', ($p['title'] ?? '') . ' teklifi silindi.');
            $deleted++;
        }
        if ($deleted > 0) {
            $_SESSION['flash_success'] = $deleted === 1 ? 'Teklif silindi.' : $deleted . ' teklif silindi.';
        } else {
            $_SESSION['flash_error'] = 'Silinecek teklif bulunamadı veya yetkiniz yok.';
        }
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
            $services = Service::findAll($this->pdo, $companyId);
        } else {
            $customers = Customer::findAll($this->pdo, null);
            $services = Service::findAll($this->pdo, null);
        }
        $proposal['items'] = ProposalItem::findByProposalId($this->pdo, $id);
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
        $items = [];
        if (!empty($_POST['item_name']) && is_array($_POST['item_name'])) {
            foreach ($_POST['item_name'] as $i => $name) {
                $name = trim($name);
                if ($name === '') continue;
                $items[] = [
                    'name' => $name,
                    'description' => trim($_POST['item_description'][$i] ?? '') ?: null,
                    'quantity' => isset($_POST['item_quantity'][$i]) ? (float) str_replace(',', '.', $_POST['item_quantity'][$i]) : 1,
                    'unit_price' => isset($_POST['item_unit_price'][$i]) ? (float) str_replace(',', '.', $_POST['item_unit_price'][$i]) : 0,
                    'service_id' => trim($_POST['item_service_id'][$i] ?? '') ?: null,
                ];
            }
        }
        if ($totalAmount == 0 && !empty($items)) {
            foreach ($items as $it) {
                $totalAmount += ((float)($it['quantity'] ?? 0)) * ((float)($it['unit_price'] ?? 0));
            }
        }
        Proposal::update($this->pdo, $id, [
            'title' => trim($_POST['title'] ?? ''),
            'customer_id' => trim($_POST['customer_id'] ?? '') ?: null,
            'status' => $_POST['status'] ?? 'draft',
            'total_amount' => $totalAmount,
            'valid_until' => trim($_POST['valid_until'] ?? '') ?: null,
            'notes' => trim($_POST['notes'] ?? '') ?: null,
            'pickup_address' => trim($_POST['pickup_address'] ?? '') ?: null,
            'delivery_address' => trim($_POST['delivery_address'] ?? '') ?: null,
            'items' => $items,
        ]);
        $_SESSION['flash_success'] = 'Teklif güncellendi.';
        header('Location: /teklifler');
        exit;
    }
}
