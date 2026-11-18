<?php
// ProPay server-to-server deposit callback.
define('GAME_API_SKIP_MAINTENANCE', true);
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/propay_gateway_helper.php';

$payload = $_POST;
if (empty($payload)) {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    if (is_array($json)) { $payload = $json; }
}

$result = propay_apply_deposit_callback($conn, $payload);
http_response_code(intval($result['http_code'] ?? 200));
echo $result['message'] ?? 'Success';
?>
