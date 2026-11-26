<?php
/**
 * Core CRM Sidebar Component - Streamlined & Focused Navigation
 * Aligned 1:1 with Client Specifications:
 * 1. System Identity: Core CRM (Customer Relationship Management)
 * 2. Multi-Role Hierarchy: Super Admin, Team Leader, Counselor / Advisor, Operations / Accessor
 * 3. Pure Focus on Leads, Social Media Leads, Call Data, and Follow-up Queues
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
$sidebarCallLogsCount = 0;

try {
    if ($pdo && $currentUser) {
        $now = time();
        $useSidebarCache = !empty($_SESSION['sidebar_cache']) && ($now - ($_SESSION['sidebar_cache_time'] ?? 0) < 15);
        
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
            $sidebarCallLogsCount    = $sc['call_logs'] ?? 0;
        } else {
            $scopeLeads = get_crm_scope_clause('l', 'assigned_counselor_id', 'branch_id');
            
            // Active Leads count
            $stmtActive = $pdo->query("SELECT COUNT(*) FROM leads l WHERE l.is_archived = 0 AND {$scopeLeads}");
            $sidebarActiveLeadsCount = (int)$stmtActive->fetchColumn();

            // Social Media Leads pending staging count
            if (can('web_leads', 'view')) {
                if (is_super_admin() || is_operations()) {
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

            try {
                $sidebarCallLogsCount = (int)$pdo->query("SELECT COUNT(*) FROM call_logs WHERE DATE(created_at) = CURDATE()")->fetchColumn();
            } catch (Exception $ce) {
                $sidebarCallLogsCount = 0;
            }

            $_SESSION['sidebar_cache'] = [
                'active_leads'   => $sidebarActiveLeadsCount,
                'web_leads'      => $sidebarWebLeadsCount,
                'scheduled'      => $sidebarScheduledCount,
                'overdue'        => $sidebarOverdueCount,
                'today_leads'    => $sidebarTodayLeadsCount,
                'pending_leads'  => $sidebarPendingLeadsCount,
                'total_tasks'    => $sidebarTotalTasks,
                'unread_notifs'  => $sidebarUnreadNotifs,
                'call_logs'      => $sidebarCallLogsCount
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
$rawRoleLabel = $currentUser['role_label'] ?? 'Staff';
$headerRoleBadge = $rawRoleLabel;
?>
  <!-- Brand Header: Core CRM Platform -->
  <div class="sidebar-header" id="sidebar-brand-header">
    <a href="<?= base_url('dashboard/index.php') ?>" class="brand-wrapper">
      <div class="brand-icon" style="background:linear-gradient(135deg, #4f46e5, #7c3aed); color:#fff; display:flex; align-items:center; justify-content:center; border-radius:10px; width:40px; height:40px; box-shadow:0 3px 8px rgba(79,70,229,0.3);">
        <i class="fa-solid fa-layer-group" style="font-size:1.15rem;"></i>
      </div>
      <div class="brand-text">
        <div class="brand-title-row">
          <h1 class="brand-title" style="letter-spacing:-0.02em; font-weight:800;">CRM</h1>
          <span class="sidebar-role-badge sidebar-demo-badge" title="<?= htmlspecialchars($rawRoleLabel) ?>"><?= htmlspecialchars($headerRoleBadge) ?></span>
        </div>
        <span class="brand-subtitle" style="font-size:0.68rem; font-weight:600; text-transform:uppercase; letter-spacing:0.04em; color:var(--text-light);">Customer Management</span>
      </div>
    </a>
  </div>

  <!-- Navigation Links -->
  <nav class="sidebar-nav">
    
    <div class="nav-section-title">Core CRM Operations</div>

    <!-- 1. Dashboard -->
    <?php if (can('dashboard', 'view')): ?>
      <a href="<?= base_url('dashboard/index.php') ?>" id="sidebar-nav-dashboard" class="nav-item <?= ($activePage === 'dashboard') ? 'active' : '' ?>">
        <div class="nav-item-content">
          <i class="fa-solid fa-table-columns nav-icon"></i>
          <span class="nav-label">Dashboard</span>
        </div>
      </a>
    <?php endif; ?>

    <!-- 2. Notifications -->
    <a href="<?= base_url('notifications/index.php') ?>" id="sidebar-nav-notifications" class="nav-item <?= ($activePage === 'notifications') ? 'active' : '' ?>">
      <div class="nav-item-content">
        <i class="fa-solid fa-bell nav-icon"></i>
        <span class="nav-label">Notifications</span>
      </div>
      <?php if ($sidebarUnreadNotifs > 0): ?>
        <span class="nav-badge badge-red" title="<?= $sidebarUnreadNotifs ?> unread / unattended notifications"><?= $sidebarUnreadNotifs ?></span>
      <?php endif; ?>
    </a>

    <!-- 3. Leads Management (Primary Focus) -->
    <?php if (can('leads', 'view')): ?>
      <a href="<?= base_url('leads/index.php') ?>" id="sidebar-nav-leads" class="nav-item <?= in_array($activePage, ['leads', 'add_lead']) ? 'active' : '' ?>" title="Manage active leads, regional distribution, and phone verification">
        <div class="nav-item-content">
          <i class="fa-solid fa-users-line nav-icon"></i>
          <span class="nav-label">Leads Directory</span>
        </div>
        <?php if ($sidebarActiveLeadsCount > 0): ?>
          <span class="nav-badge badge-primary" title="<?= $sidebarActiveLeadsCount ?> Active Leads"><?= $sidebarActiveLeadsCount ?></span>
        <?php endif; ?>
      </a>
    <?php endif; ?>

    <!-- 4. Social Media Leads (Specific tracking & prioritization) -->
    <?php if (can('web_leads', 'view')): ?>
      <a href="<?= base_url('data/web_leads.php') ?>" id="sidebar-nav-web-leads" class="nav-item <?= ($activePage === 'web_leads') ? 'active' : '' ?>" title="Social media leads ingestion (Meta, Instagram, Google Ads)">
        <div class="nav-item-content">
          <i class="fa-solid fa-hashtag nav-icon"></i>
          <span class="nav-label">Social Media Leads</span>
        </div>
        <?php if ($sidebarWebLeadsCount > 0): ?>
          <span class="nav-badge badge-blue" title="<?= $sidebarWebLeadsCount ?> Pending Social Inbound Leads"><?= $sidebarWebLeadsCount ?></span>
        <?php endif; ?>
      </a>
    <?php endif; ?>

    <!-- 5. Tasks & Follow-up Lifecycle (Top Header & Worklists) -->
    <?php if (can('tasks', 'view')): ?>
      <a href="<?= base_url('tasks/index.php') ?>" id="sidebar-nav-tasks" class="nav-item <?= ($activePage === 'tasks') ? 'active' : '' ?>" title="4-Stage Follow-up Lifecycle Queues">
        <div class="nav-item-content">
          <i class="fa-solid fa-list-check nav-icon"></i>
          <span class="nav-label">Tasks & Follow-ups</span>
        </div>
        <?php if ($sidebarTotalTasks > 0): ?>
          <span class="nav-badge badge-warning" title="<?= $sidebarTotalTasks ?> pending follow-up items"><?= $sidebarTotalTasks ?></span>
        <?php endif; ?>
      </a>
    <?php endif; ?>

    <!-- 6. Call Data Integration Center -->
    <a href="<?= base_url('followups/call_data.php') ?>" id="sidebar-nav-call-data" class="nav-item <?= ($activePage === 'call_data') ? 'active' : '' ?>" title="Centralized call logging and call data integration">
      <div class="nav-item-content">
        <i class="fa-solid fa-phone-volume nav-icon"></i>
        <span class="nav-label">Call Data Center</span>
      </div>
      <?php if ($sidebarCallLogsCount > 0): ?>
        <span class="nav-badge badge-secondary" title="<?= $sidebarCallLogsCount ?> calls today"><?= $sidebarCallLogsCount ?></span>
      <?php endif; ?>
    </a>

    <!-- 7. Archived Leads -->
    <?php if (can('archive', 'view')): ?>
      <a href="<?= base_url('archive/index.php') ?>" id="sidebar-nav-archive" class="nav-item <?= ($activePage === 'archive') ? 'active' : '' ?>">
        <div class="nav-item-content">
          <i class="fa-solid fa-box-archive nav-icon"></i>
          <span class="nav-label">Archived Leads</span>
        </div>
      </a>
    <?php endif; ?>

    <!-- 8. Administration & Role Hierarchy -->
    <?php if (can('staff', 'view') || is_super_admin()): ?>
      <div class="nav-section-title">Administration</div>
      <a href="<?= base_url('organization/users.php') ?>" id="sidebar-nav-staff" class="nav-item <?= in_array($activePage, ['organization_users', 'users']) ? 'active' : '' ?>">
        <div class="nav-item-content">
          <i class="fa-solid fa-user-gear nav-icon"></i>
          <span class="nav-label">Team & Roles</span>
        </div>
      </a>
    <?php endif; ?>

  </nav>

  <!-- Sidebar User Profile Footer -->
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
