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
     * @param string|null $bodyHtml Opsiyonel HTML gövde; verilirse e-posta HTML olarak gönderilir, bodyPlain alt metin olarak kullanılır
     * @return array ['success' => bool, 'error' => string|null]
     */
    public static function sendSmtp(array $mailSettings, string $to, string $subject, string $bodyPlain, ?string $bodyHtml = null): array
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
            if ($bodyHtml !== null && $bodyHtml !== '') {
                $mail->isHTML(true);
                $mail->Body = $bodyHtml;
                $mail->AltBody = $bodyPlain;
            } else {
                $mail->isHTML(false);
                $mail->Body = $bodyPlain;
            }
            $mail->send();
            self::logSend($to, $subject, true, null);
            return ['success' => true, 'error' => null];
        } catch (Exception $e) {
            $err = $e->getMessage();
            self::logSend($to, $subject, false, $err);
            return ['success' => false, 'error' => $err];
        }
    }

    /**
     * Her e-posta gönderimini loglar (panelden "Son gönderimler" ile takip için).
     */
    public static function logSend(string $to, string $subject, bool $success, ?string $error): void
    {
        $dir = defined('APP_ROOT') ? (APP_ROOT . '/storage/logs') : (__DIR__ . '/../storage/logs');
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $file = $dir . '/email.log';
        $line = date('Y-m-d H:i:s') . "\t" . $to . "\t" . str_replace(["\t", "\n", "\r"], ' ', $subject) . "\t" . ($success ? 'OK' : 'FAIL') . "\t" . ($error !== null ? str_replace(["\t", "\n", "\r"], ' ', $error) : '') . "\n";
        @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    /**
     * Tüm süper admin kullanıcılara aksiyon bildirimi e-postası gönderir.
     * Ayarlar > E-posta ayarları (company_mail_settings) kullanılır; companyId yoksa e-postası tanımlı ilk şirket kullanılır.
     */
    public static function sendToSuperAdmins(PDO $pdo, ?string $companyId, string $subject, string $message): void
    {
        $stmt = $pdo->query("SELECT email FROM users WHERE role = 'super_admin' AND deleted_at IS NULL AND email != ''");
        $emails = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if (empty($emails)) {
            return;
        }
        $mailSettings = null;
        if ($companyId !== null && $companyId !== '') {
            $mailSettings = Company::getMailSettings($pdo, $companyId);
        }
        if ($mailSettings === null) {
            $row = $pdo->query("SELECT company_id FROM company_mail_settings WHERE deleted_at IS NULL LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $mailSettings = Company::getMailSettings($pdo, $row['company_id']);
            }
        }
        if ($mailSettings === null || empty($mailSettings['smtp_host']) || empty($mailSettings['smtp_password'])) {
            return;
        }
        $appName = 'DepoPazar';
        if (defined('APP_ROOT') && is_file(APP_ROOT . '/config/config.php')) {
            $config = require APP_ROOT . '/config/config.php';
            $appName = $config['app_name'] ?? $appName;
        }
        $bodyHtml = self::wrapInHtmlTemplate($appName, 'Panel Bildirimi', $message, $subject);
        foreach ($emails as $to) {
            $to = trim($to);
            if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            try {
                self::sendSmtp($mailSettings, $to, $subject, $message, $bodyHtml);
            } catch (Throwable $e) {
                // Süper admin e-postası hatası ana işlemi bozmasın
            }
        }
    }

    /**
     * Son e-posta gönderim kayıtlarını döner (panelde listeleme için).
     * @return array<array{date: string, to: string, subject: string, status: string, error: string}>
     */
    public static function getLogEntries(int $limit = 50): array
    {
        $dir = defined('APP_ROOT') ? (APP_ROOT . '/storage/logs') : (__DIR__ . '/../storage/logs');
        $file = $dir . '/email.log';
        if (!is_file($file)) {
            return [];
        }
        $lines = @file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return [];
        }
        $lines = array_slice(array_reverse($lines), 0, $limit);
        $out = [];
        foreach ($lines as $line) {
            $parts = explode("\t", $line, 5);
            $out[] = [
                'date' => $parts[0] ?? '',
                'to' => $parts[1] ?? '',
                'subject' => $parts[2] ?? '',
                'status' => $parts[3] ?? '',
                'error' => $parts[4] ?? '',
            ];
        }
        return $out;
    }

    /**
     * Sistem e-postaları için ortak modern HTML şablonu (inline CSS, e-posta istemcileri uyumlu).
     * İçerik düz metin verilirse güvenli şekilde HTML'e çevrilir.
     *
     * @param string $appName Uygulama/firma adı
     * @param string $title Başlık (üst bantta)
     * @param string $bodyContent Gövde metni (yeni satırlar <br> olur) veya zaten güvenli HTML
     * @param string $subtitle Opsiyonel alt başlık (üst bantta, başlığın altında)
     * @param bool $bodyIsHtml true ise bodyContent olduğu gibi kullanılır; false ise escape + nl2br
     * @return string Tam HTML e-posta gövdesi
     */
    public static function wrapInHtmlTemplate(string $appName, string $title, string $bodyContent, string $subtitle = '', bool $bodyIsHtml = false): string
    {
        $appEsc = htmlspecialchars($appName, ENT_QUOTES, 'UTF-8');
        $titleEsc = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $subtitleEsc = $subtitle !== '' ? htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8') : '';
        $bodyHtml = $bodyIsHtml ? $bodyContent : nl2br(htmlspecialchars($bodyContent, ENT_QUOTES, 'UTF-8'));

        return '<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>' . $titleEsc . '</title>
</head>
<body style="margin:0; padding:0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif; background-color: #f3f4f6;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f3f4f6;">
    <tr>
      <td style="padding: 40px 20px;">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 560px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -2px rgba(0,0,0,0.1); overflow: hidden;">
          <tr>
            <td style="padding: 32px 32px 24px; background-color: #059669; text-align: center;">
              <div style="display: inline-block; width: 56px; height: 56px; line-height: 56px; background-color: rgba(255,255,255,0.2); border-radius: 50%; margin-bottom: 16px;">
                <span style="font-size: 28px; color: #fff;">✓</span>
              </div>
              <h1 style="margin: 0; font-size: 22px; font-weight: 700; color: #ffffff; letter-spacing: -0.02em;">' . $appEsc . '</h1>
              <p style="margin: 8px 0 0; font-size: 16px; font-weight: 600; color: rgba(255,255,255,0.95);">' . $titleEsc . '</p>
              ' . ($subtitleEsc !== '' ? '<p style="margin: 4px 0 0; font-size: 14px; color: rgba(255,255,255,0.9);">' . $subtitleEsc . '</p>' : '') . '
            </td>
          </tr>
          <tr>
            <td style="padding: 32px;">
              <div style="font-size: 15px; line-height: 1.6; color: #374151;">' . $bodyHtml . '</div>
            </td>
          </tr>
          <tr>
            <td style="padding: 20px 32px 28px; border-top: 1px solid #e5e7eb;">
              <p style="margin: 0; font-size: 12px; color: #9ca3af;">Bu mesaj otomatik olarak gönderilmiştir.</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>';
    }
}
