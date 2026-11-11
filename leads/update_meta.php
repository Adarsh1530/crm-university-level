<?php
/**
 * Fast Inline / Modal Metadata Updater for Leads
 * Supports 1-Click Update for Prospectivity and InTake (EdrootsCRM Manual Section 9.2.3 & 9.2.4)
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
    if ($field === 'prospectivity' || $field === 'priority') {
        $val = trim($_POST['priority'] ?? $_POST['prospectivity'] ?? 'Moderate');
        if (!in_array($val, ['High', 'Moderate', 'Low'])) {
            $val = 'Moderate';
        }

        $stmt = $pdo->prepare("UPDATE leads SET priority = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$val, $leadId]);

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
