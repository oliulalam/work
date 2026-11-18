<?php
ob_start(); 
session_start();

ini_set('display_errors', 0);
error_reporting(E_ALL);

$db_file = __DIR__ . '/../includes/db.php'; 
$config_file = __DIR__ . '/../includes/cowpay_config.php';
$helper_file = __DIR__ . '/../includes/propay_gateway_helper.php';

if (!file_exists($db_file)) {
    ob_end_clean();
    die("Fatal Error: db.php missing");
}

if (!file_exists($config_file)) {
    ob_end_clean();
    die("Fatal Error: cowpay_config.php missing");
}

require_once $db_file;
require_once $config_file;

if (file_exists($helper_file)) {
    require_once $helper_file;
}

if (!function_exists('generate_cowpay_sign')) {
    ob_end_clean();
    die("Error: Function generate_cowpay_sign() is missing");
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
    $channel = isset($_POST['channel']) ? trim($_POST['channel']) : '';

    // Fetch site limits
    $min_deposit = 100;
    if (function_exists('propay_get_site_transaction_settings')) {
        $txn_settings = propay_get_site_transaction_settings($conn);
        $min_deposit = max(1, (float)($txn_settings['min_deposit_amount'] ?? 100));
    }

    if ($amount < $min_deposit) {
        ob_end_clean();
        die("<div style='font-family:sans-serif; text-align:center; padding:50px;'><h2>Invalid Amount</h2><p>Minimum deposit amount is ৳" . number_format($min_deposit, 0) . "</p><a href='../player/deposit.php'>Back to Deposit</a></div>");
    }

    if (empty($method)) {
        ob_end_clean();
        die("<div style='font-family:sans-serif; text-align:center; padding:50px;'><h2>Error</h2><p>Payment method is missing.</p><a href='../player/deposit.php'>Back to Deposit</a></div>");
    }

    // Determine Cowpay pay_type (bkash, nagad)
    $method_lower = strtolower($method);
    $pay_type = 'bkash';
    if ($method_lower === 'nagad') {
        $pay_type = 'nagad';
    } elseif ($method_lower === 'bkash') {
        $pay_type = 'bkash';
    }

    // Get dynamic Cowpay credentials from DB or fallback
    $cowpay_settings = function_exists('cowpay_get_settings') ? cowpay_get_settings($conn) : [];
    $merchant_code = !empty($cowpay_settings['merchant_code']) ? $cowpay_settings['merchant_code'] : (defined('COWPAY_MERCHANT_CODE') ? COWPAY_MERCHANT_CODE : 'M2769530848203370439');
    $secret_code = !empty($cowpay_settings['secret_code']) ? $cowpay_settings['secret_code'] : (defined('COWPAY_SECRET_KEY') ? COWPAY_SECRET_KEY : '4af5bab09401122a7ac59e5b2a78b84b');

    $order_sn = 'ORD' . date('YmdHis') . rand(1000, 9999);

    // Save transaction to DB
    $method_escaped = $conn->real_escape_string($method);
    $user_phone_escaped = $conn->real_escape_string($user_phone);
    $order_sn_escaped = $conn->real_escape_string($order_sn);
    
    $sql = "INSERT INTO transactions_fake (user_id, type, amount, method, promo_id, status, wallet_number, order_sn, created_at) 
            VALUES ($uid, 'deposit', $amount, '$method_escaped', $promo_id, 'pending', '$user_phone_escaped', '$order_sn_escaped', NOW())";
    
    if ($conn->query($sql)) {
        $txId = $conn->insert_id;
        $_SESSION['last_order_sn'] = $order_sn;

        // Also record in payment_gateway_orders if available
        if (function_exists('propay_table_exists') && propay_table_exists($conn, 'payment_gateway_orders')) {
            $method_key = $pay_type;
            $channel_label = $channel ?: 'CowPay';
            $stmt2 = $conn->prepare("INSERT INTO payment_gateway_orders (transaction_id, order_no, user_id, type, method, channel, amount, status, created_at) VALUES (?, ?, ?, 'deposit', ?, ?, ?, 'pending', NOW())");
            if ($stmt2) {
                $stmt2->bind_param('isissd', $txId, $order_sn, $uid, $method_key, $channel_label, $amount);
                $stmt2->execute();
            }
        }

        // Build Cowpay transdata parameters
        $transdata = [
            "country_code"  => COWPAY_COUNTRY_CODE,
            "merchant_code" => $merchant_code,
            "notify_url"    => COWPAY_NOTIFY_URL,
            "order_amount"  => (string)intval(round($amount)),
            "order_no"      => $order_sn,
            "pay_type"      => $pay_type,
            "return_url"    => COWPAY_RETURN_URL
        ];

        // Generate MD5 signature
        $sign = generate_cowpay_sign($transdata, $secret_code);

        // Build JSON payload
        $request_payload = [
            "sign"      => $sign,
            "signtype"  => "MD5",
            "transdata" => $transdata
        ];

        $json_data = json_encode($request_payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // Send cURL Request to Cowpay API
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, COWPAY_GATEWAY_URL);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            curl_close($ch);
            ob_end_clean();
            die("<div style='font-family:sans-serif; text-align:center; padding:50px;'><h2>Connection Error</h2><p>" . htmlspecialchars($error_msg) . "</p><a href='../player/deposit.php'>Back</a></div>");
        }
        curl_close($ch);

        $res_data = json_decode($response, true);

        // Check for payment URL in response
        $pay_url = $res_data['pay_url'] ?? ($res_data['data']['pay_url'] ?? ($res_data['transdata']['pay_url'] ?? ''));
        $is_success = (!empty($res_data['status']) && $res_data['status'] === true) || (isset($res_data['code']) && ((string)$res_data['code'] === '0' || (string)$res_data['code'] === '200'));

        if (!empty($pay_url)) {
            ob_end_clean(); 
            header("Location: " . $pay_url);
            exit();
        } else {
            ob_end_clean();
            $err_msg = $res_data['message'] ?? ($res_data['msg'] ?? 'Gateway request failed. Please ensure Secret Key is configured in Admin Settings.');
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
