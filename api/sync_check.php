<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../database/sync_edroots_data.php';

header('Content-Type: text/plain');
echo "=== TESTING SYNC V6 ===\n";

try {
    unset($_SESSION['edroots_manual_synced_v6']);
    sync_edroots_database_data($pdo);

    $cnt = $pdo->query("SELECT COUNT(*) FROM leads")->fetchColumn();
    echo "Lead count: " . $cnt . "\n";

    $leads = $pdo->query("SELECT id, lead_code, full_name, phone, branch_id, stage, secondary_status FROM leads")->fetchAll(PDO::FETCH_ASSOC);
    echo "Leads in DB:\n";
    print_r($leads);

    $unis = $pdo->query("SELECT id, name, country FROM universities")->fetchAll(PDO::FETCH_ASSOC);
    echo "Universities in DB:\n";
    print_r($unis);

    $docs = $pdo->query("SELECT id, title, category FROM general_documents")->fetchAll(PDO::FETCH_ASSOC);
    echo "General Documents in DB:\n";
    print_r($docs);

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
