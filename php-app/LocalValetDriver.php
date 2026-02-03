<?php
/**
 * Valet driver: Tüm istekleri public/index.php'ye yönlendirir.
 * Link: php-app/public -> depotakip-v1.test
 */
class LocalValetDriver extends \Laravel\Valet\Drivers\ValetDriver
{
    public function serves(string $sitePath, string $siteName, string $uri): bool
    {
        return file_exists($sitePath . '/public/index.php');
    }

    public function frontControllerPath(string $sitePath, string $siteName, string $uri): ?string
    {
        return $sitePath . '/public/index.php';
    }
}
