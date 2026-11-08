<?php
/**
 * Archive & Permanent Deletion Processing Engine
 * University-Level Education Consultancy CRM (EdrootsCRM)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$action = $_REQUEST['action'] ?? '';
$type = $_REQUEST['type'] ?? 'lead'; // lead or college_data
$id = (int)($_REQUEST['id'] ?? 0);
$reason = trim($_REQUEST['reason'] ?? '');
$currentUser = current_user();

if ($id <= 0) {
    set_flash('danger', 'Invalid record identifier.');
    redirect('archive/index.php');
}

if ($action === 'archive') {
    require_permission('archive', 'archive');
    if ($type === 'lead') {
        $stmt = $pdo->prepare("UPDATE leads SET is_archived = 1, archived_at = NOW(), archived_reason = ? WHERE id = ?");
        $stmt->execute([$reason ?: 'Archived by admissions office', $id]);
        log_activity($currentUser['id'], $id, 'Lead Archived', "Archived lead ID {$id}. Reason: {$reason}");
        set_flash('info', 'Student record moved to Archive repository.');
        redirect('leads/index.php');
    }
} elseif ($action === 'restore') {
    require_permission('archive', 'restore');
    if ($type === 'lead') {
        $stmt = $pdo->prepare("UPDATE leads SET is_archived = 0, archived_at = NULL, archived_reason = NULL WHERE id = ?");
        $stmt->execute([$id]);
        log_activity($currentUser['id'], $id, 'Lead Restored', "Restored lead ID {$id} back to active pool.");
        set_flash('success', 'Student record restored to Active Leads pool.');
        redirect('archive/index.php?tab=leads');
    } elseif ($type === 'college_data') {
        $stmt = $pdo->prepare("UPDATE college_data SET is_archived = 0 WHERE id = ?");
        $stmt->execute([$id]);
        set_flash('success', 'College student record restored to College Data pool.');
        redirect('archive/index.php?tab=college_data');
    }
} elseif ($action === 'delete_permanent') {
    require_permission('archive', 'delete');
    $justification = trim($_POST['justification'] ?? '');

    if (empty($justification) || strlen($justification) < 5) {
        set_flash('danger', 'Governance Error: A detailed typed justification is strictly mandatory for permanent record deletion.');
        redirect('archive/index.php');
    }

    try {
        $pdo->beginTransaction();

        if ($type === 'lead') {
            $fStmt = $pdo->prepare("SELECT id, full_name, lead_code FROM leads WHERE id = ?");
            $fStmt->execute([$id]);
            $lead = $fStmt->fetch();

            if ($lead) {
                // Log to immutable deletion_logs
                log_permanent_deletion('Lead', $lead['id'], $lead['full_name'] . ' (' . $lead['lead_code'] . ')', $justification);

                // Delete child relations
                $pdo->prepare("DELETE FROM followups WHERE lead_id = ?")->execute([$id]);
                $pdo->prepare("DELETE FROM lead_stage_history WHERE lead_id = ?")->execute([$id]);
                $pdo->prepare("DELETE FROM student_documents WHERE lead_id = ?")->execute([$id]);
                $pdo->prepare("DELETE FROM invoice_items WHERE invoice_id IN (SELECT id FROM invoices WHERE lead_id = ?)")->execute([$id]);
                $pdo->prepare("DELETE FROM payments WHERE lead_id = ?")->execute([$id]);
                $pdo->prepare("DELETE FROM invoices WHERE lead_id = ?")->execute([$id]);
                $pdo->prepare("DELETE FROM leads WHERE id = ?")->execute([$id]);
            }
        } elseif ($type === 'college_data') {
            $fStmt = $pdo->prepare("SELECT id, student_name, phone FROM college_data WHERE id = ?");
            $fStmt->execute([$id]);
            $cd = $fStmt->fetch();

            if ($cd) {
                log_permanent_deletion('College Data', $cd['id'], $cd['student_name'] . ' (' . $cd['phone'] . ')', $justification);
                $pdo->prepare("DELETE FROM college_data WHERE id = ?")->execute([$id]);
            }
        }

        $pdo->commit();
        set_flash('success', 'Record permanently deleted. Action recorded in immutable Deletion Audit Log.');
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        set_flash('danger', 'Error deleting record: ' . htmlspecialchars($e->getMessage()));
    }
    redirect('archive/index.php');
}

redirect('archive/index.php');
