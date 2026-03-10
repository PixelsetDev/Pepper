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
$visibility = isset($body['visibility']) ? (int)$body['visibility'] : null;

if ($visibility === null || !in_array($visibility, [0, 1, 2])) {
    echo new PepperResponse()->api(ResponseCode::BadRequest(), null, 'Invalid visibility value.');
    exit;
}

$db->run("UPDATE meal_plans SET `visibility` = ? WHERE `author` = ?", [$visibility, $decoded->sub]);

echo new PepperResponse()->api(ResponseCode::OK());