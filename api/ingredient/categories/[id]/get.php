<?php

use Pepper\Processes\PepperResponse;
use Starlight\Database\MySQL;
use starlight\HTTP\Types\ResponseCode;

$db = new MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$parts = explode('/', $_SERVER['REQUEST_URI']);
$id = $parts[array_key_last($parts)];
if ($id != 0) {
    $results = $db->fetchOne("SELECT `name`, `parent` FROM ingredients_categories WHERE `id` = ?", [$id]);
    $catCount = $db->numRows();

    $ingredients = $db->fetchAll('SELECT `id` FROM ingredients WHERE `category` = ?', [$id]);
    $results['ingredients'] = [];

    $results['subcategories'] = $db->fetchAll('SELECT `id`, `name` FROM ingredients_categories WHERE `parent` = ?', [$id]);
} else {
    $results = ["name" => "Uncategorised", "parent" => NULL];
    $catCount = 1;

    $ingredients = $db->fetchAll('SELECT `id` FROM ingredients WHERE `category` IS NULL');
    $results['ingredients'] = [];

    $results['subcategories'] = [];
}

foreach ($ingredients as $ingredient) {
    $results['ingredients'][] = $ingredient['id'];
}

if ($catCount > 0) {
    echo new PepperResponse()->api(ResponseCode::OK(), json_encode($results));
} else {
    echo new PepperResponse()->api(ResponseCode::NotFound());
}