<?php
use Pepper\Process\Authentication;
use Pepper\Processes\PepperResponse;
use Starlight\Database\MySQL;
use Starlight\HTTP\Request;
use starlight\HTTP\Types\ResponseCode;

$db = new MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$auth = new Authentication(AUTH_ISSUER, AUTH_AUDIENCE, AUTH_JWKS);
$decoded = $auth->authenticate(true);
$data = new Request()->jsonValidated(['name', 'description', 'category', 'tips', 'servings', 'prep_time', 'cook_time', 'difficulty', 'visibility']);

if (strlen($data->name) > 64 || strlen($data->description) > 255) {
    echo new PepperResponse()->api(ResponseCode::BadRequest());
    exit;
}

$slug = preg_replace('/[^a-z0-9\-]/', '', str_replace(' ', '-', strtolower($data->name)));

$db->run("INSERT INTO recipes (`author`, `slug`, `name`, `description`, `category`, `tips`, `servings`, `prep_time`, `cook_time`, `difficulty`, `visibility`, `date`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [$decoded->sub, $slug, $data->name, $data->description, $data->category, $data->tips, $data->servings, $data->prep_time, $data->cook_time, $data->difficulty, $data->visibility, date('Y-m-d')]);
echo new PepperResponse()->api(ResponseCode::Created());
