<?php
/**
 * Document Deletion Handler (PDF Section 10.3)
 * University-Level Education Consultancy CRM (EdrootsCRM)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('documents/general.php');
}

verify_csrf_token($_POST['csrf_token'] ?? '');

$id = (int)($_POST['id'] ?? 0);
$type = trim($_POST['type'] ?? 'general'); // general or student
$returnUrl = $_POST['return_url'] ?? base_url('documents/general.php');
$currentUser = current_user();

if ($id <= 0) {
    set_flash('danger', 'Invalid document specified.');
    redirect($returnUrl);
}

try {
    if ($type === 'student') {
        require_permission('documents', 'delete');
        $stmt = $pdo->prepare("SELECT * FROM student_documents WHERE id = ?");
        $stmt->execute([$id]);
        $doc = $stmt->fetch();

        if (!$doc) {
            set_flash('danger', 'Document not found.');
            redirect($returnUrl);
        }

        if (!can_access_lead($doc['lead_id'])) {
            set_flash('danger', 'Access Denied: You do not have permission to delete this document.');
            redirect($returnUrl);
        }

        // Delete file on disk if exists
        $filePath = __DIR__ . '/../uploads/documents/' . $doc['file_name'];
        if (file_exists($filePath)) {
            @unlink($filePath);
        }

        $delStmt = $pdo->prepare("DELETE FROM student_documents WHERE id = ?");
        $delStmt->execute([$id]);

        log_activity(
            $currentUser['id'],
            $doc['lead_id'],
            'Student Document Deleted',
            "Deleted document \"{$doc['title']}\" from student dossier."
        );

        set_flash('success', 'Document deleted successfully from student dossier.');
    } else {
        // General Document
        require_permission('documents', 'delete');
        $stmt = $pdo->prepare("SELECT * FROM general_documents WHERE id = ?");
        $stmt->execute([$id]);
        $doc = $stmt->fetch();

        if (!$doc) {
            set_flash('danger', 'General Document not found.');
            redirect($returnUrl);
        }

        // Delete file on disk if exists
        $filePath = __DIR__ . '/../uploads/documents/' . $doc['file_name'];
        if (file_exists($filePath)) {
            @unlink($filePath);
        }

        $delStmt = $pdo->prepare("DELETE FROM general_documents WHERE id = ?");
        $delStmt->execute([$id]);

        log_activity(
            $currentUser['id'],
            null,
            'General Document Deleted',
            "Deleted general training document \"{$doc['title']}\"."
        );

        set_flash('success', 'General Document deleted successfully.');
    }
} catch (Exception $e) {
    set_flash('danger', 'Error deleting document: ' . htmlspecialchars($e->getMessage()));
}

redirect($returnUrl);
