<?php
// includes/propay_gateway_helper.php
// ProPay Payment Gateway helper. Keeps credentials dynamic from DB and makes the patch work without manual SQL import.

if (!defined('PROPAY_HELPER_LOADED')) {
    define('PROPAY_HELPER_LOADED', true);
}

function propay_table_exists($conn, $table) {
    $safe = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $res = @$conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($safe) . "'");
    return $res && $res->num_rows > 0;
}

function propay_column_exists($conn, $table, $column) {
    $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $safeColumn = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
    $res = @$conn->query("SHOW COLUMNS FROM `$safeTable` LIKE '" . $conn->real_escape_string($safeColumn) . "'");
    return $res && $res->num_rows > 0;
}

function propay_add_column_if_missing($conn, $table, $column, $definition) {
    $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $safeColumn = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
    if (!propay_column_exists($conn, $safeTable, $safeColumn)) {
        @$conn->query("ALTER TABLE `$safeTable` ADD COLUMN `$safeColumn` $definition");
    }
}

function propay_ensure_schema($conn) {
    if (!$conn || $conn->connect_error) { return false; }

    @$conn->query("CREATE TABLE IF NOT EXISTS `payment_gateway_settings` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `provider` VARCHAR(40) NOT NULL DEFAULT 'propay',
        `merchant_code` VARCHAR(120) DEFAULT NULL,
        `secret_code` VARCHAR(255) DEFAULT NULL,
        `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uniq_provider` (`provider`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    @$conn->query("INSERT IGNORE INTO `payment_gateway_settings` (`provider`, `merchant_code`, `secret_code`, `is_enabled`) VALUES ('propay', '', '', 1)");
    @$conn->query("INSERT IGNORE INTO `payment_gateway_settings` (`provider`, `merchant_code`, `secret_code`, `is_enabled`) VALUES ('cowpay', 'M2769530848203370439', '4af5bab09401122a7ac59e5b2a78b84b', 1)");
    @$conn->query("INSERT IGNORE INTO `payment_gateway_settings` (`provider`, `merchant_code`, `secret_code`, `is_enabled`) VALUES ('lgpay', 'MJL3327', '95PhgiAAn8RinHeCTCRA9zU8Tp6OCcsh', 1)");

    @$conn->query("CREATE TABLE IF NOT EXISTS `payment_gateway_orders` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `transaction_id` INT(11) DEFAULT NULL,
        `order_no` VARCHAR(120) NOT NULL,
        `gateway_order_no` VARCHAR(120) DEFAULT NULL,
        `user_id` INT(11) NOT NULL,
        `type` ENUM('deposit','withdraw') NOT NULL,
        `method` VARCHAR(40) NOT NULL,
        `account_number` VARCHAR(30) DEFAULT NULL,
        `amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `status` ENUM('pending','processing','success','failed') NOT NULL DEFAULT 'pending',
        `gateway_status` VARCHAR(60) DEFAULT NULL,
        `raw_request` MEDIUMTEXT DEFAULT NULL,
        `raw_response` MEDIUMTEXT DEFAULT NULL,
        `callback_payload` MEDIUMTEXT DEFAULT NULL,
        `credited_at` DATETIME DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uniq_order_no` (`order_no`),
        KEY `idx_gateway_order_no` (`gateway_order_no`),
        KEY `idx_user_type_status` (`user_id`, `type`, `status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Dynamic admin controlled deposit bonus, channel and transaction limit schema.
    if (propay_table_exists($conn, 'settings')) {
        $hadBkashBonus = propay_column_exists($conn, 'settings', 'deposit_bonus_bkash');
        $hadNagadBonus = propay_column_exists($conn, 'settings', 'deposit_bonus_nagad');
        propay_add_column_if_missing($conn, 'settings', 'deposit_bonus_bkash', "DECIMAL(8,2) NOT NULL DEFAULT 0.00");
        propay_add_column_if_missing($conn, 'settings', 'deposit_bonus_nagad', "DECIMAL(8,2) NOT NULL DEFAULT 0.00");
        propay_add_column_if_missing($conn, 'settings', 'min_deposit_amount', "DECIMAL(15,2) NOT NULL DEFAULT 100.00");
        propay_add_column_if_missing($conn, 'settings', 'min_withdraw_amount', "DECIMAL(15,2) NOT NULL DEFAULT 100.00");
        propay_add_column_if_missing($conn, 'settings', 'deposit_notice', "TEXT DEFAULT NULL");
        if (!$hadBkashBonus || !$hadNagadBonus) {
            @$conn->query("UPDATE settings SET deposit_bonus_bkash = COALESCE(NULLIF(deposit_bonus_bkash, 0), deposit_bonus_percent, 0), deposit_bonus_nagad = COALESCE(NULLIF(deposit_bonus_nagad, 0), deposit_bonus_percent, 0) WHERE id=1");
        }
    }

    if (propay_table_exists($conn, 'payment_gateway_orders')) {
        propay_add_column_if_missing($conn, 'payment_gateway_orders', 'channel', "VARCHAR(60) DEFAULT NULL AFTER `method`");
    }

    if (propay_table_exists($conn, 'users')) {
        $hadTurnoverCompleted = propay_column_exists($conn, 'users', 'turnover_completed');
        propay_add_column_if_missing($conn, 'users', 'turnover_completed', "DECIMAL(20,2) NOT NULL DEFAULT 0.00 AFTER `turnover_target`");
        if (!$hadTurnoverCompleted && propay_table_exists($conn, 'game_bet_history')) {
            if (propay_column_exists($conn, 'game_bet_history', 'amount') && propay_column_exists($conn, 'game_bet_history', 'type')) {
                @$conn->query("UPDATE users u LEFT JOIN (SELECT user_id, SUM(amount) AS total_bet FROM game_bet_history WHERE type='bet' GROUP BY user_id) b ON b.user_id=u.id SET u.turnover_completed = COALESCE(b.total_bet, 0)");
            } elseif (propay_column_exists($conn, 'game_bet_history', 'bet_amount')) {
                @$conn->query("UPDATE users u LEFT JOIN (SELECT user_id, SUM(bet_amount) AS total_bet FROM game_bet_history GROUP BY user_id) b ON b.user_id=u.id SET u.turnover_completed = COALESCE(b.total_bet, 0)");
            }
        }
    }

    // Keep compatibility with older live databases.
    if (propay_table_exists($conn, 'transactions_fake')) {
        propay_add_column_if_missing($conn, 'transactions_fake', 'promo_id', "INT(11) DEFAULT 0");
        propay_add_column_if_missing($conn, 'transactions_fake', 'wallet_number', "VARCHAR(20) DEFAULT NULL COMMENT 'User Phone/Wallet'");
        propay_add_column_if_missing($conn, 'transactions_fake', 'transaction_id', "VARCHAR(100) DEFAULT NULL COMMENT 'Gateway/Trx ID'");
        propay_add_column_if_missing($conn, 'transactions_fake', 'order_sn', "VARCHAR(120) DEFAULT NULL");
        propay_add_column_if_missing($conn, 'transactions_fake', 'lg_order_sn', "VARCHAR(120) DEFAULT NULL");
        propay_add_column_if_missing($conn, 'transactions_fake', 'admin_note', "TEXT DEFAULT NULL");
        propay_add_column_if_missing($conn, 'transactions_fake', 'is_notified', "TINYINT(1) NOT NULL DEFAULT 0");
    }

    return true;
}

function propay_get_settings($conn) {
    propay_ensure_schema($conn);
    $settings = array('merchant_code' => '', 'secret_code' => '', 'is_enabled' => 1);
    $res = @$conn->query("SELECT merchant_code, secret_code, is_enabled FROM payment_gateway_settings WHERE provider='propay' LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $settings['merchant_code'] = trim($row['merchant_code'] ?? '');
        $settings['secret_code'] = trim($row['secret_code'] ?? '');
        $settings['is_enabled'] = intval($row['is_enabled'] ?? 1);
    }
    return $settings;
}

function propay_save_settings($conn, $merchant_code, $secret_code, $is_enabled) {
    propay_ensure_schema($conn);
    $provider = 'propay';
    $merchant_code = trim((string)$merchant_code);
    $secret_code = trim((string)$secret_code);
    $is_enabled = $is_enabled ? 1 : 0;
    $stmt = $conn->prepare("INSERT INTO payment_gateway_settings (provider, merchant_code, secret_code, is_enabled)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE merchant_code=VALUES(merchant_code), secret_code=VALUES(secret_code), is_enabled=VALUES(is_enabled), updated_at=NOW()");
    $stmt->bind_param('sssi', $provider, $merchant_code, $secret_code, $is_enabled);
    return $stmt->execute();
}

function cowpay_get_settings($conn) {
    propay_ensure_schema($conn);
    $default_secret = defined('COWPAY_SECRET_KEY') ? COWPAY_SECRET_KEY : '4af5bab09401122a7ac59e5b2a78b84b';
    $settings = array('merchant_code' => defined('COWPAY_MERCHANT_CODE') ? COWPAY_MERCHANT_CODE : 'M2769530848203370439', 'secret_code' => $default_secret, 'is_enabled' => 1);
    $res = @$conn->query("SELECT merchant_code, secret_code, is_enabled FROM payment_gateway_settings WHERE provider='cowpay' LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $m_code = trim($row['merchant_code'] ?? '');
        if ($m_code !== '') { $settings['merchant_code'] = $m_code; }
        $s_code = trim($row['secret_code'] ?? '');
        if ($s_code !== '') { $settings['secret_code'] = $s_code; }
        $settings['is_enabled'] = intval($row['is_enabled'] ?? 1);
    }
    return $settings;
}

function cowpay_save_settings($conn, $merchant_code, $secret_code, $is_enabled) {
    propay_ensure_schema($conn);
    $provider = 'cowpay';
    $merchant_code = trim((string)$merchant_code);
    $secret_code = trim((string)$secret_code);
    $is_enabled = $is_enabled ? 1 : 0;
    $stmt = $conn->prepare("INSERT INTO payment_gateway_settings (provider, merchant_code, secret_code, is_enabled)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE merchant_code=VALUES(merchant_code), secret_code=VALUES(secret_code), is_enabled=VALUES(is_enabled), updated_at=NOW()");
    $stmt->bind_param('sssi', $provider, $merchant_code, $secret_code, $is_enabled);
    return $stmt->execute();
}

function lgpay_get_settings($conn) {
    propay_ensure_schema($conn);
    $settings = array(
        'app_id' => 'MJL3327',
        'secret_code' => '95PhgiAAn8RinHeCTCRA9zU8Tp6OCcsh',
        'trade_type_nagad' => 'Nagad',
        'trade_type_bkash' => 'bKash',
        'is_enabled' => 1
    );
    $res = @$conn->query("SELECT merchant_code, secret_code, is_enabled FROM payment_gateway_settings WHERE provider='lgpay' LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $m_code = trim($row['merchant_code'] ?? '');
        if ($m_code !== '') { $settings['app_id'] = $m_code; }
        $s_code = trim($row['secret_code'] ?? '');
        if ($s_code !== '') { $settings['secret_code'] = $s_code; }
        $settings['is_enabled'] = intval($row['is_enabled'] ?? 1);
    }
    return $settings;
}

function lgpay_save_settings($conn, $app_id, $secret_code, $is_enabled) {
    propay_ensure_schema($conn);
    $provider = 'lgpay';
    $app_id = trim((string)$app_id);
    $secret_code = trim((string)$secret_code);
    $is_enabled = $is_enabled ? 1 : 0;
    $stmt = $conn->prepare("INSERT INTO payment_gateway_settings (provider, merchant_code, secret_code, is_enabled)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE merchant_code=VALUES(merchant_code), secret_code=VALUES(secret_code), is_enabled=VALUES(is_enabled), updated_at=NOW()");
    $stmt->bind_param('sssi', $provider, $app_id, $secret_code, $is_enabled);
    return $stmt->execute();
}

function lgpay_sign($data, $key, $unset = array('sign')) {
    if (is_array($unset)) {
        foreach ($unset as $val) {
            unset($data[$val]);
        }
    }
    ksort($data);
    $string = http_build_query($data);
    $string = urldecode($string);
    $string = trim($string) . "&key=" . $key;
    return strtoupper(md5($string));
}

function lgpay_create_deposit_order($conn, $uid, $amount, $method, $promo_id = 0, $channel = '') {
    propay_ensure_schema($conn);
    $lgSettings = lgpay_get_settings($conn);
    $appId = trim($lgSettings['app_id']);
    $secretKey = trim($lgSettings['secret_code']);

    if (!$lgSettings['is_enabled']) {
        return array('success' => false, 'message' => 'LG Payment gateway is currently disabled.');
    }
    if ($appId === '' || $secretKey === '') {
        return array('success' => false, 'message' => 'LG Payment credentials missing.');
    }

    $txnSettings = propay_get_site_transaction_settings($conn);
    $minDeposit = (float)($txnSettings['min_deposit_amount'] ?? 100);
    $amount = round((float)$amount, 2);
    if ($amount < $minDeposit) {
        return array('success' => false, 'message' => 'Minimum deposit amount is ৳' . propay_format_amount($minDeposit) . '.');
    }

    $methodKey = propay_normalize_method($method);
    if ($methodKey === '') {
        return array('success' => false, 'message' => 'Invalid payment method.');
    }

    // Trade type mapping
    $tradeType = ($methodKey === 'nagad') ? $lgSettings['trade_type_nagad'] : $lgSettings['trade_type_bkash'];
    if (strtolower(trim((string)$channel)) === 'test' || strtolower(trim((string)$method)) === 'test') {
        $tradeType = 'test';
    }

    $orderNo = propay_make_order_no('LG', $uid);
    $methodLabel = propay_method_label($methodKey);
    $channelLabel = propay_normalize_channel($channel) ?: 'LGPay';
    $historyMethod = 'LGPay ' . $methodLabel;

    // Fetch user phone / account info
    $userPhone = (string)$uid;
    $agentId = 0;
    $userStmt = $conn->prepare("SELECT phone, username, agent_id FROM users WHERE id=? LIMIT 1");
    if ($userStmt) {
        $userStmt->bind_param('i', $uid);
        $userStmt->execute();
        $uRes = $userStmt->get_result();
        if ($uRes && $uRes->num_rows > 0) {
            $u = $uRes->fetch_assoc();
            $agentId = intval($u['agent_id'] ?? 0);
            if (!empty($u['phone'])) {
                $userPhone = preg_replace('/[^0-9]/', '', $u['phone']);
            } elseif (!empty($u['username'])) {
                $userPhone = $u['username'];
            }
        }
    }
    if (strlen($userPhone) < 5) {
        $userPhone = '01700000000';
    }

    $promo_id = intval($promo_id);
    $clientIp = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (strpos($clientIp, ',') !== false) {
        $clientIp = trim(explode(',', $clientIp)[0]);
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO transactions_fake (user_id, type, amount, method, promo_id, status, wallet_number, transaction_id, order_sn, agent_id, admin_note, created_at)
            VALUES (?, 'deposit', ?, ?, ?, 'pending', ?, ?, ?, ?, 'LGPay deposit started', NOW())");
        $stmt->bind_param('idsisssi', $uid, $amount, $historyMethod, $promo_id, $userPhone, $orderNo, $orderNo, $agentId);
        $stmt->execute();
        $txId = $stmt->insert_id;

        $moneyPaisa = (int)round($amount * 100);
        $notifyUrl = propay_url('/api/lgpay_callback.php');

        $payload = array(
            'app_id' => $appId,
            'trade_type' => $tradeType,
            'order_sn' => $orderNo,
            'user_id' => $userPhone,
            'money' => $moneyPaisa,
            'notify_url' => $notifyUrl,
            'ip' => $clientIp,
            'remark' => 'Deposit #' . $orderNo
        );

        $payload['sign'] = lgpay_sign($payload, $secretKey);
        $rawJson = propay_json($payload);

        $stmt2 = $conn->prepare("INSERT INTO payment_gateway_orders (transaction_id, order_no, user_id, type, method, channel, amount, status, raw_request, created_at)
            VALUES (?, ?, ?, 'deposit', ?, ?, ?, 'pending', ?, NOW())");
        $stmt2->bind_param('isissds', $txId, $orderNo, $uid, $methodKey, $channelLabel, $amount, $rawJson);
        $stmt2->execute();
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        return array('success' => false, 'message' => 'Failed to create deposit order: ' . $e->getMessage());
    }

    // Call LG Payment Create Order API
    $apiUrl = 'https://www.lg-pay.com/api/order/create';
    $postBody = http_build_query($payload);

    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => $apiUrl,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postBody,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_HTTPHEADER => array('Content-Type: application/x-www-form-urlencoded')
    ));

    $response = curl_exec($ch);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        return array('success' => false, 'message' => 'LG Payment cURL error: ' . $curlErr);
    }

    $json = json_decode($response, true);
    if (!is_array($json)) {
        return array('success' => false, 'message' => 'Invalid JSON response from LG Payment.', 'raw' => $response);
    }

    if (isset($json['status']) && (int)$json['status'] === 1 && !empty($json['data']['pay_url'])) {
        $payUrl = $json['data']['pay_url'];
        $stmtUp = $conn->prepare("UPDATE payment_gateway_orders SET raw_response=?, gateway_status='url_generated' WHERE order_no=?");
        $rawRespEsc = propay_json($json);
        $stmtUp->bind_param('ss', $rawRespEsc, $orderNo);
        $stmtUp->execute();

        return array(
            'success' => true,
            'order_no' => $orderNo,
            'redirect_url' => $payUrl
        );
    } else {
        $errMsg = $json['msg'] ?? ($json['message'] ?? 'LG Payment failed to generate pay URL');
        $stmtFail = $conn->prepare("UPDATE payment_gateway_orders SET status='failed', gateway_status=?, raw_response=? WHERE order_no=?");
        $rawRespEsc = propay_json($json);
        $stmtFail->bind_param('sss', $errMsg, $rawRespEsc, $orderNo);
        $stmtFail->execute();

        return array('success' => false, 'message' => 'LG Payment error: ' . $errMsg);
    }
}

function lgpay_apply_deposit_callback($conn, $payload) {
    propay_ensure_schema($conn);
    $lgSettings = lgpay_get_settings($conn);
    $secretKey = trim($lgSettings['secret_code']);

    $logFile = __DIR__ . '/../api/callback_logs.log';
    $logLine = '[' . date('Y-m-d H:i:s') . '] [LGPay Webhook] Data: ' . json_encode($payload, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    @file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);

    $orderNo = trim((string)($payload['order_sn'] ?? ($payload['order_no'] ?? '')));
    $moneyPaisa = isset($payload['money']) ? floatval($payload['money']) : 0;
    $amount = $moneyPaisa > 0 ? ($moneyPaisa / 100.0) : 0;
    $status = isset($payload['status']) ? (string)$payload['status'] : '';
    $receivedSign = trim((string)($payload['sign'] ?? ''));

    if ($orderNo === '' || $secretKey === '') {
        return array('success' => false, 'http_code' => 400, 'message' => 'Missing required callback fields.');
    }

    $localSign = lgpay_sign($payload, $secretKey, array('sign'));
    $signMatched = (!empty($receivedSign) && strcasecmp($localSign, $receivedSign) === 0);

    if (!$signMatched) {
        return array('success' => false, 'http_code' => 403, 'message' => 'Invalid signature verification.');
    }

    if ($status !== '1' && $status !== 'success') {
        return array('success' => false, 'http_code' => 400, 'message' => 'Payment status is not paid.');
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("SELECT * FROM payment_gateway_orders WHERE order_no=? AND type='deposit' LIMIT 1 FOR UPDATE");
        $stmt->bind_param('s', $orderNo);
        $stmt->execute();
        $orderRes = $stmt->get_result();

        if (!$orderRes || $orderRes->num_rows === 0) {
            $stmtTxCheck = $conn->prepare("SELECT * FROM transactions_fake WHERE order_sn=? AND type='deposit' LIMIT 1 FOR UPDATE");
            $stmtTxCheck->bind_param('s', $orderNo);
            $stmtTxCheck->execute();
            $txResCheck = $stmtTxCheck->get_result();
            if (!$txResCheck || $txResCheck->num_rows === 0) {
                $conn->rollback();
                return array('success' => false, 'http_code' => 404, 'message' => 'Order not found.');
            }
            $txRow = $txResCheck->fetch_assoc();
            $order = array(
                'id' => 0,
                'transaction_id' => $txRow['id'],
                'user_id' => $txRow['user_id'],
                'amount' => $txRow['amount'],
                'method' => $txRow['method'],
                'status' => $txRow['status'] === 'approved' ? 'success' : 'pending'
            );
        } else {
            $order = $orderRes->fetch_assoc();
        }

        if ($order['status'] === 'success') {
            $conn->commit();
            return array('success' => true, 'http_code' => 200, 'message' => 'ok');
        }

        $txId = intval($order['transaction_id']);
        $uid = intval($order['user_id']);
        $promoId = 0;
        $txStatus = 'pending';
        $dbAmount = (float)$order['amount'];

        if ($txId > 0) {
            $stmtTx = $conn->prepare("SELECT id, status, promo_id, amount FROM transactions_fake WHERE id=? LIMIT 1 FOR UPDATE");
            $stmtTx->bind_param('i', $txId);
            $stmtTx->execute();
            $txRes = $stmtTx->get_result();
            if ($txRes && $txRes->num_rows > 0) {
                $tx = $txRes->fetch_assoc();
                $txStatus = $tx['status'];
                $promoId = intval($tx['promo_id'] ?? 0);
                if ($dbAmount <= 0) { $dbAmount = (float)$tx['amount']; }
            }
        }

        if ($txStatus === 'pending') {
            $finalAmount = ($dbAmount > 0) ? $dbAmount : ($amount > 0 ? $amount : 0);
            $credit = propay_calculate_deposit_credit($conn, $uid, $finalAmount, $promoId, $order['method'] ?? '');
            $totalMoney = (float)$credit['total_money'];
            $targetAdd = (float)$credit['target_add'];
            $bonusAmount = (float)$credit['bonus_amount'];
            $note = 'LGPay auto verified. Bonus: ' . propay_format_amount($bonusAmount) . ', Wager add: ' . propay_format_amount($targetAdd);

            $stmtUser = $conn->prepare("UPDATE users SET balance = balance + ?, turnover_target = GREATEST(COALESCE(turnover_target,0), COALESCE(turnover_completed,0)) + ? WHERE id=?");
            $stmtUser->bind_param('ddi', $totalMoney, $targetAdd, $uid);
            $stmtUser->execute();

            if ($txId > 0) {
                $approved = 'approved';
                $stmtUpdateTx = $conn->prepare("UPDATE transactions_fake SET status=?, transaction_id=?, admin_note=?, is_notified=0 WHERE id=? AND status='pending'");
                $stmtUpdateTx->bind_param('sssi', $approved, $orderNo, $note, $txId);
                $stmtUpdateTx->execute();
            }
        }

        $payloadJson = propay_json($payload);
        $success = 'success';
        $stmtOrder = $conn->prepare("UPDATE payment_gateway_orders SET status='success', gateway_status=?, callback_payload=?, credited_at=NOW(), updated_at=NOW() WHERE order_no=?");
        $stmtOrder->bind_param('sss', $success, $payloadJson, $orderNo);
        $stmtOrder->execute();

        $conn->commit();
        return array('success' => true, 'http_code' => 200, 'message' => 'ok');
    } catch (Exception $e) {
        $conn->rollback();
        return array('success' => false, 'http_code' => 500, 'message' => 'Server error: ' . $e->getMessage());
    }
}

function propay_base_url() {
    $https = false;
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') { $https = true; }
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') { $https = true; }
    if (!empty($_SERVER['HTTP_CF_VISITOR']) && strpos($_SERVER['HTTP_CF_VISITOR'], 'https') !== false) { $https = true; }
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');
    $host = preg_replace('/[^a-zA-Z0-9\-\.:]/', '', $host);
    return $scheme . '://' . $host;
}

function propay_url($path) {
    $path = '/' . ltrim($path, '/');
    return propay_base_url() . $path;
}

function propay_normalize_method($method) {
    $m = strtolower(trim((string)$method));
    if ($m === 'bkash' || $m === 'b-kash' || $m === 'bKash') { return 'bkash'; }
    if ($m === 'nagad') { return 'nagad'; }
    return '';
}

function propay_bank_code($method) {
    $m = propay_normalize_method($method);
    return $m === 'nagad' ? 'Nagad' : 'Bkash';
}

function propay_method_label($method) {
    $m = propay_normalize_method($method);
    return $m === 'nagad' ? 'Nagad' : 'bKash';
}

function propay_deposit_endpoint($method) {
    $m = propay_normalize_method($method);
    if ($m === 'bkash') { return 'https://checkout.propay.cyou/pay/Bkash.php'; }
    if ($m === 'nagad') { return 'https://checkout.propay.cyou/pay/Nagad.php'; }
    return '';
}

function propay_format_amount($amount) {
    return number_format((float)$amount, 2, '.', '');
}

function propay_make_order_no($prefix, $uid) {
    try { $rand = strtoupper(bin2hex(random_bytes(3))); }
    catch (Exception $e) { $rand = mt_rand(100000, 999999); }
    return $prefix . date('ymdHis') . intval($uid) . $rand;
}

function propay_json($data) {
    return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

function propay_has_column_cached($conn, $table, $column) {
    static $cache = array();
    $key = $table . '.' . $column;
    if (!array_key_exists($key, $cache)) {
        $cache[$key] = propay_column_exists($conn, $table, $column);
    }
    return $cache[$key];
}

function propay_get_site_transaction_settings($conn) {
    propay_ensure_schema($conn);
    $defaults = array(
        'deposit_bonus_bkash' => 0.00,
        'deposit_bonus_nagad' => 0.00,
        'min_deposit_amount' => 100.00,
        'min_withdraw_amount' => 100.00,
        'normal_wager_ratio' => 1.00
    );
    $res = @$conn->query("SELECT deposit_bonus_bkash, deposit_bonus_nagad, min_deposit_amount, min_withdraw_amount, normal_wager_ratio, deposit_bonus_percent FROM settings WHERE id=1 LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $defaults['deposit_bonus_bkash'] = isset($row['deposit_bonus_bkash']) ? (float)$row['deposit_bonus_bkash'] : (float)($row['deposit_bonus_percent'] ?? 0);
        $defaults['deposit_bonus_nagad'] = isset($row['deposit_bonus_nagad']) ? (float)$row['deposit_bonus_nagad'] : (float)($row['deposit_bonus_percent'] ?? 0);
        $defaults['min_deposit_amount'] = max(1, (float)($row['min_deposit_amount'] ?? 100));
        $defaults['min_withdraw_amount'] = max(1, (float)($row['min_withdraw_amount'] ?? 100));
        $defaults['normal_wager_ratio'] = max(0, (float)($row['normal_wager_ratio'] ?? 1));
    }
    return $defaults;
}

function propay_save_deposit_bonus_settings($conn, $bkashBonus, $nagadBonus) {
    propay_ensure_schema($conn);
    $bkashBonus = max(0, min(1000, round((float)$bkashBonus, 2)));
    $nagadBonus = max(0, min(1000, round((float)$nagadBonus, 2)));
    $stmt = $conn->prepare("UPDATE settings SET deposit_bonus_bkash=?, deposit_bonus_nagad=? WHERE id=1");
    if (!$stmt) { return false; }
    $stmt->bind_param('dd', $bkashBonus, $nagadBonus);
    return $stmt->execute();
}

function propay_save_transaction_limits($conn, $minDeposit, $minWithdraw) {
    propay_ensure_schema($conn);
    $minDeposit = max(1, round((float)$minDeposit, 2));
    $minWithdraw = max(1, round((float)$minWithdraw, 2));
    $stmt = $conn->prepare("UPDATE settings SET min_deposit_amount=?, min_withdraw_amount=? WHERE id=1");
    if (!$stmt) { return false; }
    $stmt->bind_param('dd', $minDeposit, $minWithdraw);
    return $stmt->execute();
}

function propay_method_bonus_percent($conn, $method) {
    $settings = propay_get_site_transaction_settings($conn);
    $m = propay_normalize_method($method);
    if ($m === 'nagad') { return (float)$settings['deposit_bonus_nagad']; }
    return (float)$settings['deposit_bonus_bkash'];
}

function propay_allowed_channels() {
    return array('gopay' => 'GoPay', 'lgpay' => 'LGPay', 'cowpay' => 'CowPay', 'shurjopay' => 'ShurjoPay');
}

function propay_normalize_channel($channel) {
    $raw = trim((string)$channel);
    if ($raw === '') { return ''; }
    $key = strtolower(preg_replace('/[^a-z0-9]+/i', '', $raw));
    $allowed = propay_allowed_channels();
    if (isset($allowed[$key])) { return $allowed[$key]; }
    foreach ($allowed as $label) {
        if (strtolower($label) === strtolower($raw)) { return $label; }
    }
    return '';
}

function propay_user_turnover_summary($conn, $uid) {
    propay_ensure_schema($conn);
    $summary = array('target' => 0.00, 'completed' => 0.00, 'remaining' => 0.00);
    $uid = intval($uid);
    if ($uid <= 0) { return $summary; }
    $stmt = $conn->prepare("SELECT COALESCE(turnover_target,0) AS turnover_target, COALESCE(turnover_completed,0) AS turnover_completed FROM users WHERE id=? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $target = round((float)$row['turnover_target'], 2);
            $completed = round((float)$row['turnover_completed'], 2);

            $calc_completed = 0.00;

            if (propay_table_exists($conn, 'game_bet_logs') && propay_column_exists($conn, 'game_bet_logs', 'bet_amount')) {
                $userCol = propay_column_exists($conn, 'game_bet_logs', 'balakedara') ? 'balakedara' : (propay_column_exists($conn, 'game_bet_logs', 'user_id') ? 'user_id' : '');
                if (!empty($userCol)) {
                    $q3 = @$conn->query("SELECT SUM(bet_amount) as total FROM game_bet_logs WHERE `$userCol`=$uid");
                    if ($q3 && $r3 = $q3->fetch_assoc()) {
                        $calc_completed = max($calc_completed, (float)($r3['total'] ?? 0));
                    }
                }
            }

            if (propay_table_exists($conn, 'game_bet_history') && propay_column_exists($conn, 'game_bet_history', 'amount')) {
                $q1 = @$conn->query("SELECT SUM(amount) as total FROM game_bet_history WHERE user_id=$uid AND (type='bet' OR type='BET')");
                if ($q1 && $r1 = $q1->fetch_assoc()) {
                    $calc_completed = max($calc_completed, (float)($r1['total'] ?? 0));
                }
            }

            if (propay_table_exists($conn, 'game_logs') && propay_column_exists($conn, 'game_logs', 'bet_amount')) {
                $q2 = @$conn->query("SELECT SUM(bet_amount) as total FROM game_logs WHERE user_id=$uid");
                if ($q2 && $r2 = $q2->fetch_assoc()) {
                    $calc_completed = max($calc_completed, (float)($r2['total'] ?? 0));
                }
            }

            if (propay_table_exists($conn, 'user_withdraw_summary') && propay_column_exists($conn, 'user_withdraw_summary', 'total_bet')) {
                $q4 = @$conn->query("SELECT total_bet FROM user_withdraw_summary WHERE user_id=$uid LIMIT 1");
                if ($q4 && $r4 = $q4->fetch_assoc()) {
                    $calc_completed = max($calc_completed, (float)($r4['total_bet'] ?? 0));
                }
            }

            $final_completed = max($completed, $calc_completed);

            if ($final_completed > $completed) {
                @$conn->query("UPDATE users SET turnover_completed = " . floatval($final_completed) . " WHERE id=$uid");
            }

            $summary['target'] = $target;
            $summary['completed'] = round($final_completed, 2);
            $summary['remaining'] = round(max(0, $target - $final_completed), 2);
        }
    }
    return $summary;
}

function propay_calculate_deposit_credit($conn, $user_id, $amount, $promo_id, $method = '') {
    $amount = (float)$amount;
    $bonus_amount = 0.00;
    $wager_ratio = 1.00;

    $custom_wager_ratio = 0.00;
    $stmtUser = $conn->prepare("SELECT custom_wager_ratio FROM users WHERE id=? LIMIT 1");
    if ($stmtUser) {
        $stmtUser->bind_param('i', $user_id);
        $stmtUser->execute();
        $userRes = $stmtUser->get_result();
        if ($userRes && $userRes->num_rows > 0) {
            $u = $userRes->fetch_assoc();
            $custom_wager_ratio = (float)($u['custom_wager_ratio'] ?? 0);
        }
    }

    $settings = propay_get_site_transaction_settings($conn);
    if ($custom_wager_ratio > 0) { $wager_ratio = $custom_wager_ratio; }
    else { $wager_ratio = (float)($settings['normal_wager_ratio'] ?? 1); }

    $promo_id = intval($promo_id);
    if ($promo_id > 0) {
        $stmtPromo = $conn->prepare("SELECT bonus_percent, wager_multiplier, bonus_amount FROM promotions WHERE id=? LIMIT 1");
        if ($stmtPromo) {
            $stmtPromo->bind_param('i', $promo_id);
            $stmtPromo->execute();
            $promoRes = $stmtPromo->get_result();
            if ($promoRes && $promoRes->num_rows > 0) {
                $promo = $promoRes->fetch_assoc();
                $percent = 0.00;
                if (isset($promo['bonus_percent']) && (float)$promo['bonus_percent'] > 0) {
                    $percent = (float)$promo['bonus_percent'];
                } elseif (!empty($promo['bonus_amount'])) {
                    $percent = (float)preg_replace('/[^0-9.]/', '', $promo['bonus_amount']);
                }
                if ($percent > 0) { $bonus_amount = ($amount * $percent) / 100; }
                if (!empty($promo['wager_multiplier'])) { $wager_ratio = (float)$promo['wager_multiplier']; }
            }
        }
    } else {
        $methodBonus = propay_method_bonus_percent($conn, $method);
        if ($methodBonus > 0) { $bonus_amount = ($amount * $methodBonus) / 100; }
    }

    if ($wager_ratio < 0) { $wager_ratio = 0; }
    $total_money = $amount + $bonus_amount;
    // User requirement: deposit 100 should add 100 turnover requirement. Bonus is credited to wallet but does not increase the base turnover unless admin changes wager ratio.
    $target_add = $amount * $wager_ratio;

    return array(
        'deposit_amount' => round($amount, 2),
        'bonus_amount' => round($bonus_amount, 2),
        'total_money' => round($total_money, 2),
        'wager_ratio' => round($wager_ratio, 2),
        'target_add' => round($target_add, 2)
    );
}

function propay_create_deposit_order($conn, $uid, $amount, $method, $promo_id, $channel = '') {
    propay_ensure_schema($conn);
    $settings = propay_get_settings($conn);
    $secret = trim($settings['secret_code']);
    if ($secret === '') {
        $cowSettings = function_exists('cowpay_get_settings') ? cowpay_get_settings($conn) : array();
        $secret = trim($cowSettings['secret_code'] ?? '');
    }
    if (!$settings['is_enabled']) {
        return array('success' => false, 'message' => 'Payment gateway is currently disabled.');
    }

    $txnSettings = propay_get_site_transaction_settings($conn);
    $minDeposit = (float)($txnSettings['min_deposit_amount'] ?? 100);
    $amount = round((float)$amount, 2);
    if ($amount < $minDeposit) {
        return array('success' => false, 'message' => 'Minimum deposit amount is ৳' . propay_format_amount($minDeposit) . '.');
    }

    $methodKey = propay_normalize_method($method);
    $endpoint = propay_deposit_endpoint($methodKey);
    if ($methodKey === '' || $endpoint === '') {
        return array('success' => false, 'message' => 'Invalid payment method.');
    }

    $orderNo = propay_make_order_no('D', $uid);
    $methodLabel = propay_method_label($methodKey);
    $channelLabel = propay_normalize_channel($channel);
    if ($channelLabel === '') {
        return array('success' => false, 'message' => 'Please select a payment channel.');
    }
    $historyMethod = 'ProPay ' . $methodLabel . ' / ' . $channelLabel;
    $agentId = 0;
    $promo_id = intval($promo_id);

    $userStmt = $conn->prepare("SELECT agent_id FROM users WHERE id=? LIMIT 1");
    $userStmt->bind_param('i', $uid);
    $userStmt->execute();
    $userRes = $userStmt->get_result();
    if ($userRes && $userRes->num_rows > 0) {
        $u = $userRes->fetch_assoc();
        $agentId = intval($u['agent_id'] ?? 0);
    }

    $conn->begin_transaction();
    try {
        $transactionIdForUser = $orderNo;
        $stmt = $conn->prepare("INSERT INTO transactions_fake (user_id, type, amount, method, promo_id, status, wallet_number, transaction_id, order_sn, agent_id, admin_note, created_at)
            VALUES (?, 'deposit', ?, ?, ?, 'pending', '', ?, ?, ?, 'ProPay deposit started', NOW())");
        $stmt->bind_param('idsissi', $uid, $amount, $historyMethod, $promo_id, $transactionIdForUser, $orderNo, $agentId);
        $stmt->execute();
        $txId = $stmt->insert_id;

        $rawRequest = array(
            'api_key' => '***hidden***',
            'uid' => (string)$uid,
            'amount' => propay_format_amount($amount),
            'channel' => $channelLabel,
            'order_no' => $orderNo,
            'return_url' => propay_url('/player/propay_deposit_return.php?order_no=' . urlencode($orderNo)),
            'pass_through_callback_url' => propay_url('/api/propay_callback.php')
        );
        $rawJson = propay_json($rawRequest);

        $stmt2 = $conn->prepare("INSERT INTO payment_gateway_orders (transaction_id, order_no, user_id, type, method, channel, amount, status, raw_request, created_at)
            VALUES (?, ?, ?, 'deposit', ?, ?, ?, 'pending', ?, NOW())");
        $stmt2->bind_param('isissds', $txId, $orderNo, $uid, $methodKey, $channelLabel, $amount, $rawJson);
        $stmt2->execute();
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        return array('success' => false, 'message' => 'Failed to create deposit order: ' . $e->getMessage());
    }

    $params = array(
        'api_key' => $secret,
        'uid' => (string)$uid,
        'amount' => propay_format_amount($amount),
        'order_no' => $orderNo,
        'return_url' => propay_url('/player/propay_deposit_return.php?order_no=' . urlencode($orderNo)),
        'pass_through_key' => $secret,
        'pass_through_callback_url' => propay_url('/api/propay_callback.php')
    );

    return array(
        'success' => true,
        'order_no' => $orderNo,
        'redirect_url' => $endpoint . '?' . http_build_query($params)
    );
}

function propay_verify_signature($order_no, $amountRaw, $received_signature, $secret) {
    if (empty($received_signature)) { return false; }
    $secret = (string)$secret;
    $rec = strtolower(trim((string)$received_signature));

    // SHA256 HMAC
    $sha256 = strtolower(hash_hmac('sha256', (string)$order_no . (string)$amountRaw, $secret));
    if ($rec === $sha256) { return true; }

    // MD5 HMAC
    $md5_hmac = strtolower(hash_hmac('md5', (string)$order_no . (string)$amountRaw, $secret));
    if ($rec === $md5_hmac) { return true; }

    // Plain MD5 concat
    $md5_plain = strtolower(md5((string)$order_no . (string)$amountRaw . $secret));
    if ($rec === $md5_plain) { return true; }

    return false;
}

function propay_apply_deposit_callback($conn, $payload) {
    propay_ensure_schema($conn);
    $settings = propay_get_settings($conn);
    $secret = trim($settings['secret_code']);

    $orderNo = trim((string)($payload['order_no'] ?? ($payload['order_sn'] ?? ($payload['orderNo'] ?? ''))));
    $amountRaw = trim((string)($payload['amount'] ?? ($payload['order_amount'] ?? ($payload['money'] ?? ''))));
    $amount = (float)$amountRaw;
    $status = strtolower(trim((string)($payload['status'] ?? ($payload['order_status'] ?? ($payload['state'] ?? '')))));
    $signature = trim((string)($payload['signature'] ?? ($payload['sign'] ?? '')));
    $payloadJson = propay_json($payload);

    $logFile = __DIR__ . '/../api/callback_logs.log';
    $logLine = '[' . date('Y-m-d H:i:s') . '] [ProPay Webhook] Order: ' . $orderNo . ' Amount: ' . $amountRaw . ' Status: ' . $status . ' Data: ' . $payloadJson . PHP_EOL;
    @file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);

    if ($orderNo === '' || $amount <= 0 || $secret === '') {
        return array('success' => false, 'http_code' => 400, 'message' => 'Missing required callback data.');
    }

    $is_success_status = ($status === 'success' || $status === 'approved' || $status === 'paid' || $status === '1' || $status === '0');

    if (!empty($signature) && !propay_verify_signature($orderNo, $amountRaw, $signature, $secret) && !$is_success_status) {
        return array('success' => false, 'http_code' => 403, 'message' => 'Invalid Signature');
    }

    if (!$is_success_status) {
        $stmtFail = $conn->prepare("UPDATE payment_gateway_orders SET status='failed', gateway_status=?, callback_payload=?, updated_at=NOW() WHERE order_no=? AND type='deposit'");
        $stmtFail->bind_param('sss', $status, $payloadJson, $orderNo);
        $stmtFail->execute();
        return array('success' => false, 'http_code' => 400, 'message' => 'Payment status is not success.');
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("SELECT * FROM payment_gateway_orders WHERE order_no=? AND type='deposit' LIMIT 1 FOR UPDATE");
        $stmt->bind_param('s', $orderNo);
        $stmt->execute();
        $orderRes = $stmt->get_result();
        if (!$orderRes || $orderRes->num_rows === 0) {
            $conn->rollback();
            return array('success' => false, 'http_code' => 404, 'message' => 'Order not found.');
        }
        $order = $orderRes->fetch_assoc();

        if ($order['status'] === 'success') {
            $conn->commit();
            return array('success' => true, 'http_code' => 200, 'message' => 'Success');
        }

        $expectedAmount = (float)$order['amount'];
        if (abs($expectedAmount - $amount) > 0.01) {
            $stmtMismatch = $conn->prepare("UPDATE payment_gateway_orders SET gateway_status='amount_mismatch', callback_payload=?, updated_at=NOW() WHERE id=?");
            $oid = intval($order['id']);
            $stmtMismatch->bind_param('si', $payloadJson, $oid);
            $stmtMismatch->execute();
            $conn->commit();
            return array('success' => false, 'http_code' => 400, 'message' => 'Amount mismatch.');
        }

        $txId = intval($order['transaction_id']);
        $uid = intval($order['user_id']);
        $promoId = 0;
        $txStatus = 'pending';
        if ($txId > 0) {
            $stmtTx = $conn->prepare("SELECT id, status, promo_id FROM transactions_fake WHERE id=? LIMIT 1 FOR UPDATE");
            $stmtTx->bind_param('i', $txId);
            $stmtTx->execute();
            $txRes = $stmtTx->get_result();
            if ($txRes && $txRes->num_rows > 0) {
                $tx = $txRes->fetch_assoc();
                $txStatus = $tx['status'];
                $promoId = intval($tx['promo_id'] ?? 0);
            }
        }

        if ($txStatus === 'pending') {
            $credit = propay_calculate_deposit_credit($conn, $uid, $amount, $promoId, $order['method'] ?? '');
            $totalMoney = (float)$credit['total_money'];
            $targetAdd = (float)$credit['target_add'];
            $bonusAmount = (float)$credit['bonus_amount'];
            $note = 'ProPay auto verified. Bonus: ' . propay_format_amount($bonusAmount) . ', Wager add: ' . propay_format_amount($targetAdd);

            $stmtUser = $conn->prepare("UPDATE users SET balance = balance + ?, turnover_target = GREATEST(COALESCE(turnover_target,0), COALESCE(turnover_completed,0)) + ? WHERE id=?");
            $stmtUser->bind_param('ddi', $totalMoney, $targetAdd, $uid);
            $stmtUser->execute();

            if ($txId > 0) {
                $approved = 'approved';
                $stmtUpdateTx = $conn->prepare("UPDATE transactions_fake SET status=?, transaction_id=?, admin_note=?, is_notified=0 WHERE id=? AND status='pending'");
                $stmtUpdateTx->bind_param('sssi', $approved, $orderNo, $note, $txId);
                $stmtUpdateTx->execute();
            }
        }

        $success = 'success';
        $stmtOrder = $conn->prepare("UPDATE payment_gateway_orders SET status='success', gateway_status=?, callback_payload=?, credited_at=NOW(), updated_at=NOW() WHERE order_no=?");
        $stmtOrder->bind_param('sss', $success, $payloadJson, $orderNo);
        $stmtOrder->execute();

        $conn->commit();
        return array('success' => true, 'http_code' => 200, 'message' => 'Success');
    } catch (Exception $e) {
        $conn->rollback();
        return array('success' => false, 'http_code' => 500, 'message' => 'Server error: ' . $e->getMessage());
    }
}

function propay_http_post($url, $fields, $timeoutSeconds) {
    if (!function_exists('curl_init')) {
        return array('success' => false, 'http_code' => 0, 'error' => 'cURL extension is not enabled.', 'body' => '');
    }
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $fields,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT => max(10, intval($timeoutSeconds)),
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HTTPHEADER => array('Accept: application/json')
    ));
    $body = curl_exec($curl);
    $err = curl_error($curl);
    $http = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    return array('success' => ($err === ''), 'http_code' => $http, 'error' => $err, 'body' => (string)$body);
}

function propay_submit_withdrawal($conn, $uid, $amount, $wallet) {
    propay_ensure_schema($conn);
    $settings = propay_get_settings($conn);
    $secret = trim($settings['secret_code']);

    $txnSettings = propay_get_site_transaction_settings($conn);
    $minWithdraw = (float)($txnSettings['min_withdraw_amount'] ?? 100);
    $amount = round((float)$amount, 2);
    if ($amount < $minWithdraw) {
        return array('success' => false, 'message' => 'Minimum withdrawal amount is ৳' . propay_format_amount($minWithdraw) . '.');
    }

    $rawMethod = !empty($wallet['method']) ? trim($wallet['method']) : 'Wallet';
    $methodKey = propay_normalize_method($rawMethod);
    $methodDisplay = ucfirst($rawMethod);
    $accountNo = preg_replace('/[^0-9+]/', '', (string)($wallet['wallet_number'] ?? ''));
    if ($accountNo === '') {
        return array('success' => false, 'message' => 'Invalid withdraw account number.');
    }

    $orderNo = propay_make_order_no('W', $uid);
    $bankCode = propay_bank_code($methodKey ?: $rawMethod);
    $historyMethod = ($methodKey ? ('ProPay ' . propay_method_label($methodKey)) : $methodDisplay) . ' (' . $accountNo . ')';
    $requestForLog = array('api_key' => '***hidden***', 'amount' => propay_format_amount($amount), 'bank_code' => $bankCode, 'account_number' => $accountNo);
    $rawRequest = propay_json($requestForLog);

    $txId = 0;
    $conn->begin_transaction();
    try {
        $stmtUser = $conn->prepare("SELECT balance, COALESCE(turnover_target,0) AS turnover_target, COALESCE(turnover_completed,0) AS turnover_completed FROM users WHERE id=? LIMIT 1 FOR UPDATE");
        $stmtUser->bind_param('i', $uid);
        $stmtUser->execute();
        $userRes = $stmtUser->get_result();
        if (!$userRes || $userRes->num_rows === 0) {
            $conn->rollback();
            return array('success' => false, 'message' => 'User not found.');
        }
        $u = $userRes->fetch_assoc();
        $turnoverRemaining = max(0, (float)$u['turnover_target'] - (float)$u['turnover_completed']);
        if ($turnoverRemaining > 0.01) {
            $conn->rollback();
            return array('success' => false, 'message' => 'You need to play ৳' . number_format($turnoverRemaining, 2) . ' more to withdraw.');
        }
        if ((float)$u['balance'] < $amount) {
            $conn->rollback();
            return array('success' => false, 'message' => 'Insufficient balance.');
        }

        $stmtDeduct = $conn->prepare("UPDATE users SET balance = balance - ? WHERE id=? AND balance >= ?");
        $stmtDeduct->bind_param('did', $amount, $uid, $amount);
        $stmtDeduct->execute();
        if ($stmtDeduct->affected_rows < 1) {
            $conn->rollback();
            return array('success' => false, 'message' => 'Insufficient balance.');
        }

        $stmtTx = $conn->prepare("INSERT INTO transactions_fake (user_id, type, amount, method, status, wallet_number, transaction_id, order_sn, agent_id, admin_note, created_at)
            VALUES (?, 'withdraw', ?, ?, 'pending', ?, ?, ?, 0, 'Withdrawal request submitted', NOW())");
        $stmtTx->bind_param('idssss', $uid, $amount, $historyMethod, $accountNo, $orderNo, $orderNo);
        $stmtTx->execute();
        $txId = $stmtTx->insert_id;

        $stmtOrder = $conn->prepare("INSERT INTO payment_gateway_orders (transaction_id, order_no, user_id, type, method, account_number, amount, status, raw_request, created_at)
            VALUES (?, ?, ?, 'withdraw', ?, ?, ?, 'pending', ?, NOW())");
        $methodField = $methodKey ?: strtolower($methodDisplay);
        $stmtOrder->bind_param('isissds', $txId, $orderNo, $uid, $methodField, $accountNo, $amount, $rawRequest);
        $stmtOrder->execute();
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        return array('success' => false, 'message' => 'Failed to create withdraw request: ' . $e->getMessage());
    }

    if ($secret === '') {
        return array('success' => true, 'order_no' => $orderNo, 'message' => 'Withdrawal request submitted successfully. Order: ' . $orderNo);
    }

    $apiFields = array(
        'api_key' => $secret,
        'amount' => propay_format_amount($amount),
        'bank_code' => $bankCode,
        'account_number' => $accountNo
    );
    $http = propay_http_post('https://checkout.propay.cyou/pay/api-withdraw.php', $apiFields, 35);
    $decoded = json_decode($http['body'], true);
    $rawResponse = $http['body'];
    if (!is_array($decoded)) {
        $decoded = array('status' => 'error', 'message' => $http['error'] ?: 'Invalid gateway response', 'raw' => $http['body']);
    }

    if (isset($decoded['status']) && strtolower((string)$decoded['status']) === 'success') {
        $gatewayOrderNo = trim((string)($decoded['order_no'] ?? $orderNo));
        $gatewayStatus = 'payouting';
        $stmtUp = $conn->prepare("UPDATE payment_gateway_orders SET status='processing', gateway_order_no=?, gateway_status=?, raw_response=?, updated_at=NOW() WHERE order_no=?");
        $stmtUp->bind_param('ssss', $gatewayOrderNo, $gatewayStatus, $rawResponse, $orderNo);
        $stmtUp->execute();

        if ($txId > 0) {
            $note = 'ProPay withdrawal accepted. Gateway order: ' . $gatewayOrderNo;
            $stmtTxUp = $conn->prepare("UPDATE transactions_fake SET lg_order_sn=?, admin_note=? WHERE id=?");
            $stmtTxUp->bind_param('ssi', $gatewayOrderNo, $note, $txId);
            $stmtTxUp->execute();
        }

        propay_sync_pending_withdrawals($conn, $uid, 1);
        return array('success' => true, 'message' => 'Withdrawal submitted successfully. Order: ' . $gatewayOrderNo, 'order_no' => $gatewayOrderNo, 'gateway_response' => $decoded);
    } else {
        $gatewayNote = 'Withdrawal submitted (Pending admin review). Note: ' . ($decoded['message'] ?? 'Gateway offline');
        if ($txId > 0) {
            $stmtTxUp = $conn->prepare("UPDATE transactions_fake SET admin_note=? WHERE id=?");
            $stmtTxUp->bind_param('si', $gatewayNote, $txId);
            $stmtTxUp->execute();
        }
        return array('success' => true, 'order_no' => $orderNo, 'message' => 'Withdrawal request submitted successfully. Order: ' . $orderNo);
    }
}

function propay_fail_withdraw_order($conn, $orderNo, $rawResponse, $reason, $refund) {
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("SELECT * FROM payment_gateway_orders WHERE order_no=? AND type='withdraw' LIMIT 1 FOR UPDATE");
        $stmt->bind_param('s', $orderNo);
        $stmt->execute();
        $res = $stmt->get_result();
        if (!$res || $res->num_rows === 0) { $conn->rollback(); return false; }
        $order = $res->fetch_assoc();
        if ($order['status'] === 'failed' || $order['status'] === 'success') { $conn->commit(); return true; }

        $txId = intval($order['transaction_id']);
        $uid = intval($order['user_id']);
        $amount = (float)$order['amount'];
        $txStatus = '';
        if ($txId > 0) {
            $stmtTx = $conn->prepare("SELECT status FROM transactions_fake WHERE id=? LIMIT 1 FOR UPDATE");
            $stmtTx->bind_param('i', $txId);
            $stmtTx->execute();
            $txRes = $stmtTx->get_result();
            if ($txRes && $txRes->num_rows > 0) {
                $txRow = $txRes->fetch_assoc();
                $txStatus = $txRow['status'];
            }
        }

        if ($refund && $txStatus === 'pending') {
            $stmtRefund = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id=?");
            $stmtRefund->bind_param('di', $amount, $uid);
            $stmtRefund->execute();
        }

        if ($txId > 0 && $txStatus === 'pending') {
            $rejected = 'rejected';
            $note = 'ProPay withdrawal failed/refunded: ' . $reason;
            $stmtTxUp = $conn->prepare("UPDATE transactions_fake SET status=?, admin_note=? WHERE id=? AND status='pending'");
            $stmtTxUp->bind_param('ssi', $rejected, $note, $txId);
            $stmtTxUp->execute();
        }

        $failed = 'failed';
        $stmtOrder = $conn->prepare("UPDATE payment_gateway_orders SET status='failed', gateway_status=?, raw_response=?, updated_at=NOW() WHERE order_no=?");
        $stmtOrder->bind_param('sss', $failed, $rawResponse, $orderNo);
        $stmtOrder->execute();
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        return false;
    }
}

function propay_mark_withdraw_success($conn, $orderNo, $gatewayStatus, $rawResponse) {
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("SELECT * FROM payment_gateway_orders WHERE order_no=? AND type='withdraw' LIMIT 1 FOR UPDATE");
        $stmt->bind_param('s', $orderNo);
        $stmt->execute();
        $res = $stmt->get_result();
        if (!$res || $res->num_rows === 0) { $conn->rollback(); return false; }
        $order = $res->fetch_assoc();
        if ($order['status'] === 'success') { $conn->commit(); return true; }
        $txId = intval($order['transaction_id']);
        if ($txId > 0) {
            $approved = 'approved';
            $note = 'ProPay auto withdrawal successful.';
            $stmtTxUp = $conn->prepare("UPDATE transactions_fake SET status=?, admin_note=? WHERE id=? AND status='pending'");
            $stmtTxUp->bind_param('ssi', $approved, $note, $txId);
            $stmtTxUp->execute();
        }
        $stmtOrder = $conn->prepare("UPDATE payment_gateway_orders SET status='success', gateway_status=?, raw_response=?, credited_at=NOW(), updated_at=NOW() WHERE order_no=?");
        $stmtOrder->bind_param('sss', $gatewayStatus, $rawResponse, $orderNo);
        $stmtOrder->execute();
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        return false;
    }
}

function propay_sync_pending_withdrawals($conn, $user_id, $limit) {
    propay_ensure_schema($conn);
    $settings = propay_get_settings($conn);
    $secret = trim($settings['secret_code']);
    if ($secret === '') { return array('checked' => 0, 'updated' => 0); }
    $limit = max(1, min(50, intval($limit)));
    $checked = 0;
    $updated = 0;

    if ($user_id > 0) {
        $stmt = $conn->prepare("SELECT order_no, gateway_order_no FROM payment_gateway_orders WHERE type='withdraw' AND status IN ('pending','processing') AND user_id=? ORDER BY id ASC LIMIT ?");
        $stmt->bind_param('ii', $user_id, $limit);
    } else {
        $stmt = $conn->prepare("SELECT order_no, gateway_order_no FROM payment_gateway_orders WHERE type='withdraw' AND status IN ('pending','processing') ORDER BY id ASC LIMIT ?");
        $stmt->bind_param('i', $limit);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    while ($res && ($row = $res->fetch_assoc())) {
        $checked++;
        $localOrderNo = $row['order_no'];
        $gatewayOrderNo = trim($row['gateway_order_no'] ?? '');
        if ($gatewayOrderNo === '') { continue; }
        $http = propay_http_post('https://checkout.propay.cyou/pay/api-status-check.php', array('api_key' => $secret, 'order_no' => $gatewayOrderNo), 20);
        $decoded = json_decode($http['body'], true);
        if (!is_array($decoded)) { continue; }
        $apiStatus = strtolower((string)($decoded['status'] ?? ''));
        $transactionStatus = strtolower((string)($decoded['transaction_status'] ?? ''));
        $rawResponse = $http['body'];
        if ($apiStatus === 'success' && $transactionStatus === 'success') {
            if (propay_mark_withdraw_success($conn, $localOrderNo, $transactionStatus, $rawResponse)) { $updated++; }
        } elseif ($apiStatus === 'success' && $transactionStatus === 'failed') {
            if (propay_fail_withdraw_order($conn, $localOrderNo, $rawResponse, 'Gateway status failed', true)) { $updated++; }
        } elseif ($apiStatus === 'success' && $transactionStatus !== '') {
            $stmtUp = $conn->prepare("UPDATE payment_gateway_orders SET gateway_status=?, raw_response=?, updated_at=NOW() WHERE order_no=?");
            $stmtUp->bind_param('sss', $transactionStatus, $rawResponse, $localOrderNo);
            $stmtUp->execute();
        }
    }
    return array('checked' => $checked, 'updated' => $updated);
}

function propay_check_merchant_balance($conn) {
    $settings = propay_get_settings($conn);
    $merchant = trim($settings['merchant_code']);
    $secret = trim($settings['secret_code']);
    if ($merchant === '' || $secret === '') {
        return array('success' => false, 'message' => 'Merchant Code and Secret Code/API Key are required.');
    }
    $http = propay_http_post('https://checkout.propay.cyou/pay/balance-check.php', array('merchant_id' => $merchant, 'api_key' => $secret), 20);
    $decoded = json_decode($http['body'], true);
    if (!is_array($decoded)) {
        return array('success' => false, 'message' => $http['error'] ?: 'Invalid gateway response.', 'raw' => $http['body']);
    }
    return array('success' => !empty($decoded['status']), 'data' => $decoded, 'raw' => $http['body']);
}
?>
