<?php
/**
 * Staff & User Management Hub
 * University-Level Education Consultancy CRM (EdrootsCRM)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_permission('staff', 'view');

$currentUser = current_user();
$pageTitle = 'Staff Management';
$activePage = 'organization_users';

$search = trim($_GET['search'] ?? '');
$roleFilter = trim($_GET['role'] ?? '');
$branchFilter = isset($_GET['branch_id']) ? (int)$_GET['branch_id'] : 0;

// Scoping: branch manager only sees counselors in their branch
$where = ["1=1"];
$params = [];

if (is_branch_manager()) {
    $where[] = "u.branch_id = ?";
    $params[] = (int)$currentUser['branch_id'];
} elseif ($branchFilter > 0 && is_super_admin()) {
    $where[] = "u.branch_id = ?";
    $params[] = $branchFilter;
}

if (!empty($search)) {
    $where[] = "(u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if (!empty($roleFilter)) {
    $where[] = "r.name = ?";
    $params[] = $roleFilter;
}

$whereSql = implode(' AND ', $where);

// Handle Add / Edit Staff
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (is_super_admin() || is_branch_manager())) {
    verify_csrf_token($_POST['csrf_token'] ?? '');
    $staffId = (int)($_POST['staff_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $roleId = (int)($_POST['role_id'] ?? 3); // Default counselor
    $branchId = is_branch_manager() ? (int)$currentUser['branch_id'] : (!empty($_POST['branch_id']) ? (int)$_POST['branch_id'] : null);
    $reportingManagerId = !empty($_POST['reporting_manager_id']) ? (int)$_POST['reporting_manager_id'] : null;
    $password = trim($_POST['password'] ?? '');

    if (!empty($name) && !empty($email)) {
        if ($staffId > 0) {
            // Update
            if (!empty($password)) {
                $pwdHash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET name = ?, email = ?, phone = ?, role_id = ?, branch_id = ?, reporting_manager_id = ?, password = ?
                    WHERE id = ?
                ");
                $stmt->execute([$name, $email, $phone, $roleId, $branchId, $reportingManagerId, $pwdHash, $staffId]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET name = ?, email = ?, phone = ?, role_id = ?, branch_id = ?, reporting_manager_id = ?
                    WHERE id = ?
                ");
                $stmt->execute([$name, $email, $phone, $roleId, $branchId, $reportingManagerId, $staffId]);
            }
            set_flash('success', "Staff member <strong>" . htmlspecialchars($name) . "</strong> updated successfully.");
        } else {
            // Insert
            $pwdHash = password_hash(!empty($password) ? $password : 'admin123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                INSERT INTO users (name, email, phone, role_id, branch_id, reporting_manager_id, password, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'Active')
            ");
            $stmt->execute([$name, $email, $phone, $roleId, $branchId, $reportingManagerId, $pwdHash]);
            set_flash('success', "Staff account for <strong>" . htmlspecialchars($name) . "</strong> created successfully (Default password: admin123).");
        }
        redirect('organization/users.php');
    }
}

$branches = $pdo->query("SELECT id, name, city FROM branches WHERE status = 'Active' ORDER BY name ASC")->fetchAll();
$roles = $pdo->query("SELECT id, name, label FROM roles ORDER BY id ASC")->fetchAll();
$managers = $pdo->query("
    SELECT u.id, u.name 
    FROM users u 
    JOIN roles r ON u.role_id = r.id 
    WHERE r.name IN ('manager', 'super_admin') AND u.status = 'Active'
    ORDER BY u.name ASC
")->fetchAll();

$sql = "
    SELECT u.*, r.name as role_name, r.label as role_label, b.name as branch_name, m.name as manager_name,
           (SELECT COUNT(*) FROM leads WHERE assigned_counselor_id = u.id AND is_archived = 0) as assigned_leads_count,
           (SELECT COUNT(*) FROM followups WHERE counselor_id = u.id AND status = 'Pending' AND scheduled_date = CURDATE()) as today_tasks_count
    FROM users u
    JOIN roles r ON u.role_id = r.id
    LEFT JOIN branches b ON u.branch_id = b.id
    LEFT JOIN users m ON u.reporting_manager_id = m.id
    WHERE {$whereSql}
    ORDER BY u.role_id ASC, u.name ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$staffList = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <h1 style="font-size:1.5rem; font-weight:700; color:var(--text-heading); margin:0 0 0.35rem 0;">Staff & User Management</h1>
    <p style="font-size:0.875rem; color:var(--text-muted); margin:0;">
      Institutional roster of admissions managers, academic counselors, and admissions personnel with reporting hierarchy.
    </p>
  </div>
  <div style="display:flex; gap:0.5rem; align-items:center;">
    <?php if (can('staff', 'add')): ?>
      <button type="button" class="btn btn-primary btn-sm" onclick="openStaffModal()">
        <i class="fa-solid fa-user-plus"></i> Add Staff Account
      </button>
    <?php endif; ?>
  </div>
</div>

<!-- Filters Bar -->
<div class="card" style="margin-bottom:1.5rem; padding:1.25rem;">
  <form method="GET" action="<?= base_url('organization/users.php') ?>" style="display:flex; flex-wrap:wrap; gap:0.75rem; align-items:center;">
    <div style="flex:1; min-width:240px; position:relative;">
      <input type="text" name="search" class="form-control" placeholder="Search staff name, email, phone..." value="<?= htmlspecialchars($search) ?>" style="padding-left:2.4rem;">
      <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-light);"></i>
    </div>

    <div style="min-width:160px;">
      <select name="role" class="form-control">
        <option value="">All Roles</option>
        <option value="super_admin" <?= $roleFilter === 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
        <option value="manager" <?= $roleFilter === 'manager' ? 'selected' : '' ?>>Admissions Manager</option>
        <option value="counselor" <?= $roleFilter === 'counselor' ? 'selected' : '' ?>>Counselor</option>
      </select>
    </div>

    <?php if (is_super_admin()): ?>
      <div style="min-width:160px;">
        <select name="branch_id" class="form-control">
          <option value="0">All Branches</option>
          <?php foreach ($branches as $b): ?>
            <option value="<?= $b['id'] ?>" <?= $branchFilter === (int)$b['id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    <?php endif; ?>

    <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-filter"></i> Apply</button>
    <a href="<?= base_url('organization/users.php') ?>" class="btn btn-secondary btn-sm"><i class="fa-solid fa-rotate-left"></i></a>
  </form>
</div>

<!-- Staff Table -->
<div class="card">
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Staff Member</th>
          <th>Role & Hierarchy</th>
          <th>Branch Hub</th>
          <th>Reporting Manager</th>
          <th>Assigned Caseload</th>
          <th>Today Tasks</th>
          <th>Status</th>
          <th style="text-align:right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($staffList as $stf): 
          $roleBadgeClass = match($stf['role_name']) {
              'super_admin' => 'badge-priority-high',
              'manager' => 'badge-priority-moderate',
              default => 'badge-secondary'
          };
        ?>
          <tr>
            <td>
              <div style="display:flex; align-items:center; gap:0.6rem;">
                <div style="width:34px; height:34px; border-radius:50%; background:var(--primary-subtle); color:var(--primary); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.85rem;">
                  <?= strtoupper(substr($stf['name'], 0, 1)) ?>
                </div>
                <div>
                  <strong style="color:var(--text-heading); font-size:0.9rem;"><?= htmlspecialchars($stf['name']) ?></strong>
                  <div style="font-size:0.75rem; color:var(--text-muted);"><?= htmlspecialchars($stf['email']) ?></div>
                </div>
              </div>
            </td>
            <td>
              <span class="badge <?= $roleBadgeClass ?>" style="font-size:0.72rem; font-weight:700;">
                <?= htmlspecialchars($stf['role_label']) ?>
              </span>
            </td>
            <td>
              <span style="font-size:0.85rem; font-weight:600; color:var(--text-heading);">
                <?= htmlspecialchars($stf['branch_name'] ?? 'Global Head') ?>
              </span>
            </td>
            <td>
              <span style="font-size:0.825rem; color:var(--text-muted);">
                <?= htmlspecialchars($stf['manager_name'] ?? 'Executive Board') ?>
              </span>
            </td>
            <td>
              <span class="badge badge-secondary" style="font-size:0.75rem; font-weight:700;">
                <?= (int)$stf['assigned_leads_count'] ?> Leads
              </span>
            </td>
            <td>
              <span class="badge <?= $stf['today_tasks_count'] > 0 ? 'badge-blue' : 'badge-secondary' ?>" style="font-size:0.75rem;">
                <?= (int)$stf['today_tasks_count'] ?> Tasks
              </span>
            </td>
            <td>
              <span class="badge badge-success" style="font-size:0.7rem; font-weight:700;">
                <?= htmlspecialchars($stf['status']) ?>
              </span>
            </td>
            <td style="text-align:right;">
              <div style="display:flex; justify-content:flex-end; gap:0.35rem;">
                <a href="<?= base_url('switch_role.php?user_id=' . $stf['id']) ?>" class="btn btn-outline btn-sm" title="Switch to this user session">
                  <i class="fa-solid fa-shuffle"></i> Switch
                </a>
                <?php if (can('staff', 'edit')): ?>
                  <button type="button" class="btn btn-secondary btn-sm" onclick="editStaff(<?= htmlspecialchars(json_encode($stf)) ?>)">
                    <i class="fa-solid fa-pen"></i>
                  </button>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add / Edit Staff Modal -->
<div class="modal fade" id="staffModal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(12,36,60,0.5); align-items:center; justify-content:center;">
  <div class="modal-dialog" style="background:#fff; border-radius:14px; max-width:500px; width:92%; margin:auto; box-shadow:0 20px 50px rgba(0,0,0,0.2); overflow:hidden;">
    <form method="POST" action="<?= base_url('organization/users.php') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="staff_id" id="modal_staff_id" value="">

      <div class="modal-header" style="padding:1.25rem 1.5rem; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
        <h3 style="font-size:1.15rem; font-weight:700; margin:0; color:var(--text-heading);" id="modal_staff_title">
          Add Staff Account
        </h3>
        <button type="button" onclick="closeStaffModal()" style="border:none; background:none; font-size:1.4rem; cursor:pointer; color:var(--text-muted);">&times;</button>
      </div>

      <div class="modal-body" style="padding:1.5rem;">
        <div class="form-group" style="margin-bottom:1rem;">
          <label class="form-label">Full Name <span style="color:red;">*</span></label>
          <input type="text" name="name" id="staff_name" class="form-control" required placeholder="e.g. David Miller">
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; margin-bottom:1rem;">
          <div class="form-group">
            <label class="form-label">Email Address <span style="color:red;">*</span></label>
            <input type="email" name="email" id="staff_email" class="form-control" required placeholder="name@edroots.com">
          </div>
          <div class="form-group">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" id="staff_phone" class="form-control" placeholder="+91 9847000000">
          </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; margin-bottom:1rem;">
          <div class="form-group">
            <label class="form-label">Role <span style="color:red;">*</span></label>
            <select name="role_id" id="staff_role_id" class="form-control" required>
              <?php foreach ($roles as $r): ?>
                <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['label']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Branch Hub</label>
            <select name="branch_id" id="staff_branch_id" class="form-control">
              <option value="">Global / None</option>
              <?php foreach ($branches as $b): ?>
                <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-group" style="margin-bottom:1rem;">
          <label class="form-label">Reporting Manager</label>
          <select name="reporting_manager_id" id="staff_manager_id" class="form-control">
            <option value="">Direct to Board</option>
            <?php foreach ($managers as $m): ?>
              <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group" style="margin-bottom:0.5rem;">
          <label class="form-label">Password <span style="font-weight:400; color:var(--text-muted);">(Leave empty to keep default <code>admin123</code>)</span></label>
          <input type="password" name="password" id="staff_password" class="form-control" placeholder="••••••••">
        </div>
      </div>

      <div class="modal-footer" style="padding:1rem 1.5rem; border-top:1px solid var(--border-color); display:flex; justify-content:flex-end; gap:0.5rem;">
        <button type="button" onclick="closeStaffModal()" class="btn btn-secondary btn-sm">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm">Save Staff Account</button>
      </div>
    </form>
  </div>
</div>

<script>
function openStaffModal() {
  document.getElementById('modal_staff_id').value = '';
  document.getElementById('modal_staff_title').innerText = 'Add Staff Account';
  document.getElementById('staff_name').value = '';
  document.getElementById('staff_email').value = '';
  document.getElementById('staff_phone').value = '';
  document.getElementById('staff_role_id').value = '3';
  document.getElementById('staff_password').value = '';
  document.getElementById('staffModal').style.display = 'flex';
}

function editStaff(s) {
  document.getElementById('modal_staff_id').value = s.id;
  document.getElementById('modal_staff_title').innerText = 'Edit Staff: ' + s.name;
  document.getElementById('staff_name').value = s.name;
  document.getElementById('staff_email').value = s.email;
  document.getElementById('staff_phone').value = s.phone || '';
  document.getElementById('staff_role_id').value = s.role_id;
  document.getElementById('staff_branch_id').value = s.branch_id || '';
  document.getElementById('staff_manager_id').value = s.reporting_manager_id || '';
  document.getElementById('staff_password').value = '';
  document.getElementById('staffModal').style.display = 'flex';
}

function closeStaffModal() {
  document.getElementById('staffModal').style.display = 'none';
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
