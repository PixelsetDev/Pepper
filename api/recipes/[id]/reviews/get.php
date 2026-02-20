<?php

use Pepper\Processes\PepperResponse;
use Pepper\Processes\Users;
use Starlight\Database\MySQL;
use starlight\HTTP\Types\ResponseCode;

$uriParts = explode('/', $_SERVER['REQUEST_URI']);
$db = new MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$reviews = $db->fetchAll("SELECT `uuid`,`rating`,`comment` FROM recipes_reviews WHERE `recipe_id` = ?", [$uriParts[3]]);

$uh = new Users();

$score = 0;

foreach ($reviews as $key => $review) {
    $reviews[$key]['author'] = [
        "username" => $uh->uuidToUsername($review['uuid']),
        "name" => $uh->uuidToName($review['uuid'])
    ];
    unset($reviews[$key]['uuid']);
    $score += $review['rating'];
}

if (count($reviews) >= 1) {
    $score = round($score / count($reviews), 1);
} elseif (count($reviews) == 0) {
    $score = -1;
}

echo new PepperResponse()->api(ResponseCode::OK(), json_encode(['reviews' => $reviews, 'score' => $score]));
