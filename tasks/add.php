<?php
/**
 * Create New Task
 * University-Level Student CRM Demo
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_permission('tasks', 'add');

$pageTitle = 'Create New Task';
$activePage = 'tasks';
$currentUser = current_user();

$preStudentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : null;
$returnUrl = $_GET['return'] ?? $_POST['return'] ?? 'tasks/index.php';

$students = $pdo->query("SELECT id, student_code, full_name FROM students ORDER BY full_name ASC")->fetchAll();
$staffMembers = $pdo->query("SELECT id, name, designation FROM users WHERE role_id IN (4, 5) AND status = 'Active' ORDER BY name ASC")->fetchAll();
$categories = ['Student Follow-up', 'Document Collection', 'Application Completion', 'Admission Verification', 'General Task'];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['task_title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $category = $_POST['category'] ?? 'General Task';
    $assignedStaffId = !empty($_POST['assigned_staff_id']) ? (int)$_POST['assigned_staff_id'] : $currentUser['id'];
    $studentId = !empty($_POST['student_id']) ? (int)$_POST['student_id'] : null;
    $dueDate = !empty($_POST['due_date']) ? $_POST['due_date'] : date('Y-m-d');
    $priority = $_POST['priority'] ?? 'Moderate';
    $status = $_POST['status'] ?? 'Pending';

    if (empty($title)) $errors[] = 'Task Title is required.';
    if (empty($dueDate)) $errors[] = 'Due Date is required.';

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO tasks (task_title, description, category, assigned_staff_id, student_id, due_date, priority, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$title, $desc, $category, $assignedStaffId, $studentId, $dueDate, $priority, $status]);

            log_activity($currentUser['id'], $studentId, 'Task Created', "Created task '$title' assigned to staff ID $assignedStaffId.");

            // Add notification to assigned staff
            add_notification($assignedStaffId, 'New Task Assigned', "Task '$title' has been assigned to you, due on " . format_date($dueDate), 'task_reminder', 'tasks/index.php');

            set_flash('success', "Task <strong>$title</strong> created successfully.");
            redirect($returnUrl);
        } catch (Exception $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div class="page-title-wrap">
    <h2>Create Admissions Task</h2>
    <p>Delegate deliverables, document checks, and applicant action items.</p>
  </div>
  <div class="page-actions">
    <a href="<?= base_url($returnUrl) ?>" class="btn btn-outline">&larr; Back</a>
  </div>
</div>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger">
    <div>
      <ul style="margin: 0; padding-left: 1.25rem;">
        <?php foreach ($errors as $err): ?>
          <li><?= htmlspecialchars($err) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
<?php endif; ?>

<div class="card" style="max-width: 720px; margin: 0 auto;">
  <div class="card-header">
    <h3 class="card-title"><i class="fa-solid fa-list-check" style="color:var(--accent-blue);"></i> Task Information</h3>
  </div>
  <div class="card-body">
    <form method="POST" action="add.php">
      <?= csrf_field() ?>
      <input type="hidden" name="return" value="<?= htmlspecialchars($returnUrl) ?>">

      <div class="form-group">
        <label class="form-label" for="task_title">Task Title <span class="required">*</span></label>
        <input type="text" id="task_title" name="task_title" class="form-control" placeholder="e.g. Audit notarized transcripts & English test results" value="<?= htmlspecialchars($_POST['task_title'] ?? '') ?>" required>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="category">Category</label>
          <select id="category" name="category" class="form-select">
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat ?>" <?= (($_POST['category'] ?? '') === $cat) ? 'selected' : '' ?>><?= $cat ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="priority">Priority</label>
          <select id="priority" name="priority" class="form-select">
            <option value="High">High</option>
            <option value="Moderate" selected>Moderate</option>
            <option value="Low">Low</option>
          </select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="assigned_staff_id">Assigned Staff <span class="required">*</span></label>
          <select id="assigned_staff_id" name="assigned_staff_id" class="form-select" required>
            <?php foreach ($staffMembers as $staff): ?>
              <option value="<?= $staff['id'] ?>" <?= ($currentUser['id'] == $staff['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($staff['name']) ?> (<?= htmlspecialchars($staff['designation'] ?? 'Staff') ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="student_id">Related Student (Optional)</label>
          <select id="student_id" name="student_id" class="form-select">
            <option value="">-- No Related Student (General) --</option>
            <?php foreach ($students as $stu): ?>
              <option value="<?= $stu['id'] ?>" <?= ($preStudentId == $stu['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($stu['full_name']) ?> (<?= htmlspecialchars($stu['student_code']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="due_date">Due Date <span class="required">*</span></label>
          <input type="date" id="due_date" name="due_date" class="form-control" value="<?= htmlspecialchars($_POST['due_date'] ?? date('Y-m-d', strtotime('+3 days'))) ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="status">Initial Status</label>
          <select id="status" name="status" class="form-select">
            <option value="Pending" selected>Pending</option>
            <option value="In Progress">In Progress</option>
            <option value="Completed">Completed</option>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="description">Instructions / Details</label>
        <textarea id="description" name="description" class="form-control" rows="3" placeholder="Provide actionable guidelines or requirements..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
      </div>

      <div style="display:flex; justify-content:flex-end; gap:0.75rem; margin-top:1.5rem; padding-top:1rem; border-top:1px solid var(--border-color);">
        <a href="<?= base_url($returnUrl) ?>" class="btn btn-outline">Cancel</a>
        <button type="submit" class="btn btn-primary btn-lg">
          <i class="fa-solid fa-save"></i> Save Task
        </button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
