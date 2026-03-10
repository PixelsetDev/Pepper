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

if (empty($uriParts[3])) {
    echo new PepperResponse()->api(ResponseCode::BadRequest());
    exit;
}

$list = $db->fetchOne("SELECT `author` FROM shopping_lists WHERE `uuid` = ?", [$uriParts[3]]);

if (!$list || $list['author'] !== $decoded->sub) {
    echo new PepperResponse()->api(ResponseCode::Forbidden());
    exit;
}

$data = new Request()->json();

if (empty($data->text) && empty($data->ingredient_id)) {
    echo new PepperResponse()->api(ResponseCode::BadRequest());
    exit;
}

$hasText = isset($data->text) && trim($data->text) !== '';
$hasIngredientId = isset($data->ingredient_id) && is_numeric($data->ingredient_id);

if (!$hasText && !$hasIngredientId) {
    echo new PepperResponse()->api(ResponseCode::BadRequest(), null, 'An item must have either text or an ingredient_id.');
    exit;
}

if ($hasText && strlen($data->text) > 64) {
    echo new PepperResponse()->api(ResponseCode::BadRequest(), null, 'Item text must not exceed 64 characters.');
    exit;
}

$text = $hasText ? trim($data->text) : null;
$ingredientId = $hasIngredientId ? (int)$data->ingredient_id : null;

if ($ingredientId !== null) {
    $ingredient = $db->fetchOne("SELECT `id` FROM ingredients WHERE `id` = ?", [$ingredientId]);
    if (!$ingredient) {
        echo new PepperResponse()->api(ResponseCode::BadRequest(), null, 'Ingredient not found.');
        exit;
    }
}

$db->run("INSERT INTO shopping_lists_items (`list_uuid`, `text`, `ingredient_id`, `checked`) VALUES (?, ?, ?, 0)", [$uriParts[3], $text, $ingredientId]);

echo new PepperResponse()->api(ResponseCode::Created(), json_encode($db->lastInsertId()));