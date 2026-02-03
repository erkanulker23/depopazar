<?php
class Room
{
    public static function create(PDO $pdo, array $data): array
    {
        $id = self::uuid();
        $stmt = $pdo->prepare(
            'INSERT INTO rooms (id, room_number, warehouse_id, area_m2, monthly_price, status, floor, block, corridor, description, notes) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $id,
            $data['room_number'],
            $data['warehouse_id'],
            $data['area_m2'],
            $data['monthly_price'],
            $data['status'] ?? 'empty',
            $data['floor'] ?? null,
            $data['block'] ?? null,
            $data['corridor'] ?? null,
            $data['description'] ?? null,
            $data['notes'] ?? null,
        ]);
        return self::findOne($pdo, $id);
    }

    public static function findAll(PDO $pdo, ?string $warehouseId = null): array
    {
        $sql = 'SELECT r.*, w.name AS warehouse_name, w.company_id 
                FROM rooms r 
                LEFT JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL 
                WHERE r.deleted_at IS NULL ';
        $params = [];
        if ($warehouseId) {
            $sql .= ' AND r.warehouse_id = ? ';
            $params[] = $warehouseId;
        }
        $sql .= ' ORDER BY w.name, r.room_number ';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findOne(PDO $pdo, string $id): ?array
    {
        $stmt = $pdo->prepare(
            'SELECT r.*, w.name AS warehouse_name, w.company_id 
             FROM rooms r 
             LEFT JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL 
             WHERE r.id = ? AND r.deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function update(PDO $pdo, string $id, array $data): array
    {
        $allowed = ['room_number', 'warehouse_id', 'area_m2', 'monthly_price', 'status', 'floor', 'block', 'corridor', 'description', 'notes'];
        $set = [];
        $params = [];
        foreach ($allowed as $k) {
            if (array_key_exists($k, $data)) {
                $set[] = "`$k` = ?";
                $params[] = $data[$k];
            }
        }
        if (empty($set)) {
            return self::findOne($pdo, $id);
        }
        $params[] = $id;
        $pdo->prepare('UPDATE rooms SET ' . implode(', ', $set) . ' WHERE id = ?')->execute($params);
        return self::findOne($pdo, $id);
    }

    public static function hasActiveContract(PDO $pdo, string $roomId): bool
    {
        $stmt = $pdo->prepare(
            'SELECT 1 FROM contracts WHERE room_id = ? AND deleted_at IS NULL AND is_active = 1 LIMIT 1'
        );
        $stmt->execute([$roomId]);
        return (bool) $stmt->fetch();
    }

    public static function remove(PDO $pdo, string $id): void
    {
        $pdo->prepare('UPDATE rooms SET deleted_at = NOW() WHERE id = ?')->execute([$id]);
    }

    private static function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
