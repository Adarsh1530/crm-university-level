<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/plain');
echo "=== DEBUGGING LEAD INSERTION ===\n";

$l = [
    'id' => 1, 'lead_code' => 'LED-54879', 'full_name' => 'Nileena', 'phone' => '9074134910', 'email' => 'nileena.54879@gmail.com',
    'branch_id' => 2, 'counselor_id' => 2, 'degree' => 'B.Com-2017', 'college' => 'Perinthalmanna Arts & Science',
    'country' => 'United Kingdom', 'course' => 'MSc International Business',
    'priority' => 'Moderate', 'source' => 'Ex Student', 'stage' => 'ENQUIRY', 'secondary_status' => 'Active',
    'notes' => 'Lead - Information shared', 'is_customer' => 0, 'is_archived' => 0, 'created_at' => '2021-08-09 11:25:00'
];

try {
    $stmtLead = $pdo->prepare("
        INSERT INTO leads (
            id, lead_code, full_name, phone, email, branch_id, assigned_counselor_id,
            stage, secondary_status, priority, source, preferred_country, preferred_course,
            highest_qualification, college_name, notes, is_customer, is_archived, created_by, created_at, assigned_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, 1, ?, ?
        )
    ");
    $stmtLead->execute([
        $l['id'], $l['lead_code'], $l['full_name'], $l['phone'], $l['email'],
        $l['branch_id'], $l['counselor_id'], $l['stage'], $l['secondary_status'],
        $l['priority'], $l['source'], $l['country'], $l['course'],
        $l['degree'], $l['college'], $l['notes'], $l['is_customer'], $l['is_archived'],
        $l['created_at'], $l['created_at']
    ]);
    echo "SUCCESS: Inserted Nileena!\n";
} catch (Exception $e) {
    echo "ERROR ON LEAD INSERT: " . $e->getMessage() . "\n";
}

try {
    $pdo->exec("
        INSERT INTO general_documents (id, title, category, country, description, file_path, file_name, file_size, uploaded_by)
        VALUES 
        (1, 'Edroots CRM - Official User Guide & Training Manual', 'General Guidelines', 'Global', 'Official user manual and step-by-step operating guidelines for Edroots CRM.', 'assets/docs/edroots_crm_user_manual.pdf', 'edroots_crm_user_manual.pdf', 2393736, 1)
    ");
    echo "SUCCESS: Inserted general doc!\n";
} catch (Exception $e) {
    echo "ERROR ON DOC INSERT: " . $e->getMessage() . "\n";
}
