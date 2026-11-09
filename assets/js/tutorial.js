/**
 * UniCRM Interactive Guided Training System
 * Complete, End-to-End, Role-Specific Interactive Tutorial covering the ENTIRE Website & ALL Roles
 * Features: Multi-Page Auto-Navigation, Deep Module-by-Module Explanations, Pause/Resume, Progress Tracking
 * 100% Vanilla JavaScript - Zero External Dependencies
 */
(function() {
  'use strict';

  // Persistence Key
  const STORAGE_KEY = 'unicrm_tutorial_state';

  // Helper: Application Base URL Resolution
  function getAppUrl(path) {
    const rawBase = (window.CRM_BASE_URL || '').trim();
    const cleanBase = rawBase.replace(/\/+$/, '');
    const cleanPath = (path || '').replace(/^\/+/, '');
    if (!cleanBase || cleanBase === '.') {
      return '/' + cleanPath;
    }
    return cleanBase + '/' + cleanPath;
  }

  // Helper: Check if current page matches target URL path
  function isSamePage(targetUrlPath) {
    if (!targetUrlPath) return true;
    
    // Create temporary anchors to parse paths cleanly
    const currentA = document.createElement('a');
    currentA.href = window.location.href;

    const targetA = document.createElement('a');
    targetA.href = getAppUrl(targetUrlPath);

    // Normalize paths: remove trailing slash, lowercase, remove /index.php
    const cleanCurrent = currentA.pathname.toLowerCase().replace(/\/index\.php$/, '').replace(/\/+$/, '');
    const cleanTarget = targetA.pathname.toLowerCase().replace(/\/index\.php$/, '').replace(/\/+$/, '');

    // Check pathname equality
    if (cleanCurrent !== cleanTarget) {
      return false;
    }

    // If target URL has specific query parameters, check that they match
    if (targetA.search) {
      const targetParams = new URLSearchParams(targetA.search);
      const currentParams = new URLSearchParams(currentA.search);
      for (const [key, val] of targetParams.entries()) {
        if (currentParams.get(key) !== val) {
          return false;
        }
      }
    }

    return true;
  }

  // Helper: Load & Save Tutorial State in localStorage
  function loadTutorialState() {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      if (!raw) return null;
      return JSON.parse(raw);
    } catch (e) {
      return null;
    }
  }

  function saveTutorialState(state) {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
    } catch (e) {}
  }

  function clearTutorialState() {
    try {
      localStorage.removeItem(STORAGE_KEY);
    } catch (e) {}
  }

  // =========================================================================
  // EXHAUSTIVE ROLE-SPECIFIC WALKTHROUGH MANIFESTS
  // =========================================================================
  const TUTORIAL_ROLES = {
    // -----------------------------------------------------------------------
    // 1. SUPER ADMIN (Comprehensive 57-Step Walkthrough across ALL 16 Modules)
    // -----------------------------------------------------------------------
    super_admin: [
      // === MODULE 1: ADMISSIONS COCKPIT ===
      {
        url: '/dashboard/index.php',
        target: '#top-navbar',
        fallback: '.navbar-right',
        module: 'Admissions Cockpit',
        section: 'Global Command Header',
        title: 'Institutional Cockpit & System Administration',
        badge: 'Super Admin • Global Authority',
        icon: 'fa-crown',
        description: 'Welcome to your master training walkthrough! As Super Admin, you govern all 16 modules across all university branches. From this top bar, you have access to the Role Switcher (simulate any role with 1 click), real-time alerts, and interactive training anytime.',
        tip: 'Click the Guide button anytime to refresh your memory on workflows or restart the tour.',
        position: 'bottom'
      },
      {
        url: '/dashboard/index.php',
        target: '#role-switcher-btn',
        fallback: '#top-navbar',
        module: 'Admissions Cockpit',
        section: 'Persona Simulation',
        title: 'Instant Role Switcher Dropdown',
        badge: 'Role Simulation',
        icon: 'fa-shuffle',
        description: 'Test and audit system behavior as different personas with a single click: Dr. Arthur Vance (Super Admin), Marcus Sterling (Manager), David Miller (Counselor), or Sarah Jenkins (Advisor). Validates role permissions and caseload privacy in real time.',
        tip: 'Switch to Admissions Counselor anytime to verify what assigned staff see on their screens.',
        position: 'bottom'
      },
      {
        url: '/dashboard/index.php',
        target: '#btn-quick-add-lead',
        fallback: '.page-header',
        module: 'Admissions Cockpit',
        section: 'Rapid Lead Capture',
        title: '15-Second Quick Lead Capture',
        badge: 'Instant Ingestion',
        icon: 'fa-bolt',
        description: 'Rapidly intake walk-in students or phone inquiries without leaving the screen. Automatically generates a unique tracking code (EDR-YYYY-XXXX), normalizes phone numbers to prevent duplicates, and routes the student directly to the assigned counselor.',
        tip: 'Enter candidate name, phone number, study country, and next follow-up in under 15 seconds.',
        position: 'bottom'
      },
      {
        url: '/dashboard/index.php',
        target: '#btn-generate-invoice',
        fallback: '#widget-4step-workflow',
        module: 'Admissions Cockpit',
        section: 'Financial Quick Action',
        title: 'Instant Service Invoicing Button',
        badge: 'Financial Governance',
        icon: 'fa-file-invoice-dollar',
        description: 'Generate official institutional consultancy service invoices, application fees, or visa handling charges. Connects directly to the student ledger with automatic 18% GST calculation and downloadable client PDF receipts.',
        tip: 'Click here whenever an enrolled applicant makes a fee payment or requires an institutional bill.',
        position: 'bottom'
      },
      {
        url: '/dashboard/index.php',
        target: '#widget-4step-workflow',
        fallback: '.content-area',
        module: 'Admissions Cockpit',
        section: 'Process Lifecycle',
        title: 'Standard 4-Step Daily Admissions Workflow',
        badge: 'Operational Pipeline',
        icon: 'fa-route',
        description: 'The standardized sequence enforced across all campuses: (1) Capture Inquiry &rarr; (2) Call & WhatsApp &rarr; (3) Advance Milestones &rarr; (4) Invoicing & Fee Documentation. All branch staff members follow this standardized pathway.',
        tip: 'Click any step card anytime to jump straight into the corresponding operational surface.',
        position: 'bottom'
      },
      {
        url: '/dashboard/index.php',
        target: '#widget-action-calls',
        fallback: '#widget-metric-cards',
        module: 'Admissions Cockpit',
        section: 'Daily Priority Queue',
        title: "Today\'s Action Call List & 1-Click Outreach",
        badge: 'Outreach Engine',
        icon: 'fa-phone-volume',
        description: 'Active candidates scheduled for consultation today across the company. Features instant 1-click WhatsApp Web messaging with pre-filled candidate greetings, direct telephone dialing, and mandatory post-call outcome logging.',
        tip: 'Supervise this table to ensure counselors maintain 100% daily follow-up compliance.',
        position: 'top'
      },
      {
        url: '/dashboard/index.php',
        target: '#widget-action-calls a[href*="wa.me"]',
        fallback: '#widget-action-calls',
        module: 'Admissions Cockpit',
        section: 'WhatsApp Outreach',
        title: '1-Click WhatsApp Candidate Messaging',
        badge: 'Candidate Outreach',
        icon: 'fa-brands fa-whatsapp',
        description: 'Click the green WhatsApp icon to launch WhatsApp Web with a personalized pre-filled greeting. Enables counselors to connect with candidates instantly without typing phone numbers.',
        tip: 'WhatsApp follow-ups achieve significantly higher response rates than email consultations.',
        position: 'top'
      },
      {
        url: '/dashboard/index.php',
        target: '#widget-action-calls button',
        fallback: '#widget-action-calls',
        module: 'Admissions Cockpit',
        section: 'Outcome Compliance',
        title: 'Mandatory: Log Consultation Outcome',
        badge: 'Compliance Logging',
        icon: 'fa-check',
        description: 'Immediately after every conversation, click Log Outcome. Record what was discussed, select the interaction title, and set the next follow-up date (Tomorrow, +2 Days, Next Week).',
        tip: 'Logging an outcome automatically clears completed items from the active queue and logs an audit trail.',
        position: 'top'
      },
      {
        url: '/dashboard/index.php',
        target: '#widget-metric-cards',
        fallback: '.content-area',
        module: 'Admissions Cockpit',
        section: 'Executive Analytics',
        title: 'Institutional KPI Cards & Conversion Metrics',
        badge: 'Real-Time Telemetry',
        icon: 'fa-chart-line',
        description: 'Live performance metrics tracking Total Active Student Leads, Invoiced Clients (with conversion percentage rate), Total Collected Revenue, and Outstanding Receivables across all university campuses.',
        tip: 'Revenue and balance figures are computed directly from MySQL ledgers and update instantly upon payment receipt.',
        position: 'top'
      },
      {
        url: '/dashboard/index.php',
        target: '#widget-task-queues',
        fallback: '.content-area',
        module: 'Admissions Cockpit',
        section: 'SLA Task Enforcement',
        title: '4 Daily Operational Task Queues',
        badge: 'Zero-Lead-Abandonment',
        icon: 'fa-list-check',
        description: 'Four prioritized queues: (1) Scheduled Follow-ups (Today\'s Calls), (2) Pending Follow-ups (Overdue Calls), (3) Today Leads (Fresh inquiries assigned today), and (4) Pending Leads (Uncontacted older inquiries). Guarantees zero lead abandonment across the company.',
        tip: 'A red badge in the Overdue queue indicates delayed consultations that require managerial intervention.',
        position: 'top'
      },
      {
        url: '/dashboard/index.php',
        target: '#widget-pipeline-chart',
        fallback: '#widget-recent-leads',
        module: 'Admissions Cockpit',
        section: 'Funnel Analytics',
        title: '6-Stage Admission Pipeline Funnel',
        badge: 'Stage Progression',
        icon: 'fa-diagram-project',
        description: 'Interactive analytics tracking student distribution across the 6 official admission stages: ENQUIRY &rarr; CUSTOMER &rarr; APPLICATION &rarr; OFFER RECEIVED &rarr; TUITION FEES PAID &rarr; ENROLLED.',
        tip: 'Click "Kanban &rarr;" anytime to open the visual drag-and-drop board.',
        position: 'top'
      },
      {
        url: '/dashboard/index.php',
        target: '#widget-branch-chart',
        fallback: '#widget-recent-leads',
        module: 'Admissions Cockpit',
        section: 'Campus Yields',
        title: 'Regional Branch Intake Distribution',
        badge: 'Multi-Campus Telemetry',
        icon: 'fa-chart-pie',
        description: 'Doughnut chart comparing student recruitment yield and revenue collection across regional branches (Main Admissions Division vs Global Advisory Division).',
        tip: 'Identify high-performing regional hubs and reallocate marketing budgets accordingly.',
        position: 'top'
      },
      {
        url: '/dashboard/index.php',
        target: '#widget-recent-leads',
        fallback: '.content-area',
        module: 'Admissions Cockpit',
        section: 'Recent Inquiries',
        title: 'Recent Student Inquiries & Activity Feed',
        badge: 'Live Activity',
        icon: 'fa-clock-rotate-left',
        description: 'Recent student registrations across all divisions with tracking codes, target countries, and assigned counselors. Alongside is the live Audit Feed recording user logins, status modifications, and stage advancements.',
        tip: 'Click "Dossier &rarr;" on any student row to inspect their complete academic profile.',
        position: 'top'
      },

      // === MODULE 2: STUDENT LEADS DIRECTORY ===
      {
        url: '/leads/index.php',
        target: '.page-header',
        fallback: '#main-wrapper',
        module: 'Student Leads',
        section: 'Directory Master',
        title: 'Student Leads Directory & Global Repository',
        badge: 'Central Database',
        icon: 'fa-user-graduate',
        description: 'Welcome to the central Student Leads repository! Here you manage all prospective candidate inquiries across all stages. Notice the action buttons on the top right: Export CSV (strictly restricted from counselors, permitted for administrators) and Bulk Reassign.',
        tip: 'As Super Admin, you have global visibility across all branches and counselor caseloads.',
        position: 'bottom'
      },
      {
        url: '/leads/index.php',
        target: 'div[style*="overflow-x:auto"]',
        fallback: '.page-header',
        module: 'Student Leads',
        section: 'Stage Filter Tabs',
        title: '6 Admission Stage Filter Tabs',
        badge: 'One-Click Filtering',
        icon: 'fa-tags',
        description: 'Instantly filter your entire lead pool by stage. Click "All Stages" for global view, or isolate candidates in Enquiry, Customer, Application, Offer Received, Tuition Fees Paid, or Enrolled with live count badges.',
        tip: 'The numbers inside each pill reflect live database counts matching your current filter scope.',
        position: 'bottom'
      },
      {
        url: '/leads/index.php',
        target: 'input[name="search"]',
        fallback: '.card',
        module: 'Student Leads',
        section: 'Deep Discovery',
        title: 'Universal Candidate Search Bar',
        badge: 'Fast Lookup',
        icon: 'fa-magnifying-glass',
        description: 'Search prospective students in real time by student name, tracking ID (e.g. EDR-2026-0001), phone number, email address, or previous college/university name.',
        tip: 'Type any partial keyword and press Enter or click Apply to execute.',
        position: 'bottom'
      },
      {
        url: '/leads/index.php',
        target: 'select[name="priority"]',
        fallback: '.card form',
        module: 'Student Leads',
        section: 'Priority Filter',
        title: 'Lead Priority Filter Dropdown',
        badge: 'Urgency Triage',
        icon: 'fa-fire',
        description: 'Filter leads by urgency: High Priority (urgent intake), Moderate Priority (standard inquiries), or Low Priority (future term prospects).',
        tip: 'Encourage counselors to attend to High Priority leads before lunch every day.',
        position: 'bottom'
      },
      {
        url: '/leads/index.php',
        target: 'select[name="source"]',
        fallback: '.card form',
        module: 'Student Leads',
        section: 'Acquisition Channels',
        title: 'Lead Acquisition Source Filter',
        badge: 'Marketing Attribution',
        icon: 'fa-bullhorn',
        description: 'Filter candidates by origin: Meta Ads, Google Ads, Website Form, Walk-in, College Fair Data, or Referral. Helps measure marketing campaign performance.',
        tip: 'Combine source filtering with stage filtering to evaluate channel conversion rates.',
        position: 'bottom'
      },
      {
        url: '/leads/index.php',
        target: 'select[name="branch_id"]',
        fallback: '.card form',
        module: 'Student Leads',
        section: 'Branch Scope',
        title: 'Campus & Branch Filter Dropdown',
        badge: 'Campus Filter',
        icon: 'fa-building',
        description: 'Switch between Main Admissions Division, Global Advisory Division, or All Branches. Restricts the directory table to show only students belonging to the selected campus.',
        tip: 'Super Admin can inspect any branch; counselors are strictly restricted to their own.',
        position: 'bottom'
      },
      {
        url: '/leads/index.php',
        target: 'select[name="counselor_id"]',
        fallback: '.card form',
        module: 'Student Leads',
        section: 'Staff Caseloads',
        title: 'Counselor Caseload Selector',
        badge: 'Caseload Audit',
        icon: 'fa-users',
        description: 'Filter student records by individual counselor. The number beside each counselor indicates their active student count, making it easy to identify overloaded staff.',
        tip: 'Select any counselor to review their follow-up compliance and student progression.',
        position: 'bottom'
      },
      {
        url: '/leads/index.php',
        target: 'a[href*="export=csv"]',
        fallback: '.page-header',
        module: 'Student Leads',
        section: 'Data Export',
        title: 'Privileged CSV Data Export Button',
        badge: 'Security Governance',
        icon: 'fa-file-csv',
        description: 'Export authorized student data rosters into formatted CSV spreadsheets. Role permissions strictly block counselors from exporting, ensuring student database privacy.',
        tip: 'Exports respect all active filters applied in the search bar above.',
        position: 'bottom'
      },
      {
        url: '/leads/index.php',
        target: 'button[onclick*="openBulkReassignModal"]',
        fallback: '.page-header',
        module: 'Student Leads',
        section: 'Caseload Balancing',
        title: 'Bulk Reassign Leads Between Staff',
        badge: 'Workload Rebalancing',
        icon: 'fa-users-gear',
        description: 'Rebalance student caseloads when a counselor goes on leave or shifts territories. Select candidate checkboxes in the table and reassign them in bulk with automated notifications.',
        tip: 'Reassigning leads transfers all follow-up tasks and history seamlessly to the new counselor.',
        position: 'bottom'
      },
      {
        url: '/leads/index.php',
        target: 'table.data-table',
        fallback: '.card',
        module: 'Student Leads',
        section: 'Data Grid',
        title: 'Lead Data Table & Action Menu',
        badge: 'Comprehensive Dossier',
        icon: 'fa-table-list',
        description: 'Structured student records displaying tracking Code, Name, Phone (with 1-click WhatsApp dialer), Target Study Program & Country, Stage status, Priority badge, assigned Counselor, and Branch. Click "Dossier &rarr;" on any student to open their complete academic dossier.',
        tip: 'Select checkboxes on the left to perform bulk operations such as reassigning multiple leads at once.',
        position: 'top'
      },

      // === MODULE 3: REGISTER STUDENT LEAD ===
      {
        url: '/leads/add.php',
        target: '.page-header',
        fallback: '#main-wrapper',
        module: 'Register Student Lead',
        section: 'Form Overview',
        title: 'Student Registration Master Interface',
        badge: 'Applicant Intake',
        icon: 'fa-user-plus',
        description: 'Register prospective student leads with complete academic and counseling metadata. Designed for high operational speed: essential contact fields are mandatory, while academic and test scores can be completed now or updated later in the student dossier.',
        tip: 'Click "1-Click Fast Add" in the top right to open the rapid 15-second intake modal anytime.',
        position: 'bottom'
      },
      {
        url: '/leads/add.php',
        target: 'input[name="full_name"]',
        fallback: '.card:first-of-type',
        module: 'Register Student Lead',
        section: 'Essential Details',
        title: 'Candidate Name & Normalized Phone',
        badge: 'Mandatory Fields',
        icon: 'fa-id-card',
        description: 'Enter candidate Full Name, Phone/WhatsApp number, and Email Address. Phone numbers are automatically sanitized and normalized to international format (E.164) with automatic duplicate validation preventing duplicate student entries.',
        tip: 'If a duplicate phone number is detected in active leads, the system blocks insertion with an alert.',
        position: 'bottom'
      },
      {
        url: '/leads/add.php',
        target: 'select[name="preferred_country"]',
        fallback: '.card:first-of-type',
        module: 'Register Student Lead',
        section: 'Study Destination',
        title: 'Target Country & Academic Intake Term',
        badge: 'Study Preferences',
        icon: 'fa-earth-americas',
        description: 'Select the student\'s desired destination country (UK, Canada, Australia, USA, Ireland, Germany, New Zealand) and target intake session (e.g. September 2026, January 2027) to synchronize application deadlines.',
        tip: 'Selecting target country helps managers route the candidate to country-specialist counselors.',
        position: 'bottom'
      },
      {
        url: '/leads/add.php',
        target: 'select[name="branch_id"], select[name="counselor_id"]',
        fallback: '.card:first-of-type',
        module: 'Register Student Lead',
        section: 'Caseload Routing',
        title: 'Branch & Counselor Allocation',
        badge: 'Institutional Hierarchy',
        icon: 'fa-users-gear',
        description: 'Assign the candidate to an operational campus division and designated counselor. The counselor will immediately receive a notification in their task queue, and the lead will appear in their daily action list.',
        tip: 'Super Admin can assign to any branch; managers are scoped to their own campus.',
        position: 'bottom'
      },
      {
        url: '/leads/add.php',
        target: 'details:nth-of-type(1)',
        fallback: '.card:nth-of-type(2)',
        module: 'Register Student Lead',
        section: 'Academic & Tests',
        title: 'Academic History & English Proficiency (IELTS/PTE)',
        badge: 'Qualification Profile',
        icon: 'fa-graduation-cap',
        actionBefore: function() {
          const d = document.querySelector('details:nth-of-type(1)');
          if (d) d.open = true;
        },
        description: 'Expandable section to record student educational history: Highest Qualification, College Name, Passing Year, CGPA/Percentage, Active Backlog counts, Work Experience, and standardized English scores (IELTS, PTE, TOEFL, Duolingo) with individual band breakdowns.',
        tip: 'This section is optional during initial intake and can be filled when the student submits transcripts.',
        position: 'bottom'
      },
      {
        url: '/leads/add.php',
        target: 'textarea[name="notes"]',
        fallback: 'button[type="submit"]',
        module: 'Register Student Lead',
        section: 'Counselor Notes',
        title: 'Initial Counseling Remarks & Budget',
        badge: 'Consultation Notes',
        icon: 'fa-pen-to-square',
        description: 'Record initial counseling remarks: student career aspirations, university preferences, budget constraints, and pending document requirements.',
        tip: 'Initial notes appear on the dashboard action call list to prepare counselors before subsequent calls.',
        position: 'top'
      },
      {
        url: '/leads/add.php',
        target: 'button[type="submit"]',
        fallback: 'form',
        module: 'Register Student Lead',
        section: 'Submission',
        title: 'Register Lead & Trigger Stage History',
        badge: 'Data Persistence',
        icon: 'fa-circle-check',
        description: 'Click "Register Student Lead" to execute a database transaction that: (1) creates the lead record, (2) generates the unique EDR tracking code, (3) logs initial stage history to "ENQUIRY", and (4) notifies the assigned counselor.',
        tip: 'Upon submission, you are automatically redirected to the student\'s complete academic dossier.',
        position: 'top'
      },

      // === MODULE 4: ADMISSION PIPELINE ===
      {
        url: '/admissions/index.php',
        target: '.page-header',
        fallback: '#main-wrapper',
        module: 'Admission Pipeline',
        section: 'Milestone Hub',
        title: '6-Stage Admission Pipeline Hub',
        badge: 'Visual Milestone Tracking',
        icon: 'fa-diagram-project',
        description: 'The visual nerve center of the admissions process. Tracks prospective candidates through the 6 standardized consultancy milestones: 1. ENQUIRY &rarr; 2. CUSTOMER &rarr; 3. APPLICATION &rarr; 4. OFFER RECEIVED &rarr; 5. TUITION FEES PAID &rarr; 6. ENROLLED.',
        tip: 'Use the view mode switcher to toggle between the visual Kanban Board and structured Table View.',
        position: 'bottom'
      },
      {
        url: '/admissions/index.php',
        target: 'div[style*="background:#e2e8f0"]',
        fallback: '.page-header',
        module: 'Admission Pipeline',
        section: 'View Modes',
        title: 'Kanban vs Table View Mode Switcher',
        badge: 'Visual Toggle',
        icon: 'fa-table-columns',
        description: 'Switch between the drag-and-drop Kanban card board and structured spreadsheet Table View depending on your operational focus.',
        tip: 'Kanban view is ideal for daily milestone reviews; table view is great for bulk scanning.',
        position: 'bottom'
      },
      {
        url: '/admissions/index.php',
        target: 'select[name="stage"], select[name="country"]',
        fallback: '.card form',
        module: 'Admission Pipeline',
        section: 'Pipeline Filters',
        title: 'Stage, Country & Counselor Filters',
        badge: 'Pipeline Scoping',
        icon: 'fa-filter',
        description: 'Filter swimlanes by target study country (UK, Canada, USA, Australia, Europe) or individual counselor to review specific territory performance.',
        tip: 'Filter by country to review applicant batches ahead of university intake deadlines.',
        position: 'bottom'
      },
      {
        url: '/admissions/index.php',
        target: 'div[style*="display:grid; grid-template-columns:repeat"]',
        fallback: '.card',
        module: 'Admission Pipeline',
        section: 'Kanban Swimlanes',
        title: 'Visual 6-Stage Swimlanes & Candidate Cards',
        badge: 'Drag & Advance',
        icon: 'fa-table-columns',
        description: 'Each column represents an official milestone with live student cards showing tracking code, priority, destination country, and assigned counselor. Secondary status tags (Active, On Hold, Lost) alert managers to stalled applicants.',
        tip: 'Click "Advance &rarr;" on any student card to rapidly progress them to the next milestone.',
        position: 'top'
      },

      // === MODULE 5: OPERATIONAL WORK QUEUES ===
      {
        url: '/tasks/index.php',
        target: '.page-header',
        fallback: '#main-wrapper',
        module: 'Daily Follow-ups',
        section: 'SLA Engine',
        title: 'Daily Operational Work Queues',
        badge: 'Follow-Up Compliance',
        icon: 'fa-list-check',
        description: 'The daily operational workbench for counselors and managers. Features 4 strict SLA queues designed to ensure 0% lead abandonment: Scheduled Follow-ups, Pending Overdue Calls, Today\'s Fresh Leads, and Older Uncontacted Leads.',
        tip: 'Counselors should clear all Overdue and Scheduled calls before beginning new prospect consultations.',
        position: 'bottom'
      },
      {
        url: '/tasks/index.php',
        target: 'a[href*="queue=pending_followup"]',
        fallback: 'div[style*="display:grid"]',
        module: 'Daily Follow-ups',
        section: 'Overdue Alerting',
        title: 'Pending Follow-ups (Overdue Calls Queue)',
        badge: 'SLA Alert',
        icon: 'fa-triangle-exclamation',
        description: 'Highlights appointments that have exceeded their scheduled date without resolution. Displays overdue lag days in red to pinpoint neglected inquiries.',
        tip: 'Managers monitor this queue daily to maintain zero delayed inquiries across the team.',
        position: 'top'
      },
      {
        url: '/tasks/index.php',
        target: 'table.data-table',
        fallback: '.card',
        module: 'Daily Follow-ups',
        section: 'Outreach & Logging',
        title: 'Action List with 1-Click WhatsApp & Outcome Logging',
        badge: 'Instant Outreach',
        icon: 'fa-phone-volume',
        description: 'Contact candidates via direct telephone call or 1-click WhatsApp Web. Immediately after the conversation, click "Log Outcome" to record consultation notes, select the discussion title, and set the next follow-up date.',
        tip: 'Logging an outcome automatically moves completed tasks out of the active queue.',
        position: 'top'
      },

      // === MODULE 6: ENROLLED CLIENTS ===
      {
        url: '/customers/index.php',
        target: '.page-header',
        fallback: '#main-wrapper',
        module: 'Enrolled Clients',
        section: 'Customer Ledger',
        title: 'Enrolled Clients (Invoiced Customers)',
        badge: 'Converted Applicants',
        icon: 'fa-certificate',
        description: 'Candidates who have formally engaged institutional services and received official invoices. Displays complete billing metrics: Total Invoiced, Total Collected Revenue, and Outstanding Receivables.',
        tip: 'Filter by Payment Status (Paid, Partial, Unpaid) to manage fee recovery schedules.',
        position: 'bottom'
      },
      {
        url: '/customers/index.php',
        target: 'select[name="payment_status"]',
        fallback: '.card form',
        module: 'Enrolled Clients',
        section: 'Payment Filters',
        title: 'Payment Status Filter & Client Ledgers',
        badge: 'Billing Reconciliation',
        icon: 'fa-money-bill-transfer',
        description: 'Filter clients by billing settlement: Fully Paid, Partial Payment, or Unpaid Balances. Direct links allow downloading receipts or recording fee installments.',
        tip: 'Review partial payments before university tuition remittance deadlines.',
        position: 'bottom'
      },

      // === MODULE 7: LEAD INBOUND & TRIAGE ===
      {
        url: '/data/web_leads.php',
        target: '.page-header',
        fallback: '#main-wrapper',
        module: 'Lead Inbound & Triage',
        section: 'Digital Ads Triage',
        title: 'Digital Web & Social Inbound Triage',
        badge: 'Lead Allocation',
        icon: 'fa-bullseye',
        description: 'Prospective inquiries captured via website contact forms, Meta Facebook/Instagram Ads, and Google Ads stage here before entering active caseloads. Inspect applicant course preferences and allocate to branch counselors with duplicate phone checks.',
        tip: 'Rapid lead triage (<15 minutes from ad click) yields the highest university conversion rates.',
        position: 'bottom'
      },
      {
        url: '/data/web_leads.php',
        target: 'table.data-table',
        fallback: '.card',
        module: 'Lead Inbound & Triage',
        section: 'Inbound Verification',
        title: 'Inbound Lead Verification & Allocation Action',
        badge: 'Caseload Routing',
        icon: 'fa-share-nodes',
        description: 'Review applicant study interests, intake terms, and source campaign. Click "Allocate &rarr;" to assign the lead to a branch counselor with duplicate phone protection.',
        tip: 'Allocated leads immediately appear in the assigned counselor\'s Today Leads queue.',
        position: 'top'
      },

      // === MODULE 8: COLLEGE DATA POOL ===
      {
        url: '/data/college_data.php',
        target: '.page-header',
        fallback: '#main-wrapper',
        module: 'College Data Pool',
        section: 'Campus Fair Ingestion',
        title: 'College Data Pool & Education Fair Rosters',
        badge: 'Bulk Prospect Ingestion',
        icon: 'fa-building-columns',
        description: 'Repository for contact lists gathered during university campus fairs, school outreach, and education expos. Preview student qualification records and convert qualified fair attendees into active CRM leads with 1 click.',
        tip: 'Use "Import College List" in the sidebar to upload CSV or Excel files with duplicate phone filtering.',
        position: 'bottom'
      },
      {
        url: '/data/college_data.php',
        target: 'table.data-table',
        fallback: '.card',
        module: 'College Data Pool',
        section: 'Lead Promotion',
        title: '1-Click Convert to Active Inquiry',
        badge: 'Prospect Conversion',
        icon: 'fa-user-check',
        description: 'Click "Convert &rarr;" on any student row to promote them from raw campus fair data into an active CRM lead with an assigned counselor and tracking code.',
        tip: 'Filter by degree or college name to convert qualified student batches together.',
        position: 'top'
      },

      // === MODULE 9: FINANCE & INVOICES ===
      {
        url: '/finance/invoices.php',
        target: '.page-header',
        fallback: '#main-wrapper',
        module: 'Finance & Invoices',
        section: 'Billings & Receipts',
        title: 'Invoices, Billings & Receipts Ledger',
        badge: 'Financial Governance',
        icon: 'fa-file-invoice-dollar',
        description: 'Central billing master directory for consultancy fees, university application processing charges, and visa handling fees. Displays invoice numbers, student details, issue dates, total amounts, paid installments, and balance due.',
        tip: 'Click the PDF icon on any invoice row to generate an official printable client receipt.',
        position: 'bottom'
      },
      {
        url: '/finance/invoices.php',
        target: 'table.data-table',
        fallback: '.card',
        module: 'Finance & Invoices',
        section: 'Invoices Table',
        title: 'Invoices Master Table & PDF Generation',
        badge: 'Ledger Records',
        icon: 'fa-file-pdf',
        description: 'Review invoice numbers, client names, payment status (Paid, Partial, Unpaid), and balance receivables. Click "View PDF" to download official printable client receipts or record payment installments.',
        tip: 'All invoice balances sync directly with student profiles in real time.',
        position: 'top'
      },

      // === MODULE 10: GENERATE INVOICE ===
      {
        url: '/finance/create_invoice.php',
        target: '.page-header',
        fallback: '#main-wrapper',
        module: 'Generate Invoice',
        section: 'Invoice Builder',
        title: 'Multi-Line Item Service Invoice Builder',
        badge: 'Official Billing',
        icon: 'fa-receipt',
        description: 'Create customized institutional invoices: select an active student lead, add multiple billable service line items (e.g. University Application Fee, Visa Filing, Document Attestation), specify quantities and rates, and apply discounts with automatic 18% GST calculation.',
        tip: 'All issued invoices automatically update student balance ledgers and executive revenue cards.',
        position: 'bottom'
      },
      {
        url: '/finance/create_invoice.php',
        target: 'select[name="lead_id"]',
        fallback: '.card',
        module: 'Generate Invoice',
        section: 'Student Selection',
        title: 'Select Student Lead for Invoicing',
        badge: 'Account Selection',
        icon: 'fa-user-tag',
        description: 'Choose the candidate being invoiced. Once selected, their name, tracking code, and branch are linked to the billing document.',
        tip: 'Search by student name or tracking code in the dropdown picker.',
        position: 'bottom'
      },
      {
        url: '/finance/create_invoice.php',
        target: 'button[onclick*="addItemRow"]',
        fallback: 'table',
        module: 'Generate Invoice',
        section: 'Line Items Builder',
        title: 'Multi-Line Items Builder with 18% GST',
        badge: 'Itemized Billing',
        icon: 'fa-plus',
        description: 'Add multiple service line items with descriptions, quantities, and rates. The system dynamically computes subtotal, applies discounts, and adds 18% GST to calculate the grand total.',
        tip: 'Click "+ Add Service Line Item" to bill multiple services (e.g. Consultation + Visa Filing) on a single invoice.',
        position: 'top'
      },

      // === MODULE 11: DOCUMENT HUB ===
      {
        url: '/documents/dossiers.php',
        target: '.page-header',
        fallback: '#main-wrapper',
        module: 'Document Hub',
        section: 'Student Dossiers',
        title: 'Student Academic Dossiers & Document Verification',
        badge: 'Verification Center',
        icon: 'fa-folder-open',
        description: 'Centralized repository of applicant documents: identity passports, 10th/12th/Degree marksheets, IELTS/PTE scorecards, and financial CAS solvency certificates. Categorized for seamless university visa submission compliance.',
        tip: 'Verify all required documents before moving a student to the APPLICATION or VISA stage.',
        position: 'bottom'
      },
      {
        url: '/documents/dossiers.php',
        target: 'div[style*="overflow-x:auto"]',
        fallback: '.page-header',
        module: 'Document Hub',
        section: 'Document Categories',
        title: 'Category Filters & Checklists',
        badge: 'Document Categories',
        icon: 'fa-list-check',
        description: 'Filter applicant paperwork by category: Academic Transcripts, Passport Copies, Language Scorecards, Financial Solvency Letters, or Visa Documentation.',
        tip: 'Use category pills to audit all pending visa documents ahead of consulate appointments.',
        position: 'bottom'
      },

      // === MODULE 12: STAFF MANAGEMENT ===
      {
        url: '/organization/users.php',
        target: '.page-header',
        fallback: '#main-wrapper',
        module: 'Staff Management',
        section: 'Team Administration',
        title: 'Staff & Team Directory',
        badge: 'Personnel Governance',
        icon: 'fa-users',
        description: 'Manage staff accounts across all campuses. Provision new staff credentials, assign designations (Admissions Manager, Senior Counselor, Academic Advisor), link staff to regional branches, and monitor active caseload counts.',
        tip: 'Click "Add Staff Account" to provision team credentials with secure hashed passwords.',
        position: 'bottom'
      },
      {
        url: '/organization/users.php',
        target: 'button[onclick*="openStaffModal"]',
        fallback: '.page-header',
        module: 'Staff Management',
        section: 'User Provisioning',
        title: 'Add Staff Member & Role Assignment Modal',
        badge: 'Credential Provisioning',
        icon: 'fa-user-shield',
        description: 'Click here to open the staff provisioning dialog: configure name, email, phone, role persona, branch assignment, and reporting manager hierarchy.',
        tip: 'Staff accounts can be set to Active or Inactive anytime to restrict system access immediately.',
        position: 'bottom'
      },

      // === MODULE 13: ROLES & PERMISSIONS MATRIX ===
      {
        url: '/organization/roles.php',
        target: '.page-header',
        fallback: '#main-wrapper',
        module: 'Roles & Permissions',
        section: 'RBAC Security Matrix',
        title: '16-Module Granular Permissions Matrix',
        badge: 'Security Governance',
        icon: 'fa-shield-halved',
        description: 'Enterprise Role-Based Access Control (RBAC). Configure granular permissions across all 16 CRM modules with individual checkboxes for: View, Add, Edit, Delete, Assign, Reassign, Approve, Export, Archive, and Restore.',
        tip: 'Changes saved here take effect immediately at the PHP backend security enforcement level.',
        position: 'bottom'
      },
      {
        url: '/organization/roles.php',
        target: 'table.data-table',
        fallback: '.card',
        module: 'Roles & Permissions',
        section: 'Permission Switches',
        title: 'Granular Action Switches & Instant Security Enforcement',
        badge: 'Permission Grid',
        icon: 'fa-sliders',
        description: 'Toggle individual capabilities per module: prevent counselors from deleting leads, restrict exporting, or grant managers approval authority. Click "Save Permission Matrix" to apply changes immediately.',
        tip: 'Super Admin permissions are permanently immutable to ensure the system is never locked out.',
        position: 'top'
      },

      // === MODULE 14: PERFORMANCE ANALYTICS ===
      {
        url: '/reports/index.php',
        target: '.page-header',
        fallback: '#main-wrapper',
        module: 'Performance Analytics',
        section: 'Executive Intelligence',
        title: 'Reports & Performance Analytics Hub',
        badge: 'Business Intelligence',
        icon: 'fa-chart-pie',
        description: 'Multi-dimensional reporting suite: evaluate Student Lead Conversion trends, Regional Branch yields, Counselor consultation productivity, and Financial revenue collection over custom timeframes (7 Days, 30 Days, Fiscal Year).',
        tip: 'Click "Export CSV" to download comprehensive spreadsheet audit packages.',
        position: 'bottom'
      },
      {
        url: '/reports/index.php',
        target: 'div[style*="display:flex; gap:0.5rem; margin-bottom:1.25rem"]',
        fallback: '.page-header',
        module: 'Performance Analytics',
        section: 'Report Types',
        title: 'Lead, Branch, Counselor & Finance Reports',
        badge: 'Executive Reports',
        icon: 'fa-chart-column',
        description: 'Switch between 4 specialized reports: (1) Lead Acquisition Sources, (2) Branch Campus Yields, (3) Counselor Follow-up Productivity & Conversion %, and (4) Finance & Revenue Collections.',
        tip: 'Review counselor conversion rates to identify staff who may benefit from additional training.',
        position: 'bottom'
      },

      // === MODULE 15: SYSTEM SETTINGS ===
      {
        url: '/system/settings.php',
        target: '.page-header',
        fallback: '#main-wrapper',
        module: 'System Settings',
        section: 'Operational Rules',
        title: 'Interaction Rules & Intake Cycles Governance',
        badge: 'System Rules',
        icon: 'fa-sliders',
        description: 'Configure standardized consultation interaction titles (Initial Consultation, Document Review, Visa Mock Interview) and set maximum SLA lag duration in days. Define institutional intake cycles (e.g. September 2026, January 2027).',
        tip: 'Setting a maximum lag of 3 days ensures overdue alerts trigger promptly if a counselor neglects follow-ups.',
        position: 'bottom'
      },

      // === MODULE 16: ARCHIVE REPOSITORY ===
      {
        url: '/archive/index.php',
        target: '.page-header',
        fallback: '#main-wrapper',
        module: 'Archive Repository',
        section: 'Compliance & Audit',
        title: 'Archive Repository & Deletion Audit Log',
        badge: 'Data Integrity',
        icon: 'fa-box-archive',
        description: 'Soft-deleted student leads, client records, and college contacts are safely preserved here. Administrators can restore records with 1 click or inspect permanent deletion compliance audit logs to maintain data protection governance.',
        tip: 'Soft-deleted records never lose historical consultation logs or fee transaction history upon restoration.',
        position: 'bottom'
      }
    ],

    // -----------------------------------------------------------------------
    // 2. ADMISSIONS MANAGER (23-Step Walkthrough across Operations & Team)
    // -----------------------------------------------------------------------
    manager: [
      // Cockpit
      {
        url: '/dashboard/index.php',
        target: '.page-header',
        fallback: '#main-wrapper',
        module: 'Admissions Cockpit',
        section: 'Branch Command',
        title: 'Branch Operations Command Center',
        badge: 'Admissions Manager • Operations',
        icon: 'fa-building-user',
        description: 'Welcome, Admissions Manager! This interactive training covers all your operational responsibilities: supervising counselor caseloads, allocating fresh digital leads, monitoring follow-up compliance, and driving student conversions.',
        tip: 'You can pause, resume, or restart this walkthrough anytime using the Guide button.',
        position: 'bottom'
      },
      {
        url: '/dashboard/index.php',
        target: '#btn-quick-add-lead',
        fallback: '.page-header',
        module: 'Admissions Cockpit',
        section: 'Desk Intake',
        title: 'Quick Lead Capture & Allocation',
        badge: 'Immediate Registration',
        icon: 'fa-bolt',
        description: 'Quickly intake walk-in applicants or telephone calls arriving at the front desk and allocate them directly to a counselor in 15 seconds.',
        tip: 'Assigned counselors receive instant notifications in their task queues.',
        position: 'bottom'
      },
      {
        url: '/dashboard/index.php',
        target: '#widget-unassigned-leads',
        fallback: '#widget-4step-workflow',
        module: 'Admissions Cockpit',
        section: 'Fresh Inbound Station',
        title: 'Digital Inbound Triage Station',
        badge: 'Lead Triage',
        icon: 'fa-bullhorn',
        description: 'Digital inquiries from Meta Ads, Instagram campaigns, and Google Ads arrive here in real time. Inspect candidate study preferences and route them to counselors based on country expertise.',
        tip: 'Rapid lead triage (<15 minutes) dramatically improves candidate conversion rates.',
        position: 'bottom'
      },
      {
        url: '/dashboard/index.php',
        target: '#widget-action-calls',
        fallback: '#widget-metric-cards',
        module: 'Admissions Cockpit',
        section: 'Team Oversight',
        title: "Today\'s Action Call List Oversight",
        badge: 'Outreach Supervision',
        icon: 'fa-phone-volume',
        description: 'Supervise all candidate consultation calls scheduled for today. Verify that your counselors are reaching out promptly via direct calls and 1-click WhatsApp Web.',
        tip: 'Counselors log discussion outcomes here, creating permanent audit notes.',
        position: 'top'
      },
      {
        url: '/dashboard/index.php',
        target: '#widget-task-queues',
        fallback: '.content-area',
        module: 'Admissions Cockpit',
        section: 'SLA Tracking',
        title: 'Follow-Up SLA Enforcement & Overdue Alerting',
        badge: 'SLA Tracking',
        icon: 'fa-list-check',
        description: 'Four live queues: Today\'s Calls, Overdue Calls (>24 hours delay), Fresh Leads, and Uncontacted Inquiries. Maintain zero overdue calls to maximize student engagement.',
        tip: 'Click any queue card to filter tasks by counselor and clear overdue items.',
        position: 'top'
      },
      {
        url: '/dashboard/index.php',
        target: '#widget-pipeline-chart',
        fallback: '#widget-recent-leads',
        module: 'Admissions Cockpit',
        section: 'Conversion Funnel',
        title: '6-Stage Admission Funnel Analytics',
        badge: 'Milestone Telemetry',
        icon: 'fa-diagram-project',
        description: 'Visual analytics tracking student progression across Enquiry &rarr; Customer &rarr; Application &rarr; Offer Received &rarr; Tuition Fees Paid &rarr; Enrolled.',
        tip: 'Click "Kanban &rarr;" to interact with the visual card board.',
        position: 'top'
      },

      // Student Leads
      {
        url: '/leads/index.php',
        target: '.page-header',
        fallback: '#main-wrapper',
        module: 'Student Leads',
        section: 'Caseload Management',
        title: 'Student Leads & Bulk Reassignment Tool',
        badge: 'Caseload Management',
        icon: 'fa-user-graduate',
        description: 'View all branch student records. When a counselor is on leave or reassigning territories, use "Bulk Reassign" to rebalance student caseloads across team members.',
        tip: 'Select multiple candidates using table checkboxes and click Bulk Reassign at the top.',
        position: 'bottom'
      },
      {
        url: '/leads/index.php',
        target: 'select[name="counselor_id"]',
        fallback: '.card form',
        module: 'Student Leads',
        section: 'Counselor Filter',
        title: 'Filter Caseloads by Counselor',
        badge: 'Staff Caseloads',
        icon: 'fa-users',
        description: 'Filter student records by individual counselor to inspect their active student roster, stage distribution, and follow-up adherence.',
        tip: 'The numbers next to each counselor name show their active student count.',
        position: 'bottom'
      },
      {
        url: '/leads/index.php',
        target: 'button[onclick*="openBulkReassignModal"]',
        fallback: '.page-header',
        module: 'Student Leads',
        section: 'Workload Balancing',
        title: 'Bulk Reassign Student Leads',
        badge: 'Caseload Rebalancing',
        icon: 'fa-users-gear',
        description: 'Select candidates in the table and rebalance them between team counselors to ensure fair distribution and prompt student outreach.',
        tip: 'Reassigning notifies the new counselor immediately in their task queue.',
        position: 'bottom'
      },
      {
        url: '/leads/index.php',
        target: 'a[href*="export=csv"]',
        fallback: '.page-header',
        module: 'Student Leads',
        section: 'Reporting',
        title: 'Export Branch Student Roster',
        badge: 'Branch Reporting',
        icon: 'fa-file-csv',
        description: 'Export branch student lead lists to CSV spreadsheets for weekly operational admissions reviews.',
        tip: 'Respects active filters such as priority or counselor.',
        position: 'bottom'
      },

      // Add Lead
      {
        url: '/leads/add.php',
        target: '.page-header',
        fallback: '#main-wrapper',
        module: 'Register Student Lead',
        section: 'Branch Intake',
        title: 'Student Registration & Counselor Routing',
        badge: 'Branch Intake',
        icon: 'fa-user-plus',
        description: 'Register new prospective students arriving at the branch front desk. Assign them directly to the counselor best suited for their chosen destination country.',
        tip: 'Ensure mobile numbers are accurate to enable 1-click WhatsApp messaging.',
        position: 'bottom'
      },
      {
        url: '/leads/add.php',
        target: 'select[name="counselor_id"]',
        fallback: '.card',
        module: 'Register Student Lead',
        section: 'Staff Assignment',
        title: 'Assign Counselor by Country Expertise',
        badge: 'Specialist Allocation',
        icon: 'fa-user-tag',
        description: 'Select the counselor who specializes in the student\'s target country (UK, Canada, USA, Europe). The lead immediately appears on their priority call list.',
        tip: 'Country specialization significantly increases applicant conversion rates.',
        position: 'bottom'
      },

      // Pipeline
      {
        url: '/admissions/index.php',
        target: '.page-header',
        fallback: '#main-wrapper',
        module: 'Admission Pipeline',
        section: 'Kanban Supervision',
        title: '6-Stage Admission Pipeline Kanban',
        badge: 'Milestone Supervision',
        icon: 'fa-diagram-project',
        description: 'Monitor your branch students across all 6 stages. Review secondary statuses (On Hold, Active, Lost) to spot candidates requiring extra assistance.',
        tip: 'Filter by counselor to review specific counselor pipeline progression.',
        position: 'bottom'
      },
      {
        url: '/admissions/index.php',
        target: 'button[onclick*="openQuickStageModal"]',
        fallback: '.card',
        module: 'Admission Pipeline',
        section: 'Milestone Approval',
        title: 'Milestone Advancement & Stage Oversight',
        badge: 'Milestone Validation',
        icon: 'fa-angles-right',
        description: 'Verify and progress student stages as application forms are submitted and university offer letters are received.',
        tip: 'Ensure all required documents are attached in dossiers before promoting to Application stage.',
        position: 'top'
      },

      // Tasks
      {
        url: '/tasks/index.php',
        target: '.page-header',
        fallback: '#main-wrapper',
        module: 'Daily Follow-ups',
        section: 'Queue Supervision',
        title: 'Workload Queues & Zero-Overdue Compliance',
        badge: 'SLA Enforcement',
        icon: 'fa-list-check',
        description: 'Supervise daily follow-up queues. Ensure counselors contact fresh inbound leads within 24 hours and resolve pending overdue tasks promptly.',
        tip: 'Direct telephone and WhatsApp links enable you to test candidate communications directly.',
        position: 'bottom'
      },

      // Web Leads
      {
        url: '/data/web_leads.php',
        target: '.page-header',
        fallback: '#main-wrapper',
        module: 'Lead Inbound & Triage',
        section: 'Inbound Triage',
        title: 'Digital Inbound Triage & Allocation Board',
        badge: 'Lead Inbound',
        icon: 'fa-bullseye',
        description: 'Triage digital prospective inquiries captured from Meta, Google, and Instagram Ads. Route candidates to branch counselors based on country specialization.',
        tip: 'Verify preferred courses and intake sessions before assigning.',
        position: 'bottom'
      },
      {
        url: '/data/web_leads.php',
        target: 'table.data-table',
        fallback: '.card',
        module: 'Lead Inbound & Triage',
        section: 'Lead Distribution',
        title: 'Review Inquiry & Allocate to Counselor',
        badge: 'Immediate Distribution',
        icon: 'fa-share-nodes',
        description: 'Click "Allocate &rarr;" to assign incoming digital submissions to branch counselors with automatic duplicate phone checking.',
        tip: 'Rapid lead triage dramatically reduces candidate drop-off.',
        position: 'top'
      },

      // College Data
      {
        url: '/data/college_data.php',
        target: '.page-header',
        fallback: '#main-wrapper',
        module: 'College Data Pool',
        section: 'Expo Data',
        title: 'College Fair & Expo Rosters',
        badge: 'Prospect Ingestion',
        icon: 'fa-building-columns',
        description: 'Ingest raw student contact rosters from university fairs and workshops. Promote qualified students into active CRM inquiries with 1 click.',
        tip: 'Check duplicate flags to avoid creating duplicate active inquiries.',
        position: 'bottom'
      },

      // Finance
      {
        url: '/finance/invoices.php',
        target: '.page-header',
        fallback: '#main-wrapper',
        module: 'Finance & Invoices',
        section: 'Fee Collections',
        title: 'Branch Invoices & Billing Ledgers',
        badge: 'Fee Collections',
        icon: 'fa-file-invoice-dollar',
        description: 'Review branch consultancy invoices, payment receipts, and outstanding fee balances for your campus applicants.',
        tip: 'Download official PDF invoices to issue to students and sponsor families.',
        position: 'bottom'
      },

      // Documents
      {
        url: '/documents/dossiers.php',
        target: 'div[style*="overflow-x:auto"]',
        fallback: '.page-header',
        module: 'Document Hub',
        section: 'Compliance Check',
        title: 'Student Dossiers & Compliance Checklists',
        badge: 'Document Hub',
        icon: 'fa-folder-open',
        description: 'Inspect applicant paperwork: passports, academic transcripts, and official visa requirements. Verify documents before authorizing university submissions.',
        tip: 'Use category filters to quickly review IELTS scorecards or bank solvency letters.',
        position: 'bottom'
      },

      // Staff Team
      {
        url: '/organization/users.php',
        target: '.page-header',
        fallback: '#main-wrapper',
        module: 'Staff Management',
        section: 'Branch Roster',
        title: 'Counselor Productivity & Team Oversight',
        badge: 'Staff Administration',
        icon: 'fa-users',
        description: 'Monitor individual counselor activity, active caseload counts, and conversion rates to keep your admissions squad operating at peak productivity.',
        tip: 'Rebalance workloads if any counselor exceeds optimal active student limits.',
        position: 'bottom'
      },

      // Reports
      {
        url: '/reports/index.php',
        target: 'div[style*="display:flex; gap:0.5rem; margin-bottom:1.25rem"]',
        fallback: '.page-header',
        module: 'Performance Analytics',
        section: 'Branch Yields',
        title: 'Branch Yield & Counselor Performance Reports',
        badge: 'Performance Analytics',
        icon: 'fa-chart-pie',
        description: 'Review monthly and quarterly conversion yields, counselor follow-up volumes, and lead acquisition channel performance for your campus.',
        tip: 'Export reports to CSV for monthly branch operational reviews.',
        position: 'bottom'
      }
    ],

    // -----------------------------------------------------------------------
    // 3. ADMISSIONS COUNSELOR (22-Step Walkthrough across Daily Workflows)
    // -----------------------------------------------------------------------
    counselor: [
      // Cockpit
      {
        url: '/dashboard/index.php',
        target: '.page-header',
        fallback: '#main-wrapper',
        module: 'Consultation Cockpit',
        section: 'Daily Workspace',
        title: 'Daily Counseling & Consultation Cockpit',
        badge: 'Admissions Counselor • Student Guidance',
        icon: 'fa-headset',
        description: 'Welcome, Counselor! Your daily workspace is optimized for consulting prospective students, organizing phone calls, and advancing candidates toward university acceptance.',
        tip: 'You can pause, resume, or restart this walkthrough anytime using the Guide button in the top bar.',
        position: 'bottom'
      },
      {
        url: '/dashboard/index.php',
        target: '#btn-quick-add-lead',
        fallback: '.page-header',
        module: 'Consultation Cockpit',
        section: 'Fast Intake',
        title: 'Fast 15-Second Lead Capture',
        badge: 'Call & Walk-In Intake',
        icon: 'fa-bolt',
        description: 'Quickly capture prospective student calls or walk-ins in 15 seconds. Automatically links the candidate to your caseload and schedules your first counseling session.',
        tip: 'Fill candidate name, mobile, target country, and preferred intake term.',
        position: 'bottom'
      },
      {
        url: '/dashboard/index.php',
        target: '#widget-action-calls',
        fallback: '#widget-4step-workflow',
        module: 'Consultation Cockpit',
        section: 'Priority Queue',
        title: 'Your Priority Daily Action Call List',
        badge: 'Daily Priority Queue',
        icon: 'fa-phone-volume',
        description: 'Your most critical daily tool! Lists candidates scheduled for consultation today. Any overdue calls appear first in red so you can attend to delayed inquiries first.',
        tip: 'Work through this list every morning to maintain 100% follow-up compliance.',
        position: 'top'
      },
      {
        url: '/dashboard/index.php',
        target: '#widget-action-calls a[href*="wa.me"]',
        fallback: '#widget-action-calls',
        module: 'Consultation Cockpit',
        section: 'WhatsApp Outreach',
        title: '1-Click WhatsApp Candidate Messaging',
        badge: 'Instant Outreach',
        icon: 'fa-brands fa-whatsapp',
        description: 'Click the green WhatsApp icon to launch WhatsApp Web with a pre-filled personalized greeting directly to the candidate.',
        tip: 'WhatsApp follow-ups achieve significantly higher response rates than email.',
        position: 'top'
      },
      {
        url: '/dashboard/index.php',
        target: '#widget-action-calls a[href*="tel:"]',
        fallback: '#widget-action-calls',
        module: 'Consultation Cockpit',
        section: 'Direct Calling',
        title: 'Direct Telephone Call Launcher',
        badge: 'Telephone Outreach',
        icon: 'fa-phone',
        description: 'Click the candidate phone number to launch direct dialing via your telephone dialer or softphone client immediately.',
        tip: 'Always prepare discussion points from the student notes before placing the call.',
        position: 'top'
      },
      {
        url: '/dashboard/index.php',
        target: '#widget-action-calls button',
        fallback: '#widget-action-calls',
        module: 'Consultation Cockpit',
        section: 'Mandatory Logging',
        title: 'Mandatory: Log Consultation Outcome Immediately',
        badge: 'Call Logging',
        icon: 'fa-check',
        description: 'Immediately after every conversation, click Log Outcome. Record what was discussed, select the interaction title, and set the next follow-up date (Tomorrow, +2 Days, Next Week).',
        tip: 'Never finish a call without logging notes and setting the next follow-up appointment date.',
        position: 'top'
      },
      {
        url: '/dashboard/index.php',
        target: '#widget-4step-workflow',
        fallback: '.content-area',
        module: 'Consultation Cockpit',
        section: 'Standard Workflow',
        title: 'Standard 4-Step Daily Admissions Routine',
        badge: 'Daily Routine',
        icon: 'fa-route',
        description: 'The 4 daily phases: (1) Capture Inquiry &rarr; (2) Call & WhatsApp &rarr; (3) Advance Milestones &rarr; (4) Fee Documentation. Click any step anytime to navigate directly.',
        tip: 'Advance milestones as soon as a student submits documents or receives an offer letter.',
        position: 'bottom'
      },
      {
        url: '/dashboard/index.php',
        target: '#widget-task-queues',
        fallback: '.content-area',
        module: 'Consultation Cockpit',
        section: 'Task Queues',
        title: 'Your 4 Operational Task Queues',
        badge: 'SLA Adherence',
        icon: 'fa-list-check',
        description: 'Your 4 work queues: Today\'s Scheduled Calls, Overdue Calls (clear first!), Fresh Inbound leads, and Uncontacted inquiries. Keep your total action items low each day.',
        tip: 'Click any queue card to filter your tasks immediately.',
        position: 'top'
      },

      // Student Leads
      {
        url: '/leads/index.php',
        target: '.page-header',
        fallback: '#main-wrapper',
        module: 'Student Leads',
        section: 'Personal Caseload',
        title: 'Your Strictly Assigned Student Caseload',
        badge: 'Student Caseload',
        icon: 'fa-user-graduate',
        description: 'This directory displays only candidates strictly assigned to you. Enjoy complete focus and student privacy. Search candidates by name, mobile, email, or target country.',
        tip: 'Use the stage filter pills above to view students at each stage of their application journey.',
        position: 'bottom'
      },
      {
        url: '/leads/index.php',
        target: 'div[style*="overflow-x:auto"]',
        fallback: '.page-header',
        module: 'Student Leads',
        section: 'Stage Filter Pills',
        title: 'Filter Caseload by Milestone Stage',
        badge: 'Milestone Filter',
        icon: 'fa-tags',
        description: 'Click any stage pill (Enquiry, Customer, Application, Offer Received, Tuition Fees Paid, Enrolled) to isolate applicants in that specific phase.',
        tip: 'Focus on "Offer Received" candidates to assist them with prompt tuition fee payments.',
        position: 'bottom'
      },
      {
        url: '/leads/index.php',
        target: 'input[name="search"]',
        fallback: '.card',
        module: 'Student Leads',
        section: 'Search Caseload',
        title: 'Search Your Student Caseload',
        badge: 'Quick Search',
        icon: 'fa-magnifying-glass',
        description: 'Quickly find any student on your caseload by typing their name, mobile number, or tracking code.',
        tip: 'Press Enter or click Apply to search.',
        position: 'bottom'
      },
      {
        url: '/leads/index.php',
        target: 'table.data-table a.btn-secondary',
        fallback: 'table.data-table',
        module: 'Student Leads',
        section: 'Dossier Access',
        title: 'Candidate Records & Full Dossier Access',
        badge: 'Student Profile',
        icon: 'fa-address-card',
        description: 'Inspect applicant tracking codes, contact details, target country, and current stage. Click "Dossier &rarr;" on any student to open their complete academic profile.',
        tip: 'Keep student contact information updated to prevent communication lapses.',
        position: 'top'
      },

      // Add Lead
      {
        url: '/leads/add.php',
        target: '.page-header',
        fallback: '#main-wrapper',
        module: 'Register Student Lead',
        section: 'Comprehensive Intake',
        title: 'Student Registration Master Form',
        badge: 'Full Intake',
        icon: 'fa-user-plus',
        description: 'Register prospective students who require full academic and test score documentation. The lead is automatically assigned to your personal caseload.',
        tip: 'Essential fields are required; academic and test scores can be completed anytime.',
        position: 'bottom'
      },
      {
        url: '/leads/add.php',
        target: 'input[name="full_name"]',
        fallback: '.card',
        module: 'Register Student Lead',
        section: 'Candidate Details',
        title: 'Candidate Name & Contact Information',
        badge: 'Essential Info',
        icon: 'fa-id-card',
        description: 'Enter student Full Name, 10-digit mobile number, and email. The system automatically verifies that the phone number is not a duplicate.',
        tip: 'Double check phone numbers to ensure WhatsApp messages deliver successfully.',
        position: 'bottom'
      },
      {
        url: '/leads/add.php',
        target: 'select[name="preferred_country"]',
        fallback: '.card',
        module: 'Register Student Lead',
        section: 'Study Goals',
        title: 'Target Country & Academic Intake Term',
        badge: 'Study Goal',
        icon: 'fa-earth-americas',
        description: 'Select the student\'s preferred study destination and target intake year/term (September 2026, January 2027).',
        tip: 'Synchronizing intake terms helps you set realistic document submission targets.',
        position: 'bottom'
      },
      {
        url: '/leads/add.php',
        target: 'details:nth-of-type(1)',
        fallback: '.card:nth-of-type(2)',
        module: 'Register Student Lead',
        section: 'Academic Transcripts',
        title: 'Academic Background & Language Scores (IELTS/PTE)',
        badge: 'Scorecard Recording',
        icon: 'fa-graduation-cap',
        actionBefore: function() {
          const d = document.querySelector('details:nth-of-type(1)');
          if (d) d.open = true;
        },
        description: 'Record student degree percentage, backlogs, and standardized English scores (IELTS, PTE, TOEFL, Duolingo) with sectional Listening, Reading, Writing, and Speaking scores.',
        tip: 'Accurate test scores ensure prompt university conditional offer issuance.',
        position: 'bottom'
      },
      {
        url: '/leads/add.php',
        target: 'textarea[name="notes"]',
        fallback: 'button[type="submit"]',
        module: 'Register Student Lead',
        section: 'Consultation Notes',
        title: 'Initial Counseling Remarks',
        badge: 'Student Notes',
        icon: 'fa-pen-to-square',
        description: 'Record initial consultation remarks: student career aspirations, university preferences, budget constraints, and pending document requirements.',
        tip: 'Detailed initial remarks give you great context before every subsequent follow-up call.',
        position: 'top'
      },

      // Pipeline
      {
        url: '/admissions/index.php',
        target: 'div[style*="background:#e2e8f0"]',
        fallback: '.page-header',
        module: 'Admission Pipeline',
        section: 'Milestone Advancement',
        title: '6-Stage Admission Pipeline Kanban',
        badge: 'Milestone Progression',
        icon: 'fa-diagram-project',
        description: 'Progress your students through the 6 stages: ENQUIRY &rarr; CUSTOMER (engaged) &rarr; APPLICATION (submitted) &rarr; OFFER RECEIVED &rarr; TUITION FEES PAID &rarr; ENROLLED.',
        tip: 'Click "Advance &rarr;" on any student card as soon as they achieve their next milestone.',
        position: 'bottom'
      },
      {
        url: '/admissions/index.php',
        target: 'button[onclick*="openQuickStageModal"]',
        fallback: '.card',
        module: 'Admission Pipeline',
        section: 'Stage Advancer',
        title: 'Advance Student Milestone Dialog',
        badge: 'Promote Stage',
        icon: 'fa-angles-right',
        description: 'Click "Advance &rarr;" to progress the candidate to their next milestone, record counseling discussion notes, and trigger stage transition timestamps.',
        tip: 'Advancing milestones helps management track your conversion yield accurately.',
        position: 'top'
      },

      // Tasks
      {
        url: '/tasks/index.php',
        target: 'div[style*="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr))"]',
        fallback: '.page-header',
        module: 'Daily Follow-ups',
        section: 'Action Lists',
        title: 'Daily Follow-up Action Surface',
        badge: 'Daily Execution',
        icon: 'fa-list-check',
        description: 'Work through Today\'s Tasks, Overdue Calls (clear immediately), Fresh Leads, and Uncontacted Inquiries. 1-click WhatsApp and phone calls make outreach effortless.',
        tip: 'Always log an outcome immediately after every call to clear the item from your queue.',
        position: 'bottom'
      },
      {
        url: '/tasks/index.php',
        target: 'table.data-table',
        fallback: '.card',
        module: 'Daily Follow-ups',
        section: 'Queue Clearance',
        title: 'Clearing Tasks with Mandatory Outcome Logs',
        badge: 'Call Logging',
        icon: 'fa-circle-check',
        description: 'Execute your scheduled consultations one by one. Log notes and schedule the next follow-up date to ensure the student never drops out of the funnel.',
        tip: 'Clear all tasks every day to maintain a 100% compliance badge.',
        position: 'top'
      },

      // Documents
      {
        url: '/documents/dossiers.php',
        target: 'div[style*="overflow-x:auto"]',
        fallback: '.page-header',
        module: 'Document Hub',
        section: 'Document Verification',
        title: 'Student Document Hub & Country Checklists',
        badge: 'Document Hub',
        icon: 'fa-folder-open',
        description: 'Inspect uploaded applicant paperwork: passport copies, academic transcripts, SOPs, and university offer letters. Access country visa checklists for UK, Canada, USA, and Australia.',
        tip: 'Ensure all required documents are verified before advancing students to the APPLICATION stage.',
        position: 'bottom'
      }
    ]
  };

  // State Management
  let activeRoleKey = 'counselor';
  let activeSteps = [];
  let currentStepIndex = 0;
  let isTourActive = false;
  let isTourPaused = false;
  let currentHighlightedElement = null;

  // DOM Elements cache
  let overlayEl = null;
  let spotlightEl = null;
  let popoverEl = null;
  let resumePillEl = null;
  let navToastEl = null;
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

    if (!resumePillEl) {
      resumePillEl = document.getElementById('tutorial-resume-pill');
      if (!resumePillEl) {
        resumePillEl = document.createElement('div');
        resumePillEl.id = 'tutorial-resume-pill';
        resumePillEl.innerHTML = '<i class="fa-solid fa-play"></i> <span id="tut-resume-text">Resume Tutorial</span>';
        resumePillEl.onclick = function() { window.resumeTutorial(); };
        document.body.appendChild(resumePillEl);
      }
    }

    if (!navToastEl) {
      navToastEl = document.getElementById('tutorial-nav-toast');
      if (!navToastEl) {
        navToastEl = document.createElement('div');
        navToastEl.id = 'tutorial-nav-toast';
        navToastEl.innerHTML = '<i class="fa-solid fa-spinner fa-spin" style="color:var(--primary); font-size:1.1rem;"></i> <span id="tut-nav-toast-msg">Navigating to module...</span>';
        document.body.appendChild(navToastEl);
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
            <span class="tutorial-step-counter" id="tut-counter">Step 1 of 57</span>
            <button type="button" class="tutorial-close-btn" onclick="window.exitTutorial()" title="Exit Tour">&times;</button>
          </div>
          <div class="tutorial-progress-bar">
            <div class="tutorial-progress-fill" id="tut-progress-fill"></div>
          </div>
          <div class="tutorial-card-body">
            <div class="tutorial-meta-row" id="tut-meta-row">
              <span class="tutorial-module-tag" id="tut-module-tag"><i class="fa-solid fa-cube"></i> Module</span>
              <span class="tutorial-section-tag" id="tut-section-tag">Section</span>
            </div>
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
            <div style="display:flex; align-items:center; gap:6px;">
              <button type="button" class="tutorial-skip-btn" onclick="window.exitTutorial()" title="Exit Tour">Exit</button>
              <button type="button" class="tutorial-btn-pause" onclick="window.pauseTutorial()" title="Pause walkthrough to interact freely with page">
                <i class="fa-solid fa-pause"></i> Pause
              </button>
              <button type="button" class="tutorial-btn-pause" onclick="window.skipTutorialModule()" title="Skip rest of this module and jump to next module">
                <i class="fa-solid fa-forward-step"></i> Skip
              </button>
            </div>
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
                You have successfully mastered the entire operational workflow for your role.
              </p>
            </div>
            <div style="padding: 1.75rem 2rem; text-align: left; background: #fff;">
              <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--text-heading); margin: 0 0 0.85rem;">
                <i class="fa-solid fa-circle-check" style="color: #10b981; margin-right: 6px;"></i> Full Website Mastery Achieved:
              </h4>
              <div style="background: #F8FAFC; border: 1px solid var(--border-color); border-radius: 12px; padding: 1rem; font-size: 0.825rem; color: var(--text-main); margin-bottom: 1.5rem; line-height: 1.6;" id="tut-complete-checklist">
                <!-- Injected dynamically -->
              </div>
              <div style="display: flex; gap: 0.65rem; justify-content: flex-end; flex-wrap: wrap;">
                <button type="button" class="btn btn-outline" onclick="window.restartTutorial()" style="font-weight: 700; border-radius: 8px;">
                  <i class="fa-solid fa-rotate-right"></i> Restart Tutorial
                </button>
                <button type="button" class="btn btn-secondary" onclick="window.exitTutorial(true)" style="font-weight: 700; border-radius: 8px;">
                  <i class="fa-solid fa-book-open"></i> User Manual
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
   * Start or Restart Interactive Tutorial
   */
  window.startInteractiveTutorial = function(forcedRoleKey) {
    ensureTutorialDOM();

    activeRoleKey = forcedRoleKey || detectRoleKey();
    activeSteps = TUTORIAL_ROLES[activeRoleKey] || TUTORIAL_ROLES.counselor;
    currentStepIndex = 0;
    isTourActive = true;
    isTourPaused = false;

    // Check if step 0 requires navigating to dashboard
    const firstStep = activeSteps[0];
    if (firstStep && firstStep.url && !isSamePage(firstStep.url)) {
      saveTutorialState({
        active: true,
        role: activeRoleKey,
        stepIndex: 0,
        isPaused: false
      });
      showNavToast('Navigating to Admissions Cockpit...');
      window.location.href = getAppUrl(firstStep.url);
      return;
    }

    // Close any other open modals
    if (typeof closeModal === 'function') {
      ['quickLeadModal', 'globalLogCallModal', 'advanceStageModal', 'userManualModal', 'staffModal'].forEach(id => closeModal(id));
    }

    // Hide resume pill if visible
    if (resumePillEl) resumePillEl.style.display = 'none';

    // Show overlays
    overlayEl.classList.add('active');
    spotlightEl.style.display = 'block';
    popoverEl.style.display = 'flex';
    if (completionModalEl) completionModalEl.classList.remove('active');

    renderStep(0);
  };

  /**
   * Pause Tutorial (Allows user to click around and test interface)
   */
  window.pauseTutorial = function() {
    if (!isTourActive) return;
    isTourPaused = true;

    // Save paused state
    saveTutorialState({
      active: true,
      role: activeRoleKey,
      stepIndex: currentStepIndex,
      isPaused: true
    });

    // Hide highlight, spotlight & popover
    if (currentHighlightedElement) {
      currentHighlightedElement.classList.remove('tutorial-highlighted-element');
    }
    if (overlayEl) overlayEl.classList.remove('active');
    if (spotlightEl) spotlightEl.style.display = 'none';
    if (popoverEl) {
      popoverEl.classList.remove('visible');
      popoverEl.style.display = 'none';
    }

    // Show floating Resume pill
    if (resumePillEl) {
      const step = activeSteps[currentStepIndex];
      const resumeText = document.getElementById('tut-resume-text');
      if (resumeText) {
        resumeText.textContent = `Resume Tutorial • Step ${currentStepIndex + 1} of ${activeSteps.length}`;
      }
      resumePillEl.style.display = 'flex';
    }
  };

  /**
   * Resume Tutorial from Paused state
   */
  window.resumeTutorial = function() {
    isTourPaused = false;
    isTourActive = true;

    if (resumePillEl) resumePillEl.style.display = 'none';

    // Check if we are on the page matching current step
    const step = activeSteps[currentStepIndex];
    if (step && step.url && !isSamePage(step.url)) {
      saveTutorialState({
        active: true,
        role: activeRoleKey,
        stepIndex: currentStepIndex,
        isPaused: false
      });
      showNavToast(`Navigating to ${step.module}...`);
      window.location.href = getAppUrl(step.url);
      return;
    }

    // Restore overlays and render step
    overlayEl.classList.add('active');
    spotlightEl.style.display = 'block';
    popoverEl.style.display = 'flex';
    renderStep(currentStepIndex);
  };

  /**
   * Show Navigation Toast during page transitions
   */
  function showNavToast(message) {
    ensureTutorialDOM();
    const msgEl = document.getElementById('tut-nav-toast-msg');
    if (msgEl) msgEl.textContent = message || 'Navigating to next module...';
    if (navToastEl) navToastEl.style.display = 'flex';
  }

  function hideNavToast() {
    if (navToastEl) navToastEl.style.display = 'none';
  }

  /**
   * Navigate to specific step (Handles inter-page navigation automatically)
   */
  function goToStep(index) {
    if (index < 0) return;
    
    if (index >= activeSteps.length) {
      showCompletion();
      return;
    }

    const nextStep = activeSteps[index];

    // If next step requires a different page, save state and navigate!
    if (nextStep && nextStep.url && !isSamePage(nextStep.url)) {
      saveTutorialState({
        active: true,
        role: activeRoleKey,
        stepIndex: index,
        isPaused: false
      });

      // Show smooth transition indicator
      showNavToast(`Navigating to ${nextStep.module}...`);

      // Execute browser navigation
      setTimeout(() => {
        window.location.href = getAppUrl(nextStep.url);
      }, 150);
      return;
    }

    // If on the same page, render immediately
    renderStep(index);
  }

  /**
   * Render specific step on current page
   */
  function renderStep(index) {
    if (index < 0 || index >= activeSteps.length) {
      if (index >= activeSteps.length) showCompletion();
      return;
    }

    currentStepIndex = index;
    const step = activeSteps[index];

    // Save active state to localStorage
    saveTutorialState({
      active: true,
      role: activeRoleKey,
      stepIndex: index,
      isPaused: false
    });

    // Execute optional before-action hook (e.g. open an accordion or modal)
    if (typeof step.actionBefore === 'function') {
      try {
        step.actionBefore();
      } catch (e) {}
    }

    // Find target element with fallback
    let targetEl = null;
    if (step.target) {
      targetEl = document.querySelector(step.target);
    }
    if (!targetEl && step.fallback) {
      targetEl = document.querySelector(step.fallback);
    }
    if (!targetEl) {
      targetEl = document.querySelector('.page-header') || document.querySelector('.content-area') || document.body;
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
    
    // Module & Section Badges
    const modTag = document.getElementById('tut-module-tag');
    if (modTag) {
      modTag.innerHTML = `<i class="fa-solid fa-cube"></i> ${step.module || 'Overview'}`;
    }
    const secTag = document.getElementById('tut-section-tag');
    if (secTag) {
      secTag.textContent = step.section || 'General';
    }

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
    }, 180);
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
    const popoverWidth = Math.min(440, window.innerWidth - 32);
    const popoverHeight = popoverEl.offsetHeight || 310;
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
    goToStep(currentStepIndex + 1);
  };

  window.prevTutorialStep = function() {
    goToStep(currentStepIndex - 1);
  };

  window.skipTutorialModule = function() {
    if (!activeSteps || !activeSteps[currentStepIndex]) return;
    const currentMod = activeSteps[currentStepIndex].module;
    let nextModIndex = currentStepIndex + 1;
    while (nextModIndex < activeSteps.length && activeSteps[nextModIndex].module === currentMod) {
      nextModIndex++;
    }
    goToStep(nextModIndex);
  };

  window.restartTutorial = function() {
    if (completionModalEl) completionModalEl.classList.remove('active');
    clearTutorialState();
    window.startInteractiveTutorial(activeRoleKey);
  };

  window.exitTutorial = function(openManualAfter) {
    isTourActive = false;
    isTourPaused = false;
    clearTutorialState();

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
    if (resumePillEl) resumePillEl.style.display = 'none';
    if (completionModalEl) completionModalEl.classList.remove('active');
    hideNavToast();

    if (openManualAfter && typeof openUserManualModal === 'function') {
      openUserManualModal();
    }
  };

  /**
   * Display Completion Dialog
   */
  function showCompletion() {
    isTourActive = false;
    isTourPaused = false;
    clearTutorialState();

    if (currentHighlightedElement) {
      currentHighlightedElement.classList.remove('tutorial-highlighted-element');
      currentHighlightedElement = null;
    }
    if (spotlightEl) spotlightEl.style.display = 'none';
    if (popoverEl) {
      popoverEl.classList.remove('visible');
      popoverEl.style.display = 'none';
    }
    if (resumePillEl) resumePillEl.style.display = 'none';

    const msgEl = document.getElementById('tut-complete-role-msg');
    const checklistEl = document.getElementById('tut-complete-checklist');

    if (activeRoleKey === 'super_admin') {
      msgEl.textContent = "You have completed the full 16-module institutional tour! You\'re fully prepared to govern users, permissions, financial ledgers, and student admissions.";
      checklistEl.innerHTML = `
        <div style="margin-bottom:6px;"><strong>1. Granular RBAC:</strong> Govern 16 modules under <em>Roles & Permissions</em>.</div>
        <div style="margin-bottom:6px;"><strong>2. Financial Ledgers:</strong> Monitor receivables and payments under <em>Finance & Invoices</em>.</div>
        <div style="margin-bottom:6px;"><strong>3. Multi-Campus Triage:</strong> Distribute digital web leads and campus expo pools.</div>
        <div><strong>4. Audit Logs:</strong> Inspect system activity logs and compliance soft-delete archives.</div>
      `;
    } else if (activeRoleKey === 'manager') {
      msgEl.textContent = "You\'re all set to manage counselor workloads, triage fresh digital inbound leads, and supervise daily follow-ups across your branch.";
      checklistEl.innerHTML = `
        <div style="margin-bottom:6px;"><strong>1. Morning Triage:</strong> Allocate fresh digital leads under <em>Lead Inbound & Triage</em>.</div>
        <div style="margin-bottom:6px;"><strong>2. SLA Tracking:</strong> Maintain zero overdue calls in <em>Daily Operational Task Queues</em>.</div>
        <div style="margin-bottom:6px;"><strong>3. Caseload Balance:</strong> Use <em>Bulk Reassign</em> on Student Leads whenever needed.</div>
        <div><strong>4. Performance Yields:</strong> Review counselor call counts and conversion rates under <em>Reports</em>.</div>
      `;
    } else {
      msgEl.textContent = "You\'re all set to consult students, log outcomes on time, and advance applicants through the 6 admission milestones.";
      checklistEl.innerHTML = `
        <div style="margin-bottom:6px;"><strong>1. Review Calls:</strong> Start your morning with <em>Today\'s Action Call List</em>.</div>
        <div style="margin-bottom:6px;"><strong>2. 1-Click Outreach:</strong> Use the green WhatsApp icon to message candidates instantly.</div>
        <div style="margin-bottom:6px;"><strong>3. Mandatory Log Call:</strong> Record notes and schedule the next follow-up appointment date after every call.</div>
        <div><strong>4. Milestone Advance:</strong> Move applicants through the 6 stages as they submit papers and receive offers.</div>
      `;
    }

    if (completionModalEl) completionModalEl.classList.add('active');
  }

  /**
   * Keep highlight aligned on window resize & scroll
   */
  let repositionTimeout = null;
  function handleReposition() {
    if (!isTourActive || isTourPaused || !currentHighlightedElement) return;
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
    if (!isTourActive || isTourPaused) return;
    if (['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName)) return;

    if (e.key === 'ArrowRight' || e.key === 'Enter') {
      e.preventDefault();
      window.nextTutorialStep();
    } else if (e.key === 'ArrowLeft') {
      e.preventDefault();
      window.prevTutorialStep();
    } else if (e.key === 'Escape') {
      e.preventDefault();
      window.exitTutorial();
    } else if (e.key === ' ' || e.code === 'Space') {
      e.preventDefault();
      window.pauseTutorial();
    }
  });

  /**
   * Auto-resume tutorial on page load if active in localStorage
   */
  document.addEventListener('DOMContentLoaded', () => {
    // 1. Check if explicit ?start_tour=1 URL query parameter exists
    if (window.location.search.includes('start_tour=1')) {
      try {
        const cleanUrl = window.location.pathname;
        window.history.replaceState({}, '', cleanUrl);
      } catch(e) {}

      setTimeout(() => {
        window.startInteractiveTutorial();
      }, 350);
      return;
    }

    // 2. Check persistent state across multi-page navigation
    const savedState = loadTutorialState();
    if (savedState && savedState.active) {
      activeRoleKey = savedState.role || detectRoleKey();
      activeSteps = TUTORIAL_ROLES[activeRoleKey] || TUTORIAL_ROLES.counselor;
      currentStepIndex = savedState.stepIndex || 0;

      // Smart alignment: verify if step matches current page, or align to current page if user navigated manually
      const currentStep = activeSteps[currentStepIndex];
      if (currentStep && currentStep.url && !isSamePage(currentStep.url)) {
        const matchIdx = activeSteps.findIndex(s => s.url && isSamePage(s.url));
        if (matchIdx !== -1) {
          currentStepIndex = matchIdx;
        }
      }

      ensureTutorialDOM();

      if (savedState.isPaused) {
        // Show floating resume pill
        isTourActive = true;
        isTourPaused = true;
        if (resumePillEl) {
          const resumeText = document.getElementById('tut-resume-text');
          if (resumeText) {
            resumeText.textContent = `Resume Tutorial • Step ${currentStepIndex + 1} of ${activeSteps.length}`;
          }
          resumePillEl.style.display = 'flex';
        }
      } else {
        // Resume active tour on the newly loaded page
        isTourActive = true;
        isTourPaused = false;
        overlayEl.classList.add('active');
        spotlightEl.style.display = 'block';
        popoverEl.style.display = 'flex';

        setTimeout(() => {
          renderStep(currentStepIndex);
        }, 350);
      }
    }
  });

})();
