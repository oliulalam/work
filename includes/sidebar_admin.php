<?php
// Get Current Role
$current_role = $_SESSION['role'] ?? '';
$current_page = basename($_SERVER['PHP_SELF']);

// Dynamic Admin Branding from settings table
$admin_brand_name = 'Admin Panel';
$admin_logo_path = '';
if (!isset($conn) && file_exists(__DIR__ . '/db.php')) {
    require_once __DIR__ . '/db.php';
}
if (isset($conn) && !$conn->connect_error) {
    $brand_q = @$conn->query("SELECT * FROM settings WHERE id=1");
    if ($brand_q && $brand_q->num_rows > 0) {
        $brand_settings = $brand_q->fetch_assoc();
        $admin_brand_name = !empty($brand_settings['admin_panel_name']) ? $brand_settings['admin_panel_name'] : (!empty($brand_settings['site_name']) ? $brand_settings['site_name'] : $admin_brand_name);
        $admin_logo_path = $brand_settings['app_logo'] ?? '';
    }
}
$admin_brand_safe = htmlspecialchars($admin_brand_name, ENT_QUOTES, 'UTF-8');
$admin_logo_safe = htmlspecialchars($admin_logo_path, ENT_QUOTES, 'UTF-8');
?>

<div class="md:hidden fixed top-0 left-0 w-full h-16 bg-[#126E51] border-b border-[#264d3e] flex items-center justify-between px-4 z-40 shadow-md">
    <div class="flex items-center">
        <button id="mobile-menu-open" class="text-gray-300 hover:text-white focus:outline-none p-2">
            <i class="fas fa-bars text-2xl"></i>
        </button>
        <span class="ml-3 text-lg font-bold tracking-wider font-sans flex items-center gap-2 min-w-0">
            <?php if(!empty($admin_logo_safe)): ?>
                <img src="../<?php echo $admin_logo_safe; ?>?v=<?php echo time(); ?>" class="h-8 w-8 rounded object-contain bg-white/10 p-1" alt="Logo">
            <?php endif; ?>
            <span class="truncate"><span class="text-white"><?php echo $admin_brand_safe; ?></span><span class="text-red-500 text-xs">.<?php echo strtoupper($current_role); ?></span></span>
        </span>
    </div>
</div>

<div id="sidebar-overlay" onclick="closeSidebar()" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden md:hidden backdrop-blur-sm transition-opacity"></div>

