<?php
class TransportationJob
{
    public static function findAll(PDO $pdo, ?string $companyId = null, ?string $customerSearch = null, ?int $year = null, ?int $month = null): array
    {
        $staffNamesSelect = Personnel::tableExists($pdo)
            ? "(SELECT GROUP_CONCAT(CONCAT(p.first_name, ' ', p.last_name) SEPARATOR ', ') FROM transportation_job_personnel tjp INNER JOIN personnel p ON p.id = tjp.personnel_id AND p.deleted_at IS NULL WHERE tjp.transportation_job_id = tj.id AND (tjp.deleted_at IS NULL))"
            : 'NULL';
        $sql = 'SELECT tj.*, 
          c.first_name AS customer_first_name, c.last_name AS customer_last_name, c.email AS customer_email, c.phone AS customer_phone,
          ' . $staffNamesSelect . ' AS staff_names
          FROM transportation_jobs tj
          INNER JOIN customers c ON c.id = tj.customer_id AND c.deleted_at IS NULL
          WHERE tj.deleted_at IS NULL ';
        $params = [];
        if ($companyId) {
            $sql .= ' AND tj.company_id = ? ';
            $params[] = $companyId;
        }
        if ($customerSearch !== null && $customerSearch !== '') {
            $sql .= ' AND (c.first_name LIKE ? OR c.last_name LIKE ? OR c.email LIKE ? OR c.phone LIKE ?) ';
            $q = '%' . $customerSearch . '%';
            $params[] = $q;
            $params[] = $q;
            $params[] = $q;
            $params[] = $q;
        }
        if ($year !== null) {
            $sql .= ' AND YEAR(tj.job_date) = ? ';
            $params[] = $year;
        }
        if ($month !== null) {
            $sql .= ' AND MONTH(tj.job_date) = ? ';
            $params[] = $month;
        }
        $sql .= ' ORDER BY tj.job_date DESC, tj.created_at DESC ';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create(PDO $pdo, array $data): string
    {
        $id = self::uuid();
        $stmt = $pdo->prepare(
            'INSERT INTO transportation_jobs (id, company_id, customer_id, job_type, pickup_address, pickup_floor_status, pickup_elevator_status, pickup_room_count, delivery_address, delivery_floor_status, delivery_elevator_status, delivery_room_count, price, vat_rate, price_includes_vat, job_date, status, is_paid, notes, vehicle_plate) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $id,
            $data['company_id'],
            $data['customer_id'],
            $data['job_type'] ?? null,
            $data['pickup_address'] ?? null,
            $data['pickup_floor_status'] ?? null,
            $data['pickup_elevator_status'] ?? null,
            isset($data['pickup_room_count']) && $data['pickup_room_count'] !== '' ? (int) $data['pickup_room_count'] : null,
            $data['delivery_address'] ?? null,
            $data['delivery_floor_status'] ?? null,
            $data['delivery_elevator_status'] ?? null,
            isset($data['delivery_room_count']) && $data['delivery_room_count'] !== '' ? (int) $data['delivery_room_count'] : null,
            isset($data['price']) && $data['price'] !== '' ? (float) $data['price'] : null,
            isset($data['vat_rate']) && $data['vat_rate'] !== '' ? (float) $data['vat_rate'] : 20,
            !empty($data['price_includes_vat']) ? 1 : 0,
            !empty($data['job_date']) ? $data['job_date'] : null,
            $data['status'] ?? 'pending',
            !empty($data['is_paid']) ? 1 : 0,
            $data['notes'] ?? null,
            isset($data['vehicle_plate']) && trim($data['vehicle_plate']) !== '' ? trim($data['vehicle_plate']) : null,
        ]);
        self::syncPersonnel($pdo, $id, $data['personnel_ids'] ?? $data['staff_ids'] ?? []);
        return $id;
    }

    private static function syncPersonnel(PDO $pdo, string $jobId, array $personnelIds): void
    {
        if (!Personnel::tableExists($pdo)) {
            return;
        }
        $pdo->prepare('DELETE FROM transportation_job_personnel WHERE transportation_job_id = ?')->execute([$jobId]);
        $stmt = $pdo->prepare('INSERT INTO transportation_job_personnel (id, transportation_job_id, personnel_id) VALUES (?, ?, ?)');
        foreach ($personnelIds as $pid) {
            $pid = trim((string) $pid);
            if ($pid === '') {
                continue;
            }
            $stmt->execute([self::uuid(), $jobId, $pid]);
        }
    }

    public static function findOne(PDO $pdo, string $id): ?array
    {
        $stmt = $pdo->prepare(
            'SELECT tj.*, c.first_name AS customer_first_name, c.last_name AS customer_last_name, c.email AS customer_email, c.phone AS customer_phone
             FROM transportation_jobs tj
             INNER JOIN customers c ON c.id = tj.customer_id AND c.deleted_at IS NULL
             WHERE tj.id = ? AND tj.deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([$id]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$job) return null;
        $job['personnel_ids'] = [];
        if (Personnel::tableExists($pdo)) {
            $stmtStaff = $pdo->prepare('SELECT personnel_id FROM transportation_job_personnel WHERE transportation_job_id = ? AND deleted_at IS NULL');
            $stmtStaff->execute([$id]);
            $job['personnel_ids'] = $stmtStaff->fetchAll(PDO::FETCH_COLUMN);
        }
        $job['staff_ids'] = $job['personnel_ids'];
        return $job;
    }

    public static function update(PDO $pdo, string $id, array $data): void
    {
        $stmt = $pdo->prepare(
            'UPDATE transportation_jobs SET job_type = ?, pickup_address = ?, pickup_floor_status = ?, pickup_elevator_status = ?, pickup_room_count = ?,
             delivery_address = ?, delivery_floor_status = ?, delivery_elevator_status = ?, delivery_room_count = ?,
             price = ?, vat_rate = ?, price_includes_vat = ?, job_date = ?, status = ?, is_paid = ?, notes = ?, vehicle_plate = ?
             WHERE id = ? AND deleted_at IS NULL'
        );
        $stmt->execute([
            $data['job_type'] ?? null,
            $data['pickup_address'] ?? null,
            $data['pickup_floor_status'] ?? null,
            $data['pickup_elevator_status'] ?? null,
            isset($data['pickup_room_count']) && $data['pickup_room_count'] !== '' ? (int) $data['pickup_room_count'] : null,
            $data['delivery_address'] ?? null,
            $data['delivery_floor_status'] ?? null,
            $data['delivery_elevator_status'] ?? null,
            isset($data['delivery_room_count']) && $data['delivery_room_count'] !== '' ? (int) $data['delivery_room_count'] : null,
            isset($data['price']) && $data['price'] !== '' ? (float) $data['price'] : null,
            isset($data['vat_rate']) && $data['vat_rate'] !== '' ? (float) $data['vat_rate'] : 20,
            !empty($data['price_includes_vat']) ? 1 : 0,
            !empty($data['job_date']) ? $data['job_date'] : null,
            $data['status'] ?? 'pending',
            !empty($data['is_paid']) ? 1 : 0,
            $data['notes'] ?? null,
            isset($data['vehicle_plate']) && trim($data['vehicle_plate']) !== '' ? trim($data['vehicle_plate']) : null,
            $id,
        ]);
        self::syncPersonnel($pdo, $id, $data['personnel_ids'] ?? $data['staff_ids'] ?? []);
    }

    public static function remove(PDO $pdo, string $id): void
    {
        $pdo->prepare('DELETE FROM transportation_jobs WHERE id = ?')->execute([$id]);
    }

    private static function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
