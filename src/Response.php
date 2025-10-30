<?php
declare(strict_types=1);
namespace App;

final class Response
{
    public static function json(array $payload, int $status = 200): string
    {
        http_response_code($status);
        // дублируем код в теле, как требует задание
        if (!isset($payload['status'])) {
            $payload['status'] = $status;
        }
        return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
