<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/plain');
echo "=== ENUM VALUES FOR SECONDARY_STATUS ===\n";

$col = $pdo->query("SHOW COLUMNS FROM leads LIKE 'secondary_status'")->fetch(PDO::FETCH_ASSOC);
print_r($col);
