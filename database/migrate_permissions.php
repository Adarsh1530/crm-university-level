<?php
/**
 * Migration Script: Dynamic Role & Permissions System
 * Creates `role_permissions` and `user_permissions` tables and populates default baseline permissions.
 */

require_once __DIR__ . '/../config/database.php';

echo "Running Permissions Migration...\n";

// 1. Create role_permissions
$pdo->exec("
CREATE TABLE IF NOT EXISTS `role_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `module` varchar(50) NOT NULL,
  `can_view` tinyint(1) NOT NULL DEFAULT 0,
  `can_add` tinyint(1) NOT NULL DEFAULT 0,
  `can_edit` tinyint(1) NOT NULL DEFAULT 0,
  `can_delete` tinyint(1) NOT NULL DEFAULT 0,
  `can_assign` tinyint(1) NOT NULL DEFAULT 0,
  `can_approve` tinyint(1) NOT NULL DEFAULT 0,
  `can_export` tinyint(1) NOT NULL DEFAULT 0,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_role_module` (`role_id`, `module`),
  KEY `fk_rp_roles` (`role_id`),
  CONSTRAINT `fk_rp_roles` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// 2. Create user_permissions (Individual User Overrides)
$pdo->exec("
CREATE TABLE IF NOT EXISTS `user_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `module` varchar(50) NOT NULL,
  `can_view` tinyint(1) NOT NULL DEFAULT 0,
  `can_add` tinyint(1) NOT NULL DEFAULT 0,
  `can_edit` tinyint(1) NOT NULL DEFAULT 0,
  `can_delete` tinyint(1) NOT NULL DEFAULT 0,
  `can_assign` tinyint(1) NOT NULL DEFAULT 0,
  `can_approve` tinyint(1) NOT NULL DEFAULT 0,
  `can_export` tinyint(1) NOT NULL DEFAULT 0,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_module` (`user_id`, `module`),
  KEY `fk_up_users` (`user_id`),
  CONSTRAINT `fk_up_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

echo "Tables created successfully.\n";

// Modules list
$modules = [
    'dashboard'        => 'Main Executive Dashboard',
    'students'         => 'Student Management & Profiles',
    'leads'            => 'Lead Management & Enquiries',
    'followups'        => 'Follow-up Management',
    'admissions'       => 'Admission Stages & Pipeline',
    'staff'            => 'Staff & User Account Management',
    'staff_monitoring' => 'Staff Supervision & Monitoring',
    'tasks'            => 'Task Management',
    'points'           => 'Points & Benefits Rewards',
    'reports'          => 'Reports & Enrolment Analytics',
    'notifications'    => 'Notification & Alert Center'
];

// Baseline defaults for roles:
// 1: Super Admin (has full access hardcoded bypass anyway, but populate for completeness)
// 2: Administrator
// 3: Manager (Admissions Manager)
// 4: Team Leader
// 5: Counsellor

$roleDefaults = [
    // 2: Administrator (Full access across modules)
    2 => [
        'dashboard'        => [1, 1, 1, 1, 1, 1, 1],
        'students'         => [1, 1, 1, 1, 1, 1, 1],
        'leads'            => [1, 1, 1, 1, 1, 1, 1],
        'followups'        => [1, 1, 1, 1, 1, 1, 1],
        'admissions'       => [1, 1, 1, 1, 1, 1, 1],
        'staff'            => [1, 1, 1, 1, 1, 1, 1],
        'staff_monitoring' => [1, 1, 1, 1, 1, 1, 1],
        'tasks'            => [1, 1, 1, 1, 1, 1, 1],
        'points'           => [1, 1, 1, 1, 1, 1, 1],
        'reports'          => [1, 1, 1, 1, 1, 1, 1],
        'notifications'    => [1, 1, 1, 1, 1, 1, 1],
    ],
    // 3: Admissions Manager
    3 => [
        'dashboard'        => [1, 0, 0, 0, 0, 0, 1],
        'students'         => [1, 1, 1, 1, 1, 1, 1],
        'leads'            => [1, 1, 1, 1, 1, 1, 1],
        'followups'        => [1, 1, 1, 1, 1, 1, 1],
        'admissions'       => [1, 1, 1, 0, 1, 1, 1],
        'staff'            => [1, 0, 0, 0, 0, 0, 0], // Can view staff list
        'staff_monitoring' => [1, 0, 1, 0, 1, 1, 1], // Full supervision
        'tasks'            => [1, 1, 1, 1, 1, 1, 1],
        'points'           => [1, 1, 1, 0, 1, 1, 1],
        'reports'          => [1, 0, 0, 0, 0, 0, 1],
        'notifications'    => [1, 0, 1, 1, 0, 0, 0],
    ],
    // 4: Team Leader
    4 => [
        'dashboard'        => [1, 0, 0, 0, 0, 0, 0],
        'students'         => [1, 1, 1, 0, 1, 1, 1],
        'leads'            => [1, 1, 1, 0, 1, 1, 1],
        'followups'        => [1, 1, 1, 0, 1, 1, 1],
        'admissions'       => [1, 1, 1, 0, 1, 1, 0],
        'staff'            => [0, 0, 0, 0, 0, 0, 0], // Hidden by default
        'staff_monitoring' => [1, 0, 0, 0, 0, 0, 0], // Team oversight
        'tasks'            => [1, 1, 1, 0, 1, 1, 0],
        'points'           => [1, 1, 0, 0, 0, 0, 0],
        'reports'          => [1, 0, 0, 0, 0, 0, 0],
        'notifications'    => [1, 0, 1, 0, 0, 0, 0],
    ],
    // 5: Admissions Counsellor
    5 => [
        'dashboard'        => [1, 0, 0, 0, 0, 0, 0],
        'students'         => [1, 1, 1, 0, 0, 0, 0],
        'leads'            => [1, 1, 1, 0, 0, 0, 0],
        'followups'        => [1, 1, 1, 0, 0, 0, 0],
        'admissions'       => [1, 0, 1, 0, 0, 0, 0], // Can view and update stage
        'staff'            => [0, 0, 0, 0, 0, 0, 0], // Restricted
        'staff_monitoring' => [0, 0, 0, 0, 0, 0, 0], // Restricted
        'tasks'            => [1, 1, 1, 0, 0, 0, 0],
        'points'           => [1, 0, 0, 0, 0, 0, 0], // Can view point catalog
        'reports'          => [0, 0, 0, 0, 0, 0, 0], // Restricted
        'notifications'    => [1, 0, 1, 0, 0, 0, 0],
    ]
];

$stmt = $pdo->prepare("
    INSERT INTO role_permissions (role_id, module, can_view, can_add, can_edit, can_delete, can_assign, can_approve, can_export)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        can_view = VALUES(can_view),
        can_add = VALUES(can_add),
        can_edit = VALUES(can_edit),
        can_delete = VALUES(can_delete),
        can_assign = VALUES(can_assign),
        can_approve = VALUES(can_approve),
        can_export = VALUES(can_export)
");

foreach ($roleDefaults as $roleId => $modPerms) {
    foreach ($modPerms as $module => $p) {
        $stmt->execute([
            $roleId, $module,
            $p[0], $p[1], $p[2], $p[3], $p[4], $p[5], $p[6]
        ]);
    }
}

echo "Role permissions seeded successfully.\n";
