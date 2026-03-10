<?php

use Pepper\Process\Authentication;
use Pepper\Processes\PepperResponse;
use Starlight\Database\MySQL;
use starlight\HTTP\Types\ResponseCode;

$uriParts = explode('/', $_SERVER['REQUEST_URI']);
$db = new MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$auth = new Authentication(AUTH_ISSUER, AUTH_AUDIENCE, AUTH_JWKS);
$decoded = $auth->authenticate(true);

if (!$decoded) {
    echo new PepperResponse()->api(ResponseCode::Unauthorized());
    exit;
}

if (empty($uriParts[4])) {
    echo new PepperResponse()->api(ResponseCode::BadRequest());
    exit;
}

$itemId = (int)$uriParts[4];

$item = $db->fetchOne("SELECT `plan_id` FROM meal_plans_items WHERE `id` = ?", [$itemId]);

if (!$item || $item['plan_id'] !== $decoded->sub) {
    echo new PepperResponse()->api(ResponseCode::NotFound(), null, 'Item not found or access denied.');
    exit;
}

$db->run("DELETE FROM meal_plans_items WHERE `id` = ?", [$itemId]);

echo new PepperResponse()->api(ResponseCode::OK());