<?php
namespace App\Controllers;

use App\Core\Db;
use App\Core\Response;

class ThreadController {
    public static function getAll(): string {
        $pdo = Db::pdo();
        $stmt = $pdo->query(query: "
            SELECT t.id, t.title, t.description, t.created_at, t.updated_at,
                   u.id AS user_id, u.username
            FROM threads t
            JOIN users u ON u.id = t.user_id
            ORDER BY t.id DESC
        "); // alias for threads is t, user is u and etc.
        return Response::json(payload: ['status' => 200, 'data' => $stmt->fetchAll()]);
    }

    public static function getOne(array $params): string {
        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) return Response::json(payload: ['error' => 'Invalid id'], status: 400);
        $pdo = Db::pdo();
        // prepare() is used to prevent SQL injection
        $st = $pdo->prepare(query: "
            SELECT t.*, u.username
            FROM threads t JOIN users u ON u.id = t.user_id
            WHERE t.id = :id
        "); // alias for threads is t, user is u and etc.
        $st->execute(params: [':id' => $id]);
        $row = $st->fetch();
        return $row
            ? Response::json(payload: ['status' => 200, 'data' => $row])
            : Response::json(payload: ['error' => 'Thread not found'], status: 404);
    }

    public static function create(): string {
        $body = jsonInput();
        $title = trim(string: $body['title'] ?? '');
        $desc = trim(string: $body['description'] ?? '');
        $user_id = (int)($body['user_id'] ?? 0);
        if ($title === '' || $user_id <= 0)
            return Response::json(payload: ['error' => 'title and user_id required'], status: 422);

        $pdo = Db::pdo();
        $st = $pdo->prepare(query: "INSERT INTO threads (user_id, title, description) VALUES (:u,:t,:d)");
        $st->execute(params: [':u' => $user_id, ':t' => $title, ':d' => $desc ?: null]);
        return Response::json(payload: ['status' => 201, 'data' => ['id' => $pdo->lastInsertId()]]);
    }

    public static function delete(array $params): string {
        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) return Response::json(payload: ['error' => 'Invalid id'], status: 400);
        $pdo = Db::pdo();
        $st = $pdo->prepare(query: "DELETE FROM threads WHERE id=:id");
        $st->execute(params: [':id' => $id]);
        return $st->rowCount()
            ? Response::json(payload: ['status' => 200, 'data' => ['deleted' => $id]])
            : Response::json(payload: ['error' => 'Thread not found'], status: 404);
    }
}
