<?php
/**
 * Print & PDF Export Layout for Invoices
 * Clean standalone printable document for client remittance
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_permission('invoices', 'view');

$invoiceId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($invoiceId <= 0) {
    die('Invalid invoice ID.');
}

$stmt = $pdo->prepare("
    SELECT i.*, l.full_name, l.lead_code, l.phone, l.email, l.city, l.state,
           b.name as branch_name, b.code as branch_code, b.address as branch_address, b.phone as branch_phone
    FROM invoices i
    JOIN leads l ON i.lead_id = l.id
    LEFT JOIN branches b ON i.branch_id = b.id
    WHERE i.id = ?
    LIMIT 1
");
$stmt->execute([$invoiceId]);
$invoice = $stmt->fetch();

if (!$invoice || !can_access_lead($invoice['lead_id'])) {
    die('Access Denied.');
}

$itemsStmt = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY id ASC");
$itemsStmt->execute([$invoiceId]);
$items = $itemsStmt->fetchAll();

$pmtStmt = $pdo->prepare("SELECT * FROM payments WHERE invoice_id = ? ORDER BY id ASC");
$pmtStmt->execute([$invoiceId]);
$payments = $pmtStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Invoice - <?= htmlspecialchars($invoice['invoice_number']) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Instrument Sans', -apple-system, BlinkMacSystemFont, sans-serif;
      color: #1e293b;
      background: #f1f5f9;
      margin: 0;
      padding: 2rem 1rem;
    }
    .print-actions {
      max-width: 800px;
      margin: 0 auto 1.5rem auto;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .btn-print {
      background: #7008E7;
      color: #fff;
      border: none;
      padding: 0.6rem 1.4rem;
      border-radius: 8px;
      font-size: 0.9rem;
      font-weight: 700;
      cursor: pointer;
    }
    .invoice-card {
      max-width: 800px;
      margin: 0 auto;
      background: #ffffff;
      padding: 3rem;
      border-radius: 12px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    }
    .invoice-header {
      display: flex;
      justify-content: space-between;
      border-bottom: 2px solid #e2e8f0;
      padding-bottom: 1.5rem;
      margin-bottom: 2rem;
    }
    .brand-title {
      font-size: 1.4rem;
      font-weight: 800;
      color: #0f172a;
      margin: 0;
    }
    .tax-title {
      font-size: 1.8rem;
      font-weight: 800;
      color: #7008E7;
      margin: 0;
      letter-spacing: 0.5px;
    }
    .details-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 2rem;
      margin-bottom: 2rem;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 2rem;
    }
    th {
      background: #f8fafc;
      padding: 10px 14px;
      text-align: left;
      font-size: 0.8rem;
      font-weight: 700;
      color: #475569;
      border-bottom: 2px solid #cbd5e1;
    }
    td {
      padding: 12px 14px;
      border-bottom: 1px solid #e2e8f0;
      font-size: 0.875rem;
    }
    .totals-box {
      width: 320px;
      margin-left: auto;
      margin-bottom: 2.5rem;
    }
    .totals-row {
      display: flex;
      justify-content: space-between;
      padding: 6px 0;
      font-size: 0.9rem;
    }
    .totals-row.grand {
      border-top: 2px solid #0f172a;
      padding-top: 8px;
      font-size: 1.2rem;
      font-weight: 800;
      color: #7008E7;
    }
    @media print {
      body {
        background: #fff;
        padding: 0;
      }
      .print-actions {
        display: none !important;
      }
      .invoice-card {
        box-shadow: none;
        padding: 0;
      }
    }
  </style>
</head>
<body>

<div class="print-actions">
  <a href="<?= base_url('finance/view_invoice.php?id=' . $invoice['id']) ?>" style="color:#64748b; text-decoration:none; font-size:0.875rem;">&larr; Back to CRM Invoice</a>
  <button type="button" class="btn-print" onclick="window.print()">Print / Save as PDF</button>
</div>

<div class="invoice-card">
  <div class="invoice-header">
    <div>
      <h1 class="brand-title">UniCRM Admissions Cloud</h1>
      <div style="font-size:0.8rem; color:#64748b; margin-top:4px; line-height:1.5;">
        Higher Education Consultancy Services<br>
        <?= htmlspecialchars($invoice['branch_name'] ?? 'Consultancy Hub') ?><br>
        GSTIN: 32AABCE1234F1Z8
      </div>
    </div>
    <div style="text-align:right;">
      <h2 class="tax-title">TAX INVOICE</h2>
      <div style="font-family:monospace; font-weight:700; font-size:1.1rem; color:#0f172a; margin-top:4px;">
        <?= htmlspecialchars($invoice['invoice_number']) ?>
      </div>
      <div style="font-size:0.825rem; color:#64748b; margin-top:4px;">
        Date: <?= date('d M Y', strtotime($invoice['issue_date'])) ?><br>
        Due: <?= date('d M Y', strtotime($invoice['due_date'])) ?>
      </div>
    </div>
  </div>

  <div class="details-grid">
    <div>
      <span style="font-size:0.75rem; font-weight:700; color:#64748b; text-transform:uppercase;">INVOICE TO:</span>
      <h3 style="font-size:1.1rem; font-weight:800; color:#0f172a; margin:4px 0;">
        <?= htmlspecialchars($invoice['full_name']) ?>
      </h3>
      <div style="font-size:0.85rem; color:#64748b; line-height:1.4;">
        Student ID: <?= htmlspecialchars($invoice['lead_code']) ?><br>
        Phone: <?= htmlspecialchars($invoice['phone']) ?><br>
        Email: <?= htmlspecialchars($invoice['email'] ?: 'N/A') ?>
      </div>
    </div>

    <div style="background:#f8fafc; border-radius:8px; padding:1rem; font-size:0.825rem; line-height:1.5;">
      <strong style="color:#0f172a; display:block; margin-bottom:4px;">REMITTANCE BANK DETAILS:</strong>
      <div style="color:#475569; white-space:pre-line;">
        <?= htmlspecialchars($invoice['bank_details'] ?: 'Bank details on file.') ?>
      </div>
    </div>
  </div>

  <table>
    <thead>
      <tr>
        <th style="width:40px;">#</th>
        <th>Service Description</th>
        <th style="text-align:center; width:60px;">Qty</th>
        <th style="text-align:right; width:120px;">Unit Price</th>
        <th style="text-align:right; width:130px;">Amount</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $idx => $it): ?>
        <tr>
          <td><?= $idx + 1 ?></td>
          <td style="font-weight:600; color:#0f172a;"><?= htmlspecialchars($it['description']) ?></td>
          <td style="text-align:center;"><?= (int)$it['quantity'] ?></td>
          <td style="text-align:right; font-family:monospace;"><?= format_currency($it['unit_price']) ?></td>
          <td style="text-align:right; font-family:monospace; font-weight:700;"><?= format_currency($it['line_total']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="totals-box">
    <div class="totals-row">
      <span style="color:#64748b;">Subtotal:</span>
      <strong style="font-family:monospace;"><?= format_currency($invoice['subtotal']) ?></strong>
    </div>
    <?php if ($invoice['discount_amount'] > 0): ?>
      <div class="totals-row" style="color:#15803d;">
        <span>Discount:</span>
        <strong style="font-family:monospace;">- <?= format_currency($invoice['discount_amount']) ?></strong>
      </div>
    <?php endif; ?>
    <div class="totals-row">
      <span style="color:#64748b;">GST / Tax (<?= (float)$invoice['tax_percentage'] ?>%):</span>
      <strong style="font-family:monospace;"><?= format_currency($invoice['tax_amount']) ?></strong>
    </div>
    <div class="totals-row grand">
      <span>Grand Total:</span>
      <span style="font-family:monospace;"><?= format_currency($invoice['grand_total']) ?></span>
    </div>
    <div class="totals-row" style="color:#15803d;">
      <span>Amount Paid:</span>
      <strong style="font-family:monospace;"><?= format_currency($invoice['paid_amount']) ?></strong>
    </div>
    <div class="totals-row" style="color:#b91c1c; font-weight:800; font-size:1.05rem;">
      <span>Balance Due:</span>
      <span style="font-family:monospace;"><?= format_currency($invoice['balance_due']) ?></span>
    </div>
  </div>

  <div style="border-top:1px solid #e2e8f0; padding-top:1.5rem; font-size:0.75rem; color:#64748b; display:flex; justify-content:space-between; align-items:center;">
    <div>
      Terms: <?= htmlspecialchars($invoice['payment_terms'] ?: 'Due upon receipt.') ?><br>
      Computer-generated tax invoice.
    </div>
    <div style="text-align:right;">
      <strong>Authorized Signatory</strong><br>
      Edroots Consultancy Admissions
    </div>
  </div>
</div>

</body>
</html>
