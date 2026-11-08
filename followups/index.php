<?php
/**
 * Daily Follow-up & Call Action Center
 * University-Level Education Consultancy CRM (EdrootsCRM)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_permission('followups', 'view');

$pageTitle = 'Follow-up Management';
$activePage = 'followups';
$currentUser = current_user();

// Tab Filter
$tabFilter = $_GET['tab'] ?? 'today'; // default to 'today' for high counselor efficiency
$filterPriority = $_GET['priority'] ?? '';
$filterType = $_GET['interaction_type'] ?? '';
$filterStaff = isset($_GET['staff_id']) ? (int)$_GET['staff_id'] : 0;
$filterDate = $_GET['date'] ?? '';
$search = trim($_GET['search'] ?? '');

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Role-based scope clause
$scopeClause = get_crm_scope_clause('l', 'assigned_counselor_id', 'branch_id');

// Metric Counts
$countToday = (int)$pdo->query("SELECT COUNT(*) FROM followups f JOIN leads l ON f.lead_id = l.id WHERE {$scopeClause} AND f.followup_date = CURDATE() AND f.status != 'Completed'")->fetchColumn();
$countOverdue = (int)$pdo->query("SELECT COUNT(*) FROM followups f JOIN leads l ON f.lead_id = l.id WHERE {$scopeClause} AND (f.status = 'Overdue' OR (f.followup_date < CURDATE() AND f.status = 'Pending'))")->fetchColumn();
$countUpcoming = (int)$pdo->query("SELECT COUNT(*) FROM followups f JOIN leads l ON f.lead_id = l.id WHERE {$scopeClause} AND f.followup_date > CURDATE() AND f.status = 'Pending'")->fetchColumn();
$countCompleted = (int)$pdo->query("SELECT COUNT(*) FROM followups f JOIN leads l ON f.lead_id = l.id WHERE {$scopeClause} AND f.status = 'Completed'")->fetchColumn();
$totalFollowups = (int)$pdo->query("SELECT COUNT(*) FROM followups f JOIN leads l ON f.lead_id = l.id WHERE {$scopeClause}")->fetchColumn();

// Build WHERE Clause
$whereClauses = ['l.is_archived = 0', $scopeClause];
$params = [];

if ($tabFilter === 'today') {
    $whereClauses[] = "f.followup_date = CURDATE() AND f.status != 'Completed'";
} elseif ($tabFilter === 'overdue') {
    $whereClauses[] = "(f.status = 'Overdue' OR (f.followup_date < CURDATE() AND f.status = 'Pending'))";
} elseif ($tabFilter === 'upcoming') {
    $whereClauses[] = "f.followup_date > CURDATE() AND f.status = 'Pending'";
} elseif ($tabFilter === 'completed') {
    $whereClauses[] = "f.status = 'Completed'";
}

if (!empty($search)) {
    $whereClauses[] = "(l.full_name LIKE ? OR l.lead_code LIKE ? OR l.phone LIKE ? OR f.notes LIKE ?)";
    $searchWild = "%{$search}%";
    $params[] = $searchWild;
    $params[] = $searchWild;
    $params[] = $searchWild;
    $params[] = $searchWild;
}

if (!empty($filterPriority)) {
    $whereClauses[] = "l.priority = ?";
    $params[] = $filterPriority;
}

if (!empty($filterType)) {
    $whereClauses[] = "f.interaction_type = ?";
    $params[] = $filterType;
}

if ($filterStaff > 0) {
    $whereClauses[] = "f.counselor_id = ?";
    $params[] = $filterStaff;
}

if (!empty($filterDate)) {
    $whereClauses[] = "f.followup_date = ?";
    $params[] = $filterDate;
}

$whereSql = implode(' AND ', $whereClauses);

// Count matching records
$countStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM followups f
    JOIN leads l ON f.lead_id = l.id
    WHERE {$whereSql}
");
$countStmt->execute($params);
$totalRecords = (int)$countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $perPage);

// Fetch followups
$querySql = "
    SELECT f.*, 
           l.full_name AS lead_name, l.lead_code, l.phone AS lead_phone, l.email AS lead_email, l.id AS lead_pk,
           l.stage AS lead_stage, l.priority AS lead_priority, l.preferred_country, l.preferred_course,
           u.name AS staff_name,
           it.title AS interaction_title
    FROM followups f
    JOIN leads l ON f.lead_id = l.id
    LEFT JOIN users u ON f.counselor_id = u.id
    LEFT JOIN interaction_titles it ON f.interaction_title_id = it.id
    WHERE {$whereSql}
    ORDER BY f.followup_date ASC, f.followup_time ASC
    LIMIT {$perPage} OFFSET {$offset}
";
$stmt = $pdo->prepare($querySql);
$stmt->execute($params);
$followups = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Counselors list for filter
$staffMembers = $pdo->query("SELECT id, name FROM users WHERE role_id IN (2, 3) AND status = 'Active' ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

$myFollowupsCount = (int)$pdo->query("SELECT COUNT(*) FROM followups WHERE counselor_id = " . (int)$currentUser['id'])->fetchColumn();

include __DIR__ . '/../includes/header.php';
?>

<!-- Page Header -->
<div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <h1 style="font-size:1.5rem; font-weight:700; color:var(--text-heading); margin:0 0 0.35rem 0;">Daily Follow-up & Action Center</h1>
    <p style="font-size:0.875rem; color:var(--text-muted); margin:0;">
      Counselor consultation schedule, direct calling station, and student interaction log.
    </p>
  </div>
  <div style="display:flex; gap:0.5rem; align-items:center;">
    <button type="button" onclick="openQuickLeadModal()" class="btn btn-primary btn-sm" style="background:#7008E7; border-color:#7008E7;">
      <i class="fa-solid fa-bolt"></i> + Fast Lead
    </button>
    <?php if (can('reports', 'export')): ?>
      <a href="<?= base_url('reports/export.php?type=followups') ?>" class="btn btn-secondary btn-sm" title="Export Follow-up History">
        <i class="fa-solid fa-file-csv"></i> Export CSV
      </a>
    <?php endif; ?>
  </div>
</div>

<!-- Follow-up KPI Metric Cards -->
<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(210px, 1fr)); gap:1rem; margin-bottom:1.5rem;">
  <a href="index.php?tab=today" class="card" style="padding:1.2rem; text-decoration:none; display:flex; align-items:center; gap:1rem; border-left:4px solid #F5A70D;">
    <div style="width:48px; height:48px; border-radius:12px; background:#FFF9EE; color:#F5A70D; display:flex; align-items:center; justify-content:center; font-size:1.3rem;">
      <i class="fa-solid fa-phone-volume"></i>
    </div>
    <div>
      <div style="font-size:1.5rem; font-weight:800; color:#F5A70D; line-height:1.1;"><?= $countToday ?></div>
      <div style="font-size:0.8rem; color:var(--text-muted); font-weight:600;">Today's Follow-ups</div>
    </div>
  </a>

  <a href="index.php?tab=overdue" class="card" style="padding:1.2rem; text-decoration:none; display:flex; align-items:center; gap:1rem; border-left:4px solid #F83636;">
    <div style="width:48px; height:48px; border-radius:12px; background:#FEECEC; color:#F83636; display:flex; align-items:center; justify-content:center; font-size:1.3rem;">
      <i class="fa-solid fa-triangle-exclamation"></i>
    </div>
    <div>
      <div style="font-size:1.5rem; font-weight:800; color:#F83636; line-height:1.1;"><?= $countOverdue ?></div>
      <div style="font-size:0.8rem; color:var(--text-muted); font-weight:600;">Overdue Consultations</div>
    </div>
  </a>

  <a href="index.php?tab=upcoming" class="card" style="padding:1.2rem; text-decoration:none; display:flex; align-items:center; gap:1rem; border-left:4px solid #5955D1;">
    <div style="width:48px; height:48px; border-radius:12px; background:#EEEDFC; color:#5955D1; display:flex; align-items:center; justify-content:center; font-size:1.3rem;">
      <i class="fa-solid fa-calendar-week"></i>
    </div>
    <div>
      <div style="font-size:1.5rem; font-weight:800; color:#5955D1; line-height:1.1;"><?= $countUpcoming ?></div>
      <div style="font-size:0.8rem; color:var(--text-muted); font-weight:600;">Upcoming Consultations</div>
    </div>
  </a>

  <a href="index.php?tab=completed" class="card" style="padding:1.2rem; text-decoration:none; display:flex; align-items:center; gap:1rem; border-left:4px solid #009966;">
    <div style="width:48px; height:48px; border-radius:12px; background:#E6F7F0; color:#009966; display:flex; align-items:center; justify-content:center; font-size:1.3rem;">
      <i class="fa-solid fa-circle-check"></i>
    </div>
    <div>
      <div style="font-size:1.5rem; font-weight:800; color:#009966; line-height:1.1;"><?= $countCompleted ?></div>
      <div style="font-size:0.8rem; color:var(--text-muted); font-weight:600;">Completed Calls</div>
    </div>
  </a>
</div>

<!-- Tab Navigation -->
<div style="display:flex; gap:0.5rem; margin-bottom:1.25rem; flex-wrap:wrap; border-bottom:1px solid var(--border-color); padding-bottom:0.75rem;">
  <a href="index.php?tab=today" class="btn btn-sm <?= ($tabFilter === 'today') ? 'btn-primary' : 'btn-secondary' ?>">
    <i class="fa-solid fa-clock"></i> Today's Calls (<?= $countToday ?>)
  </a>
  <a href="index.php?tab=overdue" class="btn btn-sm <?= ($tabFilter === 'overdue') ? 'btn-primary' : 'btn-secondary' ?>">
    <i class="fa-solid fa-triangle-exclamation"></i> Overdue (<?= $countOverdue ?>)
  </a>
  <a href="index.php?tab=upcoming" class="btn btn-sm <?= ($tabFilter === 'upcoming') ? 'btn-primary' : 'btn-secondary' ?>">
    <i class="fa-solid fa-calendar"></i> Upcoming (<?= $countUpcoming ?>)
  </a>
  <a href="index.php?tab=completed" class="btn btn-sm <?= ($tabFilter === 'completed') ? 'btn-primary' : 'btn-secondary' ?>">
    <i class="fa-solid fa-check"></i> Completed (<?= $countCompleted ?>)
  </a>
  <a href="index.php?tab=all" class="btn btn-sm <?= ($tabFilter === 'all') ? 'btn-primary' : 'btn-secondary' ?>">
    All Logs (<?= $totalFollowups ?>)
  </a>
</div>

<!-- Filter Toolbar -->
<div class="card" style="margin-bottom:1.5rem; padding:1.25rem;">
  <form method="GET" action="index.php" style="display:flex; flex-wrap:wrap; gap:0.75rem; align-items:center;">
    <input type="hidden" name="tab" value="<?= htmlspecialchars($tabFilter) ?>">
    
    <div style="flex:1; min-width:220px; position:relative;">
      <input type="text" name="search" class="form-control" placeholder="Search student name, phone, notes..." value="<?= htmlspecialchars($search) ?>" style="padding-left:2.2rem;">
      <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-light);"></i>
    </div>

    <select name="priority" class="form-control" style="width:auto; min-width:130px;">
      <option value="">All Priorities</option>
      <option value="High" <?= ($filterPriority === 'High') ? 'selected' : '' ?>>🔥 High</option>
      <option value="Moderate" <?= ($filterPriority === 'Moderate') ? 'selected' : '' ?>>⚡ Moderate</option>
      <option value="Low" <?= ($filterPriority === 'Low') ? 'selected' : '' ?>>🌱 Low</option>
    </select>

    <select name="interaction_type" class="form-control" style="width:auto; min-width:150px;">
      <option value="">All Channels</option>
      <option value="Phone Call" <?= ($filterType === 'Phone Call') ? 'selected' : '' ?>>Phone Call</option>
      <option value="WhatsApp" <?= ($filterType === 'WhatsApp') ? 'selected' : '' ?>>WhatsApp</option>
      <option value="Office Visit" <?= ($filterType === 'Office Visit') ? 'selected' : '' ?>>Office Visit</option>
      <option value="Email" <?= ($filterType === 'Email') ? 'selected' : '' ?>>Email</option>
    </select>

    <?php if (!is_counselor()): ?>
      <select name="staff_id" class="form-control" style="width:auto; min-width:160px;">
        <option value="0">All Counselors</option>
        <?php foreach ($staffMembers as $sm): ?>
          <option value="<?= $sm['id'] ?>" <?= ($filterStaff === (int)$sm['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($sm['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    <?php endif; ?>

    <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($filterDate) ?>" style="width:auto;">

    <button type="submit" class="btn btn-primary btn-sm">
      <i class="fa-solid fa-filter"></i> Filter
    </button>
    <?php if (!empty($search) || !empty($filterPriority) || !empty($filterType) || $filterStaff > 0 || !empty($filterDate)): ?>
      <a href="index.php?tab=<?= urlencode($tabFilter) ?>" class="btn btn-secondary btn-sm" title="Clear Filters">
        <i class="fa-solid fa-rotate-left"></i>
      </a>
    <?php endif; ?>
  </form>
</div>

<!-- Follow-ups Table Card -->
<div class="card">
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Candidate & Lead Code</th>
          <th>Direct Contact</th>
          <th>Scheduled Date</th>
          <th>Channel & Topic</th>
          <th>Consultation Notes</th>
          <th>Next Call</th>
          <th>Counselor</th>
          <th>Status</th>
          <th style="text-align:right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($followups)): ?>
          <tr>
            <td colspan="9" style="text-align:center; padding:3.5rem; color:var(--text-muted);">
              <i class="fa-solid fa-phone-slash" style="font-size:2.2rem; margin-bottom:0.75rem; display:block; color:var(--text-light);"></i>
              No follow-up consultations found matching this criteria.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($followups as $f): 
            $isOverdue = ($f['status'] === 'Overdue') || (strtotime($f['followup_date']) < strtotime(date('Y-m-d')) && $f['status'] === 'Pending');
            $cleanPhone = preg_replace('/[^0-9]/', '', $f['lead_phone']);
          ?>
            <tr style="<?= $isOverdue ? 'background-color:#fffdfc;' : '' ?>">
              <td>
                <div style="font-family:monospace; font-size:0.75rem; font-weight:700; color:var(--primary);">
                  <?= htmlspecialchars($f['lead_code']) ?>
                </div>
                <a href="<?= base_url('leads/view.php?id=' . $f['lead_pk']) ?>" style="font-weight:700; color:var(--text-heading); font-size:0.92rem; text-decoration:none;">
                  <?= htmlspecialchars($f['lead_name']) ?>
                </a>
                <div style="font-size:0.72rem; color:var(--text-muted); margin-top:2px;">
                  <?= htmlspecialchars($f['preferred_country']) ?> • <?= htmlspecialchars($f['lead_stage']) ?>
                </div>
              </td>
              <td>
                <div style="font-family:monospace; font-weight:600; font-size:0.85rem; color:var(--text-heading);">
                  <i class="fa-solid fa-phone" style="font-size:0.75rem; color:var(--primary); margin-right:4px;"></i> <?= htmlspecialchars($f['lead_phone']) ?>
                </div>
                <div style="margin-top:4px; display:flex; gap:4px; align-items:center;">
                  <a href="tel:<?= htmlspecialchars($f['lead_phone']) ?>" class="badge" style="background:#e0e7ff; color:#3730a3; text-decoration:none; padding:2px 7px; font-size:0.68rem;" title="Call Candidate">
                    <i class="fa-solid fa-phone"></i> Call
                  </a>
                  <a href="https://wa.me/<?= $cleanPhone ?>" target="_blank" class="badge" style="background:#dcfce7; color:#15803d; text-decoration:none; padding:2px 7px; font-size:0.68rem;" title="Chat on WhatsApp">
                    <i class="fa-brands fa-whatsapp"></i> Chat
                  </a>
                </div>
              </td>
              <td>
                <div style="font-weight:700; font-size:0.85rem;">
                  <?= format_date($f['followup_date']) ?>
                </div>
                <div style="font-size:0.72rem; color:var(--text-muted);">
                  <?= !empty($f['followup_time']) ? date('h:i A', strtotime($f['followup_time'])) : '10:00 AM' ?>
                </div>
              </td>
              <td>
                <div style="font-weight:600; font-size:0.82rem; color:var(--text-heading);">
                  <?php if ($f['interaction_type'] === 'WhatsApp'): ?>
                    <i class="fa-brands fa-whatsapp" style="color:#10b981;"></i> WhatsApp
                  <?php elseif ($f['interaction_type'] === 'Phone Call'): ?>
                    <i class="fa-solid fa-phone" style="color:#2563eb;"></i> Phone Call
                  <?php elseif ($f['interaction_type'] === 'Email'): ?>
                    <i class="fa-regular fa-envelope" style="color:#f59e0b;"></i> Email
                  <?php else: ?>
                    <i class="fa-solid fa-handshake" style="color:#8b5cf6;"></i> <?= htmlspecialchars($f['interaction_type'] ?? 'Consultation') ?>
                  <?php endif; ?>
                </div>
                <?php if (!empty($f['interaction_title'])): ?>
                  <div style="font-size:0.72rem; color:var(--text-muted); margin-top:2px;">
                    <?= htmlspecialchars($f['interaction_title']) ?>
                  </div>
                <?php endif; ?>
              </td>
              <td style="max-width:240px; font-size:0.82rem; color:var(--text-main); line-height:1.35;">
                <?= htmlspecialchars($f['notes'] ?? '-') ?>
              </td>
              <td>
                <div style="font-size:0.82rem; font-weight:600;">
                  <?= !empty($f['next_followup_date']) ? format_date($f['next_followup_date']) : '-' ?>
                </div>
              </td>
              <td>
                <div style="font-size:0.82rem; font-weight:600; color:var(--text-heading);">
                  <?= htmlspecialchars($f['staff_name'] ?? 'Counselor') ?>
                </div>
              </td>
              <td>
                <?php if ($isOverdue): ?>
                  <span class="badge badge-danger" style="font-size:0.72rem; padding:2px 6px;">Overdue</span>
                <?php elseif ($f['status'] === 'Completed'): ?>
                  <span class="badge badge-success" style="font-size:0.72rem; padding:2px 6px;">Completed</span>
                <?php else: ?>
                  <span class="badge badge-warning" style="font-size:0.72rem; padding:2px 6px;">Pending</span>
                <?php endif; ?>
              </td>
              <td style="text-align:right;">
                <div style="display:flex; justify-content:flex-end; gap:0.35rem;">
                  <button type="button" 
                    onclick="openLogCallModal(<?= $f['lead_pk'] ?>, '<?= addslashes(htmlspecialchars($f['lead_name'])) ?>', '<?= addslashes(htmlspecialchars($f['lead_stage'])) ?>')"
                    class="btn btn-sm" 
                    style="background:#f1e6fd; color:#7008E7; border:none; padding:4px 8px; font-size:0.78rem; font-weight:600;" 
                    title="Log Call Outcome & Advance Stage">
                    <i class="fa-solid fa-phone-volume"></i> Log Call
                  </button>
                  <a href="<?= base_url('leads/view.php?id=' . $f['lead_pk']) ?>" class="btn btn-secondary btn-sm" title="View Full Candidate Dossier" style="padding:4px 8px; font-size:0.78rem;">
                    <i class="fa-solid fa-id-card"></i> Dossier
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination Controls -->
  <?php if ($totalPages > 1): ?>
    <div style="padding:1rem 1.25rem; border-top:1px solid var(--border-color); display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:0.5rem;">
      <div style="font-size:0.85rem; color:var(--text-muted);">
        Showing <?= min($totalRecords, $offset + 1) ?> to <?= min($totalRecords, $offset + $perPage) ?> of <?= $totalRecords ?> follow-ups
      </div>
      <div style="display:flex; gap:0.35rem;">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
          <a href="index.php?tab=<?= urlencode($tabFilter) ?>&page=<?= $p ?>&search=<?= urlencode($search) ?>&priority=<?= urlencode($filterPriority) ?>&interaction_type=<?= urlencode($filterType) ?>&staff_id=<?= $filterStaff ?>" 
             class="btn btn-sm <?= ($p === $page) ? 'btn-primary' : 'btn-secondary' ?>" 
             style="min-width:32px; padding:4px 8px;">
            <?= $p ?>
          </a>
        <?php endfor; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
