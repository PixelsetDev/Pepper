<?php

use Pepper\Processes\PepperResponse;
use Pepper\Processes\Users;
use Starlight\Database\MySQL;
use starlight\HTTP\Types\ResponseCode;

$uriParts = explode('/', $_SERVER['REQUEST_URI']);
$db = new MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$recipe = $db->fetchAll("SELECT * FROM recipes_reviews WHERE `recipe_id` = ?", [$uriParts[3]]);

if ($db->numRows() > 0) {
    echo new PepperResponse()->api(ResponseCode::OK(), json_encode($recipe));
} else {
    echo new PepperResponse()->api(ResponseCode::NotFound(), null, 'This recipe has no reviews yet, why not leave one?');
}
