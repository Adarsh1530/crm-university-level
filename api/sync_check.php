<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/plain');
echo "=== REQUIRED COLUMNS IN TABLES ===\n";

foreach (['followups', 'college_data', 'web_leads', 'invoices'] as $t) {
    echo "--- Table: $t ---\n";
    $cols = $pdo->query("SHOW COLUMNS FROM `$t`")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c) {
        if ($c['Null'] === 'NO' && $c['Default'] === null && $c['Extra'] !== 'auto_increment') {
            echo "REQUIRED: {$c['Field']} ({$c['Type']})\n";
        }
    }
}
