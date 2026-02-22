<?php

use Pepper\Process\Authentication;
use Pepper\Processes\PepperResponse;
use starlight\HTTP\Types\ResponseCode;

$auth = new Authentication(AUTH_ISSUER, AUTH_AUDIENCE, AUTH_JWKS);
$decoded = $auth->authenticate();

if (!$decoded) {
    echo new PepperResponse()->api(ResponseCode::InternalServerError(), null, 'Unable to decode');
} else {
    $user = $auth->getProfileFromIdToken();
    echo new PepperResponse()->api(ResponseCode::Ok(), json_encode([
        "username" => $user['username'],
        "name" => $user['name'],
        "email" => $user['email'],
        "uuid" => $user['sub'],
    ]));
}
