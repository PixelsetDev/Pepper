<?php

use Pepper\Process\Authentication;
use Pepper\Processes\PepperResponse;
use Starlight\Database\MySQL;
use starlight\HTTP\Types\ResponseCode;

$db = new MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$auth = new Authentication(AUTH_ISSUER, AUTH_AUDIENCE, AUTH_JWKS);
$decoded = $auth->authenticate(true);

if (!$decoded) {
    echo new PepperResponse()->api(ResponseCode::Unauthorized());
    exit;
}

$plan = $db->fetchOne("SELECT `author` FROM meal_plans WHERE `author` = ?", [$decoded->sub]);

if (!$plan) {
    echo new PepperResponse()->api(ResponseCode::NotFound(), null, 'Meal plan not found.');
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
$date = $body['date'] ?? null;
$recipeId = !empty($body['recipe_id']) ? (int)$body['recipe_id'] : null;
$text = !empty($body['text']) ? trim($body['text']) : null;

if (!$date || (!$recipeId && !$text)) {
    echo new PepperResponse()->api(ResponseCode::BadRequest(), null, 'A date and either a recipe or text is required.');
    exit;
}

if ($recipeId && !$db->fetchOne("SELECT `id` FROM recipes WHERE `id` = ?", [$recipeId])) {
    echo new PepperResponse()->api(ResponseCode::NotFound(), null, 'Recipe not found.');
    exit;
}

$db->run("INSERT INTO meal_plans_items (`plan_id`, `recipe_id`, `text`, `date`) VALUES (?, ?, ?, ?)", [$decoded->sub, $recipeId, $text, $date]);

echo new PepperResponse()->api(ResponseCode::Created(), json_encode($db->lastInsertId()));