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
$data = new Request()->jsonValidated(['id', 'amount', 'unit']);

$recipe = $db->fetchOne("SELECT `author` FROM recipes WHERE `id` = ?", [$uriParts[3]]);
if (!$recipe || $recipe['author'] !== $decoded->sub) {
    echo new PepperResponse()->api(ResponseCode::Forbidden());
    exit;
}

$db->run("UPDATE recipes_ingredients SET `amount` = ?, `unit` = ? WHERE `id` = ? AND `recipe_id` = ?", [$data->amount, $data->unit, $data->id, $uriParts[3]]);
$db->run("UPDATE recipes SET `edited` = ? WHERE `id` = ?", [date('Y-m-d H:i:s'), $uriParts[3]]);

echo new PepperResponse()->api(ResponseCode::OK());
