<?php
class Company
{
    public static function getCompanyIdForUser(PDO $pdo, array $user): ?string
    {
        if (!empty($user['company_id'])) {
            return $user['company_id'];
        }
        if (($user['role'] ?? '') === 'super_admin') {
            $stmt = $pdo->query('SELECT id FROM companies WHERE deleted_at IS NULL LIMIT 1');
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? $row['id'] : null;
        }
        return null;
    }

    public static function findOne(PDO $pdo, string $id): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM companies WHERE id = ? AND (deleted_at IS NULL) LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function getPublicBrand(PDO $pdo): ?array
    {
        $stmt = $pdo->query('SELECT project_name, logo_url FROM companies WHERE deleted_at IS NULL AND is_active = 1 LIMIT 1');
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function update(PDO $pdo, string $id, array $data): ?array
    {
        $allowed = ['name', 'project_name', 'logo_url', 'contract_template_url', 'email', 'phone', 'whatsapp_number', 'address', 'mersis_number', 'tax_office'];
        $set = [];
        $params = [];
        foreach ($allowed as $k) {
            if (array_key_exists($k, $data)) {
                $set[] = "`$k` = ?";
                $params[] = $data[$k] !== null ? $data[$k] : null;
            }
        }
        if (empty($set)) {
            return self::findOne($pdo, $id);
        }
        $params[] = $id;
        $pdo->prepare('UPDATE companies SET ' . implode(', ', $set) . ' WHERE id = ? AND deleted_at IS NULL')->execute($params);
        return self::findOne($pdo, $id);
    }
}
