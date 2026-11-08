<?php
/**
 * Edit Task
 * University-Level Student CRM Demo
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_permission('tasks', 'edit');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('danger', 'Invalid task ID.');
    redirect('tasks/index.php');
}

$currentUser = current_user();

$stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ?");
$stmt->execute([$id]);
$task = $stmt->fetch();

if (!$task) {
    set_flash('danger', 'Task not found.');
    redirect('tasks/index.php');
}

$students = $pdo->query("SELECT id, student_code, full_name FROM students ORDER BY full_name ASC")->fetchAll();
$staffMembers = $pdo->query("SELECT id, name, designation FROM users WHERE role_id IN (4, 5) AND status = 'Active' ORDER BY name ASC")->fetchAll();
$categories = ['Student Follow-up', 'Document Collection', 'Application Completion', 'Admission Verification', 'General Task'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['task_title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $category = $_POST['category'] ?? 'General Task';
    $assignedStaffId = !empty($_POST['assigned_staff_id']) ? (int)$_POST['assigned_staff_id'] : $task['assigned_staff_id'];
    $studentId = !empty($_POST['student_id']) ? (int)$_POST['student_id'] : null;
    $dueDate = !empty($_POST['due_date']) ? $_POST['due_date'] : $task['due_date'];
    $priority = $_POST['priority'] ?? 'Moderate';
    $status = $_POST['status'] ?? 'Pending';

    try {
        $upd = $pdo->prepare("
            UPDATE tasks SET
                task_title = ?, description = ?, category = ?, assigned_staff_id = ?,
                student_id = ?, due_date = ?, priority = ?, status = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $upd->execute([$title, $desc, $category, $assignedStaffId, $studentId, $dueDate, $priority, $status, $id]);

        log_activity($currentUser['id'], $studentId, 'Task Updated', "Updated task '$title' (Status: $status).");
        set_flash('success', "Task <strong>$title</strong> updated successfully.");
        redirect('tasks/index.php');
    } catch (Exception $e) {
        set_flash('danger', 'Database error: ' . $e->getMessage());
    }
}

$pageTitle = 'Edit Task: ' . $task['task_title'];
$activePage = 'tasks';

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div class="page-title-wrap">
    <h2>Edit Admissions Task</h2>
    <p>Title: <strong><?= htmlspecialchars($task['task_title']) ?></strong></p>
  </div>
  <div class="page-actions">
    <a href="index.php" class="btn btn-outline">&larr; Back to Tasks</a>
  </div>
</div>

<div class="card" style="max-width: 720px; margin: 0 auto;">
  <div class="card-header">
    <h3 class="card-title"><i class="fa-solid fa-pen-to-square" style="color:var(--accent-blue);"></i> Modify Task</h3>
  </div>
  <div class="card-body">
    <form method="POST" action="edit.php?id=<?= $id ?>">
      <?= csrf_field() ?>

      <div class="form-group">
        <label class="form-label" for="task_title">Task Title <span class="required">*</span></label>
        <input type="text" id="task_title" name="task_title" class="form-control" value="<?= htmlspecialchars($_POST['task_title'] ?? $task['task_title']) ?>" required>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="category">Category</label>
          <select id="category" name="category" class="form-select">
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat ?>" <?= (($_POST['category'] ?? $task['category']) === $cat) ? 'selected' : '' ?>><?= $cat ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="priority">Priority</label>
          <select id="priority" name="priority" class="form-select">
            <?php foreach (['High', 'Moderate', 'Low'] as $p): ?>
              <option value="<?= $p ?>" <?= (($_POST['priority'] ?? $task['priority']) === $p) ? 'selected' : '' ?>><?= $p ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="assigned_staff_id">Assigned Staff</label>
          <select id="assigned_staff_id" name="assigned_staff_id" class="form-select">
            <?php foreach ($staffMembers as $staff): ?>
              <option value="<?= $staff['id'] ?>" <?= (($task['assigned_staff_id']) == $staff['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($staff['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="student_id">Related Student</label>
          <select id="student_id" name="student_id" class="form-select">
            <option value="">-- No Related Student (General) --</option>
            <?php foreach ($students as $stu): ?>
              <option value="<?= $stu['id'] ?>" <?= (($task['student_id']) == $stu['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($stu['full_name']) ?> (<?= htmlspecialchars($stu['student_code']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="due_date">Due Date</label>
          <input type="date" id="due_date" name="due_date" class="form-control" value="<?= $task['due_date'] ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="status">Status</label>
          <select id="status" name="status" class="form-select">
            <?php foreach (['Pending', 'In Progress', 'Completed', 'Overdue'] as $s): ?>
              <option value="<?= $s ?>" <?= (($task['status']) === $s) ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="description">Instructions</label>
        <textarea id="description" name="description" class="form-control" rows="3"><?= htmlspecialchars($task['description'] ?? '') ?></textarea>
      </div>

      <div style="display:flex; justify-content:flex-end; gap:0.75rem; margin-top:1.5rem; padding-top:1rem; border-top:1px solid var(--border-color);">
        <a href="index.php" class="btn btn-outline">Cancel</a>
        <button type="submit" class="btn btn-primary btn-lg">
          <i class="fa-solid fa-save"></i> Save Changes
        </button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
