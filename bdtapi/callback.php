<?php
/**
 * Netooze Game API - Seamless Wallet Callback Handler
 *
 * Receives webhook callbacks from api.netooze.cloud for:
 *   - balance: Get current player balance
 *   - bet: Deduct bet amount from player wallet
 *   - win: Credit win amount to player wallet
 *
 * Wallet source of truth: users.balance (Main wallet)
 * Any leftover argame_balances is merged into users on every callback.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');

// Use the main database connection file
// Skip maintenance mode for API callbacks (machine-to-machine)
if (!defined('GAME_API_SKIP_MAINTENANCE')) {
    define('GAME_API_SKIP_MAINTENANCE', true);
}
include_once __DIR__ . '/../includes/db.php';

define('NETOOZE_PREFIX', 'RS_KAKI66');
define('NETOOZE_API_TOKEN', 'c5c4df22b0a94132927b9beb8b3442ec');
define('NETOOZE_SECRET_KEY', '8b4b1bde096adfe282a627aeddfc7c5e4669dda7843825f5d7ffbeabdf03632f');

$log_dir = __DIR__ . '/apilogs';
if (!file_exists($log_dir)) {
    mkdir($log_dir, 0777, true);
}

function writeLog($message) {
    global $log_dir;
    $log_file = $log_dir . '/netooze_callback_' . date('Y-m-d') . '.txt';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[{$timestamp}] {$message}\n", FILE_APPEND);
    error_log("[Netooze] " . $message);
}

function sendResponse($status, $balance = null, $error = null, $httpCode = 200) {
    ini_set('serialize_precision', -1);
    
    $response = [
        "status"  => $status,
        "code"    => ($status === "SUCCESS") ? 0 : 1,
        "msg"     => ($error !== null) ? $error : "SUCCESS",
        "message" => ($error !== null) ? $error : "SUCCESS"
    ];
    if ($balance !== null) {
        $bal = round(floatval($balance), 2);
        $response["balance"]      = $bal;
        $response["user_balance"] = $bal;
        $response["money"]        = $bal;
        $response["amount"]       = $bal;
        $response["data"] = [
            "balance"      => $bal,
            "user_balance" => $bal,
            "amount"       => $bal
        ];
    }
    if ($error !== null) {
        $response["error"] = $error;
    }
    
    $json = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    writeLog("Response: " . $json . " (HTTP: {$httpCode})");
    
    http_response_code($httpCode);
    echo $json;
    exit;
}

function updateTurnoverOnBet($conn, $user_id, $bet_amount) {
    $uid = intval($user_id);
    $amount = floatval($bet_amount);
    if ($uid <= 0 || $amount <= 0) return;

    try {
        @$conn->query("UPDATE users SET turnover_completed = COALESCE(turnover_completed, 0) + {$amount} WHERE id = {$uid}");

        @$conn->query("CREATE TABLE IF NOT EXISTS user_withdraw_summary (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            total_bet DECIMAL(15,2) DEFAULT 0.00,
            need_to_bet DECIMAL(15,2) DEFAULT 0.00,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY idx_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        @$conn->query("INSERT INTO user_withdraw_summary (user_id, total_bet, need_to_bet, updated_at) 
                       VALUES ({$uid}, {$amount}, 0, NOW()) 
                       ON DUPLICATE KEY UPDATE 
                       total_bet = total_bet + {$amount}, 
                       need_to_bet = GREATEST(0, need_to_bet - {$amount}), 
                       updated_at = NOW()");

        writeLog("✅ Turnover updated: users.turnover_completed & user_withdraw_summary updated (+{$amount}) for user {$uid}");
    } catch (Throwable $e) {
        writeLog("⚠️ Turnover update exception: " . $e->getMessage());
    }
}

/**
 * Provider often truncates player_id on bet (RS_SELIM_101751 → 10175).
 * Remember full IDs from balance calls and resolve truncated bets.
 */
function playerMapPath() {
    global $log_dir;
    return $log_dir . '/player_id_map.json';
}

