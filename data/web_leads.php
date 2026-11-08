<?php
/**
 * Website & Social Leads Staging Queue
 * University-Level Education Consultancy CRM (EdrootsCRM)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_permission('web_leads', 'view');

$currentUser = current_user();
$pageTitle = 'Website & Social Leads';
$activePage = 'web_leads';

// Handle Actions (Assign to Active Leads or Disqualify)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? '');
    $action = $_POST['action'] ?? '';
    $webLeadId = (int)($_POST['web_lead_id'] ?? 0);

    if ($action === 'assign_lead') {
        require_permission('web_leads', 'assign');
        $branchId = (int)($_POST['branch_id'] ?? 0);
        $counselorId = (int)($_POST['counselor_id'] ?? 0);
        $rawPhone = trim($_POST['phone'] ?? '');

        $stmt = $pdo->prepare("SELECT * FROM web_leads WHERE id = ? LIMIT 1");
        $stmt->execute([$webLeadId]);
        $wRow = $stmt->fetch();

        if (!$wRow) {
            set_flash('danger', 'Web lead not found.');
            redirect('data/web_leads.php');
        }

        if ($wRow['status'] === 'Assigned') {
            set_flash('warning', 'This digital lead has already been converted to an active lead.');
            redirect('leads/view.php?id=' . $wRow['assigned_lead_id']);
        }

        if ($branchId <= 0 || $counselorId <= 0) {
            set_flash('danger', 'Please select both an operational branch and a counselor.');
            redirect('data/web_leads.php');
        }

        $phoneToUse = !empty($rawPhone) ? $rawPhone : $wRow['phone'];
        $cleanPhone = normalize_phone($phoneToUse);

        // Check duplicate in active leads
        $existingLead = check_phone_duplicate($cleanPhone);
        if ($existingLead) {
            set_flash('danger', "Duplicate Blocked: An active lead with phone <strong>{$cleanPhone}</strong> already exists (" . htmlspecialchars($existingLead['full_name']) . " - " . htmlspecialchars($existingLead['lead_code']) . ").");
            redirect('data/web_leads.php');
        }

        // Generate Lead Code
        $currentYear = date('Y');
        $stmtCount = $pdo->query("SELECT COUNT(*) FROM leads");
        $seq = (int)$stmtCount->fetchColumn() + 1;
        $leadCode = sprintf("EDR-%s-%04d", $currentYear, $seq);

        try {
            $pdo->beginTransaction();

            $stmtLead = $pdo->prepare("
                INSERT INTO leads (
                    lead_code, full_name, email, phone, branch_id, assigned_counselor_id,
                    stage, source, priority, preferred_country, preferred_course,
                    intake_year, notes, assigned_at, created_by
                ) VALUES (
                    ?, ?, ?, ?, ?, ?,
                    'ENQUIRY', ?, 'High', ?, ?,
                    ?, ?, NOW(), ?
                )
            ");
            $stmtLead->execute([
                $leadCode,
                $wRow['full_name'],
                $wRow['email'],
                $cleanPhone,
                $branchId,
                $counselorId,
                $wRow['source'],
                $wRow['target_country'] ?: 'United Kingdom',
                $wRow['target_course'] ?: 'Undergraduate / Postgraduate',
                $wRow['intake'] ?: 'September 2026',
                $wRow['notes'] ? $wRow['notes'] : "Inbound lead captured via {$wRow['source']}",
                $currentUser['id']
            ]);
            $newLeadId = $pdo->lastInsertId();

            // Update web_leads record
            $updStmt = $pdo->prepare("
                UPDATE web_leads 
                SET status = 'Assigned', phone = ?, branch_id = ?, assigned_counselor_id = ?,
                    assigned_lead_id = ?, assigned_at = NOW()
                WHERE id = ?
            ");
            $updStmt->execute([$cleanPhone, $branchId, $counselorId, $newLeadId, $webLeadId]);

            // Stage history
            $stageStmt = $pdo->prepare("
                INSERT INTO lead_stage_history (lead_id, from_stage, to_stage, changed_by, notes)
                VALUES (?, NULL, 'ENQUIRY', ?, 'Lead imported from digital queue: ' ?)
            ");
            $stageStmt->execute([$newLeadId, $currentUser['id'], $wRow['source']]);

            // Notification
            $notifStmt = $pdo->prepare("
                INSERT INTO notifications (user_id, title, message, link, status)
                VALUES (?, 'Digital Web Lead Assigned', ?, ?, 'Not Attended')
            ");
            $notifStmt->execute([
                $counselorId,
                "Inbound lead {$wRow['full_name']} from {$wRow['source']} has been assigned to your caseload.",
                "leads/view.php?id={$newLeadId}"
            ]);

            log_activity(
                $currentUser['id'],
                $newLeadId,
                'Digital Lead Staged & Assigned',
                "Staged {$wRow['source']} lead {$wRow['full_name']} ({$cleanPhone}) assigned to counselor ID {$counselorId} as {$leadCode}."
            );

            $pdo->commit();
            set_flash('success', "Lead <strong>" . htmlspecialchars($wRow['full_name']) . "</strong> assigned and converted to Active Lead <strong>{$leadCode}</strong>.");
            redirect('leads/view.php?id=' . $newLeadId);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            set_flash('danger', 'Error staging lead: ' . htmlspecialchars($e->getMessage()));
            redirect('data/web_leads.php');
        }
    } elseif ($action === 'disqualify') {
        require_permission('web_leads', 'edit');
        $disqualifyReason = trim($_POST['disqualify_reason'] ?? 'Disqualified by admissions office');
        $dStmt = $pdo->prepare("UPDATE web_leads SET status = 'Disqualified', notes = CONCAT(COALESCE(notes, ''), ' [Disqualified: ', ?, ']') WHERE id = ?");
        $dStmt->execute([$disqualifyReason, $webLeadId]);
        set_flash('info', 'Web lead marked as Disqualified.');
        redirect('data/web_leads.php');
    }
}

// Filters
$statusFilter = trim($_GET['status'] ?? 'Pending');
$sourceFilter = trim($_GET['source'] ?? '');

$where = ["1=1"];
$params = [];

if ($statusFilter !== 'All') {
    $where[] = "w.status = ?";
    $params[] = $statusFilter;
}

if (!empty($sourceFilter)) {
    $where[] = "w.source = ?";
    $params[] = $sourceFilter;
}

// Scoping: branch manager only sees assigned to their branch or unassigned
if (is_branch_manager()) {
    $where[] = "(w.branch_id = ? OR w.branch_id IS NULL)";
    $params[] = (int)$currentUser['branch_id'];
}

$whereSql = implode(' AND ', $where);

$branches = $pdo->query("SELECT id, name, city FROM branches WHERE status = 'Active' ORDER BY name ASC")->fetchAll();

$counselorsStmt = $pdo->prepare("
    SELECT u.id, u.name, u.branch_id, b.name AS branch_name 
    FROM users u 
    JOIN roles r ON u.role_id = r.id 
    LEFT JOIN branches b ON u.branch_id = b.id 
    WHERE r.name = 'counselor' AND u.status = 'Active'
    ORDER BY u.name ASC
");
$counselorsStmt->execute();
$counselors = $counselorsStmt->fetchAll();

$sql = "
    SELECT w.*, b.name AS branch_name, u.name AS counselor_name, l.lead_code
    FROM web_leads w
    LEFT JOIN branches b ON w.branch_id = b.id
    LEFT JOIN users u ON w.assigned_counselor_id = u.id
    LEFT JOIN leads l ON w.assigned_lead_id = l.id
    WHERE {$whereSql}
    ORDER BY w.id DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$webLeads = $stmt->fetchAll();

$pendingCount = (int)$pdo->query("SELECT COUNT(*) FROM web_leads WHERE status = 'Pending'")->fetchColumn();
$assignedCount = (int)$pdo->query("SELECT COUNT(*) FROM web_leads WHERE status = 'Assigned'")->fetchColumn();
$disqualifiedCount = (int)$pdo->query("SELECT COUNT(*) FROM web_leads WHERE status = 'Disqualified'")->fetchColumn();

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <h1 style="font-size:1.5rem; font-weight:700; color:var(--text-heading); margin:0 0 0.35rem 0;">Website & Social Leads Staging</h1>
    <p style="font-size:0.875rem; color:var(--text-muted); margin:0;">
      Incoming inquiries from institutional landing pages, Meta/Facebook Ads, Instagram campaigns, and Google Ads. Triage, verify, and route to branch counselors.
    </p>
  </div>
  <div style="display:flex; gap:0.5rem; align-items:center;">
    <span class="badge badge-primary" style="font-size:0.8rem; padding:0.4rem 0.75rem;">
      <i class="fa-solid fa-bolt"></i> Live Inbound Queue
    </span>
  </div>
</div>

<!-- KPI Mini Cards -->
<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:1rem; margin-bottom:1.5rem;">
  <div class="card" style="padding:1.2rem; display:flex; align-items:center; gap:1rem;">
    <div style="width:48px; height:48px; border-radius:12px; background:#fef3c7; color:#b45309; display:flex; align-items:center; justify-content:center; font-size:1.3rem;">
      <i class="fa-solid fa-clock-rotate-left"></i>
    </div>
    <div>
      <div style="font-size:1.5rem; font-weight:800; color:#b45309; line-height:1.1;"><?= $pendingCount ?></div>
      <div style="font-size:0.8rem; color:var(--text-muted); font-weight:500;">Pending Assignment</div>
    </div>
  </div>
  <div class="card" style="padding:1.2rem; display:flex; align-items:center; gap:1rem;">
    <div style="width:48px; height:48px; border-radius:12px; background:#dcfce7; color:#15803d; display:flex; align-items:center; justify-content:center; font-size:1.3rem;">
      <i class="fa-solid fa-circle-check"></i>
    </div>
    <div>
      <div style="font-size:1.5rem; font-weight:800; color:#15803d; line-height:1.1;"><?= $assignedCount ?></div>
      <div style="font-size:0.8rem; color:var(--text-muted); font-weight:500;">Assigned to Leads</div>
    </div>
  </div>
  <div class="card" style="padding:1.2rem; display:flex; align-items:center; gap:1rem;">
    <div style="width:48px; height:48px; border-radius:12px; background:#fee2e2; color:#b91c1c; display:flex; align-items:center; justify-content:center; font-size:1.3rem;">
      <i class="fa-solid fa-ban"></i>
    </div>
    <div>
      <div style="font-size:1.5rem; font-weight:800; color:#b91c1c; line-height:1.1;"><?= $disqualifiedCount ?></div>
      <div style="font-size:0.8rem; color:var(--text-muted); font-weight:500;">Disqualified / Spam</div>
    </div>
  </div>
</div>

<!-- Filters Bar -->
<div class="card" style="margin-bottom:1.5rem; padding:1.25rem;">
  <form method="GET" action="<?= base_url('data/web_leads.php') ?>" style="display:flex; flex-wrap:wrap; gap:0.75rem; align-items:center;">
    <div style="min-width:180px;">
      <select name="status" class="form-control">
        <option value="Pending" <?= $statusFilter === 'Pending' ? 'selected' : '' ?>>Pending Staging Only</option>
        <option value="Assigned" <?= $statusFilter === 'Assigned' ? 'selected' : '' ?>>Assigned Leads</option>
        <option value="Disqualified" <?= $statusFilter === 'Disqualified' ? 'selected' : '' ?>>Disqualified</option>
        <option value="All" <?= $statusFilter === 'All' ? 'selected' : '' ?>>All Inbound Records</option>
      </select>
    </div>

    <div style="min-width:180px;">
      <select name="source" class="form-control">
        <option value="">All Marketing Channels</option>
        <option value="Website" <?= $sourceFilter === 'Website' ? 'selected' : '' ?>>Website Landing Page</option>
        <option value="Meta Ads" <?= $sourceFilter === 'Meta Ads' ? 'selected' : '' ?>>Meta Ads (Facebook)</option>
        <option value="Instagram" <?= $sourceFilter === 'Instagram' ? 'selected' : '' ?>>Instagram Campaign</option>
        <option value="Google Ads" <?= $sourceFilter === 'Google Ads' ? 'selected' : '' ?>>Google Ads</option>
      </select>
    </div>

    <button type="submit" class="btn btn-primary btn-sm">
      <i class="fa-solid fa-filter"></i> Filter
    </button>
    <a href="<?= base_url('data/web_leads.php') ?>" class="btn btn-secondary btn-sm" title="Reset Filters">
      <i class="fa-solid fa-rotate-left"></i>
    </a>
  </form>
</div>

<!-- Leads Staging Table -->
<div class="card">
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Source Channel</th>
          <th>Candidate Details</th>
          <th>Phone & Email</th>
          <th>Study Preference</th>
          <th>Target Intake</th>
          <th>Status</th>
          <th style="text-align:right;">Assignment & Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($webLeads)): ?>
          <tr>
            <td colspan="7" style="text-align:center; padding:3rem; color:var(--text-muted);">
              <i class="fa-solid fa-inbox" style="font-size:2rem; margin-bottom:0.75rem; display:block; color:var(--text-light);"></i>
              No inbound digital leads currently match this filter.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($webLeads as $lead): 
            $sourceIcon = match($lead['source']) {
                'Meta Ads' => 'fa-brands fa-facebook',
                'Instagram' => 'fa-brands fa-instagram',
                'Google Ads' => 'fa-brands fa-google',
                default => 'fa-solid fa-globe'
            };
            $sourceColor = match($lead['source']) {
                'Meta Ads' => '#1877f2',
                'Instagram' => '#e1306c',
                'Google Ads' => '#ea4335',
                default => 'var(--primary)'
            };
          ?>
            <tr>
              <td>
                <div style="display:flex; align-items:center; gap:0.5rem;">
                  <i class="<?= $sourceIcon ?>" style="color:<?= $sourceColor ?>; font-size:1.1rem;"></i>
                  <div>
                    <strong style="font-size:0.85rem; color:var(--text-heading); display:block;"><?= htmlspecialchars($lead['source']) ?></strong>
                    <span style="font-size:0.72rem; color:var(--text-muted);"><?= time_ago($lead['created_at']) ?></span>
                  </div>
                </div>
              </td>
              <td>
                <div style="font-weight:700; color:var(--text-heading); font-size:0.9rem;">
                  <?= htmlspecialchars($lead['full_name']) ?>
                </div>
                <?php if (!empty($lead['city'])): ?>
                  <div style="font-size:0.75rem; color:var(--text-muted);">
                    <i class="fa-solid fa-location-dot" style="font-size:0.7rem;"></i> <?= htmlspecialchars($lead['city']) ?>
                  </div>
                <?php endif; ?>
              </td>
              <td>
                <div style="font-family:monospace; font-weight:700; color:var(--primary); font-size:0.85rem;">
                  <?= htmlspecialchars($lead['phone']) ?>
                </div>
                <?php if (!empty($lead['email'])): ?>
                  <div style="font-size:0.75rem; color:var(--text-muted);">
                    <?= htmlspecialchars($lead['email']) ?>
                  </div>
                <?php endif; ?>
              </td>
              <td>
                <div style="font-weight:600; font-size:0.85rem; color:var(--text-heading);">
                  <?= htmlspecialchars($lead['target_country'] ?: 'Global') ?>
                </div>
                <div style="font-size:0.75rem; color:var(--text-muted);">
                  <?= htmlspecialchars($lead['target_course'] ?: 'Any') ?>
                </div>
              </td>
              <td style="font-weight:600; font-size:0.85rem;">
                <?= htmlspecialchars($lead['intake'] ?: 'Immediate') ?>
              </td>
              <td>
                <?php if ($lead['status'] === 'Pending'): ?>
                  <span class="badge badge-warning" style="font-weight:700;">PENDING</span>
                <?php elseif ($lead['status'] === 'Assigned'): ?>
                  <span class="badge badge-success" style="font-weight:700;">ASSIGNED</span>
                  <?php if (!empty($lead['lead_code'])): ?>
                    <a href="<?= base_url('leads/view.php?id=' . $lead['assigned_lead_id']) ?>" style="display:block; font-size:0.72rem; color:var(--primary); font-family:monospace; margin-top:2px; text-decoration:none;">
                      <?= htmlspecialchars($lead['lead_code']) ?> &rarr;
                    </a>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="badge badge-danger">DISQUALIFIED</span>
                <?php endif; ?>
              </td>
              <td style="text-align:right;">
                <?php if ($lead['status'] === 'Pending' && can('web_leads', 'assign')): ?>
                  <button type="button" class="btn btn-primary btn-sm" onclick="openAssignModal(<?= htmlspecialchars(json_encode($lead)) ?>)">
                    <i class="fa-solid fa-user-check"></i> Assign to Counselor
                  </button>
                <?php elseif ($lead['status'] === 'Assigned' && !empty($lead['assigned_lead_id'])): ?>
                  <a href="<?= base_url('leads/view.php?id=' . $lead['assigned_lead_id']) ?>" class="btn btn-secondary btn-sm">
                    <i class="fa-solid fa-eye"></i> View Lead
                  </a>
                <?php else: ?>
                  <span style="font-size:0.75rem; color:var(--text-light);">—</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal for Staging & Assigning Digital Web Lead -->
<div class="modal fade" id="assignModal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(12,36,60,0.5); align-items:center; justify-content:center;">
  <div class="modal-dialog" style="background:#fff; border-radius:14px; max-width:540px; width:92%; margin:auto; box-shadow:0 20px 50px rgba(0,0,0,0.2); overflow:hidden;">
    <form method="POST" action="<?= base_url('data/web_leads.php') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="assign_lead">
      <input type="hidden" name="web_lead_id" id="assign_web_lead_id" value="">
      
      <div class="modal-header" style="padding:1.25rem 1.5rem; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
        <h3 style="font-size:1.15rem; font-weight:700; margin:0; color:var(--text-heading);">
          <i class="fa-solid fa-bolt" style="color:var(--primary); margin-right:8px;"></i> Assign Digital Inbound Lead
        </h3>
        <button type="button" onclick="closeAssignModal()" style="border:none; background:none; font-size:1.4rem; cursor:pointer; color:var(--text-muted);">&times;</button>
      </div>

      <div class="modal-body" style="padding:1.5rem;">
        <div style="background:var(--bg-subtle); border-radius:8px; padding:0.85rem; margin-bottom:1.25rem; font-size:0.85rem;">
          <div style="font-weight:700; color:var(--text-heading);" id="assign_student_name">—</div>
          <div style="color:var(--text-muted);" id="assign_student_meta">—</div>
        </div>

        <div class="form-group" style="margin-bottom:1rem;">
          <label class="form-label">Phone Number (Editable for Verification)</label>
          <input type="text" name="phone" id="assign_phone" class="form-control" required>
          <small style="color:var(--text-muted); font-size:0.75rem;">Normalize into standard 10-digit number if submitted with typos.</small>
        </div>

        <div class="form-group" style="margin-bottom:1rem;">
          <label class="form-label">Allocated Branch <span style="color:red;">*</span></label>
          <select name="branch_id" id="assign_branch_id" class="form-control" required onchange="filterCounselors(this.value)">
            <option value="">Select Branch...</option>
            <?php foreach ($branches as $b): ?>
              <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group" style="margin-bottom:1rem;">
          <label class="form-label">Assign to Counselor <span style="color:red;">*</span></label>
          <select name="counselor_id" id="assign_counselor_id" class="form-control" required>
            <option value="">Select Counselor...</option>
            <?php foreach ($counselors as $c): ?>
              <option value="<?= $c['id'] ?>" data-branch="<?= $c['branch_id'] ?>">
                <?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['branch_name'] ?? 'Counselor') ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="modal-footer" style="padding:1rem 1.5rem; border-top:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
        <button type="button" class="btn btn-outline-danger btn-sm" onclick="disqualifyCurrentLead()">
          <i class="fa-solid fa-ban"></i> Disqualify
        </button>
        <div style="display:flex; gap:0.5rem;">
          <button type="button" onclick="closeAssignModal()" class="btn btn-secondary btn-sm">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-check"></i> Assign & Add to Active Leads
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
function openAssignModal(lead) {
  document.getElementById('assign_web_lead_id').value = lead.id;
  document.getElementById('assign_student_name').innerText = lead.full_name;
  document.getElementById('assign_student_meta').innerText = lead.source + ' • ' + (lead.target_country || 'Global') + ' • ' + (lead.intake || 'Immediate');
  document.getElementById('assign_phone').value = lead.phone;
  
  if (lead.branch_id) {
    document.getElementById('assign_branch_id').value = lead.branch_id;
    filterCounselors(lead.branch_id);
  } else {
    document.getElementById('assign_branch_id').selectedIndex = 0;
  }
  
  document.getElementById('assignModal').style.display = 'flex';
}

function closeAssignModal() {
  document.getElementById('assignModal').style.display = 'none';
}

function filterCounselors(branchId) {
  const sel = document.getElementById('assign_counselor_id');
  sel.querySelectorAll('option').forEach((opt, idx) => {
    if (idx === 0) return;
    const bId = opt.getAttribute('data-branch');
    if (!branchId || bId === branchId) {
      opt.style.display = '';
    } else {
      opt.style.display = 'none';
      if (opt.selected) sel.selectedIndex = 0;
    }
  });
}

function disqualifyCurrentLead() {
  if (confirm('Are you sure you want to disqualify this inbound lead as spam or invalid?')) {
    const f = document.createElement('form');
    f.method = 'POST';
    f.action = '<?= base_url('data/web_leads.php') ?>';
    f.innerHTML = `
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="disqualify">
      <input type="hidden" name="web_lead_id" value="${document.getElementById('assign_web_lead_id').value}">
      <input type="hidden" name="disqualify_reason" value="Disqualified via digital staging queue">
    `;
    document.body.appendChild(f);
    f.submit();
  }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
