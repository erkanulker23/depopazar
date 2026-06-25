<?php
class ReportsController
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
        $year = isset($_GET['year']) && $_GET['year'] !== '' ? (int) $_GET['year'] : (int) date('Y');
        $monthRaw = isset($_GET['month']) && $_GET['month'] !== '' ? (int) $_GET['month'] : (int) date('n');
        $month = ($monthRaw === 0) ? (int) date('n') : $monthRaw;
        $allMonths = ($monthRaw === 0);
        if ($companyId) {
            $totalUnpaid = Payment::sumUnpaidByCompany($this->pdo, $companyId);
            try {
                $totalUnpaid += CustomerCharge::sumUnpaidByCompany($this->pdo, $companyId);
            } catch (Throwable $e) {
            }
            $paidThisMonth = Payment::sumPaidThisMonthByCompany($this->pdo, $companyId);
            $activeContracts = Contract::countActiveByCompany($this->pdo, $companyId);
            $pendingCount = Payment::countByStatus($this->pdo, $companyId, 'pending');
            $overdueCount = Payment::countOverdueByDueDate($this->pdo, $companyId);
        } else {
            $totalUnpaid = Payment::sumUnpaidGlobal($this->pdo);
            try {
                $stmt = $this->pdo->query('SELECT COALESCE(SUM(amount), 0) FROM customer_charges WHERE deleted_at IS NULL AND status = \'pending\'');
                $totalUnpaid += (float) $stmt->fetchColumn();
            } catch (Throwable $e) {
            }
            $paidThisMonth = Payment::sumPaidThisMonthGlobal($this->pdo);
            $activeContracts = Contract::countActiveGlobal($this->pdo);
            $pendingCount = Payment::countByStatusGlobal($this->pdo, 'pending');
            $overdueCount = Payment::countOverdueByDueDateGlobal($this->pdo);
        }
        $paidInYear = $this->sumPaidInYear($companyId, $year);
        $occupancy = $this->getOccupancy($companyId);
        $revenueByMonth = $this->getRevenueByMonth($companyId, $year, $allMonths ? 0 : $month);
        $paymentBreakdown = $this->getPaymentBreakdownByMethod($companyId, $year, $allMonths ? 0 : $month);
        $monthDisplay = $monthRaw;
        $pendingCustomers = Payment::findCustomersWithPendingNotOverdue($this->pdo, $companyId);
        $overdueCustomers = Payment::findCustomersWithOverduePayments($this->pdo, $companyId);
        $pageTitle = 'Raporlar';
        require __DIR__ . '/../../views/reports/index.php';
    }

    /** Doluluk raporu: toplam oda, dolu, boş, doluluk oranı */
    private function getOccupancy(?string $companyId): array
    {
        $sql = 'SELECT COUNT(*) AS total,
                SUM(CASE WHEN r.status = \'occupied\' THEN 1 ELSE 0 END) AS occupied_rooms,
                SUM(CASE WHEN r.status = \'empty\' THEN 1 ELSE 0 END) AS empty_rooms
                FROM rooms r
                INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
                WHERE r.deleted_at IS NULL ';
        $params = [];
        if ($companyId) {
            $sql .= ' AND w.company_id = ? ';
            $params[] = $companyId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $total = (int) ($row['total'] ?? 0);
        $occupied = (int) ($row['occupied_rooms'] ?? 0);
        $empty = (int) ($row['empty_rooms'] ?? 0);
        $rate = $total > 0 ? round(($occupied / $total) * 100, 1) : 0;
        return [
            'total_rooms' => $total,
            'occupied_rooms' => $occupied,
            'empty_rooms' => $empty,
            'occupancy_rate' => $rate,
        ];
    }

    /** Seçilen yıl/ay için gelir raporu: toplam tutar, ödeme sayısı, ödeme listesi (month=0: tüm yıl) */
    private function getRevenueByMonth(?string $companyId, int $year, int $month): array
    {
        $start = $month === 0 ? sprintf('%04d-01-01', $year) : sprintf('%04d-%02d-01', $year, $month);
        $end = $month === 0 ? sprintf('%04d-12-31', $year) : date('Y-m-t', strtotime($start));
        $sql = 'SELECT p.id, p.amount, p.paid_at, p.payment_number, c.contract_number
                FROM payments p
                INNER JOIN contracts c ON c.id = p.contract_id AND c.deleted_at IS NULL
                INNER JOIN rooms r ON r.id = c.room_id AND r.deleted_at IS NULL
                INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
                WHERE p.deleted_at IS NULL AND p.status = \'paid\'
                AND DATE(p.paid_at) >= ? AND DATE(p.paid_at) <= ? ';
        $params = [$start, $end];
        if ($companyId) {
            $sql .= ' AND w.company_id = ? ';
            $params[] = $companyId;
        }
        $sql .= ' ORDER BY p.paid_at DESC ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total = array_sum(array_column($payments, 'amount'));
        return [
            'total_revenue' => (float) $total,
            'total_payments' => count($payments),
            'payments' => $payments,
        ];
    }

    private function sumPaidInYear(?string $companyId, int $year): float
    {
        $sql = 'SELECT COALESCE(SUM(p.amount), 0) FROM payments p
                INNER JOIN contracts c ON c.id = p.contract_id AND c.deleted_at IS NULL
                INNER JOIN rooms r ON r.id = c.room_id AND r.deleted_at IS NULL
                INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
                WHERE p.deleted_at IS NULL AND p.status = \'paid\'
                AND YEAR(p.paid_at) = ? ';
        $params = [$year];
        if ($companyId) {
            $sql .= ' AND w.company_id = ? ';
            $params[] = $companyId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (float) $stmt->fetchColumn();
    }

    /** Banka hesaplarına göre ödemeler raporu */
    public function bankAccountPayments(): void
    {
        Auth::requireStaff();
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        $bankAccounts = [];
        if ($companyId) {
            $stmt = $this->pdo->prepare('SELECT * FROM bank_accounts WHERE company_id = ? AND deleted_at IS NULL ORDER BY bank_name');
            $stmt->execute([$companyId]);
            $bankAccounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $stmt = $this->pdo->query('SELECT * FROM bank_accounts WHERE deleted_at IS NULL ORDER BY bank_name');
            $bankAccounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        $bankAccountId = isset($_GET['bank_account_id']) ? trim($_GET['bank_account_id']) : '';
        $startDate = isset($_GET['start_date']) ? trim($_GET['start_date']) : date('Y-m-01');
        $endDate = isset($_GET['end_date']) ? trim($_GET['end_date']) : date('Y-m-t');
        $search = isset($_GET['q']) ? trim($_GET['q']) : null;
        $search = $search !== '' ? $search : null;
        $paymentMethod = isset($_GET['payment_method']) && in_array($_GET['payment_method'], ['havale', 'kredi_karti'], true) ? $_GET['payment_method'] : null;
        $rows = $this->fetchBankAccountPaymentRows($companyId, $bankAccountId ?: null, $startDate, $endDate, $search, $paymentMethod);
        $expenseRows = $this->safeFetchBankAccountExpenseRows($companyId, $bankAccountId ?: null, $startDate, $endDate, $search);
        $bankBalances = $this->safeComputeBankBalances($companyId, $bankAccounts, $endDate);
        $pageTitle = 'Banka Hesaplarına Göre Ödemeler';
        require __DIR__ . '/../../views/reports/bank_accounts.php';
    }

    /** Erken / peşin ödemeler raporu */
    public function earlyPaymentsReport(): void
    {
        Auth::requireStaff();
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        $startDate = isset($_GET['start_date']) ? trim($_GET['start_date']) : date('Y-m-01', strtotime('-11 months'));
        $endDate = isset($_GET['end_date']) ? trim($_GET['end_date']) : date('Y-m-t');
        $rows = Payment::findEarlyPayments($this->pdo, $companyId, 500, $startDate, $endDate);
        $prepaidContracts = Payment::findFullyPrepaidContracts($this->pdo, $companyId, 100);
        $totalCount = Payment::countEarlyPayments($this->pdo, $companyId, $startDate, $endDate);
        $totalSum = Payment::sumEarlyPayments($this->pdo, $companyId, $startDate, $endDate);
        $pageTitle = 'Erken ve Peşin Ödemeler';
        require __DIR__ . '/../../views/reports/early_payments.php';
    }

    /** Masraflar raporu */
    public function expensesReport(): void
    {
        Auth::requireStaff();
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        $categories = [];
        $bankAccounts = [];
        $creditCards = [];
        if ($companyId) {
            $categories = $this->safeExpenseCategoriesFindAll($companyId);
            $bankAccounts = BankAccount::findAll($this->pdo, $companyId);
            $creditCards = $this->safeCreditCardsFindAll($companyId);
        }
        $categoryId = isset($_GET['category_id']) ? trim($_GET['category_id']) : '';
        $startDate = isset($_GET['start_date']) ? trim($_GET['start_date']) : date('Y-m-01');
        $endDate = isset($_GET['end_date']) ? trim($_GET['end_date']) : date('Y-m-t');
        $paymentSourceType = isset($_GET['payment_source_type']) ? trim($_GET['payment_source_type']) : '';
        $paymentSourceId = isset($_GET['payment_source_id']) ? trim($_GET['payment_source_id']) : '';
        $rows = $this->safeFetchExpenseReportRows($companyId, $categoryId ?: null, $startDate, $endDate, $paymentSourceType ?: null, $paymentSourceId ?: null);
        $totalAmount = array_sum(array_map(fn($r) => (float) ($r['amount'] ?? 0), $rows));
        $byCategory = $this->groupExpensesByCategory($rows);
        $pageTitle = 'Masraf Raporu';
        require __DIR__ . '/../../views/reports/expenses.php';
    }

    private function groupExpensesByCategory(array $rows): array
    {
        $groups = [];
        foreach ($rows as $r) {
            $cat = $r['category_name'] ?? 'Diğer';
            if (!isset($groups[$cat])) {
                $groups[$cat] = ['total' => 0, 'count' => 0];
            }
            $groups[$cat]['total'] += (float) ($r['amount'] ?? 0);
            $groups[$cat]['count']++;
        }
        return $groups;
    }

    private function fetchExpenseReportRows(?string $companyId, ?string $categoryId, string $startDate, string $endDate, ?string $paymentSourceType, ?string $paymentSourceId): array
    {
        $sql = 'SELECT e.*, ec.name AS category_name
                FROM expenses e
                INNER JOIN expense_categories ec ON ec.id = e.category_id AND ec.deleted_at IS NULL
                WHERE e.deleted_at IS NULL
                AND e.expense_date >= ? AND e.expense_date <= ? ';
        $params = [$startDate, $endDate];
        if ($companyId) {
            $sql .= ' AND e.company_id = ? ';
            $params[] = $companyId;
        }
        if ($categoryId) {
            $sql .= ' AND e.category_id = ? ';
            $params[] = $categoryId;
        }
        if ($paymentSourceType) {
            $sql .= ' AND e.payment_source_type = ? ';
            $params[] = $paymentSourceType;
        }
        if ($paymentSourceId) {
            $sql .= ' AND e.payment_source_id = ? ';
            $params[] = $paymentSourceId;
        }
        $sql .= ' ORDER BY e.expense_date DESC, e.created_at DESC ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function safeFetchBankAccountExpenseRows(?string $companyId, ?string $bankAccountId, string $startDate, string $endDate, ?string $search = null): array
    {
        try {
            return $this->fetchBankAccountExpenseRows($companyId, $bankAccountId, $startDate, $endDate, $search);
        } catch (Throwable $e) {
            return [];
        }
    }

    private function safeComputeBankBalances(?string $companyId, array $bankAccounts, string $untilDate): array
    {
        try {
            return $this->computeBankBalances($companyId, $bankAccounts, $untilDate);
        } catch (Throwable $e) {
            return array_fill_keys(array_column($bankAccounts, 'id'), 0);
        }
    }

    private function safeFetchExpenseReportRows(?string $companyId, ?string $categoryId, string $startDate, string $endDate, ?string $paymentSourceType, ?string $paymentSourceId): array
    {
        try {
            return $this->fetchExpenseReportRows($companyId, $categoryId, $startDate, $endDate, $paymentSourceType, $paymentSourceId);
        } catch (Throwable $e) {
            return [];
        }
    }

    private function safeExpenseCategoriesFindAll(?string $companyId): array
    {
        try {
            return $companyId ? ExpenseCategory::findAll($this->pdo, $companyId) : [];
        } catch (Throwable $e) {
            return [];
        }
    }

    private function safeCreditCardsFindAll(?string $companyId): array
    {
        try {
            return $companyId ? CreditCard::findAll($this->pdo, $companyId) : [];
        } catch (Throwable $e) {
            return [];
        }
    }

    private function fetchBankAccountExpenseRows(?string $companyId, ?string $bankAccountId, string $startDate, string $endDate, ?string $search = null): array
    {
        $sql = 'SELECT e.id, e.amount, e.expense_date, e.description, e.payment_source_id, ec.name AS category_name
                FROM expenses e
                INNER JOIN expense_categories ec ON ec.id = e.category_id AND ec.deleted_at IS NULL
                WHERE e.deleted_at IS NULL AND e.payment_source_type = \'bank_account\'
                AND e.expense_date >= ? AND e.expense_date <= ? ';
        $params = [$startDate, $endDate];
        if ($companyId) {
            $sql .= ' AND e.company_id = ? ';
            $params[] = $companyId;
        }
        if ($bankAccountId) {
            $sql .= ' AND e.payment_source_id = ? ';
            $params[] = $bankAccountId;
        }
        $search = trim((string) $search);
        if ($search !== '') {
            $sql .= ' AND (e.description LIKE ? OR e.notes LIKE ? OR ec.name LIKE ?) ';
            $q = '%' . $search . '%';
            $params = array_merge($params, [$q, $q, $q]);
        }
        $sql .= ' ORDER BY e.expense_date DESC ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Her banka hesabı için: açılış + tahsilat - masraflar = bakiye (bitiş tarihine kadar) */
    private function computeBankBalances(?string $companyId, array $bankAccounts, string $untilDate): array
    {
        $result = [];
        foreach ($bankAccounts as $ba) {
            $id = $ba['id'] ?? '';
            $opening = (float) ($ba['opening_balance'] ?? 0);
            $payments = $this->sumPaymentsToBankAccount($companyId, $id, $untilDate);
            $expenses = Expense::sumExpensesFromBankAccount($this->pdo, $id, $untilDate);
            $result[$id] = $opening + $payments - $expenses;
        }
        return $result;
    }

    private function sumPaymentsToBankAccount(?string $companyId, string $bankAccountId, string $untilDate): float
    {
        $sql = 'SELECT COALESCE(SUM(p.amount), 0) FROM payments p
                INNER JOIN contracts c ON c.id = p.contract_id AND c.deleted_at IS NULL
                INNER JOIN rooms r ON r.id = c.room_id AND r.deleted_at IS NULL
                INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
                WHERE p.deleted_at IS NULL AND p.status = \'paid\'
                AND p.bank_account_id = ? AND DATE(p.paid_at) <= ? ';
        $params = [$bankAccountId, $untilDate];
        if ($companyId) {
            $sql .= ' AND w.company_id = ? ';
            $params[] = $companyId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (float) $stmt->fetchColumn();
    }

    /** Ödeme yöntemine göre: Havale (eski nakit dahil), Kredi kartı */
    private function getPaymentBreakdownByMethod(?string $companyId, int $year, int $month): array
    {
        $start = $month === 0 ? sprintf('%04d-01-01', $year) : sprintf('%04d-%02d-01', $year, $month);
        $end = $month === 0 ? sprintf('%04d-12-31', $year) : date('Y-m-t', strtotime($start));
        $base = 'FROM payments p
                INNER JOIN contracts c ON c.id = p.contract_id AND c.deleted_at IS NULL
                INNER JOIN rooms r ON r.id = c.room_id AND r.deleted_at IS NULL
                INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
                WHERE p.deleted_at IS NULL AND p.status = \'paid\'
                AND DATE(p.paid_at) >= ? AND DATE(p.paid_at) <= ? ';
        $params = [$start, $end];
        if ($companyId) {
            $base .= ' AND w.company_id = ? ';
            $params[] = $companyId;
        }
        $stmt = $this->pdo->prepare('SELECT COALESCE(SUM(p.amount), 0) AS t ' . $base . ' AND (p.bank_account_id IS NOT NULL OR LOWER(TRIM(COALESCE(p.payment_method,""))) IN ("havale","bank_transfer","banka","nakit","cash"))');
        $stmt->execute($params);
        $bankTotal = (float) $stmt->fetchColumn();
        $stmt = $this->pdo->prepare('SELECT COALESCE(SUM(p.amount), 0) AS t ' . $base . ' AND (LOWER(TRIM(COALESCE(p.payment_method,""))) LIKE "%kredi%" OR LOWER(TRIM(COALESCE(p.payment_method,""))) = "credit_card")');
        $stmt->execute($params);
        $creditTotal = (float) $stmt->fetchColumn();
        return [
            'bank' => $bankTotal,
            'credit_card' => $creditTotal,
        ];
    }

    /** Bekleyen veya gecikmiş ödemesi olan müşteriler (isim + borç) */
    private function getCustomersWithPaymentsByStatus(?string $companyId, string $status): array
    {
        $sql = 'SELECT cu.id, cu.first_name, cu.last_name, SUM(p.amount) AS total_debt
                FROM payments p
                INNER JOIN contracts c ON c.id = p.contract_id AND c.deleted_at IS NULL
                INNER JOIN customers cu ON cu.id = c.customer_id AND cu.deleted_at IS NULL
                INNER JOIN rooms r ON r.id = c.room_id AND r.deleted_at IS NULL
                INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
                WHERE p.deleted_at IS NULL AND p.status = ? ';
        $params = [$status];
        if ($companyId) {
            $sql .= ' AND w.company_id = ? ';
            $params[] = $companyId;
        }
        $sql .= ' GROUP BY cu.id, cu.first_name, cu.last_name ORDER BY total_debt DESC LIMIT 50';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function fetchBankAccountPaymentRows(?string $companyId, ?string $bankAccountId, string $startDate, string $endDate, ?string $search = null, ?string $paymentMethod = null): array
    {
        $sql = 'SELECT p.id, p.payment_number, p.amount, p.paid_at, p.payment_method, p.transaction_id, p.notes,
                       c.contract_number, c.id AS contract_id, cu.first_name AS customer_first_name, cu.last_name AS customer_last_name,
                       ba.bank_name, ba.account_holder_name, ba.id AS bank_account_id
                FROM payments p
                INNER JOIN contracts c ON c.id = p.contract_id AND c.deleted_at IS NULL
                INNER JOIN customers cu ON cu.id = c.customer_id AND cu.deleted_at IS NULL
                LEFT JOIN bank_accounts ba ON ba.id = p.bank_account_id AND ba.deleted_at IS NULL
                INNER JOIN rooms r ON r.id = c.room_id AND r.deleted_at IS NULL
                INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
                WHERE p.deleted_at IS NULL AND p.status = \'paid\'
                AND DATE(p.paid_at) >= ? AND DATE(p.paid_at) <= ? ';
        $params = [$startDate, $endDate];
        if ($companyId) {
            $sql .= ' AND w.company_id = ? ';
            $params[] = $companyId;
        }
        if ($bankAccountId) {
            $sql .= ' AND p.bank_account_id = ? ';
            $params[] = $bankAccountId;
        }
        if ($paymentMethod) {
            $sql .= ' AND p.payment_method = ? ';
            $params[] = $paymentMethod;
        }
        $search = trim((string) $search);
        if ($search !== '') {
            $sql .= ' AND (p.payment_number LIKE ? OR c.contract_number LIKE ? OR cu.first_name LIKE ? OR cu.last_name LIKE ? OR p.transaction_id LIKE ? OR p.notes LIKE ?) ';
            $q = '%' . $search . '%';
            $params = array_merge($params, array_fill(0, 6, $q));
        }
        $sql .= ' ORDER BY p.paid_at DESC, ba.bank_name, p.payment_number ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
