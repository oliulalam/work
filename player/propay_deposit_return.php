<?php
session_start();
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/propay_gateway_helper.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

propay_ensure_schema($conn);
$uid = intval($_SESSION['user_id']);
$orderNo = trim($_GET['order_no'] ?? '');
$status = 'pending';
$amount = 0.00;
$message = 'Payment is being verified. Please wait a moment.';
$icon = 'fa-clock';
$color = 'yellow';
$autoRedirect = false;

if ($orderNo !== '') {
    $stmt = $conn->prepare("SELECT pgo.*, tf.status AS tx_status FROM payment_gateway_orders pgo LEFT JOIN transactions_fake tf ON pgo.transaction_id=tf.id WHERE pgo.order_no=? AND pgo.user_id=? AND pgo.type='deposit' LIMIT 1");
    $stmt->bind_param('si', $orderNo, $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $order = $res->fetch_assoc();
        $status = $order['status'];
        $amount = (float)$order['amount'];
        if ($status === 'success' || $order['tx_status'] === 'approved') {
            $message = 'Payment verified successfully. Your balance has been updated.';
            $icon = 'fa-check-circle';
            $color = 'green';
            $autoRedirect = true;
        } elseif ($status === 'failed') {
            $message = 'Payment verification failed. Please contact support if money was deducted.';
            $icon = 'fa-times-circle';
            $color = 'red';
        }
    } else {
        $message = 'Payment order was not found.';
        $icon = 'fa-exclamation-circle';
        $color = 'red';
    }
}

$boxClass = $color === 'green' ? 'bg-green-100 text-green-600' : ($color === 'red' ? 'bg-red-100 text-red-600' : 'bg-yellow-100 text-yellow-600');
$btnClass = $color === 'green' ? 'bg-green-600' : 'bg-[#0d4a3a]';
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Status</title>
    <?php if(!$autoRedirect && $status === 'pending'): ?>
    <meta http-equiv="refresh" content="5">
    <?php endif; ?>
    <?php if($autoRedirect): ?>
    <meta http-equiv="refresh" content="3;url=dashboard.php">
    <?php endif; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-3xl p-8 shadow-xl text-center">
        <div class="w-20 h-20 <?php echo $boxClass; ?> rounded-full flex items-center justify-center mx-auto mb-6">
            <i class="fas <?php echo $icon; ?> text-5xl"></i>
        </div>
        <h1 class="text-2xl font-black text-gray-800 mb-2">Deposit Status</h1>
        <p class="text-gray-600 text-sm mb-5"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php if($amount > 0): ?>
        <div class="bg-gray-50 rounded-2xl p-4 mb-6 text-sm">
            <div class="flex justify-between"><span class="text-gray-500">Order</span><span class="font-bold text-gray-700"><?php echo htmlspecialchars($orderNo, ENT_QUOTES, 'UTF-8'); ?></span></div>
            <div class="flex justify-between mt-2"><span class="text-gray-500">Amount</span><span class="font-bold text-[#0d4a3a]">৳ <?php echo number_format($amount, 2); ?></span></div>
        </div>
        <?php endif; ?>
        <a href="dashboard.php" class="block w-full <?php echo $btnClass; ?> text-white font-bold py-3 rounded-xl">Go to Dashboard</a>
        <?php if(!$autoRedirect && $status === 'pending'): ?>
            <p class="text-[11px] text-gray-400 mt-4">This page will refresh automatically.</p>
        <?php endif; ?>
    </div>
</body>
</html>
