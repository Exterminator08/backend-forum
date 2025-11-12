<?php
namespace App\Controllers;

use App\Core\Db;
use App\Core\Response;

class TopicController {
    public static function getAll(): string {
        $pdo = Db::pdo();
        $st = $pdo->query(query: "
            SELECT t.*, u.username, th.title AS thread_title
            FROM topics t
            JOIN users u ON u.id = t.user_id
            JOIN threads th ON th.id = t.thread_id
            ORDER BY t.id DESC
        ");
        return Response::json(payload: ['status'=>200,'data'=>$st->fetchAll()]);
    }

    public static function getOne(array $params): string {
        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) return Response::json(payload: ['error'=>'Invalid id'],status: 400);
        $pdo = Db::pdo();
        $st = $pdo->prepare(query: "SELECT t.*, u.username, th.title AS thread_title
            FROM topics t JOIN users u ON u.id=t.user_id
            JOIN threads th ON th.id=t.thread_id WHERE t.id=:id");
        $st->execute(params: [':id'=>$id]);
        $row = $st->fetch();
        return $row
            ? Response::json(payload: ['status'=>200,'data'=>$row])
            : Response::json(payload: ['error'=>'Topic not found'],status: 404);
    }

    public static function create(): string {
        $b=jsonInput();
        $th=(int)($b['thread_id']??0); $u=(int)($b['user_id']??0);
        $title=trim(string: $b['title']??''); $body=trim(string: $b['body']??'');
        if($th<=0||$u<=0||$title===''||$body==='')
            return Response::json(payload: ['error'=>'thread_id,user_id,title,body required'],status: 422);
        $pdo=Db::pdo();
        $chk=$pdo->prepare(query: "SELECT id FROM threads WHERE id=:id");
        $chk->execute(params: [':id'=>$th]);
        if(!$chk->fetch()) return Response::json(payload: ['error'=>'Thread not found'],status: 404);
        $st=$pdo->prepare(query: "INSERT INTO topics (thread_id,user_id,title,body) VALUES (:th,:u,:t,:b)");
        $st->execute(params: [':th'=>$th,':u'=>$u,':t'=>$title,':b'=>$body]);
        return Response::json(payload: ['status'=>201,'data'=>['id'=>$pdo->lastInsertId()]]);
    }

    public static function delete(array $params): string{
        $id=(int)($params['id']??0);
        if($id<=0)return Response::json(payload: ['error'=>'Invalid id'],status: 400);
        $pdo=Db::pdo();
        $st=$pdo->prepare(query: "DELETE FROM topics WHERE id=:id");
        $st->execute(params: [':id'=>$id]);
        return $st->rowCount()
            ? Response::json(payload: ['status'=>200,'data'=>['deleted'=>$id]])
            : Response::json(payload: ['error'=>'Topic not found'],status: 404);
    }

    public static function getByThread(array $params): string{
        $thread=(int)($params['id']??0);
        if($thread<=0)return Response::json(payload: ['error'=>'Invalid id'],status: 400);
        $pdo=Db::pdo();
        $chk=$pdo->prepare(query: "SELECT id FROM threads WHERE id=:id");
        $chk->execute(params: [':id'=>$thread]);
        if(!$chk->fetch())return Response::json(payload: ['error'=>'Thread not found'],status: 404);
        $st=$pdo->prepare(query: "SELECT t.*,u.username FROM topics t JOIN users u ON u.id=t.user_id WHERE t.thread_id=:tid");
        $st->execute(params: [':tid'=>$thread]);
        return Response::json(payload: ['status'=>200,'data'=>$st->fetchAll()]);
    }
}
