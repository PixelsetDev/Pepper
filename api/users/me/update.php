<?php

use Pepper\Process\Authentication;
use Pepper\Processes\PepperResponse;
use Starlight\Database\MySQL;
use Starlight\HTTP\Request;
use starlight\HTTP\Types\ResponseCode;

$auth = new Authentication(AUTH_ISSUER, AUTH_AUDIENCE, AUTH_JWKS);
$decoded = $auth->authenticate(true);

if (!$decoded) {
    echo new PepperResponse()->api(ResponseCode::Unauthorized());
    exit;
}

$db = new MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$data = new Request()->jsonValidated(['activity_privacy', 'email_marketing', 'email_notifications', 'email_reminders', 'email_updates']);

$activity_privacy = $data->activity_privacy ? 0 : 1;
$email_marketing = $data->email_marketing ? 1 : 0;
$email_notifications = $data->email_notifications ? 1 : 0;
$email_reminders = $data->email_reminders ? 1 : 0;
$email_updates = $data->email_updates ? 1 : 0;

$existing = $db->fetchOne('SELECT `id` FROM `users_preferences` WHERE `uuid` = ?', [$decoded->sub]);

if ($existing && $db->numRows() > 0) {
    $db->run(
        'UPDATE `users_preferences`
         SET `activity_privacy` = ?, `email_marketing` = ?, `email_notifications` = ?, `email_reminders` = ?, `email_updates` = ? WHERE `uuid` = ?',
        [$activity_privacy, $email_marketing, $email_notifications, $email_reminders, $email_updates, $decoded->sub]
    );
} else {
    $db->run(
        'INSERT INTO `users_preferences` (`uuid`, `activity_privacy`, `email_marketing`, `email_notifications`, `email_reminders`, `email_updates`) VALUES (?, ?, ?, ?, ?, ?)',
        [$decoded->sub, $activity_privacy, $email_marketing, $email_notifications, $email_reminders, $email_updates]
    );
}

echo new PepperResponse()->api(ResponseCode::Ok());