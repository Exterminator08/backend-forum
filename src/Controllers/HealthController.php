<?php
namespace App\Controllers;

use App\Core\Response;

class HealthController {
    public static function index(): string {
        return Response::json(payload: ['ok' => true, 'message' => 'API is running'], status: 200);
    }
}
