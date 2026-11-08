<?php
/**
 * Generate Service Invoice (Multi-line Item Billing)
 * University-Level Education Consultancy CRM (EdrootsCRM)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_permission('invoices', 'add');

$currentUser = current_user();
$pageTitle = 'Generate Service Invoice';
$activePage = 'create_invoice';

$preselectedLeadId = isset($_GET['lead_id']) ? (int)$_GET['lead_id'] : 0;
$scopeClause = get_crm_scope_clause('l', 'assigned_counselor_id', 'branch_id');

// Fetch leads available for invoicing
$leadsStmt = $pdo->query("
    SELECT l.id, l.lead_code, l.full_name, l.phone, b.name as branch_name
    FROM leads l
    LEFT JOIN branches b ON l.branch_id = b.id
    WHERE l.is_archived = 0 AND {$scopeClause}
    ORDER BY l.full_name ASC
");
$leads = $leadsStmt->fetchAll();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? '');

    $leadId = (int)($_POST['lead_id'] ?? 0);
    $issueDate = trim($_POST['issue_date'] ?? date('Y-m-d'));
    $dueDate = trim($_POST['due_date'] ?? date('Y-m-d', strtotime('+15 days')));
    $discountAmount = max(0, (float)($_POST['discount_amount'] ?? 0));
    $taxPercent = max(0, (float)($_POST['tax_percentage'] ?? 18));
    $paymentTerms = trim($_POST['payment_terms'] ?? 'Due within 15 days of issue.');
    $bankDetails = trim($_POST['bank_details'] ?? 'HDFC Bank - A/C: 50200012345678, IFSC: HDFC0000123');
    $notes = trim($_POST['notes'] ?? '');

    $itemDescriptions = $_POST['item_description'] ?? [];
    $itemQuantities = $_POST['item_quantity'] ?? [];
    $itemPrices = $_POST['item_price'] ?? [];

    if ($leadId <= 0 || !can_access_lead($leadId)) {
        set_flash('danger', 'Please choose a valid accessible student lead.');
    } elseif (empty($itemDescriptions)) {
        set_flash('danger', 'Please provide at least one billable service line item.');
    } else {
        // Compute totals
        $subtotal = 0;
        $validItems = [];
        for ($i = 0; $i < count($itemDescriptions); $i++) {
            $desc = trim($itemDescriptions[$i] ?? '');
            $qty = max(1, (int)($itemQuantities[$i] ?? 1));
            $price = max(0, (float)($itemPrices[$i] ?? 0));
            if (!empty($desc) && $price > 0) {
                $lineTotal = $qty * $price;
                $subtotal += $lineTotal;
                $validItems[] = [
                    'description' => $desc,
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'line_total' => $lineTotal
                ];
            }
        }

        if (empty($validItems)) {
            set_flash('danger', 'Please enter valid service line items with descriptions and prices.');
        } else {
            $taxAmount = round(($subtotal - $discountAmount) * ($taxPercent / 100), 2);
            $grandTotal = round(($subtotal - $discountAmount) + $taxAmount, 2);

            // Generate Invoice Number (e.g., INV-2026-0045)
            $invYear = date('Y');
            $cntStmt = $pdo->query("SELECT COUNT(*) FROM invoices");
            $seq = (int)$cntStmt->fetchColumn() + 1;
            $invoiceNumber = sprintf("INV-%s-%04d", $invYear, $seq);

            try {
                $pdo->beginTransaction();

                // 1. Insert Invoice
                $stmtInv = $pdo->prepare("
                    INSERT INTO invoices (
                        invoice_number, lead_id, branch_id, issue_date, due_date,
                        subtotal, discount_amount, tax_percentage, tax_amount, grand_total,
                        paid_amount, balance_due, payment_status, payment_terms, bank_details,
                        notes, created_by
                    ) VALUES (
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?,
                        0.00, ?, 'Unpaid', ?, ?,
                        ?, ?
                    )
                ");

                // Get branch of this lead
                $lbStmt = $pdo->prepare("SELECT branch_id, full_name, lead_code FROM leads WHERE id = ?");
                $lbStmt->execute([$leadId]);
                $leadMeta = $lbStmt->fetch();

                $stmtInv->execute([
                    $invoiceNumber, $leadId, $leadMeta['branch_id'], $issueDate, $dueDate,
                    $subtotal, $discountAmount, $taxPercent, $taxAmount, $grandTotal,
                    $grandTotal, $paymentTerms, $bankDetails,
                    $notes, $currentUser['id']
                ]);
                $invoiceId = $pdo->lastInsertId();

                // 2. Insert Line Items
                $stmtItem = $pdo->prepare("
                    INSERT INTO invoice_items (invoice_id, description, quantity, unit_price, line_total)
                    VALUES (?, ?, ?, ?, ?)
                ");
                foreach ($validItems as $vi) {
                    $stmtItem->execute([$invoiceId, $vi['description'], $vi['quantity'], $vi['unit_price'], $vi['line_total']]);
                }

                // 3. Formally Classify Student as Customer (is_customer = 1)
                $stmtUpdLead = $pdo->prepare("
                    UPDATE leads 
                    SET is_customer = 1,
                        invoiced_total = invoiced_total + ?,
                        balance_total = balance_total + ?
                    WHERE id = ?
                ");
                $stmtUpdLead->execute([$grandTotal, $grandTotal, $leadId]);

                // 4. Log Activity
                log_activity(
                    $currentUser['id'],
                    $leadId,
                    'Invoice Created',
                    "Generated invoice {$invoiceNumber} for " . format_currency($grandTotal) . ". Student officially classified as Customer."
                );

                $pdo->commit();
                set_flash('success', "Invoice <strong>{$invoiceNumber}</strong> generated successfully! Student <strong>" . htmlspecialchars($leadMeta['full_name']) . "</strong> is now officially classified as a Customer.");
                redirect('finance/view_invoice.php?id=' . $invoiceId);
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                set_flash('danger', 'Error creating invoice: ' . htmlspecialchars($e->getMessage()));
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <h1 style="font-size:1.5rem; font-weight:700; color:var(--text-heading); margin:0 0 0.35rem 0;">Generate Service Invoice</h1>
    <p style="font-size:0.875rem; color:var(--text-muted); margin:0;">
      Create an itemized consultancy fee invoice with customizable line items, tax percentage, and automated customer status activation.
    </p>
  </div>
  <div>
    <a href="<?= base_url('finance/invoices.php') ?>" class="btn btn-secondary btn-sm">
      <i class="fa-solid fa-arrow-left"></i> Back to Invoices
    </a>
  </div>
</div>

<form method="POST" action="<?= base_url('finance/create_invoice.php') ?>" id="invoiceForm">
  <?= csrf_field() ?>

  <!-- Basic Invoice Details -->
  <div class="card" style="margin-bottom:1.5rem; padding:1.5rem;">
    <h3 style="font-size:1.1rem; font-weight:700; color:var(--text-heading); margin:0 0 1.25rem 0;">
      <i class="fa-solid fa-circle-info" style="color:var(--primary); margin-right:6px;"></i> Invoice Header & Client Selection
    </h3>

    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:1.25rem;">
      <div class="form-group">
        <label class="form-label">Student Client <span style="color:red;">*</span></label>
        <select name="lead_id" class="form-control" required>
          <option value="">Select Student...</option>
          <?php foreach ($leads as $ld): ?>
            <option value="<?= $ld['id'] ?>" <?= ($preselectedLeadId === (int)$ld['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($ld['full_name']) ?> (<?= htmlspecialchars($ld['lead_code']) ?>) — <?= htmlspecialchars($ld['branch_name'] ?? 'Branch') ?>
            </option>
          <?php endforeach; ?>
        </select>
        <small style="color:var(--text-muted); font-size:0.75rem;">Generating an invoice grants official Customer status to this student.</small>
      </div>

      <div class="form-group">
        <label class="form-label">Issue Date <span style="color:red;">*</span></label>
        <input type="date" name="issue_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
      </div>

      <div class="form-group">
        <label class="form-label">Payment Due Date <span style="color:red;">*</span></label>
        <input type="date" name="due_date" class="form-control" value="<?= date('Y-m-d', strtotime('+15 days')) ?>" required>
      </div>
    </div>
  </div>

  <!-- Dynamic Multi-Line Items Table -->
  <div class="card" style="margin-bottom:1.5rem; padding:1.5rem;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.25rem;">
      <h3 style="font-size:1.1rem; font-weight:700; color:var(--text-heading); margin:0;">
        <i class="fa-solid fa-list-ol" style="color:var(--primary); margin-right:6px;"></i> Billable Service Line Items
      </h3>
      <button type="button" class="btn btn-outline btn-sm" onclick="addItemRow()">
        <i class="fa-solid fa-plus"></i> Add Item Row
      </button>
    </div>

    <div class="table-responsive">
      <table class="data-table" id="itemsTable">
        <thead>
          <tr>
            <th style="width:50%;">Service Description</th>
            <th style="width:15%;">Quantity</th>
            <th style="width:15%;">Unit Price (₹)</th>
            <th style="width:15%;">Line Total (₹)</th>
            <th style="width:5%; text-align:right;"></th>
          </tr>
        </thead>
        <tbody id="itemsTbody">
          <tr>
            <td>
              <input type="text" name="item_description[]" class="form-control" placeholder="e.g. University Admissions Consultation & Processing" required value="University Admissions Consultation & Processing">
            </td>
            <td>
              <input type="number" name="item_quantity[]" class="form-control item-qty" value="1" min="1" required oninput="calculateInvoice()">
            </td>
            <td>
              <input type="number" step="0.01" name="item_price[]" class="form-control item-price" value="25000" min="0" required oninput="calculateInvoice()">
            </td>
            <td>
              <input type="text" class="form-control item-total" value="₹25,000.00" disabled style="background:#f8fafc; font-weight:700;">
            </td>
            <td style="text-align:right;">
              <button type="button" class="btn btn-ghost btn-sm" onclick="removeItemRow(this)" style="color:var(--danger);"><i class="fa-solid fa-trash"></i></button>
            </td>
          </tr>
          <tr>
            <td>
              <input type="text" name="item_description[]" class="form-control" placeholder="e.g. Visa Filing & Financial Dossier Review" required value="Visa Filing & Financial Dossier Review">
            </td>
            <td>
              <input type="number" name="item_quantity[]" class="form-control item-qty" value="1" min="1" required oninput="calculateInvoice()">
            </td>
            <td>
              <input type="number" step="0.01" name="item_price[]" class="form-control item-price" value="15000" min="0" required oninput="calculateInvoice()">
            </td>
            <td>
              <input type="text" class="form-control item-total" value="₹15,000.00" disabled style="background:#f8fafc; font-weight:700;">
            </td>
            <td style="text-align:right;">
              <button type="button" class="btn btn-ghost btn-sm" onclick="removeItemRow(this)" style="color:var(--danger);"><i class="fa-solid fa-trash"></i></button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Calculations Box -->
    <div style="display:flex; justify-content:flex-end; margin-top:1.5rem;">
      <div style="width:340px; display:flex; flex-direction:column; gap:0.75rem; font-size:0.9rem;">
        <div style="display:flex; justify-content:space-between; align-items:center;">
          <span style="color:var(--text-muted);">Subtotal:</span>
          <strong id="display_subtotal" style="font-size:1.05rem;">₹40,000.00</strong>
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center;">
          <span style="color:var(--text-muted);">Discount (₹):</span>
          <input type="number" step="0.01" name="discount_amount" id="discount_amount" class="form-control" value="0.00" style="width:120px; text-align:right;" oninput="calculateInvoice()">
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center;">
          <span style="color:var(--text-muted);">GST / Tax (%):</span>
          <input type="number" step="0.5" name="tax_percentage" id="tax_percentage" class="form-control" value="18" style="width:120px; text-align:right;" oninput="calculateInvoice()">
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center;">
          <span style="color:var(--text-muted);">Tax Amount:</span>
          <strong id="display_tax">₹7,200.00</strong>
        </div>

        <div style="border-top:2px solid var(--border-color); padding-top:0.75rem; display:flex; justify-content:space-between; align-items:center;">
          <span style="font-weight:800; font-size:1.1rem; color:var(--text-heading);">Grand Total:</span>
          <strong id="display_grand_total" style="font-weight:800; font-size:1.3rem; color:var(--primary);">₹47,200.00</strong>
        </div>
      </div>
    </div>
  </div>

  <!-- Remittance Instructions & Terms -->
  <div class="card" style="margin-bottom:1.5rem; padding:1.5rem;">
    <h3 style="font-size:1.1rem; font-weight:700; color:var(--text-heading); margin:0 0 1.25rem 0;">
      <i class="fa-solid fa-building-columns" style="color:var(--primary); margin-right:6px;"></i> Remittance Instructions & Terms
    </h3>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.25rem;">
      <div class="form-group">
        <label class="form-label">Payment Terms</label>
        <input type="text" name="payment_terms" class="form-control" value="Due within 15 days of issue. Split installment accepted.">
      </div>
      <div class="form-group">
        <label class="form-label">Bank Remittance Wire Details</label>
        <textarea name="bank_details" class="form-control" rows="2">HDFC Bank - Edroots Education Consultancy Ltd.
Account: 50200088991122 | IFSC: HDFC0000240 | Kochi Branch</textarea>
      </div>
    </div>
  </div>

  <div style="display:flex; justify-content:flex-end; gap:0.75rem; margin-bottom:2rem;">
    <a href="<?= base_url('finance/invoices.php') ?>" class="btn btn-secondary">Cancel</a>
    <button type="submit" class="btn btn-primary btn-lg">
      <i class="fa-solid fa-file-invoice-dollar"></i> Issue Invoice & Activate Customer
    </button>
  </div>
</form>

<script>
function addItemRow() {
  const tbody = document.getElementById('itemsTbody');
  const tr = document.createElement('tr');
  tr.innerHTML = `
    <td>
      <input type="text" name="item_description[]" class="form-control" placeholder="e.g. CAS Letter Issuance & University Liaison" required>
    </td>
    <td>
      <input type="number" name="item_quantity[]" class="form-control item-qty" value="1" min="1" required oninput="calculateInvoice()">
    </td>
    <td>
      <input type="number" step="0.01" name="item_price[]" class="form-control item-price" value="10000" min="0" required oninput="calculateInvoice()">
    </td>
    <td>
      <input type="text" class="form-control item-total" value="₹10,000.00" disabled style="background:#f8fafc; font-weight:700;">
    </td>
    <td style="text-align:right;">
      <button type="button" class="btn btn-ghost btn-sm" onclick="removeItemRow(this)" style="color:var(--danger);"><i class="fa-solid fa-trash"></i></button>
    </td>
  `;
  tbody.appendChild(tr);
  calculateInvoice();
}

function removeItemRow(btn) {
  const tbody = document.getElementById('itemsTbody');
  if (tbody.querySelectorAll('tr').length <= 1) {
    alert('At least one line item is required.');
    return;
  }
  btn.closest('tr').remove();
  calculateInvoice();
}

function calculateInvoice() {
  let subtotal = 0;
  const rows = document.querySelectorAll('#itemsTbody tr');
  rows.forEach(r => {
    const qty = parseFloat(r.querySelector('.item-qty').value) || 0;
    const price = parseFloat(r.querySelector('.item-price').value) || 0;
    const total = qty * price;
    subtotal += total;
    r.querySelector('.item-total').value = '₹' + total.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
  });

  const discount = parseFloat(document.getElementById('discount_amount').value) || 0;
  const taxPct = parseFloat(document.getElementById('tax_percentage').value) || 0;

  const taxable = Math.max(0, subtotal - discount);
  const tax = taxable * (taxPct / 100);
  const grandTotal = taxable + tax;

  document.getElementById('display_subtotal').innerText = '₹' + subtotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
  document.getElementById('display_tax').innerText = '₹' + tax.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
  document.getElementById('display_grand_total').innerText = '₹' + grandTotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

document.addEventListener('DOMContentLoaded', calculateInvoice);
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
