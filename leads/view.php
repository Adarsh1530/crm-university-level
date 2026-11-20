<?php
/**
 * Authentic Student Lead Details & Customer Record
 * Aligned 1:1 with University CRM Specification & User Manual (Section 9.3)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_permission('leads', 'view');

$leadId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$currentUser = current_user();
$activeTab = trim($_GET['tab'] ?? 'details');

if ($leadId <= 0 || !can_access_lead($leadId)) {
    // Graceful fallback: Pick the first active lead accessible to current user
    $scopeClause = get_crm_scope_clause('l', 'assigned_counselor_id', 'branch_id');
    $firstStmt = $pdo->query("SELECT id FROM leads l WHERE l.is_archived = 0 AND {$scopeClause} ORDER BY id ASC LIMIT 1");
    $firstRow = $firstStmt ? $firstStmt->fetch() : null;
    if ($firstRow && !empty($firstRow['id'])) {
        $leadId = (int)$firstRow['id'];
    } else {
        set_flash('danger', 'Access Denied: You cannot access this student record.');
        redirect('leads/index.php');
    }
}

// Fetch Lead Details
$stmt = $pdo->prepare("
    SELECT l.*, b.name AS branch_name, b.code AS branch_code, b.city AS branch_city,
           u.name AS counselor_name, u.email AS counselor_email, u.phone AS counselor_phone,
           cb.name AS created_by_name
    FROM leads l
    LEFT JOIN branches b ON l.branch_id = b.id
    LEFT JOIN users u ON l.assigned_counselor_id = u.id
    LEFT JOIN users cb ON l.created_by = cb.id
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
$interactionTitles = $pdo->query("SELECT * FROM interaction_titles WHERE status = 'Active' ORDER BY sort_order ASC")->fetchAll();

// Historical Follow-ups (Section 9.3.4)
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

// Invoices for this student (Section 9.3.5)
$invStmt = $pdo->prepare("
    SELECT i.*, u.name AS created_by_name
    FROM invoices i
    LEFT JOIN users u ON i.created_by = u.id
    WHERE i.lead_id = ?
    ORDER BY i.id DESC
");
$invStmt->execute([$leadId]);
$invoices = $invStmt->fetchAll();

// Payments for this student (Section 9.3.6)
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
} catch (Exception $payEx) {
    $payments = [];
}

// Calculate Customer Status
$isCustomer = ($lead['stage'] === 'CUSTOMER' || !empty($lead['converted_at']) || !empty($invoices));

include __DIR__ . '/../includes/header.php';
?>

<!-- Breadcrumbs & Quick Back -->
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.25rem;">
  <div>
    <a href="<?= base_url('leads/index.php') ?>" style="color:var(--text-muted); font-size:0.85rem; text-decoration:none; display:inline-flex; align-items:center; gap:5px;">
      <i class="fa-solid fa-arrow-left"></i> Back to Leads Directory
    </a>
  </div>
  <div style="display:flex; gap:0.5rem;">
    <?php if (can('leads', 'edit')): ?>
      <a href="<?= base_url('leads/edit.php?id=' . $lead['id']) ?>" class="btn btn-secondary btn-sm" title="Edit Lead Details">
        <i class="fa-solid fa-pen-to-square"></i> Edit Lead
      </a>
    <?php endif; ?>
    <?php if (can('leads', 'delete')): ?>
      <button type="button" class="btn btn-outline-danger btn-sm" onclick="archiveCurrentLead()" title="Archive Student">
        <i class="fa-solid fa-box-archive"></i> Archive
      </button>
    <?php endif; ?>
  </div>
</div>

<!-- Main 2-Column Split: Left Profile + Right Tabs -->
<div style="display:grid; grid-template-columns:320px 1fr; gap:1.5rem; align-items:start;">

  <!-- ==================== LEFT COLUMN: LEAD PROFILE CARD (Figures p14, p15) ==================== -->
  <div>
    <div class="card" style="padding:1.5rem; border-top:4px solid var(--primary); position:sticky; top:1.5rem;">
      
      <!-- Student Avatar & Primary Identification -->
      <div style="text-align:center; padding-bottom:1.25rem; border-bottom:1px solid var(--border-color);">
        <div style="width:72px; height:72px; border-radius:50%; background:var(--primary-subtle); color:var(--primary); font-size:1.8rem; font-weight:800; display:flex; align-items:center; justify-content:center; margin:0 auto 0.75rem auto;">
          <?= strtoupper(substr($lead['full_name'], 0, 1)) ?>
        </div>
        <h2 style="font-size:1.35rem; font-weight:800; color:var(--text-heading); margin:0 0 0.35rem 0;">
          <?= htmlspecialchars($lead['full_name']) ?>
        </h2>
        
        <!-- Status & System Badges -->
        <div style="display:flex; justify-content:center; flex-wrap:wrap; gap:0.35rem; margin-bottom:0.75rem;">
          <span class="badge" style="background:#0f172a; color:#fff; font-size:0.72rem; font-weight:700;">
            Reg.# <?= htmlspecialchars($lead['lead_code']) ?>
          </span>
          <?php if ($isCustomer): ?>
            <span class="badge badge-success" style="font-size:0.72rem; font-weight:700;">
              <i class="fa-solid fa-check"></i> Customer
            </span>
          <?php else: ?>
            <span class="badge badge-primary" style="font-size:0.72rem; font-weight:700;">
              Lead
            </span>
          <?php endif; ?>
          <span class="badge badge-secondary" style="font-size:0.72rem;">
            <?= htmlspecialchars($lead['source'] ?: 'TELE') ?>
          </span>
          <span class="badge badge-secondary" style="font-size:0.72rem;">
            <?= htmlspecialchars($lead['gender'] ?? 'Male') ?>
          </span>
        </div>

        <!-- Section 9.3.2: Convert to Customer Button -->
        <?php if (!$isCustomer): ?>
          <a href="<?= base_url('finance/create_invoice.php?lead_id=' . $lead['id']) ?>" class="btn btn-success btn-sm" style="width:100%; font-weight:700; padding:0.55rem; display:flex; align-items:center; justify-content:center; gap:6px; box-shadow:0 2px 6px rgba(22,163,74,0.25);" title="Create Invoice to Convert Lead to Customer">
            <i class="fa-solid fa-arrow-right-to-bracket"></i> Convert to Customer
          </a>
        <?php else: ?>
          <div style="background:#dcfce7; color:#15803d; border-radius:6px; padding:0.4rem 0.6rem; font-size:0.78rem; font-weight:700;">
            <i class="fa-solid fa-circle-check"></i> Enrolled Customer
          </div>
        <?php endif; ?>
      </div>

      <!-- Quick Assessment Status Table (Matching PDF Figure p14) -->
      <div style="padding:1rem 0; border-bottom:1px solid var(--border-color);">
        <table style="width:100%; border-collapse:collapse; font-size:0.8rem; text-align:left;">
          <thead>
            <tr style="color:var(--text-muted); font-size:0.72rem; text-transform:uppercase;">
              <th style="padding:4px 6px; border:1px solid var(--border-color); background:var(--bg-subtle);">Have Pan Card?</th>
              <th style="padding:4px 6px; border:1px solid var(--border-color); background:var(--bg-subtle);">IELTS</th>
              <th style="padding:4px 6px; border:1px solid var(--border-color); background:var(--bg-subtle);">Score</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td style="padding:6px; border:1px solid var(--border-color); font-weight:600;">
                <?= !empty($lead['passport_number']) ? 'Yes' : 'No' ?>
              </td>
              <td style="padding:6px; border:1px solid var(--border-color); font-weight:600;">
                <?= !empty($lead['english_test']) && $lead['english_test'] !== 'None' ? 'Yes' : 'No' ?>
              </td>
              <td style="padding:6px; border:1px solid var(--border-color); font-weight:700; color:var(--primary);">
                <?= !empty($lead['english_score']) ? htmlspecialchars($lead['english_score']) : '—' ?>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Contact Coordinates -->
      <div style="padding:1rem 0 0 0; font-size:0.85rem; display:flex; flex-direction:column; gap:0.75rem;">
        <div>
          <span style="font-size:0.72rem; color:var(--text-muted); text-transform:uppercase; font-weight:700; display:block; margin-bottom:2px;">Mobile</span>
          <div style="display:flex; justify-content:space-between; align-items:center;">
            <strong style="font-size:0.95rem; color:var(--text-heading);"><?= htmlspecialchars($lead['phone']) ?></strong>
            <div style="display:flex; gap:4px;">
              <a href="tel:<?= htmlspecialchars($lead['phone']) ?>" class="btn btn-secondary btn-sm" style="padding:3px 7px;" title="Call">
                <i class="fa-solid fa-phone" style="font-size:0.75rem;"></i>
              </a>
              <a href="https://wa.me/<?= preg_replace('/\D/', '', $lead['phone']) ?>" target="_blank" class="btn btn-secondary btn-sm" style="padding:3px 7px; color:#16a34a;" title="WhatsApp">
                <i class="fa-brands fa-whatsapp" style="font-size:0.8rem;"></i>
              </a>
            </div>
          </div>
        </div>

        <div>
          <span style="font-size:0.72rem; color:var(--text-muted); text-transform:uppercase; font-weight:700; display:block; margin-bottom:2px;">Location / Branch</span>
          <strong><?= htmlspecialchars($lead['city'] ?: ($lead['branch_name'] ?? 'Not Specified')) ?></strong>
        </div>

        <div>
          <span style="font-size:0.72rem; color:var(--text-muted); text-transform:uppercase; font-weight:700; display:block; margin-bottom:2px;">Email</span>
          <a href="mailto:<?= htmlspecialchars($lead['email']) ?>" style="color:var(--primary); text-decoration:none; word-break:break-all;">
            <?= htmlspecialchars($lead['email'] ?: '—') ?>
          </a>
        </div>

        <div>
          <span style="font-size:0.72rem; color:var(--text-muted); text-transform:uppercase; font-weight:700; display:block; margin-bottom:2px;">Address</span>
          <span style="color:var(--text-body);"><?= htmlspecialchars($lead['city'] ? $lead['city'] . ', ' . $lead['state'] : '—') ?></span>
        </div>
      </div>

    </div>
  </div>

  <!-- ==================== RIGHT COLUMN: SPEC-MATCHING TABS (Details, Follow-ups, Invoices, Payments) ==================== -->
  <div>
    
    <!-- Tab Navigation Bar (Figures p14, p15, p16, p18) -->
    <div style="display:flex; gap:0.25rem; border-bottom:2px solid var(--border-color); margin-bottom:1.5rem; background:#fff; padding:0.5rem 0.5rem 0 0.5rem; border-radius:10px 10px 0 0; box-shadow:0 1px 3px rgba(0,0,0,0.03);">
      <button type="button" onclick="switchLeadTab('details')" id="tab-btn-details" class="lead-nav-tab <?= $activeTab === 'details' ? 'active' : '' ?>">
        <i class="fa-solid fa-id-card"></i> Details
      </button>
      <button type="button" onclick="switchLeadTab('followups')" id="tab-btn-followups" class="lead-nav-tab <?= $activeTab === 'followups' ? 'active' : '' ?>">
        <i class="fa-solid fa-clock-rotate-left"></i> Follow-ups
        <span class="badge <?= count($followups) > 0 ? 'badge-primary' : 'badge-secondary' ?>" style="font-size:0.7rem; margin-left:4px;"><?= count($followups) ?></span>
      </button>
      <button type="button" onclick="switchLeadTab('invoices')" id="tab-btn-invoices" class="lead-nav-tab <?= $activeTab === 'invoices' ? 'active' : '' ?>">
        <i class="fa-solid fa-file-invoice-dollar"></i> Invoices
        <span class="badge <?= count($invoices) > 0 ? 'badge-primary' : 'badge-secondary' ?>" style="font-size:0.7rem; margin-left:4px;"><?= count($invoices) ?></span>
      </button>
      <button type="button" onclick="switchLeadTab('payments')" id="tab-btn-payments" class="lead-nav-tab <?= $activeTab === 'payments' ? 'active' : '' ?>">
        <i class="fa-solid fa-receipt"></i> Payments
        <span class="badge <?= count($payments) > 0 ? 'badge-primary' : 'badge-secondary' ?>" style="font-size:0.7rem; margin-left:4px;"><?= count($payments) ?></span>
      </button>
    </div>

    <!-- ==================== PANE 1: DETAILS (Figure p14) ==================== -->
    <div id="pane-details" class="lead-tab-pane <?= $activeTab === 'details' ? 'active' : '' ?>">
      
      <!-- Top Meta Table: Created Date | Created By | Assigned To | Prospectivity | Intake -->
      <div class="card" style="margin-bottom:1.25rem; padding:0; overflow:hidden;">
        <table style="width:100%; border-collapse:collapse; font-size:0.85rem;">
          <thead>
            <tr style="background:var(--bg-subtle); color:var(--text-muted); font-size:0.75rem; text-transform:uppercase;">
              <th style="padding:0.75rem 1rem; border-right:1px solid var(--border-color);">Created Date</th>
              <th style="padding:0.75rem 1rem; border-right:1px solid var(--border-color);">Created By</th>
              <th style="padding:0.75rem 1rem; border-right:1px solid var(--border-color);">Assigned To</th>
              <th style="padding:0.75rem 1rem; border-right:1px solid var(--border-color);">Prospectivity</th>
              <th style="padding:0.75rem 1rem;">Intake</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <!-- Created Date -->
              <td style="padding:0.85rem 1rem; border-right:1px solid var(--border-color); font-weight:600; color:var(--text-heading);">
                <?= date('d-m-Y h:i A', strtotime($lead['created_at'])) ?>
              </td>

              <!-- Created By -->
              <td style="padding:0.85rem 1rem; border-right:1px solid var(--border-color); font-weight:600; color:var(--text-heading);">
                <?= htmlspecialchars($lead['created_by_name'] ?? 'Admin') ?>
              </td>

              <!-- Section 9.3.3: ReAssign Assigned To with Change Button -->
              <td style="padding:0.85rem 1rem; border-right:1px solid var(--border-color);">
                <div style="display:flex; align-items:center; justify-content:space-between; gap:0.5rem;">
                  <span style="font-weight:700; color:var(--text-heading);">
                    <?= htmlspecialchars($lead['counselor_name'] ?? 'Unassigned') ?>
                  </span>
                  <?php if (can('leads', 'edit')): ?>
                    <button type="button" class="btn btn-outline-danger btn-xs" style="padding:2px 8px; font-size:0.72rem; font-weight:700;" onclick="openChangeCounselorModal(<?= $lead['id'] ?>, '<?= htmlspecialchars(addslashes($lead['full_name'])) ?>', <?= (int)$lead['assigned_counselor_id'] ?>)" title="ReAssign Lead to another counselor">
                      Change
                    </button>
                  <?php endif; ?>
                </div>
              </td>

              <!-- Section 9.2.3: Change Prospectivity -->
              <td style="padding:0.85rem 1rem; border-right:1px solid var(--border-color);">
                <div style="display:flex; align-items:center; justify-content:space-between; gap:0.5rem;">
                  <span class="badge <?= $lead['prospectivity'] === 'Hot' ? 'badge-danger' : ($lead['prospectivity'] === 'Cold' ? 'badge-secondary' : 'badge-warning') ?>" style="font-size:0.72rem;">
                    <?= htmlspecialchars($lead['prospectivity'] ?? 'Warm') ?>
                  </span>
                  <?php if (can('leads', 'edit')): ?>
                    <button type="button" class="btn btn-secondary btn-xs" style="padding:2px 6px; font-size:0.72rem;" onclick="openChangeProspectivityModal(<?= $lead['id'] ?>, '<?= htmlspecialchars(addslashes($lead['full_name'])) ?>', '<?= htmlspecialchars($lead['prospectivity']) ?>')" title="Change Prospectivity">
                      <i class="fa-solid fa-pen"></i>
                    </button>
                  <?php endif; ?>
                </div>
              </td>

              <!-- Section 9.2.4: Change Intake -->
              <td style="padding:0.85rem 1rem;">
                <div style="display:flex; align-items:center; justify-content:space-between; gap:0.5rem;">
                  <span style="font-weight:600; font-size:0.825rem; color:var(--text-heading);">
                    <?= htmlspecialchars($lead['intake_term']) ?> <?= htmlspecialchars($lead['intake_year']) ?>
                  </span>
                  <?php if (can('leads', 'edit')): ?>
                    <button type="button" class="btn btn-secondary btn-xs" style="padding:2px 6px; font-size:0.72rem;" onclick="openChangeIntakeModal(<?= $lead['id'] ?>, '<?= htmlspecialchars(addslashes($lead['full_name'])) ?>', '<?= htmlspecialchars($lead['intake_term']) ?>', '<?= htmlspecialchars($lead['intake_year']) ?>')" title="Change Intake Cycle">
                      <i class="fa-solid fa-pen"></i>
                    </button>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Core Lead Sections (Matching Figure p14) -->
      <div style="display:flex; flex-direction:column; gap:1.25rem;">
        
        <!-- 1. Qualifications -->
        <div class="card" style="padding:1.25rem;">
          <h4 style="font-size:0.85rem; font-weight:700; text-transform:uppercase; letter-spacing:0.04em; color:var(--text-muted); margin:0 0 0.5rem 0;">
            Qualifications
          </h4>
          <p style="font-size:0.95rem; font-weight:600; color:var(--text-heading); margin:0;">
            <?= htmlspecialchars($lead['highest_qualification'] ?: 'Not Specified') ?>
            <?php if (!empty($lead['college_name'])): ?>
              <span style="font-weight:400; color:var(--text-muted);"> &bull; <?= htmlspecialchars($lead['college_name']) ?></span>
            <?php endif; ?>
          </p>
        </div>

        <!-- 2. Reference -->
        <div class="card" style="padding:1.25rem;">
          <h4 style="font-size:0.85rem; font-weight:700; text-transform:uppercase; letter-spacing:0.04em; color:var(--text-muted); margin:0 0 0.5rem 0;">
            Reference
          </h4>
          <p style="font-size:0.95rem; color:var(--text-heading); margin:0;">
            <?= htmlspecialchars($lead['source'] ?: 'Direct Lead Entry') ?>
          </p>
        </div>

        <!-- 3. Preferred Countries & Targets -->
        <div class="card" style="padding:1.25rem;">
          <h4 style="font-size:0.85rem; font-weight:700; text-transform:uppercase; letter-spacing:0.04em; color:var(--text-muted); margin:0 0 0.5rem 0;">
            Preferred Countries & Target Program
          </h4>
          <div style="display:flex; gap:2rem; font-size:0.95rem;">
            <div>
              <span style="font-size:0.75rem; color:var(--text-muted); display:block;">Target Country</span>
              <strong style="color:var(--primary);"><i class="fa-solid fa-earth-americas"></i> <?= htmlspecialchars($lead['preferred_country']) ?></strong>
            </div>
            <div>
              <span style="font-size:0.75rem; color:var(--text-muted); display:block;">Target Degree / Course</span>
              <strong style="color:var(--text-heading);"><?= htmlspecialchars($lead['preferred_course'] ?: 'General Admissions') ?></strong>
            </div>
          </div>
        </div>

        <!-- 4. Current Status Note -->
        <div class="card" style="padding:1.25rem;">
          <h4 style="font-size:0.85rem; font-weight:700; text-transform:uppercase; letter-spacing:0.04em; color:var(--text-muted); margin:0 0 0.5rem 0;">
            Current Status Note
          </h4>
          <div style="font-size:0.9rem; color:var(--text-body); background:var(--bg-subtle); padding:0.85rem 1rem; border-radius:8px; line-height:1.5;">
            <?= nl2br(htmlspecialchars($lead['initial_remarks'] ?: 'Lead - Initial enquiry registered and information shared.')) ?>
          </div>
        </div>

      </div>

    </div>

    <!-- ==================== PANE 2: FOLLOW-UPS (Section 9.3.4 & Figure p15) ==================== -->
    <div id="pane-followups" class="lead-tab-pane <?= $activeTab === 'followups' ? 'active' : '' ?>">
      <div class="card" style="padding:1.5rem;">
        
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.25rem;">
          <div>
            <h3 style="font-size:1.15rem; font-weight:700; color:var(--text-heading); margin:0 0 0.25rem 0;">
              Follow-up Interactions
            </h3>
            <p style="font-size:0.825rem; color:var(--text-muted); margin:0;">
              Schedule consultations and track client interaction history.
            </p>
          </div>
          <!-- Section 9.3.4.1: New follow-up button -->
          <button type="button" class="btn btn-primary btn-sm" onclick="openLeadFollowupModal()" style="font-weight:700;">
            <i class="fa-solid fa-plus"></i> New Follow-up
          </button>
        </div>

        <?php if (empty($followups)): ?>
          <div style="text-align:center; padding:3rem 1.5rem; background:var(--bg-subtle); border-radius:10px; color:var(--text-muted);">
            <i class="fa-regular fa-calendar-xmark" style="font-size:2.2rem; margin-bottom:0.75rem; display:block; color:var(--text-light);"></i>
            <h4 style="font-size:1rem; font-weight:700; color:var(--text-heading); margin:0 0 0.4rem 0;">No Follow-ups Logged</h4>
            <p style="font-size:0.85rem; margin:0 0 1.25rem 0;">No consultation calls or follow-ups have been recorded for this student.</p>
            <button type="button" class="btn btn-primary btn-sm" onclick="openLeadFollowupModal()">
              <i class="fa-solid fa-phone-volume"></i> Schedule First Follow-up
            </button>
          </div>
        <?php else: ?>
          <!-- Exact Table from PDF Figure p15: Student's Name | Mobile | Email | Title | Follow-up Date | Next Follow-Up Date | Actions -->
          <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; font-size:0.85rem;">
              <thead>
                <tr style="background:var(--bg-subtle); color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; text-align:left;">
                  <th style="padding:0.75rem 0.85rem; border-bottom:1px solid var(--border-color);">Student's Name</th>
                  <th style="padding:0.75rem 0.85rem; border-bottom:1px solid var(--border-color);">Mobile</th>
                  <th style="padding:0.75rem 0.85rem; border-bottom:1px solid var(--border-color);">Title</th>
                  <th style="padding:0.75rem 0.85rem; border-bottom:1px solid var(--border-color);">Follow-up Date</th>
                  <th style="padding:0.75rem 0.85rem; border-bottom:1px solid var(--border-color);">Next Follow-Up Date</th>
                  <th style="padding:0.75rem 0.85rem; border-bottom:1px solid var(--border-color); text-align:center;">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($followups as $f): 
                  $isPending = ($f['status'] === 'Pending');
                ?>
                  <tr style="border-bottom:1px solid var(--border-color);">
                    <!-- Student's Name -->
                    <td style="padding:0.85rem; font-weight:700; color:var(--text-heading);">
                      <?= htmlspecialchars($lead['full_name']) ?>
                    </td>

                    <!-- Mobile -->
                    <td style="padding:0.85rem; color:var(--text-body);">
                      <?= htmlspecialchars($lead['phone']) ?>
                    </td>

                    <!-- Title -->
                    <td style="padding:0.85rem;">
                      <div style="font-weight:600; color:var(--text-heading);">
                        <?= htmlspecialchars($f['interaction_title'] ?? $f['interaction_type'] ?? 'Consultation') ?>
                      </div>
                      <?php if (!empty($f['notes'])): ?>
                        <div style="font-size:0.75rem; color:var(--text-muted); max-width:240px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                          <?= htmlspecialchars($f['notes']) ?>
                        </div>
                      <?php endif; ?>
                    </td>

                    <!-- Follow-up Date -->
                    <td style="padding:0.85rem; color:var(--text-muted);">
                      <?= date('d-m-Y', strtotime($f['created_at'])) ?>
                    </td>

                    <!-- Next Follow-Up Date -->
                    <td style="padding:0.85rem;">
                      <strong style="color:<?= $isPending ? '#b45309' : 'var(--text-heading)' ?>;">
                        <?= date('d-m-Y', strtotime($f['scheduled_date'])) ?>
                      </strong>
                      <span class="badge <?= $isPending ? 'badge-warning' : 'badge-success' ?>" style="font-size:0.65rem; margin-left:4px;">
                        <?= htmlspecialchars($f['status']) ?>
                      </span>
                    </td>

                    <!-- Actions: Section 9.3.4.2 Edit & 9.3.4.3 Details -->
                    <td style="padding:0.85rem; text-align:center; white-space:nowrap;">
                      <button type="button" class="btn btn-primary btn-xs" style="padding:3px 8px; font-size:0.72rem;" onclick="showFollowupDetails(<?= htmlspecialchars(json_encode($f)) ?>)" title="View Details">
                        <i class="fa-solid fa-paper-plane"></i> Details
                      </button>
                      <?php if (can('followups', 'edit')): ?>
                        <a href="<?= base_url('followups/edit.php?id=' . $f['id']) ?>" class="btn btn-warning btn-xs" style="padding:3px 8px; font-size:0.72rem; color:#fff;" title="Edit Follow-up">
                          <i class="fa-solid fa-pen"></i> Edit
                        </a>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>

      </div>
    </div>

    <!-- ==================== PANE 3: INVOICES (Section 9.3.5 & Figure p16) ==================== -->
    <div id="pane-invoices" class="lead-tab-pane <?= $activeTab === 'invoices' ? 'active' : '' ?>">
      <div class="card" style="padding:1.5rem;">
        
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.25rem;">
          <div>
            <h3 style="font-size:1.15rem; font-weight:700; color:var(--text-heading); margin:0 0 0.25rem 0;">
              Student Service Invoices
            </h3>
            <p style="font-size:0.825rem; color:var(--text-muted); margin:0;">
              Manage service billing, fees, and client invoice generation.
            </p>
          </div>
          <!-- Section 9.3.5.1: Add New Invoice -->
          <?php if (can('invoices', 'add')): ?>
            <a href="<?= base_url('finance/create_invoice.php?lead_id=' . $lead['id']) ?>" class="btn btn-primary btn-sm" style="font-weight:700;">
              <i class="fa-solid fa-plus"></i> Add New
            </a>
          <?php endif; ?>
        </div>

        <?php if (empty($invoices)): ?>
          <div style="text-align:center; padding:3rem 1.5rem; background:var(--bg-subtle); border-radius:10px; color:var(--text-muted);">
            <i class="fa-solid fa-file-invoice" style="font-size:2.2rem; margin-bottom:0.75rem; display:block; color:var(--text-light);"></i>
            <h4 style="font-size:1rem; font-weight:700; color:var(--text-heading); margin:0 0 0.4rem 0;">No Invoices Created</h4>
            <p style="font-size:0.85rem; margin:0 0 1.25rem 0;">Create an invoice to officially convert this student into an enrolled Customer.</p>
            <?php if (can('invoices', 'add')): ?>
              <a href="<?= base_url('finance/create_invoice.php?lead_id=' . $lead['id']) ?>" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-plus"></i> Generate First Invoice
              </a>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <!-- Exact Table from PDF Figure p16: Date | Invoice# | Student's Name | Due Date | Amount | Balance | Action: View -->
          <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; font-size:0.85rem;">
              <thead>
                <tr style="background:var(--bg-subtle); color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; text-align:left;">
                  <th style="padding:0.75rem 0.85rem; border-bottom:1px solid var(--border-color);">Date</th>
                  <th style="padding:0.75rem 0.85rem; border-bottom:1px solid var(--border-color);">Invoice#</th>
                  <th style="padding:0.75rem 0.85rem; border-bottom:1px solid var(--border-color);">Student's Name</th>
                  <th style="padding:0.75rem 0.85rem; border-bottom:1px solid var(--border-color);">Due Date</th>
                  <th style="padding:0.75rem 0.85rem; border-bottom:1px solid var(--border-color); text-align:right;">Amount</th>
                  <th style="padding:0.75rem 0.85rem; border-bottom:1px solid var(--border-color); text-align:right;">Balance</th>
                  <th style="padding:0.75rem 0.85rem; border-bottom:1px solid var(--border-color); text-align:center;">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($invoices as $inv): 
                  $invDate = !empty($inv['invoice_date']) ? $inv['invoice_date'] : $inv['created_at'];
                  $dueDate = !empty($inv['due_date']) ? $inv['due_date'] : $invDate;
                  $balance = max(0, (float)$inv['total_amount'] - (float)$inv['paid_amount']);
                ?>
                  <tr style="border-bottom:1px solid var(--border-color);">
                    <!-- Date -->
                    <td style="padding:0.85rem; color:var(--text-muted);">
                      <?= date('d-m-Y', strtotime($invDate)) ?>
                    </td>

                    <!-- Invoice# -->
                    <td style="padding:0.85rem; font-weight:700; color:var(--primary);">
                      <?= htmlspecialchars($inv['invoice_number']) ?>
                    </td>

                    <!-- Student's Name -->
                    <td style="padding:0.85rem; font-weight:600; color:var(--text-heading);">
                      <?= htmlspecialchars($lead['full_name']) ?>
                    </td>

                    <!-- Due Date -->
                    <td style="padding:0.85rem; color:var(--text-muted);">
                      <?= date('d-m-Y', strtotime($dueDate)) ?>
                    </td>

                    <!-- Amount -->
                    <td style="padding:0.85rem; text-align:right; font-weight:700; color:var(--text-heading);">
                      ₹<?= number_format((float)$inv['total_amount'], 2) ?>
                    </td>

                    <!-- Balance -->
                    <td style="padding:0.85rem; text-align:right; font-weight:700; color:<?= $balance > 0 ? '#b45309' : '#15803d' ?>;">
                      ₹<?= number_format($balance, 2) ?>
                    </td>

                    <!-- Section 9.3.5.2 Action: View button -->
                    <td style="padding:0.85rem; text-align:center;">
                      <a href="<?= base_url('finance/view_invoice.php?id=' . $inv['id']) ?>" class="btn btn-primary btn-xs" style="padding:3px 10px; font-size:0.72rem;" title="View Invoice">
                        <i class="fa-solid fa-paper-plane"></i> View
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>

      </div>
    </div>

    <!-- ==================== PANE 4: PAYMENTS (Section 9.3.6 & Figure p18) ==================== -->
    <div id="pane-payments" class="lead-tab-pane <?= $activeTab === 'payments' ? 'active' : '' ?>">
      <div class="card" style="padding:1.5rem;">
        
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.25rem;">
          <div>
            <h3 style="font-size:1.15rem; font-weight:700; color:var(--text-heading); margin:0 0 0.25rem 0;">
              Invoice Payments & Receipts
            </h3>
            <p style="font-size:0.825rem; color:var(--text-muted); margin:0;">
              Log payment receipts, vouchers, and manage fee settlement records.
            </p>
          </div>
          <!-- Section 9.3.6.2: Add Invoice Payment -->
          <?php if (can('invoices', 'edit') && !empty($invoices)): ?>
            <a href="<?= base_url('finance/view_invoice.php?id=' . $invoices[0]['id']) ?>" class="btn btn-primary btn-sm" style="font-weight:700;">
              <i class="fa-solid fa-plus"></i> Add New Payment
            </a>
          <?php endif; ?>
        </div>

        <?php if (empty($payments)): ?>
          <div style="text-align:center; padding:3rem 1.5rem; background:var(--bg-subtle); border-radius:10px; color:var(--text-muted);">
            <i class="fa-solid fa-receipt" style="font-size:2.2rem; margin-bottom:0.75rem; display:block; color:var(--text-light);"></i>
            <h4 style="font-size:1rem; font-weight:700; color:var(--text-heading); margin:0 0 0.4rem 0;">No Payments Recorded</h4>
            <p style="font-size:0.85rem; margin:0 0 1.25rem 0;">No payment installments or receipts have been logged yet.</p>
            <?php if (!empty($invoices)): ?>
              <a href="<?= base_url('finance/view_invoice.php?id=' . $invoices[0]['id']) ?>" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-receipt"></i> Record First Payment
              </a>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <!-- Exact Table from PDF Figure p18: Date | Amount | Mode | Created User | Actions: Edit, Delete -->
          <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; font-size:0.85rem;">
              <thead>
                <tr style="background:var(--bg-subtle); color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; text-align:left;">
                  <th style="padding:0.75rem 0.85rem; border-bottom:1px solid var(--border-color);">Date</th>
                  <th style="padding:0.75rem 0.85rem; border-bottom:1px solid var(--border-color);">Invoice #</th>
                  <th style="padding:0.75rem 0.85rem; border-bottom:1px solid var(--border-color); text-align:right;">Amount</th>
                  <th style="padding:0.75rem 0.85rem; border-bottom:1px solid var(--border-color);">Mode</th>
                  <th style="padding:0.75rem 0.85rem; border-bottom:1px solid var(--border-color);">Created User</th>
                  <th style="padding:0.75rem 0.85rem; border-bottom:1px solid var(--border-color); text-align:center;">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($payments as $p): ?>
                  <tr style="border-bottom:1px solid var(--border-color);">
                    <!-- Date -->
                    <td style="padding:0.85rem; color:var(--text-muted);">
                      <?= date('d-m-Y', strtotime($p['payment_date'] ?? $p['created_at'])) ?>
                    </td>

                    <!-- Invoice # -->
                    <td style="padding:0.85rem; font-weight:700; color:var(--primary);">
                      <?= htmlspecialchars($p['invoice_number'] ?? '—') ?>
                    </td>

                    <!-- Amount -->
                    <td style="padding:0.85rem; text-align:right; font-weight:700; color:#15803d;">
                      ₹<?= number_format((float)$p['amount_paid'], 2) ?>
                    </td>

                    <!-- Mode -->
                    <td style="padding:0.85rem;">
                      <span class="badge badge-secondary" style="font-size:0.72rem;">
                        <?= htmlspecialchars($p['payment_mode']) ?>
                      </span>
                    </td>

                    <!-- Created User -->
                    <td style="padding:0.85rem; font-weight:600; color:var(--text-heading);">
                      <?= htmlspecialchars($p['received_by_name'] ?? 'Staff') ?>
                    </td>

                    <!-- Actions: View Invoice / Receipt -->
                    <td style="padding:0.85rem; text-align:center;">
                      <?php if (!empty($p['invoice_id'])): ?>
                        <a href="<?= base_url('finance/view_invoice.php?id=' . $p['invoice_id']) ?>" class="btn btn-primary btn-xs" style="padding:3px 8px; font-size:0.72rem;" title="View Invoice / Payment">
                          <i class="fa-solid fa-file-invoice-dollar"></i> View
                        </a>
                      <?php endif; ?>
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

<!-- ==================== NEW FOLLOW-UP POPUP MODAL (Section 9.3.4.1) ==================== -->
<div class="modal fade" id="leadFollowupModal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(12,36,60,0.5); align-items:center; justify-content:center;">
  <div class="modal-dialog" style="background:#fff; border-radius:14px; max-width:480px; width:92%; margin:auto; box-shadow:0 20px 50px rgba(0,0,0,0.2); overflow:hidden;">
    <form method="POST" action="<?= base_url('followups/add.php') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="lead_id" value="<?= $lead['id'] ?>">
      <input type="hidden" name="return_url" value="<?= base_url('leads/view.php?id=' . $lead['id'] . '&tab=followups') ?>">

      <div class="modal-header" style="padding:1.25rem 1.5rem; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
        <h3 style="font-size:1.15rem; font-weight:700; margin:0; color:var(--text-heading);">
          <i class="fa-solid fa-phone-volume" style="color:var(--primary); margin-right:8px;"></i> Add New Follow-up
        </h3>
        <button type="button" onclick="closeLeadFollowupModal()" style="border:none; background:none; font-size:1.4rem; cursor:pointer; color:var(--text-muted);">&times;</button>
      </div>

      <div class="modal-body" style="padding:1.5rem;">
        <div class="form-group" style="margin-bottom:1.25rem;">
          <label class="form-label">Interaction Title <span style="color:red;">*</span></label>
          <select name="interaction_title_id" class="form-control" required>
            <option value="">Select Interaction Title...</option>
            <?php foreach ($interactionTitles as $it): ?>
              <option value="<?= $it['id'] ?>">
                <?= htmlspecialchars($it['title']) ?> (Max Duration: <?= $it['max_duration_days'] ?> days)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; margin-bottom:1.25rem;">
          <div class="form-group">
            <label class="form-label">Next Follow-up Date <span style="color:red;">*</span></label>
            <input type="date" name="next_followup_date" class="form-control" required min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d', strtotime('+2 days')) ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Scheduled Time</label>
            <input type="time" name="next_followup_time" class="form-control" value="10:00">
          </div>
        </div>

        <div class="form-group" style="margin-bottom:1rem;">
          <label class="form-label">Discussion Notes & Remarks <span style="color:red;">*</span></label>
          <textarea name="notes" class="form-control" rows="3" placeholder="Enter follow-up consultation details, candidate response, next action..." required></textarea>
        </div>
      </div>

      <div class="modal-footer" style="padding:1rem 1.5rem; border-top:1px solid var(--border-color); display:flex; justify-content:flex-end; gap:0.5rem;">
        <button type="button" onclick="closeLeadFollowupModal()" class="btn btn-secondary btn-sm">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm" style="font-weight:700;">
          <i class="fa-solid fa-check"></i> Save Details
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ==================== FOLLOW-UP DETAILS POPUP MODAL (Section 9.3.4.3) ==================== -->
<div class="modal fade" id="followupDetailsModal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(12,36,60,0.5); align-items:center; justify-content:center;">
  <div class="modal-dialog" style="background:#fff; border-radius:14px; max-width:480px; width:92%; margin:auto; box-shadow:0 20px 50px rgba(0,0,0,0.2); overflow:hidden;">
    <div class="modal-header" style="padding:1.25rem 1.5rem; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
      <h3 style="font-size:1.15rem; font-weight:700; margin:0; color:var(--text-heading);">
        <i class="fa-solid fa-circle-info" style="color:var(--primary); margin-right:8px;"></i> Follow-up Details
      </h3>
      <button type="button" onclick="closeFollowupDetailsModal()" style="border:none; background:none; font-size:1.4rem; cursor:pointer; color:var(--text-muted);">&times;</button>
    </div>
    <div class="modal-body" style="padding:1.5rem; font-size:0.875rem;">
      <div style="margin-bottom:1rem;">
        <span style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase; font-weight:700; display:block;">Interaction Title</span>
        <strong id="f-detail-title" style="font-size:1rem; color:var(--text-heading);"></strong>
      </div>
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; margin-bottom:1rem;">
        <div>
          <span style="font-size:0.75rem; color:var(--text-muted); display:block;">Scheduled Next Date</span>
          <strong id="f-detail-date"></strong>
        </div>
        <div>
          <span style="font-size:0.75rem; color:var(--text-muted); display:block;">Status</span>
          <span id="f-detail-status" class="badge badge-warning"></span>
        </div>
      </div>
      <div style="margin-bottom:1rem;">
        <span style="font-size:0.75rem; color:var(--text-muted); display:block; margin-bottom:4px;">Counselor / Staff</span>
        <strong id="f-detail-counselor"></strong>
      </div>
      <div>
        <span style="font-size:0.75rem; color:var(--text-muted); display:block; margin-bottom:4px;">Discussion Notes</span>
        <div id="f-detail-notes" style="background:var(--bg-subtle); padding:0.85rem; border-radius:8px; line-height:1.5; color:var(--text-body);"></div>
      </div>
    </div>
    <div class="modal-footer" style="padding:1rem 1.5rem; border-top:1px solid var(--border-color); display:flex; justify-content:space-between;">
      <a id="f-detail-edit-link" href="#" class="btn btn-warning btn-sm" style="color:#fff;">
        <i class="fa-solid fa-pen"></i> Edit Follow-up
      </a>
      <button type="button" onclick="closeFollowupDetailsModal()" class="btn btn-secondary btn-sm">Close</button>
    </div>
  </div>
</div>

<style>
.lead-nav-tab {
  border: none;
  background: none;
  padding: 0.65rem 1.25rem;
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--text-muted);
  cursor: pointer;
  border-bottom: 2px solid transparent;
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  transition: all 0.15s ease;
}
.lead-nav-tab:hover {
  color: var(--primary);
}
.lead-nav-tab.active {
  color: var(--primary);
  border-bottom: 2px solid var(--primary);
  font-weight: 700;
}
.lead-tab-pane {
  display: none;
}
.lead-tab-pane.active {
  display: block;
}
</style>

<script>
// Spec-matching Tab switcher (Details, Follow-ups, Invoices, Payments)
function switchLeadTab(tabName) {
  document.querySelectorAll('.lead-nav-tab').forEach(el => el.classList.remove('active'));
  document.querySelectorAll('.lead-tab-pane').forEach(el => el.classList.remove('active'));

  const btn = document.getElementById('tab-btn-' + tabName);
  const pane = document.getElementById('pane-' + tabName);

  if (btn && pane) {
    btn.classList.add('active');
    pane.classList.add('active');
    // Update URL hash/query without reloading
    const newUrl = new URL(window.location);
    newUrl.searchParams.set('tab', tabName);
    window.history.replaceState({}, '', newUrl);
  }
}

// Section 9.3.4.1 New Follow-up Modal
function openLeadFollowupModal() {
  document.getElementById('leadFollowupModal').style.display = 'flex';
}
function closeLeadFollowupModal() {
  document.getElementById('leadFollowupModal').style.display = 'none';
}

// Section 9.3.4.3 Follow-up Details Modal
function showFollowupDetails(f) {
  document.getElementById('f-detail-title').textContent = f.interaction_title || f.interaction_type || 'Consultation';
  document.getElementById('f-detail-date').textContent = f.scheduled_date || '—';
  document.getElementById('f-detail-status').textContent = f.status || 'Pending';
  document.getElementById('f-detail-counselor').textContent = f.counselor_name || 'Staff';
  document.getElementById('f-detail-notes').textContent = f.notes || 'No notes attached.';
  document.getElementById('f-detail-edit-link').href = '<?= base_url('followups/edit.php?id=') ?>' + f.id;
  document.getElementById('followupDetailsModal').style.display = 'flex';
}
function closeFollowupDetailsModal() {
  document.getElementById('followupDetailsModal').style.display = 'none';
}

// Section 9.2.9 Archive Student
function archiveCurrentLead() {
  if (confirm('Are you sure you want to archive <?= htmlspecialchars(addslashes($lead['full_name'])) ?>? The student will be moved to the Archived section.')) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= base_url('leads/archive.php') ?>';
    form.innerHTML = `
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= $lead['id'] ?>">
      <input type="hidden" name="return_url" value="<?= base_url('leads/index.php') ?>">
    `;
    document.body.appendChild(form);
    form.submit();
  }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
