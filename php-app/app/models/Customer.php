<?php
class Customer
{
    public static function findAll(PDO $pdo, ?string $companyId = null, ?string $search = null, ?int $limit = null, int $offset = 0, ?string $inDepo = null, ?string $warehouseId = null, ?string $debtFilter = null, bool $duplicateOnly = false): array
    {
        $params = [];
        $debtFilter = self::normalizeDebtFilter($debtFilter);
        $withDebtStats = $debtFilter !== null;
        $sql = 'SELECT c.*';
        $sql .= ', ' . self::contractCountSubquery() . ' AS contract_count';
        $sql .= ', ' . self::primaryContractFieldSubquery('co.id') . ' AS primary_contract_id';
        $sql .= ', ' . self::primaryContractFieldSubquery('co.contract_number') . ' AS primary_contract_number';
        $sql .= ', ' . self::primaryContractFieldSubquery('w.name') . ' AS primary_warehouse_name';
        $sql .= ', ' . self::primaryContractFieldSubquery('r.room_number') . ' AS primary_room_number';
        $sql .= ', ' . self::primaryContractFieldSubquery('co.is_active') . ' AS primary_contract_active';
        if ($withDebtStats) {
            if ($debtFilter === 'overdue') {
                $sql .= ', ' . self::overduePaymentCountSubquery() . ' AS overdue_payment_count';
                $sql .= ', ' . self::overduePaymentSumSubquery() . ' AS overdue_debt_total';
            } else {
                $sql .= ', ' . self::unpaidPaymentCountSubquery() . ' AS unpaid_payment_count';
                $sql .= ', ' . self::unpaidPaymentSumSubquery() . ' AS unpaid_debt_total';
            }
        }
        $sql .= ' FROM customers c WHERE c.deleted_at IS NULL ';
        if ($companyId) {
            $sql .= ' AND c.company_id = ? ';
            $params[] = $companyId;
        }
        if ($search !== null && $search !== '') {
            appendTurkishLikeClause($sql, $params, [
                'c.first_name',
                'c.last_name',
                "CONCAT(c.first_name, ' ', c.last_name)",
                'c.email',
                'c.phone',
                'c.phone_2',
                'c.notes',
            ], $search);
        }
        $depoClause = self::depoFilterSql($companyId, $inDepo, $warehouseId, $params);
        if ($depoClause !== '') {
            $sql .= ' AND ' . $depoClause;
        }
        $debtClause = self::debtFilterSql($debtFilter, $params);
        if ($debtClause !== '') {
            $sql .= ' AND ' . $debtClause;
        }
        if ($duplicateOnly) {
            $sql .= ' AND ' . self::duplicateFilterSql();
        }
        if ($debtFilter === 'overdue') {
            $sql .= ' ORDER BY overdue_debt_total DESC, c.first_name, c.last_name ';
        } elseif ($debtFilter === 'unpaid') {
            $sql .= ' ORDER BY unpaid_debt_total DESC, c.first_name, c.last_name ';
        } else {
            $sql .= ' ORDER BY c.first_name, c.last_name ';
        }
        if ($limit !== null) {
            $sql .= ' LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        }
        if (count($params) > 0) {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        } else {
            $stmt = $pdo->query($sql);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function count(PDO $pdo, ?string $companyId = null, ?string $search = null, ?string $inDepo = null, ?string $warehouseId = null, ?string $debtFilter = null, bool $duplicateOnly = false): int
    {
        $sql = 'SELECT COUNT(*) FROM customers c WHERE c.deleted_at IS NULL ';
        $params = [];
        if ($companyId) {
            $sql .= ' AND c.company_id = ? ';
            $params[] = $companyId;
        }
        if ($search !== null && $search !== '') {
            appendTurkishLikeClause($sql, $params, [
                'c.first_name',
                'c.last_name',
                "CONCAT(c.first_name, ' ', c.last_name)",
                'c.email',
                'c.phone',
                'c.phone_2',
                'c.notes',
            ], $search);
        }
        $depoClause = self::depoFilterSql($companyId, $inDepo, $warehouseId, $params);
        if ($depoClause !== '') {
            $sql .= ' AND ' . $depoClause;
        }
        $debtClause = self::debtFilterSql(self::normalizeDebtFilter($debtFilter), $params);
        if ($debtClause !== '') {
            $sql .= ' AND ' . $debtClause;
        }
        if ($duplicateOnly) {
            $sql .= ' AND ' . self::duplicateFilterSql();
        }
        $stmt = count($params) > 0 ? $pdo->prepare($sql) : $pdo->query($sql);
        if (count($params) > 0) {
            $stmt->execute($params);
        }
        return (int) $stmt->fetchColumn();
    }

    /** borc GET: overdue = vadesi geçmiş, unpaid = ödenmemiş (tüm bekleyen) */
    private static function normalizeDebtFilter(?string $debtFilter): ?string
    {
        if ($debtFilter === null || $debtFilter === '') {
            return null;
        }
        return in_array($debtFilter, ['overdue', 'unpaid'], true) ? $debtFilter : null;
    }

    private static function paymentDebtFromSql(): string
    {
        return 'FROM payments p
            INNER JOIN contracts co ON co.id = p.contract_id AND co.deleted_at IS NULL
            INNER JOIN rooms r ON r.id = co.room_id AND r.deleted_at IS NULL
            INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
            WHERE co.customer_id = c.id
            AND p.deleted_at IS NULL
            AND p.status IN (\'pending\', \'overdue\')';
    }

    private static function contractJoinFromSql(): string
    {
        return 'FROM contracts co
            INNER JOIN rooms r ON r.id = co.room_id AND r.deleted_at IS NULL
            INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
            WHERE co.customer_id = c.id AND co.deleted_at IS NULL';
    }

    private static function contractCountSubquery(): string
    {
        return '(SELECT COUNT(*) ' . self::contractJoinFromSql() . ')';
    }

    private static function primaryContractFieldSubquery(string $field): string
    {
        return '(SELECT ' . $field . ' ' . self::contractJoinFromSql() . '
            ORDER BY co.is_active DESC, co.start_date DESC LIMIT 1)';
    }

    private static function overduePaymentCountSubquery(): string
    {
        return '(SELECT COUNT(*) ' . self::paymentDebtFromSql() . '
            AND p.due_date IS NOT NULL AND DATE(p.due_date) < CURDATE())';
    }

    private static function overduePaymentSumSubquery(): string
    {
        return '(SELECT COALESCE(SUM(p.amount), 0) ' . self::paymentDebtFromSql() . '
            AND p.due_date IS NOT NULL AND DATE(p.due_date) < CURDATE())';
    }

    private static function unpaidPaymentCountSubquery(): string
    {
        return '(SELECT COUNT(*) ' . self::paymentDebtFromSql() . ')';
    }

    private static function unpaidPaymentSumSubquery(): string
    {
        return '(SELECT COALESCE(SUM(p.amount), 0) ' . self::paymentDebtFromSql() . ')';
    }

    private static function debtFilterSql(?string $debtFilter, array &$params): string
    {
        if ($debtFilter === null) {
            return '';
        }
        if ($debtFilter === 'overdue') {
            return 'EXISTS (SELECT 1 ' . self::paymentDebtFromSql() . '
                AND p.due_date IS NOT NULL AND DATE(p.due_date) < CURDATE())';
        }
        if ($debtFilter === 'unpaid') {
            return 'EXISTS (SELECT 1 ' . self::paymentDebtFromSql() . ')';
        }
        return '';
    }

