<?php

use Starlight\Database\SQL;

$db = new SQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$uriParts = explode('/',$_SERVER['REQUEST_URI']);

$slug = $db->escape($uriParts[array_key_last($uriParts)]);

$query = $db->query("SELECT `name`, `description`, `slug`, `author`, `featured` FROM `collections` WHERE `slug` = '".$slug."'");

if ($query->num_rows > 0) {
    $result = $query->fetch_object();
    echo json_encode([
        "status" => ["code" => "200 OK", "message" => null],
        "data" => $result,
        "count" => $query->num_rows,
    ]);
} else {
    echo json_encode([
        "status" => ["code" => "404 Not Found", "message" => "Collection not found."],
        "data" => null,
        "count" => 0
    ]);
}
