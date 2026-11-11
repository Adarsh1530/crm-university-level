<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../database/sync_edroots_data.php';

header('Content-Type: text/plain');
echo "=== TESTING COMPLETE SYNC V7 ===\n";

try {
    unset($_SESSION['edroots_manual_synced_v7']);
    sync_edroots_database_data($pdo);

    $cnt = $pdo->query("SELECT COUNT(*) FROM leads")->fetchColumn();
    echo "Total Leads in DB: " . $cnt . "\n";

    $leads = $pdo->query("SELECT id, lead_code, full_name, phone, branch_id, stage, secondary_status, is_customer, is_archived FROM leads")->fetchAll(PDO::FETCH_ASSOC);
    echo "Leads:\n";
    print_r($leads);

    $colCnt = $pdo->query("SELECT COUNT(*) FROM college_data")->fetchColumn();
    echo "College Data Count: " . $colCnt . "\n";

    $webCnt = $pdo->query("SELECT COUNT(*) FROM web_leads")->fetchColumn();
    echo "Web Leads Count: " . $webCnt . "\n";

    $docCnt = $pdo->query("SELECT COUNT(*) FROM general_documents")->fetchColumn();
    echo "General Documents Count: " . $docCnt . "\n";

    $invCnt = $pdo->query("SELECT COUNT(*) FROM invoices")->fetchColumn();
    echo "Invoices Count: " . $invCnt . "\n";

    $fCnt = $pdo->query("SELECT COUNT(*) FROM followups")->fetchColumn();
    echo "Followups Count: " . $fCnt . "\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
