<?php
class Room
{
    /** Oda numarasına göre SQL sıralama (01, 02, … 10 sayısal sıra) */
    public static function sqlOrderByRoomNumber(string $alias = 'r'): string
    {
        $col = ($alias !== '' ? $alias . '.' : '') . 'room_number';
        return 'CAST(' . $col . ' AS UNSIGNED) ASC, LENGTH(' . $col . ') ASC, ' . $col . ' ASC';
    }

    /** Depodaki oda sayısı (silinmemiş depo ve oda kayıtları) */
    public static function countByWarehouseId(PDO $pdo, string $warehouseId, ?string $companyId = null): int
    {
        $sql = 'SELECT COUNT(*) FROM rooms r
                INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
                WHERE r.deleted_at IS NULL AND r.warehouse_id = ? ';
        $params = [$warehouseId];
        if ($companyId !== null && $companyId !== '') {
            $sql .= ' AND w.company_id = ? ';
            $params[] = $companyId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /** Depo oda özeti: toplam ve durum dağılımı */
    public static function statsByWarehouseId(PDO $pdo, string $warehouseId, ?string $companyId = null): array
    {
        $sql = 'SELECT r.status, COUNT(*) AS cnt FROM rooms r
                INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
                WHERE r.deleted_at IS NULL AND r.warehouse_id = ? ';
        $params = [$warehouseId];
        if ($companyId !== null && $companyId !== '') {
            $sql .= ' AND w.company_id = ? ';
            $params[] = $companyId;
        }
        $sql .= ' GROUP BY r.status ';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $byStatus = ['empty' => 0, 'occupied' => 0, 'reserved' => 0, 'locked' => 0];
        $total = 0;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $st = $row['status'] ?? 'empty';
            $cnt = (int) ($row['cnt'] ?? 0);
            if (isset($byStatus[$st])) {
                $byStatus[$st] = $cnt;
            }
            $total += $cnt;
        }
        return ['total' => $total, 'by_status' => $byStatus];
    }

    /** Şirket (veya tüm sistem) oda sayısı */
    public static function countAll(PDO $pdo, ?string $companyId = null): int
    {
        $sql = 'SELECT COUNT(*) FROM rooms r
                INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
                WHERE r.deleted_at IS NULL ';
        $params = [];
        if ($companyId !== null && $companyId !== '') {
            $sql .= ' AND w.company_id = ? ';
            $params[] = $companyId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /** Durum dağılımı (genel bakış) */
    public static function statusCounts(PDO $pdo, ?string $companyId = null): array
    {
        $sql = 'SELECT r.status, COUNT(*) AS cnt FROM rooms r
                INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
                WHERE r.deleted_at IS NULL ';
        $params = [];
        if ($companyId !== null && $companyId !== '') {
            $sql .= ' AND w.company_id = ? ';
            $params[] = $companyId;
        }
        $sql .= ' GROUP BY r.status ';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $byStatus = ['empty' => 0, 'occupied' => 0, 'reserved' => 0, 'locked' => 0];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $st = $row['status'] ?? 'empty';
            if (isset($byStatus[$st])) {
                $byStatus[$st] = (int) ($row['cnt'] ?? 0);
            }
        }
        return $byStatus;
    }

    /** Aynı depoda oda numarası eşleşmesi (1 = 01) */
    public static function findByWarehouseAndNumber(PDO $pdo, string $warehouseId, string $roomNumber, ?string $excludeId = null): ?array
    {
        $key = normalizeRoomNumberKey($roomNumber);
        if ($key === '') {
            return null;
        }
        $stmt = $pdo->prepare('SELECT * FROM rooms WHERE warehouse_id = ? AND deleted_at IS NULL');
        $stmt->execute([$warehouseId]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($excludeId !== null && $excludeId !== '' && ($row['id'] ?? '') === $excludeId) {
                continue;
            }
            if (normalizeRoomNumberKey($row['room_number'] ?? '') === $key) {
                return $row;
            }
        }
        return null;
    }

    /** Aynı depoda çift oda numarası yoksa devam; varsa InvalidArgumentException */
    public static function ensureUniqueInWarehouse(PDO $pdo, string $warehouseId, string $roomNumber, ?string $excludeId = null): void
    {
        $existing = self::findByWarehouseAndNumber($pdo, $warehouseId, $roomNumber, $excludeId);
        if ($existing) {
            throw new InvalidArgumentException(roomDuplicateMessage($roomNumber, $existing));
        }
    }

    public static function create(PDO $pdo, array $data): array
    {
        self::ensureUniqueInWarehouse($pdo, (string) $data['warehouse_id'], (string) $data['room_number']);
        $id = self::uuid();
        $stmt = $pdo->prepare(
            'INSERT INTO rooms (id, room_number, warehouse_id, area_m2, monthly_price, status, floor, block, corridor, description, notes) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $id,
            $data['room_number'],
            $data['warehouse_id'],
            $data['area_m2'],
            $data['monthly_price'],
            $data['status'] ?? 'empty',
            $data['floor'] ?? null,
            $data['block'] ?? null,
            $data['corridor'] ?? null,
            $data['description'] ?? null,
            $data['notes'] ?? null,
        ]);
        return self::findOne($pdo, $id);
    }

    public static function findAll(PDO $pdo, ?string $warehouseId = null, ?string $search = null, ?string $status = null, ?string $hasContract = null, ?string $companyId = null): array
    {
        $sql = 'SELECT r.*, w.name AS warehouse_name, w.company_id 
                FROM rooms r 
                INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL 
                WHERE r.deleted_at IS NULL ';
        $params = [];
        if ($companyId !== null && $companyId !== '') {
            $sql .= ' AND w.company_id = ? ';
            $params[] = $companyId;
        }
        if ($warehouseId !== null && $warehouseId !== '') {
            $sql .= ' AND r.warehouse_id = ? ';
            $params[] = $warehouseId;
        }
        if ($status !== null && $status !== '' && in_array($status, ['empty', 'occupied', 'reserved', 'locked'], true)) {
            $sql .= ' AND r.status = ? ';
            $params[] = $status;
        }
        $search = trim((string) $search);
        if ($search !== '') {
            appendTurkishLikeClause($sql, $params, [
                'r.room_number',
                'r.floor',
                'r.block',
                'r.corridor',
                'r.description',
                'r.notes',
                'w.name',
            ], $search);
        }
        if ($hasContract === 'yes') {
            $sql .= ' AND EXISTS (SELECT 1 FROM contracts c INNER JOIN customers cu ON cu.id = c.customer_id AND cu.deleted_at IS NULL WHERE c.room_id = r.id AND c.deleted_at IS NULL AND c.is_active = 1) ';
        } elseif ($hasContract === 'no') {
            $sql .= ' AND NOT EXISTS (SELECT 1 FROM contracts c INNER JOIN customers cu ON cu.id = c.customer_id AND cu.deleted_at IS NULL WHERE c.room_id = r.id AND c.deleted_at IS NULL AND c.is_active = 1) ';
        }
        $sql .= ' ORDER BY w.name, ' . self::sqlOrderByRoomNumber('r') . ' ';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findOne(PDO $pdo, string $id): ?array
    {
        $stmt = $pdo->prepare(
            'SELECT r.*, w.name AS warehouse_name, w.company_id 
             FROM rooms r 
             LEFT JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL 
             WHERE r.id = ? AND r.deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function update(PDO $pdo, string $id, array $data): array
    {
        $current = self::findOne($pdo, $id);
        if (!$current) {
            throw new InvalidArgumentException('Oda bulunamadı.');
        }
        $warehouseId = (string) ($data['warehouse_id'] ?? $current['warehouse_id'] ?? '');
        $roomNumber = (string) ($data['room_number'] ?? $current['room_number'] ?? '');
        if ($warehouseId !== '' && $roomNumber !== '') {
            self::ensureUniqueInWarehouse($pdo, $warehouseId, $roomNumber, $id);
        }
        $allowed = ['room_number', 'warehouse_id', 'area_m2', 'monthly_price', 'status', 'floor', 'block', 'corridor', 'description', 'notes'];
        $set = [];
        $params = [];
        foreach ($allowed as $k) {
            if (array_key_exists($k, $data)) {
                $set[] = "`$k` = ?";
                $params[] = $data[$k];
            }
        }
        if (empty($set)) {
            return self::findOne($pdo, $id);
        }
        $params[] = $id;
        $pdo->prepare('UPDATE rooms SET ' . implode(', ', $set) . ' WHERE id = ?')->execute($params);
        return self::findOne($pdo, $id);
    }

    public static function patchStatus(PDO $pdo, string $id, string $status): bool
    {
        $stmt = $pdo->prepare('UPDATE rooms SET status = ? WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([$status, $id]);
        return $stmt->rowCount() > 0;
    }

    /** Aktif sözleşme var mı – belirtilen sözleşme hariç */
    public static function hasActiveContractExcept(PDO $pdo, string $roomId, string $exceptContractId): bool
    {
        $stmt = $pdo->prepare(
            'SELECT 1 FROM contracts c
             INNER JOIN customers cu ON cu.id = c.customer_id AND cu.deleted_at IS NULL
             WHERE c.room_id = ? AND c.deleted_at IS NULL AND c.is_active = 1 AND c.id != ?
             LIMIT 1'
        );
        $stmt->execute([$roomId, $exceptContractId]);
        if ($stmt->fetch()) {
            return true;
        }
        if (!Contract::hasLinkedRoomsTable($pdo)) {
            return false;
        }
        $stmt = $pdo->prepare(
            'SELECT 1 FROM contract_linked_rooms clr
             INNER JOIN contracts c ON c.id = clr.contract_id AND c.deleted_at IS NULL AND c.is_active = 1 AND c.id != ?
             INNER JOIN customers cu ON cu.id = c.customer_id AND cu.deleted_at IS NULL
             WHERE clr.room_id = ?
             LIMIT 1'
        );
        $stmt->execute([$exceptContractId, $roomId]);
        return (bool) $stmt->fetch();
    }

    /** Aktif sözleşme var mı – sadece silinmemiş müşteriye ait sözleşmeler sayılır (detay sayfasıyla tutarlı) */
    public static function hasActiveContract(PDO $pdo, string $roomId): bool
    {
        $stmt = $pdo->prepare(
            'SELECT 1 FROM contracts c
             INNER JOIN customers cu ON cu.id = c.customer_id AND cu.deleted_at IS NULL
             WHERE c.room_id = ? AND c.deleted_at IS NULL AND c.is_active = 1 LIMIT 1'
        );
        $stmt->execute([$roomId]);
        if ($stmt->fetch()) {
            return true;
        }
        if (!Contract::hasLinkedRoomsTable($pdo)) {
            return false;
        }
        $stmt = $pdo->prepare(
            'SELECT 1 FROM contract_linked_rooms clr
             INNER JOIN contracts c ON c.id = clr.contract_id AND c.deleted_at IS NULL AND c.is_active = 1
             INNER JOIN customers cu ON cu.id = c.customer_id AND cu.deleted_at IS NULL
             WHERE clr.room_id = ?
             LIMIT 1'
        );
        $stmt->execute([$roomId]);
        return (bool) $stmt->fetch();
    }

    public static function remove(PDO $pdo, string $id): void
    {
        $pdo->prepare('DELETE FROM rooms WHERE id = ?')->execute([$id]);
    }

    private static function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
