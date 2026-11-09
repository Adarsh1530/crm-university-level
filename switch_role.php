<?php
/**
 * Fast Role Persona Switcher for Client Demonstration
 * University-Level Education Consultancy CRM
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/includes/auth.php';

$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$returnUrl = $_GET['return'] ?? base_url('dashboard/index.php');

if ($userId > 0) {
    $stmt = $pdo->prepare("
        SELECT u.*, r.name AS role_name, r.label AS role_label, b.name AS branch_name 
        FROM users u 
        JOIN roles r ON u.role_id = r.id 
        LEFT JOIN branches b ON u.branch_id = b.id 
        WHERE u.id = ? AND u.status = 'Active'
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if ($user) {
        clear_flashes();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user'] = $user;
        $GLOBALS['currentUser'] = $user;
        $GLOBALS['crm_perm_cache'] = [];
        unset($_SESSION['current_user_cache']);
        unset($_SESSION['sidebar_cache']);
        unset($_SESSION['crm_role_perms']);

        set_flash('info', 'Active session switched to <strong>' . htmlspecialchars($user['name']) . '</strong> (' . htmlspecialchars($user['role_label']) . ').');

        // Check if requested return URL is permitted for the new persona
        $targetUrl = base_url('dashboard/index.php');
        if (!empty($returnUrl) && is_url_permitted_for_user($returnUrl, $user)) {
            $targetUrl = $returnUrl;
        }
        redirect($targetUrl);
    }
}

redirect(base_url('dashboard/index.php'));

