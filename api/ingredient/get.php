<?php

use Pepper\Processes\PepperResponse;
use Starlight\Database\MySQL;
use starlight\HTTP\Types\ResponseCode;

$db = new MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$searchTerm = trim($_GET['search']);

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

if (isset($searchTerm) && trim($searchTerm) != "") {
    $search = [];

    if ($row = $db->fetchOne("SELECT `id`, `name_".$language."` FROM ingredients WHERE `name_".$language."` = ? OR `name_gb` = ?", [$searchTerm, $searchTerm])) {
        $search[] = $row;
    }
    $total = $db->numRows();

    $results = $db->fetchAll("SELECT `id`, `name_".$language."` FROM ingredients WHERE `name_".$language."` LIKE ? OR `name_gb` LIKE ? LIMIT 25", ['%'.$searchTerm.'%', '%'.$searchTerm.'%']);
    $total = $total + $db->numRows();

    $search = array_merge($search, $results);

    $search = array_values(array_reduce($search, function ($carry, $item) {
        $carry[$item['id']] = $item;
        return $carry;
    }, []));

    if ($total == 0) {
        $search = $db->fetchAll("SELECT `id`, `name_".$language."` FROM ingredients WHERE `name_".$language."` LIKE ? OR `name_gb` LIKE ? LIMIT 25", ['%' . rtrim($searchTerm,'s') . '%', '%' . rtrim($searchTerm,'s') . '%']);
        $total = $db->numRows();
    }
    if ($total == 0) {
        $search = $db->fetchAll("SELECT `id`, `name_".$language."` FROM ingredients WHERE `name_".$language."` LIKE ? OR `name_gb` LIKE ? LIMIT 25", ['%' . rtrim($searchTerm,'es') . '%', '%' . rtrim($searchTerm,'es') . '%']);
        $total = $db->numRows();
    }

    foreach ($search as $key => $item) {
        $search[$key]['name'] = $search[$key]['name_' . $language];
        unset($search[$key]['name_' . $language]);
        if ($search[$key]['name'] == null) {
            $search[$key]['name'] = $search[$key]['name_gb'];
        }
        unset($search[$key]['name_gb']);
    }
    if ($total > 0) {
        echo new PepperResponse()->api(ResponseCode::OK(), json_encode(["results"=>$search,"total"=>$total]));
    } else {
        echo new PepperResponse()->api(ResponseCode::NotFound());
    }
} else {
    echo new PepperResponse()->api(ResponseCode::BadRequest());
}
