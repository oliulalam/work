<?php
session_start();
// Adjust path to your db file
$db_path = file_exists('../includes/db.php') ? '../includes/db.php' : 'includes/db.php';
if (file_exists($db_path)) {
    require $db_path;
} else {
    // Fallback or die
    $conn = new mysqli("localhost", "root", "", "betpro_demo");
}

// --- ADMIN CHECK ---
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // header("Location: ../login.php");
    // exit();
}

$message = "";
$msg_type = "";

// --- HANDLE ACTIONS ---

// 1. Add Global Bonus Rule
if (isset($_POST['add_global'])) {
    $title = $conn->real_escape_string($_POST['title']);
    $cat = $_POST['category'];
    $cond_type = $_POST['condition_type'];
    $cond_amt = floatval($_POST['condition_amount']);
    $hours = intval($_POST['time_frame_hours']);
    $reward = floatval($_POST['reward_amount']);

    $sql = "INSERT INTO global_bonuses (title, category, condition_type, condition_amount, time_frame_hours, reward_amount, is_active) 
            VALUES ('$title', '$cat', '$cond_type', $cond_amt, $hours, $reward, 1)";
    
    if ($conn->query($sql)) {
        $message = "Global Bonus Rule Added!";
        $msg_type = "success";
    } else {
        $message = "Error: " . $conn->error;
        $msg_type = "error";
    }
}

// 2. Assign Personal Bonus
if (isset($_POST['assign_personal'])) {
    $uid = intval($_POST['user_id']); // Can be input manually or selected
    $cat = $_POST['category'];
    $amount = floatval($_POST['amount']);
    $desc = $conn->real_escape_string($_POST['description']);

    // Verify User Exists
    $check = $conn->query("SELECT id FROM users WHERE id=$uid OR username='$uid'");
    if ($check->num_rows > 0) {
        // If input was username, get ID (optional logic, assuming ID for now)
        $sql = "INSERT INTO personal_bonuses (user_id, category, amount, description, status) 
                VALUES ($uid, '$cat', $amount, '$desc', 'pending')";
        
        if ($conn->query($sql)) {
            $message = "Bonus Sent to User ID: $uid";
            $msg_type = "success";
        }
    } else {
        $message = "User ID not found!";
        $msg_type = "error";
    }
}

// 3. Delete Actions
if (isset($_GET['delete_global'])) {
    $id = intval($_GET['delete_global']);
    $conn->query("DELETE FROM global_bonuses WHERE id=$id");
    header("Location: bonus_manager.php");
    exit();
}
if (isset($_GET['delete_personal'])) {
    $id = intval($_GET['delete_personal']);
    $conn->query("DELETE FROM personal_bonuses WHERE id=$id");
    header("Location: bonus_manager.php");
    exit();
}

// 4. Toggle Active Status
if (isset($_GET['toggle_global'])) {
    $id = intval($_GET['toggle_global']);
    $conn->query("UPDATE global_bonuses SET is_active = NOT is_active WHERE id=$id");
    header("Location: bonus_manager.php");
    exit();
}

// 4. Update Registration & Referral Bonus Settings
if (isset($_POST['update_registration_bonuses'])) {
    $w_amt = floatval($_POST['welcome_bonus_amount'] ?? 50.00);
    $r_amt = floatval($_POST['referral_bonus_amount'] ?? 50.00);

    @$conn->query("ALTER TABLE settings ADD COLUMN welcome_bonus_amount DECIMAL(10,2) DEFAULT 50.00");
    @$conn->query("ALTER TABLE settings ADD COLUMN referral_bonus_amount DECIMAL(10,2) DEFAULT 50.00");

    $stmt = $conn->prepare("UPDATE settings SET welcome_bonus_amount=?, referral_bonus_amount=? WHERE id=1");
    if ($stmt) {
        $stmt->bind_param("dd", $w_amt, $r_amt);
        $stmt->execute();
        $stmt->close();
        $message = "Registration & Referral Bonus settings saved successfully!";
        $msg_type = "success";
    }
}

// --- FETCH DATA ---
$globals = $conn->query("SELECT * FROM global_bonuses ORDER BY id DESC");
$personals = $conn->query("SELECT pb.*, u.username FROM personal_bonuses pb LEFT JOIN users u ON pb.user_id = u.id ORDER BY pb.id DESC LIMIT 50");
$settings_q = $conn->query("SELECT welcome_bonus_amount, referral_bonus_amount FROM settings WHERE id = 1 LIMIT 1");
$b_settings = ($settings_q && $settings_q->num_rows > 0) ? $settings_q->fetch_assoc() : ['welcome_bonus_amount' => 50.00, 'referral_bonus_amount' => 50.00];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bonus Manager - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f3f4f6; }
        /* Mobile Overlay */
        #sidebarOverlay { background-color: rgba(0,0,0,0.5); }
    </style>
