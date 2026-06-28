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
    private static ?bool $monthlyPricesMonthColumnReadyCache = null;

    /** Eski kurulumlarda month VARCHAR(7) (Y-m); Y-m-d anahtarları için genişlet */
    public static function ensureMonthlyPricesMonthColumn(PDO $pdo): void
    {
        if (self::$monthlyPricesMonthColumnReadyCache === true) {
            return;
        }
        try {
            $stmt = $pdo->query(
                "SELECT CHARACTER_MAXIMUM_LENGTH FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contract_monthly_prices' AND COLUMN_NAME = 'month'
                 LIMIT 1"
            );
            $maxLen = (int) $stmt->fetchColumn();
            if ($maxLen > 0 && $maxLen < 10) {
                $pdo->exec('ALTER TABLE `contract_monthly_prices` MODIFY COLUMN `month` VARCHAR(10) NOT NULL');
            }
        } catch (Throwable $e) {
            // Tablo yoksa veya yetki yoksa syncMonthlyPrices yine de denenecek
        }
        self::$monthlyPricesMonthColumnReadyCache = true;
    }

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
            'SELECT c.*, cu.first_name AS customer_first_name, cu.last_name AS customer_last_name, cu.id AS customer_id, cu.email AS customer_email, cu.phone AS customer_phone, cu.phone_2 AS customer_phone_2, cu.address AS customer_address,
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

    public static function updateRoomId(PDO $pdo, string $contractId, string $roomId): void
    {
        $stmt = $pdo->prepare('UPDATE contracts SET room_id = ? WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([$roomId, $contractId]);
    }

    public static function updateContractDates(PDO $pdo, string $id, string $startDate, string $endDate): void
    {
        $stmt = $pdo->prepare(
            'UPDATE contracts SET start_date = ?, end_date = ? WHERE id = ? AND deleted_at IS NULL'
        );
        $stmt->execute([$startDate, $endDate, $id]);
    }

    public static function update(PDO $pdo, string $id, array $data): void
    {
        $soldBy = $data['sold_by_user_id'] ?? null;
        if ($soldBy === '') {
            $soldBy = null;
        }
        if (self::hasStoredItemsColumns($pdo)) {
            $stmt = $pdo->prepare(
                'UPDATE contracts SET start_date = ?, end_date = ?, monthly_price = ?, transportation_fee = ?, pickup_location = ?, discount = ?, driver_name = ?, driver_phone = ?, vehicle_plate = ?, notes = ?, stored_items_condition = ?, stored_items_condition_note = ?, sold_by_user_id = ? WHERE id = ? AND deleted_at IS NULL'
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
                $soldBy,
                $id,
            ]);
            return;
        }
        $stmt = $pdo->prepare(
            'UPDATE contracts SET start_date = ?, end_date = ?, monthly_price = ?, transportation_fee = ?, pickup_location = ?, discount = ?, driver_name = ?, driver_phone = ?, vehicle_plate = ?, notes = ?, sold_by_user_id = ? WHERE id = ? AND deleted_at IS NULL'
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
            $soldBy,
            $id,
        ]);
    }

    /** @return list<string> */
    public static function getPersonnelIdsByContractId(PDO $pdo, string $contractId): array
    {
        if (!Personnel::tableExists($pdo)) {
            return [];
        }
        $stmt = $pdo->prepare(
            'SELECT personnel_id FROM contract_personnel WHERE contract_id = ? AND deleted_at IS NULL'
        );
        $stmt->execute([$contractId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /** @param list<string> $personnelIds */
    public static function syncPersonnel(PDO $pdo, string $contractId, array $personnelIds): void
    {
        if (!Personnel::tableExists($pdo)) {
            return;
        }
        $pdo->prepare('DELETE FROM contract_personnel WHERE contract_id = ?')->execute([$contractId]);
        $stmt = $pdo->prepare('INSERT INTO contract_personnel (id, contract_id, personnel_id) VALUES (?, ?, ?)');
        foreach ($personnelIds as $pid) {
            $pid = trim((string) $pid);
            if ($pid === '') {
                continue;
            }
            $stmt->execute([self::uuid(), $contractId, $pid]);
        }
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
    /**
     * Aynı takvim ayında yanlış vade günü (ör. ayın 1'i) ile giriş yıl dönümü çakışan ödemeleri birleştirir.
     * Ödenmiş kayıt doğru vade gününe taşınır; aynı ay için yinelenen bekleyen kayıtlar silinir.
     */
    public static function reconcilePaymentDueDates(PDO $pdo, string $contractId, ?string $startDate, ?string $endDate): void
    {
        $start = ContractBilling::normalizeDate($startDate);
        $end = ContractBilling::normalizeDate($endDate);
        if ($start === '' || $end === '') {
            return;
        }

        $periodsByYm = [];
        foreach (ContractBilling::periods($start, $end) as $period) {
            $periodsByYm[substr($period['key'], 0, 7)] = $period;
        }
        if ($periodsByYm === []) {
            return;
        }

        $stmt = $pdo->prepare(
            'SELECT id, status, due_date FROM payments
             WHERE contract_id = ? AND deleted_at IS NULL AND due_date IS NOT NULL'
        );
        $stmt->execute([$contractId]);
        $byYm = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $dueKey = ContractBilling::periodKeyFromDueDate($row['due_date'] ?? null);
            if (strlen($dueKey) < 7) {
                continue;
            }
            $ym = substr($dueKey, 0, 7);
            if (!isset($periodsByYm[$ym])) {
                continue;
            }
            $byYm[$ym][] = $row;
        }

        $stmtMoveDue = $pdo->prepare('UPDATE payments SET due_date = ? WHERE id = ? AND deleted_at IS NULL');
        $stmtSoftDel = $pdo->prepare(
            'UPDATE payments SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL AND status IN (\'pending\', \'overdue\')'
        );

        foreach ($periodsByYm as $ym => $period) {
            if (empty($byYm[$ym])) {
                continue;
            }
            $correctKey = $period['key'];
            $correctDue = $period['due_date'];
            $paid = [];
            $unpaid = [];
            foreach ($byYm[$ym] as $row) {
                if (($row['status'] ?? '') === 'paid') {
                    $paid[] = $row;
                } elseif (in_array($row['status'] ?? '', ['pending', 'overdue'], true)) {
                    $unpaid[] = $row;
                }
            }

            if ($paid !== []) {
                $keeper = $paid[0];
                if (ContractBilling::periodKeyFromDueDate($keeper['due_date'] ?? null) !== $correctKey) {
                    $stmtMoveDue->execute([$correctDue, $keeper['id']]);
                }
                foreach (array_slice($paid, 1) as $extraPaid) {
                    if (ContractBilling::periodKeyFromDueDate($extraPaid['due_date'] ?? null) !== $correctKey) {
                        $stmtMoveDue->execute([$correctDue, $extraPaid['id']]);
                    }
                }
                foreach ($unpaid as $row) {
                    $stmtSoftDel->execute([$row['id']]);
                }
                continue;
            }

            if ($unpaid === []) {
                continue;
            }
            $keeper = $unpaid[0];
            if (ContractBilling::periodKeyFromDueDate($keeper['due_date'] ?? null) !== $correctKey) {
                $stmtMoveDue->execute([$correctDue, $keeper['id']]);
            }
            foreach (array_slice($unpaid, 1) as $row) {
                $stmtSoftDel->execute([$row['id']]);
            }
        }

        self::removePendingPaymentsInPaidMonths($pdo, $contractId);
        self::purgeOutOfRangePendingPayments($pdo, $contractId, $start, $end);
    }

    /** Sözleşme dönemi dışındaki bekleyen/gecikmiş ödemeleri kaldırır */
    public static function purgeOutOfRangePendingPayments(PDO $pdo, string $contractId, ?string $startDate, ?string $endDate): void
    {
        $start = ContractBilling::normalizeDate($startDate);
        $end = ContractBilling::normalizeDate($endDate);
        if ($start === '' || $end === '') {
            return;
        }

        $validKeys = [];
        foreach (ContractBilling::periods($start, $end) as $period) {
            $validKeys[] = $period['key'];
        }
        if ($validKeys === []) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($validKeys), '?'));
        $stmt = $pdo->prepare(
            "UPDATE payments SET deleted_at = NOW()
             WHERE contract_id = ? AND deleted_at IS NULL AND status IN ('pending', 'overdue')
             AND due_date IS NOT NULL AND DATE(due_date) NOT IN ($placeholders)"
        );
        $stmt->execute(array_merge([$contractId], $validKeys));
    }

    /**
     * @param array<string, array{price: float, due_date: string}> $validPeriods
     * @param list<string> $paidPeriodKeys
     */
    public static function ensurePendingPaymentsForValidPeriods(
        PDO $pdo,
        string $contractId,
        array $validPeriods,
        array $paidPeriodKeys
    ): void {
        $stmtExists = $pdo->prepare(
            "SELECT id FROM payments
             WHERE contract_id = ? AND deleted_at IS NULL
             AND status IN ('paid', 'pending', 'overdue') AND DATE(due_date) = ?
             LIMIT 1"
        );

        foreach ($validPeriods as $periodKey => $info) {
            if (ContractBilling::isPaidPeriodKey($periodKey, $paidPeriodKeys)) {
                continue;
            }
            $stmtExists->execute([$contractId, $periodKey]);
            $exists = $stmtExists->fetchColumn();
            $stmtExists->closeCursor();
            if ($exists) {
                continue;
            }
            Payment::create($pdo, [
                'contract_id' => $contractId,
                'amount' => $info['price'],
                'status' => 'pending',
                'due_date' => $info['due_date'],
            ]);
        }
    }

    /** @param array<string, array{price: float, due_date: string}> $validPeriods */
    private static function periodMonthKeyInValidPeriods(string $monthKey, array $validPeriods): bool
    {
        if (isset($validPeriods[$monthKey])) {
            return true;
        }
        if (preg_match('/^\d{4}-\d{2}$/', $monthKey)) {
            foreach ($validPeriods as $periodKey => $_) {
                if (substr($periodKey, 0, 7) === $monthKey) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Sözleşme dönemlerine göre ödeme satırlarını oluşturur / hizalar.
     *
     * @param array<string, array{price: float, due_date: string}> $validPeriods
     * @param list<string> $paidPeriodKeys
     */
    private static function syncPaymentsForPeriods(
        PDO $pdo,
        string $contractId,
        array $validPeriods,
        array $paidPeriodKeys,
        ?string $startDate,
        ?string $endDate
    ): void {
        self::purgeOutOfRangePendingPayments($pdo, $contractId, $startDate, $endDate);

        $stmtFindExact = $pdo->prepare(
            "SELECT id, status FROM payments
             WHERE contract_id = ? AND deleted_at IS NULL AND DATE(due_date) = ?
             LIMIT 1"
        );
        $stmtFindYmPending = $pdo->prepare(
            "SELECT id, DATE(due_date) AS period_key FROM payments
             WHERE contract_id = ? AND deleted_at IS NULL AND status IN ('pending', 'overdue')
             AND DATE_FORMAT(due_date, '%Y-%m') = ?
             ORDER BY due_date ASC LIMIT 1"
        );
        $stmtUpdateAmount = $pdo->prepare(
            "UPDATE payments SET amount = ? WHERE id = ? AND deleted_at IS NULL AND status IN ('pending', 'overdue')"
        );
        $stmtUpdateDueAmount = $pdo->prepare(
            "UPDATE payments SET amount = ?, due_date = ? WHERE id = ? AND deleted_at IS NULL AND status IN ('pending', 'overdue')"
        );
        $stmtDelPendingPaidMonth = $pdo->prepare(
            'UPDATE payments SET deleted_at = NOW()
             WHERE contract_id = ? AND deleted_at IS NULL AND status IN (\'pending\', \'overdue\')
             AND DATE_FORMAT(due_date, \'%Y-%m\') = ?'
        );
        $stmtCleanDup = $pdo->prepare(
            'UPDATE payments SET deleted_at = NOW()
             WHERE contract_id = ? AND deleted_at IS NULL AND status IN (\'pending\', \'overdue\')
             AND DATE(due_date) != ? AND DATE_FORMAT(due_date, \'%Y-%m\') = ?'
        );

        foreach ($validPeriods as $periodKey => $info) {
            if (ContractBilling::isPaidPeriodKey($periodKey, $paidPeriodKeys)) {
                $ym = substr($periodKey, 0, 7);
                if ($ym !== '') {
                    $stmtDelPendingPaidMonth->execute([$contractId, $ym]);
                }
                continue;
            }

            $stmtFindExact->execute([$contractId, $periodKey]);
            $exact = $stmtFindExact->fetch(PDO::FETCH_ASSOC);
            $stmtFindExact->closeCursor();
            if ($exact) {
                if (in_array($exact['status'] ?? '', ['pending', 'overdue'], true)) {
                    $stmtUpdateAmount->execute([$info['price'], $exact['id']]);
                }
                continue;
            }

            $ym = substr($periodKey, 0, 7);
            $stmtFindYmPending->execute([$contractId, $ym]);
            $ymRow = $stmtFindYmPending->fetch(PDO::FETCH_ASSOC);
            $stmtFindYmPending->closeCursor();
            if ($ymRow && ($ymRow['period_key'] ?? '') !== $periodKey) {
                $stmtUpdateDueAmount->execute([$info['price'], $info['due_date'], $ymRow['id']]);
                continue;
            }

            Payment::create($pdo, [
                'contract_id' => $contractId,
                'amount' => $info['price'],
                'status' => 'pending',
                'due_date' => $info['due_date'],
            ]);
        }

        foreach ($validPeriods as $periodKey => $_) {
            if (strlen($periodKey) < 7) {
                continue;
            }
            $ym = substr($periodKey, 0, 7);
            $stmtCleanDup->execute([$contractId, $periodKey, $ym]);
        }

        self::purgeOutOfRangePendingPayments($pdo, $contractId, $startDate, $endDate);
    }

    /** Ödemesi alınmış aylardaki yinelenen bekleyen/gecikmiş taksitleri kaldırır */
    public static function removePendingPaymentsInPaidMonths(PDO $pdo, string $contractId): void
    {
        $stmt = $pdo->prepare(
            "UPDATE payments p
             INNER JOIN (
                 SELECT DISTINCT DATE_FORMAT(due_date, '%Y-%m') AS ym
                 FROM payments
                 WHERE contract_id = ? AND deleted_at IS NULL AND status = 'paid' AND due_date IS NOT NULL
             ) paid_months ON DATE_FORMAT(p.due_date, '%Y-%m') = paid_months.ym
             SET p.deleted_at = NOW()
             WHERE p.contract_id = ? AND p.deleted_at IS NULL AND p.status IN ('pending', 'overdue')"
        );
        $stmt->execute([$contractId, $contractId]);
    }

    public static function normalizeContractPayments(PDO $pdo, string $contractId): void
    {
        $contract = self::findOne($pdo, $contractId);
        if (!$contract || !empty($contract['terminated_at'])) {
            return;
        }
        self::reconcilePaymentDueDates(
            $pdo,
            $contractId,
            $contract['start_date'] ?? null,
            $contract['end_date'] ?? null
        );
    }

    public static function syncMonthlyPrices(PDO $pdo, string $contractId, ?string $startDate, ?string $endDate, float $defaultPrice, array $monthlyPricesPost): void
    {
        if ($startDate === null || $startDate === '' || $endDate === null || $endDate === '') {
            return;
        }

        // ALTER TABLE transaction dışında olmalı (MySQL implicit commit yapar)
        self::ensureMonthlyPricesMonthColumn($pdo);

        $ownTransaction = !$pdo->inTransaction();
        if ($ownTransaction) {
            $pdo->beginTransaction();
        }
        try {
            self::syncMonthlyPricesInner($pdo, $contractId, $startDate, $endDate, $defaultPrice, $monthlyPricesPost);
            if ($ownTransaction && $pdo->inTransaction()) {
                $pdo->commit();
            }
        } catch (Throwable $e) {
            if ($ownTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    private static function syncMonthlyPricesInner(PDO $pdo, string $contractId, ?string $startDate, ?string $endDate, float $defaultPrice, array $monthlyPricesPost): void
    {
        self::reconcilePaymentDueDates($pdo, $contractId, $startDate, $endDate);

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
        $stmtMigrateMonth = $pdo->prepare('UPDATE contract_monthly_prices SET month = ?, deleted_at = NULL WHERE id = ?');

        foreach ($validPeriods as $periodKey => $info) {
            $isPaid = ContractBilling::isPaidPeriodKey($periodKey, $paidPeriodKeys);
            $existingId = null;
            if (isset($existingByMonth[$periodKey])) {
                $existingId = $existingByMonth[$periodKey]['id'];
            } else {
                $legacyYm = substr($periodKey, 0, 7);
                if (isset($existingByMonth[$legacyYm])) {
                    $existingId = $existingByMonth[$legacyYm]['id'];
                    $stmtMigrateMonth->execute([$periodKey, $existingId]);
                }
            }
            if ($existingId) {
                if (!$isPaid) {
                    $stmtUpdate->execute([$info['price'], $existingId]);
                }
            } else {
                $stmtInsert->execute([self::uuid(), $contractId, $periodKey, $info['price']]);
            }
        }

        foreach ($existingByMonth as $monthKey => $row) {
            if (self::periodMonthKeyInValidPeriods($monthKey, $validPeriods)) {
                continue;
            }
            if (ContractBilling::isPaidPeriodKey($monthKey, $paidPeriodKeys)) {
                continue;
            }
            $pdo->prepare('UPDATE contract_monthly_prices SET deleted_at = NOW() WHERE id = ?')->execute([$row['id']]);
        }

        self::syncPaymentsForPeriods($pdo, $contractId, $validPeriods, $paidPeriodKeys, $startDate, $endDate);
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
        self::ensureMonthlyPricesMonthColumn($pdo);
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

    private static ?bool $hasDeletionAuditColumnsCache = null;

    public static function hasDeletionAuditColumns(PDO $pdo): bool
    {
        if (self::$hasDeletionAuditColumnsCache !== null) {
            return self::$hasDeletionAuditColumnsCache;
        }
        try {
            $pdo->query('SELECT deletion_reason, deleted_by_user_id FROM contracts LIMIT 0');
            self::$hasDeletionAuditColumnsCache = true;
        } catch (Throwable $e) {
            self::$hasDeletionAuditColumnsCache = false;
        }
        return self::$hasDeletionAuditColumnsCache;
    }

    /**
     * Sözleşmeyi gerekçe ile arşivler (soft delete); ödeme kayıtlarını ve oda durumunu günceller.
     */
    public static function deleteWithReason(PDO $pdo, string $id, string $reason, ?string $deletedByUserId): bool
    {
        $contract = self::findOne($pdo, $id);
        if (!$contract) {
            return false;
        }
        $roomId = $contract['room_id'] ?? null;

        if (self::hasDeletionAuditColumns($pdo)) {
            $stmt = $pdo->prepare(
                'UPDATE contracts SET deleted_at = NOW(), is_active = 0, terminated_at = COALESCE(terminated_at, NOW()), deletion_reason = ?, deleted_by_user_id = ? WHERE id = ? AND deleted_at IS NULL'
            );
            $stmt->execute([$reason, $deletedByUserId, $id]);
        } else {
            $stmt = $pdo->prepare(
                'UPDATE contracts SET deleted_at = NOW(), is_active = 0, terminated_at = COALESCE(terminated_at, NOW()) WHERE id = ? AND deleted_at IS NULL'
            );
            $stmt->execute([$id]);
        }
        if ($stmt->rowCount() === 0) {
            return false;
        }

        $pdo->prepare('UPDATE payments SET deleted_at = NOW() WHERE contract_id = ? AND deleted_at IS NULL')->execute([$id]);
        $pdo->prepare('UPDATE contract_monthly_prices SET deleted_at = NOW() WHERE contract_id = ? AND deleted_at IS NULL')->execute([$id]);
        Item::deleteByContractId($pdo, $id);

        if ($roomId && !Room::hasActiveContractExcept($pdo, $roomId, $id)) {
            Room::update($pdo, $roomId, ['status' => 'empty']);
        }

        return true;
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
