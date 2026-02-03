<?php
/**
 * Valet driver: Tüm istekleri index.php'ye yönlendirir (front controller).
 * Link: cd php-app/public && valet link depotakip-v1
 */
class LocalValetDriver extends \Valet\Drivers\BasicValetDriver
{
    public function serves(string $sitePath, string $siteName, string $uri): bool
    {
        return file_exists($sitePath . '/index.php');
    }

    public function frontControllerPath(string $sitePath, string $siteName, string $uri): ?string
    {
        return $sitePath . '/index.php';
    }
}
