<?php
/**
 * Authentication and Multi-Branch Authorization Layer
 * University-Level Education Consultancy CRM (EdrootsCRM Engine)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if a user is currently logged in
 *
 * @return bool
 */
function is_logged_in() {
    return !empty($_SESSION['user_id']);
}

/**
 * Get current authenticated user details with role and branch
 *
 * @param bool $forceReload
 * @return array|null
 */
function current_user($forceReload = false) {
    if (!is_logged_in()) {
        return null;
    }

    if (!$forceReload && !empty($_SESSION['current_user_cache']) && ($_SESSION['current_user_cache']['id'] ?? 0) === ($_SESSION['user_id'] ?? 0)) {
        return $_SESSION['current_user_cache'];
    }
    
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT u.*, r.name AS role_name, r.label AS role_label,
                   b.name AS branch_name, b.code AS branch_code, b.city AS branch_city
            FROM users u 
            JOIN roles r ON u.role_id = r.id 
            LEFT JOIN branches b ON u.branch_id = b.id
            WHERE u.id = ? AND u.status = 'Active'
            LIMIT 1
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $cachedUser = $stmt->fetch();
        
        if (!$cachedUser) {
            unset($_SESSION['user_id']);
            unset($_SESSION['current_user_cache']);
            return null;
        }
        $_SESSION['current_user_cache'] = $cachedUser;
        return $cachedUser;
    } catch (Exception $e) {
        return $_SESSION['current_user_cache'] ?? null;
    }
}

/**
 * Require login or redirect to login page
 *
 * @return void
 */
function require_login() {
    if (!is_logged_in() || !current_user()) {
        $currentUri = $_SERVER['REQUEST_URI'] ?? '';
        set_flash('warning', 'Please sign in to access the University CRM portal.');
        redirect('login.php?redirect=' . urlencode($currentUri));
    }
}

/**
 * Check role name equality
 *
 * @param array|string $roles
 * @return bool
 */
function has_role($roles) {
    $user = current_user();
    if (!$user) return false;
    
    if (is_string($roles)) {
        $roles = [$roles];
    }
    
    return in_array($user['role_name'], $roles, true);
}

/**
 * Convenience role checks
 */
function is_super_admin() {
    return has_role('super_admin');
}

function is_admin() {
    return has_role(['super_admin', 'admin']);
}

function is_branch_manager() {
    return has_role(['manager', 'branch_manager']);
}

function is_manager() {
    return has_role(['super_admin', 'admin', 'manager', 'branch_manager']);
}

function is_counselor() {
    return has_role(['counselor', 'counsellor']);
}

function can_manage_staff() {
    return is_super_admin() || can('staff', 'edit');
}

/**
 * SQL Where Clause for Multi-Branch Record Isolation
 *
 * - Super Admin: unrestricted global visibility (1=1)
 * - Branch Manager: designated branch visibility ({tableAlias}.branch_id = {user.branch_id})
 * - Counselor: strictly assigned leads visibility ({tableAlias}.assigned_counselor_id = {user.id})
 *
 * @param string $tableAlias
 * @param string $counselorCol
 * @param string $branchCol
 * @return string
 */
function get_crm_scope_clause($tableAlias = 'l', $counselorCol = 'assigned_counselor_id', $branchCol = 'branch_id') {
    $user = current_user();
    if (!$user) return '1=0';
    
    if (is_super_admin()) {
        return '1=1';
    }
    
    if (is_branch_manager()) {
        $branchId = (int)($user['branch_id'] ?? 0);
        return $branchId > 0 ? "{$tableAlias}.{$branchCol} = {$branchId}" : '1=0';
    }
    
    // Counselor and other staff
    $userId = (int)$user['id'];
    return "{$tableAlias}.{$counselorCol} = {$userId}";
}

/**
 * Check whether the active user has permission to access a specific lead/student record
 *
 * @param int $leadId
 * @return bool
 */
function can_access_lead($leadId) {
    global $pdo;
    $user = current_user();
    if (!$user) return false;
    if (is_super_admin()) return true;
    
    $leadId = (int)$leadId;
    if ($leadId <= 0) return false;
    
    $stmt = $pdo->prepare("SELECT id, branch_id, assigned_counselor_id FROM leads WHERE id = ? LIMIT 1");
    $stmt->execute([$leadId]);
    $lead = $stmt->fetch();
    if (!$lead) return false;
    
    if (is_branch_manager()) {
        $userBranchId = (int)($user['branch_id'] ?? 0);
        if ($userBranchId <= 0) return false;
        return ((int)$lead['branch_id'] === $userBranchId);
    }
    
    return ((int)$lead['assigned_counselor_id'] === (int)$user['id']);
}

/**
 * Role-based record filter helper (legacy compat)
 *
 * @return int|null null if can view all records, or user id if restricted
 */
function get_scoped_staff_id() {
    $user = current_user();
    if (!$user) return 0;
    
    if (is_super_admin() || is_branch_manager()) {
        return null;
    }
    
    return (int)$user['id'];
}

