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
     * Bildirim alan kullanıcılara (personel + süper admin) e-posta gönderir.
     *
     * @param array{actor_name?: string, acted_at?: string|int, action_title?: string}|null $context
     */
    public static function sendToUsers(PDO $pdo, ?string $companyId, array $userIds, string $subject, string $message, ?array $context = null): void
    {
        $userIds = array_values(array_unique(array_filter($userIds)));
        if ($userIds === []) {
            return;
        }
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $stmt = $pdo->prepare(
            "SELECT DISTINCT email FROM users WHERE id IN ($placeholders) AND deleted_at IS NULL AND email != '' AND receive_email_notifications = 1"
        );
        $stmt->execute($userIds);
        $emails = [];
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $email) {
            $email = trim((string) $email);
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $emails[] = $email;
            }
        }
        $emails = array_values(array_unique($emails));
        if ($emails === []) {
            return;
        }

        $mailSettings = self::resolveMailSettings($pdo, $companyId);
        if ($mailSettings === null) {
            return;
        }

        $appName = self::appName();
        $meta = self::normalizeActionContext($context, $subject);
        $emailSubject = self::formatActorSubject($meta);
        $prepared = self::prepareEmailBodies($appName, 'Panel Bildirimi', $message, $emailSubject, $context);
        foreach ($emails as $to) {
            try {
                self::sendSmtp($mailSettings, $to, $emailSubject, $prepared['plain'], $prepared['html']);
            } catch (Throwable $e) {
                error_log('sendToUsers email error: ' . $e->getMessage());
            }
        }
    }

    /**
     * Şablonlu e-posta gönderir (işlem yapan + tarih/saat meta bloğu ile).
     *
     * @param array{actor_name?: string, acted_at?: string|int, action_title?: string}|null $context
     * @return array{success: bool, error: string|null}
     */
    public static function sendTemplated(
        array $mailSettings,
        string $to,
        string $subject,
        string $headerTitle,
        string $body,
        string $subtitle = '',
        ?array $context = null,
        bool $bodyIsHtml = false
    ): array {
        $meta = self::normalizeActionContext($context, $headerTitle);
        if ($context !== null) {
            $actorSubject = self::formatActorSubject($meta);
            $emailSubject = str_contains($subject, ' – ')
                ? explode(' – ', $subject, 2)[0] . ' – ' . $actorSubject
                : $actorSubject;
        } else {
            $emailSubject = $subject;
        }
        $prepared = self::prepareEmailBodies(self::appName(), $headerTitle, $body, $subtitle !== '' ? $subtitle : $emailSubject, $context, $bodyIsHtml);
        return self::sendSmtp($mailSettings, $to, $emailSubject, $prepared['plain'], $prepared['html']);
    }

    /**
     * @param array{actor_name?: string, acted_at?: string|int, action_title?: string}|null $context
     * @return array{plain: string, html: string}
     */
    public static function prepareEmailBodies(
        string $appName,
        string $headerTitle,
        string $body,
        string $subtitle = '',
        ?array $context = null,
        bool $bodyIsHtml = false
    ): array {
        $meta = self::normalizeActionContext($context, $subtitle !== '' ? $subtitle : $headerTitle);
        $headerSubtitle = $subtitle !== '' ? $subtitle : self::formatActorSubject($meta);
        $plain = self::appendActionMetaPlain($body, $meta);
        $html = self::wrapInHtmlTemplate($appName, $headerTitle, $body, $headerSubtitle, $bodyIsHtml, $meta);
        return ['plain' => $plain, 'html' => $html];
    }

    /**
     * @param array{actor_name?: string, acted_at?: string|int, action_title?: string}|null $context
     * @return array{actor_name: string, action_title: string, date: string, time: string, datetime: string}
     */
    public static function normalizeActionContext(?array $context, string $fallbackTitle = ''): array
    {
        $context = $context ?? [];
        $actor = trim((string) ($context['actor_name'] ?? ''));
        if ($actor === '' && class_exists('Auth', false)) {
            try {
                if (Auth::isAuthenticated()) {
                    $u = Auth::user();
                    $actor = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
                }
            } catch (Throwable $e) {
                // oturum yoksa sessizce devam
            }
        }
        if ($actor === '') {
            $actor = 'Sistem';
        }

        $actedAt = $context['acted_at'] ?? null;
        $ts = ($actedAt !== null && $actedAt !== '') ? strtotime((string) $actedAt) : time();
        if ($ts === false) {
            $ts = time();
        }

        $actionTitle = trim((string) ($context['action_title'] ?? $fallbackTitle));
        if ($actionTitle === '') {
            $actionTitle = 'Bildirim';
        }

        return [
            'actor_name' => $actor,
            'action_title' => $actionTitle,
            'date' => date('d.m.Y', $ts),
            'time' => date('H:i', $ts),
            'datetime' => date('d.m.Y H:i', $ts),
        ];
    }

    /**
     * E-posta konusu: "Erkan Ülker Kredi Kartını Güncelledi"
     *
     * @param array{actor_name: string, action_title: string} $meta
     */
    public static function formatActorSubject(array $meta): string
    {
        $actor = trim($meta['actor_name'] ?? '');
        $action = trim($meta['action_title'] ?? '');
        if ($action === '') {
            return $actor !== '' ? $actor : 'Bildirim';
        }
        if ($actor === '' || $actor === 'Sistem') {
            return self::titleCasePhrase($action);
        }
        if ($actor === 'Otomatik hatırlatma') {
            return 'Otomatik Hatırlatma: ' . self::titleCasePhrase($action);
        }
        return $actor . ' ' . self::actionTitleToActivePhrase($action);
    }

    private static function actionTitleToActivePhrase(string $title): string
    {
        $title = trim($title);
        $rules = [
            '/^(.+?)\s+güncellendi$/iu' => ['Güncelledi', true],
            '/^(.+?)\s+silindi$/iu' => ['Sildi', true],
            '/^(.+?)\s+eklendi$/iu' => ['Ekledi', false],
            '/^(.+?)\s+oluşturuldu$/iu' => ['Oluşturdu', false],
            '/^(.+?)\s+kaydedildi$/iu' => ['Kaydetti', false],
            '/^(.+?)\s+alındı$/iu' => ['Aldı', true],
        ];
        foreach ($rules as $pattern => [$verb, $accusative]) {
            if (preg_match($pattern, $title, $m)) {
                $object = trim($m[1]);
                $phrase = $accusative ? self::accusativePhrase($object) : self::titleCasePhrase($object);
                return $phrase . ' ' . $verb;
            }
        }
        return self::titleCasePhrase($title);
    }

    private static function titleCasePhrase(string $phrase): string
    {
        $words = preg_split('/\s+/u', trim($phrase), -1, PREG_SPLIT_NO_EMPTY);
        if ($words === false || $words === []) {
            return trim($phrase);
        }
        return implode(' ', array_map(
            static fn(string $w): string => mb_convert_case($w, MB_CASE_TITLE, 'UTF-8'),
            $words
        ));
    }

    private static function accusativePhrase(string $phrase): string
    {
        $words = preg_split('/\s+/u', trim($phrase), -1, PREG_SPLIT_NO_EMPTY);
        if ($words === false || $words === []) {
            return self::titleCasePhrase($phrase);
        }
        $last = array_pop($words);
        $last = self::toAccusativeWord($last);
        if ($words !== []) {
            $words = array_map(
                static fn(string $w): string => mb_convert_case($w, MB_CASE_TITLE, 'UTF-8'),
                $words
            );
            return implode(' ', $words) . ' ' . $last;
        }
        return $last;
    }

    private static function toAccusativeWord(string $word): string
    {
        $lower = mb_strtolower($word, 'UTF-8');
        $rules = [
            '/^(.*)leri$/u' => '$1lerini',
            '/^(.*)ları$/u' => '$1larını',
            '/^(.*)si$/u' => '$1sini',
            '/^(.*)ı$/u' => '$1ını',
            '/^(.*)i$/u' => '$1ini',
            '/^(.*)u$/u' => '$1unu',
            '/^(.*)ü$/u' => '$1ünü',
            '/^(.*)a$/u' => '$1yı',
            '/^(.*)e$/u' => '$1yi',
        ];
        foreach ($rules as $pattern => $replacement) {
            if (preg_match($pattern, $lower)) {
                $acc = preg_replace($pattern, $replacement, $lower);
                return mb_convert_case($acc, MB_CASE_TITLE, 'UTF-8');
            }
        }
        return mb_convert_case($word, MB_CASE_TITLE, 'UTF-8');
    }

    private static function actorInitials(string $name): string
    {
        $parts = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY);
        if ($parts === false || $parts === []) {
            return '?';
        }
        $first = mb_substr($parts[0], 0, 1, 'UTF-8');
        $second = count($parts) > 1 ? mb_substr($parts[count($parts) - 1], 0, 1, 'UTF-8') : '';
        return mb_strtoupper($first . $second, 'UTF-8');
    }

  /** @param array{actor_name: string, action_title: string, date: string, time: string, datetime: string} $meta */
    public static function appendActionMetaPlain(string $body, array $meta): string
    {
        return rtrim($body) . "\n\n"
            . "—\n"
            . 'İşlem: ' . $meta['action_title'] . "\n"
            . 'İşlemi yapan: ' . $meta['actor_name'] . "\n"
            . 'Tarih: ' . $meta['date'] . "\n"
            . 'Saat: ' . $meta['time'];
    }

    /** @param array{actor_name: string, action_title: string, date: string, time: string, datetime: string} $meta */
    public static function renderActionMetaHtml(array $meta): string
    {
        $actor = htmlspecialchars($meta['actor_name'], ENT_QUOTES, 'UTF-8');
        $action = htmlspecialchars($meta['action_title'], ENT_QUOTES, 'UTF-8');
        $date = htmlspecialchars($meta['date'], ENT_QUOTES, 'UTF-8');
        $time = htmlspecialchars($meta['time'], ENT_QUOTES, 'UTF-8');
        $initials = htmlspecialchars(self::actorInitials($meta['actor_name']), ENT_QUOTES, 'UTF-8');
        $subjectLine = htmlspecialchars(self::formatActorSubject($meta), ENT_QUOTES, 'UTF-8');

        return '<div style="margin-top:28px;border-radius:16px;overflow:hidden;border:1px solid #d1fae5;box-shadow:0 1px 3px rgba(0,0,0,0.06);">'
            . '<div style="padding:18px 20px;background:linear-gradient(135deg,#ecfdf5 0%,#f0fdf4 100%);border-bottom:1px solid #d1fae5;">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0"><tr>'
            . '<td style="width:52px;vertical-align:middle;">'
            . '<div style="width:44px;height:44px;line-height:44px;border-radius:50%;background-color:#059669;color:#ffffff;font-size:15px;font-weight:700;text-align:center;">' . $initials . '</div>'
            . '</td>'
            . '<td style="vertical-align:middle;padding-left:12px;">'
            . '<p style="margin:0;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:#047857;">İşlemi yapan</p>'
            . '<p style="margin:4px 0 0;font-size:17px;font-weight:700;color:#064e3b;line-height:1.3;">' . $actor . '</p>'
            . '</td></tr></table>'
            . '</div>'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#ffffff;">'
            . '<tr>'
            . '<td style="width:50%;padding:16px 20px;border-right:1px solid #f3f4f6;vertical-align:top;">'
            . '<p style="margin:0 0 4px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:#9ca3af;">Tarih</p>'
            . '<p style="margin:0;font-size:16px;font-weight:700;color:#111827;">' . $date . '</p>'
            . '</td>'
            . '<td style="width:50%;padding:16px 20px;vertical-align:top;">'
            . '<p style="margin:0 0 4px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:#9ca3af;">Saat</p>'
            . '<p style="margin:0;font-size:16px;font-weight:700;color:#111827;">' . $time . '</p>'
            . '</td>'
            . '</tr>'
            . '<tr>'
            . '<td colspan="2" style="padding:16px 20px;border-top:1px solid #f3f4f6;background-color:#fafafa;vertical-align:top;">'
            . '<p style="margin:0 0 4px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:#9ca3af;">İşlem</p>'
            . '<p style="margin:0;font-size:15px;font-weight:600;color:#374151;line-height:1.5;">' . $action . '</p>'
            . '<p style="margin:10px 0 0;padding:10px 12px;background-color:#ecfdf5;border-radius:10px;font-size:14px;font-weight:600;color:#047857;line-height:1.4;">' . $subjectLine . '</p>'
            . '</td>'
            . '</tr>'
            . '</table>'
            . '</div>';
    }

    private static function appName(): string
    {
        $appName = 'DepoPazar';
        if (defined('APP_ROOT') && is_file(APP_ROOT . '/config/config.php')) {
            $config = require APP_ROOT . '/config/config.php';
            $appName = $config['app_name'] ?? $appName;
        }
        return $appName;
    }

    private static function resolveMailSettings(PDO $pdo, ?string $companyId): ?array
    {
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
            return null;
        }
        return $mailSettings;
    }

    /**
     * Tüm süper admin kullanıcılara aksiyon bildirimi e-postası gönderir.
     * Ayarlar > E-posta ayarları (company_mail_settings) kullanılır; companyId yoksa e-postası tanımlı ilk şirket kullanılır.
     */
    public static function sendToSuperAdmins(PDO $pdo, ?string $companyId, string $subject, string $message, ?array $context = null): void
    {
        $stmt = $pdo->query("SELECT id FROM users WHERE role = 'super_admin' AND deleted_at IS NULL AND email != '' AND receive_email_notifications = 1");
        $userIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if ($userIds === []) {
            return;
        }
        self::sendToUsers($pdo, $companyId, $userIds, $subject, $message, $context);
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
     * @param array{actor_name?: string, action_title?: string, date?: string, time?: string}|null $actionMeta
     * @return string Tam HTML e-posta gövdesi
     */
    public static function wrapInHtmlTemplate(
        string $appName,
        string $title,
        string $bodyContent,
        string $subtitle = '',
        bool $bodyIsHtml = false,
        ?array $actionMeta = null
    ): string {
        $appEsc = htmlspecialchars($appName, ENT_QUOTES, 'UTF-8');
        $titleEsc = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $subtitleEsc = $subtitle !== '' ? htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8') : '';
        $bodyHtml = $bodyIsHtml ? $bodyContent : nl2br(htmlspecialchars($bodyContent, ENT_QUOTES, 'UTF-8'));
        $metaHtml = ($actionMeta !== null && $actionMeta !== []) ? self::renderActionMetaHtml($actionMeta) : '';

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
              ' . $metaHtml . '
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
