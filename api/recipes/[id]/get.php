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

$db->run("DELETE FROM recipes_views_dedupe WHERE expires_at < NOW()");

if (isset($uriParts[4])) {
    $recipe = $db->fetchOne("SELECT `id`, `slug`, `name`, `description`, `author`, `servings`, `prep_time`, `cook_time`, `difficulty`, `visibility`, `created`, `edited`, `tips`, `category` FROM recipes WHERE `author` = ? AND `slug` = ?", [$userHelper->usernameToUuid($uriParts[3]), $uriParts[4]]);
} else {
    $recipe = $db->fetchOne("SELECT `id`, `slug`, `name`, `description`, `author`, `servings`, `prep_time`, `cook_time`, `difficulty`, `visibility`, `created`, `edited`, `tips`, `category` FROM recipes WHERE `id` = ?", [$uriParts[3]]);
}

if ($recipe && $auth->canViewObject($decoded, $recipe['author'], (int)$recipe['visibility'], false)) {
    $recipe['isOwned'] = ($decoded && $decoded->sub === $recipe['author']);
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $isHuman = str_contains($ua, 'Mozilla') && !preg_match('/bot|crawl|slurp|spider|gpt|openai|claude|perplexity|bytespider/i', $ua);

    if (!$recipe['isOwned'] && $isHuman) {
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $fingerprint = hash('sha256', $ip . $ua);

        $isDuplicate = $db->fetchOne("SELECT 1 FROM recipes_views_dedupe WHERE recipe_id = ? AND fingerprint = ? AND expires_at > NOW()", [$recipe['id'], $fingerprint]);
        if (!$isDuplicate) {
            $db->run("INSERT INTO recipes_views_dedupe (recipe_id, fingerprint, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 DAY)) ON DUPLICATE KEY UPDATE expires_at = DATE_ADD(NOW(), INTERVAL 1 DAY)", [$recipe['id'], $fingerprint]);
            $db->run("INSERT INTO recipes_views (recipe_id, view_date, views) VALUES (?, CURDATE(), 1) ON DUPLICATE KEY UPDATE views = views + 1", [$recipe['id']]);
        }
    }

    $viewData = $db->fetchOne("SELECT SUM(views) as total FROM recipes_views WHERE recipe_id = ?", [$recipe['id']]);
    $recipe['views'] = (int)($viewData['total'] ?? 0);

    $authorUuid = $recipe['author'];
    $recipe['author'] = ['username' => $userHelper->uuidToUsername($authorUuid), 'name' => $userHelper->uuidToName($authorUuid)];
    $recipe['time'] = ["prep" => $recipe['prep_time'], "cook" => $recipe['cook_time']];
    unset($recipe['prep_time'], $recipe['cook_time']);
    echo new PepperResponse()->api(ResponseCode::OK(), json_encode($recipe));
} else {
    echo new PepperResponse()->api(ResponseCode::NotFound(), null, 'Recipe not found or access denied.');
}
