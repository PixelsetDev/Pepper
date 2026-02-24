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

$slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $data->name), '-'));
$baseSlug = substr($slug, 0, 27);
$finalSlug = $baseSlug;

while ($db->fetchOne("SELECT id FROM collections WHERE slug = ?", [$finalSlug])) {
    $finalSlug = substr($baseSlug, 0, 27) . '-' . substr(uniqid(), -4);
}

$db->run("INSERT INTO collections (`author`, `name`, `description`, `slug`, `visibility`, `created`) VALUES (?, ?, ?, ?, ?, ?)", [$decoded->sub, $data->name, $optional->description ?? null, $finalSlug, (int)$data->visibility, date('Y-m-d H:i:s')]);

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

echo new PepperResponse()->api(ResponseCode::Created(), '"' . $finalSlug . '"');