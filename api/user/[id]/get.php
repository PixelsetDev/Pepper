<?php

use Pepper\Processes\PepperResponse;
use Starlight\Database\MySQL;
use starlight\HTTP\Types\ResponseCode;

$db = new MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$uriParts = explode('/', $_SERVER['REQUEST_URI']);
$user = $db->fetchOne("SELECT `name`, `username`, `uuid` FROM `users` WHERE `uuid` = ?", [$uriParts[array_key_last($uriParts)]]);
$recipes = $db->fetchAll("SELECT `slug`, `name`, `description` FROM `recipes` WHERE `author_uuid` = ?", [$uriParts[array_key_last($uriParts)]]);

if ($db->numRows() > 0 && $user !== null) {
    $user['recipes'] = $recipes;
    echo new PepperResponse()->api(ResponseCode::OK(), json_encode($user));
} else {
    $user2 = $db->fetchOne("SELECT `name`, `username`, `uuid` FROM `users` WHERE `username` = ?", [$uriParts[array_key_last($uriParts)]]);
    if ($db->numRows() > 0 && $user2 !== null) {
        $recipes = $db->fetchAll("SELECT `slug`, `name`, `description` FROM `recipes` WHERE `author_uuid` = ?", [$user2['uuid']]);
        $user2['recipes'] = $recipes;
        echo new PepperResponse()->api(ResponseCode::OK(), json_encode($user2));
    } else {
        echo new PepperResponse()->api(ResponseCode::NotFound(), null, 'The requested user could not be found.');
    }
}
