<?php
/**
 * Convert College Data Pool Record to Active Lead
 * University-Level Education Consultancy CRM (EdrootsCRM)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_permission('leads', 'add');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('data/college_data.php');
}

verify_csrf_token($_POST['csrf_token'] ?? '');

$collegeDataId = (int)($_POST['college_data_id'] ?? 0);
$branchId = (int)($_POST['branch_id'] ?? 0);
$counselorId = (int)($_POST['counselor_id'] ?? 0);
$preferredCountry = trim($_POST['preferred_country'] ?? 'United Kingdom');
$notes = trim($_POST['notes'] ?? '');
$currentUser = current_user();

if ($collegeDataId <= 0 || $branchId <= 0 || $counselorId <= 0) {
    set_flash('danger', 'Please select both an operating Branch and an assigned Counselor to convert this candidate.');
    redirect('data/college_data.php');
}

// Fetch college data record
$stmt = $pdo->prepare("SELECT * FROM college_data WHERE id = ? LIMIT 1");
$stmt->execute([$collegeDataId]);
$cData = $stmt->fetch();

if (!$cData) {
    set_flash('danger', 'College pool record not found.');
    redirect('data/college_data.php');
}

if ($cData['converted_to_lead']) {
    set_flash('warning', 'This college record has already been converted to an active lead.');
    redirect('leads/view.php?id=' . $cData['lead_id']);
}

// Canonical phone deduplication
$cleanPhone = normalize_phone($cData['phone']);
$duplicateLead = check_phone_duplicate($cleanPhone);

if ($duplicateLead) {
    set_flash('danger', "Duplicate Blocked: A lead already exists with canonical phone <strong>{$cleanPhone}</strong> (" . htmlspecialchars($duplicateLead['full_name']) . " - " . htmlspecialchars($duplicateLead['lead_code']) . "). Cannot create duplicate active lead.");
    redirect('data/college_data.php');
}

// Generate Lead Code (e.g., EDR-2026-0042)
$currentYear = date('Y');
$stmtCount = $pdo->query("SELECT COUNT(*) FROM leads");
$seq = (int)$stmtCount->fetchColumn() + 1;
$leadCode = sprintf("EDR-%s-%04d", $currentYear, $seq);

try {
    $pdo->beginTransaction();

    // Insert into leads
    $stmtLead = $pdo->prepare("
        INSERT INTO leads (
            lead_code, full_name, email, phone, branch_id, assigned_counselor_id,
            stage, source, priority, preferred_country, highest_qualification,
            college_name, year_of_passing, notes, assigned_at, created_by
        ) VALUES (
            ?, ?, ?, ?, ?, ?,
            'ENQUIRY', 'College Data', 'Moderate', ?, ?,
            ?, ?, ?, NOW(), ?
        )
    ");
    $stmtLead->execute([
        $leadCode,
        $cData['student_name'],
        $cData['email'],
        $cleanPhone,
        $branchId,
        $counselorId,
        $preferredCountry,
        $cData['degree'],
        $cData['college_name'],
        $cData['graduation_year'],
        $notes ? $notes : "Converted from raw college data pool ({$cData['college_name']})",
        $currentUser['id']
    ]);
    $newLeadId = $pdo->lastInsertId();

    // Mark college_data as converted
    $stmtUpd = $pdo->prepare("
        UPDATE college_data 
        SET converted_to_lead = 1, converted_at = NOW(), converted_by = ?, lead_id = ?
        WHERE id = ?
    ");
    $stmtUpd->execute([$currentUser['id'], $newLeadId, $collegeDataId]);

    // Initial stage history
    $stmtStage = $pdo->prepare("
        INSERT INTO lead_stage_history (lead_id, from_stage, to_stage, changed_by, notes)
        VALUES (?, NULL, 'ENQUIRY', ?, 'Lead generated via College Data Pool conversion')
    ");
    $stmtStage->execute([$newLeadId, $currentUser['id']]);

    // Create Notification for assigned counselor
    $notifStmt = $pdo->prepare("
        INSERT INTO notifications (user_id, title, message, link, status)
        VALUES (?, 'New Lead Assigned', ?, ?, 'Not Attended')
    ");
    $notifStmt->execute([
        $counselorId,
        "Candidate {$cData['student_name']} converted from college pool and assigned to you.",
        "leads/view.php?id={$newLeadId}"
    ]);

    // Log Activity
    log_activity(
        $currentUser['id'],
        $newLeadId,
        'Lead Converted',
        "Candidate {$cData['student_name']} ({$cleanPhone}) converted from college pool to {$leadCode}."
    );

    $pdo->commit();
    set_flash('success', "Success! Candidate <strong>" . htmlspecialchars($cData['student_name']) . "</strong> has been converted to Active Lead <strong>{$leadCode}</strong>.");
    redirect('leads/view.php?id=' . $newLeadId);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    set_flash('danger', 'Error converting candidate: ' . htmlspecialchars($e->getMessage()));
    redirect('data/college_data.php');
}
