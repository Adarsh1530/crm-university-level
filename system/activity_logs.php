<?php
/**
 * System Activity Logs (Immutable Audit Trail)
 * University-Level Education Consultancy CRM (EdrootsCRM)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_permission('settings', 'view');

$currentUser = current_user();
$pageTitle = 'Activity Logs';
$activePage = 'activity_logs';

$search = trim($_GET['search'] ?? '');
$actionFilter = trim($_GET['action_name'] ?? '');

$where = ["1=1"];
$params = [];

if (!empty($search)) {
    $where[] = "(a.action LIKE ? OR a.details LIKE ? OR u.name LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if (!empty($actionFilter)) {
    $where[] = "a.action = ?";
    $params[] = $actionFilter;
}

$whereSql = implode(' AND ', $where);

// Actions list for filter
$actions = $pdo->query("SELECT DISTINCT action FROM activity_logs ORDER BY action ASC")->fetchAll(PDO::FETCH_COLUMN);

$sql = "
    SELECT a.*, u.name as user_name, u.email as user_email, l.lead_code, l.full_name as lead_name
    FROM activity_logs a
    LEFT JOIN users u ON a.user_id = u.id
    LEFT JOIN leads l ON a.lead_id = l.id
    WHERE {$whereSql}
    ORDER BY a.id DESC
    LIMIT 150
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <h1 style="font-size:1.5rem; font-weight:700; color:var(--text-heading); margin:0 0 0.35rem 0;">System Activity Logs</h1>
    <p style="font-size:0.875rem; color:var(--text-muted); margin:0;">
      Immutable audit trail capturing user authentication, lead conversions, follow-up updates, invoice generation, and permissions modifications.
    </p>
  </div>
</div>

<!-- Filters Bar -->
<div class="card" style="margin-bottom:1.5rem; padding:1.25rem;">
  <form method="GET" action="<?= base_url('system/activity_logs.php') ?>" style="display:flex; flex-wrap:wrap; gap:0.75rem; align-items:center;">
    <div style="flex:1; min-width:240px; position:relative;">
      <input type="text" name="search" class="form-control" placeholder="Search by staff name, action, details..." value="<?= htmlspecialchars($search) ?>" style="padding-left:2.4rem;">
      <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-light);"></i>
    </div>

    <div style="min-width:180px;">
      <select name="action_name" class="form-control">
        <option value="">All Action Types</option>
        <?php foreach ($actions as $act): ?>
          <option value="<?= htmlspecialchars($act) ?>" <?= $actionFilter === $act ? 'selected' : '' ?>><?= htmlspecialchars($act) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-filter"></i> Apply</button>
    <a href="<?= base_url('system/activity_logs.php') ?>" class="btn btn-secondary btn-sm"><i class="fa-solid fa-rotate-left"></i></a>
  </form>
</div>

<!-- Logs Table -->
<div class="card">
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Timestamp</th>
          <th>Staff User</th>
          <th>Action Executed</th>
          <th>Related Lead</th>
          <th>Audit Details</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($logs)): ?>
          <tr>
            <td colspan="5" style="text-align:center; padding:3rem; color:var(--text-muted);">
              No activity logs match your search.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($logs as $log): ?>
            <tr>
              <td style="font-size:0.825rem; font-weight:600; color:var(--text-heading); white-space:nowrap;">
                <?= date('d M Y, h:i A', strtotime($log['created_at'])) ?>
              </td>
              <td>
                <div style="font-weight:700; color:var(--text-heading); font-size:0.85rem;">
                  <?= htmlspecialchars($log['user_name'] ?? 'System / Guest') ?>
                </div>
                <?php if (!empty($log['user_email'])): ?>
                  <div style="font-size:0.72rem; color:var(--text-muted);"><?= htmlspecialchars($log['user_email']) ?></div>
                <?php endif; ?>
              </td>
              <td>
                <span class="badge badge-primary" style="font-weight:700; font-size:0.72rem;">
                  <?= htmlspecialchars($log['action']) ?>
                </span>
              </td>
              <td>
                <?php if (!empty($log['lead_id'])): ?>
                  <a href="<?= base_url('leads/view.php?id=' . $log['lead_id']) ?>" style="font-weight:600; color:var(--primary); font-size:0.825rem; text-decoration:none;">
                    <?= htmlspecialchars($log['lead_name'] ?: 'Student') ?>
                  </a>
                  <div style="font-family:monospace; font-size:0.7rem; color:var(--text-muted);">
                    <?= htmlspecialchars($log['lead_code'] ?: '') ?>
                  </div>
                <?php else: ?>
                  <span style="color:var(--text-light); font-size:0.8rem;">—</span>
                <?php endif; ?>
              </td>
              <td style="font-size:0.825rem; color:var(--text-body); max-width:380px; line-height:1.4;">
                <?= htmlspecialchars($log['details']) ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
