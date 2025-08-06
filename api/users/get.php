<?php

use Starlight\Database\SQL;

$db = new SQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$query = $db->query('SELECT `name`, `username`, `avatar` FROM `users` WHERE 1');

if ($query->num_rows > 0) {
    $chefs = $query->fetch_all(MYSQLI_ASSOC);
    echo '{"status": {"code": "200 OK", "message": null}, "data": '.json_encode($chefs).'}';
} else {
    echo '{"status": {"code": "404 Not Found", "message": "No chefs were found in the database."}, "data": null}';
}
