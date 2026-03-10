<?php

use Pepper\Process\Authentication;
use Pepper\Processes\PepperResponse;
use Starlight\Database\MySQL;
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

$db->run("DELETE FROM shopping_lists_items WHERE `list_uuid` = ?", [$uriParts[3]]);
$db->run("DELETE FROM shopping_lists WHERE `uuid` = ?", [$uriParts[3]]);

echo new PepperResponse()->api(ResponseCode::OK());