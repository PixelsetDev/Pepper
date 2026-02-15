<?php

use Pepper\Processes\PepperResponse;
use Starlight\Database\MySQL;
use Starlight\HTTP\Request;
use starlight\HTTP\Types\ResponseCode;
use Starlight\Security\XSS;

echo new PepperResponse()->api(ResponseCode::ServiceUnavailable());
exit;

$db = new MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$input = new Request()->jsonValidated(['id','name'], fn() => print new PepperResponse()->api(ResponseCode::BadRequest()));

if (new XSS()->hasSpecialChars($input->name, ['(',')','-',"'","&"])) {
    echo new PepperResponse()->api(ResponseCode::Forbidden());
    exit;
}

if (!isset($input->alias_of) || trim($input->alias_of) == '') {
    $alias = null;
} else {
    $alias = $input->alias_of;
    $aliasExists = $db->fetchOne("SELECT * FROM ingredients WHERE `id` = ?", [$alias]);
    if ($db->numRows() == 0) {
        echo new PepperResponse()->api(ResponseCode::FailedDependency(), null, '"Alias of" ingredient does not exist.');
        exit;
    } else if ($aliasExists['alias_of'] !== null) {
        $alias = $aliasExists['alias_of'];
    }
}

$create = $db->fetchAll("SELECT * FROM ingredients WHERE `id` = ?", [$input->id]);

if ($db->numRows() != 0) {
    $create = $db->run("UPDATE ingredients SET `name-GB` = ?, `alias_of` = ? WHERE `id` = ?", [ucfirst(trim($input->name)), $alias, $input->id]);
    $new = $db->fetchOne("SELECT * FROM ingredients WHERE `id` = ?", [$input->id]);
    echo new PepperResponse()->api(ResponseCode::OK(), json_encode($new));
} else {
    echo new PepperResponse()->api(ResponseCode::NotFound());
}