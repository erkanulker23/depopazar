<?php
class Contract
{
    public static function findAll(PDO $pdo, ?string $companyId = null, ?string $statusFilter = null, ?string $debtFilter = null, ?int $limit = null, int $offset = 0): array
    {
        $sql = 'SELECT c.*, 
          cu.first_name AS customer_first_name, cu.last_name AS customer_last_name, cu.email AS customer_email,
          r.room_number, r.monthly_price AS room_monthly_price,
          w.name AS warehouse_name
          FROM contracts c
          INNER JOIN customers cu ON cu.id = c.customer_id AND cu.deleted_at IS NULL
          INNER JOIN rooms r ON r.id = c.room_id AND r.deleted_at IS NULL
          INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
          WHERE c.deleted_at IS NULL ';
        $params = [];
        if ($companyId) {
            $sql .= ' AND w.company_id = ? ';
            $params[] = $companyId;
        }
        if ($statusFilter === 'active') {
            $sql .= ' AND c.is_active = 1 ';
        } elseif ($statusFilter === 'inactive') {
            $sql .= ' AND c.is_active = 0 ';
        }
        if ($debtFilter === 'with_debt') {
            $sql .= ' AND EXISTS (SELECT 1 FROM payments p WHERE p.contract_id = c.id AND p.deleted_at IS NULL AND p.status IN (\'pending\', \'overdue\')) ';
        } elseif ($debtFilter === 'no_debt') {
            $sql .= ' AND NOT EXISTS (SELECT 1 FROM payments p WHERE p.contract_id = c.id AND p.deleted_at IS NULL AND p.status IN (\'pending\', \'overdue\')) ';
        }
        $sql .= ' ORDER BY c.created_at DESC ';
        if ($limit !== null) {
            $sql .= ' LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function count(PDO $pdo, ?string $companyId = null, ?string $statusFilter = null, ?string $debtFilter = null): int
    {
        $sql = 'SELECT COUNT(*) FROM contracts c
          INNER JOIN rooms r ON r.id = c.room_id AND r.deleted_at IS NULL
          INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
          WHERE c.deleted_at IS NULL ';
        $params = [];
        if ($companyId) {
            $sql .= ' AND w.company_id = ? ';
            $params[] = $companyId;
        }
        if ($statusFilter === 'active') {
            $sql .= ' AND c.is_active = 1 ';
        } elseif ($statusFilter === 'inactive') {
            $sql .= ' AND c.is_active = 0 ';
        }
        if ($debtFilter === 'with_debt') {
            $sql .= ' AND EXISTS (SELECT 1 FROM payments p WHERE p.contract_id = c.id AND p.deleted_at IS NULL AND p.status IN (\'pending\', \'overdue\')) ';
        } elseif ($debtFilter === 'no_debt') {
            $sql .= ' AND NOT EXISTS (SELECT 1 FROM payments p WHERE p.contract_id = c.id AND p.deleted_at IS NULL AND p.status IN (\'pending\', \'overdue\')) ';
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public static function create(PDO $pdo, array $data): array
    {
        $id = self::uuid();
        $stmt = $pdo->prepare(
            'INSERT INTO contracts (id, contract_number, customer_id, room_id, start_date, end_date, monthly_price, payment_frequency_months, is_active, sold_by_user_id, transportation_fee, pickup_location, discount, driver_name, driver_phone, vehicle_plate, contract_pdf_url, notes) 
             VALUES (?, ?, ?, ?, ?, ?, ?, 1, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $maxAttempts = 5;
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $contractNumber = $data['contract_number'] ?? self::generateContractNumber($pdo);
            try {
                $stmt->execute([
                    $id,
                    $contractNumber,
                    $data['customer_id'],
                    $data['room_id'],
                    $data['start_date'],
                    $data['end_date'],
                    $data['monthly_price'],
                    $data['sold_by_user_id'] ?? null,
                    $data['transportation_fee'] ?? 0,
                    $data['pickup_location'] ?? null,
                    $data['discount'] ?? 0,
                    $data['driver_name'] ?? null,
                    $data['driver_phone'] ?? null,
                    $data['vehicle_plate'] ?? null,
                    $data['contract_pdf_url'] ?? null,
                    $data['notes'] ?? null,
                ]);
                return self::findOne($pdo, $id);
            } catch (PDOException $e) {
                if ($attempt === $maxAttempts - 1) {
                    throw $e;
                }
                $code = $e->getCode();
                $msg = $e->getMessage();
                if ($code === '23000' || (int) $code === 23000 || strpos($msg, '1062') !== false || strpos($msg, 'Duplicate entry') !== false) {
                    continue;
                }
                throw $e;
            }
        }
        return self::findOne($pdo, $id);
    }

    public static function findOne(PDO $pdo, string $id): ?array
    {
        $stmt = $pdo->prepare(
            'SELECT c.*, cu.first_name AS customer_first_name, cu.last_name AS customer_last_name, cu.id AS customer_id, cu.email AS customer_email, cu.phone AS customer_phone, cu.address AS customer_address,
             r.room_number, r.id AS room_id, r.monthly_price AS room_monthly_price,
             w.name AS warehouse_name, w.id AS warehouse_id, w.company_id,
             sb.first_name AS sold_by_first_name, sb.last_name AS sold_by_last_name
             FROM contracts c 
             INNER JOIN customers cu ON cu.id = c.customer_id 
             INNER JOIN rooms r ON r.id = c.room_id 
             INNER JOIN warehouses w ON w.id = r.warehouse_id 
             LEFT JOIN users sb ON sb.id = c.sold_by_user_id AND sb.deleted_at IS NULL
             WHERE c.id = ? AND c.deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function update(PDO $pdo, string $id, array $data): void
    {
        $stmt = $pdo->prepare(
            'UPDATE contracts SET start_date = ?, end_date = ?, transportation_fee = ?, pickup_location = ?, discount = ?, driver_name = ?, driver_phone = ?, vehicle_plate = ?, notes = ? WHERE id = ? AND deleted_at IS NULL'
        );
        $stmt->execute([
            $data['start_date'] ?? null,
            $data['end_date'] ?? null,
            $data['transportation_fee'] ?? 0,
            $data['pickup_location'] ?? null,
            $data['discount'] ?? 0,
            $data['driver_name'] ?? null,
            $data['driver_phone'] ?? null,
            $data['vehicle_plate'] ?? null,
            $data['notes'] ?? null,
            $id,
        ]);
    }

    public static function getMonthlyPricesByContractId(PDO $pdo, string $contractId): array
    {
        $stmt = $pdo->prepare(
            'SELECT id, contract_id, month, price, notes FROM contract_monthly_prices WHERE contract_id = ? AND deleted_at IS NULL ORDER BY month ASC'
        );
        $stmt->execute([$contractId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findByCustomerId(PDO $pdo, string $customerId, ?string $companyId = null): array
    {
        $sql = 'SELECT c.*, r.room_number, w.name AS warehouse_name
                FROM contracts c
                INNER JOIN rooms r ON r.id = c.room_id AND r.deleted_at IS NULL
                INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
                WHERE c.customer_id = ? AND c.deleted_at IS NULL ';
        $params = [$customerId];
        if ($companyId) {
            $sql .= ' AND w.company_id = ? ';
            $params[] = $companyId;
        }
        $sql .= ' ORDER BY c.start_date DESC ';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private static function generateContractNumber(PDO $pdo): string
    {
        $y = date('Y');
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM contracts WHERE contract_number LIKE ? AND deleted_at IS NULL");
        $stmt->execute(["SOZ-{$y}-%"]);
        $n = (int) $stmt->fetchColumn() + 1;
        return sprintf('SOZ-%s-%04d', $y, $n);
    }

    private static function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    public static function countActiveByCompany(PDO $pdo, string $companyId): int
    {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM contracts c
             INNER JOIN rooms r ON r.id = c.room_id AND r.deleted_at IS NULL
             INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
             WHERE w.company_id = ? AND c.deleted_at IS NULL AND c.is_active = 1'
        );
        $stmt->execute([$companyId]);
        return (int) $stmt->fetchColumn();
    }

    /** Aktif sözleşmelerden bitiş tarihi bugünden itibaren N gün içinde olanlar */
    public static function findExpiringSoon(PDO $pdo, ?string $companyId, int $days = 30): array
    {
        $sql = 'SELECT c.*, cu.first_name AS customer_first_name, cu.last_name AS customer_last_name,
                       r.room_number, w.name AS warehouse_name
                FROM contracts c
                INNER JOIN customers cu ON cu.id = c.customer_id AND cu.deleted_at IS NULL
                INNER JOIN rooms r ON r.id = c.room_id AND r.deleted_at IS NULL
                INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
                WHERE c.deleted_at IS NULL AND c.is_active = 1
                AND DATE(c.end_date) >= CURDATE() AND DATE(c.end_date) <= DATE_ADD(CURDATE(), INTERVAL ? DAY) ';
        $params = [$days];
        if ($companyId) {
            $sql .= ' AND w.company_id = ? ';
            $params[] = $companyId;
        }
        $sql .= ' ORDER BY c.end_date ASC ';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function setActive(PDO $pdo, string $id, int $isActive): void
    {
        if ($isActive) {
            $stmt = $pdo->prepare('UPDATE contracts SET is_active = 1, terminated_at = NULL WHERE id = ? AND deleted_at IS NULL');
            $stmt->execute([$id]);
        } else {
            $stmt = $pdo->prepare('UPDATE contracts SET is_active = 0, terminated_at = NOW() WHERE id = ? AND deleted_at IS NULL');
            $stmt->execute([$id]);
        }
    }

    public static function softDelete(PDO $pdo, string $id): void
    {
        $stmt = $pdo->prepare('UPDATE contracts SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([$id]);
    }

    public static function countActiveGlobal(PDO $pdo): int
    {
        $stmt = $pdo->query(
            'SELECT COUNT(*) FROM contracts c
             INNER JOIN rooms r ON r.id = c.room_id AND r.deleted_at IS NULL
             INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
             WHERE c.deleted_at IS NULL AND c.is_active = 1'
        );
        return (int) $stmt->fetchColumn();
    }
}
