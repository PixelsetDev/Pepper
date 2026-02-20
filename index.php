<?php

#   ██████  ██████  ██████  ██████  ██████  ██████
#   ██  ██  ██      ██  ██  ██  ██  ██      ██  ██
#   ██████  ██████  ██████  ██████  ██████  ████
#   ██      ██      ██      ██      ██      ██  ██
#   ██      ██████  ██      ██      ██████  ██  ██
#        Copyright (c) 2025 - 2026 Pixelset

ob_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use Pepper\Process\Authentication;
use Pepper\Processes\PepperResponse;
use Pepper\Processes\Routes;
use Starlight\HTTP\Headers;
use Starlight\HTTP\Response;
use starlight\HTTP\Types\ContentType;
use starlight\HTTP\Types\ResponseCode;

require_once 'vendor/autoload.php';

require_once __DIR__ . '/starlight/Authentication/LogTo.php';
require_once __DIR__ . '/starlight/HTTP/Router.php';
require_once __DIR__ . '/starlight/HTTP/Types/ResponseCode.php';
require_once __DIR__ . '/starlight/HTTP/Types/ContentType.php';
require_once __DIR__ . '/starlight/HTTP/Response.php';
require_once __DIR__ . '/starlight/HTTP/Request.php';
require_once __DIR__ . '/starlight/HTTP/Headers.php';
require_once __DIR__ . '/starlight/Database/DBMS.php';
require_once __DIR__ . '/starlight/Database/MySQL.php';
require_once __DIR__ . '/starlight/Security/XSS.php';

require_once __DIR__ . '/Processes/PepperResponse.php';
require_once __DIR__ . '/Processes/Authentication.php';
require_once __DIR__ . '/Processes/Routes.php';
require_once __DIR__ . '/Processes/Users.php';

require_once __DIR__ . '/settings.php';

// Security Headers
new Headers()->StrictTransportSecurity(63072000, true, false);
new Headers()->Server(SERVER_NAME);
new Headers()->Via("Pepper");
new Headers()->CORS(CORS_ALLOWED_ORIGINS, true, ['GET', 'OPTIONS'], ['Content-Type','Authorization','X-PIXELSET-IDENTITY']);
new Headers()->ContentSecurityPolicy();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

new Response()->type(ContentType::JSON());

try {
    new Routes()->register();
    echo new PepperResponse()->api(ResponseCode::NotFound(), null, "API route or resource not found.");
} catch (Exception $e) {
    if (VERBOSE_ERRORS) {
        echo new PepperResponse()->api(ResponseCode::InternalServerError(), null, $e->getMessage());
    } else {
        echo new PepperResponse()->api(ResponseCode::InternalServerError(), null, "An unknown error has occurred.");
    }
}
