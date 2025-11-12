<?php
if (php_sapi_name() === 'cli-server') {
    $path = parse_url(url: $_SERVER['REQUEST_URI'], component: PHP_URL_PATH);
    $full = __DIR__ . $path;
    if (is_file(filename: $full)) {
        return false;
    }
}
require __DIR__ . '/index.php';
