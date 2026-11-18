<?php
require '../includes/auth_session.php';
require '../includes/db.php';
require '../includes/functions.php';
require_once '../includes/game_api_helper.php';
game_api_start_error_logging();

// Keep API endpoint, currency, agent/prefix and provider mapping managed in code/settings.
// Admin only updates API Key and Secret Key from this simplified screen.
game_api_ensure_schema($conn, true);

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die('Access Denied');
}

$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_game_api_key'])) {
    $reseller = trim($_POST['reseller_id'] ?? '');
    $token = trim($_POST['api_token'] ?? '');
    $secret = trim($_POST['secret_key'] ?? '');
    $agent = trim($_POST['agent_code'] ?? '');
    $currency = trim($_POST['currency'] ?? 'BDT');

    if ($reseller !== '') { game_api_set_setting($conn, 'reseller_id', $reseller); }
    game_api_set_setting($conn, 'api_token', $token);
    game_api_set_setting($conn, 'secret_key', $secret);
    if ($agent !== '') { game_api_set_setting($conn, 'agent_code', $agent); }
    if ($currency !== '') { game_api_set_setting($conn, 'currency', $currency); }
    game_api_set_setting($conn, 'game_api_provider', 'OXEN_TECH');
    $msg = 'API Credentials updated successfully.';
}

$config = game_api_get_settings($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Game API Key | Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>
<body class="bg-gray-50 font-sans text-slate-800">
    <?php include '../includes/sidebar_admin.php'; ?>
    <main class="lg:ml-64 p-4 lg:p-8 min-h-screen transition-all duration-300">
        <div class="max-w-3xl mx-auto">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2"><i class="fas fa-key text-yellow-500"></i> GameCloud API Settings</h1>
            </div>

            <?php if($msg): ?><div class="mb-5 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg font-semibold"><?php echo game_api_h($msg); ?></div><?php endif; ?>
            <?php if($error): ?><div class="mb-5 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg font-semibold"><?php echo game_api_h($error); ?></div><?php endif; ?>

            <section class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-bold mb-5">GameCloud / Netooze Configuration</h2>
                <form method="POST" class="space-y-5">
                    <div>
                        <label class="block text-sm font-bold text-gray-600 mb-1">Reseller ID</label>
                        <input type="text" name="reseller_id" value="<?php echo game_api_h($config['reseller_id'] ?? '292'); ?>" class="w-full border border-gray-300 rounded-lg p-3 font-mono text-sm focus:ring-2 focus:ring-yellow-400 outline-none" placeholder="e.g. 292">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-600 mb-1">API Token</label>
                        <input type="text" name="api_token" value="<?php echo game_api_h($config['api_token'] ?? 'c5c4df22b0a94132927b9beb8b3442ec'); ?>" autocomplete="off" class="w-full border border-gray-300 rounded-lg p-3 font-mono text-sm focus:ring-2 focus:ring-yellow-400 outline-none" placeholder="Enter API Token">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-600 mb-1">Secret Key</label>
                        <input type="text" name="secret_key" value="<?php echo game_api_h($config['secret_key'] ?? '8b4b1bde096adfe282a627aeddfc7c5e4669dda7843825f5d7ffbeabdf03632f'); ?>" autocomplete="off" class="w-full border border-gray-300 rounded-lg p-3 font-mono text-sm focus:ring-2 focus:ring-yellow-400 outline-none" placeholder="Enter Secret Key">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-600 mb-1">API Prefix Segment (Agent Code)</label>
                        <input type="text" name="agent_code" value="<?php echo game_api_h($config['agent_code'] ?? 'RS_KAKI66'); ?>" class="w-full border border-gray-300 rounded-lg p-3 font-mono text-sm focus:ring-2 focus:ring-yellow-400 outline-none" placeholder="e.g. RS_KAKI66">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-600 mb-1">Currency Code</label>
                        <input type="text" name="currency" value="<?php echo game_api_h($config['currency'] ?? 'BDT'); ?>" class="w-full border border-gray-300 rounded-lg p-3 font-mono text-sm focus:ring-2 focus:ring-yellow-400 outline-none" placeholder="BDT">
                    </div>
                    <button type="submit" name="save_game_api_key" class="w-full bg-yellow-500 hover:bg-yellow-400 text-black font-black py-3 rounded-lg shadow transition">
                        <i class="fas fa-save"></i> SAVE GAME API SETTINGS
                    </button>
                </form>
            </section>

            </div>
        </div>
    </main>
</body>
</html>
