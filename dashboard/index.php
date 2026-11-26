<?php
/**
 * Main CRM Operations Cockpit - Customer Relationship Management
 * Aligned 1:1 with Client Requirements:
 * 1. System Identity: Core CRM Platform & Multi-Role Oversight
 * 2. Daily CRM Duties Checklist Card
 * 3. Regional Geographic Segmentation: Region distribution, Central Zone density vs Outer Zone prospectivity, Regional expansion
 * 4. 4-Stage Follow-Up Management Lifecycle: New/First, Scheduled, Pending Backlog, Long-Cycle
 * 5. Call Data Integration: Linked Call Records & Recent Outcomes
 * 6. Social Media Leads Prioritization Queue
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_permission('dashboard', 'view');

$currentUser = current_user();
$pageTitle = 'CRM Operations Cockpit';
$activePage = 'dashboard';

// Role identifiers
$isCounselor = is_counselor();
$isTeamLeader = is_team_leader();
$isOperations = is_operations();
$isSuperAdmin = is_super_admin();

// Scoping clause for current user
$scopeLeads = get_crm_scope_clause('l', 'assigned_counselor_id', 'branch_id');

// 1. Core KPIs
$totalLeads = (int)$pdo->query("SELECT COUNT(*) FROM leads l WHERE l.is_archived = 0 AND {$scopeLeads}")->fetchColumn();
$totalConverted = (int)$pdo->query("SELECT COUNT(*) FROM leads l WHERE (l.is_customer = 1 OR l.active_status = 'Converted') AND l.is_archived = 0 AND {$scopeLeads}")->fetchColumn();
$totalVerifiedPhones = (int)$pdo->query("SELECT COUNT(*) FROM leads l WHERE l.is_phone_verified = 1 AND l.is_archived = 0 AND {$scopeLeads}")->fetchColumn();
$conversionRate = ($totalLeads > 0) ? round(($totalConverted / $totalLeads) * 100, 1) : 0;

// 2. The 4-Stage Follow-Up Management Lifecycle
// Stage 1: New / First Follow-Up (Immediate touchpoint for freshly generated leads, 0 followups done)
$countNewFollowup = (int)$pdo->query("
    SELECT COUNT(*) FROM leads l
    WHERE l.followup_count = 0 AND l.is_archived = 0 AND {$scopeLeads}
")->fetchColumn();

// Stage 2: Scheduled Follow-Ups (Scheduled for today or future with alarms)
$countScheduledFollowups = (int)$pdo->query("
    SELECT COUNT(*) FROM followups f
    JOIN leads l ON f.lead_id = l.id
    WHERE f.scheduled_date = CURDATE() AND f.status = 'Pending' AND l.is_archived = 0 AND {$scopeLeads}
")->fetchColumn();

// Stage 3: Pending Follow-Ups Backlog (Critical daily queue that agents must clear: overdue calls)
$countPendingBacklog = (int)$pdo->query("
    SELECT COUNT(*) FROM followups f
    JOIN leads l ON f.lead_id = l.id
    WHERE f.scheduled_date < CURDATE() AND f.status = 'Pending' AND l.is_archived = 0 AND {$scopeLeads}
")->fetchColumn();

// Stage 4: Long-Cycle Follow-Ups (Prolonged prospect cycles over weeks/months)
$countLongCycle = (int)$pdo->query("
    SELECT COUNT(*) FROM leads l
    WHERE l.is_long_cycle = 1 AND l.is_archived = 0 AND {$scopeLeads}
")->fetchColumn();

$totalLifecycleTasks = $countNewFollowup + $countScheduledFollowups + $countPendingBacklog + $countLongCycle;

// 3. Social Media Leads Count
$countSocialMediaLeads = 0;
try {
    $countSocialMediaLeads = (int)$pdo->query("SELECT COUNT(*) FROM web_leads WHERE status = 'Pending'")->fetchColumn();
} catch (Exception $e) {}

// 4. Regional Geographic Segmentation Data
$regionalData = [];
try {
    $regionalData = $pdo->query("
        SELECT 
            COALESCE(l.main_region, 'Central Zone') as region_name,
            COUNT(*) as total_count,
            SUM(CASE WHEN l.zone_type = 'Center' THEN 1 ELSE 0 END) as center_density,
            SUM(CASE WHEN l.zone_type != 'Center' THEN 1 ELSE 0 END) as outer_density,
            SUM(CASE WHEN l.prospectivity = 'Hot' THEN 1 ELSE 0 END) as hot_count,
            SUM(CASE WHEN l.is_phone_verified = 1 THEN 1 ELSE 0 END) as verified_count
        FROM leads l
        WHERE l.is_archived = 0 AND {$scopeLeads}
        GROUP BY region_name
        ORDER BY total_count DESC
    ")->fetchAll();
} catch (Exception $e) {}

// 5. Lead Prospectivity Breakdown (Hot, Warm, Cold)
$prospectivityStats = $pdo->query("
    SELECT COALESCE(l.prospectivity, 'Warm') as prospect, COUNT(*) as count 
    FROM leads l 
    WHERE l.is_archived = 0 AND {$scopeLeads}
    GROUP BY prospect
")->fetchAll(PDO::FETCH_KEY_PAIR);

$prospectLabels = ['Hot', 'Warm', 'Cold'];
$prospectData = [
    (int)($prospectivityStats['Hot'] ?? 0),
    (int)($prospectivityStats['Warm'] ?? 0),
    (int)($prospectivityStats['Cold'] ?? 0)
];

// 6. Recent Call Data Records
$recentCallLogs = [];
try {
    $recentCallLogs = $pdo->query("
        SELECT c.*, l.lead_code, l.full_name as lead_name, l.phone as lead_phone, l.region_number, u.name as caller_name
        FROM call_logs c
        LEFT JOIN leads l ON c.lead_id = l.id
        LEFT JOIN users u ON c.caller_id = u.id
        ORDER BY c.id DESC
        LIMIT 6
    ")->fetchAll();
} catch (Exception $e) {}

// 7. Recent Inbound Social Media Leads awaiting triage
$pendingSocialLeads = [];
try {
    $pendingSocialLeads = $pdo->query("
        SELECT w.* FROM web_leads w WHERE w.status = 'Pending' ORDER BY w.id DESC LIMIT 4
    ")->fetchAll();
} catch (Exception $e) {}

// 8. Recent Active Leads
$recentLeads = $pdo->query("
    SELECT l.*, b.name as branch_name, u.name as counselor_name
    FROM leads l
    LEFT JOIN branches b ON l.branch_id = b.id
    LEFT JOIN users u ON l.assigned_counselor_id = u.id
    WHERE l.is_archived = 0 AND {$scopeLeads}
    ORDER BY l.id DESC
    LIMIT 6
")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<!-- 1. HERO OPERATIONS BANNER -->
<div class="card" style="margin-bottom:1.5rem; padding:1.5rem 1.75rem; background:linear-gradient(135deg, #ffffff 0%, #f8faff 100%); border:1px solid #e2e8f0; border-radius:14px; box-shadow:0 2px 8px rgba(0,0,0,0.03);">
  <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1.25rem;">
    <div>
      <div style="display:flex; align-items:center; gap:8px; margin-bottom:0.35rem;">
        <h1 style="font-size:1.65rem; font-weight:800; color:var(--text-heading); margin:0;">
          CRM Operations Cockpit
        </h1>
        <span class="badge badge-primary" style="font-size:0.75rem; font-weight:700;"><?= htmlspecialchars($currentUser['role_label'] ?? 'Staff') ?></span>
      </div>
      <p style="font-size:0.875rem; color:var(--text-muted); margin:0; line-height:1.5;">
        Welcome back, <strong><?= htmlspecialchars($currentUser['name']) ?></strong> &bull; 
        Centralized lead ingestion, regional segmentation, phone cross-checking, and 4-stage follow-up queues.
      </p>
    </div>

    <!-- Quick Action Launchpad -->
    <div style="display:flex; gap:0.6rem; align-items:center; flex-wrap:wrap;">
      <button type="button" class="btn btn-primary" id="btn-quick-add-lead" onclick="openQuickLeadModal()" style="font-weight:700; padding:0.55rem 1.15rem; font-size:0.875rem; border-radius:10px; box-shadow:0 3px 8px rgba(79,70,229,0.25);">
        <i class="fa-solid fa-plus"></i> Quick Add Lead
      </button>

      <button type="button" class="btn btn-success" onclick="openTopUpdateModal()" style="font-weight:700; padding:0.55rem 1.15rem; font-size:0.875rem; border-radius:10px; background:#059669; border-color:#059669; color:#fff;">
        <i class="fa-solid fa-arrows-rotate"></i> 1-Click Update
      </button>

      <a href="<?= base_url('tasks/index.php') ?>" class="btn btn-ghost" style="font-weight:700; padding:0.55rem 1rem; font-size:0.875rem; border-radius:10px; border:1px solid var(--border-color); background:#fff;">
        <i class="fa-solid fa-list-check" style="color:var(--primary);"></i> Assigned Tasks (<?= $totalLifecycleTasks ?>)
      </a>
    </div>
  </div>
</div>

<!-- 2. SUMMARY CHECKLIST OF DAILY CRM DUTIES (From Client Audio & Specifications) -->
<div class="card" style="margin-bottom:1.5rem; padding:1.25rem 1.5rem; border-radius:14px; border-left:4px solid var(--primary); background:#fff;">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.85rem; flex-wrap:wrap; gap:0.5rem;">
    <div>
      <h3 style="font-size:1.05rem; font-weight:800; color:var(--text-heading); margin:0; display:flex; align-items:center; gap:8px;">
        <i class="fa-solid fa-clipboard-check" style="color:var(--primary);"></i> Summary Checklist of Daily CRM Duties
      </h3>
      <span style="font-size:0.78rem; color:var(--text-muted);">Standard operating procedures for Counselors, Team Leaders, and Operations/Accessors</span>
    </div>
    <span class="badge badge-success" style="font-size:0.75rem; font-weight:700;">Daily Operating Protocol</span>
  </div>

  <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:0.85rem;">
    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:0.85rem 1rem;">
      <div style="font-weight:800; font-size:0.825rem; color:#1e293b; margin-bottom:4px; display:flex; align-items:center; gap:6px;">
        <span style="background:var(--primary-subtle); color:var(--primary); width:20px; height:20px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:0.75rem;">1</span>
        Daily Start
      </div>
      <p style="margin:0; font-size:0.78rem; color:#475569; line-height:1.4;">
        Open the CRM task list via the top header bar (<strong>Tasks</strong>) to review active worklists.
      </p>
    </div>

    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:0.85rem 1rem;">
      <div style="font-weight:800; font-size:0.825rem; color:#1e293b; margin-bottom:4px; display:flex; align-items:center; gap:6px;">
        <span style="background:var(--primary-subtle); color:var(--primary); width:20px; height:20px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:0.75rem;">2</span>
        Data Review
      </div>
      <p style="margin:0; font-size:0.78rem; color:#475569; line-height:1.4;">
        Pull active <strong>Social Media Leads</strong> and inspect assigned regions & central density.
      </p>
    </div>

    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:0.85rem 1rem;">
      <div style="font-weight:800; font-size:0.825rem; color:#1e293b; margin-bottom:4px; display:flex; align-items:center; gap:6px;">
        <span style="background:var(--primary-subtle); color:var(--primary); width:20px; height:20px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:0.75rem;">3</span>
        Call Execution
      </div>
      <p style="margin:0; font-size:0.78rem; color:#475569; line-height:1.4;">
        Review linked <strong>Call Data</strong>, initiate contact, and log connection outcome.
      </p>
    </div>

    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:0.85rem 1rem;">
      <div style="font-weight:800; font-size:0.825rem; color:#1e293b; margin-bottom:4px; display:flex; align-items:center; gap:6px;">
        <span style="background:var(--primary-subtle); color:var(--primary); width:20px; height:20px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:0.75rem;">4</span>
        Queue Maintenance
      </div>
      <p style="margin:0; font-size:0.78rem; color:#475569; line-height:1.4;">
        Clear all <strong>Pending Follow-ups Backlog</strong> and book next <strong>Scheduled Follow-ups</strong>.
      </p>
    </div>

    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:0.85rem 1rem;">
      <div style="font-weight:800; font-size:0.825rem; color:#1e293b; margin-bottom:4px; display:flex; align-items:center; gap:6px;">
        <span style="background:var(--primary-subtle); color:var(--primary); width:20px; height:20px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:0.75rem;">5</span>
        Data Verification
      </div>
      <p style="margin:0; font-size:0.78rem; color:#475569; line-height:1.4;">
        Cross-verify contact phone numbers aloud and update fields using the top <strong>Update</strong> button.
      </p>
    </div>

    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:0.85rem 1rem;">
      <div style="font-weight:800; font-size:0.825rem; color:#1e293b; margin-bottom:4px; display:flex; align-items:center; gap:6px;">
        <span style="background:var(--primary-subtle); color:var(--primary); width:20px; height:20px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:0.75rem;">6</span>
        Escalation
      </div>
      <p style="margin:0; font-size:0.78rem; color:#475569; line-height:1.4;">
        Route qualified Hot/Warm prospects to <strong>Counselors</strong> following standard procedure.
      </p>
    </div>
  </div>
</div>

<!-- 3. THE 4-STAGE FOLLOW-UP LIFECYCLE QUEUES -->
<div class="card" id="widget-task-queues" style="margin-bottom:1.5rem; padding:1.25rem 1.5rem; border-radius:14px;">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; flex-wrap:wrap; gap:0.5rem;">
    <div>
      <h3 style="font-size:1.05rem; font-weight:800; color:var(--text-heading); margin:0; display:flex; align-items:center; gap:8px;">
        <i class="fa-solid fa-clock-rotate-left" style="color:var(--primary);"></i> 4-Stage Follow-Up Management Lifecycle
      </h3>
      <span style="font-size:0.78rem; color:var(--text-muted);">Daily operational queues to ensure no leads remain stagnant in the pipeline</span>
    </div>
    <a href="<?= base_url('tasks/index.php') ?>" class="btn btn-outline btn-sm" style="font-size:0.78rem; font-weight:700;">
      Open Tasks Hub &rarr;
    </a>
  </div>

  <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(210px, 1fr)); gap:1rem;">
    <!-- Stage 1: New / First Follow-Up -->
    <a href="<?= base_url('tasks/index.php?stage=new_first') ?>" style="text-decoration:none; background:#ecfdf5; border:1px solid #a7f3d0; border-radius:12px; padding:1.15rem; display:flex; justify-content:space-between; align-items:center; transition:transform 0.15s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
      <div>
        <span style="font-size:0.72rem; font-weight:800; color:#047857; text-transform:uppercase; letter-spacing:0.04em; display:block;">Stage 1</span>
        <div style="font-size:1.15rem; font-weight:800; color:#064e3b; margin-top:2px;">New / First Touch</div>
        <div style="font-size:0.75rem; color:#065f46; margin-top:2px;">Fresh Inbound Leads</div>
      </div>
      <span class="badge" style="background:#059669; color:#fff; font-size:1.1rem; font-weight:800; padding:6px 12px; border-radius:10px;"><?= $countNewFollowup ?></span>
    </a>

    <!-- Stage 2: Scheduled Follow-Ups -->
    <a href="<?= base_url('tasks/index.php?stage=scheduled') ?>" style="text-decoration:none; background:#eff6ff; border:1px solid #bfdbfe; border-radius:12px; padding:1.15rem; display:flex; justify-content:space-between; align-items:center; transition:transform 0.15s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
      <div>
        <span style="font-size:0.72rem; font-weight:800; color:#1d4ed8; text-transform:uppercase; letter-spacing:0.04em; display:block;">Stage 2</span>
        <div style="font-size:1.15rem; font-weight:800; color:#1e3a8a; margin-top:2px;">Scheduled Calls</div>
        <div style="font-size:0.75rem; color:#1e40af; margin-top:2px;">Dates & Reminder Alarms</div>
      </div>
      <span class="badge" style="background:#2563eb; color:#fff; font-size:1.1rem; font-weight:800; padding:6px 12px; border-radius:10px;"><?= $countScheduledFollowups ?></span>
    </a>

    <!-- Stage 3: Pending Follow-Ups Backlog -->
    <a href="<?= base_url('tasks/index.php?stage=pending_backlog') ?>" style="text-decoration:none; background:#fef2f2; border:1px solid #fecaca; border-radius:12px; padding:1.15rem; display:flex; justify-content:space-between; align-items:center; transition:transform 0.15s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
      <div>
        <span style="font-size:0.72rem; font-weight:800; color:#b91c1c; text-transform:uppercase; letter-spacing:0.04em; display:block;">Stage 3 &bull; Critical</span>
        <div style="font-size:1.15rem; font-weight:800; color:#7f1d1d; margin-top:2px;">Pending Backlog</div>
        <div style="font-size:0.75rem; color:#991b1b; margin-top:2px;">Overdue Must-Clear Queue</div>
      </div>
      <span class="badge" style="background:#dc2626; color:#fff; font-size:1.1rem; font-weight:800; padding:6px 12px; border-radius:10px;"><?= $countPendingBacklog ?></span>
    </a>

    <!-- Stage 4: Long-Cycle Follow-Ups -->
    <a href="<?= base_url('tasks/index.php?stage=long_cycle') ?>" style="text-decoration:none; background:#fdf4ff; border:1px solid #f5d0fe; border-radius:12px; padding:1.15rem; display:flex; justify-content:space-between; align-items:center; transition:transform 0.15s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
      <div>
        <span style="font-size:0.72rem; font-weight:800; color:#a21caf; text-transform:uppercase; letter-spacing:0.04em; display:block;">Stage 4</span>
        <div style="font-size:1.15rem; font-weight:800; color:#701a75; margin-top:2px;">Long-Cycle Leads</div>
        <div style="font-size:0.75rem; color:#86198f; margin-top:2px;">Prolonged Prospect Cycles</div>
      </div>
      <span class="badge" style="background:#a21caf; color:#fff; font-size:1.1rem; font-weight:800; padding:6px 12px; border-radius:10px;"><?= $countLongCycle ?></span>
    </a>
  </div>
</div>

<!-- 4. REGIONAL & GEOGRAPHIC SEGMENTATION CARD (Client Audio Requirement) -->
<div style="display:grid; grid-template-columns:2fr 1fr; gap:1.5rem; margin-bottom:1.5rem; align-items:start;">
  <!-- Regional Density Breakdown -->
  <div class="card" style="padding:1.5rem; border-radius:14px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; flex-wrap:wrap; gap:0.5rem;">
      <div>
        <h3 style="font-size:1.05rem; font-weight:800; color:var(--text-heading); margin:0; display:flex; align-items:center; gap:8px;">
          <i class="fa-solid fa-map-location-dot" style="color:var(--primary);"></i> Regional & Geographic Segmentation
        </h3>
        <span style="font-size:0.78rem; color:var(--text-muted);">
          Mapping distribution and density across central zones &bull; <em>"The area that are located in the center is prospectivity"</em>
        </span>
      </div>
      <a href="<?= base_url('leads/index.php') ?>" class="btn btn-outline btn-sm" style="font-size:0.78rem; font-weight:600;">Filter by Region &rarr;</a>
    </div>

    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>Main Region</th>
            <th>Total Pipeline</th>
            <th>Center Zone Density</th>
            <th>Hot Prospects</th>
            <th>Phone Verified</th>
            <th>Conversion Prospectivity</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($regionalData)): ?>
            <tr><td colspan="6" style="text-align:center; color:var(--text-muted);">No regional data available.</td></tr>
          <?php else: ?>
            <?php foreach ($regionalData as $rd): 
              $ratio = ($rd['total_count'] > 0) ? round(($rd['center_density'] / $rd['total_count']) * 100) : 0;
            ?>
              <tr>
                <td>
                  <strong style="color:var(--text-heading); font-size:0.9rem;"><?= htmlspecialchars($rd['region_name']) ?></strong>
                </td>
                <td><strong style="font-size:0.92rem;"><?= $rd['total_count'] ?></strong> leads</td>
                <td>
                  <span class="badge badge-primary" style="font-weight:700;"><?= $rd['center_density'] ?> Center</span>
                  <?php if ($rd['outer_density'] > 0): ?>
                    <span class="badge badge-secondary" style="font-size:0.72rem;"><?= $rd['outer_density'] ?> Outer</span>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="badge badge-danger" style="font-weight:700;">🔥 <?= $rd['hot_count'] ?> Hot</span>
                </td>
                <td>
                  <span class="badge badge-success" style="font-weight:700;">✓ <?= $rd['verified_count'] ?> Verified</span>
                </td>
                <td>
                  <div style="display:flex; align-items:center; gap:6px;">
                    <div style="flex:1; height:6px; background:#e2e8f0; border-radius:3px; overflow:hidden; max-width:80px;">
                      <div style="height:100%; width:<?= $ratio ?>%; background:var(--primary);"></div>
                    </div>
                    <span style="font-size:0.75rem; font-weight:700; color:var(--text-heading);"><?= $ratio ?>% Center</span>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Prospectivity Distribution Donut Chart -->
  <div class="card" style="padding:1.5rem; border-radius:14px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
      <h3 style="font-size:1.05rem; font-weight:800; color:var(--text-heading); margin:0; display:flex; align-items:center; gap:6px;">
        <i class="fa-solid fa-fire" style="color:#ef4444;"></i> Lead Prospectivity
      </h3>
      <span class="badge badge-secondary" style="font-size:0.72rem;">Warmth Tier</span>
    </div>
    <div style="height:210px;">
      <canvas id="prospectChart"></canvas>
    </div>
    <div style="display:flex; justify-content:space-around; margin-top:1rem; border-top:1px solid var(--border-color); padding-top:0.75rem; text-align:center;">
      <div>
        <div style="font-size:1.2rem; font-weight:800; color:#ef4444;"><?= (int)($prospectivityStats['Hot'] ?? 0) ?></div>
        <div style="font-size:0.75rem; color:var(--text-muted); font-weight:700;">Hot Leads</div>
      </div>
      <div>
        <div style="font-size:1.2rem; font-weight:800; color:#f59e0b;"><?= (int)($prospectivityStats['Warm'] ?? 0) ?></div>
        <div style="font-size:0.75rem; color:var(--text-muted); font-weight:700;">Warm Leads</div>
      </div>
      <div>
        <div style="font-size:1.2rem; font-weight:800; color:#64748b;"><?= (int)($prospectivityStats['Cold'] ?? 0) ?></div>
        <div style="font-size:0.75rem; color:var(--text-muted); font-weight:700;">Cold Leads</div>
      </div>
    </div>
  </div>
</div>

<!-- 5. CALL DATA INTEGRATION & SOCIAL MEDIA LEADS INGESTION -->
<div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; margin-bottom:1.5rem; align-items:start;">
  
  <!-- Call Data Integration Widget -->
  <div class="card" style="padding:1.5rem; border-radius:14px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; flex-wrap:wrap; gap:0.5rem;">
      <div>
        <h3 style="font-size:1.05rem; font-weight:800; color:var(--text-heading); margin:0; display:flex; align-items:center; gap:8px;">
          <i class="fa-solid fa-phone-volume" style="color:#0284c7;"></i> Call Data Integration
        </h3>
        <span style="font-size:0.78rem; color:var(--text-muted);">Centralized call logging linked directly to prospect profiles</span>
      </div>
      <a href="<?= base_url('followups/call_data.php') ?>" class="btn btn-outline btn-sm" style="font-size:0.78rem; font-weight:700;">
        Full Call Center &rarr;
      </a>
    </div>

    <div style="display:flex; flex-direction:column; gap:0.75rem;">
      <?php if (empty($recentCallLogs)): ?>
        <div style="text-align:center; padding:2rem; color:var(--text-muted); font-size:0.85rem;">
          <i class="fa-solid fa-phone-slash" style="font-size:1.6rem; color:var(--text-light); margin-bottom:0.5rem; display:block;"></i>
          No call data records found. Use the Call Data action to record calls.
        </div>
      <?php else: ?>
        <?php foreach ($recentCallLogs as $cl): 
          $statusBg = ($cl['call_status'] === 'Connected') ? '#dcfce7' : (($cl['call_status'] === 'Busy') ? '#fef3c7' : '#fee2e2');
          $statusColor = ($cl['call_status'] === 'Connected') ? '#15803d' : (($cl['call_status'] === 'Busy') ? '#b45309' : '#b91c1c');
        ?>
          <div style="display:flex; justify-content:space-between; align-items:center; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:0.75rem 1rem;">
            <div>
              <div style="display:flex; align-items:center; gap:6px;">
                <span class="badge" style="background:<?= $cl['call_direction'] === 'Inbound' ? '#dbeafe' : '#f1f5f9' ?>; color:<?= $cl['call_direction'] === 'Inbound' ? '#1e40af' : '#334155' ?>; font-size:0.7rem; font-weight:700;">
                  <i class="fa-solid <?= $cl['call_direction'] === 'Inbound' ? 'fa-arrow-down-left' : 'fa-arrow-up-right' ?>"></i> <?= htmlspecialchars($cl['call_direction']) ?>
                </span>
                <span style="font-weight:700; font-size:0.875rem; color:var(--text-heading);"><?= htmlspecialchars($cl['lead_name'] ?: 'Contact') ?></span>
                <span style="font-size:0.75rem; color:var(--text-muted);">(<?= htmlspecialchars($cl['lead_phone']) ?>)</span>
              </div>
              <div style="font-size:0.78rem; color:var(--text-muted); margin-top:3px; max-width:320px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                <?= htmlspecialchars($cl['notes'] ?: 'Call interaction logged') ?>
              </div>
            </div>
            <div style="text-align:right;">
              <span class="badge" style="background:<?= $statusBg ?>; color:<?= $statusColor ?>; font-weight:700; font-size:0.72rem;">
                <?= htmlspecialchars($cl['call_status']) ?>
              </span>
              <div style="font-size:0.72rem; color:var(--text-muted); margin-top:2px;">
                <?= $cl['duration_seconds'] ?>s &bull; <?= date('M d, H:i', strtotime($cl['created_at'])) ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Social Media Leads Ingestion Widget -->
  <div class="card" style="padding:1.5rem; border-radius:14px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; flex-wrap:wrap; gap:0.5rem;">
      <div>
        <h3 style="font-size:1.05rem; font-weight:800; color:var(--text-heading); margin:0; display:flex; align-items:center; gap:8px;">
          <i class="fa-solid fa-hashtag" style="color:#7c3aed;"></i> Social Media Leads Ingestion
        </h3>
        <span style="font-size:0.78rem; color:var(--text-muted);">Inbound advertising campaigns (Meta, Instagram, Google Ads)</span>
      </div>
      <a href="<?= base_url('data/web_leads.php') ?>" class="btn btn-outline btn-sm" style="font-size:0.78rem; font-weight:700;">
        View Queue (<?= $countSocialMediaLeads ?>) &rarr;
      </a>
    </div>

    <div style="display:flex; flex-direction:column; gap:0.75rem;">
      <?php if (empty($pendingSocialLeads)): ?>
        <div style="text-align:center; padding:2rem; color:var(--text-muted); font-size:0.85rem;">
          <i class="fa-solid fa-circle-check" style="font-size:1.6rem; color:#10b981; margin-bottom:0.5rem; display:block;"></i>
          All social media inbound leads have been triaged and routed!
        </div>
      <?php else: ?>
        <?php foreach ($pendingSocialLeads as $sl): ?>
          <div style="display:flex; justify-content:space-between; align-items:center; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:0.75rem 1rem;">
            <div>
              <div style="display:flex; align-items:center; gap:6px;">
                <span class="badge" style="background:#f3e8ff; color:#7e22ce; font-size:0.72rem; font-weight:700;">
                  <i class="fa-brands <?= strpos($sl['source'], 'Meta') !== false ? 'fa-meta' : (strpos($sl['source'], 'Instagram') !== false ? 'fa-instagram' : 'fa-google') ?>"></i> <?= htmlspecialchars($sl['source']) ?>
                </span>
                <strong style="font-size:0.875rem; color:var(--text-heading);"><?= htmlspecialchars($sl['student_name'] ?: $sl['full_name']) ?></strong>
              </div>
              <div style="font-size:0.78rem; color:var(--text-muted); margin-top:2px;">
                <?= htmlspecialchars($sl['phone']) ?> &bull; <?= htmlspecialchars($sl['city_state'] ?: 'Central Hub') ?>
              </div>
            </div>
            <div>
              <a href="<?= base_url('data/web_leads.php') ?>" class="btn btn-primary btn-sm" style="font-size:0.75rem; padding:4px 10px; font-weight:700;">
                Triage & Verify &rarr;
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- 6. RECENT LEADS DIRECTORY OVERVIEW -->
<div class="card" style="padding:1.5rem; border-radius:14px; margin-bottom:1.5rem;">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; flex-wrap:wrap; gap:0.5rem;">
    <div>
      <h3 style="font-size:1.05rem; font-weight:800; color:var(--text-heading); margin:0; display:flex; align-items:center; gap:8px;">
        <i class="fa-solid fa-users" style="color:var(--primary);"></i> Active Lead Accounts
      </h3>
      <span style="font-size:0.78rem; color:var(--text-muted);">Fast access to lead records, phone verification status, and regional identifiers</span>
    </div>
    <a href="<?= base_url('leads/index.php') ?>" class="btn btn-outline btn-sm" style="font-size:0.78rem; font-weight:700;">
      View All Leads &rarr;
    </a>
  </div>

  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Record # & Lead Name</th>
          <th>Business / Account</th>
          <th>Phone (Cross-Check)</th>
          <th>Region & Zone</th>
          <th>Prospectivity</th>
          <th>Active Status</th>
          <th>Counselor</th>
          <th style="text-align:right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($recentLeads)): ?>
          <tr><td colspan="8" style="text-align:center; padding:2rem; color:var(--text-muted);">No active leads found.</td></tr>
        <?php else: ?>
          <?php foreach ($recentLeads as $rl): ?>
            <tr>
              <td>
                <a href="<?= base_url('leads/view.php?id=' . $rl['id']) ?>" style="font-weight:700; color:var(--text-heading); font-size:0.92rem; text-decoration:none;">
                  <?= htmlspecialchars($rl['full_name']) ?>
                </a>
                <div style="font-family:monospace; font-size:0.75rem; color:var(--primary); font-weight:700;"><?= htmlspecialchars($rl['lead_code']) ?></div>
              </td>
              <td style="font-size:0.85rem; font-weight:600; color:#334155;">
                <?= htmlspecialchars($rl['business_name'] ?: 'Individual Account') ?>
              </td>
              <td>
                <div style="font-weight:700; font-size:0.85rem; color:var(--text-heading);"><?= htmlspecialchars($rl['phone']) ?></div>
                <?php if (!empty($rl['is_phone_verified'])): ?>
                  <span class="badge badge-success" style="font-size:0.68rem; padding:1px 6px;">✓ Verified</span>
                <?php else: ?>
                  <span class="badge badge-warning" style="font-size:0.68rem; padding:1px 6px;">Pending Cross-Check</span>
                <?php endif; ?>
              </td>
              <td>
                <span class="badge badge-secondary" style="font-size:0.72rem; font-weight:700;"><?= htmlspecialchars($rl['region_number'] ?? 'REG-01') ?></span>
                <span style="font-size:0.78rem; color:var(--text-muted); display:block;"><?= htmlspecialchars($rl['main_region'] ?? 'Central Zone') ?></span>
              </td>
              <td>
                <span class="badge <?= ($rl['prospectivity'] === 'Hot') ? 'badge-danger' : (($rl['prospectivity'] === 'Cold') ? 'badge-secondary' : 'badge-warning') ?>" style="font-size:0.72rem; font-weight:700;">
                  <?= htmlspecialchars($rl['prospectivity'] ?? 'Warm') ?>
                </span>
              </td>
              <td>
                <span class="badge badge-primary" style="font-size:0.72rem; font-weight:700;">
                  <?= htmlspecialchars($rl['active_status'] ?? 'New Lead') ?>
                </span>
              </td>
              <td style="font-size:0.825rem; font-weight:600;">
                <?= htmlspecialchars($rl['counselor_name'] ?? 'Unassigned') ?>
              </td>
              <td style="text-align:right;">
                <div style="display:inline-flex; gap:4px;">
                  <a href="<?= base_url('leads/view.php?id=' . $rl['id']) ?>" class="btn btn-secondary btn-sm" style="font-size:0.75rem; padding:3px 8px; font-weight:600;">
                    Profile &rarr;
                  </a>
                  <button type="button" class="btn btn-outline btn-sm" style="font-size:0.75rem; padding:3px 8px;" onclick="openLogCallModal(<?= $rl['id'] ?>, '<?= htmlspecialchars(addslashes($rl['full_name'])) ?>', '<?= htmlspecialchars($rl['phone']) ?>')" title="Log Call">
                    <i class="fa-solid fa-phone"></i>
                  </button>
                  <button type="button" class="btn btn-outline btn-sm" style="font-size:0.75rem; padding:3px 8px;" onclick="openTopUpdateModal(<?= $rl['id'] ?>)" title="Update Status">
                    <i class="fa-solid fa-arrows-rotate"></i>
                  </button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var prospectEl = document.getElementById('prospectChart');
  if (prospectEl) {
    new Chart(prospectEl, {
      type: 'doughnut',
      data: {
        labels: <?= json_encode($prospectLabels) ?>,
        datasets: [{
          data: <?= json_encode($prospectData) ?>,
          backgroundColor: ['#ef4444', '#f59e0b', '#64748b']
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } }
      }
    });
  }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
