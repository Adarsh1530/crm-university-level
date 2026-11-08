<?php
/**
 * Reports & Performance Analytics Hub
 * University-Level Education Consultancy CRM (EdrootsCRM)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_permission('reports', 'view');

$currentUser = current_user();
$pageTitle = 'Reports & Performance Analytics';
$activePage = 'reports';

$activeReport = $_GET['report'] ?? 'leads'; // leads, branches, counselors, finance
$range = $_GET['range'] ?? 'month'; // week, month, fy, all

// Date range SQL calculation
$dateFilter = match($range) {
    'week' => "AND l.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)",
    'month' => "AND l.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)",
    'fy' => "AND l.created_at >= DATE_FORMAT(CURDATE() ,'%Y-04-01')",
    default => ""
};

$scopeClause = get_crm_scope_clause('l', 'assigned_counselor_id', 'branch_id');

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    if (!can('reports', 'export')) {
        set_flash('danger', 'Access Denied: Counselors cannot export institutional analytics reports.');
        redirect('reports/index.php');
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . ucfirst($activeReport) . '_Report_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');

    if ($activeReport === 'branches') {
        fputcsv($out, ['Branch Name', 'City', 'Active Counselors', 'Total Leads', 'Invoiced Customers', 'Conversion Rate', 'Collected Revenue', 'Outstanding Receivables']);
        $bRows = $pdo->query("
            SELECT b.name, b.city,
                   (SELECT COUNT(*) FROM users WHERE branch_id = b.id AND status = 'Active') as staff_count,
                   COUNT(l.id) as total_leads,
                   COUNT(CASE WHEN l.is_customer = 1 THEN 1 END) as total_customers,
                   ROUND((COUNT(CASE WHEN l.is_customer = 1 THEN 1 END) / NULLIF(COUNT(l.id), 0)) * 100, 1) as conv_rate,
                   COALESCE(SUM(l.paid_total), 0) as total_collected,
                   COALESCE(SUM(l.balance_total), 0) as total_balance
            FROM branches b
            LEFT JOIN leads l ON b.id = l.branch_id AND l.is_archived = 0
            GROUP BY b.id
            ORDER BY total_leads DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($bRows as $br) fputcsv($out, $br);

    } elseif ($activeReport === 'counselors') {
        fputcsv($out, ['Counselor Name', 'Branch', 'Active Caseload', 'Total Followups', 'Customers Converted', 'Conversion %', 'Revenue Collected']);
        $cRows = $pdo->query("
            SELECT u.name, b.name as branch,
                   COUNT(l.id) as caseload,
                   COALESCE(SUM(l.followup_count), 0) as total_followups,
                   COUNT(CASE WHEN l.is_customer = 1 THEN 1 END) as converted_customers,
                   ROUND((COUNT(CASE WHEN l.is_customer = 1 THEN 1 END) / NULLIF(COUNT(l.id), 0)) * 100, 1) as conv_rate,
                   COALESCE(SUM(l.paid_total), 0) as revenue
            FROM users u
            JOIN roles r ON u.role_id = r.id AND r.name = 'counselor'
            LEFT JOIN branches b ON u.branch_id = b.id
            LEFT JOIN leads l ON u.id = l.assigned_counselor_id AND l.is_archived = 0
            WHERE u.status = 'Active'
            GROUP BY u.id
            ORDER BY caseload DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cRows as $cr) fputcsv($out, $cr);

    } else {
        // Leads report export
        fputcsv($out, ['Channel Source', 'Total Leads', 'Enquiries', 'Customers', 'Enrolled', 'Yield Rate']);
        $sRows = $pdo->query("
            SELECT l.source,
                   COUNT(l.id) as total_leads,
                   COUNT(CASE WHEN l.stage = 'ENQUIRY' THEN 1 END) as enquiries,
                   COUNT(CASE WHEN l.is_customer = 1 THEN 1 END) as customers,
                   COUNT(CASE WHEN l.stage = 'ENROLLED' THEN 1 END) as enrolled,
                   ROUND((COUNT(CASE WHEN l.is_customer = 1 THEN 1 END) / NULLIF(COUNT(l.id), 0)) * 100, 1) as yield_rate
            FROM leads l
            WHERE l.is_archived = 0 AND {$scopeClause}
            GROUP BY l.source
            ORDER BY total_leads DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($sRows as $sr) fputcsv($out, $sr);
    }
    fclose($out);
    exit;
}

// 1. Lead Source Analytics
$sourceStats = $pdo->query("
    SELECT l.source,
           COUNT(l.id) as total_leads,
           COUNT(CASE WHEN l.is_customer = 1 THEN 1 END) as converted_customers,
           COUNT(CASE WHEN l.stage = 'ENROLLED' THEN 1 END) as enrolled_count,
           ROUND((COUNT(CASE WHEN l.is_customer = 1 THEN 1 END) / NULLIF(COUNT(l.id), 0)) * 100, 1) as conv_pct
    FROM leads l
    WHERE l.is_archived = 0 AND {$scopeClause} {$dateFilter}
    GROUP BY l.source
    ORDER BY total_leads DESC
")->fetchAll();

// 2. Branch Performance
$branchStats = $pdo->query("
    SELECT b.name as branch_name, b.city,
           (SELECT COUNT(*) FROM users WHERE branch_id = b.id AND status = 'Active') as counselors_count,
           COUNT(l.id) as total_leads,
           COUNT(CASE WHEN l.is_customer = 1 THEN 1 END) as customers_count,
           COUNT(CASE WHEN l.stage = 'ENROLLED' THEN 1 END) as enrolled_count,
           COALESCE(SUM(l.paid_total), 0) as revenue_collected,
           COALESCE(SUM(l.balance_total), 0) as balance_due
    FROM branches b
    LEFT JOIN leads l ON b.id = l.branch_id AND l.is_archived = 0 {$dateFilter}
    GROUP BY b.id
    ORDER BY total_leads DESC
")->fetchAll();

// 3. Counselor Caseload & Performance
$counselorStats = $pdo->query("
    SELECT u.name as counselor_name, b.name as branch_name,
           COUNT(l.id) as active_caseload,
           COALESCE(SUM(l.followup_count), 0) as total_followups,
           COUNT(CASE WHEN l.is_customer = 1 THEN 1 END) as customers_converted,
           COUNT(CASE WHEN l.stage = 'ENROLLED' THEN 1 END) as enrolled_students,
           ROUND((COUNT(CASE WHEN l.is_customer = 1 THEN 1 END) / NULLIF(COUNT(l.id), 0)) * 100, 1) as conversion_rate,
           COALESCE(SUM(l.paid_total), 0) as total_collected
    FROM users u
    JOIN roles r ON u.role_id = r.id AND r.name = 'counselor'
    LEFT JOIN branches b ON u.branch_id = b.id
    LEFT JOIN leads l ON u.id = l.assigned_counselor_id AND l.is_archived = 0 {$dateFilter}
    WHERE u.status = 'Active' " . (is_branch_manager() ? ("AND u.branch_id = " . (int)$currentUser['branch_id']) : "") . "
    GROUP BY u.id
    ORDER BY active_caseload DESC
")->fetchAll();

// Chart JSON Data
$chartLabels = array_column($sourceStats, 'source');
$chartLeadCounts = array_column($sourceStats, 'total_leads');
$chartCustomerCounts = array_column($sourceStats, 'converted_customers');

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <h1 style="font-size:1.5rem; font-weight:700; color:var(--text-heading); margin:0 0 0.35rem 0;">Reports & Intelligence Analytics</h1>
    <p style="font-size:0.875rem; color:var(--text-muted); margin:0;">
      Cross-branch yield metrics, marketing source conversions, counselor caseload ratings, and financial yields.
    </p>
  </div>
  <div style="display:flex; gap:0.5rem; align-items:center;">
    <!-- Time Horizon Switcher -->
    <div style="background:#e2e8f0; border-radius:8px; padding:3px; display:flex;">
      <a href="<?= base_url('reports/index.php?report=' . $activeReport . '&range=week') ?>" class="btn btn-sm <?= $range === 'week' ? 'btn-primary' : 'btn-ghost' ?>" style="padding:0.35rem 0.75rem; border-radius:6px; font-size:0.75rem;">This Week</a>
      <a href="<?= base_url('reports/index.php?report=' . $activeReport . '&range=month') ?>" class="btn btn-sm <?= $range === 'month' ? 'btn-primary' : 'btn-ghost' ?>" style="padding:0.35rem 0.75rem; border-radius:6px; font-size:0.75rem;">This Month</a>
      <a href="<?= base_url('reports/index.php?report=' . $activeReport . '&range=fy') ?>" class="btn btn-sm <?= $range === 'fy' ? 'btn-primary' : 'btn-ghost' ?>" style="padding:0.35rem 0.75rem; border-radius:6px; font-size:0.75rem;">This FY</a>
      <a href="<?= base_url('reports/index.php?report=' . $activeReport . '&range=all') ?>" class="btn btn-sm <?= $range === 'all' ? 'btn-primary' : 'btn-ghost' ?>" style="padding:0.35rem 0.75rem; border-radius:6px; font-size:0.75rem;">All Time</a>
    </div>

    <?php if (can('reports', 'export')): ?>
      <a href="<?= base_url('reports/index.php?report=' . $activeReport . '&range=' . $range . '&export=csv') ?>" class="btn btn-secondary btn-sm">
        <i class="fa-solid fa-file-csv"></i> Export CSV
      </a>
    <?php endif; ?>
  </div>
</div>

<!-- 4 Primary Operational Report Tabs -->
<div style="display:flex; gap:0.5rem; margin-bottom:1.5rem; overflow-x:auto;">
  <a href="<?= base_url('reports/index.php?report=leads&range=' . $range) ?>" class="btn <?= $activeReport === 'leads' ? 'btn-primary' : 'btn-secondary' ?>" style="font-size:0.85rem; padding:0.45rem 1rem;">
    <i class="fa-solid fa-bullseye"></i> Lead Source Yields
  </a>
  <a href="<?= base_url('reports/index.php?report=branches&range=' . $range) ?>" class="btn <?= $activeReport === 'branches' ? 'btn-primary' : 'btn-secondary' ?>" style="font-size:0.85rem; padding:0.45rem 1rem;">
    <i class="fa-solid fa-building-columns"></i> Branch Performance
  </a>
  <a href="<?= base_url('reports/index.php?report=counselors&range=' . $range) ?>" class="btn <?= $activeReport === 'counselors' ? 'btn-primary' : 'btn-secondary' ?>" style="font-size:0.85rem; padding:0.45rem 1rem;">
    <i class="fa-solid fa-chart-line"></i> Counselor Caseload
  </a>
  <a href="<?= base_url('reports/index.php?report=finance&range=' . $range) ?>" class="btn <?= $activeReport === 'finance' ? 'btn-primary' : 'btn-secondary' ?>" style="font-size:0.85rem; padding:0.45rem 1rem;">
    <i class="fa-solid fa-file-invoice-dollar"></i> Financial Yields
  </a>
</div>

<?php if ($activeReport === 'leads'): ?>
  <!-- Chart & Table for Leads -->
  <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; margin-bottom:1.5rem; align-items:start;">
    <div class="card" style="padding:1.5rem;">
      <h3 style="font-size:1.05rem; font-weight:700; color:var(--text-heading); margin:0 0 1rem 0;">Marketing Inbound Channels Yield</h3>
      <div style="height:260px;">
        <canvas id="leadsChart"></canvas>
      </div>
    </div>
    <div class="card" style="padding:1.5rem;">
      <h3 style="font-size:1.05rem; font-weight:700; color:var(--text-heading); margin:0 0 1rem 0;">Conversion Yield Summary</h3>
      <div style="display:flex; flex-direction:column; gap:0.75rem; font-size:0.85rem;">
        <?php if (empty($sourceStats)): ?>
          <div style="text-align:center; padding:2rem 1rem; color:var(--text-muted);">
            <i class="fa-solid fa-chart-pie" style="font-size:1.6rem; margin-bottom:0.5rem; display:block; color:var(--text-light);"></i>
            No lead channel analytics available yet.
          </div>
        <?php else: ?>
          <?php foreach ($sourceStats as $ss): ?>
            <div style="display:flex; justify-content:space-between; align-items:center; padding:0.6rem 0.85rem; background:var(--bg-subtle); border-radius:6px;">
              <div>
                <strong><?= htmlspecialchars($ss['source']) ?></strong>
                <div style="font-size:0.72rem; color:var(--text-muted);"><?= (int)$ss['total_leads'] ?> leads generated</div>
              </div>
              <div style="text-align:right;">
                <span class="badge badge-success" style="font-weight:700; font-size:0.75rem;"><?= $ss['conv_pct'] ?>% Yield</span>
                <div style="font-size:0.72rem; color:var(--text-muted); margin-top:2px;"><?= (int)$ss['converted_customers'] ?> clients</div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>Source Channel</th>
            <th>Total Inbound Leads</th>
            <th>Clients Invoiced</th>
            <th>Enrolled Students</th>
            <th>Conversion Yield %</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($sourceStats)): ?>
            <tr>
              <td colspan="5" style="text-align:center; padding:2.5rem; color:var(--text-muted);">
                No lead source data available for this time range.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($sourceStats as $row): ?>
              <tr>
                <td><strong><?= htmlspecialchars($row['source']) ?></strong></td>
                <td style="font-weight:700; font-size:0.9rem;"><?= (int)$row['total_leads'] ?></td>
                <td style="font-weight:700; color:#15803d;"><?= (int)$row['converted_customers'] ?></td>
                <td style="font-weight:700; color:var(--primary);"><?= (int)$row['enrolled_count'] ?></td>
                <td><span class="badge badge-primary"><?= $row['conv_pct'] ?>%</span></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <script>
  document.addEventListener('DOMContentLoaded', function() {
    new Chart(document.getElementById('leadsChart'), {
      type: 'bar',
      data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [
          { label: 'Total Inbound Leads', data: <?= json_encode($chartLeadCounts) ?>, backgroundColor: '#7008E7' },
          { label: 'Invoiced Clients', data: <?= json_encode($chartCustomerCounts) ?>, backgroundColor: '#10b981' }
        ]
      },
      options: { responsive: true, maintainAspectRatio: false }
    });
  });
  </script>

<?php elseif ($activeReport === 'branches'): ?>
  <!-- Branch Performance Table -->
  <div class="card">
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>Branch Hub</th>
            <th>City</th>
            <th>Counselor Count</th>
            <th>Total Leads</th>
            <th>Customers Converted</th>
            <th>Enrolled Students</th>
            <th>Revenue Collected</th>
            <th>Outstanding Balance</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($branchStats as $bs): ?>
            <tr>
              <td><strong><?= htmlspecialchars($bs['branch_name']) ?></strong></td>
              <td><?= htmlspecialchars($bs['city']) ?></td>
              <td><span class="badge badge-secondary"><?= (int)$bs['counselors_count'] ?> Staff</span></td>
              <td style="font-weight:700;"><?= (int)$bs['total_leads'] ?></td>
              <td style="font-weight:700; color:#15803d;"><?= (int)$bs['customers_count'] ?></td>
              <td style="font-weight:700; color:var(--primary);"><?= (int)$bs['enrolled_count'] ?></td>
              <td style="font-weight:800; color:#15803d;"><?= format_currency($bs['revenue_collected']) ?></td>
              <td style="font-weight:800; color:#b91c1c;"><?= format_currency($bs['balance_due']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

<?php elseif ($activeReport === 'counselors'): ?>
  <!-- Counselor Caseload & Performance Table -->
  <div class="card">
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>Counselor Name</th>
            <th>Branch Hub</th>
            <th>Active Caseload</th>
            <th>Follow-up Logs</th>
            <th>Customers Converted</th>
            <th>Enrolled</th>
            <th>Conversion Rate</th>
            <th>Revenue Collected</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($counselorStats as $cs): ?>
            <tr>
              <td><strong><?= htmlspecialchars($cs['counselor_name']) ?></strong></td>
              <td><?= htmlspecialchars($cs['branch_name'] ?? 'General') ?></td>
              <td style="font-weight:700;"><?= (int)$cs['active_caseload'] ?> leads</td>
              <td><span class="badge badge-secondary"><?= (int)$cs['total_followups'] ?></span></td>
              <td style="font-weight:700; color:#15803d;"><?= (int)$cs['customers_converted'] ?></td>
              <td style="font-weight:700; color:var(--primary);"><?= (int)$cs['enrolled_students'] ?></td>
              <td><span class="badge badge-primary"><?= $cs['conversion_rate'] ?>%</span></td>
              <td style="font-weight:800; color:#15803d;"><?= format_currency($cs['total_collected']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

<?php elseif ($activeReport === 'finance'): ?>
  <!-- Financial Yields Table -->
  <div class="card">
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>Branch Hub</th>
            <th>Total Invoiced</th>
            <th>Total Collected</th>
            <th>Total Outstanding</th>
            <th>Collection Efficiency %</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($branchStats as $bs): 
            $inv = (float)$bs['revenue_collected'] + (float)$bs['balance_due'];
            $eff = $inv > 0 ? round(($bs['revenue_collected'] / $inv) * 100, 1) : 100;
          ?>
            <tr>
              <td><strong><?= htmlspecialchars($bs['branch_name']) ?></strong></td>
              <td style="font-weight:700; font-size:0.9rem;"><?= format_currency($inv) ?></td>
              <td style="font-weight:700; color:#15803d; font-size:0.9rem;"><?= format_currency($bs['revenue_collected']) ?></td>
              <td style="font-weight:800; color:#b91c1c; font-size:0.9rem;"><?= format_currency($bs['balance_due']) ?></td>
              <td><span class="badge <?= $eff >= 75 ? 'badge-success' : 'badge-warning' ?>"><?= $eff ?>%</span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