</head>
<body class="bg-gray-50 font-sans text-slate-800 flex flex-col md:flex-row min-h-screen">

    <div class="md:hidden bg-gray-900 text-white p-4 flex justify-between items-center sticky top-0 z-50 shadow-md">
        <h1 class="font-bold text-lg text-yellow-500">ADMIN PANEL</h1>
        <button onclick="toggleSidebar()" class="text-white text-2xl focus:outline-none"><i class="fas fa-bars"></i></button>
    </div>

    <div id="sidebar" class="fixed inset-y-0 left-0 transform -translate-x-full md:relative md:translate-x-0 transition duration-200 ease-in-out z-50 md:block w-64 bg-gray-800 text-white h-full overflow-y-auto">
        <?php include '../includes/sidebar_admin.php'; ?>
    </div>

    <div id="sidebarOverlay" onclick="toggleSidebar()" class="fixed inset-0 z-40 hidden md:hidden"></div>

    <main class="flex-1 p-4 md:p-8 w-full md:w-auto overflow-y-auto">

        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-gift text-red-500"></i> Bonus System
                </h1>
                <p class="text-gray-500 text-sm mt-1">Manage global rewards and individual player bonuses.</p>
            </div>
            
            <a href="dashboard.php" class="text-gray-500 hover:text-gray-700 text-sm font-medium flex items-center gap-1">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <?php if($message): ?>
            <div class="<?php echo $msg_type=='success' ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700'; ?> border rounded-lg p-4 mb-6 shadow-sm flex items-center gap-3">
                <i class="fas <?php echo $msg_type=='success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> text-lg"></i>
                <div>
                    <p class="font-bold"><?php echo $msg_type=='success' ? 'Success' : 'Error'; ?></p>
                    <p class="text-sm"><?php echo $message; ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Registration & Referral Bonus Configuration Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 border-t-4 border-t-emerald-500 mb-6">
            <h2 class="text-lg font-bold text-gray-800 mb-4 border-b border-gray-100 pb-2 flex items-center justify-between">
                <span><i class="fas fa-user-plus text-emerald-600 mr-2"></i> Registration & Referral Bonus Amounts</span>
                <span class="text-xs bg-emerald-100 text-emerald-700 px-2.5 py-1 rounded-full font-bold">Live System Settings</span>
            </h2>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="update_registration_bonuses" value="1">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase mb-1">New User Signup Bonus (৳)</label>
                        <input type="number" step="0.01" min="0" name="welcome_bonus_amount" value="<?php echo htmlspecialchars($b_settings['welcome_bonus_amount'] ?? 50.00); ?>" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm font-bold text-emerald-700 focus:ring-2 focus:ring-emerald-500 outline-none" required>
                        <p class="text-[11px] text-gray-400 mt-1">Amount credited to new user upon registration.</p>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Per Referral Register Bonus (৳)</label>
                        <input type="number" step="0.01" min="0" name="referral_bonus_amount" value="<?php echo htmlspecialchars($b_settings['referral_bonus_amount'] ?? 50.00); ?>" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm font-bold text-blue-700 focus:ring-2 focus:ring-blue-500 outline-none" required>
                        <p class="text-[11px] text-gray-400 mt-1">Amount credited to referrer when a referee signs up.</p>
                    </div>
                </div>
                <div class="pt-2">
                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-5 py-2.5 rounded-lg text-xs shadow-md transition flex items-center gap-2">
                        <i class="fas fa-save"></i> Save Bonus Settings
                    </button>
                </div>
            </form>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <div class="space-y-6">
                
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 border-t-4 border-t-blue-500">
                    <h2 class="text-lg font-bold text-gray-800 mb-4 border-b border-gray-100 pb-2">Set Global Rules (Auto)</h2>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="add_global" value="1">
                        
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Bonus Title</label>
                            <input type="text" name="title" required placeholder="e.g. Daily Deposit Reward" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Display Area</label>
                                <select name="category" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm bg-white focus:ring-2 focus:ring-blue-500 outline-none">
                                    <option value="promo">Promo Code (Blue)</option>
                                    <option value="temu">Temu Ticket (Orange)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Condition Type</label>
                                <select name="condition_type" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm bg-white focus:ring-2 focus:ring-blue-500 outline-none">
                                    <option value="deposit">Deposit Amount</option>
                                    <option value="loss">Loss Amount</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Target</label>
                                <input type="number" step="0.01" name="condition_amount" required placeholder="500" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Time (Hrs)</label>
                                <input type="number" name="time_frame_hours" required placeholder="24" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-green-600 uppercase mb-1">Reward</label>
                                <input type="number" step="0.01" name="reward_amount" required placeholder="10" class="w-full border border-green-200 bg-green-50 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-green-500 outline-none">
                            </div>
                        </div>

                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 rounded-lg shadow transition transform active:scale-95">Create Rule</button>
                    </form>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 overflow-hidden">
                    <h3 class="text-md font-bold text-gray-700 mb-4">Active Rules</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                                <tr>
                                    <th class="p-3 rounded-l-lg">Title</th>
                                    <th class="p-3">Cond.</th>
                                    <th class="p-3">Give</th>
                                    <th class="p-3 text-center rounded-r-lg">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php while($row = $globals->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50 transition <?php echo $row['is_active'] ? '' : 'opacity-50 grayscale'; ?>">
                                    <td class="p-3">
                                        <p class="font-bold text-gray-800"><?php echo $row['title']; ?></p>
                                        <span class="text-[10px] uppercase px-2 py-0.5 rounded font-bold <?php echo $row['category']=='promo' ? 'bg-blue-100 text-blue-600' : 'bg-orange-100 text-orange-600'; ?>">
                                            <?php echo $row['category']; ?>
                                        </span>
                                    </td>
                                    <td class="p-3 text-gray-600">
                                        <?php echo ucfirst($row['condition_type']); ?> > <?php echo $row['condition_amount']; ?><br>
                                        <span class="text-xs text-gray-400">in <?php echo $row['time_frame_hours']; ?>h</span>
                                    </td>
                                    <td class="p-3 font-bold text-green-600">+<?php echo $row['reward_amount']; ?></td>
                                    <td class="p-3 text-center flex justify-center gap-3">
                                        <a href="?toggle_global=<?php echo $row['id']; ?>" class="text-xl transition hover:scale-110" title="Toggle Active">
                                            <i class="fas <?php echo $row['is_active'] ? 'fa-toggle-on text-green-500' : 'fa-toggle-off text-gray-400'; ?>"></i>
                                        </a>
                                        <a href="?delete_global=<?php echo $row['id']; ?>" onclick="return confirm('Delete this rule?')" class="text-red-400 hover:text-red-600 text-xl transition hover:scale-110" title="Delete">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <div class="space-y-6">
                
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 border-t-4 border-t-yellow-500">
                    <h2 class="text-lg font-bold text-gray-800 mb-4 border-b border-gray-100 pb-2">Send Personal Bonus (Manual)</h2>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="assign_personal" value="1">
                        
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">User ID</label>
                            <input type="number" name="user_id" required placeholder="Enter Player ID (e.g., 3)" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-yellow-500 outline-none">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Type</label>
                                <select name="category" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm bg-white focus:ring-2 focus:ring-yellow-500 outline-none">
                                    <option value="claim">General Claim (Green)</option>
                                    <option value="recovery">Recovery Fund (Yellow)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Amount (Tk)</label>
                                <input type="number" step="0.01" name="amount" required placeholder="100" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-yellow-500 outline-none">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Description / Message</label>
                            <input type="text" name="description" required placeholder="e.g. VIP Weekly Reward" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-yellow-500 outline-none">
                        </div>

                        <button type="submit" class="w-full bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2.5 rounded-lg shadow transition transform active:scale-95">Send Money to Player</button>
                    </form>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 overflow-hidden">
                    <h3 class="text-md font-bold text-gray-700 mb-4">Recent Distributions</h3>
                    <div class="overflow-x-auto h-96 overflow-y-auto pr-1 custom-scrollbar">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-gray-50 text-gray-500 uppercase text-xs sticky top-0 z-10">
                                <tr>
                                    <th class="p-3 rounded-l-lg">User</th>
                                    <th class="p-3">Type</th>
                                    <th class="p-3">Amount</th>
                                    <th class="p-3">Status</th>
                                    <th class="p-3 text-center rounded-r-lg">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php while($row = $personals->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="p-3">
                                        <div class="font-bold text-gray-800"><?php echo $row['username'] ?? 'ID: '.$row['user_id']; ?></div>
                                        <div class="text-[10px] text-gray-400"><?php echo date('d M, h:i A', strtotime($row['created_at'])); ?></div>
                                    </td>
                                    <td class="p-3">
                                        <span class="text-[10px] uppercase px-2 py-0.5 rounded font-bold <?php echo $row['category']=='claim' ? 'bg-green-100 text-green-600' : 'bg-yellow-100 text-yellow-600'; ?>">
                                            <?php echo $row['category']; ?>
                                        </span>
                                        <div class="text-[10px] text-gray-500 truncate w-24 mt-1" title="<?php echo $row['description']; ?>">
                                            <?php echo $row['description']; ?>
                                        </div>
                                    </td>
                                    <td class="p-3 font-bold text-gray-700">৳<?php echo $row['amount']; ?></td>
                                    <td class="p-3">
                                        <?php if($row['status'] == 'claimed'): ?>
                                            <span class="text-green-500 font-bold text-xs flex items-center gap-1"><i class="fas fa-check-circle"></i> Taken</span>
                                        <?php else: ?>
                                            <span class="text-orange-400 font-bold text-xs flex items-center gap-1"><i class="fas fa-clock"></i> Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-3 text-center">
                                        <?php if($row['status'] == 'pending'): ?>
                                        <a href="?delete_personal=<?php echo $row['id']; ?>" onclick="return confirm('Revoke this bonus?')" class="text-red-400 hover:text-red-600 transition" title="Revoke">
                                            <i class="fas fa-times-circle text-lg"></i>
                                        </a>
                                        <?php else: ?>
                                            <span class="text-gray-300">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

        </div>

    </main>

    <script>
        // Toggle Mobile Sidebar
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            if (sidebar.classList.contains('-translate-x-full')) {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
            } else {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
            }
        }
    </script>

</body>
</html>