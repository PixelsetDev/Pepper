<?php

use Pepper\Helpers\Users;
use Starlight\Database\SQL;

$db = new SQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$uriParts = explode('/',$_SERVER['REQUEST_URI']);
$recipeSlug = $db->escape($uriParts[array_key_last($uriParts)]);
$username = $db->escape($uriParts[array_key_last($uriParts)-1]);

$query = $db->query("SELECT * FROM recipes WHERE slug = '".$recipeSlug."' AND `uuid` = '".new Users()->usernameToUuid($username)."' AND `visibility` = 3");

if ($query->num_rows > 0) {
    $results = $query->fetch_all(MYSQLI_ASSOC);

    echo json_encode([
        "status" => ["code" => "200 OK", "message" => null],
        "data" => $results,
        "count" => $query->num_rows,
    ]);
} else {
    echo json_encode([
        "status" => ["code" => "404 Not Found", "message" => "The requested recipe does not exist."],
        "data" => null,
        "count" => 0,
    ]);
}
