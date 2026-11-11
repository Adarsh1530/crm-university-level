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
$staffId = (int)($_POST['counselor_id'] ?? $_POST['staff_id'] ?? $_GET['counselor_id'] ?? $_GET['staff_id'] ?? 0);
$returnUrl = $_POST['return'] ?? $_GET['return'] ?? ('leads/view.php?id=' . $leadId);

if ($leadId <= 0 || $staffId <= 0) {
    set_flash('danger', 'Invalid lead or counselor selection.');
    redirect($returnUrl);
}

try {
    $leadStmt = $pdo->prepare("SELECT * FROM leads WHERE id = ?");
    $leadStmt->execute([$leadId]);
    $lead = $leadStmt->fetch();

    $staffStmt = $pdo->prepare("SELECT id, name, email, branch_id FROM users WHERE id = ? AND status = 'Active'");
    $staffStmt->execute([$staffId]);
    $staff = $staffStmt->fetch();

    if ($lead && $staff) {
        $upd = $pdo->prepare("UPDATE leads SET assigned_counselor_id = ?, branch_id = COALESCE(?, branch_id), updated_at = NOW() WHERE id = ?");
        $upd->execute([$staffId, $staff['branch_id'], $leadId]);

        // Create internal notification for assigned counselor (Attended/Not Attended workflow)
        add_notification(
            $staffId,
            'New Lead Assigned: ' . $lead['full_name'],
            "You have been assigned prospective student {$lead['full_name']} ({$lead['lead_code']}) for " . ($lead['preferred_course'] ?: 'Higher Education') . ".",
            'info',
            'leads/view.php?id=' . $leadId
        );

        // Activity log
        log_activity(
            $currentUser['id'],
            $leadId,
            'Lead Reassigned',
            "Reassigned student {$lead['full_name']} ({$lead['lead_code']}) to counselor {$staff['name']}."
        );

        set_flash('success', "Student lead <strong>" . htmlspecialchars($lead['full_name']) . "</strong> successfully reassigned to <strong>" . htmlspecialchars($staff['name']) . "</strong>.");
    } else {
        set_flash('danger', 'Lead or Counselor record not found.');
    }
} catch (Exception $e) {
    set_flash('danger', 'Assignment failed: ' . $e->getMessage());
}

redirect($returnUrl);
