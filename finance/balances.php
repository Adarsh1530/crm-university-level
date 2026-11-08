<?php
/**
 * Outstanding Balances & Aging Report
 * University-Level Education Consultancy CRM (EdrootsCRM)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_permission('invoices', 'view');

$currentUser = current_user();
$pageTitle = 'Outstanding Balances';
$activePage = 'balances';

$scopeClause = get_crm_scope_clause('l', 'assigned_counselor_id', 'branch_id');
$branchFilter = isset($_GET['branch_id']) ? (int)$_GET['branch_id'] : 0;

$where = ["l.balance_total > 0", "l.is_archived = 0", $scopeClause];
$params = [];

if ($branchFilter > 0 && is_super_admin()) {
    $where[] = "l.branch_id = ?";
    $params[] = $branchFilter;
}

$whereSql = implode(' AND ', $where);

$branches = $pdo->query("SELECT id, name, city FROM branches WHERE status = 'Active' ORDER BY name ASC")->fetchAll();

$totalReceivable = (float)$pdo->query("
    SELECT COALESCE(SUM(l.balance_total), 0)
    FROM leads l
    WHERE l.balance_total > 0 AND l.is_archived = 0 AND {$scopeClause}
")->fetchColumn();

$sql = "
    SELECT l.*, b.name as branch_name, u.name as counselor_name
    FROM leads l
    LEFT JOIN branches b ON l.branch_id = b.id
    LEFT JOIN users u ON l.assigned_counselor_id = u.id
    WHERE {$whereSql}
    ORDER BY l.balance_total DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$debtors = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <h1 style="font-size:1.5rem; font-weight:700; color:var(--text-heading); margin:0 0 0.35rem 0;">Outstanding Balances</h1>
    <p style="font-size:0.875rem; color:var(--text-muted); margin:0;">
      Clients with unpaid fee balances, split installments pending collection, and aging receivables.
    </p>
  </div>
  <div>
    <span class="badge badge-danger" style="font-size:0.95rem; padding:0.5rem 1rem;">
      <i class="fa-solid fa-scale-unbalanced"></i> Total Outstanding: <strong><?= format_currency($totalReceivable) ?></strong>
    </span>
  </div>
</div>

<!-- Filters Bar -->
<?php if (is_super_admin()): ?>
  <div class="card" style="margin-bottom:1.5rem; padding:1.25rem;">
    <form method="GET" action="<?= base_url('finance/balances.php') ?>" style="display:flex; gap:0.75rem; align-items:center;">
      <div style="min-width:200px;">
        <select name="branch_id" class="form-control">
          <option value="0">All Branches</option>
          <?php foreach ($branches as $b): ?>
            <option value="<?= $b['id'] ?>" <?= $branchFilter === (int)$b['id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-filter"></i> Filter</button>
      <a href="<?= base_url('finance/balances.php') ?>" class="btn btn-secondary btn-sm"><i class="fa-solid fa-rotate-left"></i></a>
    </form>
  </div>
<?php endif; ?>

<!-- Balances Table -->
<div class="card">
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Client & Lead Code</th>
          <th>Phone & Contact</th>
          <th>Total Invoiced</th>
          <th>Total Paid</th>
          <th>Balance Due</th>
          <th>Admission Stage</th>
          <th>Branch & Counselor</th>
          <th style="text-align:right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($debtors)): ?>
          <tr>
            <td colspan="8" style="text-align:center; padding:3.5rem; color:var(--text-muted);">
              <i class="fa-solid fa-circle-check" style="font-size:2.5rem; color:var(--success); margin-bottom:0.75rem; display:block;"></i>
              <strong>Zero Outstanding Balances!</strong> All client fees have been fully collected.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($debtors as $d): ?>
            <tr>
              <td>
                <div style="font-family:monospace; font-size:0.75rem; font-weight:700; color:var(--primary);">
                  <?= htmlspecialchars($d['lead_code']) ?>
                </div>
                <a href="<?= base_url('customers/view.php?id=' . $d['id']) ?>" style="font-weight:700; color:var(--text-heading); text-decoration:none; font-size:0.92rem;">
                  <?= htmlspecialchars($d['full_name']) ?>
                </a>
              </td>
              <td>
                <div style="font-family:monospace; font-weight:600; color:var(--text-heading); font-size:0.85rem;">
                  <?= htmlspecialchars($d['phone']) ?>
                </div>
              </td>
              <td style="font-weight:700; font-size:0.9rem;"><?= format_currency($d['invoiced_total']) ?></td>
              <td style="font-weight:700; color:#15803d; font-size:0.9rem;"><?= format_currency($d['paid_total']) ?></td>
              <td style="font-weight:800; color:#b91c1c; font-size:0.95rem;"><?= format_currency($d['balance_total']) ?></td>
              <td>
                <span class="badge badge-primary" style="font-size:0.72rem; font-weight:700;">
                  <?= htmlspecialchars($d['stage']) ?>
                </span>
              </td>
              <td>
                <div style="font-size:0.82rem; font-weight:600;"><?= htmlspecialchars($d['counselor_name'] ?? 'Counselor') ?></div>
                <div style="font-size:0.72rem; color:var(--text-muted);"><?= htmlspecialchars($d['branch_name'] ?? 'Branch') ?></div>
              </td>
              <td style="text-align:right;">
                <a href="<?= base_url('customers/view.php?id=' . $d['id']) ?>" class="btn btn-secondary btn-sm">
                  Ledger &rarr;
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
