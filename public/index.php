<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Core/Response.php';
require_once __DIR__ . '/../src/Core/Router.php';
require_once __DIR__ . '/../src/Core/Db.php';
require_once __DIR__ . '/../src/helpers.php';

require_once __DIR__ . '/../src/Controllers/HealthController.php';
require_once __DIR__ . '/../src/Controllers/ThreadController.php';
require_once __DIR__ . '/../src/Controllers/TopicController.php';
require_once __DIR__ . '/../src/Controllers/ReplyController.php';

use App\Core\Router;
use App\Controllers\HealthController;
use App\Controllers\ThreadController;
use App\Controllers\TopicController;
use App\Controllers\ReplyController;

header('Content-Type: application/json; charset=utf-8');

$router = new Router();

// Health
$router->get('/health', [HealthController::class, 'index']);

// Threads
$router->get('/threads', [ThreadController::class, 'getAll']);
$router->get('/thread/{id}', [ThreadController::class, 'getOne']);
$router->post('/threads', [ThreadController::class, 'create']);
$router->delete('/thread/{id}', [ThreadController::class, 'delete']);
$router->get('/thread/{id}/topics', [TopicController::class, 'getByThread']);

// Topics
$router->get('/topics', [TopicController::class, 'getAll']);
$router->get('/topic/{id}', [TopicController::class, 'getOne']);
$router->post('/topics', [TopicController::class, 'create']);
$router->delete('/topic/{id}', [TopicController::class, 'delete']);
$router->get('/topic/{id}/replies', [ReplyController::class, 'getByTopic']);

// Replies
$router->get('/replies', [ReplyController::class, 'getAll']);
$router->get('/reply/{id}', [ReplyController::class, 'getOne']);
$router->post('/replies', [ReplyController::class, 'create']);
$router->delete('/reply/{id}', [ReplyController::class, 'delete']);

echo $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
