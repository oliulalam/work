<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/game_errors.log');
error_reporting(E_ALL);
define('GAME_ACCESS', true);

session_start();
require __DIR__ . '/../../includes/db.php';

/* ================= API CONFIG (GAMING API) ================= */
define('API_KEY',       'cVHxP3DfOty4f7V6');
define('CLIENT_SECRET', 'JXjQH0XZBP33Tjad');
define('GATEWAY_URL',   'https://api.pkapiserver.fun/v1/launch');
define('PROVIDER_URL',  'https://api.pkapiserver.fun/api/v1/providers');
define('GAMES_URL',     'https://api.pkapiserver.fun/api/v1/games');
define('CURRENCY',      'BDT');
define('EXIT_URL',      'http://localhost:8081/');
define('INOUT_CURRENCY','COP');

$PROTOCOL = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$CALLBACK_URL = $PROTOCOL . '://' . $_SERVER['HTTP_HOST'] . preg_replace('/\.php$/', '', strtok($_SERVER['REQUEST_URI'], '?'));

/* ================= HEADERS ================= */
header('Content-Type: application/json; charset=utf-8');
header('Strict-Transport-Security: max-age=31536000');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization, X-Signature');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
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

date_default_timezone_set("Asia/Dhaka");
$serviceTime = date("Y-m-d H:i:s");
$logFile = __DIR__ . "/game_debug.log";

function logMsg($msg) {
    global $logFile;
    file_put_contents($logFile, date("Y-m-d H:i:s") . " - " . $msg . PHP_EOL, FILE_APPEND);
}

/* ================= ROUTING ================= */
$rawBody = file_get_contents("php://input");
$post = json_decode($rawBody, true);

if (!$post && !empty($_POST)) {
    $post = $_POST;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    if ($action === 'providers') {
        handleProviderList();
    } elseif ($action === 'games' && !empty($_GET['provider'])) {
        handleGameList(trim($_GET['provider']));
    } else {
        $post = $_GET;
    }
}

if (empty($post) || !is_array($post)) {
    echo json_encode([
        'code' => 7, 'msg' => 'Invalid request data',
        'debug' => ['method' => $_SERVER['REQUEST_METHOD'], 'get' => $_GET, 'post' => $_POST],
        'serviceNowTime' => $serviceTime
    ]);
    exit;
}

/* ================= DETECT MODE ================= */
$hasMember = isset($post['member_account']);
$hasSerial = isset($post['serial_number']) && $post['serial_number'] !== '';
$hasRound  = (isset($post['round_id']) && $post['round_id'] !== '') || (isset($post['game_round']) && $post['game_round'] !== '');
$hasAction = isset($post['action']);
$actionVal = strtolower(trim($post['action'] ?? ''));

$isBalanceReq = $hasAction && in_array($actionVal, ['balance', 'getbalance', 'get_balance', 'check']);
$isCallback   = !$isBalanceReq && $hasMember && ($hasSerial || $hasRound);
$isLaunch     = (isset($post['gameCode']) || isset($post['game_code'])) && !$isCallback && !$isBalanceReq;

if ($isBalanceReq) {
    handleBalance($post, $conn, $serviceTime);
} elseif ($isCallback) {
    handleCallback($post, $conn, $serviceTime, $rawBody);
} elseif ($isLaunch) {
    $attemptedCurrencies = [];
    $launchResult = attemptLaunch($post, $conn, $serviceTime, $attemptedCurrencies);
    echo json_encode($launchResult);
    exit;
} else {
    if (
        $hasMember ||
        isset($post['user_id']) ||
        isset($post['player_id']) ||
        isset($post['userId']) ||
        isset($post['method']) && stripos($post['method'], 'balance') !== false
    ) {
        handleBalance($post, $conn, $serviceTime);
    }
    echo json_encode(['code' => 7, 'msg' => 'Invalid request', 'serviceNowTime' => $serviceTime]);
    exit;
}

