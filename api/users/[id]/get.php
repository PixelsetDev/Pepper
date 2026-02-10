<?php

use Starlight\Database\MySQL;

$db = new MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$uriParts = explode('/',$_SERVER['REQUEST_URI']);

$user = $db->fetchOne("SELECT `name`, `username`, `uuid` FROM `user` WHERE `username` = ?",[$uriParts[array_key_last($uriParts)]]);

if ($db->numRows() > 0 || $user === null) {
    echo '{"status": {"code": "200 OK", "message": null}, "data": '.json_encode($user).'}';
} else {
    echo '{"status": {"code": "404 Not Found", "message": "The requested user could not be found."}, "data": null}';
}
