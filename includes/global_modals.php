<?php
/**
 * Global Modals & User-Friendly Operational Helpers
 * Provides: Quick Add Lead, Quick Log Call / Follow-up, Stage Advancer, and Comprehensive User Manual
 */

global $pdo, $currentUser;
if (!$pdo || !$currentUser) return;

// Fetch reference data for global modals
$counselorsList = [];
if (!is_counselor()) {
    $cStmt = $pdo->query("SELECT u.id, u.name, b.name as branch_name FROM users u LEFT JOIN branches b ON u.branch_id = b.id WHERE u.status = 'Active' AND u.role_id IN (2, 3) ORDER BY u.name ASC");
    $counselorsList = $cStmt->fetchAll(PDO::FETCH_ASSOC);
}

$interactionTitles = [];
try {
    $tStmt = $pdo->query("SELECT id, title, max_duration_days FROM interaction_titles WHERE status = 'Active' ORDER BY sort_order ASC");
    $interactionTitles = $tStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$standardAdmissionStages = ['ENQUIRY', 'CUSTOMER', 'APPLICATION', 'OFFER RECEIVED', 'TUITION FEES PAID', 'ENROLLED'];
?>

<!-- 1. GLOBAL QUICK ADD LEAD MODAL -->
<div class="modal fade" id="quickLeadModal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(12,36,60,0.55); align-items:center; justify-content:center; backdrop-filter:blur(3px);">
  <div class="modal-dialog" style="background:#fff; border-radius:16px; max-width:540px; width:92%; margin:auto; box-shadow:0 25px 60px rgba(0,0,0,0.25); overflow:hidden; max-height:92vh; display:flex; flex-direction:column;">
    <form method="POST" action="<?= base_url('leads/quick_add.php') ?>" style="display:flex; flex-direction:column; overflow:hidden;">
      <?= csrf_field() ?>

      <div class="modal-header" style="padding:1.2rem 1.5rem; background:linear-gradient(135deg, #7008E7 0%, #4a049e 100%); color:#fff; display:flex; justify-content:space-between; align-items:center;">
        <div>
          <h3 style="font-size:1.15rem; font-weight:700; margin:0; color:#fff; display:flex; align-items:center; gap:8px;">
            <i class="fa-solid fa-user-plus"></i> Quick Add Student Lead
          </h3>
          <span style="font-size:0.75rem; color:rgba(255,255,255,0.85);">Fast inquiry capture for calls & walk-ins</span>
        </div>
        <button type="button" onclick="closeQuickLeadModal()" style="border:none; background:rgba(255,255,255,0.2); color:#fff; width:30px; height:30px; border-radius:50%; font-size:1.2rem; cursor:pointer; display:flex; align-items:center; justify-content:center;">&times;</button>
      </div>

      <div class="modal-body" style="padding:1.5rem; overflow-y:auto; flex:1;">
        <!-- Core Contact -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.85rem; margin-bottom:0.85rem;">
          <div class="form-group" style="margin:0;">
            <label class="form-label" style="font-weight:700; font-size:0.825rem;">Candidate Name <span style="color:red;">*</span></label>
            <input type="text" name="full_name" class="form-control" placeholder="e.g. Rahul Sharma" required autofocus>
          </div>
          <div class="form-group" style="margin:0;">
            <label class="form-label" style="font-weight:700; font-size:0.825rem;">Mobile / WhatsApp <span style="color:red;">*</span></label>
            <input type="tel" name="phone" class="form-control" placeholder="10-digit mobile" required>
          </div>
        </div>

        <div class="form-group" style="margin-bottom:0.85rem;">
          <label class="form-label" style="font-weight:600; font-size:0.825rem;">Email Address</label>
          <input type="email" name="email" class="form-control" placeholder="candidate@example.com">
        </div>

        <!-- Academic Target -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.85rem; margin-bottom:0.85rem;">
          <div class="form-group" style="margin:0;">
            <label class="form-label" style="font-weight:700; font-size:0.825rem;">Target Country</label>
            <select name="preferred_country" class="form-control" required>
              <option value="United Kingdom">United Kingdom (UK)</option>
              <option value="Australia">Australia</option>
              <option value="Canada">Canada</option>
              <option value="Ireland">Ireland</option>
              <option value="Germany">Germany</option>
              <option value="United States">United States (USA)</option>
              <option value="New Zealand">New Zealand</option>
              <option value="Europe General">Europe General</option>
            </select>
          </div>
          <div class="form-group" style="margin:0;">
            <label class="form-label" style="font-weight:700; font-size:0.825rem;">Course / Degree Interest</label>
            <input type="text" name="preferred_course" class="form-control" placeholder="e.g. MSc Data Science / MBA">
          </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:0.75rem; margin-bottom:0.85rem;">
          <div class="form-group" style="margin:0;">
            <label class="form-label" style="font-size:0.78rem;">Target Intake</label>
            <select name="intake_term" class="form-control" style="font-size:0.82rem;">
              <option value="September">September (Fall)</option>
              <option value="January">January (Spring)</option>
              <option value="May">May (Summer)</option>
            </select>
          </div>
          <div class="form-group" style="margin:0;">
            <label class="form-label" style="font-size:0.78rem;">Year</label>
            <input type="number" name="intake_year" class="form-control" value="<?= date('Y') ?>" style="font-size:0.82rem;">
          </div>
          <div class="form-group" style="margin:0;">
            <label class="form-label" style="font-size:0.78rem;">Priority</label>
            <select name="priority" class="form-control" style="font-size:0.82rem;">
              <option value="High">🔥 High</option>
              <option value="Moderate" selected>⚡ Moderate</option>
              <option value="Low">💤 Low</option>
            </select>
          </div>
        </div>

        <?php if (!is_counselor() && !empty($counselorsList)): ?>
          <div class="form-group" style="margin-bottom:0.85rem;">
            <label class="form-label" style="font-weight:700; font-size:0.825rem;">Assign Admissions Counselor</label>
            <select name="counselor_id" class="form-control">
              <?php foreach ($counselorsList as $cl): ?>
                <option value="<?= $cl['id'] ?>" <?= $cl['id'] == $currentUser['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($cl['name']) ?> (<?= htmlspecialchars($cl['branch_name'] ?? 'Counselor') ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php endif; ?>

        <div class="form-group" style="margin-bottom:0.85rem;">
          <label class="form-label" style="font-weight:600; font-size:0.825rem;">Initial Consultation Notes</label>
          <textarea name="notes" class="form-control" rows="2" placeholder="Summary of initial call / candidate aspirations..."></textarea>
        </div>

        <div class="form-group" style="margin-bottom:0;">
          <label class="form-label" style="font-weight:700; font-size:0.825rem;">Schedule Next Follow-up Call</label>
          <div style="display:flex; gap:0.5rem; align-items:center;">
            <input type="date" name="next_followup_date" id="quickLeadFollowupDate" class="form-control" value="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
            <button type="button" class="btn btn-outline btn-sm" onclick="document.getElementById('quickLeadFollowupDate').value = '<?= date('Y-m-d', strtotime('+1 day')) ?>'">Tomorrow</button>
            <button type="button" class="btn btn-outline btn-sm" onclick="document.getElementById('quickLeadFollowupDate').value = '<?= date('Y-m-d', strtotime('+3 days')) ?>'">+3 Days</button>
          </div>
        </div>
      </div>

      <div class="modal-footer" style="padding:1rem 1.5rem; border-top:1px solid var(--border-color); display:flex; justify-content:flex-end; gap:0.5rem; background:#f8fafc;">
        <button type="button" class="btn btn-secondary" onclick="closeQuickLeadModal()">Cancel</button>
        <button type="submit" class="btn btn-primary" style="font-weight:700; padding:0.5rem 1.25rem;">
          <i class="fa-solid fa-check"></i> Register Lead
        </button>
      </div>
    </form>
  </div>
</div>

<!-- 2. GLOBAL LOG CALL / FOLLOW-UP MODAL -->
<div class="modal fade" id="globalLogCallModal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(12,36,60,0.55); align-items:center; justify-content:center; backdrop-filter:blur(3px);">
  <div class="modal-dialog" style="background:#fff; border-radius:16px; max-width:520px; width:92%; margin:auto; box-shadow:0 25px 60px rgba(0,0,0,0.25); overflow:hidden;">
    <form method="POST" action="<?= base_url('followups/add.php') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="lead_id" id="logCallLeadId" value="0">
      <input type="hidden" name="return_url" id="logCallReturnUrl" value="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '') ?>">

      <div class="modal-header" style="padding:1.2rem 1.5rem; background:linear-gradient(135deg, #10b981 0%, #059669 100%); color:#fff; display:flex; justify-content:space-between; align-items:center;">
        <div>
          <h3 style="font-size:1.15rem; font-weight:700; margin:0; color:#fff; display:flex; align-items:center; gap:8px;">
            <i class="fa-solid fa-phone-volume"></i> Record Consultation Call
          </h3>
          <span style="font-size:0.75rem; color:rgba(255,255,255,0.9);" id="logCallStudentLabel">Student: Candidate</span>
        </div>
        <button type="button" onclick="closeLogCallModal()" style="border:none; background:rgba(255,255,255,0.2); color:#fff; width:30px; height:30px; border-radius:50%; font-size:1.2rem; cursor:pointer; display:flex; align-items:center; justify-content:center;">&times;</button>
      </div>

      <div class="modal-body" style="padding:1.5rem;">
        <div class="form-group" style="margin-bottom:1rem;">
          <label class="form-label" style="font-weight:700; font-size:0.825rem;">Call Outcome / Interaction Title <span style="color:red;">*</span></label>
          <select name="interaction_title_id" id="logCallTitleSelect" class="form-control" required onchange="updateMaxFollowupNotice(this)">
            <?php foreach ($interactionTitles as $it): ?>
              <option value="<?= $it['id'] ?>" data-max="<?= $it['max_duration_days'] ?>">
                <?= htmlspecialchars($it['title']) ?> (Max Next Call: <?= $it['max_duration_days'] ?> days)
              </option>
            <?php endforeach; ?>
          </select>
          <div id="maxDaysNotice" style="font-size:0.75rem; color:var(--text-muted); margin-top:4px;">
            Next follow-up must be scheduled within allowed policy limit.
          </div>
        </div>

        <div class="form-group" style="margin-bottom:1rem;">
          <label class="form-label" style="font-weight:700; font-size:0.825rem;">Discussion Summary / Call Notes <span style="color:red;">*</span></label>
          <textarea name="notes" class="form-control" rows="3" placeholder="What was discussed with the student? (e.g. course choice, document submission date, visa requirements)" required></textarea>
        </div>

        <!-- Optional Move Stage -->
        <div class="form-group" style="margin-bottom:1rem;">
          <label class="form-label" style="font-weight:600; font-size:0.825rem;">Update Admission Pipeline Stage (Optional)</label>
          <select name="new_stage" id="logCallNewStage" class="form-control">
            <option value="">Keep Current Stage</option>
            <?php foreach ($standardAdmissionStages as $stg): ?>
              <option value="<?= $stg ?>">Move to: <?= $stg ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <input type="hidden" name="schedule_next" id="logCallScheduleNext" value="1">

        <!-- Next Call Date -->
        <div class="form-group" style="margin-bottom:0;" id="logCallNextDateGroup">
          <label class="form-label" style="font-weight:700; font-size:0.825rem;">Next Follow-up Date</label>
          <input type="date" name="next_followup_date" id="logCallNextDate" class="form-control" value="<?= date('Y-m-d', strtotime('+2 days')) ?>">
          <div style="display:flex; gap:0.4rem; margin-top:6px; flex-wrap:wrap;">
            <button type="button" class="btn btn-outline btn-sm" style="font-size:0.75rem; padding:2px 8px;" onclick="setLogCallDate(1)">Tomorrow</button>
            <button type="button" class="btn btn-outline btn-sm" style="font-size:0.75rem; padding:2px 8px;" onclick="setLogCallDate(2)">In 2 Days</button>
            <button type="button" class="btn btn-outline btn-sm" style="font-size:0.75rem; padding:2px 8px;" onclick="setLogCallDate(5)">In 5 Days</button>
            <button type="button" class="btn btn-outline btn-sm" style="font-size:0.75rem; padding:2px 8px;" onclick="setLogCallDate(7)">Next Week</button>
          </div>
        </div>
      </div>

      <div class="modal-footer" style="padding:1rem 1.5rem; border-top:1px solid var(--border-color); display:flex; justify-content:flex-end; gap:0.5rem; background:#f8fafc; flex-wrap:wrap;">
        <button type="button" class="btn btn-secondary btn-sm" onclick="closeLogCallModal()">Cancel</button>
        <button type="submit" onclick="document.getElementById('logCallScheduleNext').value='0'; document.getElementById('logCallNextDate').required=false;" class="btn btn-outline btn-sm" style="font-weight:600;">
          <i class="fa-solid fa-check"></i> Save Outcome Only
        </button>
        <button type="submit" onclick="document.getElementById('logCallScheduleNext').value='1'; document.getElementById('logCallNextDate').required=true;" class="btn btn-primary btn-sm" style="background:#059669; border-color:#059669; font-weight:700;">
          <i class="fa-solid fa-calendar-check"></i> Save & Schedule Next
        </button>
      </div>
    </form>
  </div>
</div>

<!-- 3. GLOBAL ADVANCE ADMISSION STAGE MODAL -->
<div class="modal fade" id="advanceStageModal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(12,36,60,0.55); align-items:center; justify-content:center; backdrop-filter:blur(3px);">
  <div class="modal-dialog" style="background:#fff; border-radius:16px; max-width:480px; width:92%; margin:auto; box-shadow:0 25px 60px rgba(0,0,0,0.25); overflow:hidden;">
    <form method="POST" action="<?= base_url('admissions/update_stage.php') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="lead_id" id="advanceStageLeadId" value="0">
      <input type="hidden" name="return_url" id="advanceStageReturnUrl" value="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '') ?>">

      <div class="modal-header" style="padding:1.2rem 1.5rem; background:linear-gradient(135deg, #0284c7 0%, #0369a1 100%); color:#fff; display:flex; justify-content:space-between; align-items:center;">
        <div>
          <h3 style="font-size:1.15rem; font-weight:700; margin:0; color:#fff; display:flex; align-items:center; gap:8px;">
            <i class="fa-solid fa-stairs"></i> Advance Admission Stage
          </h3>
          <span style="font-size:0.75rem; color:rgba(255,255,255,0.9);" id="advanceStageStudentLabel">Student Candidate</span>
        </div>
        <button type="button" onclick="closeAdvanceStageModal()" style="border:none; background:rgba(255,255,255,0.2); color:#fff; width:30px; height:30px; border-radius:50%; font-size:1.2rem; cursor:pointer; display:flex; align-items:center; justify-content:center;">&times;</button>
      </div>

      <div class="modal-body" style="padding:1.5rem;">
        <div class="form-group" style="margin-bottom:1rem;">
          <label class="form-label" style="font-weight:700; font-size:0.825rem;">Select Target Admission Milestone <span style="color:red;">*</span></label>
          <select name="new_stage" id="advanceStageSelect" class="form-control" required style="font-weight:700; font-size:0.9rem;">
            <option value="ENQUIRY">Stage 1: ENQUIRY (Initial Prospect)</option>
            <option value="CUSTOMER">Stage 2: CUSTOMER (Retained / Committed)</option>
            <option value="APPLICATION">Stage 3: APPLICATION (University Filing)</option>
            <option value="OFFER RECEIVED">Stage 4: OFFER RECEIVED (Conditional/Unconditional)</option>
            <option value="TUITION FEES PAID">Stage 5: TUITION FEES PAID (Fee Remittance)</option>
            <option value="ENROLLED">Stage 6: ENROLLED (Visa Granted & Admitted)</option>
          </select>
        </div>

        <div class="form-group" style="margin-bottom:1rem;">
          <label class="form-label" style="font-weight:600; font-size:0.825rem;">Secondary Operational Status</label>
          <select name="secondary_status" class="form-control">
            <option value="Active">Active (In Progress)</option>
            <option value="On Hold">On Hold (Pending Student Action)</option>
            <option value="Rejected">Rejected</option>
            <option value="Lost">Lost / Dropped</option>
          </select>
        </div>

        <div class="form-group" style="margin-bottom:0;">
          <label class="form-label" style="font-weight:600; font-size:0.825rem;">Stage Transition Note</label>
          <textarea name="notes" class="form-control" rows="2" placeholder="e.g. Received official offer letter from University of Manchester; proceeding with CAS and visa filing."></textarea>
        </div>
      </div>

      <div class="modal-footer" style="padding:1rem 1.5rem; border-top:1px solid var(--border-color); display:flex; justify-content:flex-end; gap:0.5rem; background:#f8fafc;">
        <button type="button" class="btn btn-secondary" onclick="closeAdvanceStageModal()">Cancel</button>
        <button type="submit" class="btn btn-primary" style="background:#0284c7; border-color:#0284c7; font-weight:700;">
          <i class="fa-solid fa-check-double"></i> Update Milestone
        </button>
      </div>
    </form>
  </div>
</div>

<!-- 4. INTERACTIVE USER MANUAL & ROLE GUIDELINES MODAL -->
<div class="modal fade" id="userManualModal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(12,36,60,0.6); align-items:center; justify-content:center; backdrop-filter:blur(4px);">
  <div class="modal-dialog" style="background:#fff; border-radius:18px; max-width:880px; width:95%; margin:auto; box-shadow:0 30px 80px rgba(0,0,0,0.3); overflow:hidden; max-height:90vh; display:flex; flex-direction:column;">
    <!-- Modal Header -->
    <div class="modal-header" style="padding:1.25rem 1.75rem; background:linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color:#fff; display:flex; justify-content:space-between; align-items:center;">
      <div style="display:flex; align-items:center; gap:12px;">
        <div style="width:40px; height:40px; border-radius:10px; background:rgba(255,255,255,0.15); display:flex; align-items:center; justify-content:center; font-size:1.25rem;">
          <i class="fa-solid fa-book-open" style="color:#f59e0b;"></i>
        </div>
        <div>
          <h3 style="font-size:1.2rem; font-weight:800; margin:0; color:#fff; display:flex; align-items:center; gap:8px;">
            UniCRM Standard Operating Guide
            <span style="font-size:0.68rem; font-weight:700; background:rgba(245,158,11,0.25); color:#fbbf24; border:1px solid rgba(245,158,11,0.4); padding:2px 8px; border-radius:20px; text-transform:uppercase; letter-spacing:0.04em;">Operational Playbook</span>
          </h3>
          <span style="font-size:0.78rem; color:rgba(255,255,255,0.8);">Step-by-step role instructions, daily routines & admission workflows</span>
        </div>
      </div>
      <button type="button" onclick="closeUserManualModal()" style="border:none; background:rgba(255,255,255,0.15); color:#fff; width:34px; height:34px; border-radius:50%; font-size:1.35rem; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:background 0.2s;" title="Close Guide">&times;</button>
    </div>

    <!-- Guide Navigation Tabs -->
    <div style="background:#f1f5f9; padding:0.65rem 1.25rem; display:flex; gap:0.4rem; border-bottom:1px solid var(--border-color); overflow-x:auto; flex-shrink:0;">
      <button type="button" class="btn btn-sm guide-tab-btn active" id="tabBtn-counselor" onclick="switchGuideTab('counselor')" style="font-weight:700; border-radius:6px; padding:0.45rem 0.95rem; font-size:0.8rem; display:inline-flex; align-items:center; gap:6px;">
        <i class="fa-solid fa-headset" style="color:#10b981;"></i> Counselor Playbook
      </button>
      <button type="button" class="btn btn-sm guide-tab-btn" id="tabBtn-manager" onclick="switchGuideTab('manager')" style="font-weight:700; border-radius:6px; padding:0.45rem 0.95rem; font-size:0.8rem; display:inline-flex; align-items:center; gap:6px;">
        <i class="fa-solid fa-building-user" style="color:#f59e0b;"></i> Admissions Manager
      </button>
      <button type="button" class="btn btn-sm guide-tab-btn" id="tabBtn-admin" onclick="switchGuideTab('admin')" style="font-weight:700; border-radius:6px; padding:0.45rem 0.95rem; font-size:0.8rem; display:inline-flex; align-items:center; gap:6px;">
        <i class="fa-solid fa-crown" style="color:#ef4444;"></i> Super Admin
      </button>
      <button type="button" class="btn btn-sm guide-tab-btn" id="tabBtn-stages" onclick="switchGuideTab('stages')" style="font-weight:700; border-radius:6px; padding:0.45rem 0.95rem; font-size:0.8rem; display:inline-flex; align-items:center; gap:6px;">
        <i class="fa-solid fa-stairs" style="color:#7008E7;"></i> 6 Milestones Explained
      </button>
      <button type="button" class="btn btn-sm guide-tab-btn" id="tabBtn-faq" onclick="switchGuideTab('faq')" style="font-weight:700; border-radius:6px; padding:0.45rem 0.95rem; font-size:0.8rem; display:inline-flex; align-items:center; gap:6px;">
        <i class="fa-solid fa-circle-question" style="color:#0284c7;"></i> FAQ & Help
      </button>
    </div>

    <!-- Guide Content Container -->
    <div class="modal-body" style="padding:1.5rem 1.75rem; overflow-y:auto; flex:1; font-size:0.875rem; line-height:1.65; color:var(--text-main);">
      
      <!-- Interactive Walkthrough Launcher Card -->
      <div style="background: linear-gradient(135deg, #7008E7 0%, #5955D1 100%); color: #fff; padding: 0.95rem 1.35rem; border-radius: 12px; margin-bottom: 1.35rem; display: flex; justify-content: space-between; align-items: center; gap: 12px; box-shadow: 0 4px 15px rgba(112,8,231,0.25);">
        <div>
          <strong style="font-size: 0.95rem; display: flex; align-items: center; gap: 6px;">
            <i class="fa-solid fa-wand-magic-sparkles" style="color: #fbbf24;"></i> Prefer a live interactive walkthrough?
          </strong>
          <span style="font-size: 0.8rem; color: rgba(255,255,255,0.9); display: block; margin-top: 2px;">
            Take a step-by-step interactive guided tutorial highlighting your actual dashboard buttons and tools.
          </span>
        </div>
        <button type="button" class="btn btn-sm" onclick="closeUserManualModal(); startInteractiveTutorial();" style="background: #fff; color: #7008E7; font-weight: 800; border: none; padding: 0.5rem 1.15rem; border-radius: 8px; white-space: nowrap; box-shadow: 0 4px 10px rgba(0,0,0,0.15); display: inline-flex; align-items: center; gap: 6px;">
          Start Live Tour <i class="fa-solid fa-arrow-right"></i>
        </button>
      </div>
      
      <!-- TAB 1: COUNSELOR DAILY PLAYBOOK -->
      <div id="guideContent-counselor" class="guide-content-panel">
        <div style="background:#ecfdf5; border-left:4px solid #10b981; padding:1rem 1.25rem; border-radius:8px; margin-bottom:1.5rem;">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
            <strong style="color:#065f46; font-size:0.95rem;"><i class="fa-solid fa-user-check"></i> Counselor Mission: Candidate Guidance & Conversion</strong>
            <span style="font-size:0.7rem; font-weight:700; background:#d1fae5; color:#065f46; padding:2px 8px; border-radius:12px; text-transform:uppercase;">Daily Routine</span>
          </div>
          <span style="color:#047857; font-size:0.825rem;">Your daily responsibility is consulting prospective students, addressing questions on overseas universities, keeping prompt follow-ups, and advancing students across admission stages.</span>
        </div>

        <h4 style="font-size:1.05rem; font-weight:800; color:var(--text-heading); margin-bottom:1rem; display:flex; align-items:center; gap:8px;">
          <span style="background:var(--primary); color:#fff; width:24px; height:24px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:0.75rem;">★</span>
          Your 4-Step Daily Operational Routine:
        </h4>

        <!-- Step 1 Card -->
        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:1.15rem; margin-bottom:1rem;">
          <div style="display:flex; align-items:center; gap:10px; margin-bottom:0.5rem;">
            <span style="background:#10b981; color:#fff; font-weight:800; font-size:0.75rem; padding:3px 9px; border-radius:6px;">STEP 1</span>
            <strong style="font-size:0.95rem; color:var(--text-heading);">Review Today's Scheduled Calls</strong>
          </div>
          <p style="margin:0 0 0.5rem 0; color:var(--text-main); font-size:0.85rem;">
            Start your day by opening <strong>Daily Follow-ups</strong> in the left sidebar or the blue <strong>Today's Calls</strong> widget on your dashboard.
          </p>
          <div style="background:#fff; border:1px dashed #cbd5e1; border-radius:8px; padding:0.6rem 0.85rem; font-size:0.8rem; color:var(--text-muted);">
            <strong style="color:#0f172a;"><i class="fa-solid fa-arrow-right" style="color:#10b981;"></i> What to look for:</strong> Priority calls scheduled for today appear first. Overdue calls appear at the very top in red so you can attend to delayed inquiries first.
          </div>
        </div>

        <!-- Step 2 Card -->
        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:1.15rem; margin-bottom:1rem;">
          <div style="display:flex; align-items:center; gap:10px; margin-bottom:0.5rem;">
            <span style="background:#10b981; color:#fff; font-weight:800; font-size:0.75rem; padding:3px 9px; border-radius:6px;">STEP 2</span>
            <strong style="font-size:0.95rem; color:var(--text-heading);">Connect with the Student (1-Click Calling & WhatsApp)</strong>
          </div>
          <p style="margin:0 0 0.5rem 0; color:var(--text-main); font-size:0.85rem;">
            Connect directly with the student using the quick communication shortcuts:
          </p>
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem;">
            <div style="background:#ecfdf5; border:1px solid #a7f3d0; border-radius:8px; padding:0.65rem 0.85rem;">
              <strong style="color:#065f46; font-size:0.825rem; display:flex; align-items:center; gap:6px;">
                <i class="fa-brands fa-whatsapp" style="color:#10b981; font-size:1rem;"></i> 1-Click WhatsApp
              </strong>
              <span style="font-size:0.78rem; color:#047857; display:block; margin-top:3px;">Click the green WhatsApp icon to launch WhatsApp Web with a direct chat with the student's mobile number.</span>
            </div>
            <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:0.65rem 0.85rem;">
              <strong style="color:#1e40af; font-size:0.825rem; display:flex; align-items:center; gap:6px;">
                <i class="fa-solid fa-phone" style="color:#3b82f6; font-size:0.9rem;"></i> Direct Phone Call
              </strong>
              <span style="font-size:0.78rem; color:#1d4ed8; display:block; margin-top:3px;">Click the student's mobile number to trigger direct dialing on your phone or softphone app.</span>
            </div>
          </div>
        </div>

        <!-- Step 3 Card -->
        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:1.15rem; margin-bottom:1rem;">
          <div style="display:flex; align-items:center; gap:10px; margin-bottom:0.5rem;">
            <span style="background:#10b981; color:#fff; font-weight:800; font-size:0.75rem; padding:3px 9px; border-radius:6px;">STEP 3</span>
            <strong style="font-size:0.95rem; color:var(--text-heading);">Record Consultation Outcome Immediately (Log Call)</strong>
          </div>
          <p style="margin:0 0 0.5rem 0; color:var(--text-main); font-size:0.85rem;">
            Immediately after every conversation or consultation, click the green <strong>Log Call</strong> button next to the student's name:
          </p>
          <ul style="margin:0; padding-left:1.25rem; font-size:0.825rem; color:var(--text-main); display:flex; flex-direction:column; gap:4px;">
            <li><strong>Select Outcome:</strong> Choose title (e.g. <em>Information Shared</em>, <em>Documents Requested</em>, <em>Parent Consultation</em>, <em>Attempted - No Answer</em>).</li>
            <li><strong>Enter Notes:</strong> Type a clear 1-2 sentence summary of what was discussed (courses, intake, visa, required papers).</li>
            <li><strong>Set Next Follow-up Date:</strong> Click a quick button (<em>Tomorrow</em>, <em>In 2 Days</em>, <em>In 5 Days</em>, <em>Next Week</em>) or choose a calendar date.</li>
          </ul>
        </div>

        <!-- Step 4 Card -->
        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:1.15rem; margin-bottom:1.25rem;">
          <div style="display:flex; align-items:center; gap:10px; margin-bottom:0.5rem;">
            <span style="background:#10b981; color:#fff; font-weight:800; font-size:0.75rem; padding:3px 9px; border-radius:6px;">STEP 4</span>
            <strong style="font-size:0.95rem; color:var(--text-heading);">Advance Admission Milestones on Progress</strong>
          </div>
          <p style="margin:0 0 0.5rem 0; color:var(--text-main); font-size:0.85rem;">
            When the candidate submits required documents, files a university application, receives an offer letter, pays tuition fees, or gets a visa:
          </p>
          <div style="background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:0.6rem 0.85rem; font-size:0.8rem; color:var(--text-main);">
            Click the blue <strong>Advance Stage</strong> button (or select the new milestone directly in the Log Call dialog) and choose the appropriate target milestone (e.g. <em>CUSTOMER &rarr; APPLICATION &rarr; OFFER RECEIVED &rarr; TUITION FEES PAID &rarr; ENROLLED</em>).
          </div>
        </div>

        <!-- Best Practices Box -->
        <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:10px; padding:1rem;">
          <strong style="color:#166534; font-size:0.85rem; display:flex; align-items:center; gap:6px;">
            <i class="fa-solid fa-circle-check" style="color:#16a34a;"></i> 3 Golden Rules for High Counselor Performance:
          </strong>
          <ul style="margin:0.5rem 0 0 0; padding-left:1.25rem; font-size:0.8rem; color:#15803d; display:flex; flex-direction:column; gap:4px;">
            <li><strong>Rule 1:</strong> Never finish a call without logging notes and setting the next follow-up date.</li>
            <li><strong>Rule 2:</strong> If a student doesn't answer, log <em>Attempted - No Answer</em> and schedule the next call for 24 hours later.</li>
            <li><strong>Rule 3:</strong> Keep all notes clear and professional—your notes are visible in the student's permanent audit dossier.</li>
          </ul>
        </div>
      </div>

      <!-- TAB 2: ADMISSIONS MANAGER GUIDE -->
      <div id="guideContent-manager" class="guide-content-panel" style="display:none;">
        <div style="background:#fffbeb; border-left:4px solid #f59e0b; padding:1rem 1.25rem; border-radius:8px; margin-bottom:1.5rem;">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
            <strong style="color:#92400e; font-size:0.95rem;"><i class="fa-solid fa-users-gear"></i> Admissions Manager Mission: Team Orchestration & Quality Control</strong>
            <span style="font-size:0.7rem; font-weight:700; background:#fef3c7; color:#92400e; padding:2px 8px; border-radius:12px; text-transform:uppercase;">Supervision</span>
          </div>
          <span style="color:#b45309; font-size:0.825rem;">Oversee all active student caseloads, rapidly triage inbound digital leads, ensure balanced counselor capacity, and maintain high conversion standards.</span>
        </div>

        <h4 style="font-size:1.05rem; font-weight:800; color:var(--text-heading); margin-bottom:1rem; display:flex; align-items:center; gap:8px;">
          <span style="background:#f59e0b; color:#fff; width:24px; height:24px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:0.75rem;">★</span>
          Manager Key Operational Workflows:
        </h4>

        <!-- Workflow 1 -->
        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:1.15rem; margin-bottom:1rem;">
          <div style="display:flex; align-items:center; gap:10px; margin-bottom:0.4rem;">
            <span style="background:#f59e0b; color:#fff; font-weight:800; font-size:0.75rem; padding:3px 9px; border-radius:6px;">WORKFLOW 1</span>
            <strong style="font-size:0.95rem; color:var(--text-heading);">Triage Fresh Inbound Digital Leads (Morning & Afternoon)</strong>
          </div>
          <p style="margin:0 0 0.4rem 0; font-size:0.85rem; color:var(--text-main);">
            Go to <strong>Lead Inbound & Triage &gt; Digital Web & Social Leads</strong>. Newly submitted student inquiries from Instagram Ads, Meta, and Google Ads arrive here in real time.
          </p>
          <div style="background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:0.6rem 0.85rem; font-size:0.8rem; color:var(--text-muted);">
            <strong style="color:var(--text-heading);">Action:</strong> Click <strong>Assign Counselor</strong> next to unassigned leads. Choose the most suitable counselor based on their specialty or current caseload, then confirm. The counselor is immediately notified.
          </div>
        </div>

        <!-- Workflow 2 -->
        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:1.15rem; margin-bottom:1rem;">
          <div style="display:flex; align-items:center; gap:10px; margin-bottom:0.4rem;">
            <span style="background:#f59e0b; color:#fff; font-weight:800; font-size:0.75rem; padding:3px 9px; border-radius:6px;">WORKFLOW 2</span>
            <strong style="font-size:0.95rem; color:var(--text-heading);">Bulk Lead Reassignment & Rebalancing</strong>
          </div>
          <p style="margin:0 0 0.4rem 0; font-size:0.85rem; color:var(--text-main);">
            When a counselor is on leave or when redistributing workload across your squad:
          </p>
          <div style="background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:0.6rem 0.85rem; font-size:0.8rem; color:var(--text-muted);">
            Open <strong>Student Leads</strong>, select checkboxes next to the students to transfer, click <strong>Bulk Reassign</strong> at the top of the table, pick the recipient counselor, and confirm. All records and tasks transfer instantly.
          </div>
        </div>

        <!-- Workflow 3 -->
        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:1.15rem; margin-bottom:1rem;">
          <div style="display:flex; align-items:center; gap:10px; margin-bottom:0.4rem;">
            <span style="background:#f59e0b; color:#fff; font-weight:800; font-size:0.75rem; padding:3px 9px; border-radius:6px;">WORKFLOW 3</span>
            <strong style="font-size:0.95rem; color:var(--text-heading);">Import Campus Rosters & Bulk College Contacts</strong>
          </div>
          <p style="margin:0 0 0.4rem 0; font-size:0.85rem; color:var(--text-main);">
            Navigate to <strong>Lead Inbound & Triage &gt; Import College List</strong>. Upload Excel (.xlsx) or CSV rosters from university education fairs, campus seminars, or partner events.
          </p>
          <div style="background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:0.6rem 0.85rem; font-size:0.8rem; color:var(--text-muted);">
            <strong style="color:var(--text-heading);">Duplicate Protection:</strong> UniCRM automatically screens mobile numbers to prevent duplicates. Clean records are imported straight into the Raw College Data Pool.
          </div>
        </div>

        <!-- Workflow 4 -->
        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:1.15rem; margin-bottom:1rem;">
          <div style="display:flex; align-items:center; gap:10px; margin-bottom:0.4rem;">
            <span style="background:#f59e0b; color:#fff; font-weight:800; font-size:0.75rem; padding:3px 9px; border-radius:6px;">WORKFLOW 4</span>
            <strong style="font-size:0.95rem; color:var(--text-heading);">Daily SLA & Overdue Call Tracking</strong>
          </div>
          <p style="margin:0 0 0.4rem 0; font-size:0.85rem; color:var(--text-main);">
            Open <strong>Daily Follow-ups</strong> (use the counselor filter dropdown) or open <strong>Staff & Team</strong>:
          </p>
          <div style="background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:0.6rem 0.85rem; font-size:0.8rem; color:var(--text-muted);">
            Ensure no counselor has uncompleted follow-ups older than 24 hours. Address bottlenecks quickly to maintain student satisfaction.
          </div>
        </div>

        <!-- Workflow 5 -->
        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:1.15rem;">
          <div style="display:flex; align-items:center; gap:10px; margin-bottom:0.4rem;">
            <span style="background:#f59e0b; color:#fff; font-weight:800; font-size:0.75rem; padding:3px 9px; border-radius:6px;">WORKFLOW 5</span>
            <strong style="font-size:0.95rem; color:var(--text-heading);">Issue Invoices & Track Receivables</strong>
          </div>
          <p style="margin:0 0 0.4rem 0; font-size:0.85rem; color:var(--text-main);">
            Go to <strong>Finance & Invoices &gt; Generate Invoice</strong>. Select the student candidate, enter consultancy or visa fee items, and issue the bill. Download and share official PDF receipts.
          </p>
        </div>
      </div>

      <!-- TAB 3: SUPER ADMIN MANUAL -->
      <div id="guideContent-admin" class="guide-content-panel" style="display:none;">
        <div style="background:#fef2f2; border-left:4px solid #ef4444; padding:1rem 1.25rem; border-radius:8px; margin-bottom:1.5rem;">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
            <strong style="color:#991b1b; font-size:0.95rem;"><i class="fa-solid fa-shield-halved"></i> Super Admin Governance: Global System Authority</strong>
            <span style="font-size:0.7rem; font-weight:700; background:#fee2e2; color:#991b1b; padding:2px 8px; border-radius:12px; text-transform:uppercase;">Full Access</span>
          </div>
          <span style="color:#b91c1c; font-size:0.825rem;">Complete unrestricted governance over all 16 application modules, organizational branches, staff accounts, RBAC permissions, and system security audits.</span>
        </div>

        <h4 style="font-size:1.05rem; font-weight:800; color:var(--text-heading); margin-bottom:1rem; display:flex; align-items:center; gap:8px;">
          <span style="background:#ef4444; color:#fff; width:24px; height:24px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:0.75rem;">★</span>
          Administrative Governance Modules:
        </h4>

        <!-- Admin Area 1 -->
        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:1.15rem; margin-bottom:1rem;">
          <div style="display:flex; align-items:center; gap:10px; margin-bottom:0.4rem;">
            <span style="background:#ef4444; color:#fff; font-weight:800; font-size:0.75rem; padding:3px 9px; border-radius:6px;">AREA 1</span>
            <strong style="font-size:0.95rem; color:var(--text-heading);">Staff & User Account Provisioning</strong>
          </div>
          <p style="margin:0 0 0.4rem 0; font-size:0.85rem; color:var(--text-main);">
            Go to <strong>Staff & Team</strong> to manage all organizational users:
          </p>
          <ul style="margin:0; padding-left:1.25rem; font-size:0.825rem; color:var(--text-main); display:flex; flex-direction:column; gap:4px;">
            <li>Click <strong>+ Add New Staff</strong> to create credentials for new Counselors or Admissions Managers.</li>
            <li>Assign staff to their designated operational branch location and role.</li>
            <li>Deactivate accounts immediately when staff members depart to maintain data integrity.</li>
          </ul>
        </div>

        <!-- Admin Area 2 -->
        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:1.15rem; margin-bottom:1rem;">
          <div style="display:flex; align-items:center; gap:10px; margin-bottom:0.4rem;">
            <span style="background:#ef4444; color:#fff; font-weight:800; font-size:0.75rem; padding:3px 9px; border-radius:6px;">AREA 2</span>
            <strong style="font-size:0.95rem; color:var(--text-heading);">Granular Roles & Permissions Control (RBAC Matrix)</strong>
          </div>
          <p style="margin:0 0 0.4rem 0; font-size:0.85rem; color:var(--text-main);">
            Open <strong>Roles & Permissions</strong>. Configure exact feature capabilities across all 16 modules:
          </p>
          <div style="background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:0.6rem 0.85rem; font-size:0.8rem; color:var(--text-muted);">
            Manage <em>View</em>, <em>Add</em>, <em>Edit</em>, <em>Delete</em>, <em>Assign</em>, <em>Approve</em>, and <em>Export</em> capabilities individually for Admissions Managers and Counselors. Changes apply instantly across the PHP security layer.
          </div>
        </div>

        <!-- Admin Area 3 -->
        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:1.15rem; margin-bottom:1rem;">
          <div style="display:flex; align-items:center; gap:10px; margin-bottom:0.4rem;">
            <span style="background:#ef4444; color:#fff; font-weight:800; font-size:0.75rem; padding:3px 9px; border-radius:6px;">AREA 3</span>
            <strong style="font-size:0.95rem; color:var(--text-heading);">Company Branch Locations Administration</strong>
          </div>
          <p style="margin:0 0 0.4rem 0; font-size:0.85rem; color:var(--text-main);">
            Under <strong>System Settings &gt; Company Locations</strong>, create, edit, activate, or deactivate organizational branches. Each branch represents an operational admissions division.
          </p>
        </div>

        <!-- Admin Area 4 -->
        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:1.15rem;">
          <div style="display:flex; align-items:center; gap:10px; margin-bottom:0.4rem;">
            <span style="background:#ef4444; color:#fff; font-weight:800; font-size:0.75rem; padding:3px 9px; border-radius:6px;">AREA 4</span>
            <strong style="font-size:0.95rem; color:var(--text-heading);">Security Audits, Activity Feeds & Archives</strong>
          </div>
          <p style="margin:0 0 0.4rem 0; font-size:0.85rem; color:var(--text-main);">
            Open <strong>Archive Repository</strong> to inspect system logs: user sign-ins, lead reallocations, financial records, and milestone adjustments with actor names, timestamps, and IP addresses.
          </p>
        </div>
      </div>

      <!-- TAB 4: THE 6 ADMISSION MILESTONES -->
      <div id="guideContent-stages" class="guide-content-panel" style="display:none;">
        <div style="background:#f5f3ff; border-left:4px solid #7008E7; padding:1rem 1.25rem; border-radius:8px; margin-bottom:1.5rem;">
          <strong style="color:#5b21b6; font-size:0.95rem; display:block; margin-bottom:4px;"><i class="fa-solid fa-route"></i> The 6-Stage Education Consultancy Lifecycle</strong>
          <span style="color:#6d28d9; font-size:0.825rem;">Every student candidate progresses through these 6 official milestones from their first inquiry to campus arrival.</span>
        </div>

        <div style="display:flex; flex-direction:column; gap:0.9rem;">
          <!-- Stage 1 -->
          <div style="padding:1rem 1.25rem; border-radius:10px; background:#f8fafc; border:1px solid #e2e8f0; border-left:5px solid #64748b;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.35rem;">
              <strong style="color:#1e293b; font-size:0.95rem;">1. ENQUIRY (Fresh Prospective Inquirer)</strong>
              <span style="font-size:0.7rem; font-weight:700; background:#e2e8f0; color:#475569; padding:2px 8px; border-radius:12px;">Stage 1</span>
            </div>
            <p style="margin:0 0 0.35rem 0; font-size:0.825rem; color:var(--text-main);">
              <strong>Definition:</strong> Initial prospective student whose contact information was captured through digital advertising, website inquiry, phone call, or campus fair.
            </p>
            <div style="font-size:0.8rem; color:#475569;">
              <strong>Counselor Action:</strong> Connect with candidate, evaluate eligibility, qualifications, preferred destination country, and program budget. Once candidate agrees to proceed with your consultancy, advance to <em>CUSTOMER</em>.
            </div>
          </div>

          <!-- Stage 2 -->
          <div style="padding:1rem 1.25rem; border-radius:10px; background:#f0f9ff; border:1px solid #bae6fd; border-left:5px solid #0284c7;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.35rem;">
              <strong style="color:#0369a1; font-size:0.95rem;">2. CUSTOMER (Retained Consultancy Client)</strong>
              <span style="font-size:0.7rem; font-weight:700; background:#e0f2fe; color:#0284c7; padding:2px 8px; border-radius:12px;">Stage 2</span>
            </div>
            <p style="margin:0 0 0.35rem 0; font-size:0.825rem; color:var(--text-main);">
              <strong>Definition:</strong> Candidate has formally engaged your consultancy. Official registration or service invoice is issued.
            </p>
            <div style="font-size:0.8rem; color:#0369a1;">
              <strong>Counselor Action:</strong> Issue processing fee invoice. Collect passport copy, academic transcripts, degree certificates, resume, SOP, and English proficiency test scores (IELTS/PTE/TOEFL).
            </div>
          </div>

          <!-- Stage 3 -->
          <div style="padding:1rem 1.25rem; border-radius:10px; background:#f5f3ff; border:1px solid #ddd6fe; border-left:5px solid #7008E7;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.35rem;">
              <strong style="color:#5b21b6; font-size:0.95rem;">3. APPLICATION (Submitted to Foreign Universities)</strong>
              <span style="font-size:0.7rem; font-weight:700; background:#ede9fe; color:#7008E7; padding:2px 8px; border-radius:12px;">Stage 3</span>
            </div>
            <p style="margin:0 0 0.35rem 0; font-size:0.825rem; color:var(--text-main);">
              <strong>Definition:</strong> Official applications have been filed with partner international universities.
            </p>
            <div style="font-size:0.8rem; color:#5b21b6;">
              <strong>Counselor Action:</strong> Track application reference numbers, follow up with university admissions departments, and submit any requested supplementary documents.
            </div>
          </div>

          <!-- Stage 4 -->
          <div style="padding:1rem 1.25rem; border-radius:10px; background:#fffbeb; border:1px solid #fde68a; border-left:5px solid #f59e0b;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.35rem;">
              <strong style="color:#92400e; font-size:0.95rem;">4. OFFER RECEIVED (Admission Acceptance Issued)</strong>
              <span style="font-size:0.7rem; font-weight:700; background:#fef3c7; color:#d97706; padding:2px 8px; border-radius:12px;">Stage 4</span>
            </div>
            <p style="margin:0 0 0.35rem 0; font-size:0.825rem; color:var(--text-main);">
              <strong>Definition:</strong> University has issued an official Conditional or Unconditional Offer Letter to the candidate.
            </p>
            <div style="font-size:0.8rem; color:#92400e;">
              <strong>Counselor Action:</strong> Review offer conditions with candidate and family. Guide student on fulfilling conditions and preparing fee remittance.
            </div>
          </div>

          <!-- Stage 5 -->
          <div style="padding:1rem 1.25rem; border-radius:10px; background:#fdf4ff; border:1px solid #f5d0fe; border-left:5px solid #c026d3;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.35rem;">
              <strong style="color:#86198f; font-size:0.95rem;">5. TUITION FEES PAID (Deposit Paid & CAS / I-20 Issued)</strong>
              <span style="font-size:0.7rem; font-weight:700; background:#fae8ff; color:#c026d3; padding:2px 8px; border-radius:12px;">Stage 5</span>
            </div>
            <p style="margin:0 0 0.35rem 0; font-size:0.825rem; color:var(--text-main);">
              <strong>Definition:</strong> Candidate has remitted the first-semester tuition deposit; university has released CAS (UK), I-20 (USA), or COE (Australia).
            </p>
            <div style="font-size:0.8rem; color:#86198f;">
              <strong>Counselor Action:</strong> Prepare visa application file, verify bank statements and financial proofs, book embassy biometrics, and conduct mock visa interviews.
            </div>
          </div>

          <!-- Stage 6 -->
          <div style="padding:1rem 1.25rem; border-radius:10px; background:#ecfdf5; border:1px solid #a7f3d0; border-left:5px solid #10b981;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.35rem;">
              <strong style="color:#065f46; font-size:0.95rem;">6. ENROLLED (Visa Granted & Student Admitted)</strong>
              <span style="font-size:0.7rem; font-weight:700; background:#d1fae5; color:#059669; padding:2px 8px; border-radius:12px;">Stage 6 • Goal Complete</span>
            </div>
            <p style="margin:0 0 0.35rem 0; font-size:0.825rem; color:var(--text-main);">
              <strong>Definition:</strong> Student visa is approved! Flight tickets are booked and candidate has matriculated at their destination university.
            </p>
            <div style="font-size:0.8rem; color:#065f46;">
              <strong>Counselor Action:</strong> Provide pre-departure briefing, share accommodation contacts, airport pickup details, and congratulate the student on their success.
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 5: FAQ & QUICK HELP -->
      <div id="guideContent-faq" class="guide-content-panel" style="display:none;">
        <div style="background:#f0f9ff; border-left:4px solid #0284c7; padding:1rem 1.25rem; border-radius:8px; margin-bottom:1.5rem;">
          <strong style="color:#0369a1; font-size:0.95rem; display:block; margin-bottom:4px;"><i class="fa-solid fa-circle-question"></i> Frequently Asked Questions & Quick Help</strong>
          <span style="color:#0284c7; font-size:0.825rem;">Practical answers to common daily tasks and system questions.</span>
        </div>

        <div style="display:flex; flex-direction:column; gap:0.9rem;">
          <div style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:1rem 1.25rem;">
            <strong style="color:var(--text-heading); font-size:0.9rem; display:flex; align-items:center; gap:8px;">
              <i class="fa-brands fa-whatsapp" style="color:#10b981;"></i> How do I contact a student via WhatsApp?
            </strong>
            <p style="margin:6px 0 0 0; font-size:0.825rem; color:var(--text-muted);">
              In the <strong>Student Leads</strong> table or <strong>Daily Follow-ups</strong> queue, look for the green WhatsApp icon next to the student's mobile number. Clicking it opens WhatsApp Web with the student's phone number ready so you can message them immediately.
            </p>
          </div>

          <div style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:1rem 1.25rem;">
            <strong style="color:var(--text-heading); font-size:0.9rem; display:flex; align-items:center; gap:8px;">
              <i class="fa-solid fa-phone-slash" style="color:#ef4444;"></i> What should I do if a student didn't answer my call?
            </strong>
            <p style="margin:6px 0 0 0; font-size:0.825rem; color:var(--text-muted);">
              Always record the attempt! Click the green <strong>Log Call</strong> button, choose <em>Attempted - No Answer</em>, and click <em>Tomorrow</em> for the next follow-up date. This ensures your follow-up queue remains accurate and compliant.
            </p>
          </div>

          <div style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:1rem 1.25rem;">
            <strong style="color:var(--text-heading); font-size:0.9rem; display:flex; align-items:center; gap:8px;">
              <i class="fa-solid fa-lock" style="color:#f59e0b;"></i> Can Counselors see leads assigned to other Counselors?
            </strong>
            <p style="margin:6px 0 0 0; font-size:0.825rem; color:var(--text-muted);">
              No. To ensure confidentiality, strict record isolation is enforced. Counselors can only see candidates assigned directly to them. Admissions Managers and Super Admin have oversight of all records.
            </p>
          </div>

          <div style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:1rem 1.25rem;">
            <strong style="color:var(--text-heading); font-size:0.9rem; display:flex; align-items:center; gap:8px;">
              <i class="fa-solid fa-file-invoice-dollar" style="color:#7008E7;"></i> How do I generate and print a client invoice?
            </strong>
            <p style="margin:6px 0 0 0; font-size:0.825rem; color:var(--text-muted);">
              Navigate to <strong>Finance & Invoices &gt; Generate Invoice</strong>. Select the candidate (must be in <em>Customer</em> or later stage), choose the fee package, verify the total, and click <em>Save & Issue</em>. You can print or download the official PDF receipt immediately.
            </p>
          </div>
        </div>
      </div>

    </div>

    <!-- Modal Footer -->
    <div class="modal-footer" style="padding:1rem 1.75rem; border-top:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center; background:#f8fafc; flex-shrink:0;">
      <span style="font-size:0.78rem; color:var(--text-muted);">
        <i class="fa-solid fa-circle-info" style="color:var(--primary); margin-right:4px;"></i> UniCRM Standard Operations Manual &bull; Continuous Guidance
      </span>
      <div style="display:flex; gap:0.5rem; align-items:center;">
        <button type="button" class="btn btn-outline" onclick="closeUserManualModal(); startInteractiveTutorial();" style="font-weight:700; border-radius:8px;">
          <i class="fa-solid fa-wand-magic-sparkles" style="color:var(--primary);"></i> Launch Live Tour
        </button>
        <button type="button" class="btn btn-primary" onclick="closeUserManualModal()" style="font-weight:700; padding:0.45rem 1.25rem; border-radius:8px;">
          Close Guide
        </button>
      </div>
    </div>
  </div>
</div>

<script>
// Expose CRM environment & current user metadata globally for interactive tutorials
window.CRM_BASE_URL = <?= json_encode(base_url()) ?>;
window.CRM_CURRENT_USER = {
  id: <?= (int)($currentUser['id'] ?? 0) ?>,
  name: <?= json_encode($currentUser['name'] ?? 'User') ?>,
  role_id: <?= (int)($currentUser['role_id'] ?? 0) ?>,
  role_name: <?= json_encode($currentUser['role_name'] ?? 'counselor') ?>,
  role_label: <?= json_encode($currentUser['role_label'] ?? 'Staff') ?>,
  is_super_admin: <?= is_super_admin() ? 'true' : 'false' ?>,
  is_branch_manager: <?= is_branch_manager() ? 'true' : 'false' ?>,
  is_counselor: <?= is_counselor() ? 'true' : 'false' ?>
};
// Global Modals Controller Functions
function openQuickLeadModal() {
  const m = document.getElementById('quickLeadModal');
  if (m) m.style.display = 'flex';
}
function closeQuickLeadModal() {
  const m = document.getElementById('quickLeadModal');
  if (m) m.style.display = 'none';
}

function openLogCallModal(leadId, studentName, currentStage) {
  const m = document.getElementById('globalLogCallModal');
  if (!m) return;
  document.getElementById('logCallLeadId').value = leadId;
  document.getElementById('logCallStudentLabel').textContent = 'Candidate: ' + (studentName || 'Student #' + leadId);
  if (currentStage && document.getElementById('logCallNewStage')) {
    document.getElementById('logCallNewStage').value = '';
  }
  m.style.display = 'flex';
}
function closeLogCallModal() {
  const m = document.getElementById('globalLogCallModal');
  if (m) m.style.display = 'none';
}
function setLogCallDate(days) {
  const d = new Date();
  d.setDate(d.getDate() + days);
  const dateStr = d.toISOString().split('T')[0];
  document.getElementById('logCallNextDate').value = dateStr;
}
function updateMaxFollowupNotice(selectElem) {
  const opt = selectElem.options[selectElem.selectedIndex];
  const max = opt.getAttribute('data-max') || 7;
  const notice = document.getElementById('maxDaysNotice');
  if (notice) {
    notice.innerHTML = `Governance Constraint: Next follow-up must be within <strong>${max} days</strong>.`;
  }
}

function openAdvanceStageModal(leadId, studentName, currentStage) {
  const m = document.getElementById('advanceStageModal');
  if (!m) return;
  document.getElementById('advanceStageLeadId').value = leadId;
  document.getElementById('advanceStageStudentLabel').textContent = 'Student: ' + (studentName || '#' + leadId);
  if (currentStage && document.getElementById('advanceStageSelect')) {
    document.getElementById('advanceStageSelect').value = currentStage;
  }
  m.style.display = 'flex';
}
function closeAdvanceStageModal() {
  const m = document.getElementById('advanceStageModal');
  if (m) m.style.display = 'none';
}

function openUserManualModal(tab) {
  const m = document.getElementById('userManualModal');
  if (m) {
    m.style.display = 'flex';
    switchGuideTab(tab || 'counselor');
  }
}
function closeUserManualModal() {
  const m = document.getElementById('userManualModal');
  if (m) m.style.display = 'none';
}
function switchGuideTab(tabId) {
  document.querySelectorAll('.guide-content-panel').forEach(p => p.style.display = 'none');
  document.querySelectorAll('.guide-tab-btn').forEach(b => {
    b.classList.remove('active', 'btn-primary');
    b.style.background = '#e2e8f0';
    b.style.color = '#334155';
  });
  
  const target = document.getElementById('guideContent-' + tabId);
  const btn = document.getElementById('tabBtn-' + tabId);
  if (target) target.style.display = 'block';
  if (btn) {
    btn.classList.add('active', 'btn-primary');
    btn.style.background = 'var(--primary)';
    btn.style.color = '#fff';
  }
}

// Close modals when clicking outside
window.addEventListener('click', function(e) {
  ['quickLeadModal', 'globalLogCallModal', 'advanceStageModal', 'userManualModal'].forEach(id => {
    const el = document.getElementById(id);
    if (el && e.target === el) {
      el.style.display = 'none';
    }
  });
});
</script>
