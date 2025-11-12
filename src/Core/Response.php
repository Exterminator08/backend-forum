<?php
namespace App\Core;

final class Response {
    public static function json(array $payload, int $status = 200): string {
        http_response_code(response_code: $status);
        if (!isset($payload['status'])) $payload['status'] = $status;
        return json_encode(value: $payload, flags: JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
