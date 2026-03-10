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

$data = new Request()->jsonValidated(['name', 'visibility']);

if (!isset($data->name) || trim($data->name) === '') {
    echo new PepperResponse()->api(ResponseCode::BadRequest(), null, 'Name is required.');
    exit;
}

if (strlen($data->name) > 64) {
    echo new PepperResponse()->api(ResponseCode::BadRequest(), null, 'Name must not exceed 64 characters.');
    exit;
}

if (!is_numeric($data->visibility) || $data->visibility < 0 || $data->visibility > 2) {
    echo new PepperResponse()->api(ResponseCode::BadRequest(), null, 'Visibility must be 0 (private), 1 (friends), or 2 (unlisted).');
    exit;
}

$db->run("UPDATE shopping_lists SET `name` = ?, `visibility` = ? WHERE `uuid` = ?", [trim($data->name), (int)$data->visibility, $uriParts[3]]);

echo new PepperResponse()->api(ResponseCode::OK());