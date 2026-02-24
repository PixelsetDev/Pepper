<?php

use Pepper\Process\Authentication;
use Pepper\Processes\PepperResponse;
use Pepper\Processes\Users;
use Starlight\Database\MySQL;
use starlight\HTTP\Types\ResponseCode;

$uriParts = explode('/', $_SERVER['REQUEST_URI']);
$db = new MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$userHelper = new Users();

$auth = new Authentication(AUTH_ISSUER, AUTH_AUDIENCE, AUTH_JWKS);
$decoded = $auth->authenticate(false);

$identifier = $uriParts[3];
$column = is_numeric($identifier) ? 'id' : 'slug';

$collection = $db->fetchOne("SELECT `id`,`author`,`slug`,`name`,`description`,`featured`,`visibility` FROM collections WHERE {$column} = ?", [$identifier]);
if (!$collection) {
    echo new PepperResponse()->api(ResponseCode::NotFound(), null, 'Collection not found.');
    exit;
}
if (!$auth->canViewObject($decoded, $collection['author'], (int)$collection['visibility'], false)) {
    echo new PepperResponse()->api(ResponseCode::Forbidden(), null, 'You do not have permission to view collection.');
    exit;
}
$recipes = $db->fetchAll("SELECT `recipe_id` FROM collections_recipes WHERE `collection_id` = ?", [$collection['id']]);

foreach ($recipes as $key => $recipe) {
    $recipeData = $db->fetchOne("SELECT `author`,`slug`,`name`,`visibility` FROM recipes WHERE `id` = ?", [$recipe['recipe_id']]);

    if (!$recipeData || !$auth->canViewObject($decoded, $recipeData['author'], (int)$recipeData['visibility'], false)) {
        unset($recipes[$key]);
        continue;
    }

    $recipeData['author'] = ['name' => $userHelper->uuidToName($recipeData['author']), 'username' => $userHelper->uuidToUsername($recipeData['author'])];
    $recipes[$key] = $recipeData;
}

$collection['author'] = ["uuid" => $collection['author'], "name" => $userHelper->uuidToName($collection['author']), "username" => $userHelper->uuidToUsername($collection['author'])];

echo new PepperResponse()->api(ResponseCode::OK(), json_encode(["collection" => $collection, "recipes" => array_values($recipes)]));
