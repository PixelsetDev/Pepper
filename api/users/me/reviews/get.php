<?php

use Pepper\Process\Authentication;
use Pepper\Processes\PepperResponse;
use Starlight\Database\MySQL;
use starlight\HTTP\Types\ResponseCode;

$db = new MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$auth = new Authentication(AUTH_ISSUER, AUTH_AUDIENCE, AUTH_JWKS);
$decoded = $auth->authenticate(true);

$sql = "SELECT rr.id, rr.rating, rr.comment, r.name as recipe_name, r.slug as recipe_slug 
        FROM recipes_reviews rr 
        JOIN recipes r ON rr.recipe_id = r.id 
        WHERE rr.uuid = ?";

$reviews = $db->fetchAll($sql, [$decoded->sub]);

if ($db->numRows() === 0) {
    echo new PepperResponse()->api(ResponseCode::OK(), json_encode(['reviews' => [], 'message' => 'You haven\'t left any reviews yet.']));
    exit;
}

echo new PepperResponse()->api(ResponseCode::OK(), json_encode(['reviews' => $reviews]));
