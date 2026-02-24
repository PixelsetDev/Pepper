<?php

use Pepper\Process\Authentication;
use Pepper\Processes\PepperResponse;
use Pepper\Processes\Users;
use Starlight\Database\MySQL;
use starlight\HTTP\Types\ResponseCode;

$db = new MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$collections = $db->fetchAll('SELECT `id`,`author`,`slug`,`name`,`description`,`featured`,`visibility` FROM `collections` WHERE 1');

$auth = new Authentication(AUTH_ISSUER, AUTH_AUDIENCE, AUTH_JWKS);
$decoded = $auth->authenticate(false);

foreach ($collections as $key => $collection) {
    if (!$auth->canViewObject($decoded, $collection['author'], (int)$collection['visibility'], true)) {
        unset($collections[$key]);
        continue;
    }

    $collections[$key]['author'] = [
        "uuid" => $collection['author'],
        "name" => new Users()->uuidToName($collection['author']),
        "username" => new Users()->uuidToUsername($collection['author'])
    ];
}

echo new PepperResponse()->api(ResponseCode::OK(), json_encode($collections));
