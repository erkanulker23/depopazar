<?php
class Payment
{
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
        $sql = 'SELECT p.*, c.contract_number, c.customer_id, cu.first_name AS customer_first_name, cu.last_name AS customer_last_name
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
        $sql .= ' ORDER BY p.due_date DESC ';
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

    public static function markAsPaid(PDO $pdo, string $paymentId, string $paymentMethod, ?string $transactionId = null, ?string $notes = null, ?string $bankAccountId = null): void
    {
        $stmt = $pdo->prepare(
            'UPDATE payments SET status = \'paid\', paid_at = NOW(), payment_method = ?, transaction_id = ?, notes = ?, bank_account_id = ? WHERE id = ? AND deleted_at IS NULL'
        );
        $stmt->execute([
            $paymentMethod === 'cash' ? 'nakit' : ($paymentMethod === 'bank_transfer' ? 'havale' : 'kredi_karti'),
            $transactionId,
            $notes,
            $bankAccountId,
            $paymentId,
        ]);
    }

    public static function markManyAsPaid(PDO $pdo, array $paymentIds, string $paymentMethod, ?string $transactionId = null, ?string $notes = null, ?string $bankAccountId = null): void
    {
        $method = $paymentMethod === 'cash' ? 'nakit' : ($paymentMethod === 'bank_transfer' ? 'havale' : 'kredi_karti');
        $placeholders = implode(',', array_fill(0, count($paymentIds), '?'));
        $params = array_merge([$method, $transactionId, $notes, $bankAccountId], $paymentIds);
        $stmt = $pdo->prepare(
            "UPDATE payments SET status = 'paid', paid_at = NOW(), payment_method = ?, transaction_id = ?, notes = ?, bank_account_id = ? WHERE id IN ($placeholders) AND deleted_at IS NULL"
        );
        $stmt->execute($params);
    }
}
