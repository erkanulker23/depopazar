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

    /** Karşılaştırma için alanları normalize eder */
    public static function normalizeFields(array $data): array
    {
        $branch = trim($data['branch_name'] ?? '');
        return [
            'bank_name' => mb_strtolower(trim($data['bank_name'] ?? '')),
            'account_holder_name' => mb_strtolower(trim($data['account_holder_name'] ?? '')),
            'account_number' => preg_replace('/\s+/', '', trim($data['account_number'] ?? '')),
            'iban' => self::normalizeIban($data['iban'] ?? null),
            'branch_name' => $branch !== '' ? mb_strtolower($branch) : null,
        ];
    }

    public static function normalizeIban(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }
        $iban = strtoupper(preg_replace('/\s+/', '', $value));
        return $iban !== '' ? $iban : null;
    }

    /** Aynı şirkette birebir aynı banka hesabı var mı */
    public static function existsDuplicate(PDO $pdo, string $companyId, array $data, ?string $excludeId = null): bool
    {
        $fingerprint = self::normalizeFields($data);
        foreach (self::findAll($pdo, $companyId) as $account) {
            if ($excludeId !== null && ($account['id'] ?? '') === $excludeId) {
                continue;
            }
            if (self::normalizeFields($account) === $fingerprint) {
                return true;
            }
        }
        return false;
    }

    public static function create(PDO $pdo, array $data): array
    {
        $id = self::uuid();
        $openingBalance = isset($data['opening_balance']) ? (float) $data['opening_balance'] : 0;
        $stmt = $pdo->prepare(
            'INSERT INTO bank_accounts (id, company_id, bank_name, account_holder_name, account_number, iban, branch_name, is_active, opening_balance) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
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
            $openingBalance,
        ]);
        return self::findOne($pdo, $id, null);
    }

    public static function update(PDO $pdo, string $id, array $data, ?string $companyId = null): ?array
    {
        $allowed = ['bank_name', 'account_holder_name', 'account_number', 'iban', 'branch_name', 'is_active', 'opening_balance'];
        $set = [];
        $params = [];
        foreach ($allowed as $k) {
            if (array_key_exists($k, $data)) {
                $set[] = "`$k` = ?";
                $params[] = $k === 'is_active' ? (int) $data[$k] : ($k === 'opening_balance' ? (float) ($data[$k] ?? 0) : ($data[$k] !== null ? $data[$k] : null));
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
        $sql = 'DELETE FROM bank_accounts WHERE id = ?';
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
