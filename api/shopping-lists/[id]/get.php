<?php

use Pepper\Process\Authentication;
use Pepper\Processes\PepperResponse;
use Pepper\Processes\Users;
use Starlight\Database\MySQL;
use starlight\HTTP\Types\ResponseCode;

$uriParts = explode('/', $_SERVER['REQUEST_URI']);
$db = new MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$userHelper = new Users();
$auth = new Authentication(AUTH_ISSUER, AUTH_AUDIENCE, AUTH_JWKS);
$decoded = $auth->authenticate(false);

if (empty($uriParts[3])) {
    echo new PepperResponse()->api(ResponseCode::BadRequest());
    exit;
}

$list = $db->fetchOne("SELECT `uuid`, `author`, `name`, `date`, `visibility` FROM shopping_lists WHERE `uuid` = ?", [$uriParts[3]]);

if (!$list || !$auth->canViewObject($decoded, $list['author'], (int)$list['visibility'], false)) {
    echo new PepperResponse()->api(ResponseCode::NotFound(), null, 'Shopping list not found or access denied.');
    exit;
}

$list['isOwned'] = ($decoded && $decoded->sub === $list['author']);
$authorUuid = $list['author'];
$list['author'] = ['username' => $userHelper->uuidToUsername($authorUuid), 'name' => $userHelper->uuidToName($authorUuid)];
$list['items'] = $db->fetchAll("SELECT `id`, `text`, `ingredient_id`, `checked` FROM shopping_lists_items WHERE `list_uuid` = ?", [$uriParts[3]]) ?: [];

echo new PepperResponse()->api(ResponseCode::OK(), json_encode($list));