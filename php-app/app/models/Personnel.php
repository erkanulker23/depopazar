<?php
class Personnel
{
    public static function jobTypeLabels(): array
    {
        return [
            'sofor' => 'Şoför',
            'tasimaci' => 'Taşımacı',
            'yukleyici' => 'Yükleyici',
            'paketleme' => 'Paketleme',
            'diger' => 'Diğer',
        ];
    }

    public static function normalizeJobType(?string $jobType): string
    {
        $key = strtolower(trim((string) $jobType));
        return array_key_exists($key, self::jobTypeLabels()) ? $key : 'diger';
    }

    public static function jobTypeLabel(?string $jobType): string
    {
        $key = self::normalizeJobType($jobType);
        return self::jobTypeLabels()[$key];
    }

    public static function tableExists(PDO $pdo): bool
    {
        try {
            $stmt = $pdo->query("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'personnel' LIMIT 1");
            return (bool) ($stmt && $stmt->fetch());
        } catch (Throwable $e) {
            return false;
        }
    }

    public static function findAll(PDO $pdo, ?string $companyId = null, ?string $search = null, ?string $jobType = null, ?string $activeFilter = null): array
    {
        $sql = 'SELECT * FROM personnel WHERE deleted_at IS NULL ';
        $params = [];
        if ($companyId) {
            $sql .= ' AND company_id = ? ';
            $params[] = $companyId;
        }
        if ($jobType !== null && $jobType !== '') {
            $sql .= ' AND job_type = ? ';
            $params[] = self::normalizeJobType($jobType);
        }
        if ($activeFilter === '1') {
            $sql .= ' AND is_active = 1 ';
        } elseif ($activeFilter === '0') {
            $sql .= ' AND is_active = 0 ';
        }
        if ($search !== null && $search !== '') {
            $like = '%' . $search . '%';
            $sql .= ' AND (first_name LIKE ? OR last_name LIKE ? OR phone LIKE ? OR notes LIKE ? OR CONCAT(first_name, \' \', last_name) LIKE ?) ';
            array_push($params, $like, $like, $like, $like, $like);
        }
        $sql .= ' ORDER BY first_name ASC, last_name ASC ';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findActiveForCompany(PDO $pdo, ?string $companyId, ?string $jobType = null): array
    {
        return self::findAll($pdo, $companyId, null, $jobType, '1');
    }

    public static function findOne(PDO $pdo, string $id, ?string $companyId = null): ?array
    {
        $sql = 'SELECT * FROM personnel WHERE id = ? AND deleted_at IS NULL ';
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

    /** @param string[] $ids */
    public static function filterIdsForCompany(PDO $pdo, array $ids, string $companyId): array
    {
        $ids = array_values(array_filter(array_map('trim', $ids)));
        if ($ids === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = $ids;
        $params[] = $companyId;
        $stmt = $pdo->prepare("SELECT id FROM personnel WHERE deleted_at IS NULL AND is_active = 1 AND id IN ($placeholders) AND company_id = ?");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function create(PDO $pdo, array $data): string
    {
        $id = self::uuid();
        $stmt = $pdo->prepare(
            'INSERT INTO personnel (id, company_id, first_name, last_name, phone, job_type, is_active, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $id,
            $data['company_id'],
            trim($data['first_name'] ?? ''),
            trim($data['last_name'] ?? ''),
            trim($data['phone'] ?? '') ?: null,
            self::normalizeJobType($data['job_type'] ?? null),
            !empty($data['is_active']) ? 1 : 0,
            trim($data['notes'] ?? '') ?: null,
        ]);
        return $id;
    }

    public static function update(PDO $pdo, string $id, array $data, ?string $companyId = null): bool
    {
        $sql = 'UPDATE personnel SET first_name = ?, last_name = ?, phone = ?, job_type = ?, is_active = ?, notes = ? WHERE id = ? AND deleted_at IS NULL ';
        $params = [
            trim($data['first_name'] ?? ''),
            trim($data['last_name'] ?? ''),
            trim($data['phone'] ?? '') ?: null,
            self::normalizeJobType($data['job_type'] ?? null),
            !empty($data['is_active']) ? 1 : 0,
            trim($data['notes'] ?? '') ?: null,
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
        $sql = 'DELETE FROM personnel WHERE id = ? ';
        $params = [$id];
        if ($companyId) {
            $sql .= ' AND company_id = ? ';
            $params[] = $companyId;
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
