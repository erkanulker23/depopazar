<?php
class Item
{
    /** Müşteriye ait tüm eşyaları getirir (sözleşmeler üzerinden) */
    public static function findByCustomerId(PDO $pdo, string $customerId): array
    {
        $stmt = $pdo->prepare(
            'SELECT i.id, i.name, i.description, i.quantity, i.unit
             FROM items i
             INNER JOIN contracts c ON c.id = i.contract_id AND c.deleted_at IS NULL
             WHERE c.customer_id = ? AND i.deleted_at IS NULL
             ORDER BY i.name'
        );
        $stmt->execute([$customerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
