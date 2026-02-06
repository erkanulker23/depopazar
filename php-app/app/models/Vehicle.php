<?php
class Vehicle
{
    public static function findAll(PDO $pdo, ?string $companyId = null): array
    {
        $sql = 'SELECT * FROM vehicles WHERE deleted_at IS NULL ';
        $params = [];
        if ($companyId) {
            $sql .= ' AND company_id = ? ';
            $params[] = $companyId;
        }
        $sql .= ' ORDER BY plate ASC ';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findById(PDO $pdo, string $id, ?string $companyId = null): ?array
    {
        $sql = 'SELECT * FROM vehicles WHERE id = ? AND deleted_at IS NULL ';
        $params = [$id];
        if ($companyId) {
            $sql .= ' AND company_id = ? ';
            $params[] = $companyId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function create(PDO $pdo, array $data): string
    {
        $id = self::uuid();
        $stmt = $pdo->prepare(
            'INSERT INTO vehicles (id, company_id, plate, model_year, kasko_date, inspection_date, cargo_volume_m3, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $id,
            $data['company_id'],
            self::normalizePlate($data['plate'] ?? ''),
            !empty($data['model_year']) ? (int) $data['model_year'] : null,
            !empty($data['kasko_date']) ? $data['kasko_date'] : null,
            !empty($data['inspection_date']) ? $data['inspection_date'] : null,
            isset($data['cargo_volume_m3']) && $data['cargo_volume_m3'] !== '' ? (float) str_replace(',', '.', $data['cargo_volume_m3']) : null,
            !empty($data['notes']) ? trim($data['notes']) : null,
        ]);
        return $id;
    }

    public static function update(PDO $pdo, string $id, array $data, ?string $companyId = null): bool
    {
        $sql = 'UPDATE vehicles SET plate = ?, model_year = ?, kasko_date = ?, inspection_date = ?, cargo_volume_m3 = ?, notes = ? WHERE id = ? AND deleted_at IS NULL ';
        $params = [
            self::normalizePlate($data['plate'] ?? ''),
            !empty($data['model_year']) ? (int) $data['model_year'] : null,
            !empty($data['kasko_date']) ? $data['kasko_date'] : null,
            !empty($data['inspection_date']) ? $data['inspection_date'] : null,
            isset($data['cargo_volume_m3']) && $data['cargo_volume_m3'] !== '' ? (float) str_replace(',', '.', $data['cargo_volume_m3']) : null,
            !empty($data['notes']) ? trim($data['notes']) : null,
            $id,
        ];
        if ($companyId) {
            $sql .= ' AND company_id = ? ';
            $params[] = $companyId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    public static function delete(PDO $pdo, string $id, ?string $companyId = null): bool
    {
        $sql = 'UPDATE vehicles SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL ';
        $params = [$id];
        if ($companyId) {
            $sql .= ' AND company_id = ? ';
            $params[] = $companyId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    public static function normalizePlate(string $plate): string
    {
        $plate = trim(preg_replace('/\s+/', ' ', $plate));
        return mb_strtoupper($plate, 'UTF-8');
    }

    /** @return string[] */
    public static function parsePlatesFromField(?string $value): array
    {
        if ($value === null || trim($value) === '') {
            return [];
        }
        $plates = [];
        foreach (preg_split('/[,;]/', $value) as $s) {
            $p = self::normalizePlate($s);
            if ($p !== '') {
                $plates[] = $p;
            }
        }
        return $plates;
    }

    private static function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