/**
 * Returns list of system modules with labels, icons, and descriptions
 *
 * @return array
 */
function get_crm_modules() {
    return [
        'dashboard' => [
            'key' => 'dashboard',
            'label' => 'Main Dashboard',
            'icon' => 'fa-table-columns',
            'desc' => 'Executive KPI statistics, branch analytics, intake performance, and live operational updates'
        ],
        'college_data' => [
            'key' => 'college_data',
            'label' => 'College Data Pool',
            'icon' => 'fa-database',
            'desc' => 'Raw college student data repository with Excel/CSV bulk import and 1-click lead conversion'
        ],
        'web_leads' => [
            'key' => 'web_leads',
            'label' => 'Website & Social Leads',
            'icon' => 'fa-globe',
            'desc' => 'Digital lead staging queue with branch routing and counselor assignment'
        ],
        'leads' => [
            'key' => 'leads',
            'label' => 'Active Leads',
            'icon' => 'fa-user-graduate',
            'desc' => 'Active qualified student leads with canonical phone deduplication and 6-stage tracking'
        ],
        'customers' => [
            'key' => 'customers',
            'label' => 'Customers',
            'icon' => 'fa-certificate',
            'desc' => 'Formal student customers confirmed upon service invoice generation'
        ],
        'admissions' => [
            'key' => 'admissions',
            'label' => 'Admission Pipeline',
            'icon' => 'fa-diagram-project',
            'desc' => 'Visual 6-stage university admission pipeline (Enquiry, Customer, Application, Offer, Fees, Enrolled)'
        ],
        'tasks' => [
            'key' => 'tasks',
            'label' => 'My Tasks',
            'icon' => 'fa-list-check',
            'desc' => 'Operational queues: Scheduled Follow-ups, Pending Follow-ups, Today Leads, and Pending Leads'
        ],
        'invoices' => [
            'key' => 'invoices',
            'label' => 'Finance & Invoices',
            'icon' => 'fa-file-invoice-dollar',
            'desc' => 'Multi-line consultancy invoices, split installments, balances, and downloadable PDFs'
        ],
        'documents' => [
            'key' => 'documents',
            'label' => 'Document Repository',
            'icon' => 'fa-folder-open',
            'desc' => 'Institutional guidelines repository and 4-tier student academic dossiers'
        ],
        'branches' => [
            'key' => 'branches',
            'label' => 'Branch Network',
            'icon' => 'fa-building-columns',
            'desc' => '9 domestic and international consultancy branch hubs and branch manager administration'
        ],
        'staff' => [
            'key' => 'staff',
            'label' => 'Staff Management',
            'icon' => 'fa-users',
            'desc' => 'Branch managers, senior counselors, account access, and credentials'
        ],
        'roles' => [
            'key' => 'roles',
            'label' => 'Roles & Permissions',
            'icon' => 'fa-shield-halved',
            'desc' => 'Granular permission matrix across viewing, adding, editing, deleting, assigning, and exporting'
        ],
        'reports' => [
            'key' => 'reports',
            'label' => 'Reports & Analytics',
            'icon' => 'fa-chart-pie',
            'desc' => 'Branch conversion performance, counselor caseload ratings, and financial revenue yields'
        ],
        'archive' => [
            'key' => 'archive',
            'label' => 'Archive Management',
            'icon' => 'fa-box-archive',
            'desc' => 'Soft-archived college data, leads, customers, and two-step permanent deletion audit log'
        ],
        'notifications' => [
            'key' => 'notifications',
            'label' => 'Notifications',
            'icon' => 'fa-bell',
            'desc' => 'Attended / Not Attended actionable alerts and task reminders'
        ],
        'settings' => [
            'key' => 'settings',
            'label' => 'System Governance',
            'icon' => 'fa-gear',
            'desc' => 'Interaction titles, max duration constraints, intake cycles, and immutable activity logs'
        ]
    ];
}

/**
 * Clear in-memory permission cache
 *
 * @return void
 */
function clear_permission_cache() {
    $GLOBALS['crm_perm_cache'] = [];
}

/**
 * Core Dynamic Permission Check
 *
 * Evaluates whether a user can perform an action on a module.
 * Super Admin always has unrestricted access (true).
 *
 * @param string $module Module key (e.g., 'leads', 'invoices', 'reports')
 * @param string $action Action key: 'view', 'add', 'edit', 'delete', 'assign', 'reassign', 'approve', 'export', 'archive', 'restore'
 * @param array|null $user User array (defaults to current_user())
 * @return bool
 */
