<?php
use Pepper\Process\Authentication;
use Pepper\Processes\PepperResponse;
use Starlight\Database\MySQL;
use starlight\HTTP\Types\ResponseCode;

$uriParts = explode('/', $_SERVER['REQUEST_URI']);
$db = new MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$decoded = new Authentication(AUTH_ISSUER, AUTH_AUDIENCE, AUTH_JWKS)->authenticate(true);

$db->run("DELETE FROM collections WHERE `slug` = ? AND `author` = ?", [$uriParts[3], $decoded->sub]);

if ($db->numRows() === 0) {
    echo new PepperResponse()->api(ResponseCode::NotFound());
    exit;
}

echo new PepperResponse()->api(ResponseCode::OK());
