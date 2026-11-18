<?php
session_start();
// ১. ডাটাবেজ কানেকশন (ম্যানুয়াল কানেকশন রিমুভড)
$db_path = file_exists('includes/db.php') ? 'includes/db.php' : '../includes/db.php';
if (file_exists($db_path)) {
    require $db_path;
}

$referral_helper_path = file_exists('includes/referral_helper.php') ? 'includes/referral_helper.php' : '../includes/referral_helper.php';
if (file_exists($referral_helper_path)) {
    require_once $referral_helper_path;
}

// লগইন চেক
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$claim_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['claim_rewards']) && function_exists('referral_claim_rewards')) {
    $claim_result = referral_claim_rewards($conn, $user_id);
    $claim_message = $claim_result['message'];
}

// 1. ইউজারের আসল ডাটা ফেচ করা
$user_query = $conn->query("SELECT * FROM users WHERE id=$user_id");
$user_data = $user_query->fetch_assoc();

// 2. রিয়েল ইনভাইট কোড (ডাটাবেস থেকে)
$invite_code = $user_data['ref_code'];

// যদি কোনো কারণেref_code খালি থাকে, তবে একটি জেনারেট করে সেভ করা হবে
if (empty($invite_code)) {
    $invite_code = 'REF' . rand(10000, 99999) . time();
    $conn->query("UPDATE users SET ref_code='$invite_code' WHERE id=$user_id");
}

// 3. রিয়েল রেফারেল লিংক (অটোমেটিক ডোমেইন ডিটেকশন সহ)
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
$referral_link = $base_url . "/player/signup.php?ref=" . $invite_code;

// QR Code API
$qr_code_url = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($referral_link);

// 4. রিয়েল রেফারেল কাউন্ট ও রিওয়ার্ড স্ট্যাটস
if (function_exists('referral_get_stats')) {
    $ref_stats = referral_get_stats($conn, $user_id);
    $referral_count = $ref_stats['referral_count'];
    $today_reward = $ref_stats['today_reward'];
    $yesterday_reward = $ref_stats['yesterday_reward'];
    $total_reward = $ref_stats['total_reward'];
} else {
    $ref_count_query = $conn->query("SELECT COUNT(id) as total_refs FROM users WHERE referrer_id = $user_id");
    $referral_count = $ref_count_query->fetch_assoc()['total_refs'];
    $today_reward = 0;
    $yesterday_reward = 0;
    $total_reward = 0;
}