function can($module, $action = 'view', $user = null) {
    global $pdo;

    if ($user === null) {
        $user = current_user();
    }
    if (!$user) {
        return false;
    }

    // 1. Super Admin has complete unrestricted control
    if (($user['role_name'] ?? '') === 'super_admin' || ($user['role_id'] ?? 0) == 1) {
        return true;
    }

    // Map action string to database column
    $actionMap = [
        'view'     => 'can_view',
        'add'      => 'can_add',
        'edit'     => 'can_edit',
        'delete'   => 'can_delete',
        'assign'   => 'can_assign',
        'reassign' => 'can_reassign',
        'approve'  => 'can_approve',
        'export'   => 'can_export',
        'archive'  => 'can_archive',
        'restore'  => 'can_restore',
    ];

    $column = $actionMap[strtolower($action)] ?? 'can_view';
    $userId = (int)$user['id'];
    $roleId = (int)$user['role_id'];

    // High-performance session-level role permissions cache (avoids repeated round-trip DB queries)
    if (!isset($_SESSION['crm_role_perms'][$roleId])) {
        if (!$pdo) {
            return false;
        }
        try {
            $stmt = $pdo->prepare("SELECT * FROM role_permissions WHERE role_id = ?");
            $stmt->execute([$roleId]);
            $perms = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $perms[$row['module']] = $row;
            }
            $_SESSION['crm_role_perms'][$roleId] = $perms;
        } catch (Exception $e) {
            return false;
        }
    }

    return !empty($_SESSION['crm_role_perms'][$roleId][$module][$column]);
}

/**
 * Enforce permission or redirect with error
 *
 * @param string $module
 * @param string $action
 * @return void
 */
function require_permission($module, $action = 'view') {
    require_login();
    if (!can($module, $action)) {
        $actionName = ucfirst($action);
        set_flash('danger', "Access Denied: You do not have permission to <strong>{$actionName}</strong> in the <strong>" . htmlspecialchars($module) . "</strong> module.");
        
        // If cannot view dashboard, redirect to first allowed module
        if ($module === 'dashboard' && $action === 'view') {
            $allowedModules = ['leads', 'tasks', 'admissions', 'customers', 'documents'];
            foreach ($allowedModules as $alt) {
                if (can($alt, 'view')) {
                    redirect("{$alt}/index.php");
                }
            }
        }
        
        redirect('dashboard/index.php');
    }
}

/**
 * Retrieve permissions array for a role
 *
 * @param int $roleId
 * @return array
 */
function get_role_permissions($roleId) {
    global $pdo;
    $perms = [];
    $modules = get_crm_modules();

    foreach ($modules as $mKey => $mMeta) {
        $perms[$mKey] = [
            'can_view'     => 0,
            'can_add'      => 0,
            'can_edit'     => 0,
            'can_delete'   => 0,
            'can_assign'   => 0,
            'can_reassign' => 0,
            'can_approve'  => 0,
            'can_export'   => 0,
            'can_archive'  => 0,
            'can_restore'  => 0,
        ];
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM role_permissions WHERE role_id = ?");
        $stmt->execute([$roleId]);
        while ($row = $stmt->fetch()) {
            $perms[$row['module']] = [
                'can_view'     => (int)($row['can_view'] ?? 0),
                'can_add'      => (int)($row['can_add'] ?? 0),
                'can_edit'     => (int)($row['can_edit'] ?? 0),
                'can_delete'   => (int)($row['can_delete'] ?? 0),
                'can_assign'   => (int)($row['can_assign'] ?? 0),
                'can_reassign' => (int)($row['can_reassign'] ?? 0),
                'can_approve'  => (int)($row['can_approve'] ?? 0),
                'can_export'   => (int)($row['can_export'] ?? 0),
                'can_archive'  => (int)($row['can_archive'] ?? 0),
                'can_restore'  => (int)($row['can_restore'] ?? 0),
            ];
        }
    } catch (Exception $e) {}

    return $perms;
}

/**
 * Verify whether a target URL is accessible to the specified user
 * Prevents unauthorized redirection and Access Denied flash warnings upon persona switching or login
 *
 * @param string $url
 * @param array|null $user
 * @return bool
 */
function is_url_permitted_for_user($url, $user = null) {
    if (empty($url)) {
        return true;
    }

    $path = parse_url($url, PHP_URL_PATH) ?? $url;
    $path = str_replace('\\', '/', strtolower($path));

    // Module URL patterns mapping
    $moduleMap = [
        'organization/roles'       => 'roles',
        'organization/users'       => 'staff',
        'system/settings'          => 'settings',
        'finance/invoices'         => 'invoices',
        'finance/create_invoice'   => 'invoices',
        'finance/balances'         => 'invoices',
        'finance/payments'         => 'payments',
        'data/web_leads'           => 'web_leads',
        'data/college_data'        => 'college_data',
        'data/import_college_data' => 'college_data',
        'archive'                  => 'archive',
        'reports'                  => 'reports',
        'documents'                => 'documents',
        'tasks'                    => 'tasks',
        'followups'                => 'followups',
        'admissions'               => 'admissions',
        'customers'                => 'customers',
        'leads'                    => 'leads',
        'dashboard'                => 'dashboard',
        'notifications'            => 'notifications'
    ];

    foreach ($moduleMap as $pattern => $module) {
        if (strpos($path, $pattern) !== false) {
            return can($module, 'view', $user);
        }
    }

    return true;
}

