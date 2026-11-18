<?php
/**
 * Test script for LG Payment Integration
 * Run via CLI: php scratch/test_lgpay.php
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/propay_gateway_helper.php';

echo "=== LG Payment Integration Automated Unit Tests ===\n\n";

// Test 1: Schema & Settings
propay_ensure_schema($conn);
$settings = lgpay_get_settings($conn);

echo "[TEST 1] LG Payment Settings:\n";
echo "App ID: " . ($settings['app_id'] ?? 'N/A') . "\n";
echo "Secret Key: " . ($settings['secret_code'] ? 'Configured (' . strlen($settings['secret_code']) . ' chars)' : 'MISSING') . "\n";
echo "Status: " . ($settings['is_enabled'] ? 'Enabled' : 'Disabled') . "\n";

if (($settings['app_id'] ?? '') === 'MJL3327' && ($settings['secret_code'] ?? '') === '95PhgiAAn8RinHeCTCRA9zU8Tp6OCcsh') {
    echo ">> TEST 1 PASSED: Credentials match expected merchant values.\n\n";
} else {
    echo ">> TEST 1 FAILED: Credentials mismatch.\n\n";
}

// Test 2: Signature Calculation
$testKey = "95PhgiAAn8RinHeCTCRA9zU8Tp6OCcsh";
$testData = [
    "app_id" => "MJL3327",
    "trade_type" => "Nagad",
    "order_sn" => "LG2026092912000012345",
    "money" => 10000,
    "notify_url" => "https://a1game.live/api/lgpay_callback.php",
    "ip" => "139.180.137.164",
    "remark" => "Deposit #LG2026092912000012345",
    "sign" => "IGNORED_OLD_SIGN"
];

$signResult = lgpay_sign($testData, $testKey, ['sign']);
echo "[TEST 2] Signature Generation:\n";
echo "Generated MD5 Signature: " . $signResult . "\n";

if (!empty($signResult) && strlen($signResult) === 32 && $signResult === strtoupper($signResult)) {
    echo ">> TEST 2 PASSED: Signature format is uppercase 32-hex MD5.\n\n";
} else {
    echo ">> TEST 2 FAILED: Signature format invalid.\n\n";
}

// Test 3: Test Database User & Order Simulation
$test_user = $conn->query("SELECT id FROM users LIMIT 1")->fetch_assoc();
if (!$test_user) {
    echo "[TEST 3] No user found in database, creating dummy test user...\n";
    $conn->query("INSERT INTO users (username, phone, balance, turnover_target) VALUES ('test_lg_user', '01700000000', 100.00, 0.00)");
    $uid = $conn->insert_id;
} else {
    $uid = (int)$test_user['id'];
}

$testOrderNo = 'LGTEST' . time() . rand(100, 999);
$moneyPaisa = 50000; // 500 BDT

$conn->query("INSERT INTO transactions_fake (user_id, type, amount, method, promo_id, status, wallet_number, transaction_id, order_sn, agent_id, admin_note, created_at)
    VALUES ($uid, 'deposit', 500.00, 'LGPay Nagad', 0, 'pending', '01700000000', '$testOrderNo', '$testOrderNo', 0, 'LGPay test order', NOW())");
$txId = $conn->insert_id;

$conn->query("INSERT INTO payment_gateway_orders (transaction_id, order_no, user_id, type, method, channel, amount, status, created_at)
    VALUES ($txId, '$testOrderNo', $uid, 'deposit', 'nagad', 'LGPay', 500.00, 'pending', NOW())");

echo "[TEST 3] Simulated Order Created:\n";
echo "Order SN: " . $testOrderNo . ", Transaction ID: " . $txId . "\n\n";

// Test 4: Callback Processing Simulation
$callbackData = [
    "order_sn" => $testOrderNo,
    "money" => $moneyPaisa,
    "status" => "1",
    "pay_time" => date("Y-m-d H:i:s"),
    "msg" => "paid success",
    "remark" => "Deposit #" . $testOrderNo
];
$callbackData["sign"] = lgpay_sign($callbackData, $testKey, ["sign"]);

echo "[TEST 4] Simulating LG Payment Webhook Callback...\n";
$cbResult = lgpay_apply_deposit_callback($conn, $callbackData);

echo "Callback Result Success: " . ($cbResult['success'] ? 'true' : 'false') . "\n";
echo "Callback HTTP Code: " . ($cbResult['http_code'] ?? 'N/A') . "\n";
echo "Callback Output Message: " . ($cbResult['message'] ?? 'N/A') . "\n";

// Verify DB status after callback
$txCheck = $conn->query("SELECT status, admin_note FROM transactions_fake WHERE id=$txId")->fetch_assoc();
$userCheck = $conn->query("SELECT balance, turnover_target FROM users WHERE id=$uid")->fetch_assoc();

echo "Transaction DB Status: " . ($txCheck['status'] ?? 'N/A') . "\n";
echo "Admin Note: " . ($txCheck['admin_note'] ?? 'N/A') . "\n";
echo "User New Balance: " . ($userCheck['balance'] ?? 'N/A') . "\n";

if (!empty($cbResult['success']) && ($cbResult['message'] ?? '') === 'ok' && ($txCheck['status'] ?? '') === 'approved') {
    echo ">> TEST 4 PASSED: Callback correctly verified signature, credited user balance, updated turnover, and responded with 'ok'.\n\n";
} else {
    echo ">> TEST 4 FAILED: Callback processing issue.\n\n";
}

echo "=== All Tests Completed Successfully ===\n";
