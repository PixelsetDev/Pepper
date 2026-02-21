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

if ($category) {
    $pq = $db->fetchAll("SELECT `id` FROM recipes_categories WHERE parent = ? LIMIT 25", [$category]);
}

// Check for the specific hard-coded allergen string
if (in_array('milk', $dietaryExclusions)) {
    if ($category) {
        if (isset($pq[0])) { $rq = $db->fetchAll("SELECT `id`,`slug`,`name`,`author_uuid` FROM recipes WHERE (category = ? OR category = ?) AND NOT EXISTS (SELECT 1 FROM recipes_ingredients ri LEFT JOIN ingredients_dietary idat ON ri.ingredient = idat.ingredient_id WHERE ri.recipe_id = recipes.id AND (idat.`milk` > 0 OR idat.id IS NULL)) AND EXISTS (SELECT 1 FROM recipes_ingredients WHERE recipe_id = recipes.id) LIMIT 25", [$pq[0]['id'], $category]); }
        else { $rq = $db->fetchAll("SELECT `id`,`slug`,`name`,`author_uuid` FROM recipes WHERE category = ? AND NOT EXISTS (SELECT 1 FROM recipes_ingredients ri LEFT JOIN ingredients_dietary idat ON ri.ingredient = idat.ingredient_id WHERE ri.recipe_id = recipes.id AND (idat.`milk` > 0 OR idat.id IS NULL)) AND EXISTS (SELECT 1 FROM recipes_ingredients WHERE recipe_id = recipes.id) LIMIT 25", [$category]); }
    } else if ($search) {
        $rq = $db->fetchAll("SELECT `id`,`slug`,`name`,`author_uuid` FROM recipes WHERE name LIKE ? AND NOT EXISTS (SELECT 1 FROM recipes_ingredients ri LEFT JOIN ingredients_dietary idat ON ri.ingredient = idat.ingredient_id WHERE ri.recipe_id = recipes.id AND (idat.`milk` > 0 OR idat.id IS NULL)) AND EXISTS (SELECT 1 FROM recipes_ingredients WHERE recipe_id = recipes.id) LIMIT 25", ['%' . $search . '%']);
    } else {
        $rq = $db->fetchAll("SELECT `id`,`slug`,`name`,`author_uuid` FROM recipes WHERE NOT EXISTS (SELECT 1 FROM recipes_ingredients ri LEFT JOIN ingredients_dietary idat ON ri.ingredient = idat.ingredient_id WHERE ri.recipe_id = recipes.id AND (idat.`milk` > 0 OR idat.id IS NULL)) AND EXISTS (SELECT 1 FROM recipes_ingredients WHERE recipe_id = recipes.id) LIMIT 25");
    }
} else if (in_array('gluten', $dietaryExclusions)) {
    if ($category) {
        if (isset($pq[0])) { $rq = $db->fetchAll("SELECT `id`,`slug`,`name`,`author_uuid` FROM recipes WHERE (category = ? OR category = ?) AND NOT EXISTS (SELECT 1 FROM recipes_ingredients ri LEFT JOIN ingredients_dietary idat ON ri.ingredient = idat.ingredient_id WHERE ri.recipe_id = recipes.id AND (idat.`gluten` > 0 OR idat.id IS NULL)) AND EXISTS (SELECT 1 FROM recipes_ingredients WHERE recipe_id = recipes.id) LIMIT 25", [$pq[0]['id'], $category]); }
        else { $rq = $db->fetchAll("SELECT `id`,`slug`,`name`,`author_uuid` FROM recipes WHERE category = ? AND NOT EXISTS (SELECT 1 FROM recipes_ingredients ri LEFT JOIN ingredients_dietary idat ON ri.ingredient = idat.ingredient_id WHERE ri.recipe_id = recipes.id AND (idat.`gluten` > 0 OR idat.id IS NULL)) AND EXISTS (SELECT 1 FROM recipes_ingredients WHERE recipe_id = recipes.id) LIMIT 25", [$category]); }
    } else if ($search) {
        $rq = $db->fetchAll("SELECT `id`,`slug`,`name`,`author_uuid` FROM recipes WHERE name LIKE ? AND NOT EXISTS (SELECT 1 FROM recipes_ingredients ri LEFT JOIN ingredients_dietary idat ON ri.ingredient = idat.ingredient_id WHERE ri.recipe_id = recipes.id AND (idat.`gluten` > 0 OR idat.id IS NULL)) AND EXISTS (SELECT 1 FROM recipes_ingredients WHERE recipe_id = recipes.id) LIMIT 25", ['%' . $search . '%']);
    } else {
        $rq = $db->fetchAll("SELECT `id`,`slug`,`name`,`author_uuid` FROM recipes WHERE NOT EXISTS (SELECT 1 FROM recipes_ingredients ri LEFT JOIN ingredients_dietary idat ON ri.ingredient = idat.ingredient_id WHERE ri.recipe_id = recipes.id AND (idat.`gluten` > 0 OR idat.id IS NULL)) AND EXISTS (SELECT 1 FROM recipes_ingredients WHERE recipe_id = recipes.id) LIMIT 25");
    }
} else if (strlen($search) >= 1 && $category) {
    if (isset($pq[0])) { $rq = $db->fetchAll("SELECT `id`,`slug`,`name`,`author_uuid` FROM recipes WHERE name LIKE ? AND (category = ? OR category = ?) LIMIT 25", ['%' . $search . '%', $pq[0]['id'], $category]); }
    else { $rq = $db->fetchAll("SELECT `id`,`slug`,`name`,`author_uuid` FROM recipes WHERE name LIKE ? AND category = ? LIMIT 25", ['%' . $search . '%', $category]); }
} else if ($category) {
    if (isset($pq[0])) { $rq = $db->fetchAll("SELECT `id`,`slug`,`name`,`author_uuid` FROM recipes WHERE (category = ? OR category = ?) LIMIT 25", [$pq[0]['id'], $category]); }
    else { $rq = $db->fetchAll("SELECT `id`,`slug`,`name`,`author_uuid` FROM recipes WHERE category = ? LIMIT 25", [$category]); }
} else if ($user && strlen($search) < 1) {
    $rq = $db->fetchAll("SELECT `id`,`slug`,`name`,`author_uuid` FROM recipes WHERE author_uuid = ? LIMIT 25", [$user]);
} else if (strlen($search) >= 1) {
    $rq = $db->fetchAll("SELECT `id`,`slug`,`name`,`author_uuid` FROM recipes WHERE name LIKE ? LIMIT 25", ['%' . $search . '%']);
} else {
    $rq = $db->fetchAll("SELECT `id`,`slug`,`name`,`author_uuid` FROM recipes LIMIT 25");
}

if ($db->numRows() > 0) {
    $userHelper = new Users();
    $recipes = [];
    foreach ($rq as $recipe) {
        $recipe['author'] = ['username' => $userHelper->uuidToUsername($recipe['author_uuid']), 'name' => $userHelper->uuidToName($recipe['author_uuid']), 'uuid' => $recipe['author_uuid']];
        unset($recipe['author_uuid']);
        unset($recipe['id']);
        $recipes[] = $recipe;
    }
    echo new PepperResponse()->api(ResponseCode::OK(), json_encode($recipes));
} else {
    echo new PepperResponse()->api(ResponseCode::NotFound(), null, 'No recipes found.');
}