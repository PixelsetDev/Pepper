<?php

use Pepper\Processes\PepperResponse;
use Starlight\Database\MySQL;
use Starlight\HTTP\Request;
use starlight\HTTP\Types\ResponseCode;

$db = new MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$input = new Request()->jsonValidated(['id','celery','gluten','crustaceans','eggs','fish','lupin','milk','molluscs','mustard','peanuts','sesame','soybeans','sulphites','treenuts','animal_products','meat'], fn() => print new PepperResponse()->api(ResponseCode::BadRequest()));

$create = $db->fetchAll("SELECT * FROM ingredients_dietary WHERE `ingredient_id` = ?", [$input->id]);

if ($db->numRows() == 0) {
    $create = $db->run("INSERT INTO ingredients_dietary (`id`,`ingredient_id`,`celery`,`gluten`,`crustaceans`,`eggs`,`fish`,`lupin`,`milk`,`molluscs`,`mustard`,`peanuts`,`sesame`,`soybeans`,`sulphites`,`treenuts`,`animal_products`,`meat`) VALUES (NULL,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)", [$input->id,$input->celery,$input->gluten,$input->crustaceans,$input->eggs,$input->fish,$input->lupin,$input->milk,$input->molluscs,$input->mustard,$input->peanuts,$input->sesame,$input->soybeans,$input->sulphites,$input->treenuts,$input->animal_products,$input->meat]);
    $select = $db->fetchOne("SELECT `id` FROM ingredients_dietary WHERE `ingredient_id` = ?", [$input->id]);
    echo new PepperResponse()->api(ResponseCode::Created(), json_encode($select));
} else {
    echo new PepperResponse()->api(ResponseCode::Conflict());
}
