<?php
/**
 * Log Consultation & Follow-up Engine
 * University-Level Education Consultancy CRM (EdrootsCRM)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('dashboard/index.php');
}

verify_csrf_token($_POST['csrf_token'] ?? '');

$leadId = (int)($_POST['lead_id'] ?? 0);
$titleId = (int)($_POST['interaction_title_id'] ?? 0);
$notes = trim($_POST['notes'] ?? '');
$nextDate = trim($_POST['next_followup_date'] ?? '');
$nextTime = trim($_POST['next_followup_time'] ?? '10:00:00');
$returnUrl = $_POST['return_url'] ?? base_url("leads/view.php?id={$leadId}");
$currentUser = current_user();

if ($leadId <= 0) {
    set_flash('danger', 'Invalid student lead record.');
    redirect('leads/index.php');
}

// Scoping & Ownership Validation
if (!can_access_lead($leadId)) {
    set_flash('danger', 'Access Denied: You cannot log follow-ups on student records outside your assigned caseload.');
    redirect('leads/index.php');
}

// Fetch Lead
$leadStmt = $pdo->prepare("SELECT id, full_name, phone, branch_id, assigned_counselor_id FROM leads WHERE id = ? LIMIT 1");
$leadStmt->execute([$leadId]);
$lead = $leadStmt->fetch();

if (!$lead) {
    set_flash('danger', 'Lead record not found.');
    redirect('leads/index.php');
}

$scheduleNext = isset($_POST['schedule_next']) ? (int)$_POST['schedule_next'] : 1;

// Fetch Interaction Title & Duration Constraint
$titleStmt = $pdo->prepare("SELECT id, title, max_duration_days FROM interaction_titles WHERE id = ? LIMIT 1");
$titleStmt->execute([$titleId]);
$interactionTitle = $titleStmt->fetch();

if (!$interactionTitle) {
    set_flash('danger', 'Please select a valid Interaction Title.');
    redirect($returnUrl);
}

$maxDays = (int)($interactionTitle['max_duration_days'] ?? 7);

// If scheduling next follow-up, validate date
if ($scheduleNext) {
    if (empty($nextDate)) {
        $nextDate = date('Y-m-d', strtotime("+{$maxDays} days"));
    }

    $today = new DateTime('today');
    $selectedDate = new DateTime($nextDate);
    $allowedMaxDate = (clone $today)->modify("+{$maxDays} days");

    if ($selectedDate < $today) {
        set_flash('danger', 'Validation Error: Next follow-up date cannot be in the past.');
        redirect($returnUrl);
    }

    if ($selectedDate > $allowedMaxDate) {
        $maxDateStr = $allowedMaxDate->format('d M Y');
        set_flash('danger', "Governance Constraint Violated: Interaction title <strong>\"{$interactionTitle['title']}\"</strong> enforces a maximum interval of <strong>{$maxDays} days</strong>. The next follow-up date cannot exceed <strong>{$maxDateStr}</strong>.");
        redirect($returnUrl);
    }
}

try {
    $pdo->beginTransaction();

    // 1. Mark any existing pending follow-up for this lead as completed
    $completeOldStmt = $pdo->prepare("
        UPDATE followups 
        SET status = 'Completed', completed_at = NOW(), completed_by = ? 
        WHERE lead_id = ? AND status = 'Pending'
    ");
    $completeOldStmt->execute([$currentUser['id'], $leadId]);

    // 2. Insert this completed interaction log
    $insStmt = $pdo->prepare("
        INSERT INTO followups (
            lead_id, counselor_id, interaction_title_id, interaction_type,
            scheduled_date, scheduled_time, completed_at, completed_by,
            status, notes, duration_days_allowed
        ) VALUES (
            ?, ?, ?, 'Phone Call',
            CURDATE(), CURTIME(), NOW(), ?,
            'Completed', ?, ?
        )
    ");
    $insStmt->execute([
        $leadId,
        $currentUser['id'],
        $titleId,
        $currentUser['id'],
        $notes ? $notes : "Follow-up completed: {$interactionTitle['title']}",
        $maxDays
    ]);

    // 3. Schedule the next follow-up task (if requested)
    if ($scheduleNext) {
        $insNextStmt = $pdo->prepare("
            INSERT INTO followups (
                lead_id, counselor_id, interaction_title_id, interaction_type,
                scheduled_date, scheduled_time, status, notes
            ) VALUES (
                ?, ?, ?, 'Scheduled Follow-up',
                ?, ?, 'Pending', ?
            )
        ");
        $insNextStmt->execute([
            $leadId,
            $lead['assigned_counselor_id'] ?: $currentUser['id'],
            $titleId,
            $nextDate,
            $nextTime,
            "Next follow-up following: {$interactionTitle['title']}"
        ]);

        // Update Lead metadata with next follow-up date
        $updLeadStmt = $pdo->prepare("
            UPDATE leads 
            SET followup_count = followup_count + 1,
                last_followup_date = CURDATE(),
                next_followup_date = ?
            WHERE id = ?
        ");
        $updLeadStmt->execute([$nextDate, $leadId]);
    } else {
        // Only update lead followup count and clear next date if not scheduling
        $updLeadStmt = $pdo->prepare("
            UPDATE leads 
            SET followup_count = followup_count + 1,
                last_followup_date = CURDATE(),
                next_followup_date = NULL
            WHERE id = ?
        ");
        $updLeadStmt->execute([$leadId]);
    }

    // 4b. Optional Admission Stage Update
    $newStage = trim($_POST['new_stage'] ?? '');
    $validStages = ['ENQUIRY', 'CUSTOMER', 'APPLICATION', 'OFFER RECEIVED', 'TUITION FEES PAID', 'ENROLLED'];
    if (!empty($newStage) && in_array($newStage, $validStages, true) && $newStage !== $lead['stage']) {
        $pdo->prepare("UPDATE leads SET stage = ? WHERE id = ?")->execute([$newStage, $leadId]);
        $pdo->prepare("INSERT INTO lead_stage_history (lead_id, from_stage, to_stage, changed_by, notes) VALUES (?, ?, ?, ?, ?)")
            ->execute([$leadId, $lead['stage'] ?? 'ENQUIRY', $newStage, $currentUser['id'], "Advanced during follow-up call: {$notes}"]);
    }

    // 5. If title is 'Need to Archive', soft archive the lead
    if ($interactionTitle['title'] === 'Need to Archive') {
        $archiveStmt = $pdo->prepare("
            UPDATE leads 
            SET is_archived = 1, archived_at = NOW(), archived_reason = ? 
            WHERE id = ?
        ");
        $archiveStmt->execute(["Counselor tagged as Need to Archive: {$notes}", $leadId]);
    }

    // 6. Log Activity
    log_activity(
        $currentUser['id'],
        $leadId,
        'Follow-up Logged',
        "Interaction [{$interactionTitle['title']}] recorded. Next follow-up scheduled for {$nextDate}."
    );

    $pdo->commit();
    set_flash('success', "Consultation follow-up recorded successfully! Next interaction set for <strong>" . date('d M Y', strtotime($nextDate)) . "</strong>.");
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    set_flash('danger', 'Error recording follow-up: ' . htmlspecialchars($e->getMessage()));
}

redirect($returnUrl);
