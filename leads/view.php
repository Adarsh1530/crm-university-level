<?php
/**
 * Authentic Lead Details & Customer Record
 * Fully Aligned with Client Requirements & Malayalam Training Audio:
 * 1. System Identity: Core CRM (Customer Relationship Management)
 * 2. Customer Contact Verification & Phone Cross-Checking (Digit-by-digit format & read aloud)
 * 3. Owner & Business Record Completeness (% progress bar)
 * 4. Regional & Geographic Segmentation (Region numbers, Central Zone density vs Outer Zone)
 * 5. Call Data Integration (Centralized call logging table & inline call logger)
 * 6. Follow-up Management Lifecycle across 4 Stages (New, Scheduled, Pending Backlog, Long-Cycle)
 * 7. Top Header Update & Counselor Escalation Actions
 * 8. Bottom / Details Panel: Key and status model toggles and detailed fields
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_permission('leads', 'view');

$leadId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$currentUser = current_user();
$activeTab = trim($_GET['tab'] ?? 'calls');

if ($leadId <= 0 || !can_access_lead($leadId)) {
    $scopeClause = get_crm_scope_clause('l', 'assigned_counselor_id', 'branch_id');
    $firstStmt = $pdo->query("SELECT id FROM leads l WHERE l.is_archived = 0 AND {$scopeClause} ORDER BY id ASC LIMIT 1");
    $firstRow = $firstStmt ? $firstStmt->fetch() : null;
    if ($firstRow && !empty($firstRow['id'])) {
        $leadId = (int)$firstRow['id'];
    } else {
        set_flash('danger', 'Access Denied: You cannot access this lead record.');
        redirect('leads/index.php');
    }
}

// Fetch Lead Details
$stmt = $pdo->prepare("
    SELECT l.*, b.name AS branch_name, b.code AS branch_code, b.city AS branch_city,
           u.name AS counselor_name, u.email AS counselor_email, u.phone AS counselor_phone,
           cb.name AS created_by_name,
           vb.name AS verified_by_name
    FROM leads l
    LEFT JOIN branches b ON l.branch_id = b.id
    LEFT JOIN users u ON l.assigned_counselor_id = u.id
    LEFT JOIN users cb ON l.created_by = cb.id
    LEFT JOIN users vb ON l.phone_verified_by = vb.id
    WHERE l.id = ?
    LIMIT 1
");
$stmt->execute([$leadId]);
$lead = $stmt->fetch();

if (!$lead) {
    set_flash('danger', 'Lead record not found.');
    redirect('leads/index.php');
}

$pageTitle = $lead['full_name'] . ' (' . $lead['lead_code'] . ')';
$activePage = 'leads';

// Interaction Titles for follow-up form
$interactionTitles = [];
try {
    $interactionTitles = $pdo->query("SELECT * FROM interaction_titles WHERE status = 'Active' ORDER BY sort_order ASC")->fetchAll();
} catch (Exception $e) {}

// Linked Call Data Logs
$callLogs = [];
try {
    $cStmt = $pdo->prepare("
        SELECT c.*, u.name as caller_name, r.label as caller_role
        FROM call_logs c
        LEFT JOIN users u ON c.caller_id = u.id
        LEFT JOIN roles r ON u.role_id = r.id
        WHERE c.lead_id = ?
        ORDER BY c.id DESC
    ");
    $cStmt->execute([$leadId]);
    $callLogs = $cStmt->fetchAll();
} catch (Exception $ce) {}

// Historical Follow-ups
$fStmt = $pdo->prepare("
    SELECT f.*, it.title AS interaction_title, it.max_duration_days, u.name AS counselor_name
    FROM followups f
    LEFT JOIN interaction_titles it ON f.interaction_title_id = it.id
    LEFT JOIN users u ON f.counselor_id = u.id
    WHERE f.lead_id = ?
    ORDER BY f.id DESC
");
$fStmt->execute([$leadId]);
$followups = $fStmt->fetchAll();

// Invoices (if converted customer)
$invoices = [];
try {
    $invStmt = $pdo->prepare("
        SELECT i.*, u.name AS created_by_name
        FROM invoices i
        LEFT JOIN users u ON i.created_by = u.id
        WHERE i.lead_id = ?
        ORDER BY i.id DESC
    ");
    $invStmt->execute([$leadId]);
    $invoices = $invStmt->fetchAll();
} catch (Exception $ie) {}

// Payments
$payments = [];
try {
    $payStmt = $pdo->prepare("
        SELECT p.*, i.invoice_number, u.name AS received_by_name
        FROM payments p
        LEFT JOIN invoices i ON p.invoice_id = i.id
        LEFT JOIN users u ON p.received_by = u.id
        WHERE p.lead_id = ?
        ORDER BY p.id DESC
    ");
    $payStmt->execute([$leadId]);
    $payments = $payStmt->fetchAll();
} catch (Exception $payEx) {}

// Calculate Customer Status
$isCustomer = ($lead['stage'] === 'CUSTOMER' || $lead['active_status'] === 'Converted' || !empty($lead['converted_at']) || !empty($invoices));

// Counselors List for Reassignment / Escalation
$counselors = [];
try {
    $counselors = $pdo->query("SELECT u.id, u.name, r.label as role_label FROM users u JOIN roles r ON u.role_id = r.id WHERE u.status = 'Active' ORDER BY u.name ASC")->fetchAll();
} catch (Exception $e) {}

// Prepare clean digits for phone reading aloud
$rawPhoneDigits = preg_replace('/\D/', '', $lead['phone']);
$spacedDigits = implode(' - ', str_split($rawPhoneDigits));

include __DIR__ . '/../includes/header.php';
?>

<!-- Top Breadcrumbs & Record Actions Bar -->
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.25rem; flex-wrap:wrap; gap:0.75rem;">
  <div>
    <a href="<?= base_url('leads/index.php') ?>" style="color:var(--text-muted); font-size:0.85rem; text-decoration:none; display:inline-flex; align-items:center; gap:5px;">
      <i class="fa-solid fa-arrow-left"></i> Back to Leads Directory
    </a>
  </div>

  <div style="display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap;">
    <!-- 1-Click Top Update Button -->
    <button type="button" class="btn btn-sm" onclick="openTopUpdateModal(<?= $lead['id'] ?>)" style="background:linear-gradient(135deg, #059669, #10b981); color:#fff; font-weight:700; padding:0.45rem 1rem; border-radius:8px; border:none; box-shadow:0 2px 6px rgba(16,185,129,0.3); display:inline-flex; align-items:center; gap:6px;">
      <i class="fa-solid fa-arrows-rotate"></i> Update
    </button>

    <!-- Escalate to Counselor Button -->
    <button type="button" class="btn btn-sm" onclick="openEscalateCounselorModal(<?= $lead['id'] ?>, '<?= htmlspecialchars(addslashes($lead['full_name'])) ?>')" style="background:#7c3aed; color:#fff; font-weight:700; padding:0.45rem 0.95rem; border-radius:8px; border:none; display:inline-flex; align-items:center; gap:6px;" title="Route qualified lead to counselor">
      <i class="fa-solid fa-share"></i> Route to Counselor
    </button>

    <!-- Log Call Button -->
    <button type="button" class="btn btn-sm" onclick="openLogCallModal(<?= $lead['id'] ?>, '<?= htmlspecialchars(addslashes($lead['full_name'])) ?>', '<?= htmlspecialchars($lead['phone']) ?>')" style="background:#0284c7; color:#fff; font-weight:700; padding:0.45rem 0.95rem; border-radius:8px; border:none; display:inline-flex; align-items:center; gap:6px;">
      <i class="fa-solid fa-phone"></i> Log Call
    </button>

    <?php if (can('leads', 'edit')): ?>
      <a href="<?= base_url('leads/edit.php?id=' . $lead['id']) ?>" class="btn btn-secondary btn-sm" title="Edit Lead Details">
        <i class="fa-solid fa-pen-to-square"></i> Edit
      </a>
    <?php endif; ?>

    <?php if (can('leads', 'delete')): ?>
      <button type="button" class="btn btn-outline-danger btn-sm" onclick="archiveCurrentLead()" title="Archive Lead">
        <i class="fa-solid fa-box-archive"></i> Archive
      </button>
    <?php endif; ?>
  </div>
</div>

<!-- Main 2-Column Split: Left Profile + Right Workstreams -->
<div style="display:grid; grid-template-columns:330px 1fr; gap:1.5rem; align-items:start;">

  <!-- ==================== LEFT COLUMN: LEAD IDENTITY, PHONE VERIFICATION & REGION ==================== -->
  <div>
    <!-- Primary Identity Card -->
    <div class="card" style="padding:1.5rem; border-top:4px solid var(--primary); margin-bottom:1.25rem;">
      
      <!-- Lead Avatar & Names -->
      <div style="text-align:center; padding-bottom:1.25rem; border-bottom:1px solid var(--border-color);">
        <div style="width:70px; height:70px; border-radius:50%; background:var(--primary-subtle); color:var(--primary); font-size:1.8rem; font-weight:800; display:flex; align-items:center; justify-content:center; margin:0 auto 0.75rem auto;">
          <?= strtoupper(substr($lead['full_name'], 0, 1)) ?>
        </div>
        <h2 style="font-size:1.35rem; font-weight:800; color:var(--text-heading); margin:0 0 0.25rem 0;">
          <?= htmlspecialchars($lead['full_name']) ?>
        </h2>
        
        <?php if (!empty($lead['business_name'])): ?>
          <div style="font-size:0.875rem; font-weight:700; color:#475569; margin-bottom:0.5rem;">
            <i class="fa-solid fa-building"></i> <?= htmlspecialchars($lead['business_name']) ?>
          </div>
        <?php endif; ?>

        <!-- System Identifiers & Badges -->
        <div style="display:flex; justify-content:center; flex-wrap:wrap; gap:0.35rem; margin-bottom:0.75rem;">
          <span class="badge" style="background:#0f172a; color:#fff; font-size:0.72rem; font-weight:700;">
            <?= htmlspecialchars($lead['lead_code']) ?>
          </span>
          <span class="badge <?= ($lead['prospectivity'] === 'Hot') ? 'badge-danger' : (($lead['prospectivity'] === 'Cold') ? 'badge-secondary' : 'badge-warning') ?>" style="font-size:0.72rem; font-weight:700;">
            🔥 <?= htmlspecialchars($lead['prospectivity'] ?? 'Warm') ?>
          </span>
          <span class="badge badge-primary" style="font-size:0.72rem; font-weight:700;">
            <?= htmlspecialchars($lead['active_status'] ?? 'New Lead') ?>
          </span>
        </div>

        <?php if ($isCustomer): ?>
          <div style="background:#dcfce7; color:#15803d; border-radius:8px; padding:0.4rem 0.6rem; font-size:0.8rem; font-weight:700;">
            <i class="fa-solid fa-circle-check"></i> Converted Customer
          </div>
        <?php else: ?>
          <a href="<?= base_url('finance/create_invoice.php?lead_id=' . $lead['id']) ?>" class="btn btn-success btn-sm" style="width:100%; font-weight:700; padding:0.45rem; display:flex; align-items:center; justify-content:center; gap:6px;">
            <i class="fa-solid fa-arrow-right-to-bracket"></i> Convert to Customer
          </a>
        <?php endif; ?>
      </div>

      <!-- Customer Contact Verification & Phone Cross-Checking Panel (Client Requirement) -->
      <div style="padding:1.15rem 0; border-bottom:1px solid var(--border-color);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem;">
          <span style="font-size:0.72rem; color:var(--text-muted); text-transform:uppercase; font-weight:800; letter-spacing:0.04em;">Phone Verification</span>
          <?php if (!empty($lead['is_phone_verified'])): ?>
            <span id="phoneVerifiedBadge" class="badge badge-success" style="font-size:0.7rem; font-weight:700;">
              <i class="fa-solid fa-circle-check"></i> Verified
            </span>
          <?php else: ?>
            <span id="phoneVerifiedBadge" class="badge badge-warning" style="font-size:0.7rem; font-weight:700;">
              <i class="fa-solid fa-triangle-exclamation"></i> Pending Cross-Check
            </span>
          <?php endif; ?>
        </div>

        <!-- Phone Number Read Aloud Format ("8-7-8-7-9-4-6-8-9-6") -->
        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:0.75rem; text-align:center; margin-bottom:0.6rem;">
          <div style="font-family:monospace; font-size:1.1rem; font-weight:800; color:var(--text-heading); letter-spacing:0.06em;">
            <?= htmlspecialchars($spacedDigits) ?>
          </div>
          <span style="font-size:0.7rem; color:var(--text-muted); display:block; margin-top:2px;">Digit-by-digit format for cross-checking</span>
        </div>

        <!-- Action: Read Aloud & Verify -->
        <div style="display:flex; gap:0.4rem; justify-content:space-between;">
          <button type="button" class="btn btn-outline btn-sm" onclick="speakPhoneDigits('<?= $rawPhoneDigits ?>')" style="flex:1; font-size:0.75rem; font-weight:700; display:inline-flex; align-items:center; justify-content:center; gap:4px;" title="Read aloud digit-by-digit">
            <i class="fa-solid fa-volume-high" style="color:var(--primary);"></i> Read Aloud
          </button>
          
          <button type="button" id="toggleVerifyBtn" class="btn <?= !empty($lead['is_phone_verified']) ? 'btn-secondary' : 'btn-primary' ?> btn-sm" onclick="togglePhoneVerification(<?= $lead['id'] ?>)" style="flex:1; font-size:0.75rem; font-weight:700; display:inline-flex; align-items:center; justify-content:center; gap:4px;">
            <i class="fa-solid fa-check"></i> <?= !empty($lead['is_phone_verified']) ? 'Reset' : 'Verify Phone' ?>
          </button>
        </div>

        <div style="display:flex; gap:0.4rem; margin-top:0.5rem;">
          <a href="tel:<?= htmlspecialchars($lead['phone']) ?>" class="btn btn-secondary btn-sm" style="flex:1; font-size:0.75rem; padding:4px;" title="Call Phone">
            <i class="fa-solid fa-phone"></i> Call
          </a>
          <a href="https://wa.me/91<?= normalize_phone($lead['phone']) ?>" target="_blank" class="btn btn-secondary btn-sm" style="flex:1; font-size:0.75rem; padding:4px; color:#16a34a;" title="WhatsApp">
            <i class="fa-brands fa-whatsapp"></i> WhatsApp
          </a>
        </div>
      </div>

      <!-- Owner & Business Record Completeness (% Bar) -->
      <div style="padding:1rem 0; border-bottom:1px solid var(--border-color);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
          <span style="font-size:0.72rem; color:var(--text-muted); text-transform:uppercase; font-weight:800; letter-spacing:0.04em;">Record Completeness</span>
          <strong style="font-size:0.8rem; color:var(--primary);"><?= (int)($lead['record_completeness'] ?? 85) ?>%</strong>
        </div>
        <div style="height:8px; background:#e2e8f0; border-radius:4px; overflow:hidden;">
          <div style="height:100%; width:<?= (int)($lead['record_completeness'] ?? 85) ?>%; background:linear-gradient(90deg, #10b981, #059669);"></div>
        </div>
        <span style="font-size:0.7rem; color:var(--text-muted); display:block; margin-top:4px;">
          Business profile, contact coordinates & regional mapping
        </span>
      </div>

      <!-- Regional & Geographic Segmentation Info -->
      <div style="padding:1rem 0 0 0; display:flex; flex-direction:column; gap:0.65rem; font-size:0.825rem;">
        <div>
          <span style="font-size:0.7rem; color:var(--text-muted); text-transform:uppercase; font-weight:800; display:block;">Region Identifier</span>
          <div style="display:flex; align-items:center; gap:6px; margin-top:2px;">
            <span class="badge" style="background:#334155; color:#fff; font-size:0.75rem; font-weight:800;"><?= htmlspecialchars($lead['region_number'] ?? 'REG-01') ?></span>
            <strong style="color:var(--text-heading);"><?= htmlspecialchars($lead['main_region'] ?? 'Central Zone') ?></strong>
          </div>
        </div>

        <div>
          <span style="font-size:0.7rem; color:var(--text-muted); text-transform:uppercase; font-weight:800; display:block;">Sub-Area & Zone Type</span>
          <div style="display:flex; align-items:center; gap:6px; margin-top:2px;">
            <span style="color:var(--text-heading); font-weight:600;"><?= htmlspecialchars($lead['sub_area'] ?? 'Downtown Central') ?></span>
            <span class="badge <?= ($lead['zone_type'] === 'Center') ? 'badge-primary' : 'badge-secondary' ?>" style="font-size:0.68rem;">
              <?= htmlspecialchars($lead['zone_type'] ?? 'Center') ?>
            </span>
          </div>
        </div>

        <div>
          <span style="font-size:0.7rem; color:var(--text-muted); text-transform:uppercase; font-weight:800; display:block;">Assigned Counselor / Advisor</span>
          <strong style="color:var(--text-heading);"><?= htmlspecialchars($lead['counselor_name'] ?? 'Unassigned') ?></strong>
        </div>

        <div>
          <span style="font-size:0.7rem; color:var(--text-muted); text-transform:uppercase; font-weight:800; display:block;">Lead Source</span>
          <span style="color:var(--text-heading); font-weight:600;"><?= htmlspecialchars($lead['source'] ?? 'Social Media (Meta Ads)') ?></span>
        </div>
      </div>

    </div>
  </div>

  <!-- ==================== RIGHT COLUMN: 3 WORKSTREAM TABS (Calls, Follow-ups, Commercial) ==================== -->
  <div>
    <!-- Workstream Tab Navigation -->
    <div style="display:flex; gap:0.35rem; border-bottom:2px solid var(--border-color); margin-bottom:1.5rem; background:#fff; padding:0.5rem 0.5rem 0 0.5rem; border-radius:10px 10px 0 0; box-shadow:0 1px 3px rgba(0,0,0,0.03);">
      <button type="button" onclick="switchWorkTab('calls')" id="tab-btn-calls" class="lead-nav-tab <?= $activeTab === 'calls' ? 'active' : '' ?>">
        <i class="fa-solid fa-phone-volume"></i> Call Data Center
        <span class="badge <?= count($callLogs) > 0 ? 'badge-primary' : 'badge-secondary' ?>" style="font-size:0.7rem; margin-left:4px;"><?= count($callLogs) ?></span>
      </button>

      <button type="button" onclick="switchWorkTab('followups')" id="tab-btn-followups" class="lead-nav-tab <?= $activeTab === 'followups' ? 'active' : '' ?>">
        <i class="fa-solid fa-clock-rotate-left"></i> 4-Stage Follow-ups
        <span class="badge <?= count($followups) > 0 ? 'badge-primary' : 'badge-secondary' ?>" style="font-size:0.7rem; margin-left:4px;"><?= count($followups) ?></span>
      </button>

      <button type="button" onclick="switchWorkTab('commercial')" id="tab-btn-commercial" class="lead-nav-tab <?= $activeTab === 'commercial' ? 'active' : '' ?>">
        <i class="fa-solid fa-file-invoice-dollar"></i> Commercial & Invoices
        <span class="badge <?= count($invoices) > 0 ? 'badge-primary' : 'badge-secondary' ?>" style="font-size:0.7rem; margin-left:4px;"><?= count($invoices) ?></span>
      </button>
    </div>

    <!-- ==================== PANE 1: CALL DATA CENTER ==================== -->
    <div id="pane-calls" class="lead-tab-pane <?= $activeTab === 'calls' ? 'active' : '' ?>">
      
      <!-- Quick Call Entry Box -->
      <div class="card" style="margin-bottom:1.25rem; padding:1.25rem; border-radius:12px; background:#f8fafc; border:1px solid #e2e8f0;">
        <h4 style="font-size:0.95rem; font-weight:800; color:var(--text-heading); margin:0 0 0.85rem 0; display:flex; align-items:center; gap:6px;">
          <i class="fa-solid fa-phone-plus" style="color:#0284c7;"></i> Log Call Interaction
        </h4>

        <form method="POST" action="<?= base_url('leads/update_meta.php') ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="field" value="log_call">
          <input type="hidden" name="lead_id" value="<?= $lead['id'] ?>">
          <input type="hidden" name="phone_dialed" value="<?= htmlspecialchars($lead['phone']) ?>">
          <input type="hidden" name="return_url" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">

          <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:0.75rem; margin-bottom:0.75rem;">
            <div>
              <label class="form-label" style="font-size:0.78rem; font-weight:700;">Direction</label>
              <select name="call_direction" class="form-control" style="font-size:0.825rem;">
                <option value="Outbound">Outbound (Agent Dialed)</option>
                <option value="Inbound">Inbound (Client Called)</option>
              </select>
            </div>
            <div>
              <label class="form-label" style="font-size:0.78rem; font-weight:700;">Call Status</label>
              <select name="call_status" class="form-control" style="font-size:0.825rem;">
                <option value="Connected">Connected & Spoke</option>
                <option value="Busy">Line Busy</option>
                <option value="No Answer">No Answer / Ringing</option>
                <option value="Left Voicemail">Left Voicemail</option>
                <option value="Switched Off">Switched Off</option>
              </select>
            </div>
            <div>
              <label class="form-label" style="font-size:0.78rem; font-weight:700;">Duration (Seconds)</label>
              <input type="number" name="duration_seconds" class="form-control" value="60" min="0" style="font-size:0.825rem;">
            </div>
          </div>

          <div style="margin-bottom:0.75rem;">
            <label class="form-label" style="font-size:0.78rem; font-weight:700;">Call Notes & Discussion Summary</label>
            <textarea name="notes" class="form-control" rows="2" placeholder="Record discussion details..." required style="font-size:0.85rem;"></textarea>
          </div>

          <div style="display:flex; justify-content:flex-end;">
            <button type="submit" class="btn btn-primary btn-sm" style="background:#0284c7; border-color:#0284c7; font-weight:700; padding:0.45rem 1.25rem;">
              <i class="fa-solid fa-floppy-disk"></i> Save Call Log
            </button>
          </div>
        </form>
      </div>

      <!-- Call Logs History Table -->
      <div class="card" style="padding:1.25rem; border-radius:12px;">
        <h4 style="font-size:0.95rem; font-weight:800; color:var(--text-heading); margin:0 0 1rem 0;">
          <i class="fa-solid fa-list" style="color:var(--primary); margin-right:4px;"></i> Call History Timeline
        </h4>

        <?php if (empty($callLogs)): ?>
          <div style="text-align:center; padding:2rem; color:var(--text-muted); font-size:0.85rem;">
            <i class="fa-solid fa-phone-slash" style="font-size:1.8rem; color:var(--text-light); margin-bottom:0.5rem; display:block;"></i>
            No call interactions logged yet for this contact.
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Date & Time</th>
                  <th>Direction</th>
                  <th>Outcome Status</th>
                  <th>Duration</th>
                  <th>Agent / Caller</th>
                  <th>Discussion Notes</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($callLogs as $cl): 
                  $statusBg = ($cl['call_status'] === 'Connected') ? '#dcfce7' : (($cl['call_status'] === 'Busy') ? '#fef3c7' : '#fee2e2');
                  $statusColor = ($cl['call_status'] === 'Connected') ? '#15803d' : (($cl['call_status'] === 'Busy') ? '#b45309' : '#b91c1c');
                ?>
                  <tr>
                    <td style="font-size:0.8rem; font-weight:600; white-space:nowrap;">
                      <?= date('d M Y, h:i A', strtotime($cl['created_at'])) ?>
                    </td>
                    <td>
                      <span class="badge" style="background:<?= $cl['call_direction'] === 'Inbound' ? '#dbeafe' : '#f1f5f9' ?>; color:<?= $cl['call_direction'] === 'Inbound' ? '#1e40af' : '#334155' ?>; font-size:0.72rem; font-weight:700;">
                        <i class="fa-solid <?= $cl['call_direction'] === 'Inbound' ? 'fa-arrow-down-left' : 'fa-arrow-up-right' ?>"></i> <?= htmlspecialchars($cl['call_direction']) ?>
                      </span>
                    </td>
                    <td>
                      <span class="badge" style="background:<?= $statusBg ?>; color:<?= $statusColor ?>; font-size:0.72rem; font-weight:700;">
                        <?= htmlspecialchars($cl['call_status']) ?>
                      </span>
                    </td>
                    <td style="font-size:0.8rem; font-weight:600;">
                      <?= $cl['duration_seconds'] ?>s
                    </td>
                    <td style="font-size:0.8rem; color:var(--text-heading); font-weight:600;">
                      <?= htmlspecialchars($cl['caller_name'] ?? 'System') ?>
                    </td>
                    <td style="font-size:0.825rem; color:#334155; line-height:1.4;">
                      <?= nl2br(htmlspecialchars($cl['notes'] ?: '—')) ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

    </div>

    <!-- ==================== PANE 2: 4-STAGE FOLLOW-UP MANAGEMENT ==================== -->
    <div id="pane-followups" class="lead-tab-pane <?= $activeTab === 'followups' ? 'active' : '' ?>">
      
      <!-- 4 Stages Lifecycle Indicator Header -->
      <div style="display:grid; grid-template-columns:repeat(4, 1fr); gap:0.65rem; margin-bottom:1.25rem;">
        <div style="background:#ecfdf5; border:1px solid #a7f3d0; border-radius:8px; padding:0.75rem; text-align:center;">
          <span style="font-size:0.68rem; font-weight:800; color:#047857; text-transform:uppercase;">Stage 1</span>
          <div style="font-size:0.85rem; font-weight:800; color:#064e3b; margin-top:2px;">New / First</div>
        </div>
        <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:0.75rem; text-align:center;">
          <span style="font-size:0.68rem; font-weight:800; color:#1d4ed8; text-transform:uppercase;">Stage 2</span>
          <div style="font-size:0.85rem; font-weight:800; color:#1e3a8a; margin-top:2px;">Scheduled</div>
        </div>
        <div style="background:#fef2f2; border:1px solid #fecaca; border-radius:8px; padding:0.75rem; text-align:center;">
          <span style="font-size:0.68rem; font-weight:800; color:#b91c1c; text-transform:uppercase;">Stage 3</span>
          <div style="font-size:0.85rem; font-weight:800; color:#7f1d1d; margin-top:2px;">Pending Backlog</div>
        </div>
        <div style="background:#fdf4ff; border:1px solid #f5d0fe; border-radius:8px; padding:0.75rem; text-align:center;">
          <span style="font-size:0.68rem; font-weight:800; color:#a21caf; text-transform:uppercase;">Stage 4</span>
          <div style="font-size:0.85rem; font-weight:800; color:#701a75; margin-top:2px;">Long-Cycle</div>
        </div>
      </div>

      <!-- Schedule Follow-up Box -->
      <div class="card" style="margin-bottom:1.25rem; padding:1.25rem; border-radius:12px; background:#fff;">
        <h4 style="font-size:0.95rem; font-weight:800; color:var(--text-heading); margin:0 0 0.85rem 0;">
          <i class="fa-solid fa-calendar-plus" style="color:var(--primary); margin-right:4px;"></i> Schedule Follow-Up & Set Reminder Alarm
        </h4>

        <form method="POST" action="<?= base_url('followups/add.php') ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="lead_id" value="<?= $lead['id'] ?>">
          <input type="hidden" name="return_url" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">

          <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:0.75rem; margin-bottom:0.75rem;">
            <div>
              <label class="form-label" style="font-size:0.78rem; font-weight:700;">Interaction Title</label>
              <select name="interaction_title_id" class="form-control" style="font-size:0.825rem;" required>
                <?php foreach ($interactionTitles as $it): ?>
                  <option value="<?= $it['id'] ?>"><?= htmlspecialchars($it['title']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label class="form-label" style="font-size:0.78rem; font-weight:700;">Scheduled Date</label>
              <input type="date" name="scheduled_date" class="form-control" value="<?= date('Y-m-d', strtotime('+1 day')) ?>" required style="font-size:0.825rem;">
            </div>
            <div>
              <label class="form-label" style="font-size:0.78rem; font-weight:700;">Scheduled Time</label>
              <input type="time" name="scheduled_time" class="form-control" value="10:00" style="font-size:0.825rem;">
            </div>
          </div>

          <div style="margin-bottom:0.75rem;">
            <label class="form-label" style="font-size:0.78rem; font-weight:700;">Action Plan & Notes</label>
            <textarea name="notes" class="form-control" rows="2" placeholder="Agreed follow-up points..." required style="font-size:0.85rem;"></textarea>
          </div>

          <div style="display:flex; justify-content:flex-end;">
            <button type="submit" class="btn btn-primary btn-sm" style="font-weight:700; padding:0.45rem 1.25rem;">
              <i class="fa-solid fa-calendar-check"></i> Book Scheduled Follow-Up
            </button>
          </div>
        </form>
      </div>

      <!-- Follow-up History -->
      <div class="card" style="padding:1.25rem; border-radius:12px;">
        <h4 style="font-size:0.95rem; font-weight:800; color:var(--text-heading); margin:0 0 1rem 0;">
          <i class="fa-solid fa-history" style="color:var(--primary); margin-right:4px;"></i> Follow-Up Activity Logs
        </h4>

        <?php if (empty($followups)): ?>
          <div style="text-align:center; padding:2rem; color:var(--text-muted); font-size:0.85rem;">
            <i class="fa-solid fa-calendar-xmark" style="font-size:1.8rem; color:var(--text-light); margin-bottom:0.5rem; display:block;"></i>
            No previous follow-ups recorded yet.
          </div>
        <?php else: ?>
          <div style="display:flex; flex-direction:column; gap:0.75rem;">
            <?php foreach ($followups as $f): ?>
              <div style="border:1px solid #e2e8f0; border-radius:10px; padding:0.85rem 1rem; background:#f8fafc;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                  <strong style="color:var(--text-heading); font-size:0.875rem;">
                    <?= htmlspecialchars($f['interaction_title'] ?: 'Consultation Interaction') ?>
                  </strong>
                  <span class="badge <?= $f['status'] === 'Completed' ? 'badge-success' : 'badge-warning' ?>" style="font-size:0.72rem;">
                    <?= htmlspecialchars($f['status']) ?>
                  </span>
                </div>
                <div style="font-size:0.78rem; color:var(--text-muted); margin-bottom:4px;">
                  Date: <?= date('d M Y', strtotime($f['scheduled_date'])) ?> &bull; Assigned: <?= htmlspecialchars($f['counselor_name'] ?? 'Staff') ?>
                </div>
                <div style="font-size:0.825rem; color:#334155;">
                  <?= nl2br(htmlspecialchars($f['notes'])) ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

    </div>

    <!-- ==================== PANE 3: COMMERCIAL & INVOICES ==================== -->
    <div id="pane-commercial" class="lead-tab-pane <?= $activeTab === 'commercial' ? 'active' : '' ?>">
      <div class="card" style="padding:1.5rem; border-radius:12px; margin-bottom:1.25rem;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
          <div>
            <h4 style="font-size:1.05rem; font-weight:800; color:var(--text-heading); margin:0;">
              Commercial Invoices & Accounts
            </h4>
            <span style="font-size:0.78rem; color:var(--text-muted);">Client service vouchers, fee schedules, and payment vouchers</span>
          </div>
          <?php if (can('invoices', 'add')): ?>
            <a href="<?= base_url('finance/create_invoice.php?lead_id=' . $lead['id']) ?>" class="btn btn-primary btn-sm" style="font-weight:700;">
              <i class="fa-solid fa-plus"></i> Create Invoice
            </a>
          <?php endif; ?>
        </div>

        <?php if (empty($invoices)): ?>
          <div style="text-align:center; padding:2.5rem; color:var(--text-muted); font-size:0.85rem;">
            <i class="fa-solid fa-file-invoice" style="font-size:2rem; color:var(--text-light); margin-bottom:0.5rem; display:block;"></i>
            No invoices generated for this account yet. Click "Convert to Customer" or "Create Invoice" to generate commercial retainer.
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Invoice #</th>
                  <th>Date</th>
                  <th>Grand Total</th>
                  <th>Paid Amount</th>
                  <th>Balance Due</th>
                  <th>Status</th>
                  <th style="text-align:right;">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($invoices as $inv): ?>
                  <tr>
                    <td>
                      <a href="<?= base_url('finance/view_invoice.php?id=' . $inv['id']) ?>" style="font-weight:700; color:var(--primary); text-decoration:none;">
                        <?= htmlspecialchars($inv['invoice_number']) ?>
                      </a>
                    </td>
                    <td style="font-size:0.8rem;"><?= date('d M Y', strtotime($inv['invoice_date'])) ?></td>
                    <td style="font-weight:700; font-size:0.85rem;">₹<?= number_format($inv['grand_total'], 2) ?></td>
                    <td style="color:#15803d; font-weight:700; font-size:0.85rem;">₹<?= number_format($inv['paid_total'] ?? $inv['paid_amount'], 2) ?></td>
                    <td style="color:#b91c1c; font-weight:700; font-size:0.85rem;">₹<?= number_format($inv['balance_total'] ?? $inv['balance_due'], 2) ?></td>
                    <td>
                      <span class="badge <?= $inv['payment_status'] === 'Paid' ? 'badge-success' : 'badge-warning' ?>" style="font-size:0.72rem;">
                        <?= htmlspecialchars($inv['payment_status']) ?>
                      </span>
                    </td>
                    <td style="text-align:right;">
                      <a href="<?= base_url('finance/view_invoice.php?id=' . $inv['id']) ?>" class="btn btn-secondary btn-sm" style="font-size:0.75rem; padding:3px 8px;">View &rarr;</a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<!-- ==================== BOTTOM / DETAILS PANEL (Client Requirement 3: "This is the down. The key and the status of the model. All options") ==================== -->
<div class="card" style="margin-top:1.5rem; padding:1.5rem; border-radius:14px; border-top:4px solid #334155; background:#fff;">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.25rem; flex-wrap:wrap; gap:0.5rem;">
    <div>
      <h3 style="font-size:1.1rem; font-weight:800; color:var(--text-heading); margin:0; display:flex; align-items:center; gap:8px;">
        <i class="fa-solid fa-sliders" style="color:var(--primary);"></i> Key & Status Model Controls (All Options)
      </h3>
      <span style="font-size:0.78rem; color:var(--text-muted);">
        Master controls for record status, prospectivity grading, geographic zone type, and operational parameters
      </span>
    </div>
    <span class="badge" style="background:#0f172a; color:#fff; font-size:0.75rem; font-weight:700;">Bottom Details Panel</span>
  </div>

  <form method="POST" action="<?= base_url('leads/update_meta.php') ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="field" value="top_update">
    <input type="hidden" name="lead_id" value="<?= $lead['id'] ?>">
    <input type="hidden" name="return_url" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">

    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:1rem; margin-bottom:1.25rem;">
      <!-- Option 1: Active Status Model -->
      <div class="form-group" style="margin:0;">
        <label class="form-label" style="font-weight:700; font-size:0.825rem;">Active Status Model</label>
        <select name="active_status" class="form-control" style="font-size:0.85rem; font-weight:600;">
          <?php 
          $statusOptions = ['New Lead', 'Call In Progress', 'Contacted', 'Follow-up Scheduled', 'Prospect Qualified', 'Escalated to Counselor', 'Converted', 'Disqualified'];
          foreach ($statusOptions as $opt): 
          ?>
            <option value="<?= $opt ?>" <?= ($lead['active_status'] === $opt) ? 'selected' : '' ?>><?= $opt ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Option 2: Prospectivity Model -->
      <div class="form-group" style="margin:0;">
        <label class="form-label" style="font-weight:700; font-size:0.825rem;">Prospectivity Tier</label>
        <select name="prospectivity" class="form-control" style="font-size:0.85rem; font-weight:700;">
          <option value="Hot" <?= ($lead['prospectivity'] === 'Hot') ? 'selected' : '' ?>>🔥 Hot (High Conversion Prospect)</option>
          <option value="Warm" <?= ($lead['prospectivity'] === 'Warm' || empty($lead['prospectivity'])) ? 'selected' : '' ?>>⚡ Warm (Active Follow-up)</option>
          <option value="Cold" <?= ($lead['prospectivity'] === 'Cold') ? 'selected' : '' ?>>💤 Cold (Low Immediate Prospect)</option>
        </select>
      </div>

      <!-- Option 3: Regional Zone Type -->
      <div class="form-group" style="margin:0;">
        <label class="form-label" style="font-weight:700; font-size:0.825rem;">Geographic Zone Type</label>
        <select name="zone_type" class="form-control" style="font-size:0.85rem; font-weight:600;">
          <option value="Center" <?= ($lead['zone_type'] === 'Center') ? 'selected' : '' ?>>Center (High Prospectivity Zone)</option>
          <option value="Sub-area" <?= ($lead['zone_type'] === 'Sub-area') ? 'selected' : '' ?>>Sub-area (Secondary Zone)</option>
          <option value="Outer Zone" <?= ($lead['zone_type'] === 'Outer Zone') ? 'selected' : '' ?>>Outer Zone (Regional Expansion)</option>
        </select>
      </div>

      <!-- Option 4: Lifecycle Cycle Type -->
      <div class="form-group" style="margin:0; display:flex; flex-direction:column; justify-content:center;">
        <label style="display:flex; align-items:center; gap:8px; font-size:0.825rem; font-weight:700; color:var(--text-heading); cursor:pointer;">
          <input type="checkbox" name="is_long_cycle" value="1" <?= !empty($lead['is_long_cycle']) ? 'checked' : '' ?>>
          <span>Long-Cycle Follow-Up</span>
        </label>
        <span style="font-size:0.72rem; color:var(--text-muted); margin-top:2px;">Check if lead is undergoing prolonged multi-month cycle</span>
      </div>
    </div>

    <!-- Metadata Details Table -->
    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:1rem; margin-bottom:1.25rem;">
      <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:0.85rem; font-size:0.8rem;">
        <div>
          <span style="color:var(--text-muted); font-size:0.72rem; text-transform:uppercase; font-weight:700; display:block;">Creation Date</span>
          <strong><?= date('d M Y, h:i A', strtotime($lead['created_at'])) ?></strong>
        </div>
        <div>
          <span style="color:var(--text-muted); font-size:0.72rem; text-transform:uppercase; font-weight:700; display:block;">Created By</span>
          <strong><?= htmlspecialchars($lead['created_by_name'] ?? 'System Ingestion') ?></strong>
        </div>
        <div>
          <span style="color:var(--text-muted); font-size:0.72rem; text-transform:uppercase; font-weight:700; display:block;">Last Updated</span>
          <strong><?= !empty($lead['updated_at']) ? date('d M Y, h:i A', strtotime($lead['updated_at'])) : '—' ?></strong>
        </div>
        <div>
          <span style="color:var(--text-muted); font-size:0.72rem; text-transform:uppercase; font-weight:700; display:block;">Verified By</span>
          <strong><?= htmlspecialchars($lead['verified_by_name'] ?? 'Pending') ?></strong>
        </div>
      </div>
    </div>

    <div style="display:flex; justify-content:space-between; align-items:center;">
      <span style="font-size:0.75rem; color:var(--text-muted);">
        Saving updates all status models and broadcasts changes to counselor worklists.
      </span>
      <button type="submit" class="btn btn-primary btn-sm" style="font-weight:700; padding:0.5rem 1.5rem;">
        <i class="fa-solid fa-floppy-disk"></i> Save Model Changes
      </button>
    </div>
  </form>
</div>

<!-- Page Interactions Script -->
<script>
function switchWorkTab(tabId) {
  document.querySelectorAll('.lead-tab-pane').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.lead-nav-tab').forEach(b => b.classList.remove('active'));

  const pane = document.getElementById('pane-' + tabId);
  const btn = document.getElementById('tab-btn-' + tabId);
  if (pane) pane.classList.add('active');
  if (btn) btn.classList.add('active');
}

// Speak phone digits aloud via browser Web Speech Synthesis
function speakPhoneDigits(digits) {
  if (!('speechSynthesis' in window)) {
    alert('Phone: ' + digits.split('').join(' - '));
    return;
  }
  window.speechSynthesis.cancel();
  const digitWords = digits.split('').join(', ');
  const utterance = new SpeechSynthesisUtterance('Phone number: ' + digitWords);
  utterance.rate = 0.85;
  utterance.pitch = 1.0;
  window.speechSynthesis.speak(utterance);
}

// Toggle Phone Verification via AJAX
function togglePhoneVerification(leadId) {
  const btn = document.getElementById('toggleVerifyBtn');
  const badge = document.getElementById('phoneVerifiedBadge');
  if (!btn) return;

  const originalHtml = btn.innerHTML;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
  btn.disabled = true;

  const formData = new FormData();
  formData.append('field', 'phone_verify');
  formData.append('lead_id', leadId);
  formData.append('csrf_token', '<?= csrf_token() ?>');

  fetch('<?= base_url('leads/update_meta.php') ?>', {
    method: 'POST',
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    btn.disabled = false;
    if (data.success) {
      if (data.is_verified) {
        btn.className = 'btn btn-secondary btn-sm';
        btn.innerHTML = '<i class="fa-solid fa-check"></i> Reset';
        if (badge) {
          badge.className = 'badge badge-success';
          badge.innerHTML = '<i class="fa-solid fa-circle-check"></i> Verified';
        }
      } else {
        btn.className = 'btn btn-primary btn-sm';
        btn.innerHTML = '<i class="fa-solid fa-check"></i> Verify Phone';
        if (badge) {
          badge.className = 'badge badge-warning';
          badge.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Pending Cross-Check';
        }
      }
    } else {
      btn.innerHTML = originalHtml;
      alert(data.message || 'Verification update failed.');
    }
  })
  .catch(err => {
    btn.disabled = false;
    btn.innerHTML = originalHtml;
    alert('Verification request error.');
  });
}

function archiveCurrentLead() {
  if (confirm('Are you sure you want to archive this lead record?')) {
    window.location.href = '<?= base_url('archive/action.php?action=archive_lead&id=' . $lead['id'] . '&csrf_token=' . csrf_token()) ?>';
  }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
