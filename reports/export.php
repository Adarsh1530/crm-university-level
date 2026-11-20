<?php
/**
 * CSV Export Controller for CRM Reports
 * Enterprise University Admissions & Enrollment Management System (UniCRM)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$type = $_GET['type'] ?? 'leads';
if (!can('reports', 'export')) {
    set_flash('danger', 'You do not have permission to export this report data.');
    redirect('reports/index.php');
}

$filename = "unicrm_{$type}_report_" . date('Ymd_His') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

if ($type === 'students' || $type === 'leads') {
    fputcsv($output, ['Lead Code', 'Full Name', 'Email', 'Mobile', 'Preferred Country', 'Course', 'Intake Term', 'Intake Year', 'Prospectivity', 'Source', 'Counselor', 'Branch', 'Is Customer', 'Created Date']);

    $stmt = $pdo->query("
        SELECT l.lead_code, l.full_name, l.email, l.phone,
               l.preferred_country, l.preferred_course, l.intake_term, l.intake_year,
               COALESCE(l.prospectivity, l.priority, 'Warm') as prospect,
               l.source, u.name AS counselor_name, b.name AS branch_name,
               CASE WHEN l.is_customer = 1 THEN 'Yes' ELSE 'No' END as customer_status,
               l.created_at
        FROM leads l
        LEFT JOIN users u ON l.assigned_counselor_id = u.id
        LEFT JOIN branches b ON l.branch_id = b.id
        WHERE l.is_archived = 0
        ORDER BY l.id DESC
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, array_values($row));
    }

} elseif ($type === 'followups') {
    fputcsv($output, ['ID', 'Candidate Name', 'Mobile', 'Follow-up Date', 'Time', 'Channel', 'Title / Outcome', 'Notes', 'Next Date', 'Counselor']);

    $stmt = $pdo->query("
        SELECT f.id, 
               l.full_name AS candidate_name, l.phone,
               f.followup_date, f.followup_time, f.interaction_type,
               it.title as interaction_title,
               f.notes, f.next_followup_date, u.name AS counselor_name
        FROM followups f
        JOIN leads l ON f.lead_id = l.id
        LEFT JOIN interaction_titles it ON f.interaction_title_id = it.id
        LEFT JOIN users u ON f.counselor_id = u.id
        ORDER BY f.followup_date DESC
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, array_values($row));
    }
}

fclose($output);
exit;
