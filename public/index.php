<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Response.php';
require_once __DIR__ . '/../src/Router.php';

use App\Response;
use App\Router;

// Всегда JSON
header('Content-Type: application/json; charset=utf-8');

// Простейший роутер
$router = new Router();

// health-check (для проверки, что сервер жив)
$router->get('/health', function () {
    return Response::json(['ok' => true, 'message' => 'API is running'], 200);
});

// заглушка threads (пока без БД)
$router->get('/threads', function () {
    return Response::json([
        'status' => 200,                // (1) обязательно в каждой ответе
        'data'   => [                   // (2) пример данных (пока фейковые)
            ['id' => 1, 'title' => 'Welcome', 'description' => 'Start here'],
            ['id' => 2, 'title' => 'Rules',   'description' => 'Be nice'],
        ],
    ], 200);
});

echo $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
