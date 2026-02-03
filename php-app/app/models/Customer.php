<?php
class Customer
{
    public static function findAll(PDO $pdo, ?string $companyId = null, ?string $search = null): array
    {
        $sql = 'SELECT c.* FROM customers c WHERE c.deleted_at IS NULL ';
        $params = [];
        if ($companyId) {
            $sql .= ' AND c.company_id = ? ';
            $params[] = $companyId;
        }
        if ($search !== null && $search !== '') {
            $sql .= ' AND (c.first_name LIKE ? OR c.last_name LIKE ? OR c.email LIKE ? OR c.phone LIKE ?) ';
            $q = '%' . $search . '%';
            $params[] = $q;
            $params[] = $q;
            $params[] = $q;
            $params[] = $q;
        }
        $sql .= ' ORDER BY c.first_name, c.last_name ';
        if (count($params) > 0) {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        } else {
            $stmt = $pdo->query($sql);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findOne(PDO $pdo, string $id): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM customers WHERE id = ? AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function create(PDO $pdo, array $data): array
    {
        $id = self::uuid();
        $stmt = $pdo->prepare(
            'INSERT INTO customers (id, company_id, first_name, last_name, email, phone, identity_number, address, notes, is_active) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $id,
            $data['company_id'],
            trim($data['first_name'] ?? ''),
            trim($data['last_name'] ?? ''),
            trim($data['email'] ?? '') !== '' ? trim($data['email']) : '',
            trim($data['phone'] ?? '') ?: null,
            trim($data['identity_number'] ?? '') ?: null,
            trim($data['address'] ?? '') ?: null,
            trim($data['notes'] ?? '') ?: null,
            isset($data['is_active']) ? (int) $data['is_active'] : 1,
        ]);
        return self::findOne($pdo, $id);
    }

    private static function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
