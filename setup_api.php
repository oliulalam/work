<?php
/**
 * 1-Command Integration Installer & Diagnostic Script (setup_api.php)
 * Runs DB connection tests, repairs schema, syncs new API credentials, and verifies live launch API connections.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain; charset=utf-8');
define('DB_IGNORE_CONNECT_ERROR', true);

echo "=====================================================\n";
echo " NETOOZE / GAMECLOUD API SETUP & DIAGNOSTIC INSTALLER\n";
echo "=====================================================\n\n";

$db_file = __DIR__ . '/includes/db.php';
if (!file_exists($db_file)) {
    die("❌ Error: includes/db.php not found.\n");
}

require_once __DIR__ . '/includes/game_api_helper.php';

// 1. Test DB Connection
echo "[1/4] Testing Database Connection...\n";
$conn = null;
try {
    mysqli_report(MYSQLI_REPORT_OFF);
    @require_once $db_file;
    if (isset($conn) && $conn && !$conn->connect_error) {
        echo "  ✅ Database Connected Successfully.\n\n";
        
        // 2. Repair Schema & Tables
        echo "[2/4] Repairing Schema & Ensuring Tables...\n";
        game_api_ensure_schema($conn, false);

        if (function_exists('ensure_rewards_tables')) {
            ensure_rewards_tables($conn);
            echo "  • Rewards & Ticket tables checked/created.\n";
        }

        // Ensure bonus columns in settings table
        $chkCol1 = @$conn->query("SHOW COLUMNS FROM settings LIKE 'welcome_bonus_amount'");
        if (!$chkCol1 || $chkCol1->num_rows === 0) {
            @$conn->query("ALTER TABLE settings ADD COLUMN welcome_bonus_amount DECIMAL(10,2) NOT NULL DEFAULT 50.00");
            echo "  • Added welcome_bonus_amount to settings.\n";
        }
        $chkCol2 = @$conn->query("SHOW COLUMNS FROM settings LIKE 'referral_bonus_amount'");
        if (!$chkCol2 || $chkCol2->num_rows === 0) {
            @$conn->query("ALTER TABLE settings ADD COLUMN referral_bonus_amount DECIMAL(10,2) NOT NULL DEFAULT 50.00");
            echo "  • Added referral_bonus_amount to settings.\n";
        }

        // Ensure referral helper schema
        $refHelper = __DIR__ . '/includes/referral_helper.php';
        if (file_exists($refHelper)) {
            require_once $refHelper;
            if (function_exists('referral_ensure_schema')) {
                referral_ensure_schema($conn);
                echo "  • Referral schema and rewards verified.\n";
            }
        }

        if (!game_api_table_exists($conn, 'game_settings')) {
            echo "  ⚠️ Table game_settings missing, creating...\n";
            $conn->query("CREATE TABLE IF NOT EXISTS game_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(80) NOT NULL,
                setting_value TEXT DEFAULT NULL,
                KEY idx_key (setting_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        echo "  ✅ Database Schema Verified & Repaired.\n\n";
    } else {
        $conn = null;
        echo "  ℹ️ Database not reachable locally (will connect on web host).\n\n";
    }
} catch (Throwable $e) {
    $conn = null;
    echo "  ℹ️ Local DB connection skipped: " . $e->getMessage() . "\n\n";
}

// 3. Update API Credentials in DB
echo "[3/4] Updating & Syncing New API Credentials in DB...\n";
$newCredentials = [
    'reseller_id' => '292',
    'api_token'   => 'c5c4df22b0a94132927b9beb8b3442ec',
    'secret_key'  => '8b4b1bde096adfe282a627aeddfc7c5e4669dda7843825f5d7ffbeabdf03632f',
    'agent_code'  => 'RS_KAKI66',
    'currency'    => 'BDT'
];

foreach ($newCredentials as $key => $val) {
    if ($conn) { game_api_set_setting($conn, $key, $val); }
    echo "  • Setting '{$key}' set to: " . (in_array($key, ['api_token', 'secret_key']) ? substr($val, 0, 8) . '...' : $val) . "\n";
}
echo "  ✅ API Settings Configured.\n\n";

// 4. Test Live Game Launch API Connection
echo "[4/4] Testing Live GameCloud API Connection (Aviator)...\n";
$apiUrl = 'https://api.gamecloudapi.com/api/v1/game/launch';
$testPayload = [
    'reseller_id'   => 292,
    'player_id'     => 'RS_KAKI66_1',
    'game_uid'      => 'a04d1f3eb8ccec8a4823bdf18e3f0e84', // Aviator
    'mode'          => 'seamless',
    'currency_code' => 'BDT',
    'home_url'      => 'https://kaki66.com/player/dashboard.php'
];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testPayload));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-API-Token: ' . $newCredentials['api_token'],
    'X-Secret-Key: ' . $newCredentials['secret_key']
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($curlErr) {
    echo "  ❌ Live API Test Failed (cURL Error): {$curlErr}\n";
} else {
    echo "  HTTP Response Code: {$httpCode}\n";
    echo "  Raw API Response: {$response}\n";
    $json = json_decode($response, true);
    if (is_array($json) && isset($json['status']) && $json['status'] === 'SUCCESS') {
        echo "  ✅ Live API Connection SUCCESSFUL! Launch URL received.\n";
    } else {
        echo "  ℹ️ API Response parsed: " . print_r($json, true) . "\n";
    }
}

echo "\n=====================================================\n";
echo " API INTEGRATION SETUP COMPLETE\n";
echo "=====================================================\n";
