<?php
/**
 * Fast Inline / Modal Metadata & Action Updater for Leads
 * Core CRM Operational Actions:
 * - 1-Click Top Update (Active Status, Prospectivity, Notes)
 * - Phone Cross-Checking & Verification (is_phone_verified)
 * - Call Data Logging (call_logs integration)
 * - Counselor Escalation (Qualification routing)
 * - Prospectivity & Cycle Updates
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_permission('leads', 'edit');

$leadId = (int)($_POST['lead_id'] ?? 0);
$field = trim($_POST['field'] ?? '');
$returnUrl = $_POST['return_url'] ?? base_url('leads/index.php');
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
$currentUser = current_user();

if ($leadId <= 0 || !can_access_lead($leadId)) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Access denied or invalid lead.']);
        exit;
    }
    set_flash('danger', 'Access denied.');
    redirect($returnUrl);
}

verify_csrf_token($_POST['csrf_token'] ?? '');

try {
    if ($field === 'top_update') {
        // 1-Click Top Update action from header or lead view
        $activeStatus = trim($_POST['active_status'] ?? 'Contacted');
        $prospectivity = trim($_POST['prospectivity'] ?? 'Warm');
        $notes = trim($_POST['notes'] ?? '');
        $isLongCycle = isset($_POST['is_long_cycle']) ? 1 : 0;

        $validStatuses = ['New Lead', 'Call In Progress', 'Contacted', 'Follow-up Scheduled', 'Prospect Qualified', 'Escalated to Counselor', 'Converted', 'Disqualified'];
        if (!in_array($activeStatus, $validStatuses)) {
            $activeStatus = 'Contacted';
        }
        if (!in_array($prospectivity, ['Hot', 'Warm', 'Cold', 'High', 'Moderate', 'Low'])) {
            $prospectivity = 'Warm';
        }

        // Map to High/Moderate/Low for existing priority column compatibility
        $priorityMap = ['Hot' => 'High', 'Warm' => 'Moderate', 'Cold' => 'Low', 'High' => 'High', 'Moderate' => 'Moderate', 'Low' => 'Low'];
        $priorityVal = $priorityMap[$prospectivity] ?? 'Moderate';

        $stmt = $pdo->prepare("
            UPDATE leads 
            SET active_status = ?, prospectivity = ?, priority = ?, is_long_cycle = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$activeStatus, $prospectivity, $priorityVal, $isLongCycle, $leadId]);

        // If optional call log is submitted simultaneously
        if (!empty($_POST['log_call_now'])) {
            $callDir = trim($_POST['call_direction'] ?? 'Outbound');
            $callStat = trim($_POST['call_status'] ?? 'Connected');
            $duration = (int)($_POST['duration_seconds'] ?? 60);
            $phoneDialed = trim($_POST['phone_dialed'] ?? '');
            
            if (empty($phoneDialed)) {
                $pStmt = $pdo->prepare("SELECT phone FROM leads WHERE id = ?");
                $pStmt->execute([$leadId]);
                $phoneDialed = $pStmt->fetchColumn() ?: '';
            }

            $stmtCall = $pdo->prepare("
                INSERT INTO call_logs (lead_id, caller_id, call_direction, call_status, duration_seconds, phone_dialed, notes, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmtCall->execute([$leadId, $currentUser['id'], $callDir, $callStat, $duration, $phoneDialed, $notes]);
        }

        log_activity($currentUser['id'], $leadId, 'Top Update Executed', "Updated status to '{$activeStatus}', prospectivity to '{$prospectivity}'. Note: {$notes}");

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'active_status' => $activeStatus, 'prospectivity' => $prospectivity, 'message' => 'Record successfully updated.']);
            exit;
        }

        set_flash('success', "Lead record updated successfully: Status <strong>{$activeStatus}</strong> &bull; Prospectivity <strong>{$prospectivity}</strong>.");
        redirect($returnUrl);

    } elseif ($field === 'phone_verify') {
        // Toggle phone number verification
        $statusStmt = $pdo->prepare("SELECT is_phone_verified, phone FROM leads WHERE id = ?");
        $statusStmt->execute([$leadId]);
        $row = $statusStmt->fetch();
        $newStatus = ($row && $row['is_phone_verified'] == 1) ? 0 : 1;

        $upd = $pdo->prepare("
            UPDATE leads 
            SET is_phone_verified = ?, 
                phone_verified_at = " . ($newStatus ? 'NOW()' : 'NULL') . ", 
                phone_verified_by = " . ($newStatus ? '?' : 'NULL') . "
            WHERE id = ?
        ");
        if ($newStatus) {
            $upd->execute([$newStatus, $currentUser['id'], $leadId]);
            $actionText = 'Cross-checked and Verified';
        } else {
            $upd->execute([$newStatus, $leadId]);
            $actionText = 'Reset to Pending Verification';
        }

        log_activity($currentUser['id'], $leadId, 'Phone Number Verification', "Phone number {$row['phone']} was {$actionText}.");

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'is_verified' => $newStatus, 'message' => "Phone number is now " . ($newStatus ? 'Verified' : 'Pending Verification')]);
            exit;
        }

        set_flash('success', "Phone number {$actionText} successfully.");
        redirect($returnUrl);

    } elseif ($field === 'log_call') {
        // Standalone Call Data entry
        $callDir = trim($_POST['call_direction'] ?? 'Outbound');
        $callStat = trim($_POST['call_status'] ?? 'Connected');
        $duration = (int)($_POST['duration_seconds'] ?? 0);
        $phoneDialed = trim($_POST['phone_dialed'] ?? '');
        $notes = trim($_POST['call_notes'] ?? $_POST['notes'] ?? '');

        if (empty($phoneDialed)) {
            $pStmt = $pdo->prepare("SELECT phone FROM leads WHERE id = ?");
            $pStmt->execute([$leadId]);
            $phoneDialed = $pStmt->fetchColumn() ?: '';
        }

        $stmtCall = $pdo->prepare("
            INSERT INTO call_logs (lead_id, caller_id, call_direction, call_status, duration_seconds, phone_dialed, notes, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmtCall->execute([$leadId, $currentUser['id'], $callDir, $callStat, $duration, $phoneDialed, $notes]);

        // Auto-update lead active status if new
        $pdo->prepare("UPDATE leads SET active_status = 'Contacted', updated_at = NOW() WHERE id = ? AND active_status = 'New Lead'")->execute([$leadId]);

        log_activity($currentUser['id'], $leadId, 'Call Logged', "Logged {$callDir} call ({$callStat}, {$duration}s) to {$phoneDialed}.");

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Call data logged successfully.']);
            exit;
        }

        set_flash('success', "Call data recorded successfully.");
        redirect($returnUrl);

    } elseif ($field === 'escalate_counselor') {
        // Counselor Escalation Rule: Route qualified prospect to designated counselor
        $counselorId = (int)($_POST['counselor_id'] ?? 0);
        if ($counselorId <= 0) {
            // Pick first active senior counselor if none selected
            $cStmt = $pdo->query("SELECT id FROM users WHERE role_id = 3 AND status = 'Active' ORDER BY id ASC LIMIT 1");
            $counselorId = (int)$cStmt->fetchColumn() ?: 3;
        }

        $stmtEsc = $pdo->prepare("
            UPDATE leads 
            SET assigned_counselor_id = ?, active_status = 'Escalated to Counselor', prospectivity = 'Hot', priority = 'High', updated_at = NOW() 
            WHERE id = ?
        ");
        $stmtEsc->execute([$counselorId, $leadId]);

        $cName = $pdo->query("SELECT name FROM users WHERE id = {$counselorId}")->fetchColumn() ?: 'Counselor';
        log_activity($currentUser['id'], $leadId, 'Counselor Escalation', "Prospect escalated and routed to counselor {$cName} based on prospectivity criteria.");

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'counselor_name' => $cName, 'message' => "Lead routed to {$cName}."]);
            exit;
        }

        set_flash('success', "Lead successfully escalated and routed to <strong>{$cName}</strong>.");
        redirect($returnUrl);

    } elseif ($field === 'prospectivity' || $field === 'priority') {
        $val = trim($_POST['priority'] ?? $_POST['prospectivity'] ?? 'Moderate');
        if (!in_array($val, ['Hot', 'Warm', 'Cold', 'High', 'Moderate', 'Low'])) {
            $val = 'Moderate';
        }
        $priorityMap = ['Hot' => 'High', 'Warm' => 'Moderate', 'Cold' => 'Low', 'High' => 'High', 'Moderate' => 'Moderate', 'Low' => 'Low'];
        $priorityVal = $priorityMap[$val] ?? 'Moderate';

        $stmt = $pdo->prepare("UPDATE leads SET prospectivity = ?, priority = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$val, $priorityVal, $leadId]);

        log_activity($currentUser['id'], $leadId, 'Prospectivity Updated', "Changed prospectivity priority to {$val}.");

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'field' => 'prospectivity', 'value' => $val]);
            exit;
        }

        set_flash('success', "Prospectivity successfully updated to <strong>{$val}</strong>.");
        redirect($returnUrl);

    } elseif ($field === 'intake') {
        $term = trim($_POST['intake_term'] ?? 'September');
        $year = (int)($_POST['intake_year'] ?? date('Y'));

        $stmt = $pdo->prepare("UPDATE leads SET intake_term = ?, intake_year = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$term, $year, $leadId]);

        log_activity($currentUser['id'], $leadId, 'Intake Updated', "Changed target intake to {$term} {$year}.");

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'field' => 'intake', 'term' => $term, 'year' => $year]);
            exit;
        }

        set_flash('success', "Target InTake successfully updated to <strong>{$term} {$year}</strong>.");
        redirect($returnUrl);

    } else {
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid update field requested.']);
            exit;
        }
        redirect($returnUrl);
    }
} catch (Exception $e) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
    set_flash('danger', 'Update failed: ' . $e->getMessage());
    redirect($returnUrl);
}
