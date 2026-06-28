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

    public static function findOne(PDO $pdo, string $id): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM customer_charges WHERE id = ? AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
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

    public static function sumPaidByCustomerId(PDO $pdo, string $customerId, ?string $companyId = null): float
    {
        $sql = 'SELECT COALESCE(SUM(amount), 0) FROM customer_charges WHERE customer_id = ? AND deleted_at IS NULL AND status = \'paid\' ';
        $params = [$customerId];
        if ($companyId) {
            $sql .= ' AND company_id = ? ';
            $params[] = $companyId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (float) $stmt->fetchColumn();
    }

    public static function sumUnpaidByCompany(PDO $pdo, string $companyId): float
    {
        $stmt = $pdo->prepare(
            'SELECT COALESCE(SUM(amount), 0) FROM customer_charges WHERE company_id = ? AND deleted_at IS NULL AND status = \'pending\''
        );
        $stmt->execute([$companyId]);
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

    public static function markAsPaid(PDO $pdo, string $chargeId, ?string $notes = null, ?string $paidAt = null): void
    {
        $paidAtValue = normalizePaidAt($paidAt);
        $stmt = $pdo->prepare(
            'UPDATE customer_charges SET status = \'paid\', paid_at = ?, notes = COALESCE(?, notes) WHERE id = ? AND deleted_at IS NULL AND status = \'pending\''
        );
        $stmt->execute([$paidAtValue, $notes, $chargeId]);
    }

    public static function markManyAsPaid(PDO $pdo, array $chargeIds, ?string $notes = null, ?string $paidAt = null): void
    {
        foreach ($chargeIds as $id) {
            self::markAsPaid($pdo, $id, $notes, $paidAt);
        }
    }

    public static function cancel(PDO $pdo, string $chargeId): bool
    {
        $stmt = $pdo->prepare(
            'UPDATE customer_charges SET status = \'cancelled\', paid_at = NULL WHERE id = ? AND deleted_at IS NULL'
        );
        $stmt->execute([$chargeId]);
        return $stmt->rowCount() > 0;
    }

    private static function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
