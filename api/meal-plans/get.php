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

// Get all UUIDs the user should see: their own + owners who shared with them
$sharedQuery = $sql->query("
    SELECT `owner_uuid`
    FROM `meal_plans_shares`
    WHERE `shared_uuid` = '".$sql->escape($user->sub)."'
");

$visibleUuids = [$sql->escape($user->sub)];
if ($sharedQuery->num_rows > 0) {
    while ($row = $sharedQuery->fetch_assoc()) {
        $visibleUuids[] = $sql->escape($row['owner_uuid']);
    }
}
$uuidList = "'" . implode("','", $visibleUuids) . "'";

// Fetch meal plan items for all visible UUIDs
$query = $sql->query("
    SELECT `id`,`uuid`,`author`,`text`,`date`,`recipe`
    FROM `meal_plans_items`
    WHERE `uuid` IN ($uuidList)
");

if ($query->num_rows > 0) {
    $data = $query->fetch_all(MYSQLI_ASSOC);

    foreach ($data as $key => $value) {
        if ($value['text'] == null) {
            $rquery = $sql->query("SELECT `title` FROM `recipes` WHERE `uuid` = '".$sql->escape($value['author'])."'");
            if ($rquery->num_rows > 0) {
                $recipe = $rquery->fetch_assoc();
                $data[$key]['text'] = $recipe['title'];
                $data[$key]['author'] = new Users()->uuidToName($value['author']);
                $data[$key]['link'] = '/@'.new Users()->uuidToUsername($value['author']).'/'.$value['recipe'];
            } else {
                $data[$key]['text'] = 'This recipe was deleted.';
            }
        }
        unset($data[$key]['recipe']);
    }

    echo json_encode([
        "status" => ["code" => "200 OK", "message" => null],
        "data" => $data,
        "count" => $query->num_rows
    ]);
} else {
    $response->http204();
    echo json_encode([
        "status" => ["code" => "204 No Content", "message" => "You don't have any meal plans yet, why not make one?"],
        "data" => null,
        "count" => 0
    ]);
}
