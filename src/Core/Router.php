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
        $path = parse_url(url: $uri, component: PHP_URL_PATH) ?? '/';
        foreach ($this->routes[$method] ?? [] as [$pattern, $handler]) {
            $regex = $this->toRegex(pattern: $pattern, paramNames: $paramNames);
            if (preg_match(pattern: $regex, subject: $path, matches: $matches)) {
                $params = [];
                foreach ($paramNames as $name) {
                    $params[$name] = $matches[$name] ?? null;
                }
                if (is_array(value: $handler) && count(value: $handler) === 2) {
                    [$class, $method] = $handler;
                    return $class::$method($params);
                }
                return $handler($params);
            }
        }
        return Response::json(payload: ['error' => 'Not Found'], status: 404);
    }

    private function toRegex(string $pattern, ?array &$paramNames = []): string {
        $paramNames = [];
        $regex = preg_replace_callback(pattern: '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', callback: function ($m) use (&$paramNames): string {
            $paramNames[] = $m[1];
            return '(?P<' . $m[1] . '>[^/]+)';
        }, subject: $pattern);
        return '#^' . $regex . '$#';
    }
}
