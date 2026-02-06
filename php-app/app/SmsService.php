<?php
/**
 * Netgsm SMS gönderimi. Ayarlar > SMS (Netgsm) sekmesindeki company_sms_settings kullanılır.
 */
class SmsService
{
    private const DEFAULT_API_URL = 'https://api.netgsm.com.tr/sms/send/get';

    /**
     * Şirketin SMS ayarlarına göre tek numaraya SMS gönderir.
     *
     * @param PDO $pdo
     * @param string $companyId
     * @param string $phone Telefon (5xxxxxxxxx, 905xxxxxxxxx, 05xxxxxxxxx vb.)
     * @param string $message UTF-8 mesaj metni
     * @return array ['success' => bool, 'error' => string|null]
     */
    public static function send(PDO $pdo, string $companyId, string $phone, string $message): array
    {
        $settings = self::getSettings($pdo, $companyId);
        if (!$settings) {
            return ['success' => false, 'error' => 'SMS ayarları bulunamadı.'];
        }
        if (empty($settings['is_active'])) {
            return ['success' => false, 'error' => 'SMS gönderimi kapalı.'];
        }
        if (empty($settings['username']) || empty($settings['sender_id'])) {
            return ['success' => false, 'error' => 'Kullanıcı kodu veya gönderici adı eksik.'];
        }
        if (empty($settings['password'])) {
            return ['success' => false, 'error' => 'SMS şifresi eksik.'];
        }

        if (!empty($settings['test_mode'])) {
            return ['success' => true, 'error' => null];
        }

        $gsm = self::normalizePhone($phone);
        if ($gsm === '') {
            return ['success' => false, 'error' => 'Geçersiz telefon numarası.'];
        }

        $apiUrl = !empty($settings['api_url']) ? $settings['api_url'] : self::DEFAULT_API_URL;
        $params = [
            'usercode' => $settings['username'],
            'password' => $settings['password'],
            'gsmno' => $gsm,
            'message' => $message,
            'msgheader' => $settings['sender_id'],
        ];

        $url = $apiUrl . (strpos($apiUrl, '?') !== false ? '&' : '?') . http_build_query($params);
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 15,
                'ignore_errors' => true,
            ],
        ]);
        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            return ['success' => false, 'error' => 'API isteği başarısız (bağlantı hatası).'];
        }

        $code = trim($response);
        if (preg_match('/^\d+$/', $code)) {
            $num = (int) $code;
            if ($num > 0) {
                return ['success' => true, 'error' => null];
            }
            if ($num === 0) {
                return ['success' => false, 'error' => 'Netgsm hata: 0 (gönderim başarısız).'];
            }
        }
        if (preg_match('/^(\d+)\s/', $code, $m) && (int) $m[1] > 0) {
            return ['success' => true, 'error' => null];
        }
        return ['success' => false, 'error' => 'Netgsm yanıt: ' . $code];
    }

    /**
     * Şirketin SMS ayarlarını döndürür (şifre dahil; sadece gönderim için kullanın).
     */
    public static function getSettings(PDO $pdo, string $companyId): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM company_sms_settings WHERE company_id = ? AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([$companyId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Türkiye cep numarasını Netgsm formatına çevirir (905xxxxxxxxx).
     */
    public static function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);
        if (strlen($digits) === 10 && $digits[0] === '5') {
            return '90' . $digits;
        }
        if (strlen($digits) === 11 && substr($digits, 0, 2) === '05') {
            return '9' . substr($digits, 1);
        }
        if (strlen($digits) === 12 && substr($digits, 0, 2) === '90') {
            return $digits;
        }
        if (strlen($digits) >= 10) {
            return '90' . ltrim($digits, '0');
        }
        return '';
    }
}
