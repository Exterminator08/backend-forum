<?php
namespace App\Core;

use App\Core\Response;

final class Router {
    private array $routes = [
        'GET'    => [],
        'POST'   => [],
        'DELETE' => [],
    ];

    public function get(string $pattern, callable|array $handler): void    { $this->routes['GET'][]    = [$pattern, $handler]; }
    public function post(string $pattern, callable|array $handler): void   { $this->routes['POST'][]   = [$pattern, $handler]; }
    public function delete(string $pattern, callable|array $handler): void { $this->routes['DELETE'][] = [$pattern, $handler]; }

    public function dispatch(string $method, string $uri): string {
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';
        foreach ($this->routes[$method] ?? [] as [$pattern, $handler]) {
            $regex = $this->toRegex($pattern, $paramNames);
            if (preg_match($regex, $path, $matches)) {
                $params = [];
                foreach ($paramNames as $name) {
                    $params[$name] = $matches[$name] ?? null;
                }
                if (is_array($handler) && count($handler) === 2) {
                    [$class, $method] = $handler;
                    return $class::$method($params);
                }
                return $handler($params);
            }
        }
        return Response::json(['error' => 'Not Found'], 404);
    }

    private function toRegex(string $pattern, ?array &$paramNames = []): string {
        $paramNames = [];
        $regex = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', function ($m) use (&$paramNames) {
            $paramNames[] = $m[1];
            return '(?P<' . $m[1] . '>[^/]+)';
        }, $pattern);
        return '#^' . $regex . '$#';
    }
}
