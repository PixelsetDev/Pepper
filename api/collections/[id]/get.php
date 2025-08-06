<?php

use Pepper\Helpers\Users;
use Starlight\Database\SQL;

$db = new SQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$userHelper = new Users();

$uriParts = explode('/',$_SERVER['REQUEST_URI']);

$slug = $db->escape($uriParts[array_key_last($uriParts)]);

$query = $db->query("SELECT `name`, `description`, `slug`, `author`, `featured` FROM `collections` WHERE `slug` = '".$slug."'");

if ($query->num_rows > 0) {
    $result = $query->fetch_object();

    $query2 = $db->query("SELECT `slug`, `author`, `title`, `notes` FROM `collections_recipes` WHERE `collection` = '".$slug."'");
    if ($query2->num_rows > 0) {
        $result->recipes = $query2->fetch_all(MYSQLI_ASSOC);

        foreach ($result->recipes as $key => $recipe) {
            $result->recipes[$key]['author'] = [
                "name" => $userHelper->uuidToName($result->recipes[$key]['author']),
                "username" => $userHelper->uuidToUsername($result->recipes[$key]['author'])
            ];
        }
    }

    $userQuery = $db->query("SELECT `avatar` FROM `users` WHERE `uuid` = '".$db->escape($result->author)."'");
    if ($userQuery->num_rows > 0) {
        $result->author = [
            "name" => $userHelper->uuidToName($result->author),
            "username" => $userHelper->uuidToUsername($result->author),
            "avatar" => $userQuery->fetch_object()->avatar
        ];
    } else {
        $result->author = [
            "name" => $userHelper->uuidToName($result->author),
            "username" => $userHelper->uuidToUsername($result->author),
            "avatar" => 'https://data.portalsso.com/avatar/missing.webp'
        ];
    }

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
