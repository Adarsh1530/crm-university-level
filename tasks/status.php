<?php
/**
 * Update Task Status
 * University-Level Student CRM Demo
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_permission('tasks', 'edit');

$id = (int)($_GET['id'] ?? 0);
$status = $_GET['status'] ?? 'Completed';
$returnUrl = $_GET['return'] ?? 'tasks/index.php';

if ($id <= 0) {
    set_flash('danger', 'Invalid task ID.');
    redirect($returnUrl);
}

$currentUser = current_user();

try {
    $stmt = $pdo->prepare("SELECT t.*, u.name AS staff_name FROM tasks t JOIN users u ON t.assigned_staff_id = u.id WHERE t.id = ?");
    $stmt->execute([$id]);
    $task = $stmt->fetch();

    if ($task) {
        $upd = $pdo->prepare("UPDATE tasks SET status = ?, updated_at = NOW() WHERE id = ?");
        $upd->execute([$status, $id]);

        log_activity($currentUser['id'], $task['student_id'], 'Task Status Updated', "Marked task '{$task['task_title']}' as $status.");
        set_flash('success', "Task <strong>{$task['task_title']}</strong> marked as <strong>$status</strong>.");
    }
} catch (Exception $e) {
    set_flash('danger', 'Could not update task: ' . $e->getMessage());
}

redirect($returnUrl);
