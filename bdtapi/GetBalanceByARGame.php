<?php 
// Robust DB Connection Loader
if (!isset($conn) || !$conn || $conn->connect_error) {
    $db_paths = [
        __DIR__ . '/../includes/db.php',
        __DIR__ . '/../../includes/db.php',
        __DIR__ . '/../conn.php',
        __DIR__ . '/../../conn.php'
    ];
    foreach ($db_paths as $path) {
        if (file_exists($path)) {
            include_once $path;
            if (isset($conn) && $conn && !$conn->connect_error) {
                break;
            }
        }
    }
}
if (!isset($conn) || !$conn || $conn->connect_error) {
    $host = 'localhost';
    $user = 'kakicom_selim';
    $pass = 'kakicom_selim';
    $dbname = 'kakicom_selim';
    $conn = @new mysqli($host, $user, $pass, $dbname);
    if (!$conn->connect_error) {
        $conn->set_charset("utf8mb4");
    }
}

if (!function_exists('is_jwt_valid')) {
    function is_jwt_valid(string $token): string {
        $token = trim($token);
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

header('Content-Type: application/json; charset=utf-8');
header('Strict-Transport-Security: max-age=31536000');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');
header('Access-Control-Allow-Credentials: true');
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
header('Access-Control-Allow-Origin: ' . $origin);
header('vary: Origin');

date_default_timezone_set("Asia/Kolkata");
$shnunc = date("Y-m-d H:i:s");

$res = [
    'code' => 4,
    'msg' => 'No operation permission',
    'msgCode' => 2,
    'serviceNowTime' => $shnunc
];

// Extract JWT Token
$author = '';
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $bearer = explode(" ", $_SERVER['HTTP_AUTHORIZATION']);
    $author = $bearer[1] ?? '';
}

if (empty($author)) {
    $shonubody = file_get_contents("php://input");
    $shonupost = json_decode($shonubody, true) ?: [];
    $author = $shonupost['token'] ?? ($_GET['token'] ?? '');
}

if (!empty($author)) {
    $is_jwt_valid = is_jwt_valid($author);
    $data_auth = json_decode($is_jwt_valid, true);

    if ($data_auth && ($data_auth['status'] === 'Success' || isset($data_auth['payload']['id']))) {
        $user_id = $data_auth['payload']['id'];

        // Primary check: users table
        $userBalance = 0.0;
        $uStmt = $conn->prepare("SELECT balance FROM users WHERE id = ? LIMIT 1");
        if ($uStmt) {
            $uStmt->bind_param("i", $user_id);
            $uStmt->execute();
            $uRes = $uStmt->get_result();
            if ($uRes && $uRes->num_rows > 0) {
                $userBalance = floatval($uRes->fetch_assoc()['balance']);
            }
            $uStmt->close();
        }

        // 1. Get balance from argame_balances
        $arBalance = 0.0;
        $arStmt = $conn->prepare("SELECT balance FROM argame_balances WHERE user_id = ?");
        if ($arStmt) {
            $arStmt->bind_param("s", $user_id);
            $arStmt->execute();
            $arRes = $arStmt->get_result();
            if ($arRes && $arRes->num_rows > 0) {
                $arRow = $arRes->fetch_assoc();
                $arBalance = floatval($arRow['balance']);
            }
            $arStmt->close();
        }

        // 2. Get balance from shonu_kaichila (motta)
        $mainBalance = 0.0;
        $mStmt = $conn->prepare("SELECT motta FROM shonu_kaichila WHERE balakedara = ?");
        if ($mStmt) {
            $mStmt->bind_param("s", $user_id);
            $mStmt->execute();
            $mRes = $mStmt->get_result();
            if ($mRes && $mRes->num_rows > 0) {
                $mRow = $mRes->fetch_assoc();
                $mainBalance = floatval($mRow['motta']);
            }
            $mStmt->close();
        }

        if ($userBalance > 0) {
            $mainBalance = max($mainBalance, $userBalance);
            // Sync to shonu_kaichila if needed
            @$conn->query("UPDATE shonu_kaichila SET motta = $userBalance WHERE balakedara = '$user_id'");
        }

        $totalBalance = max($userBalance, $arBalance + $mainBalance);

        $res = [
            'code' => 0,
            'msg' => 'Succeed',
            'msgCode' => 0,
            'data' => [
                'arGameBalance' => $arBalance,
                'mainBalance'   => $mainBalance,
                'balance'       => $totalBalance,
                'amount'        => $totalBalance
            ],
            'serviceNowTime' => $shnunc
        ];
        http_response_code(200);
        echo json_encode($res);
        exit;
    }
}

http_response_code(200);
echo json_encode($res);
?>
