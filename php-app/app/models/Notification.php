<?php
class Notification
{
    public static function create(PDO $pdo, string $userId, string $type, string $title, string $message, ?array $metadata = null, bool $sendPush = true, bool $sendEmail = true): void
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
        if ($sendPush || $sendEmail) {
            $url = self::resolveNotificationUrl($type, $metadata);
            $emailContext = self::emailContextForNotification($title, $metadata);
            self::deferDelivery($pdo, null, [$userId], $title, $message, $type, $url, $sendPush, $sendEmail, $emailContext);
        }
    }

    /**
     * Şirketteki staff + süper admin kullanıcılarına uygulama içi bildirim.
     * Varsayılan: push + e-posta arka planda (yanıt gönderildikten sonra).
     *
     * @param array{push?: bool, email?: bool} $options
     */
    public static function createForCompany(PDO $pdo, ?string $companyId, string $type, string $title, string $message, ?array $metadata = null, array $options = []): void
    {
        $sendPush = $options['push'] ?? true;
        $sendEmail = $options['email'] ?? true;

        $userIds = self::companyStaffUserIds($pdo, $companyId);
        if ($userIds === []) {
            return;
        }

        $metaJson = $metadata ? json_encode($metadata, JSON_UNESCAPED_UNICODE) : null;
        $placeholders = [];
        $params = [];
        foreach ($userIds as $uid) {
            $placeholders[] = '(?, ?, ?, ?, ?, ?)';
            array_push($params, self::uuid(), $uid, $type, $title, $message, $metaJson);
        }
        $sql = 'INSERT INTO notifications (id, user_id, type, title, message, metadata) VALUES '
            . implode(', ', $placeholders);
        $pdo->prepare($sql)->execute($params);

        if ($sendPush || $sendEmail) {
            $url = self::resolveNotificationUrl($type, $metadata);
            $emailContext = self::emailContextForNotification($title, $metadata);
            self::deferDelivery($pdo, $companyId, $userIds, $title, $message, $type, $url, $sendPush, $sendEmail, $emailContext);
        }
    }

    /**
     * Belirli depodan sorumlu depo sorumlularına bildirim + e-posta.
     *
     * @param array{push?: bool, email?: bool} $options
     */
    public static function createForWarehouse(PDO $pdo, string $warehouseId, ?string $companyId, string $type, string $title, string $message, ?array $metadata = null, array $options = []): void
    {
        if ($warehouseId === '') {
            return;
        }
        $sendPush = $options['push'] ?? true;
        $sendEmail = $options['email'] ?? true;

        $userIds = self::warehouseManagerUserIds($pdo, $warehouseId);
        if ($userIds === []) {
            return;
        }

        $metadata = $metadata ?? [];
        $metadata['warehouse_id'] = $warehouseId;
        $metaJson = json_encode($metadata, JSON_UNESCAPED_UNICODE);

        $placeholders = [];
        $params = [];
        foreach ($userIds as $uid) {
            $placeholders[] = '(?, ?, ?, ?, ?, ?)';
            array_push($params, self::uuid(), $uid, $type, $title, $message, $metaJson);
        }
        $sql = 'INSERT INTO notifications (id, user_id, type, title, message, metadata) VALUES '
            . implode(', ', $placeholders);
        $pdo->prepare($sql)->execute($params);

        if ($sendPush || $sendEmail) {
            $url = self::resolveNotificationUrl($type, $metadata);
            $emailContext = self::emailContextForNotification($title, $metadata);
            self::deferDelivery($pdo, $companyId, $userIds, $title, $message, $type, $url, $sendPush, $sendEmail, $emailContext);
        }
    }

    /**
     * Şirket geneli + depo sorumlularına (depo olayları için).
     *
     * @param array{push?: bool, email?: bool} $options
     */
    public static function createForCompanyAndWarehouse(PDO $pdo, ?string $companyId, ?string $warehouseId, string $type, string $title, string $message, ?array $metadata = null, array $options = []): void
    {
        self::createForCompany($pdo, $companyId, $type, $title, $message, $metadata, $options);
        if ($warehouseId !== null && $warehouseId !== '') {
            self::createForWarehouse($pdo, $warehouseId, $companyId, $type, $title, $message, $metadata, $options);
        }
    }

    /** @return list<string> */
    private static function companyStaffUserIds(PDO $pdo, ?string $companyId): array
    {
        $userIds = [];
        if ($companyId) {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE company_id = ? AND deleted_at IS NULL AND role IN (' . RolePermissions::sqlCompanyBroadcastNotificationRoles() . ')');
            $stmt->execute([$companyId]);
            $userIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
        $stmt = $pdo->query('SELECT id FROM users WHERE deleted_at IS NULL AND role = \'super_admin\'');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $userIds[] = $row['id'];
        }
        return array_values(array_unique($userIds));
    }

    /** @return list<string> */
    private static function warehouseManagerUserIds(PDO $pdo, string $warehouseId): array
    {
        try {
            $stmt = $pdo->prepare(
                "SELECT id FROM users WHERE deleted_at IS NULL AND is_active = 1 AND role = 'warehouse_manager' AND managed_warehouse_id = ?"
            );
            $stmt->execute([$warehouseId]);
            return array_values(array_filter($stmt->fetchAll(PDO::FETCH_COLUMN)));
        } catch (Throwable $e) {
            error_log('warehouseManagerUserIds: ' . $e->getMessage());
            return [];
        }
    }

    /** @return array{actor_name?: string, acted_at: string, action_title: string} */
    private static function emailContextForNotification(string $title, ?array $metadata): array
    {
        $metadata = $metadata ?? [];
        if (empty($metadata['acted_at'])) {
            $metadata['acted_at'] = date('Y-m-d H:i:s');
        }
        if (empty($metadata['action_title'])) {
            $metadata['action_title'] = $title;
        }
        return $metadata;
    }

    /** Push + e-posta gönderimini istek yanıtından sonra çalıştırır. */
    private static function deferDelivery(
        PDO $pdo,
        ?string $companyId,
        array $userIds,
        string $title,
        string $message,
        string $type,
        string $url,
        bool $sendPush,
        bool $sendEmail,
        ?array $emailContext = null
    ): void {
        $userIds = array_values(array_unique(array_filter($userIds)));
        if ($userIds === []) {
            return;
        }
        register_shutdown_function(static function () use ($pdo, $companyId, $userIds, $title, $message, $type, $url, $sendPush, $sendEmail, $emailContext): void {
            releaseHttpResponse();
            if ($sendPush) {
                try {
                    PushService::sendToUsers($pdo, $userIds, $title, $message, $url, $type);
                } catch (Throwable $e) {
                    error_log('Notification push shutdown: ' . $e->getMessage());
                }
            }
            if ($sendEmail) {
                try {
                    MailService::sendToUsers($pdo, $companyId, $userIds, $title, $message, $emailContext);
                } catch (Throwable $e) {
                    error_log('Notification email shutdown: ' . $e->getMessage());
                }
            }
        });
    }

    private static function resolveNotificationUrl(string $type, ?array $metadata): string
    {
        if (!empty($metadata['url']) && is_string($metadata['url'])) {
            return $metadata['url'];
        }
        if ($type === 'contract' && !empty($metadata['contract_id'])) {
            return '/girisler/' . $metadata['contract_id'];
        }
        if ($type === 'room' && !empty($metadata['room_id'])) {
            return '/odalar/' . $metadata['room_id'];
        }
        if ($type === 'warehouse' && !empty($metadata['warehouse_id'])) {
            return '/depolar/' . $metadata['warehouse_id'];
        }
        if ($type === 'customer' && !empty($metadata['customer_id'])) {
            return '/musteriler/' . $metadata['customer_id'];
        }
        return match ($type) {
            'contract', 'payment' => '/girisler',
            'customer' => '/musteriler',
            'transport' => '/nakliye-isler',
            'warehouse' => '/depolar',
            'room' => '/odalar',
            'expense' => '/masraflar',
            'proposal' => '/teklifler',
            'vehicle' => '/araclar',
            'user' => '/kullanicilar',
            'settings', 'bank' => '/ayarlar',
            default => '/bildirimler',
        };
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
        $stmt = $pdo->prepare('DELETE FROM notifications WHERE user_id = ?');
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
