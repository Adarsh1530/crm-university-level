<?php
/**
 * Operational Task Queues Hub (EdrootsCRM Engine)
 * 4 Dedicated Work Queues: Scheduled, Pending Follow-ups, Today Leads, Pending Leads
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_permission('tasks', 'view');

$currentUser = current_user();
$pageTitle = 'Operational Tasks';
$activePage = 'tasks';

$queue = $_GET['queue'] ?? 'scheduled'; // scheduled, pending_followup, today_leads, pending_leads
$scopeLeads = get_crm_scope_clause('l', 'assigned_counselor_id', 'branch_id');

// 1. Calculate live counts for all 4 queues
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

$totalPendingTasks = $countScheduled + $countOverdue + $countTodayLeads + $countPendingLeads;

// Interaction Titles for follow-up form modal
$interactionTitles = $pdo->query("SELECT * FROM interaction_titles WHERE status = 'Active' ORDER BY sort_order ASC")->fetchAll();

// 2. Fetch records according to selected queue
$records = [];

if ($queue === 'scheduled') {
    // Scheduled for today
    $stmt = $pdo->query("
        SELECT f.id as followup_id, f.scheduled_date, f.scheduled_time, f.notes as task_note,
               it.title as interaction_title, it.max_duration_days,
               l.id as lead_id, l.lead_code, l.full_name, l.phone, l.email, l.stage, l.priority,
               l.preferred_country, l.preferred_course, l.assigned_counselor_id,
               b.name as branch_name, u.name as counselor_name
        FROM followups f
        JOIN leads l ON f.lead_id = l.id
        LEFT JOIN interaction_titles it ON f.interaction_title_id = it.id
        LEFT JOIN branches b ON l.branch_id = b.id
        LEFT JOIN users u ON l.assigned_counselor_id = u.id
        WHERE f.scheduled_date = CURDATE() AND f.status = 'Pending' AND l.is_archived = 0 AND {$scopeLeads}
        ORDER BY f.scheduled_time ASC
    ");
    $records = $stmt->fetchAll();
} elseif ($queue === 'pending_followup') {
    // Overdue scheduled before today
    $stmt = $pdo->query("
        SELECT f.id as followup_id, f.scheduled_date, f.scheduled_time, f.notes as task_note,
               it.title as interaction_title, it.max_duration_days,
               l.id as lead_id, l.lead_code, l.full_name, l.phone, l.email, l.stage, l.priority,
               l.preferred_country, l.preferred_course, l.assigned_counselor_id,
               b.name as branch_name, u.name as counselor_name,
               DATEDIFF(CURDATE(), f.scheduled_date) as days_overdue
        FROM followups f
        JOIN leads l ON f.lead_id = l.id
        LEFT JOIN interaction_titles it ON f.interaction_title_id = it.id
        LEFT JOIN branches b ON l.branch_id = b.id
        LEFT JOIN users u ON l.assigned_counselor_id = u.id
        WHERE f.scheduled_date < CURDATE() AND f.status = 'Pending' AND l.is_archived = 0 AND {$scopeLeads}
        ORDER BY f.scheduled_date ASC
    ");
    $records = $stmt->fetchAll();
} elseif ($queue === 'today_leads') {
    // Fresh leads assigned today with 0 follow-ups
    $stmt = $pdo->query("
        SELECT NULL as followup_id, l.id as lead_id, l.lead_code, l.full_name, l.phone, l.email,
               l.stage, l.priority, l.source, l.preferred_country, l.preferred_course,
               l.assigned_at, l.assigned_counselor_id, b.name as branch_name, u.name as counselor_name
        FROM leads l
        LEFT JOIN branches b ON l.branch_id = b.id
        LEFT JOIN users u ON l.assigned_counselor_id = u.id
        WHERE DATE(l.assigned_at) = CURDATE() AND l.followup_count = 0 AND l.is_archived = 0 AND {$scopeLeads}
        ORDER BY l.assigned_at DESC
    ");
    $records = $stmt->fetchAll();
} elseif ($queue === 'pending_leads') {
    // Leads assigned on previous dates with 0 follow-ups
    $stmt = $pdo->query("
        SELECT NULL as followup_id, l.id as lead_id, l.lead_code, l.full_name, l.phone, l.email,
               l.stage, l.priority, l.source, l.preferred_country, l.preferred_course,
               l.assigned_at, l.assigned_counselor_id, b.name as branch_name, u.name as counselor_name,
               DATEDIFF(CURDATE(), DATE(l.assigned_at)) as days_uncontacted
        FROM leads l
        LEFT JOIN branches b ON l.branch_id = b.id
        LEFT JOIN users u ON l.assigned_counselor_id = u.id
        WHERE DATE(l.assigned_at) < CURDATE() AND l.followup_count = 0 AND l.is_archived = 0 AND {$scopeLeads}
        ORDER BY l.assigned_at ASC
    ");
    $records = $stmt->fetchAll();
}

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <h1 style="font-size:1.5rem; font-weight:700; color:var(--text-heading); margin:0 0 0.35rem 0;">Operational Work Queues</h1>
    <p style="font-size:0.875rem; color:var(--text-muted); margin:0;">
      Counselor daily action surface. Contact leads, execute scheduled follow-ups, and log consultation outcomes to clear queues.
    </p>
  </div>
  <div>
    <span class="badge badge-primary" style="font-size:0.8rem; padding:0.4rem 0.75rem;">
      <i class="fa-solid fa-list-check"></i> Total Action Items: <strong><?= $totalPendingTasks ?></strong>
    </span>
  </div>
</div>

<!-- 4 Primary Operational Queues Tabs -->
<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:1rem; margin-bottom:1.5rem;">
  <!-- Queue 1: Scheduled Follow-ups -->
  <a href="<?= base_url('tasks/index.php?queue=scheduled') ?>" class="card" style="padding:1.2rem; text-decoration:none; border:2px solid <?= $queue === 'scheduled' ? 'var(--primary)' : 'transparent' ?>; background:<?= $queue === 'scheduled' ? 'var(--primary-subtle)' : '#fff' ?>;">
    <div style="display:flex; justify-content:space-between; align-items:center;">
      <span style="font-size:0.8rem; font-weight:700; color:var(--primary); text-transform:uppercase;">Scheduled Follow-ups</span>
      <span class="badge badge-blue" style="font-size:0.85rem; font-weight:800;"><?= $countScheduled ?></span>
    </div>
    <div style="font-size:1.4rem; font-weight:800; color:var(--text-heading); margin-top:6px;">
      Today's Tasks
    </div>
    <div style="font-size:0.75rem; color:var(--text-muted); margin-top:2px;">Appointments scheduled for today</div>
  </a>

  <!-- Queue 2: Pending Follow-ups (Overdue) -->
  <a href="<?= base_url('tasks/index.php?queue=pending_followup') ?>" class="card" style="padding:1.2rem; text-decoration:none; border:2px solid <?= $queue === 'pending_followup' ? 'var(--danger)' : 'transparent' ?>; background:<?= $queue === 'pending_followup' ? '#fee2e2' : '#fff' ?>;">
    <div style="display:flex; justify-content:space-between; align-items:center;">
      <span style="font-size:0.8rem; font-weight:700; color:#b91c1c; text-transform:uppercase;">Pending Follow-ups</span>
      <span class="badge badge-red" style="font-size:0.85rem; font-weight:800;"><?= $countOverdue ?></span>
    </div>
    <div style="font-size:1.4rem; font-weight:800; color:#b91c1c; margin-top:6px;">
      Overdue Calls
    </div>
    <div style="font-size:0.75rem; color:var(--text-muted); margin-top:2px;">Past due date and unresolved</div>
  </a>

  <!-- Queue 3: Today Leads -->
  <a href="<?= base_url('tasks/index.php?queue=today_leads') ?>" class="card" style="padding:1.2rem; text-decoration:none; border:2px solid <?= $queue === 'today_leads' ? 'var(--success)' : 'transparent' ?>; background:<?= $queue === 'today_leads' ? '#dcfce7' : '#fff' ?>;">
    <div style="display:flex; justify-content:space-between; align-items:center;">
      <span style="font-size:0.8rem; font-weight:700; color:#15803d; text-transform:uppercase;">Today Leads</span>
      <span class="badge badge-green" style="font-size:0.85rem; font-weight:800;"><?= $countTodayLeads ?></span>
    </div>
    <div style="font-size:1.4rem; font-weight:800; color:#15803d; margin-top:6px;">
      Fresh Inbound
    </div>
    <div style="font-size:0.75rem; color:var(--text-muted); margin-top:2px;">Assigned today with 0 contact logs</div>
  </a>

  <!-- Queue 4: Pending Leads -->
  <a href="<?= base_url('tasks/index.php?queue=pending_leads') ?>" class="card" style="padding:1.2rem; text-decoration:none; border:2px solid <?= $queue === 'pending_leads' ? '#b45309' : 'transparent' ?>; background:<?= $queue === 'pending_leads' ? '#fef3c7' : '#fff' ?>;">
    <div style="display:flex; justify-content:space-between; align-items:center;">
      <span style="font-size:0.8rem; font-weight:700; color:#b45309; text-transform:uppercase;">Pending Leads</span>
      <span class="badge badge-warning" style="font-size:0.85rem; font-weight:800;"><?= $countPendingLeads ?></span>
    </div>
    <div style="font-size:1.4rem; font-weight:800; color:#b45309; margin-top:6px;">
      Uncontacted Leads
    </div>
    <div style="font-size:0.75rem; color:var(--text-muted); margin-top:2px;">Older leads with 0 contact logs</div>
  </a>
</div>

<!-- Active Queue Action Table -->
<div class="card">
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Student Lead</th>
          <th>Contact & Dial</th>
          <th>Target Study</th>
          <th>Stage & Priority</th>
          <?php if ($queue === 'scheduled'): ?>
            <th>Scheduled Time</th>
            <th>Milestone</th>
          <?php elseif ($queue === 'pending_followup'): ?>
            <th>Originally Due</th>
            <th>Overdue Lag</th>
          <?php elseif ($queue === 'today_leads'): ?>
            <th>Assigned Time</th>
            <th>Source</th>
          <?php elseif ($queue === 'pending_leads'): ?>
            <th>Assigned Date</th>
            <th>Uncontacted Lag</th>
          <?php endif; ?>
          <th style="text-align:right;">Immediate Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($records)): 
          $emptyMsg = match($queue) {
            'scheduled' => "All scheduled calls for today are completed! Fantastic work staying on top of your student consultations.",
            'pending_followup' => "Zero overdue calls! Your follow-up queue is completely up to date.",
            'today_leads' => "All today's fresh inbound inquiries have been contacted. No pending new inquiries.",
            'pending_leads' => "All previous leads have been contacted! No backlog of uncontacted inquiries.",
            default => "No items currently pending in this operational queue."
          };
        ?>
          <tr>
            <td colspan="7" style="text-align:center; padding:3.5rem; color:var(--text-muted);">
              <div style="width:56px; height:56px; border-radius:50%; background:#dcfce7; color:#15803d; display:inline-flex; align-items:center; justify-content:center; font-size:1.8rem; margin-bottom:0.75rem;">
                <i class="fa-solid fa-circle-check"></i>
              </div>
              <div style="font-size:1.15rem; font-weight:800; color:#166534; margin-bottom:0.35rem;">Queue Cleared! 🎉</div>
              <p style="font-size:0.875rem; color:var(--text-muted); max-width:480px; margin:0 auto 1.25rem auto;"><?= $emptyMsg ?></p>
              <div style="display:flex; justify-content:center; gap:0.5rem;">
                <a href="<?= base_url('dashboard/index.php') ?>" class="btn btn-secondary btn-sm"><i class="fa-solid fa-table-columns"></i> Go to Cockpit</a>
                <a href="<?= base_url('leads/index.php') ?>" class="btn btn-primary btn-sm"><i class="fa-solid fa-user-graduate"></i> View All Leads</a>
              </div>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($records as $row): 
            $dialUrl = "tel:" . preg_replace('/[^0-9+]/', '', $row['phone']);
            $waUrl = "https://wa.me/91" . normalize_phone($row['phone']) . "?text=" . urlencode("Hello " . $row['full_name'] . ", this is " . ($currentUser['name'] ?? 'Edroots') . " regarding your higher education admission enquiry.");
          ?>
            <tr>
              <td>
                <div style="font-family:monospace; font-size:0.72rem; font-weight:700; color:var(--primary);">
                  <?= htmlspecialchars($row['lead_code']) ?>
                </div>
                <a href="<?= base_url('leads/view.php?id=' . $row['lead_id']) ?>" style="font-weight:700; color:var(--text-heading); text-decoration:none; font-size:0.92rem;">
                  <?= htmlspecialchars($row['full_name']) ?>
                </a>
                <div style="font-size:0.72rem; color:var(--text-muted); margin-top:2px;">
                  <?= htmlspecialchars($row['branch_name'] ?? 'Branch') ?> • <?= htmlspecialchars($row['counselor_name'] ?? 'Counselor') ?>
                </div>
              </td>
              <td>
                <div style="display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap;">
                  <span style="font-family:monospace; font-weight:700; color:var(--text-heading); font-size:0.85rem;">
                    <?= htmlspecialchars($row['phone']) ?>
                  </span>
                  <a href="<?= $waUrl ?>" target="_blank" class="btn btn-sm" style="background:#25d366; color:#fff; font-size:0.75rem; padding:2px 8px; border-radius:6px; font-weight:700; display:inline-flex; align-items:center; gap:4px;" title="WhatsApp Candidate">
                    <i class="fa-brands fa-whatsapp"></i> Chat
                  </a>
                  <a href="<?= $dialUrl ?>" class="btn btn-outline btn-sm" style="padding:2px 7px; font-size:0.75rem; border-radius:6px;" title="Direct Phone Call">
                    <i class="fa-solid fa-phone" style="font-size:0.75rem; color:var(--primary);"></i>
                  </a>
                </div>
                <?php if (!empty($row['email'])): ?>
                  <div style="font-size:0.72rem; color:var(--text-muted); margin-top:2px;">
                    <?= htmlspecialchars($row['email']) ?>
                  </div>
                <?php endif; ?>
              </td>
              <td>
                <div style="font-weight:600; font-size:0.85rem; color:var(--text-heading);">
                  <?= htmlspecialchars($row['preferred_country']) ?>
                </div>
                <div style="font-size:0.72rem; color:var(--text-muted);">
                  <?= htmlspecialchars($row['preferred_course'] ?: 'General Program') ?>
                </div>
              </td>
              <td>
                <span class="badge badge-primary" style="font-size:0.72rem; font-weight:700;">
                  <?= htmlspecialchars($row['stage']) ?>
                </span>
                <div style="margin-top:3px;">
                  <?= priority_badge($row['priority']) ?>
                </div>
              </td>

              <?php if ($queue === 'scheduled'): ?>
                <td style="font-weight:700; font-size:0.85rem;">
                  <i class="fa-regular fa-clock" style="color:var(--primary); margin-right:3px;"></i>
                  <?= date('h:i A', strtotime($row['scheduled_time'] ?: '10:00:00')) ?>
                </td>
                <td>
                  <span class="badge badge-secondary" style="font-size:0.72rem;">
                    <?= htmlspecialchars($row['interaction_title'] ?: 'Consultation') ?>
                  </span>
                </td>
              <?php elseif ($queue === 'pending_followup'): ?>
                <td style="font-size:0.825rem; font-weight:600;">
                  <?= date('d M Y', strtotime($row['scheduled_date'])) ?>
                </td>
                <td>
                  <span class="badge badge-danger" style="font-size:0.72rem; font-weight:700;">
                    <?= (int)$row['days_overdue'] ?> days overdue
                  </span>
                </td>
              <?php elseif ($queue === 'today_leads'): ?>
                <td style="font-size:0.825rem; font-weight:600;">
                  <?= date('h:i A', strtotime($row['assigned_at'])) ?>
                </td>
                <td>
                  <span class="badge badge-secondary"><?= htmlspecialchars($row['source'] ?? 'Inbound') ?></span>
                </td>
              <?php elseif ($queue === 'pending_leads'): ?>
                <td style="font-size:0.825rem; font-weight:600;">
                  <?= date('d M Y', strtotime($row['assigned_at'])) ?>
                </td>
                <td>
                  <span class="badge badge-warning" style="font-size:0.72rem; font-weight:700;">
                    <?= (int)$row['days_uncontacted'] ?> days idle
                  </span>
                </td>
              <?php endif; ?>

              <td style="text-align:right;">
                <div style="display:flex; justify-content:flex-end; gap:0.4rem;">
                  <button type="button" class="btn btn-primary btn-sm" onclick="openLogModal(<?= (int)$row['lead_id'] ?>, <?= htmlspecialchars(json_encode($row['full_name'])) ?>, <?= htmlspecialchars(json_encode($row['phone'])) ?>, <?= (int)($row['assigned_counselor_id'] ?: $currentUser['id']) ?>)">
                    <i class="fa-solid fa-phone-volume"></i> Log Call
                  </button>
                  <a href="<?= base_url('leads/view.php?id=' . $row['lead_id']) ?>" class="btn btn-secondary btn-sm" title="View Dossier">
                    <i class="fa-solid fa-eye"></i>
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

<!-- Modal for 1-Click Consultation Logging directly from task queues -->
<div class="modal fade" id="logModal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(12,36,60,0.5); align-items:center; justify-content:center;">
  <div class="modal-dialog" style="background:#fff; border-radius:14px; max-width:520px; width:92%; margin:auto; box-shadow:0 20px 50px rgba(0,0,0,0.2); overflow:hidden;">
    <form method="POST" action="<?= base_url('followups/add.php') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="lead_id" id="modal_lead_id" value="">
      <input type="hidden" name="return_url" value="<?= base_url('tasks/index.php?queue=' . $queue) ?>">

      <div class="modal-header" style="padding:1.25rem 1.5rem; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
        <h3 style="font-size:1.15rem; font-weight:700; margin:0; color:var(--text-heading);">
          <i class="fa-solid fa-phone-volume" style="color:var(--primary); margin-right:8px;"></i> Log Consultation Outcome
        </h3>
        <button type="button" onclick="closeLogModal()" style="border:none; background:none; font-size:1.4rem; cursor:pointer; color:var(--text-muted);">&times;</button>
      </div>

      <div class="modal-body" style="padding:1.5rem;">
        <div style="background:var(--bg-subtle); border-radius:8px; padding:0.85rem; margin-bottom:1.25rem; font-size:0.85rem;">
          Candidate: <strong id="modal_candidate_name">—</strong> • <span id="modal_candidate_phone">—</span>
        </div>

        <div class="form-group" style="margin-bottom:1rem;">
          <label class="form-label">Interaction Title / Milestone <span style="color:red;">*</span></label>
          <select name="interaction_title_id" id="modal_interaction_title" class="form-control" required onchange="onModalTitleChange(this)">
            <option value="">Select Milestone...</option>
            <?php foreach ($interactionTitles as $it): ?>
              <option value="<?= $it['id'] ?>" data-max-days="<?= $it['max_duration_days'] ?>">
                <?= htmlspecialchars($it['title']) ?> (Max: <?= $it['max_duration_days'] ?> days)
              </option>
            <?php endforeach; ?>
          </select>
          <small id="modal_hint" style="font-size:0.75rem; color:var(--text-muted); display:block; margin-top:2px;">
            Title enforces maximum allowable next follow-up interval.
          </small>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; margin-bottom:1rem;">
          <div class="form-group">
            <label class="form-label">Next Date <span style="color:red;">*</span></label>
            <input type="date" name="next_followup_date" id="modal_next_date" class="form-control" required min="<?= date('Y-m-d') ?>" onchange="checkWorkload(this.value)">
          </div>
          <div class="form-group">
            <label class="form-label">Next Time</label>
            <input type="time" name="next_followup_time" class="form-control" value="10:00">
          </div>
        </div>

        <div id="modal_workload_box" style="display:none; background:#e0e7ff; border-radius:6px; padding:0.6rem 0.85rem; font-size:0.8rem; color:#3730a3; margin-bottom:1rem;">
          <i class="fa-solid fa-chart-line"></i> Counselor workload on selected date: <strong id="modal_workload_val">0</strong> follow-ups scheduled.
        </div>

        <div class="form-group" style="margin-bottom:0.5rem;">
          <label class="form-label">Consultation Notes & Next Steps</label>
          <textarea name="notes" class="form-control" rows="3" placeholder="Candidate agreed to submit 12th marksheet and passport copy tomorrow..." required></textarea>
        </div>
      </div>

      <div class="modal-footer" style="padding:1rem 1.5rem; border-top:1px solid var(--border-color); display:flex; justify-content:flex-end; gap:0.5rem;">
        <button type="button" onclick="closeLogModal()" class="btn btn-secondary btn-sm">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm">
          <i class="fa-solid fa-check"></i> Record Consultation & Clear Queue
        </button>
      </div>
    </form>
  </div>
</div>

<script>
let currentCounselorId = <?= (int)$currentUser['id'] ?>;

function openLogModal(leadId, name, phone, counselorId) {
  document.getElementById('modal_lead_id').value = leadId;
  document.getElementById('modal_candidate_name').innerText = name;
  document.getElementById('modal_candidate_phone').innerText = phone;
  currentCounselorId = counselorId;
  document.getElementById('logModal').style.display = 'flex';
}

function closeLogModal() {
  document.getElementById('logModal').style.display = 'none';
}

function onModalTitleChange(sel) {
  const opt = sel.options[sel.selectedIndex];
  const maxDays = opt.getAttribute('data-max-days');
  const dateInput = document.getElementById('modal_next_date');
  const hint = document.getElementById('modal_hint');

  if (maxDays) {
    const today = new Date();
    const maxDate = new Date();
    maxDate.setDate(today.getDate() + parseInt(maxDays));

    const yyyy = maxDate.getFullYear();
    const mm = String(maxDate.getMonth() + 1).padStart(2, '0');
    const dd = String(maxDate.getDate()).padStart(2, '0');
    const maxDateStr = `${yyyy}-${mm}-${dd}`;

    dateInput.max = maxDateStr;
    dateInput.value = maxDateStr;
    hint.innerHTML = `<span style="color:var(--primary); font-weight:600;"><i class="fa-solid fa-circle-check"></i> Max interval allowed: ${maxDays} days (Latest: ${maxDate.toLocaleDateString('en-GB')})</span>`;
    checkWorkload(maxDateStr);
  }
}

function checkWorkload(dateVal) {
  if (!dateVal) return;
  fetch(`<?= base_url('tasks/workload_api.php') ?>?counselor_id=${currentCounselorId}&date=${dateVal}`)
    .then(r => r.json())
    .then(d => {
      const box = document.getElementById('modal_workload_box');
      if (d.success) {
        document.getElementById('modal_workload_val').innerText = d.count;
        box.style.display = 'block';
      } else {
        box.style.display = 'none';
      }
    }).catch(() => {});
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
