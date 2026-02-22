<?php

use Pepper\Processes\PepperResponse;
use Pepper\Processes\Users;
use Starlight\Database\MySQL;
use starlight\HTTP\Types\ResponseCode;

$db = new MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? null;
$user = $_GET['user'] ?? null;
$dietaryRaw = $_GET['dietary'] ?? '[]';
$dietaryExclusions = json_decode($dietaryRaw, true) ?: [];

$allowedAllergens = ['celery', 'gluten', 'crustaceans', 'eggs', 'fish', 'lupin', 'milk', 'molluscs', 'mustard', 'peanuts', 'sesame', 'soybeans', 'sulphites', 'treenuts', 'animal_products', 'meat'];

$dietaryClause = "";
if (!empty($dietaryExclusions)) {
    $dietaryConditions = [];
    foreach ($dietaryExclusions as $exclusion) {
        if (in_array($exclusion, $allowedAllergens)) {
            $dietaryConditions[] = "idat.`" . $exclusion . "` > 0";
        }
    }
    if (!empty($dietaryConditions)) {
        $allergenCheck = implode(' OR ', $dietaryConditions);
        // Supports aliases: checks the ingredient or its parent alias against the dietary table
        $dietaryClause = " AND recipes.id NOT IN (
            SELECT ri.recipe_id 
            FROM recipes_ingredients ri 
            INNER JOIN ingredients i ON ri.ingredient = i.id
            LEFT JOIN ingredients_dietary idat ON COALESCE(i.alias_of, i.id) = idat.ingredient_id 
            WHERE ($allergenCheck OR idat.ingredient_id IS NULL)
        ) AND recipes.id IN (SELECT recipe_id FROM recipes_ingredients)";
    }
}

$pq = null;
if ($category) {
    $pq = $db->fetchAll("SELECT `id` FROM recipes_categories WHERE parent = ? LIMIT 25", [$category]);
}

if ($category) {
    $catId2 = isset($pq[0]) ? $pq[0]['id'] : $category;
    $sql = "SELECT `id`,`slug`,`name`,`author` FROM recipes WHERE (category = ? OR category = ?) $dietaryClause";
    $params = [$catId2, $category];
    if (strlen($search) >= 1) {
        $sql .= " AND name LIKE ?";
        $params[] = '%' . $search . '%';
    }
    $rq = $db->fetchAll($sql . " LIMIT 25", $params);
} else if ($user && strlen($search) < 1) {
    $rq = $db->fetchAll("SELECT `id`,`slug`,`name`,`author` FROM recipes WHERE author = ? $dietaryClause LIMIT 25", [$user]);
} else if (strlen($search) >= 1) {
    $rq = $db->fetchAll("SELECT `id`,`slug`,`name`,`author` FROM recipes WHERE name LIKE ? $dietaryClause LIMIT 25", ['%' . $search . '%']);
} else {
    $rq = $db->fetchAll("SELECT `id`,`slug`,`name`,`author` FROM recipes WHERE 1=1 $dietaryClause LIMIT 25");
}

if ($db->numRows() > 0) {
    $userHelper = new Users();
    $recipes = [];
    foreach ($rq as $recipe) {
        $recipe['author'] = [
            'username' => $userHelper->uuidToUsername($recipe['author']),
            'name' => $userHelper->uuidToName($recipe['author']),
            'uuid' => $recipe['author']
        ];
        unset($recipe['id']);
        $recipes[] = $recipe;
    }
    echo new PepperResponse()->api(ResponseCode::OK(), json_encode($recipes));
} else {
    echo new PepperResponse()->api(ResponseCode::NotFound(), null, 'No recipes found.');
}