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
          <h1 class="brand-title">EdrootsCRM</h1>
          <span class="sidebar-role-badge sidebar-demo-badge" title="<?= htmlspecialchars($rawRoleLabel) ?>"><?= htmlspecialchars($headerRoleBadge) ?></span>
        </div>
        <span class="brand-subtitle">Edroots International</span>
      </div>
    </a>
  </div>

  <!-- Navigation Links -->
  <nav class="sidebar-nav">
    
    <div class="nav-section-title">Core CRM</div>

    <!-- 1. Dashboard (PDF Section 4.0) -->
    <?php if (can('dashboard', 'view')): ?>
      <a href="<?= base_url('dashboard/index.php') ?>" id="sidebar-nav-dashboard" class="nav-item <?= ($activePage === 'dashboard') ? 'active' : '' ?>">
        <div class="nav-item-content">
          <i class="fa-solid fa-table-columns nav-icon"></i>
          <span class="nav-label">Dashboard</span>
        </div>
      </a>
    <?php endif; ?>

    <!-- 2. Notifications (PDF Section 5.0) -->
    <a href="<?= base_url('notifications/index.php') ?>" id="sidebar-nav-notifications" class="nav-item <?= ($activePage === 'notifications') ? 'active' : '' ?>">
      <div class="nav-item-content">
        <i class="fa-solid fa-bell nav-icon"></i>
        <span class="nav-label">Notifications</span>
      </div>
      <?php if ($sidebarUnreadNotifs > 0): ?>
        <span class="nav-badge badge-red" title="<?= $sidebarUnreadNotifs ?> unread / unattended notifications"><?= $sidebarUnreadNotifs ?></span>
      <?php endif; ?>
    </a>

    <!-- 3. Students (PDF Section 9.0) -->
    <?php if (can('leads', 'view')): ?>
      <a href="<?= base_url('leads/index.php') ?>" id="sidebar-nav-leads" class="nav-item <?= in_array($activePage, ['leads', 'add_lead']) ? 'active' : '' ?>">
        <div class="nav-item-content">
          <i class="fa-solid fa-user-graduate nav-icon"></i>
          <span class="nav-label">Students</span>
        </div>
        <?php if ($sidebarActiveLeadsCount > 0): ?>
          <span class="nav-badge badge-primary"><?= $sidebarActiveLeadsCount ?></span>
        <?php endif; ?>
      </a>
    <?php endif; ?>

    <!-- 4. My Tasks (PDF Section 9.7) -->
    <?php if (can('tasks', 'view')): ?>
      <a href="<?= base_url('tasks/index.php') ?>" id="sidebar-nav-tasks" class="nav-item <?= ($activePage === 'tasks') ? 'active' : '' ?>">
        <div class="nav-item-content">
          <i class="fa-solid fa-list-check nav-icon"></i>
          <span class="nav-label">My Tasks</span>
        </div>
        <?php if ($sidebarTotalTasks > 0): ?>
          <span class="nav-badge badge-warning" title="<?= $sidebarTotalTasks ?> pending items"><?= $sidebarTotalTasks ?></span>
        <?php endif; ?>
      </a>
    <?php endif; ?>

    <!-- 5. College Data (PDF Section 9.5) -->
    <?php if (can('college_data', 'view')): ?>
      <a href="<?= base_url('data/college_data.php') ?>" id="sidebar-nav-college-data" class="nav-item <?= in_array($activePage, ['college_data', 'import_college_data']) ? 'active' : '' ?>">
        <div class="nav-item-content">
          <i class="fa-solid fa-database nav-icon"></i>
          <span class="nav-label">College Data</span>
        </div>
      </a>
    <?php endif; ?>

    <!-- 6. Website Leads (PDF Section 11.0 / 12.0) -->
    <?php if (can('web_leads', 'view')): ?>
      <a href="<?= base_url('data/web_leads.php') ?>" id="sidebar-nav-web-leads" class="nav-item <?= ($activePage === 'web_leads') ? 'active' : '' ?>">
        <div class="nav-item-content">
          <i class="fa-solid fa-globe nav-icon"></i>
          <span class="nav-label">Website Leads</span>
        </div>
        <?php if ($sidebarWebLeadsCount > 0): ?>
          <span class="nav-badge badge-blue"><?= $sidebarWebLeadsCount ?></span>
        <?php endif; ?>
      </a>
    <?php endif; ?>

    <!-- 7. General Docs (PDF Section 10.0) -->
    <?php if (can('documents', 'view')): ?>
      <a href="<?= base_url('documents/general.php') ?>" id="sidebar-nav-general-docs" class="nav-item <?= ($activePage === 'general_docs') ? 'active' : '' ?>">
        <div class="nav-item-content">
          <i class="fa-solid fa-folder-open nav-icon"></i>
          <span class="nav-label">General Docs</span>
        </div>
      </a>
    <?php endif; ?>

    <!-- 8. Archived (PDF Section 9.6) -->
    <?php if (can('archive', 'view')): ?>
      <a href="<?= base_url('archive/index.php') ?>" id="sidebar-nav-archive" class="nav-item <?= ($activePage === 'archive') ? 'active' : '' ?>">
        <div class="nav-item-content">
          <i class="fa-solid fa-box-archive nav-icon"></i>
          <span class="nav-label">Archived</span>
        </div>
      </a>
    <?php endif; ?>

    <!-- 9. Administration (PDF Section 1.1, 4.2 & Page 6 - Staff Caseload Management for Managers & Admin) -->
    <?php if (can('staff', 'view')): ?>
      <div class="nav-section-title">Administration</div>
      <a href="<?= base_url('organization/users.php') ?>" id="sidebar-nav-staff" class="nav-item <?= in_array($activePage, ['organization_users', 'users']) ? 'active' : '' ?>">
        <div class="nav-item-content">
          <i class="fa-solid fa-users nav-icon"></i>
          <span class="nav-label">Administration</span>
        </div>
      </a>
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
