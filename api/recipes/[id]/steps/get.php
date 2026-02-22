<?php

use Pepper\Process\Authentication;
use Pepper\Processes\PepperResponse;
use Pepper\Processes\Users;
use Starlight\Database\MySQL;
use starlight\HTTP\Types\ResponseCode;

$uriParts = explode('/', $_SERVER['REQUEST_URI']);
$db = new MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$auth = new Authentication(AUTH_ISSUER, AUTH_AUDIENCE, AUTH_JWKS);
$decoded = $auth->authenticate(false);

$recipe = $db->fetchOne("SELECT `author`, `visibility` FROM recipes WHERE `id` = ?", [$uriParts[3]]);

if (!$recipe || !$auth->canViewObject($decoded, $recipe['author'], (int)$recipe['visibility'], false)) {
    echo (new PepperResponse())->api(ResponseCode::NotFound(), null, 'Recipe steps not found or access denied.');
    exit;
}

$steps = $db->fetchAll("SELECT `step`, `text` FROM recipes_steps WHERE `recipe_id` = ? ORDER BY `step`", [$uriParts[3]]);

if ($db->numRows() > 0) {
    $oSteps = [];
    foreach ($steps as $step) { $oSteps[$step['step']-1] = $step['text']; }
    echo new PepperResponse()->api(ResponseCode::OK(), json_encode($oSteps));
} else {
    echo new PepperResponse()->api(ResponseCode::NotFound(), null, 'This recipe has no steps.');
}
