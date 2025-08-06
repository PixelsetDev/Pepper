<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use OurCookbook\Routes;
use Starlight\HTTP\Response;
use Starlight\HTTP\Type;

require_once __DIR__ . '/starlight/HTTP/Router.php';
require_once __DIR__ . '/starlight/HTTP/Type.php';
require_once __DIR__ . '/starlight/HTTP/Response.php';
require_once __DIR__ . '/starlight/Database/SQL.php';

require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/Routes.php';

new Type()->json();
new Routes()->register();

new Response()->http404();
echo '{"status": {"code": "404 Not Found", "message": "API route not found."}, "data": null}';
