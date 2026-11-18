<?php
/**
 * ThePerfectProvider — Combined Launcher + Callback v6.0
 * INR | Direct Redirect | Prefix Strip + Fuzzy ID
 * Branding: PERMANENTLY OFF (clean iframe, no splash/header)
 */

ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/game_errors.log');
error_reporting(E_ALL);
define('GAME_ACCESS', true);

/* ================= CONFIGURATION ================= */
include_once __DIR__ . "/config.php";

/* ================= API CONFIG (THEPERFECTPROVIDER) ================= */
// Set your ThePerfectProvider API Panel details below
if (!defined('API_URL'))       define('API_URL',       'https://win81.online/v1/launch');
if (!defined('API_KEY'))       define('API_KEY',       'I4WZmrePOQdErmwX');
if (!defined('API_SECRET'))    define('API_SECRET',    'BdBFH2nyvVpxOrkq');
if (!defined('PLAYER_PREFIX')) define('PLAYER_PREFIX', 'player_');
if (!defined('CURRENCY'))      define('CURRENCY',      'BDT');
if (!defined('EXIT_URL'))      define('EXIT_URL',      'https://skywin66.net/');
if (!defined('BRAND_MODE'))    define('BRAND_MODE',    'off');

$PROTOCOL = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$request_dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$CALLBACK_URL = $PROTOCOL . '://' . $_SERVER['HTTP_HOST'] . $request_dir . '/callback.php';

/* ================= HEADERS ================= */
header('Content-Type: application/json; charset=utf-8');
header('Strict-Transport-Security: max-age=31536000');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization, X-Signature');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Credentials: true');

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (!empty($origin)) {
    header('Access-Control-Allow-Origin: ' . $origin);
}
header('Vary: Origin');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

date_default_timezone_set("Asia/Kolkata");
$serviceTime = date("Y-m-d H:i:s");
$logFile = __DIR__ . "/game_debug.log";

function logMsg($msg) {
    global $logFile;
    file_put_contents($logFile, date("Y-m-d H:i:s") . " - " . $msg . PHP_EOL, FILE_APPEND);
}

/* ================= HELPERS ================= */
function ensureAbsoluteUrl($url) {
    $url = trim($url);
    if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) return $url;
    return 'https://' . ltrim($url, '/');
}

/* Force branding=off on every launch URL (clean iframe) */
function forceBrandingOff($url) {
    $sep = (strpos($url, '?') === false) ? '?' : '&';
    if (preg_match('/[?&]b=/', $url)) {
        return preg_replace('/([?&])b=[^&]*/', '$1b=brandingoff', $url);
    }
    return $url . $sep . 'b=brandingoff';
}

/* ================= READ BODY ================= */
$rawBody = file_get_contents("php://input");
logMsg("RAW INCOMING REQUEST: " . $rawBody);
$headers = function_exists('getallheaders') ? getallheaders() : $_SERVER;
logMsg("HEADERS: " . json_encode($headers));

$post = json_decode($rawBody, true);

if (!$post) {
    logMsg("ERROR: Invalid JSON received");
    echo json_encode(['code' => 7, 'msg' => 'Invalid JSON', 'serviceNowTime' => $serviceTime]);
    exit;
}

/* ================= DETECT MODE ================= */
// Jalwax callbacks send member_account and action. Balance queries might lack serial_number.
$isCallback = isset($post['member_account']) && (isset($post['serial_number']) || isset($post['action']));
$isLaunch   = isset($post['gameCode']) && !$isCallback;

if ($isCallback) {
    handleCallback($post, $conn, $serviceTime, $rawBody);
} elseif ($isLaunch) {
    handleLaunch($post, $conn, $serviceTime);
} else {
    // Log invalid request for debugging Spribe
    logMsg("INVALID REQUEST DETECTED: " . $rawBody);
    echo json_encode(['code' => 7, 'msg' => 'Invalid request', 'serviceNowTime' => $serviceTime]);
    exit;
}