function rememberPlayerAliases($userId, $rawId = '') {
    $uid = (string)$userId;
    if ($uid === '') {
        return;
    }
    $path = playerMapPath();
    $map = [];
    if (file_exists($path)) {
        $map = json_decode(@file_get_contents($path), true) ?: [];
    }
    $map[$uid] = $uid;
    // Map truncated prefixes (drop 1+ trailing digits) to the active full user id
    for ($len = strlen($uid) - 1; $len >= 3; $len--) {
        $map[substr($uid, 0, $len)] = $uid;
    }
    
    // Spribe (Aviator) via GameCloud extracts digits and truncates to 5 chars!
    // RS_SR999GAM_224 -> 999224 -> 99922
    if ($rawId !== '') {
        $digits = preg_replace('/[^0-9]/', '', $rawId);
        if ($digits !== '' && $digits !== $uid) {
            $map[$digits] = $uid;
            for ($len = strlen($digits) - 1; $len >= 3; $len--) {
                $map[substr($digits, 0, $len)] = $uid;
            }
        }
    }
    
    @file_put_contents($path, json_encode($map), LOCK_EX);
}

function lookupPlayerAlias($candidate) {
    $cand = (string)$candidate;
    $path = playerMapPath();
    if (!file_exists($path)) {
        return null;
    }
    $map = json_decode(@file_get_contents($path), true) ?: [];
    return isset($map[$cand]) ? (string)$map[$cand] : null;
}

function extractCandidateId($player_id_raw) {
    $raw = trim((string)$player_id_raw);
    if ($raw === '') {
        return '';
    }
    if (preg_match('/^RS_[A-Za-z0-9]+_(.+)$/i', $raw, $m)) {
        return $m[1];
    }
    if (preg_match('/p(\d+)$/i', $raw, $m)) {
        return $m[1];
    }
    if (preg_match('/(\d+)$/', $raw, $m)) {
        return $m[1];
    }
    if (strpos($raw, '_') !== false) {
        $parts = explode('_', $raw);
        return end($parts);
    }
    return $raw;
}

function getWalletRow($conn, $userId) {
    $clean = trim((string)$userId);
    if ($clean === '') return null;

    // 1. Search by numeric ID
    if (is_numeric($clean)) {
        $uid = (int)$clean;
        $stmt = $conn->prepare("SELECT id, balance FROM users WHERE id = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("i", $uid);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $res->num_rows > 0) {
                $row = $res->fetch_assoc();
                $stmt->close();
                return ['id' => (string)$row['id'], 'balance' => floatval($row['balance'])];
            }
            $stmt->close();
        }
    }

    // 2. Search by username or phone
    $stmt = $conn->prepare("SELECT id, balance FROM users WHERE username = ? OR phone = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("ss", $clean, $clean);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $stmt->close();
            return ['id' => (string)$row['id'], 'balance' => floatval($row['balance'])];
        }
        $stmt->close();
    }

    return null;
}

function findUserBySubString($conn, $str) {
    $num = preg_replace('/[^0-9]/', '', (string)$str);
    if ($num === '') return null;

    // 1. Direct match
    $w = getWalletRow($conn, $num);
    if ($w) return $w;

    // 2. Sub-string match by stripping 1..6 leading digits (e.g. 28232 -> 232 or 260232 -> 232)
    $len = strlen($num);
    for ($skip = 1; $skip <= 6; $skip++) {
        if ($len - $skip < 1) break;
        $sub = substr($num, $skip);
        if ($sub !== '' && is_numeric($sub)) {
            $w = getWalletRow($conn, $sub);
            if ($w) return $w;
        }
    }
    return null;
}

