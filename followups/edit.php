<?php
/**
 * Edit Follow-up Consultation
 * University-Level Education Consultancy CRM (EdrootsCRM)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_permission('followups', 'edit');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('danger', 'Invalid follow-up identifier.');
    redirect('followups/index.php');
}

$currentUser = current_user();

$stmt = $pdo->prepare("
    SELECT f.*, 
           l.full_name AS lead_name, l.lead_code, l.id AS lead_pk
    FROM followups f
    JOIN leads l ON f.lead_id = l.id
    WHERE f.id = ?
");
$stmt->execute([$id]);
$followup = $stmt->fetch();

if (!$followup) {
    set_flash('danger', 'Follow-up not found.');
    redirect('followups/index.php');
}

$staffMembers = $pdo->query("SELECT id, name, designation FROM users WHERE role_id IN (2, 3) AND status = 'Active' ORDER BY name ASC")->fetchAll();
$targetName = $followup['lead_name'] ?? 'Candidate';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $counselorId = !empty($_POST['counselor_id']) ? (int)$_POST['counselor_id'] : $followup['counselor_id'];
    $followupDate = !empty($_POST['followup_date']) ? $_POST['followup_date'] : $followup['followup_date'];
    $followupTime = !empty($_POST['followup_time']) ? $_POST['followup_time'] : $followup['followup_time'];
    $comms = $_POST['interaction_type'] ?? 'Phone Call';
    $status = $_POST['status'] ?? 'Pending';
    $notes = trim($_POST['notes'] ?? '');
    $nextDate = !empty($_POST['next_followup_date']) ? $_POST['next_followup_date'] : null;

    try {
        $upd = $pdo->prepare("
            UPDATE followups SET
                counselor_id = ?,
                followup_date = ?,
                followup_time = ?,
                interaction_type = ?,
                status = ?,
                notes = ?,
                next_followup_date = ?,
                completed_at = CASE WHEN ? = 'Completed' AND completed_at IS NULL THEN NOW() ELSE completed_at END
            WHERE id = ?
        ");
        $upd->execute([
            $counselorId, $followupDate, $followupTime, $comms,
            $status, $notes, $nextDate, $status, $id
        ]);

        log_activity($currentUser['id'], $followup['lead_id'], 'Follow-up Updated', "Updated follow-up details for {$targetName}.");

        set_flash('success', "Follow-up record for <strong>{$targetName}</strong> updated successfully.");
        redirect('followups/index.php');
    } catch (Exception $e) {
        set_flash('danger', 'Error updating follow-up: ' . $e->getMessage());
    }
}

$pageTitle = 'Edit Follow-up: ' . $targetName;
$activePage = 'followups';

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header" style="margin-bottom:1.5rem;">
  <div>
    <h1 style="font-size:1.5rem; font-weight:700; color:var(--text-heading); margin:0 0 0.35rem 0;">Edit Follow-up Consultation</h1>
    <p style="font-size:0.875rem; color:var(--text-muted); margin:0;">
      Candidate: <strong><?= htmlspecialchars($targetName) ?></strong> (<?= htmlspecialchars($followup['lead_code']) ?>)
    </p>
  </div>
</div>

<div class="card" style="max-width:680px; margin:0 auto; padding:1.75rem;">
  <form method="POST">
    <?= csrf_field() ?>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.25rem; margin-bottom:1.25rem;">
      <div class="form-group">
        <label class="form-label" style="font-weight:700;">Scheduled Date <span style="color:red;">*</span></label>
        <input type="date" name="followup_date" class="form-control" value="<?= htmlspecialchars($followup['followup_date']) ?>" required>
      </div>

      <div class="form-group">
        <label class="form-label" style="font-weight:600;">Consultation Time</label>
        <input type="time" name="followup_time" class="form-control" value="<?= htmlspecialchars($followup['followup_time'] ?? '10:00:00') ?>">
      </div>
    </div>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.25rem; margin-bottom:1.25rem;">
      <div class="form-group">
        <label class="form-label" style="font-weight:700;">Channel</label>
        <select name="interaction_type" class="form-control">
          <option value="Phone Call" <?= ($followup['interaction_type'] === 'Phone Call') ? 'selected' : '' ?>>Phone Call</option>
          <option value="WhatsApp" <?= ($followup['interaction_type'] === 'WhatsApp') ? 'selected' : '' ?>>WhatsApp</option>
          <option value="Office Visit" <?= ($followup['interaction_type'] === 'Office Visit') ? 'selected' : '' ?>>Office Visit</option>
          <option value="Email" <?= ($followup['interaction_type'] === 'Email') ? 'selected' : '' ?>>Email</option>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label" style="font-weight:700;">Status</label>
        <select name="status" class="form-control">
          <option value="Pending" <?= ($followup['status'] === 'Pending') ? 'selected' : '' ?>>Pending</option>
          <option value="Completed" <?= ($followup['status'] === 'Completed') ? 'selected' : '' ?>>Completed</option>
          <option value="Overdue" <?= ($followup['status'] === 'Overdue') ? 'selected' : '' ?>>Overdue</option>
          <option value="Cancelled" <?= ($followup['status'] === 'Cancelled') ? 'selected' : '' ?>>Cancelled</option>
        </select>
      </div>
    </div>

    <?php if (!is_counselor()): ?>
      <div class="form-group" style="margin-bottom:1.25rem;">
        <label class="form-label" style="font-weight:700;">Assigned Counselor</label>
        <select name="counselor_id" class="form-control">
          <?php foreach ($staffMembers as $sm): ?>
            <option value="<?= $sm['id'] ?>" <?= ($followup['counselor_id'] == $sm['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($sm['name']) ?> (<?= htmlspecialchars($sm['designation'] ?? 'Counselor') ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    <?php endif; ?>

    <div class="form-group" style="margin-bottom:1.25rem;">
      <label class="form-label" style="font-weight:700;">Consultation Notes</label>
      <textarea name="notes" class="form-control" rows="4"><?= htmlspecialchars($followup['notes'] ?? '') ?></textarea>
    </div>

    <div class="form-group" style="margin-bottom:1.5rem;">
      <label class="form-label" style="font-weight:600;">Next Follow-up Date (Optional)</label>
      <input type="date" name="next_followup_date" class="form-control" value="<?= htmlspecialchars($followup['next_followup_date'] ?? '') ?>">
    </div>

    <div style="display:flex; justify-content:flex-end; gap:0.75rem; border-top:1px solid var(--border-color); padding-top:1.25rem;">
      <a href="<?= base_url('followups/index.php') ?>" class="btn btn-secondary">Cancel</a>
      <button type="submit" class="btn btn-primary">
        <i class="fa-solid fa-check"></i> Update Follow-up
      </button>
    </div>
  </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
