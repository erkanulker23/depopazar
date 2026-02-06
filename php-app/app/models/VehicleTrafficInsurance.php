<?php
class VehicleTrafficInsurance
{
    public static function findByVehicle(PDO $pdo, string $vehicleId): array
    {
        $stmt = $pdo->prepare('SELECT * FROM vehicle_traffic_insurances WHERE vehicle_id = ? AND deleted_at IS NULL ORDER BY end_date DESC, start_date DESC');
        $stmt->execute([$vehicleId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findById(PDO $pdo, string $id, ?string $vehicleId = null): ?array
    {
        $sql = 'SELECT * FROM vehicle_traffic_insurances WHERE id = ? AND deleted_at IS NULL';
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
            'INSERT INTO vehicle_traffic_insurances (id, vehicle_id, policy_number, insurer_name, start_date, end_date, notes) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $id,
            $data['vehicle_id'],
            trim($data['policy_number'] ?? '') ?: null,
            trim($data['insurer_name'] ?? '') ?: null,
            $data['start_date'] ?? date('Y-m-d'),
            $data['end_date'] ?? date('Y-m-d'),
            trim($data['notes'] ?? '') ?: null,
        ]);
        return $id;
    }

    public static function update(PDO $pdo, string $id, array $data, ?string $vehicleId = null): bool
    {
        $sql = 'UPDATE vehicle_traffic_insurances SET policy_number = ?, insurer_name = ?, start_date = ?, end_date = ?, notes = ? WHERE id = ? AND deleted_at IS NULL';
        $params = [
            trim($data['policy_number'] ?? '') ?: null,
            trim($data['insurer_name'] ?? '') ?: null,
            $data['start_date'] ?? null,
            $data['end_date'] ?? null,
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
        $sql = 'UPDATE vehicle_traffic_insurances SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL';
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
