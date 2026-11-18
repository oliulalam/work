<?php
ob_start();
session_start();

ini_set('display_errors', 0);
error_reporting(E_ALL);

$db_file = __DIR__ . '/../includes/db.php';
$helper_file = __DIR__ . '/../includes/propay_gateway_helper.php';

if (!file_exists($db_file)) {
    ob_end_clean();
    die("Fatal Error: db.php missing");
}

if (!file_exists($helper_file)) {
    ob_end_clean();
    die("Fatal Error: propay_gateway_helper.php missing");
}

require_once $db_file;
require_once $helper_file;

if (!isset($_SESSION['user_id'])) {
    ob_end_clean();
    header("Location: ../player/login.php");
    exit();
}

$uid = intval($_SESSION['user_id']);
$amount = isset($_POST['amount']) ? floatval($_POST['amount']) : (isset($_GET['amount']) ? floatval($_GET['amount']) : 0);
$method = isset($_POST['method']) ? trim($_POST['method']) : (isset($_GET['method']) ? trim($_GET['method']) : 'Nagad');
$promo_id = isset($_POST['promo_id']) ? intval($_POST['promo_id']) : (isset($_GET['promo_id']) ? intval($_GET['promo_id']) : 0);
$channel = isset($_POST['channel']) ? trim($_POST['channel']) : (isset($_GET['channel']) ? trim($_GET['channel']) : 'LGPay');

$result = lgpay_create_deposit_order($conn, $uid, $amount, $method, $promo_id, $channel);

if (!empty($result['success']) && !empty($result['redirect_url'])) {
    ob_end_clean();
    header("Location: " . $result['redirect_url']);
    exit();
}

$message = $result['message'] ?? 'Unable to start LG Payment deposit. Please try again.';
ob_end_clean();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LG Payment Error</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-3xl p-7 shadow-xl text-center">
        <div class="w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-5">
            <i class="fas fa-exclamation-triangle text-3xl"></i>
        </div>
        <h1 class="text-xl font-black text-gray-800 mb-2">Payment Start Failed</h1>
        <p class="text-sm text-gray-600 mb-6"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
        <a href="../player/deposit.php" class="block w-full bg-[#0d4a3a] text-white font-bold py-3 rounded-xl">Back to Deposit</a>
    </div>
</body>
</html>