/* ========================= LAUNCH ========================= */
function handleLaunch($post, $conn, $serviceTime) {
    global $CALLBACK_URL;

    // Log outgoing IP to help the user whitelist the correct server IP
    $outgoing_ip = @file_get_contents('https://api.ipify.org');
    logMsg("SERVER OUTGOING IP: " . ($outgoing_ip ? trim($outgoing_ip) : 'unknown'));

    $gameCode     = trim($post['gameCode'] ?? '');
    $gameName     = trim($post['gameName'] ?? 'Game');
    $providerCode = trim($post['providerCode'] ?? $post['vendorCode'] ?? '');

    logMsg("=== LAUNCH === Game: $gameCode | Provider: $providerCode | Brand: OFF (forced)");

    /* --- JWT AUTH --- */
    $bearer = explode(" ", $_SERVER['HTTP_AUTHORIZATION'] ?? '');
    $token  = $bearer[1] ?? '';

    if (empty($token)) {
        http_response_code(401);
        echo json_encode(['code' => 4, 'msg' => 'Missing Token', 'serviceNowTime' => $serviceTime]);
        exit;
    }

    $is_jwt_valid = is_jwt_valid($token);
    $data_auth    = json_decode($is_jwt_valid, true);

    if (!$data_auth || ($data_auth['status'] ?? '') !== 'Success') {
        http_response_code(401);
        echo json_encode(['code' => 4, 'msg' => 'Permission Denied', 'serviceNowTime' => $serviceTime]);
        exit;
    }

    if (!verify_active_session($conn, $token)) {
        http_response_code(401);
        echo json_encode(['code' => 4, 'msg' => 'Session Inactive', 'serviceNowTime' => $serviceTime]);
        exit;
    }

    $user_id = (int)$data_auth['payload']['id'];
    logMsg("AUTH OK | User ID: $user_id");

    /* --- RECHARGE LOCK (min 200) --- */
    $totalRecharge = get_player_total_recharge($conn, $user_id);

    if ($totalRecharge < 200) {
        logMsg("PLAY LOCKED | User: $user_id | Recharge: $totalRecharge");
        echo json_encode([
            'code' => 403, 'msg' => 'Need Recharge 200',
            'data' => ['requiredRecharge' => 200, 'currentRecharge' => $totalRecharge],
            'serviceNowTime' => $serviceTime
        ]);
        exit;
    }

    /* --- GET BALANCE --- */
    $walletBalance = get_player_balance($conn, $user_id);

    if ($walletBalance === null) {
        echo json_encode(['code' => 404, 'msg' => 'Wallet Not Found', 'serviceNowTime' => $serviceTime]);
        exit;
    }

    if ($walletBalance < 0) {
        echo json_encode([
            'code' => 403, 'msg' => 'Insufficient balance',
            'data' => ['balance' => $walletBalance],
            'serviceNowTime' => $serviceTime
        ]);
        exit;
    }

    /* --- BUILD DIRECT GET LAUNCH URL (win81.online style) --- */
    // Use different prefix per currency so player is registered fresh in the correct currency
    $currPrefix = (CURRENCY === 'PKR') ? '88888' : '99999';
    $userId   = $currPrefix . (string)$user_id;
    $balance  = number_format($walletBalance, 2, '.', '');
    $ts       = time();

    $PROTOCOL     = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $request_dir  = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    $CALLBACK_URL = $PROTOCOL . '://' . $_SERVER['HTTP_HOST'] . $request_dir . '/callback.php';

    // HMAC signature: api_key|user_id|game_uid|balance|ts
    $payload = API_KEY . "|" . $userId . "|" . $gameCode . "|" . $balance . "|" . $ts;
    $sig     = hash_hmac('sha256', $payload, API_SECRET);

    $isMobile = preg_match('/Mobile|Android|BlackBerry|iPhone|Windows Phone/i', $_SERVER['HTTP_USER_AGENT'] ?? '') ? 'mobile' : 'desktop';

    $query = http_build_query([
        'api_key'      => API_KEY,
        'user_id'      => $userId,
        'game_uid'     => $gameCode,
        'balance'      => $balance,
        'currency'     => CURRENCY,
        'currency_code'=> CURRENCY,
        'callback_url' => $CALLBACK_URL,
        'exit_url'     => EXIT_URL,
        'home_url'     => EXIT_URL,
        'ts'           => $ts,
        'sig'          => $sig,
    ]);

    $launchUrl = API_URL . '?' . $query;
    logMsg("LAUNCH SUCCESS | User: $user_id | URL: $launchUrl");

    $finalUrl = $launchUrl;
    if ($isMobile === 'mobile') {
        $PROTOCOL_L = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $BASE_URL   = $PROTOCOL_L . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);
        $finalUrl   = $BASE_URL . '/play.php?url=' . urlencode($launchUrl);
    }

    echo json_encode([
        'code' => 0, 'msg' => 'Success',
        'data' => [
            'url'        => $finalUrl,
            'returnType' => '1',
            'balance'    => $walletBalance,
            'currency'   => CURRENCY
        ],
        'serviceNowTime' => $serviceTime
    ]);
    exit;
}

