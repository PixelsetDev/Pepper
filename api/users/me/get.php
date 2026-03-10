<?php

use Pepper\Process\Authentication;
use Pepper\Processes\PepperResponse;
use Starlight\Database\MySQL;
use starlight\HTTP\Types\ResponseCode;

$auth = new Authentication(AUTH_ISSUER, AUTH_AUDIENCE, AUTH_JWKS);
$decoded = $auth->authenticate();

if (!$decoded) {
    echo new PepperResponse()->api(ResponseCode::InternalServerError(), null, 'Unable to decode');
} else {
    $user = $auth->getProfileFromIdToken();

    $db = new MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $db->fetchOne('SELECT * FROM `users` WHERE `uuid` = ?', [$decoded->sub]);

    if ($db->numRows() == 0) {
        $db->run('INSERT INTO users (uuid, name, username, email) VALUES (?,?,?,?)', [$user['sub'], $user['name'], $user['username'], $user['email']]);
    } else {
        $db->run('UPDATE users SET name = ?, username = ?, email = ? WHERE uuid = ?', [$user['name'], $user['username'], $user['email'], $user['sub']]);
    }

    $prefs = $db->fetchOne('SELECT * FROM `users_preferences` WHERE `uuid` = ?', [$decoded->sub]);

    $preferences = null;
    if ($prefs && $db->numRows() > 0) {
        $preferences = [
            'activity_privacy'    => (bool) $prefs['activity_privacy'],
            'email_marketing'     => (bool) $prefs['email_marketing'],
            'email_notifications' => (bool) $prefs['email_notifications'],
            'email_reminders'     => (bool) $prefs['email_reminders'],
            'email_updates'       => (bool) $prefs['email_updates'],
        ];
    }

    echo new PepperResponse()->api(ResponseCode::Ok(), json_encode([
        "username"    => $user['username'],
        "name"        => $user['name'],
        "email"       => $user['email'],
        "uuid"        => $user['sub'],
        "preferences" => $preferences
    ]));
}