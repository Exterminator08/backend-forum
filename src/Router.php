<?php
declare(strict_types=1);
namespace App;

final class Router
{
    private array $routes = [
        'GET'    => [],
        'POST'   => [],
        'DELETE' => [],
    ];

    public function get(string $pattern, callable $handler): void    { $this->routes['GET'][]    = [$pattern, $handler]; }
    public function post(string $pattern, callable $handler): void   { $this->routes['POST'][]   = [$pattern, $handler]; }
    public function delete(string $pattern, callable $handler): void { $this->routes['DELETE'][] = [$pattern, $handler]; }

    public function dispatch(string $method, string $uri): string
    {
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';
        foreach ($this->routes[$method] ?? [] as [$pattern, $handler]) {
            $regex = $this->toRegex($pattern, $paramNames);
            if (preg_match($regex, $path, $matches)) {
                $params = [];
                foreach ($paramNames as $name) {
                    $params[$name] = $matches[$name] ?? null;
                }
                return $handler($params);
            }
        }
        return Response::json(['error' => 'Not Found'], 404);
    }

    private function toRegex(string $pattern, ?array &$paramNames = []): string
    {
        // /thread/{id} -> #^/thread/(?P<id>[^/]+)$# 
        $paramNames = [];
        $regex = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', function ($m) use (&$paramNames) {
            $paramNames[] = $m[1];
            return '(?P<' . $m[1] . '>[^/]+)';
        }, $pattern);

        return '#^' . $regex . '$#';
    }
}
