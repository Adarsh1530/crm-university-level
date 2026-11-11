<?php
/**
 * College Data Pool - Raw Student Repository
 * University-Level Education Consultancy CRM (EdrootsCRM)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_permission('college_data', 'view');

$currentUser = current_user();
$pageTitle = 'College Data Pool';
$activePage = 'college_data';

// Search & Filter parameters
$search = trim($_GET['search'] ?? '');
$degreeFilter = trim($_GET['degree'] ?? '');
$statusFilter = trim($_GET['status'] ?? ''); // '0' for Unconverted, '1' for Converted, '' for All
$branchFilter = isset($_GET['branch_id']) ? (int)$_GET['branch_id'] : 0;

// Scoping: Super Admin sees all or filtered branch; Branch Manager sees their branch or unassigned
$where = ["c.is_archived = 0"];
$params = [];

if (is_branch_manager()) {
    $where[] = "(c.branch_id = ? OR c.branch_id IS NULL)";
    $params[] = (int)$currentUser['branch_id'];
} elseif ($branchFilter > 0 && is_super_admin()) {
    $where[] = "c.branch_id = ?";
    $params[] = $branchFilter;
}

if (!empty($search)) {
    $where[] = "(c.student_name LIKE ? OR c.phone LIKE ? OR c.mobile_number LIKE ? OR c.email LIKE ? OR c.college_name LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if (!empty($degreeFilter)) {
    $where[] = "c.degree = ?";
    $params[] = $degreeFilter;
}

if ($statusFilter !== '') {
    $where[] = "c.converted_to_lead = ?";
    $params[] = (int)$statusFilter;
}

$whereSql = implode(' AND ', $where);

// Summary counts
$totalRecords = (int)$pdo->query("SELECT COUNT(*) FROM college_data WHERE is_archived = 0")->fetchColumn();
$convertedCount = (int)$pdo->query("SELECT COUNT(*) FROM college_data WHERE is_archived = 0 AND converted_to_lead = 1")->fetchColumn();
$unconvertedCount = $totalRecords - $convertedCount;

// Fetch unique degrees for filter dropdown
$degrees = $pdo->query("SELECT DISTINCT degree FROM college_data WHERE degree IS NOT NULL AND degree != '' ORDER BY degree ASC")->fetchAll(PDO::FETCH_COLUMN);

// Fetch branches for admin filter and conversion modal
$branches = $pdo->query("SELECT id, name, city FROM branches WHERE status = 'Active' ORDER BY name ASC")->fetchAll();

// Counselors for assignment
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

// Main query
$sql = "
    SELECT c.*, b.name AS branch_name, 
           COALESCE(c.lead_id, c.converted_lead_id) AS lead_id, 
           l.lead_code, u.name AS converted_by_name,
           COALESCE(c.phone, c.mobile_number) AS phone,
           COALESCE(c.city, c.location) AS city,
           COALESCE(c.graduation_year, c.passout_year) AS graduation_year
    FROM college_data c
    LEFT JOIN branches b ON c.branch_id = b.id
    LEFT JOIN leads l ON (l.id = c.lead_id OR l.id = c.converted_lead_id)
    LEFT JOIN users u ON c.converted_by = u.id
    WHERE {$whereSql}
    ORDER BY c.converted_to_lead ASC, c.id DESC
    LIMIT 100
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<!-- PDF Section 9.0: Student Module Top Tab Bar -->
<div class="pdf-student-nav-tabs" style="display:flex; gap:0.25rem; border-bottom:2px solid var(--border-color); margin-bottom:1.25rem; overflow-x:auto; padding-bottom:1px;">
  <a href="<?= base_url('leads/index.php?type=all') ?>" class="pdf-tab" style="padding:0.6rem 1.15rem; font-weight:700; text-decoration:none; font-size:0.875rem; border-radius:8px 8px 0 0; color:var(--text-muted); border-bottom:3px solid transparent; white-space:nowrap;">
    <i class="fa-solid fa-users"></i> All
  </a>
  <a href="<?= base_url('leads/index.php?type=leads') ?>" class="pdf-tab" style="padding:0.6rem 1.15rem; font-weight:700; text-decoration:none; font-size:0.875rem; border-radius:8px 8px 0 0; color:var(--text-muted); border-bottom:3px solid transparent; white-space:nowrap;">
    <i class="fa-solid fa-user-graduate"></i> Leads
  </a>
  <a href="<?= base_url('customers/index.php') ?>" class="pdf-tab" style="padding:0.6rem 1.15rem; font-weight:700; text-decoration:none; font-size:0.875rem; border-radius:8px 8px 0 0; color:var(--text-muted); border-bottom:3px solid transparent; white-space:nowrap;">
    <i class="fa-solid fa-certificate"></i> Customers
  </a>
  <a href="<?= base_url('tasks/index.php?queue=scheduled') ?>" class="pdf-tab" style="padding:0.6rem 1.15rem; font-weight:700; text-decoration:none; font-size:0.875rem; border-radius:8px 8px 0 0; color:var(--text-muted); border-bottom:3px solid transparent; white-space:nowrap;">
    <i class="fa-solid fa-phone-volume"></i> Follow-ups
  </a>
  <a href="<?= base_url('data/college_data.php') ?>" class="pdf-tab active" style="padding:0.6rem 1.15rem; font-weight:700; text-decoration:none; font-size:0.875rem; border-radius:8px 8px 0 0; color:var(--primary); border-bottom:3px solid var(--primary); background:#f5f3ff; white-space:nowrap;">
    <i class="fa-solid fa-database"></i> College Datas <span class="badge badge-secondary" style="margin-left:4px; font-size:0.75rem;"><?= number_format($totalRecords) ?></span>
  </a>
  <a href="<?= base_url('archive/index.php') ?>" class="pdf-tab" style="padding:0.6rem 1.15rem; font-weight:700; text-decoration:none; font-size:0.875rem; border-radius:8px 8px 0 0; color:var(--text-muted); border-bottom:3px solid transparent; white-space:nowrap;">
    <i class="fa-solid fa-box-archive"></i> Archived
  </a>
  <a href="<?= base_url('tasks/index.php') ?>" class="pdf-tab" style="padding:0.6rem 1.15rem; font-weight:700; text-decoration:none; font-size:0.875rem; border-radius:8px 8px 0 0; color:var(--text-muted); border-bottom:3px solid transparent; white-space:nowrap;">
    <i class="fa-solid fa-list-check"></i> My Tasks
  </a>
</div>

<div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <h1 style="font-size:1.5rem; font-weight:700; color:var(--text-heading); margin:0 0 0.35rem 0;">College Data Pool</h1>
    <p style="font-size:0.875rem; color:var(--text-muted); margin:0;">
      Raw college student repository. Triage, filter, and convert prospective students into qualified Active Leads with 1 click.
    </p>
  </div>
  <div style="display:flex; gap:0.5rem; align-items:center;">
    <?php if (can('college_data', 'add')): ?>
      <a href="<?= base_url('data/import_college_data.php') ?>" class="btn btn-primary btn-sm">
        <i class="fa-solid fa-file-arrow-up"></i> Import College Data
      </a>
    <?php endif; ?>
  </div>
</div>

<!-- KPI Mini Cards -->
<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:1rem; margin-bottom:1.5rem;">
  <div class="card" style="padding:1.2rem; display:flex; align-items:center; gap:1rem;">
    <div style="width:48px; height:48px; border-radius:12px; background:var(--primary-subtle); color:var(--primary); display:flex; align-items:center; justify-content:center; font-size:1.3rem;">
      <i class="fa-solid fa-database"></i>
    </div>
    <div>
      <div style="font-size:1.5rem; font-weight:800; color:var(--text-heading); line-height:1.1;"><?= number_format($totalRecords) ?></div>
      <div style="font-size:0.8rem; color:var(--text-muted); font-weight:500;">Total Student Pool</div>
    </div>
  </div>
  <div class="card" style="padding:1.2rem; display:flex; align-items:center; gap:1rem;">
    <div style="width:48px; height:48px; border-radius:12px; background:#fef3c7; color:#b45309; display:flex; align-items:center; justify-content:center; font-size:1.3rem;">
      <i class="fa-solid fa-filter"></i>
    </div>
    <div>
      <div style="font-size:1.5rem; font-weight:800; color:#b45309; line-height:1.1;"><?= number_format($unconvertedCount) ?></div>
      <div style="font-size:0.8rem; color:var(--text-muted); font-weight:500;">Pending Conversion (NO)</div>
    </div>
  </div>
  <div class="card" style="padding:1.2rem; display:flex; align-items:center; gap:1rem;">
    <div style="width:48px; height:48px; border-radius:12px; background:#dcfce7; color:#15803d; display:flex; align-items:center; justify-content:center; font-size:1.3rem;">
      <i class="fa-solid fa-check-double"></i>
    </div>
    <div>
      <div style="font-size:1.5rem; font-weight:800; color:#15803d; line-height:1.1;"><?= number_format($convertedCount) ?></div>
      <div style="font-size:0.8rem; color:var(--text-muted); font-weight:500;">Converted to Leads (YES)</div>
    </div>
  </div>
</div>

<!-- Filters Bar -->
<div class="card" style="margin-bottom:1.5rem; padding:1.25rem;">
  <form method="GET" action="<?= base_url('data/college_data.php') ?>" style="display:flex; flex-wrap:wrap; gap:0.75rem; align-items:center;">
    <div style="flex:1; min-width:240px; position:relative;">
      <input type="text" name="search" class="form-control" placeholder="Search by student, phone, college..." value="<?= htmlspecialchars($search) ?>" style="padding-left:2.4rem;">
      <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-light);"></i>
    </div>

    <div style="min-width:160px;">
      <select name="degree" class="form-control">
        <option value="">All Degrees</option>
        <?php foreach ($degrees as $d): ?>
          <option value="<?= htmlspecialchars($d) ?>" <?= $degreeFilter === $d ? 'selected' : '' ?>><?= htmlspecialchars($d) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div style="min-width:160px;">
      <select name="status" class="form-control">
        <option value="">All Statuses</option>
        <option value="0" <?= $statusFilter === '0' ? 'selected' : '' ?>>Unconverted Only (NO)</option>
        <option value="1" <?= $statusFilter === '1' ? 'selected' : '' ?>>Converted Only (YES)</option>
      </select>
    </div>

    <?php if (is_super_admin()): ?>
      <div style="min-width:170px;">
        <select name="branch_id" class="form-control">
          <option value="0">All Branches</option>
          <?php foreach ($branches as $b): ?>
            <option value="<?= $b['id'] ?>" <?= $branchFilter === (int)$b['id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    <?php endif; ?>

    <button type="submit" class="btn btn-primary btn-sm">
      <i class="fa-solid fa-filter"></i> Apply Filter
    </button>
    <a href="<?= base_url('data/college_data.php') ?>" class="btn btn-secondary btn-sm" title="Reset Filters">
      <i class="fa-solid fa-rotate-left"></i>
    </a>
  </form>
</div>

<!-- Main Data Table -->
<div class="card">
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Student Details</th>
          <th>Contact & Phone</th>
          <th>Academic Institution</th>
          <th>Degree / Stream</th>
          <th>Grad Year</th>
          <th>Branch</th>
          <th>Status</th>
          <th style="text-align:right;">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($records)): ?>
          <tr>
            <td colspan="8" style="text-align:center; padding:3rem; color:var(--text-muted);">
              <i class="fa-solid fa-folder-open" style="font-size:2rem; margin-bottom:0.75rem; display:block; color:var(--text-light);"></i>
              No college pool records match your current search criteria.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($records as $row): 
            $isConverted = (bool)$row['converted_to_lead'];
          ?>
            <tr>
              <td>
                <div style="font-weight:700; color:var(--text-heading); font-size:0.9rem;">
                  <?= htmlspecialchars($row['student_name']) ?>
                </div>
                <div style="font-size:0.75rem; color:var(--text-muted);">
                  <?= htmlspecialchars($row['city'] ?? 'Location N/A') ?>
                </div>
              </td>
              <td>
                <div style="font-family:monospace; font-weight:600; color:var(--primary); font-size:0.85rem;">
                  <i class="fa-solid fa-phone" style="font-size:0.75rem; margin-right:4px;"></i> <?= htmlspecialchars($row['phone']) ?>
                </div>
                <?php if (!empty($row['email'])): ?>
                  <div style="font-size:0.75rem; color:var(--text-muted);">
                    <i class="fa-regular fa-envelope" style="font-size:0.72rem; margin-right:4px;"></i> <?= htmlspecialchars($row['email']) ?>
                  </div>
                <?php endif; ?>
              </td>
              <td>
                <div style="font-weight:600; color:var(--text-heading); font-size:0.85rem;">
                  <?= htmlspecialchars($row['college_name'] ?? 'N/A') ?>
                </div>
              </td>
              <td>
                <span class="badge badge-secondary" style="font-weight:600; font-size:0.75rem;">
                  <?= htmlspecialchars($row['degree'] ?? 'General') ?>
                </span>
                <?php if (!empty($row['stream'])): ?>
                  <div style="font-size:0.72rem; color:var(--text-muted); margin-top:2px;">
                    <?= htmlspecialchars($row['stream']) ?>
                  </div>
                <?php endif; ?>
              </td>
              <td style="font-weight:600; font-size:0.85rem;">
                <?= htmlspecialchars($row['graduation_year'] ?? '—') ?>
              </td>
              <td>
                <span style="font-size:0.8rem; font-weight:600; color:var(--text-muted);">
                  <?= htmlspecialchars($row['branch_name'] ?? 'Unassigned') ?>
                </span>
              </td>
              <td>
                <?php if ($isConverted): ?>
                  <span class="badge badge-success" style="font-weight:700; font-size:0.75rem;">
                    <i class="fa-solid fa-check"></i> CONVERTED
                  </span>
                  <?php if (!empty($row['lead_code'])): ?>
                    <a href="<?= base_url('leads/view.php?id=' . $row['lead_id']) ?>" style="display:block; font-size:0.72rem; color:var(--primary); font-family:monospace; margin-top:3px; text-decoration:none;">
                      <?= htmlspecialchars($row['lead_code']) ?> &rarr;
                    </a>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="badge badge-warning" style="font-weight:600; font-size:0.75rem;">
                    NO (Raw Pool)
                  </span>
                <?php endif; ?>
              </td>
              <td style="text-align:right;">
                <?php if (!$isConverted && (can('leads', 'add') || can('college_data', 'edit'))): ?>
                  <button type="button" class="btn btn-primary btn-sm" onclick="openConvertModal(<?= htmlspecialchars(json_encode($row)) ?>)">
                    <i class="fa-solid fa-arrow-right-to-bracket"></i> Convert to Lead
                  </button>
                <?php elseif ($isConverted && !empty($row['lead_id'])): ?>
                  <a href="<?= base_url('leads/view.php?id=' . $row['lead_id']) ?>" class="btn btn-secondary btn-sm">
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

<!-- Convert College Data to Lead Modal -->
<div class="modal fade" id="convertModal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(12,36,60,0.5); align-items:center; justify-content:center;">
  <div class="modal-dialog" style="background:#fff; border-radius:14px; max-width:540px; width:92%; margin:auto; box-shadow:0 20px 50px rgba(0,0,0,0.2); overflow:hidden;">
    <form method="POST" action="<?= base_url('leads/convert_college_data.php') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="college_data_id" id="modal_college_data_id" value="">
      <div class="modal-header" style="padding:1.25rem 1.5rem; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
        <h3 style="font-size:1.15rem; font-weight:700; margin:0; color:var(--text-heading);">
          <i class="fa-solid fa-user-plus" style="color:var(--primary); margin-right:8px;"></i> Convert to Active Lead
        </h3>
        <button type="button" onclick="closeConvertModal()" style="border:none; background:none; font-size:1.4rem; cursor:pointer; color:var(--text-muted);">&times;</button>
      </div>

      <div class="modal-body" style="padding:1.5rem;">
        <div style="background:var(--bg-subtle); border-radius:8px; padding:0.85rem; margin-bottom:1.25rem; font-size:0.85rem;">
          <div style="font-weight:700; color:var(--text-heading);" id="modal_student_name">—</div>
          <div style="color:var(--text-muted);" id="modal_student_meta">—</div>
        </div>

        <div class="form-group" style="margin-bottom:1rem;">
          <label class="form-label">Assign to Branch <span style="color:red;">*</span></label>
          <select name="branch_id" id="modal_branch_id" class="form-control" required onchange="filterCounselorsByBranch(this.value)">
            <option value="">Select Branch...</option>
            <?php foreach ($branches as $b): ?>
              <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group" style="margin-bottom:1rem;">
          <label class="form-label">Assign to Counselor <span style="color:red;">*</span></label>
          <select name="counselor_id" id="modal_counselor_id" class="form-control" required>
            <option value="">Select Counselor...</option>
            <?php foreach ($counselors as $c): ?>
              <option value="<?= $c['id'] ?>" data-branch="<?= $c['branch_id'] ?>">
                <?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['branch_name'] ?? 'Counselor') ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group" style="margin-bottom:1rem;">
          <label class="form-label">Target Study Destination</label>
          <select name="preferred_country" class="form-control">
            <option value="United Kingdom">United Kingdom</option>
            <option value="Canada">Canada</option>
            <option value="Australia">Australia</option>
            <option value="United States">United States</option>
            <option value="Ireland">Ireland</option>
            <option value="Germany">Germany</option>
            <option value="New Zealand">New Zealand</option>
            <option value="Dubai">Dubai</option>
          </select>
        </div>

        <div class="form-group" style="margin-bottom:0.5rem;">
          <label class="form-label">Initial Consultation Note</label>
          <textarea name="notes" class="form-control" rows="2" placeholder="Converted from college pool. Student interested in Master's degrees..."></textarea>
        </div>
      </div>

      <div class="modal-footer" style="padding:1rem 1.5rem; border-top:1px solid var(--border-color); display:flex; justify-content:flex-end; gap:0.5rem;">
        <button type="button" onclick="closeConvertModal()" class="btn btn-secondary btn-sm">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm">
          <i class="fa-solid fa-check"></i> Complete Conversion
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function openConvertModal(data) {
  document.getElementById('modal_college_data_id').value = data.id;
  document.getElementById('modal_student_name').innerText = data.student_name;
  document.getElementById('modal_student_meta').innerText = (data.college_name || 'N/A') + ' • ' + (data.degree || 'Degree') + ' • ' + data.phone;
  
  if (data.branch_id) {
    document.getElementById('modal_branch_id').value = data.branch_id;
    filterCounselorsByBranch(data.branch_id);
  } else {
    document.getElementById('modal_branch_id').selectedIndex = 0;
  }
  
  const modal = document.getElementById('convertModal');
  modal.style.display = 'flex';
}

function closeConvertModal() {
  document.getElementById('convertModal').style.display = 'none';
}

function filterCounselorsByBranch(branchId) {
  const counselorSelect = document.getElementById('modal_counselor_id');
  const options = counselorSelect.querySelectorAll('option');
  
  options.forEach((opt, idx) => {
    if (idx === 0) return; // Keep "Select Counselor..."
    const cBranch = opt.getAttribute('data-branch');
    if (!branchId || cBranch === branchId) {
      opt.style.display = '';
    } else {
      opt.style.display = 'none';
      if (opt.selected) counselorSelect.selectedIndex = 0;
    }
  });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
