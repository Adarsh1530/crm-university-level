<?php
/**
 * Edit Student Lead Record
 * University-Level Education Consultancy CRM (EdrootsCRM)
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
                    SET full_name = ?, phone = ?, email = ?, city = ?, state = ?, country = ?,
                        dob = ?, gender = ?, passport_number = ?, passport_expiry = ?,
                        highest_qualification = ?, college_name = ?, year_of_passing = ?, percentage = ?, backlogs_count = ?,
                        english_test = ?, english_score = ?, score_listening = ?, score_reading = ?, score_writing = ?, score_speaking = ?,
                        work_experience_years = ?, preferred_country = ?, preferred_course = ?, intake_year = ?, intake_term = ?,
                        priority = ?, source = ?, notes = ?
                    WHERE id = ?
                ";
                $updStmt = $pdo->prepare($updSql);
                $updStmt->execute([
                    $fullName, $cleanPhone, $email, $city, $state, $country,
                    $dob, $gender, $passportNumber, $passportExpiry,
                    $highestQual, $collegeName, $yearOfPassing, $percentage, $backlogs,
                    $englishTest, $englishScore, $scoreL, $scoreR, $scoreW, $scoreS,
                    $workExp, $preferredCountry, $preferredCourse, $intakeYear, $intakeTerm,
                    $priority, $source, $notes,
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
      <i class="fa-solid fa-arrow-left"></i> Back to Dossier
    </a>
  </div>
</div>

<form method="POST" action="<?= base_url('leads/edit.php?id=' . $lead['id']) ?>">
  <?= csrf_field() ?>

  <!-- Personal Information -->
  <div class="card" style="margin-bottom:1.5rem; padding:1.5rem;">
    <h3 style="font-size:1.1rem; font-weight:700; color:var(--text-heading); margin:0 0 1.25rem 0;">
      <i class="fa-solid fa-user" style="color:var(--primary); margin-right:6px;"></i> Personal Information
    </h3>
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:1.25rem;">
      <div class="form-group">
        <label class="form-label">Full Name <span style="color:red;">*</span></label>
        <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($lead['full_name']) ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">Phone Number <span style="color:red;">*</span></label>
        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($lead['phone']) ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">Email Address</label>
        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($lead['email'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">City</label>
        <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($lead['city'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Passport Number</label>
        <input type="text" name="passport_number" class="form-control" value="<?= htmlspecialchars($lead['passport_number'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Passport Expiry Date</label>
        <input type="date" name="passport_expiry" class="form-control" value="<?= htmlspecialchars($lead['passport_expiry'] ?? '') ?>">
      </div>
    </div>
  </div>

  <!-- Academic Background -->
  <div class="card" style="margin-bottom:1.5rem; padding:1.5rem;">
    <h3 style="font-size:1.1rem; font-weight:700; color:var(--text-heading); margin:0 0 1.25rem 0;">
      <i class="fa-solid fa-graduation-cap" style="color:var(--primary); margin-right:6px;"></i> Academic Background & English Scores
    </h3>
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:1.25rem; margin-bottom:1.25rem;">
      <div class="form-group">
        <label class="form-label">Highest Qualification</label>
        <input type="text" name="highest_qualification" class="form-control" value="<?= htmlspecialchars($lead['highest_qualification'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">College / Institution</label>
        <input type="text" name="college_name" class="form-control" value="<?= htmlspecialchars($lead['college_name'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Percentage / CGPA</label>
        <input type="number" step="0.01" name="percentage" class="form-control" value="<?= htmlspecialchars($lead['percentage'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Active Backlogs</label>
        <input type="number" name="backlogs_count" class="form-control" value="<?= (int)($lead['backlogs_count'] ?? 0) ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Work Experience (Years)</label>
        <input type="number" step="0.5" name="work_experience_years" class="form-control" value="<?= htmlspecialchars($lead['work_experience_years'] ?? '0') ?>">
      </div>
    </div>

    <div style="background:var(--bg-subtle); border-radius:8px; padding:1rem;">
      <h4 style="font-size:0.9rem; font-weight:700; margin:0 0 0.75rem 0;">English Language Assessment</h4>
      <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(130px, 1fr)); gap:0.75rem;">
        <div class="form-group">
          <label class="form-label">Test Type</label>
          <select name="english_test" class="form-control">
            <option value="IELTS" <?= $lead['english_test'] === 'IELTS' ? 'selected' : '' ?>>IELTS</option>
            <option value="PTE" <?= $lead['english_test'] === 'PTE' ? 'selected' : '' ?>>PTE</option>
            <option value="TOEFL" <?= $lead['english_test'] === 'TOEFL' ? 'selected' : '' ?>>TOEFL</option>
            <option value="Duolingo" <?= $lead['english_test'] === 'Duolingo' ? 'selected' : '' ?>>Duolingo</option>
            <option value="None" <?= $lead['english_test'] === 'None' ? 'selected' : '' ?>>None</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Overall Score</label>
          <input type="text" name="english_score" class="form-control" value="<?= htmlspecialchars($lead['english_score'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Listening</label>
          <input type="number" step="0.5" name="score_listening" class="form-control" value="<?= htmlspecialchars($lead['score_listening'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Reading</label>
          <input type="number" step="0.5" name="score_reading" class="form-control" value="<?= htmlspecialchars($lead['score_reading'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Writing</label>
          <input type="number" step="0.5" name="score_writing" class="form-control" value="<?= htmlspecialchars($lead['score_writing'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Speaking</label>
          <input type="number" step="0.5" name="score_speaking" class="form-control" value="<?= htmlspecialchars($lead['score_speaking'] ?? '') ?>">
        </div>
      </div>
    </div>
  </div>

  <!-- Preferences -->
  <div class="card" style="margin-bottom:1.5rem; padding:1.5rem;">
    <h3 style="font-size:1.1rem; font-weight:700; color:var(--text-heading); margin:0 0 1.25rem 0;">
      <i class="fa-solid fa-earth-americas" style="color:var(--primary); margin-right:6px;"></i> Consultancy Preferences
    </h3>
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:1.25rem;">
      <div class="form-group">
        <label class="form-label">Target Destination</label>
        <select name="preferred_country" class="form-control">
          <?php foreach (['United Kingdom', 'Canada', 'Australia', 'United States', 'Ireland', 'Germany', 'New Zealand', 'Dubai'] as $c): ?>
            <option value="<?= $c ?>" <?= $lead['preferred_country'] === $c ? 'selected' : '' ?>><?= $c ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Target Course</label>
        <input type="text" name="preferred_course" class="form-control" value="<?= htmlspecialchars($lead['preferred_course'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Priority</label>
        <select name="priority" class="form-control">
          <option value="High" <?= $lead['priority'] === 'High' ? 'selected' : '' ?>>High</option>
          <option value="Moderate" <?= $lead['priority'] === 'Moderate' ? 'selected' : '' ?>>Moderate</option>
          <option value="Low" <?= $lead['priority'] === 'Low' ? 'selected' : '' ?>>Low</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Marketing Source</label>
        <select name="source" class="form-control">
          <?php foreach (['College Data', 'Website', 'Meta Ads', 'Instagram', 'Google Ads', 'Referral', 'Walk-in'] as $s): ?>
            <option value="<?= $s ?>" <?= $lead['source'] === $s ? 'selected' : '' ?>><?= $s ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
  </div>

  <div style="display:flex; justify-content:flex-end; gap:0.75rem;">
    <a href="<?= base_url('leads/view.php?id=' . $lead['id']) ?>" class="btn btn-secondary">Cancel</a>
    <button type="submit" class="btn btn-primary">
      <i class="fa-solid fa-save"></i> Save Changes
    </button>
  </div>
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>
