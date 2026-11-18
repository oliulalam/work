<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../../includes/db.php';

define('CLIENT_SECRET', 'dxZM2kmzRYfg2vAe');

date_default_timezone_set("Asia/Dhaka");
$serviceTime = date("Y-m-d H:i:s");
$logFile = __DIR__ . "/game_debug.log";

function logMsg($msg) {
    global $logFile;
    file_put_contents($logFile, date("Y-m-d H:i:s") . " - CALLBACK: " . $msg . PHP_EOL, FILE_APPEND);
}

header('Content-Type: application/json; charset=utf-8');

$rawBody = file_get_contents("php://input");
$post = json_decode($rawBody, true);

if (!$post) {
    http_response_code(400);
    echo json_encode(['code' => 1, 'msg' => 'Invalid JSON']);
    exit;
}

$serial_number  = trim($post['serial_number'] ?? $post['round_id'] ?? '');
$member_account = trim($post['member_account'] ?? '');
$bet_amount     = round(floatval($post['bet_amount'] ?? 0), 2);
$win_amount     = round(floatval($post['win_amount'] ?? 0), 2);
$game_uid       = trim($post['game_uid'] ?? $post['game_code'] ?? '');
$game_round     = trim($post['game_round'] ?? $post['round_id'] ?? '');
$currency       = strtoupper(trim($post['currency'] ?? 'BDT'));
$game_name      = trim($post['game_name'] ?? $post['game_code'] ?? $game_uid);
$action = strtolower(trim($post['action'] ?? 'bet'));

logMsg("action=$action | serial=$serial_number | member=$member_account | bet=$bet_amount | win=$win_amount | game=$game_uid");

$isBalanceReq = !empty($member_account) && empty($serial_number) && empty($game_round) && empty($game_uid) && $bet_amount == 0 && $win_amount == 0;
$hasMember = !empty($member_account);
$hasUserField = !empty($post['user_id'] ?? '') || !empty($post['player_id'] ?? '') || !empty($post['userId'] ?? '');
$hasBalanceMethod = isset($post['method']) && stripos($post['method'], 'balance') !== false;
if ($isBalanceReq || in_array($action, ['balance', 'getbalance', 'get_balance', 'check']) || (!$hasMember && ($hasUserField || $hasBalanceMethod))) {
    logMsg("BALANCE INQUIRY RAW: " . substr($rawBody, 0, 1000));
    $accountField = $member_account ?: $post['user_id'] ?? $post['player_id'] ?? $post['userId'] ?? '';
    $cleanAccount = trim($accountField);
    $uid = 0;
    if (preg_match('/^([1-6])(\d{10})$/', $cleanAccount, $m)) {
        $uid = (int)$m[2];
    } elseif (is_numeric($cleanAccount)) {
        $uid = intval($cleanAccount);
    } elseif (preg_match('/(\d{3,})/', $cleanAccount, $m)) {
        $uid = intval($m[1]);
    }
    if ($uid > 0) {
        $stmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $uid);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $res->num_rows > 0) {
                $bal = round(floatval($res->fetch_assoc()['balance']), 2);
                $stmt->close();
                logMsg("BALANCE INQUIRY | User: $uid | Balance: $bal");
                echo json_encode(['code' => 0, 'msg' => 'Success', 'balance' => $bal, 'serviceNowTime' => $serviceTime]);
                exit;
            }
            $stmt->close();
        }
    }
    echo json_encode(['code' => 1, 'msg' => 'User not found', 'serviceNowTime' => $serviceTime]);
    exit;
}

$signature = $_SERVER['HTTP_X_SIGNATURE'] ?? '';
if (!empty($signature)) {
    $expectedSig = hash_hmac('sha256', $rawBody, CLIENT_SECRET);
    if (!hash_equals($expectedSig, $signature)) {
        logMsg("SIGNATURE MISMATCH");
    }
}

$cleanAccount = trim($member_account);
$user_id = 0;

if (preg_match('/^([1-6])(\d{10})$/', $cleanAccount, $m)) {
    $user_id = (int)$m[2];
} elseif (preg_match('/^88(\d{8})$/', $cleanAccount, $m)) {
    $user_id = (int)$m[1];
} elseif (is_numeric($cleanAccount)) {
    $user_id = (int)$cleanAccount;
} elseif (preg_match('/(\d{3,})/', $cleanAccount, $m)) {
    $user_id = (int)$m[1];
}

if ($bet_amount == 0 && $win_amount == 0) {
    $noopBal = 0;
    if ($user_id > 0) {
        $stmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $res->num_rows > 0) {
                $noopBal = round(floatval($res->fetch_assoc()['balance']), 2);
            }
            $stmt->close();
        }
    }
    echo json_encode(['code' => 0, 'msg' => 'OK (no-op)', 'balance' => $noopBal, 'serviceNowTime' => $serviceTime]);
    exit;
}

