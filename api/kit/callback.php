<?php
/**
 * win81.online (ThePerfectProvider) Callback Handler — Fully Corrected & Stable
 * DB Sync Crashes Fixed | Game Log Checks Fixed | PKR GGR Deduct Fixed | Correct JSON Response Format
 */
ini_set('display_errors', 0);
error_reporting(0);
date_default_timezone_set("Asia/Kolkata");

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization, X-Signature');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$serviceTime = date('Y-m-d H:i:s');

// --- DETECT & LOAD CONFIG ---
define('GAME_ACCESS', true);
$configPaths = [
    __DIR__ . '/config.php',                              // Same folder (deepanshu/api/webapi/)
    __DIR__ . '/Teacherlara/api/webapi/config.php',       // luckywin site subfolder
    __DIR__ . '/../../../deepanshu/api/webapi/config.php',// From root → deepanshu
    __DIR__ . '/../../api/webapi/config.php',
    __DIR__ . '/../config.php',
    __DIR__ . '/../../config.php',
];

$configLoaded = false;
foreach ($configPaths as $path) {
    if (file_exists($path)) {
        include_once $path;
        $configLoaded = true;
        $loadedConfigPath = $path;
        break;
    }
}

// Set up log directory based on where config was found
$logDir = $configLoaded ? dirname($loadedConfigPath) : __DIR__;
function fastLog($msg) {
    global $logDir;
    @file_put_contents($logDir . '/callback_debug.log', date('Y-m-d H:i:s') . ' - ' . $msg . "\n", FILE_APPEND | LOCK_EX);
}

if (!$configLoaded || !isset($conn) || $conn->connect_error) {
    fastLog("CONFIG LOAD FAILED");
    echo json_encode(['code' => 7, 'msg' => 'Config error', 'serviceNowTime' => $serviceTime]);
    exit;
}

// --- READ REQUEST ---
$rawBody = file_get_contents("php://input");
fastLog("CALLBACK RAW: " . $rawBody);

$post = json_decode($rawBody, true);
if (!$post) {
    fastLog("Invalid JSON raw: " . $rawBody);
    echo json_encode(['code' => 7, 'msg' => 'Invalid JSON', 'serviceNowTime' => $serviceTime]);
    exit;
}

// --- PARSE ---
$member_account = trim($post['member_account'] ?? '');
$bet_amount     = round(floatval($post['bet_amount'] ?? 0), 2);
$win_amount     = round(floatval($post['win_amount'] ?? 0), 2);
$serial_number  = trim($post['serial_number'] ?? '');
$game_round     = trim($post['game_round'] ?? '');
$game_uid       = trim($post['game_uid'] ?? '');
$game_name      = trim($post['game_name'] ?? $game_uid);
$currency       = strtoupper(trim($post['currency'] ?? 'INR'));
$action         = strtolower(trim($post['action'] ?? 'bet'));

// --- EXTRACT USER ID ---
$cleanAccount = trim($member_account);

// 1. Strip all known reseller / merchant / agent / currency prefixes
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
// Check direct numeric ID
if (ctype_digit($cleanAccount)) {
    $user_id = intval($cleanAccount);
} elseif (preg_match('/(\d+)/', $cleanAccount, $m)) {
    $user_id = intval($m[1]);
}

