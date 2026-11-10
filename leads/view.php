<?php
/**
 * 360-Degree Student Lead Profile & Dossier
 * University-Level Education Consultancy CRM (EdrootsCRM)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_permission('leads', 'view');

$leadId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$currentUser = current_user();

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

// Historical Stage Transitions
$sStmt = $pdo->prepare("
    SELECT h.*, u.name AS changed_by_name
    FROM lead_stage_history h
    LEFT JOIN users u ON h.changed_by = u.id
    WHERE h.lead_id = ?
    ORDER BY h.id DESC
");
$sStmt->execute([$leadId]);
$stageHistory = $sStmt->fetchAll();

// Student Academic Dossier Documents
$dStmt = $pdo->prepare("
    SELECT d.*, u.name AS uploaded_by_name
    FROM student_documents d
    LEFT JOIN users u ON d.uploaded_by = u.id
    WHERE d.lead_id = ?
    ORDER BY d.id DESC
");
$dStmt->execute([$leadId]);
$studentDocs = $dStmt->fetchAll();

// Invoices for this student
$invStmt = $pdo->prepare("
    SELECT i.*, u.name AS created_by_name
    FROM invoices i
    LEFT JOIN users u ON i.created_by = u.id
    WHERE i.lead_id = ?
    ORDER BY i.id DESC
");
$invStmt->execute([$leadId]);
$invoices = $invStmt->fetchAll();

// 6 Pipeline Stages definition
$standardStages = [
    'ENQUIRY' => ['label' => '1. Enquiry', 'icon' => 'fa-comments'],
    'CUSTOMER' => ['label' => '2. Customer', 'icon' => 'fa-certificate'],
    'APPLICATION' => ['label' => '3. Application', 'icon' => 'fa-file-signature'],
    'OFFER RECEIVED' => ['label' => '4. Offer Received', 'icon' => 'fa-envelope-open-text'],
    'TUITION FEES PAID' => ['label' => '5. Tuition Fees Paid', 'icon' => 'fa-receipt'],
    'ENROLLED' => ['label' => '6. Enrolled', 'icon' => 'fa-user-graduate']
];

$stageKeys = array_keys($standardStages);
$currentStageIndex = array_search($lead['stage'], $stageKeys);
if ($currentStageIndex === false) $currentStageIndex = 0;

include __DIR__ . '/../includes/header.php';
?>

<!-- Profile Header Banner -->
<div class="card" style="margin-bottom:1.5rem; padding:1.5rem; border-top:4px solid var(--primary);">
  <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1rem;">
    <div style="display:flex; gap:1.2rem; align-items:center;">
      <div style="width:64px; height:64px; border-radius:14px; background:var(--primary-subtle); color:var(--primary); display:flex; align-items:center; justify-content:center; font-size:1.8rem; font-weight:800;">
        <?= strtoupper(substr($lead['full_name'], 0, 1)) ?>
      </div>
      <div>
        <div style="display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap;">
          <h1 style="font-size:1.45rem; font-weight:800; color:var(--text-heading); margin:0;">
            <?= htmlspecialchars($lead['full_name']) ?>
          </h1>
          <span style="font-family:monospace; background:#e0e7ff; color:#3730a3; padding:2px 8px; border-radius:4px; font-weight:700; font-size:0.8rem;">
            <?= htmlspecialchars($lead['lead_code']) ?>
          </span>
          <?php if ($lead['is_customer']): ?>
            <span class="badge badge-success" style="font-size:0.75rem; font-weight:700;">
              <i class="fa-solid fa-certificate"></i> OFFICIAL CUSTOMER
            </span>
          <?php endif; ?>
          <?= priority_badge($lead['priority']) ?>
        </div>
        <div style="font-size:0.85rem; color:var(--text-muted); margin-top:0.35rem; display:flex; gap:1rem; flex-wrap:wrap; align-items:center;">
          <span>
            <i class="fa-solid fa-phone" style="color:var(--primary);"></i> 
            <strong style="color:var(--text-heading);"><?= htmlspecialchars($lead['phone']) ?></strong>
            <a href="tel:<?= preg_replace('/[^0-9+]/', '', $lead['phone']) ?>" class="btn btn-outline btn-sm" style="padding:1px 6px; margin-left:4px;" title="Call"><i class="fa-solid fa-phone"></i></a>
            <?php 
              $viewWaMsg = get_whatsapp_message($lead['stage'], $lead['full_name'], $currentUser['name'] ?? 'Edroots', $lead['preferred_country'] ?? 'UK', $lead['preferred_course'] ?? 'Higher Ed');
            ?>
            <a href="https://wa.me/91<?= normalize_phone($lead['phone']) ?>?text=<?= urlencode($viewWaMsg) ?>" target="_blank" class="btn btn-outline btn-sm" style="padding:1px 6px; margin-left:2px;" title="WhatsApp Candidate"><i class="fa-brands fa-whatsapp" style="color:#25d366;"></i></a>
          </span>
          <?php if (!empty($lead['email'])): ?>
            <span><i class="fa-regular fa-envelope"></i> <?= htmlspecialchars($lead['email']) ?></span>
          <?php endif; ?>
          <span><i class="fa-solid fa-building-columns"></i> <?= htmlspecialchars($lead['branch_name'] ?? 'Branch') ?></span>
          <span><i class="fa-solid fa-headset"></i> <?= htmlspecialchars($lead['counselor_name'] ?? 'Counselor') ?></span>
        </div>
      </div>
    </div>

    <!-- Header Quick Actions -->
    <div style="display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap;">
      <button type="button" class="btn btn-primary btn-sm" onclick="openLogCallModal(<?= $lead['id'] ?>, '<?= htmlspecialchars(addslashes($lead['full_name'])) ?>', '<?= htmlspecialchars($lead['stage']) ?>')" style="font-weight:700;">
        <i class="fa-solid fa-phone-volume"></i> Log Call / Note
      </button>

      <button type="button" class="btn btn-outline btn-sm" onclick="openAdvanceStageModal(<?= $lead['id'] ?>, '<?= htmlspecialchars(addslashes($lead['full_name'])) ?>', '<?= htmlspecialchars($lead['stage']) ?>')">
        <i class="fa-solid fa-stairs"></i> Advance Stage
      </button>

      <?php if (can('invoices', 'add')): ?>
        <a href="<?= base_url('finance/create_invoice.php?lead_id=' . $lead['id']) ?>" class="btn btn-success btn-sm">
          <i class="fa-solid fa-file-invoice-dollar"></i> Generate Invoice
        </a>
      <?php endif; ?>

      <?php if (can('leads', 'edit')): ?>
        <a href="<?= base_url('leads/edit.php?id=' . $lead['id']) ?>" class="btn btn-secondary btn-sm" title="Edit Profile">
          <i class="fa-solid fa-pen-to-square"></i> Edit
        </a>
      <?php endif; ?>

      <?php if (can('archive', 'archive') && !$lead['is_archived']): ?>
        <button type="button" class="btn btn-outline-danger btn-sm" onclick="archiveCurrentLead()">
          <i class="fa-solid fa-box-archive"></i> Archive
        </button>
      <?php endif; ?>
    </div>
  </div>

  <!-- 6-Stage Visual Stepper (Interactive 1-Click Milestone Advance) -->
  <div style="margin-top:1.5rem; padding-top:1.25rem; border-top:1px solid var(--border-color);">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.6rem;">
      <span style="font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:0.04em; color:var(--text-muted);">
        Interactive Admissions Stepper (Click any stage to update candidate status)
      </span>
      <span style="font-size:0.75rem; color:var(--primary); font-weight:600;">Current: <strong><?= htmlspecialchars($lead['stage']) ?></strong></span>
    </div>
    <div style="display:grid; grid-template-columns:repeat(6, 1fr); gap:0.5rem;">
      <?php foreach ($standardStages as $sKey => $sMeta): 
        $idx = array_search($sKey, $stageKeys);
        $isCompleted = ($idx < $currentStageIndex);
        $isCurrent = ($idx === $currentStageIndex);
        
        $stepColor = $isCurrent ? 'var(--primary)' : ($isCompleted ? 'var(--success)' : 'var(--text-light)');
        $stepBg = $isCurrent ? 'var(--primary-subtle)' : ($isCompleted ? '#dcfce7' : 'var(--bg-subtle)');
      ?>
        <div onclick="openAdvanceStageModal(<?= $lead['id'] ?>, '<?= htmlspecialchars(addslashes($lead['full_name'])) ?>', '<?= $sKey ?>')" style="cursor:pointer; text-align:center; padding:0.65rem 0.35rem; background:<?= $stepBg ?>; border-radius:8px; border:1px solid <?= $isCurrent ? 'var(--primary)' : 'transparent' ?>; transition:all 0.15s;" onmouseover="this.style.transform='translateY(-2px)';" onmouseout="this.style.transform='none';" title="Click to move candidate to <?= htmlspecialchars($sMeta['label']) ?>">
          <div style="font-size:1.1rem; color:<?= $stepColor ?>; margin-bottom:3px;">
            <?php if ($isCompleted): ?>
              <i class="fa-solid fa-circle-check"></i>
            <?php else: ?>
              <i class="<?= $sMeta['icon'] ?>"></i>
            <?php endif; ?>
          </div>
          <div style="font-size:0.75rem; font-weight:<?= $isCurrent ? '800' : '600' ?>; color:<?= $isCurrent ? 'var(--primary)' : 'var(--text-heading)' ?>; line-height:1.2;">
            <?= htmlspecialchars($sMeta['label']) ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Main Split Layout -->
<div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; align-items:start;">

  <!-- Left Column: Academic Profile & Dossier -->
  <div>
    <!-- Academic & Eligibility Card -->
    <div class="card" style="margin-bottom:1.5rem; padding:1.5rem;">
      <h3 style="font-size:1.05rem; font-weight:700; color:var(--text-heading); margin:0 0 1rem 0; display:flex; align-items:center; gap:0.5rem;">
        <i class="fa-solid fa-graduation-cap" style="color:var(--primary);"></i> Academic & Eligibility Dossier
      </h3>

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.85rem; font-size:0.85rem;">
        <div>
          <span style="color:var(--text-muted); display:block; font-size:0.75rem;">Highest Qualification</span>
          <strong><?= htmlspecialchars($lead['highest_qualification'] ?: 'Not Specified') ?></strong>
        </div>
        <div>
          <span style="color:var(--text-muted); display:block; font-size:0.75rem;">Institution / College</span>
          <strong><?= htmlspecialchars($lead['college_name'] ?: 'Not Specified') ?></strong>
        </div>
        <div>
          <span style="color:var(--text-muted); display:block; font-size:0.75rem;">Graduation Year</span>
          <strong><?= htmlspecialchars($lead['year_of_passing'] ?: '—') ?></strong>
        </div>
        <div>
          <span style="color:var(--text-muted); display:block; font-size:0.75rem;">Percentage / CGPA</span>
          <strong><?= $lead['percentage'] ? htmlspecialchars($lead['percentage']) . '%' : '—' ?></strong>
        </div>
        <div>
          <span style="color:var(--text-muted); display:block; font-size:0.75rem;">Active Backlogs</span>
          <strong><?= (int)$lead['backlogs_count'] ?></strong>
        </div>
        <div>
          <span style="color:var(--text-muted); display:block; font-size:0.75rem;">Work Experience</span>
          <strong><?= $lead['work_experience_years'] ? htmlspecialchars($lead['work_experience_years']) . ' years' : 'Fresher' ?></strong>
        </div>
      </div>

      <!-- English Assessment Box -->
      <div style="margin-top:1.25rem; background:var(--bg-subtle); border-radius:8px; padding:1rem;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.6rem;">
          <strong style="font-size:0.85rem; color:var(--text-heading);">
            <i class="fa-solid fa-language" style="color:var(--primary); margin-right:4px;"></i> English Assessment (<?= htmlspecialchars($lead['english_test'] ?: 'None') ?>)
          </strong>
          <span class="badge badge-primary" style="font-size:0.75rem;">Overall: <?= htmlspecialchars($lead['english_score'] ?: 'Awaiting') ?></span>
        </div>
        <div style="display:grid; grid-template-columns:repeat(4, 1fr); gap:0.5rem; text-align:center; font-size:0.75rem;">
          <div style="background:#fff; padding:4px; border-radius:4px; border:1px solid var(--border-color);">
            <span style="color:var(--text-muted); display:block;">L</span>
            <strong><?= $lead['score_listening'] ?: '—' ?></strong>
          </div>
          <div style="background:#fff; padding:4px; border-radius:4px; border:1px solid var(--border-color);">
            <span style="color:var(--text-muted); display:block;">R</span>
            <strong><?= $lead['score_reading'] ?: '—' ?></strong>
          </div>
          <div style="background:#fff; padding:4px; border-radius:4px; border:1px solid var(--border-color);">
            <span style="color:var(--text-muted); display:block;">W</span>
            <strong><?= $lead['score_writing'] ?: '—' ?></strong>
          </div>
          <div style="background:#fff; padding:4px; border-radius:4px; border:1px solid var(--border-color);">
            <span style="color:var(--text-muted); display:block;">S</span>
            <strong><?= $lead['score_speaking'] ?: '—' ?></strong>
          </div>
        </div>
      </div>
    </div>

    <!-- Consultancy Target Preferences -->
    <div class="card" style="margin-bottom:1.5rem; padding:1.5rem;">
      <h3 style="font-size:1.05rem; font-weight:700; color:var(--text-heading); margin:0 0 1rem 0; display:flex; align-items:center; gap:0.5rem;">
        <i class="fa-solid fa-plane-departure" style="color:var(--primary);"></i> Consultancy Targets
      </h3>
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.85rem; font-size:0.85rem;">
        <div>
          <span style="color:var(--text-muted); display:block; font-size:0.75rem;">Target Country</span>
          <strong><i class="fa-solid fa-flag" style="color:var(--primary); font-size:0.75rem;"></i> <?= htmlspecialchars($lead['preferred_country']) ?></strong>
        </div>
        <div>
          <span style="color:var(--text-muted); display:block; font-size:0.75rem;">Target Course</span>
          <strong><?= htmlspecialchars($lead['preferred_course'] ?: 'General') ?></strong>
        </div>
        <div>
          <span style="color:var(--text-muted); display:block; font-size:0.75rem;">Target Intake</span>
          <strong><?= htmlspecialchars($lead['intake_term']) ?> <?= htmlspecialchars($lead['intake_year']) ?></strong>
        </div>
        <div>
          <span style="color:var(--text-muted); display:block; font-size:0.75rem;">Lead Source</span>
          <span class="badge badge-secondary"><?= htmlspecialchars($lead['source']) ?></span>
        </div>
      </div>
    </div>

    <!-- Student Academic Dossier (Documents) -->
    <div class="card" style="margin-bottom:1.5rem; padding:1.5rem;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
        <h3 style="font-size:1.05rem; font-weight:700; color:var(--text-heading); margin:0; display:flex; align-items:center; gap:0.5rem;">
          <i class="fa-solid fa-folder-open" style="color:var(--primary);"></i> Academic Dossier (<?= count($studentDocs) ?>)
        </h3>
        <?php if (can('documents', 'add')): ?>
          <button type="button" class="btn btn-outline btn-sm" onclick="openDocUploadModal()">
            <i class="fa-solid fa-cloud-arrow-up"></i> Upload Doc
          </button>
        <?php endif; ?>
      </div>

      <?php if (empty($studentDocs)): ?>
        <div style="text-align:center; padding:1.5rem; background:var(--bg-subtle); border-radius:8px; color:var(--text-muted); font-size:0.85rem;">
          <i class="fa-regular fa-folder" style="font-size:1.8rem; margin-bottom:0.5rem; display:block; color:var(--text-light);"></i>
          No student dossier files uploaded yet.
        </div>
      <?php else: ?>
        <div style="display:flex; flex-direction:column; gap:0.5rem;">
          <?php foreach ($studentDocs as $doc): ?>
            <div style="display:flex; justify-content:space-between; align-items:center; padding:0.6rem 0.85rem; background:var(--bg-subtle); border-radius:6px; font-size:0.85rem;">
              <div>
                <span class="badge badge-secondary" style="font-size:0.7rem; margin-right:4px;"><?= htmlspecialchars($doc['category']) ?></span>
                <strong><?= htmlspecialchars($doc['document_name'] ?? $doc['title'] ?? 'Document') ?></strong>
                <span style="font-size:0.72rem; color:var(--text-muted); display:block;"><?= htmlspecialchars($doc['file_name']) ?></span>
              </div>
              <a href="<?= base_url('uploads/documents/' . rawurlencode($doc['file_name'])) ?>" target="_blank" class="btn btn-secondary btn-sm" title="View Document">
                <i class="fa-solid fa-arrow-up-right-from-square"></i>
              </a>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Financial Billing Summary -->
    <?php if (can('invoices', 'view')): ?>
      <div class="card" style="margin-bottom:1.5rem; padding:1.5rem;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
          <h3 style="font-size:1.05rem; font-weight:700; color:var(--text-heading); margin:0; display:flex; align-items:center; gap:0.5rem;">
            <i class="fa-solid fa-file-invoice-dollar" style="color:var(--primary);"></i> Invoicing & Payments
          </h3>
          <?php if (can('invoices', 'add')): ?>
            <a href="<?= base_url('finance/create_invoice.php?lead_id=' . $lead['id']) ?>" class="btn btn-primary btn-sm">
              <i class="fa-solid fa-plus"></i> New Invoice
            </a>
          <?php endif; ?>
        </div>

        <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:0.75rem; text-align:center; margin-bottom:1rem;">
          <div style="background:var(--bg-subtle); padding:0.75rem; border-radius:8px;">
            <span style="font-size:0.72rem; color:var(--text-muted); display:block;">Total Invoiced</span>
            <strong style="font-size:1.1rem; color:var(--text-heading);"><?= format_currency($lead['invoiced_total']) ?></strong>
          </div>
          <div style="background:#dcfce7; padding:0.75rem; border-radius:8px;">
            <span style="font-size:0.72rem; color:#15803d; display:block;">Total Paid</span>
            <strong style="font-size:1.1rem; color:#15803d;"><?= format_currency($lead['paid_total']) ?></strong>
          </div>
          <div style="background:#fee2e2; padding:0.75rem; border-radius:8px;">
            <span style="font-size:0.72rem; color:#b91c1c; display:block;">Balance Due</span>
            <strong style="font-size:1.1rem; color:#b91c1c;"><?= format_currency($lead['balance_total']) ?></strong>
          </div>
        </div>

        <?php if (!empty($invoices)): ?>
          <div style="display:flex; flex-direction:column; gap:0.5rem;">
            <?php foreach ($invoices as $inv): ?>
              <div style="display:flex; justify-content:space-between; align-items:center; padding:0.6rem 0.85rem; border:1px solid var(--border-color); border-radius:6px; font-size:0.85rem;">
                <div>
                  <strong style="font-family:monospace; color:var(--primary);"><?= htmlspecialchars($inv['invoice_number']) ?></strong>
                  <span style="font-size:0.75rem; color:var(--text-muted); margin-left:6px;"><?= date('d M Y', strtotime($inv['issue_date'])) ?></span>
                  <div style="font-weight:700; color:var(--text-heading); margin-top:2px;">
                    <?= format_currency($inv['grand_total']) ?>
                  </div>
                </div>
                <div style="text-align:right;">
                  <span class="badge <?= $inv['payment_status'] === 'Paid' ? 'badge-success' : ($inv['payment_status'] === 'Partially Paid' ? 'badge-warning' : 'badge-danger') ?>" style="font-size:0.72rem;">
                    <?= htmlspecialchars($inv['payment_status']) ?>
                  </span>
                  <a href="<?= base_url('finance/view_invoice.php?id=' . $inv['id']) ?>" class="btn btn-secondary btn-sm" style="display:block; margin-top:4px; font-size:0.75rem; padding:2px 8px;">
                    View &rarr;
                  </a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Right Column: Follow-up Engine & Timeline -->
  <div>
    <!-- Quick Log Follow-up Form Card -->
    <div class="card" style="margin-bottom:1.5rem; padding:1.5rem; border-left:4px solid var(--primary);">
      <h3 style="font-size:1.1rem; font-weight:700; color:var(--text-heading); margin:0 0 1rem 0; display:flex; align-items:center; gap:0.5rem;">
        <i class="fa-solid fa-phone-volume" style="color:var(--primary);"></i> Log Consultation & Set Next Follow-up
      </h3>

      <form method="POST" action="<?= base_url('followups/add.php') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="lead_id" value="<?= $lead['id'] ?>">
        <input type="hidden" name="return_url" value="<?= base_url('leads/view.php?id=' . $lead['id']) ?>">

        <div class="form-group" style="margin-bottom:1rem;">
          <label class="form-label">Interaction Title / Milestone <span style="color:red;">*</span></label>
          <select name="interaction_title_id" id="interaction_title_id" class="form-control" required onchange="onTitleChanged(this)">
            <option value="">Select Interaction Title...</option>
            <?php foreach ($interactionTitles as $it): ?>
              <option value="<?= $it['id'] ?>" data-max-days="<?= $it['max_duration_days'] ?>">
                <?= htmlspecialchars($it['title']) ?> (Max Interval: <?= $it['max_duration_days'] ?> days)
              </option>
            <?php endforeach; ?>
          </select>
          <small id="duration_hint" style="font-size:0.75rem; color:var(--text-muted); display:block; margin-top:2px;">
            Select an interaction title to enforce the governance next-date limit.
          </small>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; margin-bottom:1rem;">
          <div class="form-group">
            <label class="form-label">Mandatory Next Date <span style="color:red;">*</span></label>
            <input type="date" name="next_followup_date" id="next_followup_date" class="form-control" required min="<?= date('Y-m-d') ?>" onchange="checkCounselorWorkload(this.value)">
          </div>
          <div class="form-group">
            <label class="form-label">Scheduled Time</label>
            <input type="time" name="next_followup_time" class="form-control" value="10:00">
          </div>
        </div>

        <!-- Live Workload Counter Indicator -->
        <div id="workload_badge_box" style="display:none; background:#e0e7ff; border-radius:6px; padding:0.6rem 0.85rem; font-size:0.8rem; color:#3730a3; margin-bottom:1rem;">
          <i class="fa-solid fa-chart-line"></i> Counselor Live Workload on selected date: <strong id="workload_count_val">0</strong> scheduled follow-ups.
        </div>

        <div class="form-group" style="margin-bottom:1rem;">
          <label class="form-label">Consultation Notes & Next Action Plan</label>
          <textarea name="notes" class="form-control" rows="3" placeholder="Discussed course requirements, IELTS waiver conditions, pending bank loan sanctions..." required></textarea>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%;">
          <i class="fa-solid fa-calendar-check"></i> Record Follow-up & Schedule Next Task
        </button>
      </form>
    </div>

    <!-- Chronological Consultation Audit Trail -->
    <div class="card" style="padding:1.5rem;">
      <h3 style="font-size:1.05rem; font-weight:700; color:var(--text-heading); margin:0 0 1rem 0; display:flex; align-items:center; gap:0.5rem;">
        <i class="fa-solid fa-clock-rotate-left" style="color:var(--primary);"></i> Follow-up History (<?= count($followups) ?>)
      </h3>

      <?php if (empty($followups)): ?>
        <div style="text-align:center; padding:2rem; color:var(--text-muted); font-size:0.85rem;">
          <i class="fa-regular fa-comment-dots" style="font-size:1.8rem; margin-bottom:0.5rem; display:block; color:var(--text-light);"></i>
          No follow-ups recorded yet. Log your first consultation above.
        </div>
      <?php else: ?>
        <div style="position:relative; padding-left:1.5rem; border-left:2px solid var(--border-color); display:flex; flex-direction:column; gap:1.25rem;">
          <?php foreach ($followups as $f): 
            $isPending = ($f['status'] === 'Pending');
          ?>
            <div style="position:relative;">
              <!-- Timeline node circle -->
              <div style="position:absolute; left:-1.95rem; top:2px; width:12px; height:12px; border-radius:50%; background:<?= $isPending ? 'var(--warning)' : 'var(--primary)' ?>; border:2px solid #fff; box-shadow:0 0 0 2px <?= $isPending ? 'var(--warning)' : 'var(--primary)' ?>;"></div>

              <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:0.25rem;">
                <div>
                  <strong style="font-size:0.9rem; color:var(--text-heading);">
                    <?= htmlspecialchars($f['interaction_title'] ?? $f['interaction_type'] ?? 'Consultation') ?>
                  </strong>
                  <span class="badge <?= $isPending ? 'badge-warning' : 'badge-success' ?>" style="font-size:0.68rem; margin-left:6px;">
                    <?= htmlspecialchars($f['status']) ?>
                  </span>
                </div>
                <span style="font-size:0.75rem; color:var(--text-muted);">
                  <?= date('d M Y', strtotime($f['scheduled_date'])) ?>
                </span>
              </div>

              <div style="font-size:0.825rem; color:var(--text-body); background:var(--bg-subtle); padding:0.65rem 0.85rem; border-radius:6px; margin-top:0.35rem; line-height:1.4;">
                <?= nl2br(htmlspecialchars($f['notes'] ?: 'No notes attached.')) ?>
              </div>

              <div style="font-size:0.72rem; color:var(--text-muted); margin-top:0.35rem;">
                Counselor: <strong><?= htmlspecialchars($f['counselor_name'] ?? 'Staff') ?></strong>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Stage Transition Audit Trail -->
    <div class="card" style="margin-top:1.5rem; padding:1.5rem;">
      <h3 style="font-size:1.05rem; font-weight:700; color:var(--text-heading); margin:0 0 1rem 0; display:flex; align-items:center; gap:0.5rem;">
        <i class="fa-solid fa-list-check" style="color:var(--primary);"></i> Stage Transition Audit Log
      </h3>
      <?php if (empty($stageHistory)): ?>
        <p style="font-size:0.85rem; color:var(--text-muted); margin:0;">No stage transition logs recorded yet.</p>
      <?php else: ?>
        <ul style="margin:0; padding-left:1.25rem; font-size:0.825rem; color:var(--text-muted); display:flex; flex-direction:column; gap:0.5rem;">
          <?php foreach ($stageHistory as $sh): ?>
            <li>
              <strong><?= htmlspecialchars($sh['to_stage']) ?></strong>
              <?php if (!empty($sh['from_stage'])): ?>
                (from <?= htmlspecialchars($sh['from_stage']) ?>)
              <?php endif; ?>
              — by <strong><?= htmlspecialchars($sh['changed_by_name'] ?? 'Admin') ?></strong> on <?= date('d M Y, h:i A', strtotime($sh['created_at'])) ?>
              <?php if (!empty($sh['notes'])): ?>
                <div style="font-size:0.75rem; color:var(--text-heading); margin-top:1px;">&ldquo;<?= htmlspecialchars($sh['notes']) ?>&rdquo;</div>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>

</div>

<!-- Stage Update Modal -->
<div class="modal fade" id="stageModal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(12,36,60,0.5); align-items:center; justify-content:center;">
  <div class="modal-dialog" style="background:#fff; border-radius:14px; max-width:480px; width:92%; margin:auto; box-shadow:0 20px 50px rgba(0,0,0,0.2); overflow:hidden;">
    <form method="POST" action="<?= base_url('admissions/update_stage.php') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="lead_id" value="<?= $lead['id'] ?>">
      <input type="hidden" name="return_url" value="<?= base_url('leads/view.php?id=' . $lead['id']) ?>">

      <div class="modal-header" style="padding:1.25rem 1.5rem; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
        <h3 style="font-size:1.15rem; font-weight:700; margin:0; color:var(--text-heading);">
          <i class="fa-solid fa-diagram-project" style="color:var(--primary); margin-right:8px;"></i> Transition Admission Stage
        </h3>
        <button type="button" onclick="closeStageModal()" style="border:none; background:none; font-size:1.4rem; cursor:pointer; color:var(--text-muted);">&times;</button>
      </div>

      <div class="modal-body" style="padding:1.5rem;">
        <div class="form-group" style="margin-bottom:1.25rem;">
          <label class="form-label">New Pipeline Stage <span style="color:red;">*</span></label>
          <select name="new_stage" class="form-control" required>
            <?php foreach ($standardStages as $sKey => $sMeta): ?>
              <option value="<?= $sKey ?>" <?= $lead['stage'] === $sKey ? 'selected' : '' ?>>
                <?= htmlspecialchars($sMeta['label']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group" style="margin-bottom:1.25rem;">
          <label class="form-label">Secondary Operational Status</label>
          <select name="secondary_status" class="form-control">
            <option value="Active" <?= $lead['secondary_status'] === 'Active' ? 'selected' : '' ?>>Active Progression</option>
            <option value="On Hold" <?= $lead['secondary_status'] === 'On Hold' ? 'selected' : '' ?>>On Hold (Pending Student Decision)</option>
            <option value="Rejected" <?= $lead['secondary_status'] === 'Rejected' ? 'selected' : '' ?>>Rejected by University</option>
            <option value="Lost" <?= $lead['secondary_status'] === 'Lost' ? 'selected' : '' ?>>Lost / Opted Out</option>
          </select>
        </div>

        <div class="form-group" style="margin-bottom:0.5rem;">
          <label class="form-label">Stage Change Justification Note</label>
          <textarea name="notes" class="form-control" rows="2" placeholder="e.g. Conditional offer letter issued by University of Hertfordshire..."></textarea>
        </div>
      </div>

      <div class="modal-footer" style="padding:1rem 1.5rem; border-top:1px solid var(--border-color); display:flex; justify-content:flex-end; gap:0.5rem;">
        <button type="button" onclick="closeStageModal()" class="btn btn-secondary btn-sm">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm">
          <i class="fa-solid fa-check"></i> Save Stage Transition
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Upload Student Dossier Document Modal -->
<div class="modal fade" id="docUploadModal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(12,36,60,0.5); align-items:center; justify-content:center;">
  <div class="modal-dialog" style="background:#fff; border-radius:14px; max-width:480px; width:92%; margin:auto; box-shadow:0 20px 50px rgba(0,0,0,0.2); overflow:hidden;">
    <form method="POST" action="<?= base_url('documents/upload.php') ?>" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="lead_id" value="<?= $lead['id'] ?>">
      <input type="hidden" name="type" value="student">
      <input type="hidden" name="return_url" value="<?= base_url('leads/view.php?id=' . $lead['id']) ?>">

      <div class="modal-header" style="padding:1.25rem 1.5rem; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
        <h3 style="font-size:1.15rem; font-weight:700; margin:0; color:var(--text-heading);">
          <i class="fa-solid fa-cloud-arrow-up" style="color:var(--primary); margin-right:8px;"></i> Upload Dossier Document
        </h3>
        <button type="button" onclick="closeDocUploadModal()" style="border:none; background:none; font-size:1.4rem; cursor:pointer; color:var(--text-muted);">&times;</button>
      </div>

      <div class="modal-body" style="padding:1.5rem;">
        <div class="form-group" style="margin-bottom:1.25rem;">
          <label class="form-label">Dossier Category <span style="color:red;">*</span></label>
          <select name="category" class="form-control" required>
            <option value="Identity">Identity (Passport, ID Proof)</option>
            <option value="Academic">Academic Transcripts (10th, 12th, Degree)</option>
            <option value="English Proficiency">English Proficiency (IELTS / PTE / TOEFL)</option>
            <option value="Financial & Visa">Financial & Visa (Bank Statement, CAS, Visa)</option>
          </select>
        </div>

        <div class="form-group" style="margin-bottom:1.25rem;">
          <label class="form-label">Document Title <span style="color:red;">*</span></label>
          <input type="text" name="title" class="form-control" placeholder="e.g. Passport Front & Back Copy" required>
        </div>

        <div class="form-group" style="margin-bottom:0.5rem;">
          <label class="form-label">Select Document File (PDF / Image) <span style="color:red;">*</span></label>
          <input type="file" name="document_file" class="form-control" accept=".pdf, .png, .jpg, .jpeg" required>
        </div>
      </div>

      <div class="modal-footer" style="padding:1rem 1.5rem; border-top:1px solid var(--border-color); display:flex; justify-content:flex-end; gap:0.5rem;">
        <button type="button" onclick="closeDocUploadModal()" class="btn btn-secondary btn-sm">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm">
          <i class="fa-solid fa-check"></i> Upload to Dossier
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function openStageModal() {
  document.getElementById('stageModal').style.display = 'flex';
}
function closeStageModal() {
  document.getElementById('stageModal').style.display = 'none';
}

function openDocUploadModal() {
  document.getElementById('docUploadModal').style.display = 'flex';
}
function closeDocUploadModal() {
  document.getElementById('docUploadModal').style.display = 'none';
}

function onTitleChanged(sel) {
  const opt = sel.options[sel.selectedIndex];
  const maxDays = opt.getAttribute('data-max-days');
  const dateInput = document.getElementById('next_followup_date');
  const hint = document.getElementById('duration_hint');

  if (maxDays) {
    const today = new Date();
    const maxDate = new Date();
    maxDate.setDate(today.getDate() + parseInt(maxDays));

    const yyyy = maxDate.getFullYear();
    const mm = String(maxDate.getMonth() + 1).padStart(2, '0');
    const dd = String(maxDate.getDate()).padStart(2, '0');
    const maxDateStr = `${yyyy}-${mm}-${dd}`;

    dateInput.max = maxDateStr;
    dateInput.value = maxDateStr; // Pre-fill with allowed target date
    hint.innerHTML = `<span style="color:var(--primary); font-weight:600;"><i class="fa-solid fa-circle-check"></i> Max interval allowed: ${maxDays} days (Latest allowed: ${maxDate.toLocaleDateString('en-GB')})</span>`;
    checkCounselorWorkload(maxDateStr);
  }
}

function checkCounselorWorkload(dateVal) {
  if (!dateVal) return;
  const counselorId = <?= (int)($lead['assigned_counselor_id'] ?: $currentUser['id']) ?>;
  fetch(`<?= base_url('tasks/workload_api.php') ?>?counselor_id=${counselorId}&date=${dateVal}`)
    .then(res => res.json())
    .then(data => {
      const box = document.getElementById('workload_badge_box');
      if (data.success) {
        document.getElementById('workload_count_val').innerText = data.count;
        box.style.display = 'block';
      } else {
        box.style.display = 'none';
      }
    }).catch(() => {});
}

function archiveCurrentLead() {
  const reason = prompt('Please enter the reason for archiving this lead (e.g., student postponed intake, financial shortfall):');
  if (reason) {
    window.location.href = `<?= base_url('archive/action.php?type=lead&id=' . $lead['id'] . '&action=archive&reason=') ?>` + encodeURIComponent(reason);
  }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
