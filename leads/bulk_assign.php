<?php
/**
 * Bulk Lead Reassignment Handler
 * University-Level Education Consultancy CRM (EdrootsCRM)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_permission('leads', 'reassign');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('leads/index.php');
}

verify_csrf_token($_POST['csrf_token'] ?? '');

$rawLeadIds = trim($_POST['lead_ids'] ?? '');
$targetCounselorId = (int)($_POST['target_counselor_id'] ?? 0);
$reason = trim($_POST['reassign_reason'] ?? 'Bulk lead rebalance');
$currentUser = current_user();

if (empty($rawLeadIds) || $targetCounselorId <= 0) {
    set_flash('danger', 'Please choose valid leads and a designated target counselor.');
    redirect('leads/index.php');
}

// Fetch target counselor details
$cStmt = $pdo->prepare("SELECT id, name, branch_id FROM users WHERE id = ? AND status = 'Active'");
$cStmt->execute([$targetCounselorId]);
$targetCounselor = $cStmt->fetch();

if (!$targetCounselor) {
    set_flash('danger', 'Designated counselor not found or account is deactivated.');
    redirect('leads/index.php');
}

$leadIds = array_filter(array_map('intval', explode(',', $rawLeadIds)));
if (empty($leadIds)) {
    set_flash('danger', 'No leads selected.');
    redirect('leads/index.php');
}

$inClause = implode(',', $leadIds);

// Scope validation: Branch manager can only reassign leads within their branch
if (is_branch_manager()) {
    $bmBranch = (int)$currentUser['branch_id'];
    $checkStmt = $pdo->query("SELECT COUNT(*) FROM leads WHERE id IN ({$inClause}) AND branch_id != {$bmBranch}");
    if ((int)$checkStmt->fetchColumn() > 0) {
        set_flash('danger', 'Permission Denied: You can only reassign leads belonging to your branch.');
        redirect('leads/index.php');
    }
}

try {
    $pdo->beginTransaction();

    // Reassign
    $updateSql = "
        UPDATE leads 
        SET assigned_counselor_id = ?, branch_id = COALESCE(?, branch_id), assigned_at = NOW()
        WHERE id IN ({$inClause})
    ";
    $updStmt = $pdo->prepare($updateSql);
    $updStmt->execute([$targetCounselorId, $targetCounselor['branch_id']]);
    $affectedCount = $updStmt->rowCount();

    // Create Notification for Target Counselor
    $notifStmt = $pdo->prepare("
        INSERT INTO notifications (user_id, title, message, link, status)
        VALUES (?, 'Bulk Leads Reassigned to You', ?, 'tasks/index.php?queue=today_leads', 'Not Attended')
    ");
    $notifMsg = "{$affectedCount} active student leads have been reassigned to your caseload. Reason: {$reason}";
    $notifStmt->execute([$targetCounselorId, $notifMsg]);

    // Log Activity
    log_activity(
        $currentUser['id'],
        null,
        'Bulk Leads Reassigned',
        "Reassigned {$affectedCount} leads to {$targetCounselor['name']} (ID: {$targetCounselorId}). Reason: {$reason}"
    );

    $pdo->commit();
    set_flash('success', "Success! <strong>{$affectedCount}</strong> leads successfully reassigned to <strong>" . htmlspecialchars($targetCounselor['name']) . "</strong>.");
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    set_flash('danger', 'Error reassigning leads: ' . htmlspecialchars($e->getMessage()));
}

redirect('leads/index.php');
