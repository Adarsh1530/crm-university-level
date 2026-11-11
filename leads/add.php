<?php
/**
 * Add New Lead / Student Registration
 * University-Level Education Consultancy CRM (EdrootsCRM)
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
    $email = trim($_POST['email'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $country = trim($_POST['country'] ?? 'India');
    $dob = !empty($_POST['dob']) ? $_POST['dob'] : null;
    $gender = trim($_POST['gender'] ?? 'Other');
    $passportNumber = trim($_POST['passport_number'] ?? '');
    $passportExpiry = !empty($_POST['passport_expiry']) ? $_POST['passport_expiry'] : null;

    $highestQual = trim($_POST['highest_qualification'] ?? '');
    $collegeName = trim($_POST['college_name'] ?? '');
    $yearOfPassing = !empty($_POST['year_of_passing']) ? (int)$_POST['year_of_passing'] : null;
    $percentage = !empty($_POST['percentage']) ? (float)$_POST['percentage'] : null;
    $backlogs = !empty($_POST['backlogs_count']) ? (int)$_POST['backlogs_count'] : 0;
    $workExp = !empty($_POST['work_experience_years']) ? (float)$_POST['work_experience_years'] : 0;

    $englishTest = trim($_POST['english_test'] ?? 'None');
    $englishScore = trim($_POST['english_score'] ?? '');
    $scoreL = !empty($_POST['score_listening']) ? (float)$_POST['score_listening'] : null;
    $scoreR = !empty($_POST['score_reading']) ? (float)$_POST['score_reading'] : null;
    $scoreW = !empty($_POST['score_writing']) ? (float)$_POST['score_writing'] : null;
    $scoreS = !empty($_POST['score_speaking']) ? (float)$_POST['score_speaking'] : null;

    $preferredCountry = trim($_POST['preferred_country'] ?? 'United Kingdom');
    $preferredCourse = trim($_POST['preferred_course'] ?? '');
    $intakeYear = !empty($_POST['intake_year']) ? (int)$_POST['intake_year'] : 2026;
    $intakeTerm = trim($_POST['intake_term'] ?? 'September');
    $priority = trim($_POST['priority'] ?? 'Moderate');
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
            // Check canonical duplicate
            $duplicateLead = check_phone_duplicate($cleanPhone);
            if ($duplicateLead) {
                set_flash('danger', "Duplicate Blocked: An active lead or client already exists with phone <strong>{$cleanPhone}</strong> (" . htmlspecialchars($duplicateLead['full_name']) . " - " . htmlspecialchars($duplicateLead['lead_code']) . ").");
            } else {
                // Generate Lead Code
                $currentYear = date('Y');
                $stmtCount = $pdo->query("SELECT COUNT(*) FROM leads");
                $seq = (int)$stmtCount->fetchColumn() + 1;
                $leadCode = sprintf("EDR-%s-%04d", $currentYear, $seq);

                try {
                    $pdo->beginTransaction();

                    $stmt = $pdo->prepare("
                        INSERT INTO leads (
                            lead_code, full_name, email, phone, city, state, country,
                            dob, gender, passport_number, passport_expiry,
                            branch_id, assigned_counselor_id, stage, secondary_status, priority, source,
                            preferred_country, preferred_course, intake_year, intake_term,
                            highest_qualification, college_name, year_of_passing, percentage, backlogs_count,
                            english_test, english_score, score_listening, score_reading, score_writing, score_speaking,
                            work_experience_years, notes, assigned_at, created_by
                        ) VALUES (
                            ?, ?, ?, ?, ?, ?, ?,
                            ?, ?, ?, ?,
                            ?, ?, 'ENQUIRY', 'Active', ?, ?,
                            ?, ?, ?, ?,
                            ?, ?, ?, ?, ?,
                            ?, ?, ?, ?, ?, ?,
                            ?, ?, NOW(), ?
                        )
                    ");
                    $stmt->execute([
                        $leadCode, $fullName, $email, $cleanPhone, $city, $state, $country,
                        $dob, $gender, $passportNumber, $passportExpiry,
                        $branchId, $counselorId, $priority, $source,
                        $preferredCountry, $preferredCourse, $intakeYear, $intakeTerm,
                        $highestQual, $collegeName, $yearOfPassing, $percentage, $backlogs,
                        $englishTest, $englishScore, $scoreL, $scoreR, $scoreW, $scoreS,
                        $workExp, $notes, $currentUser['id']
                    ]);
                    $newLeadId = $pdo->lastInsertId();

                    // Initial stage history
                    $stmtStage = $pdo->prepare("
                        INSERT INTO lead_stage_history (lead_id, from_stage, to_stage, changed_by, notes)
                        VALUES (?, NULL, 'ENQUIRY', ?, 'Lead registered in CRM')
                    ");
                    $stmtStage->execute([$newLeadId, $currentUser['id']]);

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
    <h1 style="font-size:1.5rem; font-weight:700; color:var(--text-heading); margin:0 0 0.35rem 0;">Register New Student Lead</h1>
    <p style="font-size:0.875rem; color:var(--text-muted); margin:0;">
      Fast and flexible candidate registration. Essential study details are required; transcripts and test scores can be completed now or anytime in the student dossier.
    </p>
  </div>
  <div style="display:flex; gap:0.5rem; align-items:center;">
    <button type="button" onclick="openQuickLeadModal()" class="btn btn-primary btn-sm" style="background:#7008E7; border-color:#7008E7;">
      <i class="fa-solid fa-bolt"></i> 1-Click Fast Add
    </button>
    <a href="<?= base_url('leads/index.php') ?>" class="btn btn-secondary btn-sm">
      <i class="fa-solid fa-arrow-left"></i> Back to Caseload
    </a>
  </div>
</div>

<!-- Friendly Guidance Tip -->
<div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:12px; padding:0.85rem 1.25rem; margin-bottom:1.5rem; display:flex; align-items:center; gap:0.75rem;">
  <div style="width:36px; height:36px; border-radius:50%; background:#dcfce7; color:#15803d; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
    <i class="fa-solid fa-wand-magic-sparkles"></i>
  </div>
  <div style="font-size:0.85rem; color:#166534; line-height:1.4;">
    <strong>Streamlined Registration:</strong> Fill the <strong>Essential Details</strong> below to create the lead immediately. Academic history, English scores, and passport details are optional and can be expanded if already available.
  </div>
</div>

<form method="POST" action="<?= base_url('leads/add.php') ?>">
  <?= csrf_field() ?>

  <!-- 1. ESSENTIAL ADMISSION DETAILS (Primary Card) -->
  <div class="card" style="margin-bottom:1.5rem; padding:1.75rem; border:1px solid rgba(89,85,209,0.2); box-shadow:0 4px 14px rgba(89,85,209,0.06);">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.25rem; border-bottom:1px solid var(--border-color); padding-bottom:0.75rem;">
      <h3 style="font-size:1.15rem; font-weight:700; color:var(--text-heading); margin:0; display:flex; align-items:center; gap:0.5rem;">
        <i class="fa-solid fa-user-graduate" style="color:var(--primary);"></i> 1. Essential Student & Study Details
      </h3>
      <span class="badge badge-primary" style="font-size:0.72rem; padding:3px 8px;">Required Core Fields</span>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:1.25rem;">
      <!-- Full Name -->
      <div class="form-group">
        <label class="form-label" style="font-weight:700;">Full Name <span style="color:red;">*</span></label>
        <input type="text" name="full_name" class="form-control" placeholder="e.g. Arun Kumar" required value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" autofocus>
      </div>

      <!-- Phone Number (With Real-Time Duplicate Validation - User Manual Page 13) -->
      <div class="form-group">
        <label class="form-label" style="font-weight:700;">Phone / WhatsApp (10-Digits) <span style="color:red;">*</span></label>
        <div style="position:relative;">
          <input type="tel" id="leadAddPhone" name="phone" class="form-control" placeholder="e.g. 9847112233" required value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" oninput="checkLeadPhoneDuplicate(this.value, 'leadAddPhoneFeedback', 'btn-save-lead-details')">
          <i class="fa-solid fa-phone" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); color:var(--text-light);"></i>
        </div>
        <div id="leadAddPhoneFeedback" style="display:none; margin-top:6px; font-size:0.8rem;"></div>
        <small style="font-size:0.72rem; color:var(--text-muted);">Country code will be automatically normalized.</small>
      </div>

      <!-- Email Address -->
      <div class="form-group">
        <label class="form-label" style="font-weight:600;">Email Address</label>
        <input type="email" name="email" class="form-control" placeholder="arun.kumar@gmail.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>

      <!-- Target Country -->
      <div class="form-group">
        <label class="form-label" style="font-weight:700;">Target Study Country <span style="color:red;">*</span></label>
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

      <!-- Desired Course -->
      <div class="form-group">
        <label class="form-label" style="font-weight:600;">Desired Degree / Course</label>
        <input type="text" name="preferred_course" class="form-control" placeholder="e.g. M.Sc Data Science / MBA" value="<?= htmlspecialchars($_POST['preferred_course'] ?? '') ?>">
      </div>

      <!-- Intake Year & Term -->
      <div class="form-group">
        <label class="form-label" style="font-weight:600;">Target Intake</label>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.5rem;">
          <select name="intake_year" class="form-control">
            <option value="2026" selected>2026</option>
            <option value="2027">2027</option>
          </select>
          <select name="intake_term" class="form-control">
            <option value="September" selected>September</option>
            <option value="January">January</option>
            <option value="May">May</option>
          </select>
        </div>
      </div>

      <!-- Priority Level -->
      <div class="form-group">
        <label class="form-label" style="font-weight:600;">Priority Level</label>
        <select name="priority" class="form-control">
          <option value="High">🔥 High Priority (Urgent)</option>
          <option value="Moderate" selected>⚡ Moderate Priority</option>
          <option value="Low">🌱 Low Priority</option>
        </select>
      </div>

      <!-- Lead Source -->
      <div class="form-group">
        <label class="form-label" style="font-weight:600;">Lead Source</label>
        <select name="source" class="form-control">
          <option value="Walk-in">Walk-in Inquiry</option>
          <option value="Website">Website Form</option>
          <option value="College Data">College Data Pool</option>
          <option value="Referral">Student Referral</option>
          <option value="Meta Ads">Meta / Social Ads</option>
          <option value="Google Ads">Google Ads</option>
        </select>
      </div>

      <!-- Branch Assignment -->
      <div class="form-group">
        <label class="form-label" style="font-weight:700;">Division / Branch <span style="color:red;">*</span></label>
        <?php if (is_super_admin()): ?>
          <select name="branch_id" id="lead_branch_id" class="form-control" required onchange="filterCounselors(this.value)">
            <option value="">Select Division...</option>
            <?php foreach ($branches as $b): ?>
              <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
            <?php endforeach; ?>
          </select>
        <?php else: ?>
          <input type="hidden" name="branch_id" value="<?= $currentUser['branch_id'] ?? 1 ?>">
          <input type="text" class="form-control" value="<?= htmlspecialchars($currentUser['branch_name'] ?? 'Main Admissions Division') ?>" disabled style="background:#f1f5f9;">
        <?php endif; ?>
      </div>

      <!-- Counselor Assignment -->
      <div class="form-group">
        <label class="form-label" style="font-weight:700;">Assigned Counselor <span style="color:red;">*</span></label>
        <?php if (is_counselor()): ?>
          <input type="hidden" name="counselor_id" value="<?= $currentUser['id'] ?>">
          <input type="text" class="form-control" value="<?= htmlspecialchars($currentUser['name']) ?> (You)" disabled style="background:#f1f5f9;">
        <?php else: ?>
          <select name="counselor_id" id="lead_counselor_id" class="form-control" required>
            <option value="">Select Counselor...</option>
            <?php foreach ($counselors as $c): ?>
              <option value="<?= $c['id'] ?>" data-branch="<?= $c['branch_id'] ?>">
                <?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['branch_name'] ?? 'Division') ?>)
              </option>
            <?php endforeach; ?>
          </select>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- 2. OPTIONAL: ACADEMIC BACKGROUND & ENGLISH PROFICIENCY (Accordion) -->
  <div class="card" style="margin-bottom:1.5rem; overflow:hidden;">
    <details style="cursor:pointer;" <?= (!empty($_POST['highest_qualification']) || !empty($_POST['english_score'])) ? 'open' : '' ?>>
      <summary style="padding:1.25rem 1.75rem; background:var(--bg-subtle); display:flex; justify-content:space-between; align-items:center; user-select:none; font-weight:700; color:var(--text-heading); outline:none;">
        <div style="display:flex; align-items:center; gap:0.6rem; font-size:1.05rem;">
          <i class="fa-solid fa-graduation-cap" style="color:var(--primary);"></i>
          2. Academic Background & English Proficiency (Optional)
        </div>
        <span style="font-size:0.8rem; color:var(--primary); font-weight:600; display:flex; align-items:center; gap:4px;">
          Click to Expand / Collapse <i class="fa-solid fa-chevron-down"></i>
        </span>
      </summary>

      <div style="padding:1.5rem; border-top:1px solid var(--border-color); cursor:default;">
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:1.25rem; margin-bottom:1.25rem;">
          <div class="form-group">
            <label class="form-label">Highest Qualification</label>
            <input type="text" name="highest_qualification" class="form-control" placeholder="e.g. B.Tech Computer Science, BBA" value="<?= htmlspecialchars($_POST['highest_qualification'] ?? '') ?>">
          </div>

          <div class="form-group">
            <label class="form-label">College / University Name</label>
            <input type="text" name="college_name" class="form-control" placeholder="e.g. State University, City College" value="<?= htmlspecialchars($_POST['college_name'] ?? '') ?>">
          </div>

          <div class="form-group">
            <label class="form-label">Year of Passing</label>
            <input type="number" name="year_of_passing" class="form-control" placeholder="2025" min="2010" max="2035" value="<?= htmlspecialchars($_POST['year_of_passing'] ?? '2025') ?>">
          </div>

          <div class="form-group">
            <label class="form-label">Percentage / CGPA</label>
            <input type="number" step="0.01" name="percentage" class="form-control" placeholder="e.g. 74.5" value="<?= htmlspecialchars($_POST['percentage'] ?? '') ?>">
          </div>

          <div class="form-group">
            <label class="form-label">Active / Total Backlogs</label>
            <input type="number" name="backlogs_count" class="form-control" placeholder="0" min="0" value="<?= htmlspecialchars($_POST['backlogs_count'] ?? '0') ?>">
          </div>

          <div class="form-group">
            <label class="form-label">Work Experience (Years)</label>
            <input type="number" step="0.5" name="work_experience_years" class="form-control" placeholder="0" min="0" value="<?= htmlspecialchars($_POST['work_experience_years'] ?? '0') ?>">
          </div>
        </div>

        <!-- English Proficiency Sub-section -->
        <div style="background:var(--bg-subtle); border-radius:12px; padding:1.25rem; border:1px solid var(--border-color);">
          <h4 style="font-size:0.95rem; font-weight:700; color:var(--text-heading); margin:0 0 1rem 0;">
            <i class="fa-solid fa-language" style="color:var(--primary); margin-right:6px;"></i> English Language Assessment
          </h4>
          <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(130px, 1fr)); gap:1rem;">
            <div class="form-group">
              <label class="form-label">Test Type</label>
              <select name="english_test" class="form-control">
                <option value="IELTS">IELTS</option>
                <option value="PTE">PTE Academic</option>
                <option value="TOEFL">TOEFL</option>
                <option value="Duolingo">Duolingo</option>
                <option value="None" selected>None / Awaiting</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Overall Score</label>
              <input type="text" name="english_score" class="form-control" placeholder="e.g. 7.0 / 68" value="<?= htmlspecialchars($_POST['english_score'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Listening</label>
              <input type="number" step="0.5" name="score_listening" class="form-control" placeholder="7.0">
            </div>
            <div class="form-group">
              <label class="form-label">Reading</label>
              <input type="number" step="0.5" name="score_reading" class="form-control" placeholder="6.5">
            </div>
            <div class="form-group">
              <label class="form-label">Writing</label>
              <input type="number" step="0.5" name="score_writing" class="form-control" placeholder="6.5">
            </div>
            <div class="form-group">
              <label class="form-label">Speaking</label>
              <input type="number" step="0.5" name="score_speaking" class="form-control" placeholder="7.0">
            </div>
          </div>
        </div>
      </div>
    </details>
  </div>

  <!-- 3. OPTIONAL: PERSONAL & PASSPORT DETAILS (Accordion) -->
  <div class="card" style="margin-bottom:1.5rem; overflow:hidden;">
    <details style="cursor:pointer;" <?= (!empty($_POST['passport_number']) || !empty($_POST['city'])) ? 'open' : '' ?>>
      <summary style="padding:1.25rem 1.75rem; background:var(--bg-subtle); display:flex; justify-content:space-between; align-items:center; user-select:none; font-weight:700; color:var(--text-heading); outline:none;">
        <div style="display:flex; align-items:center; gap:0.6rem; font-size:1.05rem;">
          <i class="fa-solid fa-passport" style="color:var(--primary);"></i>
          3. Additional Personal & Passport Information (Optional)
        </div>
        <span style="font-size:0.8rem; color:var(--primary); font-weight:600; display:flex; align-items:center; gap:4px;">
          Click to Expand / Collapse <i class="fa-solid fa-chevron-down"></i>
        </span>
      </summary>

      <div style="padding:1.5rem; border-top:1px solid var(--border-color); cursor:default;">
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:1.25rem;">
          <div class="form-group">
            <label class="form-label">Date of Birth</label>
            <input type="date" name="dob" class="form-control" value="<?= htmlspecialchars($_POST['dob'] ?? '') ?>">
          </div>

          <div class="form-group">
            <label class="form-label">Gender</label>
            <select name="gender" class="form-control">
              <option value="Male">Male</option>
              <option value="Female">Female</option>
              <option value="Other">Other</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">City / Residence</label>
            <input type="text" name="city" class="form-control" placeholder="e.g. City Name" value="<?= htmlspecialchars($_POST['city'] ?? '') ?>">
          </div>

          <div class="form-group">
            <label class="form-label">Passport Number</label>
            <input type="text" name="passport_number" class="form-control" placeholder="e.g. Z1234567" value="<?= htmlspecialchars($_POST['passport_number'] ?? '') ?>">
          </div>

          <div class="form-group">
            <label class="form-label">Passport Expiry Date</label>
            <input type="date" name="passport_expiry" class="form-control" value="<?= htmlspecialchars($_POST['passport_expiry'] ?? '') ?>">
          </div>
        </div>
      </div>
    </details>
  </div>

  <!-- 4. COUNSELING REMARKS & SUBMISSION -->
  <div class="card" style="margin-bottom:2rem; padding:1.5rem;">
    <div class="form-group" style="margin-bottom:1.25rem;">
      <label class="form-label" style="font-weight:700;">Initial Counseling Remarks / Notes</label>
      <textarea name="notes" class="form-control" rows="3" placeholder="Consultation notes, student aspirations, budget constraints, documents pending..."></textarea>
    </div>

    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; border-top:1px solid var(--border-color); padding-top:1.25rem;">
      <a href="<?= base_url('leads/index.php') ?>" class="btn btn-secondary">
        <i class="fa-solid fa-times"></i> Cancel
      </a>
      <div style="display:flex; gap:0.75rem;">
        <button type="submit" id="btn-save-lead-details" class="btn btn-primary btn-lg" style="padding:0.75rem 2rem; font-weight:700; box-shadow:0 4px 14px rgba(89,85,209,0.3);">
          <i class="fa-solid fa-check"></i> Save Details
        </button>
      </div>
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