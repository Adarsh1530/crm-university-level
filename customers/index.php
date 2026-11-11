<?php
/**
 * Customers Directory - Formally Invoiced Student Clients
 * University-Level Education Consultancy CRM (EdrootsCRM)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_permission('customers', 'view');

$currentUser = current_user();
$pageTitle = 'Customers (Invoiced Clients)';
$activePage = 'customers';

$search = trim($_GET['search'] ?? '');
$paymentFilter = trim($_GET['payment_status'] ?? '');
$branchFilter = isset($_GET['branch_id']) ? (int)$_GET['branch_id'] : 0;
$counselorFilter = isset($_GET['counselor_id']) ? (int)$_GET['counselor_id'] : 0;

$scopeClause = get_crm_scope_clause('l', 'assigned_counselor_id', 'branch_id');
$where = ["l.is_customer = 1", "l.is_archived = 0", $scopeClause];
$params = [];

if (!empty($search)) {
    $where[] = "(l.full_name LIKE ? OR l.lead_code LIKE ? OR l.phone LIKE ? OR l.email LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if ($paymentFilter === 'Paid') {
    $where[] = "l.balance_total <= 0 AND l.invoiced_total > 0";
} elseif ($paymentFilter === 'Partial') {
    $where[] = "l.paid_total > 0 AND l.balance_total > 0";
} elseif ($paymentFilter === 'Unpaid') {
    $where[] = "l.paid_total = 0 AND l.invoiced_total > 0";
}

if ($branchFilter > 0 && is_super_admin()) {
    $where[] = "l.branch_id = ?";
    $params[] = $branchFilter;
}

if ($counselorFilter > 0 && (is_super_admin() || is_branch_manager())) {
    $where[] = "l.assigned_counselor_id = ?";
    $params[] = $counselorFilter;
}

$whereSql = implode(' AND ', $where);

// Aggregates for header cards
$statsStmt = $pdo->query("
    SELECT 
        COUNT(*) as total_customers,
        COALESCE(SUM(l.invoiced_total), 0) as total_invoiced,
        COALESCE(SUM(l.paid_total), 0) as total_collected,
        COALESCE(SUM(l.balance_total), 0) as total_receivable
    FROM leads l
    WHERE l.is_customer = 1 AND l.is_archived = 0 AND {$scopeClause}
");
$custStats = $statsStmt->fetch();

$branches = $pdo->query("SELECT id, name, city FROM branches WHERE status = 'Active' ORDER BY name ASC")->fetchAll();

$cslSql = "
    SELECT u.id, u.name, u.branch_id, b.name AS branch_name
    FROM users u 
    JOIN roles r ON u.role_id = r.id 
    LEFT JOIN branches b ON u.branch_id = b.id 
    WHERE r.name = 'counselor' AND u.status = 'Active'
";
if (is_branch_manager()) {
    $cslSql .= " AND u.branch_id = " . (int)$currentUser['branch_id'];
}
$counselors = $pdo->query($cslSql)->fetchAll();

// Main Query
$sql = "
    SELECT l.*, b.name AS branch_name, u.name AS counselor_name,
           (SELECT COUNT(*) FROM invoices WHERE lead_id = l.id) AS invoice_count
    FROM leads l
    LEFT JOIN branches b ON l.branch_id = b.id
    LEFT JOIN users u ON l.assigned_counselor_id = u.id
    WHERE {$whereSql}
    ORDER BY l.id DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<!-- PDF Section 9.0: Student Module Top Tab Bar -->
<div class="pdf-student-nav-tabs" style="display:flex; gap:0.25rem; border-bottom:2px solid var(--border-color); margin-bottom:1.25rem; overflow-x:auto; padding-bottom:1px;">
  <a href="<?= base_url('leads/index.php?type=all') ?>" class="pdf-tab" style="padding:0.6rem 1.15rem; font-weight:700; text-decoration:none; font-size:0.875rem; border-radius:8px 8px 0 0; color:var(--text-muted); border-bottom:3px solid transparent; white-space:nowrap;">
    <i class="fa-solid fa-users"></i> All
  </a>
  <a href="<?= base_url('leads/index.php?type=leads') ?>" class="pdf-tab" style="padding:0.6rem 1.15rem; font-weight:700; text-decoration:none; font-size:0.875rem; border-radius:8px 8px 0 0; color:var(--text-muted); border-bottom:3px solid transparent; white-space:nowrap;">
    <i class="fa-solid fa-user-graduate"></i> Leads
  </a>
  <a href="<?= base_url('customers/index.php') ?>" class="pdf-tab" style="padding:0.6rem 1.15rem; font-weight:700; text-decoration:none; font-size:0.875rem; border-radius:8px 8px 0 0; color:var(--primary); border-bottom:3px solid var(--primary); background:#f5f3ff; white-space:nowrap;">
    <i class="fa-solid fa-certificate"></i> Customers <span class="badge badge-success" style="margin-left:4px; font-size:0.75rem;"><?= (int)($custStats['total_customers'] ?? 0) ?></span>
  </a>
  <a href="<?= base_url('tasks/index.php?queue=scheduled') ?>" class="pdf-tab" style="padding:0.6rem 1.15rem; font-weight:700; text-decoration:none; font-size:0.875rem; border-radius:8px 8px 0 0; color:var(--text-muted); border-bottom:3px solid transparent; white-space:nowrap;">
    <i class="fa-solid fa-phone-volume"></i> Follow-ups
  </a>
  <a href="<?= base_url('data/college_data.php') ?>" class="pdf-tab" style="padding:0.6rem 1.15rem; font-weight:700; text-decoration:none; font-size:0.875rem; border-radius:8px 8px 0 0; color:var(--text-muted); border-bottom:3px solid transparent; white-space:nowrap;">
    <i class="fa-solid fa-database"></i> College Datas
  </a>
  <a href="<?= base_url('archive/index.php') ?>" class="pdf-tab" style="padding:0.6rem 1.15rem; font-weight:700; text-decoration:none; font-size:0.875rem; border-radius:8px 8px 0 0; color:var(--text-muted); border-bottom:3px solid transparent; white-space:nowrap;">
    <i class="fa-solid fa-box-archive"></i> Archived
  </a>
  <a href="<?= base_url('tasks/index.php') ?>" class="pdf-tab" style="padding:0.6rem 1.15rem; font-weight:700; text-decoration:none; font-size:0.875rem; border-radius:8px 8px 0 0; color:var(--text-muted); border-bottom:3px solid transparent; white-space:nowrap;">
    <i class="fa-solid fa-list-check"></i> My Tasks
  </a>
</div>

<div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <h1 style="font-size:1.5rem; font-weight:700; color:var(--text-heading); margin:0 0 0.35rem 0;">Customers (Invoiced Clients)</h1>
    <p style="font-size:0.875rem; color:var(--text-muted); margin:0;">
      Formally classified student clients with generated service fee invoices, active billing ledgers, and installment balances.
    </p>
  </div>
  <div>
    <?php if (can('invoices', 'add')): ?>
      <a href="<?= base_url('finance/create_invoice.php') ?>" class="btn btn-primary btn-sm">
        <i class="fa-solid fa-plus"></i> Generate Service Invoice
      </a>
    <?php endif; ?>
  </div>
</div>

<!-- KPI Mini Cards -->
<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:1rem; margin-bottom:1.5rem;">
  <div class="card" style="padding:1.2rem; display:flex; align-items:center; gap:1rem;">
    <div style="width:48px; height:48px; border-radius:12px; background:#dcfce7; color:#15803d; display:flex; align-items:center; justify-content:center; font-size:1.3rem;">
      <i class="fa-solid fa-certificate"></i>
    </div>
    <div>
      <div style="font-size:1.5rem; font-weight:800; color:#15803d; line-height:1.1;"><?= (int)($custStats['total_customers'] ?? 0) ?></div>
      <div style="font-size:0.8rem; color:var(--text-muted); font-weight:500;">Active Clients</div>
    </div>
  </div>

  <div class="card" style="padding:1.2rem; display:flex; align-items:center; gap:1rem;">
    <div style="width:48px; height:48px; border-radius:12px; background:var(--primary-subtle); color:var(--primary); display:flex; align-items:center; justify-content:center; font-size:1.3rem;">
      <i class="fa-solid fa-file-invoice"></i>
    </div>
    <div>
      <div style="font-size:1.4rem; font-weight:800; color:var(--text-heading); line-height:1.1;"><?= format_currency($custStats['total_invoiced'] ?? 0) ?></div>
      <div style="font-size:0.8rem; color:var(--text-muted); font-weight:500;">Total Invoiced</div>
    </div>
  </div>

  <div class="card" style="padding:1.2rem; display:flex; align-items:center; gap:1rem;">
    <div style="width:48px; height:48px; border-radius:12px; background:#ecfdf5; color:#059669; display:flex; align-items:center; justify-content:center; font-size:1.3rem;">
      <i class="fa-solid fa-hand-holding-dollar"></i>
    </div>
    <div>
      <div style="font-size:1.4rem; font-weight:800; color:#059669; line-height:1.1;"><?= format_currency($custStats['total_collected'] ?? 0) ?></div>
      <div style="font-size:0.8rem; color:var(--text-muted); font-weight:500;">Collected Revenue</div>
    </div>
  </div>

  <div class="card" style="padding:1.2rem; display:flex; align-items:center; gap:1rem;">
    <div style="width:48px; height:48px; border-radius:12px; background:#fee2e2; color:#b91c1c; display:flex; align-items:center; justify-content:center; font-size:1.3rem;">
      <i class="fa-solid fa-scale-unbalanced"></i>
    </div>
    <div>
      <div style="font-size:1.4rem; font-weight:800; color:#b91c1c; line-height:1.1;"><?= format_currency($custStats['total_receivable'] ?? 0) ?></div>
      <div style="font-size:0.8rem; color:var(--text-muted); font-weight:500;">Outstanding Balance</div>
    </div>
  </div>
</div>

<!-- Filters Bar -->
<div class="card" style="margin-bottom:1.5rem; padding:1.25rem;">
  <form method="GET" action="<?= base_url('customers/index.php') ?>" style="display:flex; flex-wrap:wrap; gap:0.75rem; align-items:center;">
    <div style="flex:1; min-width:240px; position:relative;">
      <input type="text" name="search" class="form-control" placeholder="Search client name, code, phone, email..." value="<?= htmlspecialchars($search) ?>" style="padding-left:2.4rem;">
      <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-light);"></i>
    </div>

    <div style="min-width:160px;">
      <select name="payment_status" class="form-control">
        <option value="">All Payment Statuses</option>
        <option value="Paid" <?= $paymentFilter === 'Paid' ? 'selected' : '' ?>>Fully Cleared</option>
        <option value="Partial" <?= $paymentFilter === 'Partial' ? 'selected' : '' ?>>Partial Installments</option>
        <option value="Unpaid" <?= $paymentFilter === 'Unpaid' ? 'selected' : '' ?>>Unpaid Balances</option>
      </select>
    </div>

    <?php if (is_super_admin()): ?>
      <div style="min-width:160px;">
        <select name="branch_id" class="form-control">
          <option value="0">All Branches</option>
          <?php foreach ($branches as $b): ?>
            <option value="<?= $b['id'] ?>" <?= $branchFilter === (int)$b['id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    <?php endif; ?>

    <button type="submit" class="btn btn-primary btn-sm">
      <i class="fa-solid fa-filter"></i> Apply
    </button>
    <a href="<?= base_url('customers/index.php') ?>" class="btn btn-secondary btn-sm">
      <i class="fa-solid fa-rotate-left"></i>
    </a>
  </form>
</div>

<!-- Customers Table -->
<div class="card">
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Client & Lead Code</th>
          <th>Phone & Contact</th>
          <th>Invoiced Amount</th>
          <th>Paid Amount</th>
          <th>Balance Due</th>
          <th>Admission Stage</th>
          <th>Branch & Counselor</th>
          <th style="text-align:right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($customers)): ?>
          <tr>
            <td colspan="8" style="text-align:center; padding:3rem; color:var(--text-muted);">
              <i class="fa-solid fa-certificate" style="font-size:2rem; margin-bottom:0.75rem; display:block; color:var(--text-light);"></i>
              No active invoiced customers match this filter.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($customers as $cust): 
            $bal = (float)$cust['balance_total'];
            $balColor = $bal <= 0 ? 'var(--success)' : ($cust['paid_total'] > 0 ? '#b45309' : 'var(--danger)');
          ?>
            <tr>
              <td>
                <div style="font-family:monospace; font-size:0.75rem; font-weight:700; color:var(--primary);">
                  <?= htmlspecialchars($cust['lead_code']) ?>
                </div>
                <a href="<?= base_url('customers/view.php?id=' . $cust['id']) ?>" style="font-weight:700; color:var(--text-heading); font-size:0.92rem; text-decoration:none;">
                  <?= htmlspecialchars($cust['full_name']) ?>
                </a>
                <div style="font-size:0.72rem; color:var(--text-muted); margin-top:2px;">
                  <?= htmlspecialchars($cust['preferred_country']) ?> • <?= htmlspecialchars($cust['preferred_course']) ?>
                </div>
              </td>
              <td>
                <div style="font-family:monospace; font-weight:600; font-size:0.85rem; color:var(--text-heading);">
                  <i class="fa-solid fa-phone" style="font-size:0.75rem; color:var(--primary); margin-right:4px;"></i> <?= htmlspecialchars($cust['phone']) ?>
                </div>
                <?php $cleanPhone = preg_replace('/[^0-9]/', '', $cust['phone']); ?>
                <div style="margin-top:4px; display:flex; gap:4px; align-items:center;">
                  <a href="tel:<?= htmlspecialchars($cust['phone']) ?>" class="badge" style="background:#e0e7ff; color:#3730a3; text-decoration:none; padding:2px 7px; font-size:0.68rem;" title="Call Client">
                    <i class="fa-solid fa-phone"></i> Call
                  </a>
                  <a href="https://wa.me/<?= $cleanPhone ?>" target="_blank" class="badge" style="background:#dcfce7; color:#15803d; text-decoration:none; padding:2px 7px; font-size:0.68rem;" title="Chat on WhatsApp">
                    <i class="fa-brands fa-whatsapp"></i> Chat
                  </a>
                </div>
                <?php if (!empty($cust['email'])): ?>
                  <div style="font-size:0.72rem; color:var(--text-muted); margin-top:3px;">
                    <?= htmlspecialchars($cust['email']) ?>
                  </div>
                <?php endif; ?>
              </td>
              <td style="font-weight:700; font-size:0.9rem;">
                <?= format_currency($cust['invoiced_total']) ?>
              </td>
              <td style="font-weight:700; color:#15803d; font-size:0.9rem;">
                <?= format_currency($cust['paid_total']) ?>
              </td>
              <td style="font-weight:800; color:<?= $balColor ?>; font-size:0.9rem;">
                <?= format_currency($cust['balance_total']) ?>
                <?php if ($bal <= 0): ?>
                  <span class="badge badge-success" style="font-size:0.65rem; padding:1px 5px; margin-left:2px;">CLEARED</span>
                <?php endif; ?>
              </td>
              <td>
                <button type="button" 
                  onclick="openAdvanceStageModal(<?= $cust['id'] ?>, '<?= addslashes(htmlspecialchars($cust['full_name'])) ?>', '<?= addslashes(htmlspecialchars($cust['stage'])) ?>')"
                  class="badge badge-primary" 
                  style="border:none; cursor:pointer; font-size:0.72rem; font-weight:700; display:inline-flex; align-items:center; gap:4px;"
                  title="Click to Advance Admission Stage">
                  <?= htmlspecialchars($cust['stage']) ?> <i class="fa-solid fa-angle-right" style="font-size:0.65rem;"></i>
                </button>
              </td>
              <td>
                <div style="font-size:0.82rem; font-weight:600; color:var(--text-heading);">
                  <?= htmlspecialchars($cust['counselor_name'] ?? 'Counselor') ?>
                </div>
                <div style="font-size:0.72rem; color:var(--text-muted);">
                  <?= htmlspecialchars($cust['branch_name'] ?? 'Branch') ?>
                </div>
              </td>
              <td style="text-align:right;">
                <div style="display:flex; justify-content:flex-end; gap:0.35rem;">
                  <button type="button" 
                    onclick="openLogCallModal(<?= $cust['id'] ?>, '<?= addslashes(htmlspecialchars($cust['full_name'])) ?>', '<?= addslashes(htmlspecialchars($cust['stage'])) ?>')"
                    class="btn btn-sm" 
                    style="background:#f1e6fd; color:#7008E7; border:none; padding:4px 8px; font-size:0.78rem; font-weight:600;" 
                    title="Log Call / Interaction">
                    <i class="fa-solid fa-phone-volume"></i> Log
                  </button>
                  <a href="<?= base_url('customers/view.php?id=' . $cust['id']) ?>" class="btn btn-secondary btn-sm" title="View Customer Ledger & Invoices" style="padding:4px 8px; font-size:0.78rem;">
                    <i class="fa-solid fa-file-invoice-dollar"></i> Ledger
                  </a>
                  <a href="<?= base_url('leads/view.php?id=' . $cust['id']) ?>" class="btn btn-outline btn-sm" title="View Full Student Dossier" style="padding:4px 8px; font-size:0.78rem;">
                    <i class="fa-solid fa-id-card"></i>
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
