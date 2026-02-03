<?php
class User
{
    public static function findByEmail(PDO $pdo, string $email): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND (deleted_at IS NULL) LIMIT 1');
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function findOne(PDO $pdo, string $id): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? AND (deleted_at IS NULL) LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function updateLastLogin(PDO $pdo, string $id): void
    {
        $stmt = $pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?');
        $stmt->execute([$id]);
    }

    public static function verifyPassword(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }

    public static function hashPassword(string $plain): string
    {
        return password_hash($plain, PASSWORD_BCRYPT, ['cost' => 10]);
    }

    public static function findByCompanyId(PDO $pdo, string $companyId): array
    {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE company_id = ? AND (deleted_at IS NULL)');
        $stmt->execute([$companyId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function findStaff(PDO $pdo, ?string $companyId = null): array
    {
        if ($companyId) {
            $stmt = $pdo->prepare('SELECT id, first_name, last_name, email, phone, role, is_active, created_at FROM users WHERE company_id = ? AND deleted_at IS NULL AND role IN (\'super_admin\', \'company_owner\', \'company_staff\', \'data_entry\', \'accounting\') ORDER BY first_name, last_name');
            $stmt->execute([$companyId]);
        } else {
            $stmt = $pdo->query('SELECT id, first_name, last_name, email, phone, role, is_active, created_at FROM users WHERE deleted_at IS NULL AND role IN (\'super_admin\', \'company_owner\', \'company_staff\', \'data_entry\', \'accounting\') ORDER BY first_name, last_name');
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findAllSuperAdminIds(PDO $pdo): array
    {
        $stmt = $pdo->query("SELECT id FROM users WHERE role = 'super_admin' AND (deleted_at IS NULL)");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function create(PDO $pdo, array $data): array
    {
        $id = self::uuid();
        $stmt = $pdo->prepare(
            'INSERT INTO users (id, email, password, first_name, last_name, phone, role, company_id, is_active) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $id,
            trim($data['email'] ?? ''),
            self::hashPassword($data['password'] ?? bin2hex(random_bytes(8))),
            trim($data['first_name'] ?? ''),
            trim($data['last_name'] ?? ''),
            trim($data['phone'] ?? '') ?: null,
            $data['role'] ?? 'company_staff',
            trim($data['company_id'] ?? '') ?: null,
            isset($data['is_active']) ? (int) $data['is_active'] : 1,
        ]);
        $u = self::findOne($pdo, $id);
        return $u ?: [];
    }

    public static function update(PDO $pdo, string $id, array $data): void
    {
        $updates = [];
        $params = [];
        $allowed = ['first_name', 'last_name', 'email', 'phone', 'role', 'company_id', 'is_active'];
        foreach ($allowed as $k) {
            if (array_key_exists($k, $data)) {
                $updates[] = "`$k` = ?";
                $params[] = $k === 'is_active' ? (int) $data[$k] : ($data[$k] !== null ? $data[$k] : null);
            }
        }
        if (isset($data['password']) && $data['password'] !== '') {
            $updates[] = '`password` = ?';
            $params[] = self::hashPassword($data['password']);
        }
        if (empty($updates)) return;
        $params[] = $id;
        $pdo->prepare('UPDATE users SET ' . implode(', ', $updates) . ' WHERE id = ? AND deleted_at IS NULL')->execute($params);
    }

    public static function remove(PDO $pdo, string $id): void
    {
        $pdo->prepare('UPDATE users SET deleted_at = NOW() WHERE id = ?')->execute([$id]);
    }

    private static function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
