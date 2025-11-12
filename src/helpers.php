<?php
declare(strict_types=1);

function jsonInput(): array {
    $raw = file_get_contents(filename: 'php://input');
    if ($raw === false || $raw === '') return [];
    $data = json_decode(json: $raw, associative: true);
    return is_array(value: $data) ? $data : [];
}
