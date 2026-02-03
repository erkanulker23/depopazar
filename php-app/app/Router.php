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
        http_response_code(404);
        header('Content-Type: text/html; charset=utf-8');
        echo '<h1>404</h1>';
    }

    private function getRequestUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        if (($q = strpos($uri, '?')) !== false) $uri = substr($uri, 0, $q);
        return '/' . trim($uri, '/');
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