/* ========================= CATALOG ========================= */
function handleProviderList() {
    global $serviceTime;

    $ch = curl_init(PROVIDER_URL . '?api_key=' . API_KEY);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        echo json_encode(['code' => 8, 'msg' => 'Provider fetch failed', 'serviceNowTime' => $serviceTime]);
        exit;
    }

    $data = json_decode($response, true);
    echo json_encode([
        'code' => 0, 'msg' => 'Succeed',
        'data' => $data['data'] ?? $data,
        'serviceNowTime' => $serviceTime
    ]);
    exit;
}

function handleGameList($provider) {
    global $serviceTime;

    $ch = curl_init(GAMES_URL . '?api_key=' . API_KEY . '&provider=' . urlencode($provider));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        echo json_encode(['code' => 8, 'msg' => 'Game list fetch failed', 'serviceNowTime' => $serviceTime]);
        exit;
    }

    $data = json_decode($response, true);
    echo json_encode([
        'code' => 0, 'msg' => 'Succeed',
        'data' => $data['data'] ?? $data,
        'serviceNowTime' => $serviceTime
    ]);
    exit;
}

/* ========================= EXTRACT CURRENCIES FROM ERROR ========================= */
function extractCurrenciesFromError($gwBody, $gwData) {
    $currencies = [];
    $checkFields = [
        $gwData['msg'] ?? '',
        $gwData['provider_response']['msg'] ?? '',
        $gwData['data']['url'] ?? '',
        $gwData['provider_response']['data']['url'] ?? ''
    ];
    foreach ($checkFields as $text) {
        if (preg_match('/Please\s+use\s+\[([A-Z]{2,3}(?:,[A-Z]{2,3})*)\]/i', $text, $m)) {
            $parts = explode(',', strtoupper($m[1]));
            foreach ($parts as $p) {
                $currencies[] = trim($p);
            }
            return $currencies;
        }
    }
    if (preg_match('/Please\s*use\s*\[([A-Z]{2,3}(?:,[A-Z]{2,3})*)\]/i', $gwBody, $m)) {
        $parts = explode(',', strtoupper($m[1]));
        foreach ($parts as $p) {
            $currencies[] = trim($p);
        }
        return $currencies;
    }
    if (preg_match('/Please%2Buse%2B%5B([A-Z]{2,3}(?:%2C[A-Z]{2,3})*)/i', $gwBody, $m)) {
        $parts = explode('%2C', strtoupper($m[1]));
        foreach ($parts as $p) {
            $currencies[] = trim($p);
        }
        return $currencies;
    }
    return $currencies;
}

