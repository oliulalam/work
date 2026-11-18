<?php
// Dynamic header branding for Admin/Agent panels
if (!isset($admin_brand_safe) || !isset($admin_logo_safe)) {
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
}
?>
<header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-8 fixed top-0 right-0 left-64 z-10 shadow-sm">
    <div>
        <h2 class="text-lg font-bold text-gray-700 capitalize"><?php echo basename($_SERVER['PHP_SELF'], '.php'); ?> Overview</h2>
        <p class="text-xs text-green-600 flex items-center">
            <span class="w-2 h-2 rounded-full bg-green-500 mr-2 animate-pulse"></span> System Operational
        </p>
    </div>

    <div class="flex items-center gap-4">
        <div class="hidden sm:flex items-center gap-2 px-3 py-2 rounded-xl bg-gray-50 border border-gray-200">
            <?php if(!empty($admin_logo_safe)): ?>
                <img src="../<?php echo $admin_logo_safe; ?>?v=<?php echo time(); ?>" class="h-8 w-8 object-contain rounded" alt="Logo">
            <?php endif; ?>
            <span class="text-sm font-bold text-gray-800 max-w-[180px] truncate"><?php echo $admin_brand_safe; ?></span>
        </div>
        <div class="text-right">
            <p class="text-sm font-bold text-gray-800">SuperAdmin</p>
            <p class="text-xs text-gray-500">Security Level: Master</p>
        </div>
        <div class="h-10 w-10 rounded-full bg-gray-800 flex items-center justify-center text-white font-bold border-2 border-gray-300">
            A
        </div>
    </div>
</header>
