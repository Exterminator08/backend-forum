<?php
namespace App\Core;

use PDO;
use PDOException;

final class Db {
    private static ?PDO $pdo = null;

    public static function pdo(): PDO {
        if (self::$pdo === null) {
            $dbPath = dirname(__DIR__, 2) . '/var/forum.sqlite';
            $dsn = 'sqlite:' . $dbPath;
            try {
                self::$pdo = new PDO($dsn, null, null, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
                self::$pdo->exec('PRAGMA foreign_keys = ON;');
            } catch (PDOException $e) {
                http_response_code(500);
                die(json_encode([
                    'status' => 500,
                    'error'  => 'DB connection failed',
                    'detail' => $e->getMessage(),
                ], JSON_UNESCAPED_UNICODE));
            }
        }
        return self::$pdo;
    }
}
