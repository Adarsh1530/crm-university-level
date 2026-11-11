<?php
/**
 * Real-time Phone Duplicate Verification Endpoint
 * EdrootsCRM User Manual Page 13 (Section 9.2.7)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['error' => 'Unauthenticated']);
    exit;
}

$phone = trim($_GET['phone'] ?? $_POST['phone'] ?? '');
$clean = normalize_phone($phone);

if (strlen($clean) < 10) {
    echo json_encode([
        'valid' => false,
        'exists' => false,
        'message' => 'Please enter a valid 10-digit mobile number.'
    ]);
    exit;
}

$duplicate = check_phone_duplicate($clean);

if ($duplicate) {
    echo json_encode([
        'valid' => false,
        'exists' => true,
        'lead_id' => (int)$duplicate['id'],
        'lead_code' => $duplicate['lead_code'],
        'full_name' => $duplicate['full_name'],
        'branch_name' => $duplicate['branch_name'] ?? 'Branch',
        'counselor_name' => $duplicate['counselor_name'] ?? 'Counselor',
        'is_customer' => (bool)$duplicate['is_customer'],
        'message' => 'Already Exists! Click here to take a look'
    ]);
    exit;
}

echo json_encode([
    'valid' => true,
    'exists' => false,
    'message' => 'Mobile number is unique and available.'
]);
