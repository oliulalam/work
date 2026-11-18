<?php
// includes/db.php

$host = 'localhost';               // cPanel pe localhost
$user = 'kakicom_selim';  // cPanel DB username
$pass = 'kakicom_selim';      // cPanel DB password
$dbname = 'kakicom_selim'; // cPanel DB name

if (function_exists('mysqli_report')) {
    @mysqli_report(MYSQLI_REPORT_OFF);
}

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    if (!defined('DB_IGNORE_CONNECT_ERROR')) {
        die("❌ Connection Failed: " . $conn->connect_error);
    }
}

// Set Charset to handle special characters (e.g. Currency symbols)
$conn->set_charset("utf8mb4");


// Auto-fix 9Wicket local game icon if the SQL backup has an empty/placeholder image.
$nine_wicket_icon_patch = __DIR__ . '/game_api_9wicket_icon_patch.php';
if (file_exists($nine_wicket_icon_patch)) {
    require_once $nine_wicket_icon_patch;
    if (function_exists('apply_9wicket_icon_patch')) {
        @apply_9wicket_icon_patch($conn);
    }
}

// Auto-ensure lucky_tickets and bonus tables exist in database
$rewards_helper = __DIR__ . '/rewards_helper.php';
if (file_exists($rewards_helper)) {
    require_once $rewards_helper;
    if (function_exists('ensure_rewards_tables')) {
        @ensure_rewards_tables($conn);
    }
}

// Enforce Maintenance Mode on all public/user-facing pages that load the database.
$maintenance_guard = __DIR__ . '/maintenance_guard.php';
if (!defined('GAME_API_SKIP_MAINTENANCE') && file_exists($maintenance_guard)) {
    require_once $maintenance_guard;
    maintenance_enforce($conn);
}
?>