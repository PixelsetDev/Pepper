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
if (!isset($input->id)) {
    $response->http400();
    echo json_encode([
        "status" => ["code" => "400 Bad Request", "message" => "Missing required parameter 'text'."],
        "data" => null,
        "count" => 0
    ]);
    exit;
}

$sql = new SQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$findItem = $sql->query("SELECT `uuid` FROM `meal_plans_items` WHERE `id` = '".$sql->escape($input->id)."'");

if ($findItem->num_rows != 0) {
    $findItem = $findItem->fetch_assoc();
    if ($findItem['uuid'] !== $user->sub) {
        $response->http403();
        echo json_encode([
            "status" => ["code" => "403 Forbidden", "message" => "You don't have permission to access this item."],
            "data" => null,
            "count" => 0
        ]);
    } else {
        if ($sql->query("DELETE FROM `meal_plans_items` WHERE `id` = '".$sql->escape($input->id)."' AND `uuid` = '".$sql->escape($user->sub)."'")) {
            echo json_encode([
                "status" => ["code" => "200 OK", "message" => "Item deleted."],
                "data" => null,
                "count" => 0
            ]);
        } else {
            $response->http500();
            echo json_encode([
                "status" => ["code" => "500 Internal Server Error", "message" => "The database encountered an error which could not be recovered from."],
                "data" => null,
                "count" => 0
            ]);
        }
    }
} else {
    $response->http204();
    echo json_encode([
        "status" => ["code" => "204 No Content", "message" => "The item could not be found."],
        "data" => null,
        "count" => 1
    ]);
}
