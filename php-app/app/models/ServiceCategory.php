<?php
class ServiceCategory
{
    public static function findAll(PDO $pdo, ?string $companyId = null): array
    {
        $sql = 'SELECT * FROM service_categories WHERE deleted_at IS NULL ';
        $params = [];
        if ($companyId) {
            $sql .= ' AND company_id = ? ';
            $params[] = $companyId;
        }
        $sql .= ' ORDER BY name ';
        $stmt = $params ? $pdo->prepare($sql) : $pdo->query($sql);
        if ($params) $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findOne(PDO $pdo, string $id): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM service_categories WHERE id = ? AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function create(PDO $pdo, array $data): string
    {
        $id = self::uuid();
        $stmt = $pdo->prepare('INSERT INTO service_categories (id, company_id, name, description) VALUES (?, ?, ?, ?)');
        $stmt->execute([
            $id,
            $data['company_id'],
            $data['name'] ?? '',
            $data['description'] ?? null,
        ]);
        return $id;
    }

    public static function update(PDO $pdo, string $id, array $data): void
    {
        $stmt = $pdo->prepare('UPDATE service_categories SET name = ?, description = ? WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([$data['name'] ?? '', $data['description'] ?? null, $id]);
    }

    public static function softDelete(PDO $pdo, string $id): void
    {
        $stmt = $pdo->prepare('UPDATE service_categories SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL');
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
