<?php
class ExpenseCategory
{
    public static function findAll(PDO $pdo, string $companyId): array
    {
        $stmt = $pdo->prepare('SELECT * FROM expense_categories WHERE company_id = ? AND deleted_at IS NULL ORDER BY sort_order, name');
        $stmt->execute([$companyId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findOne(PDO $pdo, string $id, ?string $companyId = null): ?array
    {
        $sql = 'SELECT * FROM expense_categories WHERE id = ? AND deleted_at IS NULL LIMIT 1';
        $params = [$id];
        if ($companyId !== null) {
            $sql = 'SELECT * FROM expense_categories WHERE id = ? AND company_id = ? AND deleted_at IS NULL LIMIT 1';
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
            'INSERT INTO expense_categories (id, company_id, name, description, sort_order) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $id,
            $data['company_id'],
            trim($data['name'] ?? ''),
            trim($data['description'] ?? '') ?: null,
            (int) ($data['sort_order'] ?? 0),
        ]);
        return self::findOne($pdo, $id, null);
    }

    public static function update(PDO $pdo, string $id, array $data, ?string $companyId = null): ?array
    {
        $allowed = ['name', 'description', 'sort_order'];
        $set = [];
        $params = [];
        foreach ($allowed as $k) {
            if (array_key_exists($k, $data)) {
                $set[] = "`$k` = ?";
                $params[] = $k === 'sort_order' ? (int) $data[$k] : ($data[$k] !== null ? $data[$k] : null);
            }
        }
        if (empty($set)) {
            return self::findOne($pdo, $id, $companyId);
        }
        $params[] = $id;
        $sql = 'UPDATE expense_categories SET ' . implode(', ', $set) . ' WHERE id = ? AND deleted_at IS NULL';
        if ($companyId !== null) {
            $sql .= ' AND company_id = ?';
            $params[] = $companyId;
        }
        $pdo->prepare($sql)->execute($params);
        return self::findOne($pdo, $id, $companyId);
    }

    public static function remove(PDO $pdo, string $id, ?string $companyId = null): void
    {
        $sql = 'UPDATE expense_categories SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL';
        $params = [$id];
        if ($companyId !== null) {
            $sql .= ' AND company_id = ?';
            $params[] = $companyId;
        }
        $pdo->prepare($sql)->execute($params);
    }

    /** Şirkete göre isimle kategori bulur (deleted_at IS NULL). */
    public static function findByName(PDO $pdo, string $companyId, string $name): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM expense_categories WHERE company_id = ? AND name = ? AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([$companyId, trim($name)]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Şirkette varsa döndürür, yoksa oluşturur. */
    public static function findOrCreateByName(PDO $pdo, string $companyId, string $name, ?string $description = null): array
    {
        $existing = self::findByName($pdo, $companyId, $name);
        if ($existing !== null) {
            return $existing;
        }
        return self::create($pdo, [
            'company_id' => $companyId,
            'name' => trim($name),
            'description' => $description,
            'sort_order' => 0,
        ]);
    }

    /** Kategoriye bağlı masraf var mı? */
    public static function hasExpenses(PDO $pdo, string $categoryId): bool
    {
        $stmt = $pdo->prepare('SELECT 1 FROM expenses WHERE category_id = ? AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([$categoryId]);
        return (bool) $stmt->fetchColumn();
    }

    private static function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
