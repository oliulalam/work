<?php
/**
 * Helper to ensure rewards & ticket tables exist in database (includes/rewards_helper.php)
 */

if (!function_exists('ensure_rewards_tables')) {
    function ensure_rewards_tables($conn) {
        if (!$conn || (property_exists($conn, 'connect_error') && $conn->connect_error)) return;

        // 1. lucky_tickets
        @$conn->query("CREATE TABLE IF NOT EXISTS `lucky_tickets` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `user_id` INT(11) NOT NULL,
            `tickets` INT(11) NOT NULL DEFAULT 0,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `user_id` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // 2. personal_bonuses
        @$conn->query("CREATE TABLE IF NOT EXISTS `personal_bonuses` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `user_id` INT(11) NOT NULL,
            `amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            `category` VARCHAR(50) DEFAULT 'claim',
            `status` VARCHAR(20) DEFAULT 'pending',
            `description` VARCHAR(255) DEFAULT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `user_id` (`user_id`),
            KEY `status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // 3. global_bonuses
        @$conn->query("CREATE TABLE IF NOT EXISTS `global_bonuses` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `title` VARCHAR(100) DEFAULT NULL,
            `category` VARCHAR(50) DEFAULT 'deposit',
            `condition_type` VARCHAR(50) DEFAULT 'deposit',
            `condition_amount` DECIMAL(15,2) DEFAULT 0.00,
            `time_frame_hours` INT(11) DEFAULT 24,
            `reward_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            `amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            `is_active` TINYINT(1) DEFAULT 1,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // 4. bonus_claims
        @$conn->query("CREATE TABLE IF NOT EXISTS `bonus_claims` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `user_id` INT(11) NOT NULL,
            `bonus_id` INT(11) NOT NULL,
            `claimed_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `user_id_bonus` (`user_id`, `bonus_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
}
