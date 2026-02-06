<?php
class VehicleAccident
{
    public static function findByVehicle(PDO $pdo, string $vehicleId): array
    {
        $stmt = $pdo->prepare('SELECT * FROM vehicle_accidents WHERE vehicle_id = ? AND deleted_at IS NULL ORDER BY accident_date DESC, created_at DESC');
        $stmt->execute([$vehicleId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findById(PDO $pdo, string $id, ?string $vehicleId = null): ?array
    {
        $sql = 'SELECT * FROM vehicle_accidents WHERE id = ? AND deleted_at IS NULL';
        $params = [$id];
        if ($vehicleId !== null) {
            $sql .= ' AND vehicle_id = ?';
            $params[] = $vehicleId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function create(PDO $pdo, array $data): string
    {
        $id = self::uuid();
        $stmt = $pdo->prepare(
            'INSERT INTO vehicle_accidents (id, vehicle_id, accident_date, description, damage_info, repair_cost, notes) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $id,
            $data['vehicle_id'],
            $data['accident_date'] ?? date('Y-m-d'),
            trim($data['description'] ?? '') ?: null,
            trim($data['damage_info'] ?? '') ?: null,
            isset($data['repair_cost']) && $data['repair_cost'] !== '' ? (float) str_replace(',', '.', $data['repair_cost']) : null,
            trim($data['notes'] ?? '') ?: null,
        ]);
        return $id;
    }

    public static function update(PDO $pdo, string $id, array $data, ?string $vehicleId = null): bool
    {
        $sql = 'UPDATE vehicle_accidents SET accident_date = ?, description = ?, damage_info = ?, repair_cost = ?, notes = ? WHERE id = ? AND deleted_at IS NULL';
        $params = [
            $data['accident_date'] ?? null,
            trim($data['description'] ?? '') ?: null,
            trim($data['damage_info'] ?? '') ?: null,
            isset($data['repair_cost']) && $data['repair_cost'] !== '' ? (float) str_replace(',', '.', $data['repair_cost']) : null,
            trim($data['notes'] ?? '') ?: null,
            $id,
        ];
        if ($vehicleId !== null) {
            $sql .= ' AND vehicle_id = ?';
            $params[] = $vehicleId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    public static function delete(PDO $pdo, string $id, ?string $vehicleId = null): bool
    {
        $sql = 'UPDATE vehicle_accidents SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL';
        $params = [$id];
        if ($vehicleId !== null) {
            $sql .= ' AND vehicle_id = ?';
            $params[] = $vehicleId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    private static function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
