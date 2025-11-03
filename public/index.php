<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Response.php';
require_once __DIR__ . '/../src/Router.php';
require_once __DIR__ . '/../src/Db.php';

use App\Response;
use App\Router;
use App\Db;

header('Content-Type: application/json; charset=utf-8');

function jsonInput(): array {
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

$router = new Router();

// health
$router->get('/health', function () {
    return Response::json(['ok' => true, 'message' => 'API is running'], 200);
});

// THREADS — список
$router->get('/threads', function () {
    $pdo = Db::pdo();
    $stmt = $pdo->query("SELECT t.id, t.title, t.description, t.created_at, t.updated_at,
                                u.id AS user_id, u.username
                         FROM threads t
                         JOIN users u ON u.id = t.user_id
                         ORDER BY t.id DESC");
    $rows = $stmt->fetchAll();
    return Response::json(['status' => 200, 'data' => $rows], 200);
});

// THREAD — один по id
$router->get('/thread/{id}', function (array $params) {
    $id = (int)($params['id'] ?? 0);
    if ($id <= 0) return Response::json(['error' => 'Invalid id'], 400);

    $pdo = Db::pdo();
    $st = $pdo->prepare("SELECT t.id, t.title, t.description, t.created_at, t.updated_at,
                                u.id AS user_id, u.username
                         FROM threads t
                         JOIN users u ON u.id = t.user_id
                         WHERE t.id = :id");
    $st->execute([':id' => $id]);
    $row = $st->fetch();
    if (!$row) return Response::json(['error' => 'Thread not found'], 404);

    return Response::json(['status' => 200, 'data' => $row], 200);
});

// THREAD — создание (POST /threads)
$router->post('/threads', function () {
    $body = jsonInput();
    $title = trim($body['title'] ?? '');
    $description = trim($body['description'] ?? '');
    $user_id = (int)($body['user_id'] ?? 0);

    if ($title === '' || $user_id <= 0) {
        return Response::json(['error' => 'title and user_id are required'], 422);
    }

    $pdo = Db::pdo();
    $st = $pdo->prepare("INSERT INTO threads (user_id, title, description) VALUES (:uid, :title, :desc)");
    $st->execute([':uid' => $user_id, ':title' => $title, ':desc' => $description ?: null]);

    $id = (int)$pdo->lastInsertId();
    return Response::json(['status' => 201, 'data' => ['id' => $id]], 201);
});

// THREAD — удаление (DELETE /thread/{id}) (каскадом удалит topics/replies)
$router->delete('/thread/{id}', function (array $params) {
    $id = (int)($params['id'] ?? 0);
    if ($id <= 0) return Response::json(['error' => 'Invalid id'], 400);

    $pdo = Db::pdo();
    $st = $pdo->prepare("DELETE FROM threads WHERE id = :id");
    $st->execute([':id' => $id]);

    if ($st->rowCount() === 0) {
        return Response::json(['error' => 'Thread not found'], 404);
    }
    return Response::json(['status' => 200, 'data' => ['deleted' => $id]], 200);
});

echo $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
