<?php
class Contract
{
    public static function findAll(PDO $pdo, ?string $companyId = null, ?string $statusFilter = null, ?string $debtFilter = null, ?int $limit = null, int $offset = 0, ?string $search = null): array
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
        self::appendSearchFilter($sql, $params, $search);
        $sql .= ' ORDER BY c.created_at DESC ';
        if ($limit !== null) {
            $sql .= ' LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function count(PDO $pdo, ?string $companyId = null, ?string $statusFilter = null, ?string $debtFilter = null, ?string $search = null): int
    {
        $sql = 'SELECT COUNT(*) FROM contracts c
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
        self::appendSearchFilter($sql, $params, $search);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    private static function appendSearchFilter(string &$sql, array &$params, ?string $search): void
    {
        appendTurkishLikeClause($sql, $params, [
            'c.contract_number',
            'cu.first_name',
            'cu.last_name',
            "CONCAT(cu.first_name, ' ', cu.last_name)",
            'cu.email',
            'cu.phone',
            'r.room_number',
            'w.name',
            "COALESCE(c.vehicle_plate, '')",
            "COALESCE(c.driver_name, '')",
        ], $search);
    }

    private static ?bool $hasStoredItemsColumnsCache = null;

    public static function hasStoredItemsColumns(PDO $pdo): bool
    {
        if (self::$hasStoredItemsColumnsCache !== null) {
            return self::$hasStoredItemsColumnsCache;
        }
        try {
            $pdo->query('SELECT stored_items_condition, stored_items_condition_note FROM contracts LIMIT 0');
            self::$hasStoredItemsColumnsCache = true;
        } catch (Throwable $e) {
            self::$hasStoredItemsColumnsCache = false;
        }
        return self::$hasStoredItemsColumnsCache;
    }

    public static function create(PDO $pdo, array $data): array
    {
        $id = self::uuid();
        $withStoredItems = self::hasStoredItemsColumns($pdo);
        if ($withStoredItems) {
            $stmt = $pdo->prepare(
                'INSERT INTO contracts (id, contract_number, customer_id, room_id, start_date, end_date, monthly_price, payment_frequency_months, is_active, sold_by_user_id, transportation_fee, pickup_location, discount, driver_name, driver_phone, vehicle_plate, contract_pdf_url, notes, stored_items_condition, stored_items_condition_note) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, 1, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO contracts (id, contract_number, customer_id, room_id, start_date, end_date, monthly_price, payment_frequency_months, is_active, sold_by_user_id, transportation_fee, pickup_location, discount, driver_name, driver_phone, vehicle_plate, contract_pdf_url, notes) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, 1, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
        }
        $maxAttempts = 5;
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $contractNumber = $data['contract_number'] ?? self::generateContractNumber($pdo);
            try {
                if ($withStoredItems) {
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
                        $data['stored_items_condition'] ?? null,
                        $data['stored_items_condition_note'] ?? null,
                    ]);
                } else {
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
                }
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
        if (self::hasStoredItemsColumns($pdo)) {
            $stmt = $pdo->prepare(
                'UPDATE contracts SET start_date = ?, end_date = ?, monthly_price = ?, transportation_fee = ?, pickup_location = ?, discount = ?, driver_name = ?, driver_phone = ?, vehicle_plate = ?, notes = ?, stored_items_condition = ?, stored_items_condition_note = ? WHERE id = ? AND deleted_at IS NULL'
            );
            $stmt->execute([
                $data['start_date'] ?? null,
                $data['end_date'] ?? null,
                $data['monthly_price'] ?? 0,
                $data['transportation_fee'] ?? 0,
                $data['pickup_location'] ?? null,
                $data['discount'] ?? 0,
                $data['driver_name'] ?? null,
                $data['driver_phone'] ?? null,
                $data['vehicle_plate'] ?? null,
                $data['notes'] ?? null,
                $data['stored_items_condition'] ?? null,
                $data['stored_items_condition_note'] ?? null,
                $id,
            ]);
            return;
        }
        $stmt = $pdo->prepare(
            'UPDATE contracts SET start_date = ?, end_date = ?, monthly_price = ?, transportation_fee = ?, pickup_location = ?, discount = ?, driver_name = ?, driver_phone = ?, vehicle_plate = ?, notes = ? WHERE id = ? AND deleted_at IS NULL'
        );
        $stmt->execute([
            $data['start_date'] ?? null,
            $data['end_date'] ?? null,
            $data['monthly_price'] ?? 0,
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

    public static function setContractPdfUrl(PDO $pdo, string $id, ?string $url): void
    {
        $contract = self::findOne($pdo, $id);
        if ($contract && !empty($contract['contract_pdf_url']) && $contract['contract_pdf_url'] !== $url) {
            unlinkPublicFile($contract['contract_pdf_url']);
        }
        $stmt = $pdo->prepare('UPDATE contracts SET contract_pdf_url = ? WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([$url, $id]);
    }

    /**
     * Sözleşme dönemindeki aylık fiyatları günceller; bekleyen ödeme tutarlarını eşitler.
     * Vadeler giriş tarihinin yıl dönümüne göre hesaplanır (ContractBilling).
     *
     * @param array<string, string|float> $monthlyPricesPost Anahtar: Y-m-d (ör. 2026-06-28)
     */
    public static function syncMonthlyPrices(PDO $pdo, string $contractId, ?string $startDate, ?string $endDate, float $defaultPrice, array $monthlyPricesPost): void
    {
        if ($startDate === null || $startDate === '' || $endDate === null || $endDate === '') {
            return;
        }

        $existing = self::getMonthlyPricesByContractId($pdo, $contractId);
        $existingByMonth = [];
        foreach ($existing as $row) {
            $existingByMonth[$row['month']] = $row;
        }
        $paidPeriodKeys = Payment::getPaidPeriodKeysByContractId($pdo, $contractId);
        $paidAmountsByPeriod = Payment::getPaidAmountsByPeriodForContract($pdo, $contractId);

        $validPeriods = [];
        foreach (ContractBilling::periods($startDate, $endDate) as $period) {
            $periodKey = $period['key'];
            if (ContractBilling::isPaidPeriodKey($periodKey, $paidPeriodKeys)) {
                if (isset($existingByMonth[$periodKey])) {
                    $validPeriods[$periodKey] = [
                        'price' => (float) $existingByMonth[$periodKey]['price'],
                        'due_date' => $period['due_date'],
                    ];
                } elseif (isset($paidAmountsByPeriod[$periodKey])) {
                    $validPeriods[$periodKey] = [
                        'price' => $paidAmountsByPeriod[$periodKey],
                        'due_date' => $period['due_date'],
                    ];
                } else {
                    $validPeriods[$periodKey] = [
                        'price' => $defaultPrice,
                        'due_date' => $period['due_date'],
                    ];
                }
            } else {
                $price = ContractBilling::resolvePriceForPeriod($periodKey, $defaultPrice, $monthlyPricesPost);
                if ($price <= 0 && isset($existingByMonth[$periodKey])) {
                    $price = (float) ($existingByMonth[$periodKey]['price'] ?? $defaultPrice);
                } elseif ($price <= 0) {
                    $price = ContractBilling::priceFromExistingRows($periodKey, $existingByMonth, $defaultPrice);
                }
                $validPeriods[$periodKey] = [
                    'price' => $price,
                    'due_date' => $period['due_date'],
                ];
            }
        }

        $stmtUpdate = $pdo->prepare('UPDATE contract_monthly_prices SET price = ?, deleted_at = NULL WHERE id = ?');
        $stmtInsert = $pdo->prepare('INSERT INTO contract_monthly_prices (id, contract_id, month, price) VALUES (?, ?, ?, ?)');

        foreach ($validPeriods as $periodKey => $info) {
            if (ContractBilling::isPaidPeriodKey($periodKey, $paidPeriodKeys)) {
                continue;
            }
            if (isset($existingByMonth[$periodKey])) {
                $stmtUpdate->execute([$info['price'], $existingByMonth[$periodKey]['id']]);
            } else {
                $stmtInsert->execute([self::uuid(), $contractId, $periodKey, $info['price']]);
            }
        }

        foreach ($existingByMonth as $monthKey => $row) {
            if (!isset($validPeriods[$monthKey])) {
                if (ContractBilling::isPaidPeriodKey($monthKey, $paidPeriodKeys)) {
                    continue;
                }
                $pdo->prepare('UPDATE contract_monthly_prices SET deleted_at = NOW() WHERE id = ?')->execute([$row['id']]);
            }
        }

        $stmtPayPeriods = $pdo->prepare(
            'SELECT id, status, DATE(due_date) AS period_key
             FROM payments WHERE contract_id = ? AND deleted_at IS NULL AND due_date IS NOT NULL'
        );
        $stmtPayPeriods->execute([$contractId]);
        $paymentsByPeriod = [];
        while ($row = $stmtPayPeriods->fetch(PDO::FETCH_ASSOC)) {
            $periodKey = $row['period_key'] ?? '';
            if ($periodKey !== '' && !isset($paymentsByPeriod[$periodKey])) {
                $paymentsByPeriod[$periodKey] = $row;
            }
        }

        $stmtPayUpdate = $pdo->prepare(
            'UPDATE payments SET amount = ? WHERE contract_id = ? AND deleted_at IS NULL AND status IN (\'pending\', \'overdue\') AND DATE(due_date) = ?'
        );
        $stmtPayDelete = $pdo->prepare(
            'UPDATE payments SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL AND status IN (\'pending\', \'overdue\')'
        );

        foreach ($validPeriods as $periodKey => $info) {
            if (ContractBilling::isPaidPeriodKey($periodKey, $paidPeriodKeys)) {
                continue;
            }
            if (!isset($paymentsByPeriod[$periodKey])) {
                Payment::create($pdo, [
                    'contract_id' => $contractId,
                    'amount' => $info['price'],
                    'status' => 'pending',
                    'due_date' => $info['due_date'],
                ]);
            } else {
                $stmtPayUpdate->execute([$info['price'], $contractId, $periodKey]);
            }
        }

        foreach ($paymentsByPeriod as $periodKey => $row) {
            if (isset($validPeriods[$periodKey]) || ($row['status'] ?? '') === 'paid') {
                continue;
            }
            $stmtPayDelete->execute([$row['id']]);
        }
    }

    /** Sözleşme dönemine göre eksik ödeme kayıtlarını oluşturur (bitiş uzatma sonrası vb.) */
    public static function ensurePaymentsForContract(PDO $pdo, string $contractId): void
    {
        $contract = self::findOne($pdo, $contractId);
        if (!$contract) {
            return;
        }
        $monthlyPricesPost = [];
        foreach (self::getMonthlyPricesByContractId($pdo, $contractId) as $mp) {
            if (!empty($mp['month'])) {
                $monthlyPricesPost[$mp['month']] = $mp['price'];
            }
        }
        self::syncMonthlyPrices(
            $pdo,
            $contractId,
            $contract['start_date'] ?? null,
            $contract['end_date'] ?? null,
            (float) ($contract['monthly_price'] ?? 0),
            $monthlyPricesPost
        );
    }

    public static function getMonthlyPricesByContractId(PDO $pdo, string $contractId): array
    {
        $stmt = $pdo->prepare(
            'SELECT id, contract_id, month, price, notes FROM contract_monthly_prices WHERE contract_id = ? AND deleted_at IS NULL ORDER BY month ASC'
        );
        $stmt->execute([$contractId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Tek ay için contract_monthly_prices kaydını güncelle veya oluştur */
    public static function setMonthlyPriceForMonth(PDO $pdo, string $contractId, string $monthYm, float $price): void
    {
        $stmt = $pdo->prepare(
            'SELECT id FROM contract_monthly_prices WHERE contract_id = ? AND month = ? AND deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([$contractId, $monthYm]);
        $existingId = $stmt->fetchColumn();
        if ($existingId) {
            $pdo->prepare('UPDATE contract_monthly_prices SET price = ? WHERE id = ?')->execute([$price, $existingId]);
            return;
        }
        $pdo->prepare('INSERT INTO contract_monthly_prices (id, contract_id, month, price) VALUES (?, ?, ?, ?)')
            ->execute([self::uuid(), $contractId, $monthYm, $price]);
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
        // Tüm kayıtlar (silinmiş dahil) dikkate alınmalı; UNIQUE tüm tabloda geçerli
        $stmt = $pdo->prepare("SELECT MAX(contract_number) FROM contracts WHERE contract_number LIKE ?");
        $stmt->execute(["SOZ-{$y}-%"]);
        $max = $stmt->fetchColumn();
        $n = 1;
        if ($max && preg_match('/^SOZ-\d{4}-(\d+)$/', $max, $m)) {
            $n = (int) $m[1] + 1;
        }
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

    public static function hardDelete(PDO $pdo, string $id): bool
    {
        $contract = self::findOne($pdo, $id);
        if ($contract && !empty($contract['contract_pdf_url'])) {
            unlinkPublicFile($contract['contract_pdf_url']);
        }
        Item::deleteByContractId($pdo, $id);
        $stmt = $pdo->prepare('DELETE FROM contracts WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public static function softDelete(PDO $pdo, string $id): void
    {
        self::hardDelete($pdo, $id);
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

    /** Bu dönemde en çok satış yapan kullanıcılar (sold_by_user_id) */
    public static function findTopSellers(PDO $pdo, ?string $companyId, string $startDate, string $endDate, int $limit = 5): array
    {
        $limit = max(1, min(20, $limit));
        $sql = 'SELECT u.id AS user_id, u.first_name, u.last_name, u.role,
                       COUNT(c.id) AS sale_count,
                       COALESCE(SUM(c.monthly_price + COALESCE(c.transportation_fee, 0) - COALESCE(c.discount, 0)), 0) AS sale_total
                FROM contracts c
                INNER JOIN users u ON u.id = c.sold_by_user_id AND u.deleted_at IS NULL
                INNER JOIN rooms r ON r.id = c.room_id AND r.deleted_at IS NULL
                INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
                WHERE c.deleted_at IS NULL AND c.sold_by_user_id IS NOT NULL
                AND DATE(c.created_at) >= ? AND DATE(c.created_at) <= ? ';
        $params = [$startDate, $endDate];
        if ($companyId) {
            $sql .= ' AND w.company_id = ? ';
            $params[] = $companyId;
        }
        $sql .= ' GROUP BY u.id, u.first_name, u.last_name, u.role
                  ORDER BY sale_count DESC, sale_total DESC
                  LIMIT ' . (int) $limit;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
