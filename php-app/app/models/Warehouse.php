<?php
class Warehouse
{
    public static function create(PDO $pdo, array $data): array
    {
        $id = self::uuid();
        $stmt = $pdo->prepare(
            'INSERT INTO warehouses (id, name, company_id, address, city, district, total_floors, description, is_active, monthly_base_fee) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $id,
            $data['name'],
            $data['company_id'],
            $data['address'] ?? null,
            $data['city'] ?? null,
            $data['district'] ?? null,
            isset($data['total_floors']) ? (int) $data['total_floors'] : null,
            $data['description'] ?? null,
            isset($data['is_active']) ? (int) (bool) $data['is_active'] : 1,
            isset($data['monthly_base_fee']) && $data['monthly_base_fee'] !== '' ? (float) $data['monthly_base_fee'] : null,
        ]);
        return self::findOne($pdo, $id);
    }

    public static function countAll(PDO $pdo): int
    {
        $stmt = $pdo->query('SELECT COUNT(*) FROM warehouses WHERE deleted_at IS NULL');
        return (int) $stmt->fetchColumn();
    }

    public static function findAll(PDO $pdo, ?string $companyId = null): array
    {
        if ($companyId) {
            $stmt = $pdo->prepare(
                'SELECT w.*, 
                  (SELECT COUNT(*) FROM rooms r WHERE r.warehouse_id = w.id AND r.deleted_at IS NULL) AS room_count
                 FROM warehouses w 
                 WHERE w.company_id = ? AND w.deleted_at IS NULL 
                 ORDER BY w.name'
            );
            $stmt->execute([$companyId]);
        } else {
            $stmt = $pdo->query(
                'SELECT w.*, 
                  (SELECT COUNT(*) FROM rooms r WHERE r.warehouse_id = w.id AND r.deleted_at IS NULL) AS room_count
                 FROM warehouses w 
                 WHERE w.deleted_at IS NULL ORDER BY w.name'
            );
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findOne(PDO $pdo, string $id): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM warehouses WHERE id = ? AND (deleted_at IS NULL) LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        $stmt2 = $pdo->prepare('SELECT * FROM rooms WHERE warehouse_id = ? AND deleted_at IS NULL ORDER BY room_number');
        $stmt2->execute([$id]);
        $row['rooms'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        return $row;
    }

    public static function update(PDO $pdo, string $id, array $data): array
    {
        $allowed = ['name', 'address', 'city', 'district', 'total_floors', 'description', 'is_active', 'monthly_base_fee'];
        $set = [];
        $params = [];
        foreach ($allowed as $k) {
            if (array_key_exists($k, $data)) {
                $set[] = "`$k` = ?";
                $val = $data[$k];
                if ($k === 'is_active') $val = (int) (bool) $val;
                elseif ($k === 'monthly_base_fee') $val = ($val !== '' && $val !== null) ? (float) $val : null;
                $params[] = $val;
            }
        }
        if (empty($set)) {
            return self::findOne($pdo, $id);
        }
        $params[] = $id;
        $pdo->prepare('UPDATE warehouses SET ' . implode(', ', $set) . ' WHERE id = ?')->execute($params);
        return self::findOne($pdo, $id);
    }

    public static function hasActiveContracts(PDO $pdo, string $warehouseId): bool
    {
        $stmt = $pdo->prepare(
            'SELECT 1 FROM rooms r 
             INNER JOIN contracts c ON c.room_id = r.id AND c.deleted_at IS NULL AND c.is_active = 1 
             WHERE r.warehouse_id = ? AND r.deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([$warehouseId]);
        return (bool) $stmt->fetch();
    }

    public static function remove(PDO $pdo, string $id): void
    {
        $pdo->prepare('UPDATE rooms SET deleted_at = NOW() WHERE warehouse_id = ?')->execute([$id]);
        $pdo->prepare('UPDATE warehouses SET deleted_at = NOW() WHERE id = ?')->execute([$id]);
    }

    private static function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
