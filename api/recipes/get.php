<?php

use Pepper\Process\Authentication;
use Pepper\Processes\PepperResponse;
use Pepper\Processes\Users;
use Starlight\Database\MySQL;
use starlight\HTTP\Types\ResponseCode;

$auth = new Authentication(AUTH_ISSUER, AUTH_AUDIENCE, AUTH_JWKS);
$decoded = $auth->authenticate(false);
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
    foreach ($dietaryExclusions as $exclusion) { if (in_array($exclusion, $allowedAllergens)) { $dietaryConditions[] = "idat.`" . $exclusion . "` > 0"; } }
    if (!empty($dietaryConditions)) {
        $allergenCheck = implode(' OR ', $dietaryConditions);
        $dietaryClause = " AND recipes.id NOT IN (SELECT ri.recipe_id FROM recipes_ingredients ri INNER JOIN ingredients i ON ri.ingredient = i.id LEFT JOIN ingredients_dietary idat ON COALESCE(i.alias_of, i.id) = idat.ingredient_id WHERE ($allergenCheck OR idat.ingredient_id IS NULL)) AND recipes.id IN (SELECT recipe_id FROM recipes_ingredients)";
    }
}

$pq = $category ? $db->fetchAll("SELECT `id` FROM recipes_categories WHERE parent = ? LIMIT 25", [$category]) : null;

if ($category) {
    $catId2 = isset($pq[0]) ? $pq[0]['id'] : $category;
    $sql = "SELECT `id`,`slug`,`name`,`author`,`visibility` FROM recipes WHERE (category = ? OR category = ?) $dietaryClause";
    $params = [$catId2, $category];
    if (strlen($search) >= 1) { $sql .= " AND name LIKE ?"; $params[] = '%' . $search . '%'; }
    $rq = $db->fetchAll($sql . " LIMIT 25", $params);
} else if ($user && strlen($search) < 1) {
    $rq = $db->fetchAll("SELECT `id`,`slug`,`name`,`author`,`visibility` FROM recipes WHERE author = ? $dietaryClause LIMIT 25", [$user]);
} else if (strlen($search) >= 1) {
    $rq = $db->fetchAll("SELECT `id`,`slug`,`name`,`author`,`visibility` FROM recipes WHERE name LIKE ? $dietaryClause LIMIT 25", ['%' . $search . '%']);
} else {
    $rq = $db->fetchAll("SELECT `id`,`slug`,`name`,`author`,`visibility` FROM recipes WHERE 1=1 $dietaryClause LIMIT 25");
}

$userHelper = new Users();
$recipes = [];
foreach (($rq ?? []) as $recipe) {
    if (!$auth->canViewObject($decoded, $recipe['author'], (int)$recipe['visibility'], true)) { continue; }
    $recipe['author'] = ['username' => $userHelper->uuidToUsername($recipe['author']), 'name' => $userHelper->uuidToName($recipe['author']), 'uuid' => $recipe['author']];
    unset($recipe['id']);
    $recipes[] = $recipe;
}

if (empty($recipes)) { echo (new PepperResponse())->api(ResponseCode::NotFound(), null, 'No recipes found.'); }
else { echo (new PepperResponse())->api(ResponseCode::OK(), json_encode($recipes)); }