<?php

use Starlight\Database\MySQL;

$db = new MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$users = $db->fetchAll('SELECT `name`, `username`, `uuid` FROM `user` WHERE 1');

if ($db->numRows() > 0 || $users === null) {
    echo '{"status": {"code": "200 OK", "message": null}, "data": '.json_encode($users).'}';
} else {
    echo '{"status": {"code": "404 Not Found", "message": "No users were found in the database."}, "data": null}';
}