// Verify user exists in DB; if not, search by username or phone
if ($user_id > 0 && isset($conn) && $conn) {
    try {
        $chk = $conn->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
        if ($chk) {
            $chk->bind_param("i", $user_id);
            $chk->execute();
            $r = $chk->get_result();
            if (!$r || $r->num_rows === 0) {
                $user_id = 0; // Not found by ID, fall back to username/phone
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
    fastLog("ERROR: Invalid user extracted. member_account: $member_account -> clean: $cleanAccount");
    echo json_encode(['code' => 1, 'msg' => 'Invalid user', 'serviceNowTime' => $serviceTime]);
    exit;
}

fastLog("Extracted user_id: $user_id | Action: $action | Bet: $bet_amount | Win: $win_amount");

// --- GET BALANCE (single fast query) ---
$currentBalance = null;
$balanceTable = '';

// Try users table first (active database)
try {
    $stmt = $conn->prepare("SELECT balance FROM users WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $currentBalance = round(floatval($res->fetch_assoc()['balance']), 2);
            $balanceTable = 'users';
        }
        $stmt->close();
    }
} catch (Throwable $e) {
    fastLog("Primary users table query skipped/failed: " . $e->getMessage());
}

// Fallback to shonu_kaichila table safely
if ($currentBalance === null) {
    try {
        $stmt = $conn->prepare("SELECT motta FROM shonu_kaichila WHERE balakedara = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $res->num_rows > 0) {
                $currentBalance = round(floatval($res->fetch_assoc()['motta']), 2);
                $balanceTable = 'shonu_kaichila';
            }
            $stmt->close();
        }
    } catch (Throwable $e) {
        fastLog("Fallback shonu_kaichila table query skipped/failed: " . $e->getMessage());
    }
}

if ($currentBalance === null) {
    fastLog("ERROR: User $user_id not found in shonu_kaichila or users");
    echo json_encode(['code' => 1, 'msg' => 'User not found', 'balance' => 0, 'serviceNowTime' => $serviceTime]);
    exit;
}

fastLog("Current Balance: $currentBalance in table $balanceTable");

// --- BALANCE QUERY (no-op) ---
$isBalanceQuery = ($bet_amount == 0 && $win_amount == 0) || 
                  in_array($action, ['getbalance', 'get_balance', 'balance']);

if ($isBalanceQuery) {
    fastLog("Balance query handled. Returning $currentBalance");
    echo json_encode([
        'code' => 0, 
        'msg' => 'OK', 
        'balance' => $currentBalance, 
        'serviceNowTime' => $serviceTime
    ]);
    exit;
}

// --- DUPLICATE CHECK ---
if (!empty($serial_number)) {
    try {
        $dupStmt = $conn->prepare("SELECT balance_after FROM game_bet_logs WHERE balakedara = ? AND serial_number = ? LIMIT 1");
        if ($dupStmt) {
            $dupStmt->bind_param("is", $user_id, $serial_number);
            $dupStmt->execute();
            $dupRes = $dupStmt->get_result();
            if ($dupRes && $dupRes->num_rows > 0) {
                $bal = round(floatval($dupRes->fetch_assoc()['balance_after']), 2);
                fastLog("DUPLICATE REQUEST DETECTED: serial_number = $serial_number. Returning balance = $bal");
                echo json_encode([
                    'code' => 0, 
                    'msg' => 'Duplicate', 
                    'balance' => $bal, 
                    'serviceNowTime' => $serviceTime
                ]);
                $dupStmt->close();
                exit;
            }
            $dupStmt->close();
        }
    } catch (Throwable $e) {
        fastLog("Duplicate check skipped/failed: " . $e->getMessage());
    }
}

// --- INSUFFICIENT CHECK ---
$balanceAfterBet = $currentBalance - $bet_amount;
fastLog("Calculation: $currentBalance - $bet_amount = $balanceAfterBet");
if ($balanceAfterBet < 0) {
    fastLog("INSUFFICIENT BALANCE for user $user_id. Bet: $bet_amount | Bal: $currentBalance");
    echo json_encode([
        'code' => 10025, 
        'msg' => 'Insufficient balance', 
        'balance' => $currentBalance, 
        'serviceNowTime' => $serviceTime
    ]);
    exit;
}

$newBalance = round($balanceAfterBet + $win_amount, 2);

// --- UPDATE BALANCE ---
try {
    if ($balanceTable === 'shonu_kaichila') {
        $stmt = @$conn->prepare("UPDATE shonu_kaichila SET motta = ? WHERE balakedara = ?");
    } else {
        $stmt = @$conn->prepare("UPDATE users SET balance = ? WHERE id = ?");
    }
    if ($stmt) {
        $stmt->bind_param("di", $newBalance, $user_id);
        $stmt->execute();
        $stmt->close();
        fastLog("DB primary update successful");
    } else {
        fastLog("Failed to prepare primary update statement");
    }
} catch (Throwable $e) {
    fastLog("DB primary update CRASHED: " . $e->getMessage());
}

// Also sync the other table — only if column exists (silent on error)
try {
    if ($balanceTable === 'shonu_kaichila') {
        // Check if users table has 'balance' column before updating
        $colCheck = @$conn->query("SHOW COLUMNS FROM users LIKE 'balance'");
        if ($colCheck && $colCheck->num_rows > 0) {
            @$conn->query("UPDATE users SET balance = $newBalance WHERE id = $user_id");
        }
        // Also try 'motta' column in users table
        $colCheck2 = @$conn->query("SHOW COLUMNS FROM users LIKE 'motta'");
        if ($colCheck2 && $colCheck2->num_rows > 0) {
            @$conn->query("UPDATE users SET motta = $newBalance WHERE balakedara = $user_id");
        }
    } else {
        @$conn->query("UPDATE shonu_kaichila SET motta = $newBalance WHERE balakedara = $user_id");
    }
} catch (Throwable $e) {
    // Silent — sync is best-effort only
}

// --- RESPONSE FIRST (speed priority) ---
echo json_encode([
    'code' => 0,
    'msg' => 'OK',
    'balance' => $newBalance,
    'serviceNowTime' => $serviceTime
]);
fastLog("Response sent: code=0, balance=$newBalance");

// --- LOG BET & GGR DEDUCTION AFTER RESPONSE (non-blocking) ---
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
}

