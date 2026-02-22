<?php

use Pepper\Process\Authentication;
use Pepper\Processes\PepperResponse;
use Pepper\Processes\Users;
use Starlight\Database\MySQL;
use starlight\HTTP\Types\ResponseCode;

$auth = new Authentication(AUTH_ISSUER, AUTH_AUDIENCE, AUTH_JWKS);
$decoded = $auth->authenticate(false);
$db = new MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$uriParts = explode('/', $_SERVER['REQUEST_URI']);
$identifier = $uriParts[array_key_last($uriParts)];

$user = $db->fetchOne("SELECT `name`, `username`, `uuid` FROM users WHERE `uuid` = ? OR `username` = ?", [$identifier, $identifier]);

if ($db->numRows() > 0 && $user !== null) {
    $isSelf = ($decoded && $decoded->sub === $user['uuid']);
    $user['self'] = $isSelf;

    $rawRecipes = $db->fetchAll("SELECT `id`, `slug`, `name`, `description`, `visibility`, `author` FROM recipes WHERE author = ?", [$user['uuid']]);
    $rawCollections = $db->fetchAll("SELECT `slug`, `name`, `description`, `featured`, `visibility`, `author` FROM collections WHERE author = ?", [$user['uuid']]);
    $rawReviews = $db->fetchAll("SELECT rr.rating, rr.comment, rr.recipe_id, r.name as recipe_name, r.slug as recipe_slug, r.visibility as recipe_visibility, r.author as recipe_author FROM recipes_reviews rr JOIN recipes r ON rr.recipe_id = r.id WHERE rr.uuid = ?", [$user['uuid']]);

    $user['recipes'] = [];
    foreach ($rawRecipes as $r) {
        if ($isSelf || $auth->canViewObject($decoded, $r['author'], (int)$r['visibility'], true)) { $user['recipes'][] = $r; }
    }

    $user['collections'] = [];
    foreach ($rawCollections as $c) {
        if ($isSelf || $auth->canViewObject($decoded, $c['author'], (int)$c['visibility'], true)) { $user['collections'][] = $c; }
    }

    $user['reviews'] = [];
    foreach ($rawReviews as $rv) {
        if ($isSelf || $auth->canViewObject($decoded, $rv['recipe_author'], (int)$rv['recipe_visibility'], true)) {
            $uh = new Users();
            $rv['recipe_author'] = [
                'username' => $uh->uuidToUsername($rv['recipe_author']),
                'name' => $uh->uuidToName($rv['recipe_author'])
            ];
            unset($rv['recipe_visibility']);
            $user['reviews'][] = $rv;
        }
    }

    echo new PepperResponse()->api(ResponseCode::OK(), json_encode($user));
} else {
    echo new PepperResponse()->api(ResponseCode::NotFound(), null, 'The requested user could not be found.');
}