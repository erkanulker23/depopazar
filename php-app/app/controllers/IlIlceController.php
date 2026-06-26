<?php
/**
 * Türkiye il/ilçe verisi - api.turkiyeapi.dev üzerinden önbelleklenir.
 * GET /api/iller -> { "data": [ {"id": 1, "name": "Adana"}, ... ] }
 * GET /api/ilceler?il_id=34 -> { "data": [ {"id": 123, "name": "Kadıköy"}, ... ] }
 */
class IlIlceController
{
    private const CACHE_FILE = 'storage/il-ilce.json';
    private const BUNDLE_FILE = 'data/turkiye-il-ilce.json';
    private const CACHE_TTL = 86400 * 7; // 7 gün

    public static function getProvinces(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $data = self::loadCache();
        if (empty($data)) {
            echo json_encode(['data' => []]);
            return;
        }
        $list = array_map(static function ($p) {
            return ['id' => (int) $p['id'], 'name' => $p['name']];
        }, $data);
        usort($list, static fn($a, $b) => strcoll($a['name'], $b['name']));
        echo json_encode(['data' => $list], JSON_UNESCAPED_UNICODE);
    }

    public static function getDistricts(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $ilId = isset($_GET['il_id']) ? (int) $_GET['il_id'] : 0;
        if ($ilId <= 0) {
            echo json_encode(['data' => []]);
            return;
        }
        $data = self::loadCache();
        foreach ($data as $p) {
            if ((int) $p['id'] === $ilId && !empty($p['districts'])) {
                $list = array_map(static function ($d) {
                    return ['id' => (int) $d['id'], 'name' => $d['name']];
                }, $p['districts']);
                usort($list, static fn($a, $b) => strcoll($a['name'], $b['name']));
                echo json_encode(['data' => $list], JSON_UNESCAPED_UNICODE);
                return;
            }
        }
        echo json_encode(['data' => []]);
    }

    private static function appRoot(): string
    {
        return defined('APP_ROOT') ? APP_ROOT : dirname(__DIR__, 2);
    }

    private static function loadCache(): array
    {
        $root = self::appRoot();
        $path = $root . '/' . self::CACHE_FILE;
        if (is_file($path) && (time() - filemtime($path)) < self::CACHE_TTL) {
            $dec = self::decodeJsonFile($path);
            if ($dec !== []) {
                return $dec;
            }
        }
        $fetched = self::fetchFromApi();
        if ($fetched !== []) {
            $dir = dirname($path);
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            @file_put_contents($path, json_encode($fetched, JSON_UNESCAPED_UNICODE));
            return $fetched;
        }
        if (is_file($path)) {
            $dec = self::decodeJsonFile($path);
            if ($dec !== []) {
                return $dec;
            }
        }
        $bundlePath = $root . '/' . self::BUNDLE_FILE;
        return self::decodeJsonFile($bundlePath);
    }

    /** @return list<array<string, mixed>> */
    private static function decodeJsonFile(string $path): array
    {
        if (!is_file($path)) {
            return [];
        }
        $raw = @file_get_contents($path);
        if ($raw === false) {
            return [];
        }
        $dec = json_decode($raw, true);
        if (!is_array($dec)) {
            return [];
        }
        if (isset($dec['data']) && is_array($dec['data'])) {
            return $dec['data'];
        }
        return $dec;
    }

    private static function fetchFromApi(): array
    {
        $url = 'https://api.turkiyeapi.dev/v1/provinces?limit=100&sort=name';
        $ctx = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'DepoPazar/1.0',
            ],
        ]);
        $raw = @file_get_contents($url, false, $ctx);
        if ($raw === false) {
            return [];
        }
        $json = json_decode($raw, true);
        if (empty($json['data']) || !is_array($json['data'])) {
            return [];
        }
        return $json['data'];
    }
}
