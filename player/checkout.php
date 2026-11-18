<?php
session_start();
require __DIR__ . '/../includes/db.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$uid = (int)$_SESSION['user_id'];

// Data from deposit selection form
$amount = isset($_POST['amount']) ? floatval($_POST['amount']) : (isset($_GET['amount']) ? floatval($_GET['amount']) : 100);
$method = isset($_POST['method']) ? trim($_POST['method']) : (isset($_GET['method']) ? trim($_GET['method']) : 'Nagad');
$promo_id = isset($_POST['promo_id']) ? intval($_POST['promo_id']) : 0;
$channel = isset($_POST['channel']) ? trim($_POST['channel']) : '';

if ($amount <= 0) {
    header("Location: deposit.php");
    exit();
}

$method_lower = strtolower($method);
$is_nagad = ($method_lower === 'nagad');
$is_bkash = ($method_lower === 'bkash');

// Fetch active payment account for this method
$stmt = $conn->prepare("SELECT number, type FROM payment_accounts WHERE LOWER(method) = ? AND is_active = 1 LIMIT 1");
if ($stmt) {
    $stmt->bind_param("s", $method_lower);
    $stmt->execute();
    $res = $stmt->get_result();
    $account = ($res && $res->num_rows > 0) ? $res->fetch_assoc() : null;
    $stmt->close();
}

$wallet_no = '';
if (!empty($account['number'])) {
    $wallet_no = $account['number'];
} else {
    // Fallback numbers
    if ($is_nagad) {
        $wallet_no = '01877935979';
    } else {
        $wallet_no = '0179049971';
    }
}

// Styling based on method (matching Image 2)
if ($is_nagad) {
    $theme_bg = '#f05043'; // Nagad Red
    $method_title = 'NAGAD Deposit';
    $logo_url = 'https://freelogopng.com/images/all_img/1679248828Nagad-Logo-PNG.png';
} else {
    $theme_bg = '#e2136e'; // Bkash Pink/Magenta
    $method_title = 'BKASH Deposit';
    $logo_url = 'https://freelogopng.com/images/all_img/1656234745bkash-app-logo-png.png';
}

