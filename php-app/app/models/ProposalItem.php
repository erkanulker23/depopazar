<?php
class ProposalItem
{
    public static function findByProposalId(PDO $pdo, string $proposalId): array
    {
        $stmt = $pdo->prepare(
            'SELECT pi.*, s.name AS service_name FROM proposal_items pi
             LEFT JOIN services s ON s.id = pi.service_id AND s.deleted_at IS NULL
             WHERE pi.proposal_id = ? AND (pi.deleted_at IS NULL) ORDER BY pi.created_at ASC'
        );
        $stmt->execute([$proposalId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function deleteByProposalId(PDO $pdo, string $proposalId): void
    {
        $stmt = $pdo->prepare('UPDATE proposal_items SET deleted_at = NOW() WHERE proposal_id = ?');
        $stmt->execute([$proposalId]);
    }

    public static function create(PDO $pdo, string $proposalId, array $item): void
    {
        $id = self::uuid();
        $name = trim($item['name'] ?? '');
        if ($name === '') return;
        $quantity = isset($item['quantity']) ? (float) $item['quantity'] : 1;
        $unitPrice = isset($item['unit_price']) ? (float) str_replace(',', '.', $item['unit_price']) : 0;
        $totalPrice = $quantity * $unitPrice;
        $stmt = $pdo->prepare(
            'INSERT INTO proposal_items (id, proposal_id, service_id, name, description, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $id,
            $proposalId,
            !empty($item['service_id']) ? $item['service_id'] : null,
            $name,
            trim($item['description'] ?? '') ?: null,
            $quantity,
            $unitPrice,
            $totalPrice,
        ]);
    }

    private static function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
