<?php
/**
 * LG-Pay Callback Handler
 * Path: api/lg_callback.php
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/lg_config.php';

$data = $_POST;

if (!empty($data)) {
    $remote_sign = isset($data['sign']) ? $data['sign'] : '';
    unset($data['sign']);
    $local_sign = generate_lg_sign($data, LG_KEY);

    // Verify signature & paid status (status == 1)
    if (strcasecmp($local_sign, $remote_sign) === 0 && isset($data['status']) && $data['status'] == 1) {
        
        $order_sn = $conn->real_escape_string($data['order_sn']);
        $money = floatval(($data['money'] ?? 0) / 100); 

        $check = $conn->query("SELECT user_id, status, promo_id, amount FROM transactions_fake WHERE order_sn = '$order_sn' LIMIT 1");
        
        if ($check && $check->num_rows > 0) {
            $row = $check->fetch_assoc();
            
            if ($row['status'] !== 'approved') {
                $uid = intval($row['user_id']);
                $promo_id = intval($row['promo_id']); 
                $db_amount = floatval($row['amount']); 
                
                $conn->begin_transaction();
                try {
                    $gateway_txn_id = $conn->real_escape_string($data['msg'] ?? ($data['trade_sn'] ?? 'SUCCESS'));

                    $conn->query("UPDATE transactions_fake SET 
                        status = 'approved', 
                        lg_order_sn = '$gateway_txn_id' 
                        WHERE order_sn = '$order_sn'");
                    
                    $final_deposit_amount = $db_amount > 0 ? $db_amount : $money;
                    $wager_to_add = $final_deposit_amount; 

                    if ($promo_id > 0) {
                        $settings_res = $conn->query("SELECT deposit_bonus_percent, wager_multiplier FROM settings WHERE id=1");
                        if ($settings_res && $settings_res->num_rows > 0) {
                            $settings = $settings_res->fetch_assoc();
                            $bonus_percent = (float)($settings['deposit_bonus_percent'] ?? 0);
                            $multiplier = (float)($settings['wager_multiplier'] ?? 1);

                            if ($bonus_percent > 0) {
                                $bonus_money = ($final_deposit_amount * $bonus_percent) / 100;
                                $final_deposit_amount += $bonus_money;
                                $wager_to_add = $final_deposit_amount * $multiplier;
                            }
                        }
                    }

                    $conn->query("UPDATE users SET 
                        balance = balance + $final_deposit_amount, 
                        turnover_target = turnover_target + $wager_to_add 
                        WHERE id = $uid");
                    
                    $conn->commit();
                    echo "ok";
                    exit();
                    
                } catch (Exception $e) {
                    $conn->rollback();
                    error_log("LG_Callback_Error: " . $e->getMessage());
                }
            } else {
                echo "ok";
                exit();
            }
        }
    }
}
echo "fail";
?>