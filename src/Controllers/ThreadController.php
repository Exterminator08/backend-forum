<?php
namespace App\Controllers;

use App\Core\Db;
use App\Core\Response;

class ThreadController {
    public static function getAll() {
        $pdo = Db::pdo();
        $stmt = $pdo->query("
            SELECT t.id, t.title, t.description, t.created_at, t.updated_at,
                   u.id AS user_id, u.username
            FROM threads t
            JOIN users u ON u.id = t.user_id
            ORDER BY t.id DESC
        ");
        return Response::json(['status' => 200, 'data' => $stmt->fetchAll()]);
    }

    public static function getOne(array $params) {
        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) return Response::json(['error' => 'Invalid id'], 400);
        $pdo = Db::pdo();
        $st = $pdo->prepare("
            SELECT t.*, u.username
            FROM threads t JOIN users u ON u.id = t.user_id
            WHERE t.id = :id
        ");
        $st->execute([':id' => $id]);
        $row = $st->fetch();
        return $row
            ? Response::json(['status' => 200, 'data' => $row])
            : Response::json(['error' => 'Thread not found'], 404);
    }

    public static function create() {
        $body = jsonInput();
        $title = trim($body['title'] ?? '');
        $desc = trim($body['description'] ?? '');
        $user_id = (int)($body['user_id'] ?? 0);
        if ($title === '' || $user_id <= 0)
            return Response::json(['error' => 'title and user_id required'], 422);

        $pdo = Db::pdo();
        $st = $pdo->prepare("INSERT INTO threads (user_id, title, description) VALUES (:u,:t,:d)");
        $st->execute([':u' => $user_id, ':t' => $title, ':d' => $desc ?: null]);
        return Response::json(['status' => 201, 'data' => ['id' => $pdo->lastInsertId()]]);
    }

    public static function delete(array $params) {
        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) return Response::json(['error' => 'Invalid id'], 400);
        $pdo = Db::pdo();
        $st = $pdo->prepare("DELETE FROM threads WHERE id=:id");
        $st->execute([':id' => $id]);
        return $st->rowCount()
            ? Response::json(['status' => 200, 'data' => ['deleted' => $id]])
            : Response::json(['error' => 'Thread not found'], 404);
    }
}
