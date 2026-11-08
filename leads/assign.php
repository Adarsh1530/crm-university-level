<?php
/**
 * Fast Lead Assignment Handler
 * University-Level Student CRM Demo
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_permission('leads', 'assign');

$currentUser = current_user();
$leadId = (int)($_POST['lead_id'] ?? $_GET['id'] ?? 0);
$staffId = (int)($_POST['staff_id'] ?? $_GET['staff_id'] ?? 0);
$returnUrl = $_POST['return'] ?? $_GET['return'] ?? 'leads/index.php';

if ($leadId <= 0 || $staffId <= 0) {
    set_flash('danger', 'Invalid lead or staff selection.');
    redirect($returnUrl);
}

try {
    $leadStmt = $pdo->prepare("SELECT * FROM leads WHERE id = ?");
    $leadStmt->execute([$leadId]);
    $lead = $leadStmt->fetch();

    $staffStmt = $pdo->prepare("SELECT id, name, email FROM users WHERE id = ? AND status = 'Active'");
    $staffStmt->execute([$staffId]);
    $staff = $staffStmt->fetch();

    if ($lead && $staff) {
        $upd = $pdo->prepare("UPDATE leads SET assigned_staff_id = ?, updated_at = NOW() WHERE id = ?");
        $upd->execute([$staffId, $leadId]);

        // Create internal notification for assigned counsellor
        add_notification(
            $staffId,
            'New Lead Assigned: ' . $lead['full_name'],
            "You have been assigned prospective lead {$lead['full_name']} ({$lead['lead_code']}) for {$lead['interested_course']}.",
            'info',
            'leads/view.php?id=' . $leadId
        );

        // Activity log
        log_activity(
            $currentUser['id'],
            null,
            'Lead Reassigned',
            "Assigned lead {$lead['full_name']} ({$lead['lead_code']}) to {$staff['name']}."
        );

        set_flash('success', "Lead <strong>" . htmlspecialchars($lead['full_name']) . "</strong> successfully assigned to <strong>" . htmlspecialchars($staff['name']) . "</strong>.");
    } else {
        set_flash('danger', 'Lead or Counsellor not found.');
    }
} catch (Exception $e) {
    set_flash('danger', 'Assignment failed: ' . $e->getMessage());
}

redirect($returnUrl);
