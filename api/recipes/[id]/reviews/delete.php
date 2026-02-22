<?php
use Pepper\Process\Authentication;
use Pepper\Processes\PepperResponse;
use Starlight\Database\MySQL;
use Starlight\HTTP\Request;
use starlight\HTTP\Types\ResponseCode;

$uriParts = explode('/', $_SERVER['REQUEST_URI']);
$db = new MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$decoded = new Authentication(AUTH_ISSUER, AUTH_AUDIENCE, AUTH_JWKS)->authenticate(true);

$db->run("DELETE FROM recipes_reviews WHERE `recipe_id` = ? AND `uuid` = ?", [$uriParts[3], $decoded->sub]);

if ($db->numRows() === 0) {
    echo new PepperResponse()->api(ResponseCode::Forbidden(), null, 'Delete failed: Review not found or unauthorized.');
    exit;
}

echo new PepperResponse()->api(ResponseCode::OK(), json_encode(['status' => 'Review deleted']));