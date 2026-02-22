<?php

use Pepper\Processes\PepperResponse;
use Pepper\Processes\Users;
use Starlight\Database\MySQL;
use starlight\HTTP\Types\ResponseCode;

$uriParts = explode('/', $_SERVER['REQUEST_URI']);
$db = new MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$userHelper = new Users();
if (isset($uriParts[4])) {
    $recipe = $db->fetchOne("SELECT `id`, `slug`, `name`, `description`, `author`, `servings`, `prep_time`, `cook_time`, `difficulty`, `visibility`, `date`, `tips` FROM recipes WHERE `author` = ? AND `slug` = ?", [$userHelper->usernameToUuid($uriParts[3]), $uriParts[4]]);
} else {
    $recipe = $db->fetchOne("SELECT `id`, `slug`, `name`, `description`, `author`, `servings`, `prep_time`, `cook_time`, `difficulty`, `visibility`, `date`, `tips` FROM recipes WHERE `id` = ?", [$uriParts[3]]);
}

if ($db->numRows() > 0) {
    $recipe['author'] = [
        'username' => $userHelper->uuidToUsername($recipe['author']),
        'name' => $userHelper->uuidToName($recipe['author']),
    ];
    $recipe['time'] = [
        "prep" => $recipe['prep_time'],
        "cook" => $recipe['cook_time'],
    ];
    unset($recipe['author'],$recipe['prep_time'],$recipe['cook_time']);

    echo new PepperResponse()->api(ResponseCode::OK(), json_encode($recipe));
} else {
    echo new PepperResponse()->api(ResponseCode::NotFound(), null, 'Recipe not found.');
}
