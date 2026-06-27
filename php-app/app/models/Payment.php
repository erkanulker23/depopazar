<?php
class Payment
{
    /** Vadesi geçmiş ödeme adedi (vade tarihine göre; status overdue olmasa da sayılır) */
    public static function countOverdueByDueDate(PDO $pdo, string $companyId): int
    {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM payments p
             INNER JOIN contracts c ON c.id = p.contract_id AND c.deleted_at IS NULL
             INNER JOIN rooms r ON r.id = c.room_id AND r.deleted_at IS NULL
             INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
             WHERE w.company_id = ? AND p.deleted_at IS NULL AND p.status IN (\'pending\', \'overdue\')
             AND DATE(p.due_date) < CURDATE()'
        );
        $stmt->execute([$companyId]);
        return (int) $stmt->fetchColumn();
    }

    public static function countOverdueByDueDateGlobal(PDO $pdo): int
    {
        $stmt = $pdo->query(
            'SELECT COUNT(*) FROM payments p
             INNER JOIN contracts c ON c.id = p.contract_id AND c.deleted_at IS NULL
             INNER JOIN rooms r ON r.id = c.room_id AND r.deleted_at IS NULL
             INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
             WHERE p.deleted_at IS NULL AND p.status IN (\'pending\', \'overdue\')
             AND DATE(p.due_date) < CURDATE()'
        );
        return (int) $stmt->fetchColumn();
    }

    /** Vadesi geçmiş ödemesi olan müşteriler (raporlar için) */
    public static function findCustomersWithOverduePayments(PDO $pdo, ?string $companyId, int $limit = 50): array
    {
        $sql = 'SELECT cu.id, cu.first_name, cu.last_name, cu.email, SUM(p.amount) AS total_debt
                FROM payments p
                INNER JOIN contracts c ON c.id = p.contract_id AND c.deleted_at IS NULL
                INNER JOIN customers cu ON cu.id = c.customer_id AND cu.deleted_at IS NULL
                INNER JOIN rooms r ON r.id = c.room_id AND r.deleted_at IS NULL
                INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
                WHERE p.deleted_at IS NULL AND p.status IN (\'pending\', \'overdue\')
                AND DATE(p.due_date) < CURDATE() ';
        $params = [];
        if ($companyId) {
            $sql .= ' AND w.company_id = ? ';
            $params[] = $companyId;
        }
        $sql .= ' GROUP BY cu.id, cu.first_name, cu.last_name, cu.email ORDER BY total_debt DESC LIMIT ' . (int) $limit;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Vadesi gelmemiş/bekleyen ödemesi olan müşteriler (gecikmiş hariç) */
    public static function findCustomersWithPendingNotOverdue(PDO $pdo, ?string $companyId, int $limit = 50): array
    {
        $sql = 'SELECT cu.id, cu.first_name, cu.last_name, cu.email, SUM(p.amount) AS total_debt
                FROM payments p
                INNER JOIN contracts c ON c.id = p.contract_id AND c.deleted_at IS NULL
                INNER JOIN customers cu ON cu.id = c.customer_id AND cu.deleted_at IS NULL
                INNER JOIN rooms r ON r.id = c.room_id AND r.deleted_at IS NULL
                INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
                WHERE p.deleted_at IS NULL AND p.status IN (\'pending\', \'overdue\')
                AND (p.due_date IS NULL OR DATE(p.due_date) >= CURDATE()) ';
        $params = [];
        if ($companyId) {
            $sql .= ' AND w.company_id = ? ';
            $params[] = $companyId;
        }
        $sql .= ' GROUP BY cu.id, cu.first_name, cu.last_name, cu.email ORDER BY total_debt DESC LIMIT ' . (int) $limit;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Vadesi geçmiş ödemeler (e-posta hatırlatması için) */
    public static function findOverdueForReminder(PDO $pdo, string $companyId): array
    {
        $stmt = $pdo->prepare(
            'SELECT p.*, c.contract_number, w.company_id, c.customer_id, cu.first_name AS customer_first_name,
                    cu.last_name AS customer_last_name, cu.email AS customer_email
             FROM payments p
             INNER JOIN contracts c ON c.id = p.contract_id AND c.deleted_at IS NULL
             INNER JOIN customers cu ON cu.id = c.customer_id AND cu.deleted_at IS NULL
             INNER JOIN rooms r ON r.id = c.room_id AND r.deleted_at IS NULL
             INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
             WHERE w.company_id = ? AND p.deleted_at IS NULL AND p.status IN (\'pending\', \'overdue\')
             AND DATE(p.due_date) < CURDATE()
             ORDER BY p.due_date ASC'
        );
        $stmt->execute([$companyId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function countByStatus(PDO $pdo, string $companyId, string $status): int
    {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM payments p
             INNER JOIN contracts c ON c.id = p.contract_id AND c.deleted_at IS NULL
             INNER JOIN rooms r ON r.id = c.room_id AND r.deleted_at IS NULL
             INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
             WHERE w.company_id = ? AND p.deleted_at IS NULL AND p.status = ?'
        );
        $stmt->execute([$companyId, $status]);
        return (int) $stmt->fetchColumn();
    }

    public static function sumUnpaidByCompany(PDO $pdo, string $companyId): float
    {
        $stmt = $pdo->prepare(
            'SELECT COALESCE(SUM(p.amount), 0) FROM payments p
             INNER JOIN contracts c ON c.id = p.contract_id AND c.deleted_at IS NULL
             INNER JOIN rooms r ON r.id = c.room_id AND r.deleted_at IS NULL
             INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
             WHERE w.company_id = ? AND p.deleted_at IS NULL AND p.status IN (\'pending\', \'overdue\')'
        );
        $stmt->execute([$companyId]);
        return (float) $stmt->fetchColumn();
    }

    /** Tahsil edilebilir ödeme adedi (Ödeme Al menüsü rozeti) */
    public static function countCollectible(PDO $pdo, ?string $companyId): int
    {
        $sql = 'SELECT COUNT(*) FROM payments p
             INNER JOIN contracts c ON c.id = p.contract_id AND c.deleted_at IS NULL
             INNER JOIN rooms r ON r.id = c.room_id AND r.deleted_at IS NULL
             INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
             WHERE p.deleted_at IS NULL AND p.status IN (\'pending\', \'overdue\')';
        $params = [];
        if ($companyId) {
            $sql .= ' AND w.company_id = ?';
            $params[] = $companyId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /** Şirket: vadesi geçmiş borç (vade tarihi bugünden önce, ödenmemiş) */
    public static function sumOverdueByCompany(PDO $pdo, string $companyId): float
    {
        $stmt = $pdo->prepare(
            'SELECT COALESCE(SUM(p.amount), 0) FROM payments p
             INNER JOIN contracts c ON c.id = p.contract_id AND c.deleted_at IS NULL
             INNER JOIN rooms r ON r.id = c.room_id AND r.deleted_at IS NULL
             INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
             WHERE w.company_id = ? AND p.deleted_at IS NULL AND p.status IN (\'pending\', \'overdue\')
             AND DATE(p.due_date) < CURDATE()'
        );
        $stmt->execute([$companyId]);
        return (float) $stmt->fetchColumn();
    }

    /** Şirket: vadesi gelmiş borç – bu ay vadesi gelen ve ödenmemiş */
    public static function sumDueThisMonthByCompany(PDO $pdo, string $companyId): float
    {
        $stmt = $pdo->prepare(
            'SELECT COALESCE(SUM(p.amount), 0) FROM payments p
             INNER JOIN contracts c ON c.id = p.contract_id AND c.deleted_at IS NULL
             INNER JOIN rooms r ON r.id = c.room_id AND r.deleted_at IS NULL
             INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
             WHERE w.company_id = ? AND p.deleted_at IS NULL AND p.status IN (\'pending\', \'overdue\')
             AND YEAR(p.due_date) = YEAR(CURDATE()) AND MONTH(p.due_date) = MONTH(CURDATE())'
        );
        $stmt->execute([$companyId]);
        return (float) $stmt->fetchColumn();
    }

    public static function sumPaidThisMonthByCompany(PDO $pdo, string $companyId): float
    {
        $stmt = $pdo->prepare(
            'SELECT COALESCE(SUM(p.amount), 0) FROM payments p
             INNER JOIN contracts c ON c.id = p.contract_id AND c.deleted_at IS NULL
             INNER JOIN rooms r ON r.id = c.room_id AND r.deleted_at IS NULL
             INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
             WHERE w.company_id = ? AND p.deleted_at IS NULL AND p.status = \'paid\'
             AND p.paid_at >= DATE_FORMAT(NOW(), \'%Y-%m-01\') AND p.paid_at < DATE_ADD(DATE_FORMAT(NOW(), \'%Y-%m-01\'), INTERVAL 1 MONTH)'
        );
        $stmt->execute([$companyId]);
        return (float) $stmt->fetchColumn();
    }

    public static function countByStatusGlobal(PDO $pdo, string $status): int
    {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM payments p INNER JOIN contracts c ON c.id = p.contract_id AND c.deleted_at IS NULL
             INNER JOIN rooms r ON r.id = c.room_id AND r.deleted_at IS NULL
             INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
             WHERE p.deleted_at IS NULL AND p.status = ?'
        );
        $stmt->execute([$status]);
        return (int) $stmt->fetchColumn();
    }

    public static function sumUnpaidGlobal(PDO $pdo): float
    {
        $stmt = $pdo->query(
            'SELECT COALESCE(SUM(p.amount), 0) FROM payments p
             INNER JOIN contracts c ON c.id = p.contract_id AND c.deleted_at IS NULL
             INNER JOIN rooms r ON r.id = c.room_id AND r.deleted_at IS NULL
             INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
             WHERE p.deleted_at IS NULL AND p.status IN (\'pending\', \'overdue\')'
        );
        return (float) $stmt->fetchColumn();
    }

    /** Global: vadesi geçmiş borç */
    public static function sumOverdueGlobal(PDO $pdo): float
    {
        $stmt = $pdo->query(
            'SELECT COALESCE(SUM(p.amount), 0) FROM payments p
             INNER JOIN contracts c ON c.id = p.contract_id AND c.deleted_at IS NULL
             INNER JOIN rooms r ON r.id = c.room_id AND r.deleted_at IS NULL
             INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
             WHERE p.deleted_at IS NULL AND p.status IN (\'pending\', \'overdue\')
             AND DATE(p.due_date) < CURDATE()'
        );
        return (float) $stmt->fetchColumn();
    }

    /** Global: bu ay vadesi gelen ödenmemiş borç */
    public static function sumDueThisMonthGlobal(PDO $pdo): float
    {
        $stmt = $pdo->query(
            'SELECT COALESCE(SUM(p.amount), 0) FROM payments p
             INNER JOIN contracts c ON c.id = p.contract_id AND c.deleted_at IS NULL
             INNER JOIN rooms r ON r.id = c.room_id AND r.deleted_at IS NULL
             INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
             WHERE p.deleted_at IS NULL AND p.status IN (\'pending\', \'overdue\')
             AND YEAR(p.due_date) = YEAR(CURDATE()) AND MONTH(p.due_date) = MONTH(CURDATE())'
        );
        return (float) $stmt->fetchColumn();
    }

    public static function sumPaidThisMonthGlobal(PDO $pdo): float
    {
        $stmt = $pdo->query(
            'SELECT COALESCE(SUM(p.amount), 0) FROM payments p
             INNER JOIN contracts c ON c.id = p.contract_id AND c.deleted_at IS NULL
             WHERE p.deleted_at IS NULL AND p.status = \'paid\'
             AND p.paid_at >= DATE_FORMAT(NOW(), \'%Y-%m-01\') AND p.paid_at < DATE_ADD(DATE_FORMAT(NOW(), \'%Y-%m-01\'), INTERVAL 1 MONTH)'
        );
        return (float) $stmt->fetchColumn();
    }

    public static function create(PDO $pdo, array $data): string
    {
        $id = self::uuid();
        $paymentNumber = $data['payment_number'] ?? self::generatePaymentNumber($pdo);
        $stmt = $pdo->prepare(
            'INSERT INTO payments (id, payment_number, contract_id, amount, status, type, due_date) 
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $id,
            $paymentNumber,
            $data['contract_id'],
            $data['amount'],
            $data['status'] ?? 'pending',
            $data['type'] ?? 'warehouse',
            $data['due_date'],
        ]);
        return $id;
    }

    private static function generatePaymentNumber(PDO $pdo): string
    {
        $y = date('Y');
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE payment_number LIKE ? AND deleted_at IS NULL");
        $stmt->execute(["PAY-{$y}-%"]);
        $n = (int) $stmt->fetchColumn() + 1;
        return sprintf('PAY-%s-%06d', $y, $n);
    }

    private static function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    public static function findOne(PDO $pdo, string $id): ?array
    {
        $stmt = $pdo->prepare(
            'SELECT p.*, c.contract_number, c.id AS contract_id, c.customer_id,
             cu.first_name AS customer_first_name, cu.last_name AS customer_last_name, cu.email AS customer_email, cu.phone AS customer_phone,
             r.room_number, w.name AS warehouse_name, w.company_id
             FROM payments p
             INNER JOIN contracts c ON c.id = p.contract_id AND c.deleted_at IS NULL
             INNER JOIN customers cu ON cu.id = c.customer_id AND cu.deleted_at IS NULL
             INNER JOIN rooms r ON r.id = c.room_id AND r.deleted_at IS NULL
             INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
             WHERE p.id = ? AND p.deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findAll(PDO $pdo, ?string $companyId = null): array
    {
        $sql = 'SELECT p.*, c.contract_number, c.customer_id, cu.first_name AS customer_first_name, cu.last_name AS customer_last_name, cu.email AS customer_email
                FROM payments p
                INNER JOIN contracts c ON c.id = p.contract_id AND c.deleted_at IS NULL
                INNER JOIN customers cu ON cu.id = c.customer_id AND cu.deleted_at IS NULL
                INNER JOIN rooms r ON r.id = c.room_id AND r.deleted_at IS NULL
                INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
                WHERE p.deleted_at IS NULL ';
        $params = [];
        if ($companyId) {
            $sql .= ' AND w.company_id = ? ';
            $params[] = $companyId;
        }
        $sql .= ' ORDER BY p.due_date DESC ';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ödeme listesi: arama, vade tarihi ve durum filtreleri (SQL).
     */
    public static function findForList(
        PDO $pdo,
        ?string $companyId,
        ?string $statusFilter = null,
        ?string $search = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        bool $collectibleOnly = false
    ): array {
        $sql = 'SELECT p.*, c.contract_number, c.customer_id, cu.first_name AS customer_first_name, cu.last_name AS customer_last_name, cu.email AS customer_email
                FROM payments p
                INNER JOIN contracts c ON c.id = p.contract_id AND c.deleted_at IS NULL
                INNER JOIN customers cu ON cu.id = c.customer_id AND cu.deleted_at IS NULL
                INNER JOIN rooms r ON r.id = c.room_id AND r.deleted_at IS NULL
                INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
                WHERE p.deleted_at IS NULL ';
        $params = [];
        if ($companyId) {
            $sql .= ' AND w.company_id = ? ';
            $params[] = $companyId;
        }
        if ($collectibleOnly) {
            $sql .= " AND p.status IN ('pending', 'overdue') ";
        }
        if ($statusFilter === 'paid') {
            $sql .= " AND p.status = 'paid' ";
        } elseif ($statusFilter === 'cancelled') {
            $sql .= " AND p.status = 'cancelled' ";
        } elseif ($statusFilter === 'overdue') {
            $sql .= " AND p.status IN ('pending', 'overdue') AND p.due_date IS NOT NULL AND DATE(p.due_date) < CURDATE() ";
        } elseif ($statusFilter === 'pending') {
            $sql .= " AND p.status = 'pending' AND (p.due_date IS NULL OR DATE(p.due_date) >= CURDATE()) ";
        } elseif ($statusFilter === 'unpaid') {
            $sql .= " AND p.status IN ('pending', 'overdue') ";
        } elseif ($statusFilter === 'early') {
            $sql .= " AND p.status = 'paid' AND p.paid_at IS NOT NULL AND p.due_date IS NOT NULL AND DATE(p.paid_at) < DATE(p.due_date) ";
        }
        if ($search !== null && $search !== '') {
            $like = '%' . $search . '%';
            $sql .= ' AND (
                p.payment_number LIKE ? OR c.contract_number LIKE ? OR
                cu.first_name LIKE ? OR cu.last_name LIKE ? OR
                CONCAT(cu.first_name, \' \', cu.last_name) LIKE ? OR
                cu.email LIKE ? OR CAST(p.amount AS CHAR) LIKE ?
            ) ';
            array_push($params, $like, $like, $like, $like, $like, $like, $like);
        }
        if ($dateFrom !== null && $dateFrom !== '') {
            $sql .= ' AND p.due_date IS NOT NULL AND DATE(p.due_date) >= ? ';
            $params[] = $dateFrom;
        }
        if ($dateTo !== null && $dateTo !== '') {
            $sql .= ' AND p.due_date IS NOT NULL AND DATE(p.due_date) <= ? ';
            $params[] = $dateTo;
        }
        $sql .= ' ORDER BY p.due_date DESC ';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($statusFilter !== null && $statusFilter !== '') {
            $rows = array_values(array_filter($rows, fn($p) => paymentMatchesStatusFilter($p, $statusFilter)));
        }
        return $rows;
    }

    public static function findByContractId(PDO $pdo, string $contractId): array
    {
        $stmt = $pdo->prepare(
            'SELECT p.* FROM payments p
             WHERE p.contract_id = ? AND p.deleted_at IS NULL
             ORDER BY p.due_date ASC'
        );
        $stmt->execute([$contractId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Ödemesi alınmış aylar (Y-m); sözleşme düzenlemede fiyat kilidi için */
    public static function getPaidMonthsByContractId(PDO $pdo, string $contractId): array
    {
        $stmt = $pdo->prepare(
            "SELECT DISTINCT DATE_FORMAT(due_date, '%Y-%m') AS month_key
             FROM payments
             WHERE contract_id = ? AND deleted_at IS NULL AND status = 'paid' AND due_date IS NOT NULL
             ORDER BY month_key ASC"
        );
        $stmt->execute([$contractId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /** Ödemesi alınmış ayların tahsil edilen tutarları (Y-m => amount) */
    public static function getPaidAmountsByMonthForContract(PDO $pdo, string $contractId): array
    {
        $stmt = $pdo->prepare(
            "SELECT DATE_FORMAT(due_date, '%Y-%m') AS month_key, amount
             FROM payments
             WHERE contract_id = ? AND deleted_at IS NULL AND status = 'paid' AND due_date IS NOT NULL
             ORDER BY paid_at DESC"
        );
        $stmt->execute([$contractId]);
        $out = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $key = $row['month_key'] ?? '';
            if ($key !== '' && !isset($out[$key])) {
                $out[$key] = (float) ($row['amount'] ?? 0);
            }
        }
        return $out;
    }

    /** Bekleyen/gecikmiş ödeme tutarını güncelle (ödenmiş aylar kilitli) */
    public static function updatePendingAmount(PDO $pdo, string $paymentId, float $amount): bool
    {
        $stmt = $pdo->prepare(
            "UPDATE payments SET amount = ? WHERE id = ? AND deleted_at IS NULL AND status IN ('pending', 'overdue')"
        );
        $stmt->execute([$amount, $paymentId]);
        return $stmt->rowCount() > 0;
    }

    /** Vadesi gelmiş / ödemesi alınmamış müşteriler: sadece bulunduğumuz ay ve önceki aylara ait ödenmemiş borç (gelecek ayların vadeleri dahil değil) */
    public static function findCustomersWithUnpaidPayments(PDO $pdo, ?string $companyId = null, int $limit = 50): array
    {
        $sql = 'SELECT c.customer_id,
                cu.first_name AS customer_first_name,
                cu.last_name AS customer_last_name,
                SUM(p.amount) AS total_debt,
                COUNT(p.id) AS payment_count
                FROM payments p
                INNER JOIN contracts c ON c.id = p.contract_id AND c.deleted_at IS NULL
                INNER JOIN customers cu ON cu.id = c.customer_id AND cu.deleted_at IS NULL
                INNER JOIN rooms r ON r.id = c.room_id AND r.deleted_at IS NULL
                INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
                WHERE p.deleted_at IS NULL AND p.status IN (\'pending\', \'overdue\')
                AND DATE(p.due_date) <= LAST_DAY(CURDATE())
                ';
        $params = [];
        if ($companyId) {
            $sql .= ' AND w.company_id = ? ';
            $params[] = $companyId;
        }
        $sql .= ' GROUP BY c.customer_id, cu.first_name, cu.last_name HAVING SUM(p.amount) > 0 ORDER BY total_debt DESC LIMIT ' . (int) $limit;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Bekleyen/gecikmiş ödemelerden vadesi bugünden itibaren N gün içinde olanlar */
    public static function findUpcoming(PDO $pdo, ?string $companyId, int $days = 10): array
    {
        $sql = 'SELECT p.*, c.contract_number, c.customer_id, cu.first_name AS customer_first_name, cu.last_name AS customer_last_name
                FROM payments p
                INNER JOIN contracts c ON c.id = p.contract_id AND c.deleted_at IS NULL
                INNER JOIN customers cu ON cu.id = c.customer_id AND cu.deleted_at IS NULL
                INNER JOIN rooms r ON r.id = c.room_id AND r.deleted_at IS NULL
                INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
                WHERE p.deleted_at IS NULL AND p.status IN (\'pending\', \'overdue\')
                AND DATE(p.due_date) >= CURDATE() AND DATE(p.due_date) <= DATE_ADD(CURDATE(), INTERVAL ? DAY) ';
        $params = [$days];
        if ($companyId) {
            $sql .= ' AND w.company_id = ? ';
            $params[] = $companyId;
        }
        $sql .= ' ORDER BY p.due_date ASC ';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Bu ayın ilk–son gün aralığı (Y-m-d) ve etiket */
    public static function currentMonthRange(): array
    {
        $start = new DateTime('first day of this month');
        $end = new DateTime('last day of this month');
        $months = ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];
        $monthName = $months[(int) $start->format('n') - 1];
        $label = $monthName . ' ' . $start->format('Y');
        return [
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d'),
            'label' => $label,
        ];
    }

    /** Bu haftanın Pazartesi–Pazar aralığı (Y-m-d) ve etiket */
    public static function currentWeekRange(): array
    {
        $monday = new DateTime('monday this week');
        $sunday = new DateTime('sunday this week');
        $months = ['Oca', 'Şub', 'Mar', 'Nis', 'May', 'Haz', 'Tem', 'Ağu', 'Eyl', 'Eki', 'Kas', 'Ara'];
        $m1 = $months[(int) $monday->format('n') - 1];
        $m2 = $months[(int) $sunday->format('n') - 1];
        $label = $monday->format('j') . ' ' . $m1 . ' – ' . $sunday->format('j') . ' ' . $m2 . ' ' . $sunday->format('Y');
        return [
            'start' => $monday->format('Y-m-d'),
            'end' => $sunday->format('Y-m-d'),
            'label' => $label,
        ];
    }

    /** Vadesi geçmiş ödemeler (liste) */
    public static function findOverdueList(PDO $pdo, ?string $companyId, int $limit = 15): array
    {
        $sql = 'SELECT p.*, c.contract_number, c.customer_id, c.id AS contract_id,
                       cu.first_name AS customer_first_name, cu.last_name AS customer_last_name
                FROM payments p
                INNER JOIN contracts c ON c.id = p.contract_id AND c.deleted_at IS NULL
                INNER JOIN customers cu ON cu.id = c.customer_id AND cu.deleted_at IS NULL
                INNER JOIN rooms r ON r.id = c.room_id AND r.deleted_at IS NULL
                INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
                WHERE p.deleted_at IS NULL AND p.status IN (\'pending\', \'overdue\')
                AND DATE(p.due_date) < CURDATE() ';
        $params = [];
        if ($companyId) {
            $sql .= ' AND w.company_id = ? ';
            $params[] = $companyId;
        }
        $sql .= ' ORDER BY p.due_date ASC LIMIT ' . (int) $limit;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Bu ay vadesi dolmuş (ay başı–bugün arası, ödenmemiş) */
    public static function findOverdueDueThisMonth(PDO $pdo, ?string $companyId, int $limit = 15): array
    {
        $month = self::currentMonthRange();
        $sql = 'SELECT p.*, c.contract_number, c.customer_id, c.id AS contract_id,
                       cu.first_name AS customer_first_name, cu.last_name AS customer_last_name
                FROM payments p
                INNER JOIN contracts c ON c.id = p.contract_id AND c.deleted_at IS NULL
                INNER JOIN customers cu ON cu.id = c.customer_id AND cu.deleted_at IS NULL
                INNER JOIN rooms r ON r.id = c.room_id AND r.deleted_at IS NULL
                INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
                WHERE p.deleted_at IS NULL AND p.status IN (\'pending\', \'overdue\')
                AND DATE(p.due_date) >= ? AND DATE(p.due_date) < CURDATE() ';
        $params = [$month['start']];
        if ($companyId) {
            $sql .= ' AND w.company_id = ? ';
            $params[] = $companyId;
        }
        $sql .= ' ORDER BY p.due_date ASC LIMIT ' . (int) $limit;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Bu ay vadesi gelecek (bugün–ay sonu, ödenmemiş) */
    public static function findDueThisMonth(PDO $pdo, ?string $companyId, int $limit = 15): array
    {
        $month = self::currentMonthRange();
        $sql = 'SELECT p.*, c.contract_number, c.customer_id, c.id AS contract_id,
                       cu.first_name AS customer_first_name, cu.last_name AS customer_last_name
                FROM payments p
                INNER JOIN contracts c ON c.id = p.contract_id AND c.deleted_at IS NULL
                INNER JOIN customers cu ON cu.id = c.customer_id AND cu.deleted_at IS NULL
                INNER JOIN rooms r ON r.id = c.room_id AND r.deleted_at IS NULL
                INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
                WHERE p.deleted_at IS NULL AND p.status IN (\'pending\', \'overdue\')
                AND DATE(p.due_date) >= CURDATE() AND DATE(p.due_date) <= ? ';
        $params = [$month['end']];
        if ($companyId) {
            $sql .= ' AND w.company_id = ? ';
            $params[] = $companyId;
        }
        $sql .= ' ORDER BY p.due_date ASC LIMIT ' . (int) $limit;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Bu ay tahsil edilen ödemeler */
    public static function findPaidThisMonth(PDO $pdo, ?string $companyId, int $limit = 10): array
    {
        $month = self::currentMonthRange();
        $sql = 'SELECT p.*, c.contract_number, c.customer_id,
                       cu.first_name AS customer_first_name, cu.last_name AS customer_last_name
                FROM payments p
                INNER JOIN contracts c ON c.id = p.contract_id AND c.deleted_at IS NULL
                INNER JOIN customers cu ON cu.id = c.customer_id AND cu.deleted_at IS NULL
                INNER JOIN rooms r ON r.id = c.room_id AND r.deleted_at IS NULL
                INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
                WHERE p.deleted_at IS NULL AND p.status = \'paid\'
                AND DATE(p.paid_at) >= ? AND DATE(p.paid_at) <= ? ';
        $params = [$month['start'], $month['end']];
        if ($companyId) {
            $sql .= ' AND w.company_id = ? ';
            $params[] = $companyId;
        }
        $sql .= ' ORDER BY p.paid_at DESC LIMIT ' . (int) $limit;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function sumPaidThisMonth(PDO $pdo, ?string $companyId): float
    {
        $month = self::currentMonthRange();
        $sql = 'SELECT COALESCE(SUM(p.amount), 0) FROM payments p
                INNER JOIN contracts c ON c.id = p.contract_id AND c.deleted_at IS NULL
                INNER JOIN rooms r ON r.id = c.room_id AND r.deleted_at IS NULL
                INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
                WHERE p.deleted_at IS NULL AND p.status = \'paid\'
                AND DATE(p.paid_at) >= ? AND DATE(p.paid_at) <= ? ';
        $params = [$month['start'], $month['end']];
        if ($companyId) {
            $sql .= ' AND w.company_id = ? ';
            $params[] = $companyId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (float) $stmt->fetchColumn();
    }

    public static function countDueThisMonth(PDO $pdo, ?string $companyId): int
    {
        $month = self::currentMonthRange();
        $sql = 'SELECT COUNT(*) FROM payments p
                INNER JOIN contracts c ON c.id = p.contract_id AND c.deleted_at IS NULL
                INNER JOIN rooms r ON r.id = c.room_id AND r.deleted_at IS NULL
                INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
                WHERE p.deleted_at IS NULL AND p.status IN (\'pending\', \'overdue\')
                AND DATE(p.due_date) >= CURDATE() AND DATE(p.due_date) <= ? ';
        $params = [$month['end']];
        if ($companyId) {
            $sql .= ' AND w.company_id = ? ';
            $params[] = $companyId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public static function sumDueThisMonth(PDO $pdo, ?string $companyId): float
    {
        $month = self::currentMonthRange();
        $sql = 'SELECT COALESCE(SUM(p.amount), 0) FROM payments p
                INNER JOIN contracts c ON c.id = p.contract_id AND c.deleted_at IS NULL
                INNER JOIN rooms r ON r.id = c.room_id AND r.deleted_at IS NULL
                INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
                WHERE p.deleted_at IS NULL AND p.status IN (\'pending\', \'overdue\')
                AND DATE(p.due_date) >= CURDATE() AND DATE(p.due_date) <= ? ';
        $params = [$month['end']];
        if ($companyId) {
            $sql .= ' AND w.company_id = ? ';
            $params[] = $companyId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (float) $stmt->fetchColumn();
    }

    public static function countOverdueDueThisMonth(PDO $pdo, ?string $companyId): int
    {
        $month = self::currentMonthRange();
        $sql = 'SELECT COUNT(*) FROM payments p
                INNER JOIN contracts c ON c.id = p.contract_id AND c.deleted_at IS NULL
                INNER JOIN rooms r ON r.id = c.room_id AND r.deleted_at IS NULL
                INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
                WHERE p.deleted_at IS NULL AND p.status IN (\'pending\', \'overdue\')
                AND DATE(p.due_date) >= ? AND DATE(p.due_date) < CURDATE() ';
        $params = [$month['start']];
        if ($companyId) {
            $sql .= ' AND w.company_id = ? ';
            $params[] = $companyId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public static function sumOverdueDueThisMonth(PDO $pdo, ?string $companyId): float
    {
        $month = self::currentMonthRange();
        $sql = 'SELECT COALESCE(SUM(p.amount), 0) FROM payments p
                INNER JOIN contracts c ON c.id = p.contract_id AND c.deleted_at IS NULL
                INNER JOIN rooms r ON r.id = c.room_id AND r.deleted_at IS NULL
                INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
                WHERE p.deleted_at IS NULL AND p.status IN (\'pending\', \'overdue\')
                AND DATE(p.due_date) >= ? AND DATE(p.due_date) < CURDATE() ';
        $params = [$month['start']];
        if ($companyId) {
            $sql .= ' AND w.company_id = ? ';
            $params[] = $companyId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (float) $stmt->fetchColumn();
    }

    public static function findByCustomerId(PDO $pdo, string $customerId, ?string $companyId = null): array
    {
        $sql = 'SELECT p.*, c.contract_number
                FROM payments p
                INNER JOIN contracts c ON c.id = p.contract_id AND c.deleted_at IS NULL
                INNER JOIN rooms r ON r.id = c.room_id AND r.deleted_at IS NULL
                INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
                WHERE c.customer_id = ? AND p.deleted_at IS NULL ';
        $params = [$customerId];
        if ($companyId) {
            $sql .= ' AND w.company_id = ? ';
            $params[] = $companyId;
        }
        $sql .= ' ORDER BY p.due_date ASC ';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Müşteri: tahsil edilebilir ödemeler (vadesi gelmemiş dahil, vade tarihi filtresi yok) */
    public static function findCollectibleByCustomerId(PDO $pdo, string $customerId, ?string $companyId = null): array
    {
        $sql = 'SELECT p.*, c.contract_number
                FROM payments p
                INNER JOIN contracts c ON c.id = p.contract_id AND c.deleted_at IS NULL
                INNER JOIN rooms r ON r.id = c.room_id AND r.deleted_at IS NULL
                INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
                WHERE c.customer_id = ? AND p.deleted_at IS NULL
                AND p.status IN (\'pending\', \'overdue\') ';
        $params = [$customerId];
        if ($companyId) {
            $sql .= ' AND w.company_id = ? ';
            $params[] = $companyId;
        }
        $sql .= ' ORDER BY p.due_date ASC ';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function sumUnpaidByCustomerId(PDO $pdo, string $customerId, ?string $companyId = null): float
    {
        $sql = 'SELECT COALESCE(SUM(p.amount), 0) FROM payments p
                INNER JOIN contracts c ON c.id = p.contract_id AND c.deleted_at IS NULL
                INNER JOIN rooms r ON r.id = c.room_id AND r.deleted_at IS NULL
                INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
                WHERE c.customer_id = ? AND p.deleted_at IS NULL AND p.status IN (\'pending\', \'overdue\') ';
        $params = [$customerId];
        if ($companyId) {
            $sql .= ' AND w.company_id = ? ';
            $params[] = $companyId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (float) $stmt->fetchColumn();
    }

    /** Müşteri: bu ay vadesi gelen (ve henüz ödenmemiş) tutar */
    public static function sumUnpaidDueThisMonthByCustomerId(PDO $pdo, string $customerId, ?string $companyId = null): float
    {
        $sql = 'SELECT COALESCE(SUM(p.amount), 0) FROM payments p
                INNER JOIN contracts c ON c.id = p.contract_id AND c.deleted_at IS NULL
                INNER JOIN rooms r ON r.id = c.room_id AND r.deleted_at IS NULL
                INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
                WHERE c.customer_id = ? AND p.deleted_at IS NULL AND p.status IN (\'pending\', \'overdue\')
                AND YEAR(p.due_date) = YEAR(CURDATE()) AND MONTH(p.due_date) = MONTH(CURDATE()) ';
        $params = [$customerId];
        if ($companyId) {
            $sql .= ' AND w.company_id = ? ';
            $params[] = $companyId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (float) $stmt->fetchColumn();
    }

    /** Müşteri: vadesi geçmiş (henüz ödenmemiş) tutar – due_date < bugün */
    public static function sumUnpaidOverdueByCustomerId(PDO $pdo, string $customerId, ?string $companyId = null): float
    {
        $sql = 'SELECT COALESCE(SUM(p.amount), 0) FROM payments p
                INNER JOIN contracts c ON c.id = p.contract_id AND c.deleted_at IS NULL
                INNER JOIN rooms r ON r.id = c.room_id AND r.deleted_at IS NULL
                INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
                WHERE c.customer_id = ? AND p.deleted_at IS NULL AND p.status IN (\'pending\', \'overdue\')
                AND DATE(p.due_date) < CURDATE() ';
        $params = [$customerId];
        if ($companyId) {
            $sql .= ' AND w.company_id = ? ';
            $params[] = $companyId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (float) $stmt->fetchColumn();
    }

    /** Ödemeyi iptal et / geri al (paid -> pending, paid_at temizlenir; borç olarak tekrar görünür) */
    public static function cancel(PDO $pdo, string $paymentId): bool
    {
        $stmt = $pdo->prepare(
            'UPDATE payments SET status = \'pending\', paid_at = NULL, payment_method = NULL, transaction_id = NULL, bank_account_id = NULL WHERE id = ? AND deleted_at IS NULL'
        );
        $stmt->execute([$paymentId]);
        return $stmt->rowCount() > 0;
    }

    /** Ödenmiş kaydın tahsilat tarihini güncelle */
    public static function updatePaidAt(PDO $pdo, string $paymentId, ?string $paidAt): bool
    {
        $paidAtValue = normalizePaidAt($paidAt);
        $stmt = $pdo->prepare(
            'UPDATE payments SET paid_at = ? WHERE id = ? AND deleted_at IS NULL AND status = \'paid\''
        );
        $stmt->execute([$paidAtValue, $paymentId]);
        return $stmt->rowCount() > 0;
    }

    public static function markAsPaid(PDO $pdo, string $paymentId, string $paymentMethod, ?string $transactionId = null, ?string $notes = null, ?string $bankAccountId = null, ?string $paidAt = null): void
    {
        $paidAtValue = normalizePaidAt($paidAt);
        $stmt = $pdo->prepare(
            'UPDATE payments SET status = \'paid\', paid_at = ?, payment_method = ?, transaction_id = ?, notes = ?, bank_account_id = ? WHERE id = ? AND deleted_at IS NULL'
        );
        $stmt->execute([
            $paidAtValue,
            $paymentMethod === 'bank_transfer' ? 'havale' : 'kredi_karti',
            $transactionId,
            $notes,
            $bankAccountId,
            $paymentId,
        ]);
    }

    public static function markManyAsPaid(PDO $pdo, array $paymentIds, string $paymentMethod, ?string $transactionId = null, ?string $notes = null, ?string $bankAccountId = null, ?string $paidAt = null): void
    {
        $method = $paymentMethod === 'bank_transfer' ? 'havale' : 'kredi_karti';
        $paidAtValue = normalizePaidAt($paidAt);
        $placeholders = implode(',', array_fill(0, count($paymentIds), '?'));
        $params = array_merge([$paidAtValue, $method, $transactionId, $notes, $bankAccountId], $paymentIds);
        $stmt = $pdo->prepare(
            "UPDATE payments SET status = 'paid', paid_at = ?, payment_method = ?, transaction_id = ?, notes = ?, bank_account_id = ? WHERE id IN ($placeholders) AND deleted_at IS NULL"
        );
        $stmt->execute($params);
    }

    /** Vadesinden önce tahsil edilmiş ödemeler (paid_at < due_date) */
    public static function findEarlyPayments(PDO $pdo, ?string $companyId, int $limit = 50, ?string $paidFrom = null, ?string $paidTo = null): array
    {
        $sql = 'SELECT p.*, c.contract_number, c.customer_id, c.id AS contract_id,
                       cu.first_name AS customer_first_name, cu.last_name AS customer_last_name,
                       DATEDIFF(DATE(p.due_date), DATE(p.paid_at)) AS days_early
                FROM payments p
                INNER JOIN contracts c ON c.id = p.contract_id AND c.deleted_at IS NULL
                INNER JOIN customers cu ON cu.id = c.customer_id AND cu.deleted_at IS NULL
                INNER JOIN rooms r ON r.id = c.room_id AND r.deleted_at IS NULL
                INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
                WHERE p.deleted_at IS NULL AND p.status = \'paid\'
                AND p.paid_at IS NOT NULL AND p.due_date IS NOT NULL
                AND DATE(p.paid_at) < DATE(p.due_date) ';
        $params = [];
        if ($companyId) {
            $sql .= ' AND w.company_id = ? ';
            $params[] = $companyId;
        }
        if ($paidFrom) {
            $sql .= ' AND DATE(p.paid_at) >= ? ';
            $params[] = $paidFrom;
        }
        if ($paidTo) {
            $sql .= ' AND DATE(p.paid_at) <= ? ';
            $params[] = $paidTo;
        }
        $sql .= ' ORDER BY p.paid_at DESC LIMIT ' . (int) $limit;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function countEarlyPayments(PDO $pdo, ?string $companyId, ?string $paidFrom = null, ?string $paidTo = null): int
    {
        $sql = 'SELECT COUNT(*) FROM payments p
                INNER JOIN contracts c ON c.id = p.contract_id AND c.deleted_at IS NULL
                INNER JOIN rooms r ON r.id = c.room_id AND r.deleted_at IS NULL
                INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
                WHERE p.deleted_at IS NULL AND p.status = \'paid\'
                AND p.paid_at IS NOT NULL AND p.due_date IS NOT NULL
                AND DATE(p.paid_at) < DATE(p.due_date) ';
        $params = [];
        if ($companyId) {
            $sql .= ' AND w.company_id = ? ';
            $params[] = $companyId;
        }
        if ($paidFrom) {
            $sql .= ' AND DATE(p.paid_at) >= ? ';
            $params[] = $paidFrom;
        }
        if ($paidTo) {
            $sql .= ' AND DATE(p.paid_at) <= ? ';
            $params[] = $paidTo;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public static function sumEarlyPayments(PDO $pdo, ?string $companyId, ?string $paidFrom = null, ?string $paidTo = null): float
    {
        $sql = 'SELECT COALESCE(SUM(p.amount), 0) FROM payments p
                INNER JOIN contracts c ON c.id = p.contract_id AND c.deleted_at IS NULL
                INNER JOIN rooms r ON r.id = c.room_id AND r.deleted_at IS NULL
                INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
                WHERE p.deleted_at IS NULL AND p.status = \'paid\'
                AND p.paid_at IS NOT NULL AND p.due_date IS NOT NULL
                AND DATE(p.paid_at) < DATE(p.due_date) ';
        $params = [];
        if ($companyId) {
            $sql .= ' AND w.company_id = ? ';
            $params[] = $companyId;
        }
        if ($paidFrom) {
            $sql .= ' AND DATE(p.paid_at) >= ? ';
            $params[] = $paidFrom;
        }
        if ($paidTo) {
            $sql .= ' AND DATE(p.paid_at) <= ? ';
            $params[] = $paidTo;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (float) $stmt->fetchColumn();
    }

    /** Tüm taksitleri ödenmiş ve en az bir erken ödeme içeren aktif sözleşmeler */
    public static function findFullyPrepaidContracts(PDO $pdo, ?string $companyId, int $limit = 20): array
    {
        $sql = 'SELECT c.id AS contract_id, c.contract_number, c.customer_id, c.start_date, c.end_date,
                       cu.first_name AS customer_first_name, cu.last_name AS customer_last_name,
                       w.name AS warehouse_name, r.room_number,
                       COUNT(p.id) AS payment_count,
                       COALESCE(SUM(p.amount), 0) AS total_paid,
                       MIN(p.paid_at) AS first_paid_at,
                       MAX(p.due_date) AS last_due_date,
                       SUM(CASE WHEN DATE(p.paid_at) < DATE(p.due_date) THEN 1 ELSE 0 END) AS early_payment_count,
                       MAX(DATEDIFF(DATE(p.due_date), DATE(p.paid_at))) AS max_days_early
                FROM contracts c
                INNER JOIN customers cu ON cu.id = c.customer_id AND cu.deleted_at IS NULL
                INNER JOIN rooms r ON r.id = c.room_id AND r.deleted_at IS NULL
                INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
                INNER JOIN payments p ON p.contract_id = c.id AND p.deleted_at IS NULL AND p.status = \'paid\'
                WHERE c.deleted_at IS NULL AND c.is_active = 1
                AND NOT EXISTS (
                    SELECT 1 FROM payments px
                    WHERE px.contract_id = c.id AND px.deleted_at IS NULL
                    AND px.status IN (\'pending\', \'overdue\')
                ) ';
        $params = [];
        if ($companyId) {
            $sql .= ' AND w.company_id = ? ';
            $params[] = $companyId;
        }
        $sql .= ' GROUP BY c.id, c.contract_number, c.customer_id, c.start_date, c.end_date,
                         cu.first_name, cu.last_name, w.name, r.room_number
                  HAVING early_payment_count > 0 AND payment_count >= 1
                  ORDER BY first_paid_at DESC
                  LIMIT ' . (int) $limit;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Seçilen dönemde vadesi gelen (ödenmemiş) ödemeler */
    public static function findDuePaymentRowsInPeriod(PDO $pdo, ?string $companyId, string $startDate, string $endDate, int $limit = 1000): array
    {
        $sql = 'SELECT p.id, p.payment_number, p.amount, p.due_date, p.status,
                       c.contract_number, c.id AS contract_id, c.customer_id,
                       cu.first_name AS customer_first_name, cu.last_name AS customer_last_name, cu.phone AS customer_phone,
                       w.name AS warehouse_name, r.room_number
                FROM payments p
                INNER JOIN contracts c ON c.id = p.contract_id AND c.deleted_at IS NULL
                INNER JOIN customers cu ON cu.id = c.customer_id AND cu.deleted_at IS NULL
                INNER JOIN rooms r ON r.id = c.room_id AND r.deleted_at IS NULL
                INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
                WHERE p.deleted_at IS NULL AND p.status IN (\'pending\', \'overdue\')
                AND p.due_date IS NOT NULL
                AND DATE(p.due_date) >= ? AND DATE(p.due_date) <= ? ';
        $params = [$startDate, $endDate];
        if ($companyId) {
            $sql .= ' AND w.company_id = ? ';
            $params[] = $companyId;
        }
        $sql .= ' ORDER BY p.due_date ASC, cu.last_name, cu.first_name LIMIT ' . (int) $limit;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Seçilen dönemde tahsil edilmiş ödemeler */
    public static function findPaidPaymentRowsInPeriod(PDO $pdo, ?string $companyId, string $startDate, string $endDate, int $limit = 1000): array
    {
        $sql = 'SELECT p.id, p.payment_number, p.amount, p.paid_at, p.due_date, p.payment_method, p.status,
                       c.contract_number, c.id AS contract_id, c.customer_id,
                       cu.first_name AS customer_first_name, cu.last_name AS customer_last_name, cu.phone AS customer_phone,
                       w.name AS warehouse_name, r.room_number,
                       ba.bank_name
                FROM payments p
                INNER JOIN contracts c ON c.id = p.contract_id AND c.deleted_at IS NULL
                INNER JOIN customers cu ON cu.id = c.customer_id AND cu.deleted_at IS NULL
                INNER JOIN rooms r ON r.id = c.room_id AND r.deleted_at IS NULL
                INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
                LEFT JOIN bank_accounts ba ON ba.id = p.bank_account_id AND ba.deleted_at IS NULL
                WHERE p.deleted_at IS NULL AND p.status = \'paid\'
                AND p.paid_at IS NOT NULL
                AND DATE(p.paid_at) >= ? AND DATE(p.paid_at) <= ? ';
        $params = [$startDate, $endDate];
        if ($companyId) {
            $sql .= ' AND w.company_id = ? ';
            $params[] = $companyId;
        }
        $sql .= ' ORDER BY p.paid_at DESC, cu.last_name, cu.first_name LIMIT ' . (int) $limit;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
