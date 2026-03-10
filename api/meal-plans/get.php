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

$plans = $db->fetchAll("SELECT `author`, `visibility` FROM meal_plans WHERE `author` = ? OR `visibility` >= 1 ORDER BY `author` DESC", [$decoded->sub]);

$results = [];
foreach ($plans as $plan) {
    if (!$auth->canViewObject($decoded, $plan['author'], (int)$plan['visibility'], false)) continue;
    $authorUuid = $plan['author'];
    $authorName = $userHelper->uuidToName($authorUuid);
    $results[] = [
        'id' => $authorUuid,
        'visibility' => (int)$plan['visibility'],
        'isOwned' => ($decoded->sub === $authorUuid),
        'author' => ['username' => $userHelper->uuidToUsername($authorUuid), 'name' => $authorName, 'uuid' => $authorUuid,],
    ];
}

echo new PepperResponse()->api(ResponseCode::OK(), json_encode($results));
