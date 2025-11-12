<?php
namespace App\Controllers;

use App\Core\Db;
use App\Core\Response;

class TopicController {
    public static function getAll() {
        $pdo = Db::pdo();
        $st = $pdo->query("
            SELECT t.*, u.username, th.title AS thread_title
            FROM topics t
            JOIN users u ON u.id = t.user_id
            JOIN threads th ON th.id = t.thread_id
            ORDER BY t.id DESC
        ");
        return Response::json(['status'=>200,'data'=>$st->fetchAll()]);
    }

    public static function getOne(array $params) {
        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) return Response::json(['error'=>'Invalid id'],400);
        $pdo = Db::pdo();
        $st = $pdo->prepare("SELECT t.*, u.username, th.title AS thread_title
            FROM topics t JOIN users u ON u.id=t.user_id
            JOIN threads th ON th.id=t.thread_id WHERE t.id=:id");
        $st->execute([':id'=>$id]);
        $row = $st->fetch();
        return $row
            ? Response::json(['status'=>200,'data'=>$row])
            : Response::json(['error'=>'Topic not found'],404);
    }

    public static function create() {
        $b=jsonInput();
        $th=(int)($b['thread_id']??0); $u=(int)($b['user_id']??0);
        $title=trim($b['title']??''); $body=trim($b['body']??'');
        if($th<=0||$u<=0||$title===''||$body==='')
            return Response::json(['error'=>'thread_id,user_id,title,body required'],422);
        $pdo=Db::pdo();
        $chk=$pdo->prepare("SELECT id FROM threads WHERE id=:id");
        $chk->execute([':id'=>$th]);
        if(!$chk->fetch()) return Response::json(['error'=>'Thread not found'],404);
        $st=$pdo->prepare("INSERT INTO topics (thread_id,user_id,title,body) VALUES (:th,:u,:t,:b)");
        $st->execute([':th'=>$th,':u'=>$u,':t'=>$title,':b'=>$body]);
        return Response::json(['status'=>201,'data'=>['id'=>$pdo->lastInsertId()]]);
    }

    public static function delete(array $params){
        $id=(int)($params['id']??0);
        if($id<=0)return Response::json(['error'=>'Invalid id'],400);
        $pdo=Db::pdo();
        $st=$pdo->prepare("DELETE FROM topics WHERE id=:id");
        $st->execute([':id'=>$id]);
        return $st->rowCount()
            ? Response::json(['status'=>200,'data'=>['deleted'=>$id]])
            : Response::json(['error'=>'Topic not found'],404);
    }

    public static function getByThread(array $params){
        $thread=(int)($params['id']??0);
        if($thread<=0)return Response::json(['error'=>'Invalid id'],400);
        $pdo=Db::pdo();
        $chk=$pdo->prepare("SELECT id FROM threads WHERE id=:id");
        $chk->execute([':id'=>$thread]);
        if(!$chk->fetch())return Response::json(['error'=>'Thread not found'],404);
        $st=$pdo->prepare("SELECT t.*,u.username FROM topics t JOIN users u ON u.id=t.user_id WHERE t.thread_id=:tid");
        $st->execute([':tid'=>$thread]);
        return Response::json(['status'=>200,'data'=>$st->fetchAll()]);
    }
}
