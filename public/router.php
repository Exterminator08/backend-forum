<?php
if (php_sapi_name() === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $full = __DIR__ . $path;
    if (is_file($full)) {
        return false;
    }
}
require __DIR__ . '/index.php';