if ($user_id <= 0) {
    logMsg("INVALID USER: $member_account");
    echo json_encode(['code' => 1, 'msg' => 'Invalid user', 'serviceNowTime' => $serviceTime]);
    exit;
}

logMsg("USER ID: $user_id");

$conn->query("
    CREATE TABLE IF NOT EXISTS game_bet_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        balakedara INT NOT NULL,
        serial_number VARCHAR(100) NOT NULL,
        game_uid VARCHAR(100) DEFAULT '',
        game_round VARCHAR(100) DEFAULT '',
        game_name VARCHAR(255) DEFAULT '',
        bet_amount DECIMAL(15,2) DEFAULT 0,
        win_amount DECIMAL(15,2) DEFAULT 0,
        balance_before DECIMAL(15,2) DEFAULT 0,
        balance_after DECIMAL(15,2) DEFAULT 0,
        currency VARCHAR(10) DEFAULT 'BDT',
        ggr_amount DECIMAL(15,2) DEFAULT 0,
        action_type VARCHAR(20) DEFAULT '',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_serial (serial_number),
        INDEX idx_user (balakedara),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

if (!empty($serial_number)) {
    $dupCheck = $conn->prepare("SELECT id FROM game_bet_logs WHERE serial_number = ?");
    $dupCheck->bind_param("s", $serial_number);
    $dupCheck->execute();
    $dupRes = $dupCheck->get_result();
    if ($dupRes && $dupRes->num_rows > 0) {
        $dupCheck->close();
        logMsg("DUPLICATE CALLBACK IGNORED: serial=$serial_number user=$user_id");
        $balStmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
        $balStmt->bind_param("i", $user_id);
        $balStmt->execute();
        $curBal = round(floatval($balStmt->get_result()->fetch_assoc()['balance']), 2);
        $balStmt->close();
        echo json_encode(['code' => 0, 'msg' => 'Already processed', 'balance' => $curBal, 'serviceNowTime' => $serviceTime]);
        exit;
    }
    $dupCheck->close();
}

$stmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
if (!$stmt) {
    logMsg("DB ERROR: " . $conn->error);
    echo json_encode(['code' => 1, 'msg' => 'DB error', 'serviceNowTime' => $serviceTime]);
    exit;
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows == 0) {
    $stmt->close();
    logMsg("USER NOT FOUND: $user_id");
    echo json_encode(['code' => 1, 'msg' => 'User not found', 'serviceNowTime' => $serviceTime]);
    exit;
}
$currentBalance = round(floatval($res->fetch_assoc()['balance']), 2);
$stmt->close();

$isSettleAction = in_array($action, ['settle', 'win', 'result', 'payout', 'cancel', 'rollback']);

if ($isSettleAction) {
    $newBalance = round($currentBalance + $win_amount, 2);
    if ($newBalance < 0) {
        $newBalance = 0;
    }
} else {
    $balanceAfterBet = $currentBalance - $bet_amount;

    if ($balanceAfterBet < 0) {
        logMsg("INSUFFICIENT BALANCE: user=$user_id balance=$currentBalance bet=$bet_amount");
        echo json_encode(['code' => 1, 'msg' => 'Insufficient balance', 'balance' => $currentBalance, 'serviceNowTime' => $serviceTime]);
        exit;
    }

    $newBalance = round($balanceAfterBet + $win_amount, 2);
}

$updateStmt = $conn->prepare("UPDATE users SET balance = ? WHERE id = ?");
$updateStmt->bind_param("di", $newBalance, $user_id);
$updateStmt->execute();
$updateStmt->close();

$loss = $bet_amount - $win_amount;
$ggrAmount = ($loss > 0) ? round($loss, 2) : 0;

$insertStmt = $conn->prepare("
    INSERT INTO game_bet_logs 
    (balakedara, serial_number, game_uid, game_round, game_name, bet_amount, win_amount, 
     balance_before, balance_after, currency, ggr_amount, action_type, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
");
if ($insertStmt) {
    $insertStmt->bind_param(
        "issssddddsds",
        $user_id, $serial_number, $game_uid, $game_round, $game_name,
        $bet_amount, $win_amount, $currentBalance, $newBalance, $currency, $ggrAmount, $action
    );
    $insertStmt->execute();
    $insertStmt->close();
}

logMsg("UPDATED: user=$user_id balance=$currentBalance -> $newBalance bet=$bet_amount win=$win_amount");

echo json_encode([
    'code'    => 0,
    'msg'     => 'OK',
    'balance' => $newBalance,
    'serviceNowTime' => $serviceTime
]);
