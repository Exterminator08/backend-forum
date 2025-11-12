<?php
namespace App\Core;

use PDO;
use PDOException;

final class Db {
    private static ?PDO $pdo = null;

    public static function pdo(): PDO {
        if (self::$pdo === null) {
            $dbPath = dirname(path: __DIR__, levels: 2) . '/var/forum.sqlite';
            $dsn = 'sqlite:' . $dbPath;
            try {
                self::$pdo = new PDO(dsn: $dsn, username: null, password: null, options: [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,    // throw exceptions on errors
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,          // fetch associative arrays
                    PDO::ATTR_EMULATE_PREPARES   => false,                     // use native prepared statements if possible
                ]);
                self::$pdo->exec(statement: 'PRAGMA foreign_keys = ON;');
            } catch (PDOException $e) {
                http_response_code(response_code: 500);
                die(json_encode(value: [
                    'status' => 500,
                    'error'  => 'DB connection failed',
                    'detail' => $e->getMessage(),
                ], flags: JSON_UNESCAPED_UNICODE));
            }
        }
        return self::$pdo;
    }
}
