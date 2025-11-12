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

header(header: 'Content-Type: application/json; charset=utf-8');

$router = new Router();

// Health
$router->get(pattern: '/health', handler: [HealthController::class, 'index']);

// Threads
$router->get(pattern: '/threads', handler: [ThreadController::class, 'getAll']);
$router->get(pattern: '/thread/{id}', handler: [ThreadController::class, 'getOne']);
$router->post(pattern: '/threads', handler: [ThreadController::class, 'create']);
$router->delete(pattern: '/thread/{id}', handler: [ThreadController::class, 'delete']);
$router->get(pattern: '/thread/{id}/topics', handler: [TopicController::class, 'getByThread']);

// Topics
$router->get(pattern: '/topics', handler: [TopicController::class, 'getAll']);
$router->get(pattern: '/topic/{id}', handler: [TopicController::class, 'getOne']);
$router->post(pattern: '/topics', handler: [TopicController::class, 'create']);
$router->delete(pattern: '/topic/{id}', handler: [TopicController::class, 'delete']);
$router->get(pattern: '/topic/{id}/replies', handler: [ReplyController::class, 'getByTopic']);

// Replies
$router->get(pattern: '/replies', handler: [ReplyController::class, 'getAll']);
$router->get(pattern: '/reply/{id}', handler: [ReplyController::class, 'getOne']);
$router->post(pattern: '/replies', handler: [ReplyController::class, 'create']);
$router->delete(pattern: '/reply/{id}', handler: [ReplyController::class, 'delete']);

echo $router->dispatch(method: $_SERVER['REQUEST_METHOD'], uri: $_SERVER['REQUEST_URI']);
