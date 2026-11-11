<?php
/**
 * Notifications Center (Attended / Not Attended Workflow)
 * University-Level Education Consultancy CRM (EdrootsCRM)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$currentUser = current_user();
$pageTitle = 'Notifications Center';
$activePage = 'notifications';

$statusFilter = trim($_GET['status'] ?? 'Not Attended'); // 'Not Attended', 'Attended', 'All'

$where = ["(n.user_id = ? OR n.user_id IS NULL)"];
$params = [$currentUser['id']];

if ($statusFilter !== 'All') {
    $where[] = "n.status = ?";
    $params[] = $statusFilter;
}

$whereSql = implode(' AND ', $where);

// Counts
$notAttendedCount = (int)$pdo->query("SELECT COUNT(*) FROM notifications WHERE (user_id = {$currentUser['id']} OR user_id IS NULL) AND status = 'Not Attended'")->fetchColumn();
$attendedCount = (int)$pdo->query("SELECT COUNT(*) FROM notifications WHERE (user_id = {$currentUser['id']} OR user_id IS NULL) AND status = 'Attended'")->fetchColumn();

$sql = "
    SELECT n.*
    FROM notifications n
    WHERE {$whereSql}
    ORDER BY n.id DESC
    LIMIT 100
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$notifs = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <h1 style="font-size:1.5rem; font-weight:700; color:var(--text-heading); margin:0 0 0.35rem 0;">Notifications Center</h1>
    <p style="font-size:0.875rem; color:var(--text-muted); margin:0;">
      Actionable admission alerts, follow-up reminders, and new lead assignments with Attended / Not Attended tracking.
    </p>
  </div>
  <div>
    <?php if ($notAttendedCount > 0): ?>
      <a href="<?= base_url('notifications/mark_attended.php?all=1&return=' . urlencode(base_url('notifications/index.php'))) ?>" class="btn btn-secondary btn-sm">
        <i class="fa-solid fa-check-double"></i> Mark All as Attended
      </a>
    <?php endif; ?>
  </div>
</div>

<!-- Tabs -->
<div style="display:flex; gap:0.5rem; margin-bottom:1.5rem;">
  <a href="<?= base_url('notifications/index.php?status=Not+Attended') ?>" class="btn <?= $statusFilter === 'Not Attended' ? 'btn-primary' : 'btn-secondary' ?>" style="font-size:0.85rem; padding:0.45rem 1rem;">
    Not Attended (<?= $notAttendedCount ?>)
  </a>
  <a href="<?= base_url('notifications/index.php?status=Attended') ?>" class="btn <?= $statusFilter === 'Attended' ? 'btn-primary' : 'btn-secondary' ?>" style="font-size:0.85rem; padding:0.45rem 1rem;">
    Attended (<?= $attendedCount ?>)
  </a>
  <a href="<?= base_url('notifications/index.php?status=All') ?>" class="btn <?= $statusFilter === 'All' ? 'btn-primary' : 'btn-secondary' ?>" style="font-size:0.85rem; padding:0.45rem 1rem;">
    All Alerts
  </a>
</div>

<!-- Notifications List -->
<div class="card">
  <?php if (empty($notifs)): ?>
    <div style="padding:3.5rem; text-align:center; color:var(--text-muted);">
      <i class="fa-regular fa-bell-slash" style="font-size:2.5rem; color:var(--text-light); margin-bottom:0.75rem; display:block;"></i>
      No notifications found in this view.
    </div>
  <?php else: ?>
    <div style="display:flex; flex-direction:column;">
      <?php foreach ($notifs as $n): 
        $isNotAttended = ($n['status'] === 'Not Attended');
      ?>
        <div style="display:flex; justify-content:space-between; align-items:center; padding:1.15rem 1.5rem; border-bottom:1px solid var(--border-color); background:<?= $isNotAttended ? '#ffffff' : '#f0fdf4' ?>;">
          <div style="display:flex; align-items:center; gap:1rem;">
            <div style="width:40px; height:40px; border-radius:50%; background:<?= $isNotAttended ? '#1e293b' : '#16a34a' ?>; color:#fff; display:flex; align-items:center; justify-content:center; font-size:1.1rem;">
              <i class="fa-solid fa-bell"></i>
            </div>
            <div>
              <div style="display:flex; align-items:center; gap:0.5rem;">
                <strong style="color:<?= $isNotAttended ? '#0f172a' : '#15803d' ?>; font-size:0.92rem;"><?= htmlspecialchars($n['title']) ?></strong>
                <span class="badge <?= $isNotAttended ? 'badge-secondary' : 'badge-success' ?>" style="font-size:0.68rem; font-weight:700;">
                  <?= htmlspecialchars($n['status']) ?>
                </span>
              </div>
              <div style="font-size:0.825rem; color:<?= $isNotAttended ? '#475569' : '#166534' ?>; margin-top:2px;">
                <?= htmlspecialchars($n['message']) ?>
              </div>
              <div style="font-size:0.72rem; color:var(--text-light); margin-top:3px;">
                <?= time_ago($n['created_at']) ?>
              </div>
            </div>
          </div>

          <div style="display:flex; gap:0.5rem; align-items:center;">
            <?php if (!empty($n['link'])): ?>
              <a href="<?= base_url($n['link']) ?>" class="btn btn-secondary btn-sm" style="font-size:0.8rem;">
                View Details &rarr;
              </a>
            <?php endif; ?>
            <?php if ($isNotAttended): ?>
              <a href="<?= base_url('notifications/mark_attended.php?id=' . $n['id'] . '&return=' . urlencode($_SERVER['REQUEST_URI'] ?? '')) ?>" class="btn btn-outline-success btn-sm" style="font-weight:600; font-size:0.75rem;" title="Marked as Attended">
                <i class="fa-solid fa-check"></i> Mark as Attended
              </a>
            <?php else: ?>
              <a href="<?= base_url('notifications/mark_attended.php?id=' . $n['id'] . '&unattend=1&return=' . urlencode($_SERVER['REQUEST_URI'] ?? '')) ?>" class="btn btn-outline-secondary btn-sm" style="font-size:0.75rem;" title="Marked as Not-Attended">
                <i class="fa-solid fa-rotate-left"></i> Mark as Not-Attended
              </a>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
