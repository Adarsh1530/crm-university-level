<?php
/**
 * Edit Student Lead Record
 * Enterprise University Admissions & Enrollment Management System (UniCRM)
 * Aligned 1:1 with User Manual Section 9.2.8 & Figure p14
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_permission('leads', 'edit');

$leadId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$currentUser = current_user();

if ($leadId <= 0 || !can_access_lead($leadId)) {
    set_flash('danger', 'Access Denied: You cannot modify this student record.');
    redirect('leads/index.php');
}

$stmt = $pdo->prepare("SELECT * FROM leads WHERE id = ? LIMIT 1");
$stmt->execute([$leadId]);
$lead = $stmt->fetch();

if (!$lead) {
    set_flash('danger', 'Lead record not found.');
    redirect('leads/index.php');
}

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

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? '');

    $fullName = trim($_POST['full_name'] ?? '');
    $rawPhone = trim($_POST['phone'] ?? '');
    $alternatePhone = trim($_POST['alternate_phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $city = trim($_POST['city'] ?? '');
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
    $priority = $prospectivity;
    $source = trim($_POST['source'] ?? 'Walk-in');
    $notes = trim($_POST['notes'] ?? '');

    $cleanPhone = normalize_phone($rawPhone);
    if (empty($fullName) || strlen($cleanPhone) < 10) {
        set_flash('danger', 'Please enter a valid Full Name and 10-digit Phone Number.');
    } else {
        // Check duplicate excluding this lead
        $dup = check_phone_duplicate($cleanPhone, $leadId);
        if ($dup) {
            set_flash('danger', "Duplicate Blocked: Phone number {$cleanPhone} is already in use by lead " . htmlspecialchars($dup['full_name']) . " ({$dup['lead_code']}).");
        } else {
            try {
                $updSql = "
                    UPDATE leads 
                    SET full_name = ?, phone = ?, alternate_phone = ?, email = ?, city = ?,
                        dob = ?, gender = ?, pan_number = ?,
                        has_ielts = ?, english_test = ?, english_score = ?, ielts_overall = ?,
                        highest_qualification = ?, college_name = ?,
                        preferred_country = ?, preferred_course = ?, intake_year = ?, intake_term = ?,
                        prospectivity = ?, priority = ?, source = ?, notes = ?
                    WHERE id = ?
                ";
                $updStmt = $pdo->prepare($updSql);
                $updStmt->execute([
                    $fullName, $cleanPhone, $alternatePhone, $email, $city,
                    $dob, $gender, $panNumber,
                    $hasIelts, ($hasIelts ? 'IELTS' : 'None'), $ieltsScore, $ieltsScore,
                    $highestQual, $collegeName,
                    $preferredCountry, $preferredCourse, $intakeYear, $intakeTerm,
                    $prospectivity, $priority, $source, $notes,
                    $leadId
                ]);

                log_activity($currentUser['id'], $leadId, 'Lead Updated', "Lead details updated for {$fullName} ({$lead['lead_code']}).");
                set_flash('success', "Lead <strong>" . htmlspecialchars($fullName) . "</strong> updated successfully.");
                redirect('leads/view.php?id=' . $leadId);
            } catch (Exception $e) {
                set_flash('danger', 'Error updating lead: ' . htmlspecialchars($e->getMessage()));
            }
        }
    }
}

$pageTitle = 'Edit Lead: ' . $lead['full_name'];
$activePage = 'leads';

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <h1 style="font-size:1.5rem; font-weight:700; color:var(--text-heading); margin:0 0 0.35rem 0;">Edit Student Lead Record</h1>
    <p style="font-size:0.875rem; color:var(--text-muted); margin:0;">
      Updating profile for <strong><?= htmlspecialchars($lead['full_name']) ?></strong> (<?= htmlspecialchars($lead['lead_code']) ?>).
    </p>
  </div>
  <div>
    <a href="<?= base_url('leads/view.php?id=' . $lead['id']) ?>" class="btn btn-secondary btn-sm">
      <i class="fa-solid fa-arrow-left"></i> Back to Lead Details
    </a>
  </div>
</div>

<form method="POST" action="<?= base_url('leads/edit.php?id=' . $lead['id']) ?>">
  <?= csrf_field() ?>

  <!-- Personal & Contact Information -->
  <div class="card" style="margin-bottom:1.5rem; padding:1.5rem;">
    <h3 style="font-size:1.1rem; font-weight:700; color:var(--text-heading); margin:0 0 1.25rem 0;">
      <i class="fa-solid fa-user" style="color:var(--primary); margin-right:6px;"></i> Personal & Contact Information
    </h3>
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:1.25rem;">
      <div class="form-group">
        <label class="form-label" style="font-weight:700;">Full Name <span style="color:red;">*</span></label>
        <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($lead['full_name']) ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label" style="font-weight:700;">Mobile Phone <span style="color:red;">*</span></label>
        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($lead['phone']) ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">Secondary / Alternate Phone</label>
        <input type="text" name="alternate_phone" class="form-control" value="<?= htmlspecialchars($lead['alternate_phone'] ?? '') ?>" placeholder="e.g. 0495-2345678">
      </div>
      <div class="form-group">
        <label class="form-label">Email Address</label>
        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($lead['email'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Date of Birth</label>
        <input type="date" name="dob" class="form-control" value="<?= htmlspecialchars($lead['dob'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Gender</label>
        <select name="gender" class="form-control">
          <option value="Male" <?= ($lead['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
          <option value="Female" <?= ($lead['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
          <option value="Other" <?= ($lead['gender'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">City / Address</label>
        <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($lead['city'] ?? '') ?>">
      </div>
    </div>
  </div>

  <!-- Study Preferences & InTake -->
  <div class="card" style="margin-bottom:1.5rem; padding:1.5rem;">
    <h3 style="font-size:1.1rem; font-weight:700; color:var(--text-heading); margin:0 0 1.25rem 0;">
      <i class="fa-solid fa-earth-americas" style="color:var(--primary); margin-right:6px;"></i> Study Preferences & InTake
    </h3>
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:1.25rem;">
      <div class="form-group">
        <label class="form-label" style="font-weight:700;">Target Destination <span style="color:red;">*</span></label>
        <select name="preferred_country" class="form-control" required>
          <?php foreach (['United Kingdom', 'Canada', 'Australia', 'United States', 'Ireland', 'Germany', 'New Zealand', 'Europe General'] as $c): ?>
            <option value="<?= $c ?>" <?= $lead['preferred_country'] === $c ? 'selected' : '' ?>><?= $c ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Desired Course</label>
        <input type="text" name="preferred_course" class="form-control" value="<?= htmlspecialchars($lead['preferred_course'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label" style="font-weight:700;">Prospectivity <span style="color:red;">*</span></label>
        <?php $curP = $lead['prospectivity'] ?? $lead['priority'] ?? 'Warm'; ?>
        <select name="prospectivity" class="form-control" required>
          <option value="Hot" <?= ($curP === 'Hot' || $curP === 'High') ? 'selected' : '' ?>>🔥 Hot</option>
          <option value="Warm" <?= ($curP === 'Warm' || $curP === 'Moderate') ? 'selected' : '' ?>>⚡ Warm</option>
          <option value="Cold" <?= ($curP === 'Cold' || $curP === 'Low') ? 'selected' : '' ?>>🌱 Cold</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label" style="font-weight:700;">InTake Year & Term <span style="color:red;">*</span></label>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.5rem;">
          <select name="intake_term" class="form-control">
            <option value="September" <?= ($lead['intake_term'] ?? '') === 'September' ? 'selected' : '' ?>>September</option>
            <option value="January" <?= ($lead['intake_term'] ?? '') === 'January' ? 'selected' : '' ?>>January</option>
            <option value="May" <?= ($lead['intake_term'] ?? '') === 'May' ? 'selected' : '' ?>>May</option>
          </select>
          <select name="intake_year" class="form-control">
            <option value="2026" <?= ($lead['intake_year'] ?? 2026) == 2026 ? 'selected' : '' ?>>2026</option>
            <option value="2027" <?= ($lead['intake_year'] ?? 2026) == 2027 ? 'selected' : '' ?>>2027</option>
            <option value="2028" <?= ($lead['intake_year'] ?? 2026) == 2028 ? 'selected' : '' ?>>2028</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Highest Qualification</label>
        <input type="text" name="highest_qualification" class="form-control" value="<?= htmlspecialchars($lead['highest_qualification'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">College / Institution</label>
        <input type="text" name="college_name" class="form-control" value="<?= htmlspecialchars($lead['college_name'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Source of Lead</label>
        <select name="source" class="form-control">
          <?php foreach (['Walk-in', 'Website', 'College Data', 'Student Referral', 'Meta Ads', 'Google Ads', 'Direct Reference'] as $s): ?>
            <option value="<?= $s ?>" <?= $lead['source'] === $s ? 'selected' : '' ?>><?= $s ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
  </div>

  <!-- Verification Checks: IELTS & PAN Card (Figure p13 & Section 9.3) -->
  <div class="card" style="margin-bottom:1.5rem; padding:1.5rem;">
    <h3 style="font-size:1.1rem; font-weight:700; color:var(--text-heading); margin:0 0 1.25rem 0;">
      <i class="fa-solid fa-id-card-clip" style="color:var(--primary); margin-right:6px;"></i> Verification Status (IELTS & PAN Card)
    </h3>
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.25rem;">
      <!-- IELTS -->
      <?php $hasIeltsVal = !empty($lead['has_ielts']) || (!empty($lead['english_test']) && $lead['english_test'] === 'IELTS'); ?>
      <div style="background:var(--bg-subtle); border-radius:10px; padding:1rem; border:1px solid var(--border-color);">
        <label style="display:flex; align-items:center; gap:0.5rem; font-weight:700; cursor:pointer; color:var(--text-heading);">
          <input type="checkbox" name="has_ielts" id="chk_has_ielts" value="1" <?= $hasIeltsVal ? 'checked' : '' ?> onchange="document.getElementById('ielts_score_wrap').style.display = this.checked ? 'block' : 'none';">
          <span>Taken IELTS Exam?</span>
        </label>
        <div id="ielts_score_wrap" style="display:<?= $hasIeltsVal ? 'block' : 'none' ?>; margin-top:0.75rem;">
          <label class="form-label" style="font-size:0.78rem;">IELTS Overall Band Score</label>
          <input type="text" name="ielts_score" class="form-control" value="<?= htmlspecialchars($lead['english_score'] ?? $lead['ielts_overall'] ?? '') ?>" placeholder="e.g. 7.0 or 6.5">
        </div>
      </div>

      <!-- PAN Card -->
      <?php $hasPanVal = !empty($lead['pan_number']); ?>
      <div style="background:var(--bg-subtle); border-radius:10px; padding:1rem; border:1px solid var(--border-color);">
        <label style="display:flex; align-items:center; gap:0.5rem; font-weight:700; cursor:pointer; color:var(--text-heading);">
          <input type="checkbox" name="has_pancard" id="chk_has_pancard" value="1" <?= $hasPanVal ? 'checked' : '' ?> onchange="document.getElementById('pan_number_wrap').style.display = this.checked ? 'block' : 'none';">
          <span>Have PAN Card?</span>
        </label>
        <div id="pan_number_wrap" style="display:<?= $hasPanVal ? 'block' : 'none' ?>; margin-top:0.75rem;">
          <label class="form-label" style="font-size:0.78rem;">PAN Card Number</label>
          <input type="text" name="pan_number" class="form-control" value="<?= htmlspecialchars($lead['pan_number'] ?? '') ?>" placeholder="e.g. ABCDE1234F" style="text-transform:uppercase;">
        </div>
      </div>
    </div>
  </div>

  <!-- Current Status / Remarks -->
  <div class="card" style="margin-bottom:2rem; padding:1.5rem;">
    <div class="form-group" style="margin-bottom:1.25rem;">
      <label class="form-label" style="font-weight:700;">Current Status / Consultation Notes</label>
      <textarea name="notes" class="form-control" rows="3"><?= htmlspecialchars($lead['notes'] ?? '') ?></textarea>
    </div>

    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; border-top:1px solid var(--border-color); padding-top:1.25rem;">
      <a href="<?= base_url('leads/view.php?id=' . $lead['id']) ?>" class="btn btn-secondary">
        <i class="fa-solid fa-times"></i> Cancel
      </a>
      <button type="submit" class="btn btn-primary btn-lg" style="padding:0.65rem 2rem; font-weight:700;">
        <i class="fa-solid fa-check"></i> Save Details
      </button>
    </div>
  </div>
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>
