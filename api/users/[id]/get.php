<?php

use Starlight\Database\SQL;

$db = new SQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$uriParts = explode('/',$_SERVER['REQUEST_URI']);
$username = $db->escape($uriParts[array_key_last($uriParts)]);

$query = $db->query("SELECT `name`, `username`, `uuid` FROM `user` WHERE `username` = '".$username."'");

if ($query->num_rows > 0) {
    $user = $query->fetch_object();
    echo '{"status": {"code": "200 OK", "message": null}, "data": '.json_encode($user).'}';
} else {
    echo '{"status": {"code": "404 Not Found", "message": "The requested user could not be found."}, "data": null}';
}
