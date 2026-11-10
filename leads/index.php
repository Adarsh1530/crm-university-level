<?php
/**
 * Active Leads Directory
 * University-Level Education Consultancy CRM (EdrootsCRM)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_permission('leads', 'view');

$currentUser = current_user();
$pageTitle = 'Active Leads';
$activePage = 'leads';

// Filter inputs
$search = trim($_GET['search'] ?? '');
$stageFilter = trim($_GET['stage'] ?? '');
$priorityFilter = trim($_GET['priority'] ?? '');
$sourceFilter = trim($_GET['source'] ?? '');
$counselorFilter = isset($_GET['counselor_id']) ? (int)$_GET['counselor_id'] : 0;
$branchFilter = isset($_GET['branch_id']) ? (int)$_GET['branch_id'] : 0;

// Export CSV handler (Strictly restricted from Counselors)
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    if (!can('leads', 'export')) {
        set_flash('danger', 'Access Denied: Counselors are strictly restricted from exporting student databases.');
        redirect('leads/index.php');
    }

    $scopeClause = get_crm_scope_clause('l', 'assigned_counselor_id', 'branch_id');
    $stmtExp = $pdo->query("
        SELECT l.lead_code, l.full_name, l.phone, l.email, b.name as branch, u.name as counselor,
               l.stage, l.priority, l.source, l.preferred_country, l.preferred_course, l.created_at
        FROM leads l
        LEFT JOIN branches b ON l.branch_id = b.id
        LEFT JOIN users u ON l.assigned_counselor_id = u.id
        WHERE l.is_archived = 0 AND {$scopeClause}
        ORDER BY l.id DESC
    ");
    $expRows = $stmtExp->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="Active_Leads_Export_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Lead ID', 'Student Name', 'Canonical Phone', 'Email', 'Branch', 'Assigned Counselor', 'Stage', 'Priority', 'Source', 'Target Country', 'Target Course', 'Created At']);
    foreach ($expRows as $er) {
        fputcsv($out, $er);
    }
    fclose($out);
    exit;
}

// Scoping
$scopeClause = get_crm_scope_clause('l', 'assigned_counselor_id', 'branch_id');
$where = ["l.is_archived = 0", $scopeClause];
$params = [];

if (!empty($search)) {
    $where[] = "(l.full_name LIKE ? OR l.lead_code LIKE ? OR l.phone LIKE ? OR l.email LIKE ? OR l.college_name LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if (!empty($stageFilter)) {
    $where[] = "l.stage = ?";
    $params[] = $stageFilter;
}

if (!empty($priorityFilter)) {
    $where[] = "l.priority = ?";
    $params[] = $priorityFilter;
}

if (!empty($sourceFilter)) {
    $where[] = "l.source = ?";
    $params[] = $sourceFilter;
}

if ($counselorFilter > 0 && (is_super_admin() || is_branch_manager())) {
    $where[] = "l.assigned_counselor_id = ?";
    $params[] = $counselorFilter;
}

if ($branchFilter > 0 && is_super_admin()) {
    $where[] = "l.branch_id = ?";
    $params[] = $branchFilter;
}

$whereSql = implode(' AND ', $where);

// Stage breakdown counts for tab pills
$stageCountsStmt = $pdo->query("
    SELECT l.stage, COUNT(*) as count 
    FROM leads l 
    WHERE l.is_archived = 0 AND {$scopeClause} 
    GROUP BY l.stage
");
$stageCounts = $stageCountsStmt->fetchAll(PDO::FETCH_KEY_PAIR);
$totalScopedLeads = array_sum($stageCounts);

// Branches and Counselors for filter dropdowns & reassign modals
$branches = $pdo->query("SELECT id, name, city FROM branches WHERE status = 'Active' ORDER BY name ASC")->fetchAll();

$cslSql = "
    SELECT u.id, u.name, u.branch_id, b.name AS branch_name,
           (SELECT COUNT(*) FROM leads WHERE assigned_counselor_id = u.id AND is_archived = 0) AS active_caseload
    FROM users u 
    JOIN roles r ON u.role_id = r.id 
    LEFT JOIN branches b ON u.branch_id = b.id 
    WHERE r.name = 'counselor' AND u.status = 'Active'
";
if (is_branch_manager()) {
    $cslSql .= " AND u.branch_id = " . (int)$currentUser['branch_id'];
}
$cslSql .= " ORDER BY u.name ASC";
$counselors = $pdo->query($cslSql)->fetchAll();

// Main Lead Query
$sql = "
    SELECT l.*, b.name AS branch_name, b.code AS branch_code, u.name AS counselor_name
    FROM leads l
    LEFT JOIN branches b ON l.branch_id = b.id
    LEFT JOIN users u ON l.assigned_counselor_id = u.id
    WHERE {$whereSql}
    ORDER BY l.id DESC
    LIMIT 150
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$leads = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <h1 style="font-size:1.5rem; font-weight:700; color:var(--text-heading); margin:0 0 0.35rem 0;">Active Leads</h1>
    <p style="font-size:0.875rem; color:var(--text-muted); margin:0;">
      Centralized student directory across 6 standard admission stages. 
      <?php if (is_counselor()): ?>
        Showing your <strong>strictly assigned caseload</strong> only.
      <?php elseif (is_branch_manager()): ?>
        Admissions management operational view across all active counselor caseloads.
      <?php else: ?>
        Global view across all active admissions pipelines and consultancy records.
      <?php endif; ?>
    </p>
  </div>
  <div style="display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap;">
    <?php if (can('leads', 'export')): ?>
      <a href="<?= base_url('leads/index.php?' . http_build_query(array_merge($_GET, ['export' => 'csv']))) ?>" class="btn btn-secondary btn-sm">
        <i class="fa-solid fa-file-csv"></i> Export CSV
      </a>
    <?php endif; ?>
    <?php if (can('leads', 'reassign')): ?>
      <button type="button" class="btn btn-outline btn-sm" onclick="openBulkReassignModal()">
        <i class="fa-solid fa-users-gear"></i> Bulk Reassign
      </button>
    <?php endif; ?>
    <?php if (can('leads', 'add')): ?>
      <a href="<?= base_url('leads/add.php') ?>" class="btn btn-primary btn-sm">
        <i class="fa-solid fa-user-plus"></i> Add New Lead
      </a>
    <?php endif; ?>
  </div>
</div>

<!-- 6 Stages Filter Tabs / Pills -->
<div style="display:flex; gap:0.5rem; overflow-x:auto; padding-bottom:0.75rem; margin-bottom:1.25rem;">
  <a href="<?= base_url('leads/index.php') ?>" class="btn btn-sm <?= empty($stageFilter) ? 'btn-primary' : 'btn-secondary' ?>" style="white-space:nowrap; border-radius:20px; padding:0.4rem 1rem;">
    All Stages (<?= $totalScopedLeads ?>)
  </a>
  <?php 
  $stagesList = ['ENQUIRY', 'CUSTOMER', 'APPLICATION', 'OFFER RECEIVED', 'TUITION FEES PAID', 'ENROLLED'];
  foreach ($stagesList as $stg): 
    $cnt = $stageCounts[$stg] ?? 0;
  ?>
    <a href="<?= base_url('leads/index.php?stage=' . urlencode($stg)) ?>" class="btn btn-sm <?= ($stageFilter === $stg) ? 'btn-primary' : 'btn-secondary' ?>" style="white-space:nowrap; border-radius:20px; padding:0.4rem 0.9rem;">
      <?= htmlspecialchars($stg) ?> (<?= $cnt ?>)
    </a>
  <?php endforeach; ?>
</div>

<!-- Filter Bar -->
<div class="card" style="margin-bottom:1.5rem; padding:1.25rem;">
  <form method="GET" action="<?= base_url('leads/index.php') ?>" style="display:flex; flex-wrap:wrap; gap:0.75rem; align-items:center;">
    <?php if (!empty($stageFilter)): ?>
      <input type="hidden" name="stage" value="<?= htmlspecialchars($stageFilter) ?>">
    <?php endif; ?>

    <div style="flex:1; min-width:240px; position:relative;">
      <input type="text" name="search" class="form-control" placeholder="Search by name, code, phone, email, college..." value="<?= htmlspecialchars($search) ?>" style="padding-left:2.4rem;">
      <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-light);"></i>
    </div>

    <div style="min-width:140px;">
      <select name="priority" class="form-control">
        <option value="">All Priorities</option>
        <option value="High" <?= $priorityFilter === 'High' ? 'selected' : '' ?>>High Priority</option>
        <option value="Moderate" <?= $priorityFilter === 'Moderate' ? 'selected' : '' ?>>Moderate</option>
        <option value="Low" <?= $priorityFilter === 'Low' ? 'selected' : '' ?>>Low</option>
      </select>
    </div>

    <div style="min-width:150px;">
      <select name="source" class="form-control">
        <option value="">All Sources</option>
        <option value="College Data" <?= $sourceFilter === 'College Data' ? 'selected' : '' ?>>College Data</option>
        <option value="Website" <?= $sourceFilter === 'Website' ? 'selected' : '' ?>>Website</option>
        <option value="Meta Ads" <?= $sourceFilter === 'Meta Ads' ? 'selected' : '' ?>>Meta Ads</option>
        <option value="Instagram" <?= $sourceFilter === 'Instagram' ? 'selected' : '' ?>>Instagram</option>
        <option value="Google Ads" <?= $sourceFilter === 'Google Ads' ? 'selected' : '' ?>>Google Ads</option>
        <option value="Referral" <?= $sourceFilter === 'Referral' ? 'selected' : '' ?>>Referral</option>
        <option value="Walk-in" <?= $sourceFilter === 'Walk-in' ? 'selected' : '' ?>>Walk-in</option>
      </select>
    </div>

    <?php if (is_super_admin()): ?>
      <div style="min-width:150px;">
        <select name="branch_id" class="form-control">
          <option value="0">All Branches</option>
          <?php foreach ($branches as $b): ?>
            <option value="<?= $b['id'] ?>" <?= $branchFilter === (int)$b['id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    <?php endif; ?>

    <?php if (is_super_admin() || is_branch_manager()): ?>
      <div style="min-width:160px;">
        <select name="counselor_id" class="form-control">
          <option value="0">All Counselors</option>
          <?php foreach ($counselors as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $counselorFilter === (int)$c['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($c['name']) ?> (<?= $c['active_caseload'] ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    <?php endif; ?>

    <button type="submit" class="btn btn-primary btn-sm">
      <i class="fa-solid fa-filter"></i> Apply
    </button>
    <a href="<?= base_url('leads/index.php') ?>" class="btn btn-secondary btn-sm" title="Reset Filters">
      <i class="fa-solid fa-rotate-left"></i>
    </a>
  </form>
</div>

<!-- Main Active Leads Table -->
<div class="card">
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <?php if (can('leads', 'reassign')): ?>
            <th style="width:36px;"><input type="checkbox" id="selectAllCheckbox" onclick="toggleSelectAll(this)"></th>
          <?php endif; ?>
          <th>Lead Code & Student</th>
          <th>Contact & Phone</th>
          <th>Destination & Course</th>
          <th>Admission Stage</th>
          <th>Priority</th>
          <th>Branch & Counselor</th>
          <th>Follow-ups</th>
          <th style="text-align:right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($leads)): ?>
          <tr>
            <td colspan="<?= can('leads', 'reassign') ? 9 : 8 ?>" style="text-align:center; padding:3.5rem; color:var(--text-muted);">
              <div style="width:56px; height:56px; border-radius:50%; background:var(--primary-subtle); color:var(--primary); display:inline-flex; align-items:center; justify-content:center; font-size:1.8rem; margin-bottom:0.75rem;">
                <i class="fa-solid fa-user-graduate"></i>
              </div>
              <div style="font-size:1.15rem; font-weight:800; color:var(--text-heading); margin-bottom:0.35rem;">No Student Leads Found</div>
              <p style="font-size:0.875rem; color:var(--text-muted); max-width:440px; margin:0 auto 1.25rem auto;">
                <?php if (!empty($search) || !empty($stageFilter) || !empty($priorityFilter) || !empty($sourceFilter)): ?>
                  No student leads match your current search and filter combination. Try clearing your filters.
                <?php else: ?>
                  No active student leads are recorded in this caseload yet. You can add a new inquiry anytime.
                <?php endif; ?>
              </p>
              <div style="display:flex; justify-content:center; gap:0.5rem;">
                <?php if (!empty($search) || !empty($stageFilter) || !empty($priorityFilter) || !empty($sourceFilter)): ?>
                  <a href="<?= base_url('leads/index.php') ?>" class="btn btn-secondary btn-sm"><i class="fa-solid fa-rotate-left"></i> Reset Filters</a>
                <?php endif; ?>
                <?php if (can('leads', 'add')): ?>
                  <button type="button" class="btn btn-primary btn-sm" onclick="openQuickLeadModal()"><i class="fa-solid fa-plus"></i> + Add Student Lead</button>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($leads as $lead): 
            $stageBadgeClass = match($lead['stage']) {
                'ENQUIRY' => 'badge-secondary',
                'CUSTOMER' => 'badge-info',
                'APPLICATION' => 'badge-primary',
                'OFFER RECEIVED' => 'badge-warning',
                'TUITION FEES PAID' => 'badge-primary',
                'ENROLLED' => 'badge-success',
                default => 'badge-secondary'
            };
          ?>
            <tr>
              <?php if (can('leads', 'reassign')): ?>
                <td>
                  <input type="checkbox" class="lead-select-checkbox" value="<?= $lead['id'] ?>">
                </td>
              <?php endif; ?>
              <td>
                <div style="font-family:monospace; font-size:0.75rem; font-weight:700; color:var(--primary); margin-bottom:2px;">
                  <?= htmlspecialchars($lead['lead_code']) ?>
                </div>
                <a href="<?= base_url('leads/view.php?id=' . $lead['id']) ?>" style="font-weight:700; color:var(--text-heading); text-decoration:none; font-size:0.92rem;">
                  <?= htmlspecialchars($lead['full_name']) ?>
                </a>
                <?php if ($lead['is_customer']): ?>
                  <span class="badge badge-success" style="font-size:0.65rem; padding:1px 5px; margin-left:4px;" title="Official Customer (Invoiced)">
                    <i class="fa-solid fa-certificate"></i> CLIENT
                  </span>
                <?php endif; ?>
                <div style="font-size:0.72rem; color:var(--text-muted); margin-top:2px;">
                  <?= htmlspecialchars($lead['college_name'] ?? 'Higher Ed') ?>
                </div>
              </td>
              <td>
                <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                  <span style="font-family:monospace; font-weight:700; color:var(--text-heading); font-size:0.85rem;">
                    <?= htmlspecialchars($lead['phone']) ?>
                  </span>
                  <a href="https://wa.me/91<?= normalize_phone($lead['phone']) ?>?text=<?= urlencode("Hello " . $lead['full_name'] . ", this is " . ($currentUser['name'] ?? 'Edroots') . " regarding your higher education admission enquiry.") ?>" target="_blank" class="btn btn-sm" style="background:#25d366; color:#fff; font-size:0.75rem; padding:2px 7px; border-radius:6px; font-weight:700; display:inline-flex; align-items:center; gap:4px;" title="WhatsApp Candidate">
                    <i class="fa-brands fa-whatsapp"></i> Chat
                  </a>
                  <a href="tel:<?= preg_replace('/[^0-9+]/', '', $lead['phone']) ?>" class="btn btn-outline btn-sm" style="padding:2px 7px; font-size:0.75rem; border-radius:6px;" title="Direct Call">
                    <i class="fa-solid fa-phone" style="font-size:0.75rem; color:var(--primary);"></i>
                  </a>
                </div>
                <?php if (!empty($lead['email'])): ?>
                  <div style="font-size:0.72rem; color:var(--text-muted); margin-top:2px;">
                    <?= htmlspecialchars($lead['email']) ?>
                  </div>
                <?php endif; ?>
              </td>
              <td>
                <div style="font-weight:600; font-size:0.85rem; color:var(--text-heading);">
                  <?= htmlspecialchars($lead['preferred_country'] ?: 'Global') ?>
                </div>
                <div style="font-size:0.75rem; color:var(--text-muted);">
                  <?= htmlspecialchars($lead['preferred_course'] ?: 'General Program') ?>
                </div>
              </td>
              <td>
                <span class="badge <?= $stageBadgeClass ?>" style="font-weight:700; font-size:0.72rem;">
                  <?= htmlspecialchars($lead['stage']) ?>
                </span>
                <?php if ($lead['secondary_status'] !== 'Active'): ?>
                  <div style="font-size:0.7rem; color:var(--danger); font-weight:600; margin-top:2px;">
                    <?= htmlspecialchars($lead['secondary_status']) ?>
                  </div>
                <?php endif; ?>
              </td>
              <td>
                <?= priority_badge($lead['priority']) ?>
              </td>
              <td>
                <div style="font-size:0.82rem; font-weight:600; color:var(--text-heading);">
                  <?= htmlspecialchars($lead['counselor_name'] ?? 'Unassigned') ?>
                </div>
                <div style="font-size:0.72rem; color:var(--text-muted);">
                  <i class="fa-solid fa-location-dot" style="font-size:0.68rem;"></i> <?= htmlspecialchars($lead['branch_name'] ?? 'General') ?>
                </div>
              </td>
              <td>
                <span class="badge badge-secondary" style="font-weight:600; font-size:0.75rem;">
                  <?= (int)$lead['followup_count'] ?> Logs
                </span>
                <?php if (!empty($lead['next_followup_date'])): ?>
                  <div style="font-size:0.72rem; color:var(--text-muted); margin-top:2px;">
                    Next: <strong><?= date('d M', strtotime($lead['next_followup_date'])) ?></strong>
                  </div>
                <?php endif; ?>
              </td>
              <td style="text-align:right;">
                <div style="display:flex; justify-content:flex-end; gap:0.35rem; align-items:center;">
                  <button type="button" class="btn btn-primary btn-sm" style="font-size:0.75rem; padding:3px 8px; font-weight:600;" onclick="openLogCallModal(<?= $lead['id'] ?>, '<?= htmlspecialchars(addslashes($lead['full_name'])) ?>', '<?= htmlspecialchars($lead['stage']) ?>')" title="Log Call Outcome & Next Follow-up">
                    <i class="fa-solid fa-phone-volume"></i> <span class="d-none d-lg-inline">Log Call</span>
                  </button>
                  <button type="button" class="btn btn-secondary btn-sm" style="font-size:0.75rem; padding:3px 8px;" onclick="openAdvanceStageModal(<?= $lead['id'] ?>, '<?= htmlspecialchars(addslashes($lead['full_name'])) ?>', '<?= htmlspecialchars($lead['stage']) ?>')" title="Advance Admission Stage">
                    <i class="fa-solid fa-stairs"></i>
                  </button>
                  <a href="<?= base_url('leads/view.php?id=' . $lead['id']) ?>" class="btn btn-outline btn-sm" style="padding:3px 8px;" title="View Student Dossier">
                    <i class="fa-solid fa-eye"></i>
                  </a>
                  <?php if (can('leads', 'edit')): ?>
                    <a href="<?= base_url('leads/edit.php?id=' . $lead['id']) ?>" class="btn btn-ghost btn-sm" style="padding:3px 8px;" title="Edit Profile">
                      <i class="fa-solid fa-pen-to-square"></i>
                    </a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Bulk Reassignment Modal (Super Admin & Branch Manager only) -->
<?php if (can('leads', 'reassign')): ?>
  <div class="modal fade" id="bulkReassignModal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(12,36,60,0.5); align-items:center; justify-content:center;">
    <div class="modal-dialog" style="background:#fff; border-radius:14px; max-width:500px; width:92%; margin:auto; box-shadow:0 20px 50px rgba(0,0,0,0.2); overflow:hidden;">
      <form method="POST" action="<?= base_url('leads/bulk_assign.php') ?>" id="bulkAssignForm">
        <?= csrf_field() ?>
        <input type="hidden" name="lead_ids" id="bulk_lead_ids" value="">

        <div class="modal-header" style="padding:1.25rem 1.5rem; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
          <h3 style="font-size:1.15rem; font-weight:700; margin:0; color:var(--text-heading);">
            <i class="fa-solid fa-shuffle" style="color:var(--primary); margin-right:8px;"></i> Reassign Selected Leads
          </h3>
          <button type="button" onclick="closeBulkReassignModal()" style="border:none; background:none; font-size:1.4rem; cursor:pointer; color:var(--text-muted);">&times;</button>
        </div>

        <div class="modal-body" style="padding:1.5rem;">
          <div style="background:var(--bg-subtle); border-radius:8px; padding:0.85rem; margin-bottom:1.25rem; font-size:0.85rem;">
            Selected Leads: <strong id="selected_count_badge">0 leads</strong>
          </div>

          <div class="form-group" style="margin-bottom:1.25rem;">
            <label class="form-label">New Assigned Counselor <span style="color:red;">*</span></label>
            <select name="target_counselor_id" class="form-control" required>
              <option value="">Select Counselor...</option>
              <?php foreach ($counselors as $c): ?>
                <option value="<?= $c['id'] ?>">
                  <?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['branch_name'] ?? 'Hub') ?>) — Active: <?= $c['active_caseload'] ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group" style="margin-bottom:0.5rem;">
            <label class="form-label">Reassignment Reason / Note</label>
            <textarea name="reassign_reason" class="form-control" rows="2" placeholder="Caseload rebalance or regional intake reallocation..."></textarea>
          </div>
        </div>

        <div class="modal-footer" style="padding:1rem 1.5rem; border-top:1px solid var(--border-color); display:flex; justify-content:flex-end; gap:0.5rem;">
          <button type="button" onclick="closeBulkReassignModal()" class="btn btn-secondary btn-sm">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-check"></i> Execute Reassignment
          </button>
        </div>
      </form>
    </div>
  </div>

  <script>
  function toggleSelectAll(master) {
    const boxes = document.querySelectorAll('.lead-select-checkbox');
    boxes.forEach(b => b.checked = master.checked);
  }

  function openBulkReassignModal() {
    const selected = Array.from(document.querySelectorAll('.lead-select-checkbox:checked')).map(b => b.value);
    if (selected.length === 0) {
      alert('Please check at least one lead from the table to reassign.');
      return;
    }
    document.getElementById('bulk_lead_ids').value = selected.join(',');
    document.getElementById('selected_count_badge').innerText = selected.length + ' leads selected';
    document.getElementById('bulkReassignModal').style.display = 'flex';
  }

  function closeBulkReassignModal() {
    document.getElementById('bulkReassignModal').style.display = 'none';
  }
  </script>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
