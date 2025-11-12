<?php
namespace App\Controllers;

use App\Core\Db;
use App\Core\Response;

class ReplyController {
    public static function getAll(): string {
        $pdo = Db::pdo();
        $st = $pdo->query(query: "
            SELECT r.*, u.username, tp.title AS topic_title
            FROM replies r
            JOIN users u ON u.id = r.user_id
            JOIN topics tp ON tp.id = r.topic_id
            ORDER BY r.id DESC
        ");
        return Response::json(payload: ['status'=>200,'data'=>$st->fetchAll()]);
    }

    public static function getOne(array $params): string {
        $id=(int)($params['id']??0);
        if($id<=0)return Response::json(payload: ['error'=>'Invalid id'],status: 400);
        $pdo=Db::pdo();
        $st=$pdo->prepare(query: "SELECT r.*,u.username,tp.title AS topic_title
            FROM replies r JOIN users u ON u.id=r.user_id
            JOIN topics tp ON tp.id=r.topic_id WHERE r.id=:id");
        $st->execute(params: [':id'=>$id]);
        $row=$st->fetch();
        return $row
            ? Response::json(payload: ['status'=>200,'data'=>$row])
            : Response::json(payload: ['error'=>'Reply not found'],status: 404);
    }

    public static function create(): string {
        $b=jsonInput();
        $topic=(int)($b['topic_id']??0);
        $user=(int)($b['user_id']??0);
        $body=trim(string: $b['body']??'');
        if($topic<=0||$user<=0||$body==='')
            return Response::json(payload: ['error'=>'topic_id,user_id,body required'],status: 422);
        $pdo=Db::pdo();
        $chk=$pdo->prepare(query: "SELECT id FROM topics WHERE id=:id");
        $chk->execute(params: [':id'=>$topic]);
        if(!$chk->fetch())return Response::json(payload: ['error'=>'Topic not found'],status: 404);
        $st=$pdo->prepare(query: "INSERT INTO replies (topic_id,user_id,body) VALUES (:t,:u,:b)");
        $st->execute(params: [':t'=>$topic,':u'=>$user,':b'=>$body]);
        return Response::json(payload: ['status'=>201,'data'=>['id'=>$pdo->lastInsertId()]]);
    }

    public static function delete(array $params): string{
        $id=(int)($params['id']??0);
        if($id<=0)return Response::json(payload: ['error'=>'Invalid id'],status: 400);
        $pdo=Db::pdo();
        $st=$pdo->prepare(query: "DELETE FROM replies WHERE id=:id");
        $st->execute(params: [':id'=>$id]);
        return $st->rowCount()
            ? Response::json(payload: ['status'=>200,'data'=>['deleted'=>$id]])
            : Response::json(payload: ['error'=>'Reply not found'],status: 404);
    }

    public static function getByTopic(array $params): string{
        $topic=(int)($params['id']??0);
        if($topic<=0)return Response::json(payload: ['error'=>'Invalid id'],status: 400);
        $pdo=Db::pdo();
        $chk=$pdo->prepare(query: "SELECT id FROM topics WHERE id=:id");
        $chk->execute(params: [':id'=>$topic]);
        if(!$chk->fetch())return Response::json(payload: ['error'=>'Topic not found'],status: 404);
        $st=$pdo->prepare(query: "SELECT r.*,u.username FROM replies r JOIN users u ON u.id=r.user_id WHERE r.topic_id=:tid");
        $st->execute(params: [':tid'=>$topic]);
        return Response::json(payload: ['status'=>200,'data'=>$st->fetchAll()]);
    }
}
