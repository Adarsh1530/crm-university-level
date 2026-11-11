<?php
/**
 * Invoices & Billings Directory
 * University-Level Education Consultancy CRM (EdrootsCRM)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_permission('invoices', 'view');

$currentUser = current_user();
$pageTitle = 'Invoices & Billings';
$activePage = 'invoices';

$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$branchFilter = isset($_GET['branch_id']) ? (int)$_GET['branch_id'] : 0;

$scopeClause = get_crm_scope_clause('l', 'assigned_counselor_id', 'branch_id');
$where = ["1=1", $scopeClause];
$params = [];

if (!empty($search)) {
    $where[] = "(i.invoice_number LIKE ? OR l.full_name LIKE ? OR l.lead_code LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if (!empty($statusFilter)) {
    $where[] = "i.payment_status = ?";
    $params[] = $statusFilter;
}

if ($branchFilter > 0 && is_super_admin()) {
    $where[] = "l.branch_id = ?";
    $params[] = $branchFilter;
}

$whereSql = implode(' AND ', $where);

// Summary metrics
$stats = $pdo->query("
    SELECT 
        COUNT(*) as total_invoices,
        COALESCE(SUM(i.grand_total), 0) as sum_invoiced,
        COALESCE(SUM(i.paid_amount), 0) as sum_collected,
        COALESCE(SUM(i.balance_due), 0) as sum_balance
    FROM invoices i
    JOIN leads l ON i.lead_id = l.id
    WHERE {$scopeClause}
")->fetch();

$branches = $pdo->query("SELECT id, name, city FROM branches WHERE status = 'Active' ORDER BY name ASC")->fetchAll();

$sql = "
    SELECT i.*, l.full_name, l.lead_code, l.phone, b.name as branch_name, u.name as created_by_name
    FROM invoices i
    JOIN leads l ON i.lead_id = l.id
    LEFT JOIN branches b ON l.branch_id = b.id
    LEFT JOIN users u ON i.created_by = u.id
    WHERE {$whereSql}
    ORDER BY i.id DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$invoices = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <h1 style="font-size:1.5rem; font-weight:700; color:var(--text-heading); margin:0 0 0.35rem 0;">Invoices & Billings</h1>
    <p style="font-size:0.875rem; color:var(--text-muted); margin:0;">
      Consultancy service fee invoices, payment installments, and student client billing ledger.
    </p>
  </div>
  <div>
    <?php if (can('invoices', 'add')): ?>
      <a href="<?= base_url('finance/create_invoice.php') ?>" class="btn btn-primary btn-sm">
        <i class="fa-solid fa-plus"></i> Generate New Invoice
      </a>
    <?php endif; ?>
  </div>
</div>

<!-- KPI Mini Cards -->
<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:1rem; margin-bottom:1.5rem;">
  <div class="card" style="padding:1.2rem; display:flex; align-items:center; gap:1rem;">
    <div style="width:48px; height:48px; border-radius:12px; background:var(--primary-subtle); color:var(--primary); display:flex; align-items:center; justify-content:center; font-size:1.3rem;">
      <i class="fa-solid fa-file-invoice"></i>
    </div>
    <div>
      <div style="font-size:1.4rem; font-weight:800; color:var(--text-heading); line-height:1.1;"><?= format_currency($stats['sum_invoiced'] ?? 0) ?></div>
      <div style="font-size:0.8rem; color:var(--text-muted); font-weight:500;">Total Invoiced (<?= (int)($stats['total_invoices'] ?? 0) ?> Invoices)</div>
    </div>
  </div>

  <div class="card" style="padding:1.2rem; display:flex; align-items:center; gap:1rem;">
    <div style="width:48px; height:48px; border-radius:12px; background:#dcfce7; color:#15803d; display:flex; align-items:center; justify-content:center; font-size:1.3rem;">
      <i class="fa-solid fa-circle-check"></i>
    </div>
    <div>
      <div style="font-size:1.4rem; font-weight:800; color:#15803d; line-height:1.1;"><?= format_currency($stats['sum_collected'] ?? 0) ?></div>
      <div style="font-size:0.8rem; color:var(--text-muted); font-weight:500;">Collected Revenue</div>
    </div>
  </div>

  <div class="card" style="padding:1.2rem; display:flex; align-items:center; gap:1rem;">
    <div style="width:48px; height:48px; border-radius:12px; background:#fee2e2; color:#b91c1c; display:flex; align-items:center; justify-content:center; font-size:1.3rem;">
      <i class="fa-solid fa-clock"></i>
    </div>
    <div>
      <div style="font-size:1.4rem; font-weight:800; color:#b91c1c; line-height:1.1;"><?= format_currency($stats['sum_balance'] ?? 0) ?></div>
      <div style="font-size:0.8rem; color:var(--text-muted); font-weight:500;">Outstanding Receivables</div>
    </div>
  </div>
</div>

<!-- Filters Bar -->
<div class="card" style="margin-bottom:1.5rem; padding:1.25rem;">
  <form method="GET" action="<?= base_url('finance/invoices.php') ?>" style="display:flex; flex-wrap:wrap; gap:0.75rem; align-items:center;">
    <div style="flex:1; min-width:240px; position:relative;">
      <input type="text" name="search" class="form-control" placeholder="Search invoice #, student name, lead code..." value="<?= htmlspecialchars($search) ?>" style="padding-left:2.4rem;">
      <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-light);"></i>
    </div>

    <div style="min-width:160px;">
      <select name="status" class="form-control">
        <option value="">All Payment Statuses</option>
        <option value="Paid" <?= $statusFilter === 'Paid' ? 'selected' : '' ?>>Paid</option>
        <option value="Partially Paid" <?= $statusFilter === 'Partially Paid' ? 'selected' : '' ?>>Partially Paid</option>
        <option value="Unpaid" <?= $statusFilter === 'Unpaid' ? 'selected' : '' ?>>Unpaid</option>
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
    <a href="<?= base_url('finance/invoices.php') ?>" class="btn btn-secondary btn-sm">
      <i class="fa-solid fa-rotate-left"></i>
    </a>
  </form>
</div>

<!-- Invoices Table -->
<div class="card">
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Invoice Number</th>
          <th>Student Client</th>
          <th>Issue Date</th>
          <th>Due Date</th>
          <th>Grand Total</th>
          <th>Amount Paid</th>
          <th>Balance Due</th>
          <th>Status</th>
          <th style="text-align:right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($invoices)): ?>
          <tr>
            <td colspan="9" style="text-align:center; padding:3rem; color:var(--text-muted);">
              No invoices match your filter criteria.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($invoices as $inv): 
            $statusClass = match($inv['payment_status']) {
                'Paid' => 'badge-success',
                'Partially Paid' => 'badge-warning',
                default => 'badge-danger'
            };
          ?>
            <tr>
              <td>
                <a href="<?= base_url('finance/view_invoice.php?id=' . $inv['id']) ?>" style="font-family:monospace; font-weight:700; color:var(--primary); text-decoration:none;">
                  <?= htmlspecialchars($inv['invoice_number']) ?>
                </a>
              </td>
              <td>
                <div style="font-weight:700; color:var(--text-heading); font-size:0.9rem;">
                  <?= htmlspecialchars($inv['full_name']) ?>
                </div>
                <div style="font-family:monospace; font-size:0.72rem; color:var(--text-muted);">
                  <?= htmlspecialchars($inv['lead_code']) ?> • <?= htmlspecialchars($inv['branch_name'] ?? 'Branch') ?>
                </div>
              </td>
              <td style="font-size:0.825rem;"><?= !empty($inv['issue_date']) ? date('d M Y', strtotime($inv['issue_date'])) : (!empty($inv['invoice_date']) ? date('d M Y', strtotime($inv['invoice_date'])) : '-') ?></td>
              <td style="font-size:0.825rem;"><?= !empty($inv['due_date']) ? date('d M Y', strtotime($inv['due_date'])) : '-' ?></td>
              <td style="font-weight:700; font-size:0.9rem;"><?= format_currency($inv['grand_total']) ?></td>
              <td style="font-weight:700; color:#15803d; font-size:0.9rem;"><?= format_currency($inv['paid_amount']) ?></td>
              <td style="font-weight:800; color:<?= $inv['balance_due'] > 0 ? '#b91c1c' : '#15803d' ?>; font-size:0.9rem;">
                <?= format_currency($inv['balance_due']) ?>
              </td>
              <td>
                <span class="badge <?= $statusClass ?>" style="font-weight:700; font-size:0.72rem;">
                  <?= htmlspecialchars($inv['payment_status']) ?>
                </span>
              </td>
              <td style="text-align:right;">
                <div style="display:flex; justify-content:flex-end; gap:0.35rem;">
                  <a href="<?= base_url('finance/view_invoice.php?id=' . $inv['id']) ?>" class="btn btn-secondary btn-sm" title="View Invoice">
                    <i class="fa-solid fa-eye"></i>
                  </a>
                  <a href="<?= base_url('finance/pdf_invoice.php?id=' . $inv['id']) ?>" target="_blank" class="btn btn-outline btn-sm" title="Printable / PDF">
                    <i class="fa-solid fa-print"></i>
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
