<?php
/**
 * Branch Network Management (9 Strategic Consultancy Locations)
 * University-Level Education Consultancy CRM (EdrootsCRM)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_permission('branches', 'view');

$currentUser = current_user();
$pageTitle = 'Branch Network';
$activePage = 'branches';

// Handle Add/Edit Branch (Super Admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_super_admin()) {
    verify_csrf_token($_POST['csrf_token'] ?? '');
    $branchId = (int)($_POST['branch_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? 'Kerala');
    $country = trim($_POST['country'] ?? 'India');
    $address = trim($_POST['address'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $managerId = !empty($_POST['manager_id']) ? (int)$_POST['manager_id'] : null;

    if (!empty($name) && !empty($code)) {
        if ($branchId > 0) {
            $stmt = $pdo->prepare("
                UPDATE branches 
                SET name = ?, code = ?, city = ?, state = ?, country = ?, address = ?, phone = ?, email = ?, manager_id = ?
                WHERE id = ?
            ");
            $stmt->execute([$name, $code, $city, $state, $country, $address, $phone, $email, $managerId, $branchId]);
            set_flash('success', "Branch <strong>" . htmlspecialchars($name) . "</strong> updated successfully.");
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO branches (company_id, name, code, city, state, country, address, phone, email, manager_id)
                VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$name, $code, $city, $state, $country, $address, $phone, $email, $managerId]);
            set_flash('success', "Branch <strong>" . htmlspecialchars($name) . "</strong> created successfully.");
        }
        redirect('organization/branches.php');
    }
}

// Fetch all managers for branch manager dropdown
$managers = $pdo->query("
    SELECT u.id, u.name 
    FROM users u 
    JOIN roles r ON u.role_id = r.id 
    WHERE r.name IN ('manager', 'super_admin') AND u.status = 'Active'
    ORDER BY u.name ASC
")->fetchAll();

// Main branches query with dynamic counts
$sql = "
    SELECT b.*, u.name as manager_name,
           (SELECT COUNT(*) FROM users WHERE branch_id = b.id AND status = 'Active') as staff_count,
           (SELECT COUNT(*) FROM leads WHERE branch_id = b.id AND is_archived = 0) as active_leads_count,
           (SELECT COALESCE(SUM(invoiced_total), 0) FROM leads WHERE branch_id = b.id AND is_customer = 1 AND is_archived = 0) as branch_revenue
    FROM branches b
    LEFT JOIN users u ON b.manager_id = u.id
    ORDER BY b.id ASC
";
$branches = $pdo->query($sql)->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <h1 style="font-size:1.5rem; font-weight:700; color:var(--text-heading); margin:0 0 0.35rem 0;">Branch Network (9 Hubs)</h1>
    <p style="font-size:0.875rem; color:var(--text-muted); margin:0;">
      Regional and international operations hubs. Hierarchical scoping restricts branch managers and counselors to designated branches.
    </p>
  </div>
  <div>
    <?php if (is_super_admin()): ?>
      <button type="button" class="btn btn-primary btn-sm" onclick="openBranchModal()">
        <i class="fa-solid fa-plus"></i> Add New Branch
      </button>
    <?php endif; ?>
  </div>
</div>

<!-- Branches Grid -->
<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); gap:1.25rem;">
  <?php foreach ($branches as $b): ?>
    <div class="card" style="padding:1.5rem; display:flex; flex-direction:column; justify-content:space-between;">
      <div>
        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:0.75rem;">
          <div style="display:flex; align-items:center; gap:0.6rem;">
            <div style="width:40px; height:40px; border-radius:10px; background:var(--primary-subtle); color:var(--primary); display:flex; align-items:center; justify-content:center; font-size:1.15rem; font-weight:800;">
              <i class="fa-solid fa-building-columns"></i>
            </div>
            <div>
              <h3 style="font-size:1.1rem; font-weight:800; color:var(--text-heading); margin:0; line-height:1.1;">
                <?= htmlspecialchars($b['name']) ?>
              </h3>
              <span style="font-family:monospace; font-size:0.75rem; font-weight:700; color:var(--primary);">
                <?= htmlspecialchars($b['code']) ?> &bull; <?= htmlspecialchars($b['city']) ?>, <?= htmlspecialchars($b['country']) ?>
              </span>
            </div>
          </div>
          <span class="badge <?= $b['status'] === 'Active' ? 'badge-success' : 'badge-danger' ?>" style="font-size:0.7rem;">
            <?= htmlspecialchars($b['status']) ?>
          </span>
        </div>

        <div style="font-size:0.825rem; color:var(--text-muted); line-height:1.5; margin-bottom:1rem;">
          <div><i class="fa-solid fa-user-tie" style="width:16px; color:var(--primary);"></i> Manager: <strong><?= htmlspecialchars($b['manager_name'] ?: 'Pending Assignment') ?></strong></div>
          <div><i class="fa-solid fa-location-dot" style="width:16px; color:var(--text-light);"></i> <?= htmlspecialchars($b['address'] ?: 'Regional Office') ?></div>
          <div><i class="fa-solid fa-phone" style="width:16px; color:var(--text-light);"></i> <?= htmlspecialchars($b['phone'] ?: '+91 484 2900000') ?></div>
        </div>

        <!-- Metrics Snapshot -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.5rem; background:var(--bg-subtle); border-radius:8px; padding:0.75rem; font-size:0.8rem; margin-bottom:1rem;">
          <div>
            <span style="color:var(--text-muted); display:block; font-size:0.7rem;">Active Counselors</span>
            <strong style="font-size:1.1rem; color:var(--text-heading);"><?= (int)$b['staff_count'] ?></strong>
          </div>
          <div>
            <span style="color:var(--text-muted); display:block; font-size:0.7rem;">Active Leads</span>
            <strong style="font-size:1.1rem; color:var(--primary);"><?= (int)$b['active_leads_count'] ?></strong>
          </div>
        </div>
      </div>

      <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid var(--border-color); padding-top:0.75rem; font-size:0.8rem;">
        <span style="font-weight:700; color:var(--text-heading);">
          Revenue: <span style="color:#15803d;"><?= format_currency($b['branch_revenue']) ?></span>
        </span>
        <?php if (is_super_admin()): ?>
          <button type="button" class="btn btn-outline btn-sm" style="font-size:0.75rem;" onclick="editBranch(<?= htmlspecialchars(json_encode($b)) ?>)">
            <i class="fa-solid fa-pen"></i> Edit
          </button>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<!-- Add / Edit Branch Modal -->
<div class="modal fade" id="branchModal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(12,36,60,0.5); align-items:center; justify-content:center;">
  <div class="modal-dialog" style="background:#fff; border-radius:14px; max-width:500px; width:92%; margin:auto; box-shadow:0 20px 50px rgba(0,0,0,0.2); overflow:hidden;">
    <form method="POST" action="<?= base_url('organization/branches.php') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="branch_id" id="modal_branch_id" value="">

      <div class="modal-header" style="padding:1.25rem 1.5rem; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
        <h3 style="font-size:1.15rem; font-weight:700; margin:0; color:var(--text-heading);" id="modal_branch_title">
          Add Regional Branch
        </h3>
        <button type="button" onclick="closeBranchModal()" style="border:none; background:none; font-size:1.4rem; cursor:pointer; color:var(--text-muted);">&times;</button>
      </div>

      <div class="modal-body" style="padding:1.5rem;">
        <div style="display:grid; grid-template-columns:2fr 1fr; gap:0.75rem; margin-bottom:1rem;">
          <div class="form-group">
            <label class="form-label">Branch Name <span style="color:red;">*</span></label>
            <input type="text" name="name" id="branch_name" class="form-control" placeholder="e.g. Edroots Kochi Hub" required>
          </div>
          <div class="form-group">
            <label class="form-label">Code <span style="color:red;">*</span></label>
            <input type="text" name="code" id="branch_code" class="form-control" placeholder="COK" required>
          </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; margin-bottom:1rem;">
          <div class="form-group">
            <label class="form-label">City</label>
            <input type="text" name="city" id="branch_city" class="form-control" placeholder="Kochi" required>
          </div>
          <div class="form-group">
            <label class="form-label">Country</label>
            <input type="text" name="country" id="branch_country" class="form-control" value="India" required>
          </div>
        </div>

        <div class="form-group" style="margin-bottom:1rem;">
          <label class="form-label">Designated Branch Manager</label>
          <select name="manager_id" id="branch_manager_id" class="form-control">
            <option value="">Select Manager...</option>
            <?php foreach ($managers as $m): ?>
              <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group" style="margin-bottom:1rem;">
          <label class="form-label">Physical Address</label>
          <input type="text" name="address" id="branch_address" class="form-control" placeholder="MG Road, Ravipuram...">
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; margin-bottom:0.5rem;">
          <div class="form-group">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" id="branch_phone" class="form-control" placeholder="+91 484 2900000">
          </div>
          <div class="form-group">
            <label class="form-label">Email</label>
            <input type="email" name="email" id="branch_email" class="form-control" placeholder="kochi@edroots.com">
          </div>
        </div>
      </div>

      <div class="modal-footer" style="padding:1rem 1.5rem; border-top:1px solid var(--border-color); display:flex; justify-content:flex-end; gap:0.5rem;">
        <button type="button" onclick="closeBranchModal()" class="btn btn-secondary btn-sm">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm">Save Branch</button>
      </div>
    </form>
  </div>
</div>

<script>
function openBranchModal() {
  document.getElementById('modal_branch_id').value = '';
  document.getElementById('modal_branch_title').innerText = 'Add Regional Branch';
  document.getElementById('branch_name').value = '';
  document.getElementById('branch_code').value = '';
  document.getElementById('branch_city').value = '';
  document.getElementById('branch_manager_id').selectedIndex = 0;
  document.getElementById('branch_address').value = '';
  document.getElementById('branch_phone').value = '';
  document.getElementById('branch_email').value = '';
  document.getElementById('branchModal').style.display = 'flex';
}

function editBranch(b) {
  document.getElementById('modal_branch_id').value = b.id;
  document.getElementById('modal_branch_title').innerText = 'Edit Branch: ' + b.name;
  document.getElementById('branch_name').value = b.name;
  document.getElementById('branch_code').value = b.code;
  document.getElementById('branch_city').value = b.city;
  document.getElementById('branch_country').value = b.country;
  document.getElementById('branch_manager_id').value = b.manager_id || '';
  document.getElementById('branch_address').value = b.address || '';
  document.getElementById('branch_phone').value = b.phone || '';
  document.getElementById('branch_email').value = b.email || '';
  document.getElementById('branchModal').style.display = 'flex';
}

function closeBranchModal() {
  document.getElementById('branchModal').style.display = 'none';
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
