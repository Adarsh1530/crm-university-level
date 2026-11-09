<?php
/**
 * NexLink Sidebar Component - Streamlined & User-Friendly Navigation
 * Tailored for Daily Operational Workflows: Super Admin, Admissions Manager, & Counselors
 */

global $pdo;

$currentUser = current_user();
$activePage = $activePage ?? '';
$currentUri = $_SERVER['REQUEST_URI'] ?? '';

// Live dynamic badge counts for sidebar
$sidebarScheduledCount = 0;
$sidebarOverdueCount = 0;
$sidebarTodayLeadsCount = 0;
$sidebarPendingLeadsCount = 0;
$sidebarTotalTasks = 0;
$sidebarWebLeadsCount = 0;
$sidebarActiveLeadsCount = 0;
$sidebarUnreadNotifs = 0;

try {
    if ($pdo && $currentUser) {
        $now = time();
        $useSidebarCache = !empty($_SESSION['sidebar_cache']) && ($now - ($_SESSION['sidebar_cache_time'] ?? 0) < 30);
        
        if ($useSidebarCache) {
            $sc = $_SESSION['sidebar_cache'];
            $sidebarActiveLeadsCount = $sc['active_leads'] ?? 0;
            $sidebarWebLeadsCount    = $sc['web_leads'] ?? 0;
            $sidebarScheduledCount   = $sc['scheduled'] ?? 0;
            $sidebarOverdueCount     = $sc['overdue'] ?? 0;
            $sidebarTodayLeadsCount  = $sc['today_leads'] ?? 0;
            $sidebarPendingLeadsCount= $sc['pending_leads'] ?? 0;
            $sidebarTotalTasks       = $sc['total_tasks'] ?? 0;
            $sidebarUnreadNotifs     = $sc['unread_notifs'] ?? 0;
        } else {
            $scopeLeads = get_crm_scope_clause('l', 'assigned_counselor_id', 'branch_id');
            
            // Active Leads count
            $stmtActive = $pdo->query("SELECT COUNT(*) FROM leads l WHERE l.is_archived = 0 AND {$scopeLeads}");
            $sidebarActiveLeadsCount = (int)$stmtActive->fetchColumn();

            // Web leads pending staging count
            if (can('web_leads', 'view')) {
                if (is_super_admin()) {
                    $sidebarWebLeadsCount = (int)$pdo->query("SELECT COUNT(*) FROM web_leads WHERE status = 'Pending'")->fetchColumn();
                } else {
                    $bId = (int)($currentUser['branch_id'] ?? 0);
                    $wStmt = $pdo->prepare("SELECT COUNT(*) FROM web_leads WHERE status = 'Pending' AND (branch_id = ? OR branch_id IS NULL)");
                    $wStmt->execute([$bId]);
                    $sidebarWebLeadsCount = (int)$wStmt->fetchColumn();
                }
            }

            // 1. Scheduled follow-ups due today
            $stmt = $pdo->query("
                SELECT COUNT(*) FROM followups f
                JOIN leads l ON f.lead_id = l.id
                WHERE f.scheduled_date = CURDATE() AND f.status = 'Pending' AND l.is_archived = 0 AND {$scopeLeads}
            ");
            $sidebarScheduledCount = (int)$stmt->fetchColumn();

            // 2. Overdue pending follow-ups
            $stmt = $pdo->query("
                SELECT COUNT(*) FROM followups f
                JOIN leads l ON f.lead_id = l.id
                WHERE f.scheduled_date < CURDATE() AND f.status = 'Pending' AND l.is_archived = 0 AND {$scopeLeads}
            ");
            $sidebarOverdueCount = (int)$stmt->fetchColumn();

            // 3. Today Leads (assigned today, 0 followups)
            $stmt = $pdo->query("
                SELECT COUNT(*) FROM leads l
                WHERE DATE(l.assigned_at) = CURDATE() AND l.followup_count = 0 AND l.is_archived = 0 AND {$scopeLeads}
            ");
            $sidebarTodayLeadsCount = (int)$stmt->fetchColumn();

            // 4. Pending Leads (assigned earlier, 0 followups)
            $stmt = $pdo->query("
                SELECT COUNT(*) FROM leads l
                WHERE DATE(l.assigned_at) < CURDATE() AND l.followup_count = 0 AND l.is_archived = 0 AND {$scopeLeads}
            ");
            $sidebarPendingLeadsCount = (int)$stmt->fetchColumn();

            $sidebarTotalTasks = $sidebarScheduledCount + $sidebarOverdueCount + $sidebarTodayLeadsCount + $sidebarPendingLeadsCount;
            $sidebarUnreadNotifs = get_unread_notifications_count($currentUser['id']);

            $_SESSION['sidebar_cache'] = [
                'active_leads'   => $sidebarActiveLeadsCount,
                'web_leads'      => $sidebarWebLeadsCount,
                'scheduled'      => $sidebarScheduledCount,
                'overdue'        => $sidebarOverdueCount,
                'today_leads'    => $sidebarTodayLeadsCount,
                'pending_leads'  => $sidebarPendingLeadsCount,
                'total_tasks'    => $sidebarTotalTasks,
                'unread_notifs'  => $sidebarUnreadNotifs
            ];
            $_SESSION['sidebar_cache_time'] = $now;
        }
    }
} catch (Exception $e) {
    // Fail silently in case of db lag
}

// Helper to check if a specific route is active
if (!function_exists('is_sub_active')) {
    function is_sub_active($path, $paramKey = null, $paramVal = null) {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($uri, $path) === false) {
            return false;
        }
        if ($paramKey !== null && $paramVal !== null) {
            $query = parse_url($uri, PHP_URL_QUERY);
            parse_str($query ?? '', $params);
            return isset($params[$paramKey]) && $params[$paramKey] == $paramVal;
        }
        return true;
    }
}
?>

<aside id="sidebar">
<?php
// Compute a clean, concise badge for the brand header to ensure perfect horizontal alignment
$rawRoleLabel = $currentUser['role_label'] ?? 'Staff';
$headerRoleBadge = $rawRoleLabel;
if (stripos($headerRoleBadge, 'Admissions ') === 0) {
    $headerRoleBadge = substr($headerRoleBadge, 11);
}
?>
  <!-- Brand Header -->
  <div class="sidebar-header" id="sidebar-brand-header">
    <a href="<?= base_url('dashboard/index.php') ?>" class="brand-wrapper">
      <div class="brand-icon">
        <i class="fa-solid fa-graduation-cap"></i>
      </div>
      <div class="brand-text">
        <div class="brand-title-row">
          <h1 class="brand-title">UniCRM</h1>
          <span class="sidebar-role-badge sidebar-demo-badge" title="<?= htmlspecialchars($rawRoleLabel) ?>"><?= htmlspecialchars($headerRoleBadge) ?></span>
        </div>
        <span class="brand-subtitle">Admissions Cloud</span>
      </div>
    </a>
  </div>

  <!-- Navigation Links -->
  <nav class="sidebar-nav">
    
    <!-- 1. MAIN COCKPIT -->
    <div class="nav-section-title">Main</div>
    <?php if (can('dashboard', 'view')): ?>
      <a href="<?= base_url('dashboard/index.php') ?>" id="sidebar-nav-dashboard" class="nav-item <?= ($activePage === 'dashboard') ? 'active' : '' ?>">
        <div class="nav-item-content">
          <i class="fa-solid fa-table-columns nav-icon"></i>
          <span class="nav-label">Dashboard</span>
        </div>
      </a>
    <?php endif; ?>

    <!-- 2. STUDENT ADMISSIONS WORKSPACE (1-Click Direct Access) -->
    <div class="nav-section-title">Admissions Workspace</div>

    <?php if (can('leads', 'view')): ?>
      <a href="<?= base_url('leads/index.php') ?>" id="sidebar-nav-leads" class="nav-item <?= in_array($activePage, ['leads', 'add_lead']) ? 'active' : '' ?>">
        <div class="nav-item-content">
          <i class="fa-solid fa-user-graduate nav-icon"></i>
          <span class="nav-label">Student Leads</span>
        </div>
        <?php if ($sidebarActiveLeadsCount > 0): ?>
          <span class="nav-badge badge-primary"><?= $sidebarActiveLeadsCount ?></span>
        <?php endif; ?>
      </a>
    <?php endif; ?>

    <?php if (can('admissions', 'view')): ?>
      <a href="<?= base_url('admissions/index.php') ?>" id="sidebar-nav-pipeline" class="nav-item <?= ($activePage === 'admissions') ? 'active' : '' ?>">
        <div class="nav-item-content">
          <i class="fa-solid fa-diagram-project nav-icon"></i>
          <span class="nav-label">Admission Pipeline</span>
        </div>
      </a>
    <?php endif; ?>

    <?php if (can('tasks', 'view')): ?>
      <a href="<?= base_url('tasks/index.php') ?>" id="sidebar-nav-tasks" class="nav-item <?= ($activePage === 'tasks') ? 'active' : '' ?>">
        <div class="nav-item-content">
          <i class="fa-solid fa-list-check nav-icon"></i>
          <span class="nav-label">Daily Follow-ups</span>
        </div>
        <?php if ($sidebarTotalTasks > 0): ?>
          <span class="nav-badge badge-warning" title="<?= $sidebarTotalTasks ?> pending items"><?= $sidebarTotalTasks ?></span>
        <?php endif; ?>
      </a>
    <?php endif; ?>

    <?php if (can('customers', 'view')): ?>
      <a href="<?= base_url('customers/index.php') ?>" id="sidebar-nav-customers" class="nav-item <?= ($activePage === 'customers') ? 'active' : '' ?>">
        <div class="nav-item-content">
          <i class="fa-solid fa-certificate nav-icon"></i>
          <span class="nav-label">Enrolled Clients</span>
        </div>
      </a>
    <?php endif; ?>

    <!-- 3. INBOUND DATA & ALLOCATION (Manager & Super Admin) -->
    <?php if (can('web_leads', 'view') || can('college_data', 'view')): 
      $groupDataOpen = in_array($activePage, ['web_leads', 'college_data', 'import_college_data']);
    ?>
      <div class="nav-section-title">Lead Inbound</div>
      <div class="nav-group <?= $groupDataOpen ? 'nav-group-open active' : '' ?>" id="sidebar-group-inbound">
        <button type="button" class="nav-group-toggle" onclick="toggleNavGroup(this)">
          <div class="nav-item-content">
            <i class="fa-solid fa-bullseye nav-icon"></i>
            <span class="nav-label">Lead Inbound & Triage</span>
          </div>
          <?php if ($sidebarWebLeadsCount > 0): ?>
            <span class="nav-badge badge-blue" style="margin-left:auto; margin-right:8px;"><?= $sidebarWebLeadsCount ?></span>
          <?php endif; ?>
          <i class="fa-solid fa-chevron-down nav-arrow"></i>
        </button>
        <ul class="nav-submenu">
          <?php if (can('web_leads', 'view')): ?>
            <li>
              <a href="<?= base_url('data/web_leads.php') ?>" id="sidebar-nav-web-leads" class="nav-subitem <?= ($activePage === 'web_leads') ? 'active' : '' ?>">
                Digital Web & Social Leads
                <?php if ($sidebarWebLeadsCount > 0): ?>
                  <span class="nav-badge badge-blue" style="margin-left:auto;"><?= $sidebarWebLeadsCount ?></span>
                <?php endif; ?>
              </a>
            </li>
          <?php endif; ?>

          <?php if (can('college_data', 'view')): ?>
            <li>
              <a href="<?= base_url('data/college_data.php') ?>" id="sidebar-nav-college-data" class="nav-subitem <?= ($activePage === 'college_data') ? 'active' : '' ?>">
                College Data Pool
              </a>
            </li>
          <?php endif; ?>

          <?php if (can('college_data', 'add')): ?>
            <li>
              <a href="<?= base_url('data/import_college_data.php') ?>" id="sidebar-nav-import-data" class="nav-subitem <?= ($activePage === 'import_college_data') ? 'active' : '' ?>">
                Import College List
              </a>
            </li>
          <?php endif; ?>
        </ul>
      </div>
    <?php endif; ?>

    <!-- 4. OPERATIONS (Finance & Documents) -->
    <?php if (can('invoices', 'view') || can('documents', 'view')): 
      $groupOpsOpen = in_array($activePage, ['finance', 'invoices', 'payments', 'balances', 'create_invoice', 'documents', 'general_docs', 'dossiers']);
    ?>
      <div class="nav-section-title">Operations</div>
      <div class="nav-group <?= $groupOpsOpen ? 'nav-group-open active' : '' ?>" id="sidebar-group-finance">
        <button type="button" class="nav-group-toggle" onclick="toggleNavGroup(this)">
          <div class="nav-item-content">
            <i class="fa-solid fa-file-invoice-dollar nav-icon"></i>
            <span class="nav-label">Finance & Invoices</span>
          </div>
          <i class="fa-solid fa-chevron-down nav-arrow"></i>
        </button>
        <ul class="nav-submenu">
          <li>
            <a href="<?= base_url('finance/invoices.php') ?>" class="nav-subitem <?= ($activePage === 'invoices') ? 'active' : '' ?>">
              Invoices & Billings
            </a>
          </li>
          <?php if (can('invoices', 'add')): ?>
            <li>
              <a href="<?= base_url('finance/create_invoice.php') ?>" class="nav-subitem <?= ($activePage === 'create_invoice') ? 'active' : '' ?>">
                Generate Invoice
              </a>
            </li>
          <?php endif; ?>
          <li>
            <a href="<?= base_url('finance/payments.php') ?>" class="nav-subitem <?= ($activePage === 'payments') ? 'active' : '' ?>">
              Payment Receipts
            </a>
          </li>
          <li>
            <a href="<?= base_url('finance/balances.php') ?>" class="nav-subitem <?= ($activePage === 'balances') ? 'active' : '' ?>">
              Outstanding Balances
            </a>
          </li>
        </ul>
      </div>

      <div class="nav-group <?= in_array($activePage, ['documents', 'general_docs', 'dossiers']) ? 'nav-group-open active' : '' ?>" id="sidebar-group-docs">
        <button type="button" class="nav-group-toggle" onclick="toggleNavGroup(this)">
          <div class="nav-item-content">
            <i class="fa-solid fa-folder-open nav-icon"></i>
            <span class="nav-label">Document Hub</span>
          </div>
          <i class="fa-solid fa-chevron-down nav-arrow"></i>
        </button>
        <ul class="nav-submenu">
          <li>
            <a href="<?= base_url('documents/dossiers.php') ?>" class="nav-subitem <?= ($activePage === 'dossiers') ? 'active' : '' ?>">
              Student Dossiers
            </a>
          </li>
          <li>
            <a href="<?= base_url('documents/general.php') ?>" class="nav-subitem <?= ($activePage === 'general_docs') ? 'active' : '' ?>">
              Country Checklists & Guidelines
            </a>
          </li>
        </ul>
      </div>
    <?php endif; ?>

    <!-- 5. MANAGEMENT & GOVERNANCE (Manager & Super Admin) -->
    <?php if (can('staff', 'view') || can('reports', 'view') || can('roles', 'view')): 
      $groupMgmtOpen = in_array($activePage, ['organization_users', 'roles', 'branches', 'reports', 'activity_logs', 'system_settings', 'archive']);
    ?>
      <div class="nav-section-title">Administration</div>

      <?php if (can('staff', 'view')): ?>
        <a href="<?= base_url('organization/users.php') ?>" id="sidebar-nav-staff" class="nav-item <?= ($activePage === 'organization_users') ? 'active' : '' ?>">
          <div class="nav-item-content">
            <i class="fa-solid fa-users nav-icon"></i>
            <span class="nav-label">Staff & Team</span>
          </div>
        </a>
      <?php endif; ?>

      <?php if (can('branches', 'view')): ?>
        <a href="<?= base_url('organization/branches.php') ?>" id="sidebar-nav-branches" class="nav-item <?= ($activePage === 'branches') ? 'active' : '' ?>">
          <div class="nav-item-content">
            <i class="fa-solid fa-building nav-icon"></i>
            <span class="nav-label">Campus Branches</span>
          </div>
        </a>
      <?php endif; ?>

      <?php if (is_super_admin()): ?>
        <a href="<?= base_url('organization/roles.php') ?>" id="sidebar-nav-roles" class="nav-item <?= ($activePage === 'roles') ? 'active' : '' ?>">
          <div class="nav-item-content">
            <i class="fa-solid fa-shield-halved nav-icon"></i>
            <span class="nav-label">Roles & Permissions</span>
          </div>
        </a>
      <?php endif; ?>

      <?php if (can('reports', 'view')): ?>
        <a href="<?= base_url('reports/index.php') ?>" id="sidebar-nav-reports" class="nav-item <?= ($activePage === 'reports') ? 'active' : '' ?>">
          <div class="nav-item-content">
            <i class="fa-solid fa-chart-pie nav-icon"></i>
            <span class="nav-label">Performance Reports</span>
          </div>
        </a>
      <?php endif; ?>

      <?php if (is_super_admin()): ?>
        <a href="<?= base_url('system/settings.php') ?>" id="sidebar-nav-settings" class="nav-item <?= ($activePage === 'system_settings') ? 'active' : '' ?>">
          <div class="nav-item-content">
            <i class="fa-solid fa-sliders nav-icon"></i>
            <span class="nav-label">System Settings</span>
          </div>
        </a>

        <a href="<?= base_url('system/activity_logs.php') ?>" id="sidebar-nav-logs" class="nav-item <?= ($activePage === 'activity_logs') ? 'active' : '' ?>">
          <div class="nav-item-content">
            <i class="fa-solid fa-clock-rotate-left nav-icon"></i>
            <span class="nav-label">Audit Activity Trail</span>
          </div>
        </a>
      <?php endif; ?>

      <?php if (can('archive', 'view')): ?>
        <a href="<?= base_url('archive/index.php') ?>" id="sidebar-nav-archive" class="nav-item <?= ($activePage === 'archive') ? 'active' : '' ?>">
          <div class="nav-item-content">
            <i class="fa-solid fa-box-archive nav-icon"></i>
            <span class="nav-label">Archive Repository</span>
          </div>
        </a>
      <?php endif; ?>
    <?php endif; ?>

  </nav>

  <!-- Sidebar User Profile Footer (Neatly Pinned & Properly Aligned) -->
  <div class="sidebar-user">
    <div class="sidebar-user-card">
      <div class="user-snippet">
        <div class="user-avatar-sm online" title="<?= htmlspecialchars($currentUser['name'] ?? 'User') ?>">
          <?= user_initials($currentUser['name'] ?? 'U') ?>
        </div>
        <div class="user-info">
          <span class="user-name" title="<?= htmlspecialchars($currentUser['name'] ?? 'User') ?>"><?= htmlspecialchars($currentUser['name'] ?? 'User') ?></span>
          <span class="user-role"><?= htmlspecialchars($currentUser['role_label'] ?? 'Staff') ?></span>
        </div>
      </div>
      <a href="<?= base_url('logout.php') ?>" class="user-logout-btn" title="Sign Out of Session">
        <i class="fa-solid fa-right-from-bracket"></i>
      </a>
    </div>
  </div>
</aside>
