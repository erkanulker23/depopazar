<?php
class Customer
{
    public static function findAll(PDO $pdo, ?string $companyId = null, ?string $search = null, ?int $limit = null, int $offset = 0): array
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
        if ($limit !== null) {
            $sql .= ' LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        }
        if (count($params) > 0) {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        } else {
            $stmt = $pdo->query($sql);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function count(PDO $pdo, ?string $companyId = null, ?string $search = null): int
    {
        $sql = 'SELECT COUNT(*) FROM customers c WHERE c.deleted_at IS NULL ';
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
        $stmt = count($params) > 0 ? $pdo->prepare($sql) : $pdo->query($sql);
        if (count($params) > 0) {
            $stmt->execute($params);
        }
        return (int) $stmt->fetchColumn();
    }

    public static function findOne(PDO $pdo, string $id): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM customers WHERE id = ? AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Aynı şirkette aynı e-posta ile kayıtlı müşteri var mı? (excludeId = güncellemede kendi kaydı) */
    public static function findByEmail(PDO $pdo, string $companyId, string $email, ?string $excludeId = null): ?array
    {
        if ($email === '') {
            return null;
        }
        $sql = 'SELECT * FROM customers WHERE company_id = ? AND email = ? AND deleted_at IS NULL';
        $params = [$companyId, $email];
        if ($excludeId !== null && $excludeId !== '') {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }
        $sql .= ' LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Aynı şirkette aynı telefon ile kayıtlı müşteri var mı? (excludeId = güncellemede kendi kaydı) */
    public static function findByPhone(PDO $pdo, string $companyId, ?string $phone, ?string $excludeId = null): ?array
    {
        if ($phone === null || $phone === '') {
            return null;
        }
        $sql = 'SELECT * FROM customers WHERE company_id = ? AND phone = ? AND deleted_at IS NULL';
        $params = [$companyId, $phone];
        if ($excludeId !== null && $excludeId !== '') {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }
        $sql .= ' LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function update(PDO $pdo, string $id, array $data): void
    {
        $allowed = ['first_name', 'last_name', 'email', 'phone', 'identity_number', 'address', 'notes', 'is_active'];
        $updates = [];
        $params = [];
        foreach ($allowed as $key) {
            if (array_key_exists($key, $data)) {
                $updates[] = "`$key` = ?";
                $params[] = $key === 'is_active' ? (int) $data[$key] : $data[$key];
            }
        }
        if (empty($updates)) {
            return;
        }
        $params[] = $id;
        $stmt = $pdo->prepare('UPDATE customers SET ' . implode(', ', $updates) . ' WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute($params);
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

    /** Soft delete: deleted_at set eder */
    public static function softDelete(PDO $pdo, string $id): bool
    {
        $stmt = $pdo->prepare('UPDATE customers SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([$id]);
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