$reward_details = function_exists('referral_get_details') ? referral_get_details($conn, $user_id) : [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Referral Program</title> <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-color: #f0f2f5;
            --header-color: #154b77;
            --accent-color: #43a047;
            --card-bg: #ffffff;
            --text-main: #333333;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
        }

        body { 
            background-color: var(--bg-color); 
            color: var(--text-main); 
            font-family: 'Roboto', sans-serif; 
            padding-bottom: 90px;
            -webkit-tap-highlight-color: transparent;
        }

        .header-bg { 
            background: linear-gradient(135deg, #0f395c 0%, #1a5c92 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .tabs-container {
            display: flex;
            background-color: #ffffff;
            border-bottom: 1px solid var(--border-color);
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
        }
        .tab-btn {
            flex: 1;
            text-align: center;
            padding: 16px 0;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-muted);
            cursor: pointer;
            position: relative;
            transition: all 0.2s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .tab-btn.active {
            color: var(--header-color);
            font-weight: 800;
        }
        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background-color: var(--accent-color);
            border-top-left-radius: 3px;
            border-top-right-radius: 3px;
        }

        .white-card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
        }
        
        .section-title {
            border-left: 4px solid var(--accent-color);
            padding-left: 10px;
            font-size: 14px;
            color: var(--header-color);
            margin-bottom: 16px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .copy-box {
            background-color: #f8fafc;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            display: flex;
            align-items: center;
            padding: 4px;
            margin-top: 6px;
        }
        
        .copy-input {
            background: transparent;
            border: none;
            color: var(--header-color);
            width: 100%;
            padding: 8px 10px;
            font-size: 13px;
            font-weight: 700;
            outline: none;
        }
        
        .copy-btn {
            background: linear-gradient(to bottom, #4caf50 0%, #388e3c 100%);
            color: #ffffff;
            font-weight: 800;
            font-size: 12px;
            padding: 10px 18px;
            border-radius: 6px;
            white-space: nowrap;
            border: none;
            text-transform: uppercase;
            box-shadow: 0 2px 4px rgba(67, 160, 71, 0.2);
        }

        .copy-icon-btn {
            background: linear-gradient(to bottom, #4caf50 0%, #388e3c 100%);
            width: 40px; height: 40px;
            display: flex; align-items: center; justify-content: center;
            border-radius: 6px; color: #ffffff;
            box-shadow: 0 2px 4px rgba(67, 160, 71, 0.2);
        }

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            text-align: center;
            background-color: #f8fafc;
            border-top: 1px solid var(--border-color);
            margin: 0 -16px -16px -16px;
            border-bottom-left-radius: 12px;
            border-bottom-right-radius: 12px;
        }
        .stat-item { padding: 12px 8px; border-right: 1px solid var(--border-color); }
        .stat-item:last-child { border-right: none; }
        .stat-item h4 { font-size: 10px; color: var(--text-muted); margin-bottom: 4px; font-weight: 600; text-transform: uppercase;}
        .stat-item p { font-size: 15px; color: var(--header-color); font-weight: 900; }

        .claim-row {
            display: flex; justify-content: space-between; align-items: center;
            padding-top: 8px;
        }
        .claim-btn {
            background: #e0f2fe;
            color: var(--header-color);
            font-weight: 800;
            font-size: 13px;
            padding: 8px 24px;
            border-radius: 6px;
            border: 1px solid #bae6fd;
            text-transform: uppercase;
        }

        .pyramid-img {
            width: 100%;
            max-width: 320px;
            margin: 20px auto;
            display: block;
            border-radius: 8px;
        }

        .tab-content { display: none; animation: fadeIn 0.3s ease; }
        .tab-content.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    </style>
</head>
<body>

    <div class="header-bg p-4 flex justify-between items-center sticky top-0 z-50">
        <a href="index.php" class="text-white text-xl p-1"><i class="fas fa-home"></i></a>
        <h1 class="text-white font-bold text-[15px] uppercase tracking-wider">Referral Program</h1>
        <button onclick="window.history.back()" class="text-white text-xl p-1"><i class="fas fa-times"></i></button>
    </div>

    <div class="tabs-container sticky top-[60px] z-40">
        <div class="tab-btn active" onclick="switchTab('invite', this)">Invite</div>
        <div class="tab-btn" onclick="switchTab('details', this)">Details</div>
    </div>

    <div id="invite" class="tab-content active p-4">
        <?php if ($claim_message): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 text-sm font-semibold rounded-lg p-3 mb-4 text-center">
                <?php echo htmlspecialchars($claim_message); ?>
            </div>
        <?php endif; ?>
        <div class="white-card p-0 overflow-hidden">
            <div class="p-4 pb-0">
                <div class="section-title">Refer Friends and Earn</div>
                <img src="../assets/img/refer_banner.png" onerror="this.src='https://placehold.co/600x220/154b77/fff?text=REFER+A+FRIEND'" class="w-full rounded-lg mb-5 shadow-sm border border-gray-100">
                
                <div class="flex gap-4">
                    <div class="bg-white p-1 rounded-lg w-[110px] h-[110px] flex-shrink-0 border border-gray-200 shadow-sm">
                        <img src="<?php echo $qr_code_url; ?>" class="w-full h-full object-contain">
                    </div>
                    
                    <div class="flex-1 flex flex-col justify-between">
                        <div>
                            <p class="text-[10.5px] font-bold text-gray-500 uppercase">Invitation Link</p>
                            <div class="copy-box">
                                <button class="w-full text-center text-[12px] font-extrabold text-white bg-gradient-to-b from-[#4caf50] to-[#388e3c] rounded py-2 shadow-sm" onclick="shareLink()">
                                    <i class="fas fa-share-alt mr-1"></i> Share Link
                                </button>
                            </div>
                        </div>
                        <div class="mt-2">
                            <p class="text-[10.5px] font-bold text-gray-500 uppercase">Invitation Code</p>
                            <div class="copy-box">
                                <input type="text" value="<?php echo $invite_code; ?>" readonly class="copy-input text-center">
                                <button class="copy-icon-btn" onclick="copyToClipboard('<?php echo $invite_code; ?>')">
                                    <i class="far fa-copy"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="stat-grid mt-5">
                <div class="stat-item">
                    <h4>Total Referrals</h4>
                    <p class="text-[#43a047]"><?php echo $referral_count; ?></p>
                </div>
                <div class="stat-item">
                    <h4>Today's Rewards</h4>
                    <p>৳ <?php echo number_format($today_reward, 2); ?></p>
                </div>
                <div class="stat-item">
                    <h4>Yesterday's Rewards</h4>
                    <p>৳ <?php echo number_format($yesterday_reward, 2); ?></p>
                </div>
            </div>
        </div>

        <div class="white-card">
            <div class="section-title">Available Cash Rewards</div>
            <div class="claim-row">
                <span class="text-3xl text-[#154b77] font-black drop-shadow-sm">৳ <?php echo number_format($total_reward, 2); ?></span>
                <form method="POST" class="m-0">
                    <button type="submit" name="claim_rewards" value="1" class="claim-btn" <?php echo $total_reward <= 0 ? 'disabled style="opacity:0.5;cursor:not-allowed;"' : ''; ?>>Claim Now</button>
                </form>
            </div>
        </div>

        <div class="white-card">
            <div class="section-title">How to earn more rewards</div>
            <p class="text-[12px] text-gray-600 leading-relaxed mb-4 font-medium text-justify">
                All referrers will receive a certain cash reward percentage for every referee when they play games on the platform. Build your network to increase earnings.
            </p>
            <img src="../assets/img/refer_pyramid.png" onerror="this.src='https://placehold.co/400x250/f8fafc/154b77?text=Reward+Structure'" class="pyramid-img border border-gray-100 shadow-sm">
            <div class="bg-blue-50 p-3 rounded-lg border border-blue-100 mt-4">
                <p class="text-[11px] text-[#154b77] text-center mb-1 font-bold">Be diligent in referring, be the upline and earn up to 3 tiers easily!</p>
                <p class="text-[13px] text-[#43a047] font-black text-center uppercase tracking-wide">Welcome to lifetime commissions!</p>
            </div>
        </div>
    </div>

    <div id="details" class="tab-content">
        <?php if (empty($reward_details)): ?>
            <div class="flex flex-col items-center justify-center h-[350px] bg-white">
                <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                    <i class="fas fa-box-open text-3xl text-gray-300"></i>
                </div>
                <p class="text-gray-400 text-sm font-bold">No Records Found</p>
            </div>
        <?php else: ?>
            <div class="p-4 space-y-3">
                <?php foreach ($reward_details as $detail): ?>
                    <div class="white-card !mb-0">
                        <div class="flex justify-between items-start gap-3">
                            <div>
                                <p class="text-[12px] font-bold text-[#154b77]">
                                    <?php echo htmlspecialchars($detail['username'] ?: $detail['phone'] ?: 'User #' . $detail['referee_id']); ?>
                                </p>
                                <p class="text-[10px] text-gray-500 mt-1">
                                    <?php if (($detail['source_type'] ?? '') === 'signup_bonus'): ?>
                                        Referral Signup Bonus
                                    <?php else: ?>
                                        Tier <?php echo (int)$detail['tier']; ?> • Bet ৳<?php echo number_format((float)$detail['bet_amount'], 2); ?>
                                    <?php endif; ?>
                                </p>
                                <p class="text-[10px] text-gray-400 mt-1">
                                    <?php echo date('d M Y, h:i A', strtotime($detail['created_at'])); ?>
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-[14px] font-black text-[#43a047]">+৳<?php echo number_format((float)$detail['amount'], 2); ?></p>
                                <p class="text-[10px] font-semibold uppercase mt-1 <?php echo $detail['status'] === 'claimed' ? 'text-gray-400' : 'text-orange-500'; ?>">
                                    <?php echo htmlspecialchars($detail['status']); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'bottom_nav.php'; ?>

    <script>
        function switchTab(tabId, btn) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
            btn.classList.add('active');
        }

        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                alert('Copied to clipboard: ' + text); 
            });
        }
        
        function shareLink() {
            if (navigator.share) {
                navigator.share({
                    title: 'Referral Program',
                    text: 'Join me and earn rewards!',
                    url: '<?php echo $referral_link; ?>',
                });
            } else {
                copyToClipboard('<?php echo $referral_link; ?>');
            }
        }
    </script>
</body>
</html>