    /** Depoda olan / olmayan veya belirli depo filtresi için SQL koşulu (EXISTS / NOT EXISTS). $params by ref. */
    private static function depoFilterSql(?string $companyId, ?string $inDepo, ?string $warehouseId, array &$params): string
    {
        if (($inDepo === null || $inDepo === '') && ($warehouseId === null || $warehouseId === '')) {
            return '';
        }
        $sub = 'SELECT 1 FROM contracts co INNER JOIN rooms r ON r.id = co.room_id AND r.deleted_at IS NULL INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL WHERE co.customer_id = c.id AND co.deleted_at IS NULL';
        if ($companyId !== null && $companyId !== '') {
            $sub .= ' AND w.company_id = ?';
            $params[] = $companyId;
        }
        if ($warehouseId !== null && $warehouseId !== '') {
            $sub .= ' AND w.id = ?';
            $params[] = $warehouseId;
        }
        if ($inDepo === 'no') {
            return 'NOT EXISTS (' . $sub . ')';
        }
        return 'EXISTS (' . $sub . ')';
    }

    /** Aynı ad-soyada birden fazla kayıt olan müşteriler (tekrarlayan). */
    private static function duplicateFilterSql(): string
    {
        return '(SELECT COUNT(*) FROM customers c2 WHERE c2.deleted_at IS NULL
            AND TRIM(c2.first_name) = TRIM(c.first_name)
            AND TRIM(c2.last_name) = TRIM(c.last_name)
            AND (c.company_id <=> c2.company_id)) > 1';
    }

    /** Aynı ad-soyada sahip (tekrarlanan) müşteri adlarını döndürür – şirket veya tümü. */
    public static function getDuplicateFullNames(PDO $pdo, ?string $companyId = null): array
    {
        $sql = 'SELECT TRIM(first_name) AS fn, TRIM(last_name) AS ln FROM customers WHERE deleted_at IS NULL ';
        $params = [];
        if ($companyId !== null && $companyId !== '') {
            $sql .= ' AND company_id = ? ';
            $params[] = $companyId;
        }
        $sql .= ' GROUP BY TRIM(first_name), TRIM(last_name) HAVING COUNT(*) > 1';
        $stmt = count($params) > 0 ? $pdo->prepare($sql) : $pdo->query($sql);
        if (count($params) > 0) {
            $stmt->execute($params);
        }
        $out = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $out[] = trim(($row['fn'] ?? '') . ' ' . ($row['ln'] ?? ''));
        }
        return $out;
    }

    public static function findOne(PDO $pdo, string $id): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM customers WHERE id = ? AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findOneForCompany(PDO $pdo, string $id, string $companyId): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM customers WHERE id = ? AND company_id = ? AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([$id, $companyId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Aynı şirkette aynı e-posta ile kayıtlı müşteri var mı? (excludeId = güncellemede kendi kaydı) */
    public static function findByEmail(PDO $pdo, string $companyId, string $email, ?string $excludeId = null): ?array
    {
        $email = mb_strtolower(trim($email));
        if ($email === '') {
            return null;
        }
        $sql = 'SELECT * FROM customers WHERE company_id = ? AND LOWER(email) = ? AND deleted_at IS NULL';
        $params = [$companyId, $email];
        if ($excludeId !== null && $excludeId !== '') {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }
        $sql .= ' LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Aynı şirkette aynı telefon ile kayıtlı müşteri var mı? (excludeId = güncellemede kendi kaydı) */
    public static function findByPhone(PDO $pdo, string $companyId, ?string $phone, ?string $excludeId = null): ?array
    {
        if ($phone === null || $phone === '') {
            return null;
        }
        $sql = 'SELECT * FROM customers WHERE company_id = ? AND phone = ? AND deleted_at IS NULL';
        $params = [$companyId, $phone];
        if ($excludeId !== null && $excludeId !== '') {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }
        $sql .= ' LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Aynı şirkette bu numara başka bir müşteride phone veya phone_2 olarak kayıtlı mı? (excludeId = güncellemede kendi kaydı) */
    public static function findByPhoneOrPhone2(PDO $pdo, string $companyId, ?string $phone, ?string $excludeId = null): ?array
    {
        if ($phone === null || $phone === '') {
            return null;
        }
        $sql = 'SELECT * FROM customers WHERE company_id = ? AND (phone = ? OR phone_2 = ?) AND deleted_at IS NULL';
        $params = [$companyId, $phone, $phone];
        if ($excludeId !== null && $excludeId !== '') {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }
        $sql .= ' LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Excel/dış sistem eşleşmesi: aynı şirkette external_id ile müşteri (boş değilse) */
    public static function findByExternalId(PDO $pdo, string $companyId, string $externalId): ?array
    {
        $externalId = trim($externalId);
        if ($externalId === '') {
            return null;
        }
        $stmt = $pdo->prepare('SELECT * FROM customers WHERE company_id = ? AND external_id = ? AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([$companyId, $externalId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Aynı şirkette TC kimlik no ile müşteri */
    public static function findByIdentityNumber(PDO $pdo, string $companyId, ?string $identityNumber, ?string $excludeId = null): ?array
    {
        $identityNumber = trim((string) $identityNumber);
        if ($identityNumber === '') {
            return null;
        }
        $sql = 'SELECT * FROM customers WHERE company_id = ? AND identity_number = ? AND deleted_at IS NULL';
        $params = [$companyId, $identityNumber];
        if ($excludeId !== null && $excludeId !== '') {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }
        $sql .= ' LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Yeni müşteri kaydı öncesi aynı kişi zaten var mı (telefon, e-posta, TC veya ad-soyad).
     * Çift tıklama / eşzamanlı isteklerde tekrar kayıt oluşturmayı engellemek için kullanılır.
     */
    public static function findExistingForCreate(
        PDO $pdo,
        string $companyId,
        string $firstName,
        string $lastName,
        ?string $phone = null,
        ?string $email = null,
        ?string $identityNumber = null
    ): ?array {
        if ($phone !== null && $phone !== '') {
            $existing = self::findByPhoneOrPhone2($pdo, $companyId, $phone, null);
            if ($existing) {
                return $existing;
            }
        }
        $email = trim((string) $email);
        if ($email !== '') {
            $existing = self::findByEmail($pdo, $companyId, $email, null);
            if ($existing) {
                return $existing;
            }
        }
        if ($identityNumber !== null && trim($identityNumber) !== '') {
            $existing = self::findByIdentityNumber($pdo, $companyId, $identityNumber, null);
            if ($existing) {
                return $existing;
            }
        }
        $firstName = trim($firstName);
        $lastName = trim($lastName);
        if ($firstName === '' || $lastName === '') {
            return null;
        }
        // Telefon/e-posta/TC yoksa yalnızca çok kısa sürede tekrarlanan istekleri birleştir (çift tıklama).
        $stmt = $pdo->prepare(
            'SELECT * FROM customers
             WHERE company_id = ? AND deleted_at IS NULL
             AND LOWER(TRIM(first_name)) = LOWER(?)
             AND LOWER(TRIM(last_name)) = LOWER(?)
             AND created_at >= DATE_SUB(NOW(), INTERVAL 2 MINUTE)
             ORDER BY created_at DESC
             LIMIT 1'
        );
        $stmt->execute([$companyId, $firstName, $lastName]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Telefon eşleşmesi (biçim farklarını normalize ederek) */
    public static function findByPhoneNormalized(PDO $pdo, string $companyId, ?string $phone, ?string $excludeId = null): ?array
    {
        $digits = normalizePhoneDigits($phone);
        if ($digits === null) {
            return null;
        }
        $existing = self::findByPhoneOrPhone2($pdo, $companyId, $digits, $excludeId);
        if ($existing) {
            return $existing;
        }
        $stmt = $pdo->prepare(
            'SELECT * FROM customers WHERE company_id = ? AND deleted_at IS NULL
             AND (phone IS NOT NULL OR phone_2 IS NOT NULL)'
        );
        $stmt->execute([$companyId]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($excludeId !== null && $excludeId !== '' && ($row['id'] ?? '') === $excludeId) {
                continue;
            }
            foreach (['phone', 'phone_2'] as $field) {
                if (!empty($row[$field]) && normalizePhoneDigits($row[$field]) === $digits) {
                    return $row;
                }
            }
        }
        return null;
    }

    public static function update(PDO $pdo, string $id, array $data): void
    {
        $allowed = ['first_name', 'last_name', 'email', 'phone', 'phone_2', 'identity_number', 'address', 'notes', 'invoice_info', 'is_active', 'external_id'];
        $updates = [];
        $params = [];
        foreach ($allowed as $key) {
            if (array_key_exists($key, $data)) {
                $updates[] = "`$key` = ?";
                $params[] = $key === 'is_active' ? (int) $data[$key] : $data[$key];
            }
        }
        if (empty($updates)) {
            return;
        }
        $params[] = $id;
        $stmt = $pdo->prepare('UPDATE customers SET ' . implode(', ', $updates) . ' WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute($params);
    }

    public static function create(PDO $pdo, array $data): array
    {
        $id = self::uuid();
        $paramsNew = [
            $id,
            $data['company_id'],
            trim($data['first_name'] ?? ''),
            trim($data['last_name'] ?? ''),
            trim($data['email'] ?? '') !== '' ? trim($data['email']) : '',
            trim($data['phone'] ?? '') ?: null,
            trim($data['phone_2'] ?? '') ?: null,
            trim($data['identity_number'] ?? '') ?: null,
            trim($data['address'] ?? '') ?: null,
            trim($data['notes'] ?? '') ?: null,
            trim($data['invoice_info'] ?? '') ?: null,
            isset($data['is_active']) ? (int) $data['is_active'] : 1,
            trim($data['external_id'] ?? '') ?: null,
        ];
        $paramsOld = [
            $id,
            $data['company_id'],
            trim($data['first_name'] ?? ''),
            trim($data['last_name'] ?? ''),
            trim($data['email'] ?? '') !== '' ? trim($data['email']) : '',
            trim($data['phone'] ?? '') ?: null,
            trim($data['identity_number'] ?? '') ?: null,
            trim($data['address'] ?? '') ?: null,
            trim($data['notes'] ?? '') ?: null,
            isset($data['is_active']) ? (int) $data['is_active'] : 1,
        ];
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO customers (id, company_id, first_name, last_name, email, phone, phone_2, identity_number, address, notes, invoice_info, is_active, external_id) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute($paramsNew);
        } catch (PDOException $e) {
            $existing = self::findOne($pdo, $id);
            if ($existing) {
                return $existing;
            }
            if (strpos($e->getMessage(), 'phone_2') === false && strpos($e->getMessage(), 'external_id') === false) {
                throw $e;
            }
            $stmt = $pdo->prepare(
                'INSERT INTO customers (id, company_id, first_name, last_name, email, phone, identity_number, address, notes, is_active) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute($paramsOld);
        }
        return self::findOne($pdo, $id);
    }

    /** Soft delete: deleted_at set eder (eski kayıtlar; yeni silmeler hardDelete kullanır) */
    public static function softDelete(PDO $pdo, string $id): bool
    {
        $stmt = $pdo->prepare('UPDATE customers SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    /** Aktif depo sözleşmesi var mı */
    public static function hasActiveContract(PDO $pdo, string $customerId): bool
    {
        $stmt = $pdo->prepare(
            'SELECT 1 FROM contracts c
             INNER JOIN customers cu ON cu.id = c.customer_id AND cu.deleted_at IS NULL
             WHERE c.customer_id = ? AND c.deleted_at IS NULL AND c.is_active = 1 LIMIT 1'
        );
        $stmt->execute([$customerId]);
        return (bool) $stmt->fetch();
    }

    /** Müşteriyi veritabanından tamamen siler (aktif sözleşme yoksa). İlişkili kayıtlar CASCADE ile silinir. */
    public static function hardDelete(PDO $pdo, string $id): bool
    {
        if (self::hasActiveContract($pdo, $id)) {
            return false;
        }
        $stmt = $pdo->prepare('DELETE FROM customers WHERE id = ?');
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
