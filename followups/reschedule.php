<?php
/**
 * Reschedule Follow-up
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

$targetName = $followup['lead_name'] ?? 'Candidate';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newDate = $_POST['followup_date'] ?? '';
    $newTime = $_POST['followup_time'] ?? '10:00:00';
    $reason = trim($_POST['reason'] ?? '');

    if (empty($newDate)) {
        set_flash('danger', 'Please provide a valid new follow-up date.');
    } else {
        $updatedNotes = $followup['notes'] . " | Rescheduled: " . $reason;
        $upd = $pdo->prepare("
            UPDATE followups SET
                followup_date = ?,
                followup_time = ?,
                notes = ?
            WHERE id = ?
        ");
        $upd->execute([$newDate, $newTime, $updatedNotes, $id]);

        log_activity($currentUser['id'], $followup['lead_id'], 'Follow-up Rescheduled', "Rescheduled follow-up with {$targetName} to {$newDate}. Reason: {$reason}");

        set_flash('success', "Follow-up consultation with <strong>{$targetName}</strong> rescheduled to " . format_date($newDate) . ".");
        redirect('followups/index.php');
    }
}

$pageTitle = 'Reschedule Follow-up: ' . $targetName;
$activePage = 'followups';

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header" style="margin-bottom:1.5rem;">
  <div>
    <h1 style="font-size:1.5rem; font-weight:700; color:var(--text-heading); margin:0 0 0.35rem 0;">Reschedule Follow-up Consultation</h1>
    <p style="font-size:0.875rem; color:var(--text-muted); margin:0;">
      Candidate: <strong><?= htmlspecialchars($targetName) ?></strong> (<?= htmlspecialchars($followup['lead_code']) ?>)
    </p>
  </div>
</div>

<div class="card" style="max-width:580px; margin:0 auto; padding:1.75rem;">
  <form method="POST">
    <?= csrf_field() ?>

    <div class="form-group" style="margin-bottom:1.25rem;">
      <label class="form-label" style="font-weight:700;">New Scheduled Date <span style="color:red;">*</span></label>
      <input type="date" name="followup_date" class="form-control" value="<?= htmlspecialchars($followup['followup_date']) ?>" min="<?= date('Y-m-d') ?>" required>
    </div>

    <div class="form-group" style="margin-bottom:1.25rem;">
      <label class="form-label" style="font-weight:600;">Time of Consultation</label>
      <input type="time" name="followup_time" class="form-control" value="<?= htmlspecialchars($followup['followup_time'] ?? '10:00:00') ?>">
    </div>

    <div class="form-group" style="margin-bottom:1.5rem;">
      <label class="form-label" style="font-weight:700;">Reason for Rescheduling <span style="color:red;">*</span></label>
      <textarea name="reason" class="form-control" rows="3" placeholder="e.g. Student requested callback after semester exams; busy at work..." required></textarea>
    </div>

    <div style="display:flex; justify-content:flex-end; gap:0.75rem; border-top:1px solid var(--border-color); padding-top:1.25rem;">
      <a href="<?= base_url('followups/index.php') ?>" class="btn btn-secondary">Cancel</a>
      <button type="submit" class="btn btn-primary">
        <i class="fa-solid fa-calendar-check"></i> Save New Date
      </button>
    </div>
  </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
