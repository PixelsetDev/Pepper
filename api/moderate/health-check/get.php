<?php

use Pepper\Process\Authentication;
use Pepper\Processes\PepperResponse;
use Starlight\Database\MySQL;
use starlight\HTTP\Types\ResponseCode;

$db = new MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$auth = new Authentication(AUTH_ISSUER, AUTH_AUDIENCE, AUTH_JWKS);
$decoded = $auth->authenticate(true, [], ['OCB:MODERATE']);

$start = microtime(true);

$ids = $db->fetchAll('SELECT id FROM recipes WHERE 1');

$recipesMissingSteps = [];
$recipesMissingIngredients = [];

foreach ($ids as $id) {
    $db->fetchAll('SELECT id FROM recipes_ingredients WHERE recipe_id = ?', [$id['id']]);

    if ($db->numRows() == 0) {
        $recipesMissingIngredients[] = $id['id'];
    }

    $db->fetchAll('SELECT id FROM recipes_steps WHERE recipe_id = ?', [$id['id']]);

    if ($db->numRows() == 0) {
        $recipesMissingSteps[] = $id['id'];
    }

    $db->fetchAll('SELECT id FROM recipes_steps WHERE recipe_id = ?', [$id['id']]);

    if ($db->numRows() == 0) {
        $recipesMissingSteps[] = $id['id'];
    }
}

$ingredientsMissingDietary = $db->fetchAll('SELECT i.id, i.name_gb FROM ingredients i LEFT JOIN ingredients_dietary id ON id.ingredient_id = i.id WHERE id.id IS NULL;');
$ingredientsMissingCategories = $db->fetchAll('SELECT i.id, i.name_gb FROM ingredients i WHERE i.category IS NULL;');
$ingredientsParentDied = $db->fetchAll('SELECT i.id, i.name_gb, i.name_us, i.category FROM ingredients i INNER JOIN ingredients a ON a.id = i.alias_of WHERE i.alias_of IS NOT NULL AND a.id IS NULL;');

$end = microtime(true);

echo new PepperResponse()->api(ResponseCode::OK(), json_encode([
    "recipes" => [
        "missing_steps" => $recipesMissingSteps,
        "missing_ingredients" => $recipesMissingIngredients
    ],
    "ingredients" => [
        "missing_dietary" => $ingredientsMissingDietary,
        "missing_categories" => $ingredientsMissingCategories,
        "parent_died" => $ingredientsParentDied,
    ],
    "processing-time" => ($end - $start) * 1000
],JSON_PRETTY_PRINT));
