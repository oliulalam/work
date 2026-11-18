<?php
ob_start(); 
session_start();

ini_set('display_errors', 0);
error_reporting(E_ALL);

$db_file = __DIR__ . '/../includes/db.php'; 
$config_file = __DIR__ . '/../includes/lg_config.php';

if (!file_exists($db_file)) {
    ob_end_clean();
    die("Fatal Error: db.php missing");
}

if (!file_exists($config_file)) {
    ob_end_clean();
    die("Fatal Error: lg_config.php missing");
}

require_once $db_file;
require_once $config_file;

if (!function_exists('generate_lg_sign')) {
    ob_end_clean();
    die("Error: Function generate_lg_sign() is missing");
}

if (!isset($_SESSION['user_id'])) {
    ob_end_clean();
    header("Location: ../player/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $uid = intval($_SESSION['user_id']);
    
    // Fetch user phone or username for identifier
    $user_res = $conn->query("SELECT phone, username FROM users WHERE id=$uid");
    $user_data = $user_res ? $user_res->fetch_assoc() : [];
    $user_phone = !empty($user_data['phone']) ? $user_data['phone'] : (!empty($user_data['username']) ? $user_data['username'] : (string)$uid);

    // Inputs
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    $method = isset($_POST['method']) ? trim($_POST['method']) : ''; 
    $promo_id = isset($_POST['promo_id']) ? intval($_POST['promo_id']) : 0; 

    if ($amount < 100) {
        ob_end_clean();
        die("<div style='font-family:sans-serif; text-align:center; padding:50px;'><h2>Invalid Amount</h2><p>Minimum deposit amount is ৳100</p><a href='../player/deposit.php'>Back to Deposit</a></div>");
    }

    if (empty($method)) {
        ob_end_clean();
        die("<div style='font-family:sans-serif; text-align:center; padding:50px;'><h2>Error</h2><p>Payment method is missing.</p><a href='../player/deposit.php'>Back to Deposit</a></div>");
    }

    // Determine trade_type for LG Payment gateway (bKash vs Nagad)
    $trade_type = $method;
    if (defined('LG_TEST_MODE') && LG_TEST_MODE === true) {
        $trade_type = 'test';
    } else {
        $method_lower = strtolower($method);
        if ($method_lower === 'bkash') {
            $trade_type = 'bKash';
        } elseif ($method_lower === 'nagad') {
            $trade_type = 'Nagad';
        }
    }

    $order_sn = 'ORD' . time() . rand(1000, 9999);

    // Save transaction to DB
    $method_escaped = $conn->real_escape_string($method);
    $user_phone_escaped = $conn->real_escape_string($user_phone);
    
    $sql = "INSERT INTO transactions_fake (user_id, type, amount, method, promo_id, status, wallet_number, order_sn, created_at) 
            VALUES ($uid, 'deposit', $amount, '$method_escaped', $promo_id, 'pending', '$user_phone_escaped', '$order_sn', NOW())";
    
    if ($conn->query($sql)) {
        $_SESSION['last_order_sn'] = $order_sn;

        // Build LG-Pay Request
        $params = [
            "app_id"     => LG_APP_ID,
            "trade_type" => $trade_type, 
            "order_sn"   => $order_sn,
            "user_id"    => $user_phone,
            "money"      => intval(round($amount * 100)),
            "notify_url" => LG_NOTIFY_URL,
            "return_url"   => LG_RETURN_URL, 
            "redirect_url" => LG_RETURN_URL,
            "callback_url" => LG_RETURN_URL,
            "ip"         => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
        ];

        $params['sign'] = generate_lg_sign($params, LG_KEY);

        // cURL Request to LG-Pay API
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, LG_GATEWAY_URL);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            curl_close($ch);
            ob_end_clean();
            die("<div style='font-family:sans-serif; text-align:center; padding:50px;'><h2>Connection Error</h2><p>" . htmlspecialchars($error_msg) . "</p><a href='../player/deposit.php'>Back</a></div>");
        }
        curl_close($ch);

        $res_data = json_decode($response, true);

        if (isset($res_data['status']) && $res_data['status'] == 1 && !empty($res_data['data']['pay_url'])) {
            ob_end_clean(); 
            header("Location: " . $res_data['data']['pay_url']);
            exit();
        } else {
            ob_end_clean();
            $err_msg = $res_data['msg'] ?? 'Gateway request failed.';
            echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1.0'><title>Payment Error</title><script src='https://cdn.tailwindcss.com'></script></head><body class='bg-gray-100 flex items-center justify-center min-h-screen p-4'><div class='bg-white p-8 rounded-2xl shadow-xl max-w-md w-full text-center'><div class='w-16 h-16 bg-red-100 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl font-bold'>✕</div><h2 class='text-xl font-bold text-gray-800 mb-2'>Gateway Error</h2><p class='text-gray-600 text-sm mb-6'>" . htmlspecialchars($err_msg) . "</p><a href='../player/deposit.php' class='inline-block bg-emerald-600 text-white font-bold px-6 py-3 rounded-xl hover:bg-emerald-700 transition'>Return to Deposit</a></div></body></html>";
            exit();
        }

    } else {
        ob_end_clean();
        die("Database Error: " . $conn->error);
    }
} else {
    ob_end_clean();
    header("Location: ../player/deposit.php");
    exit();
}
?>