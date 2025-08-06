<?php

#   ██████  ██████  ██████  ██████  ██████  ██████
#   ██  ██  ██      ██  ██  ██  ██  ██      ██  ██
#   ██████  ██████  ██████  ██████  ██████  ████
#   ██      ██      ██      ██      ██      ██  ██
#   ██      ██████  ██      ██      ██████  ██  ██
#           Copyright (c) 2025 Pixelset

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use Pepper\Helpers\Routes;
use Starlight\HTTP\Type;

require_once __DIR__ . '/starlight/HTTP/Router.php';
require_once __DIR__ . '/starlight/HTTP/Type.php';
require_once __DIR__ . '/starlight/Database/SQL.php';

require_once __DIR__ . '/Helpers/Routes.php';
require_once __DIR__ . '/Helpers/Users.php';

require_once __DIR__ . '/settings.php';

new Type()->json();
new Routes()->register();

echo '{"status": {"code": "404 Not Found", "message": "API route not found."}, "data": null}';