function getArgameBalance($conn, $userId) {
    $stmt = $conn->prepare("SELECT balance FROM argame_balances WHERE user_id = ? LIMIT 1");
    if (!$stmt) {
        return 0.0;
    }
    $stmt->bind_param("s", $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $bal = 0.0;
    if ($res && $res->num_rows > 0) {
        $bal = floatval($res->fetch_assoc()['balance']);
    }
    $stmt->close();
    return $bal;
}

/**
 * Resolve provider player_id → real site user id (handles Spribe Aviator mangling & truncation).
 */
function resolvePlayerUserId($conn, $player_id_raw) {
    $raw = trim((string)$player_id_raw);
    writeLog("Resolving raw player_id='{$raw}'");

    if ($raw === '') {
        return null;
    }

    // 1) Direct alias lookup on raw string or candidate
    $mapped = lookupPlayerAlias($raw);
    if ($mapped) {
        $w = getWalletRow($conn, $mapped);
        if ($w) {
            writeLog("Resolved via alias map: {$raw} → {$w['id']}");
            return $w['id'];
        }
    }

    // 2) Strip reseller prefixes (RS_A1GAME_, RS_TAKAA99_, etc.)
    $clean = $raw;
    if (preg_match('/^RS_[A-Za-z0-9]+_(.+)$/i', $clean, $m)) {
        $clean = $m[1];
    } elseif (preg_match('/^RS_[A-Za-z0-9]+-(.+)$/i', $clean, $m)) {
        $clean = $m[1];
    }

    // Direct check of clean string
    $direct = getWalletRow($conn, $clean);
    if ($direct) {
        writeLog("Resolved via direct wallet match: {$clean} → {$direct['id']}");
        rememberPlayerAliases($direct['id'], $raw);
        return $direct['id'];
    }

    // 3) Try candidate extractions (Spribe / Netooze vendor formats)
    $candidates = [];

    // Format like h7f094r260p28203 or p28203 -> extract digits after 'p'
    if (preg_match('/p(\d+)$/i', $clean, $m)) {
        $candidates[] = $m[1];
    }

    // Extract all trailing digits (e.g. h7f094r260p28203 -> 28203 or 26028203)
    if (preg_match('/(\d+)$/', $clean, $m)) {
        $candidates[] = $m[1];
        if (preg_match('/^(?:260|262)(\d+)$/', $m[1], $m2)) {
            $candidates[] = $m2[1];
        }
    }

    // Extract all digits from raw
    $allDigits = preg_replace('/[^0-9]/', '', $raw);
    if ($allDigits !== '') {
        $candidates[] = $allDigits;
        if (preg_match('/^(?:260|262)(\d+)$/', $allDigits, $m3)) {
            $candidates[] = $m3[1];
        }
    }

    $candidates = array_values(array_unique(array_filter($candidates)));

    foreach ($candidates as $cand) {
        // Check alias map for candidate
        $mappedCand = lookupPlayerAlias($cand);
        if ($mappedCand) {
            $w = getWalletRow($conn, $mappedCand);
            if ($w) {
                writeLog("Resolved via candidate alias map: {$cand} → {$w['id']}");
                rememberPlayerAliases($w['id'], $raw);
                return $w['id'];
            }
        }

        // Check wallet row directly or via sub-string suffix match
        $w = findUserBySubString($conn, $cand);
        if ($w) {
            writeLog("Resolved via sub-string wallet match: {$cand} → {$w['id']}");
            rememberPlayerAliases($w['id'], $raw);
            return $w['id'];
        }

        // Try prefix LIKE query on users table
        if (is_numeric($cand) && strlen($cand) >= 3) {
            $like = $cand . '%';
            $stmt = $conn->prepare("SELECT id FROM users WHERE id LIKE ? OR phone LIKE ? ORDER BY balance DESC, id ASC LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("ss", $like, $like);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res && $res->num_rows > 0) {
                    $foundId = (string)$res->fetch_assoc()['id'];
                    $stmt->close();
                    writeLog("Resolved via candidate prefix LIKE: {$cand} → {$foundId}");
                    rememberPlayerAliases($foundId, $raw);
                    return $foundId;
                }
                $stmt->close();
            }
        }
    }

    writeLog("❌ Failed to resolve player_id='{$player_id_raw}'");
    return null;
}

/**
 * Merge leftover 3rd-party (argame) into main wallet, return main balance.
 */
function ensureMainWalletBalance($conn, $userId) {
    $row = getWalletRow($conn, $userId);
    if (!$row) {
        return null;
    }
    return floatval($row['balance']);
}

