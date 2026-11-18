<?php
// ১. এরর রিপোর্টিং
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
$db_path = file_exists('includes/db.php') ? 'includes/db.php' : '../includes/db.php';

try {
    if (file_exists($db_path)) {
        require $db_path;
    }
    $propay_helper_path = file_exists('includes/propay_gateway_helper.php') ? 'includes/propay_gateway_helper.php' : '../includes/propay_gateway_helper.php';
    if (file_exists($propay_helper_path)) {
        require_once $propay_helper_path;
    }
} catch (Exception $e) {
    die("Database Connection Error: " . $e->getMessage());
}

// 2. Auth Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$uid = $_SESSION['user_id'];
$message = "";
$msg_type = "";
if (isset($_SESSION['withdraw_flash_msg'])) {
    $message = $_SESSION['withdraw_flash_msg'];
    $msg_type = $_SESSION['withdraw_flash_type'] ?? 'info';
    unset($_SESSION['withdraw_flash_msg'], $_SESSION['withdraw_flash_type']);
}

if (empty($_SESSION['withdraw_token'])) {
    $_SESSION['withdraw_token'] = md5(uniqid(mt_rand(), true));
}
$withdraw_token = $_SESSION['withdraw_token'];

$txn_settings = function_exists('propay_get_site_transaction_settings') ? propay_get_site_transaction_settings($conn) : array('min_withdraw_amount' => 100);
$min_withdraw = max(1, (float)($txn_settings['min_withdraw_amount'] ?? 100));

// Sync pending ProPay withdrawals for this user whenever the withdraw page opens.
if (function_exists('propay_sync_pending_withdrawals')) {
    @propay_sync_pending_withdrawals($conn, intval($uid), 5);
}

