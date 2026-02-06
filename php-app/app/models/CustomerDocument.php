<?php
class CustomerDocument
{
    public static function findByCustomerId(PDO $pdo, string $customerId, ?string $companyId = null): array
    {
        $sql = 'SELECT * FROM customer_documents WHERE customer_id = ? AND deleted_at IS NULL ';
        $params = [$customerId];
        if ($companyId) {
            $sql .= ' AND company_id = ? ';
            $params[] = $companyId;
        }
        $sql .= ' ORDER BY created_at DESC ';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findOne(PDO $pdo, string $id): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM customer_documents WHERE id = ? AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function create(PDO $pdo, array $data): string
    {
        $id = self::uuid();
        $stmt = $pdo->prepare(
            'INSERT INTO customer_documents (id, customer_id, company_id, name, file_path, file_size, mime_type, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $id,
            $data['customer_id'],
            $data['company_id'],
            $data['name'],
            $data['file_path'],
            $data['file_size'] ?? null,
            $data['mime_type'] ?? null,
            $data['notes'] ?? null,
        ]);
        return $id;
    }

    public static function softDelete(PDO $pdo, string $id): void
    {
        $stmt = $pdo->prepare('UPDATE customer_documents SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([$id]);
    }

    private static function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
