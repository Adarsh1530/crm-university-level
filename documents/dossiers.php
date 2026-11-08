<?php
/**
 * Student Academic Dossiers Master Index
 * University-Level Education Consultancy CRM (EdrootsCRM)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_permission('documents', 'view');

$currentUser = current_user();
$pageTitle = 'Student Academic Dossiers';
$activePage = 'dossiers';

$search = trim($_GET['search'] ?? '');
$categoryFilter = trim($_GET['category'] ?? '');
$scopeClause = get_crm_scope_clause('l', 'assigned_counselor_id', 'branch_id');

$where = ["1=1", $scopeClause];
$params = [];

if (!empty($search)) {
    $where[] = "(d.title LIKE ? OR l.full_name LIKE ? OR l.lead_code LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if (!empty($categoryFilter)) {
    $where[] = "d.category = ?";
    $params[] = $categoryFilter;
}

$whereSql = implode(' AND ', $where);

$sql = "
    SELECT d.*, l.full_name, l.lead_code, b.name as branch_name, u.name as uploaded_by_name
    FROM student_documents d
    JOIN leads l ON d.lead_id = l.id
    LEFT JOIN branches b ON l.branch_id = b.id
    LEFT JOIN users u ON d.uploaded_by = u.id
    WHERE {$whereSql}
    ORDER BY d.id DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$docs = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <h1 style="font-size:1.5rem; font-weight:700; color:var(--text-heading); margin:0 0 0.35rem 0;">Student Academic Dossiers</h1>
    <p style="font-size:0.875rem; color:var(--text-muted); margin:0;">
      Centralized index of applicant identity documents, degree transcripts, IELTS/PTE scorecards, and financial CAS letters.
    </p>
  </div>
</div>

<!-- Category Filters -->
<div style="display:flex; gap:0.5rem; overflow-x:auto; padding-bottom:0.75rem; margin-bottom:1.25rem;">
  <a href="<?= base_url('documents/dossiers.php') ?>" class="btn btn-sm <?= empty($categoryFilter) ? 'btn-primary' : 'btn-secondary' ?>" style="white-space:nowrap; border-radius:20px; padding:0.4rem 1rem;">
    All Categories
  </a>
  <?php foreach (['Identity', 'Academic', 'English Proficiency', 'Financial & Visa'] as $c): ?>
    <a href="<?= base_url('documents/dossiers.php?category=' . urlencode($c)) ?>" class="btn btn-sm <?= ($categoryFilter === $c) ? 'btn-primary' : 'btn-secondary' ?>" style="white-space:nowrap; border-radius:20px; padding:0.4rem 0.9rem;">
      <?= htmlspecialchars($c) ?>
    </a>
  <?php endforeach; ?>
</div>

<!-- Search Bar -->
<div class="card" style="margin-bottom:1.5rem; padding:1.25rem;">
  <form method="GET" action="<?= base_url('documents/dossiers.php') ?>" style="display:flex; gap:0.75rem; align-items:center;">
    <?php if (!empty($categoryFilter)): ?>
      <input type="hidden" name="category" value="<?= htmlspecialchars($categoryFilter) ?>">
    <?php endif; ?>
    <div style="flex:1; position:relative;">
      <input type="text" name="search" class="form-control" placeholder="Search student name, lead code, document title..." value="<?= htmlspecialchars($search) ?>" style="padding-left:2.4rem;">
      <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-light);"></i>
    </div>
    <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-filter"></i> Search</button>
    <a href="<?= base_url('documents/dossiers.php') ?>" class="btn btn-secondary btn-sm"><i class="fa-solid fa-rotate-left"></i></a>
  </form>
</div>

<!-- Dossier Table -->
<div class="card">
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Category</th>
          <th>Document Title</th>
          <th>Student Applicant</th>
          <th>Original File Name</th>
          <th>Date Uploaded</th>
          <th>Uploaded By</th>
          <th style="text-align:right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($docs)): ?>
          <tr>
            <td colspan="7" style="text-align:center; padding:3rem; color:var(--text-muted);">
              No student dossier documents match this search.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($docs as $d): ?>
            <tr>
              <td>
                <span class="badge badge-secondary" style="font-weight:700; font-size:0.75rem;">
                  <?= htmlspecialchars($d['category']) ?>
                </span>
              </td>
              <td>
                <strong style="font-size:0.875rem; color:var(--text-heading);"><?= htmlspecialchars($d['title']) ?></strong>
              </td>
              <td>
                <a href="<?= base_url('leads/view.php?id=' . $d['lead_id']) ?>" style="font-weight:700; color:var(--text-heading); font-size:0.875rem; text-decoration:none;">
                  <?= htmlspecialchars($d['full_name']) ?>
                </a>
                <div style="font-family:monospace; font-size:0.72rem; color:var(--primary);">
                  <?= htmlspecialchars($d['lead_code']) ?> &bull; <?= htmlspecialchars($d['branch_name'] ?? 'Branch') ?>
                </div>
              </td>
              <td style="font-size:0.8rem; color:var(--text-muted);">
                <?= htmlspecialchars($d['original_filename'] ?? $d['file_name'] ?? 'document.pdf') ?>
              </td>
              <td style="font-size:0.825rem;"><?= date('d M Y', strtotime($d['created_at'])) ?></td>
              <td style="font-size:0.75rem; color:var(--text-muted);"><?= htmlspecialchars($d['uploaded_by_name'] ?? 'Staff') ?></td>
              <td style="text-align:right;">
                <div style="display:flex; justify-content:flex-end; gap:0.35rem;">
                  <a href="<?= base_url('uploads/documents/' . rawurlencode($d['file_name'])) ?>" target="_blank" class="btn btn-secondary btn-sm" title="Open File">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                  </a>
                  <a href="<?= base_url('leads/view.php?id=' . $d['lead_id']) ?>" class="btn btn-outline btn-sm" title="View Full Dossier">
                    <i class="fa-solid fa-eye"></i>
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
