<?php
class Item
{
    /** Sözleşmeye ait depodaki eşyalar */
    public static function findByContractId(PDO $pdo, string $contractId): array
    {
        $stmt = $pdo->prepare(
            'SELECT id, name, description, quantity, unit, `condition`
             FROM items
             WHERE contract_id = ? AND deleted_at IS NULL
             ORDER BY created_at ASC, name ASC'
        );
        $stmt->execute([$contractId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Müşteriye ait tüm eşyaları getirir (sözleşmeler üzerinden) */
    public static function findByCustomerId(PDO $pdo, string $customerId): array
    {
        $stmt = $pdo->prepare(
            'SELECT i.id, i.name, i.description, i.quantity, i.unit, i.`condition`
             FROM items i
             INNER JOIN contracts c ON c.id = i.contract_id AND c.deleted_at IS NULL
             WHERE c.customer_id = ? AND i.deleted_at IS NULL
             ORDER BY i.name'
        );
        $stmt->execute([$customerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function deleteByContractId(PDO $pdo, string $contractId): void
    {
        $stmt = $pdo->prepare('DELETE FROM items WHERE contract_id = ?');
        $stmt->execute([$contractId]);
    }

    public static function create(PDO $pdo, string $contractId, string $roomId, array $item, ?string $storedAt = null): void
    {
        $name = trim($item['name'] ?? '');
        if ($name === '') {
            return;
        }
        $quantity = isset($item['quantity']) && $item['quantity'] !== '' ? max(1, (int) $item['quantity']) : 1;
        $unit = trim((string) ($item['unit'] ?? '')) ?: 'adet';
        $condition = normalizeItemCondition($item['condition'] ?? 'sifir');
        $stmt = $pdo->prepare(
            'INSERT INTO items (id, room_id, contract_id, name, description, quantity, unit, `condition`, stored_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            self::uuid(),
            $roomId,
            $contractId,
            $name,
            trim($item['description'] ?? '') ?: null,
            $quantity,
            $unit,
            $condition,
            $storedAt ?: date('Y-m-d H:i:s'),
        ]);
    }

    /** Sözleşme eşya listesini form verisine göre yeniden yazar */
    public static function syncForContract(PDO $pdo, string $contractId, string $roomId, array $items, ?string $storedAt = null): void
    {
        self::deleteByContractId($pdo, $contractId);
        foreach ($items as $item) {
            self::create($pdo, $contractId, $roomId, $item, $storedAt);
        }
    }

    private static function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
