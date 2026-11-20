<?php
/**
 * Add New Lead / Student Registration
 * Enterprise University Admissions & Enrollment Management System (UniCRM)
 * Aligned 1:1 with User Manual Section 9.2.7 & Figure p13
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_permission('leads', 'add');

$currentUser = current_user();
$pageTitle = 'Add New Lead';
$activePage = 'add_lead';

$branches = $pdo->query("SELECT id, name, city FROM branches WHERE status = 'Active' ORDER BY name ASC")->fetchAll();
$intakes = $pdo->query("SELECT name, year, term FROM intake_cycles WHERE status = 'Active' ORDER BY sort_order ASC")->fetchAll();

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

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? '');

    $fullName = trim($_POST['full_name'] ?? '');
    $rawPhone = trim($_POST['phone'] ?? '');
    $alternatePhone = trim($_POST['alternate_phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $country = trim($_POST['country'] ?? 'India');
    $dob = !empty($_POST['dob']) ? $_POST['dob'] : null;
    $gender = trim($_POST['gender'] ?? 'Other');

    $highestQual = trim($_POST['highest_qualification'] ?? '');
    $collegeName = trim($_POST['college_name'] ?? '');

    $hasIelts = isset($_POST['has_ielts']) ? 1 : 0;
    $ieltsScore = trim($_POST['ielts_score'] ?? '');
    $hasPan = isset($_POST['has_pancard']) ? 1 : 0;
    $panNumber = trim($_POST['pan_number'] ?? '');

    $preferredCountry = trim($_POST['preferred_country'] ?? 'United Kingdom');
    $preferredCourse = trim($_POST['preferred_course'] ?? '');
    $intakeYear = !empty($_POST['intake_year']) ? (int)$_POST['intake_year'] : 2026;
    $intakeTerm = trim($_POST['intake_term'] ?? 'September');
    $prospectivity = trim($_POST['prospectivity'] ?? 'Warm');
    $priority = $prospectivity; // Keep mapped
    $source = trim($_POST['source'] ?? 'Walk-in');
    $notes = trim($_POST['notes'] ?? '');

    // Branch & Counselor Assignment resolution
    if (is_counselor()) {
        $branchId = (int)$currentUser['branch_id'];
        $counselorId = (int)$currentUser['id'];
    } elseif (is_branch_manager()) {
        $branchId = (int)$currentUser['branch_id'];
        $counselorId = (int)($_POST['counselor_id'] ?? 0);
    } else {
        $branchId = (int)($_POST['branch_id'] ?? 0);
        $counselorId = (int)($_POST['counselor_id'] ?? 0);
    }

    if (empty($fullName) || empty($rawPhone) || $branchId <= 0 || $counselorId <= 0) {
        set_flash('danger', 'Please enter candidate Full Name, Phone Number, and select valid Branch and Counselor.');
    } else {
        $cleanPhone = normalize_phone($rawPhone);
        if (strlen($cleanPhone) < 10) {
            set_flash('danger', 'Please provide a valid 10-digit phone number.');
        } else {
            // Check canonical duplicate: Only one lead can be created using a mobile number
            $duplicateLead = check_phone_duplicate($cleanPhone);
            if ($duplicateLead) {
                set_flash('danger', "Duplicate Blocked: An active lead or client already exists with phone <strong>{$cleanPhone}</strong> (" . htmlspecialchars($duplicateLead['full_name']) . " - " . htmlspecialchars($duplicateLead['lead_code']) . ").");
            } else {
                // Generate Lead Code (e.g. LED-2026-0010)
                $currentYear = date('Y');
                $stmtCount = $pdo->query("SELECT COUNT(*) FROM leads");
                $seq = (int)$stmtCount->fetchColumn() + 1;
                $leadCode = sprintf("LED-%s-%04d", $currentYear, $seq);

                try {
                    $pdo->beginTransaction();

                    $stmt = $pdo->prepare("
                        INSERT INTO leads (
                            lead_code, full_name, email, phone, alternate_phone, city, state, country,
                            dob, gender, pan_number,
                            has_ielts, english_test, english_score, ielts_overall,
                            branch_id, assigned_counselor_id, stage, secondary_status, prospectivity, priority, source,
                            preferred_country, preferred_course, intake_year, intake_term,
                            highest_qualification, college_name,
                            notes, assigned_at, created_by
                        ) VALUES (
                            ?, ?, ?, ?, ?, ?, ?, ?,
                            ?, ?, ?,
                            ?, ?, ?, ?,
                            ?, ?, 'ENQUIRY', 'Active', ?, ?, ?,
                            ?, ?, ?, ?,
                            ?, ?,
                            ?, NOW(), ?
                        )
                    ");
                    $stmt->execute([
                        $leadCode, $fullName, $email, $cleanPhone, $alternatePhone, $city, $state, $country,
                        $dob, $gender, $panNumber,
                        $hasIelts, ($hasIelts ? 'IELTS' : 'None'), $ieltsScore, $ieltsScore,
                        $branchId, $counselorId, $prospectivity, $priority, $source,
                        $preferredCountry, $preferredCourse, $intakeYear, $intakeTerm,
                        $highestQual, $collegeName,
                        $notes, $currentUser['id']
                    ]);
                    $newLeadId = $pdo->lastInsertId();

                    // Notification
                    if ($counselorId !== (int)$currentUser['id']) {
                        $notifStmt = $pdo->prepare("
                            INSERT INTO notifications (user_id, title, message, link, status)
                            VALUES (?, 'New Lead Assigned to You', ?, ?, 'Not Attended')
                        ");
                        $notifStmt->execute([
                            $counselorId,
                            "Candidate {$fullName} has been registered and assigned to your caseload.",
                            "leads/view.php?id={$newLeadId}"
                        ]);
                    }

                    // Activity log
                    log_activity(
                        $currentUser['id'],
                        $newLeadId,
                        'Lead Created',
                        "Lead {$fullName} ({$cleanPhone}) registered as {$leadCode}."
                    );

                    $pdo->commit();
                    set_flash('success', "Lead <strong>" . htmlspecialchars($fullName) . "</strong> registered successfully with ID <strong>{$leadCode}</strong>.");
                    redirect('leads/view.php?id=' . $newLeadId);
                } catch (Exception $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    set_flash('danger', 'Error saving lead: ' . htmlspecialchars($e->getMessage()));
                }
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<!-- Breadcrumb & Page Header -->
<div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <h1 style="font-size:1.5rem; font-weight:700; color:var(--text-heading); margin:0 0 0.35rem 0;">Create New Lead</h1>
    <p style="font-size:0.875rem; color:var(--text-muted); margin:0;">
      Register candidate details, regional branch allocation, target study destination, and prospectivity tier.
    </p>
  </div>
  <div style="display:flex; gap:0.5rem; align-items:center;">
    <button type="button" onclick="openQuickLeadModal()" class="btn btn-primary btn-sm" style="background:#7008E7; border-color:#7008E7;">
      <i class="fa-solid fa-bolt"></i> 1-Click Fast Add
    </button>
    <a href="<?= base_url('leads/index.php') ?>" class="btn btn-secondary btn-sm">
      <i class="fa-solid fa-arrow-left"></i> Back to Student Directory
    </a>
  </div>
</div>

<form method="POST" action="<?= base_url('leads/add.php') ?>">
  <?= csrf_field() ?>

  <!-- Core Student & Study Registration Card (Figure p13) -->
  <div class="card" style="margin-bottom:1.5rem; padding:1.75rem; border:1px solid rgba(89,85,209,0.2); box-shadow:0 4px 14px rgba(89,85,209,0.06);">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.25rem; border-bottom:1px solid var(--border-color); padding-bottom:0.75rem;">
      <h3 style="font-size:1.15rem; font-weight:700; color:var(--text-heading); margin:0; display:flex; align-items:center; gap:0.5rem;">
        <i class="fa-solid fa-user-graduate" style="color:var(--primary);"></i> Student Lead Information
      </h3>
      <span class="badge badge-primary" style="font-size:0.72rem; padding:3px 8px;">Section 9.2.7</span>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:1.25rem;">
      <!-- Mobile Number (Required, with Real-Time Duplicate Validation - User Manual Page 13) -->
      <div class="form-group">
        <label class="form-label" style="font-weight:700;">Mobile Number <span style="color:red;">*</span></label>
        <div style="position:relative;">
          <input type="tel" id="leadAddPhone" name="phone" class="form-control" placeholder="e.g. 9847112233" required value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" oninput="checkLeadPhoneDuplicate(this.value, 'leadAddPhoneFeedback', 'btn-save-lead-details')">
          <i class="fa-solid fa-phone" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); color:var(--text-light);"></i>
        </div>
        <div id="leadAddPhoneFeedback" style="display:none; margin-top:6px; font-size:0.8rem;"></div>
        <small style="font-size:0.72rem; color:var(--text-muted);">Only one lead can be created per mobile number.</small>
      </div>

      <!-- Location / Branch Allocation -->
      <div class="form-group">
        <label class="form-label" style="font-weight:700;">Location / Branch <span style="color:red;">*</span></label>
        <?php if (is_super_admin()): ?>
          <select name="branch_id" id="lead_branch_id" class="form-control" required onchange="filterCounselors(this.value)">
            <option value="">Select Branch...</option>
            <?php foreach ($branches as $b): ?>
              <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
            <?php endforeach; ?>
          </select>
        <?php else: ?>
          <input type="hidden" name="branch_id" value="<?= $currentUser['branch_id'] ?? 1 ?>">
          <input type="text" class="form-control" value="<?= htmlspecialchars($currentUser['branch_name'] ?? 'Main Branch') ?>" disabled style="background:#f1f5f9;">
        <?php endif; ?>
      </div>

      <!-- Prospectivity Level (Section 9.2.3) -->
      <div class="form-group">
        <label class="form-label" style="font-weight:700;">Prospectivity <span style="color:red;">*</span></label>
        <select name="prospectivity" class="form-control" required>
          <option value="Hot">🔥 Hot (High Intent)</option>
          <option value="Warm" selected>⚡ Warm (Moderate)</option>
          <option value="Cold">🌱 Cold (Low / Awaiting)</option>
        </select>
      </div>

      <!-- InTake Year & Term (Section 9.2.4) -->
      <div class="form-group">
        <label class="form-label" style="font-weight:700;">InTake <span style="color:red;">*</span></label>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.5rem;">
          <select name="intake_term" class="form-control">
            <option value="September" selected>September</option>
            <option value="January">January</option>
            <option value="May">May</option>
          </select>
          <select name="intake_year" class="form-control">
            <option value="2026" selected>2026</option>
            <option value="2027">2027</option>
            <option value="2028">2028</option>
          </select>
        </div>
      </div>

      <!-- Full Name -->
      <div class="form-group">
        <label class="form-label" style="font-weight:700;">Student Full Name <span style="color:red;">*</span></label>
        <input type="text" name="full_name" class="form-control" placeholder="e.g. Arun Kumar" required value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
      </div>

      <!-- Date of Birth -->
      <div class="form-group">
        <label class="form-label" style="font-weight:600;">Date of Birth</label>
        <input type="date" name="dob" class="form-control" value="<?= htmlspecialchars($_POST['dob'] ?? '') ?>">
      </div>

      <!-- Gender -->
      <div class="form-group">
        <label class="form-label" style="font-weight:600;">Gender</label>
        <select name="gender" class="form-control">
          <option value="Male">Male</option>
          <option value="Female">Female</option>
          <option value="Other">Other</option>
        </select>
      </div>

      <!-- Secondary Phone -->
      <div class="form-group">
        <label class="form-label" style="font-weight:600;">Secondary / Landline Phone</label>
        <input type="text" name="alternate_phone" class="form-control" placeholder="e.g. 0495-2345678" value="<?= htmlspecialchars($_POST['alternate_phone'] ?? '') ?>">
      </div>

      <!-- Email Address -->
      <div class="form-group">
        <label class="form-label" style="font-weight:600;">Email Address</label>
        <input type="email" name="email" class="form-control" placeholder="student@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>

      <!-- Qualification -->
      <div class="form-group">
        <label class="form-label" style="font-weight:600;">Highest Qualification</label>
        <input type="text" name="highest_qualification" class="form-control" placeholder="e.g. B.Tech Mechanical / B.Com" value="<?= htmlspecialchars($_POST['highest_qualification'] ?? '') ?>">
      </div>

      <!-- College / Institution -->
      <div class="form-group">
        <label class="form-label" style="font-weight:600;">College / Institution</label>
        <input type="text" name="college_name" class="form-control" placeholder="e.g. Govt Engineering College" value="<?= htmlspecialchars($_POST['college_name'] ?? '') ?>">
      </div>

      <!-- Lead Source -->
      <div class="form-group">
        <label class="form-label" style="font-weight:600;">Source of Lead</label>
        <select name="source" class="form-control">
          <option value="Walk-in">Walk-in Inquiry</option>
          <option value="Website">Website Form</option>
          <option value="College Data">College Data Pool</option>
          <option value="Student Referral">Student Referral</option>
          <option value="Meta Ads">Meta / Social Ads</option>
          <option value="Google Ads">Google Ads</option>
          <option value="Direct Reference">Direct Reference</option>
        </select>
      </div>

      <!-- Address / City -->
      <div class="form-group">
        <label class="form-label" style="font-weight:600;">City / Address</label>
        <input type="text" name="city" class="form-control" placeholder="e.g. Calicut, Kerala" value="<?= htmlspecialchars($_POST['city'] ?? '') ?>">
      </div>

      <!-- Preferred Country -->
      <div class="form-group">
        <label class="form-label" style="font-weight:700;">Preferred Country <span style="color:red;">*</span></label>
        <select name="preferred_country" class="form-control" required>
          <option value="United Kingdom" selected>United Kingdom (UK)</option>
          <option value="Canada">Canada</option>
          <option value="Australia">Australia</option>
          <option value="United States">United States (USA)</option>
          <option value="Ireland">Ireland</option>
          <option value="Germany">Germany</option>
          <option value="New Zealand">New Zealand</option>
          <option value="Europe General">Europe General</option>
        </select>
      </div>

      <!-- Preferred Course -->
      <div class="form-group">
        <label class="form-label" style="font-weight:600;">Preferred Course</label>
        <input type="text" name="preferred_course" class="form-control" placeholder="e.g. M.Sc Automotive Engineering" value="<?= htmlspecialchars($_POST['preferred_course'] ?? '') ?>">
      </div>

      <!-- Assigned Counselor -->
      <div class="form-group">
        <label class="form-label" style="font-weight:700;">Assigned To <span style="color:red;">*</span></label>
        <?php if (is_counselor()): ?>
          <input type="hidden" name="counselor_id" value="<?= $currentUser['id'] ?>">
          <input type="text" class="form-control" value="<?= htmlspecialchars($currentUser['name']) ?> (You)" disabled style="background:#f1f5f9;">
        <?php else: ?>
          <select name="counselor_id" id="lead_counselor_id" class="form-control" required>
            <option value="">Select Counselor...</option>
            <?php foreach ($counselors as $c): ?>
              <option value="<?= $c['id'] ?>" data-branch="<?= $c['branch_id'] ?>">
                <?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['branch_name'] ?? 'Branch') ?>)
              </option>
            <?php endforeach; ?>
          </select>
        <?php endif; ?>
      </div>
    </div>

    <!-- Quick Verification Section: IELTS & PAN Card (Figure p13 & Section 9.3) -->
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.25rem; margin-top:1.25rem; padding-top:1.25rem; border-top:1px solid var(--border-color);">
      <!-- IELTS -->
      <div style="background:var(--bg-subtle); border-radius:10px; padding:1rem; border:1px solid var(--border-color);">
        <label style="display:flex; align-items:center; gap:0.5rem; font-weight:700; cursor:pointer; color:var(--text-heading);">
          <input type="checkbox" name="has_ielts" id="chk_has_ielts" value="1" onchange="document.getElementById('ielts_score_wrap').style.display = this.checked ? 'block' : 'none';">
          <span>Taken IELTS Exam?</span>
        </label>
        <div id="ielts_score_wrap" style="display:none; margin-top:0.75rem;">
          <label class="form-label" style="font-size:0.78rem;">IELTS Overall Band Score</label>
          <input type="text" name="ielts_score" class="form-control" placeholder="e.g. 7.0 or 6.5">
        </div>
      </div>

      <!-- PAN Card -->
      <div style="background:var(--bg-subtle); border-radius:10px; padding:1rem; border:1px solid var(--border-color);">
        <label style="display:flex; align-items:center; gap:0.5rem; font-weight:700; cursor:pointer; color:var(--text-heading);">
          <input type="checkbox" name="has_pancard" id="chk_has_pancard" value="1" onchange="document.getElementById('pan_number_wrap').style.display = this.checked ? 'block' : 'none';">
          <span>Have PAN Card?</span>
        </label>
        <div id="pan_number_wrap" style="display:none; margin-top:0.75rem;">
          <label class="form-label" style="font-size:0.78rem;">PAN Card Number</label>
          <input type="text" name="pan_number" class="form-control" placeholder="e.g. ABCDE1234F" style="text-transform:uppercase;">
        </div>
      </div>
    </div>

    <!-- Current Status / Consultation Note -->
    <div class="form-group" style="margin-top:1.25rem;">
      <label class="form-label" style="font-weight:700;">Current Status / Consultation Note</label>
      <textarea name="notes" class="form-control" rows="3" placeholder="Initial student consultation remarks, course interests, or next follow-up notes..."></textarea>
    </div>

    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; border-top:1px solid var(--border-color); padding-top:1.25rem; margin-top:1.25rem;">
      <a href="<?= base_url('leads/index.php') ?>" class="btn btn-secondary">
        <i class="fa-solid fa-times"></i> Cancel
      </a>
      <button type="submit" id="btn-save-lead-details" class="btn btn-primary btn-lg" style="padding:0.65rem 2rem; font-weight:700;">
        <i class="fa-solid fa-check"></i> Save Details
      </button>
    </div>
  </div>
</form>

<script>
function filterCounselors(branchId) {
  const sel = document.getElementById('lead_counselor_id');
  if (!sel) return;
  sel.querySelectorAll('option').forEach((opt, idx) => {
    if (idx === 0) return;
    const bId = opt.getAttribute('data-branch');
    if (!branchId || bId === branchId) {
      opt.style.display = '';
    } else {
      opt.style.display = 'none';
      if (opt.selected) sel.selectedIndex = 0;
    }
  });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
