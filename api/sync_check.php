<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/plain');
echo "=== DIRECT INSERT TEST ===\n";

try {
    $stmt = $pdo->prepare("
        INSERT INTO leads (
            id, lead_code, full_name, phone, email, branch_id, assigned_counselor_id,
            stage, secondary_status, priority, source, preferred_country, preferred_course,
            highest_qualification, college_name, is_customer, is_archived, created_by, created_at, assigned_at
        ) VALUES (
            1, 'LED-54879', 'Nileena', '9074134910', 'nileena@edroots.in', 2, 2,
            'ENQUIRY', 'Lead - Information shared', 'Moderate', 'Ex Student', 'United Kingdom', 'MSc International Business',
            'B.Com-2017', 'Perinthalmanna Arts & Science', 0, 0, 1, '2021-08-09 11:25:00', '2021-08-09 11:25:00'
        )
    ");
    $stmt->execute();
    echo "SUCCESS: Inserted Nileena! ID: " . $pdo->lastInsertId() . "\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
