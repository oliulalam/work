<?php
/**
 * Daily Free Lucky Spin Helper Engine
 */

function spin_ensure_schema($conn) {
    static $done = false;
    if ($done) return;
    $done = true;

    $conn->query("CREATE TABLE IF NOT EXISTS user_spins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        prize DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_time (user_id, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
}

/**
 * Get sector configuration for the visual wheel
 * Total 10 sectors (indices 0 to 9)
 */
function spin_get_wheel_sectors() {
    return [
        ['label' => '৳ 2',     'value' => 2.00,    'color' => '#ff5722', 'win_eligible' => true],
        ['label' => '৳ 50',    'value' => 50.00,   'color' => '#1e88e5', 'win_eligible' => false],
        ['label' => '৳ 5',     'value' => 5.00,    'color' => '#4caf50', 'win_eligible' => true],
        ['label' => '৳ 100',   'value' => 100.00,  'color' => '#8e24aa', 'win_eligible' => false],
        ['label' => '৳ 8',     'value' => 8.00,    'color' => '#ffb300', 'win_eligible' => true],
        ['label' => '৳ 500',   'value' => 500.00,  'color' => '#00acc1', 'win_eligible' => false],
        ['label' => '৳ 3',     'value' => 3.00,    'color' => '#d81b60', 'win_eligible' => true],
        ['label' => '৳ 1,000', 'value' => 1000.00, 'color' => '#7cb342', 'win_eligible' => false],
        ['label' => '৳ 5,000', 'value' => 5000.00, 'color' => '#fb8c00', 'win_eligible' => false],
        ['label' => '৳ 30,000','value' => 30000.00,'color' => '#9c27b0', 'win_eligible' => false],
    ];
}

/**
 * Get user spin status: can spin, remaining seconds, last spin record
 */
function spin_get_user_status($conn, $user_id) {
    spin_ensure_schema($conn);
    $user_id = (int)$user_id;

    $q = $conn->query("SELECT created_at FROM user_spins WHERE user_id = $user_id ORDER BY id DESC LIMIT 1");
    if ($q && $q->num_rows > 0) {
        $last_spin_time = strtotime($q->fetch_assoc()['created_at']);
        $now = time();
        $elapsed = $now - $last_spin_time;
        $cooldown = 86400; // 24 hours in seconds

        if ($elapsed < $cooldown) {
            $remaining = $cooldown - $elapsed;
            return [
                'can_spin' => false,
                'remaining_seconds' => $remaining,
                'last_spin_at' => date('Y-m-d H:i:s', $last_spin_time)
            ];
        }
    }

    return [
        'can_spin' => true,
        'remaining_seconds' => 0,
        'last_spin_at' => null
    ];
}

/**
 * Process a spin request for user
 */
function spin_process_user_spin($conn, $user_id) {
    spin_ensure_schema($conn);
    $user_id = (int)$user_id;

    $status = spin_get_user_status($conn, $user_id);
    if (!$status['can_spin']) {
        $hrs = floor($status['remaining_seconds'] / 3600);
        $mins = floor(($status['remaining_seconds'] % 3600) / 60);
        return [
            'success' => false,
            'message' => "You have already claimed your daily spin! Next spin available in {$hrs}h {$mins}m.",
            'remaining_seconds' => $status['remaining_seconds']
        ];
    }

    $sectors = spin_get_wheel_sectors();
    
    // Filter eligible winning sectors (strictly value < 10)
    $eligible_indices = [];
    foreach ($sectors as $idx => $sec) {
        if (!empty($sec['win_eligible']) && $sec['value'] < 10.00) {
            $eligible_indices[] = $idx;
        }
    }

    if (empty($eligible_indices)) {
        $winning_sector_index = 0; // Fallback to index 0 (2.00)
    } else {
        $winning_sector_index = $eligible_indices[array_rand($eligible_indices)];
    }

    $winning_sector = $sectors[$winning_sector_index];
    $won_amount = (float)$winning_sector['value'];

    $conn->begin_transaction();
    try {
        // 1. Credit balance
        $conn->query("UPDATE users SET balance = balance + $won_amount WHERE id = $user_id");

        // 2. Record spin
        $stmt = $conn->prepare("INSERT INTO user_spins (user_id, prize, created_at) VALUES (?, ?, NOW())");
        if ($stmt) {
            $stmt->bind_param('id', $user_id, $won_amount);
            $stmt->execute();
            $stmt->close();
        }

        $conn->commit();

        // Fetch updated balance
        $bal_q = $conn->query("SELECT balance FROM users WHERE id = $user_id LIMIT 1");
        $new_balance = ($bal_q && $bal_q->num_rows > 0) ? (float)$bal_q->fetch_assoc()['balance'] : 0.00;

        return [
            'success' => true,
            'sector_index' => $winning_sector_index,
            'prize' => $won_amount,
            'prize_label' => $winning_sector['label'],
            'new_balance' => $new_balance,
            'remaining_seconds' => 86400,
            'message' => 'Congratulations! You won ' . $winning_sector['label'] . '!'
        ];
    } catch (Throwable $e) {
        $conn->rollback();
        return [
            'success' => false,
            'message' => 'Spin processing failed: ' . $e->getMessage()
        ];
    }
}

/**
 * Get recent spin history for user
 */
function spin_get_history($conn, $user_id, $limit = 20) {
    spin_ensure_schema($conn);
    $user_id = (int)$user_id;
    $limit = (int)$limit;

    $history = [];
    $q = $conn->query("SELECT * FROM user_spins WHERE user_id = $user_id ORDER BY id DESC LIMIT $limit");
    if ($q) {
        while ($row = $q->fetch_assoc()) {
            $history[] = $row;
        }
    }

    return $history;
}
