<?php

use Pepper\Process\Authentication;
use Pepper\Processes\PepperResponse;
use starlight\HTTP\Types\ResponseCode;

$auth = new Authentication(AUTH_ISSUER, AUTH_AUDIENCE, AUTH_JWKS);
$decoded = $auth->authenticate();

if (!$decoded) {
    echo new PepperResponse()->api(ResponseCode::InternalServerError(), null, 'Unable to decode');
} else {
    echo new PepperResponse()->api(ResponseCode::Ok(), json_encode([
        'user' => $auth->getProfileFromIdToken()
    ]));
}
