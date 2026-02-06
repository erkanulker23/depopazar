<?php
/**
 * SMTP ile e-posta gönderimi. Ayarlar > E-posta sekmesindeki company_mail_settings kullanılır.
 */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class MailService
{
    /**
     * Şirketin e-posta ayarlarına göre SMTP üzerinden e-posta gönderir.
     *
     * @param array $mailSettings company_mail_settings satırı (smtp_host, smtp_port, smtp_secure, smtp_username, smtp_password, from_email, from_name)
     * @param string $to Alıcı e-posta
     * @param string $subject Konu
     * @param string $bodyPlain Düz metin gövde (UTF-8)
     * @return array ['success' => bool, 'error' => string|null]
     */
    public static function sendSmtp(array $mailSettings, string $to, string $subject, string $bodyPlain): array
    {
        $host = trim($mailSettings['smtp_host'] ?? '');
        $port = (int) ($mailSettings['smtp_port'] ?? 587);
        if ($host === '' || $port <= 0) {
            return ['success' => false, 'error' => 'SMTP sunucu ve port ayarları eksik.'];
        }

        $fromEmail = trim($mailSettings['from_email'] ?? '') ?: 'noreply@depopazar.com';
        $fromName = trim($mailSettings['from_name'] ?? '') ?: 'Depo ve Nakliye Takip';
        $username = trim($mailSettings['smtp_username'] ?? '');
        $password = (string) ($mailSettings['smtp_password'] ?? '');
        $smtpSecure = !empty($mailSettings['smtp_secure']);

        try {
            $mail = new PHPMailer(true);
            $mail->CharSet = PHPMailer::CHARSET_UTF8;
            $mail->Encoding = PHPMailer::ENCODING_BASE64;
            $mail->isSMTP();
            $mail->Host = $host;
            $mail->Port = $port;
            $mail->SMTPAuth = $username !== '';
            if ($mail->SMTPAuth) {
                $mail->Username = $username;
                $mail->Password = $password;
            }
            if ($smtpSecure) {
                $mail->SMTPSecure = $port === 465 ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            }
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->Body = $bodyPlain;
            $mail->isHTML(false);
            $mail->send();
            return ['success' => true, 'error' => null];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
