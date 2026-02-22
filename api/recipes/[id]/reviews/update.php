<?php
use Pepper\Process\Authentication;
use Pepper\Processes\PepperResponse;
use Starlight\Database\MySQL;
use Starlight\HTTP\Request;
use starlight\HTTP\Types\ResponseCode;

$uriParts = explode('/', $_SERVER['REQUEST_URI']);
$db = new MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$decoded = new Authentication(AUTH_ISSUER, AUTH_AUDIENCE, AUTH_JWKS)->authenticate(true);
$data = new Request()->jsonValidated(['rating', 'comment']);

if ($data->rating < 1 || $data->rating > 5) {
    echo new PepperResponse()->api(ResponseCode::BadRequest(), null, 'Rating must be between 1 and 5.');
    exit;
}

$db->run("UPDATE recipes_reviews SET `rating` = ?, `comment` = ? WHERE `recipe_id` = ? AND `uuid` = ?", [$data->rating, $data->comment, $uriParts[3], $decoded->sub]);

if ($db->numRows() === 0) {
    echo new PepperResponse()->api(ResponseCode::Forbidden(), null, 'Update failed: Review not found or unauthorized.');
    exit;
}

echo new PepperResponse()->api(ResponseCode::OK(), json_encode(['status' => 'Review updated']));