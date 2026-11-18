<?php
/**
 * Player referral program helpers.
 */

function referral_ensure_schema($conn) {
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    if (!$conn || (property_exists($conn, 'connect_error') && $conn->connect_error)) {
        return;
    }

    try {
        @$conn->query("CREATE TABLE IF NOT EXISTS referral_rewards (
            id INT AUTO_INCREMENT PRIMARY KEY,
            referrer_id INT NOT NULL,
            referee_id INT NOT NULL,
            amount DECIMAL(15,2) NOT NULL DEFAULT 0,
            bet_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
            tier TINYINT NOT NULL DEFAULT 1,
            status ENUM('pending','claimed') NOT NULL DEFAULT 'pending',
            source_type VARCHAR(50) DEFAULT 'bet',
            source_ref VARCHAR(150) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_referrer_date (referrer_id, created_at),
            INDEX idx_referrer_status (referrer_id, status),
            UNIQUE KEY uniq_source_reward (referrer_id, source_ref, tier)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

        // Ensure settings table has bonus columns
        $chkCol1 = @$conn->query("SHOW COLUMNS FROM settings LIKE 'welcome_bonus_amount'");
        if (!$chkCol1 || $chkCol1->num_rows === 0) {
            @$conn->query("ALTER TABLE settings ADD COLUMN welcome_bonus_amount DECIMAL(10,2) NOT NULL DEFAULT 50.00");
        }
        $chkCol2 = @$conn->query("SHOW COLUMNS FROM settings LIKE 'referral_bonus_amount'");
        if (!$chkCol2 || $chkCol2->num_rows === 0) {
            @$conn->query("ALTER TABLE settings ADD COLUMN referral_bonus_amount DECIMAL(10,2) NOT NULL DEFAULT 50.00");
        }
    } catch (Throwable $e) {
        error_log("referral_ensure_schema error: " . $e->getMessage());
    }
}

function referral_capture_from_request() {
    if (!empty($_GET['ref'])) {
        $_SESSION['signup_ref_code'] = trim($_GET['ref']);
    } elseif (!empty($_GET['invite'])) {
        $_SESSION['signup_ref_code'] = trim($_GET['invite']);
    } elseif (!empty($_GET['invite_code'])) {
        $_SESSION['signup_ref_code'] = trim($_GET['invite_code']);
    } elseif (!empty($_GET['code'])) {
        $_SESSION['signup_ref_code'] = trim($_GET['code']);
    }
}

function referral_get_stored_ref() {
    return !empty($_SESSION['signup_ref_code']) ? trim($_SESSION['signup_ref_code']) : '';
}

function referral_clear_stored_ref() {
    unset($_SESSION['signup_ref_code']);
}

function referral_resolve_code($conn, $ref_code) {
    referral_ensure_schema($conn);

    $result = ['referrer_id' => 0, 'agent_id' => 0];
    $ref_code = $conn->real_escape_string(trim($ref_code));
    if ($ref_code === '') {
        return $result;
    }

    // 1. Player ref_code match
    $user_q = $conn->query("SELECT id FROM users WHERE ref_code = '$ref_code' LIMIT 1");
    if ($user_q && $user_q->num_rows > 0) {
        $result['referrer_id'] = (int)$user_q->fetch_assoc()['id'];
        return $result;
    }

    // 2. Agent promo_code match
    $agent_q = $conn->query("SELECT id, user_id FROM agents WHERE promo_code = '$ref_code' LIMIT 1");
    if ($agent_q && $agent_q->num_rows > 0) {
        $agent = $agent_q->fetch_assoc();
        $result['agent_id'] = (int)$agent['id'];
        $result['referrer_id'] = (int)$agent['user_id'];
        return $result;
    }

    // 3. Fallback: username or phone match
    $user_q2 = $conn->query("SELECT id FROM users WHERE (phone = '$ref_code' OR username = '$ref_code') LIMIT 1");
    if ($user_q2 && $user_q2->num_rows > 0) {
        $result['referrer_id'] = (int)$user_q2->fetch_assoc()['id'];
        return $result;
    }

    return $result;
}

function referral_get_admin_bonus_settings($conn) {
    static $cachedSettings = null;
    if ($cachedSettings !== null) return $cachedSettings;

    $welcome = 50.00;
    $referral = 50.00;

    if ($conn && method_exists($conn, 'query')) {
        try {
            $chk = @$conn->query("SHOW COLUMNS FROM settings LIKE 'welcome_bonus_amount'");
            if ($chk && $chk->num_rows > 0) {
                $q = @$conn->query("SELECT welcome_bonus_amount, referral_bonus_amount FROM settings WHERE id = 1 LIMIT 1");
                if ($q && $row = $q->fetch_assoc()) {
                    if (isset($row['welcome_bonus_amount']) && $row['welcome_bonus_amount'] !== null) {
                        $welcome = (float)$row['welcome_bonus_amount'];
                    }
                    if (isset($row['referral_bonus_amount']) && $row['referral_bonus_amount'] !== null) {
                        $referral = (float)$row['referral_bonus_amount'];
                    }
                }
            }
        } catch (Throwable $e) {
            error_log("referral_get_admin_bonus_settings error: " . $e->getMessage());
        }
    }

    $cachedSettings = ['welcome_bonus' => $welcome, 'referral_bonus' => $referral];
    return $cachedSettings;
}

function referral_process_signup_reward($conn, $new_user_id, $referrer_id = 0, $referral_bonus = null, $welcome_bonus = null) {
    try {
        referral_ensure_schema($conn);

        $new_user_id = (int)$new_user_id;
        $referrer_id = (int)$referrer_id;

        if ($new_user_id <= 0) {
            return false;
        }

        if ($welcome_bonus === null || $referral_bonus === null) {
            $adminSettings = referral_get_admin_bonus_settings($conn);
            if ($welcome_bonus === null) $welcome_bonus = $adminSettings['welcome_bonus'];
            if ($referral_bonus === null) $referral_bonus = $adminSettings['referral_bonus'];
        }

        $welcome_bonus = (float)$welcome_bonus;
        $referral_bonus = (float)$referral_bonus;

        // 1. Grant Welcome Bonus to Referee (New Registered Player)
        if ($welcome_bonus > 0) {
            @$conn->query("UPDATE users SET balance = balance + {$welcome_bonus} WHERE id = {$new_user_id}");
            
            $source_ref = 'welcome_user_' . $new_user_id;
            $stmt_welcome = @$conn->prepare("INSERT IGNORE INTO referral_rewards
                (referrer_id, referee_id, amount, bet_amount, tier, status, source_type, source_ref, created_at)
                VALUES (?, ?, ?, 0.00, 1, 'claimed', 'welcome_bonus', ?, NOW())");
            if ($stmt_welcome) {
                $stmt_welcome->bind_param('iids', $new_user_id, $new_user_id, $welcome_bonus, $source_ref);
                @$stmt_welcome->execute();
                @$stmt_welcome->close();
            }
        }

        // 2. Grant Referral Commission to Referrer (Upline)
        if ($referrer_id > 0 && $referrer_id !== $new_user_id && $referral_bonus > 0) {
            $source_ref = 'signup_ref_' . $new_user_id;
            $stmt_ref = @$conn->prepare("INSERT IGNORE INTO referral_rewards
                (referrer_id, referee_id, amount, bet_amount, tier, status, source_type, source_ref, created_at)
                VALUES (?, ?, ?, 0.00, 1, 'pending', 'signup_bonus', ?, NOW())");
            if ($stmt_ref) {
                $stmt_ref->bind_param('iids', $referrer_id, $new_user_id, $referral_bonus, $source_ref);
                @$stmt_ref->execute();
                @$stmt_ref->close();
            }
        }

        return true;
    } catch (Throwable $e) {
        error_log("referral_process_signup_reward error: " . $e->getMessage());
        return false;
    }
}

function referral_tier_rates() {
    return [1 => 0.30, 2 => 0.10, 3 => 0.05];
}

function referral_process_bet_reward($conn, $player_id, $bet_amount, $source_ref = '') {
    referral_ensure_schema($conn);

    $player_id = (int)$player_id;
    $bet_amount = round((float)$bet_amount, 2);
    if ($player_id <= 0 || $bet_amount <= 0) {
        return;
    }

    $source_ref = $conn->real_escape_string(trim((string)$source_ref));
    if ($source_ref === '') {
        $source_ref = 'bet_' . $player_id . '_' . time();
    }

    $user_q = $conn->query("SELECT referrer_id FROM users WHERE id = $player_id LIMIT 1");
    if (!$user_q || $user_q->num_rows === 0) {
        return;
    }

    $current_id = (int)$user_q->fetch_assoc()['referrer_id'];
    $rates = referral_tier_rates();

    foreach ($rates as $tier => $rate) {
        if ($current_id <= 0) {
            break;
        }

        $reward = round(($bet_amount * $rate) / 100, 2);
        if ($reward <= 0) {
            $parent_q = $conn->query("SELECT referrer_id FROM users WHERE id = $current_id LIMIT 1");
            $current_id = ($parent_q && $parent_q->num_rows > 0) ? (int)$parent_q->fetch_assoc()['referrer_id'] : 0;
            continue;
        }

        $stmt = $conn->prepare("INSERT IGNORE INTO referral_rewards
            (referrer_id, referee_id, amount, bet_amount, tier, status, source_type, source_ref, created_at)
            VALUES (?, ?, ?, ?, ?, 'pending', 'bet', ?, NOW())");
        if ($stmt) {
            $stmt->bind_param('iiddis', $current_id, $player_id, $reward, $bet_amount, $tier, $source_ref);
            $stmt->execute();
            $stmt->close();
        }

        $parent_q = $conn->query("SELECT referrer_id FROM users WHERE id = $current_id LIMIT 1");
        $current_id = ($parent_q && $parent_q->num_rows > 0) ? (int)$parent_q->fetch_assoc()['referrer_id'] : 0;
    }
}

function referral_get_stats($conn, $user_id) {
    referral_ensure_schema($conn);
    $user_id = (int)$user_id;

    $referral_count = 0;
    $count_q = $conn->query("SELECT COUNT(id) AS total_refs FROM users WHERE referrer_id = $user_id");
    if ($count_q) {
        $referral_count = (int)$count_q->fetch_assoc()['total_refs'];
    }

    $today_reward = 0;
    $yesterday_reward = 0;
    $total_reward = 0;

    $today_q = $conn->query("SELECT COALESCE(SUM(amount), 0) AS total
        FROM referral_rewards
        WHERE referrer_id = $user_id AND DATE(created_at) = CURDATE()");
    if ($today_q) {
        $today_reward = (float)$today_q->fetch_assoc()['total'];
    }

    $yesterday_q = $conn->query("SELECT COALESCE(SUM(amount), 0) AS total
        FROM referral_rewards
        WHERE referrer_id = $user_id AND DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)");
    if ($yesterday_q) {
        $yesterday_reward = (float)$yesterday_q->fetch_assoc()['total'];
    }

    $pending_q = $conn->query("SELECT COALESCE(SUM(amount), 0) AS total
        FROM referral_rewards
        WHERE referrer_id = $user_id AND status = 'pending'");
    if ($pending_q) {
        $total_reward = (float)$pending_q->fetch_assoc()['total'];
    }

    return [
        'referral_count' => $referral_count,
        'today_reward' => $today_reward,
        'yesterday_reward' => $yesterday_reward,
        'total_reward' => $total_reward,
    ];
}

function referral_claim_rewards($conn, $user_id) {
    referral_ensure_schema($conn);
    $user_id = (int)$user_id;

    $pending_q = $conn->query("SELECT COALESCE(SUM(amount), 0) AS total
        FROM referral_rewards
        WHERE referrer_id = $user_id AND status = 'pending'");
    $pending = ($pending_q) ? (float)$pending_q->fetch_assoc()['total'] : 0;

    if ($pending <= 0) {
        return ['success' => false, 'message' => 'No rewards available to claim.'];
    }

    $conn->begin_transaction();
    try {
        $conn->query("UPDATE users SET balance = balance + $pending WHERE id = $user_id");
        $conn->query("UPDATE referral_rewards SET status = 'claimed'
            WHERE referrer_id = $user_id AND status = 'pending'");
        $conn->commit();
        return ['success' => true, 'message' => '৳ ' . number_format($pending, 2) . ' claimed successfully!', 'amount' => $pending];
    } catch (Throwable $e) {
        $conn->rollback();
        return ['success' => false, 'message' => 'Claim failed. Please try again.'];
    }
}

function referral_get_details($conn, $user_id, $limit = 50) {
    referral_ensure_schema($conn);
    $user_id = (int)$user_id;
    $limit = (int)$limit;

    $records = [];
    $sql = "SELECT rr.*, u.username, u.phone
        FROM referral_rewards rr
        LEFT JOIN users u ON u.id = rr.referee_id
        WHERE rr.referrer_id = $user_id
        ORDER BY rr.created_at DESC
        LIMIT $limit";
    $q = $conn->query($sql);
    if ($q) {
        while ($row = $q->fetch_assoc()) {
            $records[] = $row;
        }
    }

    return $records;
}
