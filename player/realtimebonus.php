<?php
session_start();
$db_path = file_exists('includes/db.php') ? 'includes/db.php' : '../includes/db.php';
if (file_exists($db_path)) {
    require $db_path;
} else {
    $conn = new mysqli('localhost', 'root', '', 'bajipari');
}

// Auth Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$uid = $_SESSION['user_id'];
$msg = "";

// --- 1. CALCULATE AVAILABLE BONUS ---
$p_bonus_q = $conn->query("SELECT SUM(amount) as total FROM personal_bonuses WHERE user_id=$uid AND status='pending'");
$p_bonus = $p_bonus_q->fetch_assoc()['total'] ?? 0;

$g_bonus_q = $conn->query("SELECT SUM(reward_amount) as total FROM global_bonuses WHERE is_active=1 AND id NOT IN (SELECT bonus_id FROM bonus_claims WHERE user_id=$uid)");
$g_bonus = $g_bonus_q->fetch_assoc()['total'] ?? 0;

$total_available = $p_bonus + $g_bonus;

// --- 2. HANDLE CLAIM ACTION ---
if (isset($_POST['claim_bonus']) && $total_available > 0) {
    $conn->begin_transaction();
    try {
        $conn->query("UPDATE users SET balance = balance + $total_available WHERE id=$uid");
        $conn->query("UPDATE personal_bonuses SET status='claimed' WHERE user_id=$uid AND status='pending'");
        
        $global_ids_q = $conn->query("SELECT id FROM global_bonuses WHERE is_active=1 AND id NOT IN (SELECT bonus_id FROM bonus_claims WHERE user_id=$uid)");
        if ($global_ids_q->num_rows > 0) {
            while ($row = $global_ids_q->fetch_assoc()) {
                $bid = $row['id'];
                $conn->query("INSERT INTO bonus_claims (user_id, bonus_id, claimed_at) VALUES ($uid, $bid, NOW())");
            }
        }

        $desc = "Real-time Bonus Claim";
        $conn->query("INSERT INTO transactions_fake (user_id, type, amount, method, status, created_at) VALUES ($uid, 'bonus', $total_available, '$desc', 'approved', NOW())");

        $conn->commit();
        $msg = "Bonus claimed successfully!";
        $total_available = 0; 
    } catch (Exception $e) {
        $conn->rollback();
        $msg = "Error claiming bonus.";
    }
}

// --- 3. FETCH STATS ---
$today_q = $conn->query("SELECT SUM(amount) as total FROM transactions_fake WHERE user_id=$uid AND type='bonus' AND DATE(created_at) = CURDATE()");
$claimed_today = $today_q->fetch_assoc()['total'] ?? 0;

$week_q = $conn->query("SELECT SUM(amount) as total FROM transactions_fake WHERE user_id=$uid AND type='bonus' AND YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)");
$claimed_week = $week_q->fetch_assoc()['total'] ?? 0;

// --- 4. FETCH HISTORY ---
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'today';
$custom_date = isset($_GET['date']) ? $conn->real_escape_string($_GET['date']) : '';

$where_sql = "user_id=$uid AND type='bonus'";

if ($filter == 'today') {
    $where_sql .= " AND DATE(created_at) = CURDATE()";
} elseif ($filter == 'yesterday') {
    $where_sql .= " AND DATE(created_at) = CURDATE() - INTERVAL 1 DAY";
} elseif ($filter == 'week') {
    $where_sql .= " AND created_at >= CURDATE() - INTERVAL 7 DAY";
} elseif ($filter == 'custom' && !empty($custom_date)) {
    $where_sql .= " AND DATE(created_at) = '$custom_date'";
}

$history_q = $conn->query("SELECT * FROM transactions_fake WHERE $where_sql ORDER BY created_at DESC");

