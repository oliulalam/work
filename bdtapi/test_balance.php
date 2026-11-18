<?php
/**
 * Quick wallet balance tester for GameCloud.
 * Open: /bdtapi/test_balance.php?player_id=RS_PATNER77_77224
 * Or:   /bdtapi/test_balance.php?phone=97350734222
 */
define('GAME_API_SKIP_MAINTENANCE', true);
header('Content-Type: application/json; charset=utf-8');

foreach (array(__DIR__ . '/../includes/db.php') as $path) {
    if (file_exists($path)) {
        include_once $path;
        break;
    }
}
require_once __DIR__ . '/../includes/gamecloud_wallet.php';

if (!isset($conn) || !$conn || $conn->connect_error) {
    echo json_encode(array('status' => 'FAILED', 'error' => 'DB'));
    exit;
}

$raw = isset($_GET['player_id']) ? $_GET['player_id'] : (isset($_GET['phone']) ? $_GET['phone'] : (isset($_GET['user_id']) ? $_GET['user_id'] : ''));
$userId = gc_wallet_resolve_user_id($conn, $raw);
$balance = $userId > 0 ? gc_wallet_sync_user($conn, $userId) : 0;

$detail = array(
    'users' => null,
    'motta' => null,
    'argame' => null
);
if ($userId > 0) {
    $uid = (string)$userId;
    $r = @$conn->query("SELECT balance, username, phone FROM users WHERE id=" . (int)$userId . " LIMIT 1");
    if ($r && $row = $r->fetch_assoc()) {
        $detail['users'] = $row;
    }
    $r = @$conn->query("SELECT motta FROM shonu_kaichila WHERE balakedara='" . $conn->real_escape_string($uid) . "' LIMIT 1");
    if ($r && $row = $r->fetch_assoc()) {
        $detail['motta'] = (float)$row['motta'];
    }
    $r = @$conn->query("SELECT balance FROM argame_balances WHERE user_id='" . $conn->real_escape_string($uid) . "' LIMIT 1");
    if ($r && $row = $r->fetch_assoc()) {
        $detail['argame'] = (float)$row['balance'];
    }
}

echo json_encode(array(
    'status' => 'SUCCESS',
    'input' => $raw,
    'resolved_user_id' => $userId,
    'player_id_for_launch' => $userId > 0 ? ('RS_KAKI66_' . $userId) : null,
    'balance' => $balance,
    'wallets' => $detail,
    'callback_url' => 'https://kaki66.com/bdtapi/callback.php',
    'balance_test_url' => 'https://kaki66.com/bdtapi/callback.php?action=balance&player_id=RS_KAKI66_' . $userId
), JSON_PRETTY_PRINT);
?>
