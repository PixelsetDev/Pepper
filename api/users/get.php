<?php

use Starlight\Database\SQL;

$db = new SQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$query = $db->query('SELECT `name`, `username`, `uuid` FROM `user` WHERE 1');

if ($query->num_rows > 0) {
    $users = $query->fetch_all(MYSQLI_ASSOC);
    echo '{"status": {"code": "200 OK", "message": null}, "data": '.json_encode($users).'}';
} else {
    echo '{"status": {"code": "404 Not Found", "message": "No users were found in the database."}, "data": null}';
}
