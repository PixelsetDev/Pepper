<?php
use Pepper\Process\Authentication;
use Pepper\Processes\PepperResponse;
use Starlight\Database\MySQL;
use Starlight\HTTP\Request;
use starlight\HTTP\Types\ResponseCode;

$uriParts = explode('/', $_SERVER['REQUEST_URI']);
$db = new MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$auth = new Authentication(AUTH_ISSUER, AUTH_AUDIENCE, AUTH_JWKS);
$decoded = $auth->authenticate(true);
$data = new Request()->jsonValidated(['step']);

$recipe = $db->fetchOne("SELECT `author` FROM recipes WHERE `id` = ?", [$uriParts[3]]);
if (!$recipe || $recipe['author'] !== $decoded->sub) {
    echo new PepperResponse()->api(ResponseCode::Forbidden());
    exit;
}

$db->run("DELETE FROM recipes_steps WHERE `step` = ? AND `recipe_id` = ?", [$data->step, $uriParts[3]]);
$db->run("UPDATE recipes_steps SET `step` = `step` - 1 WHERE `recipe_id` = ? AND `step` > ?", [$uriParts[3], $data->step]);

echo new PepperResponse()->api(ResponseCode::OK());