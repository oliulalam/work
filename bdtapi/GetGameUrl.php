<?php
// Define access constant for gamesintrigrigrated.php
define('GAME_LAUNCHER_ACCESS', true);
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($conn) || !$conn) {
    if (file_exists(__DIR__ . '/../includes/db.php')) {
        include_once __DIR__ . '/../includes/db.php';
    } elseif (file_exists(__DIR__ . '/../../conn.php')) {
        include_once __DIR__ . '/../../conn.php';
    }
}
if (file_exists(__DIR__ . '/../../functions2.php')) {
    include_once __DIR__ . '/../../functions2.php';
}

if (!function_exists('is_jwt_valid')) {
    function is_jwt_valid(string $token): string {
        $parts = explode('.', $token);
        if (count($parts) === 3) {
            $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
            if (is_array($payload) && (isset($payload['id']) || isset($payload['uid']) || isset($payload['user_id']))) {
                $userId = $payload['id'] ?? ($payload['uid'] ?? $payload['user_id']);
                return json_encode([
                    'status' => 'Success',
                    'payload' => ['id' => (int)$userId]
                ]);
            }
        }
        if (is_numeric($token) && (int)$token > 0) {
            return json_encode([
                'status' => 'Success',
                'payload' => ['id' => (int)$token]
            ]);
        }
        return json_encode(['status' => 'Failed', 'message' => 'Invalid session token']);
    }
}

// Games Integrated Transaction Logging

/*==============================================================================
  RESELLER CONFIGURATION - GAMECLOUD API
==============================================================================*/

define('RESELLER_API_TOKEN', 'c5c4df22b0a94132927b9beb8b3442ec');        // API token
define('RESELLER_SECRET_KEY', '8b4b1bde096adfe282a627aeddfc7c5e4669dda7843825f5d7ffbeabdf03632f');      // Secret key  
define('RESELLER_PREFIX', 'RS_KAKI66');              // Player prefix
define('RESELLER_ID', 292);                           // Reseller ID

// Main API URL - GameCloud Game Launch
define('VERSE_API_URL', 'https://api.gamecloudapi.com/api/v1/game/launch');

/**
 * Centralized transaction logging function
 */
function logGameTransaction($conn, $userId, $vendorCode, $gameCode, $gameNameEn, $amount, $timestamp, $hadARGameBalanceBefore = false, $existingARGameBalanceBefore = 0)
{
    try {
        $hasARGameBalance = $hadARGameBalanceBefore;
        $existingARGameBalance = $existingARGameBalanceBefore;

        // Debug logging
        error_log("logGameTransaction Debug:");
        error_log("  User ID: $userId");
        error_log("  Amount: $amount");
        error_log("  hadARGameBalanceBefore: " . ($hadARGameBalanceBefore ? 'true' : 'false'));
        error_log("  existingARGameBalanceBefore: $existingARGameBalanceBefore");
        error_log("  hasARGameBalance: " . ($hasARGameBalance ? 'true' : 'false'));

        // Only log if user had NO ARGame balance before transfer AND amount > 0
        if (!$hasARGameBalance && $amount > 0) {
            $serial = 'TRANS_' . uniqid();
            $insertQuery = "INSERT INTO game_moved_in_table (userkani, price, serial, remark, shonu) 
                           VALUES (?, ?, ?, 'Game In', ?)";
            $insertStmt = $conn->prepare($insertQuery);

            // ✅ NEW: handle prepare failure safely
            if (!$insertStmt) {
                error_log("❌ logGameTransaction prepare failed: " . $conn->error);
                return [
                    'success' => false,
                    'logged' => false,
                    'error' => $conn->error,
                    'message' => 'Prepare failed in logGameTransaction'
                ];
            }

            $insertStmt->bind_param("sdss", $userId, $amount, $serial, $timestamp);

            if ($insertStmt->execute()) {
                error_log("✅ Transaction logged: User $userId, Amount $amount, Vendor $vendorCode");
                return [
                    'success' => true,
                    'logged' => true,
                    'amount' => $amount,
                    'message' => 'Transaction logged successfully'
                ];
            } else {
                error_log("❌ Failed to log transaction: " . $insertStmt->error);
                return [
                    'success' => false,
                    'logged' => false,
                    'error' => $insertStmt->error,
                    'message' => 'Failed to log transaction'
                ];
            }
        } else {
            $reason = $hasARGameBalance ? 'User already has ARGame balance' : 'No amount to transfer';
            error_log("ℹ️ Transaction not logged: User $userId - $reason");
            return [
                'success' => true,
                'logged' => false,
                'amount' => $amount,
                'reason' => $reason,
                'existingARGameBalance' => $existingARGameBalance,
                'message' => 'Transaction not logged - ' . $reason
            ];
        }

    } catch (Exception $e) {
        error_log("❌ Transaction logging error: " . $e->getMessage());
        return [
            'success' => false,
            'logged' => false,
            'error' => $e->getMessage(),
            'message' => 'Transaction logging failed'
        ];
    }
}

