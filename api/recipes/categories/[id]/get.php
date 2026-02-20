<?php

use Pepper\Processes\PepperResponse;
use Starlight\Database\MySQL;
use starlight\HTTP\Types\ResponseCode;

$db = new MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$parts = explode('/', $_SERVER['REQUEST_URI']);
$id = $parts[array_key_last($parts)];
if ($id != 0) {
    $results = $db->fetchOne("SELECT `name`, `parent` FROM recipes_categories WHERE `id` = ?", [$id]);
    $catCount = $db->numRows();

    $recipes = $db->fetchAll('SELECT `id` FROM recipes WHERE `category` = ?', [$id]);
    $results['recipes'] = [];

    $results['subcategories'] = $db->fetchAll('SELECT `id`, `name` FROM recipes_categories WHERE `parent` = ?', [$id]);
} else {
    $results = ["name" => "Uncategorised", "parent" => NULL];
    $catCount = 1;

    $recipes = $db->fetchAll('SELECT `id` FROM recipes WHERE `category` IS NULL');
    $results['recipes'] = [];

    $results['subcategories'] = [];
}

foreach ($recipes as $recipe) {
    $results['recipes'][] = $recipe['id'];
}

if ($catCount > 0) {
    echo new PepperResponse()->api(ResponseCode::OK(), json_encode($results));
} else {
    echo new PepperResponse()->api(ResponseCode::NotFound());
}