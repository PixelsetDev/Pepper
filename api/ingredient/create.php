<?php

use Pepper\Processes\PepperResponse;
use Starlight\Database\MySQL;
use Starlight\HTTP\Request;
use starlight\HTTP\Types\ResponseCode;
use starlight\Security\XSS;

$db = new MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$input = new Request()->jsonValidated(['name_gb','name_us'], fn() => print new PepperResponse()->api(ResponseCode::BadRequest()));

$name_gb = ucfirst(trim($input->name_gb));
$name_us = ucfirst(trim($input->name_us));

if (new XSS()->hasSpecialChars($name_gb, ['(',')','-',"'","&"])) {
    echo new PepperResponse()->api(ResponseCode::Forbidden());
    exit;
}

if (new XSS()->hasSpecialChars($name_us, ['(',')','-',"'","&"])) {
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

$create = $db->fetchAll("SELECT * FROM ingredients WHERE `name_gb` = ? OR `name_us` = ?", [$name_gb,$name_us]);
$nr1 = $db->numRows();
$create = $db->fetchAll("SELECT * FROM ingredients WHERE `name_gb` = ? OR `name_us` = ?", [$name_gb.'s',$name_us.'s']);
$nr2 = $db->numRows();
$create = $db->fetchAll("SELECT * FROM ingredients WHERE `name_gb` = ? OR `name_us` = ?", [substr($name_gb, 0, -1),substr($name_us, 0, -1)]);
$nr3 = $db->numRows();

if ($nr1 == 0 && $nr2 == 0 && $nr3 == 0) {
    $create = $db->run("INSERT INTO ingredients (`id`, `name_gb`, `name_us` `alias_of`) VALUES (NULL, ?, ?, ?)", [$name_gb, $name_us, $alias]);
    $select = $db->fetchOne("SELECT `id` FROM ingredients WHERE `name_gb` = ? OR `name_us` = ?", [$name_gb, $name_us]);
    echo new PepperResponse()->api(ResponseCode::Created(), json_encode($select));
} else {
    echo new PepperResponse()->api(ResponseCode::Conflict());
}
