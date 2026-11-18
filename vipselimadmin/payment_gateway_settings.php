<?php
require '../includes/auth_session.php';
require '../includes/db.php';
require '../includes/functions.php';
require '../includes/propay_gateway_helper.php';

propay_ensure_schema($conn);
$msg = '';
$msg_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_lgpay'])) {
    $app_id = sanitize($conn, $_POST['lgpay_app_id'] ?? '');
    $secret_code = trim($_POST['lgpay_secret_code'] ?? '');
    $secret_code = htmlspecialchars(strip_tags($secret_code), ENT_QUOTES, 'UTF-8');
    $is_enabled = isset($_POST['lgpay_is_enabled']) ? 1 : 0;

    if (lgpay_save_settings($conn, $app_id, $secret_code, $is_enabled)) {
        $msg = 'LG Payment Gateway credentials saved successfully.';
        $msg_type = 'success';
    } else {
        $msg = 'Unable to save LG Payment credentials: ' . $conn->error;
        $msg_type = 'error';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_cowpay'])) {
    $merchant_code = sanitize($conn, $_POST['cowpay_merchant_code'] ?? '');
    $secret_code = trim($_POST['cowpay_secret_code'] ?? '');
    $secret_code = htmlspecialchars(strip_tags($secret_code), ENT_QUOTES, 'UTF-8');

    if (cowpay_save_settings($conn, $merchant_code, $secret_code, 1)) {
        $msg = 'Cowpay Payment Gateway credentials saved successfully.';
        $msg_type = 'success';
    } else {
        $msg = 'Unable to save Cowpay credentials: ' . $conn->error;
        $msg_type = 'error';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_propay'])) {
    $merchant_code = sanitize($conn, $_POST['merchant_code'] ?? '');
    $secret_code = trim($_POST['secret_code'] ?? '');
    $secret_code = htmlspecialchars(strip_tags($secret_code), ENT_QUOTES, 'UTF-8');

    if (propay_save_settings($conn, $merchant_code, $secret_code, 1)) {
        $msg = 'ProPay Payment Gateway credentials saved successfully.';
        $msg_type = 'success';
    } else {
        $msg = 'Unable to save ProPay credentials: ' . $conn->error;
        $msg_type = 'error';
    }
}

$lgpay = lgpay_get_settings($conn);
$cowpay = cowpay_get_settings($conn);
$gateway = propay_get_settings($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Gateway Settings</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 text-slate-800">
    <?php include '../includes/sidebar_admin.php'; ?>

    <main class="lg:ml-64 p-4 lg:p-8 min-h-screen">
        <div class="mb-6">
            <h1 class="text-2xl font-black text-gray-800">Payment Gateway Settings</h1>
            <p class="text-sm text-gray-500">Manage credentials for LG Payment (bKash/Nagad Deposit), Cowpay (Deposit), and ProPay (Withdrawal) payment gateways.</p>
        </div>

        <?php if($msg): ?>
            <div class="mb-6 p-4 rounded-xl border-l-4 bg-white shadow-sm <?php echo $msg_type === 'success' ? 'border-green-500 text-green-700' : 'border-red-500 text-red-700'; ?>">
                <i class="fas <?php echo $msg_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> mr-2"></i>
                <span class="font-bold text-sm"><?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- LG Payment (Deposit Gateway) Settings Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4 border-b pb-3">
                    <h2 class="font-black text-lg text-gray-800 flex items-center gap-2">
                        <i class="fas fa-bolt text-blue-600"></i> LG Payment (Deposit)
                    </h2>
                    <span class="bg-blue-100 text-blue-800 text-xs font-bold px-2.5 py-1 rounded-full">bKash / Nagad</span>
                </div>

                <form method="POST" class="space-y-5">
                    <div>
                        <label class="block text-xs font-black text-gray-500 uppercase tracking-widest mb-2">Merchant App ID (app_id)</label>
                        <input type="text" name="lgpay_app_id" value="<?php echo htmlspecialchars($lgpay['app_id'] ?? 'MJL3327', ENT_QUOTES, 'UTF-8'); ?>" placeholder="MJL3327" class="w-full border border-gray-300 rounded-xl px-4 py-3 font-bold focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>

                    <div>
                        <label class="block text-xs font-black text-gray-500 uppercase tracking-widest mb-2">Secret Key (key)</label>
                        <input type="text" name="lgpay_secret_code" value="<?php echo htmlspecialchars($lgpay['secret_code'] ?? '95PhgiAAn8RinHeCTCRA9zU8Tp6OCcsh', ENT_QUOTES, 'UTF-8'); ?>" placeholder="95PhgiAAn8RinHeCTCRA9zU8Tp6OCcsh" class="w-full border border-gray-300 rounded-xl px-4 py-3 font-bold focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="lgpay_is_enabled" id="lgpay_is_enabled" value="1" <?php echo !empty($lgpay['is_enabled']) ? 'checked' : ''; ?> class="w-4 h-4 text-blue-600 rounded">
                        <label for="lgpay_is_enabled" class="text-xs font-bold text-gray-700">Enable LG Payment Gateway</label>
                    </div>

                    <div class="bg-gray-50 p-3 rounded-xl border border-gray-200 text-xs text-gray-600 space-y-1">
                        <p><strong>Merchant Login:</strong> <a href="https://www.lg-pay.com/app/merchant/page/login.html" target="_blank" class="text-blue-600 underline">https://www.lg-pay.com</a></p>
                        <p><strong>Trade Types:</strong> Nagad: <code>Nagad</code> | bKash: <code>bKash</code></p>
                        <p><strong>Callback IP:</strong> 139.180.137.164 / 2401:c080:1400:7249:5400:4ff:fed7:b9aa</p>
                        <p><strong>Callback URL:</strong> <?php echo htmlspecialchars((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'a1game.live') . '/api/lgpay_callback.php'); ?></p>
                    </div>

                    <button type="submit" name="save_lgpay" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-black shadow-md transition">
                        <i class="fas fa-save mr-2"></i> Save LG Payment Credentials
                    </button>
                </form>
            </div>

            <!-- Cowpay (Deposit Gateway) Settings Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4 border-b pb-3">
                    <h2 class="font-black text-lg text-gray-800 flex items-center gap-2">
                        <i class="fas fa-coins text-emerald-600"></i> Cowpay Gateway (Deposit)
                    </h2>
                    <span class="bg-emerald-100 text-emerald-800 text-xs font-bold px-2.5 py-1 rounded-full">Active Deposit</span>
                </div>

                <form method="POST" class="space-y-5">
                    <div>
                        <label class="block text-xs font-black text-gray-500 uppercase tracking-widest mb-2">Merchant Code / Merchant ID</label>
                        <input type="text" name="cowpay_merchant_code" value="<?php echo htmlspecialchars($cowpay['merchant_code'] ?? 'M2769530848203370439', ENT_QUOTES, 'UTF-8'); ?>" placeholder="M2769530848203370439" class="w-full border border-gray-300 rounded-xl px-4 py-3 font-bold focus:outline-none focus:ring-2 focus:ring-emerald-400">
                    </div>

                    <div>
                        <label class="block text-xs font-black text-gray-500 uppercase tracking-widest mb-2">Secret Key / API Key</label>
                        <input type="text" name="cowpay_secret_code" value="<?php echo htmlspecialchars($cowpay['secret_code'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Enter Cowpay Secret Key from Merchant Dashboard" class="w-full border border-gray-300 rounded-xl px-4 py-3 font-bold focus:outline-none focus:ring-2 focus:ring-emerald-400">
                    </div>

                    <div class="bg-gray-50 p-3 rounded-xl border border-gray-200 text-xs text-gray-600 space-y-1">
                        <p><strong>Merchant System:</strong> <a href="https://merchant888.cowpay.co" target="_blank" class="text-emerald-600 underline">https://merchant888.cowpay.co</a></p>
                        <p><strong>Webhook IP:</strong> 16.163.88.85, 54.46.98.55</p>
                        <p><strong>Callback URL:</strong> <?php echo htmlspecialchars((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'kaki66.com') . '/api/cowpay_callback.php'); ?></p>
                    </div>

                    <button type="submit" name="save_cowpay" class="w-full bg-[#126E51] hover:bg-[#0d4a3a] text-white px-6 py-3 rounded-xl font-black shadow-md transition">
                        <i class="fas fa-save mr-2"></i> Save Cowpay Credentials
                    </button>
                </form>
            </div>

            <!-- ProPay (Withdrawal Gateway) Settings Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4 border-b pb-3">
                    <h2 class="font-black text-lg text-gray-800 flex items-center gap-2">
                        <i class="fas fa-key text-indigo-600"></i> ProPay Gateway (Withdraw)
                    </h2>
                    <span class="bg-indigo-100 text-indigo-800 text-xs font-bold px-2.5 py-1 rounded-full">Withdrawal</span>
                </div>

                <form method="POST" class="space-y-5">
                    <div>
                        <label class="block text-xs font-black text-gray-500 uppercase tracking-widest mb-2">Merchant Code / Merchant ID</label>
                        <input type="text" name="merchant_code" value="<?php echo htmlspecialchars($gateway['merchant_code'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Example: 801100004" class="w-full border border-gray-300 rounded-xl px-4 py-3 font-bold focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    </div>

                    <div>
                        <label class="block text-xs font-black text-gray-500 uppercase tracking-widest mb-2">Secret Code / API Key</label>
                        <input type="text" name="secret_code" value="<?php echo htmlspecialchars($gateway['secret_code'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="ProPay API Key" class="w-full border border-gray-300 rounded-xl px-4 py-3 font-bold focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    </div>

                    <div class="h-[76px]"></div>

                    <button type="submit" name="save_propay" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-xl font-black shadow-md transition">
                        <i class="fas fa-save mr-2"></i> Save ProPay Credentials
                    </button>
                </form>
            </div>
        </div>
    </main>
</body>
</html>
