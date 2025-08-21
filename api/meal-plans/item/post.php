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

if (isset($input->type) && $input->type == "text") {
    if (!isset($input->text)) {
        $response->http400();
        echo json_encode([
            "status" => ["code" => "400 Bad Request", "message" => "Missing required parameter 'text'."],
            "data" => null,
            "count" => 0
        ]);
        exit;
    }
} else if ($input->type == "recipe") {
    if (!isset($input->recipe)) {
        $response->http400();
        echo json_encode([
            "status" => ["code" => "400 Bad Request", "message" => "Missing required parameter 'recipe'."],
            "data" => null,
            "count" => 0
        ]);
        exit;
    }
    if (!isset($input->recipe)) {
        $response->http400();
        echo json_encode([
            "status" => ["code" => "400 Bad Request", "message" => "Missing required parameter 'author'."],
            "data" => null,
            "count" => 0
        ]);
        exit;
    }
} else {
    $response->http400();
    echo json_encode([
        "status" => ["code" => "400 Bad Request", "message" => "Requested type is not supported."],
        "data" => null,
        "count" => 0
    ]);
    exit;
}
if (!isset($input->date)) {
    $response->http400();
    echo json_encode([
        "status" => ["code" => "400 Bad Request", "message" => "Missing required parameter 'date'."],
        "data" => null,
        "count" => 0
    ]);
    exit;
}

$sql = new SQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($input->type == "text") {
    $query = "INSERT INTO `meal_plans_items` (`id`, `uuid`, `date`, `text`, `recipe`, `author`) VALUES (NULL, '".$sql->escape($user->sub)."', '".$sql->escape($input->date)."', '".$sql->escape($input->text)."', NULL, NULL)";
} else {
    $query = "INSERT INTO `meal_plans_items` (`id`, `uuid`, `date`, `text`, `recipe`, `author`) VALUES (NULL, '".$sql->escape($user->sub)."', '".$sql->escape($input->date)."', NULL, '".$sql->escape($input->recipe)."', '".$sql->escape($input->author)."')";
}

if ($sql->query($query)) {
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
