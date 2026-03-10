<?php

use Pepper\Process\Authentication;
use Pepper\Processes\PepperResponse;
use Starlight\Database\MySQL;
use Starlight\HTTP\Request;
use starlight\HTTP\Types\ResponseCode;

$db = new MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$auth = new Authentication(AUTH_ISSUER, AUTH_AUDIENCE, AUTH_JWKS);
$decoded = $auth->authenticate(true);
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

$uuid = date('YmdHis') . bin2hex(random_bytes(8));
$db->run("INSERT INTO shopping_lists (`uuid`, `author`, `name`, `date`, `visibility`) VALUES (?, ?, ?, ?, ?)", [$uuid, $decoded->sub, trim($data->name), date('Y-m-d H:i:s'), (int)$data->visibility]);

echo new PepperResponse()->api(ResponseCode::Created(), json_encode($uuid));