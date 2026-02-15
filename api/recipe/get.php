<?php

use Pepper\Processes\PepperResponse;
use Pepper\Processes\Users;
use Starlight\Database\MySQL;
use starlight\HTTP\Types\ResponseCode;

$db = new MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!isset($_GET['search']) || strlen($_GET['search']) < 1) {
    $rq = $db->fetchAll("SELECT `slug`,`name`,`author_uuid` FROM recipes WHERE 1 LIMIT 25");
} else {
    $rq = $db->fetchAll("SELECT `slug`,`name`,`author_uuid` FROM recipes WHERE name LIKE ? LIMIT 25", ['%' . $_GET['search'] . '%']);
}

if ($db->numRows() > 0) {
    $userHelper = new Users();
    $recipes = [];
    foreach ($rq as $recipe) {
        $recipe['author'] = [
            'username' => $userHelper->uuidToUsername($recipe['author_uuid']),
            'name' => $userHelper->uuidToName($recipe['author_uuid']),
            'uuid' => $recipe['author_uuid'],
        ];
        unset($recipe['author_uuid']);
        $recipes[] = $recipe;
    }
    echo new PepperResponse()->api(ResponseCode::OK(), json_encode($recipes));
} else {
    echo new PepperResponse()->api(ResponseCode::NotFound(), null, 'No recipes found.');
}
