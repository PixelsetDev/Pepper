<?php

use Pepper\Processes\PepperResponse;
use Starlight\Database\MySQL;
use starlight\HTTP\Types\ResponseCode;

$db = new MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$results = $db->fetchAll("SELECT `id`, `name` FROM recipes_categories WHERE `parent` IS NULL");
$results[] = ["id" => 0, "name" => "Uncategorised"];

if ($db->numRows() > 0) {
    echo new PepperResponse()->api(ResponseCode::OK(), json_encode($results));
} else {
    echo new PepperResponse()->api(ResponseCode::NotFound());
}
