<?php

use Pepper\Process\Authentication;
use Pepper\Processes\PepperResponse;
use Starlight\Database\MySQL;
use starlight\HTTP\Types\ResponseCode;

$auth = new Authentication(AUTH_ISSUER, AUTH_AUDIENCE, AUTH_JWKS);
$decoded = $auth->authenticate(false);

$db = new MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$uriParts = explode('/', $_SERVER['REQUEST_URI']);
$user = $db->fetchOne("SELECT `name`, `username`, `uuid` FROM users WHERE `uuid` = ?", [$uriParts[array_key_last($uriParts)]]);
if ($db->numRows() === 0 || $user === null) {
    $user = $db->fetchOne("SELECT `name`, `username`, `uuid` FROM users WHERE `username` = ?", [$uriParts[array_key_last($uriParts)]]);
}

if ($db->numRows() > 0 && $user !== null) {
    if (!$decoded || $decoded->sub != $user['uuid']) {
        $recipes = $db->fetchAll("SELECT `slug`, `name`, `description` FROM recipes WHERE author = ? AND visibility = 3", [$user['uuid']]);
        $collections = $db->fetchAll("SELECT `slug`, `name`, `description`, `featured` FROM collections WHERE author = ? AND visibility = 3", [$user['uuid']]);
    } else {
        $recipes = $db->fetchAll("SELECT `slug`, `name`, `description` FROM recipes WHERE author = ?", [$user['uuid']]);
        $collections = $db->fetchAll("SELECT `slug`, `name`, `description`, `featured` FROM collections WHERE author = ?", [$user['uuid']]);
    }
    $user['recipes'] = $recipes;
    $user['collections'] = $collections;
    echo new PepperResponse()->api(ResponseCode::OK(), json_encode($user));
} else {
    echo new PepperResponse()->api(ResponseCode::NotFound(), null, 'The requested user could not be found.');
}
