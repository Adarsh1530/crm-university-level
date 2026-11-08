<?php
/**
 * CSV Export Controller for CRM Reports
 * University-Level Student CRM Demo
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$type = $_GET['type'] ?? 'students';
$moduleMap = [
    'students' => 'students',
    'leads' => 'leads',
    'followups' => 'followups',
    'staff' => 'staff_monitoring'
];
$targetMod = $moduleMap[$type] ?? 'reports';
if (!can('reports', 'export') && !can($targetMod, 'export')) {
    set_flash('danger', 'You do not have permission to export this report data.');
    redirect('reports/index.php');
}

$filename = "unicrm_{$type}_report_" . date('Ymd_His') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

if ($type === 'students') {
    // Header row
    fputcsv($output, ['Student Code', 'Full Name', 'Email', 'Mobile', 'Country', 'State', 'City', 'Qualification', 'University', 'Course', 'Intake', 'Stage', 'Status', 'Priority', 'Staff', 'Points', 'Enquiry Date']);

    $stmt = $pdo->query("
        SELECT s.student_code, s.full_name, s.email, s.mobile_number, s.country, s.state, s.city,
               s.academic_qualification, u.name AS uni_name, c.title AS course_title,
               s.preferred_intake, st.name AS stage_name, s.admission_status, s.priority_level,
               usr.name AS staff_name, s.points_balance, s.enquiry_date
        FROM students s
        LEFT JOIN universities u ON s.university_id = u.id
        LEFT JOIN courses c ON s.course_id = c.id
        LEFT JOIN admission_stages st ON s.stage_id = st.id
        LEFT JOIN users usr ON s.assigned_staff_id = usr.id
        ORDER BY s.id DESC
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, array_values($row));
    }

} elseif ($type === 'leads') {
    fputcsv($output, ['Lead Code', 'Full Name', 'Email', 'Mobile', 'Interested Course', 'Country', 'Lead Source', 'Status', 'Priority', 'Assigned Staff', 'Created At']);

    $stmt = $pdo->query("
        SELECT l.lead_code, l.full_name, l.email, l.mobile_number, l.interested_course,
               l.interested_country, l.lead_source, l.status, l.priority, u.name AS staff_name, l.created_at
        FROM leads l
        LEFT JOIN users u ON l.assigned_staff_id = u.id
        ORDER BY l.id DESC
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, array_values($row));
    }

} elseif ($type === 'followups') {
    fputcsv($output, ['ID', 'Target Student/Lead', 'Follow-up Date', 'Time', 'Channel', 'Priority', 'Status', 'Notes', 'Student Response', 'Next Date', 'Assigned Staff']);

    $stmt = $pdo->query("
        SELECT f.id, 
               COALESCE(s.full_name, l.full_name, 'Unlinked') AS target_name,
               f.followup_date, f.followup_time, f.communication_type, f.priority, f.status,
               f.notes, f.student_response, f.next_followup_date, u.name AS staff_name
        FROM followups f
        LEFT JOIN students s ON f.student_id = s.id
        LEFT JOIN leads l ON f.lead_id = l.id
        LEFT JOIN users u ON f.assigned_staff_id = u.id
        ORDER BY f.followup_date DESC
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, array_values($row));
    }

} elseif ($type === 'staff') {
    fputcsv($output, ['Staff Name', 'Designation', 'Email', 'Role', 'Assigned Students', 'Assigned Leads', 'Completed Follow-ups', 'Confirmed Admissions']);

    $stmt = $pdo->query("
        SELECT u.name, u.designation, u.email, r.label AS role_label,
               COUNT(DISTINCT s.id) AS assigned_students,
               COUNT(DISTINCT l.id) AS assigned_leads,
               COUNT(DISTINCT CASE WHEN f.status = 'Completed' THEN f.id END) AS completed_fu,
               COUNT(DISTINCT CASE WHEN s.stage_id = 8 THEN s.id END) AS confirmed
        FROM users u
        JOIN roles r ON u.role_id = r.id
        LEFT JOIN students s ON u.id = s.assigned_staff_id
        LEFT JOIN leads l ON u.id = l.assigned_staff_id
        LEFT JOIN followups f ON u.id = f.assigned_staff_id
        WHERE u.role_id IN (4, 5)
        GROUP BY u.id
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, array_values($row));
    }
}

fclose($output);
exit;
