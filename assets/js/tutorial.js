/**
 * UniCRM Interactive Guided Tutorial System
 * Comprehensive, Role-Specific, Step-by-Step Guided Walkthrough
 * 100% Vanilla JavaScript - Zero External Dependencies
 */
(function() {
  'use strict';

  // Role-Specific Step Walkthrough Specifications
  const TUTORIAL_ROLES = {
    // 1. SUPER ADMIN (12 Comprehensive Steps)
    super_admin: [
      {
        target: '#btn-guide-tutorial',
        fallback: '#top-navbar',
        title: 'System Administration Cockpit & Role Switcher',
        badge: 'Super Admin • Global Authority',
        icon: 'fa-crown',
        description: 'Welcome, Super Admin! From the top navigation bar, you have access to the Role Switcher (simulate any role with 1 click), real-time notification alerts, and this interactive tutorial at any time.',
        tip: 'Click the Guide button whenever you want to refresh your memory on workflows or restart the tour.',
        position: 'bottom'
      },
      {
        target: '#btn-quick-add-lead',
        fallback: '.page-header',
        title: '15-Second Lead Capture',
        badge: 'Inquiry Capture',
        icon: 'fa-user-plus',
        description: 'Rapidly record student inquiries from walk-ins, phone calls, or education expo desks. Automatically assigns an institutional tracking code (EDR-YYYY-XXXX) and routes the candidate to the designated branch counselor.',
        tip: 'Enter candidate name, phone, study country, and next follow-up in under 15 seconds.',
        position: 'bottom'
      },
      {
        target: '#btn-generate-invoice',
        fallback: '#widget-4step-workflow',
        title: 'Institutional Invoicing & Billings',
        badge: 'Financial Governance',
        icon: 'fa-file-invoice-dollar',
        description: 'Generate official institutional consultancy service invoices, university application fees, and visa handling charges with automated tax calculations and downloadable client PDF receipts.',
        tip: 'All invoices update branch accounts receivables and client balance ledgers in real time.',
        position: 'bottom'
      },
      {
        target: '#widget-4step-workflow',
        fallback: '.content-area',
        title: 'Standard 4-Step Daily Admissions Workflow',
        badge: 'Process Lifecycle',
        icon: 'fa-route',
        description: 'The core daily operational sequence: (1) Capture Inquiry &rarr; (2) Call & WhatsApp &rarr; (3) Advance Milestones &rarr; (4) Invoicing & Fee Collection. All staff members follow this standardized pathway.',
        tip: 'Click on any step card anytime to jump straight into the corresponding operational surface.',
        position: 'bottom'
      },
      {
        target: '#widget-action-calls',
        fallback: '#widget-metric-cards',
        title: "Today's Action Call List & 1-Click Outreach",
        badge: 'Daily Consultation Queue',
        icon: 'fa-phone-volume',
        description: 'Active candidates scheduled for consultation today across the company. Features 1-click WhatsApp Web messaging, direct phone dialing, and instant consultation outcome logging.',
        tip: 'Staff must log the consultation outcome immediately after each call to maintain compliance.',
        position: 'top'
      },
      {
        target: '#widget-metric-cards',
        fallback: '.content-area',
        title: 'Executive Institutional KPI Cards',
        badge: 'Executive Analytics',
        icon: 'fa-chart-line',
        description: 'Live institutional performance metrics tracking Active Student Leads, Invoiced Clients (with conversion rate percentage), Total Collected Revenue, and Outstanding Receivables across all campuses.',
        tip: 'Values are computed directly from the MySQL database and update instantly upon payment receipt.',
        position: 'top'
      },
      {
        target: '#widget-task-queues',
        fallback: '.content-area',
        title: 'Daily Operational Task Queues & SLA Tracking',
        badge: 'SLA Enforcement',
        icon: 'fa-list-check',
        description: 'Four prioritized task queues: Today\'s Scheduled Calls, Overdue Calls (alerting delayed inquiries), Fresh Inbound Leads, and Uncontacted Candidates. Guarantees 0% lead abandonment.',
        tip: 'Counselors with red overdue badges should be prompted to complete their pending calls.',
        position: 'top'
      },
      {
        target: '#widget-pipeline-chart',
        fallback: '#widget-recent-leads',
        title: '6-Stage Admission Pipeline Funnel',
        badge: 'Lifecycle Milestones',
        icon: 'fa-diagram-project',
        description: 'Visual analytics tracking student distribution across the 6 official admission milestones: ENQUIRY &rarr; CUSTOMER &rarr; APPLICATION &rarr; OFFER RECEIVED &rarr; TUITION FEES PAID &rarr; ENROLLED.',
        tip: 'Click "Kanban &rarr;" to interact with the visual drag-and-drop card board.',
        position: 'top'
      },
      {
        target: '#sidebar-nav-leads',
        fallback: '#sidebar-nav-dashboard',
        title: 'Central Student Leads Repository',
        badge: 'Admissions Workspace',
        icon: 'fa-user-graduate',
        description: 'Access the complete student database. Search, filter by branch or counselor, view complete academic dossiers, track IELTS/PTE scores, and export authorized reports.',
        tip: 'Counselors only see their directly assigned students; Super Admin maintains complete global visibility.',
        position: 'right'
      },
      {
        target: '#sidebar-group-inbound',
        fallback: '#sidebar-nav-leads',
        title: 'Digital Web Leads & College Pools',
        badge: 'Lead Inbound & Triage',
        icon: 'fa-bullseye',
        description: 'Triage digital prospective inquiries captured from Meta, Google, and Instagram Ads. Ingest bulk campus fair rosters (.xlsx/.csv) with automatic duplicate phone filtering.',
        tip: 'Bulk convert qualified fair attendees into active student inquiries with 1 click.',
        position: 'right'
      },
      {
        target: '#sidebar-group-finance',
        fallback: '#sidebar-nav-customers',
        title: 'Operations: Finance, Invoices & Dossiers',
        badge: 'Operations Suite',
        icon: 'fa-file-invoice-dollar',
        description: 'Review invoice billings, payment receipts, and balance reports. Access the Document Hub to inspect uploaded passports, academic transcripts, and official visa requirements.',
        tip: 'Issue PDF receipts to candidates and families directly from the billing surface.',
        position: 'right'
      },
      {
        target: '#sidebar-nav-roles',
        fallback: '#sidebar-nav-staff',
        title: 'Staff, Roles & Permissions Governance',
        badge: 'Enterprise Administration',
        icon: 'fa-shield-halved',
        description: 'Manage staff credentials, control granular permissions (View, Add, Edit, Delete, Assign, Approve, Export) across all 16 modules in the Roles & Permissions matrix, and review audit logs.',
        tip: 'All permission changes take effect immediately at the PHP backend security level.',
        position: 'right'
      }
    ],

    // 2. ADMISSIONS MANAGER (10 Steps)
    manager: [
      {
        target: '#top-navbar',
        fallback: '.page-header',
        title: 'Admissions Operations Command Center',
        badge: 'Admissions Manager • Operations',
        icon: 'fa-building-user',
        description: 'Welcome, Admissions Manager! You oversee branch counselors, student caseload distribution, inbound triage, and conversion rates. This walkthrough covers every management tool at your disposal.',
        tip: 'You can restart this walkthrough anytime using the Guide button in the top navigation bar.',
        position: 'bottom'
      },
      {
        target: '#btn-quick-add-lead',
        fallback: '.page-header',
        title: 'Quick Lead Capture & Counselor Allocation',
        badge: 'Immediate Registration',
        icon: 'fa-user-plus',
        description: 'Quickly capture prospective student walk-ins or telephone inquiries at the branch desk, and assign them directly to one of your counselors in 15 seconds.',
        tip: 'Assigned counselors receive instant notifications in their task queues.',
        position: 'bottom'
      },
      {
        target: '#widget-unassigned-leads',
        fallback: '#widget-4step-workflow',
        title: 'Fresh Inbound Lead Triage Station',
        badge: 'Digital Lead Allocation',
        icon: 'fa-bullhorn',
        description: 'Digital leads from Meta Ads, Instagram campaigns, and Google Ads arrive here in real time. Inspect candidate study preferences and allocate to counselors based on country expertise.',
        tip: 'Rapid lead triage (<15 minutes) dramatically improves candidate conversion rates.',
        position: 'bottom'
      },
      {
        target: '#widget-action-calls',
        fallback: '#widget-metric-cards',
        title: "Today's Action Call List Oversight",
        badge: 'Team Call Supervision',
        icon: 'fa-phone-volume',
        description: 'Supervise all candidate consultation calls scheduled for today. Verify that your counselors are reaching out promptly via direct calls and 1-click WhatsApp Web.',
        tip: 'Counselors log discussion outcomes here, creating permanent audit notes.',
        position: 'top'
      },
      {
        target: '#widget-4step-workflow',
        fallback: '.content-area',
        title: '4-Step Daily Admissions Workflow',
        badge: 'Team Standardization',
        icon: 'fa-route',
        description: 'Ensure your squad follows the standardized process: Inquiry Capture &rarr; Consultation Call &rarr; Milestone Advancement &rarr; Invoicing & Fee Documentation.',
        tip: 'Click any step card to open the corresponding operational workspace.',
        position: 'bottom'
      },
      {
        target: '#widget-metric-cards',
        fallback: '.content-area',
        title: 'Branch Conversion & Financial KPIs',
        badge: 'Branch Metrics',
        icon: 'fa-chart-pie',
        description: 'Real-time visibility into branch Active Leads, Invoiced Customer conversions, Total Collected Revenue, and Outstanding Fee Balances.',
        tip: 'Monitor fee collection rates to ensure smooth applicant university remittances.',
        position: 'top'
      },
      {
        target: '#widget-task-queues',
        fallback: '.content-area',
        title: 'Follow-Up SLA Enforcement & Overdue Alerting',
        badge: 'SLA Tracking',
        icon: 'fa-list-check',
        description: 'Four live queues: Today\'s Calls, Overdue Calls (>24 hours delay), Fresh Leads, and Uncontacted Inquiries. Maintain zero overdue calls to maximize student engagement.',
        tip: 'Click any queue card to filter tasks by counselor and clear overdue items.',
        position: 'top'
      },
      {
        target: '#sidebar-nav-leads',
        fallback: '#sidebar-nav-dashboard',
        title: 'Student Leads & Bulk Reassignment',
        badge: 'Caseload Management',
        icon: 'fa-user-graduate',
        description: 'View all branch student records. Use the Bulk Reassign tool to rebalance caseloads when a counselor is on leave or reassigning territories.',
        tip: 'Select multiple candidates and click Bulk Reassign at the top of the table.',
        position: 'right'
      },
      {
        target: '#sidebar-group-inbound',
        fallback: '#sidebar-nav-leads',
        title: 'Bulk College Lists & Campus Expo Ingestion',
        badge: 'Lead Inbound',
        icon: 'fa-bullseye',
        description: 'Upload CSV or Excel contact rosters gathered at university expos and campus fairs. Built-in phone normalization prevents duplicate records automatically.',
        tip: 'Convert qualified fair attendees into active student inquiries with 1 click.',
        position: 'right'
      },
      {
        target: '#sidebar-nav-staff',
        fallback: '#sidebar-nav-leads',
        title: 'Counselor Productivity & Team Oversight',
        badge: 'Staff Administration',
        icon: 'fa-users',
        description: 'Monitor individual counselor activity, call counts, pending tasks, and conversion rates to keep your admissions squad operating at peak productivity.',
        tip: 'Generate official consultancy invoices under Finance & Invoices for your enrolled applicants.',
        position: 'right'
      }
    ],

    // 3. ADMISSIONS COUNSELOR & ADVISOR (8 Steps)
    counselor: [
      {
        target: '.page-header',
        fallback: '#top-navbar',
        title: 'Daily Counseling & Consultation Cockpit',
        badge: 'Admissions Counselor • Student Guidance',
        icon: 'fa-headset',
        description: 'Welcome, Counselor! Your daily workspace is optimized for consulting prospective students, organizing phone calls, and advancing candidates toward university acceptance.',
        tip: 'Click the Guide button anytime to re-open this walkthrough or access the Counselor Playbook.',
        position: 'bottom'
      },
      {
        target: '#btn-quick-add-lead',
        fallback: '.page-header',
        title: 'Fast 15-Second Lead Capture',
        badge: 'Call & Walk-In Intake',
        icon: 'fa-user-plus',
        description: 'Quickly capture prospective student calls or walk-ins in 15 seconds. Automatically links the candidate to your caseload and schedules your first counseling session.',
        tip: 'Fill candidate name, mobile, target country, and preferred intake term.',
        position: 'bottom'
      },
      {
        target: '#widget-action-calls',
        fallback: '#widget-4step-workflow',
        title: 'Your Priority Daily Action Call List',
        badge: 'Daily Priority Queue',
        icon: 'fa-phone-volume',
        description: 'Your most critical daily tool! Lists candidates scheduled for consultation today. Any overdue calls appear first in red so you can attend to delayed inquiries first.',
        tip: 'Work through this list every morning to maintain 100% follow-up compliance.',
        position: 'top'
      },
      {
        target: 'table.data-table tbody tr:first-child td:nth-child(2)',
        fallback: '#widget-action-calls',
        title: '1-Click WhatsApp & Direct Phone Calling',
        badge: 'Instant Outreach',
        icon: 'fa-brands fa-whatsapp',
        description: 'Click the green WhatsApp icon to launch WhatsApp Web with a direct pre-filled chat with the candidate. Click the phone number to start a direct telephone call.',
        tip: 'WhatsApp follow-ups achieve significantly higher response rates than email.',
        position: 'top'
      },
      {
        target: 'table.data-table tbody tr:first-child button',
        fallback: '#widget-action-calls',
        title: 'Mandatory: Log Consultation Outcome Immediately',
        badge: 'Call Logging',
        icon: 'fa-check',
        description: 'Immediately after every conversation, click Log Outcome. Record what was discussed, select the interaction title, and set the next follow-up date (Tomorrow, +2 Days, Next Week).',
        tip: 'Never finish a call without logging notes and setting the next follow-up date.',
        position: 'top'
      },
      {
        target: '#widget-4step-workflow',
        fallback: '.content-area',
        title: 'Standard 4-Step Admissions Workflow',
        badge: 'Daily Routine',
        icon: 'fa-route',
        description: 'The 4 daily phases: (1) Capture Inquiry &rarr; (2) Call & WhatsApp &rarr; (3) Advance Milestones &rarr; (4) Fee Documentation. Click any step anytime to navigate directly.',
        tip: 'Advance milestones as soon as a student submits documents or receives an offer letter.',
        position: 'bottom'
      },
      {
        target: '#sidebar-nav-pipeline',
        fallback: '#sidebar-nav-leads',
        title: '6-Stage Admission Pipeline Kanban',
        badge: 'Milestone Progression',
        icon: 'fa-diagram-project',
        description: 'Move your students across stages: ENQUIRY &rarr; CUSTOMER (engaged) &rarr; APPLICATION (submitted) &rarr; OFFER RECEIVED &rarr; TUITION FEES PAID &rarr; ENROLLED.',
        tip: 'Drag candidate cards or click Advance Stage inside student dossiers.',
        position: 'right'
      },
      {
        target: '#sidebar-group-docs',
        fallback: '#sidebar-nav-tasks',
        title: 'Document Hub & Country Guidelines',
        badge: 'Student Dossiers',
        icon: 'fa-folder-open',
        description: 'Upload passports, academic transcripts, and SOPs directly into candidate dossiers. Access official visa checklists for UK, Canada, USA, Australia, and Europe.',
        tip: 'Ensure all required documents are verified before advancing to the APPLICATION stage.',
        position: 'right'
      }
    ]
  };

  // State Management
  let activeRoleKey = 'counselor';
  let activeSteps = [];
  let currentStepIndex = 0;
  let isTourActive = false;
  let currentHighlightedElement = null;

  // DOM Elements cache
  let overlayEl = null;
  let spotlightEl = null;
  let popoverEl = null;
  let completionModalEl = null;

  /**
   * Determine user role key
   */
  function detectRoleKey() {
    const user = window.CRM_CURRENT_USER || {};
    if (user.is_super_admin || user.role_name === 'super_admin' || user.role_id === 1) {
      return 'super_admin';
    }
    if (user.is_branch_manager || user.role_name === 'manager' || user.role_id === 2) {
      return 'manager';
    }
    return 'counselor';
  }

  /**
   * Create or retrieve tutorial DOM elements
   */
  function ensureTutorialDOM() {
    if (!overlayEl) {
      overlayEl = document.getElementById('tutorial-overlay');
      if (!overlayEl) {
        overlayEl = document.createElement('div');
        overlayEl.id = 'tutorial-overlay';
        document.body.appendChild(overlayEl);
      }
    }

    if (!spotlightEl) {
      spotlightEl = document.getElementById('tutorial-spotlight');
      if (!spotlightEl) {
        spotlightEl = document.createElement('div');
        spotlightEl.id = 'tutorial-spotlight';
        document.body.appendChild(spotlightEl);
      }
    }

    if (!popoverEl) {
      popoverEl = document.getElementById('tutorial-popover');
      if (!popoverEl) {
        popoverEl = document.createElement('div');
        popoverEl.id = 'tutorial-popover';
        popoverEl.innerHTML = `
          <div class="tutorial-card-header">
            <span class="tutorial-role-badge" id="tut-badge">Super Admin</span>
            <span class="tutorial-step-counter" id="tut-counter">Step 1 of 10</span>
            <button type="button" class="tutorial-close-btn" onclick="window.exitTutorial()" title="Exit Tour">&times;</button>
          </div>
          <div class="tutorial-progress-bar">
            <div class="tutorial-progress-fill" id="tut-progress-fill"></div>
          </div>
          <div class="tutorial-card-body">
            <div class="tutorial-title-row">
              <div class="tutorial-icon-box" id="tut-icon-box"><i class="fa-solid fa-crown" id="tut-icon"></i></div>
              <h4 class="tutorial-step-title" id="tut-title">Step Title</h4>
            </div>
            <p class="tutorial-step-description" id="tut-desc">Step description details here.</p>
            <div class="tutorial-tip-box" id="tut-tip-box">
              <i class="fa-solid fa-lightbulb"></i>
              <span id="tut-tip-text">Helpful action tip</span>
            </div>
          </div>
          <div class="tutorial-card-footer">
            <button type="button" class="tutorial-skip-btn" onclick="window.exitTutorial()">Skip Tour</button>
            <div class="tutorial-nav-buttons">
              <button type="button" class="tutorial-btn-prev" id="tut-btn-prev" onclick="window.prevTutorialStep()">
                <i class="fa-solid fa-arrow-left"></i> Previous
              </button>
              <button type="button" class="tutorial-btn-next" id="tut-btn-next" onclick="window.nextTutorialStep()">
                Next <i class="fa-solid fa-arrow-right"></i>
              </button>
            </div>
          </div>
        `;
        document.body.appendChild(popoverEl);
      }
    }

    if (!completionModalEl) {
      completionModalEl = document.getElementById('tutorial-complete-modal');
      if (!completionModalEl) {
        completionModalEl = document.createElement('div');
        completionModalEl.id = 'tutorial-complete-modal';
        completionModalEl.innerHTML = `
          <div class="tutorial-complete-dialog">
            <div style="padding: 2.25rem 2rem 1.75rem; background: linear-gradient(135deg, #7008E7 0%, #4a049e 100%); color: #fff;">
              <div style="width: 64px; height: 64px; border-radius: 50%; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 1rem; color: #fbbf24; box-shadow: 0 8px 20px rgba(0,0,0,0.2);">
                <i class="fa-solid fa-award"></i>
              </div>
              <h2 style="font-size: 1.5rem; font-weight: 800; margin: 0 0 0.4rem; color: #fff;">Tutorial Completed!</h2>
              <p style="font-size: 0.88rem; color: rgba(255,255,255,0.85); margin: 0; line-height: 1.5;" id="tut-complete-role-msg">
                You have successfully mastered the operational workflow for your role.
              </p>
            </div>
            <div style="padding: 1.75rem 2rem; text-align: left; background: #fff;">
              <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--text-heading); margin: 0 0 0.85rem;">
                <i class="fa-solid fa-circle-check" style="color: #10b981; margin-right: 6px;"></i> Key Takeaways for High Productivity:
              </h4>
              <div style="background: #F8FAFC; border: 1px solid var(--border-color); border-radius: 12px; padding: 1rem; font-size: 0.825rem; color: var(--text-main); margin-bottom: 1.5rem; line-height: 1.6;" id="tut-complete-checklist">
                <!-- Injected dynamically -->
              </div>
              <div style="display: flex; gap: 0.65rem; justify-content: flex-end; flex-wrap: wrap;">
                <button type="button" class="btn btn-outline" onclick="window.restartTutorial()" style="font-weight: 700; border-radius: 8px;">
                  <i class="fa-solid fa-rotate-right"></i> Restart Tutorial
                </button>
                <button type="button" class="btn btn-secondary" onclick="window.exitTutorial(true)" style="font-weight: 700; border-radius: 8px;">
                  <i class="fa-solid fa-book-open"></i> Open Written Manual
                </button>
                <button type="button" class="btn btn-primary" onclick="window.exitTutorial()" style="font-weight: 800; border-radius: 8px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none;">
                  <i class="fa-solid fa-check"></i> Done, Back to Cockpit
                </button>
              </div>
            </div>
          </div>
        `;
        document.body.appendChild(completionModalEl);
      }
    }
  }

  /**
   * Start Interactive Tutorial
   */
  window.startInteractiveTutorial = function(forcedRoleKey) {
    // If not currently on dashboard, redirect to dashboard with ?start_tour=1
    const path = window.location.pathname.toLowerCase();
    const isDashboard = path.includes('/dashboard/') || path.endsWith('/dashboard') || path.endsWith('dashboard/index.php');

    if (!isDashboard) {
      const baseUrl = window.CRM_BASE_URL || '';
      window.location.href = baseUrl + '/dashboard/index.php?start_tour=1';
      return;
    }

    // Close any other open modals
    if (typeof closeModal === 'function') {
      ['quickLeadModal', 'globalLogCallModal', 'advanceStageModal', 'userManualModal'].forEach(id => closeModal(id));
    }

    ensureTutorialDOM();

    activeRoleKey = forcedRoleKey || detectRoleKey();
    activeSteps = TUTORIAL_ROLES[activeRoleKey] || TUTORIAL_ROLES.counselor;
    currentStepIndex = 0;
    isTourActive = true;

    // Show overlay
    overlayEl.classList.add('active');
    spotlightEl.style.display = 'block';
    popoverEl.style.display = 'flex';
    completionModalEl.classList.remove('active');

    renderStep(0);
  };

  /**
   * Render specific step
   */
  function renderStep(index) {
    if (index < 0 || index >= activeSteps.length) {
      if (index >= activeSteps.length) {
        showCompletion();
      }
      return;
    }

    currentStepIndex = index;
    const step = activeSteps[index];

    // Find target element
    let targetEl = document.querySelector(step.target);
    if (!targetEl && step.fallback) {
      targetEl = document.querySelector(step.fallback);
    }
    if (!targetEl) {
      targetEl = document.querySelector('.page-header') || document.body;
    }

    // If target is inside a collapsed sidebar nav-group, automatically expand it!
    const parentNavGroup = targetEl.closest('.nav-group');
    if (parentNavGroup && !parentNavGroup.classList.contains('nav-group-open')) {
      parentNavGroup.classList.add('nav-group-open');
    }

    // If sidebar is collapsed on desktop, un-collapse it so the menu item is visible
    const appLayout = document.getElementById('app-layout');
    if (targetEl.closest('#sidebar') && appLayout && appLayout.classList.contains('sidebar-collapsed')) {
      appLayout.classList.remove('sidebar-collapsed');
    }

    // Remove highlight from previous element
    if (currentHighlightedElement && currentHighlightedElement !== targetEl) {
      currentHighlightedElement.classList.remove('tutorial-highlighted-element');
    }
    currentHighlightedElement = targetEl;
    targetEl.classList.add('tutorial-highlighted-element');

    // Smooth scroll to target element
    targetEl.scrollIntoView({ behavior: 'smooth', block: 'center' });

    // Update Popover Content
    const roleBadgeEl = document.getElementById('tut-badge');
    const roleLabel = activeRoleKey === 'super_admin' ? 'Super Admin' : (activeRoleKey === 'manager' ? 'Admissions Manager' : 'Counselor');
    roleBadgeEl.textContent = step.badge || roleLabel;
    roleBadgeEl.className = 'tutorial-role-badge badge-' + activeRoleKey;

    document.getElementById('tut-counter').textContent = `Step ${index + 1} of ${activeSteps.length}`;
    
    // Progress bar width
    const pct = Math.round(((index + 1) / activeSteps.length) * 100);
    document.getElementById('tut-progress-fill').style.width = pct + '%';

    // Title & Icon
    document.getElementById('tut-title').textContent = step.title;
    const iconEl = document.getElementById('tut-icon');
    iconEl.className = 'fa-solid ' + (step.icon || 'fa-info');

    // Description & Tip
    document.getElementById('tut-desc').innerHTML = step.description;
    document.getElementById('tut-tip-text').innerHTML = step.tip || 'Follow standard operating procedures.';

    // Buttons
    const prevBtn = document.getElementById('tut-btn-prev');
    prevBtn.disabled = (index === 0);
    prevBtn.style.opacity = (index === 0) ? '0.4' : '1';

    const nextBtn = document.getElementById('tut-btn-next');
    if (index === activeSteps.length - 1) {
      nextBtn.innerHTML = 'Finish Tour <i class="fa-solid fa-check"></i>';
      nextBtn.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
    } else {
      nextBtn.innerHTML = 'Next <i class="fa-solid fa-arrow-right"></i>';
      nextBtn.style.background = 'linear-gradient(135deg, var(--primary) 0%, #7008E7 100%)';
    }

    // Position spotlight & popover with a short delay to account for smooth scroll & layout
    setTimeout(() => {
      positionSpotlight(targetEl);
      positionPopover(targetEl, step.position || 'bottom');
      popoverEl.classList.add('visible');
    }, 150);
  }

  /**
   * Position Spotlight cutout frame exactly over target element
   */
  function positionSpotlight(targetEl) {
    if (!spotlightEl || !targetEl) return;
    const rect = targetEl.getBoundingClientRect();
    const pad = 6;

    const top = Math.max(0, rect.top - pad);
    const left = Math.max(0, rect.left - pad);
    const width = rect.width + (pad * 2);
    const height = rect.height + (pad * 2);

    spotlightEl.style.top = top + 'px';
    spotlightEl.style.left = left + 'px';
    spotlightEl.style.width = width + 'px';
    spotlightEl.style.height = height + 'px';
    spotlightEl.style.display = 'block';
  }

  /**
   * Smart position Popover relative to target element
   */
  function positionPopover(targetEl, preferredPos) {
    if (!popoverEl || !targetEl) return;
    const rect = targetEl.getBoundingClientRect();
    const popoverWidth = Math.min(420, window.innerWidth - 32);
    const popoverHeight = popoverEl.offsetHeight || 290;
    const margin = 14;

    let top = 0;
    let left = 0;

    // Mobile / small viewport: dock to bottom
    if (window.innerWidth < 768) {
      top = window.innerHeight - popoverHeight - 16;
      left = (window.innerWidth - popoverWidth) / 2;
      popoverEl.style.top = Math.max(16, top) + 'px';
      popoverEl.style.left = Math.max(16, left) + 'px';
      popoverEl.style.width = popoverWidth + 'px';
      return;
    }

    // Sidebar items prefer right position
    if (preferredPos === 'right' && (rect.right + margin + popoverWidth < window.innerWidth)) {
      left = rect.right + margin;
      top = Math.max(16, Math.min(window.innerHeight - popoverHeight - 16, rect.top + (rect.height / 2) - (popoverHeight / 2)));
    } else if (preferredPos === 'top' && (rect.top - margin - popoverHeight > 16)) {
      top = rect.top - margin - popoverHeight;
      left = Math.max(16, Math.min(window.innerWidth - popoverWidth - 16, rect.left + (rect.width / 2) - (popoverWidth / 2)));
    } else {
      // Default: below target if fits, otherwise above
      if (rect.bottom + margin + popoverHeight <= window.innerHeight - 16) {
        top = rect.bottom + margin;
        left = Math.max(16, Math.min(window.innerWidth - popoverWidth - 16, rect.left + (rect.width / 2) - (popoverWidth / 2)));
      } else {
        top = Math.max(16, rect.top - margin - popoverHeight);
        left = Math.max(16, Math.min(window.innerWidth - popoverWidth - 16, rect.left + (rect.width / 2) - (popoverWidth / 2)));
      }
    }

    popoverEl.style.top = top + 'px';
    popoverEl.style.left = left + 'px';
    popoverEl.style.width = popoverWidth + 'px';
  }

  /**
   * Navigation Handlers
   */
  window.nextTutorialStep = function() {
    renderStep(currentStepIndex + 1);
  };

  window.prevTutorialStep = function() {
    renderStep(currentStepIndex - 1);
  };

  window.restartTutorial = function() {
    if (completionModalEl) completionModalEl.classList.remove('active');
    window.startInteractiveTutorial(activeRoleKey);
  };

  window.exitTutorial = function(openManualAfter) {
    isTourActive = false;
    if (currentHighlightedElement) {
      currentHighlightedElement.classList.remove('tutorial-highlighted-element');
      currentHighlightedElement = null;
    }
    if (overlayEl) overlayEl.classList.remove('active');
    if (spotlightEl) spotlightEl.style.display = 'none';
    if (popoverEl) {
      popoverEl.classList.remove('visible');
      popoverEl.style.display = 'none';
    }
    if (completionModalEl) completionModalEl.classList.remove('active');

    if (openManualAfter && typeof openUserManualModal === 'function') {
      openUserManualModal();
    }
  };

  /**
   * Display Completion Dialog
   */
  function showCompletion() {
    isTourActive = false;
    if (currentHighlightedElement) {
      currentHighlightedElement.classList.remove('tutorial-highlighted-element');
      currentHighlightedElement = null;
    }
    if (spotlightEl) spotlightEl.style.display = 'none';
    if (popoverEl) {
      popoverEl.classList.remove('visible');
      popoverEl.style.display = 'none';
    }

    const msgEl = document.getElementById('tut-complete-role-msg');
    const checklistEl = document.getElementById('tut-complete-checklist');

    if (activeRoleKey === 'super_admin') {
      msgEl.textContent = "You're now fully prepared to govern user roles, institutional branches, lead triage, and billing ledgers.";
      checklistEl.innerHTML = `
        <div style="margin-bottom:6px;"><strong>1. User Provisioning:</strong> Add and configure branch staff under <em>Staff & Team</em>.</div>
        <div style="margin-bottom:6px;"><strong>2. RBAC Enforcement:</strong> Control module switches under <em>Roles & Permissions</em>.</div>
        <div><strong>3. Financial Ledgers:</strong> Monitor receivables and revenue under <em>Finance & Invoices</em>.</div>
      `;
    } else if (activeRoleKey === 'manager') {
      msgEl.textContent = "You're all set to manage counselor workloads, triage fresh digital inbound leads, and supervise daily follow-ups.";
      checklistEl.innerHTML = `
        <div style="margin-bottom:6px;"><strong>1. Morning Triage:</strong> Allocate new digital leads under <em>Lead Inbound & Triage</em>.</div>
        <div style="margin-bottom:6px;"><strong>2. SLA Tracking:</strong> Maintain zero overdue calls in <em>Daily Operational Task Queues</em>.</div>
        <div><strong>3. Caseload Balance:</strong> Use <em>Bulk Reassign</em> on Student Leads whenever needed.</div>
      `;
    } else {
      msgEl.textContent = "You're all set to consult students, log outcomes on time, and advance applicants through the 6 admission milestones.";
      checklistEl.innerHTML = `
        <div style="margin-bottom:6px;"><strong>1. Review Calls:</strong> Start your day with <em>Today's Action Call List</em>.</div>
        <div style="margin-bottom:6px;"><strong>2. 1-Click Outreach:</strong> Use the green WhatsApp icon to message candidates instantly.</div>
        <div><strong>3. Mandatory Log Call:</strong> Record notes and schedule the next follow-up date after every conversation.</div>
      `;
    }

    if (completionModalEl) completionModalEl.classList.add('active');
  }

  /**
   * Listen to resize & scroll to keep highlight aligned
   */
  let repositionTimeout = null;
  function handleReposition() {
    if (!isTourActive || !currentHighlightedElement) return;
    if (repositionTimeout) cancelAnimationFrame(repositionTimeout);
    repositionTimeout = requestAnimationFrame(() => {
      positionSpotlight(currentHighlightedElement);
      const step = activeSteps[currentStepIndex];
      positionPopover(currentHighlightedElement, step ? step.position : 'bottom');
    });
  }

  window.addEventListener('resize', handleReposition, { passive: true });
  window.addEventListener('scroll', handleReposition, { passive: true });

  /**
   * Keyboard shortcuts for tour navigation
   */
  document.addEventListener('keydown', (e) => {
    if (!isTourActive) return;
    if (e.key === 'ArrowRight' || e.key === 'Enter') {
      // Don't intercept if inside input or textarea
      if (['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName)) return;
      e.preventDefault();
      window.nextTutorialStep();
    } else if (e.key === 'ArrowLeft') {
      if (['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName)) return;
      e.preventDefault();
      window.prevTutorialStep();
    } else if (e.key === 'Escape') {
      e.preventDefault();
      window.exitTutorial();
    }
  });

  /**
   * Auto-start tour if requested via URL param: ?start_tour=1
   */
  document.addEventListener('DOMContentLoaded', () => {
    if (window.location.search.includes('start_tour=1')) {
      // Clean query parameter from URL without page reload
      try {
        const cleanUrl = window.location.pathname;
        window.history.replaceState({}, '', cleanUrl);
      } catch(e) {}

      setTimeout(() => {
        window.startInteractiveTutorial();
      }, 400);
    }
  });

})();
