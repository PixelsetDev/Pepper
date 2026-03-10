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
$data = new Request()->jsonValidated(['rating', 'comment']);

if (!is_numeric($data->rating) || $data->rating < 1 || $data->rating > 5) {
    echo new PepperResponse()->api(ResponseCode::BadRequest(), null, 'Rating must be between 1 and 5.');
    exit;
}

if ($data->comment !== null) { $data->comment = trim($data->comment); }

$recipe = $db->fetchOne("SELECT `author`, `visibility` FROM recipes WHERE `id` = ?", [$uriParts[3]]);

if (!$recipe || !$auth->canViewObject($decoded, $recipe['author'], (int)$recipe['visibility'], false)) {
    echo new PepperResponse()->api(ResponseCode::Forbidden(), null, 'You cannot review a recipe you cannot access.');
    exit;
}

if ($recipe['author'] === $decoded->sub) {
    echo new PepperResponse()->api(ResponseCode::Forbidden(), null, 'You cannot review your own recipe.');
    exit;
}

$existing = $db->fetchOne("SELECT `id` FROM recipes_reviews WHERE `recipe_id` = ? AND `uuid` = ?", [$uriParts[3], $decoded->sub]);
if ($existing) { echo new PepperResponse()->api(ResponseCode::Conflict(), null, 'You have already reviewed this recipe.'); exit; }

$db->run("INSERT INTO recipes_reviews (`recipe_id`, `uuid`, `rating`, `comment`, `created`) VALUES (?, ?, ?, ?, ?)", [$uriParts[3], $decoded->sub, $data->rating, $data->comment, date('Y-m-d H:i:s')]);
echo new PepperResponse()->api(ResponseCode::Created());
