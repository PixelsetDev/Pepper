<?php

use Pepper\Processes\PepperResponse;
use Starlight\Database\MySQL;
use starlight\HTTP\Types\ResponseCode;

$db = new MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$db->fetchAll('SELECT `id` FROM `users` WHERE 1');
$users = $db->numRows();

$db->fetchAll('SELECT `id` FROM `recipes` WHERE 1');
$recipes = $db->numRows();

$db->fetchAll('SELECT `id` FROM `ingredients` WHERE 1');
$ingredients_partial = $db->numRows();

$db->fetchAll('SELECT `id` FROM `ingredients` WHERE `alias_of` IS NOT null');
$ingredients_alias = $db->numRows();

$db->fetchAll('SELECT `id` FROM `ingredients_dietary` WHERE 1');
$ingredients_full = $db->numRows();

$db->fetchAll('SELECT `id` FROM `recipes_reviews` WHERE 1');
$reviews = $db->numRows();

echo new PepperResponse()->api(ResponseCode::OK(), json_encode([
    "users" => $users,
    "recipes" => $recipes,
    "ingredients" => [
        "entries" => $ingredients_partial,
        "with_dietary" => $ingredients_full + $ingredients_alias,
    ],
    "reviews" => $reviews,
    "patchInfo" => "alg"
]));