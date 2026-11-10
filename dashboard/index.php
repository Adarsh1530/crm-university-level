<?php
/**
 * Main Institutional Dashboard - Multi-Branch Hierarchy
 * University-Level Education Consultancy CRM (EdrootsCRM)
 * Ultra-Simple, Clean & Fast Daily Workflow for All Roles
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_permission('dashboard', 'view');

$currentUser = current_user();
$pageTitle = 'Admissions Cockpit';
$activePage = 'dashboard';

// Role identifiers
$isCounselor = is_counselor();
$isManager = is_branch_manager();
$isSuperAdmin = is_super_admin();

// Scoping clause for current user
$scopeLeads = get_crm_scope_clause('l', 'assigned_counselor_id', 'branch_id');

// 1. Core KPIs
$totalLeads = (int)$pdo->query("SELECT COUNT(*) FROM leads l WHERE l.is_archived = 0 AND {$scopeLeads}")->fetchColumn();
$totalCustomers = (int)$pdo->query("SELECT COUNT(*) FROM leads l WHERE l.is_customer = 1 AND l.is_archived = 0 AND {$scopeLeads}")->fetchColumn();
$totalEnrolled = (int)$pdo->query("SELECT COUNT(*) FROM leads l WHERE l.stage = 'ENROLLED' AND l.is_archived = 0 AND {$scopeLeads}")->fetchColumn();

$revStats = $pdo->query("
    SELECT 
        COALESCE(SUM(l.invoiced_total), 0) as total_invoiced,
        COALESCE(SUM(l.paid_total), 0) as total_collected,
        COALESCE(SUM(l.balance_total), 0) as total_balance
    FROM leads l
    WHERE l.is_customer = 1 AND l.is_archived = 0 AND {$scopeLeads}
")->fetch();

$totalInvoiced = (float)($revStats['total_invoiced'] ?? 0);
$totalCollected = (float)($revStats['total_collected'] ?? 0);
$totalBalance = (float)($revStats['total_balance'] ?? 0);
$conversionRate = ($totalLeads > 0) ? round(($totalCustomers / $totalLeads) * 100, 1) : 0;

// 2. The 4 Operational Task Queues Counts
$countScheduled = (int)$pdo->query("
    SELECT COUNT(*) FROM followups f
    JOIN leads l ON f.lead_id = l.id
    WHERE f.scheduled_date = CURDATE() AND f.status = 'Pending' AND l.is_archived = 0 AND {$scopeLeads}
")->fetchColumn();

$countOverdue = (int)$pdo->query("
    SELECT COUNT(*) FROM followups f
    JOIN leads l ON f.lead_id = l.id
    WHERE f.scheduled_date < CURDATE() AND f.status = 'Pending' AND l.is_archived = 0 AND {$scopeLeads}
")->fetchColumn();

$countTodayLeads = (int)$pdo->query("
    SELECT COUNT(*) FROM leads l
    WHERE DATE(l.assigned_at) = CURDATE() AND l.followup_count = 0 AND l.is_archived = 0 AND {$scopeLeads}
")->fetchColumn();

$countPendingLeads = (int)$pdo->query("
    SELECT COUNT(*) FROM leads l
    WHERE DATE(l.assigned_at) < CURDATE() AND l.followup_count = 0 AND l.is_archived = 0 AND {$scopeLeads}
")->fetchColumn();

$totalTasks = $countScheduled + $countOverdue + $countTodayLeads + $countPendingLeads;

// 3. 6 Standard Pipeline Stages Breakdown
$stageStats = $pdo->query("
    SELECT l.stage, COUNT(*) as count 
    FROM leads l 
    WHERE l.is_archived = 0 AND {$scopeLeads}
    GROUP BY l.stage
")->fetchAll(PDO::FETCH_KEY_PAIR);

$standardStages = ['ENQUIRY', 'CUSTOMER', 'APPLICATION', 'OFFER RECEIVED', 'TUITION FEES PAID', 'ENROLLED'];
$stageChartLabels = [];
$stageChartData = [];
foreach ($standardStages as $stg) {
    $stageChartLabels[] = $stg;
    $stageChartData[] = (int)($stageStats[$stg] ?? 0);
}

// 4. Branch Yields (for Super Admin)
$branchStats = [];
if ($isSuperAdmin) {
    $branchStats = $pdo->query("
        SELECT b.id, b.name as branch_name, b.city,
               COUNT(l.id) as lead_count,
               COUNT(CASE WHEN l.stage = 'ENROLLED' THEN 1 END) as enrolled_count,
               COALESCE(SUM(l.paid_total), 0) as collected_amount
        FROM branches b
        LEFT JOIN leads l ON b.id = l.branch_id AND l.is_archived = 0
        GROUP BY b.id
        ORDER BY lead_count DESC
    ")->fetchAll();
}

$branchChartLabels = array_column($branchStats, 'branch_name');
$branchChartLeads = array_column($branchStats, 'lead_count');

// 5. Branch Counselors Summary (for Branch Manager)
$branchCounselors = [];
if ($isManager) {
    $bId = (int)($currentUser['branch_id'] ?? 0);
    $stmtBc = $pdo->prepare("
        SELECT u.id, u.name, u.email,
               (SELECT COUNT(*) FROM leads WHERE assigned_counselor_id = u.id AND is_archived = 0) as active_leads,
               (SELECT COUNT(*) FROM followups f JOIN leads l ON f.lead_id = l.id WHERE l.assigned_counselor_id = u.id AND f.scheduled_date = CURDATE() AND f.status = 'Pending' AND l.is_archived = 0) as today_calls,
               (SELECT COUNT(*) FROM followups f JOIN leads l ON f.lead_id = l.id WHERE l.assigned_counselor_id = u.id AND f.scheduled_date < CURDATE() AND f.status = 'Pending' AND l.is_archived = 0) as overdue_calls,
               (SELECT COUNT(*) FROM leads WHERE assigned_counselor_id = u.id AND stage = 'ENROLLED' AND is_archived = 0) as enrolled_count
        FROM users u
        JOIN roles r ON u.role_id = r.id
        WHERE r.name = 'counselor' AND u.status = 'Active' AND u.branch_id = ?
        ORDER BY active_leads DESC
    ");
    $stmtBc->execute([$bId]);
    $branchCounselors = $stmtBc->fetchAll();
}

// 6. Recent Active Leads
$recentLeads = $pdo->query("
    SELECT l.*, b.name as branch_name, u.name as counselor_name
    FROM leads l
    LEFT JOIN branches b ON l.branch_id = b.id
    LEFT JOIN users u ON l.assigned_counselor_id = u.id
    WHERE l.is_archived = 0 AND {$scopeLeads}
    ORDER BY l.id DESC
    LIMIT 6
")->fetchAll();

// 7. Recent Activity Logs (Admin only)
$recentActivities = [];
if ($isSuperAdmin) {
    $recentActivities = $pdo->query("
        SELECT a.*, u.name as user_name
        FROM activity_logs a
        LEFT JOIN users u ON a.user_id = u.id
        ORDER BY a.id DESC
        LIMIT 5
    ")->fetchAll();
}

// 8. Urgent Follow-up Calls Due Today
$urgentCalls = $pdo->query("
    SELECT f.id as followup_id, f.lead_id, f.scheduled_date, f.scheduled_time, f.notes as last_notes,
           l.full_name, l.phone, l.preferred_country, l.stage, l.lead_code
    FROM followups f
    JOIN leads l ON f.lead_id = l.id
    WHERE f.status = 'Pending' AND f.scheduled_date <= CURDATE() AND l.is_archived = 0 AND {$scopeLeads}
    ORDER BY f.scheduled_date ASC, f.id DESC
    LIMIT 6
")->fetchAll();

// 9. Inbound Leads awaiting allocation (for Manager / Admin)
$unassignedWebLeads = [];
if (!$isCounselor) {
    $unassignedWebLeads = $pdo->query("
        SELECT w.* FROM web_leads w WHERE w.status = 'Pending' AND w.assigned_counselor_id IS NULL ORDER BY w.id DESC LIMIT 4
    ")->fetchAll();
}

include __DIR__ . '/../includes/header.php';
?>

<!-- 1. HERO BANNER: Clean, Personalized Daily Command -->
<div class="card" style="margin-bottom:1.5rem; padding:1.5rem 1.75rem; background:linear-gradient(135deg, #ffffff 0%, #f8faff 100%); border:1px solid #e2e8f0; border-radius:14px; box-shadow:0 2px 8px rgba(0,0,0,0.03);">
  <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1.25rem;">
    <div>
      <div style="display:flex; align-items:center; gap:8px; margin-bottom:0.35rem;">
        <h1 style="font-size:1.65rem; font-weight:800; color:var(--text-heading); margin:0;">
          <?php if ($isCounselor): ?>
            Good day, <?= htmlspecialchars($currentUser['name']) ?>! ☀️
          <?php elseif ($isManager): ?>
            Welcome back, <?= htmlspecialchars($currentUser['name']) ?>! 🏢
          <?php else: ?>
            Welcome, <?= htmlspecialchars($currentUser['name']) ?>! 🎓
          <?php endif; ?>
        </h1>
      </div>
      <p style="font-size:0.9rem; color:var(--text-muted); margin:0; line-height:1.5;">
        <?php if ($isCounselor): ?>
          <strong><?= htmlspecialchars($currentUser['branch_name'] ?? 'Admissions') ?></strong> &bull; 
          You have <strong style="color:var(--primary);"><?= $countScheduled ?> calls</strong> due today
          <?php if ($countOverdue > 0): ?>
            and <strong style="color:var(--danger);"><?= $countOverdue ?> overdue</strong>.
          <?php else: ?>
            and <strong style="color:var(--success);">0 overdue</strong> 🎉.
          <?php endif; ?>
        <?php elseif ($isManager): ?>
          Managing <strong><?= htmlspecialchars($currentUser['branch_name'] ?? 'Division') ?></strong> &bull;
          <strong><?= $totalLeads ?></strong> active students &bull; <strong><?= count($branchCounselors) ?></strong> active counselors.
        <?php else: ?>
          Institutional Executive Cockpit &bull; Global multi-campus oversight.
        <?php endif; ?>
      </p>
    </div>

    <!-- Quick Action Launchpad -->
    <div style="display:flex; gap:0.6rem; align-items:center; flex-wrap:wrap;">
      <button type="button" class="btn btn-primary" id="btn-quick-add-lead" onclick="openQuickLeadModal()" style="font-weight:700; padding:0.55rem 1.15rem; font-size:0.875rem; border-radius:10px; box-shadow:0 3px 8px rgba(89,85,209,0.25);">
        <i class="fa-solid fa-plus"></i> Quick Add Lead
      </button>

      <?php if ($isCounselor): ?>
        <a href="<?= base_url('tasks/index.php?queue=scheduled') ?>" class="btn btn-success" style="font-weight:700; padding:0.55rem 1.15rem; font-size:0.875rem; border-radius:10px;">
          <i class="fa-solid fa-phone-volume"></i> Start Calling (<?= $countScheduled ?>)
        </a>
      <?php elseif ($isManager && !empty($unassignedWebLeads)): ?>
        <a href="<?= base_url('data/web_leads.php') ?>" class="btn btn-info" style="font-weight:700; padding:0.55rem 1.15rem; font-size:0.875rem; border-radius:10px; background:#0284c7; color:#fff;">
          <i class="fa-solid fa-bolt"></i> Triage Leads (<?= count($unassignedWebLeads) ?>)
        </a>
      <?php endif; ?>

      <?php if (can('invoices', 'add')): ?>
        <a href="<?= base_url('finance/create_invoice.php') ?>" class="btn btn-secondary" id="btn-generate-invoice" style="font-weight:600; padding:0.55rem 1rem; font-size:0.875rem; border-radius:10px;">
          <i class="fa-solid fa-file-invoice-dollar"></i> Invoice
        </a>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- 2. ROLE-TAILORED PRIMARY METRICS (Keep #widget-metric-cards for Tutorial) -->
<div id="widget-metric-cards" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(210px, 1fr)); gap:1rem; margin-bottom:1.5rem;">
  <?php if ($isCounselor): ?>
    <!-- Counselor KPI 1: My Active Students -->
    <a href="<?= base_url('leads/index.php') ?>" class="card" style="padding:1.15rem 1.25rem; display:flex; align-items:center; gap:1rem; text-decoration:none; transition:all 0.15s; border-radius:12px;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
      <div style="width:48px; height:48px; border-radius:12px; background:var(--primary-subtle); color:var(--primary); display:flex; align-items:center; justify-content:center; font-size:1.3rem;">
        <i class="fa-solid fa-user-graduate"></i>
      </div>
      <div>
        <div style="font-size:1.55rem; font-weight:800; color:var(--text-heading); line-height:1.1;"><?= number_format($totalLeads) ?></div>
        <div style="font-size:0.8rem; color:var(--text-muted); font-weight:600; margin-top:2px;">My Active Students</div>
      </div>
    </a>

    <!-- Counselor KPI 2: Today's Scheduled Calls -->
    <a href="<?= base_url('tasks/index.php?queue=scheduled') ?>" class="card" style="padding:1.15rem 1.25rem; display:flex; align-items:center; gap:1rem; text-decoration:none; transition:all 0.15s; border-radius:12px;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
      <div style="width:48px; height:48px; border-radius:12px; background:#e0f2fe; color:#0284c7; display:flex; align-items:center; justify-content:center; font-size:1.3rem;">
        <i class="fa-solid fa-phone-volume"></i>
      </div>
      <div>
        <div style="font-size:1.55rem; font-weight:800; color:#0284c7; line-height:1.1;"><?= number_format($countScheduled) ?></div>
        <div style="font-size:0.8rem; color:var(--text-muted); font-weight:600; margin-top:2px;">Calls Due Today</div>
      </div>
    </a>

    <!-- Counselor KPI 3: Overdue / Action Needed -->
    <a href="<?= base_url('tasks/index.php?queue=pending_followup') ?>" class="card" style="padding:1.15rem 1.25rem; display:flex; align-items:center; gap:1rem; text-decoration:none; transition:all 0.15s; border-radius:12px;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
      <div style="width:48px; height:48px; border-radius:12px; background:<?= $countOverdue > 0 ? '#fee2e2' : '#dcfce7' ?>; color:<?= $countOverdue > 0 ? '#b91c1c' : '#15803d' ?>; display:flex; align-items:center; justify-content:center; font-size:1.3rem;">
        <i class="fa-solid <?= $countOverdue > 0 ? 'fa-triangle-exclamation' : 'fa-circle-check' ?>"></i>
      </div>
      <div>
        <div style="font-size:1.55rem; font-weight:800; color:<?= $countOverdue > 0 ? '#b91c1c' : '#15803d' ?>; line-height:1.1;"><?= number_format($countOverdue) ?></div>
        <div style="font-size:0.8rem; color:var(--text-muted); font-weight:600; margin-top:2px;"><?= $countOverdue > 0 ? 'Overdue Follow-ups' : '0 Overdue (Caught Up!)' ?></div>
      </div>
    </a>

    <!-- Counselor KPI 4: My Enrolled Students -->
    <a href="<?= base_url('leads/index.php?stage=ENROLLED') ?>" class="card" style="padding:1.15rem 1.25rem; display:flex; align-items:center; gap:1rem; text-decoration:none; transition:all 0.15s; border-radius:12px;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
      <div style="width:48px; height:48px; border-radius:12px; background:#dcfce7; color:#15803d; display:flex; align-items:center; justify-content:center; font-size:1.3rem;">
        <i class="fa-solid fa-trophy"></i>
      </div>
      <div>
        <div style="font-size:1.55rem; font-weight:800; color:#15803d; line-height:1.1;"><?= number_format($totalEnrolled) ?></div>
        <div style="font-size:0.8rem; color:var(--text-muted); font-weight:600; margin-top:2px;">My Enrolled Students</div>
      </div>
    </a>

  <?php elseif ($isManager): ?>
    <!-- Manager KPI 1: Branch Active Leads -->
    <a href="<?= base_url('leads/index.php') ?>" class="card" style="padding:1.15rem 1.25rem; display:flex; align-items:center; gap:1rem; text-decoration:none; border-radius:12px;">
      <div style="width:48px; height:48px; border-radius:12px; background:var(--primary-subtle); color:var(--primary); display:flex; align-items:center; justify-content:center; font-size:1.3rem;">
        <i class="fa-solid fa-building-user"></i>
      </div>
      <div>
        <div style="font-size:1.55rem; font-weight:800; color:var(--text-heading); line-height:1.1;"><?= number_format($totalLeads) ?></div>
        <div style="font-size:0.8rem; color:var(--text-muted); font-weight:600;">Branch Active Leads</div>
      </div>
    </a>

    <!-- Manager KPI 2: Team Calls Due Today -->
    <a href="<?= base_url('tasks/index.php?queue=scheduled') ?>" class="card" style="padding:1.15rem 1.25rem; display:flex; align-items:center; gap:1rem; text-decoration:none; border-radius:12px;">
      <div style="width:48px; height:48px; border-radius:12px; background:#e0f2fe; color:#0284c7; display:flex; align-items:center; justify-content:center; font-size:1.3rem;">
        <i class="fa-solid fa-phone-volume"></i>
      </div>
      <div>
        <div style="font-size:1.55rem; font-weight:800; color:#0284c7; line-height:1.1;"><?= number_format($countScheduled) ?></div>
        <div style="font-size:0.8rem; color:var(--text-muted); font-weight:600;">Team Calls Due Today</div>
      </div>
    </a>

    <!-- Manager KPI 3: Overdue Lag -->
    <a href="<?= base_url('tasks/index.php?queue=pending_followup') ?>" class="card" style="padding:1.15rem 1.25rem; display:flex; align-items:center; gap:1rem; text-decoration:none; border-radius:12px;">
      <div style="width:48px; height:48px; border-radius:12px; background:<?= $countOverdue > 0 ? '#fee2e2' : '#dcfce7' ?>; color:<?= $countOverdue > 0 ? '#b91c1c' : '#15803d' ?>; display:flex; align-items:center; justify-content:center; font-size:1.3rem;">
        <i class="fa-solid fa-clock-rotate-left"></i>
      </div>
      <div>
        <div style="font-size:1.55rem; font-weight:800; color:<?= $countOverdue > 0 ? '#b91c1c' : '#15803d' ?>; line-height:1.1;"><?= number_format($countOverdue) ?></div>
        <div style="font-size:0.8rem; color:var(--text-muted); font-weight:600;">Overdue Calls Lag</div>
      </div>
    </a>

    <!-- Manager KPI 4: Branch Enrolled Students -->
    <a href="<?= base_url('leads/index.php?stage=ENROLLED') ?>" class="card" style="padding:1.15rem 1.25rem; display:flex; align-items:center; gap:1rem; text-decoration:none; border-radius:12px;">
      <div style="width:48px; height:48px; border-radius:12px; background:#dcfce7; color:#15803d; display:flex; align-items:center; justify-content:center; font-size:1.3rem;">
        <i class="fa-solid fa-award"></i>
      </div>
      <div>
        <div style="font-size:1.55rem; font-weight:800; color:#15803d; line-height:1.1;"><?= number_format($totalEnrolled) ?></div>
        <div style="font-size:0.8rem; color:var(--text-muted); font-weight:600;">Branch Enrolled</div>
      </div>
    </a>

  <?php else: ?>
    <!-- Super Admin KPI 1: Total Active Leads -->
    <div class="card" style="padding:1.15rem 1.25rem; display:flex; align-items:center; gap:1rem; border-radius:12px;">
      <div style="width:48px; height:48px; border-radius:12px; background:var(--primary-subtle); color:var(--primary); display:flex; align-items:center; justify-content:center; font-size:1.3rem;">
        <i class="fa-solid fa-user-graduate"></i>
      </div>
      <div>
        <div style="font-size:1.55rem; font-weight:800; color:var(--text-heading); line-height:1.1;"><?= number_format($totalLeads) ?></div>
        <div style="font-size:0.8rem; color:var(--text-muted); font-weight:600;">Total Active Leads</div>
      </div>
    </div>

    <!-- Super Admin KPI 2: Enrolled Clients -->
    <div class="card" style="padding:1.15rem 1.25rem; display:flex; align-items:center; gap:1rem; border-radius:12px;">
      <div style="width:48px; height:48px; border-radius:12px; background:#dcfce7; color:#15803d; display:flex; align-items:center; justify-content:center; font-size:1.3rem;">
        <i class="fa-solid fa-certificate"></i>
      </div>
      <div>
        <div style="font-size:1.55rem; font-weight:800; color:#15803d; line-height:1.1;"><?= number_format($totalCustomers) ?></div>
        <div style="font-size:0.8rem; color:var(--text-muted); font-weight:600;">Enrolled (<?= $conversionRate ?>%)</div>
      </div>
    </div>

    <!-- Super Admin KPI 3: Collected Revenue -->
    <div class="card" style="padding:1.15rem 1.25rem; display:flex; align-items:center; gap:1rem; border-radius:12px;">
      <div style="width:48px; height:48px; border-radius:12px; background:#ecfdf5; color:#059669; display:flex; align-items:center; justify-content:center; font-size:1.3rem;">
        <i class="fa-solid fa-wallet"></i>
      </div>
      <div>
        <div style="font-size:1.4rem; font-weight:800; color:#059669; line-height:1.1;"><?= format_currency($totalCollected) ?></div>
        <div style="font-size:0.8rem; color:var(--text-muted); font-weight:600;">Collected Revenue</div>
      </div>
    </div>

    <!-- Super Admin KPI 4: Outstanding Balance -->
    <div class="card" style="padding:1.15rem 1.25rem; display:flex; align-items:center; gap:1rem; border-radius:12px;">
      <div style="width:48px; height:48px; border-radius:12px; background:#fee2e2; color:#b91c1c; display:flex; align-items:center; justify-content:center; font-size:1.3rem;">
        <i class="fa-solid fa-scale-unbalanced"></i>
      </div>
      <div>
        <div style="font-size:1.4rem; font-weight:800; color:#b91c1c; line-height:1.1;"><?= format_currency($totalBalance) ?></div>
        <div style="font-size:0.8rem; color:var(--text-muted); font-weight:600;">Pending Balance</div>
      </div>
    </div>
  <?php endif; ?>
</div>

<!-- 3. COUNSELOR'S TODAY ACTION CALL QUEUE (Prominent, High Priority) -->
<?php if ($isCounselor): ?>
  <div class="card" id="widget-action-calls" style="margin-bottom:1.5rem; padding:1.25rem; border-left:4px solid #10b981; border-radius:12px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; flex-wrap:wrap; gap:0.5rem;">
      <div>
        <h3 style="font-size:1.05rem; font-weight:800; color:var(--text-heading); margin:0; display:flex; align-items:center; gap:8px;">
          <i class="fa-solid fa-phone-volume" style="color:#10b981;"></i> Today's Action Call List
          <?php if (!empty($urgentCalls)): ?>
            <span class="badge badge-success"><?= count($urgentCalls) ?> Ready</span>
          <?php endif; ?>
        </h3>
        <p style="font-size:0.8rem; color:var(--text-muted); margin:2px 0 0 0;">Candidates scheduled for consultation today. 1-click WhatsApp or Call to connect.</p>
      </div>
      <a href="<?= base_url('tasks/index.php') ?>" class="btn btn-outline btn-sm">Full Call Queue &rarr;</a>
    </div>

    <?php if (empty($urgentCalls)): ?>
      <div style="text-align:center; padding:2rem 1rem; background:#f0fdf4; border-radius:10px; border:1px solid #bbf7d0;">
        <div style="width:48px; height:48px; border-radius:50%; background:#dcfce7; color:#15803d; display:inline-flex; align-items:center; justify-content:center; font-size:1.4rem; margin-bottom:0.6rem;">
          <i class="fa-solid fa-circle-check"></i>
        </div>
        <h4 style="font-size:1.1rem; font-weight:800; color:#166534; margin:0 0 0.35rem 0;">All caught up for today! 🎉</h4>
        <p style="font-size:0.85rem; color:#15803d; margin:0 0 1rem 0;">You have zero pending follow-ups due right now. Ready for fresh inquiries or advancing student applications.</p>
        <div style="display:flex; justify-content:center; gap:0.5rem;">
          <a href="<?= base_url('leads/index.php') ?>" class="btn btn-secondary btn-sm"><i class="fa-solid fa-list"></i> View My Students</a>
          <button type="button" class="btn btn-primary btn-sm" onclick="openQuickLeadModal()"><i class="fa-solid fa-plus"></i> + Add New Lead</button>
        </div>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Candidate</th>
              <th>1-Click Contact</th>
              <th>Target Country</th>
              <th>Current Stage</th>
              <th>Last Discussion</th>
              <th style="text-align:right;">Immediate Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($urgentCalls as $call): 
              $dialUrl = "tel:" . preg_replace('/[^0-9+]/', '', $call['phone']);
              $waUrl = "https://wa.me/91" . normalize_phone($call['phone']) . "?text=" . urlencode("Hello " . $call['full_name'] . ", this is " . ($currentUser['name'] ?? 'Edroots') . " regarding your higher education admission enquiry.");
            ?>
              <tr>
                <td>
                  <a href="<?= base_url('leads/view.php?id=' . $call['lead_id']) ?>" style="font-weight:700; color:var(--text-heading); text-decoration:none; font-size:0.95rem;">
                    <?= htmlspecialchars($call['full_name']) ?>
                  </a>
                  <div style="font-family:monospace; font-size:0.75rem; color:var(--primary); font-weight:700;"><?= htmlspecialchars($call['lead_code']) ?></div>
                </td>
                <td>
                  <div style="display:flex; align-items:center; gap:8px;">
                    <a href="<?= $waUrl ?>" target="_blank" class="btn btn-sm" style="background:#25d366; color:#fff; font-weight:700; padding:4px 10px; font-size:0.8rem; border-radius:6px; display:inline-flex; align-items:center; gap:5px;" title="Send WhatsApp Message">
                      <i class="fa-brands fa-whatsapp"></i> WhatsApp
                    </a>
                    <a href="<?= $dialUrl ?>" class="btn btn-outline btn-sm" style="padding:4px 9px; font-size:0.8rem; border-radius:6px;" title="Call Phone">
                      <i class="fa-solid fa-phone" style="color:var(--primary);"></i>
                    </a>
                  </div>
                </td>
                <td><span style="font-weight:700; font-size:0.85rem; color:var(--text-heading);"><?= htmlspecialchars($call['preferred_country']) ?></span></td>
                <td><span class="badge badge-primary" style="font-size:0.72rem; font-weight:700;"><?= htmlspecialchars($call['stage']) ?></span></td>
                <td style="font-size:0.8rem; color:var(--text-muted); max-width:240px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                  <?= htmlspecialchars($call['last_notes'] ?: 'Scheduled consultation') ?>
                </td>
                <td style="text-align:right;">
                  <button type="button" class="btn btn-primary btn-sm" style="font-size:0.8rem; padding:4px 12px; font-weight:700; border-radius:6px;" onclick="openLogCallModal(<?= $call['lead_id'] ?>, '<?= htmlspecialchars(addslashes($call['full_name'])) ?>', '<?= htmlspecialchars($call['stage']) ?>')">
                    <i class="fa-solid fa-check"></i> Log Outcome
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
<?php endif; ?>

<!-- 4. MANAGER & ADMIN: UNASSIGNED WEB LEADS TRIAGE -->
<?php if (!empty($unassignedWebLeads) && !$isCounselor): ?>
  <div class="card" id="widget-unassigned-leads" style="margin-bottom:1.5rem; padding:1.25rem; border-left:4px solid #0284c7; border-radius:12px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; flex-wrap:wrap; gap:0.5rem;">
      <div>
        <h3 style="font-size:1.05rem; font-weight:800; color:var(--text-heading); margin:0; display:flex; align-items:center; gap:8px;">
          <i class="fa-solid fa-bolt" style="color:#0284c7;"></i> Fresh Digital Leads Awaiting Allocation
          <span class="badge badge-blue"><?= count($unassignedWebLeads) ?> Pending</span>
        </h3>
        <p style="font-size:0.8rem; color:var(--text-muted); margin:2px 0 0 0;">Inbound submissions from Meta / Google ads awaiting counselor assignment.</p>
      </div>
      <a href="<?= base_url('data/web_leads.php') ?>" class="btn btn-outline btn-sm">Full Triage Board &rarr;</a>
    </div>

    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>Candidate</th>
            <th>Phone</th>
            <th>Study Interest</th>
            <th>Channel</th>
            <th>Received</th>
            <th style="text-align:right;">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($unassignedWebLeads as $wl): ?>
            <tr>
              <td><strong><?= htmlspecialchars($wl['full_name']) ?></strong></td>
              <td style="font-family:monospace; font-weight:700;"><?= htmlspecialchars($wl['phone']) ?></td>
              <td><?= htmlspecialchars($wl['preferred_country']) ?> &bull; <?= htmlspecialchars($wl['preferred_course']) ?></td>
              <td><span class="badge badge-secondary"><?= htmlspecialchars($wl['source']) ?></span></td>
              <td style="font-size:0.8rem; color:var(--text-muted);"><?= time_ago($wl['created_at']) ?></td>
              <td style="text-align:right;">
                <a href="<?= base_url('data/web_leads.php') ?>" class="btn btn-primary btn-sm" style="font-size:0.75rem; padding:3px 10px; font-weight:700;">
                  Allocate &rarr;
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<!-- 5. BRANCH MANAGER: COUNSELOR CASELOAD & PERFORMANCE BALANCE -->
<?php if ($isManager && !empty($branchCounselors)): ?>
  <div class="card" id="widget-counselor-caseload" style="margin-bottom:1.5rem; padding:1.25rem; border-radius:12px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; flex-wrap:wrap; gap:0.5rem;">
      <div>
        <h3 style="font-size:1.05rem; font-weight:800; color:var(--text-heading); margin:0; display:flex; align-items:center; gap:8px;">
          <i class="fa-solid fa-users-viewfinder" style="color:var(--primary);"></i> Counselor Caseload & Performance Balance
        </h3>
        <p style="font-size:0.8rem; color:var(--text-muted); margin:2px 0 0 0;">Live tracking of counselor workload, daily follow-up discipline, and student enrollments.</p>
      </div>
      <a href="<?= base_url('organization/users.php') ?>" class="btn btn-outline btn-sm">Manage Team &rarr;</a>
    </div>

    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>Counselor</th>
            <th>Active Caseload</th>
            <th>Calls Due Today</th>
            <th>Follow-up Discipline</th>
            <th>Enrolled Students</th>
            <th style="text-align:right;">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($branchCounselors as $bc): ?>
            <tr>
              <td>
                <div style="font-weight:700; color:var(--text-heading); font-size:0.92rem;">
                  <?= htmlspecialchars($bc['name']) ?>
                </div>
                <div style="font-size:0.75rem; color:var(--text-muted);"><?= htmlspecialchars($bc['email']) ?></div>
              </td>
              <td>
                <span class="badge badge-primary" style="font-size:0.85rem; font-weight:800; padding:4px 10px;"><?= $bc['active_leads'] ?> Students</span>
              </td>
              <td>
                <span style="font-weight:700; color:var(--text-heading);"><?= $bc['today_calls'] ?> Due</span>
              </td>
              <td>
                <?php if ($bc['overdue_calls'] > 0): ?>
                  <span class="badge badge-red" style="font-weight:700;"><?= $bc['overdue_calls'] ?> Overdue</span>
                <?php else: ?>
                  <span class="badge badge-green" style="font-weight:600;"><i class="fa-solid fa-check"></i> On Track</span>
                <?php endif; ?>
              </td>
              <td>
                <strong style="color:#15803d; font-size:0.92rem;"><?= $bc['enrolled_count'] ?> Enrolled</strong>
              </td>
              <td style="text-align:right;">
                <a href="<?= base_url('leads/index.php?counselor_id=' . $bc['id']) ?>" class="btn btn-secondary btn-sm" style="font-size:0.75rem; padding:3px 10px; font-weight:600;">
                  View Leads &rarr;
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<!-- 6. SUPER ADMIN: CAMPUS BRANCHES PERFORMANCE -->
<?php if ($isSuperAdmin && !empty($branchStats)): ?>
  <div class="card" style="margin-bottom:1.5rem; padding:1.25rem; border-radius:12px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; flex-wrap:wrap; gap:0.5rem;">
      <div>
        <h3 style="font-size:1.05rem; font-weight:800; color:var(--text-heading); margin:0; display:flex; align-items:center; gap:8px;">
          <i class="fa-solid fa-building" style="color:var(--primary);"></i> Campus Branches Performance Overview
        </h3>
        <p style="font-size:0.8rem; color:var(--text-muted); margin:2px 0 0 0;">Institutional intake volumes, enrolled conversions, and revenue yields by campus hub.</p>
      </div>
      <a href="<?= base_url('organization/branches.php') ?>" class="btn btn-outline btn-sm">All Branches &rarr;</a>
    </div>

    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>Campus Branch</th>
            <th>Location</th>
            <th>Active Inquiries</th>
            <th>Enrolled Students</th>
            <th>Collected Revenue</th>
            <th style="text-align:right;">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($branchStats as $bs): ?>
            <tr>
              <td>
                <strong><?= htmlspecialchars($bs['branch_name']) ?></strong>
              </td>
              <td style="font-size:0.85rem; color:var(--text-muted);"><?= htmlspecialchars($bs['city']) ?></td>
              <td><span class="badge badge-primary" style="font-weight:700;"><?= number_format($bs['lead_count']) ?></span></td>
              <td><span class="badge badge-success" style="font-weight:700;"><?= number_format($bs['enrolled_count']) ?></span></td>
              <td><strong style="color:#059669;"><?= format_currency($bs['collected_amount']) ?></strong></td>
              <td style="text-align:right;">
                <a href="<?= base_url('leads/index.php?branch_id=' . $bs['id']) ?>" class="btn btn-secondary btn-sm" style="font-size:0.75rem; padding:3px 10px;">
                  Leads &rarr;
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<!-- 7. THE 4 OPERATIONAL TASK QUEUES (Keep #widget-task-queues for Tutorial) -->
<div class="card" id="widget-task-queues" style="margin-bottom:1.5rem; padding:1.25rem; border-radius:12px;">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; flex-wrap:wrap; gap:0.5rem;">
    <div style="display:flex; align-items:center; gap:0.5rem;">
      <h3 style="font-size:1.05rem; font-weight:800; color:var(--text-heading); margin:0;">
        <i class="fa-solid fa-list-check" style="color:var(--primary); margin-right:4px;"></i> Daily Operational Task Queues
      </h3>
      <span class="badge badge-primary" style="font-weight:700;"><?= $totalTasks ?> Actions Pending</span>
    </div>
    <a href="<?= base_url('tasks/index.php') ?>" style="font-size:0.825rem; color:var(--primary); font-weight:700; text-decoration:none;">
      Open Task Surface &rarr;
    </a>
  </div>

  <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:0.85rem;">
    <a href="<?= base_url('tasks/index.php?queue=scheduled') ?>" style="text-decoration:none; background:var(--primary-subtle); border-radius:10px; padding:1rem; display:flex; justify-content:space-between; align-items:center; transition:transform 0.15s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
      <div>
        <span style="font-size:0.75rem; font-weight:700; color:var(--primary); text-transform:uppercase;">Scheduled Follow-ups</span>
        <div style="font-size:1.35rem; font-weight:800; color:var(--text-heading); margin-top:2px;">Today's Calls</div>
      </div>
      <span class="badge badge-blue" style="font-size:1rem; font-weight:800;"><?= $countScheduled ?></span>
    </a>

    <a href="<?= base_url('tasks/index.php?queue=pending_followup') ?>" style="text-decoration:none; background:#fee2e2; border-radius:10px; padding:1rem; display:flex; justify-content:space-between; align-items:center; transition:transform 0.15s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
      <div>
        <span style="font-size:0.75rem; font-weight:700; color:#b91c1c; text-transform:uppercase;">Pending Follow-ups</span>
        <div style="font-size:1.35rem; font-weight:800; color:#b91c1c; margin-top:2px;">Overdue Calls</div>
      </div>
      <span class="badge badge-red" style="font-size:1rem; font-weight:800;"><?= $countOverdue ?></span>
    </a>

    <a href="<?= base_url('tasks/index.php?queue=today_leads') ?>" style="text-decoration:none; background:#dcfce7; border-radius:10px; padding:1rem; display:flex; justify-content:space-between; align-items:center; transition:transform 0.15s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
      <div>
        <span style="font-size:0.75rem; font-weight:700; color:#15803d; text-transform:uppercase;">Today Leads</span>
        <div style="font-size:1.35rem; font-weight:800; color:#15803d; margin-top:2px;">Fresh Inbound</div>
      </div>
      <span class="badge badge-green" style="font-size:1rem; font-weight:800;"><?= $countTodayLeads ?></span>
    </a>

    <a href="<?= base_url('tasks/index.php?queue=pending_leads') ?>" style="text-decoration:none; background:#fef3c7; border-radius:10px; padding:1rem; display:flex; justify-content:space-between; align-items:center; transition:transform 0.15s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
      <div>
        <span style="font-size:0.75rem; font-weight:700; color:#b45309; text-transform:uppercase;">Pending Leads</span>
        <div style="font-size:1.35rem; font-weight:800; color:#b45309; margin-top:2px;">Uncontacted</div>
      </div>
      <span class="badge badge-warning" style="font-size:1rem; font-weight:800;"><?= $countPendingLeads ?></span>
    </a>
  </div>
</div>

<!-- 8. PIPELINE VISUAL FUNNEL & QUICK GUIDANCE (Keep #widget-pipeline-chart and #widget-branch-chart) -->
<div style="display:grid; grid-template-columns:<?= $isCounselor ? '1fr 1fr' : '1fr 1fr' ?>; gap:1.5rem; margin-bottom:1.5rem; align-items:start;">
  <!-- 6-Stage Pipeline Chart -->
  <div class="card" id="widget-pipeline-chart" style="padding:1.5rem; border-radius:12px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
      <h3 style="font-size:1.05rem; font-weight:700; color:var(--text-heading); margin:0;">
        <i class="fa-solid fa-diagram-project" style="color:var(--primary); margin-right:4px;"></i> 
        <?= $isCounselor ? 'My 6-Stage Student Funnel' : '6-Stage Admission Pipeline' ?>
      </h3>
      <a href="<?= base_url('admissions/index.php') ?>" style="font-size:0.78rem; color:var(--primary); font-weight:600; text-decoration:none;">Kanban Board &rarr;</a>
    </div>
    <div style="height:250px;">
      <canvas id="pipelineChart"></canvas>
    </div>
  </div>

  <?php if ($isCounselor): ?>
    <!-- Counselor Guidance & Daily Best Practices Card -->
    <div class="card" id="widget-branch-chart" style="padding:1.5rem; border-radius:12px; background:linear-gradient(135deg, #ffffff 0%, #fafafa 100%);">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
        <h3 style="font-size:1.05rem; font-weight:700; color:var(--text-heading); margin:0;">
          <i class="fa-solid fa-lightbulb" style="color:#f59e0b; margin-right:4px;"></i> Counselor Daily Tips
        </h3>
        <span class="badge badge-primary" style="font-size:0.72rem;">Pro Guidance</span>
      </div>
      <div style="display:flex; flex-direction:column; gap:0.75rem; font-size:0.825rem;">
        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:0.75rem 1rem;">
          <strong style="color:var(--text-heading); display:flex; align-items:center; gap:6px;">
            <i class="fa-brands fa-whatsapp" style="color:#25d366;"></i> 1-Click WhatsApp Greetings
          </strong>
          <p style="margin:4px 0 0 0; color:var(--text-muted); font-size:0.78rem;">Click WhatsApp directly from the list to send prefilled admission greetings without having to save numbers on your phone.</p>
        </div>
        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:0.75rem 1rem;">
          <strong style="color:var(--text-heading); display:flex; align-items:center; gap:6px;">
            <i class="fa-solid fa-phone-volume" style="color:#10b981;"></i> Always Log Call Outcomes
          </strong>
          <p style="margin:4px 0 0 0; color:var(--text-muted); font-size:0.78rem;">After each conversation, click "Log Outcome" to record discussion notes and auto-schedule your next follow-up date.</p>
        </div>
        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:0.75rem 1rem;">
          <strong style="color:var(--text-heading); display:flex; align-items:center; gap:6px;">
            <i class="fa-solid fa-file-arrow-up" style="color:var(--primary);"></i> Student Dossiers
          </strong>
          <p style="margin:4px 0 0 0; color:var(--text-muted); font-size:0.78rem;">Keep passport copies and marksheets updated in the Document Hub so applications are ready for university submission.</p>
        </div>
      </div>
    </div>
  <?php else: ?>
    <!-- Branch / Campus Chart for Super Admin & Branch Managers -->
    <div class="card" id="widget-branch-chart" style="padding:1.5rem; border-radius:12px;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
        <h3 style="font-size:1.05rem; font-weight:700; color:var(--text-heading); margin:0;">
          <i class="fa-solid fa-chart-pie" style="color:var(--primary); margin-right:4px;"></i> Regional Branch Intake Distribution
        </h3>
        <a href="<?= base_url('reports/index.php?report=branches') ?>" style="font-size:0.78rem; color:var(--primary); font-weight:600; text-decoration:none;">All Hubs &rarr;</a>
      </div>
      <div style="height:250px;">
        <canvas id="branchChart"></canvas>
      </div>
    </div>
  <?php endif; ?>
</div>

<!-- 9. RECENT LEADS TABLE (Keep #widget-recent-leads for Tutorial) -->
<div style="display:grid; grid-template-columns:<?= $isSuperAdmin ? '2fr 1fr' : '1fr' ?>; gap:1.5rem; align-items:start;">
  <!-- Recent Leads Table -->
  <div class="card" id="widget-recent-leads" style="padding:1.5rem; border-radius:12px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
      <h3 style="font-size:1.05rem; font-weight:700; color:var(--text-heading); margin:0;">
        <i class="fa-solid fa-user-graduate" style="color:var(--primary); margin-right:4px;"></i> 
        <?= $isCounselor ? 'My Recent Student Inquiries' : 'Recent Student Inquiries' ?>
      </h3>
      <a href="<?= base_url('leads/index.php') ?>" style="font-size:0.8rem; color:var(--primary); font-weight:600; text-decoration:none;">View All Leads &rarr;</a>
    </div>

    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>Student Name</th>
            <th>Target Country</th>
            <th>Stage</th>
            <?php if (!$isCounselor): ?>
              <th>Branch</th>
              <th>Counselor</th>
            <?php endif; ?>
            <th>1-Click WhatsApp & Call</th>
            <th style="text-align:right;">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($recentLeads)): ?>
            <tr>
              <td colspan="<?= $isCounselor ? 5 : 6 ?>" style="text-align:center; padding:2.5rem; color:var(--text-muted);">
                <i class="fa-solid fa-user-graduate" style="font-size:1.6rem; margin-bottom:0.5rem; display:block; color:var(--text-light);"></i>
                No active student inquiries recorded yet. New leads will automatically display here.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($recentLeads as $rl): 
              $dialUrl = "tel:" . preg_replace('/[^0-9+]/', '', $rl['phone']);
              $waUrl = "https://wa.me/91" . normalize_phone($rl['phone']) . "?text=" . urlencode("Hello " . $rl['full_name'] . ", this is " . ($currentUser['name'] ?? 'Edroots') . " regarding your higher education admission enquiry.");
            ?>
              <tr>
                <td>
                  <a href="<?= base_url('leads/view.php?id=' . $rl['id']) ?>" style="font-weight:700; color:var(--text-heading); font-size:0.92rem; text-decoration:none;">
                    <?= htmlspecialchars($rl['full_name']) ?>
                  </a>
                  <div style="font-family:monospace; font-size:0.75rem; color:var(--primary); font-weight:700;"><?= htmlspecialchars($rl['lead_code']) ?></div>
                </td>
                <td style="font-size:0.85rem; font-weight:600;"><?= htmlspecialchars($rl['preferred_country']) ?></td>
                <td><span class="badge badge-primary" style="font-size:0.7rem; font-weight:700;"><?= htmlspecialchars($rl['stage']) ?></span></td>
                <?php if (!$isCounselor): ?>
                  <td style="font-size:0.8rem; color:var(--text-muted);"><?= htmlspecialchars($rl['branch_name'] ?? 'Branch') ?></td>
                  <td style="font-size:0.8rem; font-weight:600;"><?= htmlspecialchars($rl['counselor_name'] ?? 'Counselor') ?></td>
                <?php endif; ?>
                <td>
                  <div style="display:flex; align-items:center; gap:6px;">
                    <a href="<?= $waUrl ?>" target="_blank" class="btn btn-sm" style="background:#25d366; color:#fff; font-size:0.78rem; padding:3px 8px; border-radius:6px; font-weight:700;" title="WhatsApp">
                      <i class="fa-brands fa-whatsapp"></i> Chat
                    </a>
                    <a href="<?= $dialUrl ?>" class="btn btn-outline btn-sm" style="padding:3px 7px; font-size:0.78rem; border-radius:6px;" title="Call">
                      <i class="fa-solid fa-phone" style="color:var(--primary);"></i>
                    </a>
                  </div>
                </td>
                <td style="text-align:right;">
                  <a href="<?= base_url('leads/view.php?id=' . $rl['id']) ?>" class="btn btn-secondary btn-sm" style="font-size:0.75rem; padding:3px 10px; font-weight:600;">Dossier &rarr;</a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php if ($isSuperAdmin): ?>
    <!-- Recent System Activity Trail (Admin Only) -->
    <div class="card" style="padding:1.5rem; border-radius:12px;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
        <h3 style="font-size:1.05rem; font-weight:700; color:var(--text-heading); margin:0;">
          <i class="fa-solid fa-clock-rotate-left" style="color:var(--primary); margin-right:4px;"></i> Audit Feed
        </h3>
        <a href="<?= base_url('system/activity_logs.php') ?>" style="font-size:0.78rem; color:var(--primary); font-weight:600; text-decoration:none;">Logs &rarr;</a>
      </div>

      <div style="display:flex; flex-direction:column; gap:0.85rem; font-size:0.825rem;">
        <?php if (empty($recentActivities)): ?>
          <div style="text-align:center; padding:2.5rem 1rem; color:var(--text-muted);">
            <i class="fa-solid fa-clock-rotate-left" style="font-size:1.6rem; margin-bottom:0.5rem; display:block; color:var(--text-light);"></i>
            No audit activity logged yet.
          </div>
        <?php else: ?>
          <?php foreach ($recentActivities as $act): ?>
            <div style="background:var(--bg-subtle); padding:0.65rem 0.85rem; border-radius:6px; line-height:1.4;">
              <div style="display:flex; justify-content:space-between; margin-bottom:2px;">
                <strong style="color:var(--text-heading); font-size:0.8rem;"><?= htmlspecialchars($act['action']) ?></strong>
                <span style="font-size:0.7rem; color:var(--text-light);"><?= time_ago($act['created_at']) ?></span>
              </div>
              <div style="color:var(--text-muted); font-size:0.78rem;"><?= htmlspecialchars($act['details']) ?></div>
              <div style="font-size:0.7rem; color:var(--primary); font-weight:600; margin-top:2px;">By <?= htmlspecialchars($act['user_name'] ?? 'Staff') ?></div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Pipeline Chart
  var pipeEl = document.getElementById('pipelineChart');
  if (pipeEl) {
    new Chart(pipeEl, {
      type: 'bar',
      data: {
        labels: <?= json_encode($stageChartLabels) ?>,
        datasets: [{
          label: 'Students in Stage',
          data: <?= json_encode($stageChartData) ?>,
          backgroundColor: ['#64748b', '#0284c7', '#5955D1', '#f59e0b', '#8b5cf6', '#10b981'],
          borderRadius: 6
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
      }
    });
  }

  // Branch Chart (only rendered if canvas exists)
  var branchEl = document.getElementById('branchChart');
  if (branchEl) {
    new Chart(branchEl, {
      type: 'doughnut',
      data: {
        labels: <?= json_encode($branchChartLabels) ?>,
        datasets: [{
          data: <?= json_encode($branchChartLeads) ?>,
          backgroundColor: ['#5955D1', '#3b82f6', '#10b981', '#f59e0b', '#ec4899', '#6366f1']
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'right' } }
      }
    });
  }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
