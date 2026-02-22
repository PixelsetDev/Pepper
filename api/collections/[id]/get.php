<?php

use Pepper\Processes\PepperResponse;
use Pepper\Processes\Users;
use Starlight\Database\MySQL;
use starlight\HTTP\Types\ResponseCode;

$uriParts = explode('/', $_SERVER['REQUEST_URI']);
$db = new MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$userHelper = new Users();

if (!is_numeric($uriParts[3])) {
    $collection = $db->fetchOne("SELECT `id`,`author`,`slug`,`name`,`description`,`featured` FROM collections WHERE `slug` = ? AND `visibility` = 3", [$uriParts[3]]);
    if ($db->numRows() > 0) {
        $recipes = $db->fetchAll("SELECT `recipe_id` FROM collections_recipes WHERE `collection_id` = ?", [$collection['id']]);
    } else {
        echo new PepperResponse()->api(ResponseCode::Forbidden(), null, 'You do not have permission to view collection.');
        exit;
    }
} else {
    $collection = $db->fetchOne("SELECT `id`,`author`,`slug`,`name`,`description`,`featured` FROM collections WHERE `id` = ? AND `visibility` = 3", [$uriParts[3]]);
    if ($db->numRows() > 0) {
        $recipes = $db->fetchAll("SELECT `recipe_id` FROM collections_recipes WHERE `collection_id` = ?", [$uriParts[3]]);
    } else {
        echo new PepperResponse()->api(ResponseCode::Forbidden(), null, 'You do not have permission to view collection.');
        exit;
    }
}

foreach ($recipes as $key => $recipe) {
    $recipes[$key] = $db->fetchOne("SELECT `author`,`slug`,`name` FROM recipes WHERE `id` = ?", [$recipe['recipe_id']]);
    $recipes[$key]['author'] = ['name' => $userHelper->uuidToName($recipes[$key]['author']), 'username' => $userHelper->uuidToUsername($recipes[$key]['author'])];
}

$collection['author'] = [ "name" => new Users()->uuidToName($collection['author']), "username" => new Users()->uuidToUsername($collection['author'])];

echo new PepperResponse()->api(ResponseCode::OK(), json_encode(["collection" => $collection, "recipes" => $recipes]));
