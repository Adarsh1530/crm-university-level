<?php
/**
 * Call Data Integration Center - Core CRM
 * Fully Aligned with Client Requirements & Malayalam Training Audio:
 * "We have a call data... So, first, you have a call data"
 * Centralized call records repository linked directly to prospect profiles.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$currentUser = current_user();
$pageTitle = 'Call Data Center';
$activePage = 'call_data';

// Filter inputs
$directionFilter = trim($_GET['direction'] ?? '');
$statusFilter = trim($_GET['call_status'] ?? '');
$callerFilter = isset($_GET['caller_id']) ? (int)$_GET['caller_id'] : 0;
$search = trim($_GET['search'] ?? '');
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');

$scopeClause = get_crm_scope_clause('l', 'assigned_counselor_id', 'branch_id');
$where = ["l.is_archived = 0", $scopeClause];
$params = [];

if (!empty($directionFilter)) {
    $where[] = "c.call_direction = ?";
    $params[] = $directionFilter;
}

if (!empty($statusFilter)) {
    $where[] = "c.call_status = ?";
    $params[] = $statusFilter;
}

if ($callerFilter > 0) {
    $where[] = "c.caller_id = ?";
    $params[] = $callerFilter;
}

if (!empty($dateFrom)) {
    $where[] = "DATE(c.created_at) >= ?";
    $params[] = $dateFrom;
}
if (!empty($dateTo)) {
    $where[] = "DATE(c.created_at) <= ?";
    $params[] = $dateTo;
}

if (!empty($search)) {
    $where[] = "(l.full_name LIKE ? OR l.lead_code LIKE ? OR c.phone_dialed LIKE ? OR c.notes LIKE ? OR l.business_name LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

$whereSql = implode(' AND ', $where);

// Metrics
$totalCalls = (int)$pdo->query("SELECT COUNT(*) FROM call_logs c JOIN leads l ON c.lead_id = l.id WHERE {$scopeClause}")->fetchColumn();
$callsToday = (int)$pdo->query("SELECT COUNT(*) FROM call_logs c JOIN leads l ON c.lead_id = l.id WHERE DATE(c.created_at) = CURDATE() AND {$scopeClause}")->fetchColumn();
$connectedCalls = (int)$pdo->query("SELECT COUNT(*) FROM call_logs c JOIN leads l ON c.lead_id = l.id WHERE c.call_status = 'Connected' AND {$scopeClause}")->fetchColumn();
$connectedRate = ($totalCalls > 0) ? round(($connectedCalls / $totalCalls) * 100) : 0;

// Pagination
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM call_logs c JOIN leads l ON c.lead_id = l.id WHERE {$whereSql}");
$countStmt->execute($params);
$totalRecords = (int)$countStmt->fetchColumn();

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$totalPages = max(1, ceil($totalRecords / $perPage));
$offset = ($page - 1) * $perPage;

// Fetch Call Logs
$stmt = $pdo->prepare("
    SELECT c.*, l.lead_code, l.full_name, l.business_name, l.region_number, l.main_region, l.active_status, l.prospectivity,
           u.name as caller_name, r.label as caller_role
    FROM call_logs c
    JOIN leads l ON c.lead_id = l.id
    LEFT JOIN users u ON c.caller_id = u.id
    LEFT JOIN roles r ON u.role_id = r.id
    WHERE {$whereSql}
    ORDER BY c.id DESC
    LIMIT {$perPage} OFFSET {$offset}
");
$stmt->execute($params);
$calls = $stmt->fetchAll();

// Fetch Staff for Caller Filter
$staffList = $pdo->query("SELECT id, name FROM users WHERE status = 'Active' ORDER BY name ASC")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<!-- Header & Quick Actions -->
<div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <h1 style="font-size:1.5rem; font-weight:800; color:var(--text-heading); margin:0 0 0.25rem 0;">
      Call Data Integration Center
    </h1>
    <p style="font-size:0.875rem; color:var(--text-muted); margin:0;">
      Centralized call logging, connection outcomes, durations, and lead profile synchronization.
    </p>
  </div>
  <div style="display:flex; gap:0.5rem; align-items:center;">
    <button type="button" class="btn btn-primary btn-sm" onclick="openTopUpdateModal()" style="font-weight:700;">
      <i class="fa-solid fa-phone"></i> + Log Call
    </button>
  </div>
</div>

<!-- Call Data KPIs -->
<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:1rem; margin-bottom:1.5rem;">
  <div class="card" style="padding:1.15rem; border-radius:12px; display:flex; align-items:center; gap:1rem;">
    <div style="width:46px; height:46px; border-radius:12px; background:#e0f2fe; color:#0284c7; display:flex; align-items:center; justify-content:center; font-size:1.3rem;">
      <i class="fa-solid fa-phone-volume"></i>
    </div>
    <div>
      <div style="font-size:1.45rem; font-weight:800; color:var(--text-heading); line-height:1.1;"><?= number_format($totalCalls) ?></div>
      <div style="font-size:0.78rem; color:var(--text-muted); font-weight:600; margin-top:2px;">Total Calls Logged</div>
    </div>
  </div>

  <div class="card" style="padding:1.15rem; border-radius:12px; display:flex; align-items:center; gap:1rem;">
    <div style="width:46px; height:46px; border-radius:12px; background:#dcfce7; color:#15803d; display:flex; align-items:center; justify-content:center; font-size:1.3rem;">
      <i class="fa-solid fa-calendar-day"></i>
    </div>
    <div>
      <div style="font-size:1.45rem; font-weight:800; color:#15803d; line-height:1.1;"><?= number_format($callsToday) ?></div>
      <div style="font-size:0.78rem; color:var(--text-muted); font-weight:600; margin-top:2px;">Calls Today</div>
    </div>
  </div>

  <div class="card" style="padding:1.15rem; border-radius:12px; display:flex; align-items:center; gap:1rem;">
    <div style="width:46px; height:46px; border-radius:12px; background:#f0fdf4; color:#16a34a; display:flex; align-items:center; justify-content:center; font-size:1.3rem;">
      <i class="fa-solid fa-circle-check"></i>
    </div>
    <div>
      <div style="font-size:1.45rem; font-weight:800; color:#16a34a; line-height:1.1;"><?= $connectedRate ?>%</div>
      <div style="font-size:0.78rem; color:var(--text-muted); font-weight:600; margin-top:2px;">Connection Rate (<?= $connectedCalls ?>)</div>
    </div>
  </div>
</div>

<!-- Search & Filters Bar -->
<div class="card" style="margin-bottom:1.5rem; padding:1.25rem; border-radius:12px;">
  <form method="GET" action="<?= base_url('followups/call_data.php') ?>" style="display:flex; flex-wrap:wrap; gap:0.75rem; align-items:center;">
    
    <div style="flex:2; min-width:240px; position:relative;">
      <input type="text" name="search" class="form-control" placeholder="Search by phone, lead name, or discussion notes..." value="<?= htmlspecialchars($search) ?>" style="padding-left:2.4rem;">
      <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-light);"></i>
    </div>

    <div style="min-width:130px;">
      <select name="direction" class="form-control">
        <option value="">All Directions</option>
        <option value="Outbound" <?= $directionFilter === 'Outbound' ? 'selected' : '' ?>>Outbound</option>
        <option value="Inbound" <?= $directionFilter === 'Inbound' ? 'selected' : '' ?>>Inbound</option>
      </select>
    </div>

    <div style="min-width:140px;">
      <select name="call_status" class="form-control">
        <option value="">All Outcomes</option>
        <option value="Connected" <?= $statusFilter === 'Connected' ? 'selected' : '' ?>>Connected</option>
        <option value="Busy" <?= $statusFilter === 'Busy' ? 'selected' : '' ?>>Line Busy</option>
        <option value="No Answer" <?= $statusFilter === 'No Answer' ? 'selected' : '' ?>>No Answer</option>
        <option value="Left Voicemail" <?= $statusFilter === 'Left Voicemail' ? 'selected' : '' ?>>Left Voicemail</option>
        <option value="Switched Off" <?= $statusFilter === 'Switched Off' ? 'selected' : '' ?>>Switched Off</option>
      </select>
    </div>

    <?php if (is_super_admin() || is_team_leader()): ?>
      <div style="min-width:140px;">
        <select name="caller_id" class="form-control">
          <option value="0">All Agents</option>
          <?php foreach ($staffList as $s): ?>
            <option value="<?= $s['id'] ?>" <?= $callerFilter === (int)$s['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($s['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    <?php endif; ?>

    <button type="submit" class="btn btn-primary btn-sm" style="font-weight:700;">
      <i class="fa-solid fa-filter"></i> Apply
    </button>
    <a href="<?= base_url('followups/call_data.php') ?>" class="btn btn-secondary btn-sm" title="Reset Filters">
      <i class="fa-solid fa-rotate-left"></i>
    </a>
  </form>
</div>

<!-- Call Data Table -->
<div class="card" style="border-radius:14px; overflow:hidden;">
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Date & Time</th>
          <th>Lead Record # & Contact</th>
          <th>Business / Account</th>
          <th>Phone Dialed</th>
          <th>Direction</th>
          <th>Connection Outcome</th>
          <th>Duration</th>
          <th>Agent / Caller</th>
          <th>Discussion Notes</th>
          <th style="text-align:right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($calls)): ?>
          <tr>
            <td colspan="10" style="text-align:center; padding:3.5rem; color:var(--text-muted);">
              <div style="width:56px; height:56px; border-radius:50%; background:#e0f2fe; color:#0284c7; display:inline-flex; align-items:center; justify-content:center; font-size:1.8rem; margin-bottom:0.75rem;">
                <i class="fa-solid fa-phone-slash"></i>
              </div>
              <div style="font-size:1.15rem; font-weight:800; color:var(--text-heading); margin-bottom:0.35rem;">No Call Records Found</div>
              <p style="font-size:0.875rem; color:var(--text-muted); margin:0;">
                No call logs match the selected filter criteria.
              </p>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($calls as $c): 
            $statusBg = ($c['call_status'] === 'Connected') ? '#dcfce7' : (($c['call_status'] === 'Busy') ? '#fef3c7' : '#fee2e2');
            $statusColor = ($c['call_status'] === 'Connected') ? '#15803d' : (($c['call_status'] === 'Busy') ? '#b45309' : '#b91c1c');
          ?>
            <tr>
              <!-- Date & Time -->
              <td style="font-size:0.8rem; font-weight:600; white-space:nowrap;">
                <?= date('d M Y, h:i A', strtotime($c['created_at'])) ?>
              </td>

              <!-- Lead -->
              <td>
                <div style="font-family:monospace; font-size:0.72rem; font-weight:700; color:var(--primary);">
                  <?= htmlspecialchars($c['lead_code']) ?>
                </div>
                <a href="<?= base_url('leads/view.php?id=' . $c['lead_id']) ?>" style="font-weight:700; color:var(--text-heading); text-decoration:none; font-size:0.9rem;">
                  <?= htmlspecialchars($c['full_name']) ?>
                </a>
              </td>

              <!-- Business / Account -->
              <td style="font-size:0.825rem; font-weight:600; color:#334155;">
                <?= htmlspecialchars($c['business_name'] ?: 'Individual') ?>
              </td>

              <!-- Phone -->
              <td style="font-family:monospace; font-weight:700; font-size:0.85rem;">
                <?= htmlspecialchars($c['phone_dialed']) ?>
              </td>

              <!-- Direction -->
              <td>
                <span class="badge" style="background:<?= $c['call_direction'] === 'Inbound' ? '#dbeafe' : '#f1f5f9' ?>; color:<?= $c['call_direction'] === 'Inbound' ? '#1e40af' : '#334155' ?>; font-size:0.72rem; font-weight:700;">
                  <i class="fa-solid <?= $c['call_direction'] === 'Inbound' ? 'fa-arrow-down-left' : 'fa-arrow-up-right' ?>"></i> <?= htmlspecialchars($c['call_direction']) ?>
                </span>
              </td>

              <!-- Outcome -->
              <td>
                <span class="badge" style="background:<?= $statusBg ?>; color:<?= $statusColor ?>; font-size:0.72rem; font-weight:700;">
                  <?= htmlspecialchars($c['call_status']) ?>
                </span>
              </td>

              <!-- Duration -->
              <td style="font-size:0.8rem; font-weight:600;">
                <?= $c['duration_seconds'] ?>s
              </td>

              <!-- Caller -->
              <td style="font-size:0.8rem; font-weight:600;">
                <?= htmlspecialchars($c['caller_name'] ?? 'System') ?>
              </td>

              <!-- Notes -->
              <td style="font-size:0.8rem; color:#475569; max-width:240px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                <?= htmlspecialchars($c['notes'] ?: '—') ?>
              </td>

              <!-- Actions -->
              <td style="text-align:right;">
                <div style="display:inline-flex; gap:4px;">
                  <a href="<?= base_url('leads/view.php?id=' . $c['lead_id']) ?>" class="btn btn-secondary btn-sm" style="font-size:0.75rem; padding:3px 8px;">
                    Profile &rarr;
                  </a>
                  <button type="button" class="btn btn-outline btn-sm" style="padding:3px 8px;" onclick="openLogCallModal(<?= $c['lead_id'] ?>, '<?= htmlspecialchars(addslashes($c['full_name'])) ?>', '<?= htmlspecialchars($c['phone_dialed']) ?>')" title="Call Again">
                    <i class="fa-solid fa-phone"></i>
                  </button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ($totalPages > 1): ?>
    <div style="padding:1rem 1.25rem; display:flex; justify-content:space-between; align-items:center; border-top:1px solid var(--border-color); background:#f8fafc;">
      <span style="font-size:0.8rem; color:var(--text-muted);">
        Showing <?= $offset + 1 ?> to <?= min($offset + $perPage, $totalRecords) ?> of <?= $totalRecords ?> calls
      </span>
      <div style="display:flex; gap:0.35rem;">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
          <a href="<?= base_url('followups/call_data.php?' . http_build_query(array_merge($_GET, ['page' => $p]))) ?>" class="btn btn-sm <?= ($p === $page) ? 'btn-primary' : 'btn-ghost' ?>" style="padding:3px 8px; font-size:0.75rem;">
            <?= $p ?>
          </a>
        <?php endfor; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
