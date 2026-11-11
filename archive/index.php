<?php
/**
 * Archive Management & Permanent Deletion Audit Log
 * University-Level Education Consultancy CRM (EdrootsCRM)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_permission('archive', 'view');

$currentUser = current_user();
$pageTitle = 'Archive Repository';
$activePage = 'archive';

$tab = $_GET['tab'] ?? 'leads'; // leads, customers, college_data, deletion_logs
$search = trim($_GET['search'] ?? '');
$scopeClause = get_crm_scope_clause('l', 'assigned_counselor_id', 'branch_id');

// Counts
$countArchivedLeads = (int)$pdo->query("SELECT COUNT(*) FROM leads l WHERE l.is_archived = 1 AND l.is_customer = 0 AND {$scopeClause}")->fetchColumn();
$countArchivedCustomers = (int)$pdo->query("SELECT COUNT(*) FROM leads l WHERE l.is_archived = 1 AND l.is_customer = 1 AND {$scopeClause}")->fetchColumn();
$countArchivedCollege = (int)$pdo->query("SELECT COUNT(*) FROM college_data WHERE is_archived = 1")->fetchColumn();
$countDeletionLogs = (int)$pdo->query("SELECT COUNT(*) FROM deletion_logs")->fetchColumn();

// Fetch Records according to active tab
$records = [];
if ($tab === 'leads') {
    $stmt = $pdo->query("
        SELECT l.*, b.name as branch_name, u.name as counselor_name
        FROM leads l
        LEFT JOIN branches b ON l.branch_id = b.id
        LEFT JOIN users u ON l.assigned_counselor_id = u.id
        WHERE l.is_archived = 1 AND l.is_customer = 0 AND {$scopeClause}
        ORDER BY l.archived_at DESC
    ");
    $records = $stmt->fetchAll();
} elseif ($tab === 'customers') {
    $stmt = $pdo->query("
        SELECT l.*, b.name as branch_name, u.name as counselor_name
        FROM leads l
        LEFT JOIN branches b ON l.branch_id = b.id
        LEFT JOIN users u ON l.assigned_counselor_id = u.id
        WHERE l.is_archived = 1 AND l.is_customer = 1 AND {$scopeClause}
        ORDER BY l.archived_at DESC
    ");
    $records = $stmt->fetchAll();
} elseif ($tab === 'college_data') {
    $stmt = $pdo->query("
        SELECT c.*, b.name as branch_name
        FROM college_data c
        LEFT JOIN branches b ON c.branch_id = b.id
        WHERE c.is_archived = 1
        ORDER BY c.id DESC
    ");
    $records = $stmt->fetchAll();
} elseif ($tab === 'deletion_logs') {
    $stmt = $pdo->query("
        SELECT dl.*, u.name as deleted_by_name
        FROM deletion_logs dl
        LEFT JOIN users u ON dl.deleted_by = u.id
        ORDER BY dl.id DESC
    ");
    $records = $stmt->fetchAll();
}

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
  <a href="<?= base_url('data/college_data.php') ?>" class="pdf-tab" style="padding:0.6rem 1.15rem; font-weight:700; text-decoration:none; font-size:0.875rem; border-radius:8px 8px 0 0; color:var(--text-muted); border-bottom:3px solid transparent; white-space:nowrap;">
    <i class="fa-solid fa-database"></i> College Datas
  </a>
  <a href="<?= base_url('archive/index.php') ?>" class="pdf-tab active" style="padding:0.6rem 1.15rem; font-weight:700; text-decoration:none; font-size:0.875rem; border-radius:8px 8px 0 0; color:var(--primary); border-bottom:3px solid var(--primary); background:#f5f3ff; white-space:nowrap;">
    <i class="fa-solid fa-box-archive"></i> Archived <span class="badge badge-secondary" style="margin-left:4px; font-size:0.75rem;"><?= ($countArchivedLeads + $countArchivedCustomers + $countArchivedCollege) ?></span>
  </a>
  <a href="<?= base_url('tasks/index.php') ?>" class="pdf-tab" style="padding:0.6rem 1.15rem; font-weight:700; text-decoration:none; font-size:0.875rem; border-radius:8px 8px 0 0; color:var(--text-muted); border-bottom:3px solid transparent; white-space:nowrap;">
    <i class="fa-solid fa-list-check"></i> My Tasks
  </a>
</div>

<div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <h1 style="font-size:1.5rem; font-weight:700; color:var(--text-heading); margin:0 0 0.35rem 0;">Archive Repository & Purge Logs</h1>
    <p style="font-size:0.875rem; color:var(--text-muted); margin:0;">
      Soft-archived student leads and raw data pools. Two-step permanent deletion requires typed justification recorded in the immutable audit trail.
    </p>
  </div>
</div>

<!-- Tabs -->
<div style="display:flex; gap:0.5rem; margin-bottom:1.5rem; overflow-x:auto;">
  <a href="<?= base_url('archive/index.php?tab=leads') ?>" class="btn <?= $tab === 'leads' ? 'btn-primary' : 'btn-secondary' ?>" style="font-size:0.85rem; padding:0.45rem 1rem;">
    <i class="fa-solid fa-user-graduate"></i> Archived Leads (<?= $countArchivedLeads ?>)
  </a>
  <a href="<?= base_url('archive/index.php?tab=customers') ?>" class="btn <?= $tab === 'customers' ? 'btn-primary' : 'btn-secondary' ?>" style="font-size:0.85rem; padding:0.45rem 1rem;">
    <i class="fa-solid fa-certificate"></i> Archived Customers (<?= $countArchivedCustomers ?>)
  </a>
  <a href="<?= base_url('archive/index.php?tab=college_data') ?>" class="btn <?= $tab === 'college_data' ? 'btn-primary' : 'btn-secondary' ?>" style="font-size:0.85rem; padding:0.45rem 1rem;">
    <i class="fa-solid fa-database"></i> Archived College Pool (<?= $countArchivedCollege ?>)
  </a>
  <a href="<?= base_url('archive/index.php?tab=deletion_logs') ?>" class="btn <?= $tab === 'deletion_logs' ? 'btn-primary' : 'btn-secondary' ?>" style="font-size:0.85rem; padding:0.45rem 1rem;">
    <i class="fa-solid fa-shield-halved"></i> Deletion Audit Logs (<?= $countDeletionLogs ?>)
  </a>
</div>

<!-- Main Table -->
<div class="card">
  <div class="table-responsive">
    <table class="data-table">
      <?php if ($tab === 'deletion_logs'): ?>
        <thead>
          <tr>
            <th>Timestamp</th>
            <th>Record Type</th>
            <th>Identifier / Name</th>
            <th>Typed Justification</th>
            <th>Purged By</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($records)): ?>
            <tr>
              <td colspan="5" style="text-align:center; padding:3rem; color:var(--text-muted);">
                No permanent deletion records logged yet.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($records as $dl): ?>
              <tr>
                <td style="font-size:0.825rem; font-weight:600;"><?= date('d M Y, h:i A', strtotime($dl['deleted_at'])) ?></td>
                <td><span class="badge badge-secondary"><?= htmlspecialchars($dl['record_type']) ?></span></td>
                <td><strong style="color:var(--text-heading);"><?= htmlspecialchars($dl['record_identifier']) ?></strong></td>
                <td style="font-size:0.825rem; color:#b91c1c; font-style:italic; max-width:320px;"><?= htmlspecialchars($dl['justification']) ?></td>
                <td style="font-size:0.8rem; color:var(--text-muted);"><?= htmlspecialchars($dl['deleted_by_name'] ?? 'Admin') ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>

      <?php elseif ($tab === 'college_data'): ?>
        <thead>
          <tr>
            <th>Student Name</th>
            <th>Phone</th>
            <th>College Name</th>
            <th>Degree</th>
            <th>Branch</th>
            <th style="text-align:right;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($records)): ?>
            <tr><td colspan="6" style="text-align:center; padding:3rem; color:var(--text-muted);">No archived college records.</td></tr>
          <?php else: ?>
            <?php foreach ($records as $cd): ?>
              <tr>
                <td><strong><?= htmlspecialchars($cd['student_name']) ?></strong></td>
                <td style="font-family:monospace;"><?= htmlspecialchars($cd['phone']) ?></td>
                <td><?= htmlspecialchars($cd['college_name']) ?></td>
                <td><?= htmlspecialchars($cd['degree']) ?></td>
                <td><?= htmlspecialchars($cd['branch_name'] ?? 'Unassigned') ?></td>
                <td style="text-align:right;">
                  <a href="<?= base_url('archive/action.php?type=college_data&id=' . $cd['id'] . '&action=restore') ?>" class="btn btn-outline-success btn-sm" title="Restore / Unzip (User Manual Section 9.6.1)">
                    <i class="fa-solid fa-file-zipper"></i> Unzip
                  </a>
                  <?php if (is_super_admin()): ?>
                    <button type="button" class="btn btn-danger btn-sm" onclick="openPurgeModal('college_data', <?= (int)$cd['id'] ?>, <?= htmlspecialchars(json_encode($cd['student_name'])) ?>)">
                      <i class="fa-solid fa-trash"></i> Delete
                    </button>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>

      <?php else: ?>
        <thead>
          <tr>
            <th>Student / Code</th>
            <th>Phone & Contact</th>
            <th>Admission Stage</th>
            <th>Branch & Counselor</th>
            <th>Archived Reason</th>
            <th>Archived Date</th>
            <th style="text-align:right;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($records)): ?>
            <tr><td colspan="7" style="text-align:center; padding:3rem; color:var(--text-muted);">No archived records in this view.</td></tr>
          <?php else: ?>
            <?php foreach ($records as $ld): ?>
              <tr>
                <td>
                  <strong style="color:var(--text-heading);"><?= htmlspecialchars($ld['full_name']) ?></strong>
                  <div style="font-family:monospace; font-size:0.72rem; color:var(--primary);"><?= htmlspecialchars($ld['lead_code']) ?></div>
                </td>
                <td style="font-family:monospace;"><?= htmlspecialchars($ld['phone']) ?></td>
                <td><span class="badge badge-secondary"><?= htmlspecialchars($ld['stage']) ?></span></td>
                <td>
                  <div><?= htmlspecialchars($ld['counselor_name'] ?? 'Counselor') ?></div>
                  <div style="font-size:0.72rem; color:var(--text-muted);"><?= htmlspecialchars($ld['branch_name'] ?? 'Branch') ?></div>
                </td>
                <td style="font-size:0.8rem; color:var(--text-muted); max-width:240px;"><?= htmlspecialchars($ld['archived_reason'] ?? $ld['archive_reason'] ?? 'Archived') ?></td>
                <td style="font-size:0.825rem;"><?= !empty($ld['archived_at']) ? date('d M Y', strtotime($ld['archived_at'])) : '—' ?></td>
                <td style="text-align:right;">
                  <div style="display:flex; justify-content:flex-end; gap:0.35rem;">
                    <a href="<?= base_url('archive/action.php?type=lead&id=' . $ld['id'] . '&action=restore') ?>" class="btn btn-outline-success btn-sm" title="Restore / Unzip (User Manual Section 9.6.1)">
                      <i class="fa-solid fa-file-zipper"></i> Unzip
                    </a>
                    <?php if (is_super_admin()): 
                      $leadDesc = $ld['full_name'] . ' (' . $ld['lead_code'] . ')';
                    ?>
                      <button type="button" class="btn btn-danger btn-sm" onclick="openPurgeModal('lead', <?= (int)$ld['id'] ?>, <?= htmlspecialchars(json_encode($leadDesc)) ?>)">
                        <i class="fa-solid fa-trash"></i> Delete
                      </button>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      <?php endif; ?>
    </table>
  </div>
</div>

<!-- Two-Step Permanent Deletion Modal with Typed Justification -->
<div class="modal fade" id="purgeModal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(12,36,60,0.5); align-items:center; justify-content:center;">
  <div class="modal-dialog" style="background:#fff; border-radius:14px; max-width:480px; width:92%; margin:auto; box-shadow:0 20px 50px rgba(0,0,0,0.2); overflow:hidden;">
    <form method="POST" action="<?= base_url('archive/action.php') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="delete_permanent">
      <input type="hidden" name="type" id="purge_type" value="lead">
      <input type="hidden" name="id" id="purge_id" value="">

      <div class="modal-header" style="padding:1.25rem 1.5rem; border-bottom:1px solid var(--border-color); background:#fee2e2; display:flex; justify-content:space-between; align-items:center;">
        <h3 style="font-size:1.15rem; font-weight:800; margin:0; color:#b91c1c;">
          <i class="fa-solid fa-triangle-exclamation"></i> 2-Step Permanent Purge
        </h3>
        <button type="button" onclick="closePurgeModal()" style="border:none; background:none; font-size:1.4rem; cursor:pointer; color:#b91c1c;">&times;</button>
      </div>

      <div class="modal-body" style="padding:1.5rem;">
        <p style="font-size:0.85rem; color:#b91c1c; font-weight:600; margin:0 0 1rem 0;">
          CAUTION: This will permanently delete <strong id="purge_target_name">—</strong> from the MySQL database along with all its consultation logs, documents, and records. This action CANNOT be undone.
        </p>

        <div class="form-group" style="margin-bottom:0.5rem;">
          <label class="form-label">Mandatory Typed Justification <span style="color:red;">*</span></label>
          <textarea name="justification" class="form-control" rows="3" placeholder="Explain the compliance or operational reason for complete permanent removal (e.g., student formal GDPR deletion request)..." required></textarea>
          <small style="color:var(--text-muted); font-size:0.75rem; display:block; margin-top:2px;">This justification will be permanently written to the immutable Deletion Audit Log.</small>
        </div>
      </div>

      <div class="modal-footer" style="padding:1rem 1.5rem; border-top:1px solid var(--border-color); display:flex; justify-content:flex-end; gap:0.5rem;">
        <button type="button" onclick="closePurgeModal()" class="btn btn-secondary btn-sm">Cancel</button>
        <button type="submit" class="btn btn-danger btn-sm">Permanently Purge Record</button>
      </div>
    </form>
  </div>
</div>

<script>
function openPurgeModal(type, id, name) {
  document.getElementById('purge_type').value = type;
  document.getElementById('purge_id').value = id;
  document.getElementById('purge_target_name').innerText = name;
  document.getElementById('purgeModal').style.display = 'flex';
}
function closePurgeModal() {
  document.getElementById('purgeModal').style.display = 'none';
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
