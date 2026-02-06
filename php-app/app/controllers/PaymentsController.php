<?php
class PaymentsController
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
        if ($companyId) {
            $payments = Payment::findAll($this->pdo, $companyId);
        } elseif (($user['role'] ?? '') === 'super_admin') {
            $payments = Payment::findAll($this->pdo, null);
        } else {
            $payments = [];
        }
        $statusFilter = isset($_GET['status']) && in_array($_GET['status'], ['pending', 'paid', 'overdue', 'cancelled', 'unpaid'], true) ? $_GET['status'] : '';
        if ($statusFilter === 'unpaid') {
            $payments = array_filter($payments, fn($p) => in_array($p['status'] ?? '', ['pending', 'overdue']));
        } elseif ($statusFilter !== '') {
            $payments = array_filter($payments, fn($p) => ($p['status'] ?? '') === $statusFilter);
        }
        $dateFrom = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
        $dateTo = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';
        if ($dateFrom !== '') {
            $payments = array_filter($payments, fn($p) => (strtotime($p['due_date'] ?? '') >= strtotime($dateFrom . ' 00:00:00')));
        }
        if ($dateTo !== '') {
            $payments = array_filter($payments, fn($p) => (strtotime($p['due_date'] ?? '') <= strtotime($dateTo . ' 23:59:59')));
        }
        $searchQ = isset($_GET['q']) ? trim($_GET['q']) : '';
        if ($searchQ !== '') {
            $q = mb_strtolower($searchQ);
            $payments = array_filter($payments, function ($p) use ($q) {
                return (
                    str_contains(mb_strtolower($p['payment_number'] ?? ''), $q) ||
                    str_contains(mb_strtolower($p['contract_number'] ?? ''), $q) ||
                    str_contains(mb_strtolower(($p['customer_first_name'] ?? '') . ' ' . ($p['customer_last_name'] ?? '')), $q) ||
                    str_contains(mb_strtolower($p['customer_email'] ?? ''), $q) ||
                    str_contains((string)($p['amount'] ?? ''), $q)
                );
            });
        }
        $payments = array_values($payments);
        $unpaid = array_filter($payments, fn($p) => in_array($p['status'] ?? '', ['pending', 'overdue']));
        $unpaidPaymentsByCustomer = [];
        foreach ($unpaid as $p) {
            $cid = $p['customer_id'] ?? '';
            if ($cid === '') continue;
            if (!isset($unpaidPaymentsByCustomer[$cid])) $unpaidPaymentsByCustomer[$cid] = [];
            $unpaidPaymentsByCustomer[$cid][] = $p;
        }
        $customersWithDebt = [];
        foreach ($unpaidPaymentsByCustomer as $cid => $list) {
            $first = $list[0];
            $customersWithDebt[] = ['id' => $cid, 'customer_first_name' => $first['customer_first_name'] ?? '', 'customer_last_name' => $first['customer_last_name'] ?? '', 'payments' => $list];
        }
        $totalPayments = count($payments);
        $perPage = 25;
        $page = max(1, (int)($_GET['page'] ?? 1));
        $offset = ($page - 1) * $perPage;
        $payments = array_slice($payments, $offset, $perPage);
        $totalPages = $totalPayments > 0 ? (int)ceil($totalPayments / $perPage) : 1;
        $collectMode = isset($_GET['collect']) && $_GET['collect'] !== '0';
        $preselectedCustomerId = isset($_GET['customer']) ? trim($_GET['customer']) : '';
        $statusLabels = ['pending' => 'Bekliyor', 'paid' => 'Ödendi', 'overdue' => 'Gecikmiş', 'cancelled' => 'İptal'];
        $bankAccounts = [];
        if ($companyId) {
            $stmt = $this->pdo->prepare('SELECT * FROM bank_accounts WHERE company_id = ? AND deleted_at IS NULL AND is_active = 1 ORDER BY bank_name');
            $stmt->execute([$companyId]);
            $bankAccounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } elseif (($user['role'] ?? '') === 'super_admin') {
            $stmt = $this->pdo->query('SELECT * FROM bank_accounts WHERE deleted_at IS NULL AND is_active = 1 ORDER BY bank_name');
            $bankAccounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        $totalPayments = $totalPayments ?? count($payments);
        $preselectedCustomerId = $preselectedCustomerId ?? '';
        require __DIR__ . '/../../views/payments/index.php';
    }

    public function markPaid(): void
    {
        Auth::requireStaff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /odemeler');
            exit;
        }
        $paymentIds = isset($_POST['payment_ids']) && is_array($_POST['payment_ids']) ? array_filter($_POST['payment_ids']) : (isset($_POST['payment_id']) && $_POST['payment_id'] !== '' ? [$_POST['payment_id']] : []);
        $paymentMethod = trim($_POST['payment_method'] ?? '');
        $bankAccountId = trim($_POST['bank_account_id'] ?? '') ?: null;
        $transactionId = trim($_POST['transaction_id'] ?? '') ?: null;
        $notes = trim($_POST['notes'] ?? '') ?: null;
        if (empty($paymentIds) || !in_array($paymentMethod, ['cash', 'bank_transfer', 'credit_card'], true)) {
            $_SESSION['flash_error'] = 'Geçersiz istek. Ödeme seçin ve ödeme yöntemini belirleyin.';
            header('Location: /odemeler');
            exit;
        }
        if ($paymentMethod === 'bank_transfer' && !$bankAccountId) {
            $_SESSION['flash_error'] = 'Havale ile ödeme için banka hesabı seçin.';
            header('Location: /odemeler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        foreach ($paymentIds as $pid) {
            $payment = Payment::findOne($this->pdo, $pid);
            if (!$payment) continue;
            if ($companyId && ($payment['company_id'] ?? '') !== $companyId) {
                $_SESSION['flash_error'] = 'Bu ödemeye erişim yetkiniz yok.';
                header('Location: /odemeler');
                exit;
            }
        }
        try {
            if (count($paymentIds) === 1) {
                Payment::markAsPaid($this->pdo, $paymentIds[0], $paymentMethod, $transactionId, $notes, $bankAccountId);
                $firstPayment = Payment::findOne($this->pdo, $paymentIds[0]);
                if ($firstPayment && !empty($firstPayment['contract_id'])) {
                    $contract = Contract::findOne($this->pdo, $firstPayment['contract_id']);
                    if ($contract) {
                        Notification::createForCompany($this->pdo, $contract['company_id'] ?? null, 'payment', 'Ödeme alındı', 'Sözleşme ' . ($contract['contract_number'] ?? '') . ' için ödeme alındı.', ['contract_id' => $firstPayment['contract_id']]);
                    }
                }
            } else {
                Payment::markManyAsPaid($this->pdo, $paymentIds, $paymentMethod, $transactionId, $notes, $bankAccountId);
                Notification::createForCompany($this->pdo, $companyId, 'payment', 'Ödemeler alındı', count($paymentIds) . ' adet ödeme kaydedildi.');
            }
            $_SESSION['flash_success'] = 'Ödeme kaydedildi.';
        } catch (Exception $e) {
            $_SESSION['flash_error'] = 'Kayıt yapılamadı: ' . $e->getMessage();
        }
        $redirect = trim($_POST['redirect'] ?? '');
        if ($redirect !== '' && preg_match('#^/[a-z0-9/\-]+$#i', $redirect)) {
            header('Location: ' . $redirect);
        } else {
            header('Location: /odemeler');
        }
        exit;
    }

    public function show(array $params): void
    {
        Auth::requireStaff();
        $id = $params['id'] ?? '';
        if (!$id) {
            header('Location: /odemeler');
            exit;
        }
        $payment = Payment::findOne($this->pdo, $id);
        if (!$payment) {
            $_SESSION['flash_error'] = 'Ödeme kaydı bulunamadı.';
            header('Location: /odemeler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($payment['company_id'] ?? '') !== $companyId) {
            $_SESSION['flash_error'] = 'Bu ödemeye erişim yetkiniz yok.';
            header('Location: /odemeler');
            exit;
        }
        $statusLabels = ['pending' => 'Bekliyor', 'paid' => 'Ödendi', 'overdue' => 'Gecikmiş', 'cancelled' => 'İptal'];
        $company = !empty($payment['company_id']) ? Company::findOne($this->pdo, $payment['company_id']) : null;
        $pageTitle = 'Ödeme: ' . ($payment['payment_number'] ?? $id);
        require __DIR__ . '/../../views/payments/detail.php';
    }

    public function printPage(array $params): void
    {
        Auth::requireStaff();
        $id = $params['id'] ?? '';
        if (!$id) {
            header('Location: /odemeler');
            exit;
        }
        $payment = Payment::findOne($this->pdo, $id);
        if (!$payment) {
            $_SESSION['flash_error'] = 'Ödeme kaydı bulunamadı.';
            header('Location: /odemeler');
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if ($companyId && ($payment['company_id'] ?? '') !== $companyId) {
            $_SESSION['flash_error'] = 'Bu ödemeye erişim yetkiniz yok.';
            header('Location: /odemeler');
            exit;
        }
        $statusLabels = ['pending' => 'Bekliyor', 'paid' => 'Ödendi', 'overdue' => 'Gecikmiş', 'cancelled' => 'İptal'];
        $company = !empty($payment['company_id']) ? Company::findOne($this->pdo, $payment['company_id']) : null;
        require __DIR__ . '/../../views/payments/print.php';
    }
}
