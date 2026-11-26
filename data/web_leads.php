<?php
/**
 * Social Media Leads Staging & Prioritization Queue - Core CRM
 * Fully Aligned with Client Requirements & Malayalam Training Audio:
 * - Tracking and prioritization of leads sourced through online/social advertising channels
 * - Channels: Meta Ads (Facebook), Instagram, Google Ads, TikTok Ads
 * - Regional & Geographic Segmentation (Central Zone density, Center vs Outer)
 * - Customer Contact Verification: Phone number cross-checking
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_permission('web_leads', 'view');

$currentUser = current_user();
$pageTitle = 'Social Media Leads';
$activePage = 'web_leads';

// Handle Actions (Assign to Active Leads or Disqualify)
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? '');
    $action = $_POST['action'] ?? '';
    $webLeadId = (int)($_POST['web_lead_id'] ?? 0);

    if ($action === 'assign_lead') {
        require_permission('web_leads', 'assign');
        $branchId = (int)($_POST['branch_id'] ?? 1);
        $counselorId = (int)($_POST['counselor_id'] ?? 0);
        $rawPhone = trim($_POST['phone'] ?? '');
        $regionNumber = trim($_POST['region_number'] ?? 'REG-01');
        $mainRegion = trim($_POST['main_region'] ?? 'Central Zone');
        $zoneType = trim($_POST['zone_type'] ?? 'Center');
        $businessName = trim($_POST['business_name'] ?? '');

        $stmt = $pdo->prepare("SELECT * FROM web_leads WHERE id = ? LIMIT 1");
        $stmt->execute([$webLeadId]);
        $wRow = $stmt->fetch();

        if (!$wRow) {
            set_flash('danger', 'Social media lead not found.');
            redirect('data/web_leads.php');
        }

        if ($wRow['status'] === 'Assigned') {
            set_flash('warning', 'This social media lead has already been converted to an active lead.');
            redirect('leads/view.php?id=' . $wRow['assigned_lead_id']);
        }

        if ($counselorId <= 0) {
            // Pick first active counselor
            $counselorId = 3;
        }

        $phoneToUse = !empty($rawPhone) ? $rawPhone : $wRow['phone'];
        $cleanPhone = normalize_phone($phoneToUse);

        // Check duplicate in active leads
        $existingLead = check_phone_duplicate($cleanPhone);
        if ($existingLead) {
            if (!empty($_POST['link_existing'])) {
                $pdo->beginTransaction();
                $updStmt = $pdo->prepare("
                    UPDATE web_leads 
                    SET status = 'Assigned', assigned_lead_id = ?, assigned_at = NOW(), assigned_counselor_id = ?
                    WHERE id = ?
                ");
                $updStmt->execute([$existingLead['id'], $counselorId ?: $existingLead['assigned_counselor_id'], $webLeadId]);
                log_activity(
                    $currentUser['id'],
                    $existingLead['id'],
                    'Social Media Lead Linked',
                    "Inbound inquiry from {$wRow['source']} ({$cleanPhone}) linked to existing lead record."
                );
                $pdo->commit();
                set_flash('success', "Social media inquiry linked to lead <strong>" . htmlspecialchars($existingLead['full_name']) . "</strong> ({$existingLead['lead_code']}).");
                redirect('leads/view.php?id=' . $existingLead['id']);
            } else {
                set_flash('warning', "Notice: An active lead already exists with phone <strong>{$cleanPhone}</strong>: <strong>" . htmlspecialchars($existingLead['full_name']) . "</strong> ({$existingLead['lead_code']}). <a href='" . base_url('leads/view.php?id=' . $existingLead['id']) . "' class='btn btn-sm btn-primary' style='margin-left:8px; font-weight:700;'>Open Lead &rarr;</a>");
                redirect('data/web_leads.php');
            }
        }

        // Generate Lead Code: LED-YYYY-XXXX
        $currentYear = date('Y');
        $stmtCount = $pdo->query("SELECT COUNT(*) FROM leads");
        $seq = (int)$stmtCount->fetchColumn() + 1;
        $leadCode = sprintf("LED-%s-%04d", $currentYear, $seq);

        try {
            $pdo->beginTransaction();

            $leadName = $wRow['full_name'] ?: ($wRow['student_name'] ?: 'Prospect Contact');
            $business = !empty($businessName) ? $businessName : ($leadName . ' Commercial');

            $stmtLead = $pdo->prepare("
                INSERT INTO leads (
                    lead_code, full_name, email, phone, phone_normalized, mobile_number,
                    branch_id, assigned_counselor_id, region_number, main_region, sub_area, zone_type,
                    business_name, record_completeness, active_status, prospectivity, priority,
                    stage, source, is_phone_verified, notes, assigned_at, created_by, created_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?,
                    ?, 88, 'New Lead', 'Warm', 'Moderate',
                    'ENQUIRY', ?, 0, ?, NOW(), ?, NOW()
                )
            ");
            $stmtLead->execute([
                $leadCode,
                $leadName,
                $wRow['email'],
                $cleanPhone,
                $cleanPhone,
                $cleanPhone,
                $branchId,
                $counselorId,
                $regionNumber,
                $mainRegion,
                'Downtown Central',
                $zoneType,
                $business,
                $wRow['source'],
                $wRow['notes'] ?: "Inbound lead captured via {$wRow['source']}",
                $currentUser['id']
            ]);
            $newLeadId = $pdo->lastInsertId();

            // Update web_leads record
            $updStmt = $pdo->prepare("
                UPDATE web_leads 
                SET status = 'Assigned', phone = ?, branch_id = ?, assigned_counselor_id = ?,
                    assigned_lead_id = ?, assigned_at = NOW()
                WHERE id = ?
            ");
            $updStmt->execute([$cleanPhone, $branchId, $counselorId, $newLeadId, $webLeadId]);

            // Notification to counselor
            $notifStmt = $pdo->prepare("
                INSERT INTO notifications (user_id, title, message, link, status)
                VALUES (?, 'Social Media Lead Assigned', ?, ?, 'Not Attended')
            ");
            $notifStmt->execute([
                $counselorId,
                "Social media lead {$leadName} from {$wRow['source']} has been assigned to your caseload.",
                "leads/view.php?id={$newLeadId}"
            ]);

            log_activity(
                $currentUser['id'],
                $newLeadId,
                'Social Media Lead Converted',
                "Converted inbound {$wRow['source']} lead into active record {$leadCode} assigned to counselor."
            );

            $pdo->commit();
            set_flash('success', "Social media lead converted to active lead <strong>" . htmlspecialchars($leadName) . "</strong> ({$leadCode}).");
            redirect('leads/view.php?id=' . $newLeadId);
        } catch (Exception $e) {
            $pdo->rollBack();
            set_flash('danger', 'Error converting lead: ' . $e->getMessage());
            redirect('data/web_leads.php');
        }
    } elseif ($action === 'disqualify') {
        require_permission('web_leads', 'delete');
        $reason = trim($_POST['disqualify_reason'] ?? 'Invalid contact / Spam');

        $upd = $pdo->prepare("UPDATE web_leads SET status = 'Disqualified', notes = CONCAT(IFNULL(notes,''), ' | Disqualified: ', ?) WHERE id = ?");
        $upd->execute([$reason, $webLeadId]);

        set_flash('warning', 'Lead disqualified successfully.');
        redirect('data/web_leads.php');
    }
}

// Fetch Social Media Leads
$statusFilter = $_GET['status'] ?? 'Pending';
$sourceFilter = trim($_GET['source'] ?? '');

$where = ["1=1"];
$params = [];

if ($statusFilter !== 'All') {
    $where[] = "w.status = ?";
    $params[] = $statusFilter;
}

if (!empty($sourceFilter)) {
    $where[] = "w.source = ?";
    $params[] = $sourceFilter;
}

$whereSql = implode(' AND ', $where);

$stmt = $pdo->prepare("
    SELECT w.*, l.lead_code, b.name as branch_name, u.name as counselor_name
    FROM web_leads w
    LEFT JOIN leads l ON w.assigned_lead_id = l.id
    LEFT JOIN branches b ON w.branch_id = b.id
    LEFT JOIN users u ON w.assigned_counselor_id = u.id
    WHERE {$whereSql}
    ORDER BY w.id DESC
");
$stmt->execute($params);
$webLeads = $stmt->fetchAll();

// Live Counts
$pendingCount = (int)$pdo->query("SELECT COUNT(*) FROM web_leads WHERE status = 'Pending'")->fetchColumn();
$assignedCount = (int)$pdo->query("SELECT COUNT(*) FROM web_leads WHERE status = 'Assigned'")->fetchColumn();
$disqualifiedCount = (int)$pdo->query("SELECT COUNT(*) FROM web_leads WHERE status = 'Disqualified'")->fetchColumn();

// Counselors List
$counselors = $pdo->query("SELECT u.id, u.name, r.label as role_label FROM users u JOIN roles r ON u.role_id = r.id WHERE u.status = 'Active' ORDER BY u.name ASC")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<!-- Header -->
<div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <h1 style="font-size:1.5rem; font-weight:800; color:var(--text-heading); margin:0 0 0.25rem 0;">
      Social Media Leads Staging Queue
    </h1>
    <p style="font-size:0.875rem; color:var(--text-muted); margin:0;">
      Prioritization, phone number cross-checking, regional distribution, and counselor allocation for online campaigns.
    </p>
  </div>
  <div style="display:flex; gap:0.5rem; align-items:center;">
    <span class="badge badge-primary" style="font-size:0.8rem; padding:0.4rem 0.75rem;">
      <i class="fa-solid fa-bolt"></i> Live Social Media Ingestion
    </span>
  </div>
</div>

<!-- KPI Mini Cards -->
<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:1rem; margin-bottom:1.5rem;">
  <div class="card" style="padding:1.2rem; display:flex; align-items:center; gap:1rem;">
    <div style="width:48px; height:48px; border-radius:12px; background:#fef3c7; color:#b45309; display:flex; align-items:center; justify-content:center; font-size:1.3rem;">
      <i class="fa-solid fa-clock-rotate-left"></i>
    </div>
    <div>
      <div style="font-size:1.5rem; font-weight:800; color:#b45309; line-height:1.1;"><?= $pendingCount ?></div>
      <div style="font-size:0.8rem; color:var(--text-muted); font-weight:600;">Pending Triage</div>
    </div>
  </div>
  <div class="card" style="padding:1.2rem; display:flex; align-items:center; gap:1rem;">
    <div style="width:48px; height:48px; border-radius:12px; background:#dcfce7; color:#15803d; display:flex; align-items:center; justify-content:center; font-size:1.3rem;">
      <i class="fa-solid fa-circle-check"></i>
    </div>
    <div>
      <div style="font-size:1.5rem; font-weight:800; color:#15803d; line-height:1.1;"><?= $assignedCount ?></div>
      <div style="font-size:0.8rem; color:var(--text-muted); font-weight:600;">Assigned to Counselors</div>
    </div>
  </div>
  <div class="card" style="padding:1.2rem; display:flex; align-items:center; gap:1rem;">
    <div style="width:48px; height:48px; border-radius:12px; background:#fee2e2; color:#b91c1c; display:flex; align-items:center; justify-content:center; font-size:1.3rem;">
      <i class="fa-solid fa-ban"></i>
    </div>
    <div>
      <div style="font-size:1.5rem; font-weight:800; color:#b91c1c; line-height:1.1;"><?= $disqualifiedCount ?></div>
      <div style="font-size:0.8rem; color:var(--text-muted); font-weight:600;">Disqualified / Spam</div>
    </div>
  </div>
</div>

<!-- Filters Bar -->
<div class="card" style="margin-bottom:1.5rem; padding:1.25rem; border-radius:12px;">
  <form method="GET" action="<?= base_url('data/web_leads.php') ?>" style="display:flex; flex-wrap:wrap; gap:0.75rem; align-items:center;">
    <div style="min-width:180px;">
      <select name="status" class="form-control">
        <option value="Pending" <?= $statusFilter === 'Pending' ? 'selected' : '' ?>>Pending Triage Only</option>
        <option value="Assigned" <?= $statusFilter === 'Assigned' ? 'selected' : '' ?>>Assigned Leads</option>
        <option value="Disqualified" <?= $statusFilter === 'Disqualified' ? 'selected' : '' ?>>Disqualified</option>
        <option value="All" <?= $statusFilter === 'All' ? 'selected' : '' ?>>All Social Inbound Records</option>
      </select>
    </div>

    <div style="min-width:180px;">
      <select name="source" class="form-control">
        <option value="">All Social Channels</option>
        <option value="Meta Ads" <?= $sourceFilter === 'Meta Ads' ? 'selected' : '' ?>>Meta Ads (Facebook)</option>
        <option value="Instagram" <?= $sourceFilter === 'Instagram' ? 'selected' : '' ?>>Instagram Campaign</option>
        <option value="Google Ads" <?= $sourceFilter === 'Google Ads' ? 'selected' : '' ?>>Google Ads</option>
        <option value="TikTok Ads" <?= $sourceFilter === 'TikTok Ads' ? 'selected' : '' ?>>TikTok Ads</option>
      </select>
    </div>

    <button type="submit" class="btn btn-primary btn-sm" style="font-weight:700;">
      <i class="fa-solid fa-filter"></i> Apply
    </button>
    <a href="<?= base_url('data/web_leads.php') ?>" class="btn btn-secondary btn-sm" title="Reset Filters">
      <i class="fa-solid fa-rotate-left"></i>
    </a>
  </form>
</div>

<!-- Social Media Leads Table -->
<div class="card" style="border-radius:14px; overflow:hidden;">
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Source Channel</th>
          <th>Lead / Contact Name</th>
          <th>Phone (Cross-Check)</th>
          <th>Campaign / Interest</th>
          <th>Ingestion Date</th>
          <th>Status</th>
          <th style="text-align:right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($webLeads)): ?>
          <tr>
            <td colspan="7" style="text-align:center; padding:3rem; color:var(--text-muted);">
              <i class="fa-solid fa-inbox" style="font-size:2rem; margin-bottom:0.75rem; display:block; color:var(--text-light);"></i>
              No social media inbound leads match this filter.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($webLeads as $lead): 
            $sourceIcon = match($lead['source']) {
                'Meta Ads' => 'fa-brands fa-facebook',
                'Instagram' => 'fa-brands fa-instagram',
                'Google Ads' => 'fa-brands fa-google',
                default => 'fa-solid fa-hashtag'
            };
            $sourceColor = match($lead['source']) {
                'Meta Ads' => '#1877f2',
                'Instagram' => '#e1306c',
                'Google Ads' => '#ea4335',
                default => 'var(--primary)'
            };
          ?>
            <tr>
              <td>
                <div style="display:flex; align-items:center; gap:0.5rem;">
                  <i class="<?= $sourceIcon ?>" style="color:<?= $sourceColor ?>; font-size:1.1rem;"></i>
                  <div>
                    <strong style="font-size:0.85rem; color:var(--text-heading); display:block;"><?= htmlspecialchars((string)($lead['source'] ?? 'Social Media')) ?></strong>
                    <span style="font-size:0.72rem; color:var(--text-muted);"><?= htmlspecialchars($lead['source_campaign'] ?: 'Ad Campaign') ?></span>
                  </div>
                </div>
              </td>
              <td>
                <div style="font-weight:700; color:var(--text-heading); font-size:0.92rem;">
                  <?= htmlspecialchars((string)($lead['full_name'] ?: ($lead['student_name'] ?: 'Prospect'))) ?>
                </div>
                <div style="font-size:0.75rem; color:var(--text-muted);">
                  <i class="fa-solid fa-location-dot" style="font-size:0.7rem;"></i> <?= htmlspecialchars((string)($lead['city_state'] ?: 'Central Hub')) ?>
                </div>
              </td>
              <td>
                <div style="font-family:monospace; font-weight:800; color:var(--text-heading); font-size:0.88rem;">
                  <?= htmlspecialchars((string)($lead['phone'] ?: $lead['mobile_number'])) ?>
                </div>
                <?php if (!empty($lead['email'])): ?>
                  <div style="font-size:0.75rem; color:var(--text-muted);"><?= htmlspecialchars((string)$lead['email']) ?></div>
                <?php endif; ?>
              </td>
              <td>
                <div style="font-weight:600; font-size:0.85rem; color:var(--text-heading);">
                  <?= htmlspecialchars((string)($lead['target_course'] ?: 'Business Account')) ?>
                </div>
                <div style="font-size:0.75rem; color:var(--text-muted);">
                  <?= htmlspecialchars((string)($lead['target_country'] ?: 'Commercial')) ?>
                </div>
              </td>
              <td style="font-size:0.8rem; color:var(--text-muted);">
                <?= date('d M Y, h:i A', strtotime($lead['created_at'])) ?>
              </td>
              <td>
                <?php if ($lead['status'] === 'Pending'): ?>
                  <span class="badge badge-warning" style="font-weight:700;">PENDING TRIAGE</span>
                <?php elseif ($lead['status'] === 'Assigned'): ?>
                  <span class="badge badge-success" style="font-weight:700;">ASSIGNED</span>
                  <?php if (!empty($lead['lead_code'])): ?>
                    <a href="<?= base_url('leads/view.php?id=' . $lead['assigned_lead_id']) ?>" style="display:block; font-size:0.72rem; color:var(--primary); font-family:monospace; margin-top:2px; text-decoration:none;">
                      <?= htmlspecialchars($lead['lead_code']) ?> &rarr;
                    </a>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="badge badge-danger">DISQUALIFIED</span>
                <?php endif; ?>
              </td>
              <td style="text-align:right;">
                <div style="display:inline-flex; gap:0.4rem; align-items:center;">
                  <?php if ($lead['status'] === 'Pending' && can('web_leads', 'assign')): ?>
                    <button type="button" class="btn btn-primary btn-sm" onclick="openAssignModal(<?= htmlspecialchars(json_encode($lead)) ?>)" style="font-weight:700;">
                      <i class="fa-solid fa-user-check"></i> Route to Counselor
                    </button>
                  <?php elseif ($lead['status'] === 'Assigned' && !empty($lead['assigned_lead_id'])): ?>
                    <a href="<?= base_url('leads/view.php?id=' . $lead['assigned_lead_id']) ?>" class="btn btn-secondary btn-sm" style="font-weight:600;">
                      <i class="fa-solid fa-eye"></i> View Lead &rarr;
                    </a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal for Staging & Assigning Social Media Lead -->
<div class="modal fade" id="assignModal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(12,36,60,0.5); align-items:center; justify-content:center;">
  <div class="modal-dialog" style="background:#fff; border-radius:14px; max-width:540px; width:92%; margin:auto; box-shadow:0 20px 50px rgba(0,0,0,0.2); overflow:hidden;">
    <form method="POST" action="<?= base_url('data/web_leads.php') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="assign_lead">
      <input type="hidden" name="web_lead_id" id="assign_web_lead_id" value="">
      
      <div class="modal-header" style="padding:1.25rem 1.5rem; background:linear-gradient(135deg, #4f46e5 0%, #3730a3 100%); color:#fff; display:flex; justify-content:space-between; align-items:center;">
        <div>
          <h3 style="font-size:1.15rem; font-weight:700; margin:0; color:#fff;">
            <i class="fa-solid fa-share"></i> Route Social Media Lead to Counselor
          </h3>
          <span style="font-size:0.75rem; color:rgba(255,255,255,0.85);">Verify phone number and assign regional identifier</span>
        </div>
        <button type="button" onclick="closeAssignModal()" style="border:none; background:none; font-size:1.4rem; cursor:pointer; color:#fff;">&times;</button>
      </div>

      <div class="modal-body" style="padding:1.5rem;">
        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:0.85rem; margin-bottom:1.25rem; font-size:0.85rem;">
          <div style="font-weight:700; color:var(--text-heading);" id="assign_student_name">—</div>
          <div style="color:var(--text-muted); font-size:0.75rem;" id="assign_student_meta">—</div>
        </div>

        <div class="form-group" style="margin-bottom:1rem;">
          <label class="form-label" style="font-weight:700;">Phone Number (Cross-Check Verification)</label>
          <input type="text" name="phone" id="assign_phone" class="form-control" required style="font-weight:700; font-family:monospace;">
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; margin-bottom:1rem;">
          <div>
            <label class="form-label" style="font-weight:700; font-size:0.8rem;">Region Number</label>
            <select name="region_number" class="form-control">
              <option value="REG-01">REG-01</option>
              <option value="REG-02">REG-02</option>
              <option value="REG-03">REG-03</option>
              <option value="REG-04">REG-04</option>
            </select>
          </div>
          <div>
            <label class="form-label" style="font-weight:700; font-size:0.8rem;">Main Region</label>
            <select name="main_region" class="form-control">
              <option value="Central Zone">Central Zone</option>
              <option value="North Zone">North Zone</option>
              <option value="South Zone">South Zone</option>
              <option value="West Zone">West Zone</option>
            </select>
          </div>
        </div>

        <div class="form-group" style="margin-bottom:1rem;">
          <label class="form-label" style="font-weight:700;">Assign to Counselor / Advisor <span style="color:red;">*</span></label>
          <select name="counselor_id" id="assign_counselor_id" class="form-control" required style="font-weight:600;">
            <?php foreach ($counselors as $c): ?>
              <option value="<?= $c['id'] ?>">
                <?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['role_label'] ?? 'Counselor') ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="modal-footer" style="padding:1rem 1.5rem; border-top:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center; background:#f8fafc;">
        <button type="button" class="btn btn-outline-danger btn-sm" onclick="disqualifyCurrentLead()">
          <i class="fa-solid fa-ban"></i> Disqualify
        </button>
        <div style="display:flex; gap:0.5rem;">
          <button type="button" onclick="closeAssignModal()" class="btn btn-secondary btn-sm">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm" style="font-weight:700;">
            <i class="fa-solid fa-check"></i> Assign to Active Leads
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
function openAssignModal(lead) {
  document.getElementById('assign_web_lead_id').value = lead.id;
  document.getElementById('assign_student_name').innerText = lead.full_name || lead.student_name || 'Prospect';
  document.getElementById('assign_student_meta').innerText = (lead.source || 'Social Media') + ' • ' + (lead.city_state || 'Central Hub');
  document.getElementById('assign_phone').value = lead.phone || lead.mobile_number;
  document.getElementById('assignModal').style.display = 'flex';
}

function closeAssignModal() {
  document.getElementById('assignModal').style.display = 'none';
}

function disqualifyCurrentLead() {
  if (confirm('Are you sure you want to disqualify this inbound lead as spam or invalid?')) {
    const f = document.createElement('form');
    f.method = 'POST';
    f.action = '<?= base_url('data/web_leads.php') ?>';
    f.innerHTML = `
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="disqualify">
      <input type="hidden" name="web_lead_id" value="${document.getElementById('assign_web_lead_id').value}">
      <input type="hidden" name="disqualify_reason" value="Disqualified via social media staging queue">
    `;
    document.body.appendChild(f);
    f.submit();
  }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
