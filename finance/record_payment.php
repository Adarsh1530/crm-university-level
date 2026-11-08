<?php
/**
 * Record Payment Installment Handler
 * University-Level Education Consultancy CRM (EdrootsCRM)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_permission('invoices', 'edit');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('finance/invoices.php');
}

verify_csrf_token($_POST['csrf_token'] ?? '');

$invoiceId = (int)($_POST['invoice_id'] ?? 0);
$amount = (float)($_POST['amount'] ?? 0);
$paymentDate = trim($_POST['payment_date'] ?? date('Y-m-d'));
$method = trim($_POST['payment_method'] ?? 'Bank Transfer');
$txnRef = trim($_POST['transaction_reference'] ?? '');
$notes = trim($_POST['notes'] ?? '');
$currentUser = current_user();

if ($invoiceId <= 0 || $amount <= 0) {
    set_flash('danger', 'Please enter a valid payment amount greater than zero.');
    redirect("finance/view_invoice.php?id={$invoiceId}");
}

// Fetch invoice and lead
$stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ? LIMIT 1");
$stmt->execute([$invoiceId]);
$invoice = $stmt->fetch();

if (!$invoice) {
    set_flash('danger', 'Invoice not found.');
    redirect('finance/invoices.php');
}

if (!can_access_lead($invoice['lead_id'])) {
    set_flash('danger', 'Access Denied: You cannot record payments for this student.');
    redirect('finance/invoices.php');
}

$balanceDue = (float)$invoice['balance_due'];
if ($amount > ($balanceDue + 0.01)) {
    set_flash('danger', 'Payment amount cannot exceed the outstanding balance of ' . format_currency($balanceDue) . '.');
    redirect("finance/view_invoice.php?id={$invoiceId}");
}

// Generate Receipt Number (e.g. RCT-2026-0034)
$currYear = date('Y');
$cntStmt = $pdo->query("SELECT COUNT(*) FROM payments");
$seq = (int)$cntStmt->fetchColumn() + 1;
$receiptNumber = sprintf("RCT-%s-%04d", $currYear, $seq);

try {
    $pdo->beginTransaction();

    // 1. Insert payment record
    $insPmt = $pdo->prepare("
        INSERT INTO payments (
            receipt_number, invoice_id, lead_id, branch_id, amount,
            payment_date, payment_method, transaction_reference, notes, recorded_by
        ) VALUES (
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?
        )
    ");
    $insPmt->execute([
        $receiptNumber, $invoiceId, $invoice['lead_id'], $invoice['branch_id'], $amount,
        $paymentDate, $method, $txnRef, $notes, $currentUser['id']
    ]);

    // 2. Update Invoice balance & status
    $newPaidAmount = (float)$invoice['paid_amount'] + $amount;
    $newBalanceDue = max(0, (float)$invoice['grand_total'] - $newPaidAmount);
    $newStatus = ($newBalanceDue <= 0) ? 'Paid' : 'Partially Paid';

    $updInv = $pdo->prepare("
        UPDATE invoices 
        SET paid_amount = ?, balance_due = ?, payment_status = ?
        WHERE id = ?
    ");
    $updInv->execute([$newPaidAmount, $newBalanceDue, $newStatus, $invoiceId]);

    // 3. Update Lead balance totals
    $updLead = $pdo->prepare("
        UPDATE leads 
        SET paid_total = paid_total + ?,
            balance_total = GREATEST(0, balance_total - ?)
        WHERE id = ?
    ");
    $updLead->execute([$amount, $amount, $invoice['lead_id']]);

    // 4. Log Activity
    log_activity(
        $currentUser['id'],
        $invoice['lead_id'],
        'Payment Recorded',
        "Payment of " . format_currency($amount) . " recorded for invoice {$invoice['invoice_number']} via {$method}. Receipt: {$receiptNumber}."
    );

    $pdo->commit();
    set_flash('success', "Payment installment of <strong>" . format_currency($amount) . "</strong> recorded successfully! Receipt <strong>{$receiptNumber}</strong> issued.");
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    set_flash('danger', 'Error recording payment: ' . htmlspecialchars($e->getMessage()));
}

redirect("finance/view_invoice.php?id={$invoiceId}");
