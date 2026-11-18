<?php
// Optional cron endpoint to sync ProPay auto-withdraw final status.
define('GAME_API_SKIP_MAINTENANCE', true);
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/propay_gateway_helper.php';

$settings = propay_get_settings($conn);
$secret = trim($settings['secret_code'] ?? '');
$expectedKey = $secret !== '' ? substr(hash('sha256', $secret), 0, 16) : '';
$providedKey = trim($_GET['key'] ?? $_POST['key'] ?? '');

header('Content-Type: application/json; charset=utf-8');
if ($expectedKey === '' || !hash_equals($expectedKey, $providedKey)) {
    http_response_code(403);
    echo json_encode(array('status' => false, 'message' => 'Forbidden'));
    exit;
}

$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
$result = propay_sync_pending_withdrawals($conn, 0, $limit);
echo json_encode(array('status' => true, 'result' => $result), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
