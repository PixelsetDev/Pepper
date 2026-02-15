<?php

use Pepper\Processes\PepperResponse;
use Starlight\Database\MySQL;
use starlight\HTTP\Types\ResponseCode;

$db = new MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);

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

if (isset($_GET['search']) && trim($_GET['search']) != "") {
    $search = $db->fetchAll("SELECT `id`, `name_gb`, `name_".$language."` FROM ingredients WHERE `name_".$language."` LIKE ? OR `name_gb` LIKE ? LIMIT 25", ['%' . $_GET['search'] . '%', '%' . $_GET['search'] . '%']);
    $total = $db->numRows();
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
