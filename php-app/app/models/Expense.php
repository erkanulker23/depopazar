<?php
class Expense
{
    public static function findAll(PDO $pdo, string $companyId, ?string $categoryId = null, ?string $startDate = null, ?string $endDate = null, ?string $paymentSourceType = null, ?string $paymentSourceId = null): array
    {
        $sql = 'SELECT e.*, ec.name AS category_name, ec.id AS category_id
                FROM expenses e
                INNER JOIN expense_categories ec ON ec.id = e.category_id AND ec.deleted_at IS NULL
                WHERE e.company_id = ? AND e.deleted_at IS NULL ';
        $params = [$companyId];
        if ($categoryId) {
            $sql .= ' AND e.category_id = ? ';
            $params[] = $categoryId;
        }
        if ($startDate) {
            $sql .= ' AND e.expense_date >= ? ';
            $params[] = $startDate;
        }
        if ($endDate) {
            $sql .= ' AND e.expense_date <= ? ';
            $params[] = $endDate;
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
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findOne(PDO $pdo, string $id, ?string $companyId = null): ?array
    {
        $sql = 'SELECT e.*, ec.name AS category_name
                FROM expenses e
                INNER JOIN expense_categories ec ON ec.id = e.category_id AND ec.deleted_at IS NULL
                WHERE e.id = ? AND e.deleted_at IS NULL LIMIT 1';
        $params = [$id];
        if ($companyId !== null) {
            $sql = 'SELECT e.*, ec.name AS category_name
                    FROM expenses e
                    INNER JOIN expense_categories ec ON ec.id = e.category_id AND ec.deleted_at IS NULL
                    WHERE e.id = ? AND e.company_id = ? AND e.deleted_at IS NULL LIMIT 1';
            $params[] = $companyId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function create(PDO $pdo, array $data): array
    {
        $id = self::uuid();
        $stmt = $pdo->prepare(
            'INSERT INTO expenses (id, company_id, category_id, amount, expense_date, payment_source_type, payment_source_id, description, notes, created_by_user_id) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $id,
            $data['company_id'],
            $data['category_id'],
            (float) ($data['amount'] ?? 0),
            $data['expense_date'] ?? date('Y-m-d'),
            $data['payment_source_type'] ?? 'bank_account',
            $data['payment_source_id'] ?? '',
            trim($data['description'] ?? '') ?: null,
            trim($data['notes'] ?? '') ?: null,
            $data['created_by_user_id'] ?? null,
        ]);
        return self::findOne($pdo, $id, null);
    }

    public static function update(PDO $pdo, string $id, array $data, ?string $companyId = null): ?array
    {
        $allowed = ['category_id', 'amount', 'expense_date', 'payment_source_type', 'payment_source_id', 'description', 'notes'];
        $set = [];
        $params = [];
        foreach ($allowed as $k) {
            if (array_key_exists($k, $data)) {
                $set[] = "`$k` = ?";
                $params[] = $k === 'amount' ? (float) $data[$k] : $data[$k];
            }
        }
        if (empty($set)) {
            return self::findOne($pdo, $id, $companyId);
        }
        $params[] = $id;
        $sql = 'UPDATE expenses SET ' . implode(', ', $set) . ' WHERE id = ? AND deleted_at IS NULL';
        if ($companyId !== null) {
            $sql .= ' AND company_id = ?';
            $params[] = $companyId;
        }
        $pdo->prepare($sql)->execute($params);
        return self::findOne($pdo, $id, $companyId);
    }

    public static function remove(PDO $pdo, string $id, ?string $companyId = null): void
    {
        $sql = 'UPDATE expenses SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL';
        $params = [$id];
        if ($companyId !== null) {
            $sql .= ' AND company_id = ?';
            $params[] = $companyId;
        }
        $pdo->prepare($sql)->execute($params);
    }

    public static function sumByCompany(PDO $pdo, string $companyId, ?string $startDate = null, ?string $endDate = null): float
    {
        $sql = 'SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE company_id = ? AND deleted_at IS NULL ';
        $params = [$companyId];
        if ($startDate) {
            $sql .= ' AND expense_date >= ? ';
            $params[] = $startDate;
        }
        if ($endDate) {
            $sql .= ' AND expense_date <= ? ';
            $params[] = $endDate;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (float) $stmt->fetchColumn();
    }

    /** Banka hesabı için: açılış + tahsilat - masraflar = bakiye */
    public static function sumExpensesFromBankAccount(PDO $pdo, string $bankAccountId, ?string $untilDate = null): float
    {
        $sql = 'SELECT COALESCE(SUM(amount), 0) FROM expenses 
                WHERE payment_source_type = \'bank_account\' AND payment_source_id = ? AND deleted_at IS NULL ';
        $params = [$bankAccountId];
        if ($untilDate) {
            $sql .= ' AND expense_date <= ? ';
            $params[] = $untilDate;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (float) $stmt->fetchColumn();
    }

    private static function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
