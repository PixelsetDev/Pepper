<?php

use Pepper\Processes\PepperResponse;
use Starlight\Database\MySQL;
use starlight\HTTP\Types\ResponseCode;

$db = new MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$users = $db->fetchAll('SELECT `name`, `username`, `uuid` FROM `user` WHERE 1');

if ($db->numRows() > 0 || $users === null) {
    $response = ResponseCode::OK();
    $message = null;
    $data = json_encode($users);
} else {
    $response = ResponseCode::NotFound();
    $message = 'No users were found in the database.';
    $data = null;
}

echo new PepperResponse()->api($response, $data, $message);
