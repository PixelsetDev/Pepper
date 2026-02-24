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
$data = new Request()->jsonValidated(['name', 'description', 'category', 'tips', 'servings', 'prep_time', 'cook_time', 'difficulty', 'visibility']);

if (strlen($data->name) > 64 || strlen($data->description) > 255) {
    echo new PepperResponse()->api(ResponseCode::BadRequest());
    exit;
}

$recipe = $db->fetchOne("SELECT `author` FROM recipes WHERE `id` = ?", [$uriParts[3]]);
if (!$recipe || $recipe['author'] !== $decoded->sub) {
    echo new PepperResponse()->api(ResponseCode::Forbidden());
    exit;
}

$db->run("UPDATE recipes SET `name` = ?, `description` = ?, `category` = ?, `tips` = ?, `servings` = ?, `prep_time` = ?, `cook_time` = ?, `difficulty` = ?, `visibility` = ? WHERE `id` = ?", [$data->name, $data->description, $data->category, $data->tips, $data->servings, $data->prep_time, $data->cook_time, $data->difficulty, $data->visibility, $uriParts[3]]);
echo new PepperResponse()->api(ResponseCode::OK());