/* ========================= ATTEMPT LAUNCH (with currency fallback) ========================= */
function attemptLaunch($post, $conn, $serviceTime, &$attemptedCurrencies) {
    global $CALLBACK_URL;

    $gameCode     = trim($post['gameCode'] ?? $post['game_code'] ?? '');
    $gameName     = trim($post['gameName'] ?? $post['game_name'] ?? '');
    $providerCode = trim($post['providerCode'] ?? '');

    /* --- SESSION AUTH --- */
    $user_id = $_SESSION['user_id'] ?? 0;
    if ($user_id <= 0) {
        http_response_code(401);
        return ['code' => 4, 'msg' => 'Not logged in', 'serviceNowTime' => $serviceTime];
    }

    logMsg("AUTH OK | User ID: $user_id");

    /* --- GET BALANCE --- */
    $walletBalance = getBalance($conn, $user_id);

    if ($walletBalance === null) {
        return ['code' => 404, 'msg' => 'Wallet Not Found', 'serviceNowTime' => $serviceTime];
    }

    $prefixMap = ['PKR' => '1', 'INR' => '2', 'BDT' => '3', 'USD' => '4', 'BRL' => '5', 'COP' => '6'];

    $firstCurrency = ($gameCode === '7004') ? INOUT_CURRENCY : CURRENCY;
    $currenciesToTry = [$firstCurrency];

    $callGateway = function($currency) use ($gameCode, $walletBalance, $user_id, $prefixMap, $CALLBACK_URL, $serviceTime) {
        $ts = (string) time();
        $balanceFormatted = (string)(float)$walletBalance;
        $prefix = $prefixMap[$currency] ?? '9';
        $apiUserId = $prefix . str_pad((string)$user_id, 10, '0', STR_PAD_LEFT);
        $sigData = API_KEY . '|' . $apiUserId . '|' . $gameCode . '|' . $balanceFormatted . '|' . $ts;
        $sig = hash_hmac('sha256', $sigData, CLIENT_SECRET);

        logMsg("SIG DATA: $sigData | SIG: $sig");

        $postData = http_build_query([
            'api_key'       => API_KEY,
            'user_id'       => $apiUserId,
            'game_uid'      => $gameCode,
            'balance'       => $balanceFormatted,
            'currency_code' => $currency,
            'language'      => 'en',
            'ts'            => $ts,
            'sig'           => $sig,
            'json'          => '1',
            'notify_url'    => $CALLBACK_URL,
            'return_url'    => EXIT_URL
        ]);

        $ch = curl_init(GATEWAY_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $postData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded']
        ]);
        $gwResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $gwBody = substr($gwResponse, $headerSize);
        curl_close($ch);

        logMsg("GW RESPONSE | Currency: $currency | HTTP: $httpCode | Body(500): " . substr($gwBody, 0, 500));

        $gwData = json_decode($gwBody, true);

        $gameUrl = '';
        if ($httpCode >= 300 && $httpCode < 400) {
            if (preg_match('/^Location:\s+(.+)$/im', $gwResponse, $m)) {
                $gameUrl = trim($m[1]);
            }
        } elseif ($httpCode === 200 && !empty($gwBody)) {
            if ($gwData) {
                $gameUrl = $gwData['url'] ?? $gwData['game_url'] ?? $gwData['data']['url'] ?? '';
                if (empty($gameUrl) && isset($gwData['provider_response']['data']['url'])) {
                    $maybeUrl = $gwData['provider_response']['data']['url'];
                    if (strpos($maybeUrl, '/error?') === false) {
                        $gameUrl = $maybeUrl;
                    }
                }
            }
        }

        $currencyHints = extractCurrenciesFromError($gwBody, $gwData);
        return [$gameUrl, $gwBody, $httpCode, $curlError, $currencyHints];
    };

    $attemptedCurrencies[] = $firstCurrency;
    logMsg("=== LAUNCH Attempt 1 === Game: $gameCode | Currency: $firstCurrency");
    list($gameUrl, $gwBody, $httpCode, $curlError, $currencyHints) = $callGateway($firstCurrency);

    if (!empty($gameUrl)) {
        logMsg("LAUNCH SUCCESS | User: $user_id | Currency: $firstCurrency");
        return [
            'code' => 0, 'msg' => 'Success',
            'data' => [
                'url'        => $gameUrl,
                'returnType' => '1',
                'balance'    => $walletBalance,
                'currency'   => $firstCurrency
            ],
            'serviceNowTime' => $serviceTime
        ];
    }

    if (!empty($currencyHints) && is_array($currencyHints)) {
        foreach ($currencyHints as $hintCurrency) {
            $hintCurrency = strtoupper(trim($hintCurrency));
            if (in_array($hintCurrency, $attemptedCurrencies)) continue;
            $attemptedCurrencies[] = $hintCurrency;
            logMsg("=== LAUNCH Attempt === Game: $gameCode | Currency: $hintCurrency (from error hint)");
            list($gameUrl, $gwBody, $httpCode, $curlError, $currencyHints2) = $callGateway($hintCurrency);
            if (!empty($gameUrl)) {
                logMsg("LAUNCH SUCCESS | User: $user_id | Currency: $hintCurrency");
                return [
                    'code' => 0, 'msg' => 'Success',
                    'data' => [
                        'url'        => $gameUrl,
                        'returnType' => '1',
                        'balance'    => $walletBalance,
                        'currency'   => $hintCurrency
                    ],
                    'serviceNowTime' => $serviceTime
                ];
            }
            if (!empty($currencyHints2) && is_array($currencyHints2)) {
                foreach ($currencyHints2 as $newHint) {
                    $newHint = strtoupper(trim($newHint));
                    if (!in_array($newHint, $attemptedCurrencies) && !in_array($newHint, $currencyHints)) {
                        $currencyHints[] = $newHint;
                    }
                }
            }
        }
    }

    logMsg("LAUNCH FAILED | User: $user_id | Game: $gameCode | Tried: " . implode(',', $attemptedCurrencies));
    return [
        'code' => -1, 'msg' => 'Gateway error',
        'detail' => 'Tried currencies: ' . implode(', ', $attemptedCurrencies) . ' | Raw: ' . substr($gwBody, 0, 500),
        'curl_error' => $curlError ?? '',
        'http_code' => $httpCode ?? 0,
        'serviceNowTime' => $serviceTime
    ];
}

