<?php
class CreditCard
{
    public static function findAll(PDO $pdo, string $companyId): array
    {
        $stmt = $pdo->prepare('SELECT * FROM credit_cards WHERE company_id = ? AND deleted_at IS NULL ORDER BY bank_name, card_holder_name');
        $stmt->execute([$companyId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findOne(PDO $pdo, string $id, ?string $companyId = null): ?array
    {
        $sql = 'SELECT * FROM credit_cards WHERE id = ? AND deleted_at IS NULL LIMIT 1';
        $params = [$id];
        if ($companyId !== null) {
            $sql = 'SELECT * FROM credit_cards WHERE id = ? AND company_id = ? AND deleted_at IS NULL LIMIT 1';
            $params[] = $companyId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Son 4 haneyi normalize eder; boşsa null, geçersizse null döner */
    public static function parseLastFourDigits(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }
        $digits = preg_replace('/\D/', '', $value);
        return strlen($digits) === 4 ? $digits : null;
    }

    public static function isValidLastFourInput(?string $value): bool
    {
        if ($value === null || trim($value) === '') {
            return true;
        }
        return self::parseLastFourDigits($value) !== null;
    }

    public static function existsByLastFourDigits(PDO $pdo, string $companyId, string $lastFour, ?string $excludeId = null): bool
    {
        $sql = 'SELECT 1 FROM credit_cards WHERE company_id = ? AND deleted_at IS NULL AND last_four_digits = ?';
        $params = [$companyId, $lastFour];
        if ($excludeId !== null && $excludeId !== '') {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }
        $sql .= ' LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    }

    public static function create(PDO $pdo, array $data): array
    {
        $id = self::uuid();
        $stmt = $pdo->prepare(
            'INSERT INTO credit_cards (id, company_id, bank_name, card_holder_name, last_four_digits, nickname, is_active) 
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $id,
            $data['company_id'],
            trim($data['bank_name'] ?? ''),
            trim($data['card_holder_name'] ?? ''),
            trim($data['last_four_digits'] ?? '') ?: null,
            trim($data['nickname'] ?? '') ?: null,
            isset($data['is_active']) ? (int) $data['is_active'] : 1,
        ]);
        return self::findOne($pdo, $id, null);
    }

    public static function update(PDO $pdo, string $id, array $data, ?string $companyId = null): ?array
    {
        $allowed = ['bank_name', 'card_holder_name', 'last_four_digits', 'nickname', 'is_active'];
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
        $sql = 'UPDATE credit_cards SET ' . implode(', ', $set) . ' WHERE id = ? AND deleted_at IS NULL';
        if ($companyId !== null) {
            $sql .= ' AND company_id = ?';
            $params[] = $companyId;
        }
        $pdo->prepare($sql)->execute($params);
        return self::findOne($pdo, $id, $companyId);
    }

    public static function remove(PDO $pdo, string $id, ?string $companyId = null): void
    {
        $sql = 'DELETE FROM credit_cards WHERE id = ?';
        $params = [$id];
        if ($companyId !== null) {
            $sql .= ' AND company_id = ?';
            $params[] = $companyId;
        }
        $pdo->prepare($sql)->execute($params);
    }

    public static function getDisplayName(array $card): string
    {
        $parts = [$card['bank_name'] ?? ''];
        if (!empty($card['nickname'])) {
            $parts[] = $card['nickname'];
        }
        if (!empty($card['last_four_digits'])) {
            $parts[] = '****' . $card['last_four_digits'];
        }
        return implode(' - ', array_filter($parts));
    }

    private static function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
