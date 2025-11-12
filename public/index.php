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


// =============================
// TOPICS
// =============================

// Все topics (не вложенно)
$router->get('/topics', function () {
    $pdo = Db::pdo();
    $st = $pdo->query("
        SELECT t.id, t.thread_id, t.user_id, t.title, t.body, t.created_at, t.updated_at,
               u.username, th.title AS thread_title
        FROM topics t
        JOIN users u ON u.id = t.user_id
        JOIN threads th ON th.id = t.thread_id
        ORDER BY t.id DESC
    ");
    return Response::json(['status' => 200, 'data' => $st->fetchAll()], 200);
});

// Один topic по id
$router->get('/topic/{id}', function (array $params) {
    $id = (int)($params['id'] ?? 0);
    if ($id <= 0) return Response::json(['error' => 'Invalid id'], 400);

    $pdo = Db::pdo();
    $st = $pdo->prepare("
        SELECT t.id, t.thread_id, t.user_id, t.title, t.body, t.created_at, t.updated_at,
               u.username, th.title AS thread_title
        FROM topics t
        JOIN users u ON u.id = t.user_id
        JOIN threads th ON th.id = t.thread_id
        WHERE t.id = :id
    ");
    $st->execute([':id' => $id]);
    $row = $st->fetch();
    if (!$row) return Response::json(['error' => 'Topic not found'], 404);

    return Response::json(['status' => 200, 'data' => $row], 200);
});

// Создать topic (POST /topics)
$router->post('/topics', function () {
    $body = jsonInput();
    $thread_id = (int)($body['thread_id'] ?? 0);
    $user_id   = (int)($body['user_id'] ?? 0);
    $title     = trim($body['title'] ?? '');
    $text      = trim($body['body'] ?? '');

    if ($thread_id <= 0 || $user_id <= 0 || $title === '' || $text === '') {
        return Response::json(['error' => 'thread_id, user_id, title, body are required'], 422);
    }

    $pdo = Db::pdo();
    // проверим, что thread существует
    $chk = $pdo->prepare("SELECT id FROM threads WHERE id = :id");
    $chk->execute([':id' => $thread_id]);
    if (!$chk->fetch()) return Response::json(['error' => 'Thread not found'], 404);

    $st = $pdo->prepare("
        INSERT INTO topics (thread_id, user_id, title, body)
        VALUES (:th, :uid, :title, :body)
    ");
    $st->execute([':th' => $thread_id, ':uid' => $user_id, ':title' => $title, ':body' => $text]);
    $id = (int)$pdo->lastInsertId();

    return Response::json(['status' => 201, 'data' => ['id' => $id]], 201);
});

// Удалить topic (DELETE /topic/{id})
$router->delete('/topic/{id}', function (array $params) {
    $id = (int)($params['id'] ?? 0);
    if ($id <= 0) return Response::json(['error' => 'Invalid id'], 400);

    $pdo = Db::pdo();
    $st = $pdo->prepare("DELETE FROM topics WHERE id = :id");
    $st->execute([':id' => $id]);
    if ($st->rowCount() === 0) return Response::json(['error' => 'Topic not found'], 404);

    // каскад удалит replies
    return Response::json(['status' => 200, 'data' => ['deleted' => $id]], 200);
});

// =============================
// REPLIES
// =============================

// Все replies
$router->get('/replies', function () {
    $pdo = Db::pdo();
    $st = $pdo->query("
        SELECT r.id, r.topic_id, r.user_id, r.body, r.created_at, r.updated_at,
               u.username, tp.title AS topic_title
        FROM replies r
        JOIN users u ON u.id = r.user_id
        JOIN topics tp ON tp.id = r.topic_id
        ORDER BY r.id DESC
    ");
    return Response::json(['status' => 200, 'data' => $st->fetchAll()], 200);
});

// Одна reply
$router->get('/reply/{id}', function (array $params) {
    $id = (int)($params['id'] ?? 0);
    if ($id <= 0) return Response::json(['error' => 'Invalid id'], 400);

    $pdo = Db::pdo();
    $st = $pdo->prepare("
        SELECT r.id, r.topic_id, r.user_id, r.body, r.created_at, r.updated_at,
               u.username, tp.title AS topic_title
        FROM replies r
        JOIN users u ON u.id = r.user_id
        JOIN topics tp ON tp.id = r.topic_id
        WHERE r.id = :id
    ");
    $st->execute([':id' => $id]);
    $row = $st->fetch();
    if (!$row) return Response::json(['error' => 'Reply not found'], 404);

    return Response::json(['status' => 200, 'data' => $row], 200);
});

// Создать reply (POST /replies)
$router->post('/replies', function () {
    $body = jsonInput();
    $topic_id = (int)($body['topic_id'] ?? 0);
    $user_id  = (int)($body['user_id'] ?? 0);
    $text     = trim($body['body'] ?? '');

    if ($topic_id <= 0 || $user_id <= 0 || $text === '') {
        return Response::json(['error' => 'topic_id, user_id, body are required'], 422);
    }

    $pdo = Db::pdo();
    // проверим, что topic существует
    $chk = $pdo->prepare("SELECT id FROM topics WHERE id = :id");
    $chk->execute([':id' => $topic_id]);
    if (!$chk->fetch()) return Response::json(['error' => 'Topic not found'], 404);

    $st = $pdo->prepare("
        INSERT INTO replies (topic_id, user_id, body)
        VALUES (:tp, :uid, :body)
    ");
    $st->execute([':tp' => $topic_id, ':uid' => $user_id, ':body' => $text]);
    $id = (int)$pdo->lastInsertId();

    return Response::json(['status' => 201, 'data' => ['id' => $id]], 201);
});

// Удалить reply
$router->delete('/reply/{id}', function (array $params) {
    $id = (int)($params['id'] ?? 0);
    if ($id <= 0) return Response::json(['error' => 'Invalid id'], 400);

    $pdo = Db::pdo();
    $st = $pdo->prepare("DELETE FROM replies WHERE id = :id");
    $st->execute([':id' => $id]);
    if ($st->rowCount() === 0) return Response::json(['error' => 'Reply not found'], 404);

    return Response::json(['status' => 200, 'data' => ['deleted' => $id]], 200);
});

// =============================
// ВЛОЖЕННЫЕ МАРШРУТЫ (clean URLs)
// =============================

// Все topics внутри конкретного thread
$router->get('/thread/{id}/topics', function (array $params) {
    $thread_id = (int)($params['id'] ?? 0);
    if ($thread_id <= 0) return Response::json(['error' => 'Invalid id'], 400);

    $pdo = Db::pdo();
    // сначала проверим, что thread есть
    $chk = $pdo->prepare("SELECT id FROM threads WHERE id = :id");
    $chk->execute([':id' => $thread_id]);
    if (!$chk->fetch()) return Response::json(['error' => 'Thread not found'], 404);

    $st = $pdo->prepare("
        SELECT t.id, t.title, t.body, t.user_id, t.created_at, t.updated_at, u.username
        FROM topics t
        JOIN users u ON u.id = t.user_id
        WHERE t.thread_id = :tid
        ORDER BY t.id DESC
    ");
    $st->execute([':tid' => $thread_id]);

    return Response::json(['status' => 200, 'data' => $st->fetchAll()], 200);
});

// Все replies внутри конкретного topic
$router->get('/topic/{id}/replies', function (array $params) {
    $topic_id = (int)($params['id'] ?? 0);
    if ($topic_id <= 0) return Response::json(['error' => 'Invalid id'], 400);

    $pdo = Db::pdo();
    // проверим, что topic есть
    $chk = $pdo->prepare("SELECT id FROM topics WHERE id = :id");
    $chk->execute([':id' => $topic_id]);
    if (!$chk->fetch()) return Response::json(['error' => 'Topic not found'], 404);

    $st = $pdo->prepare("
        SELECT r.id, r.body, r.user_id, r.created_at, r.updated_at, u.username
        FROM replies r
        JOIN users u ON u.id = r.user_id
        WHERE r.topic_id = :tid
        ORDER BY r.id DESC
    ");
    $st->execute([':tid' => $topic_id]);

    return Response::json(['status' => 200, 'data' => $st->fetchAll()], 200);
});

echo $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);