$loss = $bet_amount - $win_amount;

// ─── GGR DEDUCTION ──────────────────────────────────────────────────────────
// win81.online GGR endpoint requires Bearer token and API Secret headers to authenticate.
$ggrKey    = defined('API_KEY') ? API_KEY : 'I4WZmrePOQdErmwX';
$ggrSecret = defined('API_SECRET') ? API_SECRET : 'BdBFH2nyvVpxOrkq';

if (!defined('GGR_DEDUCT_URL'))  define('GGR_DEDUCT_URL',  'https://win81.online/v1/ggr-deduct');

// Currency conversion: PKR → INR (1 INR = 3.50 PKR)
$CURRENCY_RATES = ['PKR' => 3.50, 'BDT' => 1.30, 'NPR' => 1.60, 'INR' => 1.00];
$convRate = $CURRENCY_RATES[strtoupper($currency)] ?? 1.00;

if ($loss > 0) {
    try {
        // Convert to INR if needed
        $ggrLoss = round($loss       / $convRate, 2);
        $ggrBet  = round($bet_amount / $convRate, 2);
        $ggrWin  = round($win_amount / $convRate, 2);

        fastLog("GGR | $currency loss=$loss → INR loss=$ggrLoss (rate=1INR={$convRate}PKR)");

        $ggrPayload = json_encode([
            'api_key'    => $ggrKey,
            'loss'       => $ggrLoss,
            'bet'        => $ggrBet,
            'win'        => $ggrWin,
            'currency'   => 'INR',
            'game_uid'   => $game_uid,
            'game_round' => $game_round,
        ]);

        $ch = curl_init(GGR_DEDUCT_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $ggrPayload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $ggrKey,
                'X-API-Secret: ' . $ggrSecret
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
        ]);
        $ggrResp = curl_exec($ch);
        $ggrHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fastLog("GGR DEDUCT | HTTP: $ggrHttp | Resp: $ggrResp");
    } catch (Throwable $e) {
        fastLog("GGR DEDUCT CRASHED: " . $e->getMessage());
    }
}

// --- LOG BET ---
try {
    // Check if game_bet_logs table exists before inserting
    $tableCheck = @$conn->query("SHOW TABLES LIKE 'game_bet_logs'");
    if ($tableCheck && $tableCheck->num_rows > 0) {
        $ggrAmount = ($loss > 0) ? round($loss, 2) : 0;
        $insertStmt = @$conn->prepare("
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
            fastLog("Game bet log inserted");

            if ($bet_amount > 0) {
                @$conn->query("UPDATE users SET turnover_completed = COALESCE(turnover_completed, 0) + " . floatval($bet_amount) . " WHERE id = " . intval($user_id));
            }

            if ($bet_amount > 0 && function_exists('referral_process_bet_reward')) {
                $ref_helper = __DIR__ . '/../../includes/referral_helper.php';
                if (file_exists($ref_helper)) {
                    require_once $ref_helper;
                    referral_process_bet_reward($conn, $user_id, $bet_amount, $serial_number ?: ('kit_' . $game_round));
                }
            }
        }
    }
} catch (Throwable $e) {
    // Silent
}

$conn->close();
fastLog("Connection closed");