<?php

use Pepper\Helpers\Users;
use Starlight\Database\MySQL;

$db = new MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$uriParts = explode('/',$_SERVER['REQUEST_URI']);
$recipeSlug = $uriParts[array_key_last($uriParts)-1];
$username = $uriParts[array_key_last($uriParts)-2];

$query = $db->run("SELECT `rating`, `comment`, `uuid` FROM ratings WHERE recipe = ? AND `author` = ?",[$recipeSlug,new Users()->usernameToUuid($username)]);
$numRows = $db->numRows();

if ($numRows > 0) {
    $results = $query->fetchAll(MYSQLI_ASSOC);

    $userHelper = new Users();

    foreach ($results as $key => $result) {
        $results[$key]['author']['name'] = $userHelper->uuidToName($result['uuid']);
        $results[$key]['author']['username'] = $userHelper->uuidToUsername($result['uuid']);

        unset($results[$key]['uuid']);
    }

    echo json_encode([
        "status" => ["code" => "200 OK", "message" => null],
        "data" => $results,
        "count" => $numRows,
    ]);
} else {
    echo json_encode([
        "status" => ["code" => "404 Not Found", "message" => "The requested recipe either does not exist or has no reviews."],
        "data" => null,
        "count" => 0,
    ]);
}
