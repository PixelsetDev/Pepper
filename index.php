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

use Pepper\Helpers\Routes;
use Starlight\HTTP\Response;
use starlight\HTTP\Types\ContentType;

require_once 'vendor/autoload.php';

require_once __DIR__ . '/starlight/Authentication/LogTo.php';
require_once __DIR__ . '/starlight/HTTP/Router.php';
require_once __DIR__ . '/starlight/HTTP/Types/ContentType.php';
require_once __DIR__ . '/starlight/Database/MySQL.php';

require_once __DIR__ . '/Helpers/Auth.php';
require_once __DIR__ . '/Helpers/Routes.php';
require_once __DIR__ . '/Helpers/Users.php';

require_once __DIR__ . '/settings.php';

new Response()->type(ContentType::JSON());
new Routes()->register();

echo '{"status": {"code": "404 Not Found", "message": "API route not found."}, "data": null}';
