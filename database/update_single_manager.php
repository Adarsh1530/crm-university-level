<?php
/**
 * Update System to Single Manager Architecture
 * Removes location mentions from roles and designations
 */
require_once __DIR__ . '/../config/database.php';
global $pdo;

echo "=== UPDATING TO SINGLE ADMISSIONS MANAGER (NO PLACES) ===\n";

// 1. Update Role Labels
$pdo->exec("UPDATE roles SET label = 'Admissions Manager', description = 'Manages team operations, counselors, lead assignments, and reporting.' WHERE name = 'manager'");
$pdo->exec("UPDATE roles SET label = 'Admissions Counselor', description = 'Strictly isolated access to assigned student leads, follow-ups, and tasks.' WHERE name = 'counselor'");
echo "  [OK] Roles updated to 'Admissions Manager' and 'Admissions Counselor'\n";

// 2. Update Marcus Sterling as the ONLY Manager (No place names)
$pdo->exec("
    UPDATE users 
    SET name = 'Marcus Sterling',
        email = 'manager@university.edu',
        designation = 'Admissions Manager',
        role_id = 2,
        manager_id = NULL,
        reporting_manager_id = NULL
    WHERE id = 2
");
echo "  [OK] User 2 (Marcus Sterling) set as single Admissions Manager\n";

// 3. Update Priya Sharma to Senior Admissions Counselor (reporting to Marcus)
$pdo->exec("
    UPDATE users 
    SET name = 'Priya Sharma',
        email = 'priya@university.edu',
        designation = 'Senior Admissions Counselor',
        role_id = 3,
        manager_id = 2,
        reporting_manager_id = 2
    WHERE id = 3
");
echo "  [OK] User 3 (Priya Sharma) set as Senior Admissions Counselor (reports to Marcus)\n";

// 4. Update David Miller (Admissions Counselor, no place)
$pdo->exec("
    UPDATE users 
    SET name = 'David Miller',
        email = 'david@university.edu',
        designation = 'Admissions Counselor',
        role_id = 3,
        manager_id = 2,
        reporting_manager_id = 2
    WHERE id = 4
");
echo "  [OK] User 4 (David Miller) updated (no place)\n";

// 5. Update Sarah Jenkins (Academic Advisor, no place)
$pdo->exec("
    UPDATE users 
    SET name = 'Sarah Jenkins',
        email = 'sarah@university.edu',
        designation = 'Academic Advisor',
        role_id = 3,
        manager_id = 2,
        reporting_manager_id = 2
    WHERE id = 5
");
echo "  [OK] User 5 (Sarah Jenkins) updated (no place)\n";

// 6. Update Alex Chen (Admissions Counselor, no place)
$pdo->exec("
    UPDATE users 
    SET name = 'Alex Chen',
        email = 'alex@university.edu',
        designation = 'Admissions Counselor',
        role_id = 3,
        manager_id = 2,
        reporting_manager_id = 2
    WHERE id = 6
");
echo "  [OK] User 6 (Alex Chen) updated (no place)\n";

// 7. Update Ananya Nair (Admissions Counselor, no place)
$pdo->exec("
    UPDATE users 
    SET name = 'Ananya Nair',
        email = 'ananya@university.edu',
        designation = 'Admissions Counselor',
        role_id = 3,
        manager_id = 2,
        reporting_manager_id = 2
    WHERE id = 7
");
echo "  [OK] User 7 (Ananya Nair) updated (no place)\n";

// Ensure all leads assigned to counselors are visible to Marcus Sterling as manager
$pdo->exec("UPDATE leads SET branch_id = COALESCE(branch_id, 1)");

echo "=== UPDATE COMPLETED SUCCESSFULLY ===\n";