function setMainWalletBalance($conn, $userId, $newBalance) {
    $stmt = $conn->prepare("UPDATE users SET balance = ? WHERE id = ?");
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param("ds", $newBalance, $userId);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

// Ensure $conn is valid
if (!isset($conn) || !$conn || $conn->connect_error) {
    $DB_HOST = defined('DB_SERVER') ? DB_SERVER : 'localhost';
    $DB_USER = defined('DB_USERNAME') ? DB_USERNAME : 'kakicom_selim';
    $DB_PASS = defined('DB_PASSWORD') ? DB_PASSWORD : 'kakicom_selim';
    $DB_NAME = defined('DB_NAME') ? DB_NAME : 'kakicom_selim';

    try {
        $conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
        if ($conn->connect_error) {
            writeLog("❌ Database connection failed: " . $conn->connect_error);
            sendResponse("FAILED", null, "DATABASE_ERROR", 500);
        }
        $conn->set_charset('utf8mb4');
    } catch (Throwable $e) {
        writeLog("❌ Database exception: " . $e->getMessage());
        sendResponse("FAILED", null, "DATABASE_ERROR", 500);
    }
}

writeLog("========================================");
writeLog("=== Netooze Callback Received ===");
writeLog("Method: " . $_SERVER['REQUEST_METHOD']);
writeLog("URI: " . $_SERVER['REQUEST_URI']);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$raw_input = @file_get_contents('php://input');
writeLog("Raw input: " . $raw_input);

$data = array();
if (!empty($raw_input)) {
    $decoded = json_decode($raw_input, true);
    if (is_array($decoded)) {
        $data = $decoded;
    }
}
if (empty($data) && !empty($_POST)) {
    $data = $_POST;
}
if (empty($data) && !empty($_GET)) {
    $data = $_GET;
}
if (empty($data) && !empty($_REQUEST)) {
    $data = $_REQUEST;
}

if (empty($data)) {
    writeLog("❌ Invalid/empty payload data");
    sendResponse("FAILED", null, "INVALID_PAYLOAD", 400);
}

writeLog("Parsed payload: " . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

$action          = $data['action'] ?? ($data['type'] ?? ($data['event'] ?? ''));
$player_id_raw   = $data['player_id'] ?? ($data['user_id'] ?? ($data['player'] ?? ($data['member_id'] ?? '')));
$amount          = floatval($data['amount'] ?? ($data['balance'] ?? 0));
$provider_txn_id = $data['provider_txn_id'] ?? ($data['serial_number'] ?? ($data['txn_id'] ?? ''));
$proxy_txn_id    = $data['proxy_txn_id'] ?? '';
$game_code       = $data['game_code'] ?? ($data['game_id'] ?? '');
$currency        = $data['currency'] ?? ($data['currency_code'] ?? 'INR');

$bet_amount = isset($data['bet_amount']) ? floatval($data['bet_amount']) : 0;
$win_amount = isset($data['win_amount']) ? floatval($data['win_amount']) : 0;

if (empty($action) || in_array(strtolower($action), ['get_balance', 'user_balance', 'ping', 'check_balance', 'getbalance'])) {
    if ($bet_amount > 0 && $win_amount >= 0) {
        $action = ($win_amount > 0) ? 'bet_and_win' : 'bet';
        if ($action === 'bet') {
            $amount = $bet_amount;
        }
    } elseif ($bet_amount > 0) {
        $action = 'bet';
        $amount = $bet_amount;
    } elseif ($win_amount > 0) {
        $action = 'win';
        $amount = $win_amount;
    } else {
        $action = 'balance';
    }
}

if (!in_array(strtolower($action), ['balance', 'bet', 'win', 'bet_and_win', 'get_balance', 'user_balance', 'ping'], true)) {
    writeLog("❌ Invalid action: {$action}");
    sendResponse("FAILED", null, "INVALID_ACTION", 400);
}

@$conn->query("CREATE TABLE IF NOT EXISTS argame_balances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(100) NOT NULL UNIQUE,
    balance DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$user_id = resolvePlayerUserId($conn, $player_id_raw);
if (!$user_id) {
    writeLog("❌ Could not resolve player_id to user: {$player_id_raw}");
    sendResponse("FAILED", null, "USER_NOT_FOUND", 404);
}

// Remember full id + truncated aliases (so next bet "10175" maps to "101751")
rememberPlayerAliases($user_id, $player_id_raw);

writeLog("Action: {$action} | Player: {$player_id_raw} | Real User ID: {$user_id} | Amount: {$amount} | TXN: {$provider_txn_id}");

// Duplicate Transaction Check
if (($action === 'bet' || $action === 'win' || $action === 'bet_and_win') && !empty($provider_txn_id)) {
    try {
        $dup_stmt = $conn->prepare("SELECT id FROM game_bet_logs WHERE serial_number = ?");
        if ($dup_stmt) {
            $dup_stmt->bind_param("s", $provider_txn_id);
            $dup_stmt->execute();
            $dup_result = $dup_stmt->get_result();
            if ($dup_result && $dup_result->num_rows > 0) {
                $balance = ensureMainWalletBalance($conn, $user_id);
                writeLog("⚠️ Duplicate transaction: {$provider_txn_id} - returning balance {$balance}");
                sendResponse("SUCCESS", $balance);
            }
        }
    } catch (Throwable $e) {
        writeLog("⚠️ Duplicate check error (non-fatal): " . $e->getMessage());
    }
}

$current_balance = ensureMainWalletBalance($conn, $user_id);
if ($current_balance === null) {
    writeLog("❌ User not found in users: {$user_id}");
    sendResponse("FAILED", null, "USER_NOT_FOUND", 404);
}

writeLog("✅ Main wallet balance for user {$user_id}: {$current_balance} {$currency}");

try {
    switch (strtolower($action)) {
        case 'balance':
        case 'get_balance':
        case 'user_balance':
        case 'check_balance':
        case 'getbalance':
        case 'get_user_balance':
        case 'player_balance':
        case 'ping':
        case 'account':
        case 'wallet':
            sendResponse("SUCCESS", $current_balance);
            break;

        case 'bet':
            if ($current_balance < $amount) {
                writeLog("❌ Insufficient funds: balance={$current_balance}, bet={$amount}");
                sendResponse("FAILED", $current_balance, "INSUFFICIENT_FUNDS", 400);
            }
            $new_balance = $current_balance - $amount;
            if (!setMainWalletBalance($conn, $user_id, $new_balance)) {
                sendResponse("FAILED", $current_balance, "UPDATE_FAILED", 500);
            }
            writeLog("✅ BET processed: deducted {$amount}, new balance: {$new_balance}");
            logBetTransaction($conn, $user_id, $game_code, $amount, 0, $provider_txn_id, $proxy_txn_id, $current_balance, $new_balance);

            // --- Update Withdrawal Summary (Turnover) for API games ---
            updateTurnoverOnBet($conn, $user_id, $amount);
            // --- End Update Withdrawal Summary ---

            updateVipAndRebate($conn, $user_id, $amount);
            sendResponse("SUCCESS", $new_balance);
            break;

        case 'bet_and_win':
            if ($bet_amount <= 0 && isset($data['amount'])) {
                $bet_amount = floatval($data['amount']);
            }
            if ($current_balance < $bet_amount) {
                writeLog("❌ Insufficient funds for bet_and_win: balance={$current_balance}, bet={$bet_amount}");
                sendResponse("FAILED", $current_balance, "INSUFFICIENT_FUNDS", 400);
            }
            $new_balance = $current_balance - $bet_amount + $win_amount;
            if (!setMainWalletBalance($conn, $user_id, $new_balance)) {
                sendResponse("FAILED", $current_balance, "UPDATE_FAILED", 500);
            }
            writeLog("✅ BET_AND_WIN processed: deducted {$bet_amount}, credited {$win_amount}, new balance: {$new_balance}");
            logBetTransaction($conn, $user_id, $game_code, $bet_amount, $win_amount, $provider_txn_id, $proxy_txn_id, $current_balance, $new_balance);

            // --- Update Withdrawal Summary (Turnover) for API games ---
            updateTurnoverOnBet($conn, $user_id, $bet_amount);
            // --- End Update Withdrawal Summary ---

            updateVipAndRebate($conn, $user_id, $bet_amount);
            sendResponse("SUCCESS", $new_balance);
            break;

        case 'win':
            $new_balance = $current_balance + $amount;
            if (!setMainWalletBalance($conn, $user_id, $new_balance)) {
                sendResponse("FAILED", $current_balance, "UPDATE_FAILED", 500);
            }
            writeLog("✅ WIN processed: credited {$amount}, new balance: {$new_balance}");
            logBetTransaction($conn, $user_id, $game_code, 0, $amount, $provider_txn_id, $proxy_txn_id, $current_balance, $new_balance);
            sendResponse("SUCCESS", $new_balance);
            break;
    }
} catch (Exception $e) {
    writeLog("❌ Exception: " . $e->getMessage());
    sendResponse("FAILED", null, $e->getMessage(), 500);
}

function logBetTransaction($conn, $user_id, $game_code, $bet_amount, $win_amount, $serial_number, $proxy_txn_id, $balance_before, $balance_after) {
    try {
        $profit_loss = $win_amount - $bet_amount;
        $vendor_code = 'netooze';
        $game_round = $proxy_txn_id ?: $serial_number;

        // Auto-create table if missing
        @$conn->query("CREATE TABLE IF NOT EXISTS game_bet_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(100) NULL,
            balakedara INT NULL,
            vendor_code VARCHAR(50) DEFAULT 'netooze',
            game_code VARCHAR(100) DEFAULT '',
            bet_amount DECIMAL(15,2) DEFAULT 0.00,
            win_amount DECIMAL(15,2) DEFAULT 0.00,
            profit_loss DECIMAL(15,2) DEFAULT 0.00,
            balance_before DECIMAL(15,2) DEFAULT 0.00,
            balance_after DECIMAL(15,2) DEFAULT 0.00,
            serial_number VARCHAR(100) DEFAULT '',
            game_round VARCHAR(100) DEFAULT '',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Inspect existing columns
        $hasUserId = false;
        $hasBalakedara = false;
        $colsRes = @$conn->query("SHOW COLUMNS FROM game_bet_logs");
        if ($colsRes) {
            while ($c = $colsRes->fetch_assoc()) {
                if ($c['Field'] === 'user_id') $hasUserId = true;
                if ($c['Field'] === 'balakedara') $hasBalakedara = true;
            }
        }

        if (!$hasUserId && !$hasBalakedara) {
            @$conn->query("ALTER TABLE game_bet_logs ADD COLUMN user_id VARCHAR(100) NULL AFTER id");
            $hasUserId = true;
        }

        if ($hasUserId && $hasBalakedara) {
            $stmt = $conn->prepare("INSERT INTO game_bet_logs (user_id, balakedara, vendor_code, game_code, bet_amount, win_amount, profit_loss, balance_before, balance_after, serial_number, game_round, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            if ($stmt) {
                $uidInt = (int)$user_id;
                $uidStr = (string)$user_id;
                $stmt->bind_param("sissdddddss", $uidStr, $uidInt, $vendor_code, $game_code, $bet_amount, $win_amount, $profit_loss, $balance_before, $balance_after, $serial_number, $game_round);
                $stmt->execute();
                $stmt->close();
            }
        } elseif ($hasUserId) {
            $stmt = $conn->prepare("INSERT INTO game_bet_logs (user_id, vendor_code, game_code, bet_amount, win_amount, profit_loss, balance_before, balance_after, serial_number, game_round, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            if ($stmt) {
                $uidStr = (string)$user_id;
                $stmt->bind_param("sssdddddss", $uidStr, $vendor_code, $game_code, $bet_amount, $win_amount, $profit_loss, $balance_before, $balance_after, $serial_number, $game_round);
                $stmt->execute();
                $stmt->close();
            }
        } elseif ($hasBalakedara) {
            $stmt = $conn->prepare("INSERT INTO game_bet_logs (balakedara, vendor_code, game_code, bet_amount, win_amount, profit_loss, balance_before, balance_after, serial_number, game_round, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            if ($stmt) {
                $uidInt = (int)$user_id;
                $stmt->bind_param("issdddddss", $uidInt, $vendor_code, $game_code, $bet_amount, $win_amount, $profit_loss, $balance_before, $balance_after, $serial_number, $game_round);
                $stmt->execute();
                $stmt->close();
            }
        }
    } catch (Throwable $e) {
        writeLog("⚠️ Transaction logging error: " . $e->getMessage());
    }
}

function updateVipAndRebate($conn, $user_id, $bet_amount) {
    // VIP and Rebate updating skipped because rebetrec table doesn't exist
}
?>
