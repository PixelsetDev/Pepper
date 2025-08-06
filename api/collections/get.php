<?php

use Pepper\Helpers\Users;
use Starlight\Database\SQL;

$db = new SQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$userHelper = new Users();

// BASE QUERY
if (!isset($_GET['query'])) {
    $_GET['query'] = '';
}

$sql = "SELECT `name`, `description`, `slug`, `author`, `featured` FROM collections WHERE `visible` = 1 AND `name` LIKE '%".$db->escape($_GET['query'])."%'";

// ADD FILTERS
$filters = [
    'user' => isset($_GET['user']) ? $db->escape($_GET['user']) : null,
];

if ($filters['user'] !== null) {
    $sql .= " AND `author` = '".$userHelper->usernameToUuid($filters['user'])."'";
}

$query = $db->query($sql." ORDER BY CASE WHEN name = '".$db->escape($_GET['query'])."' THEN 0
              WHEN name LIKE '".$db->escape($_GET['query'])."%' THEN 1
              WHEN name LIKE '%".$db->escape($_GET['query'])."%' THEN 2
              WHEN name LIKE '%".$db->escape($_GET['query'])."' THEN 3
              ELSE 4
         END, name ASC LIMIT 20");

if ($query->num_rows > 0) {
    $recipes = [];
    while ($row = $query->fetch_object()) {
        $row->author = [
            'username' => $userHelper->uuidToUsername($row->author),
            'name' => $userHelper->uuidToName($row->author),
        ];
        $recipes[] = $row;
    }
    echo json_encode([
        "status" => ["code" => "200 OK", "message" => null],
        "data" => $recipes,
        "count" => $query->num_rows,
    ]);
} else {
    echo json_encode([
        "status" => ["code" => "404 Not Found", "message" => "No collections found."],
        "data" => null,
        "count" => 0
    ]);
}
