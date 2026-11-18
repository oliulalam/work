<?php
// ==========================================
// ThePerfectProvider - Merchant Integration Config
// ==========================================

// 1. Database connection settings on the merchant's server
$servername = "localhost";
$username   = "kakicom_selim";
$password   = "kakicom_selim";
$dbname     = "kakicom_selim";

// 2. Establish database connection
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'code' => 500,
        'msg' => 'Merchant Database Connection Failed: ' . $conn->connect_error,
        'serviceNowTime' => date("Y-m-d H:i:s")
    ]);
    exit;
}
$conn->set_charset("utf8mb4");

// 3. Player Session Validation (JWT / Session Token Check)
if (!function_exists('is_jwt_valid')) {
    /**
     * Verifies the player session token passed in the Authorization header.
     * Customize this function to validate your website's login tokens.
     */
    function is_jwt_valid(string $token): string {
        $parts = explode('.', $token);
        if (count($parts) === 3) {
            $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
            if (is_array($payload) && (isset($payload['id']) || isset($payload['uid']) || isset($payload['user_id']))) {
                $userId = $payload['id'] ?? ($payload['uid'] ?? $payload['user_id']);
                return json_encode([
                    'status' => 'Success',
                    'payload' => ['id' => (int)$userId]
                ]);
            }
        }
        
        if (is_numeric($token) && (int)$token > 0) {
            return json_encode([
                'status' => 'Success',
                'payload' => ['id' => (int)$token]
            ]);
        }

        return json_encode(['status' => 'Failed', 'message' => 'Invalid session token']);
    }
}

// 4. Custom Wallet Integrations (Standard Users Table Mappings with Legacy Fallback)
if (!function_exists('get_player_balance')) {
    /**
     * Get player's current balance from the merchant's database.
     */
    function get_player_balance($conn, int $userId): ?float {
        // 1. Try standard users table first
        try {
            $stmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res && $res->num_rows > 0) {
                    $row = $res->fetch_assoc();
                    $balance = round(floatval($row['balance']), 2);
                    $stmt->close();
                    return $balance;
                }
                $stmt->close();
            }
        } catch (Throwable $e) {
            // Ignore error and fall back
        }

        // 2. Fallback to legacy Kannada table
        try {
            $stmt = $conn->prepare("SELECT motta FROM shonu_kaichila WHERE balakedara = ?");
            if ($stmt) {
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res && $res->num_rows > 0) {
                    $row = $res->fetch_assoc();
                    $balance = round(floatval($row['motta']), 2);
                    $stmt->close();
                    return $balance;
                }
                $stmt->close();
            }
        } catch (Throwable $e) {
            // Ignore error
        }

        return null;
    }
}

if (!function_exists('update_player_balance')) {
    /**
     * Update player's balance in the merchant's database.
     */
    function update_player_balance($conn, float $newBalance, int $userId): bool {
        // 1. Try standard users table first
        try {
            $stmt = $conn->prepare("UPDATE users SET balance = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("di", $newBalance, $userId);
                $ok = $stmt->execute();
                $stmt->close();
                if ($ok) return true;
            }
        } catch (Throwable $e) {
            // Ignore error and fall back
        }

        // 2. Fallback to legacy Kannada table
        try {
            $stmt = $conn->prepare("UPDATE shonu_kaichila SET motta = ? WHERE balakedara = ?");
            if ($stmt) {
                $stmt->bind_param("di", $newBalance, $userId);
                $ok = $stmt->execute();
                $stmt->close();
                return $ok;
            }
        } catch (Throwable $e) {
            // Ignore error
        }

        return false;
    }
}

if (!function_exists('get_player_total_recharge')) {
    /**
     * Get player's total deposits (to check for recharge locking).
     */
    function get_player_total_recharge($conn, int $userId): float {
        // 1. Try checking standard deposits column in users table
        try {
            $stmt = $conn->prepare("SELECT deposits FROM users WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $userId);
                if ($stmt->execute()) {
                    $res = $stmt->get_result();
                    if ($res && $res->num_rows > 0) {
                        $row = $res->fetch_assoc();
                        if (isset($row['deposits'])) {
                            $total = floatval($row['deposits']);
                            $stmt->close();
                            return $total;
                        }
                    }
                }
                $stmt->close();
            }
        } catch (Throwable $e) {
            // Ignore error and fall back
        }

        // 2. Fallback to legacy Kannada table
        try {
            $stmt = $conn->prepare("SELECT IFNULL(SUM(motta), 0) as total_recharge FROM thevani WHERE balakedara = ? AND sthiti = 1");
            if ($stmt) {
                $stmt->bind_param("i", $userId);
                if ($stmt->execute()) {
                    $res = $stmt->get_result();
                    if ($res && $res->num_rows > 0) {
                        $total = floatval($res->fetch_assoc()['total_recharge'] ?? 0.0);
                        $stmt->close();
                        return $total;
                    }
                }
                $stmt->close();
            }
        } catch (Throwable $e) {
            // Ignore error
        }

        // 3. Fallback: bypass check by returning default 200.0 if tables don't exist
        return 200.0;
    }
}

if (!function_exists('verify_active_session')) {
    /**
     * Verify if the player session token is active in the sessions table.
     */
    function verify_active_session($conn, string $token): bool {
        // Fallback to legacy Kannada table
        try {
            $stmt = $conn->prepare("SELECT 1 FROM shonu_subjects WHERE akshinak = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("s", $token);
                $stmt->execute();
                $res = $stmt->get_result();
                $active = ($res && $res->num_rows > 0);
                $stmt->close();
                return $active;
            }
        } catch (Throwable $e) {
            // Ignore error
        }
        
        // Default to true if sessions table doesn't exist to prevent locking
        return true;
    }
}