<aside id="sidebar" class="w-64 bg-[#126E51] text-[#ffffff] flex flex-col h-screen fixed left-0 top-0 border-r border-[#264d3e] font-sans z-50 transition-transform duration-300 ease-in-out transform -translate-x-full md:translate-x-0">
    
    <div class="h-16 flex items-center justify-between px-6 border-b border-[#264d3e] bg-[#126E51] shadow-md shrink-0">
        <h1 class="text-xl font-bold tracking-wider font-sans flex items-center gap-2 min-w-0">
            <?php if(!empty($admin_logo_safe)): ?>
                <img src="../<?php echo $admin_logo_safe; ?>?v=<?php echo time(); ?>" class="h-9 w-9 rounded object-contain bg-white/10 p-1 shrink-0" alt="Logo">
            <?php endif; ?>
            <span class="truncate"><span class="text-white"><?php echo $admin_brand_safe; ?></span><span class="text-red-600">.<?php echo strtoupper($current_role); ?></span></span>
        </h1>
        
        <button id="mobile-menu-close" onclick="closeSidebar()" class="md:hidden text-gray-400 hover:text-red-600 focus:outline-none transition-colors">
            <i class="fas fa-times text-2xl"></i>
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto py-4 scrollbar-hide">
        
        <?php if($current_role === 'admin'): ?>
        
        <p class="px-6 text-[10px] font-bold uppercase tracking-widest text-[#55826f] mb-2 mt-2">Main</p>
        
        <a href="dashboard.php" class="flex items-center px-6 py-3 text-[#dbece3] hover:bg-[#1f4234] hover:text-white transition <?php echo $current_page == 'dashboard.php' ? 'bg-[#1f4234] text-white border-l-4 border-[#ffdf1b]' : ''; ?>">
            <span class="mr-3 w-5 text-center">📊</span> <span class="text-sm font-medium">Dashboard</span>
        </a>
        <a href="users_all.php" class="flex items-center px-6 py-3 text-[#dbece3] hover:bg-[#1f4234] hover:text-white transition <?php echo $current_page == 'users_all.php' ? 'bg-[#1f4234] text-white border-l-4 border-[#ffdf1b]' : ''; ?>">
            <span class="mr-3 w-5 text-center">👥</span> <span class="text-sm font-medium">User Management</span>
        </a>
        <a href="agents.php" class="flex items-center px-6 py-3 text-[#dbece3] hover:bg-[#1f4234] hover:text-white transition <?php echo $current_page == 'agents.php' ? 'bg-[#1f4234] text-white border-l-4 border-[#ffdf1b]' : ''; ?>">
            <span class="mr-3 w-5 text-center">🕴️</span> <span class="text-sm font-medium">Agents & Comm.</span>
        </a>

        <p class="px-6 text-[10px] font-bold uppercase tracking-widest text-[#55826f] mt-6 mb-2">Operations</p>
        
        <a href="payment_setup.php" class="flex items-center px-6 py-3 text-[#dbece3] hover:bg-[#1f4234] hover:text-white transition <?php echo $current_page == 'payment_setup.php' ? 'bg-[#1f4234] text-white border-l-4 border-[#ffdf1b]' : ''; ?>">
            <span class="mr-3 w-5 text-center">💳</span> <span class="text-sm font-medium">MFS Accounts</span>
        </a>
        <a href="payment_gateway_settings.php" class="flex items-center px-6 py-3 text-[#dbece3] hover:bg-[#1f4234] hover:text-white transition <?php echo $current_page == 'payment_gateway_settings.php' ? 'bg-[#1f4234] text-white border-l-4 border-[#ffdf1b]' : ''; ?>">
            <span class="mr-3 w-5 text-center">🔐</span> <span class="text-sm font-medium">Payment Gateway</span>
        </a>
        <a href="deposit_transaction_settings.php" class="flex items-center px-6 py-3 text-[#dbece3] hover:bg-[#1f4234] hover:text-white transition <?php echo $current_page == 'deposit_transaction_settings.php' ? 'bg-[#1f4234] text-white border-l-4 border-[#ffdf1b]' : ''; ?>">
            <span class="mr-3 w-5 text-center">⚙️</span> <span class="text-sm font-medium">Deposit & Transaction</span>
        </a>
        <a href="kyc_queue.php" class="flex items-center px-6 py-3 text-[#dbece3] hover:bg-[#1f4234] hover:text-white transition <?php echo $current_page == 'kyc_queue.php' ? 'bg-[#1f4234] text-white border-l-4 border-[#ffdf1b]' : ''; ?>">
            <span class="mr-3 w-5 text-center">🆔</span> <span class="text-sm font-medium">KYC Verification</span>
        </a>
        
        <a href="promotions.php" class="flex items-center px-6 py-3 text-[#dbece3] hover:bg-[#1f4234] hover:text-white transition <?php echo $current_page == 'promotions.php' ? 'bg-[#1f4234] text-white border-l-4 border-[#ffdf1b]' : ''; ?>">
            <span class="mr-3 w-5 text-center">📢</span> <span class="text-sm font-medium">Promotions</span>
        </a>
        
        <a href="manage_display.php" class="flex items-center px-6 py-3 text-[#dbece3] hover:bg-[#1f4234] hover:text-white transition <?php echo $current_page == 'manage_display.php' ? 'bg-[#1f4234] text-white border-l-4 border-[#ffdf1b]' : ''; ?>">
            <span class="mr-3 w-5 text-center">🎮</span> <span class="text-sm font-medium">Game Manage</span>
        </a>
        
        <a href="ggr-panel.php" class="flex items-center px-6 py-3 text-[#dbece3] hover:bg-[#1f4234] hover:text-white transition <?php echo $current_page == 'ggr-panel.php' ? 'bg-[#1f4234] text-white border-l-4 border-[#ffdf1b]' : ''; ?>">
            <span class="mr-3 w-5 text-center">📶</span> <span class="text-sm font-medium">GGR Panel</span>
        </a>
        
        <a href="bonus_manager.php" class="flex items-center px-6 py-3 text-[#dbece3] hover:bg-[#1f4234] hover:text-white transition <?php echo $current_page == 'bonus_manager.php' ? 'bg-[#1f4234] text-white border-l-4 border-[#ffdf1b]' : ''; ?>">
            <span class="mr-3 w-5 text-center">🎁</span> <span class="text-sm font-medium">Bonus Manager</span>
        </a>
        <?php endif; ?>

        <p class="px-6 text-[10px] font-bold uppercase tracking-widest text-[#55826f] mt-6 mb-2">Support</p>

        <a href="support.php" class="flex items-center px-6 py-3 text-[#dbece3] hover:bg-[#1f4234] hover:text-white transition <?php echo $current_page == 'support.php' ? 'bg-[#1f4234] text-white border-l-4 border-[#ffdf1b]' : ''; ?>">
            <span class="mr-3 w-5 text-center">🎧</span> <span class="text-sm font-medium">Live Support</span>
            <?php if($current_role === 'admin'): ?>
            <span class="ml-auto bg-blue-600 text-white text-[10px] px-1.5 py-0.5 rounded-full">New</span>
            <?php endif; ?>
        </a>

        <?php if($current_role === 'admin'): ?>
        
        <p class="px-6 text-[10px] font-bold uppercase tracking-widest text-[#55826f] mt-6 mb-2">Control Center</p>
        
        <a href="providers.php" class="flex items-center px-6 py-3 text-[#dbece3] hover:bg-[#1f4234] hover:text-white transition <?php echo $current_page == 'providers.php' ? 'bg-[#1f4234] text-white border-l-4 border-[#ffdf1b]' : ''; ?>">
            <span class="mr-3 w-5 text-center">🎮</span> <span class="text-sm font-medium">Providers & API</span>
        </a>
        <a href="game_api_key.php" class="flex items-center px-6 py-3 text-[#dbece3] hover:bg-[#1f4234] hover:text-white transition <?php echo $current_page == 'game_api_key.php' ? 'bg-[#1f4234] text-white border-l-4 border-[#ffdf1b]' : ''; ?>">
            <span class="mr-3 w-5 text-center">🔑</span> <span class="text-sm font-medium">Game API Key</span>
        </a>
        <a href="risk_control.php" class="flex items-center px-6 py-3 text-[#dbece3] hover:bg-[#1f4234] hover:text-white transition <?php echo $current_page == 'risk_control.php' ? 'bg-[#1f4234] text-white border-l-4 border-[#ffdf1b]' : ''; ?>">
            <span class="mr-3 w-5 text-center">🛡️</span> <span class="text-sm font-medium">Risk & Security</span>
        </a>

        <p class="px-6 text-[10px] font-bold uppercase tracking-widest text-[#55826f] mt-6 mb-2">Finance & Logs</p>
        
        <?php
        $get_status = $_GET['status'] ?? '';
        $get_type = $_GET['type'] ?? '';
        ?>
        <a href="finance.php" class="flex items-center px-6 py-3 text-[#dbece3] hover:bg-[#1f4234] hover:text-white transition <?php echo ($current_page == 'finance.php' && empty($get_status)) ? 'bg-[#1f4234] text-white border-l-4 border-[#ffdf1b]' : ''; ?>">
            <span class="mr-3 w-5 text-center">💰</span> <span class="text-sm font-medium">Transactions</span>
        </a>
        <a href="finance.php?status=pending&type=deposit&period=lifetime" class="flex items-center px-6 py-3 text-[#dbece3] hover:bg-[#1f4234] hover:text-white transition <?php echo ($current_page == 'finance.php' && $get_status == 'pending' && $get_type == 'deposit') ? 'bg-[#1f4234] text-white border-l-4 border-[#ffdf1b]' : ''; ?>">
            <span class="mr-3 w-5 text-center">📥</span> <span class="text-sm font-medium">Pending Deposits</span>
        </a>
        <a href="finance.php?status=pending&type=withdraw&period=lifetime" class="flex items-center px-6 py-3 text-[#dbece3] hover:bg-[#1f4234] hover:text-white transition <?php echo ($current_page == 'finance.php' && $get_status == 'pending' && $get_type == 'withdraw') ? 'bg-[#1f4234] text-white border-l-4 border-[#ffdf1b]' : ''; ?>">
            <span class="mr-3 w-5 text-center">📤</span> <span class="text-sm font-medium">Pending Withdrawals</span>
        </a>
        <a href="system_logs.php" class="flex items-center px-6 py-3 text-[#dbece3] hover:bg-[#1f4234] hover:text-white transition <?php echo $current_page == 'system_logs.php' ? 'bg-[#1f4234] text-white border-l-4 border-[#ffdf1b]' : ''; ?>">
            <span class="mr-3 w-5 text-center">📜</span> <span class="text-sm font-medium">Audit Logs</span>
        </a>

        <p class="px-6 text-[10px] font-bold uppercase tracking-widest text-[#55826f] mt-6 mb-2">System</p>
        
        <a href="settings.php" class="flex items-center px-6 py-3 text-[#dbece3] hover:bg-[#1f4234] hover:text-white transition <?php echo $current_page == 'settings.php' ? 'bg-[#1f4234] text-white border-l-4 border-[#ffdf1b]' : ''; ?>">
            <span class="mr-3 w-5 text-center">⚙️</span> <span class="text-sm font-medium">Settings</span>
        </a>
        
        <?php endif; ?>

        <div class="mt-8 mb-4 px-6">
            <a href="../logout.php" class="flex items-center justify-center w-full bg-red-900/20 hover:bg-red-900/40 text-red-500 py-3 rounded-lg transition border border-red-900/30 group">
                <span class="mr-2 group-hover:scale-110 transition-transform">🚪</span> <span class="font-bold text-sm">Logout</span>
            </a>
        </div>
    </nav>
</aside>

<script>
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    const openBtn = document.getElementById('mobile-menu-open');

    // Open Sidebar
    if(openBtn) {
        openBtn.addEventListener('click', () => {
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
        });
    }

    // Close Sidebar Function
    function closeSidebar() {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
    }
</script>