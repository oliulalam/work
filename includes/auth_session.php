<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/admin_path_helper.php';

// 1. Check if logged in
if (!isset($_SESSION['user_id'])) {
    // This file is required from the admin panel folder, so login.php stays correct after folder rename.
    header("Location: login.php");
    exit();
}

// 2. Role & Path Security
$current_path = $_SERVER['PHP_SELF'] ?? '';
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';

// --- SECURITY FOR DYNAMIC ADMIN FOLDER ---
// Admin URL now follows the folder name automatically. Example:
// /webcornerbd/login.php -> rename folder to /secure-panel/ -> /secure-panel/login.php works.
if (admin_panel_is_request($current_path)) {

    // CASE A: Support User
    if ($role === 'support') {
        // Only allow access to support.php
        if (basename($current_path) !== 'support.php') {
            header("Location: support.php");
            exit();
        }
        return;
    }

    // CASE B: Agent User (Trying to access Admin)
    if ($role === 'agent') {
        header("Location: ../agent/index.php");
        exit();
    }

    // CASE C: Player User (Trying to access Admin)
    if ($role === 'player') {
        header("Location: ../index.php");
        exit();
    }

    // CASE D: Admin User
    if ($role === 'admin') {
        return;
    }

    // Default Deny for unknown roles
    header("Location: login.php");
    exit();
}

// --- SECURITY FOR AGENT FOLDER ---
if (strpos($current_path, '/agent/') !== false) {

    // Only Agents allowed
    if ($role !== 'agent') {
        // If Admin, redirect to detected Admin Dashboard
        if ($role === 'admin') {
            admin_panel_redirect('dashboard.php');
        }
        // Support or Players -> Redirect to Player Index
        header("Location: ../index.php");
        exit();
    }
}
?>
