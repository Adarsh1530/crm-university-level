<?php
/**
 * Customer Billing Dossier & Client Profile
 * University-Level Education Consultancy CRM (EdrootsCRM)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_permission('customers', 'view');

$customerId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$currentUser = current_user();

if ($customerId <= 0 || !can_access_lead($customerId)) {
    set_flash('danger', 'Access Denied: You cannot access this customer record.');
    redirect('customers/index.php');
}

// Fetch Lead Details
$stmt = $pdo->prepare("
    SELECT l.*, b.name AS branch_name, b.code AS branch_code, b.city AS branch_city,
           u.name AS counselor_name, u.email AS counselor_email, u.phone AS counselor_phone
    FROM leads l
    LEFT JOIN branches b ON l.branch_id = b.id
    LEFT JOIN users u ON l.assigned_counselor_id = u.id
    WHERE l.id = ? AND l.is_customer = 1
    LIMIT 1
");
$stmt->execute([$customerId]);
$customer = $stmt->fetch();

if (!$customer) {
    // If lead exists but not yet invoiced, redirect to leads view with notice
    $chkStmt = $pdo->prepare("SELECT id FROM leads WHERE id = ?");
    $chkStmt->execute([$customerId]);
    if ($chkStmt->fetch()) {
        set_flash('info', 'This student has not yet been invoiced as an official client. Redirected to Lead Dossier.');
        redirect('leads/view.php?id=' . $customerId);
    }
    set_flash('danger', 'Customer record not found.');
    redirect('customers/index.php');
}

$pageTitle = 'Client Dossier: ' . $customer['full_name'];
$activePage = 'customers';

// Fetch Invoices
$invStmt = $pdo->prepare("
    SELECT i.*, u.name AS created_by_name
    FROM invoices i
    LEFT JOIN users u ON i.created_by = u.id
    WHERE i.lead_id = ?
    ORDER BY i.id DESC
");
$invStmt->execute([$customerId]);
$invoices = $invStmt->fetchAll();

// Fetch Payment Receipts
$pStmt = $pdo->prepare("
    SELECT p.*, i.invoice_number, u.name AS recorded_by_name
    FROM payments p
    LEFT JOIN invoices i ON p.invoice_id = i.id
    LEFT JOIN users u ON p.recorded_by = u.id
    WHERE p.lead_id = ?
    ORDER BY p.id DESC
");
$pStmt->execute([$customerId]);
$payments = $pStmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.35rem;">
      <h1 style="font-size:1.5rem; font-weight:700; color:var(--text-heading); margin:0;">
        <?= htmlspecialchars($customer['full_name']) ?>
      </h1>
      <span style="font-family:monospace; background:#e0e7ff; color:#3730a3; padding:2px 8px; border-radius:4px; font-weight:700; font-size:0.8rem;">
        <?= htmlspecialchars($customer['lead_code']) ?>
      </span>
      <span class="badge badge-success" style="font-weight:700; font-size:0.75rem;">
        <i class="fa-solid fa-certificate"></i> ACTIVE CLIENT
      </span>
    </div>
    <p style="font-size:0.875rem; color:var(--text-muted); margin:0;">
      Billing ledger, fee schedules, split payment installments, and admission milestones.
    </p>
  </div>
  <div style="display:flex; gap:0.5rem; align-items:center;">
    <a href="<?= base_url('leads/view.php?id=' . $customer['id']) ?>" class="btn btn-outline btn-sm">
      <i class="fa-solid fa-graduation-cap"></i> View Academic Profile
    </a>
    <?php if (can('invoices', 'add')): ?>
      <a href="<?= base_url('finance/create_invoice.php?lead_id=' . $customer['id']) ?>" class="btn btn-primary btn-sm">
        <i class="fa-solid fa-plus"></i> Generate Invoice
      </a>
    <?php endif; ?>
  </div>
</div>

<!-- Financial Summary Cards -->
<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:1rem; margin-bottom:1.5rem;">
  <div class="card" style="padding:1.25rem;">
    <span style="font-size:0.75rem; color:var(--text-muted); display:block; font-weight:600;">TOTAL INVOICED</span>
    <div style="font-size:1.6rem; font-weight:800; color:var(--text-heading); margin-top:4px;">
      <?= format_currency($customer['invoiced_total']) ?>
    </div>
    <div style="font-size:0.75rem; color:var(--text-muted); margin-top:2px;">Across <?= count($invoices) ?> service invoices</div>
  </div>

  <div class="card" style="padding:1.25rem; border-left:4px solid var(--success);">
    <span style="font-size:0.75rem; color:#15803d; display:block; font-weight:600;">TOTAL COLLECTED</span>
    <div style="font-size:1.6rem; font-weight:800; color:#15803d; margin-top:4px;">
      <?= format_currency($customer['paid_total']) ?>
    </div>
    <div style="font-size:0.75rem; color:var(--text-muted); margin-top:2px;"><?= count($payments) ?> verified payments received</div>
  </div>

  <div class="card" style="padding:1.25rem; border-left:4px solid <?= $customer['balance_total'] > 0 ? 'var(--danger)' : 'var(--success)' ?>;">
    <span style="font-size:0.75rem; color:<?= $customer['balance_total'] > 0 ? '#b91c1c' : '#15803d' ?>; display:block; font-weight:600;">OUTSTANDING BALANCE</span>
    <div style="font-size:1.6rem; font-weight:800; color:<?= $customer['balance_total'] > 0 ? '#b91c1c' : '#15803d' ?>; margin-top:4px;">
      <?= format_currency($customer['balance_total']) ?>
    </div>
    <div style="font-size:0.75rem; color:var(--text-muted); margin-top:2px;">
      <?= $customer['balance_total'] > 0 ? 'Pending split remittance' : 'All accounts cleared' ?>
    </div>
  </div>
</div>

<div style="display:grid; grid-template-columns:2fr 1fr; gap:1.5rem; align-items:start;">
  <!-- Left Side: Invoices & Payments Tables -->
  <div>
    <!-- Invoices List -->
    <div class="card" style="margin-bottom:1.5rem; padding:1.5rem;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
        <h3 style="font-size:1.1rem; font-weight:700; color:var(--text-heading); margin:0;">
          <i class="fa-solid fa-file-invoice" style="color:var(--primary); margin-right:6px;"></i> Invoices Issued
        </h3>
      </div>

      <?php if (empty($invoices)): ?>
        <p style="color:var(--text-muted); font-size:0.85rem; margin:0;">No invoices generated for this client yet.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>Invoice #</th>
                <th>Issue Date</th>
                <th>Grand Total</th>
                <th>Paid</th>
                <th>Balance</th>
                <th>Status</th>
                <th style="text-align:right;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($invoices as $inv): ?>
                <tr>
                  <td>
                    <a href="<?= base_url('finance/view_invoice.php?id=' . $inv['id']) ?>" style="font-family:monospace; font-weight:700; color:var(--primary); text-decoration:none;">
                      <?= htmlspecialchars($inv['invoice_number']) ?>
                    </a>
                  </td>
                  <td style="font-size:0.825rem;"><?= date('d M Y', strtotime($inv['issue_date'])) ?></td>
                  <td style="font-weight:700;"><?= format_currency($inv['grand_total']) ?></td>
                  <td style="color:#15803d; font-weight:600;"><?= format_currency($inv['paid_amount']) ?></td>
                  <td style="color:<?= $inv['balance_due'] > 0 ? '#b91c1c' : '#15803d' ?>; font-weight:700;"><?= format_currency($inv['balance_due']) ?></td>
                  <td>
                    <span class="badge <?= $inv['payment_status'] === 'Paid' ? 'badge-success' : ($inv['payment_status'] === 'Partially Paid' ? 'badge-warning' : 'badge-danger') ?>" style="font-size:0.72rem;">
                      <?= htmlspecialchars($inv['payment_status']) ?>
                    </span>
                  </td>
                  <td style="text-align:right;">
                    <a href="<?= base_url('finance/view_invoice.php?id=' . $inv['id']) ?>" class="btn btn-secondary btn-sm" title="View & Print">
                      <i class="fa-solid fa-eye"></i>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <!-- Payment Receipts Ledger -->
    <div class="card" style="padding:1.5rem;">
      <h3 style="font-size:1.1rem; font-weight:700; color:var(--text-heading); margin:0 0 1rem 0;">
        <i class="fa-solid fa-receipt" style="color:var(--primary); margin-right:6px;"></i> Payment Receipts & Remittances
      </h3>

      <?php if (empty($payments)): ?>
        <p style="color:var(--text-muted); font-size:0.85rem; margin:0;">No payments recorded yet.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>Receipt #</th>
                <th>Invoice</th>
                <th>Date Paid</th>
                <th>Method</th>
                <th>Amount</th>
                <th>Recorded By</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($payments as $p): ?>
                <tr>
                  <td style="font-family:monospace; font-weight:700;"><?= htmlspecialchars($p['receipt_number']) ?></td>
                  <td style="font-family:monospace; font-size:0.8rem;"><?= htmlspecialchars($p['invoice_number'] ?? '—') ?></td>
                  <td style="font-size:0.825rem;"><?= date('d M Y', strtotime($p['payment_date'])) ?></td>
                  <td><span class="badge badge-secondary"><?= htmlspecialchars($p['payment_method']) ?></span></td>
                  <td style="font-weight:700; color:#15803d;"><?= format_currency($p['amount']) ?></td>
                  <td style="font-size:0.75rem; color:var(--text-muted);"><?= htmlspecialchars($p['recorded_by_name'] ?? 'Staff') ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Right Side: Client Overview Card -->
  <div>
    <div class="card" style="padding:1.5rem;">
      <h3 style="font-size:1.05rem; font-weight:700; color:var(--text-heading); margin:0 0 1rem 0;">
        <i class="fa-solid fa-circle-user" style="color:var(--primary); margin-right:6px;"></i> Client Information
      </h3>

      <div style="display:flex; flex-direction:column; gap:0.75rem; font-size:0.85rem;">
        <div>
          <span style="color:var(--text-muted); display:block; font-size:0.72rem;">FULL NAME</span>
          <strong><?= htmlspecialchars($customer['full_name']) ?></strong>
        </div>
        <div>
          <span style="color:var(--text-muted); display:block; font-size:0.72rem;">CONTACT PHONE</span>
          <strong><?= htmlspecialchars($customer['phone']) ?></strong>
        </div>
        <div>
          <span style="color:var(--text-muted); display:block; font-size:0.72rem;">EMAIL ADDRESS</span>
          <strong><?= htmlspecialchars($customer['email'] ?: 'N/A') ?></strong>
        </div>
        <div>
          <span style="color:var(--text-muted); display:block; font-size:0.72rem;">BRANCH ALLOCATION</span>
          <strong><?= htmlspecialchars($customer['branch_name'] ?? 'Global') ?></strong>
        </div>
        <div>
          <span style="color:var(--text-muted); display:block; font-size:0.72rem;">ASSIGNED COUNSELOR</span>
          <strong><?= htmlspecialchars($customer['counselor_name'] ?? 'Staff') ?></strong>
        </div>
        <div>
          <span style="color:var(--text-muted); display:block; font-size:0.72rem;">ADMISSION PIPELINE STAGE</span>
          <span class="badge badge-primary" style="font-weight:700; margin-top:2px; display:inline-block;">
            <?= htmlspecialchars($customer['stage']) ?>
          </span>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
