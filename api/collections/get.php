<?php

use Pepper\Processes\PepperResponse;
use Pepper\Processes\Users;
use Starlight\Database\MySQL;
use starlight\HTTP\Types\ResponseCode;

$db = new MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$collections = $db->fetchAll('SELECT `id`,`author`,`slug`,`name`,`description`,`featured` FROM `collections` WHERE `visibility` = 3');

foreach ($collections as $key => $collection) {
    $collections[$key]['author'] = [ "name" => new Users()->uuidToName($collection['author']), "username" => new Users()->uuidToUsername($collection['author'])];
}

echo new PepperResponse()->api(ResponseCode::OK(), json_encode($collections));
