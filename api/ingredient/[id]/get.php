<?php

use Pepper\Processes\PepperResponse;
use Starlight\Database\MySQL;
use starlight\HTTP\Types\ResponseCode;

$db = new MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$parts = explode('/', $_SERVER['REQUEST_URI']);
$item = $parts[array_key_last($parts)];

if (isset($_GET['lang'])) {
    if (strtolower($_GET['lang']) == 'gb') {
        $language = 'gb';
    } elseif (strtolower($_GET['lang']) == 'us') {
        $language = 'us';
    } else {
        new PepperResponse()->api(ResponseCode::BadRequest(),null,'Invalid language.');
        exit;
    }
} else {
    $language = 'gb';
}

$search = $db->fetchOne("SELECT `id`, `name_".$language."`, `alias_of` FROM ingredients WHERE id = ?", [$item]);
$found = $db->numRows();
if ($search['name_'.$language] == null) {
    $search = $db->fetchOne("SELECT `id`, `name_gb`, `alias_of` FROM ingredients WHERE id = ?", [$item]);
    $search['name'] = $search['name_gb'];
    unset($search['name_gb']);
} else {
    $search['name'] = $search['name_'.$language];
    unset($search['name_'.$language]);
}
if ($search['alias_of'] !== null) {
    $dietary = $db->fetchOne("SELECT * FROM ingredients_dietary WHERE ingredient_id = ?", [$search['alias_of']]);
} else {
    $dietary = $db->fetchOne("SELECT * FROM ingredients_dietary WHERE ingredient_id = ?", [$item]);
}
if ($dietary != null) {
    $search['dietary'] = $dietary;
    $search['disclaimer'] = "Always check the packaging of ingredients for allergen information. Some ingredients may be produced in facilities that handle allergens and could contain traces. We cannot guarantee the accuracy or completeness of this information.";
    unset($search['dietary']['id'],$search['dietary']['ingredient_id']);
} else {
    $search['dietary'] = null;
    $search['disclaimer'] = "Allergen information is unavailable for one or more ingredients in this recipe. The allergen summary provided may be incomplete. Always check the packaging of ingredients for allergen information.";
}
unset($search['id']);

if ($found > 0) {
    echo new PepperResponse()->api(ResponseCode::OK(), json_encode($search));
} else {
    echo new PepperResponse()->api(ResponseCode::NotFound(), null, 'Ingredient not found.');
}
