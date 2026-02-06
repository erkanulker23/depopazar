<?php
class CustomerCharge
{
    public static function findByCustomerId(PDO $pdo, string $customerId, ?string $companyId = null): array
    {
        $sql = 'SELECT * FROM customer_charges WHERE customer_id = ? AND deleted_at IS NULL ';
        $params = [$customerId];
        if ($companyId) {
            $sql .= ' AND company_id = ? ';
            $params[] = $companyId;
        }
        $sql .= ' ORDER BY due_date DESC, created_at DESC ';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function sumUnpaidByCustomerId(PDO $pdo, string $customerId, ?string $companyId = null): float
    {
        $sql = 'SELECT COALESCE(SUM(amount), 0) FROM customer_charges WHERE customer_id = ? AND deleted_at IS NULL AND status = \'pending\' ';
        $params = [$customerId];
        if ($companyId) {
            $sql .= ' AND company_id = ? ';
            $params[] = $companyId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (float) $stmt->fetchColumn();
    }

    public static function create(PDO $pdo, array $data): string
    {
        $id = self::uuid();
        $stmt = $pdo->prepare(
            'INSERT INTO customer_charges (id, customer_id, company_id, amount, description, due_date, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $id,
            $data['customer_id'],
            $data['company_id'],
            $data['amount'],
            $data['description'] ?? null,
            !empty($data['due_date']) ? trim($data['due_date']) : null,
            $data['status'] ?? 'pending',
            $data['notes'] ?? null,
        ]);
        return $id;
    }

    private static function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