/* ========================= CALLBACK ========================= */
function handleCallback($post, $conn, $serviceTime, $rawBody) {
    $serial_number  = trim($post['serial_number'] ?? $post['round_id'] ?? '');
    $member_account = trim($post['member_account'] ?? '');
    $bet_amount     = round(floatval($post['bet_amount'] ?? 0), 2);
    $win_amount     = round(floatval($post['win_amount'] ?? 0), 2);
    $game_uid       = trim($post['game_uid'] ?? $post['game_code'] ?? '');
    $game_round     = trim($post['game_round'] ?? $post['round_id'] ?? '');
    $currency       = strtoupper(trim($post['currency'] ?? CURRENCY));
    $game_name      = trim($post['game_name'] ?? $post['game_code'] ?? $game_uid);
    $action = strtolower(trim($post['action'] ?? 'bet'));

    logMsg("=== CALLBACK === action=$action | serial=$serial_number | member=$member_account | bet=$bet_amount | win=$win_amount | game=$game_uid");
    logMsg("CALLBACK RAW: " . substr($rawBody, 0, 1000));

    $signature = $_SERVER['HTTP_X_SIGNATURE'] ?? '';
    if (!empty($signature)) {
        $expectedSig = hash_hmac('sha256', $rawBody, CLIENT_SECRET);
        if (!hash_equals($expectedSig, $signature)) {
            logMsg("SIGNATURE MISMATCH");
        }
    }

    $cleanAccount = trim($member_account);

    if (preg_match('/^([1-6])(\d{10})$/', $cleanAccount, $m)) {
        $user_id = (int)$m[2];
    } elseif (preg_match('/^(.+?)([a-z0-9]{3})$/i', $cleanAccount, $sfx)) {
        $suffix = $sfx[2];
        if (preg_match('/[a-zA-Z]/', $suffix)) {
            $cleanAccount = $sfx[1];
        }
    }

    if (empty($user_id)) {
        if (is_numeric($cleanAccount)) {
            $user_id = intval($cleanAccount);
        } elseif (preg_match('/(\d{3,})/', $cleanAccount, $m)) {
            $user_id = intval($m[1]);
        }
    }

    if ($user_id <= 0) {
        echo json_encode(['code' => 1, 'msg' => 'Invalid user', 'serviceNowTime' => $serviceTime]);
        exit;
    }

    if ($bet_amount == 0 && $win_amount == 0) {
        $noopBal = getBalance($conn, $user_id);
        echo json_encode([
            'code' => 0, 'msg' => 'OK (no-op)',
            'balance' => $noopBal,
            'serviceNowTime' => $serviceTime
        ]);
        exit;
    }

    ensureTable($conn);

    $dupKey = $serial_number ?: $game_round;
    if (!empty($dupKey)) {
        $dupStmt = $conn->prepare("
            SELECT id FROM game_bet_logs 
            WHERE (serial_number = ? OR game_round = ?) AND created_at > DATE_SUB(NOW(), INTERVAL 60 SECOND)
            LIMIT 1
        ");
        if ($dupStmt) {
            $dupStmt->bind_param("ss", $serial_number, $game_round);
            $dupStmt->execute();
            $dupResult = $dupStmt->get_result();
            if ($dupResult && $dupResult->num_rows > 0) {
                $dupStmt->close();
                $bal = getBalance($conn, $user_id);
                echo json_encode(['code' => 0, 'msg' => 'Duplicate', 'balance' => $bal, 'serviceNowTime' => $serviceTime]);
                exit;
            }
            $dupStmt->close();
        }
    }

    $currentBalance = getBalance($conn, $user_id);
    if ($currentBalance === null) {
        echo json_encode(['code' => 1, 'msg' => 'User not found', 'serviceNowTime' => $serviceTime]);
        exit;
    }

    $isSettleAction = in_array($action, ['settle', 'win', 'result', 'payout', 'cancel', 'rollback']);

    if ($isSettleAction) {
        $newBalance = round($currentBalance + $win_amount, 2);
        if ($newBalance < 0) {
            $newBalance = 0;
        }
    } else {
        $balanceAfterBet = $currentBalance - $bet_amount;

        if ($balanceAfterBet < 0) {
            echo json_encode([
                'code' => 1, 'msg' => 'Insufficient balance',
                'balance' => $currentBalance,
                'serviceNowTime' => $serviceTime
            ]);
            exit;
        }

        $newBalance = round($balanceAfterBet + $win_amount, 2);
    }

    /* --- Update wallet in users table --- */
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

    echo json_encode([
        'code'    => 0,
        'msg'     => 'OK',
        'balance' => $newBalance,
        'data'    => [
            'user_id'        => $user_id,
            'game'           => $game_name,
            'bet'            => $bet_amount,
            'win'            => $win_amount,
            'balance_before' => $currentBalance,
            'balance_after'  => $newBalance,
            'loss'           => max(0, $loss),
            'currency'       => $currency,
            'action'         => $action
        ],
        'serviceNowTime' => $serviceTime
    ]);
    exit;
}

/* ========================= BALANCE INQUIRY ========================= */
function handleBalance($post, $conn, $serviceTime) {
    $member_account = trim($post['member_account'] ?? $post['user_id'] ?? $post['player_id'] ?? $post['userId'] ?? $post['member'] ?? '');

    logMsg("BALANCE INQUIRY RAW: " . json_encode($post));

    if (empty($member_account)) {
        echo json_encode(['code' => 1, 'msg' => 'Invalid account', 'serviceNowTime' => $serviceTime]);
        exit;
    }

    $cleanAccount = trim($member_account);
    $user_id = 0;

    if (preg_match('/^([1-6])(\d{10})$/', $cleanAccount, $m)) {
        $user_id = (int)$m[2];
    } elseif (is_numeric($cleanAccount)) {
        $user_id = intval($cleanAccount);
    } elseif (preg_match('/(\d{3,})/', $cleanAccount, $m)) {
        $user_id = intval($m[1]);
    }

    if ($user_id <= 0) {
        echo json_encode(['code' => 1, 'msg' => 'Invalid user', 'serviceNowTime' => $serviceTime]);
        exit;
    }

    $balance = getBalance($conn, $user_id);
    if ($balance === null) {
        echo json_encode(['code' => 1, 'msg' => 'User not found', 'serviceNowTime' => $serviceTime]);
        exit;
    }

    logMsg("BALANCE INQUIRY | User: $user_id | Balance: $balance");

    echo json_encode([
        'code' => 0,
        'msg' => 'Success',
        'balance' => $balance,
        'serviceNowTime' => $serviceTime
    ]);
    exit;
}

/* ================= HELPERS ================= */
function getBalance($conn, $user_id) {
    $stmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
    if (!$stmt) return null;
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if (!$res || $res->num_rows == 0) {
        $stmt->close();
        return null;
    }
    $balance = round(floatval($res->fetch_assoc()['balance']), 2);
    $stmt->close();
    return $balance;
}

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
            currency VARCHAR(10) DEFAULT 'BDT',
            ggr_amount DECIMAL(15,2) DEFAULT 0,
            action_type VARCHAR(20) DEFAULT '',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user (balakedara),
            INDEX idx_serial (serial_number),
            INDEX idx_round (game_round),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $cols = ['currency', 'ggr_amount', 'game_name', 'balance_before', 'balance_after', 'action_type'];
    foreach ($cols as $col) {
        $check = $conn->query("SHOW COLUMNS FROM game_bet_logs LIKE '$col'");
        if ($check && $check->num_rows == 0) {
            $type = ($col === 'game_name') ? 'VARCHAR(255) DEFAULT \'\'' :
                    (($col === 'currency') ? 'VARCHAR(10) DEFAULT \'BDT\'' :
                    (($col === 'action_type') ? 'VARCHAR(20) DEFAULT \'\'' : 'DECIMAL(15,2) DEFAULT 0'));
            $conn->query("ALTER TABLE game_bet_logs ADD COLUMN $col $type");
        }
    }
}
