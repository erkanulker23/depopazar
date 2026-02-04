<?php
class PushSubscription
{
    public static function save(PDO $pdo, string $userId, string $endpoint, string $p256dhKey, string $authKey, ?string $userAgent = null): void
    {
        $id = self::uuid();
        $stmt = $pdo->prepare(
            'INSERT INTO push_subscriptions (id, user_id, endpoint, p256dh_key, auth_key, user_agent) 
             VALUES (?, ?, ?, ?, ?, ?) 
             ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), p256dh_key = VALUES(p256dh_key), auth_key = VALUES(auth_key), user_agent = VALUES(user_agent)'
        );
        $stmt->execute([$id, $userId, $endpoint, $p256dhKey, $authKey, $userAgent ?? '']);
    }

    /** Belirtilen kullanıcı ID'leri için tüm abonelikleri döner */
    public static function getByUserIds(PDO $pdo, array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $stmt = $pdo->prepare(
            "SELECT id, user_id, endpoint, p256dh_key, auth_key FROM push_subscriptions WHERE user_id IN ($placeholders)"
        );
        $stmt->execute(array_values($userIds));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function deleteByEndpoint(PDO $pdo, string $endpoint): void
    {
        $stmt = $pdo->prepare('DELETE FROM push_subscriptions WHERE endpoint = ?');
        $stmt->execute([$endpoint]);
    }

    private static function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
