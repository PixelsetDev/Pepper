<?php

use Pepper\Helpers\Users;
use Starlight\Database\SQL;

$db = new SQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$uriParts = explode('/',$_SERVER['REQUEST_URI']);
$recipeSlug = $db->escape($uriParts[array_key_last($uriParts)-1]);
$username = $db->escape($uriParts[array_key_last($uriParts)-2]);

$query = $db->query("SELECT `rating`, `comment`, `uuid` FROM ratings WHERE recipe = '".$recipeSlug."' AND `author` = '".new Users()->usernameToUuid($username)."'");

if ($query->num_rows > 0) {
    $results = $query->fetch_all(MYSQLI_ASSOC);

    $userHelper = new Users();

    foreach ($results as $key => $result) {
        $results[$key]['author']['name'] = $userHelper->uuidToName($result['uuid']);
        $results[$key]['author']['username'] = $userHelper->uuidToUsername($result['uuid']);

        unset($results[$key]['uuid']);
    }

    echo json_encode([
        "status" => ["code" => "200 OK", "message" => null],
        "data" => $results,
        "count" => $query->num_rows,
    ]);
} else {
    echo json_encode([
        "status" => ["code" => "404 Not Found", "message" => "The requested recipe either does not exist or has no reviews."],
        "data" => null,
        "count" => 0,
    ]);
}
