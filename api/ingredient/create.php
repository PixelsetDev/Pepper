<?php
use Pepper\Process\Authentication;
use Pepper\Processes\PepperResponse;
use Starlight\Database\MySQL;
use Starlight\HTTP\Request;
use starlight\HTTP\Types\ResponseCode;

$db = new MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$auth = new Authentication(AUTH_ISSUER, AUTH_AUDIENCE, AUTH_JWKS);
$decoded = $auth->authenticate(true);
$data = new Request()->jsonValidated(['name_gb']);

if ($data->name_gb === null || trim($data->name_gb) === '') {
    echo new PepperResponse()->api(ResponseCode::BadRequest(), null, 'Name is required.');
    exit;
}

if (strlen($data->name_gb) > 128) {
    echo new PepperResponse()->api(ResponseCode::BadRequest(), null, 'Name must be 128 characters or less.');
    exit;
}

$data->name_gb = trim($data->name_gb);

$db->fetchOne("SELECT `id` FROM ingredients WHERE name_gb = ?", [$data->name_gb]);
if ($db->numRows() > 0) {
    echo new PepperResponse()->api(ResponseCode::BadRequest(), null, 'An ingredient with this name already exists.');
    exit;
}

$db->run("INSERT INTO ingredients (`name_gb`, `name_us`, `category`, `alias_of`) VALUES (?, NULL, NULL, NULL)", [$data->name_gb]);
echo new PepperResponse()->api(ResponseCode::Created(), $db->lastInsertId());
