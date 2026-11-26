<?php
/**
 * Header Component - Core CRM UI
 * Aligned 1:1 with Client Specifications:
 * - Brand: CRM (Customer Relationship Management)
 * - Top Header Tasks Bar ("if you have a task, you can use this one in the top")
 * - Top Header Update Action ("if you have an update, you can use it in the top")
 * - Fast Search by Region Number (REG-01), customer record #, or phone
 * - Multi-Role Persona Switcher: Super Admin, Team Leader, Counselor / Advisor, Operations / Accessor
 */

require_once __DIR__ . '/auth.php';

$currentUser = current_user();
$unreadNotifCount = $currentUser ? get_unread_notifications_count($currentUser['id']) : 0;
$recentNotifs = $currentUser ? get_recent_notifications($currentUser['id'], 5) : [];
$pageTitle = $pageTitle ?? 'CRM';
$activePage = $activePage ?? '';
$currentUri = $_SERVER['REQUEST_URI'] ?? '';

// Top Header Tasks Count calculation
$topTasksCount = 0;
try {
    if ($pdo && $currentUser) {
        $scopeLeadsHeader = get_crm_scope_clause('l', 'assigned_counselor_id', 'branch_id');
        $topScheduled = (int)$pdo->query("SELECT COUNT(*) FROM followups f JOIN leads l ON f.lead_id = l.id WHERE f.scheduled_date = CURDATE() AND f.status = 'Pending' AND l.is_archived = 0 AND {$scopeLeadsHeader}")->fetchColumn();
        $topPending = (int)$pdo->query("SELECT COUNT(*) FROM followups f JOIN leads l ON f.lead_id = l.id WHERE f.scheduled_date < CURDATE() AND f.status = 'Pending' AND l.is_archived = 0 AND {$scopeLeadsHeader}")->fetchColumn();
        $topTasksCount = $topScheduled + $topPending;
    }
} catch (Exception $e) {
    $topTasksCount = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> | CRM - Customer Relationship Management</title>
  
  <!-- Google Fonts: Instrument Sans & Plus Jakarta Sans -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:ital,wght@0,400..700;1,400..700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  
  <!-- FontAwesome 6 Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  
  <!-- Chart.js 4 -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <!-- Master CRM Stylesheet -->
  <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
</head>
<body>
<div id="app-layout">
  
  <!-- Include Core CRM Collapsible Sidebar -->
  <?php include __DIR__ . '/sidebar.php'; ?>

  <!-- Main Application Wrapper -->
  <div id="main-wrapper">
    
    <!-- Top Navigation Bar -->
    <header id="top-navbar">
      <div class="navbar-left" style="display:flex; align-items:center; gap:0.75rem; flex:1;">
        <button type="button" class="sidebar-toggle-btn" id="sidebar-toggle" title="Toggle Sidebar">
          <i class="fa-solid fa-bars-staggered"></i>
        </button>
        <!-- Global Fast Search Bar (Supports Region Numbers like REG-01, Record #, Phone) -->
        <form action="<?= base_url('leads/index.php') ?>" method="GET" style="max-width:340px; width:100%; position:relative;" class="d-none d-md-block">
          <input type="text" name="search" placeholder="Search by region (e.g. REG-01), customer ID, phone..." style="width:100%; height:36px; border-radius:20px; border:1px solid var(--border-color); padding:0 12px 0 34px; font-size:0.8rem; background:var(--bg-subtle); outline:none; transition:border-color 0.2s;" onfocus="this.style.borderColor='var(--primary)'; this.style.background='#fff';" onblur="this.style.borderColor='var(--border-color)'; this.style.background='var(--bg-subtle)';">
          <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); font-size:0.78rem; color:var(--text-light); pointer-events:none;"></i>
        </form>
      </div>

      <div class="navbar-right" style="display:flex; align-items:center; gap:0.6rem;">
        <!-- Top Header Tasks Bar (Direct Access to Daily Assigned Tasks Worklist) -->
        <a href="<?= base_url('tasks/index.php') ?>" class="btn btn-ghost btn-sm" id="top-tasks-bar-btn" title="View Assigned Tasks & Follow-up Worklists" style="font-weight:700; padding:0.42rem 0.85rem; display:inline-flex; align-items:center; gap:6px; border-radius:8px; border:1px solid var(--border-color); background:#fff; color:var(--text-heading); text-decoration:none;">
          <i class="fa-solid fa-list-check" style="color:var(--primary); font-size:0.9rem;"></i>
          <span class="d-none d-sm-inline" style="font-size:0.8rem;">Tasks</span>
          <?php if ($topTasksCount > 0): ?>
            <span class="badge badge-warning" style="font-size:0.68rem; padding:2px 6px; border-radius:10px;"><?= $topTasksCount ?></span>
          <?php endif; ?>
        </a>

        <!-- Top Header Update Action Button (1-Click Save / Status Update) -->
        <button type="button" class="btn btn-sm" id="top-update-action-btn" onclick="openTopUpdateModal()" title="1-Click Record & Status Update" style="background:linear-gradient(135deg, #059669, #10b981); color:#fff; font-weight:700; padding:0.42rem 0.85rem; display:inline-flex; align-items:center; gap:6px; border-radius:8px; border:none; box-shadow:0 2px 6px rgba(16,185,129,0.25); cursor:pointer;">
          <i class="fa-solid fa-arrows-rotate"></i>
          <span style="font-size:0.8rem;">Update</span>
        </button>

        <!-- 1-Click Fast Lead Capture -->
        <?php if (can('leads', 'add')): ?>
          <button type="button" class="btn btn-primary btn-sm" onclick="openQuickLeadModal()" style="font-weight:700; padding:0.42rem 0.85rem; display:inline-flex; align-items:center; gap:6px; border-radius:8px; box-shadow:0 2px 6px rgba(89,85,209,0.25);" title="Quick Add Lead">
            <i class="fa-solid fa-plus"></i>
            <span class="d-none d-sm-inline" style="font-size:0.8rem;">+ New Lead</span>
          </button>
        <?php endif; ?>

        <!-- Role User Manual & Guided Tour -->
        <button type="button" class="btn btn-ghost btn-sm" id="btn-guide-tutorial" onclick="startInteractiveTutorial()" style="font-weight:600; padding:0.42rem 0.75rem; display:inline-flex; align-items:center; gap:5px; border-radius:8px; border:1px solid var(--border-color); background:#fff;" title="CRM System Architecture & Roles Guide">
          <i class="fa-solid fa-circle-question" style="color:var(--primary); font-size:0.95rem;"></i>
          <span class="d-none d-md-inline" style="font-size:0.8rem;">Guide</span>
        </button>

        <!-- Multi-Role Persona Switcher (Client's 4 Core Roles) -->
        <div class="role-switcher-dropdown" style="position: relative;">
          <button type="button" class="role-switcher-btn" id="role-switcher-btn" title="Simulate multi-role permissions">
            <i class="fa-solid fa-shuffle"></i>
            <span>Role: <strong><?= htmlspecialchars($currentUser['role_label'] ?? 'Guest') ?></strong></span>
            <i class="fa-solid fa-chevron-down" style="font-size:0.65rem; margin-left:2px;"></i>
          </button>
          
          <div class="dropdown-menu-custom" id="role-switcher-menu" style="width: 320px;">
            <div class="dropdown-header">
              <h4>Switch Role Persona</h4>
              <span class="sidebar-demo-badge">Multi-Role</span>
            </div>
            <div class="dropdown-list">
              <!-- Role 1: Super Admin -->
              <a href="<?= base_url('switch_role.php?user_id=1&return=' . urlencode($currentUri)) ?>" class="notif-item <?= ($currentUser['id'] ?? 0) == 1 ? 'unread' : '' ?>">
                <div class="notif-icon-wrap" style="background:#fee2e2; color:#b91c1c;"><i class="fa-solid fa-crown"></i></div>
                <div class="notif-content">
                  <div class="notif-title">Dr. Arthur Vance</div>
                  <div class="notif-time">Super Admin (Full Governance & Oversight)</div>
                </div>
                <?php if (($currentUser['id'] ?? 0) == 1): ?>
                  <i class="fa-solid fa-circle-check" style="color:var(--primary); font-size:1rem;"></i>
                <?php endif; ?>
              </a>
              <!-- Role 2: Team Leader -->
              <a href="<?= base_url('switch_role.php?user_id=2&return=' . urlencode($currentUri)) ?>" class="notif-item <?= ($currentUser['id'] ?? 0) == 2 ? 'unread' : '' ?>">
                <div class="notif-icon-wrap" style="background:#fef3c7; color:#b45309;"><i class="fa-solid fa-people-roof"></i></div>
                <div class="notif-content">
                  <div class="notif-title">Sarah Jenkins</div>
                  <div class="notif-time">Team Leader (Regional Supervisor & Lead Review)</div>
                </div>
                <?php if (($currentUser['id'] ?? 0) == 2): ?>
                  <i class="fa-solid fa-circle-check" style="color:var(--primary); font-size:1rem;"></i>
                <?php endif; ?>
              </a>
              <!-- Role 3: Counselor / Advisor -->
              <a href="<?= base_url('switch_role.php?user_id=3&return=' . urlencode($currentUri)) ?>" class="notif-item <?= ($currentUser['id'] ?? 0) == 3 ? 'unread' : '' ?>">
                <div class="notif-icon-wrap" style="background:#dcfce7; color:#15803d;"><i class="fa-solid fa-user-tie"></i></div>
                <div class="notif-content">
                  <div class="notif-title">Michael Chang</div>
                  <div class="notif-time">Counselor / Advisor (Consultations & Conversions)</div>
                </div>
                <?php if (($currentUser['id'] ?? 0) == 3): ?>
                  <i class="fa-solid fa-circle-check" style="color:var(--primary); font-size:1rem;"></i>
                <?php endif; ?>
              </a>
              <!-- Role 4: Operations / Accessor -->
              <a href="<?= base_url('switch_role.php?user_id=4&return=' . urlencode($currentUri)) ?>" class="notif-item <?= ($currentUser['id'] ?? 0) == 4 ? 'unread' : '' ?>">
                <div class="notif-icon-wrap" style="background:#f3e8ff; color:#7e22ce;"><i class="fa-solid fa-headset"></i></div>
                <div class="notif-content">
                  <div class="notif-title">Emily Watson</div>
                  <div class="notif-time">Operations / Accessor (Ingestion, Phone Verification & Routing)</div>
                </div>
                <?php if (($currentUser['id'] ?? 0) == 4): ?>
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
              <h4>Notifications (<?= $unreadNotifCount ?> unread)</h4>
              <a href="<?= base_url('notifications/mark_attended.php?all=1&return=' . urlencode($currentUri)) ?>" style="font-size:0.75rem; color:var(--primary); font-weight:600;">Mark all read</a>
            </div>
            <div class="dropdown-list">
              <?php if (empty($recentNotifs)): ?>
                <div style="padding:2rem 1.5rem; text-align:center; color:var(--text-muted); font-size:0.85rem;">
                  <i class="fa-regular fa-bell-slash" style="font-size:1.8rem; margin-bottom:0.5rem; display:block; color:var(--text-light);"></i>
                  No new notifications
                </div>
              <?php else: ?>
                <?php foreach ($recentNotifs as $notif): ?>
                  <a href="<?= !empty($notif['link']) ? base_url($notif['link']) : '#' ?>" class="notif-item <?= $notif['is_read'] ? '' : 'unread' ?>">
                    <div class="notif-icon-wrap"><i class="fa-solid fa-bell"></i></div>
                    <div class="notif-content">
                      <div class="notif-title"><?= htmlspecialchars($notif['title']) ?></div>
                      <div class="notif-message"><?= htmlspecialchars($notif['message']) ?></div>
                      <div class="notif-time"><?= date('M d, H:i', strtotime($notif['created_at'])) ?></div>
                    </div>
                  </a>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
            <div class="dropdown-footer">
              <a href="<?= base_url('notifications/index.php') ?>" style="font-size:0.75rem; color:var(--primary); font-weight:700;">View All Activity &rarr;</a>
            </div>
          </div>
        </div>

      </div>
    </header>

    <!-- Main Content Container -->
    <main class="content-area" id="main-content">
      <!-- Flash Alert Messages -->
      <div class="flash-container">
        <?php foreach (get_flashes() as $flash): ?>
          <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>" style="margin-bottom:1.25rem; border-radius:8px; padding:0.85rem 1.25rem; display:flex; justify-content:space-between; align-items:center;">
            <div><?= $flash['message'] ?></div>
            <button type="button" class="alert-close" onclick="this.parentElement.remove()" style="border:none; background:transparent; font-size:1.2rem; cursor:pointer;">&times;</button>
          </div>
        <?php endforeach; ?>
      </div>
