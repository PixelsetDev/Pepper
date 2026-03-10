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

if (!is_numeric($data->visibility) || $data->visibility < 0 || $data->visibility > 3) {
    echo new PepperResponse()->api(ResponseCode::BadRequest(), null, 'Visibility must be between 0 and 3.');
    exit;
}

if (!is_numeric($data->difficulty) || $data->difficulty < 1 || $data->difficulty > 5) {
    echo new PepperResponse()->api(ResponseCode::BadRequest(), null, 'Difficulty must be between 1 and 5.');
    exit;
}

if (!is_numeric($data->cook_time) || $data->cook_time < 0) {
    echo new PepperResponse()->api(ResponseCode::BadRequest(), null, 'Cook time must be above zero.');
    exit;
}

if (!is_numeric($data->prep_time) || $data->prep_time < 0) {
    echo new PepperResponse()->api(ResponseCode::BadRequest(), null, 'Prep time must be above zero.');
    exit;
}

if (!is_numeric($data->servings) || $data->servings <= 0) {
    echo new PepperResponse()->api(ResponseCode::BadRequest(), null, 'Servings must be above zero.');
    exit;
}

if (!is_numeric($data->category)) {
    echo new PepperResponse()->api(ResponseCode::BadRequest(), null, 'Category invalid.');
    exit;
}

if ($data->name === null) { echo new PepperResponse()->api(ResponseCode::BadRequest(), null, 'Name invalid.');exit; }

if ($data->description !== null && !trim($data->description === '')) {
    $data->description = trim($data->description);
} else {
    $data->description = null;
}

if ($data->tips !== null && !trim($data->tips === '')) {
    $data->tips = trim($data->tips);
} else {
    $data->tips = null;
}

$db->run("UPDATE recipes SET `name` = ?, `description` = ?, `category` = ?, `tips` = ?, `servings` = ?, `prep_time` = ?, `cook_time` = ?, `difficulty` = ?, `visibility` = ?, `edited` = ? WHERE `id` = ?", [trim($data->name), $data->description, $data->category, $data->tips, $data->servings, $data->prep_time, $data->cook_time, $data->difficulty, $data->visibility, date('Y-m-d H:i:s'), $uriParts[3]]);
echo new PepperResponse()->api(ResponseCode::OK());
