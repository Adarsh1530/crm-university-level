<?php
/**
 * Global Multi-Entity CRM Search Hub
 * University-Level Education Consultancy CRM (EdrootsCRM)
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/includes/auth.php';

require_login();

$pageTitle = 'Global Search';
$activePage = 'search';
$currentUser = current_user();

$q = trim($_GET['q'] ?? '');
$scopeClause = get_crm_scope_clause('l', 'assigned_counselor_id', 'branch_id');

$matchedLeads = [];
$matchedCustomers = [];
$matchedCollege = [];
$matchedInvoices = [];

if (!empty($q)) {
    $wild = "%{$q}%";

    // 1. Search Active Leads
    if (can('leads', 'view')) {
        $lStmt = $pdo->prepare("
            SELECT l.*, b.name as branch_name, u.name as counselor_name
            FROM leads l
            LEFT JOIN branches b ON l.branch_id = b.id
            LEFT JOIN users u ON l.assigned_counselor_id = u.id
            WHERE l.is_archived = 0 AND {$scopeClause}
              AND (l.full_name LIKE ? OR l.lead_code LIKE ? OR l.phone LIKE ? OR l.email LIKE ? OR l.preferred_country LIKE ?)
            ORDER BY l.id DESC
            LIMIT 20
        ");
        $lStmt->execute([$wild, $wild, $wild, $wild, $wild]);
        $matchedLeads = $lStmt->fetchAll();
    }

    // 2. Search College Data Pool
    if (can('college_data', 'view')) {
        $cStmt = $pdo->prepare("
            SELECT c.*, b.name as branch_name
            FROM college_data c
            LEFT JOIN branches b ON c.branch_id = b.id
            WHERE c.is_archived = 0
              AND (c.student_name LIKE ? OR c.phone LIKE ? OR c.email LIKE ? OR c.college_name LIKE ?)
            ORDER BY c.id DESC
            LIMIT 15
        ");
        $cStmt->execute([$wild, $wild, $wild, $wild]);
        $matchedCollege = $cStmt->fetchAll();
    }

    // 3. Search Invoices
    if (can('invoices', 'view')) {
        $iStmt = $pdo->prepare("
            SELECT i.*, l.full_name, l.lead_code
            FROM invoices i
            JOIN leads l ON i.lead_id = l.id
            WHERE {$scopeClause}
              AND (i.invoice_number LIKE ? OR l.full_name LIKE ? OR l.lead_code LIKE ?)
            ORDER BY i.id DESC
            LIMIT 15
        ");
        $iStmt->execute([$wild, $wild, $wild]);
        $matchedInvoices = $iStmt->fetchAll();
    }
}

$totalMatches = count($matchedLeads) + count($matchedCollege) + count($matchedInvoices);

include __DIR__ . '/includes/header.php';
?>

<div class="page-header" style="margin-bottom:1.5rem;">
  <h1 style="font-size:1.5rem; font-weight:700; color:var(--text-heading); margin:0 0 0.35rem 0;">
    Global Search Results
  </h1>
  <p style="font-size:0.875rem; color:var(--text-muted); margin:0;">
    <?php if (!empty($q)): ?>
      Showing matches for &ldquo;<strong><?= htmlspecialchars($q) ?></strong>&rdquo; (<?= $totalMatches ?> results found).
    <?php else: ?>
      Enter a query in the search bar above to search across leads, college data, invoices, and clients.
    <?php endif; ?>
  </p>
</div>

<!-- Search Input Form -->
<div class="card" style="margin-bottom:1.5rem; padding:1.25rem;">
  <form method="GET" action="<?= base_url('search.php') ?>" style="display:flex; gap:0.75rem;">
    <div style="flex:1; position:relative;">
      <input type="text" name="q" class="form-control" placeholder="Search by student name, lead code, phone, email, college..." value="<?= htmlspecialchars($q) ?>" style="padding-left:2.4rem;">
      <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-light);"></i>
    </div>
    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
  </form>
</div>

<?php if (!empty($q)): ?>
  <!-- Active Leads Matches -->
  <div class="card" style="margin-bottom:1.5rem; padding:1.5rem;">
    <h3 style="font-size:1.1rem; font-weight:700; color:var(--text-heading); margin:0 0 1rem 0;">
      <i class="fa-solid fa-user-graduate" style="color:var(--primary); margin-right:6px;"></i> Active Student Leads (<?= count($matchedLeads) ?>)
    </h3>
    <?php if (empty($matchedLeads)): ?>
      <p style="color:var(--text-muted); font-size:0.85rem; margin:0;">No active student leads found.</p>
    <?php else: ?>
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Lead Code & Name</th>
              <th>Contact Phone</th>
              <th>Stage</th>
              <th>Target Destination</th>
              <th>Branch & Counselor</th>
              <th style="text-align:right;">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($matchedLeads as $ml): ?>
              <tr>
                <td>
                  <a href="<?= base_url('leads/view.php?id=' . $ml['id']) ?>" style="font-weight:700; color:var(--text-heading); text-decoration:none;">
                    <?= htmlspecialchars($ml['full_name']) ?>
                  </a>
                  <div style="font-family:monospace; font-size:0.72rem; color:var(--primary);"><?= htmlspecialchars($ml['lead_code']) ?></div>
                </td>
                <td style="font-family:monospace;"><?= htmlspecialchars($ml['phone']) ?></td>
                <td><span class="badge badge-primary"><?= htmlspecialchars($ml['stage']) ?></span></td>
                <td><?= htmlspecialchars($ml['preferred_country']) ?></td>
                <td><?= htmlspecialchars($ml['branch_name'] ?? 'Branch') ?> • <?= htmlspecialchars($ml['counselor_name'] ?? 'Counselor') ?></td>
                <td style="text-align:right;">
                  <a href="<?= base_url('leads/view.php?id=' . $ml['id']) ?>" class="btn btn-secondary btn-sm">Dossier &rarr;</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <!-- College Data Pool Matches -->
  <?php if (can('college_data', 'view')): ?>
    <div class="card" style="margin-bottom:1.5rem; padding:1.5rem;">
      <h3 style="font-size:1.1rem; font-weight:700; color:var(--text-heading); margin:0 0 1rem 0;">
        <i class="fa-solid fa-database" style="color:var(--primary); margin-right:6px;"></i> Raw College Data Pool (<?= count($matchedCollege) ?>)
      </h3>
      <?php if (empty($matchedCollege)): ?>
        <p style="color:var(--text-muted); font-size:0.85rem; margin:0;">No raw college student pool records found.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>Student Name</th>
                <th>Phone</th>
                <th>College Name</th>
                <th>Degree</th>
                <th>Status</th>
                <th style="text-align:right;">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($matchedCollege as $mc): ?>
                <tr>
                  <td><strong><?= htmlspecialchars($mc['student_name']) ?></strong></td>
                  <td style="font-family:monospace;"><?= htmlspecialchars($mc['phone']) ?></td>
                  <td><?= htmlspecialchars($mc['college_name'] ?? 'N/A') ?></td>
                  <td><?= htmlspecialchars($mc['degree'] ?? 'General') ?></td>
                  <td>
                    <span class="badge <?= $mc['converted_to_lead'] ? 'badge-success' : 'badge-warning' ?>">
                      <?= $mc['converted_to_lead'] ? 'Converted' : 'Raw Pool' ?>
                    </span>
                  </td>
                  <td style="text-align:right;">
                    <?php if ($mc['converted_to_lead'] && !empty($mc['lead_id'])): ?>
                      <a href="<?= base_url('leads/view.php?id=' . $mc['lead_id']) ?>" class="btn btn-secondary btn-sm">View Lead &rarr;</a>
                    <?php else: ?>
                      <a href="<?= base_url('data/college_data.php?search=' . urlencode($mc['phone'])) ?>" class="btn btn-primary btn-sm">Convert &rarr;</a>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <!-- Invoices Matches -->
  <?php if (can('invoices', 'view')): ?>
    <div class="card" style="margin-bottom:1.5rem; padding:1.5rem;">
      <h3 style="font-size:1.1rem; font-weight:700; color:var(--text-heading); margin:0 0 1rem 0;">
        <i class="fa-solid fa-file-invoice-dollar" style="color:var(--primary); margin-right:6px;"></i> Invoices (<?= count($matchedInvoices) ?>)
      </h3>
      <?php if (empty($matchedInvoices)): ?>
        <p style="color:var(--text-muted); font-size:0.85rem; margin:0;">No invoices match this search.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>Invoice #</th>
                <th>Student Client</th>
                <th>Issue Date</th>
                <th>Grand Total</th>
                <th>Balance Due</th>
                <th>Status</th>
                <th style="text-align:right;">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($matchedInvoices as $mi): ?>
                <tr>
                  <td style="font-family:monospace; font-weight:700; color:var(--primary);"><?= htmlspecialchars($mi['invoice_number']) ?></td>
                  <td><strong><?= htmlspecialchars($mi['full_name']) ?></strong></td>
                  <td><?= date('d M Y', strtotime($mi['issue_date'])) ?></td>
                  <td style="font-weight:700;"><?= format_currency($mi['grand_total']) ?></td>
                  <td style="font-weight:700; color:#b91c1c;"><?= format_currency($mi['balance_due']) ?></td>
                  <td><span class="badge badge-primary"><?= htmlspecialchars($mi['payment_status']) ?></span></td>
                  <td style="text-align:right;">
                    <a href="<?= base_url('finance/view_invoice.php?id=' . $mi['id']) ?>" class="btn btn-secondary btn-sm">View &rarr;</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
