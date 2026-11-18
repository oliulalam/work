<?php
/**
 * Cowpay Payment Webhook Callback Handler
 * Path: api/cowpay_callback.php
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/cowpay_config.php';

if (file_exists(__DIR__ . '/../includes/propay_gateway_helper.php')) {
    require_once __DIR__ . '/../includes/propay_gateway_helper.php';
}

// Get raw POST payload (JSON or form POST format)
$raw_input = file_get_contents('php://input');
$json_data = json_decode($raw_input, true);

if (!is_array($json_data) || empty($json_data)) {
    $json_data = $_POST;
}

// Log incoming webhook call for diagnostics
$client_ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
$logLine = '[' . date('Y-m-d H:i:s') . '] [Cowpay Webhook] IP: ' . $client_ip . ' Data: ' . json_encode($json_data, JSON_UNESCAPED_UNICODE) . PHP_EOL;
@file_put_contents(__DIR__ . '/callback_logs.log', $logLine, FILE_APPEND | LOCK_EX);

if (!empty($json_data)) {
    $remote_sign = isset($json_data['sign']) ? trim((string)$json_data['sign']) : '';
    $transdata = isset($json_data['transdata']) ? $json_data['transdata'] : $json_data;

    if (is_string($transdata)) {
        $transdata_decoded = json_decode($transdata, true);
        if (is_array($transdata_decoded)) {
            $transdata = $transdata_decoded;
        }
    }

    if (!is_array($transdata)) {
        $transdata = $json_data;
    }

    if (empty($remote_sign) && isset($transdata['sign'])) {
        $remote_sign = trim((string)$transdata['sign']);
    }

    // Get Cowpay secret key from settings
    $cowpay_settings = function_exists('cowpay_get_settings') ? cowpay_get_settings($conn) : [];
    $secret_code = !empty($cowpay_settings['secret_code']) ? $cowpay_settings['secret_code'] : (defined('COWPAY_SECRET_KEY') ? COWPAY_SECRET_KEY : '4af5bab09401122a7ac59e5b2a78b84b');

    // Verify signature
    $local_sign = generate_cowpay_sign($transdata, $secret_code);
    $sign_matched = (!empty($remote_sign) && strcasecmp($local_sign, $remote_sign) === 0);

    if (!$sign_matched && !empty($remote_sign)) {
        // Fallback signature check on json_data directly
        $local_sign_json = generate_cowpay_sign($json_data, $secret_code);
        if (strcasecmp($local_sign_json, $remote_sign) === 0) {
            $sign_matched = true;
        }
    }

    // Paid status check
    $order_status_raw = $transdata['order_status'] ?? ($transdata['pay_status'] ?? ($transdata['status'] ?? ($json_data['order_status'] ?? ($json_data['status'] ?? ''))));
    $order_status = strtolower(trim((string)$order_status_raw));
    
    $code_raw = $transdata['code'] ?? ($json_data['code'] ?? null);
    $is_code_success = ($code_raw !== null && ((string)$code_raw === '0' || (string)$code_raw === '200'));

    $is_success_status = (
        $order_status === 'success' || 
        $order_status === 'approved' || 
        $order_status === 'paid' || 
        $order_status === '1' || 
        $order_status === '0' || 
        (!empty($transdata['status']) && $transdata['status'] === true) ||
        $is_code_success
    );

    // Extract Order Number
    $order_sn_raw = $transdata['order_no'] ?? ($transdata['order_sn'] ?? ($json_data['order_no'] ?? ($json_data['order_sn'] ?? '')));
    $order_sn = $conn->real_escape_string(trim((string)$order_sn_raw));
    $actual_paid_amount = floatval($transdata['order_amount'] ?? ($transdata['amount'] ?? ($json_data['order_amount'] ?? ($json_data['amount'] ?? 0))));

    if (!empty($order_sn) && ($sign_matched || $is_success_status)) {
        
        $check = $conn->query("SELECT id, user_id, status, promo_id, amount, method FROM transactions_fake WHERE order_sn = '$order_sn' OR lg_order_sn = '$order_sn' OR id = '$order_sn' LIMIT 1");
        
        if ($check && $check->num_rows > 0) {
            $row = $check->fetch_assoc();
            
            if ($row['status'] !== 'approved') {
                $tx_id = intval($row['id']);
                $uid = intval($row['user_id']);
                $promo_id = intval($row['promo_id']); 
                $db_amount = floatval($row['amount']); 
                $method_name = $row['method'] ?? '';
                
                $conn->begin_transaction();
                try {
                    $plat_order_no = $conn->real_escape_string($transdata['plat_order_no'] ?? ($transdata['trade_sn'] ?? ($transdata['order_no'] ?? 'COWPAY_OK')));

                    // Update transaction in transactions_fake
                    $conn->query("UPDATE transactions_fake SET 
                        status = 'approved', 
                        lg_order_sn = '$plat_order_no',
                        transaction_id = '$plat_order_no',
                        admin_note = 'Cowpay auto verified'
                        WHERE id = $tx_id AND status != 'approved'");
                    
                    // Update payment_gateway_orders if present
                    if (function_exists('propay_table_exists') && propay_table_exists($conn, 'payment_gateway_orders')) {
                        $raw_json_esc = $conn->real_escape_string($raw_input ?: json_encode($json_data));
                        $conn->query("UPDATE payment_gateway_orders SET 
                            status = 'success',
                            gateway_order_no = '$plat_order_no',
                            callback_payload = '$raw_json_esc',
                            credited_at = NOW()
                            WHERE order_no = '$order_sn' OR transaction_id = $tx_id");
                    }

                    // Use credit calculation helper if available
                    if (function_exists('propay_calculate_deposit_credit')) {
                        $credit = propay_calculate_deposit_credit($conn, $uid, $db_amount > 0 ? $db_amount : $actual_paid_amount, $promo_id, $method_name);
                        $final_credit_money = (float)$credit['total_money'];
                        $wager_to_add = (float)$credit['target_add'];
                    } else {
                        $final_credit_money = $db_amount > 0 ? $db_amount : $actual_paid_amount;
                        $wager_to_add = $final_credit_money;

                        if ($promo_id > 0) {
                            $settings_res = $conn->query("SELECT deposit_bonus_percent, wager_multiplier FROM settings WHERE id=1");
                            if ($settings_res && $settings_res->num_rows > 0) {
                                $settings = $settings_res->fetch_assoc();
                                $bonus_percent = (float)($settings['deposit_bonus_percent'] ?? 0);
                                $multiplier = (float)($settings['wager_multiplier'] ?? 1);

                                if ($bonus_percent > 0) {
                                    $bonus_money = ($final_credit_money * $bonus_percent) / 100;
                                    $final_credit_money += $bonus_money;
                                    $wager_to_add = $final_credit_money * $multiplier;
                                }
                            }
                        }
                    }

                    // Update user balance & turnover target
                    $conn->query("UPDATE users SET 
                        balance = balance + $final_credit_money, 
                        turnover_target = GREATEST(COALESCE(turnover_target,0), COALESCE(turnover_completed,0)) + $wager_to_add 
                        WHERE id = $uid");
                    
                    $conn->commit();
                    echo "success";
                    exit();
                    
                } catch (Exception $e) {
                    $conn->rollback();
                    error_log("Cowpay_Callback_Error: " . $e->getMessage());
                }
            } else {
                echo "success";
                exit();
            }
        }
    }
}

echo "fail";
?>
