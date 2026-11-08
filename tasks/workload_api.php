<?php
/**
 * Live Counselor Workload Index API
 * Returns the count of scheduled follow-ups for a counselor on a given date
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'error' => 'Unauthenticated']);
    exit;
}

$counselorId = isset($_GET['counselor_id']) ? (int)$_GET['counselor_id'] : 0;
$date = trim($_GET['date'] ?? '');

if ($counselorId <= 0 || empty($date)) {
    echo json_encode(['success' => false, 'count' => 0]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM followups 
        WHERE counselor_id = ? AND scheduled_date = ? AND status = 'Pending'
    ");
    $stmt->execute([$counselorId, $date]);
    $count = (int)$stmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'count' => $count,
        'counselor_id' => $counselorId,
        'date' => $date,
        'workload_level' => $count > 10 ? 'High' : ($count > 5 ? 'Moderate' : 'Light')
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
