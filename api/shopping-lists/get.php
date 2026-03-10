<?php

use Pepper\Process\Authentication;
use Pepper\Processes\PepperResponse;
use Pepper\Processes\Users;
use Starlight\Database\MySQL;
use starlight\HTTP\Types\ResponseCode;

$db = new MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$userHelper = new Users();
$auth = new Authentication(AUTH_ISSUER, AUTH_AUDIENCE, AUTH_JWKS);
$decoded = $auth->authenticate(false);

if (!$decoded) {
    echo new PepperResponse()->api(ResponseCode::Unauthorized());
    exit;
}

$lists = $db->fetchAll("SELECT `uuid`, `author`, `name`, `date`, `visibility` FROM shopping_lists WHERE `author` = ? OR `visibility` >= 1 ORDER BY `date` DESC", [$decoded->sub]);

$results = [];
foreach ($lists as $list) {
    if (!$auth->canViewObject($decoded, $list['author'], (int)$list['visibility'], true)) continue;
    $authorUuid = $list['author'];
    $list['isOwned'] = ($decoded->sub === $authorUuid);
    $list['author'] = ['username' => $userHelper->uuidToUsername($authorUuid), 'name' => $userHelper->uuidToName($authorUuid)];
    $results[] = $list;
}

echo new PepperResponse()->api(ResponseCode::OK(), json_encode(['results' => $results]));