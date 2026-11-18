<?php
session_start();
$db_path = file_exists('includes/db.php') ? 'includes/db.php' : '../includes/db.php';
if (file_exists($db_path)) {
    require $db_path;
} else {
    // Fallback Connection
    $conn = new mysqli('localhost', 'root', '', 'bajipari');
}

// Check Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user = $conn->query("SELECT * FROM users WHERE id=$user_id")->fetch_assoc();

// Mock Data for Check-in Stats
$checkin_days = 0;
$total_reward = 0;
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>সাইন ইন - Patner77</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Hind Siliguri', sans-serif; background-color: #f3f4f6; }
        
        /* Navbar Dark Background */
        .nav-header { background-color: #1a0b2e; color: white; }
        
        /* Hero Gradient Background */
        .hero-bg {
            background: linear-gradient(180deg, #F59E0B 0%, #F97316 100%);
            padding-bottom: 20px;
            position: relative;
            overflow: hidden;
        }
        /* Abstract Circles for Hero */
        .circle-deco {
            position: absolute; border-radius: 50%; background: rgba(255,255,255,0.2);
        }
        
        /* Stats Card */
        .stats-card {
            background: white; border-radius: 15px; margin: -20px 15px 15px 15px; padding: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05); position: relative; z-index: 10;
            display: flex; text-align: center;
        }
        
        /* Day Card Styles */
        .day-card {
            background: #FFFBEB; border: 1px solid #FEF3C7; border-radius: 10px; padding: 10px;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .day-title {
            background: #2563EB; color: white; font-size: 10px; width: 100%; border-top-left-radius: 8px; border-top-right-radius: 8px; margin-top: -10px; margin-bottom: 8px; padding: 2px 0;
        }
        
        /* Wheel Simulation (CSS Circles) */
        .wheel-circle {
            width: 45px; height: 45px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 5px; border: 3px solid rgba(255,255,255,0.5);
            box-shadow: inset 0 0 10px rgba(0,0,0,0.2);
        }
        .bronze { background: radial-gradient(circle, #CD7F32, #8B4513); }
        .silver { background: radial-gradient(circle, #E5E7EB, #9CA3AF); }
        .gold { background: radial-gradient(circle, #FCD34D, #D97706); }
        .platinum { background: radial-gradient(circle, #A78BFA, #7C3AED); }
        .diamond { background: radial-gradient(circle, #67E8F9, #06B6D4); }

        /* Sign In Button */
        .btn-signin {
            background: #EF4444; color: white; font-size: 10px; padding: 4px 12px; border-radius: 20px; width: 100%; font-weight: bold; margin-top: 5px;
        }
        .btn-signin-disabled {
            background: #D1D5DB; color: white; font-size: 10px; padding: 4px 12px; border-radius: 20px; width: 100%; font-weight: bold; margin-top: 5px;
        }

        /* Rules List */
        .rules-list { font-size: 12px; color: #374151; line-height: 1.6; padding: 15px; }
        
        /* Modal */
        .modal-overlay {
            background-color: rgba(0,0,0,0.6); backdrop-filter: blur(2px);
        }
    </style>
</head>
<body>

    <header class="nav-header flex items-center justify-center p-4 sticky top-0 z-50">
        <button onclick="window.history.back()" class="absolute left-4 text-white text-xl"><i class="fas fa-chevron-left"></i></button>
        <h1 class="text-base font-bold">সাইন ইন</h1>
    </header>

    <div class="hero-bg px-4 pt-4 pb-10">
        <div class="circle-deco w-20 h-20 top-2 right-10"></div>
        <div class="circle-deco w-10 h-10 top-10 left-5"></div>

        <div class="flex items-center gap-3 relative z-10">
            <div class="w-14 h-14 rounded-full border-2 border-white overflow-hidden">
                <img src="../assets/images/avatar.png" onerror="this.src='https://ui-avatars.com/api/?name=<?php echo $user['username']; ?>&background=random'" class="w-full h-full object-cover">
            </div>
            <div>
                <p class="text-yellow-100 text-xs font-bold"><?php echo $user['username']; ?></p>
                <h2 class="text-white text-2xl font-bold">৳ <?php echo number_format($user['balance'], 2); ?></h2>
            </div>
        </div>
    </div>

    <div class="stats-card">
        <div class="w-1/2 border-r border-gray-200">
            <p class="text-blue-600 font-bold text-lg"><?php echo $checkin_days; ?></p>
            <p class="text-xs text-gray-400">চেক-ইন দিন</p>
        </div>
        <div class="w-1/2">
            <p class="text-red-500 font-bold text-lg"><?php echo $total_reward; ?></p>
            <p class="text-xs text-gray-400">সাইন ইন মোট পুরস্কার</p>
        </div>
    </div>

    <div class="px-4 mb-4">
        <p class="text-center text-blue-500 text-xs font-bold mb-2">প্রতিদিন ডিপোজিট ড্র তে আইফোন ১৬ প্রো জিতুন</p>
        <div class="bg-white p-3 rounded-xl border border-red-100 flex items-center gap-3 shadow-sm">
            <i class="fas fa-gift text-3xl text-red-500"></i>
            <div class="flex-1">
                <p class="text-red-500 font-bold text-xs">প্রতিদিন ডিপোজিট ড্র তে আইফোন ১৬ প্রো জিতুন</p>
                <p class="text-[10px] text-red-800 font-bold mt-0.5">আজও চেক ইন করা হয়নি <i class="fas fa-question-circle"></i></p>
                
                <div class="flex justify-between mt-2 text-[10px] bg-red-50 p-1 rounded">
                    <div>
                        <span class="text-gray-500">ডিপোজিট শর্ত:</span><br>
                        <span class="text-red-500 font-bold">৳ ১,৫০০.০০</span>
                    </div>
                    <div class="text-right">
                        <span class="text-gray-500">বেটিং শর্ত:</span><br>
                        <span class="text-red-500 font-bold">৳ ১৫,০০০.০০</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="px-4">
        <div class="grid grid-cols-3 gap-2 mb-2">
            <div class="day-card">
                <div class="day-title">দিন ১</div>
                <div class="wheel-circle bronze"><div class="w-2 h-2 bg-yellow-200 rounded-full"></div></div>
                <p class="text-[10px] font-bold text-gray-600 mb-1">তামার চাকা</p>
                <button onclick="openModal()" class="btn-signin">সাইন ইন</button>
            </div>
            <div class="day-card">
                <div class="day-title">দিন ২</div>
                <div class="wheel-circle silver"><div class="w-2 h-2 bg-white rounded-full"></div></div>
                <p class="text-[10px] font-bold text-gray-600 mb-1">রূপার চাকা</p>
                <button class="btn-signin-disabled">সাইন ইন</button>
            </div>
            <div class="day-card">
                <div class="day-title">দিন ৩</div>
                <div class="wheel-circle gold"><div class="w-2 h-2 bg-yellow-100 rounded-full"></div></div>
                <p class="text-[10px] font-bold text-gray-600 mb-1">স্বর্ণের চাকা</p>
                <button class="btn-signin-disabled">সাইন ইন</button>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-2">
            <div class="day-card">
                <div class="day-title">দিন ৪</div>
                <div class="wheel-circle platinum"><div class="w-2 h-2 bg-purple-100 rounded-full"></div></div>
                <p class="text-[10px] font-bold text-gray-600 mb-1">প্লাটিনামের চাকা</p>
                <button class="btn-signin-disabled">সাইন ইন</button>
            </div>
            <div class="day-card">
                <div class="day-title">দিন ৫</div>
                <div class="wheel-circle diamond"><div class="w-2 h-2 bg-cyan-100 rounded-full"></div></div>
                <p class="text-[10px] font-bold text-gray-600 mb-1">হীরার চাকা</p>
                <button class="btn-signin-disabled">সাইন ইন</button>
            </div>
        </div>
    </div>

    <div class="rules-list">
        <h3 class="text-red-500 font-bold mb-2">সাইন-ইন নিয়ম</h3>
        <p class="font-bold mb-2">প্রতিদিন ডিপোজিট ড্র তে আইফোন ১৬ প্রো জিতুন</p>
        <ol class="list-decimal pl-4 space-y-2">
            <li>দৈনিক লগ-ইনের পুরস্কার পেতে আপনাকে প্রতিদিন ১,৫০০ টাকা জমা দিতে হবে এবং ১৫,০০০ টাকার প্রয়োজনীয় টার্নওভার শেষ করতে হবে।</li>
            <li>যত বেশি একটানা লগ-ইন করবেন, তত বেশি পুরস্কার পাবেন। আপনি যদি একটি দিনের জন্য এড়িয়ে যান, তাহলে পুরস্কারটি আবারও ১ দিনে রিসেট হবে।</li>
            <li>টানা ৫ দিন লগ-ইন শেষ করার পরে, চেক ইন পুরস্কার রিসেট করা হবে এবং সেটি আবারও ১ দিন থেকে শুরু হবে।</li>
        </ol>
    </div>

    <div id="confirmModal" class="fixed inset-0 modal-overlay z-[60] hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-lg w-full max-w-xs p-6 text-center shadow-2xl animate-fade-in-up">
            <h3 class="text-lg font-bold text-gray-800 mb-4">নিশ্চিতকরণ</h3>
            <p class="text-sm text-gray-600 mb-2">আপনি আজকের আপনার রিচার্জ টাস্কটি পূর্ণ করুন (#708-iv2xs)</p>
            
            <div class="text-sm text-gray-700 font-medium space-y-1 mb-6">
                <p>বর্তমান জমা পরিমাণ : 0</p>
                <p>প্রয়োজনীয় জমা পরিমাণ : 1,500</p>
            </div>

            <div class="flex justify-between gap-4">
                <button onclick="closeModal()" class="flex-1 py-2 text-gray-500 font-bold hover:text-gray-700">বাতিল করুন</button>
                <button onclick="goToDeposit()" class="flex-1 py-2 text-blue-500 font-bold hover:text-blue-700">যাও</button>
            </div>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('confirmModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('confirmModal').classList.add('hidden');
        }

        function goToDeposit() {
            window.location.href = 'deposit.php';
        }
    </script>

</body>
</html>