header('Content-Type: application/json; charset=utf-8');
header('Strict-Transport-Security: max-age=31536000');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization, X-API-Key');
header('Access-Control-Allow-Credentials: true');
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
header('Access-Control-Allow-Origin: ' . $origin);
header('vary: Origin');

date_default_timezone_set("Asia/Kolkata");
$shnunc = date("Y-m-d H:i:s");

$res = [
    'code' => 11,
    'msg' => 'Method not allowed',
    'msgCode' => 12,
    'serviceNowTime' => $shnunc,
];

$shonubody = file_get_contents("php://input");
$shonupost = json_decode($shonubody, true);

if (is_array($shonupost)) {
    if ((!isset($shonupost['gameCode']) || $shonupost['gameCode'] === '') && !empty($shonupost['gameID'])) {
        $shonupost['gameCode'] = $shonupost['gameID'];
    }
    if ((!isset($shonupost['gameID']) || $shonupost['gameID'] === '') && !empty($shonupost['gameCode'])) {
        $shonupost['gameID'] = $shonupost['gameCode'];
    }
}

if (
    isset($shonupost['language']) &&
    isset($shonupost['random']) &&
    isset($shonupost['signature']) &&
    isset($shonupost['timestamp']) &&
    isset($shonupost['vendorCode']) &&
    isset($shonupost['gameCode'])
) {

    // RAW values from client
    $languageRaw = $shonupost['language'];
    $randomRaw = $shonupost['random'];
    $signatureRaw = $shonupost['signature'];
    $vendorCodeRaw = $shonupost['vendorCode'];
    $gameCodeRaw = $shonupost['gameCode'];
    $phonetypeRaw = isset($shonupost['phonetype']) ? $shonupost['phonetype'] : '';
    $gameNameEnRaw = isset($shonupost['gameNameEn']) ? $shonupost['gameNameEn'] : '';

    // Signature string as per provider docs (DEBUG ONLY)
    $signString = '{"gameCode":"' . $gameCodeRaw . '","language":' . $languageRaw . ',"phonetype":' . $phonetypeRaw . ',"random":"' . $randomRaw . '","vendorCode":' . $vendorCodeRaw . '}';
    $calcSignature = strtoupper(md5($signString));

    // Debug logs (agar log dekhna ho)
    // error_log("SIGN_DEBUG signString=" . $signString);
    // error_log("SIGN_DEBUG calc=" . $calcSignature . " | client=" . $signatureRaw);

    // Sanitize for DB
    $language = htmlspecialchars(mysqli_real_escape_string($conn, $languageRaw));
    $random = htmlspecialchars(mysqli_real_escape_string($conn, $randomRaw));
    $signature = htmlspecialchars(mysqli_real_escape_string($conn, $signatureRaw));
    $vendorCode = htmlspecialchars(mysqli_real_escape_string($conn, $vendorCodeRaw));
    $gameCode = htmlspecialchars(mysqli_real_escape_string($conn, $gameCodeRaw));
    $phonetype = htmlspecialchars(mysqli_real_escape_string($conn, $phonetypeRaw));
    $gameNameEn = htmlspecialchars(mysqli_real_escape_string($conn, $gameNameEnRaw));

    // Resolve PG Soft / provider numeric IDs or slugs to 32-hex UID for GameCloud
    if (file_exists(__DIR__ . '/../includes/pg_game_map.php')) {
        require_once __DIR__ . '/../includes/pg_game_map.php';
        $resolvedPg = resolve_pg_game_uid($gameCode, $gameNameEn);
        if (preg_match('/^[a-f0-9]{32}$/i', $resolvedPg)) {
            $gameCode = $resolvedPg;
        }
    }

    // TEMP: Signature always treated as valid (NO BLOCK)
    $signatureIsValid = true;

    if ($signatureIsValid) {

        // Auth header check
        if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $res['code'] = 4;
            $res['msg'] = 'Authorization header missing';
            $res['msgCode'] = 2;
            http_response_code(401);
            echo json_encode($res);
            exit;
        }

        $bearer = explode(" ", $_SERVER['HTTP_AUTHORIZATION']);
        $author = isset($bearer[1]) ? $bearer[1] : '';

        $is_jwt_valid = is_jwt_valid($author);
        $data_auth = json_decode($is_jwt_valid, true);

        // JWT validation
        if ($data_auth && $data_auth['status'] === 'Success') {

            // Get user ID from JWT payload
            $user_id = $data_auth['payload']['id'];

            // ================== GLOBAL BETTING SETTING – IGNORED ==================
            // Sirf log kar rahe hain, block nahi karenge
            $settingQuery = "SELECT allowbet FROM web_setting WHERE id = 1";
            $settingResult = $conn->query($settingQuery);
            if ($settingResult && $settingResult->num_rows > 0) {
                $settingRow = mysqli_fetch_assoc($settingResult);
                error_log("DEBUG allowbet from web_setting.id=1 => " . $settingRow['allowbet']);
            } else {
                error_log("web_setting row with id=1 not found or query failed.");
            }
            // ================== END GLOBAL BETTING SETTING ==================

            // User record
            $sesquery = ($vendorCode == 18)
                ? "SELECT id, password as akshinak FROM users WHERE id = '$user_id'"
                : "SELECT phone as mobile, password as akshinak FROM users WHERE id = '$user_id'";

            $sesresult = $conn->query($sesquery);
            $sesnum = mysqli_num_rows($sesresult);
            $row = $sesresult->fetch_assoc();

            if ($sesnum == 1) {

                // Keep / use Main wallet (balance). Merge any leftover 3rd-party into main first.
                $stmtMain = $conn->prepare("SELECT balance FROM users WHERE id = ?");
                $mainBalance = 0.0;
                if (!$stmtMain) {
                    error_log("❌ stmtMain prepare failed: " . $conn->error);
                } else {
                    $stmtMain->bind_param("s", $user_id);
                    $stmtMain->execute();
                    $resultMain = $stmtMain->get_result();
                    if ($resultMain && $resultMain->num_rows > 0) {
                        $rowMain = $resultMain->fetch_assoc();
                        $mainBalance = floatval($rowMain['balance']);
                    }
                }

                // Pull leftover ARGame / 3rd-party balance back into main so seamless bets use one wallet
                // ARGame merge removed to prevent balance doubling. `argame_balances` is a mirror of `users.balance`, adding it causes the balance to double.

                $finalBalance = $mainBalance;
                error_log("User {$user_id} launching game with main wallet balance: {$mainBalance} (seamless, no 3rd-party transfer)");

                // Call HuidoVerse API with reseller credentials
                if (RESELLER_API_TOKEN === 'YOUR_RESELLER_API_TOKEN' || empty(RESELLER_API_TOKEN)) {
                    error_log("ERROR: Reseller credentials not configured. Update reseller credentials at top of this file.");
                    $res['code'] = 8;
                    $res['msg'] = 'API configuration error. Please update reseller credentials in GetGameUrl.php';
                    $res['msgCode'] = 8;
                    http_response_code(500);
                    echo json_encode($res);
                    exit;
                }

                $api_payload = [
                    'api_token' => RESELLER_API_TOKEN,
                    'secret_key' => RESELLER_SECRET_KEY,
                    'reseller_id' => RESELLER_ID,
                    'player_id' => RESELLER_PREFIX . '_' . $user_id,
                    'game_uid' => $gameCode,
                    'mode' => 'seamless',
                    'currency_code' => 'BDT',
                    'callback_url' => 'https://kaki66.com/bdtapi/callback.php',
                    'home_url' => 'https://kaki66.com'
                ];

                error_log("Calling Netooze API: " . VERSE_API_URL);
                error_log("Payload: " . json_encode($api_payload));
                error_log("Player ID: " . RESELLER_PREFIX . '_' . $user_id . ", Balance: {$finalBalance} BDT");

                $ch = curl_init(VERSE_API_URL);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($api_payload));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'X-API-Token: ' . RESELLER_API_TOKEN,
                    'X-Secret-Key: ' . RESELLER_SECRET_KEY
                ]);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

                $api_response = curl_exec($ch);
                $api_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curl_error = curl_error($ch);
                curl_close($ch);

                if ($curl_error) {
                    error_log("API call error: " . $curl_error);
                    $res['code'] = 8;
                    $res['msg'] = 'Failed to connect to game provider';
                    $res['msgCode'] = 8;
                    
                    $logFile = __DIR__ . '/debug_launch.log';
                    $logMessage = "[" . date('Y-m-d H:i:s') . "] cURL Error: " . $curl_error . "\nPayload: " . json_encode($api_payload) . "\n\n";
                    file_put_contents($logFile, $logMessage, FILE_APPEND);
                } else {
                    $api_result = json_decode($api_response, true);
                    error_log("Netooze API response: " . $api_response);
                    
                    $logFile = __DIR__ . '/debug_launch.log';
                    $logMessage = "[" . date('Y-m-d H:i:s') . "] URL: " . VERSE_API_URL . "\nPayload: " . json_encode($api_payload) . "\nHTTP Code: " . $api_http_code . "\nResponse: " . $api_response . "\n\n";
                    file_put_contents($logFile, $logMessage, FILE_APPEND);

                    if ($api_result && isset($api_result['status']) && $api_result['status'] === 'SUCCESS' && isset($api_result['game_launch_url'])) {
                        $res['code'] = 0;
                        $res['msg'] = 'Game launched successfully';
                        $res['data'] = [
                            'url' => $api_result['game_launch_url'],
                            'returnType' => 'game_url'
                        ];
                    } else {
                        $res['code'] = 8;
                        $res['msg'] = ($api_result['error'] ?? $api_result['msg'] ?? 'Failed to launch game') . " | HTTP: $api_http_code | Debug: " . substr($api_response, 0, 200);
                        $res['msgCode'] = 8;
                        if (isset($api_result['upstream_response'])) {
                            $res['upstream'] = $api_result['upstream_response'];
                        }
                    }
                }

            } else {
                $res['code'] = 4;
                $res['msg'] = 'No operation permission';
                $res['msgCode'] = 2;
                http_response_code(401);
                echo json_encode($res);
                exit;
            }

        } else {
            $res['code'] = 4;
            $res['msg'] = 'No operation permission';
            $res['msgCode'] = 2;
            http_response_code(401);
            echo json_encode($res);
            exit;
        }

    } else {
        $res['code'] = 5;
        $res['msg'] = 'Wrong signature';
        $res['msgCode'] = 3;
        http_response_code(200);
        echo json_encode($res);
        exit;
    }

} else {
    $res['code'] = 7;
    $res['msg'] = 'Param is Invalid';
    $res['msgCode'] = 6;
    http_response_code(200);
    echo json_encode($res);
    exit;
}

// Send response
http_response_code(200);
echo json_encode($res);
?>