<?php
/**
 * Logout Handler
 * University-Level Student CRM Demo
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/includes/auth.php';

$userId = $_SESSION['user_id'] ?? null;
if ($userId) {
    log_activity($userId, null, 'User Logout', 'User logged out of CRM portal');
}

$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// Start fresh session for flash message
session_start();
set_flash('info', 'You have been successfully signed out.');
redirect('login.php');
