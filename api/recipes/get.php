<?php

use Pepper\Helpers\Users;
use Starlight\Database\SQL;
use Starlight\HTTP\Response;

$db = new SQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$userHelper = new Users();

// BASE QUERY
if (!isset($_GET['query'])) {
    $_GET['query'] = '';
}

$sql = "SELECT `slug`, `title`, `uuid` FROM recipes WHERE `title` LIKE '%".$db->escape($_GET['query'])."%'";

// ADD FILTERS
$filters = [
    'user' => isset($_GET['user']) ? $db->escape($_GET['user']) : null,
    'vegetarian' => isset($_GET['vegetarian']) ? $db->escape($_GET['vegetarian']) : null,
    'vegan' => isset($_GET['vegan']) ? $db->escape($_GET['vegan']) : null,
    'gluten_free' => isset($_GET['gluten_free']) ? $db->escape($_GET['gluten_free']) : null,
    'dairy_free' => isset($_GET['dairy_free']) ? $db->escape($_GET['dairy_free']) : null
];

if ($filters['user'] !== null) {
    $sql .= " AND `uuid` = '".$userHelper->usernameToUuid($filters['user'])."'";
}

if ($filters['vegetarian'] === '0' || $filters['vegetarian'] === '1') {
    $sql .= " AND `vegetarian` = " . (int)$filters['vegetarian'];
}

if ($filters['vegan'] === '0' || $filters['vegan'] === '1') {
    $sql .= " AND `vegan` = " . (int)$filters['vegan'];
}

if ($filters['gluten_free'] === '0' || $filters['gluten_free'] === '1') {
    $sql .= " AND `gluten_free` = " . (int)$filters['gluten_free'];
}

if ($filters['dairy_free'] === '0' || $filters['dairy_free'] === '1') {
    $sql .= " AND `dairy_free` = " . (int)$filters['dairy_free'];
}

$query = $db->query($sql." ORDER BY CASE WHEN title = '".$db->escape($_GET['query'])."' THEN 0
              WHEN title LIKE '".$db->escape($_GET['query'])."%' THEN 1
              WHEN title LIKE '%".$db->escape($_GET['query'])."%' THEN 2
              WHEN title LIKE '%".$db->escape($_GET['query'])."' THEN 3
              ELSE 4
         END, title ASC LIMIT 20");

if ($query->num_rows > 0) {
    $recipes = [];
    while ($row = $query->fetch_object()) {
        $row->author = [
            'username' => $userHelper->uuidToUsername($row->uuid),
            'name' => $userHelper->uuidToName($row->uuid),
            'uuid' => $row->uuid,
        ];
        unset($row->uuid);
        $recipes[] = $row;
    }
    echo json_encode([
        "status" => ["code" => "200 OK", "message" => null],
        "data" => $recipes,
        "count" => $query->num_rows,
    ]);
} else {
    new Response()->http204();
    echo json_encode([
        "status" => ["code" => "204 No Content", "message" => "No recipes found."],
        "data" => null,
        "count" => 0,
    ]);
}
