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
        $month = isset($_GET['month']) && $_GET['month'] !== '' ? (int) $_GET['month'] : (int) date('n');
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
        $revenueByMonth = $this->getRevenueByMonth($companyId, $year, $month);
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

    /** Seçilen yıl/ay için gelir raporu: toplam tutar, ödeme sayısı, ödeme listesi */
    private function getRevenueByMonth(?string $companyId, int $year, int $month): array
    {
        $start = sprintf('%04d-%02d-01', $year, $month);
        $end = date('Y-m-t', strtotime($start));
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
        $pageTitle = 'Banka Hesaplarına Göre Ödemeler';
        require __DIR__ . '/../../views/reports/bank_accounts.php';
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
