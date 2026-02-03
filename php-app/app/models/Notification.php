<?php
class Notification
{
    public static function create(PDO $pdo, string $userId, string $type, string $title, string $message, ?array $metadata = null): void
    {
        $id = self::uuid();
        $stmt = $pdo->prepare('INSERT INTO notifications (id, user_id, type, title, message, metadata) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $id,
            $userId,
            $type,
            $title,
            $message,
            $metadata ? json_encode($metadata, JSON_UNESCAPED_UNICODE) : null,
        ]);
    }

    /** Şirketteki tüm staff kullanıcılarına + super_admin kullanıcılarına bildirim ekler */
    public static function createForCompany(PDO $pdo, ?string $companyId, string $type, string $title, string $message, ?array $metadata = null): void
    {
        $userIds = [];
        if ($companyId) {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE company_id = ? AND deleted_at IS NULL AND role IN (\'company_owner\', \'company_staff\', \'data_entry\', \'accounting\')');
            $stmt->execute([$companyId]);
            $userIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
        $stmt = $pdo->query('SELECT id FROM users WHERE deleted_at IS NULL AND role = \'super_admin\'');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $userIds[] = $row['id'];
        }
        $userIds = array_unique($userIds);
        foreach ($userIds as $uid) {
            self::create($pdo, $uid, $type, $title, $message, $metadata);
        }
    }

    /** Giriş yapan kullanıcıya ait bildirimleri getirir */
    public static function findByUserId(PDO $pdo, string $userId, int $limit = 50, bool $unreadOnly = false): array
    {
        $sql = 'SELECT id, type, title, message, is_read, read_at, created_at, metadata FROM notifications WHERE user_id = ? AND deleted_at IS NULL';
        if ($unreadOnly) {
            $sql .= ' AND is_read = 0';
        }
        $sql .= ' ORDER BY created_at DESC LIMIT ' . (int) $limit;
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) {
            if (!empty($r['metadata'])) {
                $r['metadata'] = json_decode($r['metadata'], true);
            }
        }
        return $rows;
    }

    public static function countUnread(PDO $pdo, string $userId): int
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND deleted_at IS NULL AND is_read = 0');
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    public static function markAllRead(PDO $pdo, string $userId): void
    {
        $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND deleted_at IS NULL AND is_read = 0');
        $stmt->execute([$userId]);
    }

    public static function deleteAll(PDO $pdo, string $userId): void
    {
        $stmt = $pdo->prepare('UPDATE notifications SET deleted_at = NOW() WHERE user_id = ? AND deleted_at IS NULL');
        $stmt->execute([$userId]);
    }

    private static function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
