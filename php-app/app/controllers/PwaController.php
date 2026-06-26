<?php
class PwaController
{
    /** Web App Manifest (telefona yükleme) */
    public function manifest(): void
    {
        $config = require __DIR__ . '/../../config/config.php';
        $name = $_SESSION['company_project_name'] ?? $config['app_name'] ?? 'DepoPazar';
        $short = mb_strlen($name) > 12 ? mb_substr($name, 0, 12) : $name;

        $manifest = [
            'name' => $name,
            'short_name' => $short,
            'description' => 'Depo ve nakliye yönetim paneli',
            'start_url' => '/genel-bakis',
            'scope' => '/',
            'display' => 'standalone',
            'orientation' => 'portrait-primary',
            'background_color' => '#1a1614',
            'theme_color' => '#059669',
            'lang' => 'tr',
            'dir' => 'ltr',
            'categories' => ['business', 'productivity'],
            'icons' => [
                ['src' => '/icons/icon-72.png', 'sizes' => '72x72', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/icons/icon-96.png', 'sizes' => '96x96', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/icons/icon-128.png', 'sizes' => '128x128', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/icons/icon-144.png', 'sizes' => '144x144', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/icons/icon-152.png', 'sizes' => '152x152', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/icons/icon-180.png', 'sizes' => '180x180', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/icons/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/icons/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/icons/icon-512-maskable.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
        ];

        header('Content-Type: application/manifest+json; charset=utf-8');
        header('Cache-Control: public, max-age=3600');
        echo json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /** PWA ikonu (PNG) — statik dosyadan veya en yakın boyuttan */
    public function icon(array $params): void
    {
        $size = max(48, min(512, (int) ($params['size'] ?? 192)));
        $allowed = [72, 96, 128, 144, 152, 180, 192, 512];
        if (!in_array($size, $allowed, true)) {
            $size = 192;
        }
        $path = __DIR__ . '/../../public/icons/icon-' . $size . '.png';
        if (!is_readable($path)) {
            $path = __DIR__ . '/../../public/icons/icon-192.png';
        }
        if (!is_readable($path)) {
            header('Location: /favicon.ico');
            exit;
        }
        header('Content-Type: image/png');
        header('Cache-Control: public, max-age=86400');
        readfile($path);
        exit;
    }
}
