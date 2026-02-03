<?php
class BankAccount
{
    public static function findAll(PDO $pdo, string $companyId): array
    {
        $stmt = $pdo->prepare('SELECT * FROM bank_accounts WHERE company_id = ? AND deleted_at IS NULL ORDER BY bank_name, account_holder_name');
        $stmt->execute([$companyId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findOne(PDO $pdo, string $id, ?string $companyId = null): ?array
    {
        $sql = 'SELECT * FROM bank_accounts WHERE id = ? AND deleted_at IS NULL LIMIT 1';
        $params = [$id];
        if ($companyId !== null) {
            $sql = 'SELECT * FROM bank_accounts WHERE id = ? AND company_id = ? AND deleted_at IS NULL LIMIT 1';
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
            'INSERT INTO bank_accounts (id, company_id, bank_name, account_holder_name, account_number, iban, branch_name, is_active) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $id,
            $data['company_id'],
            trim($data['bank_name'] ?? ''),
            trim($data['account_holder_name'] ?? ''),
            trim($data['account_number'] ?? ''),
            trim($data['iban'] ?? '') ?: null,
            trim($data['branch_name'] ?? '') ?: null,
            isset($data['is_active']) ? (int) $data['is_active'] : 1,
        ]);
        return self::findOne($pdo, $id, null);
    }

    public static function update(PDO $pdo, string $id, array $data, ?string $companyId = null): ?array
    {
        $allowed = ['bank_name', 'account_holder_name', 'account_number', 'iban', 'branch_name', 'is_active'];
        $set = [];
        $params = [];
        foreach ($allowed as $k) {
            if (array_key_exists($k, $data)) {
                $set[] = "`$k` = ?";
                $params[] = $k === 'is_active' ? (int) $data[$k] : ($data[$k] !== null ? $data[$k] : null);
            }
        }
        if (empty($set)) {
            return self::findOne($pdo, $id, $companyId);
        }
        $params[] = $id;
        $sql = 'UPDATE bank_accounts SET ' . implode(', ', $set) . ' WHERE id = ? AND deleted_at IS NULL';
        if ($companyId !== null) {
            $sql .= ' AND company_id = ?';
            $params[] = $companyId;
        }
        $pdo->prepare($sql)->execute($params);
        return self::findOne($pdo, $id, $companyId);
    }

    public static function remove(PDO $pdo, string $id, ?string $companyId = null): void
    {
        $sql = 'UPDATE bank_accounts SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL';
        $params = [$id];
        if ($companyId !== null) {
            $sql .= ' AND company_id = ?';
            $params[] = $companyId;
        }
        $pdo->prepare($sql)->execute($params);
    }

    private static function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
