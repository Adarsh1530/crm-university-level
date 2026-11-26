<?php
/**
 * Follow-Up Management Lifecycle Hub - Core CRM
 * Fully Aligned with Client Requirements & Malayalam Training Audio:
 * 1. New / First Follow-Up (Initial touchpoint following lead generation)
 * 2. Scheduled Follow-Ups (Specific dates, times, and reminder alarms)
 * 3. Pending Follow-Ups Backlog (Critical daily clearing queue)
 * 4. Long-Cycle Follow-Ups (Prolonged prospect cycles over weeks/months)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_permission('tasks', 'view');

$currentUser = current_user();
$pageTitle = 'Follow-Up Management Lifecycle';
$activePage = 'tasks';

// 4 Lifecycle Stages
$stage = $_GET['stage'] ?? ($_GET['queue'] ?? 'scheduled');
if (!in_array($stage, ['new_first', 'scheduled', 'pending_backlog', 'long_cycle'])) {
    if ($stage === 'pending_followup') $stage = 'pending_backlog';
    elseif ($stage === 'today_leads' || $stage === 'pending_leads') $stage = 'new_first';
    else $stage = 'scheduled';
}

$scopeLeads = get_crm_scope_clause('l', 'assigned_counselor_id', 'branch_id');

// 1. Calculate live counts for all 4 stages
// Stage 1: New / First Follow-Up (Freshly generated leads, 0 followups conducted)
$countNewFirst = (int)$pdo->query("
    SELECT COUNT(*) FROM leads l 
    WHERE l.followup_count = 0 AND l.is_archived = 0 AND {$scopeLeads}
")->fetchColumn();

// Stage 2: Scheduled Follow-Ups (Scheduled for today or future with alarms)
$countScheduled = (int)$pdo->query("
    SELECT COUNT(*) FROM followups f
    JOIN leads l ON f.lead_id = l.id
    WHERE f.scheduled_date = CURDATE() AND f.status = 'Pending' AND l.is_archived = 0 AND {$scopeLeads}
")->fetchColumn();

// Stage 3: Pending Follow-Ups Backlog (Critical daily queue: scheduled prior to today, must be cleared!)
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

$totalPendingTasks = $countNewFirst + $countScheduled + $countPendingBacklog + $countLongCycle;

// Interaction Titles for follow-up modal
$interactionTitles = [];
try {
    $interactionTitles = $pdo->query("SELECT * FROM interaction_titles WHERE status = 'Active' ORDER BY sort_order ASC")->fetchAll();
} catch (Exception $e) {}

// 2. Fetch records according to selected lifecycle stage
$records = [];

if ($stage === 'new_first') {
    // Stage 1: Fresh leads needing initial contact
    $stmt = $pdo->query("
        SELECT NULL as followup_id, l.id as lead_id, l.lead_code, l.full_name, l.phone, l.email,
               l.business_name, l.region_number, l.main_region, l.zone_type, l.is_phone_verified,
               l.prospectivity, l.active_status, l.source, l.created_at as queued_date,
               l.assigned_counselor_id, b.name as branch_name, u.name as counselor_name
        FROM leads l
        LEFT JOIN branches b ON l.branch_id = b.id
        LEFT JOIN users u ON l.assigned_counselor_id = u.id
        WHERE l.followup_count = 0 AND l.is_archived = 0 AND {$scopeLeads}
        ORDER BY l.id DESC
    ");
    $records = $stmt->fetchAll();

} elseif ($stage === 'scheduled') {
    // Stage 2: Scheduled follow-ups due today
    $stmt = $pdo->query("
        SELECT f.id as followup_id, f.scheduled_date, f.scheduled_time, f.notes as task_note,
               it.title as interaction_title,
               l.id as lead_id, l.lead_code, l.full_name, l.phone, l.email,
               l.business_name, l.region_number, l.main_region, l.zone_type, l.is_phone_verified,
               l.prospectivity, l.active_status, l.source,
               l.assigned_counselor_id, b.name as branch_name, u.name as counselor_name
        FROM followups f
        JOIN leads l ON f.lead_id = l.id
        LEFT JOIN interaction_titles it ON f.interaction_title_id = it.id
        LEFT JOIN branches b ON l.branch_id = b.id
        LEFT JOIN users u ON l.assigned_counselor_id = u.id
        WHERE f.scheduled_date = CURDATE() AND f.status = 'Pending' AND l.is_archived = 0 AND {$scopeLeads}
        ORDER BY f.scheduled_time ASC, f.id DESC
    ");
    $records = $stmt->fetchAll();

} elseif ($stage === 'pending_backlog') {
    // Stage 3: Pending Follow-Ups Backlog (overdue must-clear queue)
    $stmt = $pdo->query("
        SELECT f.id as followup_id, f.scheduled_date, f.scheduled_time, f.notes as task_note,
               it.title as interaction_title,
               DATEDIFF(CURDATE(), f.scheduled_date) as days_overdue,
               l.id as lead_id, l.lead_code, l.full_name, l.phone, l.email,
               l.business_name, l.region_number, l.main_region, l.zone_type, l.is_phone_verified,
               l.prospectivity, l.active_status, l.source,
               l.assigned_counselor_id, b.name as branch_name, u.name as counselor_name
        FROM followups f
        JOIN leads l ON f.lead_id = l.id
        LEFT JOIN interaction_titles it ON f.interaction_title_id = it.id
        LEFT JOIN branches b ON l.branch_id = b.id
        LEFT JOIN users u ON l.assigned_counselor_id = u.id
        WHERE f.scheduled_date < CURDATE() AND f.status = 'Pending' AND l.is_archived = 0 AND {$scopeLeads}
        ORDER BY f.scheduled_date ASC, f.id DESC
    ");
    $records = $stmt->fetchAll();

} elseif ($stage === 'long_cycle') {
    // Stage 4: Long-Cycle Follow-Ups
    $stmt = $pdo->query("
        SELECT NULL as followup_id, l.id as lead_id, l.lead_code, l.full_name, l.phone, l.email,
               l.business_name, l.region_number, l.main_region, l.zone_type, l.is_phone_verified,
               l.prospectivity, l.active_status, l.source, l.created_at as queued_date,
               l.assigned_counselor_id, b.name as branch_name, u.name as counselor_name
        FROM leads l
        LEFT JOIN branches b ON l.branch_id = b.id
        LEFT JOIN users u ON l.assigned_counselor_id = u.id
        WHERE l.is_long_cycle = 1 AND l.is_archived = 0 AND {$scopeLeads}
        ORDER BY l.updated_at DESC
    ");
    $records = $stmt->fetchAll();
}

include __DIR__ . '/../includes/header.php';
?>

<!-- Header Title & Overview -->
<div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <h1 style="font-size:1.5rem; font-weight:800; color:var(--text-heading); margin:0 0 0.25rem 0;">
      Follow-Up Management Lifecycle
    </h1>
    <p style="font-size:0.875rem; color:var(--text-muted); margin:0;">
      Systematic progression across 4 core follow-up stages &bull; <em>"Ensuring no unaddressed leads remain stagnant in the pipeline"</em>
    </p>
  </div>
  <div style="display:flex; gap:0.5rem; align-items:center;">
    <button type="button" class="btn btn-success btn-sm" onclick="openTopUpdateModal()" style="font-weight:700;">
      <i class="fa-solid fa-arrows-rotate"></i> 1-Click Update
    </button>
    <button type="button" class="btn btn-primary btn-sm" onclick="openQuickLeadModal()" style="font-weight:700;">
      <i class="fa-solid fa-plus"></i> + New Lead
    </button>
  </div>
</div>

<!-- 4 Lifecycle Stage Navigation Tabs -->
<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(210px, 1fr)); gap:0.85rem; margin-bottom:1.5rem;">
  
  <!-- Stage 1 Tab: New / First Follow-Up -->
  <a href="<?= base_url('tasks/index.php?stage=new_first') ?>" style="text-decoration:none; background:<?= ($stage === 'new_first') ? '#059669' : '#ecfdf5' ?>; color:<?= ($stage === 'new_first') ? '#fff' : '#064e3b' ?>; border:1px solid #a7f3d0; border-radius:12px; padding:1.1rem; display:flex; justify-content:space-between; align-items:center; transition:all 0.15s;">
    <div>
      <span style="font-size:0.7rem; font-weight:800; text-transform:uppercase; letter-spacing:0.04em; opacity:0.9;">Stage 1</span>
      <div style="font-size:1.1rem; font-weight:800; margin-top:2px;">New / First Follow-Up</div>
      <div style="font-size:0.75rem; opacity:0.85; margin-top:2px;">Initial Inbound Touchpoint</div>
    </div>
    <span class="badge" style="background:<?= ($stage === 'new_first') ? '#fff' : '#059669' ?>; color:<?= ($stage === 'new_first') ? '#059669' : '#fff' ?>; font-size:1.1rem; font-weight:800; padding:4px 10px; border-radius:8px;">
      <?= $countNewFirst ?>
    </span>
  </a>

  <!-- Stage 2 Tab: Scheduled Follow-Ups -->
  <a href="<?= base_url('tasks/index.php?stage=scheduled') ?>" style="text-decoration:none; background:<?= ($stage === 'scheduled') ? '#2563eb' : '#eff6ff' ?>; color:<?= ($stage === 'scheduled') ? '#fff' : '#1e3a8a' ?>; border:1px solid #bfdbfe; border-radius:12px; padding:1.1rem; display:flex; justify-content:space-between; align-items:center; transition:all 0.15s;">
    <div>
      <span style="font-size:0.7rem; font-weight:800; text-transform:uppercase; letter-spacing:0.04em; opacity:0.9;">Stage 2</span>
      <div style="font-size:1.1rem; font-weight:800; margin-top:2px;">Scheduled Follow-Ups</div>
      <div style="font-size:0.75rem; opacity:0.85; margin-top:2px;">Dates & Reminder Alarms</div>
    </div>
    <span class="badge" style="background:<?= ($stage === 'scheduled') ? '#fff' : '#2563eb' ?>; color:<?= ($stage === 'scheduled') ? '#2563eb' : '#fff' ?>; font-size:1.1rem; font-weight:800; padding:4px 10px; border-radius:8px;">
      <?= $countScheduled ?>
    </span>
  </a>

  <!-- Stage 3 Tab: Pending Follow-Ups Backlog -->
  <a href="<?= base_url('tasks/index.php?stage=pending_backlog') ?>" style="text-decoration:none; background:<?= ($stage === 'pending_backlog') ? '#dc2626' : '#fef2f2' ?>; color:<?= ($stage === 'pending_backlog') ? '#fff' : '#7f1d1d' ?>; border:1px solid #fecaca; border-radius:12px; padding:1.1rem; display:flex; justify-content:space-between; align-items:center; transition:all 0.15s;">
    <div>
      <span style="font-size:0.7rem; font-weight:800; text-transform:uppercase; letter-spacing:0.04em; opacity:0.9;">Stage 3 &bull; Critical</span>
      <div style="font-size:1.1rem; font-weight:800; margin-top:2px;">Pending Backlog</div>
      <div style="font-size:0.75rem; opacity:0.85; margin-top:2px;">Daily Must-Clear Queue</div>
    </div>
    <span class="badge" style="background:<?= ($stage === 'pending_backlog') ? '#fff' : '#dc2626' ?>; color:<?= ($stage === 'pending_backlog') ? '#dc2626' : '#fff' ?>; font-size:1.1rem; font-weight:800; padding:4px 10px; border-radius:8px;">
      <?= $countPendingBacklog ?>
    </span>
  </a>

  <!-- Stage 4 Tab: Long-Cycle Follow-Ups -->
  <a href="<?= base_url('tasks/index.php?stage=long_cycle') ?>" style="text-decoration:none; background:<?= ($stage === 'long_cycle') ? '#a21caf' : '#fdf4ff' ?>; color:<?= ($stage === 'long_cycle') ? '#fff' : '#701a75' ?>; border:1px solid #f5d0fe; border-radius:12px; padding:1.1rem; display:flex; justify-content:space-between; align-items:center; transition:all 0.15s;">
    <div>
      <span style="font-size:0.7rem; font-weight:800; text-transform:uppercase; letter-spacing:0.04em; opacity:0.9;">Stage 4</span>
      <div style="font-size:1.1rem; font-weight:800; margin-top:2px;">Long-Cycle Leads</div>
      <div style="font-size:0.75rem; opacity:0.85; margin-top:2px;">Multi-Week Prospect Cycles</div>
    </div>
    <span class="badge" style="background:<?= ($stage === 'long_cycle') ? '#fff' : '#a21caf' ?>; color:<?= ($stage === 'long_cycle') ? '#a21caf' : '#fff' ?>; font-size:1.1rem; font-weight:800; padding:4px 10px; border-radius:8px;">
      <?= $countLongCycle ?>
    </span>
  </a>

</div>

<!-- Stage Items Table / Queue Grid -->
<div class="card" style="border-radius:14px; overflow:hidden;">
  <div style="padding:1.25rem 1.5rem; background:#f8fafc; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.5rem;">
    <div>
      <h3 style="font-size:1.05rem; font-weight:800; color:var(--text-heading); margin:0;">
        <?php if ($stage === 'new_first'): ?>
          <i class="fa-solid fa-sparkles" style="color:#059669;"></i> Stage 1: New / First Follow-Up Queue
        <?php elseif ($stage === 'scheduled'): ?>
          <i class="fa-solid fa-calendar-check" style="color:#2563eb;"></i> Stage 2: Scheduled Follow-Ups Due Today
        <?php elseif ($stage === 'pending_backlog'): ?>
          <i class="fa-solid fa-triangle-exclamation" style="color:#dc2626;"></i> Stage 3: Critical Pending Follow-Ups Backlog
        <?php else: ?>
          <i class="fa-solid fa-hourglass-half" style="color:#a21caf;"></i> Stage 4: Long-Cycle Prolonged Prospect Tracker
        <?php endif; ?>
      </h3>
      <span style="font-size:0.78rem; color:var(--text-muted);">
        <?= count($records) ?> records waiting in this operational queue
      </span>
    </div>

    <div>
      <?php if ($stage === 'pending_backlog' && count($records) > 0): ?>
        <span class="badge badge-danger" style="font-weight:700; font-size:0.75rem;">Clear backlog before end-of-day</span>
      <?php endif; ?>
    </div>
  </div>

  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Record # & Lead</th>
          <th>Business / Account</th>
          <th>Phone (Cross-Check)</th>
          <th>Region & Zone</th>
          <th>Prospectivity</th>
          <th>Active Status</th>
          <?php if ($stage === 'scheduled' || $stage === 'pending_backlog'): ?>
            <th>Scheduled Alarm</th>
            <th>Task Notes</th>
          <?php endif; ?>
          <th>Counselor</th>
          <th style="text-align:right;">Execute Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($records)): ?>
          <tr>
            <td colspan="10" style="text-align:center; padding:3.5rem; color:var(--text-muted);">
              <div style="width:56px; height:56px; border-radius:50%; background:#dcfce7; color:#15803d; display:inline-flex; align-items:center; justify-content:center; font-size:1.8rem; margin-bottom:0.75rem;">
                <i class="fa-solid fa-check"></i>
              </div>
              <div style="font-size:1.15rem; font-weight:800; color:var(--text-heading); margin-bottom:0.35rem;">
                Queue is Clear!
              </div>
              <p style="font-size:0.875rem; color:var(--text-muted); margin:0;">
                No pending records currently awaiting action in this lifecycle stage.
              </p>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($records as $r): ?>
            <tr>
              <!-- Record # & Name -->
              <td>
                <div style="font-family:monospace; font-size:0.75rem; font-weight:700; color:var(--primary); margin-bottom:2px;">
                  <?= htmlspecialchars($r['lead_code']) ?>
                </div>
                <a href="<?= base_url('leads/view.php?id=' . $r['lead_id']) ?>" style="font-weight:700; color:var(--text-heading); text-decoration:none; font-size:0.92rem;">
                  <?= htmlspecialchars($r['full_name']) ?>
                </a>
              </td>

              <!-- Business / Account -->
              <td style="font-size:0.85rem; font-weight:600; color:#334155;">
                <?= htmlspecialchars($r['business_name'] ?: 'Individual Account') ?>
              </td>

              <!-- Phone & Verification -->
              <td>
                <div style="font-family:monospace; font-weight:800; font-size:0.88rem; color:var(--text-heading);">
                  <?= htmlspecialchars($r['phone']) ?>
                </div>
                <?php if (!empty($r['is_phone_verified'])): ?>
                  <span class="badge badge-success" style="font-size:0.65rem; padding:1px 5px;">✓ Verified</span>
                <?php else: ?>
                  <span class="badge badge-warning" style="font-size:0.65rem; padding:1px 5px;">Pending</span>
                <?php endif; ?>
              </td>

              <!-- Region & Zone -->
              <td>
                <span class="badge" style="background:#334155; color:#fff; font-size:0.7rem; font-weight:700;">
                  <?= htmlspecialchars($r['region_number'] ?? 'REG-01') ?>
                </span>
                <div style="font-size:0.75rem; font-weight:600; color:var(--text-heading); margin-top:2px;">
                  <?= htmlspecialchars($r['main_region'] ?? 'Central Zone') ?>
                </div>
              </td>

              <!-- Prospectivity -->
              <td>
                <span class="badge <?= ($r['prospectivity'] === 'Hot') ? 'badge-danger' : (($r['prospectivity'] === 'Cold') ? 'badge-secondary' : 'badge-warning') ?>" style="font-size:0.72rem; font-weight:700;">
                  <?= ($r['prospectivity'] === 'Hot') ? '🔥 Hot' : (($r['prospectivity'] === 'Cold') ? '💤 Cold' : '⚡ Warm') ?>
                </span>
              </td>

              <!-- Active Status -->
              <td>
                <span class="badge badge-primary" style="font-size:0.72rem; font-weight:700;">
                  <?= htmlspecialchars($r['active_status'] ?? 'New Lead') ?>
                </span>
              </td>

              <!-- Scheduled Alarms (if stage 2 or 3) -->
              <?php if ($stage === 'scheduled' || $stage === 'pending_backlog'): ?>
                <td>
                  <div style="font-weight:700; font-size:0.8rem; color:<?= ($stage === 'pending_backlog') ? '#dc2626' : 'var(--text-heading)' ?>;">
                    <i class="fa-solid fa-clock"></i> <?= htmlspecialchars($r['scheduled_time'] ?? '10:00:00') ?>
                  </div>
                  <?php if (!empty($r['days_overdue'])): ?>
                    <span class="badge badge-danger" style="font-size:0.65rem; padding:1px 5px;">
                      <?= $r['days_overdue'] ?>d Overdue
                    </span>
                  <?php endif; ?>
                </td>
                <td style="font-size:0.8rem; color:#475569; max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                  <?= htmlspecialchars($r['task_note'] ?: $r['interaction_title'] ?: 'Scheduled interaction') ?>
                </td>
              <?php endif; ?>

              <!-- Counselor -->
              <td style="font-size:0.825rem; font-weight:600;">
                <?= htmlspecialchars($r['counselor_name'] ?? 'Unassigned') ?>
              </td>

              <!-- Execute Action -->
              <td style="text-align:right;">
                <div style="display:inline-flex; gap:4px; align-items:center;">
                  <button type="button" class="btn btn-primary btn-sm" style="font-size:0.75rem; padding:3px 8px; font-weight:700;" onclick="openLogCallModal(<?= $r['lead_id'] ?>, '<?= htmlspecialchars(addslashes($r['full_name'])) ?>', '<?= htmlspecialchars($r['phone']) ?>')" title="Execute Call & Log Outcome">
                    <i class="fa-solid fa-phone"></i> Call
                  </button>
                  <button type="button" class="btn btn-outline btn-sm" style="font-size:0.75rem; padding:3px 8px;" onclick="openTopUpdateModal(<?= $r['lead_id'] ?>)" title="1-Click Update">
                    <i class="fa-solid fa-arrows-rotate"></i>
                  </button>
                  <a href="<?= base_url('leads/view.php?id=' . $r['lead_id']) ?>" class="btn btn-secondary btn-sm" style="font-size:0.75rem; padding:3px 8px;">
                    Profile &rarr;
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
