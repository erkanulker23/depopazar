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
            appendTurkishLikeClause($sql, $params, [
                'first_name',
                'last_name',
                'phone',
                'notes',
                "CONCAT(first_name, ' ', last_name)",
            ], $search);
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

    public static function updatePhotoUrl(PDO $pdo, string $id, ?string $photoUrl, ?string $companyId = null): bool
    {
        $sql = 'UPDATE personnel SET photo_url = ? WHERE id = ? AND deleted_at IS NULL ';
        $params = [$photoUrl, $id];
        if ($companyId) {
            $sql .= ' AND company_id = ? ';
            $params[] = $companyId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
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

    /** @param string[] $ids */
    public static function findByIds(PDO $pdo, array $ids): array
    {
        $ids = array_values(array_filter(array_map('trim', $ids)));
        if ($ids === [] || !self::tableExists($pdo)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT * FROM personnel WHERE id IN ($placeholders) AND deleted_at IS NULL ORDER BY first_name ASC, last_name ASC");
        $stmt->execute($ids);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @param string[] $jobIds @return array<string, array<int, array>> */
    public static function findGroupedForTransportationJobs(PDO $pdo, array $jobIds): array
    {
        $jobIds = array_values(array_filter(array_map('trim', $jobIds)));
        if ($jobIds === [] || !self::tableExists($pdo)) {
            return [];
        }
        try {
            $pdo->query('SELECT 1 FROM transportation_job_personnel LIMIT 1');
        } catch (Throwable $e) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($jobIds), '?'));
        $sql = "SELECT tjp.transportation_job_id AS job_id, p.id, p.first_name, p.last_name, p.job_type, p.photo_url
                FROM transportation_job_personnel tjp
                INNER JOIN personnel p ON p.id = tjp.personnel_id AND p.deleted_at IS NULL
                WHERE tjp.deleted_at IS NULL AND tjp.transportation_job_id IN ($placeholders)
                ORDER BY p.first_name ASC, p.last_name ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($jobIds);
        $grouped = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $jobId = $row['job_id'];
            unset($row['job_id']);
            $grouped[$jobId][] = $row;
        }
        return $grouped;
    }

    /** Bu dönemde en çok nakliye işine giden personel */
    public static function findTopByJobCount(PDO $pdo, ?string $companyId, string $startDate, string $endDate, int $limit = 5): array
    {
        if (!self::tableExists($pdo)) {
            return [];
        }
        try {
            $pdo->query('SELECT 1 FROM transportation_job_personnel LIMIT 1');
        } catch (Throwable $e) {
            return [];
        }
        $limit = max(1, min(20, $limit));
        $sql = 'SELECT p.id AS personnel_id, p.first_name, p.last_name, p.job_type, p.photo_url,
                       COUNT(DISTINCT tjp.transportation_job_id) AS job_count
                FROM transportation_job_personnel tjp
                INNER JOIN personnel p ON p.id = tjp.personnel_id AND p.deleted_at IS NULL
                INNER JOIN transportation_jobs tj ON tj.id = tjp.transportation_job_id AND tj.deleted_at IS NULL
                WHERE tjp.deleted_at IS NULL
                AND COALESCE(DATE(tj.job_date), DATE(tj.created_at)) >= ?
                AND COALESCE(DATE(tj.job_date), DATE(tj.created_at)) <= ? ';
        $params = [$startDate, $endDate];
        if ($companyId) {
            $sql .= ' AND tj.company_id = ? ';
            $params[] = $companyId;
        }
        $sql .= ' GROUP BY p.id, p.first_name, p.last_name, p.job_type
                  ORDER BY job_count DESC, p.first_name ASC, p.last_name ASC
                  LIMIT ' . (int) $limit;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private static function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
