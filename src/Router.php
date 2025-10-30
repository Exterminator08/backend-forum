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

    public function get(string $path, callable $handler): void    { $this->routes['GET'][$path] = $handler; }
    public function post(string $path, callable $handler): void   { $this->routes['POST'][$path] = $handler; }
    public function delete(string $path, callable $handler): void { $this->routes['DELETE'][$path] = $handler; }

    public function dispatch(string $method, string $uri): string
    {
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';
        // точное совпадение маршрута (чуть позже сделаем параметры /thread/{id})
        $handler = $this->routes[$method][$path] ?? null;

        if ($handler === null) {
            return Response::json(['error' => 'Not Found'], 404);
        }
        return $handler();
    }
}
