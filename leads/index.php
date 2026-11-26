<?php
/**
 * Active Leads Directory - Core CRM
 * Fully Aligned with Client Requirements & Malayalam Training Audio:
 * - Search by Region Number (e.g. REG-01), Customer ID, Phone, Name, or Business
 * - Filters for Regional Geographic Segmentation (Central Zone density, Center vs Outer)
 * - Prospectivity Tiers (Hot, Warm, Cold)
 * - Phone Verification Cross-Checking Badges
 * - Fast Action Bar & 1-Click Top Update & Call Logging
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_permission('leads', 'view');

$currentUser = current_user();
$pageTitle = 'Leads Directory';
$activePage = 'leads';

// Type filter: all, leads, customers
$type = strtolower(trim($_GET['type'] ?? 'all'));
if (!in_array($type, ['all', 'leads', 'customers'])) {
    $type = 'all';
}

// Filter inputs
$search = trim($_GET['search'] ?? '');
$regionFilter = trim($_GET['region'] ?? '');
$zoneFilter = trim($_GET['zone_type'] ?? '');
$prospectFilter = trim($_GET['prospectivity'] ?? '');
$statusFilter = trim($_GET['active_status'] ?? '');
$verifiedFilter = trim($_GET['verified'] ?? '');
$counselorFilter = isset($_GET['counselor_id']) ? (int)$_GET['counselor_id'] : 0;
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');

// Scoping clause for current user
$scopeClause = get_crm_scope_clause('l', 'assigned_counselor_id', 'branch_id');
$where = ["l.is_archived = 0", $scopeClause];
$params = [];

if ($type === 'leads') {
    $where[] = "l.is_customer = 0 AND l.active_status != 'Converted'";
} elseif ($type === 'customers') {
    $where[] = "(l.is_customer = 1 OR l.active_status = 'Converted')";
}

// Search across Region Numbers, Customer ID, Name, Phone, and Business Name
if (!empty($search)) {
    $where[] = "(l.full_name LIKE ? OR l.lead_code LIKE ? OR l.phone LIKE ? OR l.email LIKE ? OR l.business_name LIKE ? OR l.region_number LIKE ? OR l.main_region LIKE ? OR l.sub_area LIKE ?)";
    $term = "%{$search}%";
    for ($i = 0; $i < 8; $i++) {
        $params[] = $term;
    }
}

if (!empty($regionFilter)) {
    $where[] = "l.main_region = ?";
    $params[] = $regionFilter;
}

if (!empty($zoneFilter)) {
    $where[] = "l.zone_type = ?";
    $params[] = $zoneFilter;
}

if (!empty($prospectFilter)) {
    $where[] = "l.prospectivity = ?";
    $params[] = $prospectFilter;
}

if (!empty($statusFilter)) {
    $where[] = "l.active_status = ?";
    $params[] = $statusFilter;
}

if ($verifiedFilter !== '') {
    $where[] = "l.is_phone_verified = ?";
    $params[] = (int)$verifiedFilter;
}

if ($counselorFilter > 0) {
    $where[] = "l.assigned_counselor_id = ?";
    $params[] = $counselorFilter;
}

if (!empty($dateFrom)) {
    $where[] = "DATE(l.created_at) >= ?";
    $params[] = $dateFrom;
}
if (!empty($dateTo)) {
    $where[] = "DATE(l.created_at) <= ?";
    $params[] = $dateTo;
}

$whereSql = implode(' AND ', $where);

// Total count for pagination
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM leads l WHERE {$whereSql}");
$countStmt->execute($params);
$totalRecords = (int)$countStmt->fetchColumn();

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$totalPages = max(1, ceil($totalRecords / $perPage));
$offset = ($page - 1) * $perPage;

// Fetch Leads
$sql = "
    SELECT l.*, b.name as branch_name, u.name as counselor_name
    FROM leads l
    LEFT JOIN branches b ON l.branch_id = b.id
    LEFT JOIN users u ON l.assigned_counselor_id = u.id
    WHERE {$whereSql}
    ORDER BY l.id DESC
    LIMIT {$perPage} OFFSET {$offset}
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$leads = $stmt->fetchAll();

// Fetch Counselors for Filter
$counselors = $pdo->query("SELECT u.id, u.name, r.label as role_label FROM users u JOIN roles r ON u.role_id = r.id WHERE u.status = 'Active' ORDER BY u.name ASC")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<!-- Header Title & Quick Actions -->
<div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.25rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <h1 style="font-size:1.4rem; font-weight:800; color:var(--text-heading); margin:0 0 0.25rem 0;">
      <?= ($type === 'customers') ? 'Converted Customers Directory' : 'Leads Directory' ?>
    </h1>
    <p style="font-size:0.85rem; color:var(--text-muted); margin:0;">
      Regional distribution, phone cross-checking, prospectivity grading, and counselor routing.
    </p>
  </div>
  <div style="display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap;">
    <button type="button" class="btn btn-success btn-sm" onclick="openTopUpdateModal()" style="font-weight:700;">
      <i class="fa-solid fa-arrows-rotate"></i> 1-Click Update
    </button>
    <?php if (can('leads', 'add')): ?>
      <button type="button" class="btn btn-primary btn-sm" onclick="openQuickLeadModal()" style="font-weight:700;">
        <i class="fa-solid fa-plus"></i> + New Lead
      </button>
    <?php endif; ?>
    <?php if (can('leads', 'reassign')): ?>
      <button type="button" class="btn btn-outline btn-sm" onclick="openBulkReassignModal()" style="font-weight:700;">
        <i class="fa-solid fa-users-gear"></i> Bulk Reassign
      </button>
    <?php endif; ?>
  </div>
</div>

<!-- Type Switcher Tabs (All, Leads, Converted) -->
<div style="display:flex; gap:0.5rem; margin-bottom:1.25rem; border-bottom:2px solid var(--border-color); padding-bottom:0.5rem;">
  <a href="<?= base_url('leads/index.php?type=all') ?>" class="btn btn-sm <?= ($type === 'all') ? 'btn-primary' : 'btn-ghost' ?>" style="font-weight:700; border-radius:8px;">
    All Records (<?= $totalRecords ?>)
  </a>
  <a href="<?= base_url('leads/index.php?type=leads') ?>" class="btn btn-sm <?= ($type === 'leads') ? 'btn-primary' : 'btn-ghost' ?>" style="font-weight:700; border-radius:8px;">
    Active Leads
  </a>
  <a href="<?= base_url('leads/index.php?type=customers') ?>" class="btn btn-sm <?= ($type === 'customers') ? 'btn-primary' : 'btn-ghost' ?>" style="font-weight:700; border-radius:8px;">
    Converted Customers
  </a>
</div>

<!-- Advanced Search & Filter Bar (Requirement 2 & 3: Region Number, Prospectivity, Phone Verification) -->
<div class="card" style="margin-bottom:1.5rem; padding:1.25rem; border-radius:12px;">
  <form method="GET" action="<?= base_url('leads/index.php') ?>" style="display:flex; flex-wrap:wrap; gap:0.75rem; align-items:center;">
    <?php if ($type !== 'all'): ?>
      <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
    <?php endif; ?>

    <!-- Search Input -->
    <div style="flex:2; min-width:260px; position:relative;">
      <input type="text" name="search" class="form-control" placeholder="Search by region (e.g. REG-01), customer ID, phone, name, business..." value="<?= htmlspecialchars($search) ?>" style="padding-left:2.4rem;">
      <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-light);"></i>
    </div>

    <!-- Region Filter -->
    <div style="min-width:140px;">
      <select name="region" class="form-control">
        <option value="">All Regions</option>
        <option value="Central Zone" <?= $regionFilter === 'Central Zone' ? 'selected' : '' ?>>Central Zone</option>
        <option value="North Zone" <?= $regionFilter === 'North Zone' ? 'selected' : '' ?>>North Zone</option>
        <option value="South Zone" <?= $regionFilter === 'South Zone' ? 'selected' : '' ?>>South Zone</option>
        <option value="West Zone" <?= $regionFilter === 'West Zone' ? 'selected' : '' ?>>West Zone</option>
      </select>
    </div>

    <!-- Zone Type Filter (Center vs Outer) -->
    <div style="min-width:130px;">
      <select name="zone_type" class="form-control">
        <option value="">All Zones</option>
        <option value="Center" <?= $zoneFilter === 'Center' ? 'selected' : '' ?>>Center (High Density)</option>
        <option value="Sub-area" <?= $zoneFilter === 'Sub-area' ? 'selected' : '' ?>>Sub-area</option>
        <option value="Outer Zone" <?= $zoneFilter === 'Outer Zone' ? 'selected' : '' ?>>Outer Zone</option>
      </select>
    </div>

    <!-- Prospectivity Filter -->
    <div style="min-width:130px;">
      <select name="prospectivity" class="form-control">
        <option value="">All Prospectivity</option>
        <option value="Hot" <?= $prospectFilter === 'Hot' ? 'selected' : '' ?>>🔥 Hot Leads</option>
        <option value="Warm" <?= $prospectFilter === 'Warm' ? 'selected' : '' ?>>⚡ Warm Leads</option>
        <option value="Cold" <?= $prospectFilter === 'Cold' ? 'selected' : '' ?>>💤 Cold Leads</option>
      </select>
    </div>

    <!-- Active Status Filter -->
    <div style="min-width:140px;">
      <select name="active_status" class="form-control">
        <option value="">All Statuses</option>
        <option value="New Lead" <?= $statusFilter === 'New Lead' ? 'selected' : '' ?>>New Lead</option>
        <option value="Call In Progress" <?= $statusFilter === 'Call In Progress' ? 'selected' : '' ?>>Call In Progress</option>
        <option value="Contacted" <?= $statusFilter === 'Contacted' ? 'selected' : '' ?>>Contacted</option>
        <option value="Follow-up Scheduled" <?= $statusFilter === 'Follow-up Scheduled' ? 'selected' : '' ?>>Follow-up Scheduled</option>
        <option value="Prospect Qualified" <?= $statusFilter === 'Prospect Qualified' ? 'selected' : '' ?>>Prospect Qualified</option>
        <option value="Escalated to Counselor" <?= $statusFilter === 'Escalated to Counselor' ? 'selected' : '' ?>>Escalated to Counselor</option>
        <option value="Converted" <?= $statusFilter === 'Converted' ? 'selected' : '' ?>>Converted</option>
        <option value="Disqualified" <?= $statusFilter === 'Disqualified' ? 'selected' : '' ?>>Disqualified</option>
      </select>
    </div>

    <!-- Phone Verification Filter -->
    <div style="min-width:130px;">
      <select name="verified" class="form-control">
        <option value="">Phone Verification</option>
        <option value="1" <?= $verifiedFilter === '1' ? 'selected' : '' ?>>✓ Verified Only</option>
        <option value="0" <?= $verifiedFilter === '0' ? 'selected' : '' ?>>Pending Cross-Check</option>
      </select>
    </div>

    <!-- Counselor Filter -->
    <?php if (is_super_admin() || is_team_leader() || is_operations()): ?>
      <div style="min-width:140px;">
        <select name="counselor_id" class="form-control">
          <option value="0">All Counselors</option>
          <?php foreach ($counselors as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $counselorFilter === (int)$c['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($c['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    <?php endif; ?>

    <button type="submit" class="btn btn-primary btn-sm" style="font-weight:700;">
      <i class="fa-solid fa-filter"></i> Apply
    </button>
    <a href="<?= base_url('leads/index.php' . ($type !== 'all' ? '?type=' . $type : '')) ?>" class="btn btn-secondary btn-sm" title="Reset Filters">
      <i class="fa-solid fa-rotate-left"></i>
    </a>
  </form>
</div>

<!-- Main Leads Directory Table -->
<div class="card" style="border-radius:14px; overflow:hidden;">
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <?php if (can('leads', 'reassign')): ?>
            <th style="width:36px;"><input type="checkbox" id="selectAllCheckbox" onclick="toggleSelectAll(this)"></th>
          <?php endif; ?>
          <th>Record # & Lead Name</th>
          <th>Business / Account Name</th>
          <th>Contact Phone & Verification</th>
          <th>Region & Zone</th>
          <th>Prospectivity</th>
          <th>Active Status</th>
          <th>Assigned Counselor</th>
          <th style="text-align:right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($leads)): ?>
          <tr>
            <td colspan="<?= can('leads', 'reassign') ? 9 : 8 ?>" style="text-align:center; padding:3.5rem; color:var(--text-muted);">
              <div style="width:56px; height:56px; border-radius:50%; background:var(--primary-subtle); color:var(--primary); display:inline-flex; align-items:center; justify-content:center; font-size:1.8rem; margin-bottom:0.75rem;">
                <i class="fa-solid fa-users"></i>
              </div>
              <div style="font-size:1.15rem; font-weight:800; color:var(--text-heading); margin-bottom:0.35rem;">No Leads Found</div>
              <p style="font-size:0.875rem; color:var(--text-muted); max-width:440px; margin:0 auto 1.25rem auto;">
                No lead records matched your filter criteria. Try clearing search filters or add a new lead inquiry.
              </p>
              <div style="display:flex; justify-content:center; gap:0.5rem;">
                <a href="<?= base_url('leads/index.php') ?>" class="btn btn-secondary btn-sm"><i class="fa-solid fa-rotate-left"></i> Reset Filters</a>
                <button type="button" class="btn btn-primary btn-sm" onclick="openQuickLeadModal()"><i class="fa-solid fa-plus"></i> + Add Lead</button>
              </div>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($leads as $l): ?>
            <tr>
              <?php if (can('leads', 'reassign')): ?>
                <td>
                  <input type="checkbox" class="lead-select-checkbox" value="<?= $l['id'] ?>">
                </td>
              <?php endif; ?>

              <!-- Record # & Name -->
              <td>
                <div style="font-family:monospace; font-size:0.75rem; font-weight:700; color:var(--primary); margin-bottom:2px;">
                  <?= htmlspecialchars($l['lead_code']) ?>
                </div>
                <a href="<?= base_url('leads/view.php?id=' . $l['id']) ?>" style="font-weight:700; color:var(--text-heading); text-decoration:none; font-size:0.92rem;">
                  <?= htmlspecialchars($l['full_name']) ?>
                </a>
                <?php if ($l['is_customer'] || $l['active_status'] === 'Converted'): ?>
                  <span class="badge badge-success" style="font-size:0.65rem; padding:1px 5px; margin-left:4px;">
                    <i class="fa-solid fa-check"></i> CUSTOMER
                  </span>
                <?php endif; ?>
              </td>

              <!-- Business / Account -->
              <td>
                <div style="font-weight:600; font-size:0.85rem; color:#334155;">
                  <?= htmlspecialchars($l['business_name'] ?: 'Individual Account') ?>
                </div>
                <div style="font-size:0.72rem; color:var(--text-muted);">
                  Completeness: <?= (int)($l['record_completeness'] ?? 85) ?>%
                </div>
              </td>

              <!-- Phone & Verification Status -->
              <td>
                <div style="font-family:monospace; font-weight:800; font-size:0.9rem; color:var(--text-heading);">
                  <?= htmlspecialchars($l['phone']) ?>
                </div>
                <div>
                  <?php if (!empty($l['is_phone_verified'])): ?>
                    <span class="badge badge-success" style="font-size:0.68rem; padding:1px 6px;">
                      <i class="fa-solid fa-check"></i> Verified
                    </span>
                  <?php else: ?>
                    <span class="badge badge-warning" style="font-size:0.68rem; padding:1px 6px;">
                      Pending Cross-Check
                    </span>
                  <?php endif; ?>
                </div>
              </td>

              <!-- Region & Zone -->
              <td>
                <span class="badge" style="background:#334155; color:#fff; font-size:0.72rem; font-weight:700;">
                  <?= htmlspecialchars($l['region_number'] ?? 'REG-01') ?>
                </span>
                <div style="font-size:0.78rem; font-weight:600; color:var(--text-heading); margin-top:2px;">
                  <?= htmlspecialchars($l['main_region'] ?? 'Central Zone') ?>
                </div>
                <div style="font-size:0.7rem; color:var(--text-muted);">
                  <?= htmlspecialchars($l['sub_area'] ?? 'Downtown Central') ?> &bull; <em><?= htmlspecialchars($l['zone_type'] ?? 'Center') ?></em>
                </div>
              </td>

              <!-- Prospectivity -->
              <td>
                <span class="badge <?= ($l['prospectivity'] === 'Hot') ? 'badge-danger' : (($l['prospectivity'] === 'Cold') ? 'badge-secondary' : 'badge-warning') ?>" style="font-size:0.75rem; font-weight:700;">
                  <?= ($l['prospectivity'] === 'Hot') ? '🔥 Hot' : (($l['prospectivity'] === 'Cold') ? '💤 Cold' : '⚡ Warm') ?>
                </span>
              </td>

              <!-- Active Status -->
              <td>
                <span class="badge badge-primary" style="font-size:0.75rem; font-weight:700;">
                  <?= htmlspecialchars($l['active_status'] ?? 'New Lead') ?>
                </span>
              </td>

              <!-- Counselor -->
              <td>
                <div style="font-size:0.825rem; font-weight:600; color:var(--text-heading);">
                  <?= htmlspecialchars($l['counselor_name'] ?? 'Unassigned') ?>
                </div>
              </td>

              <!-- Actions -->
              <td style="text-align:right;">
                <div style="display:inline-flex; gap:4px; align-items:center;">
                  <a href="<?= base_url('leads/view.php?id=' . $l['id']) ?>" class="btn btn-secondary btn-sm" style="font-size:0.75rem; padding:3px 8px; font-weight:700;">
                    Profile &rarr;
                  </a>
                  <button type="button" class="btn btn-outline btn-sm" style="padding:3px 8px;" onclick="openLogCallModal(<?= $l['id'] ?>, '<?= htmlspecialchars(addslashes($l['full_name'])) ?>', '<?= htmlspecialchars($l['phone']) ?>')" title="Log Call">
                    <i class="fa-solid fa-phone"></i>
                  </button>
                  <button type="button" class="btn btn-outline btn-sm" style="padding:3px 8px;" onclick="openTopUpdateModal(<?= $l['id'] ?>)" title="Update Status">
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

  <!-- Pagination -->
  <?php if ($totalPages > 1): ?>
    <div style="padding:1rem 1.25rem; display:flex; justify-content:space-between; align-items:center; border-top:1px solid var(--border-color); background:#f8fafc;">
      <span style="font-size:0.8rem; color:var(--text-muted);">
        Showing <?= $offset + 1 ?> to <?= min($offset + $perPage, $totalRecords) ?> of <?= $totalRecords ?> records
      </span>
      <div style="display:flex; gap:0.35rem;">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
          <a href="<?= base_url('leads/index.php?' . http_build_query(array_merge($_GET, ['page' => $p]))) ?>" class="btn btn-sm <?= ($p === $page) ? 'btn-primary' : 'btn-ghost' ?>" style="padding:3px 8px; font-size:0.75rem;">
            <?= $p ?>
          </a>
        <?php endfor; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<!-- Bulk Reassignment Modal -->
<?php if (can('leads', 'reassign')): ?>
  <div class="modal fade" id="bulkReassignModal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(12,36,60,0.5); align-items:center; justify-content:center;">
    <div class="modal-dialog" style="background:#fff; border-radius:14px; max-width:480px; width:92%; margin:auto; box-shadow:0 20px 50px rgba(0,0,0,0.2); overflow:hidden;">
      <form method="POST" action="<?= base_url('leads/bulk_assign.php') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="lead_ids" id="bulk_lead_ids" value="">

        <div class="modal-header" style="padding:1.2rem 1.5rem; background:#334155; color:#fff; display:flex; justify-content:space-between; align-items:center;">
          <h3 style="font-size:1.15rem; font-weight:700; margin:0; color:#fff;">
            <i class="fa-solid fa-users-gear"></i> Bulk Reassign Leads
          </h3>
          <button type="button" onclick="closeBulkReassignModal()" style="border:none; background:none; font-size:1.4rem; cursor:pointer; color:#fff;">&times;</button>
        </div>

        <div class="modal-body" style="padding:1.5rem;">
          <div style="background:#f1f5f9; border-radius:8px; padding:0.75rem; margin-bottom:1rem; font-size:0.85rem;">
            Selected Leads: <strong id="selected_count_badge">0</strong>
          </div>

          <div class="form-group" style="margin-bottom:1rem;">
            <label class="form-label" style="font-weight:700;">Assign to Counselor <span style="color:red;">*</span></label>
            <select name="target_counselor_id" class="form-control" required>
              <option value="">Select Counselor...</option>
              <?php foreach ($counselors as $c): ?>
                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['role_label'] ?? 'Staff') ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group" style="margin-bottom:0;">
            <label class="form-label" style="font-weight:700;">Reassignment Reason</label>
            <textarea name="reassign_reason" class="form-control" rows="2" placeholder="Regional caseload balance..."></textarea>
          </div>
        </div>

        <div class="modal-footer" style="padding:0.85rem 1.5rem; border-top:1px solid var(--border-color); display:flex; justify-content:flex-end; gap:0.5rem; background:#f8fafc;">
          <button type="button" onclick="closeBulkReassignModal()" class="btn btn-secondary btn-sm">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm" style="font-weight:700;">
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
    document.getElementById('selected_count_badge').innerText = selected.length + ' leads';
    document.getElementById('bulkReassignModal').style.display = 'flex';
  }

  function closeBulkReassignModal() {
    document.getElementById('bulkReassignModal').style.display = 'none';
  }
  </script>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
