<?php
/**
 * Active Leads Directory
 * University-Level Education Consultancy CRM (EdrootsCRM)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_permission('leads', 'view');

$currentUser = current_user();
$pageTitle = 'Students Directory';
$activePage = 'leads';

// Type filter: All, Leads, Customers (PDF Section 9.2)
$type = strtolower(trim($_GET['type'] ?? 'all'));
if (!in_array($type, ['all', 'leads', 'customers'])) {
    $type = 'all';
}

// Filter inputs (PDF Section 9.2.2)
$search = trim($_GET['search'] ?? '');
$stageFilter = trim($_GET['stage'] ?? '');
$priorityFilter = trim($_GET['priority'] ?? '');
$sourceFilter = trim($_GET['source'] ?? '');
$counselorFilter = isset($_GET['counselor_id']) ? (int)$_GET['counselor_id'] : 0;
$branchFilter = isset($_GET['branch_id']) ? (int)$_GET['branch_id'] : 0;
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');

// Export CSV handler (Strictly restricted from Counselors)
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    if (!can('leads', 'export')) {
        set_flash('danger', 'Access Denied: Counselors are strictly restricted from exporting student databases.');
        redirect('leads/index.php');
    }

    $scopeClause = get_crm_scope_clause('l', 'assigned_counselor_id', 'branch_id');
    $exportWhere = ["l.is_archived = 0", $scopeClause];
    if ($type === 'leads') {
        $exportWhere[] = "l.is_customer = 0";
    } elseif ($type === 'customers') {
        $exportWhere[] = "l.is_customer = 1";
    }
    $exportWhereSql = implode(' AND ', $exportWhere);

    $stmtExp = $pdo->query("
        SELECT l.lead_code, l.full_name, l.phone, l.email, b.name as branch, u.name as counselor,
               l.stage, l.priority, l.source, l.preferred_country, l.preferred_course, l.created_at
        FROM leads l
        LEFT JOIN branches b ON l.branch_id = b.id
        LEFT JOIN users u ON l.assigned_counselor_id = u.id
        WHERE {$exportWhereSql}
        ORDER BY l.id DESC
    ");
    $expRows = $stmtExp->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="Students_' . ucfirst($type) . '_Export_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Lead ID', 'Student Name', 'Canonical Phone', 'Email', 'Branch', 'Assigned Counselor', 'Stage', 'Priority', 'Source', 'Target Country', 'Target Course', 'Created At']);
    foreach ($expRows as $er) {
        fputcsv($out, $er);
    }
    fclose($out);
    exit;
}

// Scoping
$scopeClause = get_crm_scope_clause('l', 'assigned_counselor_id', 'branch_id');
$where = ["l.is_archived = 0", $scopeClause];
$params = [];

// Apply type condition (PDF Section 9.2)
if ($type === 'leads') {
    $where[] = "l.is_customer = 0";
} elseif ($type === 'customers') {
    $where[] = "l.is_customer = 1";
}

if (!empty($search)) {
    $where[] = "(l.full_name LIKE ? OR l.lead_code LIKE ? OR l.phone LIKE ? OR l.email LIKE ? OR l.college_name LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if (!empty($stageFilter)) {
    $where[] = "l.stage = ?";
    $params[] = $stageFilter;
}

if (!empty($priorityFilter)) {
    $where[] = "l.priority = ?";
    $params[] = $priorityFilter;
}

if (!empty($sourceFilter)) {
    $where[] = "l.source = ?";
    $params[] = $sourceFilter;
}

if ($counselorFilter > 0 && (is_super_admin() || is_branch_manager())) {
    $where[] = "l.assigned_counselor_id = ?";
    $params[] = $counselorFilter;
}

if ($branchFilter > 0 && is_super_admin()) {
    $where[] = "l.branch_id = ?";
    $params[] = $branchFilter;
}

// Filter by Date (PDF Section 9.2.2)
if (!empty($dateFrom)) {
    $where[] = "DATE(l.created_at) >= ?";
    $params[] = $dateFrom;
}
if (!empty($dateTo)) {
    $where[] = "DATE(l.created_at) <= ?";
    $params[] = $dateTo;
}

$whereSql = implode(' AND ', $where);

// Section 9.0 Top Tab Navigation Counts
$countAll = (int)$pdo->query("SELECT COUNT(*) FROM leads l WHERE l.is_archived = 0 AND {$scopeClause}")->fetchColumn();
$countLeadsOnly = (int)$pdo->query("SELECT COUNT(*) FROM leads l WHERE l.is_archived = 0 AND l.is_customer = 0 AND {$scopeClause}")->fetchColumn();
$countCustomersOnly = (int)$pdo->query("SELECT COUNT(*) FROM leads l WHERE l.is_archived = 0 AND l.is_customer = 1 AND {$scopeClause}")->fetchColumn();
$countScheduledFollowups = (int)$pdo->query("
    SELECT COUNT(*) FROM followups f
    JOIN leads l ON f.lead_id = l.id
    WHERE f.scheduled_date = CURDATE() AND f.status = 'Pending' AND l.is_archived = 0 AND {$scopeClause}
")->fetchColumn();
$countCollegeData = (int)$pdo->query("SELECT COUNT(*) FROM college_data WHERE is_archived = 0")->fetchColumn();
$countArchived = (int)$pdo->query("SELECT COUNT(*) FROM leads l WHERE l.is_archived = 1 AND {$scopeClause}")->fetchColumn();

// Section 9.1: Leads & Conversions Statistics (This Week, This Month, This FY)
$statsWeekStart = date('Y-m-d', strtotime('monday this week'));
$statsMonthStart = date('Y-m-01');
$curMonth = (int)date('n');
$curYear = (int)date('Y');
$statsFyStart = ($curMonth >= 4) ? "{$curYear}-04-01" : ($curYear - 1) . "-04-01";

$stmtStats = $pdo->query("
    SELECT 
        COUNT(CASE WHEN l.created_at >= '{$statsWeekStart}' THEN 1 END) as leads_week,
        COUNT(CASE WHEN l.created_at >= '{$statsMonthStart}' THEN 1 END) as leads_month,
        COUNT(CASE WHEN l.created_at >= '{$statsFyStart}' THEN 1 END) as leads_fy,
        COUNT(CASE WHEN l.is_customer = 1 AND (COALESCE(l.converted_to_customer_at, l.updated_at, l.created_at) >= '{$statsWeekStart}') THEN 1 END) as conv_week,
        COUNT(CASE WHEN l.is_customer = 1 AND (COALESCE(l.converted_to_customer_at, l.updated_at, l.created_at) >= '{$statsMonthStart}') THEN 1 END) as conv_month,
        COUNT(CASE WHEN l.is_customer = 1 AND (COALESCE(l.converted_to_customer_at, l.updated_at, l.created_at) >= '{$statsFyStart}') THEN 1 END) as conv_fy
    FROM leads l
    WHERE l.is_archived = 0 AND {$scopeClause}
");
$perfStats = $stmtStats->fetch() ?: [
    'leads_week' => 0, 'leads_month' => 0, 'leads_fy' => 0,
    'conv_week' => 0, 'conv_month' => 0, 'conv_fy' => 0
];

// Stage breakdown counts for tab pills
$typeStageWhere = $scopeClause;
if ($type === 'leads') {
    $typeStageWhere .= " AND l.is_customer = 0";
} elseif ($type === 'customers') {
    $typeStageWhere .= " AND l.is_customer = 1";
}
$stageCountsStmt = $pdo->query("
    SELECT l.stage, COUNT(*) as count 
    FROM leads l 
    WHERE l.is_archived = 0 AND {$typeStageWhere} 
    GROUP BY l.stage
");
$stageCounts = $stageCountsStmt->fetchAll(PDO::FETCH_KEY_PAIR);
$totalScopedLeads = array_sum($stageCounts);

// Branches and Counselors for filter dropdowns & reassign modals
$branches = $pdo->query("SELECT id, name, city FROM branches WHERE status = 'Active' ORDER BY name ASC")->fetchAll();

$cslSql = "
    SELECT u.id, u.name, u.branch_id, b.name AS branch_name,
           (SELECT COUNT(*) FROM leads WHERE assigned_counselor_id = u.id AND is_archived = 0) AS active_caseload
    FROM users u 
    JOIN roles r ON u.role_id = r.id 
    LEFT JOIN branches b ON u.branch_id = b.id 
    WHERE r.name = 'counselor' AND u.status = 'Active'
";
if (is_branch_manager()) {
    $cslSql .= " AND u.branch_id = " . (int)$currentUser['branch_id'];
}
$cslSql .= " ORDER BY u.name ASC";
$counselors = $pdo->query($cslSql)->fetchAll();

// Main Lead Query
$sql = "
    SELECT l.*, b.name AS branch_name, b.code AS branch_code, u.name AS counselor_name
    FROM leads l
    LEFT JOIN branches b ON l.branch_id = b.id
    LEFT JOIN users u ON l.assigned_counselor_id = u.id
    WHERE {$whereSql}
    ORDER BY l.id DESC
    LIMIT 150
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$leads = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<!-- PDF Section 9.0: Student Module Top Tab Bar (All, Leads, Customers, Follow-ups, College Datas, Archived, My Tasks) -->
<div class="pdf-student-nav-tabs" style="display:flex; gap:0.25rem; border-bottom:2px solid var(--border-color); margin-bottom:1.25rem; overflow-x:auto; padding-bottom:1px;">
  <a href="<?= base_url('leads/index.php?type=all') ?>" class="pdf-tab" style="padding:0.6rem 1.15rem; font-weight:700; text-decoration:none; font-size:0.875rem; border-radius:8px 8px 0 0; color:<?= ($type === 'all') ? 'var(--primary)' : 'var(--text-muted)' ?>; border-bottom:<?= ($type === 'all') ? '3px solid var(--primary)' : '3px solid transparent' ?>; background:<?= ($type === 'all') ? '#f5f3ff' : 'transparent' ?>; white-space:nowrap;">
    <i class="fa-solid fa-users"></i> All <span class="badge badge-primary" style="margin-left:4px; font-size:0.75rem;"><?= $countAll ?></span>
  </a>
  <a href="<?= base_url('leads/index.php?type=leads') ?>" class="pdf-tab" style="padding:0.6rem 1.15rem; font-weight:700; text-decoration:none; font-size:0.875rem; border-radius:8px 8px 0 0; color:<?= ($type === 'leads') ? 'var(--primary)' : 'var(--text-muted)' ?>; border-bottom:<?= ($type === 'leads') ? '3px solid var(--primary)' : '3px solid transparent' ?>; background:<?= ($type === 'leads') ? '#f5f3ff' : 'transparent' ?>; white-space:nowrap;">
    <i class="fa-solid fa-user-graduate"></i> Leads <span class="badge badge-secondary" style="margin-left:4px; font-size:0.75rem;"><?= $countLeadsOnly ?></span>
  </a>
  <a href="<?= base_url('leads/index.php?type=customers') ?>" class="pdf-tab" style="padding:0.6rem 1.15rem; font-weight:700; text-decoration:none; font-size:0.875rem; border-radius:8px 8px 0 0; color:<?= ($type === 'customers') ? 'var(--primary)' : 'var(--text-muted)' ?>; border-bottom:<?= ($type === 'customers') ? '3px solid var(--primary)' : '3px solid transparent' ?>; background:<?= ($type === 'customers') ? '#f5f3ff' : 'transparent' ?>; white-space:nowrap;">
    <i class="fa-solid fa-certificate"></i> Customers <span class="badge badge-success" style="margin-left:4px; font-size:0.75rem;"><?= $countCustomersOnly ?></span>
  </a>
  <a href="<?= base_url('tasks/index.php?queue=scheduled') ?>" class="pdf-tab" style="padding:0.6rem 1.15rem; font-weight:700; text-decoration:none; font-size:0.875rem; border-radius:8px 8px 0 0; color:var(--text-muted); border-bottom:3px solid transparent; white-space:nowrap;">
    <i class="fa-solid fa-phone-volume"></i> Follow-ups <span class="badge badge-warning" style="margin-left:4px; font-size:0.75rem;"><?= $countScheduledFollowups ?></span>
  </a>
  <a href="<?= base_url('data/college_data.php') ?>" class="pdf-tab" style="padding:0.6rem 1.15rem; font-weight:700; text-decoration:none; font-size:0.875rem; border-radius:8px 8px 0 0; color:var(--text-muted); border-bottom:3px solid transparent; white-space:nowrap;">
    <i class="fa-solid fa-database"></i> College Datas <span class="badge badge-secondary" style="margin-left:4px; font-size:0.75rem;"><?= $countCollegeData ?></span>
  </a>
  <a href="<?= base_url('archive/index.php') ?>" class="pdf-tab" style="padding:0.6rem 1.15rem; font-weight:700; text-decoration:none; font-size:0.875rem; border-radius:8px 8px 0 0; color:var(--text-muted); border-bottom:3px solid transparent; white-space:nowrap;">
    <i class="fa-solid fa-box-archive"></i> Archived <span class="badge badge-secondary" style="margin-left:4px; font-size:0.75rem;"><?= $countArchived ?></span>
  </a>
  <a href="<?= base_url('tasks/index.php') ?>" class="pdf-tab" style="padding:0.6rem 1.15rem; font-weight:700; text-decoration:none; font-size:0.875rem; border-radius:8px 8px 0 0; color:var(--text-muted); border-bottom:3px solid transparent; white-space:nowrap;">
    <i class="fa-solid fa-list-check"></i> My Tasks
  </a>
</div>

<!-- PDF Section 9.1: Statistics (Total Leads & Total Conversions for Week, Month, Financial Year) -->
<div class="card" style="margin-bottom:1.25rem; padding:1rem 1.25rem; background:linear-gradient(135deg, #ffffff 0%, #fafafa 100%); border-radius:12px; border:1px solid #e2e8f0;">
  <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1.25rem;">
    <!-- Section 1: Total Leads -->
    <div style="flex:1; min-width:280px; display:flex; align-items:center; gap:1.25rem; border-right:1px solid var(--border-color); padding-right:1rem;">
      <div style="display:flex; align-items:center; gap:8px;">
        <div style="width:38px; height:38px; border-radius:8px; background:var(--primary-subtle); color:var(--primary); display:flex; align-items:center; justify-content:center; font-size:1.1rem;">
          <i class="fa-solid fa-user-plus"></i>
        </div>
        <div>
          <div style="font-size:0.72rem; font-weight:800; text-transform:uppercase; color:var(--text-muted); letter-spacing:0.5px;">Total Leads</div>
          <div style="font-size:0.92rem; font-weight:700; color:var(--text-heading);"><?= htmlspecialchars($currentUser['name']) ?> & Team</div>
        </div>
      </div>
      <div style="display:flex; gap:1.25rem; margin-left:auto;">
        <div style="text-align:center;">
          <div style="font-size:1.2rem; font-weight:800; color:var(--primary);"><?= $perfStats['leads_week'] ?></div>
          <div style="font-size:0.7rem; color:var(--text-muted); font-weight:600;">This Week</div>
        </div>
        <div style="text-align:center;">
          <div style="font-size:1.2rem; font-weight:800; color:var(--primary);"><?= $perfStats['leads_month'] ?></div>
          <div style="font-size:0.7rem; color:var(--text-muted); font-weight:600;">This Month</div>
        </div>
        <div style="text-align:center;">
          <div style="font-size:1.2rem; font-weight:800; color:var(--primary);"><?= $perfStats['leads_fy'] ?></div>
          <div style="font-size:0.7rem; color:var(--text-muted); font-weight:600;">This FY</div>
        </div>
      </div>
    </div>

    <!-- Section 2: Total Conversions -->
    <div style="flex:1; min-width:280px; display:flex; align-items:center; gap:1.25rem;">
      <div style="display:flex; align-items:center; gap:8px;">
        <div style="width:38px; height:38px; border-radius:8px; background:#dcfce7; color:#15803d; display:flex; align-items:center; justify-content:center; font-size:1.1rem;">
          <i class="fa-solid fa-trophy"></i>
        </div>
        <div>
          <div style="font-size:0.72rem; font-weight:800; text-transform:uppercase; color:#15803d; letter-spacing:0.5px;">Total Conversions</div>
          <div style="font-size:0.92rem; font-weight:700; color:var(--text-heading);">Enrolled Clients</div>
        </div>
      </div>
      <div style="display:flex; gap:1.25rem; margin-left:auto;">
        <div style="text-align:center;">
          <div style="font-size:1.2rem; font-weight:800; color:#15803d;"><?= $perfStats['conv_week'] ?></div>
          <div style="font-size:0.7rem; color:var(--text-muted); font-weight:600;">This Week</div>
        </div>
        <div style="text-align:center;">
          <div style="font-size:1.2rem; font-weight:800; color:#15803d;"><?= $perfStats['conv_month'] ?></div>
          <div style="font-size:0.7rem; color:var(--text-muted); font-weight:600;">This Month</div>
        </div>
        <div style="text-align:center;">
          <div style="font-size:1.2rem; font-weight:800; color:#15803d;"><?= $perfStats['conv_fy'] ?></div>
          <div style="font-size:0.7rem; color:var(--text-muted); font-weight:600;">This FY</div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.25rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <h1 style="font-size:1.4rem; font-weight:700; color:var(--text-heading); margin:0 0 0.25rem 0;">
      <?= ($type === 'leads') ? 'Student Leads' : (($type === 'customers') ? 'Enrolled Customers' : 'All Students (Leads & Customers)') ?>
    </h1>
    <p style="font-size:0.85rem; color:var(--text-muted); margin:0;">
      <?= ($type === 'leads') ? 'Showing candidate leads actively exploring higher education programs.' : (($type === 'customers') ? 'Showing enrolled student clients with generated invoicing and dossiers.' : 'Centralized repository across all student candidate stages and customer profiles.') ?>
    </p>
  </div>
  <div style="display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap;">
    <?php if ($type === 'all'): ?>
      <?php if (can('college_data', 'add')): ?>
        <a href="<?= base_url('data/import_college_data.php') ?>" class="btn btn-outline btn-sm" title="Upload Bulk Student Data from Excel (Section 9.2)">
          <i class="fa-solid fa-file-import"></i> Excel Import
        </a>
      <?php endif; ?>
      <?php if (can('leads', 'export')): ?>
        <a href="<?= base_url('leads/index.php?' . http_build_query(array_merge($_GET, ['export' => 'csv']))) ?>" class="btn btn-secondary btn-sm" title="Export Student Records to CSV (Section 9.2)">
          <i class="fa-solid fa-file-csv"></i> Excel Export
        </a>
      <?php endif; ?>
      <?php if (can('leads', 'add')): ?>
        <a href="<?= base_url('leads/add.php') ?>" class="btn btn-primary btn-sm" title="Create New Student Lead (Section 9.2.7)">
          <i class="fa-solid fa-user-plus"></i> New Lead Add
        </a>
      <?php endif; ?>
    <?php else: ?>
      <?php if (can('leads', 'export')): ?>
        <a href="<?= base_url('leads/index.php?' . http_build_query(array_merge($_GET, ['export' => 'csv']))) ?>" class="btn btn-secondary btn-sm">
          <i class="fa-solid fa-file-csv"></i> Excel Export
        </a>
      <?php endif; ?>
      <?php if (can('leads', 'add')): ?>
        <a href="<?= base_url('leads/add.php') ?>" class="btn btn-primary btn-sm">
          <i class="fa-solid fa-user-plus"></i> New Lead Add
        </a>
      <?php endif; ?>
    <?php endif; ?>
    <?php if (can('leads', 'reassign')): ?>
      <button type="button" class="btn btn-outline btn-sm" onclick="openBulkReassignModal()">
        <i class="fa-solid fa-users-gear"></i> Bulk Reassign
      </button>
    <?php endif; ?>
  </div>
</div>

<!-- 6 Stages Filter Tabs / Pills -->
<div style="display:flex; gap:0.5rem; overflow-x:auto; padding-bottom:0.75rem; margin-bottom:1.25rem;">
  <a href="<?= base_url('leads/index.php?' . http_build_query(array_filter(['type' => ($type !== 'all' ? $type : null)]))) ?>" class="btn btn-sm <?= empty($stageFilter) ? 'btn-primary' : 'btn-secondary' ?>" style="white-space:nowrap; border-radius:20px; padding:0.4rem 1rem;">
    All Stages (<?= $totalScopedLeads ?>)
  </a>
  <?php 
  $stagesList = ['ENQUIRY', 'CUSTOMER', 'APPLICATION', 'OFFER RECEIVED', 'TUITION FEES PAID', 'ENROLLED'];
  foreach ($stagesList as $stg): 
    $cnt = $stageCounts[$stg] ?? 0;
  ?>
    <a href="<?= base_url('leads/index.php?' . http_build_query(array_filter(['type' => ($type !== 'all' ? $type : null), 'stage' => $stg]))) ?>" class="btn btn-sm <?= ($stageFilter === $stg) ? 'btn-primary' : 'btn-secondary' ?>" style="white-space:nowrap; border-radius:20px; padding:0.4rem 0.9rem;">
      <?= htmlspecialchars($stg) ?> (<?= $cnt ?>)
    </a>
  <?php endforeach; ?>
</div>

<!-- Filter Bar (PDF Section 9.2.2 Filters: By Date, Created By, Search, Priority, Source) -->
<div class="card" style="margin-bottom:1.5rem; padding:1.25rem;">
  <form method="GET" action="<?= base_url('leads/index.php') ?>" style="display:flex; flex-wrap:wrap; gap:0.75rem; align-items:center;">
    <?php if ($type !== 'all'): ?>
      <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
    <?php endif; ?>
    <?php if (!empty($stageFilter)): ?>
      <input type="hidden" name="stage" value="<?= htmlspecialchars($stageFilter) ?>">
    <?php endif; ?>

    <div style="flex:1; min-width:240px; position:relative;">
      <input type="text" name="search" class="form-control" placeholder="Search by name, code, phone, email, college..." value="<?= htmlspecialchars($search) ?>" style="padding-left:2.4rem;">
      <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-light);"></i>
    </div>

    <!-- PDF Section 9.2.2: By Date Filter -->
    <div style="display:flex; align-items:center; gap:6px;">
      <span style="font-size:0.75rem; font-weight:700; color:var(--text-muted); white-space:nowrap;">From:</span>
      <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($dateFrom) ?>" style="font-size:0.8rem; padding:4px 8px; max-width:140px;">
    </div>
    <div style="display:flex; align-items:center; gap:6px;">
      <span style="font-size:0.75rem; font-weight:700; color:var(--text-muted); white-space:nowrap;">To:</span>
      <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($dateTo) ?>" style="font-size:0.8rem; padding:4px 8px; max-width:140px;">
    </div>

    <div style="min-width:130px;">
      <select name="priority" class="form-control">
        <option value="">All Priorities</option>
        <option value="High" <?= $priorityFilter === 'High' ? 'selected' : '' ?>>High Priority</option>
        <option value="Moderate" <?= $priorityFilter === 'Moderate' ? 'selected' : '' ?>>Moderate</option>
        <option value="Low" <?= $priorityFilter === 'Low' ? 'selected' : '' ?>>Low</option>
      </select>
    </div>

    <div style="min-width:140px;">
      <select name="source" class="form-control">
        <option value="">All Sources</option>
        <option value="College Data" <?= $sourceFilter === 'College Data' ? 'selected' : '' ?>>College Data</option>
        <option value="Website" <?= $sourceFilter === 'Website' ? 'selected' : '' ?>>Website</option>
        <option value="Meta Ads" <?= $sourceFilter === 'Meta Ads' ? 'selected' : '' ?>>Meta Ads</option>
        <option value="Instagram" <?= $sourceFilter === 'Instagram' ? 'selected' : '' ?>>Instagram</option>
        <option value="Google Ads" <?= $sourceFilter === 'Google Ads' ? 'selected' : '' ?>>Google Ads</option>
        <option value="Referral" <?= $sourceFilter === 'Referral' ? 'selected' : '' ?>>Referral</option>
        <option value="Walk-in" <?= $sourceFilter === 'Walk-in' ? 'selected' : '' ?>>Walk-in</option>
      </select>
    </div>

    <?php if (is_super_admin()): ?>
      <div style="min-width:140px;">
        <select name="branch_id" class="form-control">
          <option value="0">All Branches</option>
          <?php foreach ($branches as $b): ?>
            <option value="<?= $b['id'] ?>" <?= $branchFilter === (int)$b['id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    <?php endif; ?>

    <?php if (is_super_admin() || is_branch_manager()): ?>
      <div style="min-width:150px;">
        <select name="counselor_id" class="form-control">
          <option value="0">Created By / Counselor</option>
          <?php foreach ($counselors as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $counselorFilter === (int)$c['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($c['name']) ?> (<?= $c['active_caseload'] ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    <?php endif; ?>

    <button type="submit" class="btn btn-primary btn-sm">
      <i class="fa-solid fa-filter"></i> Apply
    </button>
    <a href="<?= base_url('leads/index.php' . ($type !== 'all' ? '?type=' . $type : '')) ?>" class="btn btn-secondary btn-sm" title="Reset Filters">
      <i class="fa-solid fa-rotate-left"></i>
    </a>
  </form>
</div>

<!-- Main Active Leads Table -->
<div class="card">
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <?php if (can('leads', 'reassign')): ?>
            <th style="width:36px;"><input type="checkbox" id="selectAllCheckbox" onclick="toggleSelectAll(this)"></th>
          <?php endif; ?>
          <th>Lead Code & Student</th>
          <th>Contact & Phone</th>
          <th>Destination & Course</th>
          <th>Admission Stage</th>
          <th>Priority</th>
          <th>Branch & Counselor</th>
          <th>Follow-ups</th>
          <th style="text-align:right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($leads)): ?>
          <tr>
            <td colspan="<?= can('leads', 'reassign') ? 9 : 8 ?>" style="text-align:center; padding:3.5rem; color:var(--text-muted);">
              <div style="width:56px; height:56px; border-radius:50%; background:var(--primary-subtle); color:var(--primary); display:inline-flex; align-items:center; justify-content:center; font-size:1.8rem; margin-bottom:0.75rem;">
                <i class="fa-solid fa-user-graduate"></i>
              </div>
              <div style="font-size:1.15rem; font-weight:800; color:var(--text-heading); margin-bottom:0.35rem;">No Student Leads Found</div>
              <p style="font-size:0.875rem; color:var(--text-muted); max-width:440px; margin:0 auto 1.25rem auto;">
                <?php if (!empty($search) || !empty($stageFilter) || !empty($priorityFilter) || !empty($sourceFilter)): ?>
                  No student leads match your current search and filter combination. Try clearing your filters.
                <?php else: ?>
                  No active student leads are recorded in this caseload yet. You can add a new inquiry anytime.
                <?php endif; ?>
              </p>
              <div style="display:flex; justify-content:center; gap:0.5rem;">
                <?php if (!empty($search) || !empty($stageFilter) || !empty($priorityFilter) || !empty($sourceFilter)): ?>
                  <a href="<?= base_url('leads/index.php') ?>" class="btn btn-secondary btn-sm"><i class="fa-solid fa-rotate-left"></i> Reset Filters</a>
                <?php endif; ?>
                <?php if (can('leads', 'add')): ?>
                  <button type="button" class="btn btn-primary btn-sm" onclick="openQuickLeadModal()"><i class="fa-solid fa-plus"></i> + Add Student Lead</button>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($leads as $lead): 
            $stageBadgeClass = match($lead['stage']) {
                'ENQUIRY' => 'badge-secondary',
                'CUSTOMER' => 'badge-info',
                'APPLICATION' => 'badge-primary',
                'OFFER RECEIVED' => 'badge-warning',
                'TUITION FEES PAID' => 'badge-primary',
                'ENROLLED' => 'badge-success',
                default => 'badge-secondary'
            };
          ?>
            <tr>
              <?php if (can('leads', 'reassign')): ?>
                <td>
                  <input type="checkbox" class="lead-select-checkbox" value="<?= $lead['id'] ?>">
                </td>
              <?php endif; ?>
              <td>
                <div style="font-family:monospace; font-size:0.75rem; font-weight:700; color:var(--primary); margin-bottom:2px;">
                  <?= htmlspecialchars($lead['lead_code']) ?>
                </div>
                <a href="<?= base_url('leads/view.php?id=' . $lead['id']) ?>" style="font-weight:700; color:var(--text-heading); text-decoration:none; font-size:0.92rem;">
                  <?= htmlspecialchars($lead['full_name']) ?>
                </a>
                <?php if ($lead['is_customer']): ?>
                  <span class="badge badge-success" style="font-size:0.65rem; padding:1px 5px; margin-left:4px;" title="Official Customer (Invoiced)">
                    <i class="fa-solid fa-certificate"></i> CLIENT
                  </span>
                <?php endif; ?>
                <div style="font-size:0.72rem; color:var(--text-muted); margin-top:2px;">
                  <?= htmlspecialchars($lead['college_name'] ?? 'Higher Ed') ?>
                </div>
              </td>
              <td>
                <?php
                  $waMsg = get_whatsapp_message($lead['stage'], $lead['full_name'], $currentUser['name'] ?? 'Edroots', $lead['preferred_country'] ?? 'UK', $lead['preferred_course'] ?? 'Higher Ed');
                  $waUrl = "https://wa.me/91" . normalize_phone($lead['phone']) . "?text=" . urlencode($waMsg);
                  $dialUrl = "tel:" . preg_replace('/[^0-9+]/', '', $lead['phone']);
                ?>
                <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                  <span style="font-family:monospace; font-weight:700; color:var(--text-heading); font-size:0.85rem;">
                    <?= htmlspecialchars($lead['phone']) ?>
                  </span>
                  <a href="<?= $waUrl ?>" target="_blank" class="btn btn-sm" style="background:#25d366; color:#fff; font-size:0.75rem; padding:2px 7px; border-radius:6px; font-weight:700; display:inline-flex; align-items:center; gap:4px;" title="WhatsApp Candidate">
                    <i class="fa-brands fa-whatsapp"></i> Chat
                  </a>
                  <a href="<?= $dialUrl ?>" class="btn btn-outline btn-sm" style="padding:2px 7px; font-size:0.75rem; border-radius:6px;" title="Direct Call">
                    <i class="fa-solid fa-phone" style="font-size:0.75rem; color:var(--primary);"></i>
                  </a>
                </div>
                <?php if (!empty($lead['email'])): ?>
                  <div style="font-size:0.72rem; color:var(--text-muted); margin-top:2px;">
                    <?= htmlspecialchars($lead['email']) ?>
                  </div>
                <?php endif; ?>
              </td>
              <td>
                <div style="font-weight:600; font-size:0.85rem; color:var(--text-heading);">
                  <?= htmlspecialchars($lead['preferred_country'] ?: 'Global') ?>
                </div>
                <div style="font-size:0.75rem; color:var(--text-muted);">
                  <?= htmlspecialchars($lead['preferred_course'] ?: 'General Program') ?>
                </div>
                <div onclick="openChangeIntakeModal(<?= $lead['id'] ?>, '<?= htmlspecialchars(addslashes($lead['full_name'])) ?>', '<?= htmlspecialchars($lead['intake_term'] ?? 'September') ?>', '<?= htmlspecialchars($lead['intake_year'] ?? '2026') ?>')" style="cursor:pointer; margin-top:2px;" title="Click to change InTake (User Manual Section 9.2.4)">
                  <span class="badge badge-secondary" style="font-size:0.68rem; font-weight:600; padding:1px 5px;">
                    <i class="fa-regular fa-calendar"></i> <?= htmlspecialchars($lead['intake_term'] ?: 'Sept') ?> <?= htmlspecialchars($lead['intake_year'] ?: date('Y')) ?>
                    <i class="fa-solid fa-pen" style="font-size:0.6rem; color:var(--text-light); margin-left:2px;"></i>
                  </span>
                </div>
              </td>
              <td>
                <span class="badge <?= $stageBadgeClass ?>" style="font-weight:700; font-size:0.72rem;">
                  <?= htmlspecialchars($lead['stage']) ?>
                </span>
                <?php if ($lead['secondary_status'] !== 'Active'): ?>
                  <div style="font-size:0.7rem; color:var(--danger); font-weight:600; margin-top:2px;">
                    <?= htmlspecialchars($lead['secondary_status']) ?>
                  </div>
                <?php endif; ?>
              </td>
              <td>
                <div onclick="openChangeProspectivityModal(<?= $lead['id'] ?>, '<?= htmlspecialchars(addslashes($lead['full_name'])) ?>', '<?= htmlspecialchars($lead['priority']) ?>')" style="cursor:pointer;" title="Click to change Prospectivity (User Manual Section 9.2.3)">
                  <?= priority_badge($lead['priority']) ?>
                  <i class="fa-solid fa-pen" style="font-size:0.65rem; color:var(--text-light); margin-left:2px;"></i>
                </div>
              </td>
              <td>
                <div style="font-size:0.82rem; font-weight:600; color:var(--text-heading);">
                  <?= htmlspecialchars($lead['counselor_name'] ?? 'Unassigned') ?>
                </div>
                <div style="font-size:0.72rem; color:var(--text-muted);">
                  <i class="fa-solid fa-location-dot" style="font-size:0.68rem;"></i> <?= htmlspecialchars($lead['branch_name'] ?? 'General') ?>
                </div>
              </td>
              <td>
                <span class="badge badge-secondary" style="font-weight:600; font-size:0.75rem;">
                  <?= (int)$lead['followup_count'] ?> Logs
                </span>
                <?php if (!empty($lead['next_followup_date'])): ?>
                  <div style="font-size:0.72rem; color:var(--text-muted); margin-top:2px;">
                    Next: <strong><?= date('d M', strtotime($lead['next_followup_date'])) ?></strong>
                  </div>
                <?php endif; ?>
              </td>
              <td style="text-align:right;">
                <div style="display:flex; justify-content:flex-end; gap:0.35rem; align-items:center;">
                  <button type="button" class="btn btn-primary btn-sm" style="font-size:0.75rem; padding:3px 8px; font-weight:600;" onclick="openLogCallModal(<?= $lead['id'] ?>, '<?= htmlspecialchars(addslashes($lead['full_name'])) ?>', '<?= htmlspecialchars($lead['stage']) ?>')" title="Log Call Outcome & Next Follow-up">
                    <i class="fa-solid fa-phone-volume"></i> <span class="d-none d-lg-inline">Log Call</span>
                  </button>
                  <button type="button" class="btn btn-secondary btn-sm" style="font-size:0.75rem; padding:3px 8px;" onclick="openAdvanceStageModal(<?= $lead['id'] ?>, '<?= htmlspecialchars(addslashes($lead['full_name'])) ?>', '<?= htmlspecialchars($lead['stage']) ?>')" title="Advance Admission Stage">
                    <i class="fa-solid fa-stairs"></i>
                  </button>
                  <a href="<?= base_url('leads/view.php?id=' . $lead['id']) ?>" class="btn btn-outline btn-sm" style="padding:3px 8px;" title="View Student Dossier">
                    <i class="fa-solid fa-eye"></i>
                  </a>
                  <?php if (can('leads', 'edit')): ?>
                    <a href="<?= base_url('leads/edit.php?id=' . $lead['id']) ?>" class="btn btn-ghost btn-sm" style="padding:3px 8px;" title="Edit Profile">
                      <i class="fa-solid fa-pen-to-square"></i>
                    </a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Bulk Reassignment Modal (Super Admin & Branch Manager only) -->
<?php if (can('leads', 'reassign')): ?>
  <div class="modal fade" id="bulkReassignModal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(12,36,60,0.5); align-items:center; justify-content:center;">
    <div class="modal-dialog" style="background:#fff; border-radius:14px; max-width:500px; width:92%; margin:auto; box-shadow:0 20px 50px rgba(0,0,0,0.2); overflow:hidden;">
      <form method="POST" action="<?= base_url('leads/bulk_assign.php') ?>" id="bulkAssignForm">
        <?= csrf_field() ?>
        <input type="hidden" name="lead_ids" id="bulk_lead_ids" value="">

        <div class="modal-header" style="padding:1.25rem 1.5rem; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
          <h3 style="font-size:1.15rem; font-weight:700; margin:0; color:var(--text-heading);">
            <i class="fa-solid fa-shuffle" style="color:var(--primary); margin-right:8px;"></i> Reassign Selected Leads
          </h3>
          <button type="button" onclick="closeBulkReassignModal()" style="border:none; background:none; font-size:1.4rem; cursor:pointer; color:var(--text-muted);">&times;</button>
        </div>

        <div class="modal-body" style="padding:1.5rem;">
          <div style="background:var(--bg-subtle); border-radius:8px; padding:0.85rem; margin-bottom:1.25rem; font-size:0.85rem;">
            Selected Leads: <strong id="selected_count_badge">0 leads</strong>
          </div>

          <div class="form-group" style="margin-bottom:1.25rem;">
            <label class="form-label">New Assigned Counselor <span style="color:red;">*</span></label>
            <select name="target_counselor_id" class="form-control" required>
              <option value="">Select Counselor...</option>
              <?php foreach ($counselors as $c): ?>
                <option value="<?= $c['id'] ?>">
                  <?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['branch_name'] ?? 'Hub') ?>) — Active: <?= $c['active_caseload'] ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group" style="margin-bottom:0.5rem;">
            <label class="form-label">Reassignment Reason / Note</label>
            <textarea name="reassign_reason" class="form-control" rows="2" placeholder="Caseload rebalance or regional intake reallocation..."></textarea>
          </div>
        </div>

        <div class="modal-footer" style="padding:1rem 1.5rem; border-top:1px solid var(--border-color); display:flex; justify-content:flex-end; gap:0.5rem;">
          <button type="button" onclick="closeBulkReassignModal()" class="btn btn-secondary btn-sm">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-check"></i> Execute Reassignment
          </button>
        </div>
      </form>
    </div>
  </div>

  <script>
  function toggleSelectAll(master) {
    const boxes = document.querySelectorAll('.lead-select-checkbox');
    boxes.forEach(b => b.checked = master.checked);
  }

  function openBulkReassignModal() {
    const selected = Array.from(document.querySelectorAll('.lead-select-checkbox:checked')).map(b => b.value);
    if (selected.length === 0) {
      alert('Please check at least one lead from the table to reassign.');
      return;
    }
    document.getElementById('bulk_lead_ids').value = selected.join(',');
    document.getElementById('selected_count_badge').innerText = selected.length + ' leads selected';
    document.getElementById('bulkReassignModal').style.display = 'flex';
  }

  function closeBulkReassignModal() {
    document.getElementById('bulkReassignModal').style.display = 'none';
  }
  </script>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
