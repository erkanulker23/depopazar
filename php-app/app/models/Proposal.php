<?php
class Proposal
{
    public static function findAll(PDO $pdo, ?string $companyId = null, ?string $statusFilter = null): array
    {
        $sql = 'SELECT p.*, c.first_name AS customer_first_name, c.last_name AS customer_last_name
                FROM proposals p
                LEFT JOIN customers c ON c.id = p.customer_id AND c.deleted_at IS NULL
                WHERE p.deleted_at IS NULL ';
        $params = [];
        if ($companyId) {
            $sql .= ' AND p.company_id = ? ';
            $params[] = $companyId;
        }
        if ($statusFilter !== null && $statusFilter !== '') {
            $sql .= ' AND p.status = ? ';
            $params[] = $statusFilter;
        }
        $sql .= ' ORDER BY p.created_at DESC ';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findOne(PDO $pdo, string $id): ?array
    {
        $stmt = $pdo->prepare('SELECT p.*, c.first_name AS customer_first_name, c.last_name AS customer_last_name FROM proposals p LEFT JOIN customers c ON c.id = p.customer_id AND c.deleted_at IS NULL WHERE p.id = ? AND p.deleted_at IS NULL LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function updateStatus(PDO $pdo, string $id, string $status): void
    {
        $stmt = $pdo->prepare('UPDATE proposals SET status = ? WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([$status, $id]);
    }

    public static function update(PDO $pdo, string $id, array $data): void
    {
        $stmt = $pdo->prepare(
            'UPDATE proposals SET title = ?, customer_id = ?, status = ?, total_amount = ?, valid_until = ?, notes = ? WHERE id = ? AND deleted_at IS NULL'
        );
        $stmt->execute([
            trim($data['title'] ?? '') ?: 'Teklif',
            trim($data['customer_id'] ?? '') ?: null,
            $data['status'] ?? 'draft',
            isset($data['total_amount']) ? (float) $data['total_amount'] : 0,
            trim($data['valid_until'] ?? '') ?: null,
            trim($data['notes'] ?? '') ?: null,
            $id,
        ]);
    }

    public static function softDelete(PDO $pdo, string $id): void
    {
        $stmt = $pdo->prepare('UPDATE proposals SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([$id]);
    }

    public static function create(PDO $pdo, array $data): array
    {
        $id = self::uuid();
        $stmt = $pdo->prepare(
            'INSERT INTO proposals (id, company_id, customer_id, title, status, total_amount, currency, valid_until, notes) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $id,
            $data['company_id'],
            trim($data['customer_id'] ?? '') ?: null,
            trim($data['title'] ?? '') ?: 'Teklif',
            $data['status'] ?? 'draft',
            isset($data['total_amount']) ? (float) $data['total_amount'] : 0,
            $data['currency'] ?? 'TRY',
            !empty($data['valid_until']) ? $data['valid_until'] : null,
            trim($data['notes'] ?? '') ?: null,
        ]);
        $p = self::findOne($pdo, $id);
        return $p ?: [];
    }

    private static function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
