<?php
class Service
{
    public static function findAll(PDO $pdo, ?string $companyId = null, ?string $categoryId = null): array
    {
        $sql = 'SELECT s.*, sc.name AS category_name FROM services s
                INNER JOIN service_categories sc ON sc.id = s.category_id AND sc.deleted_at IS NULL
                WHERE s.deleted_at IS NULL ';
        $params = [];
        if ($companyId) {
            $sql .= ' AND s.company_id = ? ';
            $params[] = $companyId;
        }
        if ($categoryId) {
            $sql .= ' AND s.category_id = ? ';
            $params[] = $categoryId;
        }
        $sql .= ' ORDER BY sc.name, s.name ';
        $stmt = $params ? $pdo->prepare($sql) : $pdo->query($sql);
        if ($params) $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findOne(PDO $pdo, string $id): ?array
    {
        $stmt = $pdo->prepare('SELECT s.*, sc.name AS category_name FROM services s LEFT JOIN service_categories sc ON sc.id = s.category_id AND sc.deleted_at IS NULL WHERE s.id = ? AND s.deleted_at IS NULL LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function create(PDO $pdo, array $data): string
    {
        $id = self::uuid();
        $stmt = $pdo->prepare('INSERT INTO services (id, company_id, category_id, name, description, unit_price, unit) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $id,
            $data['company_id'],
            $data['category_id'],
            $data['name'] ?? '',
            $data['description'] ?? null,
            isset($data['unit_price']) ? (float) $data['unit_price'] : 0,
            $data['unit'] ?? null,
        ]);
        return $id;
    }

    public static function update(PDO $pdo, string $id, array $data): void
    {
        $stmt = $pdo->prepare('UPDATE services SET category_id = ?, name = ?, description = ?, unit_price = ?, unit = ? WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([
            $data['category_id'] ?? null,
            $data['name'] ?? '',
            $data['description'] ?? null,
            isset($data['unit_price']) ? (float) $data['unit_price'] : 0,
            $data['unit'] ?? null,
            $id,
        ]);
    }

    public static function softDelete(PDO $pdo, string $id): void
    {
        $stmt = $pdo->prepare('UPDATE services SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL');
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
