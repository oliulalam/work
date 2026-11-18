<?php
include_once __DIR__ . "/config.php";

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
header('Access-Control-Allow-Origin: ' . $origin);

date_default_timezone_set("Asia/Kolkata");
$currentTime = date("Y-m-d H:i:s");

$res = [
    'code' => 11,
    'msg' => 'Method not allowed',
    'msgCode' => 12,
    'serviceNowTime' => $currentTime
];

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode($res);
    exit;
}

$post = json_decode(file_get_contents("php://input"), true);

if (!is_array($post) || !isset($post['language'], $post['random'], $post['signature'], $post['timestamp'])) {
    $res['code'] = 7;
    $res['msg'] = 'Param is Invalid';
    $res['msgCode'] = 6;
    echo json_encode($res);
    exit;
}

/* ---------- Signature Check ---------- */
$checkStr = '{"language":'.$post['language'].',"random":"'.$post['random'].'"}';
if (strtoupper(md5($checkStr)) !== $post['signature']) {
    $res['code'] = 5;
    $res['msg'] = 'Wrong signature';
    $res['msgCode'] = 3;
    echo json_encode($res);
    exit;
}

/* ---------- JWT Check ---------- */
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (empty($authHeader) && function_exists('apache_request_headers')) {
    $headers = apache_request_headers();
    $headers = array_change_key_case($headers, CASE_LOWER);
    $authHeader = $headers['authorization'] ?? '';
}
if (empty($authHeader) && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
    $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
}
$bearer = explode(" ", trim($authHeader));
$token = $bearer[1] ?? '';

$jwt = json_decode(is_jwt_valid($token), true);

if ($jwt['status'] !== 'Success') {
    $res['code'] = 4;
    $res['msg'] = 'No operation permission';
    $res['msgCode'] = 2;
    http_response_code(401);
    echo json_encode($res);
    exit;
}

$user_id = (int)$jwt['payload']['id'];

/* ---------- Session Verification (shonu_subjects) ---------- */
$sessionActive = true;
try {
    $stmt = $conn->prepare(
        "SELECT akshinak FROM shonu_subjects WHERE akshinak = ? LIMIT 1"
    );
    if ($stmt) {
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $session = $stmt->get_result();
        if ($session->num_rows !== 1) {
            $sessionActive = false;
        }
        $stmt->close();
    }
} catch (Throwable $e) {
    // If table doesn't exist, we skip session verification to prevent locking
    $sessionActive = true;
}

if (!$sessionActive) {
    $res['code'] = 4;
    $res['msg'] = 'No operation permission';
    $res['msgCode'] = 2;
    http_response_code(401);
    echo json_encode($res);
    exit;
}

/* ---------- Balance Fetch ---------- */
$balance = 0.0;
// Try users table first
try {
    $stmtBal = $conn->prepare("SELECT balance FROM users WHERE id = ? LIMIT 1");
    if ($stmtBal) {
        $stmtBal->bind_param("i", $user_id);
        $stmtBal->execute();
        $balRes = $stmtBal->get_result();
        if ($balRes && $balRes->num_rows > 0) {
            $balance = (float)$balRes->fetch_assoc()['balance'];
        }
        $stmtBal->close();
    }
} catch (Throwable $e) {
    // Fallback to shonu_kaichila
    try {
        $stmtBal = $conn->prepare("SELECT motta FROM shonu_kaichila WHERE balakedara = ? LIMIT 1");
        if ($stmtBal) {
            $stmtBal->bind_param("i", $user_id);
            $stmtBal->execute();
            $balRes = $stmtBal->get_result();
            if ($balRes && $balRes->num_rows > 0) {
                $balance = (float)$balRes->fetch_assoc()['motta'];
            }
            $stmtBal->close();
        }
    } catch (Throwable $ex) {
        $balance = 0.0;
    }
}

$response = [
    "data" => [
        "vendorCode" => "ARGame",
        "balance" => (float)$balance
    ],
    "code" => 0,
    "msg" => "Succeed",
    "msgCode" => 0,
    "serviceNowTime" => $currentTime
];

http_response_code(200);
echo json_encode($response);
