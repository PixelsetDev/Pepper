<?php

use Pepper\Processes\PepperResponse;
use Starlight\Database\MySQL;
use Starlight\HTTP\Request;
use starlight\HTTP\Types\ResponseCode;

$db = new MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);

echo new PepperResponse()->api(ResponseCode::OK(), json_encode(['celery','gluten','crustaceans','eggs','fish','lupin','milk','molluscs','mustard','peanuts','sesame','soybeans','sulphites','treenuts','animal_products','meat']));
