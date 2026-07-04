<?php
/**
 * Yaklaşan sözleşme bitiş tarihi için müşteri e-posta hatırlatması.
 * Cron: php php-app/scripts/contract-expiring-remind.php
 */
class ContractExpiringReminderService
{
    public static function sendAll(PDO $pdo, int $withinDays = 30): array
    {
        $sent = 0;
        $skipped = 0;
        $errors = [];

        $companies = $pdo->query('SELECT id FROM companies WHERE deleted_at IS NULL')->fetchAll(PDO::FETCH_ASSOC);
        foreach ($companies as $company) {
            $companyId = $company['id'];
            $mail = Company::getMailSettings($pdo, $companyId);
            if (!$mail || empty($mail['smtp_host']) || empty($mail['is_active'])) {
                $skipped++;
                continue;
            }
            if (
                array_key_exists('notify_customer_on_contract_expiring', $mail)
                && empty($mail['notify_customer_on_contract_expiring'])
            ) {
                $skipped++;
                continue;
            }

            $config = require defined('APP_ROOT') ? APP_ROOT . '/config/config.php' : __DIR__ . '/../config/config.php';
            $appName = $config['app_name'] ?? 'Depo ve Nakliye Takip';
            $defaultTpl = "Sayın {musteri_adi},\n\n{sozlesme_no} numaralı sözleşmenizin bitiş tarihi {bitis_tarihi} olarak yaklaşıyor.\nDepo: {depo_adi}\nOda: {oda_no}\n\nYenileme veya çıkış işlemleri için bizimle iletişime geçebilirsiniz.";
            $tpl = !empty(trim($mail['contract_expiring_template'] ?? '')) ? $mail['contract_expiring_template'] : $defaultTpl;

            $contracts = self::findExpiringWithEmail($pdo, $companyId, $withinDays);
            foreach ($contracts as $c) {
                $email = trim($c['customer_email'] ?? '');
                if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }
                $musteriAdi = trim(($c['customer_first_name'] ?? '') . ' ' . ($c['customer_last_name'] ?? ''));
                $replace = [
                    '{musteri_adi}' => $musteriAdi,
                    '{sozlesme_no}' => $c['contract_number'] ?? '',
                    '{depo_adi}' => $c['warehouse_name'] ?? '',
                    '{oda_no}' => $c['room_number'] ?? '',
                    '{baslangic_tarihi}' => !empty($c['start_date']) ? date('d.m.Y', strtotime($c['start_date'])) : '',
                    '{bitis_tarihi}' => !empty($c['end_date']) ? date('d.m.Y', strtotime($c['end_date'])) : '',
                    '{aylik_ucret}' => number_format((float) ($c['monthly_price'] ?? 0), 2, ',', '.') . ' ₺',
                ];
                $bodyPlain = str_replace(array_keys($replace), array_values($replace), $tpl);
                $emailContext = [
                    'actor_name' => 'Otomatik hatırlatma',
                    'acted_at' => date('Y-m-d H:i:s'),
                    'action_title' => 'Sözleşme bitiş hatırlatması',
                ];
                $result = MailService::sendTemplated(
                    $mail,
                    $email,
                    $appName . ' – Sözleşme Bitiş Hatırlatması',
                    'Sözleşme Bitiş Hatırlatması',
                    $bodyPlain,
                    $replace['{bitis_tarihi}'],
                    $emailContext
                );
                if ($result['success'] ?? false) {
                    $sent++;
                } else {
                    $errors[] = $email . ': ' . ($result['error'] ?? 'Gönderilemedi');
                }
            }
        }

        return ['sent' => $sent, 'skipped_companies' => $skipped, 'errors' => $errors];
    }

    /** @return list<array<string, mixed>> */
    private static function findExpiringWithEmail(PDO $pdo, string $companyId, int $withinDays): array
    {
        $stmt = $pdo->prepare(
            'SELECT c.*, cu.first_name AS customer_first_name, cu.last_name AS customer_last_name,
                    cu.email AS customer_email,
                    r.room_number, w.name AS warehouse_name
             FROM contracts c
             INNER JOIN customers cu ON cu.id = c.customer_id AND cu.deleted_at IS NULL
             INNER JOIN rooms r ON r.id = c.room_id AND r.deleted_at IS NULL
             INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
             WHERE c.deleted_at IS NULL AND c.is_active = 1
               AND c.terminated_at IS NULL
               AND w.company_id = ?
               AND c.end_date IS NOT NULL
               AND DATE(c.end_date) >= CURDATE()
               AND DATE(c.end_date) <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
             ORDER BY c.end_date ASC'
        );
        $stmt->execute([$companyId, max(1, $withinDays)]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
