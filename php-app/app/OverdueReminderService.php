<?php
/**
 * Vadesi geçmiş ödemeler için müşteri e-posta hatırlatması.
 * Cron: php artisan overdue:remind
 */
class OverdueReminderService
{
    public static function sendAll(PDO $pdo): array
    {
        $sent = 0;
        $skipped = 0;
        $errors = [];

        $companies = $pdo->query('SELECT id FROM companies WHERE deleted_at IS NULL')->fetchAll(PDO::FETCH_ASSOC);
        foreach ($companies as $company) {
            $companyId = $company['id'];
            $mail = Company::getMailSettings($pdo, $companyId);
            if (!$mail || empty($mail['smtp_host']) || empty($mail['is_active']) || empty($mail['notify_customer_on_overdue'])) {
                $skipped++;
                continue;
            }

            $config = require defined('APP_ROOT') ? APP_ROOT . '/config/config.php' : __DIR__ . '/../config/config.php';
            $appName = $config['app_name'] ?? 'Depo ve Nakliye Takip';
            $defaultTpl = "Sayın {musteri_adi},\n\nVadesi {vade} olan {tutar} tutarındaki ödemenizin geciktiğini hatırlatırız.\n\nLütfen en kısa sürede ödeme yapınız.";
            $tpl = !empty(trim($mail['payment_reminder_template'] ?? '')) ? $mail['payment_reminder_template'] : $defaultTpl;

            $overdue = Payment::findOverdueForReminder($pdo, $companyId);
            $byCustomer = [];
            foreach ($overdue as $p) {
                $cid = $p['customer_id'] ?? ($p['contract_id'] ?? '');
                $email = trim($p['customer_email'] ?? '');
                if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }
                $key = $email;
                if (!isset($byCustomer[$key])) {
                    $byCustomer[$key] = [
                        'email' => $email,
                        'name' => trim(($p['customer_first_name'] ?? '') . ' ' . ($p['customer_last_name'] ?? '')),
                        'payments' => [],
                    ];
                }
                $byCustomer[$key]['payments'][] = $p;
            }

            foreach ($byCustomer as $row) {
                $p = $row['payments'][0];
                $tutar = number_format(array_sum(array_map(fn($x) => (float) ($x['amount'] ?? 0), $row['payments'])), 2, ',', '.') . ' ₺';
                $vade = !empty($p['due_date']) ? date('d.m.Y', strtotime($p['due_date'])) : '-';
                $replace = [
                    '{musteri_adi}' => $row['name'],
                    '{tutar}' => $tutar,
                    '{vade}' => $vade,
                    '{sozlesme_no}' => $p['contract_number'] ?? '',
                ];
                $bodyPlain = str_replace(array_keys($replace), array_values($replace), $tpl);
                $bodyHtml = MailService::wrapInHtmlTemplate($appName, 'Ödeme Hatırlatması', $bodyPlain, $vade);
                $result = MailService::sendSmtp($mail, $row['email'], $appName . ' – Ödeme Hatırlatması', $bodyPlain, $bodyHtml);
                if ($result['success'] ?? false) {
                    $sent++;
                } else {
                    $errors[] = ($row['email'] ?? '') . ': ' . ($result['error'] ?? 'Gönderilemedi');
                }
            }
        }

        return ['sent' => $sent, 'skipped_companies' => $skipped, 'errors' => $errors];
    }
}
