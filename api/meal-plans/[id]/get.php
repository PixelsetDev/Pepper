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

if (empty($uriParts[3])) {
    echo new PepperResponse()->api(ResponseCode::BadRequest());
    exit;
}

$planAuthor = $uriParts[3];

$plan = $db->fetchOne("SELECT `author`, `visibility` FROM meal_plans WHERE `author` = ?", [$planAuthor]);

if (!$plan || !$auth->canViewObject($decoded, $plan['author'], (int)$plan['visibility'], false)) {
    echo new PepperResponse()->api(ResponseCode::NotFound(), null, 'Meal plan not found or access denied.');
    exit;
}

$plan['isOwned'] = ($decoded && $decoded->sub === $plan['author']);
$authorUuid = $plan['author'];
$authorName = $userHelper->uuidToName($authorUuid);
$plan['id'] = $authorUuid;
$plan['author'] = ['username' => $userHelper->uuidToUsername($authorUuid), 'name' => $authorName, 'uuid' => $authorUuid];

$rows = $db->fetchAll("SELECT `id`, `plan_id`, `recipe_id`, `text`, `date` FROM meal_plans_items WHERE `plan_id` = ? ORDER BY `date`, `id`", [$authorUuid]) ?: [];

$recipeCache = [];
$items = [];

foreach ($rows as $row) {
    $recipe = null;

    if (!empty($row['recipe_id'])) {
        $rid = (int)$row['recipe_id'];

        if (!array_key_exists($rid, $recipeCache)) {
            $r = $db->fetchOne("SELECT `name` FROM recipes WHERE `id` = ?", [$rid]);
            $recipeCache[$rid] = $r ? $r['name'] : null;
        }

        $recipe = $recipeCache[$rid];
    }

    $items[] = [
        'id' => (int)$row['id'],
        'plan_id' => (int)$row['plan_id'],
        'recipe_id' => $row['recipe_id'] ? (int)$row['recipe_id'] : null,
        'recipe_name' => $recipe,
        'text' => $row['text'],
        'date' => $row['date'],
    ];
}

$plan['items'] = $items;

echo new PepperResponse()->api(ResponseCode::OK(), json_encode($plan));