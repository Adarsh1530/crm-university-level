<?php
/**
 * Mark Notification(s) as Read
 * University-Level Student CRM Demo
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_permission('notifications', 'view');

$currentUser = current_user();
$userId = $currentUser['id'];
$returnUrl = $_GET['return'] ?? 'notifications/index.php';
$notifId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action = $_GET['action'] ?? 'read';

try {
    if ($action === 'delete_read') {
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE (user_id = ? OR user_id IS NULL) AND is_read = 1");
        $stmt->execute([$userId]);
        set_flash('success', 'Cleared read notifications.');
    } elseif ($notifId > 0) {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND (user_id = ? OR user_id IS NULL)");
        $stmt->execute([$notifId, $userId]);
    } else {
        // Mark all as read
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE (user_id = ? OR user_id IS NULL)");
        $stmt->execute([$userId]);
        set_flash('success', 'All notifications marked as read.');
    }
} catch (Exception $e) {
    set_flash('danger', 'Error updating notifications: ' . $e->getMessage());
}

redirect($returnUrl);
