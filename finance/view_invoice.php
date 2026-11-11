<?php
/**
 * Detailed Invoice View & Payment Recording
 * University-Level Education Consultancy CRM (EdrootsCRM)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_permission('invoices', 'view');

$invoiceId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$currentUser = current_user();

if ($invoiceId <= 0) {
    set_flash('danger', 'Invalid invoice identifier.');
    redirect('finance/invoices.php');
}

// Fetch Invoice with Lead and Branch
$stmt = $pdo->prepare("
    SELECT i.*, l.full_name, l.lead_code, l.phone, l.email, l.city, l.state,
           b.name as branch_name, b.code as branch_code, b.address as branch_address, b.phone as branch_phone,
           u.name as created_by_name
    FROM invoices i
    JOIN leads l ON i.lead_id = l.id
    LEFT JOIN branches b ON i.branch_id = b.id
    LEFT JOIN users u ON i.created_by = u.id
    WHERE i.id = ?
    LIMIT 1
");
$stmt->execute([$invoiceId]);
$invoice = $stmt->fetch();

if (!$invoice || !can_access_lead($invoice['lead_id'])) {
    set_flash('danger', 'Access Denied: You cannot view this invoice.');
    redirect('finance/invoices.php');
}

$pageTitle = 'Invoice ' . $invoice['invoice_number'];
$activePage = 'invoices';

// Fetch Line Items
$itemsStmt = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY id ASC");
$itemsStmt->execute([$invoiceId]);
$items = $itemsStmt->fetchAll();

// Fetch Payment Receipts for this invoice
$pmtStmt = $pdo->prepare("
    SELECT p.*, u.name as recorded_by_name
    FROM payments p
    LEFT JOIN users u ON p.recorded_by = u.id
    WHERE p.invoice_id = ?
    ORDER BY p.id DESC
");
$pmtStmt->execute([$invoiceId]);
$payments = $pmtStmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.35rem;">
      <h1 style="font-size:1.5rem; font-weight:700; color:var(--text-heading); margin:0;">
        <?= htmlspecialchars($invoice['invoice_number']) ?>
      </h1>
      <span class="badge <?= $invoice['payment_status'] === 'Paid' ? 'badge-success' : ($invoice['payment_status'] === 'Partially Paid' ? 'badge-warning' : 'badge-danger') ?>" style="font-weight:700; font-size:0.75rem;">
        <?= htmlspecialchars($invoice['payment_status']) ?>
      </span>
    </div>
    <p style="font-size:0.875rem; color:var(--text-muted); margin:0;">
      Issued to <strong><?= htmlspecialchars($invoice['full_name']) ?></strong> (<?= htmlspecialchars($invoice['lead_code']) ?>) &bull; <?= htmlspecialchars($invoice['branch_name'] ?? 'Branch') ?>
    </p>
  </div>
  <div style="display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap;">
    <a href="<?= base_url('leads/view.php?id=' . $invoice['lead_id']) ?>" class="btn btn-secondary btn-sm" title="Contact Details (Student Profile - User Manual Section 9.3.5.2)">
      <i class="fa-solid fa-address-card"></i> Contact Details
    </a>
    <a href="<?= base_url('finance/pdf_invoice.php?id=' . $invoice['id']) ?>" target="_blank" class="btn btn-outline-primary btn-sm" title="Print / Download PDF (User Manual Section 9.3.5.2)">
      <i class="fa-solid fa-file-pdf"></i> PDF
    </a>
    <?php if ($invoice['balance_due'] > 0 && can('invoices', 'edit')): ?>
      <button type="button" class="btn btn-primary btn-sm" onclick="openPaymentModal()">
        <i class="fa-solid fa-hand-holding-dollar"></i> Record Payment
      </button>
    <?php endif; ?>
    <a href="<?= base_url('leads/view.php?id=' . $invoice['lead_id'] . '&tab=invoices') ?>" class="btn btn-outline btn-sm">
      <i class="fa-solid fa-arrow-left"></i> Student Dossier
    </a>
  </div>
</div>

<!-- Main Printable-like Invoice Box -->
<div class="card" style="padding:2.5rem; margin-bottom:1.5rem; border:1px solid var(--border-color); box-shadow:0 10px 30px rgba(0,0,0,0.04);">
  <!-- Invoice Top Brand & Meta Header -->
  <div style="display:flex; justify-content:space-between; align-items:flex-start; border-bottom:2px solid var(--border-color); padding-bottom:1.5rem; margin-bottom:2rem; flex-wrap:wrap; gap:1.5rem;">
    <div>
      <div style="display:flex; align-items:center; gap:0.6rem; margin-bottom:0.5rem;">
        <div style="width:38px; height:38px; border-radius:10px; background:var(--primary); color:#fff; display:flex; align-items:center; justify-content:center; font-size:1.2rem;">
          <i class="fa-solid fa-graduation-cap"></i>
        </div>
        <div>
          <h2 style="font-size:1.3rem; font-weight:800; color:var(--text-heading); margin:0; line-height:1.1;">UniCRM Admissions Cloud</h2>
          <span style="font-size:0.75rem; color:var(--text-muted); font-weight:600;">Higher Education Consultancy Solutions</span>
        </div>
      </div>
      <div style="font-size:0.8rem; color:var(--text-muted); line-height:1.5;">
        <?= htmlspecialchars($invoice['branch_name'] ?? 'Consultancy Hub') ?><br>
        <?= htmlspecialchars($invoice['branch_address'] ?? 'Education Hub Office') ?><br>
        GSTIN: 32AABCE1234F1Z8 &bull; Phone: <?= htmlspecialchars($invoice['branch_phone'] ?? '+91 484 2900000') ?>
      </div>
    </div>

    <div style="text-align:right;">
      <h3 style="font-size:1.6rem; font-weight:800; color:var(--primary); margin:0 0 0.5rem 0; letter-spacing:0.5px;">TAX INVOICE</h3>
      <div style="font-family:monospace; font-size:1rem; font-weight:700; color:var(--text-heading); margin-bottom:0.35rem;">
        <?= htmlspecialchars($invoice['invoice_number']) ?>
      </div>
      <div style="font-size:0.825rem; color:var(--text-muted); line-height:1.4;">
        Issue Date: <strong><?= date('d M Y', strtotime($invoice['issue_date'])) ?></strong><br>
        Due Date: <strong><?= date('d M Y', strtotime($invoice['due_date'])) ?></strong>
      </div>
    </div>
  </div>

  <!-- Bill To Section -->
  <div style="display:grid; grid-template-columns:1fr 1fr; gap:2rem; margin-bottom:2rem;">
    <div>
      <span style="font-size:0.75rem; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px; display:block; margin-bottom:4px;">BILL TO:</span>
      <h4 style="font-size:1.05rem; font-weight:800; color:var(--text-heading); margin:0 0 4px 0;">
        <?= htmlspecialchars($invoice['full_name']) ?>
      </h4>
      <div style="font-size:0.825rem; color:var(--text-muted); line-height:1.5;">
        Student ID: <strong><?= htmlspecialchars($invoice['lead_code']) ?></strong><br>
        Contact: <?= htmlspecialchars($invoice['phone']) ?><br>
        <?= htmlspecialchars($invoice['email'] ?: '') ?><br>
        <?= htmlspecialchars($invoice['city'] ?? '') ?>, <?= htmlspecialchars($invoice['state'] ?? '') ?>
      </div>
    </div>

    <div style="background:var(--bg-subtle); padding:1rem; border-radius:8px; font-size:0.825rem; line-height:1.5;">
      <span style="font-size:0.72rem; font-weight:700; color:var(--text-muted); text-transform:uppercase; display:block; margin-bottom:2px;">PAYMENT REMITTANCE INSTRUCTIONS:</span>
      <div style="font-weight:600; color:var(--text-heading); white-space:pre-line;">
        <?= htmlspecialchars($invoice['bank_details'] ?: 'Bank details on file.') ?>
      </div>
    </div>
  </div>

  <!-- Itemized Line Items Table -->
  <div class="table-responsive" style="margin-bottom:2rem;">
    <table class="data-table" style="border:1px solid var(--border-color);">
      <thead style="background:var(--bg-subtle);">
        <tr>
          <th>#</th>
          <th>Service Description</th>
          <th style="text-align:center;">Qty</th>
          <th style="text-align:right;">Unit Price</th>
          <th style="text-align:right;">Line Total</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $idx => $it): ?>
          <tr>
            <td style="font-weight:600; color:var(--text-muted); width:40px;"><?= $idx + 1 ?></td>
            <td style="font-weight:600; color:var(--text-heading);"><?= htmlspecialchars($it['description']) ?></td>
            <td style="text-align:center;"><?= (int)$it['quantity'] ?></td>
            <td style="text-align:right; font-family:monospace;"><?= format_currency($it['unit_price']) ?></td>
            <td style="text-align:right; font-family:monospace; font-weight:700;"><?= format_currency($it['line_total']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Subtotal & Grand Total Calculations -->
  <div style="display:flex; justify-content:flex-end; margin-bottom:2rem;">
    <div style="width:320px; display:flex; flex-direction:column; gap:0.5rem; font-size:0.875rem;">
      <div style="display:flex; justify-content:space-between;">
        <span style="color:var(--text-muted);">Subtotal:</span>
        <strong style="font-family:monospace;"><?= format_currency($invoice['subtotal']) ?></strong>
      </div>

      <?php if ($invoice['discount_amount'] > 0): ?>
        <div style="display:flex; justify-content:space-between; color:#15803d;">
          <span>Discount:</span>
          <strong style="font-family:monospace;">- <?= format_currency($invoice['discount_amount']) ?></strong>
        </div>
      <?php endif; ?>

      <div style="display:flex; justify-content:space-between;">
        <span style="color:var(--text-muted);">GST / Tax (<?= (float)$invoice['tax_percentage'] ?>%):</span>
        <strong style="font-family:monospace;"><?= format_currency($invoice['tax_amount']) ?></strong>
      </div>

      <div style="border-top:2px solid var(--border-color); padding-top:0.6rem; display:flex; justify-content:space-between; font-size:1.15rem; color:var(--text-heading);">
        <span style="font-weight:800;">Grand Total:</span>
        <strong style="font-family:monospace; color:var(--primary); font-weight:800;"><?= format_currency($invoice['grand_total']) ?></strong>
      </div>

      <div style="display:flex; justify-content:space-between; color:#15803d; font-size:0.9rem;">
        <span>Amount Paid:</span>
        <strong style="font-family:monospace;"><?= format_currency($invoice['paid_amount']) ?></strong>
      </div>

      <div style="background:#fee2e2; padding:0.5rem 0.75rem; border-radius:6px; display:flex; justify-content:space-between; color:#b91c1c; font-size:1rem; font-weight:800;">
        <span>Balance Due:</span>
        <span style="font-family:monospace;"><?= format_currency($invoice['balance_due']) ?></span>
      </div>
    </div>
  </div>

  <!-- Footer Notes -->
  <div style="border-top:1px solid var(--border-color); padding-top:1rem; font-size:0.75rem; color:var(--text-muted); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
    <div>
      <strong>Payment Terms:</strong> <?= htmlspecialchars($invoice['payment_terms'] ?: 'Due upon receipt.') ?><br>
      This is a computer-generated tax invoice issued by UniCRM Admissions Cloud.
    </div>
    <div style="text-align:right;">
      Authorized Signatory &bull; <strong>Edroots Consultancy Admissions</strong>
    </div>
  </div>
</div>

<!-- Payments Ledger for this Invoice -->
<div class="card" style="padding:1.5rem; margin-bottom:1.5rem;">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
    <h3 style="font-size:1.05rem; font-weight:700; color:var(--text-heading); margin:0;">
      <i class="fa-solid fa-receipt" style="color:var(--primary); margin-right:6px;"></i> Payment Installment Receipts (<?= count($payments) ?>)
    </h3>
    <?php if ($invoice['balance_due'] > 0 && can('invoices', 'edit')): ?>
      <button type="button" class="btn btn-primary btn-sm" onclick="openPaymentModal()">
        <i class="fa-solid fa-plus"></i> Record Installment
      </button>
    <?php endif; ?>
  </div>

  <?php if (empty($payments)): ?>
    <p style="color:var(--text-muted); font-size:0.85rem; margin:0;">No payments recorded against this invoice yet.</p>
  <?php else: ?>
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>Receipt #</th>
            <th>Date Paid</th>
            <th>Payment Method</th>
            <th>Transaction Ref</th>
            <th>Amount Paid</th>
            <th>Recorded By</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($payments as $p): ?>
            <tr>
              <td style="font-family:monospace; font-weight:700;"><?= htmlspecialchars($p['receipt_number']) ?></td>
              <td style="font-size:0.85rem;"><?= date('d M Y', strtotime($p['payment_date'])) ?></td>
              <td><span class="badge badge-secondary"><?= htmlspecialchars($p['payment_method']) ?></span></td>
              <td style="font-family:monospace; font-size:0.8rem;"><?= htmlspecialchars($p['transaction_reference'] ?: '—') ?></td>
              <td style="font-weight:700; color:#15803d;"><?= format_currency($p['amount']) ?></td>
              <td style="font-size:0.75rem; color:var(--text-muted);"><?= htmlspecialchars($p['recorded_by_name'] ?? 'Staff') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<!-- Modal to Record Payment Installment -->
<div class="modal fade" id="paymentModal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(12,36,60,0.5); align-items:center; justify-content:center;">
  <div class="modal-dialog" style="background:#fff; border-radius:14px; max-width:480px; width:92%; margin:auto; box-shadow:0 20px 50px rgba(0,0,0,0.2); overflow:hidden;">
    <form method="POST" action="<?= base_url('finance/record_payment.php') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="invoice_id" value="<?= $invoice['id'] ?>">

      <div class="modal-header" style="padding:1.25rem 1.5rem; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
        <h3 style="font-size:1.15rem; font-weight:700; margin:0; color:var(--text-heading);">
          <i class="fa-solid fa-hand-holding-dollar" style="color:var(--primary); margin-right:8px;"></i> Record Payment Installment
        </h3>
        <button type="button" onclick="closePaymentModal()" style="border:none; background:none; font-size:1.4rem; cursor:pointer; color:var(--text-muted);">&times;</button>
      </div>

      <div class="modal-body" style="padding:1.5rem;">
        <div style="background:var(--bg-subtle); border-radius:8px; padding:0.85rem; margin-bottom:1.25rem; font-size:0.85rem; display:flex; justify-content:space-between;">
          <span>Outstanding Balance:</span>
          <strong style="color:var(--danger);"><?= format_currency($invoice['balance_due']) ?></strong>
        </div>

        <div class="form-group" style="margin-bottom:1rem;">
          <label class="form-label">Payment Amount (₹) <span style="color:red;">*</span></label>
          <input type="number" step="0.01" name="amount" class="form-control" value="<?= (float)$invoice['balance_due'] ?>" max="<?= (float)$invoice['balance_due'] ?>" min="1" required>
        </div>

        <div class="form-group" style="margin-bottom:1rem;">
          <label class="form-label">Payment Method <span style="color:red;">*</span></label>
          <select name="payment_method" class="form-control" required>
            <option value="Bank Transfer">Bank Wire Transfer (NEFT/RTGS)</option>
            <option value="UPI / GPay">UPI / GPay / PhonePe</option>
            <option value="Cheque">Cheque</option>
            <option value="Credit / Debit Card">Credit / Debit Card</option>
            <option value="Cash">Cash Receipt</option>
          </select>
        </div>

        <div class="form-group" style="margin-bottom:1rem;">
          <label class="form-label">Payment Date <span style="color:red;">*</span></label>
          <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
        </div>

        <div class="form-group" style="margin-bottom:1rem;">
          <label class="form-label">Transaction / UTR Reference #</label>
          <input type="text" name="transaction_reference" class="form-control" placeholder="e.g. UTR123456789">
        </div>

        <div class="form-group" style="margin-bottom:0.5rem;">
          <label class="form-label">Receipt Note</label>
          <textarea name="notes" class="form-control" rows="2" placeholder="First installment for UK visa counseling fees..."></textarea>
        </div>
      </div>

      <div class="modal-footer" style="padding:1rem 1.5rem; border-top:1px solid var(--border-color); display:flex; justify-content:flex-end; gap:0.5rem;">
        <button type="button" onclick="closePaymentModal()" class="btn btn-secondary btn-sm">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm">
          <i class="fa-solid fa-check"></i> Save & Issue Receipt
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function openPaymentModal() {
  document.getElementById('paymentModal').style.display = 'flex';
}
function closePaymentModal() {
  document.getElementById('paymentModal').style.display = 'none';
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
