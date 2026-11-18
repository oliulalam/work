<?php
session_start();
ob_start();

$db_path = file_exists(__DIR__ . '/../includes/db.php') ? __DIR__ . '/../includes/db.php' : 'includes/db.php';
require $db_path;

$spin_helper_path = file_exists(__DIR__ . '/../includes/spin_helper.php') ? __DIR__ . '/../includes/spin_helper.php' : 'includes/spin_helper.php';
require_once $spin_helper_path;

if (!isset($_SESSION['user_id'])) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || (isset($_POST['action']) && $_POST['action'] === 'spin')) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Please login to play']);
        exit();
    }
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

// AJAX Spin Endpoint
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'spin') {
    header('Content-Type: application/json');
    $result = spin_process_user_spin($conn, $user_id);
    echo json_encode($result);
    exit();
}

// Fetch Page Data
$spin_status = spin_get_user_status($conn, $user_id);
$spin_history = spin_get_history($conn, $user_id, 20);
$sectors = spin_get_wheel_sectors();

$user_q = $conn->query("SELECT balance, username FROM users WHERE id = $user_id LIMIT 1");
$user_data = ($user_q && $user_q->num_rows > 0) ? $user_q->fetch_assoc() : ['balance' => 0.00, 'username' => 'Player'];
$balance = (float)$user_data['balance'];

