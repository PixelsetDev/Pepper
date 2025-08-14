
<?php
if (!in_array('OCB:MODERATE', $user->roles ?? [])) {
    echo json_encode([
        "status" => ["code" => "403 Forbidden", "message" => null],
        "data" => null,
        "count" => 0
    ]);
    exit;
}