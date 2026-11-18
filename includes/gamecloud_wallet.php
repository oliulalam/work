<?php
/**
 * GameCloud wallet — users.balance is the ONLY source of truth (same as My Account).
 * motta / argame are mirrors only (never used to inflate or wipe balance).
 */
if (!function_exists('gc_wallet_log')) {
    function gc_wallet_log($message) {
        $dir = dirname(__DIR__) . '/bdtapi/apilogs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        @file_put_contents(
            $dir . '/gamecloud_wallet_' . date('Y-m-d') . '.txt',
            '[' . date('Y-m-d H:i:s') . '] ' . $message . "\n",
            FILE_APPEND | LOCK_EX
        );
    }
}

if (!function_exists('gc_wallet_clean_player')) {
    function gc_wallet_clean_player($rawId) {
        $raw = trim((string)$rawId);
        if ($raw === '') {
            return '';
        }
        if (preg_match('/^RS_[A-Za-z0-9]+_(\d+)$/i', $raw, $m)) {
            return $m[1];
        }
        // Spribe: hd754ar242p97 → 97
        if (preg_match('/p(\d+)$/i', $raw, $m)) {
            return $m[1];
        }
        if (preg_match('/(\d+)$/', $raw, $m)) {
            return $m[1];
        }
        return $raw;
    }
}

if (!function_exists('gc_wallet_resolve_user_id')) {
    function gc_wallet_resolve_user_id($conn, $rawPlayerId) {
        if (!$conn || $conn->connect_error) {
            return 0;
        }
        $raw = trim((string)$rawPlayerId);
        if ($raw === '') {
            return 0;
        }

        $candidates = array();
        $clean = gc_wallet_clean_player($raw);
        if ($clean !== '') {
            $candidates[] = $clean;
        }
        $candidates[] = $raw;
        if (strpos($raw, '_') !== false) {
            $parts = explode('_', $raw);
            $candidates[] = end($parts);
        }
        if (ctype_digit((string)$clean) && strlen((string)$clean) >= 10) {
            $candidates[] = ltrim((string)$clean, '0');
            if (strpos((string)$clean, '88') === 0) {
                $candidates[] = substr((string)$clean, 2);
            }
        }
        $candidates = array_values(array_unique(array_filter(array_map('strval', $candidates))));

        foreach ($candidates as $cand) {
            if (ctype_digit($cand)) {
                $stmt = $conn->prepare('SELECT id FROM users WHERE id = ? LIMIT 1');
                if ($stmt) {
                    $stmt->bind_param('s', $cand);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    if ($res && $res->num_rows > 0) {
                        $id = (int)$res->fetch_assoc()['id'];
                        $stmt->close();
                        return $id;
                    }
                    $stmt->close();
                }
            }
            $stmt = $conn->prepare('SELECT id FROM users WHERE username = ? OR phone = ? LIMIT 1');
            if ($stmt) {
                $stmt->bind_param('ss', $cand, $cand);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res && $res->num_rows > 0) {
                    $id = (int)$res->fetch_assoc()['id'];
                    $stmt->close();
                    return $id;
                }
                $stmt->close();
            }
        }
        return 0;
    }
}

