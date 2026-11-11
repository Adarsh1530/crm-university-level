<?php
/**
 * Mark Notification as Attended
 * University-Level Education Consultancy CRM (EdrootsCRM)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$currentUser = current_user();
$notifId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$markAll = isset($_GET['all']) && $_GET['all'] == '1';
$returnUrl = $_GET['return'] ?? base_url('notifications/index.php');

$unattend = isset($_GET['unattend']) && $_GET['unattend'] == '1';

if ($markAll) {
    $stmt = $pdo->prepare("UPDATE notifications SET status = 'Attended', attended_at = NOW() WHERE (user_id = ? OR user_id IS NULL) AND status = 'Not Attended'");
    $stmt->execute([$currentUser['id']]);
    set_flash('success', 'All notifications marked as Attended.');
} elseif ($notifId > 0) {
    if ($unattend) {
        $stmt = $pdo->prepare("UPDATE notifications SET status = 'Not Attended', attended_at = NULL WHERE id = ? AND (user_id = ? OR user_id IS NULL)");
        $stmt->execute([$notifId, $currentUser['id']]);
    } else {
        $stmt = $pdo->prepare("UPDATE notifications SET status = 'Attended', attended_at = NOW() WHERE id = ? AND (user_id = ? OR user_id IS NULL)");
        $stmt->execute([$notifId, $currentUser['id']]);
    }
}

redirect($returnUrl);
