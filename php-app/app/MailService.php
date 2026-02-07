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
            return ['success' => true, 'error' => null];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
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
