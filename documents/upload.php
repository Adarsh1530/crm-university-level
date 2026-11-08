<?php
/**
 * Document Upload Handler (General Guidelines & Student Dossiers)
 * University-Level Education Consultancy CRM (EdrootsCRM)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_permission('documents', 'add');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('documents/general.php');
}

verify_csrf_token($_POST['csrf_token'] ?? '');

$type = trim($_POST['type'] ?? 'general'); // general or student
$leadId = (int)($_POST['lead_id'] ?? 0);
$title = trim($_POST['title'] ?? '');
$category = trim($_POST['category'] ?? 'General Guidelines');
$description = trim($_POST['description'] ?? '');
$returnUrl = $_POST['return_url'] ?? ($type === 'student' ? base_url("leads/view.php?id={$leadId}") : base_url('documents/general.php'));
$currentUser = current_user();

$file = $_FILES['document_file'] ?? null;
if (empty($title) || empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
    set_flash('danger', 'Please provide a document title and choose a valid file.');
    redirect($returnUrl);
}

// Upload directory setup
$uploadDir = __DIR__ . '/../uploads/documents/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$origName = basename($file['name']);
$ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
$allowedExts = ['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg', 'xls', 'xlsx'];

if (!in_array($ext, $allowedExts, true)) {
    set_flash('danger', 'Invalid file type. Allowed formats: PDF, DOC, DOCX, PNG, JPG, XLS.');
    redirect($returnUrl);
}

// Generate unique stored file name
$storedFileName = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
$targetPath = $uploadDir . $storedFileName;
$fileSize = $file['size'];

if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    set_flash('danger', 'Failed to store uploaded document on server disk.');
    redirect($returnUrl);
}

try {
    if ($type === 'student') {
        if ($leadId <= 0 || !can_access_lead($leadId)) {
            set_flash('danger', 'Access Denied: You cannot upload files for this student lead.');
            redirect($returnUrl);
        }

        $stmt = $pdo->prepare("
            INSERT INTO student_documents (
                lead_id, category, title, file_name, original_filename,
                file_size, file_type, uploaded_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $leadId, $category, $title, $storedFileName, $origName,
            $fileSize, $file['type'], $currentUser['id']
        ]);

        log_activity(
            $currentUser['id'],
            $leadId,
            'Student Document Uploaded',
            "Uploaded dossier document [{$category}] \"{$title}\" ({$origName})."
        );

        set_flash('success', "Document <strong>" . htmlspecialchars($title) . "</strong> uploaded to student dossier successfully.");
    } else {
        // General Document
        $stmt = $pdo->prepare("
            INSERT INTO general_documents (
                title, category, file_name, original_filename,
                file_size, file_type, description, uploaded_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $title, $category, $storedFileName, $origName,
            $fileSize, $file['type'], $description, $currentUser['id']
        ]);

        log_activity(
            $currentUser['id'],
            null,
            'General Document Uploaded',
            "Uploaded general repository document \"{$title}\" in category [{$category}]."
        );

        set_flash('success', "Company repository document <strong>" . htmlspecialchars($title) . "</strong> uploaded successfully.");
    }
} catch (Exception $e) {
    set_flash('danger', 'Error saving document record: ' . htmlspecialchars($e->getMessage()));
}

redirect($returnUrl);
