<?php
/**
 * Daily Follow-up Workload Checker Endpoint
 * EdrootsCRM User Manual Page 20 (Section 9.4.3)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['error' => 'Unauthenticated']);
    exit;
}

$currentUser = current_user();
$date = trim($_GET['date'] ?? $_POST['date'] ?? '');
$counselorId = (int)($_GET['counselor_id'] ?? $_POST['counselor_id'] ?? $currentUser['id']);

if (empty($date)) {
    echo json_encode(['count' => 0, 'formatted_date' => '', 'message' => '']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM followups 
        WHERE scheduled_date = ? AND status = 'Pending' AND counselor_id = ?
    ");
    $stmt->execute([$date, $counselorId]);
    $count = (int)$stmt->fetchColumn();

    $dt = new DateTime($date);
    $fmtDate = $dt->format('d-m-Y');

    echo json_encode([
        'date' => $date,
        'formatted_date' => $fmtDate,
        'count' => $count,
        'message' => "Total Scheduled Followups on {$fmtDate} is: {$count}"
    ]);
} catch (Exception $e) {
    echo json_encode(['count' => 0, 'error' => $e->getMessage()]);
}
