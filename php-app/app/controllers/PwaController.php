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
                ['src' => '/pwa-icon/72', 'sizes' => '72x72', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/pwa-icon/96', 'sizes' => '96x96', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/pwa-icon/128', 'sizes' => '128x128', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/pwa-icon/144', 'sizes' => '144x144', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/pwa-icon/152', 'sizes' => '152x152', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/pwa-icon/180', 'sizes' => '180x180', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/pwa-icon/192', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/pwa-icon/512', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/pwa-icon/512', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
        ];

        header('Content-Type: application/manifest+json; charset=utf-8');
        header('Cache-Control: public, max-age=3600');
        echo json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /** PWA ikonu (PNG) */
    public function icon(array $params): void
    {
        $size = max(48, min(512, (int) ($params['size'] ?? 192)));
        header('Content-Type: image/png');
        header('Cache-Control: public, max-age=86400');

        if (!function_exists('imagecreatetruecolor')) {
            header('Location: /favicon.ico');
            exit;
        }

        $img = imagecreatetruecolor($size, $size);
        if (!$img) {
            header('Location: /favicon.ico');
            exit;
        }

        imagesavealpha($img, true);
        $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
        imagefill($img, 0, 0, $transparent);

        $bg = imagecolorallocate($img, 5, 150, 105);
        $radius = (int) round($size * 0.22);
        $this->fillRoundedRect($img, 0, 0, $size - 1, $size - 1, $radius, $bg);

        $white = imagecolorallocate($img, 255, 255, 255);
        $cx = (int) ($size / 2);
        $cy = (int) ($size / 2);
        $box = (int) round($size * 0.38);
        $half = (int) ($box / 2);
        imagefilledrectangle($img, $cx - $half, $cy - $half, $cx + $half, $cy + $half, $white);
        $inner = imagecolorallocate($img, 5, 150, 105);
        $inset = (int) round($size * 0.08);
        imagefilledrectangle($img, $cx - $half + $inset, $cy - $half + $inset, $cx + $half - $inset, $cy + $half - $inset, $inner);

        imagepng($img);
        imagedestroy($img);
    }

    private function fillRoundedRect($img, int $x1, int $y1, int $x2, int $y2, int $radius, int $color): void
    {
        imagefilledrectangle($img, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
        imagefilledrectangle($img, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);
        imagefilledellipse($img, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($img, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($img, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($img, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
    }
}
