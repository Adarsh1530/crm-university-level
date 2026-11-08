<?php
/**
 * Fast Lead Registration Handler
 * Ultra-fast lead capture for phone inquiries, walk-ins, and consultation desks.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_permission('leads', 'add');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('leads/index.php');
}

verify_csrf_token($_POST['csrf_token'] ?? '');

$currentUser = current_user();

$fullName = trim($_POST['full_name'] ?? '');
$rawPhone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$preferredCountry = trim($_POST['preferred_country'] ?? 'United Kingdom');
$preferredCourse = trim($_POST['preferred_course'] ?? '');
$intakeYear = !empty($_POST['intake_year']) ? (int)$_POST['intake_year'] : (int)date('Y');
$intakeTerm = trim($_POST['intake_term'] ?? 'September');
$priority = trim($_POST['priority'] ?? 'Moderate');
$source = trim($_POST['source'] ?? 'Walk-in Inquiry');
$notes = trim($_POST['notes'] ?? '');
$scheduleNext = trim($_POST['next_followup_date'] ?? '');

// Assignment resolution
if (is_counselor()) {
    $branchId = (int)($currentUser['branch_id'] ?: 1);
    $counselorId = (int)$currentUser['id'];
} else {
    $counselorId = !empty($_POST['counselor_id']) ? (int)$_POST['counselor_id'] : (int)$currentUser['id'];
    $stmtC = $pdo->prepare("SELECT branch_id FROM users WHERE id = ?");
    $stmtC->execute([$counselorId]);
    $branchId = (int)($stmtC->fetchColumn() ?: 1);
}

// Validation
if (empty($fullName) || empty($rawPhone)) {
    set_flash('danger', 'Candidate Full Name and Phone Number are required.');
    redirect($_SERVER['HTTP_REFERER'] ?? base_url('leads/index.php'));
}

$cleanPhone = normalize_phone($rawPhone);
if (strlen($cleanPhone) < 10) {
    set_flash('danger', 'Please enter a valid 10-digit mobile number.');
    redirect($_SERVER['HTTP_REFERER'] ?? base_url('leads/index.php'));
}

// Duplicate Phone Check
$duplicate = check_phone_duplicate($cleanPhone);
if ($duplicate) {
    set_flash('warning', "Duplicate Record: A student with phone {$cleanPhone} already exists: <a href='" . base_url('leads/view.php?id=' . $duplicate['id']) . "'><strong>" . htmlspecialchars($duplicate['full_name']) . "</strong> (" . htmlspecialchars($duplicate['lead_code']) . ")</a>.");
    redirect(base_url('leads/view.php?id=' . $duplicate['id']));
}

// Generate Lead Code (e.g. EDR-2026-0001)
$currentYear = date('Y');
$stmtCount = $pdo->query("SELECT COUNT(*) FROM leads");
$seq = (int)$stmtCount->fetchColumn() + 1;
$leadCode = sprintf("EDR-%s-%04d", $currentYear, $seq);

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO leads (
            lead_code, full_name, email, phone,
            preferred_country, preferred_course, intake_year, intake_term,
            priority, source, stage, secondary_status,
            branch_id, assigned_counselor_id, assigned_at,
            notes, created_by, created_at
        ) VALUES (
            ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, 'ENQUIRY', 'Active',
            ?, ?, NOW(),
            ?, ?, NOW()
        )
    ");
    $stmt->execute([
        $leadCode, $fullName, $email, $cleanPhone,
        $preferredCountry, $preferredCourse, $intakeYear, $intakeTerm,
        $priority, $source,
        $branchId, $counselorId,
        $notes, $currentUser['id']
    ]);
    $leadId = (int)$pdo->lastInsertId();

    // Schedule initial follow-up task if date given
    if (!empty($scheduleNext)) {
        $insTask = $pdo->prepare("
            INSERT INTO followups (
                lead_id, counselor_id, interaction_type, scheduled_date, scheduled_time, status, notes
            ) VALUES (
                ?, ?, 'Initial Call', ?, '10:00:00', 'Pending', ?
            )
        ");
        $insTask->execute([
            $leadId, $counselorId, $scheduleNext,
            $notes ? "Initial discussion: {$notes}" : "First counseling call scheduled"
        ]);

        $pdo->prepare("UPDATE leads SET next_followup_date = ? WHERE id = ?")->execute([$scheduleNext, $leadId]);
    }

    // Log Activity
    log_activity($currentUser['id'], $leadId, 'Lead Created', "Quick registered student inquiry: {$fullName} ({$leadCode}) for {$preferredCountry}.");

    $pdo->commit();

    set_flash('success', "Student lead <strong>" . htmlspecialchars($fullName) . "</strong> ({$leadCode}) successfully captured and registered!");
    redirect(base_url("leads/view.php?id={$leadId}"));

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    set_flash('danger', 'Failed to register lead: ' . htmlspecialchars($e->getMessage()));
    redirect($_SERVER['HTTP_REFERER'] ?? base_url('leads/index.php'));
}
