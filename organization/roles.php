<?php
/**
 * Dynamic Roles & Granular Permissions Matrix
 * University-Level Education Consultancy CRM (EdrootsCRM)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_permission('roles', 'view');

if (!is_super_admin()) {
    set_flash('danger', 'Access Denied: The Permissions Matrix is strictly reserved for the Super Admin.');
    redirect('dashboard/index.php');
}

$currentUser = current_user();
$pageTitle = 'Roles & Permissions Matrix';
$activePage = 'roles';

$selectedRoleId = isset($_GET['role_id']) ? (int)$_GET['role_id'] : 2; // Default to Admissions Manager (2)

// Handle Matrix Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? '');
    $roleId = (int)($_POST['role_id'] ?? 0);

    if ($roleId == 1) {
        set_flash('warning', 'Super Admin permissions are immutable and permanently unrestricted.');
        redirect('organization/roles.php?role_id=1');
    }

    $perms = $_POST['perms'] ?? [];
    $modules = get_crm_modules();

    try {
        $pdo->beginTransaction();

        $delStmt = $pdo->prepare("DELETE FROM role_permissions WHERE role_id = ?");
        $delStmt->execute([$roleId]);

        $insStmt = $pdo->prepare("
            INSERT INTO role_permissions (
                role_id, module, can_view, can_add, can_edit, can_delete,
                can_assign, can_reassign, can_approve, can_export, can_archive, can_restore
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $actions = ['view', 'add', 'edit', 'delete', 'assign', 'reassign', 'approve', 'export', 'archive', 'restore'];

        foreach ($modules as $mKey => $mMeta) {
            $rowVals = [$roleId, $mKey];
            foreach ($actions as $act) {
                $rowVals[] = isset($perms[$mKey][$act]) ? 1 : 0;
            }
            $insStmt->execute($rowVals);
        }

        log_activity(
            $currentUser['id'],
            null,
            'Permissions Matrix Updated',
            "Modified role permissions matrix for role ID {$roleId}."
        );

        $pdo->commit();
        clear_permission_cache();
        set_flash('success', 'Role permissions updated successfully. All user sessions reflect these rules immediately.');
        redirect("organization/roles.php?role_id={$roleId}");
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        set_flash('danger', 'Error updating permissions: ' . htmlspecialchars($e->getMessage()));
    }
}

$roles = $pdo->query("SELECT * FROM roles ORDER BY id ASC")->fetchAll();
$modules = get_crm_modules();

// Fetch current permissions for the selected role
$rolePermsStmt = $pdo->prepare("SELECT * FROM role_permissions WHERE role_id = ?");
$rolePermsStmt->execute([$selectedRoleId]);
$rawPerms = $rolePermsStmt->fetchAll();

$currentMatrix = [];
foreach ($rawPerms as $rp) {
    $currentMatrix[$rp['module']] = $rp;
}

$actionsList = [
    'view'     => 'View',
    'add'      => 'Add',
    'edit'     => 'Edit',
    'delete'   => 'Delete',
    'assign'   => 'Assign',
    'reassign' => 'Reassign',
    'approve'  => 'Approve',
    'export'   => 'Export',
    'archive'  => 'Archive',
    'restore'  => 'Restore'
];

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <h1 style="font-size:1.5rem; font-weight:700; color:var(--text-heading); margin:0 0 0.35rem 0;">Roles & Permissions Matrix</h1>
    <p style="font-size:0.875rem; color:var(--text-muted); margin:0;">
      Granular privilege administration. Super Admin retains unrestricted authority. Configure access boundaries for Admissions Managers and Counselors.
    </p>
  </div>
</div>

<!-- Role Switcher Tabs -->
<div style="display:flex; gap:0.5rem; margin-bottom:1.5rem;">
  <?php foreach ($roles as $r): ?>
    <a href="<?= base_url('organization/roles.php?role_id=' . $r['id']) ?>" class="btn <?= ($selectedRoleId === (int)$r['id']) ? 'btn-primary' : 'btn-secondary' ?>" style="font-size:0.875rem; padding:0.5rem 1.25rem;">
      <i class="fa-solid <?= $r['id'] == 1 ? 'fa-crown' : ($r['id'] == 2 ? 'fa-building-user' : 'fa-headset') ?>"></i>
      <?= htmlspecialchars($r['label']) ?>
    </a>
  <?php endforeach; ?>
</div>

<?php if ($selectedRoleId === 1): ?>
  <div class="card" style="padding:1.5rem; border-left:4px solid var(--primary); margin-bottom:1.5rem; background:#f5f3ff;">
    <h3 style="font-size:1.1rem; font-weight:800; color:var(--primary); margin:0 0 0.5rem 0;">
      <i class="fa-solid fa-shield-halved"></i> Super Admin Role (Unrestricted Authority)
    </h3>
    <p style="font-size:0.85rem; color:var(--text-muted); margin:0; line-height:1.5;">
      The Super Admin possesses hardcoded, unrestricted permissions across all 16 modules, all 9 branches, and all data operations. This ensures system governance cannot be compromised or locked out.
    </p>
  </div>
<?php endif; ?>

<form method="POST" action="<?= base_url('organization/roles.php') ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="role_id" value="<?= $selectedRoleId ?>">

  <div class="card">
    <div style="padding:1.25rem; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
      <div>
        <strong style="color:var(--text-heading); font-size:1rem;">Privilege Matrix for: <?= htmlspecialchars(array_column($roles, 'label', 'id')[$selectedRoleId] ?? 'Role') ?></strong>
      </div>
      <?php if ($selectedRoleId != 1): ?>
        <div style="display:flex; gap:0.5rem;">
          <button type="button" class="btn btn-secondary btn-sm" onclick="toggleAllMatrix(true)">Check All</button>
          <button type="button" class="btn btn-secondary btn-sm" onclick="toggleAllMatrix(false)">Clear All</button>
        </div>
      <?php endif; ?>
    </div>

    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th style="width:24%;">Module Name</th>
            <?php foreach ($actionsList as $actKey => $actLabel): ?>
              <th style="text-align:center; font-size:0.75rem; width:7.6%;"><?= $actLabel ?></th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($modules as $mKey => $mMeta): 
            $mPerm = $currentMatrix[$mKey] ?? [];
          ?>
            <tr>
              <td>
                <div style="font-weight:700; color:var(--text-heading); font-size:0.875rem;">
                  <i class="fa-solid <?= $mMeta['icon'] ?>" style="color:var(--primary); width:18px; margin-right:4px;"></i>
                  <?= htmlspecialchars($mMeta['label']) ?>
                </div>
                <div style="font-size:0.72rem; color:var(--text-muted); line-height:1.2; margin-top:2px;">
                  <?= htmlspecialchars($mMeta['desc']) ?>
                </div>
              </td>

              <?php foreach ($actionsList as $actKey => $actLabel): 
                $colName = 'can_' . $actKey;
                $isChecked = !empty($mPerm[$colName]) || ($selectedRoleId == 1);
              ?>
                <td style="text-align:center;">
                  <?php if ($selectedRoleId == 1): ?>
                    <i class="fa-solid fa-check" style="color:var(--success); font-size:0.9rem;"></i>
                  <?php else: ?>
                    <input type="checkbox" class="matrix-checkbox" name="perms[<?= $mKey ?>][<?= $actKey ?>]" value="1" <?= $isChecked ? 'checked' : '' ?>>
                  <?php endif; ?>
                </td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php if ($selectedRoleId != 1): ?>
      <div style="padding:1.25rem; border-top:1px solid var(--border-color); display:flex; justify-content:flex-end;">
        <button type="submit" class="btn btn-primary btn-lg">
          <i class="fa-solid fa-shield-halved"></i> Save Role Permissions
        </button>
      </div>
    <?php endif; ?>
  </div>
</form>

<script>
function toggleAllMatrix(state) {
  document.querySelectorAll('.matrix-checkbox').forEach(cb => cb.checked = state);
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
