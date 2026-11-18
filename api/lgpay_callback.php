<?php
/**
 * LG Payment Webhook Callback Handler
 * Path: api/lgpay_callback.php
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/propay_gateway_helper.php';

// Get raw POST payload (JSON or form-encoded POST)
$raw_input = file_get_contents('php://input');
$payload = json_decode($raw_input, true);

if (!is_array($payload) || empty($payload)) {
    $payload = $_POST;
}

$client_ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
$logLine = '[' . date('Y-m-d H:i:s') . '] [LGPay Callback] IP: ' . $client_ip . ' Data: ' . json_encode($payload, JSON_UNESCAPED_UNICODE) . PHP_EOL;
@file_put_contents(__DIR__ . '/callback_logs.log', $logLine, FILE_APPEND | LOCK_EX);

if (empty($payload)) {
    http_response_code(400);
    echo "fail";
    exit();
}

$result = lgpay_apply_deposit_callback($conn, $payload);

http_response_code($result['http_code'] ?? 200);

if (!empty($result['success'])) {
    echo "ok";
} else {
    echo $result['message'] ?? "fail";
}
exit();
