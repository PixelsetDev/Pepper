<?php
use Pepper\Helpers\Auth;
use Starlight\Database\SQL;

$jwt = str_replace('Bearer ', '', ($_SERVER['HTTP_AUTHORIZATION'] ?? ''));

$user = new Auth()->verifyLogtoIdToken($jwt);

if (!$user) {
    echo json_encode([
        "status" => ["code" => "401 Unauthorized", "message" => null],
        "data" => null,
        "count" => 0
    ]);
    exit;
}

$sql = new SQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$query = $sql->query("SELECT * FROM `meal_plans` WHERE `uuid` = '".$sql->escape($user->sub)."'");
if ($query->num_rows > 0) {
    echo json_encode([
        "status" => ["code" => "200 OK", "message" => null],
        "data" => $query->fetch_all(MYSQLI_ASSOC),
        "count" => $query->num_rows
    ]);
} else {
    echo json_encode([
        "status" => ["code" => "404 Not Found", "message" => "You don't have any meal plans yet, why not make one?"],
        "data" => null,
        "count" => 0
    ]);
}
