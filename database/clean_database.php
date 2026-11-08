<?php
/**
 * Database Cleanup Script
 * Clears all demo, dummy, sample, and test data.
 * Keeps all user accounts and login credentials unchanged.
 */
require_once __DIR__ . '/../config/database.php';

echo "=== STARTING DATABASE CLEANUP ===\n";

try {
    // 1. Verify users table before touching anything
    $userCountBefore = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($userCountBefore === 0) {
        throw new Exception("Users table is empty! Aborting cleanup to prevent credential loss.");
    }
    echo "Found {$userCountBefore} user accounts to preserve.\n";

    // 2. Disable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    // 3. Tables to truncate
    $tablesToClean = [
        'leads',
        'college_data',
        'college_data_imports',
        'web_leads',
        'followups',
        'invoices',
        'invoice_items',
        'payments',
        'general_documents',
        'student_documents',
        'tasks',
        'notifications',
        'activity_logs',
        'deletion_logs',
        'lead_stage_history',
        'student_stage_history',
        'students',
        'points_transactions'
    ];

    foreach ($tablesToClean as $table) {
        // Check if table exists
        $exists = $pdo->query("SHOW TABLES LIKE '{$table}'")->fetch();
        if ($exists) {
            $pdo->exec("TRUNCATE TABLE `{$table}`");
            echo "  [CLEANED] Truncated table: {$table}\n";
        }
    }

    // 4. Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    // 5. Verify user accounts are intact
    $userCountAfter = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($userCountAfter !== $userCountBefore) {
        throw new Exception("CRITICAL ERROR: User count changed from {$userCountBefore} to {$userCountAfter}!");
    }
    echo "  [OK] User credentials verified: {$userCountAfter} active staff accounts preserved.\n";

    // 6. Log system initialization
    $superAdminId = (int)$pdo->query("SELECT id FROM users WHERE role_id = 1 LIMIT 1")->fetchColumn();
    if ($superAdminId) {
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, lead_id, action, details, created_at) VALUES (?, NULL, 'System Initialized', 'Database cleaned and prepared for live production use.', NOW())");
        $stmt->execute([$superAdminId]);
        echo "  [OK] Added clean system initialization activity log.\n";
    }

    echo "\n=== CLEANUP COMPLETED SUCCESSFULLY ===\n";

} catch (Exception $e) {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
