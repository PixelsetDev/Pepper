<?php
use Pepper\Process\Authentication;
use Pepper\Processes\PepperResponse;
use Starlight\Database\MySQL;
use Starlight\HTTP\Types\ResponseCode;

$db = new MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$decoded = new Authentication(AUTH_ISSUER, AUTH_AUDIENCE, AUTH_JWKS)->authenticate(true);

$parts = explode('/', $_SERVER['REQUEST_URI']);
$id = $parts[array_key_last($parts)];

if (!is_numeric($id)) { echo new PepperResponse()->api(ResponseCode::BadRequest()); exit; }

$collection = $db->fetchOne("SELECT author FROM collections WHERE id = ?", [$id]);
if (!$collection) { echo new PepperResponse()->api(ResponseCode::NotFound()); exit; }
if ($collection['author'] !== $decoded->sub) { echo new PepperResponse()->api(ResponseCode::Forbidden()); exit; }

$db->run("DELETE FROM collections_recipes WHERE collection_id = ?", [$id]);
$db->run("DELETE FROM collections WHERE id = ?", [$id]);

echo new PepperResponse()->api(ResponseCode::OK());