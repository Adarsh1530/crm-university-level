<?php
/**
 * Admission Pipeline Hub (6 Standard Consultancy Stages)
 * University-Level Education Consultancy CRM (EdrootsCRM)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_permission('admissions', 'view');

$currentUser = current_user();
$pageTitle = 'Admission Pipeline';
$activePage = 'admissions';

$viewMode = $_GET['view'] ?? 'kanban'; // kanban or list
$filterStage = trim($_GET['stage'] ?? '');
$filterCountry = trim($_GET['country'] ?? '');
$filterBranch = isset($_GET['branch_id']) ? (int)$_GET['branch_id'] : 0;
$filterCounselor = isset($_GET['counselor_id']) ? (int)$_GET['counselor_id'] : 0;

$scopeClause = get_crm_scope_clause('l', 'assigned_counselor_id', 'branch_id');
$where = ["l.is_archived = 0", $scopeClause];
$params = [];

if (!empty($filterStage)) {
    $where[] = "l.stage = ?";
    $params[] = $filterStage;
}

if (!empty($filterCountry)) {
    $where[] = "l.preferred_country = ?";
    $params[] = $filterCountry;
}

if ($filterBranch > 0 && is_super_admin()) {
    $where[] = "l.branch_id = ?";
    $params[] = $filterBranch;
}

if ($filterCounselor > 0 && (is_super_admin() || is_branch_manager())) {
    $where[] = "l.assigned_counselor_id = ?";
    $params[] = $filterCounselor;
}

$whereSql = implode(' AND ', $where);

// The 6 standard admission stages
$stages = $pdo->query("SELECT * FROM admission_stages ORDER BY step_number ASC")->fetchAll();

// Fetch leads grouped by stage
$sql = "
    SELECT l.*, b.name AS branch_name, u.name AS counselor_name
    FROM leads l
    LEFT JOIN branches b ON l.branch_id = b.id
    LEFT JOIN users u ON l.assigned_counselor_id = u.id
    WHERE {$whereSql}
    ORDER BY l.id DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$allLeads = $stmt->fetchAll();

// Group leads by stage key for Kanban board
$leadsByStage = [];
foreach ($stages as $stg) {
    $leadsByStage[$stg['code']] = [];
}
foreach ($allLeads as $ld) {
    $stageKey = $ld['stage'];
    if (!isset($leadsByStage[$stageKey])) {
        $leadsByStage[$stageKey] = [];
    }
    $leadsByStage[$stageKey][] = $ld;
}

// Stage breakdown counts for tab pills
$stageCountsStmt = $pdo->query("
    SELECT l.stage, COUNT(*) as count 
    FROM leads l 
    WHERE l.is_archived = 0 AND {$scopeClause} 
    GROUP BY l.stage
");
$stageCounts = $stageCountsStmt->fetchAll(PDO::FETCH_KEY_PAIR);

$branches = $pdo->query("SELECT id, name, city FROM branches WHERE status = 'Active' ORDER BY name ASC")->fetchAll();

$cslSql = "
    SELECT u.id, u.name, u.branch_id, b.name AS branch_name
    FROM users u 
    JOIN roles r ON u.role_id = r.id 
    LEFT JOIN branches b ON u.branch_id = b.id 
    WHERE r.name = 'counselor' AND u.status = 'Active'
";
if (is_branch_manager()) {
    $cslSql .= " AND u.branch_id = " . (int)$currentUser['branch_id'];
}
$cslSql .= " ORDER BY u.name ASC";
$counselors = $pdo->query($cslSql)->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <h1 style="font-size:1.5rem; font-weight:700; color:var(--text-heading); margin:0 0 0.35rem 0;">Admission Pipeline</h1>
    <p style="font-size:0.875rem; color:var(--text-muted); margin:0;">
      Visual progression workflow across the 6 standard education consultancy milestones. Track students from initial enquiry through university enrollment.
    </p>
  </div>

  <div style="display:flex; gap:0.5rem; align-items:center;">
    <!-- View Switcher -->
    <div style="background:#e2e8f0; border-radius:8px; padding:3px; display:flex;">
      <a href="<?= base_url('admissions/index.php?' . http_build_query(array_merge($_GET, ['view' => 'kanban']))) ?>" class="btn btn-sm <?= $viewMode === 'kanban' ? 'btn-primary' : 'btn-ghost' ?>" style="padding:0.35rem 0.75rem; border-radius:6px; font-size:0.8rem;">
        <i class="fa-solid fa-table-columns"></i> Kanban Board
      </a>
      <a href="<?= base_url('admissions/index.php?' . http_build_query(array_merge($_GET, ['view' => 'list']))) ?>" class="btn btn-sm <?= $viewMode === 'list' ? 'btn-primary' : 'btn-ghost' ?>" style="padding:0.35rem 0.75rem; border-radius:6px; font-size:0.8rem;">
        <i class="fa-solid fa-list"></i> Table View
      </a>
    </div>

    <?php if (can('leads', 'add')): ?>
      <a href="<?= base_url('leads/add.php') ?>" class="btn btn-primary btn-sm">
        <i class="fa-solid fa-user-plus"></i> Add Student
      </a>
    <?php endif; ?>
  </div>
</div>

<!-- Filters Bar -->
<div class="card" style="margin-bottom:1.5rem; padding:1.25rem;">
  <form method="GET" action="<?= base_url('admissions/index.php') ?>" style="display:flex; flex-wrap:wrap; gap:0.75rem; align-items:center;">
    <input type="hidden" name="view" value="<?= htmlspecialchars($viewMode) ?>">

    <div style="min-width:180px;">
      <select name="stage" class="form-control">
        <option value="">All 6 Pipeline Stages</option>
        <?php foreach ($stages as $s): ?>
          <option value="<?= htmlspecialchars($s['code']) ?>" <?= $filterStage === $s['code'] ? 'selected' : '' ?>>
            <?= $s['step_number'] ?>. <?= htmlspecialchars($s['name']) ?> (<?= $stageCounts[$s['code']] ?? 0 ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div style="min-width:160px;">
      <select name="country" class="form-control">
        <option value="">All Target Countries</option>
        <?php foreach (['United Kingdom', 'Canada', 'Australia', 'United States', 'Ireland', 'Germany', 'New Zealand', 'Dubai'] as $c): ?>
          <option value="<?= $c ?>" <?= $filterCountry === $c ? 'selected' : '' ?>><?= $c ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <?php if (is_super_admin()): ?>
      <div style="min-width:160px;">
        <select name="branch_id" class="form-control">
          <option value="0">All Branches</option>
          <?php foreach ($branches as $b): ?>
            <option value="<?= $b['id'] ?>" <?= $filterBranch === (int)$b['id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    <?php endif; ?>

    <?php if (is_super_admin() || is_branch_manager()): ?>
      <div style="min-width:160px;">
        <select name="counselor_id" class="form-control">
          <option value="0">All Counselors</option>
          <?php foreach ($counselors as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $filterCounselor === (int)$c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    <?php endif; ?>

    <button type="submit" class="btn btn-primary btn-sm">
      <i class="fa-solid fa-filter"></i> Apply
    </button>
    <a href="<?= base_url('admissions/index.php?view=' . $viewMode) ?>" class="btn btn-secondary btn-sm">
      <i class="fa-solid fa-rotate-left"></i>
    </a>
  </form>
</div>

<?php if ($viewMode === 'kanban'): ?>
  <!-- Visual 6-Stage Kanban Board with Smooth Horizontal Flow -->
  <div style="overflow-x:auto; padding-bottom:1.5rem;">
    <div style="display:grid; grid-template-columns:repeat(<?= !empty($filterStage) ? '1' : '6' ?>, minmax(280px, 1fr)); gap:1rem; align-items:start; min-width:<?= !empty($filterStage) ? '380px' : '1680px' ?>;">
    <?php foreach ($stages as $s): 
      $stageKey = $s['code'];
      if (!empty($filterStage) && $filterStage !== $stageKey) continue;
      $stageCards = $leadsByStage[$stageKey] ?? [];
      $color = $s['color_code'] ?? $s['color'] ?? 'var(--primary)';
    ?>
      <div style="background:#f8fafc; border-radius:12px; border:1px solid #e2e8f0; min-height:480px; display:flex; flex-direction:column;">
        <!-- Column Header -->
        <div style="padding:1rem 1.15rem; border-bottom:2px solid <?= htmlspecialchars($color) ?>; background:#fff; border-top-left-radius:12px; border-top-right-radius:12px; display:flex; justify-content:space-between; align-items:center;">
          <div>
            <span style="font-size:0.75rem; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px;">
              Stage <?= $s['step_number'] ?>
            </span>
            <h4 style="font-size:0.95rem; font-weight:800; color:var(--text-heading); margin:2px 0 0 0;">
              <?= htmlspecialchars($s['name']) ?>
            </h4>
          </div>
          <span class="badge" style="background:var(--bg-subtle); color:var(--text-heading); font-weight:800; font-size:0.8rem; border-radius:12px; padding:2px 8px;">
            <?= count($stageCards) ?>
          </span>
        </div>

        <!-- Cards Container -->
        <div style="padding:0.85rem; display:flex; flex-direction:column; gap:0.75rem; flex:1;">
          <?php if (empty($stageCards)): ?>
            <div style="text-align:center; padding:2.5rem 1rem; color:var(--text-muted); font-size:0.8rem;">
              No students currently in this stage.
            </div>
          <?php else: ?>
            <?php foreach ($stageCards as $card): 
              $secondaryColor = match($card['secondary_status']) {
                  'On Hold' => '#b45309',
                  'Rejected', 'Lost' => '#b91c1c',
                  default => '#15803d'
              };
            ?>
              <div class="card" style="padding:1rem; border-radius:10px; box-shadow:0 2px 6px rgba(0,0,0,0.04); margin:0;">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:0.4rem;">
                  <span style="font-family:monospace; font-size:0.72rem; font-weight:700; color:var(--primary);">
                    <?= htmlspecialchars($card['lead_code']) ?>
                  </span>
                  <?= priority_badge($card['priority']) ?>
                </div>

                <a href="<?= base_url('leads/view.php?id=' . $card['id']) ?>" style="font-weight:700; color:var(--text-heading); text-decoration:none; font-size:0.92rem; display:block; margin-bottom:0.25rem;">
                  <?= htmlspecialchars($card['full_name']) ?>
                </a>

                <div style="font-size:0.78rem; color:var(--text-muted); margin-bottom:0.5rem;">
                  <i class="fa-solid fa-plane" style="font-size:0.7rem; color:var(--primary); margin-right:4px;"></i>
                  <strong><?= htmlspecialchars($card['preferred_country']) ?></strong> • <?= htmlspecialchars($card['preferred_course'] ?: 'General Program') ?>
                </div>

                <?php if ($card['secondary_status'] !== 'Active'): ?>
                  <div style="font-size:0.72rem; color:<?= $secondaryColor ?>; font-weight:700; margin-bottom:0.5rem;">
                    Status: <?= htmlspecialchars($card['secondary_status']) ?>
                  </div>
                <?php endif; ?>

                <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid var(--border-color); padding-top:0.6rem; margin-top:0.4rem; font-size:0.75rem; color:var(--text-muted);">
                  <span><i class="fa-solid fa-headset" style="font-size:0.7rem;"></i> <?= htmlspecialchars($card['counselor_name'] ?? 'Counselor') ?></span>
                  <button type="button" class="btn btn-outline btn-sm" style="font-size:0.72rem; padding:2px 8px;" onclick="openQuickStageModal(<?= (int)$card['id'] ?>, <?= htmlspecialchars(json_encode($card['full_name'])) ?>, <?= htmlspecialchars(json_encode($card['stage'])) ?>)">
                    Advance &rarr;
                  </button>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
    </div>
  </div>

<?php else: ?>
  <!-- Tabular List View -->
  <div class="card">
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>Student Name & Code</th>
            <th>Target Country & Course</th>
            <th>Current Stage</th>
            <th>Secondary Status</th>
            <th>Priority</th>
            <th>Branch & Counselor</th>
            <th style="text-align:right;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($allLeads)): ?>
            <tr>
              <td colspan="7" style="text-align:center; padding:3rem; color:var(--text-muted);">
                No students match your admission pipeline filters.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($allLeads as $lead): ?>
              <tr>
                <td>
                  <a href="<?= base_url('leads/view.php?id=' . $lead['id']) ?>" style="font-weight:700; color:var(--text-heading); text-decoration:none; font-size:0.9rem;">
                    <?= htmlspecialchars($lead['full_name']) ?>
                  </a>
                  <div style="font-family:monospace; font-size:0.72rem; color:var(--primary);">
                    <?= htmlspecialchars($lead['lead_code']) ?>
                  </div>
                </td>
                <td>
                  <div style="font-weight:600; font-size:0.85rem;"><?= htmlspecialchars($lead['preferred_country']) ?></div>
                  <div style="font-size:0.75rem; color:var(--text-muted);"><?= htmlspecialchars($lead['preferred_course'] ?: 'General') ?></div>
                </td>
                <td>
                  <span class="badge badge-primary" style="font-weight:700; font-size:0.72rem;">
                    <?= htmlspecialchars($lead['stage']) ?>
                  </span>
                </td>
                <td>
                  <span class="badge <?= $lead['secondary_status'] === 'Active' ? 'badge-success' : 'badge-warning' ?>" style="font-size:0.72rem;">
                    <?= htmlspecialchars($lead['secondary_status']) ?>
                  </span>
                </td>
                <td><?= priority_badge($lead['priority']) ?></td>
                <td>
                  <div style="font-size:0.82rem; font-weight:600;"><?= htmlspecialchars($lead['counselor_name'] ?? 'Counselor') ?></div>
                  <div style="font-size:0.72rem; color:var(--text-muted);"><?= htmlspecialchars($lead['branch_name'] ?? 'Branch') ?></div>
                </td>
                <td style="text-align:right;">
                  <button type="button" class="btn btn-outline btn-sm" onclick="openQuickStageModal(<?= (int)$lead['id'] ?>, <?= htmlspecialchars(json_encode($lead['full_name'])) ?>, <?= htmlspecialchars(json_encode($lead['stage'])) ?>)">
                    Update Stage
                  </button>
                  <a href="<?= base_url('leads/view.php?id=' . $lead['id']) ?>" class="btn btn-secondary btn-sm">
                    <i class="fa-solid fa-eye"></i>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<!-- Quick Stage Advancement Modal -->
<div class="modal fade" id="quickStageModal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(12,36,60,0.5); align-items:center; justify-content:center;">
  <div class="modal-dialog" style="background:#fff; border-radius:14px; max-width:480px; width:92%; margin:auto; box-shadow:0 20px 50px rgba(0,0,0,0.2); overflow:hidden;">
    <form method="POST" action="<?= base_url('admissions/update_stage.php') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="lead_id" id="quick_lead_id" value="">
      <input type="hidden" name="return_url" value="<?= base_url('admissions/index.php?' . http_build_query($_GET)) ?>">

      <div class="modal-header" style="padding:1.25rem 1.5rem; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
        <h3 style="font-size:1.15rem; font-weight:700; margin:0; color:var(--text-heading);">
          <i class="fa-solid fa-diagram-project" style="color:var(--primary); margin-right:8px;"></i> Advance Admission Stage
        </h3>
        <button type="button" onclick="closeQuickStageModal()" style="border:none; background:none; font-size:1.4rem; cursor:pointer; color:var(--text-muted);">&times;</button>
      </div>

      <div class="modal-body" style="padding:1.5rem;">
        <div style="background:var(--bg-subtle); border-radius:8px; padding:0.85rem; margin-bottom:1.25rem; font-size:0.85rem;">
          Candidate: <strong id="quick_lead_name">—</strong>
        </div>

        <div class="form-group" style="margin-bottom:1.25rem;">
          <label class="form-label">Destination Pipeline Stage <span style="color:red;">*</span></label>
          <select name="new_stage" id="quick_stage_select" class="form-control" required>
            <?php foreach ($stages as $s): ?>
              <option value="<?= htmlspecialchars($s['code']) ?>">
                <?= $s['step_number'] ?>. <?= htmlspecialchars($s['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group" style="margin-bottom:1.25rem;">
          <label class="form-label">Secondary Operational Status</label>
          <select name="secondary_status" class="form-control">
            <option value="Active">Active Progression</option>
            <option value="On Hold">On Hold (Pending Student Decision)</option>
            <option value="Rejected">Rejected by University</option>
            <option value="Lost">Lost / Opted Out</option>
          </select>
        </div>

        <div class="form-group" style="margin-bottom:0.5rem;">
          <label class="form-label">Stage Change Note</label>
          <textarea name="notes" class="form-control" rows="2" placeholder="e.g. CAS document received or offer letter issued..."></textarea>
        </div>
      </div>

      <div class="modal-footer" style="padding:1rem 1.5rem; border-top:1px solid var(--border-color); display:flex; justify-content:flex-end; gap:0.5rem;">
        <button type="button" onclick="closeQuickStageModal()" class="btn btn-secondary btn-sm">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm">
          <i class="fa-solid fa-check"></i> Save Transition
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function openQuickStageModal(leadId, leadName, currentStage) {
  document.getElementById('quick_lead_id').value = leadId;
  document.getElementById('quick_lead_name').innerText = leadName;
  document.getElementById('quick_stage_select').value = currentStage;
  document.getElementById('quickStageModal').style.display = 'flex';
}
function closeQuickStageModal() {
  document.getElementById('quickStageModal').style.display = 'none';
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
