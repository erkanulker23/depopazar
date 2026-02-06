<?php
class VehicleTrafficInsuranceDocument
{
    public static function findByTrafficInsuranceId(PDO $pdo, string $trafficInsuranceId): array
    {
        $stmt = $pdo->prepare('SELECT * FROM vehicle_traffic_insurance_documents WHERE traffic_insurance_id = ? AND deleted_at IS NULL ORDER BY created_at DESC');
        $stmt->execute([$trafficInsuranceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findOne(PDO $pdo, string $id): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM vehicle_traffic_insurance_documents WHERE id = ? AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function create(PDO $pdo, array $data): string
    {
        $id = self::uuid();
        $stmt = $pdo->prepare(
            'INSERT INTO vehicle_traffic_insurance_documents (id, traffic_insurance_id, file_path, file_name, file_size, mime_type) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $id,
            $data['traffic_insurance_id'],
            $data['file_path'],
            $data['file_name'] ?? null,
            $data['file_size'] ?? null,
            $data['mime_type'] ?? null,
        ]);
        return $id;
    }

    public static function softDelete(PDO $pdo, string $id): bool
    {
        $stmt = $pdo->prepare('UPDATE vehicle_traffic_insurance_documents SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([$id]);
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
