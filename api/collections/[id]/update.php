<?php
use Pepper\Process\Authentication;
use Pepper\Processes\PepperResponse;
use Starlight\Database\MySQL;
use Starlight\HTTP\Request;
use Starlight\HTTP\Types\ResponseCode;

$db = new MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$auth = new Authentication(AUTH_ISSUER, AUTH_AUDIENCE, AUTH_JWKS);
$decoded = $auth->authenticate(true);
$request = new Request();
$data = $request->jsonValidated(['name', 'visibility']);
$optional = $request->json();
$parts = explode('/', $_SERVER['REQUEST_URI']);
$id = $parts[array_key_last($parts)];

if (!is_numeric($id) || strlen($data->name) > 27 || (strlen($optional->description ?? '') > 128)) { echo new PepperResponse()->api(ResponseCode::BadRequest()); exit; }

$collection = $db->fetchOne("SELECT author FROM collections WHERE id = ?", [$id]);
if (!$collection) { echo new PepperResponse()->api(ResponseCode::NotFound()); exit; }
if ($collection['author'] !== $decoded->sub) { echo new PepperResponse()->api(ResponseCode::Forbidden()); exit; }

$db->run("UPDATE collections SET `name` = ?, `description` = ?, `visibility` = ? WHERE id = ?", [$data->name, $optional->description ?? null, (int)$data->visibility, $id]);

if (isset($optional->recipe_ids) && is_array($optional->recipe_ids)) {
    $db->run("DELETE FROM collections_recipes WHERE collection_id = ?", [$id]);
    foreach ($optional->recipe_ids as $recipeId) {
        $recipe = $db->fetchOne("SELECT author, visibility FROM recipes WHERE id = ?", [$recipeId]);
        if ($recipe && $auth->canViewObject($decoded, $recipe['author'], (int)$recipe['visibility'], false)) { $db->run("INSERT INTO collections_recipes (`collection_id`, `recipe_id`) VALUES (?, ?)", [$id, $recipeId]); }
    }
}

echo new PepperResponse()->api(ResponseCode::OK());