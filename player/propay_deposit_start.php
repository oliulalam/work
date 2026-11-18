<?php
session_start();
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/propay_gateway_helper.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: deposit.php');
    exit();
}

$uid = intval($_SESSION['user_id']);
$amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
$method = $_POST['method'] ?? '';
$promo_id = isset($_POST['promo_id']) ? intval($_POST['promo_id']) : 0;
$channel = $_POST['channel'] ?? '';

$result = propay_create_deposit_order($conn, $uid, $amount, $method, $promo_id, $channel);

if (!empty($result['success']) && !empty($result['redirect_url'])) {
    header('Location: ' . $result['redirect_url']);
    exit();
}

$message = $result['message'] ?? 'Unable to start payment. Please try again.';
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deposit Error</title>
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
        <a href="deposit.php" class="block w-full bg-[#0d4a3a] text-white font-bold py-3 rounded-xl">Back to Deposit</a>
    </div>
</body>
</html>
