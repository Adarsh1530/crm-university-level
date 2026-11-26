<?php
/**
 * Global Modals & User-Friendly Operational Helpers - Core CRM
 * Provides: 
 * 1. Top Header 1-Click Update Action Modal (Active Status, Prospectivity, Notes, Call Log)
 * 2. Global Quick Add Lead Modal (with instant duplicate phone checking)
 * 3. Centralized Call Logger Modal (Call Direction, Status, Duration, Notes)
 * 4. Change Prospectivity & Counselor Escalation Modals
 * 5. Comprehensive CRM Architecture & Roles Guide
 */

global $pdo, $currentUser;
if (!$pdo || !$currentUser) return;

// Fetch reference data for global modals
$counselorsList = [];
try {
    $cStmt = $pdo->query("SELECT u.id, u.name, r.label as role_label, b.name as branch_name FROM users u JOIN roles r ON u.role_id = r.id LEFT JOIN branches b ON u.branch_id = b.id WHERE u.status = 'Active' ORDER BY u.name ASC");
    $counselorsList = $cStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$recentActiveLeads = [];
try {
    $scopeM = get_crm_scope_clause('l', 'assigned_counselor_id', 'branch_id');
    $recentActiveLeads = $pdo->query("SELECT l.id, l.lead_code, l.full_name, l.phone, l.active_status, l.prospectivity, l.region_number, l.main_region FROM leads l WHERE l.is_archived = 0 AND {$scopeM} ORDER BY l.id DESC LIMIT 15")->fetchAll();
} catch (Exception $e) {}

$interactionTitles = [];
try {
    $tStmt = $pdo->query("SELECT id, title, max_duration_days FROM interaction_titles WHERE status = 'Active' ORDER BY sort_order ASC");
    $interactionTitles = $tStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
?>

<!-- 1. TOP HEADER 1-CLICK UPDATE ACTION MODAL ("if you have an update, you can use it in the top") -->
<div class="modal fade" id="topUpdateModal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(12,36,60,0.55); align-items:center; justify-content:center; backdrop-filter:blur(3px);">
  <div class="modal-dialog" style="background:#fff; border-radius:16px; max-width:540px; width:92%; margin:auto; box-shadow:0 25px 60px rgba(0,0,0,0.25); overflow:hidden; max-height:92vh; display:flex; flex-direction:column;">
    <form method="POST" action="<?= base_url('leads/update_meta.php') ?>" style="display:flex; flex-direction:column; overflow:hidden;">
      <?= csrf_field() ?>
      <input type="hidden" name="field" value="top_update">
      <input type="hidden" name="return_url" id="topUpdateReturnUrl" value="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '') ?>">

      <div class="modal-header" style="padding:1.2rem 1.5rem; background:linear-gradient(135deg, #059669 0%, #10b981 100%); color:#fff; display:flex; justify-content:space-between; align-items:center;">
        <div>
          <h3 style="font-size:1.15rem; font-weight:700; margin:0; color:#fff; display:flex; align-items:center; gap:8px;">
            <i class="fa-solid fa-arrows-rotate"></i> 1-Click Record & Status Update
          </h3>
          <span style="font-size:0.75rem; color:rgba(255,255,255,0.9);">Update active statuses, prospectivity, and log interaction outcome</span>
        </div>
        <button type="button" onclick="closeTopUpdateModal()" style="border:none; background:rgba(255,255,255,0.2); color:#fff; width:30px; height:30px; border-radius:50%; font-size:1.2rem; cursor:pointer; display:flex; align-items:center; justify-content:center;">&times;</button>
      </div>

      <div class="modal-body" style="padding:1.5rem; overflow-y:auto; flex:1;">
        <!-- Lead Selector -->
        <div class="form-group" style="margin-bottom:1rem;">
          <label class="form-label" style="font-weight:700; font-size:0.825rem;">Select Lead / Account <span style="color:red;">*</span></label>
          <select name="lead_id" id="topUpdateLeadSelect" class="form-control" required style="font-size:0.85rem; font-weight:600;">
            <?php foreach ($recentActiveLeads as $ral): ?>
              <option value="<?= $ral['id'] ?>" data-status="<?= htmlspecialchars($ral['active_status'] ?? 'Contacted') ?>" data-prospect="<?= htmlspecialchars($ral['prospectivity'] ?? 'Warm') ?>">
                <?= htmlspecialchars($ral['lead_code']) ?> &bull; <?= htmlspecialchars($ral['full_name']) ?> (<?= htmlspecialchars($ral['region_number'] ?? 'REG-01') ?> - <?= htmlspecialchars($ral['main_region'] ?? 'Central') ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.85rem; margin-bottom:1rem;">
          <!-- Active Status -->
          <div class="form-group" style="margin:0;">
            <label class="form-label" style="font-weight:700; font-size:0.825rem;">Active Status <span style="color:red;">*</span></label>
            <select name="active_status" id="topUpdateStatusSelect" class="form-control" style="font-size:0.85rem; font-weight:600;">
              <option value="New Lead">New Lead</option>
              <option value="Call In Progress">Call In Progress</option>
              <option value="Contacted" selected>Contacted</option>
              <option value="Follow-up Scheduled">Follow-up Scheduled</option>
              <option value="Prospect Qualified">Prospect Qualified</option>
              <option value="Escalated to Counselor">Escalated to Counselor</option>
              <option value="Converted">Converted to Customer</option>
              <option value="Disqualified">Disqualified</option>
            </select>
          </div>

          <!-- Prospectivity -->
          <div class="form-group" style="margin:0;">
            <label class="form-label" style="font-weight:700; font-size:0.825rem;">Prospectivity Tier <span style="color:red;">*</span></label>
            <select name="prospectivity" id="topUpdateProspectivitySelect" class="form-control" style="font-size:0.85rem; font-weight:700;">
              <option value="Hot">🔥 Hot (High Conversion Readiness)</option>
              <option value="Warm" selected>⚡ Warm (Active Engagement)</option>
              <option value="Cold">💤 Cold (Low Immediate Intent)</option>
            </select>
          </div>
        </div>

        <!-- Update Notes -->
        <div class="form-group" style="margin-bottom:1rem;">
          <label class="form-label" style="font-weight:700; font-size:0.825rem;">Action Notes / Outcome Summary <span style="color:red;">*</span></label>
          <textarea name="notes" id="topUpdateNotes" class="form-control" rows="2" placeholder="Record brief notes on this interaction..." required></textarea>
        </div>

        <!-- Optional Direct Call Log Checkbox -->
        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:0.85rem; margin-bottom:0.5rem;">
          <label style="display:flex; align-items:center; gap:8px; font-weight:700; font-size:0.825rem; color:#1e293b; cursor:pointer; margin-bottom:0;">
            <input type="checkbox" name="log_call_now" id="topUpdateLogCallCheck" value="1" onchange="document.getElementById('topUpdateCallFields').style.display = this.checked ? 'block' : 'none';">
            <span>Also log this as a call interaction in Call Data</span>
          </label>

          <div id="topUpdateCallFields" style="display:none; margin-top:0.75rem; border-top:1px dashed #cbd5e1; padding-top:0.75rem;">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.65rem; margin-bottom:0.5rem;">
              <div>
                <label style="font-size:0.75rem; font-weight:600; display:block; margin-bottom:3px;">Call Direction</label>
                <select name="call_direction" class="form-control" style="font-size:0.8rem; padding:4px 8px;">
                  <option value="Outbound">Outbound (Agent Dialed)</option>
                  <option value="Inbound">Inbound (Customer Called)</option>
                </select>
              </div>
              <div>
                <label style="font-size:0.75rem; font-weight:600; display:block; margin-bottom:3px;">Call Status</label>
                <select name="call_status" class="form-control" style="font-size:0.8rem; padding:4px 8px;">
                  <option value="Connected">Connected & Spoke</option>
                  <option value="Busy">Line Busy</option>
                  <option value="No Answer">No Answer / Ringing</option>
                  <option value="Left Voicemail">Left Voicemail</option>
                  <option value="Switched Off">Phone Switched Off</option>
                </select>
              </div>
            </div>
            <div>
              <label style="font-size:0.75rem; font-weight:600; display:block; margin-bottom:3px;">Duration (Seconds)</label>
              <input type="number" name="duration_seconds" class="form-control" value="60" style="font-size:0.8rem; padding:4px 8px;">
            </div>
          </div>
        </div>

        <!-- Long-cycle toggle -->
        <div style="margin-top:0.5rem;">
          <label style="display:flex; align-items:center; gap:8px; font-size:0.78rem; color:var(--text-muted); cursor:pointer;">
            <input type="checkbox" name="is_long_cycle" value="1">
            <span>Mark as Long-Cycle Follow-Up (prolonged prospect cycle requiring multi-week nurturing)</span>
          </label>
        </div>
      </div>

      <div class="modal-footer" style="padding:1rem 1.5rem; border-top:1px solid var(--border-color); display:flex; justify-content:flex-end; gap:0.5rem; background:#f8fafc;">
        <button type="button" class="btn btn-secondary btn-sm" onclick="closeTopUpdateModal()">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm" style="background:#059669; border-color:#059669; font-weight:700; padding:0.45rem 1.25rem;">
          <i class="fa-solid fa-floppy-disk"></i> Submit Update
        </button>
      </div>
    </form>
  </div>
</div>

<!-- 2. GLOBAL QUICK ADD LEAD MODAL -->
<div class="modal fade" id="quickLeadModal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(12,36,60,0.55); align-items:center; justify-content:center; backdrop-filter:blur(3px);">
  <div class="modal-dialog" style="background:#fff; border-radius:16px; max-width:540px; width:92%; margin:auto; box-shadow:0 25px 60px rgba(0,0,0,0.25); overflow:hidden; max-height:92vh; display:flex; flex-direction:column;">
    <form method="POST" action="<?= base_url('leads/quick_add.php') ?>" style="display:flex; flex-direction:column; overflow:hidden;">
      <?= csrf_field() ?>

      <div class="modal-header" style="padding:1.2rem 1.5rem; background:linear-gradient(135deg, #4f46e5 0%, #3730a3 100%); color:#fff; display:flex; justify-content:space-between; align-items:center;">
        <div>
          <h3 style="font-size:1.15rem; font-weight:700; margin:0; color:#fff; display:flex; align-items:center; gap:8px;">
            <i class="fa-solid fa-user-plus"></i> Quick Add Lead
          </h3>
          <span style="font-size:0.75rem; color:rgba(255,255,255,0.85);">Fast lead registration with phone cross-checking</span>
        </div>
        <button type="button" onclick="closeQuickLeadModal()" style="border:none; background:rgba(255,255,255,0.2); color:#fff; width:30px; height:30px; border-radius:50%; font-size:1.2rem; cursor:pointer; display:flex; align-items:center; justify-content:center;">&times;</button>
      </div>

      <div class="modal-body" style="padding:1.5rem; overflow-y:auto; flex:1;">
        <!-- Core Contact -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.85rem; margin-bottom:0.85rem;">
          <div class="form-group" style="margin:0;">
            <label class="form-label" style="font-weight:700; font-size:0.825rem;">Contact Name <span style="color:red;">*</span></label>
            <input type="text" name="full_name" class="form-control" placeholder="e.g. Rahul Nair" required autofocus>
          </div>
          <div class="form-group" style="margin:0;">
            <label class="form-label" style="font-weight:700; font-size:0.825rem;">Phone Number <span style="color:red;">*</span></label>
            <input type="tel" id="quickLeadPhone" name="phone" class="form-control" placeholder="10-digit mobile" required oninput="checkLeadPhoneDuplicate(this.value, 'quickLeadPhoneFeedback', 'quickLeadSubmitBtn')">
            <div id="quickLeadPhoneFeedback" style="display:none; margin-top:4px; font-size:0.75rem;"></div>
          </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.85rem; margin-bottom:0.85rem;">
          <div class="form-group" style="margin:0;">
            <label class="form-label" style="font-weight:600; font-size:0.825rem;">Business / Account Name</label>
            <input type="text" name="business_name" class="form-control" placeholder="e.g. Nair Global Enterprises">
          </div>
          <div class="form-group" style="margin:0;">
            <label class="form-label" style="font-weight:600; font-size:0.825rem;">Email Address</label>
            <input type="email" name="email" class="form-control" placeholder="contact@example.com">
          </div>
        </div>

        <!-- Regional Segmentation -->
        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:0.75rem; margin-bottom:0.85rem;">
          <div class="form-group" style="margin:0;">
            <label class="form-label" style="font-size:0.78rem; font-weight:700;">Region Number</label>
            <select name="region_number" class="form-control" style="font-size:0.82rem;">
              <option value="REG-01">REG-01</option>
              <option value="REG-02">REG-02</option>
              <option value="REG-03">REG-03</option>
              <option value="REG-04">REG-04</option>
            </select>
          </div>
          <div class="form-group" style="margin:0;">
            <label class="form-label" style="font-size:0.78rem; font-weight:700;">Main Region</label>
            <select name="main_region" class="form-control" style="font-size:0.82rem;">
              <option value="Central Zone">Central Zone</option>
              <option value="North Zone">North Zone</option>
              <option value="South Zone">South Zone</option>
              <option value="West Zone">West Zone</option>
            </select>
          </div>
          <div class="form-group" style="margin:0;">
            <label class="form-label" style="font-size:0.78rem; font-weight:700;">Prospectivity</label>
            <select name="prospectivity" class="form-control" style="font-size:0.82rem; font-weight:700;">
              <option value="Hot">🔥 Hot</option>
              <option value="Warm" selected>⚡ Warm</option>
              <option value="Cold">💤 Cold</option>
            </select>
          </div>
        </div>

        <?php if (!is_counselor() && !empty($counselorsList)): ?>
          <div class="form-group" style="margin-bottom:0.85rem;">
            <label class="form-label" style="font-weight:700; font-size:0.825rem;">Assign Counselor / Advisor</label>
            <select name="counselor_id" class="form-control">
              <?php foreach ($counselorsList as $cl): ?>
                <option value="<?= $cl['id'] ?>" <?= $cl['id'] == $currentUser['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($cl['name']) ?> (<?= htmlspecialchars($cl['role_label'] ?? 'Staff') ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php endif; ?>

        <div class="form-group" style="margin-bottom:0.85rem;">
          <label class="form-label" style="font-weight:600; font-size:0.825rem;">Initial Notes</label>
          <textarea name="notes" class="form-control" rows="2" placeholder="Initial discussion summary..."></textarea>
        </div>

        <div class="form-group" style="margin-bottom:0;">
          <label class="form-label" style="font-weight:700; font-size:0.825rem;">Schedule First Follow-up</label>
          <div style="display:flex; gap:0.5rem; align-items:center;">
            <input type="date" name="next_followup_date" id="quickLeadFollowupDate" class="form-control" value="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
            <button type="button" class="btn btn-outline btn-sm" onclick="document.getElementById('quickLeadFollowupDate').value = '<?= date('Y-m-d', strtotime('+1 day')) ?>'">Tomorrow</button>
            <button type="button" class="btn btn-outline btn-sm" onclick="document.getElementById('quickLeadFollowupDate').value = '<?= date('Y-m-d', strtotime('+3 days')) ?>'">+3 Days</button>
          </div>
        </div>
      </div>

      <div class="modal-footer" style="padding:1rem 1.5rem; border-top:1px solid var(--border-color); display:flex; justify-content:flex-end; gap:0.5rem; background:#f8fafc;">
        <button type="button" class="btn btn-secondary" onclick="closeQuickLeadModal()">Cancel</button>
        <button type="submit" id="quickLeadSubmitBtn" class="btn btn-primary" style="font-weight:700; padding:0.5rem 1.25rem;">
          <i class="fa-solid fa-check"></i> Save Lead
        </button>
      </div>
    </form>
  </div>
</div>

<!-- 3. CENTRALIZED CALL LOGGER MODAL -->
<div class="modal fade" id="globalLogCallModal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(12,36,60,0.55); align-items:center; justify-content:center; backdrop-filter:blur(3px);">
  <div class="modal-dialog" style="background:#fff; border-radius:16px; max-width:520px; width:92%; margin:auto; box-shadow:0 25px 60px rgba(0,0,0,0.25); overflow:hidden;">
    <form method="POST" action="<?= base_url('leads/update_meta.php') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="field" value="log_call">
      <input type="hidden" name="lead_id" id="logCallLeadId" value="0">
      <input type="hidden" name="phone_dialed" id="logCallPhoneDialed" value="">
      <input type="hidden" name="return_url" id="logCallReturnUrl" value="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '') ?>">

      <div class="modal-header" style="padding:1.2rem 1.5rem; background:linear-gradient(135deg, #0284c7 0%, #0369a1 100%); color:#fff; display:flex; justify-content:space-between; align-items:center;">
        <div>
          <h3 style="font-size:1.15rem; font-weight:700; margin:0; color:#fff; display:flex; align-items:center; gap:8px;">
            <i class="fa-solid fa-phone-volume"></i> Record Call Interaction
          </h3>
          <span style="font-size:0.75rem; color:rgba(255,255,255,0.9);" id="logCallStudentLabel">Lead: Contact</span>
        </div>
        <button type="button" onclick="closeLogCallModal()" style="border:none; background:rgba(255,255,255,0.2); color:#fff; width:30px; height:30px; border-radius:50%; font-size:1.2rem; cursor:pointer; display:flex; align-items:center; justify-content:center;">&times;</button>
      </div>

      <div class="modal-body" style="padding:1.5rem;">
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; margin-bottom:1rem;">
          <div class="form-group" style="margin:0;">
            <label class="form-label" style="font-weight:700; font-size:0.825rem;">Call Direction <span style="color:red;">*</span></label>
            <select name="call_direction" class="form-control" required style="font-weight:600;">
              <option value="Outbound">Outbound (Agent Initiated)</option>
              <option value="Inbound">Inbound (Client Called)</option>
            </select>
          </div>
          <div class="form-group" style="margin:0;">
            <label class="form-label" style="font-weight:700; font-size:0.825rem;">Connection Outcome <span style="color:red;">*</span></label>
            <select name="call_status" class="form-control" required style="font-weight:600;">
              <option value="Connected">Connected & Spoke</option>
              <option value="Busy">Line Busy</option>
              <option value="No Answer">No Answer / Ringing</option>
              <option value="Left Voicemail">Left Voicemail</option>
              <option value="Switched Off">Switched Off</option>
            </select>
          </div>
        </div>

        <div class="form-group" style="margin-bottom:1rem;">
          <label class="form-label" style="font-weight:700; font-size:0.825rem;">Duration (Seconds)</label>
          <input type="number" name="duration_seconds" class="form-control" value="60" min="0">
        </div>

        <div class="form-group" style="margin-bottom:0;">
          <label class="form-label" style="font-weight:700; font-size:0.825rem;">Call Notes & Next Action Plan <span style="color:red;">*</span></label>
          <textarea name="notes" class="form-control" rows="3" placeholder="Summary of call discussion and agreed next steps..." required></textarea>
        </div>
      </div>

      <div class="modal-footer" style="padding:1rem 1.5rem; border-top:1px solid var(--border-color); display:flex; justify-content:flex-end; gap:0.5rem; background:#f8fafc;">
        <button type="button" class="btn btn-secondary btn-sm" onclick="closeLogCallModal()">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm" style="background:#0284c7; border-color:#0284c7; font-weight:700; padding:0.45rem 1.25rem;">
          <i class="fa-solid fa-floppy-disk"></i> Save Call Log
        </button>
      </div>
    </form>
  </div>
</div>

<!-- 4. COUNSELOR ESCALATION MODAL -->
<div class="modal fade" id="escalateCounselorModal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(12,36,60,0.55); align-items:center; justify-content:center; backdrop-filter:blur(3px);">
  <div class="modal-dialog" style="background:#fff; border-radius:14px; max-width:440px; width:92%; margin:auto; box-shadow:0 25px 60px rgba(0,0,0,0.25); overflow:hidden;">
    <form method="POST" action="<?= base_url('leads/update_meta.php') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="field" value="escalate_counselor">
      <input type="hidden" name="lead_id" id="escalateLeadId" value="0">
      <input type="hidden" name="return_url" id="escalateReturnUrl" value="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '') ?>">

      <div class="modal-header" style="padding:1.2rem 1.5rem; background:linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%); color:#fff; display:flex; justify-content:space-between; align-items:center;">
        <div>
          <h3 style="font-size:1.1rem; font-weight:700; margin:0; color:#fff;">Escalate to Counselor</h3>
          <span style="font-size:0.75rem; color:rgba(255,255,255,0.9);" id="escalateLeadLabel">Qualified Prospect</span>
        </div>
        <button type="button" onclick="closeEscalateCounselorModal()" style="border:none; background:rgba(255,255,255,0.2); color:#fff; width:28px; height:28px; border-radius:50%; font-size:1.2rem; cursor:pointer; display:flex; align-items:center; justify-content:center;">&times;</button>
      </div>

      <div class="modal-body" style="padding:1.5rem;">
        <div style="background:#f5f3ff; border:1px solid #ddd6fe; border-radius:8px; padding:0.75rem 1rem; margin-bottom:1rem; font-size:0.8rem; color:#5b21b6;">
          <i class="fa-solid fa-circle-check"></i> Lead meets prospectivity criteria (Hot/Warm) and is ready for counselor consultation.
        </div>
        <div class="form-group" style="margin-bottom:0;">
          <label class="form-label" style="font-weight:700; font-size:0.825rem;">Select Designated Counselor</label>
          <select name="counselor_id" class="form-control" style="font-size:0.85rem; font-weight:600;">
            <?php foreach ($counselorsList as $cl): ?>
              <?php if (($cl['role_label'] ?? '') === 'Counselor / Advisor' || ($cl['id'] == 3)): ?>
                <option value="<?= $cl['id'] ?>"><?= htmlspecialchars($cl['name']) ?> (Counselor)</option>
              <?php endif; ?>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="modal-footer" style="padding:0.85rem 1.5rem; border-top:1px solid var(--border-color); display:flex; justify-content:flex-end; gap:0.5rem; background:#f8fafc;">
        <button type="button" class="btn btn-secondary btn-sm" onclick="closeEscalateCounselorModal()">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm" style="background:#7c3aed; border-color:#7c3aed; font-weight:700; padding:0.45rem 1.25rem;">
          <i class="fa-solid fa-share"></i> Route to Counselor
        </button>
      </div>
    </form>
  </div>
</div>

<!-- 5. CHANGE PROSPECTIVITY MODAL -->
<div class="modal fade" id="changeProspectivityModal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(12,36,60,0.55); align-items:center; justify-content:center; backdrop-filter:blur(3px);">
  <div class="modal-dialog" style="background:#fff; border-radius:14px; max-width:400px; width:92%; margin:auto; box-shadow:0 25px 60px rgba(0,0,0,0.25); overflow:hidden;">
    <form method="POST" action="<?= base_url('leads/update_meta.php') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="field" value="prospectivity">
      <input type="hidden" name="lead_id" id="prospectivityLeadId" value="0">
      <input type="hidden" name="return_url" id="prospectivityReturnUrl" value="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '') ?>">
      <div class="modal-header" style="padding:1.2rem 1.5rem; background:linear-gradient(135deg, #7008E7 0%, #5955D1 100%); color:#fff; display:flex; justify-content:space-between; align-items:center;">
        <div>
          <h3 style="font-size:1.1rem; font-weight:700; margin:0; color:#fff;">Change Prospectivity</h3>
          <span style="font-size:0.75rem; color:rgba(255,255,255,0.9);" id="prospectivityStudentLabel">Candidate</span>
        </div>
        <button type="button" onclick="closeChangeProspectivityModal()" style="border:none; background:rgba(255,255,255,0.2); color:#fff; width:28px; height:28px; border-radius:50%; font-size:1.2rem; cursor:pointer; display:flex; align-items:center; justify-content:center;">&times;</button>
      </div>
      <div class="modal-body" style="padding:1.5rem;">
        <label class="form-label" style="font-weight:700; font-size:0.85rem; margin-bottom:0.5rem;">Select Prospectivity Level</label>
        <select name="prospectivity" id="prospectivitySelect" class="form-control" style="font-size:0.9rem; font-weight:700;">
          <option value="Hot">🔥 Hot (High Conversion Prospect)</option>
          <option value="Warm">⚡ Warm (Active Engagement)</option>
          <option value="Cold">💤 Cold (Low Immediate Prospect)</option>
        </select>
        <p style="font-size:0.75rem; color:var(--text-muted); margin-top:0.75rem; line-height:1.4;">
          Hot prospectivity leads are prioritized for counselor consultations.
        </p>
      </div>
      <div class="modal-footer" style="padding:0.85rem 1.5rem; border-top:1px solid var(--border-color); display:flex; justify-content:flex-end; gap:0.5rem; background:#f8fafc;">
        <button type="button" class="btn btn-secondary btn-sm" onclick="closeChangeProspectivityModal()">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm" style="font-weight:700; padding:0.45rem 1.25rem;">Update</button>
      </div>
    </form>
  </div>
</div>

<!-- 6. USER MANUAL & CRM ARCHITECTURE GUIDE MODAL -->
<div class="modal fade" id="userManualModal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(12,36,60,0.55); align-items:center; justify-content:center; backdrop-filter:blur(3px);">
  <div class="modal-dialog" style="background:#fff; border-radius:16px; max-width:820px; width:94%; margin:auto; box-shadow:0 25px 60px rgba(0,0,0,0.25); overflow:hidden; max-height:92vh; display:flex; flex-direction:column;">
    <div class="modal-header" style="padding:1.2rem 1.5rem; background:linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color:#fff; display:flex; justify-content:space-between; align-items:center;">
      <div>
        <h3 style="font-size:1.2rem; font-weight:800; margin:0; color:#fff; display:flex; align-items:center; gap:8px;">
          <i class="fa-solid fa-book-open"></i> CRM Operations & Roles Guide
        </h3>
        <span style="font-size:0.75rem; color:rgba(255,255,255,0.8);">Customer Relationship Management &bull; 6 Core Pillars</span>
      </div>
      <button type="button" onclick="closeUserManualModal()" style="border:none; background:rgba(255,255,255,0.2); color:#fff; width:30px; height:30px; border-radius:50%; font-size:1.2rem; cursor:pointer; display:flex; align-items:center; justify-content:center;">&times;</button>
    </div>

    <div class="modal-body" style="padding:1.5rem; overflow-y:auto; flex:1; font-size:0.875rem; line-height:1.6;">
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1.25rem;">
        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:1rem;">
          <h4 style="font-size:0.95rem; font-weight:800; color:#1e293b; margin:0 0 0.5rem 0; display:flex; align-items:center; gap:6px;">
            <i class="fa-solid fa-crown" style="color:#b91c1c;"></i> 1. Super Admin
          </h4>
          <p style="margin:0; font-size:0.8rem; color:#475569;">
            Full administrative governance, system identity management, and seamless multi-role persona switching.
          </p>
        </div>

        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:1rem;">
          <h4 style="font-size:0.95rem; font-weight:800; color:#1e293b; margin:0 0 0.5rem 0; display:flex; align-items:center; gap:6px;">
            <i class="fa-solid fa-people-roof" style="color:#b45309;"></i> 2. Team Leader
          </h4>
          <p style="margin:0; font-size:0.8rem; color:#475569;">
            Regional supervisor with lead review, pipeline density monitoring across Central/Outer zones, and assignment oversight.
          </p>
        </div>

        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:1rem;">
          <h4 style="font-size:0.95rem; font-weight:800; color:#1e293b; margin:0 0 0.5rem 0; display:flex; align-items:center; gap:6px;">
            <i class="fa-solid fa-user-tie" style="color:#15803d;"></i> 3. Counselor / Advisor
          </h4>
          <p style="margin:0; font-size:0.8rem; color:#475569;">
            Conducts qualified consultations, manages scheduled follow-up dates and reminder alarms, and closes conversions.
          </p>
        </div>

        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:1rem;">
          <h4 style="font-size:0.95rem; font-weight:800; color:#1e293b; margin:0 0 0.5rem 0; display:flex; align-items:center; gap:6px;">
            <i class="fa-solid fa-headset" style="color:#7e22ce;"></i> 4. Operations / Accessor
          </h4>
          <p style="margin:0; font-size:0.8rem; color:#475569;">
            Inbound and outbound social media lead triage, phone verification cross-checking, initial contact logging, and counselor routing.
          </p>
        </div>
      </div>

      <div style="border-top:1px solid #e2e8f0; padding-top:1rem;">
        <h4 style="font-size:0.9rem; font-weight:800; color:#0f172a; margin-bottom:0.75rem;">Daily Duties Workflow:</h4>
        <ol style="margin:0; padding-left:1.25rem; font-size:0.825rem; color:#334155;">
          <li><strong>Daily Start:</strong> Check active worklists via the top header <strong>Tasks Bar</strong>.</li>
          <li><strong>Data Review:</strong> Triage incoming <strong>Social Media Leads</strong> and verify assigned regional distribution.</li>
          <li><strong>Call Execution:</strong> Review linked <strong>Call Data</strong>, initiate contact, and log connection status.</li>
          <li><strong>Queue Maintenance:</strong> Clear all <strong>Pending Follow-ups Backlog</strong> and book next <strong>Scheduled Follow-ups</strong>.</li>
          <li><strong>Data Verification:</strong> Cross-verify contact phone numbers digit-by-digit and submit updates via the top <strong>Update Button</strong>.</li>
          <li><strong>Escalation:</strong> Route qualified Hot/Warm prospects to designated <strong>Counselors</strong>.</li>
        </ol>
      </div>
    </div>

    <div class="modal-footer" style="padding:0.85rem 1.5rem; border-top:1px solid #e2e8f0; background:#f8fafc; display:flex; justify-content:flex-end;">
      <button type="button" class="btn btn-secondary btn-sm" onclick="closeUserManualModal()">Close Guide</button>
    </div>
  </div>
</div>

<script>
// Top Update Modal Controllers
function openTopUpdateModal(leadId) {
  const m = document.getElementById('topUpdateModal');
  if (!m) return;
  const sel = document.getElementById('topUpdateLeadSelect');
  if (sel && leadId) {
    sel.value = leadId;
    const opt = sel.options[sel.selectedIndex];
    if (opt) {
      if (opt.dataset.status && document.getElementById('topUpdateStatusSelect')) {
        document.getElementById('topUpdateStatusSelect').value = opt.dataset.status;
      }
      if (opt.dataset.prospect && document.getElementById('topUpdateProspectivitySelect')) {
        document.getElementById('topUpdateProspectivitySelect').value = opt.dataset.prospect;
      }
    }
  }
  m.style.display = 'flex';
}
function closeTopUpdateModal() {
  const m = document.getElementById('topUpdateModal');
  if (m) m.style.display = 'none';
}

// Quick Lead Modal
function openQuickLeadModal() {
  const m = document.getElementById('quickLeadModal');
  if (m) m.style.display = 'flex';
}
function closeQuickLeadModal() {
  const m = document.getElementById('quickLeadModal');
  if (m) m.style.display = 'none';
}

// Log Call Modal
function openLogCallModal(leadId, leadName, phoneDialed) {
  const m = document.getElementById('globalLogCallModal');
  if (!m) return;
  if (leadId) document.getElementById('logCallLeadId').value = leadId;
  if (phoneDialed) document.getElementById('logCallPhoneDialed').value = phoneDialed;
  if (leadName) document.getElementById('logCallStudentLabel').textContent = 'Lead: ' + leadName;
  m.style.display = 'flex';
}
function closeLogCallModal() {
  const m = document.getElementById('globalLogCallModal');
  if (m) m.style.display = 'none';
}

// Escalate Counselor Modal
function openEscalateCounselorModal(leadId, leadName) {
  const m = document.getElementById('escalateCounselorModal');
  if (!m) return;
  if (leadId) document.getElementById('escalateLeadId').value = leadId;
  if (leadName) document.getElementById('escalateLeadLabel').textContent = 'Candidate: ' + leadName;
  m.style.display = 'flex';
}
function closeEscalateCounselorModal() {
  const m = document.getElementById('escalateCounselorModal');
  if (m) m.style.display = 'none';
}

// Change Prospectivity Modal
function openChangeProspectivityModal(leadId, studentName, currentProspectivity) {
  const m = document.getElementById('changeProspectivityModal');
  if (!m) return;
  document.getElementById('prospectivityLeadId').value = leadId;
  document.getElementById('prospectivityStudentLabel').textContent = 'Candidate: ' + (studentName || '#' + leadId);
  const sel = document.getElementById('prospectivitySelect');
  if (sel && currentProspectivity) sel.value = currentProspectivity;
  m.style.display = 'flex';
}
function closeChangeProspectivityModal() {
  const m = document.getElementById('changeProspectivityModal');
  if (m) m.style.display = 'none';
}

// User Manual Guide Modal
function openUserManualModal() {
  const m = document.getElementById('userManualModal');
  if (m) m.style.display = 'flex';
}
function closeUserManualModal() {
  const m = document.getElementById('userManualModal');
  if (m) m.style.display = 'none';
}

// Phone verification duplicate check
let phoneCheckTimeout = null;
function checkLeadPhoneDuplicate(phoneVal, feedbackElemId, submitBtnId) {
  const feedback = document.getElementById(feedbackElemId);
  const submitBtn = document.getElementById(submitBtnId);
  if (!feedback) return;
  clearTimeout(phoneCheckTimeout);
  const clean = phoneVal.replace(/[^0-9]/g, '');
  if (clean.length < 10) {
    feedback.style.display = 'none';
    if (submitBtn) submitBtn.disabled = false;
    return;
  }
  phoneCheckTimeout = setTimeout(() => {
    fetch((window.CRM_BASE_URL || '/') + 'api/check_phone.php?phone=' + encodeURIComponent(clean))
      .then(res => res.json())
      .then(data => {
        if (data.exists) {
          feedback.style.display = 'block';
          feedback.innerHTML = `<span style="color:#d97706; font-size:0.75rem;"><i class="fa-solid fa-triangle-exclamation"></i> Phone already exists for <strong>${data.full_name}</strong> (${data.lead_code})</span>`;
        } else {
          feedback.style.display = 'block';
          feedback.innerHTML = `<span style="color:#10b981; font-size:0.75rem;"><i class="fa-solid fa-check"></i> Phone is unique & valid</span>`;
        }
      }).catch(() => {});
  }, 300);
}

// Close modals when clicking outside
window.addEventListener('click', function(e) {
  ['topUpdateModal', 'quickLeadModal', 'globalLogCallModal', 'escalateCounselorModal', 'changeProspectivityModal', 'userManualModal'].forEach(id => {
    const el = document.getElementById(id);
    if (el && e.target === el) {
      el.style.display = 'none';
    }
  });
});
</script>
