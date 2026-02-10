<?php

use Pepper\Processes\PepperResponse;
use Starlight\Database\MySQL;
use starlight\HTTP\Types\ResponseCode;

$db = new MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$uriParts = explode('/',$_SERVER['REQUEST_URI']);

$user = $db->fetchOne("SELECT `name`, `username`, `uuid` FROM `user` WHERE `username` = ?",[$uriParts[array_key_last($uriParts)]]);

if ($db->numRows() > 0 || $user === null) {
    $response = ResponseCode::OK();
    $message = null;
    $data = json_encode($user);
} else {
    $response = ResponseCode::NotFound();
    $message = 'The requested user could not be found.';
    $data = null;
}

echo new PepperResponse()->api($response, $data, $message);