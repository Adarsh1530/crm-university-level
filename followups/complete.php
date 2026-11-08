<?php
/**
 * Mark Follow-up as Completed
 * University-Level Education Consultancy CRM (EdrootsCRM)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_permission('followups', 'edit');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('danger', 'Invalid follow-up ID.');
    redirect('followups/index.php');
}

$currentUser = current_user();

$stmt = $pdo->prepare("
    SELECT f.*, 
           l.full_name AS lead_name, l.lead_code, l.id AS lead_pk, l.stage AS lead_stage,
           u.name AS staff_name
    FROM followups f
    JOIN leads l ON f.lead_id = l.id
    LEFT JOIN users u ON f.counselor_id = u.id
    WHERE f.id = ?
");
$stmt->execute([$id]);
$followup = $stmt->fetch();

if (!$followup) {
    set_flash('danger', 'Follow-up record not found.');
    redirect('followups/index.php');
}

$targetName = $followup['lead_name'] ?? 'Candidate';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $outcomeNotes = trim($_POST['outcome_notes'] ?? '');
    $nextDate = !empty($_POST['next_followup_date']) ? $_POST['next_followup_date'] : null;

    $updatedNotes = $followup['notes'];
    if (!empty($outcomeNotes)) {
        $updatedNotes .= " | Outcome: " . $outcomeNotes;
    }

    try {
        $pdo->beginTransaction();

        $upd = $pdo->prepare("
            UPDATE followups SET
                status = 'Completed',
                notes = ?,
                next_followup_date = ?,
                completed_at = NOW(),
                completed_by = ?
            WHERE id = ?
        ");
        $upd->execute([$updatedNotes, $nextDate, $currentUser['id'], $id]);

        log_activity($currentUser['id'], $followup['lead_id'], 'Follow-up Completed', "Completed {$followup['interaction_type']} consultation with {$targetName}.");

        // If next date was scheduled, create next follow-up
        if (!empty($nextDate)) {
            $nextIns = $pdo->prepare("
                INSERT INTO followups (lead_id, counselor_id, followup_date, followup_time, interaction_type, notes, status, created_at)
                VALUES (?, ?, ?, '10:00:00', ?, ?, 'Pending', NOW())
            ");
            $nextIns->execute([
                $followup['lead_id'], $currentUser['id'],
                $nextDate, $followup['interaction_type'] ?? 'Phone Call',
                "Next follow-up following consultation on " . date('Y-m-d')
            ]);
        }

        $pdo->commit();
        set_flash('success', "Follow-up consultation with <strong>{$targetName}</strong> marked as Completed.");
        redirect('leads/view.php?id=' . $followup['lead_id']);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        set_flash('danger', 'Error updating follow-up: ' . $e->getMessage());
    }
}

$pageTitle = 'Complete Follow-up: ' . $targetName;
$activePage = 'followups';

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header" style="margin-bottom:1.5rem;">
  <div>
    <h1 style="font-size:1.5rem; font-weight:700; color:var(--text-heading); margin:0 0 0.35rem 0;">Complete Follow-up Consultation</h1>
    <p style="font-size:0.875rem; color:var(--text-muted); margin:0;">
      Candidate: <strong><?= htmlspecialchars($targetName) ?></strong> (<?= htmlspecialchars($followup['lead_code']) ?>)
    </p>
  </div>
</div>

<div class="card" style="max-width:640px; margin:0 auto; padding:1.75rem;">
  <form method="POST">
    <?= csrf_field() ?>

    <div style="background:var(--bg-subtle); border-radius:12px; padding:1.25rem; margin-bottom:1.5rem;">
      <div style="font-size:0.85rem; color:var(--text-muted); margin-bottom:4px;">Previous Discussion Notes:</div>
      <div style="font-size:0.95rem; color:var(--text-heading); font-weight:500;">
        <?= nl2br(htmlspecialchars($followup['notes'] ?? 'No notes recorded.')) ?>
      </div>
    </div>

    <div class="form-group" style="margin-bottom:1.25rem;">
      <label class="form-label" style="font-weight:700;">Consultation Outcome & Student Feedback <span style="color:red;">*</span></label>
      <textarea name="outcome_notes" class="form-control" rows="4" placeholder="Detail student reaction, university course discussed, documents requested, readiness to apply..." required autofocus></textarea>
    </div>

    <div class="form-group" style="margin-bottom:1.5rem;">
      <label class="form-label" style="font-weight:600;">Schedule Next Follow-up (Optional)</label>
      <input type="date" name="next_followup_date" class="form-control" min="<?= date('Y-m-d') ?>">
      <small style="font-size:0.75rem; color:var(--text-muted);">Leave empty if no further consultation is required at this stage.</small>
    </div>

    <div style="display:flex; justify-content:flex-end; gap:0.75rem; border-top:1px solid var(--border-color); padding-top:1.25rem;">
      <a href="<?= base_url('followups/index.php') ?>" class="btn btn-secondary">Cancel</a>
      <button type="submit" class="btn btn-primary">
        <i class="fa-solid fa-check"></i> Mark Consultation Completed
      </button>
    </div>
  </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
