<?php
use Pepper\Process\Authentication;
use Pepper\Processes\PepperResponse;
use Starlight\Database\MySQL;
use Starlight\HTTP\Request;
use starlight\HTTP\Types\ResponseCode;

$db = new MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$decoded = new Authentication(AUTH_ISSUER, AUTH_AUDIENCE, AUTH_JWKS)->authenticate(true);
$request = new Request();
$data = $request->jsonValidated(['name', 'visibility']);
$optional = $request->json();

if (strlen($data->name) > 27) {
    echo new PepperResponse()->api(ResponseCode::BadRequest(), null, 'Name too long (max 27).');
    exit;
}

if (strlen($data->name) > 128) {
    echo new PepperResponse()->api(ResponseCode::BadRequest(), null, 'Description too long (max 128).');
    exit;
}

$baseSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $data->name), '-'));
$slug = substr($baseSlug, 0, 27);

$exists = 1;
while ($exists !== -1) {
    $db->fetchOne("SELECT id FROM collections WHERE slug = ?", [$slug]);
    if ($db->numRows() === 0) {
        $exists = -1;
    }
    $exists++;
    $slug = substr($baseSlug, 0, 27).'-'.$exists;
}

$db->run("INSERT INTO collections (`author`, `name`, `description`, `slug`, `visibility`, `created`) VALUES (?, ?, ?, ?, ?, ?)", [$decoded->sub, trim($data->name), trim($optional->description) ?? null, $slug, (int)$data->visibility, date('Y-m-d H:i:s')]);

$collectionId = $db->lastInsertId();

if (!empty($optional->recipe_ids) && is_array($optional->recipe_ids)) {
    foreach (array_unique($optional->recipe_ids) as $recipeId) {
        $recipe = $db->fetchOne("SELECT author, visibility FROM recipes WHERE id = ?", [$recipeId]);
        $authHelper = new Authentication(AUTH_ISSUER, AUTH_AUDIENCE, AUTH_JWKS);
        if ($recipe && $authHelper->canViewObject($decoded, $recipe['author'], (int)$recipe['visibility'], false)) {
            $db->run("INSERT INTO collections_recipes (`collection_id`, `recipe_id`) VALUES (?, ?)", [$collectionId, $recipeId]);
        }
    }
}

echo new PepperResponse()->api(ResponseCode::Created(), '"' . $slug . '"');