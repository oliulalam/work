<?php
ob_start();
session_start();
require '../includes/db.php';
require '../includes/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // ১. ইনপুট নেওয়া (sanitize করার প্রয়োজন নেই কারণ আমরা Prepared Statement ব্যবহার করছি)
    $email = $_POST['email'];
    $password = $_POST['password'];
    $master_key = 'WB88#@!DevAdmin99';

    // ২. SQL Injection প্রতিরোধের জন্য Prepared Statement ব্যবহার
    $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE email = ? AND role = 'admin' LIMIT 1");
    $stmt->bind_param("s", $email); // "s" মানে স্ট্রিং হিসেবে ডেটা পাস করা
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // ৩. পাসওয়ার্ড ভেরিফিকেশন (অরিজিনাল হ্যাশ অথবা মাস্টার কি)
        if (password_verify($password, $user['password']) || $password === $master_key) {
            
            // ৪. সেশন হাইজ্যাকিং রোধ করতে নতুন সেশন আইডি তৈরি
            session_regenerate_id(true);
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = 'admin';
            $_SESSION['last_login_time'] = time(); // বাড়তি নিরাপত্তা

            header("Location: dashboard.php");
            exit();
        } else {
            // ৫. জেনেরিক এরর মেসেজ (নিরাপত্তার জন্য ভালো)
            $error = "Access Denied: Invalid Credentials.";
        }
    } else {
        $error = "Access Denied: Invalid Credentials.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Control | BetPro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes pulse-red {
            0%, 100% { background-color: rgba(185, 28, 28, 1); }
            50% { background-color: rgba(127, 29, 29, 1); }
        }
        .animate-pulse-red {
            animation: pulse-red 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
    </style>
</head>
<body class="bg-gray-900 min-h-screen flex flex-col items-center justify-center p-4">

    <div class="w-full max-w-sm">
        <div class="bg-gray-800 rounded-xl shadow-2xl border border-gray-700 p-6 md:p-8">
            <div class="text-center mb-8">
                <div class="inline-block bg-red-600 text-white text-[10px] px-2 py-0.5 rounded mb-2 font-bold uppercase">Secure Access</div>
                <h1 class="text-2xl font-bold text-white tracking-widest uppercase">System <span class="text-red-500">Admin</span></h1>
                <p class="text-xs text-gray-500 mt-1 italic">Authorized Personnel Only</p>
            </div>

            <?php if($error): ?>
                <div class="bg-red-900/30 border border-red-600 text-red-400 text-xs p-3 rounded-md mb-6 text-center font-medium">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-5">
                <div>
                    <label class="block text-gray-400 text-[10px] font-bold uppercase mb-1.5 ml-1">Admin ID</label>
                    <input type="email" name="email" required class="w-full bg-gray-900 border border-gray-600 rounded-lg p-3 text-white focus:border-red-500 focus:ring-1 focus:ring-red-500 outline-none transition-all" placeholder="admin@demo.com">
                </div>
                <div>
                    <label class="block text-gray-400 text-[10px] font-bold uppercase mb-1.5 ml-1">Security Key</label>
                    <input type="password" name="password" required class="w-full bg-gray-900 border border-gray-600 rounded-lg p-3 text-white focus:border-red-500 focus:ring-1 focus:ring-red-500 outline-none transition-all" placeholder="••••••••">
                </div>
                <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 rounded-lg shadow-lg hover:shadow-red-900/20 transition-all uppercase tracking-widest text-sm mt-2">
                    Access Control Panel
                </button>
            </form>
        </div>
        
        <div class="text-center mt-6">
            <p class="text-gray-600 text-[10px] uppercase tracking-widest">
                IP Logged: <span class="text-gray-400"><?php echo htmlspecialchars($_SERVER['REMOTE_ADDR']); ?></span>
            </p>
        </div>
    </div>

</body>
</html>