try {
    // 3. Fetch User Data
    $user_q = $conn->query("SELECT u.*, p.kyc_real_name FROM users u LEFT JOIN player_profiles p ON u.id = p.user_id WHERE u.id=$uid");
    $user = $user_q->fetch_assoc();

    // Verification Check
    $has_name = !empty($user['kyc_real_name']);
    $is_verified = $has_name;

    // 4. HANDLE ADD NEW WALLET (Max 5 Limit Added)
    if (isset($_POST['add_wallet'])) {
        $new_method = $_POST['new_method'] ?? '';
        $new_number = $_POST['new_number'] ?? '';
        
        $wallet_count_chk = $conn->query("SELECT COUNT(id) as total FROM player_wallets WHERE user_id=$uid")->fetch_assoc()['total'];
        
        if ($wallet_count_chk >= 5) {
            $_SESSION['withdraw_flash_msg'] = "You can add a maximum of 5 accounts.";
            $_SESSION['withdraw_flash_type'] = "error";
        } elseif (!empty($new_method) && !empty($new_number)) {
            $chk = $conn->query("SELECT id FROM player_wallets WHERE user_id=$uid AND wallet_number='$new_number' AND method='$new_method'");
            if ($chk->num_rows == 0) {
                $stmt = $conn->prepare("INSERT INTO player_wallets (user_id, method, wallet_number) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $uid, $new_method, $new_number);
                $stmt->execute();
                $_SESSION['withdraw_flash_msg'] = "New wallet added successfully!";
                $_SESSION['withdraw_flash_type'] = "success";
            } else {
                $_SESSION['withdraw_flash_msg'] = "This wallet is already linked.";
                $_SESSION['withdraw_flash_type'] = "error";
            }
        }
        header("Location: withdraw.php");
        exit();
    }

    // 5. HANDLE WITHDRAW REQUEST (ProPay automatic payout)
    if (isset($_POST['submit_withdraw'])) {
        $posted_token = $_POST['withdraw_token'] ?? '';
        if (empty($posted_token) || !isset($_SESSION['withdraw_token']) || $posted_token !== $_SESSION['withdraw_token']) {
            $_SESSION['withdraw_flash_msg'] = "This withdrawal was already processed. Please do not refresh.";
            $_SESSION['withdraw_flash_type'] = "error";
            header("Location: withdraw.php");
            exit();
        }

        // Invalidate token immediately to prevent double submit on quick clicks or refresh
        unset($_SESSION['withdraw_token']);

        $amount = floatval($_POST['amount']);
        $wallet_id = isset($_POST['selected_wallet']) ? intval($_POST['selected_wallet']) : 0;

        // TURNOVER CHECK: deposit adds requirement, valid game bets complete it.
        if (function_exists('propay_user_turnover_summary')) {
            $turnover_summary = propay_user_turnover_summary($conn, intval($uid));
            $wager_remaining = (float)$turnover_summary['remaining'];
        } else {
            $target_turnover = floatval($user['turnover_target'] ?? 0);
            $completed_turnover = floatval($user['turnover_completed'] ?? 0);
            $wager_remaining = max(0, $target_turnover - $completed_turnover);
        }

        // VALIDATION
        if ($amount < $min_withdraw) {
            $message = "Minimum withdrawal amount is ৳" . number_format($min_withdraw, 2) . ".";
            $msg_type = "error";
        } elseif ($amount > $user['balance']) {
            $message = "Insufficient balance.";
            $msg_type = "error";
        } elseif ($wager_remaining > 0.01) {
            $message = "You need to play ৳" . number_format($wager_remaining, 2) . " more to withdraw.";
            $msg_type = "error";
        } elseif ($wallet_id == 0) {
            $message = "Please select a wallet to proceed.";
            $msg_type = "error";
        } elseif (!function_exists('propay_submit_withdrawal')) {
            $message = "Payment gateway helper is missing. Please upload the updated files again.";
            $msg_type = "error";
        } else {
            $w_res = $conn->query("SELECT * FROM player_wallets WHERE id=$wallet_id AND user_id=$uid");
            if ($w_res && $w_res->num_rows > 0) {
                $wallet = $w_res->fetch_assoc();
                $auto_result = propay_submit_withdrawal($conn, intval($uid), $amount, $wallet);
                if (!empty($auto_result['success'])) {
                    $message = "Withdrawal submitted successfully. Gateway Order: " . htmlspecialchars($auto_result['order_no'] ?? '', ENT_QUOTES, 'UTF-8');
                    $msg_type = "success";
                } else {
                    $message = $auto_result['message'] ?? "Gateway withdrawal failed. Please try again.";
                    $msg_type = "error";
                }
            } else {
                $message = "Selected wallet not found.";
                $msg_type = "error";
            }
        }

        $_SESSION['withdraw_flash_msg'] = $message;
        $_SESSION['withdraw_flash_type'] = $msg_type;
        header("Location: withdraw.php");
        exit();
    }

    // Current turnover summary for display and withdraw lock message.
    $turnover_summary = function_exists('propay_user_turnover_summary') ? propay_user_turnover_summary($conn, intval($uid)) : array('target' => 0, 'completed' => 0, 'remaining' => 0);
    $wager_remaining_display = (float)($turnover_summary['remaining'] ?? 0);

    // Fetch Saved Wallets
    $wallets = $conn->query("SELECT * FROM player_wallets WHERE user_id=$uid ORDER BY id DESC");
    $wallet_count = $wallets->num_rows;

} catch (Exception $e) {
    die("<div style='color:white; background:red; padding:10px; text-align:center;'>System Error: " . $e->getMessage() . "</div>");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Withdraw Money</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&display=swap" rel="stylesheet">

    <style>
        :root {
            /* Updated to match your Green theme */
            --bg-color: #f0f2f5;
            --header-color: #0d4a3a;
            --accent-color: #1de9b6;
            --card-bg: #ffffff;
            --text-main: #333333;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --blue-light: #f0fffb;
        }

        html, body { max-width: 100vw; overflow-x: hidden; touch-action: manipulation; }
        body { background-color: var(--bg-color); color: var(--text-main); font-family: 'Roboto', sans-serif; padding-bottom: 100px; -webkit-tap-highlight-color: transparent; user-select: none; }
        input { user-select: auto; }

        /* Header changed to Green Gradient */
        .tab-header-bg { 
            background: linear-gradient(135deg, #0e3d2c 0%, #1a5c40 100%); 
            padding: 12px; padding-bottom: 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .tab-active { background-color: var(--card-bg); color: var(--header-color); font-weight: 800; border-top-left-radius: 8px; border-top-right-radius: 8px; border-top: 3px solid var(--accent-color);}
        .tab-inactive { background-color: transparent; color: #1de9b6; font-weight: 600; border-top-left-radius: 8px; border-top-right-radius: 8px; transition: 0.2s;}
        .tab-inactive:hover { background-color: rgba(255,255,255,0.1); color: white; }

        .balance-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
            margin-bottom: 20px;
            position: relative;
            overflow: hidden;
        }
        .balance-card::before {
            content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: var(--header-color);
        }

        .wallet-card {
            background-color: var(--card-bg); 
            border: 1.5px solid var(--border-color); 
            border-radius: 8px; padding: 14px 16px;
            display: flex; justify-content: space-between; align-items: center; 
            cursor: pointer; transition: all 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            margin-bottom: 12px;
        }
        .wallet-card.selected { 
            border-color: var(--accent-color); 
            background-color: var(--blue-light); 
            box-shadow: 0 4px 10px rgba(30, 233, 182, 0.08);
        }
        .wallet-icon { width: 44px; height: 42EYHgEzWEdjJgYKAu8VjC4DohVYUMbvZ6Ua45MgSaYBTFv3725expKB69YsjM6Utj99E6Sn3LpcxQ4mNrwfrJLM5NGbWiQ; background: #fff;}
        
        .check-circle { width: 22px; height: 22px; border-radius: 50%; border: 2px solid var(--border-color); display: flex; align-items: center; justify-content: center; transition: 0.2s; background: white;}
        .wallet-card.selected .check-circle { border-color: var(--accent-color); }
        .inner-circle { width: 12px; height: 12px; background-color: var(--accent-color); border-radius: 50%; display: none; }
        .wallet-card.selected .inner-circle { display: block; }

        .input-wrapper { position: relative; }
        .custom-input {
            width: 100%; background: var(--card-bg); border: 1.5px solid var(--border-color);
            border-radius: 8px; padding: 14px 15px 14px 45px; color: var(--header-color);
            font-size: 20px; font-weight: 800; outline: none; transition: 0.2s; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
        }
        .custom-input:focus { border-color: var(--accent-color); box-shadow: 0 0 0 3px rgba(30, 233, 182, 0.1); }
        .custom-input::placeholder { color: #cbd5e1; font-weight: 500; font-size: 16px; }
        .currency-symbol { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--header-color); font-size: 20px; font-weight: 800; }

        /* Confirm Button changed to Green Gradient */
        .btn-submit { 
            background: linear-gradient(to bottom, #1de9b6 0%, #00897b 100%); 
            color: white; width: 100%; padding: 14px; 
            border-radius: 8px; font-weight: 800; font-size: 15px; border: none; cursor: pointer;
            text-transform: uppercase; letter-spacing: 1px; box-shadow: 0 4px 10px rgba(30, 233, 182, 0.3); transition: 0.2s;
        }
        .btn-submit:active { transform: translateY(2px); box-shadow: 0 2px 5px rgba(30, 233, 182, 0.3); }

        .btn-add-wallet {
            color: var(--header-color); font-size: 12px; font-weight: 800; text-transform: uppercase;
            background: var(--blue-light); border: 1px solid #1de9b6; padding: 6px 12px; border-radius: 6px; transition: 0.2s;
        }
        .btn-add-wallet:active { background: #bae6fd; transform: scale(0.95); }

        .text-error { color: #ef4444; font-size: 12px; margin-top: 6px; display: none; font-weight: 700; }
        .verify-alert { background: #fff1f2; border-left: 4px solid #ef4444; border-radius: 8px; padding: 16px; margin-bottom: 20px; border: 1px solid #fecaca; border-left-width: 4px; }
        
        .method-box {
            background: #f8fafc; border: 1.5px solid var(--border-color); padding: 10px; border-radius: 8px;
            text-align: center; color: var(--text-muted); font-size: 13px; font-weight: 700; transition: 0.2s;
        }
        .peer:checked + .method-box {
            border-color: var(--accent-color); color: var(--header-color); background: var(--blue-light); box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
        }
    </style>
</head>
<body>

    <div class="tab-header-bg sticky top-0 z-50">
        <div class="flex justify-between items-center mb-3">
            <h2 class="text-white text-[16px] font-black mx-auto uppercase tracking-widest italic">Withdraw Money</h2>
            <button onclick="window.location.href='index.php'" class="absolute right-4 text-emerald-200 hover:text-white transition"><i class="fas fa-times text-xl"></i></button>
        </div>
        <div class="flex rounded-md overflow-hidden">
            <a href="deposit.php" class="flex-1 text-center py-3 text-[13px] tab-inactive tracking-wide italic">DEPOSIT</a>
            <div class="flex-1 text-center py-3 text-[13px] tab-active tracking-wide italic">WITHDRAW</div>
        </div>
    </div>

    <div class="px-4 mt-5">
        
        <div class="balance-card">
            <p class="text-[11.5px] text-gray-500 font-bold uppercase tracking-wider mb-1">Available Balance</p>
            <h1 class="text-4xl font-black text-[#0d4a3a] drop-shadow-sm tracking-tight">৳ <?php echo number_format($user['balance'], 2); ?></h1>
        </div>

        <?php if(!empty($wager_remaining_display) && $wager_remaining_display > 0.01): ?>
        <div class="p-3 mb-5 rounded-lg border-l-4 bg-yellow-50 text-yellow-800 border-yellow-500 text-[13px] font-bold shadow-sm flex items-start gap-2">
            <i class="fas fa-sync-alt text-yellow-600 text-lg mt-0.5"></i>
            <div>
                <div>Turnover Remaining: ৳<?php echo number_format($wager_remaining_display, 2); ?></div>
                <div class="text-[11px] font-medium mt-1">Complete this turnover by playing games before withdrawal.</div>
            </div>
        </div>
        <?php endif; ?>


        <?php if(!empty($message)): ?>
        <div class="p-3 mb-5 rounded-lg border-l-4 text-[13px] font-bold shadow-sm flex items-center gap-2 <?php echo $msg_type == 'success' ? 'bg-green-50 text-green-700 border-emerald-500' : 'bg-red-50 text-red-700 border-red-500'; ?>">
            <i class="fas <?php echo $msg_type == 'success' ? 'fa-check-circle text-emerald-500' : 'fa-exclamation-circle text-red-500'; ?> text-lg"></i> 
            <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
        </div>
        <?php endif; ?>

        <form method="POST" id="withdrawForm" onsubmit="return validateSubmission()">
            <input type="hidden" name="withdraw_token" value="<?php echo htmlspecialchars($withdraw_token, ENT_QUOTES, 'UTF-8'); ?>">
            
            <div class="mb-6">
                <label class="block text-[#0d4a3a] font-bold text-[12px] uppercase tracking-wide mb-2">Amount (Min. ৳<?php echo number_format($min_withdraw, 0); ?>)</label>
                <div class="input-wrapper">
                    <span class="currency-symbol">৳</span>
                    <input type="number" name="amount" id="amountInput" placeholder="0.00" class="custom-input" required>
                </div>
                <p id="amountError" class="text-error"><i class="fas fa-exclamation-triangle mr-1"></i> Minimum withdrawal amount is ৳<?php echo number_format($min_withdraw, 0); ?>.</p>
                <p id="balanceError" class="text-error"><i class="fas fa-exclamation-triangle mr-1"></i> Insufficient balance.</p>
            </div>

            <div class="mb-6">
                <div class="flex justify-between items-center mb-3">
                    <label class="text-[#0d4a3a] font-bold text-[12px] uppercase tracking-wide">Withdraw To</label>
                    
                    <?php if($wallet_count < 5): ?>
                        <button type="button" onclick="openModal()" class="btn-add-wallet active:scale-90 transition">
                            <i class="fas fa-plus mr-1"></i> Add Account
                        </button>
                    <?php else: ?>
                        <span class="text-[10px] bg-red-100 text-red-600 border border-red-200 px-2 py-1 rounded font-bold uppercase tracking-wide"><i class="fas fa-exclamation-circle"></i> Limit Reached</span>
                    <?php endif; ?>
                </div>

                <div class="space-y-3">
                    <?php if($wallets->num_rows > 0): ?>
                        <?php while($w = $wallets->fetch_assoc()): ?>
                        <label class="wallet-card group relative active:scale-95 transition">
                            <input type="radio" name="selected_wallet" value="<?php echo $w['id']; ?>" class="absolute opacity-0 w-full h-full cursor-pointer" onchange="selectWallet(this)">
                            <div class="flex items-center gap-4">
                                <?php 
                                    $img = 'https://freelogopng.com/images/all_img/1656234745bkash-app-logo-png.png'; 
                                    $m = strtolower($w['method']);
                                    if($m == 'nagad') $img = 'https://freelogopng.com/images/all_img/1679248828Nagad-Logo-PNG.png';
                                    elseif($m == 'rocket') $img = 'https://seeklogo.com/images/D/dutch-bangla-rocket-logo-B4D1CC458D-seeklogo.com.png';
                                    elseif($m == 'upay') $img = 'https://images.seeklogo.com/logo-png/64/1/upay-logo-png_seeklogo-642533.png';
                                ?>
                                <img src="<?php echo $img; ?>" class="wallet-icon shadow-sm">
                                <div>
                                    <p class="text-[#0d4a3a] font-black text-[14px] capitalize tracking-wide"><?php echo htmlspecialchars($w['method']); ?></p>
                                    <p class="text-gray-500 font-bold text-[13px] font-mono mt-0.5"><?php echo htmlspecialchars($w['wallet_number']); ?></p>
                                </div>
                            </div>
                            <div class="check-circle">
                                <div class="inner-circle"></div>
                            </div>
                        </label>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-8 border-2 border-dashed border-gray-300 rounded-xl bg-gray-50">
                            <i class="fas fa-university text-3xl text-gray-300 mb-2"></i>
                            <p class="text-[#0d4a3a] text-[13px] font-bold">No Account Added</p>
                            <p class="text-[11px] text-gray-500 mt-1 font-medium">Please add an account to withdraw funds.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mt-8">
                <button type="submit" name="submit_withdraw" id="submitBtn" class="btn-submit italic">
                    Confirm Withdrawal
                </button>
            </div>

        </form>
    </div>

    <div id="addWalletModal" class="fixed inset-0 hidden bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4 z-[100]">
        <div class="bg-white w-full max-w-sm rounded-xl border border-gray-200 shadow-2xl p-6 relative">
            <button onclick="closeModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 bg-gray-100 w-8 h-8 rounded-full flex items-center justify-center transition"><i class="fas fa-times"></i></button>
            <h3 class="text-[#0d4a3a] font-black text-[16px] mb-5 uppercase tracking-wide border-b border-gray-100 pb-3 italic">Add New Account</h3>
            
            <form method="POST">
                <div class="mb-5">
                    <label class="text-[#0d4a3a] font-bold text-[11px] uppercase tracking-wide mb-2 block">Select Method</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="cursor-pointer">
                            <input type="radio" name="new_method" value="bkash" class="peer hidden" checked>
                            <div class="method-box">bKash</div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="new_method" value="nagad" class="peer hidden">
                            <div class="method-box">Nagad</div>
                        </label>
                    </div>
                </div>
                
                <div class="mb-6">
                    <label class="text-[#0d4a3a] font-bold text-[11px] uppercase tracking-wide mb-2 block">Account Number</label>
                    <input type="number" name="new_number" placeholder="e.g. 017xxxxxxxx" class="w-full bg-[#f8fafc] border-1.5 border-[#e2e8f0] rounded-lg p-3 text-[#0d4a3a] font-bold text-[14px] focus:border-[#1de9b6] outline-none transition focus:shadow-[0_0_0_3px_rgba(30,233,182,0.1)]" required>
                </div>
                
                <button type="submit" name="add_wallet" class="w-full bg-[#0d4a3a] text-white font-extrabold text-[13px] uppercase tracking-wide py-3.5 rounded-lg hover:bg-[#1a5c40] transition shadow-md active:scale-95">Save Account</button>
            </form>
        </div>
    </div>

    <?php include 'bottom_nav.php'; ?>

    <script>
        const userBalance = <?php echo $user['balance'] ?? 0; ?>;
        const minWithdraw = <?php echo json_encode((float)$min_withdraw); ?>;
        
        function openModal() { document.getElementById('addWalletModal').classList.remove('hidden'); }
        function closeModal() { document.getElementById('addWalletModal').classList.add('hidden'); }

        function selectWallet(radio) {
            document.querySelectorAll('.wallet-card').forEach(el => {
                el.classList.remove('selected');
            });
            radio.closest('.wallet-card').classList.add('selected');
        }

        function validateSubmission() {
            const amountVal = parseFloat(document.getElementById('amountInput').value);
            const walletSelected = document.querySelector('input[name="selected_wallet"]:checked');
            
            document.getElementById('amountError').style.display = 'none';
            document.getElementById('balanceError').style.display = 'none';

            if (isNaN(amountVal) || amountVal < minWithdraw) {
                document.getElementById('amountError').style.display = 'block';
                return false; 
            }
            if (amountVal > userBalance) {
                document.getElementById('balanceError').style.display = 'block';
                return false;
            }
            if (!walletSelected) {
                alert("Please select an account to withdraw.");
                return false;
            }
            
            return true; 
        }
    </script>

</body>
</html>