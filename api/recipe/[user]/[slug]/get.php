<?php

use Pepper\Helpers\Users;
use Starlight\Database\MySQL;

$db = new MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$uriParts = explode('/',$_SERVER['REQUEST_URI']);
$recipeSlug = $uriParts[array_key_last($uriParts)];
$username = $uriParts[array_key_last($uriParts)-1];

$query = $db->run("SELECT `uuid`, `title`, `description`, `tips`, `ingredients`, `steps`, `prep`, `cook`, `servings`, `vegetarian`, `vegan`, `dairy_free`, `gluten_free`, `difficulty`, `date` FROM recipes WHERE slug = ? AND `uuid` = ? AND `visibility` = 3",[$recipeSlug,new Users()->usernameToUuid($username)]);
$numRows = $query->rowCount();

if ($numRows > 0) {
    $results = json_decode($query->fetch());

    $results->author = [
        "name" => new Users()->uuidToName($results->uuid),
        "username" => new Users()->uuidToUsername($results->uuid)
    ];

    $results->dietary = [
        "dairy_free" => $results->dairy_free,
        "gluten_free" => $results->gluten_free,
        "vegan" => $results->vegan,
        "vegetarian" => $results->vegetarian
    ];

    $results->time = [
        "prep" => $results->prep,
        "cook" => $results->cook
    ];

    $results->ingredients = json_decode(html_entity_decode($results->ingredients));
    $results->steps = json_decode(html_entity_decode($results->steps));

    unset($results->uuid, $results->dairy_free, $results->gluten_free, $results->vegan, $results->vegetarian, $results->cook, $results->prep);

    echo json_encode([
        "status" => ["code" => "200 OK", "message" => null],
        "data" => $results,
        "count" => $numRows,
    ]);
} else {
    echo json_encode([
        "status" => ["code" => "404 Not Found", "message" => "The requested recipe does not exist."],
        "data" => null,
        "count" => 0,
    ]);
}
