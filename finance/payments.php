<?php
/**
 * Payment Receipts Ledger
 * University-Level Education Consultancy CRM (EdrootsCRM)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_permission('invoices', 'view');

$currentUser = current_user();
$pageTitle = 'Payment Receipts';
$activePage = 'payments';

$search = trim($_GET['search'] ?? '');
$methodFilter = trim($_GET['method'] ?? '');
$scopeClause = get_crm_scope_clause('l', 'assigned_counselor_id', 'branch_id');

$where = ["1=1", $scopeClause];
$params = [];

if (!empty($search)) {
    $where[] = "(p.receipt_number LIKE ? OR i.invoice_number LIKE ? OR l.full_name LIKE ? OR l.lead_code LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if (!empty($methodFilter)) {
    $where[] = "p.payment_method = ?";
    $params[] = $methodFilter;
}

$whereSql = implode(' AND ', $where);

// Metrics
$totalCollected = (float)$pdo->query("
    SELECT COALESCE(SUM(p.amount), 0)
    FROM payments p
    JOIN leads l ON p.lead_id = l.id
    WHERE {$scopeClause}
")->fetchColumn();

$sql = "
    SELECT p.*, i.invoice_number, l.full_name, l.lead_code, b.name as branch_name, u.name as recorded_by_name
    FROM payments p
    JOIN leads l ON p.lead_id = l.id
    LEFT JOIN invoices i ON p.invoice_id = i.id
    LEFT JOIN branches b ON p.branch_id = b.id
    LEFT JOIN users u ON p.recorded_by = u.id
    WHERE {$whereSql}
    ORDER BY p.id DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$payments = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <h1 style="font-size:1.5rem; font-weight:700; color:var(--text-heading); margin:0 0 0.35rem 0;">Payment Receipts</h1>
    <p style="font-size:0.875rem; color:var(--text-muted); margin:0;">
      Centralized audit ledger of student fee installments, wire transfers, and receipts.
    </p>
  </div>
  <div>
    <span class="badge badge-success" style="font-size:0.9rem; padding:0.5rem 1rem;">
      <i class="fa-solid fa-circle-check"></i> Total Collected: <strong><?= format_currency($totalCollected) ?></strong>
    </span>
  </div>
</div>

<!-- Filters Bar -->
<div class="card" style="margin-bottom:1.5rem; padding:1.25rem;">
  <form method="GET" action="<?= base_url('finance/payments.php') ?>" style="display:flex; flex-wrap:wrap; gap:0.75rem; align-items:center;">
    <div style="flex:1; min-width:240px; position:relative;">
      <input type="text" name="search" class="form-control" placeholder="Search receipt #, invoice #, student..." value="<?= htmlspecialchars($search) ?>" style="padding-left:2.4rem;">
      <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-light);"></i>
    </div>

    <div style="min-width:180px;">
      <select name="method" class="form-control">
        <option value="">All Payment Methods</option>
        <option value="Bank Transfer" <?= $methodFilter === 'Bank Transfer' ? 'selected' : '' ?>>Bank Wire Transfer</option>
        <option value="UPI / GPay" <?= $methodFilter === 'UPI / GPay' ? 'selected' : '' ?>>UPI / GPay</option>
        <option value="Cheque" <?= $methodFilter === 'Cheque' ? 'selected' : '' ?>>Cheque</option>
        <option value="Credit / Debit Card" <?= $methodFilter === 'Credit / Debit Card' ? 'selected' : '' ?>>Credit / Debit Card</option>
        <option value="Cash" <?= $methodFilter === 'Cash' ? 'selected' : '' ?>>Cash</option>
      </select>
    </div>

    <button type="submit" class="btn btn-primary btn-sm">
      <i class="fa-solid fa-filter"></i> Apply
    </button>
    <a href="<?= base_url('finance/payments.php') ?>" class="btn btn-secondary btn-sm">
      <i class="fa-solid fa-rotate-left"></i>
    </a>
  </form>
</div>

<!-- Payments Table -->
<div class="card">
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Receipt Number</th>
          <th>Student Client</th>
          <th>Invoice #</th>
          <th>Date Remitted</th>
          <th>Method</th>
          <th>Transaction Ref</th>
          <th>Amount Paid</th>
          <th>Recorded By</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($payments)): ?>
          <tr>
            <td colspan="8" style="text-align:center; padding:3rem; color:var(--text-muted);">
              No payment receipts found matching this search.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($payments as $p): ?>
            <tr>
              <td style="font-family:monospace; font-weight:700; color:var(--primary);">
                <?= htmlspecialchars($p['receipt_number']) ?>
              </td>
              <td>
                <a href="<?= base_url('customers/view.php?id=' . $p['lead_id']) ?>" style="font-weight:700; color:var(--text-heading); text-decoration:none; font-size:0.9rem;">
                  <?= htmlspecialchars($p['full_name']) ?>
                </a>
                <div style="font-size:0.72rem; color:var(--text-muted);">
                  <?= htmlspecialchars($p['lead_code']) ?> &bull; <?= htmlspecialchars($p['branch_name'] ?? 'Branch') ?>
                </div>
              </td>
              <td>
                <?php if (!empty($p['invoice_number'])): ?>
                  <a href="<?= base_url('finance/view_invoice.php?id=' . $p['invoice_id']) ?>" style="font-family:monospace; font-size:0.85rem; color:var(--primary); font-weight:600;">
                    <?= htmlspecialchars($p['invoice_number']) ?>
                  </a>
                <?php else: ?>
                  <span style="color:var(--text-muted); font-size:0.8rem;">Direct</span>
                <?php endif; ?>
              </td>
              <td style="font-size:0.85rem;"><?= date('d M Y', strtotime($p['payment_date'])) ?></td>
              <td><span class="badge badge-secondary"><?= htmlspecialchars($p['payment_method']) ?></span></td>
              <td style="font-family:monospace; font-size:0.8rem;"><?= htmlspecialchars($p['transaction_reference'] ?: '—') ?></td>
              <td style="font-weight:700; color:#15803d; font-size:0.95rem;"><?= format_currency($p['amount']) ?></td>
              <td style="font-size:0.75rem; color:var(--text-muted);"><?= htmlspecialchars($p['recorded_by_name'] ?? 'Staff') ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
