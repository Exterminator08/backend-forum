<?php
namespace App\Controllers;

use App\Core\Response;

class HealthController {
    public static function index() {
        return Response::json(['ok' => true, 'message' => 'API is running'], 200);
    }
}
