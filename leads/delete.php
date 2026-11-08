<?php
/**
 * Delete Lead
 * University-Level Student CRM Demo
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_permission('leads', 'delete');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('danger', 'Invalid lead ID.');
    redirect('leads/index.php');
}

$currentUser = current_user();

$stmt = $pdo->prepare("SELECT id, full_name, lead_code FROM leads WHERE id = ?");
$stmt->execute([$id]);
$lead = $stmt->fetch();

if (!$lead) {
    set_flash('danger', 'Lead record not found.');
    redirect('leads/index.php');
}

try {
    $del = $pdo->prepare("DELETE FROM leads WHERE id = ?");
    $del->execute([$id]);

    log_activity($currentUser['id'], null, 'Lead Deleted', "Deleted lead record {$lead['full_name']} ({$lead['lead_code']}).");
    set_flash('success', "Lead <strong>{$lead['full_name']}</strong> ({$lead['lead_code']}) deleted successfully.");
} catch (Exception $e) {
    set_flash('danger', 'Error deleting lead: ' . $e->getMessage());
}

redirect('leads/index.php');
