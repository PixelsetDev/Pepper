<?php
use Pepper\Process\Authentication;
use Pepper\Processes\PepperResponse;
use Starlight\Database\MySQL;
use starlight\HTTP\Types\ResponseCode;

$uriParts = explode('/', $_SERVER['REQUEST_URI']);
$db = new MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$auth = new Authentication(AUTH_ISSUER, AUTH_AUDIENCE, AUTH_JWKS);
$decoded = $auth->authenticate(true);

$recipe = $db->fetchOne("SELECT `author` FROM recipes WHERE `id` = ?", [$uriParts[3]]);
if (!$recipe || $recipe['author'] !== $decoded->sub) {
    echo new PepperResponse()->api(ResponseCode::Forbidden());
    exit;
}

$db->run("DELETE FROM recipes_ingredients WHERE `recipe_id` = ?", [$uriParts[3]]);
$db->run("DELETE FROM recipes_steps WHERE `recipe_id` = ?", [$uriParts[3]]);
$db->run("DELETE FROM recipes WHERE `id` = ?", [$uriParts[3]]);
echo new PepperResponse()->api(ResponseCode::OK());