$settings_q = $conn->query("SELECT site_name FROM settings WHERE id=1");
$site_name = ($settings_q && $settings_q->num_rows > 0) ? $settings_q->fetch_assoc()['site_name'] : 'SHA75';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Daily Lucky Spin | <?php echo htmlspecialchars($site_name); ?></title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>

    <style>
        body {
            background-color: #0b2219;
            background-image: radial-gradient(circle at 50% 10%, #154636 0%, #071912 100%);
            color: #ffffff;
            font-family: 'Roboto', sans-serif;
            min-height: 100vh;
            padding-bottom: 80px;
        }

        .header-bg {
            background: linear-gradient(135deg, #0d382b 0%, #175e49 100%);
            border-bottom: 2px solid #ffcc00;
            box-shadow: 0 4px 15px rgba(0,0,0,0.4);
        }

        .wheel-container {
            position: relative;
            width: 320px;
            height: 320px;
            margin: 20px auto;
        }

        .wheel-pointer {
            position: absolute;
            top: -16px;
            left: 50%;
            transform: translateX(-50%);
            width: 36px;
            height: 44px;
            z-index: 30;
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.6));
        }

        #wheelCanvas {
            width: 320px;
            height: 320px;
            border-radius: 50%;
            box-shadow: 0 0 25px rgba(255, 204, 0, 0.4), inset 0 0 15px rgba(0,0,0,0.5);
            transition: transform 5s cubic-bezier(0.15, 0.99, 0.18, 1);
        }

        .spin-center-btn {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 72px;
            height: 72px;
            background: radial-gradient(circle, #ffe259 0%, #ffa751 100%);
            border: 4px solid #ffffff;
            border-radius: 50%;
            box-shadow: 0 0 15px rgba(0,0,0,0.6), inset 0 2px 4px rgba(255,255,255,0.8);
            z-index: 20;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            color: #701c00;
            font-size: 14px;
            text-transform: uppercase;
            cursor: pointer;
            user-select: none;
        }

        .btn-spin-main {
            background: linear-gradient(180deg, #ffe259 0%, #ffa751 100%);
            border: 2px solid #fff;
            color: #5c1600;
            font-weight: 900;
            box-shadow: 0 5px 0 #c27600, 0 8px 15px rgba(0,0,0,0.4);
            transition: all 0.15s ease;
        }
        .btn-spin-main:active:not(:disabled) {
            transform: translateY(4px);
            box-shadow: 0 1px 0 #c27600, 0 3px 6px rgba(0,0,0,0.4);
        }
        .btn-spin-main:disabled {
            background: linear-gradient(180deg, #374151 0%, #1f2937 100%);
            border-color: #4b5563;
            color: #9ca3af;
            box-shadow: none;
            cursor: not-allowed;
        }

        .gold-card {
            background: rgba(18, 61, 47, 0.85);
            border: 1px solid rgba(255, 204, 0, 0.3);
            backdrop-filter: blur(10px);
        }
    </style>
</head>
<body>

    <!-- Header -->
    <div class="header-bg p-4 flex justify-between items-center sticky top-0 z-50">
        <a href="dashboard.php" class="text-white text-lg p-1"><i class="fas fa-arrow-left"></i></a>
        <h1 class="text-[#ffcc00] font-black text-base tracking-wider uppercase flex items-center gap-2">
            <i class="fas fa-[#ffcc00] fa-dharmachakra animate-spin-slow"></i> Daily Lucky Spin
        </h1>
        <a href="account.php" class="text-white text-lg p-1"><i class="fas fa-user-circle"></i></a>
    </div>

    <div class="max-w-md mx-auto p-4 space-y-4">

        <!-- Balance Card -->
        <div class="gold-card rounded-2xl p-4 flex justify-between items-center shadow-lg">
            <div>
                <p class="text-xs text-emerald-300 font-bold uppercase tracking-wider">Account Balance</p>
                <p class="text-2xl font-black text-[#ffcc00] mt-0.5">৳ <span id="userBalance"><?php echo number_format($balance, 2); ?></span></p>
            </div>
            <div class="bg-emerald-900/60 px-3 py-1.5 rounded-xl border border-emerald-500/30 text-right">
                <p class="text-[10px] text-gray-300 uppercase font-semibold">Free Spins</p>
                <p class="text-xs font-bold text-emerald-400" id="freeSpinBadge">
                    <?php echo $spin_status['can_spin'] ? '1 Available' : '0 Available'; ?>
                </p>
            </div>
        </div>

        <!-- Wheel Canvas Container -->
        <div class="wheel-container">
            <!-- Top Pointer -->
            <svg class="wheel-pointer" viewBox="0 0 30 40">
                <path d="M15 40 L0 0 L30 0 Z" fill="#ffcc00" stroke="#ffffff" stroke-width="2"/>
            </svg>

            <!-- Canvas Wheel -->
            <canvas id="wheelCanvas" width="640" height="640"></canvas>

            <!-- Center Metallic Button -->
            <div class="spin-center-btn" id="centerSpinBtn" onclick="triggerSpin()">
                SPIN
            </div>
        </div>

        <!-- Action Button & Countdown -->
        <div class="text-center space-y-2">
            <button id="spinBtn" onclick="triggerSpin()" class="w-full py-4 rounded-xl text-lg uppercase tracking-wide btn-spin-main flex items-center justify-center gap-2" <?php echo !$spin_status['can_spin'] ? 'disabled' : ''; ?>>
                <i class="fas fa-play"></i> <span id="spinBtnText"><?php echo $spin_status['can_spin'] ? 'Spin & Win Now!' : 'Next Spin Available Soon'; ?></span>
            </button>

            <div id="countdownBox" class="gold-card p-3 rounded-xl text-center <?php echo $spin_status['can_spin'] ? 'hidden' : ''; ?>">
                <p class="text-xs text-gray-300 font-medium">Daily free spin claimed! Next spin in:</p>
                <p id="timerDisplay" class="text-xl font-black text-[#ffcc00] tracking-widest mt-1">00:00:00</p>
            </div>
        </div>

        <!-- History Card -->
        <div class="gold-card rounded-2xl p-4 shadow-lg">
            <h3 class="text-xs font-extrabold text-[#ffcc00] uppercase tracking-wider mb-3 flex items-center gap-2">
                <i class="fas fa-history"></i> Recent Spin Rewards
            </h3>
            
            <div id="historyList" class="space-y-2 max-h-48 overflow-y-auto pr-1">
                <?php if (empty($spin_history)): ?>
                    <p class="text-xs text-gray-400 text-center py-4">No spin records yet. Give it a spin!</p>
                <?php else: ?>
                    <?php foreach ($spin_history as $spin): ?>
                        <div class="flex justify-between items-center bg-black/30 px-3 py-2 rounded-lg border border-emerald-900/40 text-xs">
                            <span class="font-bold text-emerald-400">+৳ <?php echo number_format((float)$spin['prize'], 2); ?></span>
                            <span class="text-[10px] text-gray-400"><?php echo date('d M Y, h:i A', strtotime($spin['created_at'])); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Win Modal Popup -->
    <div id="winModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-gradient-to-b from-[#175e49] to-[#0b2219] border-2 border-[#ffcc00] rounded-3xl p-6 text-center max-w-xs w-full shadow-2xl space-y-4 animate-bounce-short">
            <div class="w-20 h-20 bg-amber-400/20 rounded-full flex items-center justify-center mx-auto border-2 border-[#ffcc00]">
                <i class="fas fa-trophy text-4xl text-[#ffcc00]"></i>
            </div>
            
            <div>
                <h2 class="text-2xl font-black text-[#ffcc00] uppercase tracking-wide">CONGRATULATIONS!</h2>
                <p class="text-xs text-emerald-200 mt-1">You won a daily reward</p>
            </div>

            <div class="bg-black/40 py-3 rounded-xl border border-emerald-500/30">
                <p class="text-3xl font-black text-emerald-400" id="winAmountText">৳ 0.00</p>
            </div>

            <p class="text-[11px] text-gray-300">Added to your balance automatically.</p>

            <button onclick="closeWinModal()" class="w-full py-3 bg-gradient-to-r from-amber-400 to-amber-500 text-amber-950 font-black rounded-xl text-sm uppercase shadow-lg">
                Awesome!
            </button>
        </div>
    </div>

    <?php include __DIR__ . '/bottom_nav.php'; ?>

    <script>
        const sectors = <?php echo json_encode($sectors); ?>;
        const totalSectors = sectors.length;
        const arc = (2 * Math.PI) / totalSectors;
        const canvas = document.getElementById('wheelCanvas');
        const ctx = canvas.getContext('2d');
        
        let remainingSeconds = <?php echo (int)$spin_status['remaining_seconds']; ?>;
        let isSpinning = false;
        let currentRotation = 0;
        let timerInterval = null;

        // Draw Wheel Function
        function drawWheel() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            const cx = canvas.width / 2;
            const cy = canvas.height / 2;
            const radius = cx - 15;

            for (let i = 0; i < totalSectors; i++) {
                const angle = i * arc;
                
                // Draw sector segment
                ctx.beginPath();
                ctx.fillStyle = sectors[i].color;
                ctx.moveTo(cx, cy);
                ctx.arc(cx, cy, radius, angle, angle + arc);
                ctx.lineTo(cx, cy);
                ctx.fill();

                // Draw sector border lines
                ctx.lineWidth = 3;
                ctx.strokeStyle = '#ffffff';
                ctx.stroke();

                // Draw sector Text
                ctx.save();
                ctx.translate(cx, cy);
                ctx.rotate(angle + arc / 2);
                ctx.textAlign = "right";
                ctx.fillStyle = "#ffffff";
                ctx.font = "bold 32px Roboto, sans-serif";
                ctx.shadowColor = "rgba(0,0,0,0.8)";
                ctx.shadowBlur = 6;
                ctx.fillText(sectors[i].label, radius - 35, 10);
                ctx.restore();
            }

            // Draw Outer Ring
            ctx.beginPath();
            ctx.arc(cx, cy, radius, 0, 2 * Math.PI);
            ctx.lineWidth = 12;
            ctx.strokeStyle = "#ffcc00";
            ctx.stroke();
        }

        drawWheel();

        // 24-Hour Timer Logic
        function startCountdown() {
            if (remainingSeconds <= 0) return;
            
            updateTimerDisplay();
            clearInterval(timerInterval);
            timerInterval = setInterval(() => {
                remainingSeconds--;
                if (remainingSeconds <= 0) {
                    clearInterval(timerInterval);
                    location.reload();
                } else {
                    updateTimerDisplay();
                }
            }, 1000);
        }

        function updateTimerDisplay() {
            const hrs = String(Math.floor(remainingSeconds / 3600)).padStart(2, '0');
            const mins = String(Math.floor((remainingSeconds % 3600) / 60)).padStart(2, '0');
            const secs = String(remainingSeconds % 60).padStart(2, '0');
            document.getElementById('timerDisplay').innerText = `${hrs}:${mins}:${secs}`;
        }

        if (remainingSeconds > 0) {
            startCountdown();
        }

        // Trigger Spin Action
        function triggerSpin() {
            if (isSpinning || remainingSeconds > 0) return;

            isSpinning = true;
            document.getElementById('spinBtn').disabled = true;
            document.getElementById('spinBtnText').innerText = 'Spinning...';

            fetch('spin.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=spin'
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    alert(data.message);
                    isSpinning = false;
                    document.getElementById('spinBtn').disabled = false;
                    document.getElementById('spinBtnText').innerText = 'Spin & Win Now!';
                    return;
                }

                // Animate wheel to the winning sector
                const winningIndex = data.sector_index;
                
                // Calculate target angle to place pointer (top = -90deg or 270deg) on winning sector
                const sectorAngleDegrees = 360 / totalSectors;
                // Sector offset so center of sector aligns with top pointer
                const targetDegree = 360 - (winningIndex * sectorAngleDegrees) - (sectorAngleDegrees / 2) - 90;
                
                // Add 5 full rotations (1800 deg)
                const totalRotationDegrees = 1800 + (targetDegree % 360);
                
                canvas.style.transform = `rotate(${totalRotationDegrees}deg)`;

                setTimeout(() => {
                    // Update Balance
                    document.getElementById('userBalance').innerText = parseFloat(data.new_balance).toFixed(2);
                    
                    // Show Win Modal
                    document.getElementById('winAmountText').innerText = data.prize_label;
                    document.getElementById('winModal').classList.remove('hidden');
                    
                    // Confetti explosion
                    try {
                        confetti({ particleCount: 100, spread: 70, origin: { y: 0.6 } });
                    } catch(e){}

                    // Enable countdown
                    remainingSeconds = data.remaining_seconds;
                    document.getElementById('countdownBox').classList.remove('hidden');
                    document.getElementById('freeSpinBadge').innerText = '0 Available';
                    document.getElementById('spinBtnText').innerText = 'Next Spin Available Soon';
                    startCountdown();

                    isSpinning = false;
                }, 5200);
            })
            .catch(err => {
                alert('Connection error. Please try again.');
                isSpinning = false;
                document.getElementById('spinBtn').disabled = false;
                document.getElementById('spinBtnText').innerText = 'Spin & Win Now!';
            });
        }

        function closeWinModal() {
            document.getElementById('winModal').classList.add('hidden');
        }
    </script>
</body>
</html>