// Button Text Logic
$opt_btn_text = ($filter == 'custom' && !empty($custom_date)) ? $custom_date : 'Optional Date';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Real-Time Bonus</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

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
            padding-bottom: 80px; 
            -webkit-tap-highlight-color: transparent;
        }
        
        .header-bg { 
            background: linear-gradient(135deg, #0f395c 0%, #1a5c92 100%);
            padding: 16px; 
            display: flex; justify-content: space-between; align-items: center; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .bonus-card {
            background: linear-gradient(135deg, #154b77 0%, #0d2a45 100%);
            position: relative; overflow: hidden; text-align: center; padding: 35px 20px 40px;
            border-bottom: 4px solid var(--accent-color);
            box-shadow: 0 4px 15px rgba(21, 75, 119, 0.15);
        }
        .star {
            position: absolute; background: white; border-radius: 50%; opacity: 0.5;
            animation: twinkle 2s infinite ease-in-out;
        }
        @keyframes twinkle { 0%, 100% { opacity: 0.2; transform: scale(0.8); } 50% { opacity: 0.8; transform: scale(1.2); } }
        
        .money-icon { 
            width: 70px; margin: 0 auto 15px; 
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.3)); 
        }
        
        .btn-gold {
            background: linear-gradient(to bottom, #4caf50 0%, #388e3c 100%);
            color: white; font-weight: 800; font-size: 15px; padding: 12px 40px; border-radius: 6px;
            box-shadow: 0 4px 10px rgba(67, 160, 71, 0.3); border: none; transition: all 0.2s;
            text-transform: uppercase; letter-spacing: 1px;
        }
        .btn-gold:active { transform: translateY(2px); box-shadow: 0 2px 5px rgba(67, 160, 71, 0.3); }

        .btn-outline {
            background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.3);
            color: white; font-weight: 700; font-size: 14px; padding: 12px 40px; border-radius: 6px;
            text-transform: uppercase; letter-spacing: 1px; display: inline-block;
        }

        .stats-bar { 
            display: flex; background-color: var(--card-bg); padding: 15px 0; 
            border-bottom: 1px solid var(--border-color); box-shadow: 0 2px 5px rgba(0,0,0,0.02);
        }
        .stat-item { flex: 1; text-align: center; border-right: 1px solid var(--border-color); }
        .stat-item:last-child { border: none; }
        .stat-label { font-size: 11px; color: var(--text-muted); margin-bottom: 4px; font-weight: 600; text-transform: uppercase; }
        .stat-val { font-size: 16px; color: var(--header-color); font-weight: 800; }

        .sec-title { 
            border-left: 4px solid var(--accent-color); 
            padding-left: 10px; font-size: 14px; color: var(--header-color); 
            margin: 20px 15px 12px; font-weight: 800; text-transform: uppercase;
        }
        
        /* Filter Styles */
        .filter-row { 
            display: flex; gap: 8px; padding: 0 15px; margin-bottom: 15px; 
            overflow-x: auto; 
        }
        .filter-row::-webkit-scrollbar { display: none; }
        
        .filter-btn {
            background-color: var(--card-bg); color: var(--text-muted); font-size: 12px;
            padding: 8px 14px; border-radius: 6px; border: 1px solid var(--border-color);
            flex: 1; text-align: center; cursor: pointer; white-space: nowrap; min-width: fit-content;
            font-weight: 600; box-shadow: 0 1px 2px rgba(0,0,0,0.02); transition: 0.2s;
        }
        .filter-btn.active { 
            background-color: #e0f2fe; color: var(--header-color); 
            border-color: #bae6fd; font-weight: 800; 
        }
        
        /* History Item */
        .history-item { 
            background-color: var(--card-bg); padding: 14px 16px; 
            border-bottom: 1px solid var(--border-color); 
            display: flex; justify-content: space-between; align-items: center; 
        }
        
        .no-data { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 250px; }
        .clipboard-icon { width: 60px; margin-bottom: 15px; opacity: 0.2; }

        .bottom-nav { background-color: #ffffff; border-top: 1px solid var(--border-color); box-shadow: 0 -4px 15px rgba(0,0,0,0.03); }
        .nav-item { color: #94a3b8; transition: 0.2s; } 
        .nav-item.active { color: var(--header-color); }
        
        /* Flatpickr Calendar Fix for Light Theme */
        .flatpickr-calendar {
            z-index: 9999 !important;
            font-family: 'Roboto', sans-serif !important;
        }
    </style>
</head>
<body>

    <div class="header-bg sticky top-0 z-50">
        <a href="account.php" class="text-xl text-blue-200 hover:text-white p-1"><i class="fas fa-home"></i></a>
        <h1 class="text-white font-bold text-[15px] uppercase tracking-wider">Real-Time Bonus</h1>
        <a href="dashboard.php" class="text-xl text-blue-200 hover:text-white p-1"><i class="fas fa-times"></i></a>
    </div>

    <div class="bonus-card">
        <div class="star" style="top: 20%; left: 20%; width: 4px; height: 4px;"></div>
        <div class="star" style="top: 30%; right: 25%; width: 3px; height: 3px; animation-delay: 0.5s;"></div>
        <div class="star" style="top: 60%; left: 15%; width: 5px; height: 5px; animation-delay: 1s;"></div>
        <div class="star" style="top: 70%; right: 15%; width: 3px; height: 3px; animation-delay: 1.5s;"></div>
        
        <div class="relative z-10">
            <p class="text-[12px] text-blue-200 mb-3 flex justify-center items-center gap-2 font-bold uppercase tracking-wide">
                <i class="fas fa-gift text-[#43a047]"></i> Available Amount
            </p>
            <img src="https://cdn-icons-png.flaticon.com/512/2489/2489756.png" class="money-icon">
            <h2 class="text-4xl font-black text-white mb-6 tracking-tight drop-shadow-md">৳ <?php echo number_format($total_available, 0); ?></h2>
            
            <form method="POST">
                <?php if($total_available > 0): ?>
                    <button type="submit" name="claim_bonus" class="btn-gold w-56">Claim Now</button>
                <?php else: ?>
                    <a href="index.php" class="btn-outline w-56">Play Games</a>
                <?php endif; ?>
            </form>
            
            <?php if($msg): ?>
                <div class="mt-4 bg-green-500/20 text-green-100 px-3 py-1.5 rounded-full inline-block text-xs font-bold border border-green-500/30">
                    <i class="fas fa-check-circle mr-1"></i> <?php echo $msg; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="stats-bar">
        <div class="stat-item">
            <p class="stat-label">Claimed Today</p>
            <p class="stat-val">৳ <?php echo number_format($claimed_today, 0); ?></p>
        </div>
        <div class="stat-item">
            <p class="stat-label">Claimed This Week</p>
            <p class="stat-val">৳ <?php echo number_format($claimed_week, 0); ?></p>
        </div>
    </div>

    <div class="sec-title">Summary</div>
    
    <div class="filter-row">
        <a href="?filter=today" class="filter-btn <?php echo $filter == 'today' ? 'active' : ''; ?>">Today</a>
        <a href="?filter=yesterday" class="filter-btn <?php echo $filter == 'yesterday' ? 'active' : ''; ?>">Yesterday</a>
        <a href="?filter=week" class="filter-btn <?php echo $filter == 'week' ? 'active' : ''; ?>">Last 7 Days</a>
        
        <button type="button" id="optionalDateBtn" class="filter-btn <?php echo $filter == 'custom' ? 'active' : ''; ?>">
            <i class="far fa-calendar-alt mr-1"></i> <?php echo $opt_btn_text; ?>
        </button>
    </div>

    <div class="min-h-[300px] bg-white border-t border-gray-200">
        <?php if($history_q && $history_q->num_rows > 0): ?>
            <?php while($row = $history_q->fetch_assoc()): ?>
                <div class="history-item">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-green-50 rounded-full flex items-center justify-center border border-green-100">
                            <i class="fas fa-gift text-[#43a047]"></i>
                        </div>
                        <div>
                            <p class="text-[13px] text-[#154b77] font-extrabold">Real-Time Bonus</p>
                            <p class="text-[10.5px] text-gray-400 font-medium mt-0.5"><?php echo date('d M, Y - h:i A', strtotime($row['created_at'])); ?></p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-[#43a047] font-mono font-black text-[15px]">+ ৳<?php echo $row['amount']; ?></p>
                        <p class="text-[10px] text-gray-400 font-bold uppercase mt-0.5"><i class="fas fa-check text-green-500 mr-0.5"></i> Success</p>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-data">
                <i class="fas fa-box-open text-5xl text-gray-200 mb-3"></i>
                <p class="text-sm text-gray-400 font-bold">No Records Found</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="fixed bottom-0 left-0 w-full h-[65px] bottom-nav z-50 flex justify-between items-center px-4">
        <a href="index.php" class="flex flex-col items-center justify-center nav-item">
            <i class="fas fa-home text-[22px] mb-1"></i>
            <span class="text-[10px] font-bold tracking-wide">Home</span>
        </a>
        <a href="promotions.php" class="flex flex-col items-center justify-center nav-item active">
            <i class="fas fa-gift text-[22px] mb-1"></i>
            <span class="text-[10px] font-bold tracking-wide">Promos</span>
        </a>
        <a href="deposit.php" class="flex flex-col items-center justify-center nav-item">
            <i class="fas fa-wallet text-[22px] mb-1"></i>
            <span class="text-[10px] font-bold tracking-wide">Deposit</span>
        </a>
        <a href="account.php" class="flex flex-col items-center justify-center nav-item">
            <i class="fas fa-user-circle text-[22px] mb-1"></i>
            <span class="text-[10px] font-bold tracking-wide">Account</span>
        </a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        flatpickr("#optionalDateBtn", {
            dateFormat: "Y-m-d",
            maxDate: "today",
            disableMobile: true, 
            defaultDate: "<?php echo ($filter == 'custom' && !empty($custom_date)) ? $custom_date : ''; ?>",
            onChange: function(selectedDates, dateStr, instance) {
                // Redirect on select
                window.location.href = '?filter=custom&date=' + dateStr;
            }
        });
    });
    </script>

</body>
</html>