<?php
/**
 * System Governance Settings (Interaction Titles & Intake Cycles)
 * University-Level Education Consultancy CRM (EdrootsCRM)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_permission('settings', 'view');

$currentUser = current_user();
$pageTitle = 'Interaction Rules & Intake Cycles';
$activePage = 'system_settings';

// Handle Add / Edit Interaction Title
if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_super_admin()) {
    verify_csrf_token($_POST['csrf_token'] ?? '');
    $formType = $_POST['form_type'] ?? '';

    if ($formType === 'title') {
        $titleId = (int)($_POST['title_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $maxDuration = max(1, (int)($_POST['max_duration_days'] ?? 3));
        $desc = trim($_POST['description'] ?? '');

        if (!empty($title)) {
            if ($titleId > 0) {
                $stmt = $pdo->prepare("UPDATE interaction_titles SET title = ?, max_duration_days = ?, description = ? WHERE id = ?");
                $stmt->execute([$title, $maxDuration, $desc, $titleId]);
                set_flash('success', "Interaction title <strong>\"" . htmlspecialchars($title) . "\"</strong> updated successfully.");
            } else {
                $stmt = $pdo->prepare("INSERT INTO interaction_titles (title, max_duration_days, description) VALUES (?, ?, ?)");
                $stmt->execute([$title, $maxDuration, $desc]);
                set_flash('success', "Interaction title <strong>\"" . htmlspecialchars($title) . "\"</strong> added.");
            }
            redirect('system/settings.php');
        }
    } elseif ($formType === 'intake') {
        $name = trim($_POST['intake_name'] ?? '');
        $year = (int)($_POST['intake_year'] ?? date('Y'));
        $term = trim($_POST['intake_term'] ?? 'September');

        if (!empty($name)) {
            $stmt = $pdo->prepare("INSERT INTO intake_cycles (name, year, term, status) VALUES (?, ?, ?, 'Active')");
            $stmt->execute([$name, $year, $term]);
            set_flash('success', "Intake cycle <strong>\"" . htmlspecialchars($name) . "\"</strong> added.");
            redirect('system/settings.php');
        }
    }
}

$titles = $pdo->query("SELECT * FROM interaction_titles ORDER BY sort_order ASC, id ASC")->fetchAll();
$intakes = $pdo->query("SELECT * FROM intake_cycles ORDER BY sort_order ASC, year ASC")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <h1 style="font-size:1.5rem; font-weight:700; color:var(--text-heading); margin:0 0 0.35rem 0;">Interaction Titles & Governance Rules</h1>
    <p style="font-size:0.875rem; color:var(--text-muted); margin:0;">
      Enforce business rules for consultation intervals. The next follow-up date calculation is strictly bound by each interaction title's maximum duration.
    </p>
  </div>
  <div>
    <?php if (is_super_admin()): ?>
      <button type="button" class="btn btn-primary btn-sm" onclick="openTitleModal()">
        <i class="fa-solid fa-plus"></i> Add Interaction Title
      </button>
    <?php endif; ?>
  </div>
</div>

<div style="display:grid; grid-template-columns:2fr 1fr; gap:1.5rem; align-items:start;">
  <!-- Interaction Titles Table -->
  <div class="card" style="padding:1.5rem;">
    <h3 style="font-size:1.1rem; font-weight:700; color:var(--text-heading); margin:0 0 1rem 0;">
      <i class="fa-solid fa-clock-rotate-left" style="color:var(--primary); margin-right:6px;"></i> Configured Interaction Milestones & Constraints
    </h3>

    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>Interaction Title</th>
            <th style="text-align:center;">Max Allowed Interval</th>
            <th>Description & Operational Intent</th>
            <th style="text-align:right;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($titles as $t): ?>
            <tr>
              <td>
                <strong style="color:var(--text-heading); font-size:0.9rem;"><?= htmlspecialchars($t['title']) ?></strong>
              </td>
              <td style="text-align:center;">
                <span class="badge badge-primary" style="font-size:0.8rem; font-weight:800; padding:0.35rem 0.65rem;">
                  <?= (int)$t['max_duration_days'] ?> Days Max
                </span>
              </td>
              <td style="font-size:0.825rem; color:var(--text-muted); max-width:300px; line-height:1.4;">
                <?= htmlspecialchars($t['description']) ?>
              </td>
              <td style="text-align:right;">
                <?php if (is_super_admin()): ?>
                  <button type="button" class="btn btn-secondary btn-sm" onclick="editTitle(<?= htmlspecialchars(json_encode($t)) ?>)">
                    <i class="fa-solid fa-pen"></i> Edit
                  </button>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Intake Cycles Table -->
  <div class="card" style="padding:1.5rem;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
      <h3 style="font-size:1.05rem; font-weight:700; color:var(--text-heading); margin:0;">
        <i class="fa-solid fa-calendar-days" style="color:var(--primary); margin-right:6px;"></i> Intake Cycles
      </h3>
    </div>

    <div style="display:flex; flex-direction:column; gap:0.5rem; margin-bottom:1.5rem;">
      <?php foreach ($intakes as $in): ?>
        <div style="display:flex; justify-content:space-between; align-items:center; padding:0.6rem 0.85rem; background:var(--bg-subtle); border-radius:6px; font-size:0.85rem;">
          <strong><?= htmlspecialchars($in['name']) ?></strong>
          <span class="badge badge-secondary" style="font-size:0.72rem;"><?= htmlspecialchars($in['status']) ?></span>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if (is_super_admin()): ?>
      <form method="POST" action="<?= base_url('system/settings.php') ?>" style="border-top:1px solid var(--border-color); padding-top:1rem;">
        <?= csrf_field() ?>
        <input type="hidden" name="form_type" value="intake">
        <label class="form-label" style="font-size:0.8rem; font-weight:700;">Add Intake Cycle</label>
        <div class="form-group" style="margin-bottom:0.75rem;">
          <input type="text" name="intake_name" class="form-control" placeholder="e.g. September 2027" required>
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.5rem; margin-bottom:0.75rem;">
          <input type="number" name="intake_year" class="form-control" value="2027" required>
          <select name="intake_term" class="form-control">
            <option value="September">September</option>
            <option value="January">January</option>
            <option value="May">May</option>
          </select>
        </div>
        <button type="submit" class="btn btn-secondary btn-sm" style="width:100%;">Add Intake</button>
      </form>
    <?php endif; ?>
  </div>
</div>

<!-- Add / Edit Interaction Title Modal -->
<div class="modal fade" id="titleModal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(12,36,60,0.5); align-items:center; justify-content:center;">
  <div class="modal-dialog" style="background:#fff; border-radius:14px; max-width:480px; width:92%; margin:auto; box-shadow:0 20px 50px rgba(0,0,0,0.2); overflow:hidden;">
    <form method="POST" action="<?= base_url('system/settings.php') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="form_type" value="title">
      <input type="hidden" name="title_id" id="modal_title_id" value="">

      <div class="modal-header" style="padding:1.25rem 1.5rem; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
        <h3 style="font-size:1.15rem; font-weight:700; margin:0; color:var(--text-heading);" id="modal_title_heading">
          Configure Interaction Title
        </h3>
        <button type="button" onclick="closeTitleModal()" style="border:none; background:none; font-size:1.4rem; cursor:pointer; color:var(--text-muted);">&times;</button>
      </div>

      <div class="modal-body" style="padding:1.5rem;">
        <div class="form-group" style="margin-bottom:1.25rem;">
          <label class="form-label">Interaction Title Name <span style="color:red;">*</span></label>
          <input type="text" name="title" id="rule_title" class="form-control" placeholder="e.g. Visa Filing" required>
        </div>

        <div class="form-group" style="margin-bottom:1.25rem;">
          <label class="form-label">Maximum Duration Constraint (Days) <span style="color:red;">*</span></label>
          <input type="number" name="max_duration_days" id="rule_max_days" class="form-control" value="7" min="1" max="90" required>
          <small style="color:var(--text-muted); font-size:0.75rem;">Next follow-up date picker will automatically restrict dates beyond (Current Date + Max Duration).</small>
        </div>

        <div class="form-group" style="margin-bottom:0.5rem;">
          <label class="form-label">Description / Operational Guidelines</label>
          <textarea name="description" id="rule_desc" class="form-control" rows="2" placeholder="Explains the purpose and expected action for this interaction type..."></textarea>
        </div>
      </div>

      <div class="modal-footer" style="padding:1rem 1.5rem; border-top:1px solid var(--border-color); display:flex; justify-content:flex-end; gap:0.5rem;">
        <button type="button" onclick="closeTitleModal()" class="btn btn-secondary btn-sm">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm">Save Rule</button>
      </div>
    </form>
  </div>
</div>

<script>
function openTitleModal() {
  document.getElementById('modal_title_id').value = '';
  document.getElementById('modal_title_heading').innerText = 'Add Interaction Title';
  document.getElementById('rule_title').value = '';
  document.getElementById('rule_max_days').value = '7';
  document.getElementById('rule_desc').value = '';
  document.getElementById('titleModal').style.display = 'flex';
}

function editTitle(t) {
  document.getElementById('modal_title_id').value = t.id;
  document.getElementById('modal_title_heading').innerText = 'Edit Rule: ' + t.title;
  document.getElementById('rule_title').value = t.title;
  document.getElementById('rule_max_days').value = t.max_duration_days;
  document.getElementById('rule_desc').value = t.description || '';
  document.getElementById('titleModal').style.display = 'flex';
}

function closeTitleModal() {
  document.getElementById('titleModal').style.display = 'none';
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
