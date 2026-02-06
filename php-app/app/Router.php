<?php
final class Router
{
    private array $routes = [];

    public function get(string $path, callable $handler): self
    {
        $this->routes[] = ['GET', $path, $handler];
        return $this;
    }

    public function post(string $path, callable $handler): self
    {
        $this->routes[] = ['POST', $path, $handler];
        return $this;
    }

    public function run(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $this->getRequestUri();
        foreach ($this->routes as $route) {
            [$routeMethod, $pattern, $handler] = $route;
            if ($routeMethod !== $method) continue;
            $params = $this->match($pattern, $uri);
            if ($params !== null) {
                $handler($params);
                return;
            }
        }
        $rawUri = $_SERVER['REQUEST_URI'] ?? '';
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $this->log404($method, $uri, $rawUri, $scriptName);
        http_response_code(404);
        header('Content-Type: text/html; charset=utf-8');
        echo '<h1>404</h1><p>Sayfa bulunamadı: ' . htmlspecialchars($method . ' ' . $uri) . '</p>';
        echo '<pre style="font-size:12px;color:#666;margin-top:1rem">';
        echo 'Eşleşen URI: ' . htmlspecialchars($uri) . "\n";
        echo 'REQUEST_URI: ' . htmlspecialchars($rawUri) . "\n";
        echo 'SCRIPT_NAME: ' . htmlspecialchars($scriptName) . "\n";
        echo 'Log: php-app/storage/logs/php-errors.log' . "\n";
        echo '</pre>';
    }

    private function getRequestUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? $_SERVER['REDIRECT_URL'] ?? $_SERVER['PATH_INFO'] ?? '/';
        if (($q = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $q);
        }
        $uri = '/' . trim($uri, '/');
        if ($uri === '') $uri = '/';
        // Alt dizinde çalışıyorsa (örn. /depopazar/public/) base path'i çıkar
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath = rtrim(dirname($scriptName), '/');
        if ($basePath !== '' && $basePath !== '/' && strpos($uri, $basePath) === 0) {
            $uri = substr($uri, strlen($basePath));
            if ($uri === '' || $uri === false) $uri = '/';
        }
        return $uri;
    }

    private function log404(string $method, string $uri, string $rawUri = '', string $scriptName = ''): void
    {
        $logDir = defined('APP_ROOT') ? (APP_ROOT . '/storage/logs') : (__DIR__ . '/../../storage/logs');
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $logFile = $logDir . '/php-errors.log';
        $line = date('Y-m-d H:i:s') . ' 404 ' . $method . ' uri=' . $uri . ' REQUEST_URI=' . $rawUri . ' SCRIPT_NAME=' . $scriptName . "\n";
        @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    }

    private function match(string $pattern, string $uri): ?array
    {
        $pattern = '#^' . preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern) . '$#';
        if (!preg_match($pattern, $uri, $m)) return null;
        $params = [];
        foreach ($m as $k => $v) {
            if (!is_int($k)) $params[$k] = $v;
        }
        return $params;
    }
}
