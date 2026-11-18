<?php
session_start();
// ডাটাবেস সংযোগ
$db_path = file_exists('includes/db.php') ? 'includes/db.php' : '../includes/db.php';
if (file_exists($db_path)) {
    require $db_path;
} else {
    // ফলব্যাক
    $conn = new mysqli('localhost', 'root', '', 'bajipari');
}

// লগইন চেক
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// ইউজার ডাটা (আপনার লজিক অনুযায়ী)
$current_vip_level = "NORMAL";
$next_vip_level = "Ace";
$current_xp = 0;
$target_xp = 10000;
$vip_points = 0;
$vp_ratio = 60; 

// XP পার্সেন্টেজ ক্যালকুলেশন
$xp_percent = ($current_xp / $target_xp) * 100;
$xp_percent = $xp_percent > 100 ? 100 : $xp_percent;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>My VIP</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #121619; /* ডিপ ডার্ক ব্যাকগ্রাউন্ড */
            color: #d1d5db;
        }

        /* --- Header --- */
        .vip-header {
            background-color: #1c2429;
            display: flex; justify-content: center; align-items: center;
            padding: 15px; 
            position: relative;
        }
        .close-btn {
            position: absolute;
            right: 15px;
            color: #fff;
            font-size: 18px;
        }

        /* --- VIP Card (Greenish Dark Gradient) --- */
        .vip-card {
            background: linear-gradient(180deg, #253334 0%, #1a2225 100%); /* ছবির মতো গ্রিনিশ ডার্ক */
            border-radius: 16px;
            padding: 20px 20px 0 20px; /* নিচে প্যাডিং নেই কারণ ফুটার আলাদা */
            margin: 15px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            border: 1px solid #2f3b3e;
            position: relative;
            overflow: hidden;
        }

        /* Icon Circle */
        .vip-icon-circle {
            width: 45px; height: 45px;
            border-radius: 50%;
            background: #0d1112;
            border: 1px solid #374151;
            display: flex; align-items: center; justify-content: center;
        }

        /* Progress Bar Styles */
        .progress-container {
            position: relative;
            margin-top: 25px;
            margin-bottom: 5px;
        }
        .progress-track {
            background-color: #3b474a;
            height: 4px;
            border-radius: 99px;
            width: 100%;
        }
        .progress-fill {
            background-color: #d1d5db;
            height: 100%;
            border-radius: 99px;
            position: absolute;
            top: 0; left: 0;
            box-shadow: 0 0 8px rgba(255,255,255,0.3);
        }
        .progress-thumb {
            width: 10px; height: 10px;
            background: white;
            border-radius: 50%;
            position: absolute;
            top: 50%; right: 0;
            transform: translate(50%, -50%);
            box-shadow: 0 0 5px rgba(255,255,255,0.8);
        }

        /* Card Footer (View Details) */
        .card-footer {
            background-color: rgba(255,255,255,0.03);
            margin: 20px -20px 0 -20px; /* কার্ডের প্যাডিং ক্যান্সেল করার জন্য */
            padding: 12px 20px;
            text-align: right;
            border-top: 1px solid #2f3b3e;
        }

        /* --- Convert Box --- */
        .convert-container {
            background-color: #1c2429;
            border-radius: 16px;
            padding: 20px;
            margin: 0 15px 20px 15px;
            border: 1px solid #2a3439;
        }
        
        /* Inputs */
        .custom-input {
            background-color: #121619; /* ইনপুট ব্যাকগ্রাউন্ড */
            border: 1px solid #2a3439;
            color: #6b7280;
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            font-size: 16px;
            outline: none;
            font-weight: 500;
        }
        
        /* Buttons */
        .btn-history {
            background: linear-gradient(to bottom, #fcd34d, #d97706);
            color: white; font-weight: 600; font-size: 12px;
            padding: 5px 15px; border-radius: 6px;
            text-shadow: 0 1px 1px rgba(0,0,0,0.2);
            display: flex; align-items: center; gap: 5px;
        }
        .btn-detail {
            background: linear-gradient(to bottom, #fcd34d, #d97706);
            color: white; font-weight: 600; font-size: 12px;
            padding: 5px 15px; border-radius: 6px;
            display: inline-flex; align-items: center; gap: 5px;
        }

        .btn-convert-action {
            background: linear-gradient(180deg, #B48811 0%, #F9D423 50%, #B48811 100%);
            border: 1px solid #856404;
            color: white;
            width: 100%;
            padding: 14px;
            border-radius: 50px; /* পিল শেপ */
            font-weight: 600;
            font-size: 15px;
            margin-top: 20px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
            text-shadow: 0 1px 2px rgba(0,0,0,0.5);
            position: relative;
            overflow: hidden;
        }
        /* বাটন শাইন ইফেক্ট */
        .btn-convert-action::after {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 100%; height: 50%;
            background: linear-gradient(to bottom, rgba(255,255,255,0.4), transparent);
            pointer-events: none;
        }

        /* --- Animations --- */
        @keyframes bounce-arrow {
            0%, 100% { transform: translateY(0); opacity: 0.5; color: #9ca3af; }
            50% { transform: translateY(3px); opacity: 1; color: #facc15; }
        }
        .animate-arrow { animation: bounce-arrow 1.5s infinite; }
    </style>
</head>
<body>

    <div class="vip-header sticky top-0 z-50">
        <h1 class="text-white font-medium text-lg">My VIP</h1>
        <button onclick="window.history.back()" class="close-btn"><i class="fas fa-times"></i></button>
    </div>

    <div class="vip-card">
        <div class="flex justify-between items-start">
            <div class="flex items-center gap-3">
                <div class="vip-icon-circle">
                    <img src="https://cdn-icons-png.flaticon.com/512/10701/10701484.png" class="w-6 h-6 object-contain filter grayscale brightness-150">
                </div>
                <div>
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-[-2px]">VIP LEVEL</p>
                    <h2 class="text-2xl font-black text-white uppercase"><?php echo $current_vip_level; ?></h2>
                </div>
            </div>
            <button class="btn-history">
                <i class="fas fa-history"></i> History
            </button>
        </div>

        <div class="progress-container">
            <div class="flex justify-end text-xs text-gray-400 mb-1 font-medium">
                <span class="text-[#facc15] mr-0.5"><?php echo $current_xp; ?></span>/<?php echo $target_xp; ?>
                <i class="fas fa-clover text-[#4ade80] ml-2 text-sm"></i>
            </div>
            
            <div class="progress-track">
                <div class="progress-fill" style="width: <?php echo $xp_percent; ?>%;">
                    <div class="progress-thumb"></div>
                </div>
            </div>
        </div>

        <p class="text-xs text-gray-400 mt-3 leading-relaxed">
            You need <span class="text-[#facc15] font-bold"><?php echo number_format($target_xp - $current_xp); ?></span> more VIP Experience to upgrade to next <span class="text-[#facc15] font-bold"><?php echo $next_vip_level; ?></span> level.
        </p>

        <div class="card-footer">
            <a href="#" class="text-xs text-gray-400 hover:text-white flex items-center justify-end gap-1 font-medium">
                View VIP Details <i class="fas fa-angle-double-right text-[10px]"></i>
            </a>
        </div>
    </div>

    <div class="px-5 mb-4 flex justify-between items-end">
        <div>
            <p class="text-xs text-gray-400 mb-1">VIP Points</p>
            <div class="flex items-end gap-2">
                <h1 class="text-4xl font-light text-white leading-none"><?php echo $vip_points; ?></h1>
                <span class="text-[10px] text-gray-500 font-bold bg-[#1f2937] px-1.5 py-0.5 rounded mb-1">VP</span>
            </div>
        </div>
        <button class="btn-detail">
            <i class="fas fa-file-alt text-[10px]"></i> Detail
        </button>
    </div>

    <div class="px-5 mb-3 flex items-center gap-2">
        <div class="w-1 h-3 bg-[#facc15] rounded-full"></div>
        <h3 class="text-sm text-gray-300 font-medium">Convert VP</h3>
    </div>

    <div class="convert-container">
        
        <div class="flex items-center gap-4 mb-2">
            <div class="w-12 h-12 flex-shrink-0">
                <video autoplay loop muted playsinline class="w-full h-full object-contain">
                    <source src="../assets/img/v1.webm" type="video/webm">
                </video>
            </div>
            
            <div class="flex-1">
                <div class="flex justify-between text-[11px] text-gray-400 mb-1.5">
                    <span>Points</span>
                    <span>Minimum VP Required: 10</span>
                </div>
                <input type="number" value="0" class="custom-input" readonly>
            </div>
        </div>

        <div class="flex flex-col items-center justify-center -my-1 z-10 relative">
            <i class="fas fa-angle-double-down text-lg animate-arrow"></i>
            <span class="text-[10px] text-gray-500 mt-0.5">VP Conversion Ratio : <span class="text-[#facc15]"><?php echo $vp_ratio; ?></span></span>
        </div>

        <div class="flex items-center gap-4 mt-2">
            <div class="w-12 h-12 flex-shrink-0">
                <video autoplay loop muted playsinline class="w-full h-full object-contain">
                    <source src="../assets/img/v2.webm" type="video/webm">
                </video>
            </div>

            <div class="flex-1">
                <div class="flex justify-between text-[11px] text-gray-400 mb-1.5">
                    <span>Real Money</span>
                </div>
                <input type="text" value="0" class="custom-input" readonly>
            </div>
        </div>

        <button class="btn-convert-action">
            Convert to Real Money
        </button>

    </div>

</body>
</html>