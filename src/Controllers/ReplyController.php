<?php
namespace App\Controllers;

use App\Core\Db;
use App\Core\Response;

class ReplyController {
    public static function getAll() {
        $pdo = Db::pdo();
        $st = $pdo->query("
            SELECT r.*, u.username, tp.title AS topic_title
            FROM replies r
            JOIN users u ON u.id = r.user_id
            JOIN topics tp ON tp.id = r.topic_id
            ORDER BY r.id DESC
        ");
        return Response::json(['status'=>200,'data'=>$st->fetchAll()]);
    }

    public static function getOne(array $params) {
        $id=(int)($params['id']??0);
        if($id<=0)return Response::json(['error'=>'Invalid id'],400);
        $pdo=Db::pdo();
        $st=$pdo->prepare("SELECT r.*,u.username,tp.title AS topic_title
            FROM replies r JOIN users u ON u.id=r.user_id
            JOIN topics tp ON tp.id=r.topic_id WHERE r.id=:id");
        $st->execute([':id'=>$id]);
        $row=$st->fetch();
        return $row
            ? Response::json(['status'=>200,'data'=>$row])
            : Response::json(['error'=>'Reply not found'],404);
    }

    public static function create() {
        $b=jsonInput();
        $topic=(int)($b['topic_id']??0);
        $user=(int)($b['user_id']??0);
        $body=trim($b['body']??'');
        if($topic<=0||$user<=0||$body==='')
            return Response::json(['error'=>'topic_id,user_id,body required'],422);
        $pdo=Db::pdo();
        $chk=$pdo->prepare("SELECT id FROM topics WHERE id=:id");
        $chk->execute([':id'=>$topic]);
        if(!$chk->fetch())return Response::json(['error'=>'Topic not found'],404);
        $st=$pdo->prepare("INSERT INTO replies (topic_id,user_id,body) VALUES (:t,:u,:b)");
        $st->execute([':t'=>$topic,':u'=>$user,':b'=>$body]);
        return Response::json(['status'=>201,'data'=>['id'=>$pdo->lastInsertId()]]);
    }

    public static function delete(array $params){
        $id=(int)($params['id']??0);
        if($id<=0)return Response::json(['error'=>'Invalid id'],400);
        $pdo=Db::pdo();
        $st=$pdo->prepare("DELETE FROM replies WHERE id=:id");
        $st->execute([':id'=>$id]);
        return $st->rowCount()
            ? Response::json(['status'=>200,'data'=>['deleted'=>$id]])
            : Response::json(['error'=>'Reply not found'],404);
    }

    public static function getByTopic(array $params){
        $topic=(int)($params['id']??0);
        if($topic<=0)return Response::json(['error'=>'Invalid id'],400);
        $pdo=Db::pdo();
        $chk=$pdo->prepare("SELECT id FROM topics WHERE id=:id");
        $chk->execute([':id'=>$topic]);
        if(!$chk->fetch())return Response::json(['error'=>'Topic not found'],404);
        $st=$pdo->prepare("SELECT r.*,u.username FROM replies r JOIN users u ON u.id=r.user_id WHERE r.topic_id=:tid");
        $st->execute([':tid'=>$topic]);
        return Response::json(['status'=>200,'data'=>$st->fetchAll()]);
    }
}