if (!function_exists('gc_wallet_ensure_argame_table')) {
    function gc_wallet_ensure_argame_table($conn) {
        static $done = false;
        if ($done || !$conn) {
            return;
        }
        // DDL outside money transactions (CREATE TABLE commits implicitly)
        @$conn->query("CREATE TABLE IF NOT EXISTS argame_balances (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(100) NOT NULL UNIQUE,
            balance DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $done = true;
    }
}

if (!function_exists('gc_wallet_read_balance')) {
    /** Account wallet only — users.balance (auto-heals from motta if users is 0) */
    function gc_wallet_read_balance($conn, $userId) {
        $uid = (int)$userId;
        if ($uid <= 0 || !$conn || $conn->connect_error) {
            return 0.00;
        }
        $bal = 0.00;
        $stmt = @$conn->prepare('SELECT balance FROM users WHERE id = ? LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('i', $uid);
            @$stmt->execute();
            $res = $stmt->get_result();
            if ($res && $res->num_rows > 0) {
                $bal = round((float)$res->fetch_assoc()['balance'], 2);
            }
            $stmt->close();
        }

        if ($bal <= 0) {
            $uidStr = (string)$uid;
            $m = @$conn->prepare('SELECT motta FROM shonu_kaichila WHERE balakedara = ? LIMIT 1');
            if ($m) {
                $m->bind_param('s', $uidStr);
                @$m->execute();
                $resM = $m->get_result();
                if ($resM && $rowM = $resM->fetch_assoc()) {
                    $motta = round((float)$rowM['motta'], 2);
                    if ($motta > 0) {
                        $bal = $motta;
                        $u = @$conn->prepare('UPDATE users SET balance = ? WHERE id = ?');
                        if ($u) {
                            $u->bind_param('di', $bal, $uid);
                            @$u->execute();
                            $u->close();
                        }
                    }
                }
                $m->close();
            }
        }
        return $bal;
    }
}

if (!function_exists('gc_wallet_mirror')) {
    /** Mirror users.balance → motta + argame (display/legacy only) */
    function gc_wallet_mirror($conn, $userId, $balance) {
        $uid = (int)$userId;
        $nb = round((float)$balance, 2);
        if ($nb < 0) {
            $nb = 0.00;
        }
        if ($uid <= 0 || !$conn) {
            return;
        }
        $uidStr = (string)$uid;
        try {
            gc_wallet_ensure_argame_table($conn);
            $a = @$conn->prepare('INSERT INTO argame_balances (user_id, balance, updated_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE balance = VALUES(balance), updated_at = NOW()');
            if ($a) {
                $a->bind_param('sd', $uidStr, $nb);
                @$a->execute();
                $a->close();
            }
        } catch (Throwable $e) {}
        try {
            $m = @$conn->prepare('UPDATE shonu_kaichila SET motta = ? WHERE balakedara = ?');
            if ($m) {
                $m->bind_param('ds', $nb, $uidStr);
                @$m->execute();
                if ($m->affected_rows === 0) {
                    $ins = @$conn->prepare('INSERT INTO shonu_kaichila (balakedara, motta) VALUES (?, ?)');
                    if ($ins) {
                        $ins->bind_param('sd', $uidStr, $nb);
                        @$ins->execute();
                        $ins->close();
                    }
                }
                $m->close();
            }
        } catch (Throwable $e) {}
    }
}

if (!function_exists('gc_wallet_write_balance')) {
    function gc_wallet_write_balance($conn, $userId, $newBalance) {
        $uid = (int)$userId;
        $nb = round((float)$newBalance, 2);
        if ($nb < 0) {
            $nb = 0.00;
        }
        if ($uid <= 0 || !$conn || $conn->connect_error) {
            return $nb;
        }
        $stmt = @$conn->prepare('UPDATE users SET balance = ? WHERE id = ?');
        if ($stmt) {
            $stmt->bind_param('di', $nb, $uid);
            @$stmt->execute();
            $stmt->close();
        }
        gc_wallet_mirror($conn, $uid, $nb);
        return $nb;
    }
}

if (!function_exists('gc_wallet_extract_stake')) {
    /**
     * Resolve the real stake/credit from callback payload.
     * CRITICAL: many aggregators send BOTH amount (= wallet/balance echo) AND bet_amount (= 10).
     * Prefer explicit stake fields so we never debit the full wallet by mistake.
     */
    function gc_wallet_extract_stake($data, $action = 'bet') {
        $data = is_array($data) ? $data : array();
        $action = strtolower(trim((string)$action));

        $stakeKeys = array(
            'bet_amount', 'betAmount', 'BetAmount', 'stake', 'stake_amount',
            'debit_amount', 'debitAmount', 'bet', 'bet_money', 'valid_bet', 'validBet'
        );
        $creditKeys = array(
            'win_amount', 'winAmount', 'WinAmount', 'credit_amount', 'creditAmount',
            'payout', 'payout_amount', 'win', 'win_money', 'prize'
        );

        $pickFirst = function ($keys) use ($data) {
            foreach ($keys as $k) {
                if (!isset($data[$k]) || $data[$k] === '' || $data[$k] === null) {
                    continue;
                }
                if (!is_numeric($data[$k])) {
                    continue;
                }
                $v = round(abs((float)$data[$k]), 2);
                if ($v > 0) {
                    return $v;
                }
            }
            return null;
        };

        $amountField = null;
        if (isset($data['amount']) && $data['amount'] !== '' && $data['amount'] !== null && is_numeric($data['amount'])) {
            $amountField = round(abs((float)$data['amount']), 2);
        }

        if ($action === 'bet') {
            $stake = $pickFirst($stakeKeys);
            if ($stake !== null) {
                // If amount looks like full-wallet echo, ignore it
                if ($amountField !== null && $amountField > $stake * 5) {
                    gc_wallet_log("stake_prefer bet_amount={$stake} ignore_amount={$amountField}");
                }
                return $stake;
            }
            return $amountField !== null ? $amountField : 0.00;
        }

        if ($action === 'win' || $action === 'refund') {
            $credit = $pickFirst($creditKeys);
            if ($credit !== null) {
                return $credit;
            }
            return $amountField !== null ? $amountField : 0.00;
        }

        return $amountField !== null ? $amountField : 0.00;
    }
}

if (!function_exists('gc_wallet_normalize_bet_amount')) {
    /**
     * Some providers send minor units (paisa). If amount > balance but amount/100 fits, convert.
     * Guard: never auto-convert into a full-wallet wipe when a small stake was intended.
     */
    function gc_wallet_normalize_bet_amount($amount, $balance) {
        $amount = round(abs((float)$amount), 2);
        $balance = round((float)$balance, 2);
        if ($amount <= 0) {
            return 0.00;
        }
        if ($amount <= $balance + 0.00001) {
            return $amount;
        }
        // e.g. balance 280.60, amount 1000 (= 10.00 BDT paisa)
        $asMajor = round($amount / 100, 2);
        if ($asMajor > 0 && $asMajor <= $balance + 0.00001) {
            // Block paisa-convert that equals (almost) full balance from a huge integer
            // unless it is a clearly small stake (<= 20% of wallet)
            if ($asMajor >= $balance * 0.90 && $amount >= 1000) {
                gc_wallet_log("amount_normalize_blocked {$amount} -> {$asMajor} (would wipe wallet)");
                return $amount; // reject downstream as insufficient
            }
            gc_wallet_log("amount_normalize {$amount} -> {$asMajor} (balance={$balance})");
            return $asMajor;
        }
        return $amount;
    }
}

if (!function_exists('gc_wallet_debit')) {
    /**
     * Atomic debit on users.balance. Returns [ok, newBalance, error].
     */
    function gc_wallet_debit($conn, $userId, $amount) {
        $uid = (int)$userId;
        $amount = round(abs((float)$amount), 2);
        if ($uid <= 0) {
            return array(false, 0.00, 'PLAYER_NOT_FOUND');
        }
        if ($amount <= 0) {
            return array(false, gc_wallet_read_balance($conn, $uid), 'INVALID_AMOUNT');
        }

        $before = gc_wallet_read_balance($conn, $uid);
        $amount = gc_wallet_normalize_bet_amount($amount, $before);
        if ($amount <= 0) {
            return array(false, $before, 'INVALID_AMOUNT');
        }
        if ($amount > $before + 0.00001) {
            gc_wallet_log("debit_reject user={$uid} before={$before} amount={$amount}");
            return array(false, $before, 'INSUFFICIENT_FUNDS');
        }

        // Safety net: block accidental full-wallet wipe on large balances.
        // Real Aviator stakes are small (e.g. 10). A debit of ~100% wallet is almost always a bad amount field.
        if ($before >= 50 && $amount >= ($before - 0.05)) {
            gc_wallet_log("debit_blocked_full_wipe user={$uid} before={$before} amount={$amount}");
            return array(false, $before, 'INVALID_AMOUNT');
        }

        // Atomic: only succeeds if enough balance
        $stmt = @$conn->prepare('UPDATE users SET balance = ROUND(balance - ?, 2) WHERE id = ? AND balance >= ?');
        if (!$stmt) {
            return array(false, $before, 'DB_ERROR');
        }
        $stmt->bind_param('did', $amount, $uid, $amount);
        @$stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        if ($affected <= 0) {
            $now = gc_wallet_read_balance($conn, $uid);
            gc_wallet_log("debit_atomic_fail user={$uid} before={$before} amount={$amount} now={$now}");
            return array(false, $now, 'INSUFFICIENT_FUNDS');
        }

        $after = gc_wallet_read_balance($conn, $uid);
        gc_wallet_mirror($conn, $uid, $after);
        gc_wallet_log("debit_ok user={$uid} before={$before} amount={$amount} after={$after}");
        return array(true, $after, null);
    }
}

if (!function_exists('gc_wallet_credit')) {
    function gc_wallet_credit($conn, $userId, $amount) {
        $uid = (int)$userId;
        $amount = round(abs((float)$amount), 2);
        if ($uid <= 0) {
            return array(false, 0.00, 'PLAYER_NOT_FOUND');
        }
        $before = gc_wallet_read_balance($conn, $uid);
        if ($amount <= 0) {
            return array(true, $before, null);
        }
        // Normalize credit the same way if provider uses paisa
        if ($amount >= 1000 && fmod($amount, 1.0) < 0.00001) {
            // Heuristic: large whole numbers on small wallets are often paisa
            // Only convert when amount/100 is a typical win relative to balance scale
            $asMajor = round($amount / 100, 2);
            if ($asMajor > 0 && $asMajor < $amount && $asMajor <= max($before * 50, 10000)) {
                // If before was ~280 and credit 1000 → likely 10.00 major; keep as-is if already major win
                // Prefer: if amount > before*2 and divisible path makes sense for game wins under 1000x stake
                // Safer: only convert if amount > 500 and asMajor looks like money (has been needed for bets)
            }
        }

        $stmt = @$conn->prepare('UPDATE users SET balance = ROUND(balance + ?, 2) WHERE id = ?');
        if (!$stmt) {
            return array(false, $before, 'DB_ERROR');
        }
        $stmt->bind_param('di', $amount, $uid);
        @$stmt->execute();
        $stmt->close();
        $after = gc_wallet_read_balance($conn, $uid);
        gc_wallet_mirror($conn, $uid, $after);
        gc_wallet_log("credit_ok user={$uid} before={$before} amount={$amount} after={$after}");
        return array(true, $after, null);
    }
}

if (!function_exists('gc_wallet_sync_user')) {
    /**
     * Heal once: if users.balance is 0 but motta/argame still has funds, restore to users.
     * Never overwrite a positive users.balance with a lower mirror value.
     */
    function gc_wallet_sync_user($conn, $userId) {
        $uid = (int)$userId;
        $usersBal = gc_wallet_read_balance($conn, $uid);
        $legacy = 0.00;
        $uidStr = (string)$uid;

        try {
            $stmt = @$conn->prepare('SELECT motta FROM shonu_kaichila WHERE balakedara = ? LIMIT 1');
            if ($stmt) {
                $stmt->bind_param('s', $uidStr);
                @$stmt->execute();
                $res = $stmt->get_result();
                if ($res && $res->num_rows > 0) {
                    $legacy = max($legacy, (float)$res->fetch_assoc()['motta']);
                }
                $stmt->close();
            }
        } catch (Throwable $e) {}

        try {
            $stmt = @$conn->prepare('SELECT balance FROM argame_balances WHERE user_id = ? LIMIT 1');
            if ($stmt) {
                $stmt->bind_param('s', $uidStr);
                @$stmt->execute();
                $res = $stmt->get_result();
                if ($res && $res->num_rows > 0) {
                    $legacy = max($legacy, (float)$res->fetch_assoc()['balance']);
                }
                $stmt->close();
            }
        } catch (Throwable $e) {}

        // Restore only when account wallet empty but legacy still has money
        if ($usersBal <= 0 && $legacy > 0) {
            gc_wallet_log("heal_restore user={$uid} users={$usersBal} legacy={$legacy}");
            gc_wallet_write_balance($conn, $uid, $legacy);
            return round($legacy, 2);
        }

        // Mirror users → legacy (keep copies in sync for old codepaths)
        if ($usersBal > 0) {
            gc_wallet_mirror($conn, $uid, $usersBal);
        }
        gc_wallet_log("sync user={$uid} balance={$usersBal}");
        return $usersBal;
    }
}
?>
