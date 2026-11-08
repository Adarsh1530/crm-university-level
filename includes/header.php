<?php
/**
 * Header Component - NexLink UI Redesign
 * University-Level Student CRM Demo
 */

require_once __DIR__ . '/auth.php';

$currentUser = current_user();
$unreadNotifCount = $currentUser ? get_unread_notifications_count($currentUser['id']) : 0;
$recentNotifs = $currentUser ? get_recent_notifications($currentUser['id'], 5) : [];
$pageTitle = $pageTitle ?? 'University CRM';
$activePage = $activePage ?? '';
$currentUri = $_SERVER['REQUEST_URI'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> | University-Level Student CRM</title>
  
  <!-- Google Fonts: Instrument Sans & Plus Jakarta Sans -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:ital,wght@0,400..700;1,400..700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  
  <!-- FontAwesome 6 Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  
  <!-- Chart.js 4 -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <!-- Master NexLink CRM Stylesheet -->
  <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
</head>
<body>
<div id="app-layout">
  
  <!-- Include NexLink Collapsible Sidebar -->
  <?php include __DIR__ . '/sidebar.php'; ?>

  <!-- Main Application Wrapper -->
  <div id="main-wrapper">
    
    <!-- Top Navigation Bar -->
    <header id="top-navbar">
      <div class="navbar-left">
        <button type="button" class="sidebar-toggle-btn" id="sidebar-toggle" title="Toggle Sidebar">
          <i class="fa-solid fa-bars-staggered"></i>
        </button>
      </div>

      <div class="navbar-right">
        <!-- Role User Manual & Interactive Guided Tutorial Button -->
        <button type="button" class="btn btn-ghost btn-sm" id="btn-guide-tutorial" onclick="startInteractiveTutorial()" style="font-weight:600; padding:0.42rem 0.75rem; display:inline-flex; align-items:center; gap:5px; border-radius:8px; border:1px solid var(--border-color); background:#fff;" title="Start Interactive Role Tutorial">
          <i class="fa-solid fa-wand-magic-sparkles" style="color:var(--primary); font-size:0.95rem;"></i>
          <span class="d-none d-md-inline" style="font-size:0.8rem;">Guide</span>
        </button>

        <!-- Quick Role Switcher -->
        <div class="role-switcher-dropdown" style="position: relative;">
          <button type="button" class="role-switcher-btn" id="role-switcher-btn" title="Simulate different role permissions">
            <i class="fa-solid fa-shuffle"></i>
            <span>Role: <strong><?= htmlspecialchars($currentUser['role_label'] ?? 'Guest') ?></strong></span>
            <i class="fa-solid fa-chevron-down" style="font-size:0.65rem; margin-left:2px;"></i>
          </button>
          
          <div class="dropdown-menu-custom" id="role-switcher-menu" style="width: 320px;">
            <div class="dropdown-header">
              <h4>Switch Role Persona</h4>
              <span class="sidebar-demo-badge">Quick Switch</span>
            </div>
            <div class="dropdown-list">
              <a href="<?= base_url('switch_role.php?user_id=1&return=' . urlencode($currentUri)) ?>" class="notif-item <?= ($currentUser['id'] ?? 0) == 1 ? 'unread' : '' ?>">
                <div class="notif-icon-wrap" style="background:#fee2e2; color:#b91c1c;"><i class="fa-solid fa-crown"></i></div>
                <div class="notif-content">
                  <div class="notif-title">Dr. Arthur Vance</div>
                  <div class="notif-time">Super Admin (System Administration)</div>
                </div>
                <?php if (($currentUser['id'] ?? 0) == 1): ?>
                  <i class="fa-solid fa-circle-check" style="color:var(--primary); font-size:1rem;"></i>
                <?php endif; ?>
              </a>
              <a href="<?= base_url('switch_role.php?user_id=2&return=' . urlencode($currentUri)) ?>" class="notif-item <?= ($currentUser['id'] ?? 0) == 2 ? 'unread' : '' ?>">
                <div class="notif-icon-wrap" style="background:#fef3c7; color:#b45309;"><i class="fa-solid fa-building-user"></i></div>
                <div class="notif-content">
                  <div class="notif-title">Marcus Sterling</div>
                  <div class="notif-time">Admissions Manager (Operations & Approvals)</div>
                </div>
                <?php if (($currentUser['id'] ?? 0) == 2): ?>
                  <i class="fa-solid fa-circle-check" style="color:var(--primary); font-size:1rem;"></i>
                <?php endif; ?>
              </a>
              <a href="<?= base_url('switch_role.php?user_id=4&return=' . urlencode($currentUri)) ?>" class="notif-item <?= ($currentUser['id'] ?? 0) == 4 ? 'unread' : '' ?>">
                <div class="notif-icon-wrap" style="background:#dcfce7; color:#15803d;"><i class="fa-solid fa-headset"></i></div>
                <div class="notif-content">
                  <div class="notif-title">David Miller</div>
                  <div class="notif-time">Admissions Counselor (Student Consultations)</div>
                </div>
                <?php if (($currentUser['id'] ?? 0) == 4): ?>
                  <i class="fa-solid fa-circle-check" style="color:var(--primary); font-size:1rem;"></i>
                <?php endif; ?>
              </a>
              <a href="<?= base_url('switch_role.php?user_id=5&return=' . urlencode($currentUri)) ?>" class="notif-item <?= ($currentUser['id'] ?? 0) == 5 ? 'unread' : '' ?>">
                <div class="notif-icon-wrap" style="background:#f3e8ff; color:#7e22ce;"><i class="fa-solid fa-user-tie"></i></div>
                <div class="notif-content">
                  <div class="notif-title">Sarah Jenkins</div>
                  <div class="notif-time">Academic Advisor (Course & Visa Guidance)</div>
                </div>
                <?php if (($currentUser['id'] ?? 0) == 5): ?>
                  <i class="fa-solid fa-circle-check" style="color:var(--primary); font-size:1rem;"></i>
                <?php endif; ?>
              </a>
            </div>
          </div>
        </div>

        <!-- Notification Bell Dropdown -->
        <div style="position: relative;">
          <button type="button" class="notification-bell-btn" id="notif-bell-btn" title="Notifications">
            <i class="fa-regular fa-bell"></i>
            <?php if ($unreadNotifCount > 0): ?>
              <span class="notification-counter"><?= $unreadNotifCount ?></span>
            <?php endif; ?>
          </button>

          <div class="dropdown-menu-custom" id="notif-dropdown-menu" style="width: 340px;">
            <div class="dropdown-header">
              <h4>Notifications (<?= $unreadNotifCount ?> not attended)</h4>
              <a href="<?= base_url('notifications/mark_attended.php?all=1&return=' . urlencode($currentUri)) ?>" style="font-size:0.75rem; color:var(--primary); font-weight:600;">Mark all attended</a>
            </div>
            <div class="dropdown-list">
              <?php if (empty($recentNotifs)): ?>
                <div style="padding:2rem 1.5rem; text-align:center; color:var(--text-muted); font-size:0.85rem;">
                  <i class="fa-regular fa-bell-slash" style="font-size:1.8rem; margin-bottom:0.5rem; display:block; color:var(--text-light);"></i>
                  No new notifications
                </div>
              <?php else: ?>
                <?php foreach ($recentNotifs as $notif): 
                  $isNotAttended = ($notif['status'] ?? 'Not Attended') === 'Not Attended';
                ?>
                  <a href="<?= !empty($notif['link']) ? base_url($notif['link']) : base_url('notifications/index.php') ?>" class="notif-item <?= $isNotAttended ? 'unread' : '' ?>">
                    <div class="notif-icon-wrap" style="background:var(--primary-subtle); color:var(--primary);">
                      <i class="fa-solid fa-circle-exclamation"></i>
                    </div>
                    <div class="notif-content">
                      <div class="notif-title"><?= htmlspecialchars($notif['title']) ?></div>
                      <div class="notif-time"><?= time_ago($notif['created_at']) ?></div>
                    </div>
                  </a>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
            <div class="dropdown-footer">
              <a href="<?= base_url('notifications/index.php') ?>" style="color:var(--primary); text-decoration:none;">View All Notifications &rarr;</a>
            </div>
          </div>
        </div>

        <!-- User Profile Nav Dropdown -->
        <div style="position: relative;">
          <button type="button" class="profile-dropdown-btn" id="user-profile-btn">
            <div class="user-avatar-sm online">
              <?= user_initials($currentUser['name'] ?? 'U') ?>
            </div>
            <div style="text-align: left; display: none; line-height: 1.2;" class="d-md-block">
              <div style="font-size: 0.84rem; font-weight: 700; color: var(--text-heading);"><?= htmlspecialchars($currentUser['name'] ?? 'User') ?></div>
              <div style="font-size: 0.72rem; color: var(--text-muted);"><?= htmlspecialchars($currentUser['role_label'] ?? 'Staff') ?></div>
            </div>
            <i class="fa-solid fa-chevron-down" style="font-size:0.65rem; color:var(--text-muted);"></i>
          </button>

          <div class="dropdown-menu-custom" id="user-profile-menu" style="width: 240px;">
            <div class="dropdown-header">
              <div>
                <strong style="display:block; font-size:0.88rem; color:var(--text-heading);"><?= htmlspecialchars($currentUser['name'] ?? 'User') ?></strong>
                <span style="font-size:0.75rem; color:var(--text-muted);"><?= htmlspecialchars($currentUser['email'] ?? '') ?></span>
              </div>
            </div>
            <div style="padding: 0.5rem;">
              <a href="<?= base_url('tasks/index.php') ?>" class="notif-item" style="border:none; border-radius:var(--radius-sm); padding:0.55rem 0.85rem;">
                <i class="fa-solid fa-list-check" style="color:var(--primary); width:20px;"></i> My Operational Tasks
              </a>
              <a href="<?= base_url('reports/index.php?report=counselors') ?>" class="notif-item" style="border:none; border-radius:var(--radius-sm); padding:0.55rem 0.85rem;">
                <i class="fa-solid fa-chart-pie" style="color:var(--warning); width:20px;"></i> Performance Analytics
              </a>
              <?php if (is_super_admin()): ?>
                <a href="<?= base_url('organization/roles.php') ?>" class="notif-item" style="border:none; border-radius:var(--radius-sm); padding:0.55rem 0.85rem;">
                  <i class="fa-solid fa-shield-halved" style="color:#7008E7; width:20px;"></i> Permissions Matrix
                </a>
                <a href="<?= base_url('system/settings.php') ?>" class="notif-item" style="border:none; border-radius:var(--radius-sm); padding:0.55rem 0.85rem;">
                  <i class="fa-solid fa-gear" style="color:var(--primary); width:20px;"></i> System Governance
                </a>
              <?php endif; ?>
              <div style="border-top: 1px solid var(--border-color); margin: 0.4rem 0;"></div>
              <a href="<?= base_url('logout.php') ?>" class="notif-item" style="border:none; border-radius:var(--radius-sm); padding:0.55rem 0.85rem; color:var(--danger);">
                <i class="fa-solid fa-right-from-bracket" style="width:20px;"></i> Sign Out
              </a>
            </div>
          </div>
        </div>

      </div>
    </header>

    <!-- Main Content Container -->
    <main class="content-area">
      <!-- Breadcrumb Navigation Banner -->
      <nav aria-label="breadcrumb">
        <div class="breadcrumb-wrap">
          <a href="<?= base_url('dashboard/index.php') ?>"><i class="fa-solid fa-house" style="font-size:0.78rem;"></i> Home</a>
          <span class="breadcrumb-separator">/</span>
          <span class="breadcrumb-current"><?= htmlspecialchars($pageTitle) ?></span>
        </div>
      </nav>

      <!-- Flash Alert Messages -->
      <div class="flash-container">
        <?php foreach (get_flashes() as $flash): ?>
          <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>">
            <div><?= $flash['message'] ?></div>
            <button type="button" class="alert-close">&times;</button>
          </div>
        <?php endforeach; ?>
      </div>
