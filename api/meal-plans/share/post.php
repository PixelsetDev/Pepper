<?php
use Pepper\Helpers\Auth;
use Starlight\Database\SQL;
use Starlight\HTTP\Response;

$jwt = str_replace('Bearer ', '', ($_SERVER['HTTP_AUTHORIZATION'] ?? ''));
$response = new Response();

$user = new Auth()->verifyLogtoIdToken($jwt);

if (!$user) {
    $response->http401();
    echo json_encode([
        "status" => ["code" => "401 Unauthorized", "message" => null],
        "data" => null,
        "count" => 0
    ]);
    exit;
}

$input = json_decode(file_get_contents('php://input'));

if (!isset($input->uuid)) {
    $response->http400();
    echo json_encode([
        "status" => ["code" => "400 Bad Request", "message" => "Missing required parameter 'uuid'."],
        "data" => null,
        "count" => 0
    ]);
    exit;
}

$sql = new SQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);


if ($sql->query("INSERT INTO `meal_plans_shares` (`id`, `owner_uuid`, `shared_uuid`) VALUES (NULL, '".$sql->escape($user->sub)."', '".$sql->escape($input->uuid)."')")) {
    $response->http201();
    echo json_encode([
        "status" => ["code" => "201 Created", "message" => null],
        "data" => null,
        "count" => 1
    ]);
} else {
    $response->http500();
    echo json_encode([
        "status" => ["code" => "500 Internal Server Error", "message" => "The database encountered an error which could not be recovered from."],
        "data" => null,
        "count" => 0
    ]);
}
