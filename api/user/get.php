<?php

use Pepper\Processes\PepperResponse;
use Starlight\Database\MySQL;
use starlight\HTTP\Types\ResponseCode;

$db = new MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$users = $db->fetchAll('SELECT `name`, `username`, `uuid` FROM `users` WHERE 1 LIMIT 25');

if ($db->numRows() > 0 || $users === null) {
    echo new PepperResponse()->api(ResponseCode::OK(), json_encode($users));
}else {
    echo new PepperResponse()->api(ResponseCode::NotFound(), null, 'No users were found in the database.');
}
