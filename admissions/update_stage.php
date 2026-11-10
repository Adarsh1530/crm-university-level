<?php
/**
 * Admission Pipeline Stage Transition Engine
 * University-Level Education Consultancy CRM (EdrootsCRM)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_permission('admissions', 'edit');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admissions/index.php');
}

verify_csrf_token($_POST['csrf_token'] ?? '');

$leadId = (int)($_POST['lead_id'] ?? 0);
$newStage = trim($_POST['new_stage'] ?? '');
$secondaryStatus = trim($_POST['secondary_status'] ?? 'Active');
$notes = trim($_POST['notes'] ?? '');
$returnUrl = $_POST['return_url'] ?? base_url("leads/view.php?id={$leadId}");
$currentUser = current_user();

$validStages = ['ENQUIRY', 'CUSTOMER', 'APPLICATION', 'OFFER RECEIVED', 'TUITION FEES PAID', 'ENROLLED'];
if (!in_array($newStage, $validStages, true)) {
    set_flash('danger', 'Invalid admission pipeline stage specified.');
    redirect($returnUrl);
}

if (!can_access_lead($leadId)) {
    set_flash('danger', 'Access Denied: You cannot modify admission stages for student records outside your assigned scope.');
    redirect('leads/index.php');
}

// Fetch current lead state
$stmt = $pdo->prepare("SELECT id, full_name, lead_code, stage, secondary_status, branch_id, is_customer FROM leads WHERE id = ? LIMIT 1");
$stmt->execute([$leadId]);
$lead = $stmt->fetch();

if (!$lead) {
    set_flash('danger', 'Lead record not found.');
    redirect('leads/index.php');
}

$oldStage = $lead['stage'];
$isCustomerFlag = (in_array($newStage, ['CUSTOMER', 'ENROLLED'], true) || (int)($lead['is_customer'] ?? 0) === 1) ? 1 : 0;

try {
    $pdo->beginTransaction();

    // Update lead stage and customer status
    $updStmt = $pdo->prepare("
        UPDATE leads 
        SET stage = ?, secondary_status = ?, is_customer = ?
        WHERE id = ?
    ");
    $updStmt->execute([$newStage, $secondaryStatus, $isCustomerFlag, $leadId]);

    // Record stage history
    $histStmt = $pdo->prepare("
        INSERT INTO lead_stage_history (lead_id, from_stage, to_stage, changed_by, notes)
        VALUES (?, ?, ?, ?, ?)
    ");
    $histStmt->execute([
        $leadId,
        $oldStage,
        $newStage,
        $currentUser['id'],
        $notes ? $notes : "Admission pipeline updated to {$newStage} ({$secondaryStatus})"
    ]);

    // Log Activity
    log_activity(
        $currentUser['id'],
        $leadId,
        'Stage Updated',
        "Transitioned student {$lead['full_name']} ({$lead['lead_code']}) from {$oldStage} to {$newStage}."
    );

    $pdo->commit();
    set_flash('success', "Stage Updated: <strong>" . htmlspecialchars($lead['full_name']) . "</strong> has progressed to <strong>{$newStage}</strong>.");
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    set_flash('danger', 'Error updating admission stage: ' . htmlspecialchars($e->getMessage()));
}

redirect($returnUrl);
