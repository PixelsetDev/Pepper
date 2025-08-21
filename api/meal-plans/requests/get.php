<?php
use Pepper\Helpers\Auth;
use Pepper\Helpers\Users;
use Starlight\Database\SQL;
use Starlight\HTTP\Response;

$jwt = str_replace('Bearer ', '', ($_SERVER['HTTP_AUTHORIZATION'] ?? ''));
$response = new Response();

$user = new Auth()->verifyLogtoIdToken($jwt);

if (!$user) {
    $response->http403();
    echo json_encode([
        "status" => ["code" => "401 Unauthorized", "message" => null],
        "data" => null,
        "count" => 0
    ]);
    exit;
}

$sql = new SQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$query = $sql->query("SELECT `owner_uuid`, `shared_uuid` FROM `meal_plans_shares` WHERE (`owner_uuid` = '".$sql->escape($user->sub)."' OR `shared_uuid` = '".$sql->escape($user->sub)."') AND `pending` = 1");
if ($query->num_rows > 0) {
    $data = $query->fetch_all(MYSQLI_ASSOC);

    foreach ($data as $key => $value) {
        $users = new Users();
        $data[$key]['owner']['name'] = $users->uuidToName($value['owner_uuid']);
        $data[$key]['owner']['uuid'] = $value['owner_uuid'];
        $data[$key]['owner']['username'] = $users->uuidToUsername($value['owner_uuid']);
        $data[$key]['shared']['name'] = $users->uuidToName($value['shared_uuid']);
        $data[$key]['shared']['uuid'] = $value['shared_uuid'];
        $data[$key]['shared']['username'] = $users->uuidToUsername($value['shared_uuid']);

        unset($data[$key]['shared_uuid']);
        unset($data[$key]['owner_uuid']);
    }

    echo json_encode([
        "status" => ["code" => "200 OK", "message" => null],
        "data" => $data,
        "count" => $query->num_rows
    ]);
} else {
    $response->http204();
    echo json_encode([
        "status" => ["code" => "204 No Content", "message" => "No shares found."],
        "data" => null,
        "count" => 0
    ]);
}
