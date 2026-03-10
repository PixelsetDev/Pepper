<?php

use Pepper\Processes\PepperResponse;
use Starlight\Database\MySQL;
use starlight\HTTP\Types\ResponseCode;

$start = microtime(true);

$db = new MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$ids = $db->fetchAll('SELECT id FROM recipes WHERE 1');

$missingSteps = [];
$missingIngredients = [];

foreach ($ids as $id) {
    $db->fetchAll('SELECT id FROM recipes_ingredients WHERE recipe_id = ?', [$id['id']]);

    if ($db->numRows() == 0) {
        $missingIngredients[] = $id['id'];
    }

    $db->fetchAll('SELECT id FROM recipes_steps WHERE recipe_id = ?', [$id['id']]);

    if ($db->numRows() == 0) {
        $missingSteps[] = $id['id'];
    }
}

$end = microtime(true);

echo new PepperResponse()->api(ResponseCode::OK(), json_encode([
    "missing-steps" => [
        "count" => count($missingSteps),
        "list" => $missingSteps
    ],
    "missing-ingredients" => [
        "count" => count($missingIngredients),
        "list" => $missingIngredients
    ],
    "processing-time" => ($end - $start) * 1000
]));
