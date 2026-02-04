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
            $paidThisMonth = Payment::sumPaidThisMonthByCompany($this->pdo, $companyId);
            $activeContracts = Contract::countActiveByCompany($this->pdo, $companyId);
            $pendingCount = Payment::countByStatus($this->pdo, $companyId, 'pending');
            $overdueCount = Payment::countByStatus($this->pdo, $companyId, 'overdue');
        } else {
            $totalUnpaid = Payment::sumUnpaidGlobal($this->pdo);
            $paidThisMonth = Payment::sumPaidThisMonthGlobal($this->pdo);
            $activeContracts = Contract::countActiveGlobal($this->pdo);
            $pendingCount = Payment::countByStatusGlobal($this->pdo, 'pending');
            $overdueCount = Payment::countByStatusGlobal($this->pdo, 'overdue');
        }
        $paidInYear = $this->sumPaidInYear($companyId, $year);
        $occupancy = $this->getOccupancy($companyId);
        $revenueByMonth = $this->getRevenueByMonth($companyId, $year, $allMonths ? 0 : $month);
        $paymentBreakdown = $this->getPaymentBreakdownByMethod($companyId, $year, $allMonths ? 0 : $month);
        $monthDisplay = $monthRaw;
        $pendingCustomers = $this->getCustomersWithPaymentsByStatus($companyId, 'pending');
        $overdueCustomers = $this->getCustomersWithPaymentsByStatus($companyId, 'overdue');
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
        $rows = $this->fetchBankAccountPaymentRows($companyId, $bankAccountId ?: null, $startDate, $endDate);
        $expenseRows = $this->safeFetchBankAccountExpenseRows($companyId, $bankAccountId ?: null, $startDate, $endDate);
        $bankBalances = $this->safeComputeBankBalances($companyId, $bankAccounts, $endDate);
        $pageTitle = 'Banka Hesaplarına Göre Ödemeler';
        require __DIR__ . '/../../views/reports/bank_accounts.php';
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

    private function safeFetchBankAccountExpenseRows(?string $companyId, ?string $bankAccountId, string $startDate, string $endDate): array
    {
        try {
            return $this->fetchBankAccountExpenseRows($companyId, $bankAccountId, $startDate, $endDate);
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

    private function fetchBankAccountExpenseRows(?string $companyId, ?string $bankAccountId, string $startDate, string $endDate): array
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

    /** Ödeme yöntemine göre: Nakit, Kredi kartı, Banka hesabı (month=0: tüm yıl) */
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
        $stmt = $this->pdo->prepare('SELECT COALESCE(SUM(p.amount), 0) AS t ' . $base . ' AND LOWER(TRIM(COALESCE(p.payment_method,""))) IN ("nakit", "cash")');
        $stmt->execute($params);
        $cashTotal = (float) $stmt->fetchColumn();
        $stmt = $this->pdo->prepare('SELECT COALESCE(SUM(p.amount), 0) AS t ' . $base . ' AND (LOWER(TRIM(COALESCE(p.payment_method,""))) LIKE "%kredi%" OR LOWER(TRIM(COALESCE(p.payment_method,""))) = "credit_card")');
        $stmt->execute($params);
        $creditTotal = (float) $stmt->fetchColumn();
        $stmt = $this->pdo->prepare('SELECT COALESCE(SUM(p.amount), 0) AS t ' . $base . ' AND (p.bank_account_id IS NOT NULL OR LOWER(TRIM(COALESCE(p.payment_method,""))) IN ("havale","bank_transfer","banka"))');
        $stmt->execute($params);
        $bankTotal = (float) $stmt->fetchColumn();
        return [
            'cash' => $cashTotal,
            'credit_card' => $creditTotal,
            'bank' => $bankTotal,
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

    private function fetchBankAccountPaymentRows(?string $companyId, ?string $bankAccountId, string $startDate, string $endDate): array
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
        $sql .= ' ORDER BY p.paid_at DESC, ba.bank_name, p.payment_number ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
