<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/plain');
echo "=== TESTING SYNC ===\n";

try {
    // Unset the session flag to force sync
    unset($_SESSION['edroots_manual_synced_v5']);
    
    // Test lead count before
    $cnt = $pdo->query("SELECT COUNT(*) FROM leads")->fetchColumn();
    echo "Current lead count in DB: " . $cnt . "\n";
    
    // Check columns in leads table
    $cols = $pdo->query("SHOW COLUMNS FROM leads")->fetchAll(PDO::FETCH_COLUMN);
    echo "Columns in leads table:\n" . implode(', ', $cols) . "\n\n";

    // Call sync function directly
    require_once __DIR__ . '/../database/sync_edroots_data.php';
    sync_edroots_database_data($pdo);

    $cntAfter = $pdo->query("SELECT COUNT(*) FROM leads")->fetchColumn();
    echo "Lead count after sync: " . $cntAfter . "\n";

    $leads = $pdo->query("SELECT id, lead_code, full_name, phone, branch_id FROM leads LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    echo "Leads after sync:\n";
    print_r($leads);

} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