$formatted_amount = number_format($amount, 0);
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo htmlspecialchars($method_title); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Hind Siliguri', sans-serif;
            background-color: #f7fafc;
            color: #1a202c;
            margin: 0;
            padding: 0;
        }
        .header-top {
            background-color: #065e3d;
            color: #ffffff;
            padding: 14px 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .pay-service-badge {
            border: 1px solid rgba(255, 255, 255, 0.9);
            color: #ffffff;
            font-weight: 800;
            font-size: 11px;
            padding: 2px 10px;
            border-radius: 4px;
            letter-spacing: 0.5px;
        }
        .warning-text-red {
            color: #ff0000;
            font-weight: 700;
            font-size: 14px;
            line-height: 1.5;
            padding: 14px 18px;
        }
        .method-banner-card {
            margin: 0 18px;
            border-radius: 12px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .logo-circle {
            width: 48px;
            height: 48px;
            background-color: #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 4px;
            margin-right: 14px;
            flex-shrink: 0;
        }
        .wallet-box {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            margin-top: 10px;
            cursor: pointer;
        }
        .trx-input-box {
            border: 2px solid #ff0000;
            border-radius: 10px;
            padding: 14px 16px;
            width: 100%;
            font-size: 15px;
            font-weight: 600;
            outline: none;
            box-sizing: border-box;
        }
        .trx-input-box::placeholder {
            color: #a0aec0;
            font-weight: 500;
        }
        .btn-confirm {
            background-color: #ffffff;
            border: 1.5px solid #cbd5e1;
            color: #334155;
            font-weight: 700;
            font-size: 15px;
            padding: 8px 45px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .btn-confirm:active {
            background-color: #f1f5f9;
            transform: scale(0.98);
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex justify-center items-start">

    <div class="w-full max-w-md bg-white min-h-screen shadow-md flex flex-col justify-between">
        <div>
            <!-- Green Top Header Bar -->
            <div class="header-top">
                <div>
                    <h2 class="text-xl font-extrabold tracking-tight">BDT <?php echo $formatted_amount; ?></h2>
                    <p class="text-xs font-semibold text-emerald-100 mt-0.5">কম বা বেশি টাকা পাঠান করবেন না</p>
                </div>
                <div>
                    <span class="pay-service-badge">PAY SERVICE</span>
                </div>
            </div>

            <!-- Red Warning Notice -->
            <div class="warning-text-red">
                আপনি যদি টাকার পরিমাণ পরিবর্তন করেন (BDT <?php echo $formatted_amount; ?>), আপনি ক্রেডিট পেতে সক্ষম হবেন না।
            </div>

            <!-- Method Banner Card -->
            <div class="method-banner-card" style="background-color: <?php echo $theme_bg; ?>;">
                <div class="logo-circle">
                    <img src="<?php echo $logo_url; ?>" alt="<?php echo htmlspecialchars($method); ?>" class="object-contain max-h-full">
                </div>
                <h3 class="text-lg font-black tracking-wide text-white"><?php echo htmlspecialchars($method_title); ?></h3>
            </div>

            <!-- Wallet Number Details Section -->
            <div class="px-4 mt-6">
                <label class="text-sm font-bold text-gray-900 block">
                    Wallet No <span class="text-red-500">*</span>
                </label>
                <p class="text-sm font-semibold text-gray-800 mt-1">
                    এই নাম্বারে শুধুমাত্র <span class="text-red-600 font-bold">সেন্ড মানি</span> গ্রহণ করা হয়
                </p>

                <!-- Copyable Wallet Number Box -->
                <div onclick="copyWalletNumber()" class="wallet-box">
                    <div class="mr-3 text-emerald-600 text-xl">
                        <i class="far fa-copy"></i>
                    </div>
                    <span id="targetWalletNumber" class="text-xl font-bold text-gray-800 tracking-wider font-mono">
                        <?php echo htmlspecialchars($wallet_no); ?>
                    </span>
                </div>
            </div>

            <!-- Transaction Form -->
            <form action="manual_process.php" method="POST" onsubmit="return validateTrxForm()" class="px-4 mt-6 space-y-4">
                <input type="hidden" name="amount" value="<?php echo $amount; ?>">
                <input type="hidden" name="method" value="<?php echo htmlspecialchars($method); ?>">
                <input type="hidden" name="promo_id" value="<?php echo $promo_id; ?>">
                <input type="hidden" name="sender_wallet" value="">

                <div>
                    <label class="text-sm font-bold text-gray-900 block">
                        সেন্ড মানির TrxID নাম্বারটি লিখুন<span class="text-red-600 font-bold">(প্রয়োজন)</span>
                    </label>
                    <input type="text" name="transaction_id" id="trxIdInput" placeholder="TrxID অবশ্যই পূরণ করতে হবে!" class="trx-input-box mt-2">
                </div>

                <!-- Submit Button -->
                <div class="flex justify-center pt-2">
                    <button type="submit" class="btn-confirm">
                        নিশ্চিত
                    </button>
                </div>
            </form>
        </div>

        <!-- Footer Warnings -->
        <div class="px-4 mt-8 pb-8 pt-4 border-t border-gray-100">
            <h4 class="text-sm font-extrabold text-gray-900">সতর্কতাঃ</h4>
            <p class="text-xs font-bold text-red-600 mt-1">
                লেনদেন আইডি সঠিকভাবে পূরণ করতে হবে, অন্যথায় স্কোর ব্যর্থ হবে! !
            </p>
            <p class="text-xs text-gray-500 font-medium leading-relaxed mt-2">
                অনুগ্ৰহ করে নিশ্চিত হয়ে নিন যে আপনি deposit ওয়ালেট নাম্বারে সেন্ড মানি করছেন। এই নাম্বারের অন্য কোন ওয়ালেট থেকে সেন্ড মানি করলে সেই টাকা পাওয়ার কোন সম্ভাবনা নাই
            </p>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toastMsg" class="fixed bottom-6 left-1/2 transform -translate-x-1/2 bg-gray-900 text-white text-xs font-bold px-4 py-2 rounded-full shadow-lg opacity-0 transition-opacity duration-300 pointer-events-none z-50">
        নাম্বার কপি করা হয়েছে!
    </div>

    <script>
        function copyWalletNumber() {
            const num = document.getElementById('targetWalletNumber').innerText.trim();
            navigator.clipboard.writeText(num).then(() => {
                const toast = document.getElementById('toastMsg');
                toast.classList.remove('opacity-0');
                toast.classList.add('opacity-100');
                setTimeout(() => {
                    toast.classList.remove('opacity-100');
                    toast.classList.add('opacity-0');
                }, 2000);
            }).catch(() => {
                alert('Copied: ' + num);
            });
        }

        function validateTrxForm() {
            const trx = document.getElementById('trxIdInput').value.trim();
            if (trx === "") {
                alert("দয়া করে TrxID নাম্বারটি পূরণ করুন!");
                return false;
            }
            return true;
        }
    </script>
</body>
</html>
