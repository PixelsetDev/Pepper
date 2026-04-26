<?php

use Pepper\Process\Authentication;
use Pepper\Processes\PepperResponse;
use Pepper\Processes\Users;
use Starlight\Database\MySQL;
use starlight\HTTP\Types\ResponseCode;

$db = new MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$auth = new Authentication(AUTH_ISSUER, AUTH_AUDIENCE, AUTH_JWKS);
$decoded = $auth->authenticate(true);
$users = new Users();

$following_query = $db->fetchAll("SELECT `following` FROM users_following WHERE `user` = ?", [$decoded->sub]);
$following = [];
$feed = [];
foreach ($following_query as $row) {
    $prefs = $db->fetchOne("SELECT `activity_privacy` FROM `users_preferences` WHERE `uuid` = ?", [$row['following']]);
    $is_public = ($prefs && $db->numRows() > 0) ? (bool) $prefs['activity_privacy'] : false;

    $following[] = ["username" => $users->uuidToUsername($row['following']), "name" => $users->uuidToName($row['following'])];

    if (!$is_public) { continue; }

    $following_user_recipes = $db->fetchAll("SELECT `name`,`slug`,`description`,`visibility`,`created` FROM recipes WHERE `author` = ? AND `created` >= ?", [$row['following'], date("Y-m-d H:i:s", strtotime("-1 month"))]);
    foreach ($following_user_recipes as $recipe) {
        if ($auth->canViewObject($decoded->sub, $row['following'], $recipe['visibility'], true)) {
            $recipe['type'] = 'RECIPE';
            $recipe['author'] = ["username" => $users->uuidToUsername($row['following']), "name" => $users->uuidToName($row['following'])];
            $feed[] = $recipe;
        }
    }

    $following_user_collections = $db->fetchAll("SELECT `name`,`slug`,`description`,`visibility`,`created` FROM collections WHERE `author` = ? AND `created` >= ?", [$row['following'], date("Y-m-d H:i:s", strtotime("-1 month"))]);
    foreach ($following_user_collections as $collection) {
        if ($auth->canViewObject($decoded->sub, $row['following'], $collection['visibility'], true)) {
            $collection['type'] = 'COLLECTION';
            $collection['author'] = ["username" => $users->uuidToUsername($row['following']), "name" => $users->uuidToName($row['following']), "uuid" => $row['following']];
            $feed[] = $collection;
        }
    }
}

usort($feed, function($a, $b) { return strtotime($b['created']) - strtotime($a['created']); });

$followers_query = $db->fetchAll("SELECT `user` FROM users_following WHERE `following` = ?", [$decoded->sub]);
$followers = [];
foreach ($followers_query as $row) { $followers[] = ["username" => $users->uuidToUsername($row['user']), "name" => $users->uuidToName($row['user'])]; }

echo new PepperResponse()->api(ResponseCode::OK(), json_encode(["following" => $following, "followers" => $followers, "feed" => $feed]));