/* ========================= CALLBACK ========================= */
function handleCallback($post, $conn, $serviceTime, $rawBody) {
    $serial_number  = trim($post['serial_number'] ?? '');
    $member_account = trim($post['member_account'] ?? '');
    $bet_amount     = round(floatval($post['bet_amount'] ?? 0), 2);
    $win_amount     = round(floatval($post['win_amount'] ?? 0), 2);
    $game_uid       = trim($post['game_uid'] ?? '');
    $game_round     = trim($post['game_round'] ?? '');
    $currency       = strtoupper(trim($post['currency'] ?? CURRENCY));
    $game_name      = trim($post['game_name'] ?? $game_uid);
    $action         = trim($post['action'] ?? 'bet');

    logMsg("=== CALLBACK === serial=$serial_number | member=$member_account | bet=$bet_amount | win=$win_amount | game=$game_uid");

    /* --- HMAC Signature --- */
    $signature = $_SERVER['HTTP_X_SIGNATURE'] ?? '';
    if (!empty($signature)) {
        $expectedSig = hash_hmac('sha256', $rawBody, API_SECRET);
        if (!hash_equals($expectedSig, $signature)) {
            logMsg("⚠ SIGNATURE MISMATCH");
        }
    }

    /* --- Extract User ID --- */
    $cleanAccount = trim($member_account);

    $prefixes = array('RS_KAKI66_', 'RS_KAKI66', 'KAKI66_', 'KAKI66', 'RS_TAKAA99_', 'RS_TAKAA99', 'TAKAA99_', 'TAKAA99', 'RS_PATNER77_', 'RS_PATNER77', 'PATNER77_', 'PATNER77', 'hc45d2_', 'hc45d2', '99999', '88888');
    $PREFIX = defined('PLAYER_PREFIX') ? PLAYER_PREFIX : 'player_';
    if (!empty($PREFIX)) { $prefixes[] = $PREFIX; }

    foreach ($prefixes as $prefix) {
        if (stripos($cleanAccount, $prefix) === 0) {
            $cleanAccount = substr($cleanAccount, strlen($prefix));
            $cleanAccount = ltrim($cleanAccount, '_-');
            break;
        }
    }

    $user_id = 0;
    if (ctype_digit($cleanAccount)) {
        $user_id = intval($cleanAccount);
    } elseif (preg_match('/(\d+)/', $cleanAccount, $m)) {
        $user_id = intval($m[1]);
    }

    if ($user_id > 0 && isset($conn) && $conn) {
        try {
            $chk = $conn->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
            if ($chk) {
                $chk->bind_param("i", $user_id);
                $chk->execute();
                $r = $chk->get_result();
                if (!$r || $r->num_rows === 0) {
                    $user_id = 0;
                }
                $chk->close();
            }
        } catch (Throwable $e) {}
    }

    if ($user_id <= 0 && isset($conn) && $conn) {
        try {
            $uStmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR phone = ? LIMIT 1");
            if ($uStmt) {
                $uStmt->bind_param("ss", $member_account, $member_account);
                $uStmt->execute();
                $r = $uStmt->get_result();
                if ($r && $r->num_rows > 0) {
                    $user_id = intval($r->fetch_assoc()['id']);
                } else {
                    $uStmt->bind_param("ss", $cleanAccount, $cleanAccount);
                    $uStmt->execute();
                    $r2 = $uStmt->get_result();
                    if ($r2 && $r2->num_rows > 0) {
                        $user_id = intval($r2->fetch_assoc()['id']);
                    }
                }
                $uStmt->close();
            }
        } catch (Throwable $e) {}
    }

    if ($user_id <= 0) {
        echo json_encode(['status' => 'error', 'protocol' => 'failed', 'code' => 1, 'msg' => 'Invalid user', 'serviceNowTime' => $serviceTime]);
        exit;
    }

    /* --- Skip no-op / Balance query --- */
    if ($bet_amount == 0 && $win_amount == 0) {
        $bal = get_player_balance($conn, $user_id);
        echo json_encode([
            'status' => 'success',
            'timestamp' => time(),
            'code' => 0, 
            'msg' => 'OK (no-op)', 
            'balance' => $bal !== null ? $bal : 0.0,
            'serviceNowTime' => $serviceTime
        ]);
        exit;
    }

    ensureTable($conn);

    /* --- Duplicate Check --- */
    $dupStmt = $conn->prepare("
        SELECT id FROM game_bet_logs 
        WHERE serial_number = ? AND game_round = ? AND bet_amount = ? AND win_amount = ?
        LIMIT 1
    ");
    $dupStmt->bind_param("ssdd", $serial_number, $game_round, $bet_amount, $win_amount);
    $dupStmt->execute();
    $dupResult = $dupStmt->get_result();

    if ($dupResult && $dupResult->num_rows > 0) {
        $dupStmt->close();
        $bal = get_player_balance($conn, $user_id);
        echo json_encode([
            'status' => 'success',
            'timestamp' => time(),
            'code' => 0, 
            'msg' => 'Duplicate', 
            'balance' => $bal, 
            'serviceNowTime' => $serviceTime
        ]);
        exit;
    }
    $dupStmt->close();

    $currentBalance = get_player_balance($conn, $user_id);
    if ($currentBalance === null) {
        echo json_encode([
            'status' => 'error',
            'protocol' => 'failed',
            'code' => 1, 
            'msg' => 'User not found', 
            'serviceNowTime' => $serviceTime
        ]);
        exit;
    }

    $balanceAfterBet = $currentBalance - $bet_amount;
    $newBalance = round($balanceAfterBet + $win_amount, 2);

    if ($balanceAfterBet < 0) {
        echo json_encode([
            'status' => 'error',
            'protocol' => 'failed',
            'code' => 1, 
            'msg' => 'Insufficient balance',
            'balance' => $currentBalance,
            'serviceNowTime' => $serviceTime
        ]);
        exit;
    }

    update_player_balance($conn, $newBalance, $user_id);

    $loss = $bet_amount - $win_amount;
    $ggrAmount = ($loss > 0) ? round($loss, 2) : 0;

    $insertStmt = $conn->prepare("
        INSERT INTO game_bet_logs 
        (balakedara, serial_number, game_uid, game_round, game_name, bet_amount, win_amount, 
         balance_before, balance_after, currency, ggr_amount, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    if ($insertStmt) {
        $insertStmt->bind_param(
            "issssddddsd",
            $user_id, $serial_number, $game_uid, $game_round, $game_name,
            $bet_amount, $win_amount, $currentBalance, $newBalance, $currency, $ggrAmount
        );
        $insertStmt->execute();
        $insertStmt->close();
    }

    // Send GGR deduction to API if loss > 0
    if ($loss > 0) {
        $ggrPayload = json_encode([
            'api_key'   => API_KEY,
            'loss'      => $loss,
            'bet'       => $bet_amount,
            'win'       => $win_amount,
            'game_uid'  => $game_uid,
            'game_round'=> $game_round,
        ]);
        $ch = curl_init('https://win81.online/v1/ggr-deduct');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $ggrPayload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
        ]);
        $ggrResp = curl_exec($ch);
        $ggrHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        logMsg("GGR DEDUCT | HTTP: $ggrHttp | Resp: $ggrResp");
    }

    echo json_encode([
        'status'    => 'success',
        'timestamp' => time(),
        'code'      => 0,
        'msg'       => 'OK',
        'balance'   => $newBalance,
        'data'      => [
            'user_id'        => $user_id,
            'game'           => $game_name,
            'bet'            => $bet_amount,
            'win'            => $win_amount,
            'balance_before' => $currentBalance,
            'balance_after'  => $newBalance,
            'loss'           => max(0, $loss),
            'currency'       => $currency
        ],
        'serviceNowTime' => $serviceTime
    ]);
    exit;
}

/* ================= HELPERS ================= */
// getBalance function removed, balance methods are handled via config.php

function ensureTable($conn) {
    static $done = false;
    if ($done) return;
    $done = true;

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
            currency VARCHAR(10) DEFAULT 'INR',
            ggr_amount DECIMAL(15,2) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user (balakedara),
            INDEX idx_serial (serial_number),
            INDEX idx_round (game_round),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $cols = ['currency', 'ggr_amount', 'game_name', 'balance_before', 'balance_after'];
    foreach ($cols as $col) {
        $check = $conn->query("SHOW COLUMNS FROM game_bet_logs LIKE '$col'");
        if ($check && $check->num_rows == 0) {
            $type = ($col === 'game_name') ? 'VARCHAR(255) DEFAULT \'\'' :
                    (($col === 'currency') ? 'VARCHAR(10) DEFAULT \'INR\'' : 'DECIMAL(15,2) DEFAULT 0');
            $conn->query("ALTER TABLE game_bet_logs ADD COLUMN $col $type");
        }
    }
}
?>
