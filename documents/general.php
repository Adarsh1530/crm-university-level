<?php
/**
 * General Documents Repository (Institutional Guidelines & Guidelines)
 * University-Level Education Consultancy CRM (EdrootsCRM)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_permission('documents', 'view');

$currentUser = current_user();
$pageTitle = 'General Documents Repository';
$activePage = 'general_docs';

$search = trim($_GET['search'] ?? '');
$categoryFilter = trim($_GET['category'] ?? '');

$where = ["1=1"];
$params = [];

if (!empty($search)) {
    $where[] = "(g.title LIKE ? OR g.description LIKE ? OR g.original_filename LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if (!empty($categoryFilter)) {
    $where[] = "g.category = ?";
    $params[] = $categoryFilter;
}

$whereSql = implode(' AND ', $where);

// Categories
$categories = [
    'Visa Checklists',
    'Partner University Lists',
    'SOP Guidelines & Templates',
    'Financial Sponsorship Guidelines',
    'Consultancy Contract Templates',
    'General Guidelines'
];

$sql = "
    SELECT g.*, u.name as uploaded_by_name
    FROM general_documents g
    LEFT JOIN users u ON g.uploaded_by = u.id
    WHERE {$whereSql}
    ORDER BY g.id DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$docs = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <h1 style="font-size:1.5rem; font-weight:700; color:var(--text-heading); margin:0 0 0.35rem 0;">General Documents Repository</h1>
    <p style="font-size:0.875rem; color:var(--text-muted); margin:0;">
      Central library of partner university agreements, country visa checklists, SOP templates, and consultancy operational policies.
    </p>
  </div>
  <div>
    <?php if (can('documents', 'add')): ?>
      <button type="button" class="btn btn-primary btn-sm" onclick="openUploadModal()">
        <i class="fa-solid fa-cloud-arrow-up"></i> Upload Document
      </button>
    <?php endif; ?>
  </div>
</div>

<!-- Category Tabs / Filter Pills -->
<div style="display:flex; gap:0.5rem; overflow-x:auto; padding-bottom:0.75rem; margin-bottom:1.25rem;">
  <a href="<?= base_url('documents/general.php') ?>" class="btn btn-sm <?= empty($categoryFilter) ? 'btn-primary' : 'btn-secondary' ?>" style="white-space:nowrap; border-radius:20px; padding:0.4rem 1rem;">
    All Categories
  </a>
  <?php foreach ($categories as $cat): ?>
    <a href="<?= base_url('documents/general.php?category=' . urlencode($cat)) ?>" class="btn btn-sm <?= ($categoryFilter === $cat) ? 'btn-primary' : 'btn-secondary' ?>" style="white-space:nowrap; border-radius:20px; padding:0.4rem 0.9rem;">
      <?= htmlspecialchars($cat) ?>
    </a>
  <?php endforeach; ?>
</div>

<!-- Search Bar -->
<div class="card" style="margin-bottom:1.5rem; padding:1.25rem;">
  <form method="GET" action="<?= base_url('documents/general.php') ?>" style="display:flex; gap:0.75rem; align-items:center;">
    <?php if (!empty($categoryFilter)): ?>
      <input type="hidden" name="category" value="<?= htmlspecialchars($categoryFilter) ?>">
    <?php endif; ?>
    <div style="flex:1; position:relative;">
      <input type="text" name="search" class="form-control" placeholder="Search guidelines by title or keyword..." value="<?= htmlspecialchars($search) ?>" style="padding-left:2.4rem;">
      <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-light);"></i>
    </div>
    <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-filter"></i> Search</button>
    <a href="<?= base_url('documents/general.php') ?>" class="btn btn-secondary btn-sm"><i class="fa-solid fa-rotate-left"></i></a>
  </form>
</div>

<!-- Documents Grid / List -->
<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); gap:1.25rem;">
  <?php if (empty($docs)): ?>
    <div class="card" style="grid-column:1 / -1; padding:3rem; text-align:center; color:var(--text-muted);">
      <i class="fa-regular fa-folder-open" style="font-size:2.2rem; margin-bottom:0.75rem; display:block; color:var(--text-light);"></i>
      No guidelines or documents match your query.
    </div>
  <?php else: ?>
    <?php foreach ($docs as $d): ?>
      <div class="card" style="padding:1.4rem; display:flex; flex-direction:column; justify-content:space-between;">
        <div>
          <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:0.5rem;">
            <span class="badge badge-secondary" style="font-size:0.72rem; font-weight:700;">
              <?= htmlspecialchars($d['category']) ?>
            </span>
            <span style="font-size:0.72rem; color:var(--text-muted);"><?= date('d M Y', strtotime($d['created_at'])) ?></span>
          </div>

          <h3 style="font-size:1.05rem; font-weight:700; color:var(--text-heading); margin:0 0 0.5rem 0;">
            <?= htmlspecialchars($d['title']) ?>
          </h3>

          <p style="font-size:0.825rem; color:var(--text-muted); margin:0 0 1rem 0; line-height:1.4;">
            <?= htmlspecialchars($d['description'] ?? 'Official consultancy documentation resource.') ?>
          </p>
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid var(--border-color); padding-top:0.75rem; font-size:0.78rem;">
          <span style="color:var(--text-muted);">
            By: <strong><?= htmlspecialchars($d['uploaded_by_name'] ?? 'Admin') ?></strong>
          </span>
          <a href="<?= base_url('uploads/documents/' . rawurlencode($d['file_name'])) ?>" target="_blank" class="btn btn-primary btn-sm" style="font-size:0.75rem;">
            <i class="fa-solid fa-arrow-down"></i> Open / Download
          </a>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- Upload General Document Modal -->
<div class="modal fade" id="uploadModal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(12,36,60,0.5); align-items:center; justify-content:center;">
  <div class="modal-dialog" style="background:#fff; border-radius:14px; max-width:480px; width:92%; margin:auto; box-shadow:0 20px 50px rgba(0,0,0,0.2); overflow:hidden;">
    <form method="POST" action="<?= base_url('documents/upload.php') ?>" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="type" value="general">
      <input type="hidden" name="return_url" value="<?= base_url('documents/general.php') ?>">

      <div class="modal-header" style="padding:1.25rem 1.5rem; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
        <h3 style="font-size:1.15rem; font-weight:700; margin:0; color:var(--text-heading);">
          <i class="fa-solid fa-cloud-arrow-up" style="color:var(--primary); margin-right:8px;"></i> Upload Company Guideline
        </h3>
        <button type="button" onclick="closeUploadModal()" style="border:none; background:none; font-size:1.4rem; cursor:pointer; color:var(--text-muted);">&times;</button>
      </div>

      <div class="modal-body" style="padding:1.5rem;">
        <div class="form-group" style="margin-bottom:1.25rem;">
          <label class="form-label">Category <span style="color:red;">*</span></label>
          <select name="category" class="form-control" required>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group" style="margin-bottom:1.25rem;">
          <label class="form-label">Document Title <span style="color:red;">*</span></label>
          <input type="text" name="title" class="form-control" placeholder="e.g. UK Student Visa Checklist 2026" required>
        </div>

        <div class="form-group" style="margin-bottom:1.25rem;">
          <label class="form-label">Document Description</label>
          <textarea name="description" class="form-control" rows="2" placeholder="Summary of guidance or intended audience..."></textarea>
        </div>

        <div class="form-group" style="margin-bottom:0.5rem;">
          <label class="form-label">Select File (PDF / DOC / XLS) <span style="color:red;">*</span></label>
          <input type="file" name="document_file" class="form-control" accept=".pdf, .doc, .docx, .xls, .xlsx" required>
        </div>
      </div>

      <div class="modal-footer" style="padding:1rem 1.5rem; border-top:1px solid var(--border-color); display:flex; justify-content:flex-end; gap:0.5rem;">
        <button type="button" onclick="closeUploadModal()" class="btn btn-secondary btn-sm">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm">
          <i class="fa-solid fa-check"></i> Save to Repository
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function openUploadModal() {
  document.getElementById('uploadModal').style.display = 'flex';
}
function closeUploadModal() {
  document.getElementById('uploadModal').style.display = 'none';
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
