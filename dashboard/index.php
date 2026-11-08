<?php
/**
 * Main Institutional Dashboard - Multi-Branch Hierarchy
 * University-Level Education Consultancy CRM (EdrootsCRM)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_permission('dashboard', 'view');

$currentUser = current_user();
$pageTitle = 'Admissions Cockpit';
$activePage = 'dashboard';

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

// 4. Branch Yields (for Super Admin & Branch Managers)
$branchStats = $pdo->query("
    SELECT b.name as branch_name, b.city,
           COUNT(l.id) as lead_count,
           COUNT(CASE WHEN l.is_customer = 1 THEN 1 END) as customer_count,
           COALESCE(SUM(l.paid_total), 0) as collected_amount
    FROM branches b
    LEFT JOIN leads l ON b.id = l.branch_id AND l.is_archived = 0
    GROUP BY b.id
    ORDER BY lead_count DESC
    LIMIT 6
")->fetchAll();

$branchChartLabels = array_column($branchStats, 'branch_name');
$branchChartLeads = array_column($branchStats, 'lead_count');
$branchChartRevenue = array_column($branchStats, 'collected_amount');

// 5. Recent Active Leads / Tasks
$recentLeads = $pdo->query("
    SELECT l.*, b.name as branch_name, u.name as counselor_name
    FROM leads l
    LEFT JOIN branches b ON l.branch_id = b.id
    LEFT JOIN users u ON l.assigned_counselor_id = u.id
    WHERE l.is_archived = 0 AND {$scopeLeads}
    ORDER BY l.id DESC
    LIMIT 6
")->fetchAll();

// 6. Recent Activity Logs
$recentActivities = $pdo->query("
    SELECT a.*, u.name as user_name
    FROM activity_logs a
    LEFT JOIN users u ON a.user_id = u.id
    ORDER BY a.id DESC
    LIMIT 5
")->fetchAll();

// 7. Urgent Follow-up Calls Due Today (Action items)
$urgentCalls = $pdo->query("
    SELECT f.id as followup_id, f.lead_id, f.scheduled_date, f.scheduled_time, f.notes as last_notes,
           l.full_name, l.phone, l.preferred_country, l.stage, l.lead_code
    FROM followups f
    JOIN leads l ON f.lead_id = l.id
    WHERE f.status = 'Pending' AND f.scheduled_date <= CURDATE() AND l.is_archived = 0 AND {$scopeLeads}
    ORDER BY f.scheduled_date ASC, f.id DESC
    LIMIT 5
")->fetchAll();

// 8. Inbound Leads awaiting allocation (for Manager / Admin)
$unassignedWebLeads = [];
if (!is_counselor()) {
    $unassignedWebLeads = $pdo->query("
        SELECT w.* FROM web_leads w WHERE w.status = 'Pending' AND w.assigned_counselor_id IS NULL ORDER BY w.id DESC LIMIT 4
    ")->fetchAll();
}

include __DIR__ . '/../includes/header.php';
?>

<!-- Welcome Greeting & Quick Actions Header -->
<div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.25rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <h1 style="font-size:1.6rem; font-weight:800; color:var(--text-heading); margin:0 0 0.35rem 0;">
      Welcome back, <?= htmlspecialchars($currentUser['name']) ?>!
    </h1>
    <p style="font-size:0.875rem; color:var(--text-muted); margin:0;">
      Logged in as <strong><?= htmlspecialchars($currentUser['role_label']) ?></strong> &bull; Operational Cockpit
    </p>
  </div>
  <div style="display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap;">
    <button type="button" class="btn btn-primary btn-sm" id="btn-quick-add-lead" onclick="openQuickLeadModal()" style="font-weight:700; box-shadow:0 2px 6px rgba(112,8,231,0.25);">
      <i class="fa-solid fa-plus"></i> Quick Add Lead
    </button>
    <button type="button" class="btn btn-outline btn-sm" id="btn-dashboard-guide" onclick="startInteractiveTutorial()">
      <i class="fa-solid fa-wand-magic-sparkles" style="color:var(--primary);"></i> <?= htmlspecialchars($currentUser['role_label']) ?> Guide
    </button>
    <?php if (can('invoices', 'add')): ?>
      <a href="<?= base_url('finance/create_invoice.php') ?>" class="btn btn-secondary btn-sm" id="btn-generate-invoice">
        <i class="fa-solid fa-file-invoice-dollar"></i> Generate Invoice
      </a>
    <?php endif; ?>
  </div>
</div>

<!-- 4-Step Intuitive Process Navigator -->
<div class="card" id="widget-4step-workflow" style="margin-bottom:1.5rem; padding:1.25rem; background:linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); border:1px solid #e2e8f0;">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.85rem;">
    <h3 style="font-size:0.95rem; font-weight:800; color:var(--text-heading); margin:0; text-transform:uppercase; letter-spacing:0.04em;">
      <i class="fa-solid fa-route" style="color:var(--primary); margin-right:6px;"></i> Standard 4-Step Daily Admissions Workflow
    </h3>
    <span style="font-size:0.75rem; color:var(--text-muted);">Click any step to execute</span>
  </div>
  <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:0.85rem;">
    <div onclick="openQuickLeadModal()" style="cursor:pointer; background:#fff; border:1px solid var(--border-color); border-radius:10px; padding:0.9rem; transition:transform 0.15s, border-color 0.15s; box-shadow:0 1px 3px rgba(0,0,0,0.03);" onmouseover="this.style.borderColor='var(--primary)'; this.style.transform='translateY(-2px)';" onmouseout="this.style.borderColor='var(--border-color)'; this.style.transform='none';">
      <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
        <span style="width:24px; height:24px; border-radius:50%; background:var(--primary-subtle); color:var(--primary); display:flex; align-items:center; justify-content:center; font-weight:800; font-size:0.75rem;">1</span>
        <strong style="font-size:0.85rem; color:var(--text-heading);">Capture Inquiry</strong>
      </div>
      <p style="margin:0; font-size:0.75rem; color:var(--text-muted);">Quick register walk-in or phone call candidate</p>
    </div>

    <a href="<?= base_url('tasks/index.php?queue=scheduled') ?>" style="text-decoration:none; background:#fff; border:1px solid var(--border-color); border-radius:10px; padding:0.9rem; transition:transform 0.15s, border-color 0.15s; box-shadow:0 1px 3px rgba(0,0,0,0.03);" onmouseover="this.style.borderColor='#10b981'; this.style.transform='translateY(-2px)';" onmouseout="this.style.borderColor='var(--border-color)'; this.style.transform='none';">
      <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
        <span style="width:24px; height:24px; border-radius:50%; background:#dcfce7; color:#15803d; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:0.75rem;">2</span>
        <strong style="font-size:0.85rem; color:var(--text-heading);">Call & WhatsApp</strong>
      </div>
      <p style="margin:0; font-size:0.75rem; color:var(--text-muted);">Consult today's scheduled follow-up queue (<?= $countScheduled ?> due)</p>
    </a>

    <a href="<?= base_url('admissions/index.php') ?>" style="text-decoration:none; background:#fff; border:1px solid var(--border-color); border-radius:10px; padding:0.9rem; transition:transform 0.15s, border-color 0.15s; box-shadow:0 1px 3px rgba(0,0,0,0.03);" onmouseover="this.style.borderColor='#0284c7'; this.style.transform='translateY(-2px)';" onmouseout="this.style.borderColor='var(--border-color)'; this.style.transform='none';">
      <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
        <span style="width:24px; height:24px; border-radius:50%; background:#e0f2fe; color:#0369a1; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:0.75rem;">3</span>
        <strong style="font-size:0.85rem; color:var(--text-heading);">Advance Milestones</strong>
      </div>
      <p style="margin:0; font-size:0.75rem; color:var(--text-muted);">Move students from Enquiry to Enrolled on 6 stages</p>
    </a>

    <a href="<?= base_url('finance/invoices.php') ?>" style="text-decoration:none; background:#fff; border:1px solid var(--border-color); border-radius:10px; padding:0.9rem; transition:transform 0.15s, border-color 0.15s; box-shadow:0 1px 3px rgba(0,0,0,0.03);" onmouseover="this.style.borderColor='#f59e0b'; this.style.transform='translateY(-2px)';" onmouseout="this.style.borderColor='var(--border-color)'; this.style.transform='none';">
      <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
        <span style="width:24px; height:24px; border-radius:50%; background:#fef3c7; color:#b45309; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:0.75rem;">4</span>
        <strong style="font-size:0.85rem; color:var(--text-heading);">Invoicing & Fees</strong>
      </div>
      <p style="margin:0; font-size:0.75rem; color:var(--text-muted);">Issue consultancy billings & track fee receipts</p>
    </a>
  </div>
</div>

<?php if (!empty($urgentCalls)): ?>
  <!-- Action Required: Today's High-Priority Calls Widget -->
  <div class="card" id="widget-action-calls" style="margin-bottom:1.5rem; padding:1.25rem; border-left:4px solid #10b981;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; flex-wrap:wrap; gap:0.5rem;">
      <div>
        <h3 style="font-size:1.05rem; font-weight:800; color:var(--text-heading); margin:0; display:flex; align-items:center; gap:8px;">
          <i class="fa-solid fa-phone-volume" style="color:#10b981;"></i> Today's Action Call List
          <span class="badge badge-success"><?= count($urgentCalls) ?> Ready</span>
        </h3>
        <p style="font-size:0.78rem; color:var(--text-muted); margin:2px 0 0 0;">Candidates scheduled for consultation today. 1-click WhatsApp or Call to execute.</p>
      </div>
      <a href="<?= base_url('tasks/index.php') ?>" class="btn btn-outline btn-sm">View Full Queue &rarr;</a>
    </div>

    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>Candidate</th>
            <th>Contact & WhatsApp</th>
            <th>Target Country</th>
            <th>Current Stage</th>
            <th>Last Discussion</th>
            <th style="text-align:right;">Instant Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($urgentCalls as $call): 
            $dialUrl = "tel:" . preg_replace('/[^0-9+]/', '', $call['phone']);
            $waUrl = "https://wa.me/91" . normalize_phone($call['phone']) . "?text=" . urlencode("Hello " . $call['full_name'] . ", this is " . ($currentUser['name'] ?? 'Edroots') . " regarding your higher education admission enquiry.");
          ?>
            <tr>
              <td>
                <a href="<?= base_url('leads/view.php?id=' . $call['lead_id']) ?>" style="font-weight:700; color:var(--text-heading); text-decoration:none;">
                  <?= htmlspecialchars($call['full_name']) ?>
                </a>
                <div style="font-family:monospace; font-size:0.72rem; color:var(--primary);"><?= htmlspecialchars($call['lead_code']) ?></div>
              </td>
              <td>
                <div style="display:flex; align-items:center; gap:6px;">
                  <span style="font-family:monospace; font-weight:700; font-size:0.85rem;"><?= htmlspecialchars($call['phone']) ?></span>
                  <a href="<?= $dialUrl ?>" class="btn btn-outline btn-sm" style="padding:2px 6px;" title="Direct Phone Call">
                    <i class="fa-solid fa-phone" style="color:var(--primary); font-size:0.75rem;"></i>
                  </a>
                  <a href="<?= $waUrl ?>" target="_blank" class="btn btn-outline btn-sm" style="padding:2px 6px;" title="WhatsApp Candidate">
                    <i class="fa-brands fa-whatsapp" style="color:#25d366; font-size:0.85rem;"></i>
                  </a>
                </div>
              </td>
              <td><span style="font-weight:600; font-size:0.825rem;"><?= htmlspecialchars($call['preferred_country']) ?></span></td>
              <td><span class="badge badge-primary" style="font-size:0.7rem; font-weight:700;"><?= htmlspecialchars($call['stage']) ?></span></td>
              <td style="font-size:0.78rem; color:var(--text-muted); max-width:240px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                <?= htmlspecialchars($call['last_notes'] ?: 'Scheduled consultation') ?>
              </td>
              <td style="text-align:right;">
                <button type="button" class="btn btn-primary btn-sm" style="font-size:0.75rem; padding:3px 10px;" onclick="openLogCallModal(<?= $call['lead_id'] ?>, '<?= htmlspecialchars(addslashes($call['full_name'])) ?>', '<?= htmlspecialchars($call['stage']) ?>')">
                  <i class="fa-solid fa-check"></i> Log Outcome
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<?php if (!empty($unassignedWebLeads)): ?>
  <!-- Action Required: Unassigned Inbound Leads Station for Managers -->
  <div class="card" id="widget-unassigned-leads" style="margin-bottom:1.5rem; padding:1.25rem; border-left:4px solid #3b82f6;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; flex-wrap:wrap; gap:0.5rem;">
      <div>
        <h3 style="font-size:1.05rem; font-weight:800; color:var(--text-heading); margin:0; display:flex; align-items:center; gap:8px;">
          <i class="fa-solid fa-bullhorn" style="color:#3b82f6;"></i> Fresh Inbound Leads Awaiting Counselor Allocation
          <span class="badge badge-blue"><?= count($unassignedWebLeads) ?> Pending</span>
        </h3>
        <p style="font-size:0.78rem; color:var(--text-muted); margin:2px 0 0 0;">Inbound submissions from Meta / Google ads awaiting allocation.</p>
      </div>
      <a href="<?= base_url('data/web_leads.php') ?>" class="btn btn-outline btn-sm">Open Triage Board &rarr;</a>
    </div>

    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>Candidate Name</th>
            <th>Phone</th>
            <th>Study Interest</th>
            <th>Source Channel</th>
            <th>Inbound Date</th>
            <th style="text-align:right;">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($unassignedWebLeads as $wl): ?>
            <tr>
              <td><strong><?= htmlspecialchars($wl['full_name']) ?></strong></td>
              <td style="font-family:monospace;"><?= htmlspecialchars($wl['phone']) ?></td>
              <td><?= htmlspecialchars($wl['preferred_country']) ?> &bull; <?= htmlspecialchars($wl['preferred_course']) ?></td>
              <td><span class="badge badge-secondary"><?= htmlspecialchars($wl['source']) ?></span></td>
              <td style="font-size:0.78rem; color:var(--text-muted);"><?= time_ago($wl['created_at']) ?></td>
              <td style="text-align:right;">
                <a href="<?= base_url('data/web_leads.php') ?>" class="btn btn-primary btn-sm" style="font-size:0.75rem; padding:2px 8px;">
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

<!-- Primary Metric Cards (NexLink Design) -->
<div id="widget-metric-cards" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:1rem; margin-bottom:1.5rem;">
  <!-- Total Active Leads -->
  <div class="card" style="padding:1.25rem; display:flex; align-items:center; gap:1rem;">
    <div style="width:52px; height:52px; border-radius:14px; background:var(--primary-subtle); color:var(--primary); display:flex; align-items:center; justify-content:center; font-size:1.4rem;">
      <i class="fa-solid fa-user-graduate"></i>
    </div>
    <div>
      <div style="font-size:1.6rem; font-weight:800; color:var(--text-heading); line-height:1.1;"><?= number_format($totalLeads) ?></div>
      <div style="font-size:0.8rem; color:var(--text-muted); font-weight:500;">Active Student Leads</div>
    </div>
  </div>

  <!-- Invoiced Customers -->
  <div class="card" style="padding:1.25rem; display:flex; align-items:center; gap:1rem;">
    <div style="width:52px; height:52px; border-radius:14px; background:#dcfce7; color:#15803d; display:flex; align-items:center; justify-content:center; font-size:1.4rem;">
      <i class="fa-solid fa-certificate"></i>
    </div>
    <div>
      <div style="font-size:1.6rem; font-weight:800; color:#15803d; line-height:1.1;"><?= number_format($totalCustomers) ?></div>
      <div style="font-size:0.8rem; color:var(--text-muted); font-weight:500;">Invoiced Clients (<?= $conversionRate ?>%)</div>
    </div>
  </div>

  <!-- Collected Revenue -->
  <div class="card" style="padding:1.25rem; display:flex; align-items:center; gap:1rem;">
    <div style="width:52px; height:52px; border-radius:14px; background:#ecfdf5; color:#059669; display:flex; align-items:center; justify-content:center; font-size:1.4rem;">
      <i class="fa-solid fa-wallet"></i>
    </div>
    <div>
      <div style="font-size:1.4rem; font-weight:800; color:#059669; line-height:1.1;"><?= format_currency($totalCollected) ?></div>
      <div style="font-size:0.8rem; color:var(--text-muted); font-weight:500;">Collected Revenue</div>
    </div>
  </div>

  <!-- Outstanding Receivables -->
  <div class="card" style="padding:1.25rem; display:flex; align-items:center; gap:1rem;">
    <div style="width:52px; height:52px; border-radius:14px; background:#fee2e2; color:#b91c1c; display:flex; align-items:center; justify-content:center; font-size:1.4rem;">
      <i class="fa-solid fa-scale-unbalanced"></i>
    </div>
    <div>
      <div style="font-size:1.4rem; font-weight:800; color:#b91c1c; line-height:1.1;"><?= format_currency($totalBalance) ?></div>
      <div style="font-size:0.8rem; color:var(--text-muted); font-weight:500;">Outstanding Balance</div>
    </div>
  </div>
</div>

<!-- 4 Operational Task Queues Widget -->
<div class="card" id="widget-task-queues" style="margin-bottom:1.5rem; padding:1.25rem;">
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
    <a href="<?= base_url('tasks/index.php?queue=scheduled') ?>" style="text-decoration:none; background:var(--primary-subtle); border-radius:10px; padding:1rem; display:flex; justify-content:space-between; align-items:center;">
      <div>
        <span style="font-size:0.75rem; font-weight:700; color:var(--primary); text-transform:uppercase;">Scheduled Follow-ups</span>
        <div style="font-size:1.4rem; font-weight:800; color:var(--text-heading); margin-top:2px;">Today's Calls</div>
      </div>
      <span class="badge badge-blue" style="font-size:1rem; font-weight:800;"><?= $countScheduled ?></span>
    </a>

    <a href="<?= base_url('tasks/index.php?queue=pending_followup') ?>" style="text-decoration:none; background:#fee2e2; border-radius:10px; padding:1rem; display:flex; justify-content:space-between; align-items:center;">
      <div>
        <span style="font-size:0.75rem; font-weight:700; color:#b91c1c; text-transform:uppercase;">Pending Follow-ups</span>
        <div style="font-size:1.4rem; font-weight:800; color:#b91c1c; margin-top:2px;">Overdue Calls</div>
      </div>
      <span class="badge badge-red" style="font-size:1rem; font-weight:800;"><?= $countOverdue ?></span>
    </a>

    <a href="<?= base_url('tasks/index.php?queue=today_leads') ?>" style="text-decoration:none; background:#dcfce7; border-radius:10px; padding:1rem; display:flex; justify-content:space-between; align-items:center;">
      <div>
        <span style="font-size:0.75rem; font-weight:700; color:#15803d; text-transform:uppercase;">Today Leads</span>
        <div style="font-size:1.4rem; font-weight:800; color:#15803d; margin-top:2px;">Fresh Inbound</div>
      </div>
      <span class="badge badge-green" style="font-size:1rem; font-weight:800;"><?= $countTodayLeads ?></span>
    </a>

    <a href="<?= base_url('tasks/index.php?queue=pending_leads') ?>" style="text-decoration:none; background:#fef3c7; border-radius:10px; padding:1rem; display:flex; justify-content:space-between; align-items:center;">
      <div>
        <span style="font-size:0.75rem; font-weight:700; color:#b45309; text-transform:uppercase;">Pending Leads</span>
        <div style="font-size:1.4rem; font-weight:800; color:#b45309; margin-top:2px;">Uncontacted</div>
      </div>
      <span class="badge badge-warning" style="font-size:1rem; font-weight:800;"><?= $countPendingLeads ?></span>
    </a>
  </div>
</div>

<!-- Charts Section -->
<div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; margin-bottom:1.5rem; align-items:start;">
  <!-- 6-Stage Pipeline Chart -->
  <div class="card" id="widget-pipeline-chart" style="padding:1.5rem;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
      <h3 style="font-size:1.05rem; font-weight:700; color:var(--text-heading); margin:0;">
        <i class="fa-solid fa-diagram-project" style="color:var(--primary); margin-right:4px;"></i> 6-Stage Admission Pipeline
      </h3>
      <a href="<?= base_url('admissions/index.php') ?>" style="font-size:0.78rem; color:var(--primary); font-weight:600; text-decoration:none;">Kanban &rarr;</a>
    </div>
    <div style="height:260px;">
      <canvas id="pipelineChart"></canvas>
    </div>
  </div>

  <!-- Branch / Counselor Chart -->
  <div class="card" id="widget-branch-chart" style="padding:1.5rem;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
      <h3 style="font-size:1.05rem; font-weight:700; color:var(--text-heading); margin:0;">
        <i class="fa-solid fa-chart-pie" style="color:var(--primary); margin-right:4px;"></i> Regional Branch Intake Distribution
      </h3>
      <a href="<?= base_url('reports/index.php?report=branches') ?>" style="font-size:0.78rem; color:var(--primary); font-weight:600; text-decoration:none;">All Hubs &rarr;</a>
    </div>
    <div style="height:260px;">
      <canvas id="branchChart"></canvas>
    </div>
  </div>
</div>

<!-- Recent Leads & Recent Activities Split Grid -->
<div style="display:grid; grid-template-columns:2fr 1fr; gap:1.5rem; align-items:start;">
  <!-- Recent Leads Table -->
  <div class="card" id="widget-recent-leads" style="padding:1.5rem;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
      <h3 style="font-size:1.05rem; font-weight:700; color:var(--text-heading); margin:0;">
        <i class="fa-solid fa-user-graduate" style="color:var(--primary); margin-right:4px;"></i> Recent Student Inquiries
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
            <th>Branch</th>
            <th>Counselor</th>
            <th style="text-align:right;">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($recentLeads)): ?>
            <tr>
              <td colspan="6" style="text-align:center; padding:2.5rem; color:var(--text-muted);">
                <i class="fa-solid fa-user-graduate" style="font-size:1.6rem; margin-bottom:0.5rem; display:block; color:var(--text-light);"></i>
                No active student inquiries recorded yet. New leads will automatically display here.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($recentLeads as $rl): ?>
              <tr>
                <td>
                  <a href="<?= base_url('leads/view.php?id=' . $rl['id']) ?>" style="font-weight:700; color:var(--text-heading); font-size:0.875rem; text-decoration:none;">
                    <?= htmlspecialchars($rl['full_name']) ?>
                  </a>
                  <div style="font-family:monospace; font-size:0.72rem; color:var(--primary);"><?= htmlspecialchars($rl['lead_code']) ?></div>
                </td>
                <td style="font-size:0.825rem; font-weight:600;"><?= htmlspecialchars($rl['preferred_country']) ?></td>
                <td><span class="badge badge-primary" style="font-size:0.7rem; font-weight:700;"><?= htmlspecialchars($rl['stage']) ?></span></td>
                <td style="font-size:0.8rem; color:var(--text-muted);"><?= htmlspecialchars($rl['branch_name'] ?? 'Branch') ?></td>
                <td style="font-size:0.8rem; font-weight:600;"><?= htmlspecialchars($rl['counselor_name'] ?? 'Counselor') ?></td>
                <td style="text-align:right;">
                  <a href="<?= base_url('leads/view.php?id=' . $rl['id']) ?>" class="btn btn-secondary btn-sm" style="font-size:0.75rem; padding:2px 8px;">Dossier &rarr;</a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Recent System Activity Trail -->
  <div class="card" style="padding:1.5rem;">
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
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Pipeline Chart
  new Chart(document.getElementById('pipelineChart'), {
    type: 'bar',
    data: {
      labels: <?= json_encode($stageChartLabels) ?>,
      datasets: [{
        label: 'Students in Stage',
        data: <?= json_encode($stageChartData) ?>,
        backgroundColor: ['#64748b', '#0284c7', '#7008E7', '#f59e0b', '#8b5cf6', '#10b981'],
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

  // Branch Chart
  new Chart(document.getElementById('branchChart'), {
    type: 'doughnut',
    data: {
      labels: <?= json_encode($branchChartLabels) ?>,
      datasets: [{
        data: <?= json_encode($branchChartLeads) ?>,
        backgroundColor: ['#7008E7', '#3b82f6', '#10b981', '#f59e0b', '#ec4899', '#6366f1']
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { position: 'right' } }
    }
  });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
