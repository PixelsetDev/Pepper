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

if (!$recipe || !$auth->canViewObject($decoded, $recipe['author'], (int)$recipe['visibility'])) {
    echo new PepperResponse()->api(ResponseCode::NotFound(), null, 'Recipe reviews not found or access denied.');
    exit;
}

$reviews = $db->fetchAll("SELECT `uuid`,`rating`,`comment`,`created`,`edited` FROM recipes_reviews WHERE `recipe_id` = ?", [$uriParts[3]]);
$uh = new Users();
$score = 0;

foreach ($reviews as $key => $review) {
    $reviews[$key]['author'] = ["username" => $uh->uuidToUsername($review['uuid']), "name" => $uh->uuidToName($review['uuid'])];
    unset($reviews[$key]['uuid']);
    $score += $review['rating'];
}

$score = count($reviews) >= 1 ? round($score / count($reviews), 1) : -1;

echo new PepperResponse()->api(ResponseCode::OK(), json_encode(['reviews' => $reviews, 'score' => $score]));
