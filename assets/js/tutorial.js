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

    // Normalize paths: decode URI components, remove trailing slash, lowercase, remove /index.php
    const cleanCurrent = decodeURIComponent(currentA.pathname.toLowerCase()).replace(/\/index\.php$/, '').replace(/\/+$/, '');
    const cleanTarget = decodeURIComponent(targetA.pathname.toLowerCase()).replace(/\/index\.php$/, '').replace(/\/+$/, '');

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
  "super_admin": [
    {
      "url": "/dashboard/index.php",
      "target": "#top-navbar",
      "fallback": ".navbar-right",
      "module": "Admissions Cockpit",
      "section": "Global Command Header",
      "title": "Institutional Cockpit & System Navigation",
      "badge": "Super Admin • Global Command",
      "icon": "fa-crown",
      "description": "Welcome to your complete interactive master training walkthrough! As Super Admin, you govern all 16 CRM modules across all university branches. From this top command header, you have immediate access to the 1-Click Role Switcher, real-time alert notifications, and interactive system guidance anytime.",
      "tip": "Click the Guide button anytime on any screen to launch interactive training or refresh workflows.",
      "position": "bottom"
    },
    {
      "url": "/dashboard/index.php",
      "target": "#role-switcher-btn",
      "fallback": "#top-navbar",
      "module": "Admissions Cockpit",
      "section": "Persona Simulation",
      "title": "Instant Role Switcher Dropdown",
      "badge": "Role Simulation",
      "icon": "fa-shuffle",
      "description": "Test, audit, and verify system behavior across all staff personas with a single click: Dr. Arthur Vance (Super Admin), Marcus Sterling (Manager), David Miller (Counselor), or Sarah Jenkins (Advisor). Validates role permissions, caseload boundaries, and screen access in real time.",
      "tip": "Switch to Admissions Counselor anytime to verify exactly what assigned staff see on their screens.",
      "position": "bottom"
    },
    {
      "url": "/dashboard/index.php",
      "target": "#btn-quick-add-lead",
      "fallback": ".page-header",
      "module": "Admissions Cockpit",
      "section": "Rapid Lead Capture",
      "title": "15-Second Quick Lead Capture Modal",
      "badge": "Instant Ingestion",
      "icon": "fa-bolt",
      "description": "Rapidly intake walk-in students or phone inquiries without navigating away from your dashboard. Automatically generates a unique tracking code (EDR-YYYY-XXXX), normalizes phone numbers to prevent duplicates, and routes the student directly to an assigned counselor.",
      "tip": "Enter candidate name, phone number, study country, and next follow-up in under 15 seconds.",
      "position": "bottom"
    },
    {
      "url": "/dashboard/index.php",
      "target": "#btn-generate-invoice",
      "fallback": "#widget-4step-workflow",
      "module": "Admissions Cockpit",
      "section": "Financial Quick Action",
      "title": "Instant Service Invoicing Button",
      "badge": "Financial Governance",
      "icon": "fa-file-invoice-dollar",
      "description": "Generate official institutional consultancy service invoices, university application processing fees, or visa handling charges. Connects directly to the student ledger with automatic 18% GST calculation and downloadable client PDF receipts.",
      "tip": "Click here whenever an enrolled applicant makes a fee payment or requires an institutional bill.",
      "position": "bottom"
    },
    {
      "url": "/dashboard/index.php",
      "target": "#widget-4step-workflow",
      "fallback": ".content-area",
      "module": "Admissions Cockpit",
      "section": "Process Lifecycle",
      "title": "Standard 4-Step Daily Admissions Workflow",
      "badge": "Operational Pipeline",
      "icon": "fa-route",
      "description": "The standardized sequence enforced across all campuses: (1) Capture Inquiry -> (2) Call & WhatsApp Outreach -> (3) Advance Milestones -> (4) Invoicing & Fee Documentation. All branch staff members follow this standardized pathway to maximize conversion yields.",
      "tip": "Click any step card anytime to jump straight into the corresponding operational surface.",
      "position": "bottom"
    },
    {
      "url": "/dashboard/index.php",
      "target": "#widget-action-calls",
      "fallback": "#widget-metric-cards",
      "module": "Admissions Cockpit",
      "section": "Daily Priority Queue",
      "title": "Today's Action Call List & 1-Click Outreach",
      "badge": "Outreach Engine",
      "icon": "fa-phone-volume",
      "description": "Active candidates scheduled for consultation today across the company. Features instant 1-click WhatsApp Web messaging with pre-filled candidate greetings, direct telephone dialing, and mandatory post-call outcome logging.",
      "tip": "Supervise this table to ensure counselors maintain 100% daily follow-up compliance.",
      "position": "top"
    },
    {
      "url": "/dashboard/index.php",
      "target": "#widget-action-calls a[href*=\"wa.me\"]",
      "fallback": "#widget-action-calls",
      "module": "Admissions Cockpit",
      "section": "WhatsApp Outreach",
      "title": "1-Click WhatsApp Candidate Messaging",
      "badge": "Candidate Outreach",
      "icon": "fa-brands fa-whatsapp",
      "description": "Click the green WhatsApp icon to launch WhatsApp Web with a personalized pre-filled greeting. Enables counselors to connect with candidates instantly without typing phone numbers.",
      "tip": "WhatsApp follow-ups achieve significantly higher response rates than email consultations.",
      "position": "top"
    },
    {
      "url": "/dashboard/index.php",
      "target": "#widget-action-calls button",
      "fallback": "#widget-action-calls",
      "module": "Admissions Cockpit",
      "section": "Outcome Compliance",
      "title": "Mandatory: Log Consultation Outcome",
      "badge": "Compliance Logging",
      "icon": "fa-check",
      "description": "Immediately after every conversation, click Log Outcome. Record what was discussed, select the interaction title, and set the next follow-up date (Tomorrow, +2 Days, Next Week).",
      "tip": "Logging an outcome automatically clears completed items from the active queue and logs an audit trail.",
      "position": "top"
    },
    {
      "url": "/dashboard/index.php",
      "target": "#widget-metric-cards",
      "fallback": ".content-area",
      "module": "Admissions Cockpit",
      "section": "Executive Analytics",
      "title": "Institutional KPI Cards & Conversion Metrics",
      "badge": "Real-Time Telemetry",
      "icon": "fa-chart-line",
      "description": "Live performance metrics tracking Total Active Student Leads, Invoiced Clients (with conversion percentage rate), Total Collected Revenue, and Outstanding Receivables across all university campuses.",
      "tip": "Revenue and balance figures are computed directly from MySQL ledgers and update instantly upon payment receipt.",
      "position": "top"
    },
    {
      "url": "/dashboard/index.php",
      "target": "#widget-task-queues",
      "fallback": ".content-area",
      "module": "Admissions Cockpit",
      "section": "SLA Task Enforcement",
      "title": "4 Daily Operational Task Queues",
      "badge": "Zero-Lead-Abandonment",
      "icon": "fa-list-check",
      "description": "Four prioritized queues: (1) Scheduled Follow-ups (Today's Calls), (2) Pending Follow-ups (Overdue Calls), (3) Today Leads (Fresh inquiries assigned today), and (4) Pending Leads (Uncontacted older inquiries). Guarantees zero lead abandonment across the company.",
      "tip": "A red badge in the Overdue queue indicates delayed consultations that require managerial intervention.",
      "position": "top"
    },
    {
      "url": "/dashboard/index.php",
      "target": "#widget-pipeline-chart",
      "fallback": "#widget-recent-leads",
      "module": "Admissions Cockpit",
      "section": "Funnel Analytics",
      "title": "6-Stage Admission Pipeline Funnel",
      "badge": "Stage Progression",
      "icon": "fa-diagram-project",
      "description": "Interactive analytics tracking student distribution across the 6 official admission stages: ENQUIRY -> CUSTOMER -> APPLICATION -> OFFER RECEIVED -> TUITION FEES PAID -> ENROLLED.",
      "tip": "Click \"Kanban ->\" anytime to open the visual drag-and-drop board.",
      "position": "top"
    },
    {
      "url": "/dashboard/index.php",
      "target": "#widget-branch-chart",
      "fallback": "#widget-recent-leads",
      "module": "Admissions Cockpit",
      "section": "Campus Yields",
      "title": "Regional Branch Intake Distribution",
      "badge": "Multi-Campus Telemetry",
      "icon": "fa-chart-pie",
      "description": "Doughnut chart comparing student recruitment yield and revenue collection across regional branches (Main Admissions Division vs Global Advisory Division).",
      "tip": "Identify high-performing regional hubs and reallocate marketing budgets accordingly.",
      "position": "top"
    },
    {
      "url": "/dashboard/index.php",
      "target": "#widget-recent-leads",
      "fallback": ".content-area",
      "module": "Admissions Cockpit",
      "section": "Recent Inquiries",
      "title": "Recent Student Inquiries & Activity Feed",
      "badge": "Live Activity",
      "icon": "fa-clock-rotate-left",
      "description": "Recent student registrations across all divisions with tracking codes, target countries, and assigned counselors. Alongside is the live Audit Feed recording user logins, status modifications, and stage advancements.",
      "tip": "Click \"Dossier ->\" on any student row to inspect their complete academic profile.",
      "position": "top"
    },
    {
      "url": "/leads/index.php",
      "target": ".page-header",
      "fallback": "#main-wrapper",
      "module": "Student Leads",
      "section": "Directory Master",
      "title": "Student Leads Directory & Global Repository",
      "badge": "Central Database",
      "icon": "fa-user-graduate",
      "description": "Welcome to the central Student Leads repository! Here you manage all prospective candidate inquiries across all stages. Notice the action buttons on the top right: Export CSV (strictly restricted from counselors, permitted for administrators) and Bulk Reassign.",
      "tip": "As Super Admin, you have global visibility across all branches and counselor caseloads.",
      "position": "bottom"
    },
    {
      "url": "/leads/index.php",
      "target": "div[style*=\"overflow-x:auto\"]",
      "fallback": ".page-header",
      "module": "Student Leads",
      "section": "Stage Filter Tabs",
      "title": "6 Admission Stage Filter Tabs",
      "badge": "One-Click Filtering",
      "icon": "fa-tags",
      "description": "Instantly filter your entire lead pool by stage. Click \"All Stages\" for global view, or isolate candidates in Enquiry, Customer, Application, Offer Received, Tuition Fees Paid, or Enrolled with live count badges.",
      "tip": "The numbers inside each pill reflect live database counts matching your current filter scope.",
      "position": "bottom"
    },
    {
      "url": "/leads/index.php",
      "target": "input[name=\"search\"]",
      "fallback": ".card",
      "module": "Student Leads",
      "section": "Deep Discovery",
      "title": "Universal Candidate Search Bar",
      "badge": "Fast Lookup",
      "icon": "fa-magnifying-glass",
      "description": "Search prospective students in real time by student name, tracking ID (e.g. EDR-2026-0001), phone number, email address, or previous college/university name.",
      "tip": "Type any partial keyword and press Enter or click Apply to execute.",
      "position": "bottom"
    },
    {
      "url": "/leads/index.php",
      "target": "select[name=\"priority\"]",
      "fallback": ".card form",
      "module": "Student Leads",
      "section": "Priority Filter",
      "title": "Lead Priority Filter Dropdown",
      "badge": "Urgency Triage",
      "icon": "fa-fire",
      "description": "Filter leads by urgency: High Priority (urgent intake), Moderate Priority (standard inquiries), or Low Priority (future term prospects).",
      "tip": "Encourage counselors to attend to High Priority leads before lunch every day.",
      "position": "bottom"
    },
    {
      "url": "/leads/index.php",
      "target": "select[name=\"source\"]",
      "fallback": ".card form",
      "module": "Student Leads",
      "section": "Acquisition Channels",
      "title": "Lead Acquisition Source Filter",
      "badge": "Marketing Attribution",
      "icon": "fa-bullhorn",
      "description": "Filter candidates by origin: Meta Ads, Google Ads, Website Form, Walk-in, College Fair Data, or Referral. Helps measure marketing campaign performance.",
      "tip": "Combine source filtering with stage filtering to evaluate channel conversion rates.",
      "position": "bottom"
    },
    {
      "url": "/leads/index.php",
      "target": "select[name=\"branch_id\"]",
      "fallback": ".card form",
      "module": "Student Leads",
      "section": "Branch Scope",
      "title": "Campus & Branch Filter Dropdown",
      "badge": "Campus Filter",
      "icon": "fa-building",
      "description": "Switch between Main Admissions Division, Global Advisory Division, or All Branches. Restricts the directory table to show only students belonging to the selected campus.",
      "tip": "Super Admin can inspect any branch; counselors are strictly restricted to their own.",
      "position": "bottom"
    },
    {
      "url": "/leads/index.php",
      "target": "select[name=\"counselor_id\"]",
      "fallback": ".card form",
      "module": "Student Leads",
      "section": "Staff Caseloads",
      "title": "Counselor Caseload Selector",
      "badge": "Caseload Audit",
      "icon": "fa-users",
      "description": "Filter student records by individual counselor. The number beside each counselor indicates their active student count, making it easy to identify overloaded staff.",
      "tip": "Select any counselor to review their follow-up compliance and student progression.",
      "position": "bottom"
    },
    {
      "url": "/leads/index.php",
      "target": "a[href*=\"export=csv\"]",
      "fallback": ".page-header",
      "module": "Student Leads",
      "section": "Data Export",
      "title": "Privileged CSV Data Export Button",
      "badge": "Security Governance",
      "icon": "fa-file-csv",
      "description": "Export authorized student data rosters into formatted CSV spreadsheets. Role permissions strictly block counselors from exporting, ensuring student database privacy.",
      "tip": "Exports respect all active filters applied in the search bar above.",
      "position": "bottom"
    },
    {
      "url": "/leads/index.php",
      "target": "button[onclick*=\"openBulkReassignModal\"]",
      "fallback": ".page-header",
      "module": "Student Leads",
      "section": "Caseload Balancing",
      "title": "Bulk Reassign Leads Between Staff",
      "badge": "Workload Rebalancing",
      "icon": "fa-users-gear",
      "description": "Rebalance student caseloads when a counselor goes on leave or shifts territories. Select candidate checkboxes in the table and reassign them in bulk with automated notifications.",
      "tip": "Reassigning leads transfers all follow-up tasks and history seamlessly to the new counselor.",
      "position": "bottom"
    },
    {
      "url": "/leads/index.php",
      "target": "table.data-table",
      "fallback": ".card",
      "module": "Student Leads",
      "section": "Data Grid",
      "title": "Lead Data Table & Action Menu",
      "badge": "Comprehensive Dossier",
      "icon": "fa-table-list",
      "description": "Structured student records displaying tracking Code, Name, Phone (with 1-click WhatsApp dialer), Target Study Program & Country, Stage status, Priority badge, assigned Counselor, and Branch. Click \"Dossier ->\" on any student to open their complete academic dossier.",
      "tip": "Select checkboxes on the left to perform bulk operations such as reassigning multiple leads at once.",
      "position": "top"
    },
    {
      "url": "/leads/add.php",
      "target": ".page-header",
      "fallback": "#main-wrapper",
      "module": "Register Student Lead",
      "section": "Form Overview",
      "title": "Student Registration Master Interface",
      "badge": "Applicant Intake",
      "icon": "fa-user-plus",
      "description": "Register prospective student leads with complete academic and counseling metadata. Designed for high operational speed: essential contact fields are mandatory, while academic and test scores can be completed now or updated later in the student dossier.",
      "tip": "Click \"1-Click Fast Add\" in the top right to open the rapid 15-second intake modal anytime.",
      "position": "bottom"
    },
    {
      "url": "/leads/add.php",
      "target": "input[name=\"full_name\"]",
      "fallback": ".card:first-of-type",
      "module": "Register Student Lead",
      "section": "Essential Details",
      "title": "Candidate Name & Normalized Phone",
      "badge": "Mandatory Fields",
      "icon": "fa-id-card",
      "description": "Enter candidate Full Name, Phone/WhatsApp number, and Email Address. Phone numbers are automatically sanitized and normalized to international format (E.164) with automatic duplicate validation preventing duplicate student entries.",
      "tip": "If a duplicate phone number is detected in active leads, the system blocks insertion with an alert.",
      "position": "bottom"
    },
    {
      "url": "/leads/add.php",
      "target": "select[name=\"preferred_country\"]",
      "fallback": ".card:first-of-type",
      "module": "Register Student Lead",
      "section": "Study Destination",
      "title": "Target Country & Academic Intake Term",
      "badge": "Study Preferences",
      "icon": "fa-earth-americas",
      "description": "Select the student's desired destination country (UK, Canada, Australia, USA, Ireland, Germany, New Zealand) and target intake session (e.g. September 2026, January 2027) to synchronize application deadlines.",
      "tip": "Selecting target country helps managers route the candidate to country-specialist counselors.",
      "position": "bottom"
    },
    {
      "url": "/leads/add.php",
      "target": "select[name=\"branch_id\"], select[name=\"counselor_id\"]",
      "fallback": ".card:first-of-type",
      "module": "Register Student Lead",
      "section": "Caseload Routing",
      "title": "Branch & Counselor Allocation",
      "badge": "Institutional Hierarchy",
      "icon": "fa-users-gear",
      "description": "Assign the candidate to an operational campus division and designated counselor. The counselor will immediately receive a notification in their task queue, and the lead will appear in their daily action list.",
      "tip": "Super Admin can assign to any branch; managers are scoped to their own campus.",
      "position": "bottom"
    },
    {
      "url": "/leads/add.php",
      "target": "details:nth-of-type(1)",
      "fallback": ".card:nth-of-type(2)",
      "module": "Register Student Lead",
      "section": "Academic & Tests",
      "title": "Academic History & English Proficiency (IELTS/PTE)",
      "badge": "Qualification Profile",
      "icon": "fa-graduation-cap",
      "description": "Expandable section to record student educational history: Highest Qualification, College Name, Passing Year, CGPA/Percentage, Active Backlog counts, Work Experience, and standardized English scores (IELTS, PTE, TOEFL, Duolingo) with individual band breakdowns.",
      "tip": "This section is optional during initial intake and can be filled when the student submits transcripts.",
      "position": "bottom"
    },
    {
      "url": "/leads/add.php",
      "target": "textarea[name=\"notes\"]",
      "fallback": "button[type=\"submit\"]",
      "module": "Register Student Lead",
      "section": "Counselor Notes",
      "title": "Initial Counseling Remarks & Budget",
      "badge": "Consultation Notes",
      "icon": "fa-pen-to-square",
      "description": "Record initial counseling remarks: student career aspirations, university preferences, budget constraints, and pending document requirements.",
      "tip": "Initial notes appear on the dashboard action call list to prepare counselors before subsequent calls.",
      "position": "top"
    },
    {
      "url": "/leads/add.php",
      "target": "button[type=\"submit\"], .btn-primary",
      "fallback": ".card:last-of-type",
      "module": "Register Student Lead",
      "section": "Submission",
      "title": "Register Lead & Trigger Stage History",
      "badge": "Data Persistence",
      "icon": "fa-circle-check",
      "description": "Click \"Register Student Lead\" to execute a database transaction that: (1) creates the lead record, (2) generates the unique EDR tracking code, (3) logs initial stage history to \"ENQUIRY\", and (4) notifies the assigned counselor.",
      "tip": "Upon submission, you are automatically redirected to the student's complete academic dossier.",
      "position": "top"
    },
    {
      "url": "/leads/view.php",
      "target": ".card:first-of-type",
      "fallback": "#main-wrapper",
      "module": "Student Dossier & Profile",
      "section": "360° Profile Overview",
      "title": "Student Dossier & 360-Degree Profile",
      "badge": "Comprehensive Dossier",
      "icon": "fa-address-card",
      "description": "Welcome to the 360-Degree Student Dossier! Every candidate's academic credentials, contact information, passport details, test scorecards, consultation notes, document uploads, and billing history are unified here on a single comprehensive operational surface.",
      "tip": "Use the quick action buttons on top to log calls, advance admission stages, or issue fee invoices.",
      "position": "bottom"
    },
    {
      "url": "/leads/view.php",
      "target": "button[onclick*=\"openAdvanceStageModal\"], button:has(.fa-stairs)",
      "fallback": ".card:first-of-type",
      "module": "Student Dossier & Profile",
      "section": "Milestone Advancement",
      "title": "Advance Milestone Action Button",
      "badge": "Stage Transition",
      "icon": "fa-forward-step",
      "description": "Click \"Advance Stage\" to progress this student through the 6 standardized consultancy milestones (Enquiry -> Customer -> Application -> Offer Received -> Tuition Fees Paid -> Enrolled). Prompts for consultation remarks and automatically logs a timestamped milestone audit record.",
      "tip": "Advancing to \"Customer\" triggers client billing readiness in the Finance module.",
      "position": "bottom"
    },
    {
      "url": "/leads/view.php",
      "target": "button[onclick*=\"openLogCallModal\"], button:has(.fa-phone-volume)",
      "fallback": ".card:first-of-type",
      "module": "Student Dossier & Profile",
      "section": "Interaction Logging",
      "title": "Log Consultation Call & Note",
      "badge": "Counseling Log",
      "icon": "fa-phone",
      "description": "Record telephone calls, WhatsApp discussions, and in-person counseling sessions directly into the student history. Tag discussion categories (e.g. Visa Guidance, University Selection, Fee Payment) and schedule the next follow-up date.",
      "tip": "Setting a next follow-up date automatically populates the counselor daily tasks queue.",
      "position": "bottom"
    },
    {
      "url": "/leads/view.php",
      "target": ".card:first-of-type",
      "fallback": ".content-area",
      "module": "Student Dossier & Profile",
      "section": "Academic Credentials",
      "title": "Academic Scorecard & English Tests",
      "badge": "Scorecard Verification",
      "icon": "fa-graduation-cap",
      "description": "Inspect the applicant's educational qualification, college background, graduation percentage, backlogs count, and standardized test scores (IELTS/PTE bands: Listening, Reading, Writing, Speaking).",
      "tip": "Counselors reference this scorecard when matching students to university eligibility criteria.",
      "position": "top"
    },
    {
      "url": "/leads/view.php",
      "target": "div[style*=\"border-top\"], .card:last-of-type",
      "fallback": ".content-area",
      "module": "Student Dossier & Profile",
      "section": "Dossier History",
      "title": "Interaction History & Audit Trail",
      "badge": "Chronological Audit",
      "icon": "fa-timeline",
      "description": "Chronological history of every interaction logged with this student: counselor notes, follow-up dates, milestone transitions, and staff signatures. Ensures full accountability across team handovers.",
      "tip": "When reassigning leads, the incoming counselor reviews this chronological timeline.",
      "position": "top"
    },
    {
      "url": "/tasks/index.php",
      "target": ".page-header",
      "fallback": "#main-wrapper",
      "module": "Daily Follow-ups",
      "section": "SLA Engine",
      "title": "Daily Operational Work Queues",
      "badge": "Follow-Up Compliance",
      "icon": "fa-list-check",
      "description": "The daily operational workbench for counselors and managers. Features 4 strict SLA queues designed to ensure 0% lead abandonment: Scheduled Follow-ups, Pending Overdue Calls, Today's Fresh Leads, and Older Uncontacted Leads.",
      "tip": "Counselors should clear all Overdue and Scheduled calls before beginning new prospect consultations.",
      "position": "bottom"
    },
    {
      "url": "/tasks/index.php",
      "target": "a[href*=\"queue=pending_followup\"], .card:nth-of-type(2)",
      "fallback": ".card",
      "module": "Daily Follow-ups",
      "section": "Overdue Alerting",
      "title": "Pending Follow-ups (Overdue Calls Queue)",
      "badge": "SLA Alert",
      "icon": "fa-triangle-exclamation",
      "description": "Highlights appointments that have exceeded their scheduled date without resolution. Displays overdue lag days in red to pinpoint neglected inquiries.",
      "tip": "Managers monitor this queue daily to maintain zero delayed inquiries across the team.",
      "position": "top"
    },
    {
      "url": "/tasks/index.php",
      "target": "a[href*=\"queue=today_leads\"], .card:nth-of-type(3)",
      "fallback": ".card",
      "module": "Daily Follow-ups",
      "section": "Fresh Leads Queue",
      "title": "Today's Assigned Leads Queue",
      "badge": "Immediate Action",
      "icon": "fa-bolt",
      "description": "New student inquiries assigned to staff today that haven't been contacted yet. Fast initial outreach (under 15 minutes) dramatically improves candidate trust and admission conversions.",
      "tip": "Call fresh leads immediately upon assignment to establish rapport before competitors.",
      "position": "top"
    },
    {
      "url": "/tasks/index.php",
      "target": "table.data-table",
      "fallback": ".card",
      "module": "Daily Follow-ups",
      "section": "Outreach & Logging",
      "title": "Action List with 1-Click WhatsApp & Outcome Logging",
      "badge": "Instant Outreach",
      "icon": "fa-phone-volume",
      "description": "Contact candidates via direct telephone call or 1-click WhatsApp Web. Immediately after the conversation, click \"Log Outcome\" to record consultation notes, select the discussion title, and set the next follow-up date.",
      "tip": "Logging an outcome automatically moves completed tasks out of the active queue.",
      "position": "top"
    },
    {
      "url": "/customers/index.php",
      "target": ".page-header",
      "fallback": "#main-wrapper",
      "module": "Enrolled Clients",
      "section": "Customer Ledger",
      "title": "Enrolled Clients (Invoiced Customers)",
      "badge": "Converted Applicants",
      "icon": "fa-certificate",
      "description": "Candidates who have formally engaged institutional services and received official invoices. Displays complete billing metrics: Total Invoiced, Total Collected Revenue, and Outstanding Receivables.",
      "tip": "Filter by Payment Status (Paid, Partial, Unpaid) to manage fee recovery schedules.",
      "position": "bottom"
    },
    {
      "url": "/customers/index.php",
      "target": "select[name=\"payment_status\"]",
      "fallback": ".card form",
      "module": "Enrolled Clients",
      "section": "Payment Filters",
      "title": "Payment Status Filter & Client Ledgers",
      "badge": "Billing Reconciliation",
      "icon": "fa-money-bill-transfer",
      "description": "Filter clients by billing settlement: Fully Paid, Partial Payment, or Unpaid Balances. Direct links allow downloading receipts or recording fee installments.",
      "tip": "Review partial payments before university tuition remittance deadlines.",
      "position": "bottom"
    },
    {
      "url": "/customers/index.php",
      "target": "table.data-table",
      "fallback": ".card",
      "module": "Enrolled Clients",
      "section": "Clients Roster",
      "title": "Enrolled Clients Data Table",
      "badge": "Converted Clients",
      "icon": "fa-table-list",
      "description": "Detailed client directory with Customer Code, Student Name, Contact Number, Enrolled Country & Program, Total Invoiced Amount, Amount Paid, and Balance Due. Click \"View Profile\" or \"Invoices\" for full financial statements.",
      "tip": "Generate installment payment receipts directly from the action menu on the right.",
      "position": "top"
    },
    {
      "url": "/data/web_leads.php",
      "target": ".page-header",
      "fallback": "#main-wrapper",
      "module": "Digital Web Leads",
      "section": "Digital Ads Triage",
      "title": "Digital Web & Social Inbound Triage",
      "badge": "Lead Allocation",
      "icon": "fa-bullseye",
      "description": "Prospective inquiries captured via website contact forms, Meta Facebook/Instagram Ads, and Google Ads stage here before entering active caseloads. Inspect applicant course preferences and allocate to branch counselors with duplicate phone checks.",
      "tip": "Rapid lead triage (<15 minutes from ad click) yields the highest university conversion rates.",
      "position": "bottom"
    },
    {
      "url": "/data/web_leads.php",
      "target": "table.data-table",
      "fallback": ".card",
      "module": "Digital Web Leads",
      "section": "Inbound Verification",
      "title": "Inbound Lead Verification & Allocation Action",
      "badge": "Caseload Routing",
      "icon": "fa-share-nodes",
      "description": "Review applicant study interests, intake terms, and source campaign. Click \"Allocate ->\" to assign the lead to a branch counselor with duplicate phone protection.",
      "tip": "Allocated leads immediately appear in the assigned counselor's Today Leads queue.",
      "position": "top"
    },
    {
      "url": "/data/web_leads.php",
      "target": "select[name=\"status\"]",
      "fallback": ".card form",
      "module": "Digital Web Leads",
      "section": "Triage Status",
      "title": "Triage Status Filter (Pending vs Allocated)",
      "badge": "Queue Status",
      "icon": "fa-filter",
      "description": "Filter leads by triage lifecycle: Pending (fresh inbound awaiting review) or Allocated (already distributed to counseling staff). Prevents accidental double assignments.",
      "tip": "Managers should ensure the Pending queue is cleared completely twice daily.",
      "position": "bottom"
    },
    {
      "url": "/data/college_data.php",
      "target": ".page-header",
      "fallback": "#main-wrapper",
      "module": "College Data Pool",
      "section": "Campus Fair Ingestion",
      "title": "College Data Pool & Education Fair Rosters",
      "badge": "Bulk Prospect Ingestion",
      "icon": "fa-building-columns",
      "description": "Repository for contact lists gathered during university campus fairs, school outreach, and education expos. Preview student qualification records and convert qualified fair attendees into active CRM leads with 1 click.",
      "tip": "Use \"Import College List\" in the sidebar to upload CSV or Excel files with duplicate phone filtering.",
      "position": "bottom"
    },
    {
      "url": "/data/college_data.php",
      "target": "table.data-table",
      "fallback": ".card",
      "module": "College Data Pool",
      "section": "Lead Promotion",
      "title": "1-Click Convert to Active Inquiry",
      "badge": "Prospect Conversion",
      "icon": "fa-user-check",
      "description": "Click \"Convert ->\" on any student row to promote them from raw campus fair data into an active CRM lead with an assigned counselor and tracking code.",
      "tip": "Filter by degree or college name to convert qualified student batches together.",
      "position": "top"
    },
    {
      "url": "/data/college_data.php",
      "target": "select[name=\"degree\"], select[name=\"college_id\"]",
      "fallback": ".card form",
      "module": "College Data Pool",
      "section": "Data Filters",
      "title": "Degree & College Institution Filters",
      "badge": "Targeted Campaigns",
      "icon": "fa-filter",
      "description": "Filter student rosters by degree qualification (B.Tech, B.Com, B.Sc) or originating college campus to target specialized postgraduate university promotions.",
      "tip": "Export or convert targeted engineering batches for STEM master degree programs abroad.",
      "position": "bottom"
    },
    {
      "url": "/data/import_college_data.php",
      "target": ".page-header",
      "fallback": "#main-wrapper",
      "module": "Import College List",
      "section": "Bulk Ingestion Engine",
      "title": "Import College Student Rosters & CSV Dropzone",
      "badge": "Batch Data Ingestion",
      "icon": "fa-file-import",
      "description": "Upload massive spreadsheets gathered during university fairs, career seminars, or partnering college events. Supports CSV and Excel files with automated duplicate phone deduplication and phone format normalization.",
      "tip": "Download the standard CSV template before uploading to ensure column header alignment.",
      "position": "bottom"
    },
    {
      "url": "/data/import_college_data.php",
      "target": "input[name=\"csv_file\"], .dropzone",
      "fallback": "form",
      "module": "Import College List",
      "section": "File Upload",
      "title": "CSV File Dropzone & Paste Box",
      "badge": "File Ingestion",
      "icon": "fa-cloud-arrow-up",
      "description": "Select your CSV spreadsheet file or paste raw delimited text directly into the box. The parser automatically validates student names, phone numbers, emails, degrees, and passing years.",
      "tip": "Large lists of 5,000+ records are processed in seconds with progress logging.",
      "position": "bottom"
    },
    {
      "url": "/data/import_college_data.php",
      "target": "button[type=\"submit\"], .btn-primary",
      "fallback": ".card",
      "module": "Import College List",
      "section": "Execution",
      "title": "Execute Import & Deduplicate Action",
      "badge": "Batch Processing",
      "icon": "fa-play",
      "description": "Click \"Execute Import & Deduplicate\" to insert valid new prospects into the College Data Pool while automatically skipping any existing phone numbers already present in the CRM.",
      "tip": "A post-import summary reports total records scanned, inserted, and duplicates skipped.",
      "position": "top"
    },
    {
      "url": "/finance/create_invoice.php",
      "target": ".page-header",
      "fallback": "#main-wrapper",
      "module": "Generate Invoice",
      "section": "Invoice Builder",
      "title": "Multi-Line Item Service Invoice Builder",
      "badge": "Official Billing",
      "icon": "fa-receipt",
      "description": "Create customized institutional invoices: select an active student lead, add multiple billable service line items (e.g. University Application Fee, Visa Filing, Document Attestation), specify quantities and rates, and apply discounts with automatic 18% GST calculation.",
      "tip": "All issued invoices automatically update student balance ledgers and executive revenue cards.",
      "position": "bottom"
    },
    {
      "url": "/finance/create_invoice.php",
      "target": "select[name=\"lead_id\"]",
      "fallback": ".card",
      "module": "Generate Invoice",
      "section": "Student Selection",
      "title": "Select Student Lead for Invoicing",
      "badge": "Account Selection",
      "icon": "fa-user-tag",
      "description": "Choose the candidate being invoiced. Once selected, their name, tracking code, and branch are linked to the billing document.",
      "tip": "Search by student name or tracking code in the dropdown picker.",
      "position": "bottom"
    },
    {
      "url": "/finance/create_invoice.php",
      "target": "button[onclick*=\"addItemRow\"]",
      "fallback": "table",
      "module": "Generate Invoice",
      "section": "Line Items Builder",
      "title": "Multi-Line Items Builder with 18% GST",
      "badge": "Itemized Billing",
      "icon": "fa-plus",
      "description": "Add multiple service line items with descriptions, quantities, and rates. The system dynamically computes subtotal, applies discounts, and adds 18% GST to calculate the grand total.",
      "tip": "Click \"+ Add Service Line Item\" to bill multiple services (e.g. Consultation + Visa Filing) on a single invoice.",
      "position": "top"
    },
    {
      "url": "/finance/create_invoice.php",
      "target": "button[type=\"submit\"], .btn-primary",
      "fallback": ".card",
      "module": "Generate Invoice",
      "section": "Issue Invoice",
      "title": "Finalize & Issue Official Invoice",
      "badge": "Tax Invoice",
      "icon": "fa-print",
      "description": "Click \"Issue Invoice\" to generate the immutable invoice number, post billing amounts to the student balance ledger, and generate the downloadable client PDF.",
      "tip": "Clients automatically transition to formal \"Customer\" status upon invoice issuance.",
      "position": "top"
    },
    {
      "url": "/documents/general.php",
      "target": ".page-header",
      "fallback": "#main-wrapper",
      "module": "Country Checklists & Guidelines",
      "section": "Visa SOP Guidelines",
      "title": "Institutional Visa Guidelines & Country Checklists",
      "badge": "Knowledge Base",
      "icon": "fa-passport",
      "description": "Official repository of country-specific visa requirements, financial affidavit formats, and statement of purpose (SOP) guidelines for UK Tier 4, Canada SDS, Australia Subclass 500, and USA F-1 visas.",
      "tip": "Share official checklist PDFs with students to avoid visa application rejections.",
      "position": "bottom"
    },
    {
      "url": "/documents/general.php",
      "target": "button[onclick*=\"openUploadModal\"], button:has(.fa-upload), a[href*=\"upload\"]",
      "fallback": ".page-header",
      "module": "Country Checklists & Guidelines",
      "section": "Guideline Upload",
      "title": "Upload Official Guideline or Checklist PDF",
      "badge": "Staff SOPs",
      "icon": "fa-cloud-arrow-up",
      "description": "Super Admins and Compliance Officers can publish updated embassy guidelines, checklist templates, or university fee schedules for staff reference.",
      "tip": "Keep embassy visa financial requirement PDFs updated ahead of intake seasons.",
      "position": "bottom"
    },
    {
      "url": "/documents/general.php",
      "target": "div[style*=\"grid-template-columns\"], .card",
      "fallback": ".content-area",
      "module": "Country Checklists & Guidelines",
      "section": "Country Hubs",
      "title": "Country Visa Category Hubs",
      "badge": "Global Destinations",
      "icon": "fa-earth-americas",
      "description": "Select country category pills to filter documents for United Kingdom, Canada, Australia, United States, Ireland, and Germany. View or download guidelines with one click.",
      "tip": "Counselors consult these guidelines during student visa mock interviews.",
      "position": "top"
    },
    {
      "url": "/organization/users.php",
      "target": ".page-header",
      "fallback": "#main-wrapper",
      "module": "Staff & Team",
      "section": "Staff Directory",
      "title": "Staff, Counselors & User Management",
      "badge": "Identity & Access",
      "icon": "fa-users",
      "description": "Manage institutional user accounts, counseling staff, branch managers, and administrative credentials. Regulate active account status, assign staff to specific campus branches, and supervise active student caseload counts.",
      "tip": "Use \"Add Staff Member\" in the top right to onboard new employees with designated roles.",
      "position": "bottom"
    },
    {
      "url": "/organization/users.php",
      "target": "select[name=\"role_id\"], select[name=\"branch_id\"]",
      "fallback": ".card form",
      "module": "Staff & Team",
      "section": "Staff Filters",
      "title": "Filter Staff by Role & Campus Division",
      "badge": "Directory Scoping",
      "icon": "fa-filter",
      "description": "Quickly isolate counselors assigned to specific regional branches or review all managers across the organization.",
      "tip": "Filter by Admissions Counselor to review student caseload distribution across staff.",
      "position": "bottom"
    },
    {
      "url": "/organization/users.php",
      "target": "button[onclick*=\"openUserModal\"], button[onclick*=\"addUser\"], .btn-primary",
      "fallback": ".page-header",
      "module": "Staff & Team",
      "section": "Staff Creation",
      "title": "Add New Staff Account Modal",
      "badge": "User Provisioning",
      "icon": "fa-user-plus",
      "description": "Provision new staff accounts: enter Full Name, Official Email, Phone, Password, Role (Super Admin, Manager, Counselor), and assigned Branch Campus.",
      "tip": "New counselors immediately appear in lead allocation dropdowns across the CRM.",
      "position": "bottom"
    },
    {
      "url": "/organization/users.php",
      "target": "table.data-table",
      "fallback": ".card",
      "module": "Staff & Team",
      "section": "Staff Table",
      "title": "Staff Roster & Caseload Monitoring",
      "badge": "Caseload Audit",
      "icon": "fa-table-list",
      "description": "Directory table displaying Staff Name, Email, Assigned Branch, Role badge, Active Student Caseload count, and Account Status. Click \"Edit\" to modify permissions or reset passwords.",
      "tip": "Ensure no single counselor is assigned more than 150 active leads simultaneously.",
      "position": "top"
    },
    {
      "url": "/archive/index.php",
      "target": ".page-header",
      "fallback": "#main-wrapper",
      "module": "Archive Repository",
      "section": "Compliance Repository",
      "title": "Archive Repository & Soft-Delete Safeguard",
      "badge": "Zero-Data-Loss",
      "icon": "fa-box-archive",
      "description": "The safety net of the university CRM. Archived records (inactive leads, duplicate college contacts) are preserved here with soft-delete protection, preventing accidental data loss.",
      "tip": "Records can be restored to active status anytime with a single click.",
      "position": "bottom"
    },
    {
      "url": "/archive/index.php",
      "target": "div[style*=\"display:flex; gap:8px\"], .nav-tabs",
      "fallback": ".page-header",
      "module": "Archive Repository",
      "section": "Entity Tabs",
      "title": "Archived Entity Categorization Tabs",
      "badge": "Entity Categorization",
      "icon": "fa-folder-tree",
      "description": "Switch between Archived Student Leads, Archived College Data, Archived Web Leads, and Archived Customers with individual item count badges.",
      "tip": "Review archived student inquiries before each new intake to identify re-engagements.",
      "position": "bottom"
    },
    {
      "url": "/archive/index.php",
      "target": "button[onclick*=\"restore\"], a[href*=\"restore\"]",
      "fallback": "table.data-table",
      "module": "Archive Repository",
      "section": "Restore Action",
      "title": "1-Click Restore to Active CRM Status",
      "badge": "Safe Recovery",
      "icon": "fa-rotate-left",
      "description": "Click \"Restore\" on any archived candidate to return them directly into active counseling workflows with their complete prior history intact.",
      "tip": "Restoring an inquiry reassigns it to the counselor with all original notes preserved.",
      "position": "top"
    },
    {
      "url": "/archive/index.php",
      "target": "table.data-table",
      "fallback": ".card",
      "module": "Archive Repository",
      "section": "Permanent Deletion",
      "title": "GDPR/DPDP Permanent Deletion Protocol",
      "badge": "Privacy Compliance",
      "icon": "fa-trash-can",
      "description": "Only Super Admins can permanently purge records from the archive. Requires secondary confirmation and logs a permanent deletion record in compliance audit tables.",
      "tip": "Exercise caution with permanent deletion, as purged records cannot be recovered.",
      "position": "top"
    },
    {
      "url": "/notifications/index.php",
      "target": ".page-header",
      "fallback": "#main-wrapper",
      "module": "Notification Center",
      "section": "Alert Stream",
      "title": "Real-Time Notification & Action Center",
      "badge": "Live Operational Alerts",
      "icon": "fa-bell",
      "description": "Central notifications hub for real-time operational alerts: fresh web lead assignments, upcoming consultation calls, overdue follow-up warnings, and invoice payments.",
      "tip": "Check notifications first thing every morning to stay aligned with daily priorities.",
      "position": "bottom"
    },
    {
      "url": "/notifications/index.php",
      "target": "div[style*=\"display:flex; gap:8px\"], button:has(.fa-check-double), a[href*=\"mark_all\"]",
      "fallback": ".page-header",
      "module": "Notification Center",
      "section": "Filter Tabs",
      "title": "Not Attended vs Attended Alert Filters",
      "badge": "Alert Triage",
      "icon": "fa-filter",
      "description": "Filter between Not Attended (pending action) and Attended (completed/read) alerts. Click \"Mark All Attended\" to clear your badge counter in 1 click.",
      "tip": "Keep your Not Attended counter at zero to ensure no student inquiries slip through.",
      "position": "bottom"
    },
    {
      "url": "/notifications/index.php",
      "target": ".card, table.data-table",
      "fallback": ".content-area",
      "module": "Notification Center",
      "section": "Alert Stream",
      "title": "Actionable Alerts Stream & Deep Links",
      "badge": "One-Click Resolution",
      "icon": "fa-bolt",
      "description": "Each notification features a direct link to the relevant student dossier, follow-up task, or billing invoice. Click the link to jump directly into action.",
      "tip": "Clicking an alert link marks it as attended and takes you right to the student dossier.",
      "position": "top"
    }
  ],
  "manager": [
    {
      "url": "/dashboard/index.php",
      "target": "#top-navbar",
      "fallback": ".navbar-right",
      "module": "Admissions Cockpit",
      "section": "Global Command Header",
      "title": "Institutional Cockpit & System Navigation",
      "badge": "Admissions Manager • Operational Supervision",
      "icon": "fa-crown",
      "description": "Welcome to your complete interactive master training walkthrough! As Super Admin, you govern all 16 CRM modules across all university branches. From this top command header, you have immediate access to the 1-Click Role Switcher, real-time alert notifications, and interactive system guidance anytime.",
      "tip": "Click the Guide button anytime on any screen to launch interactive training or refresh workflows.",
      "position": "bottom"
    },
    {
      "url": "/dashboard/index.php",
      "target": "#role-switcher-btn",
      "fallback": "#top-navbar",
      "module": "Admissions Cockpit",
      "section": "Persona Simulation",
      "title": "Instant Role Switcher Dropdown",
      "badge": "Role Simulation",
      "icon": "fa-shuffle",
      "description": "Test, audit, and verify system behavior across all staff personas with a single click: Dr. Arthur Vance (Super Admin), Marcus Sterling (Manager), David Miller (Counselor), or Sarah Jenkins (Advisor). Validates role permissions, caseload boundaries, and screen access in real time.",
      "tip": "Switch to Admissions Counselor anytime to verify exactly what assigned staff see on their screens.",
      "position": "bottom"
    },
    {
      "url": "/dashboard/index.php",
      "target": "#btn-quick-add-lead",
      "fallback": ".page-header",
      "module": "Admissions Cockpit",
      "section": "Rapid Lead Capture",
      "title": "15-Second Quick Lead Capture Modal",
      "badge": "Instant Ingestion",
      "icon": "fa-bolt",
      "description": "Rapidly intake walk-in students or phone inquiries without navigating away from your dashboard. Automatically generates a unique tracking code (EDR-YYYY-XXXX), normalizes phone numbers to prevent duplicates, and routes the student directly to an assigned counselor.",
      "tip": "Enter candidate name, phone number, study country, and next follow-up in under 15 seconds.",
      "position": "bottom"
    },
    {
      "url": "/dashboard/index.php",
      "target": "#btn-generate-invoice",
      "fallback": "#widget-4step-workflow",
      "module": "Admissions Cockpit",
      "section": "Financial Quick Action",
      "title": "Instant Service Invoicing Button",
      "badge": "Financial Governance",
      "icon": "fa-file-invoice-dollar",
      "description": "Generate official institutional consultancy service invoices, university application processing fees, or visa handling charges. Connects directly to the student ledger with automatic 18% GST calculation and downloadable client PDF receipts.",
      "tip": "Click here whenever an enrolled applicant makes a fee payment or requires an institutional bill.",
      "position": "bottom"
    },
    {
      "url": "/dashboard/index.php",
      "target": "#widget-4step-workflow",
      "fallback": ".content-area",
      "module": "Admissions Cockpit",
      "section": "Process Lifecycle",
      "title": "Standard 4-Step Daily Admissions Workflow",
      "badge": "Operational Pipeline",
      "icon": "fa-route",
      "description": "The standardized sequence enforced across all campuses: (1) Capture Inquiry -> (2) Call & WhatsApp Outreach -> (3) Advance Milestones -> (4) Invoicing & Fee Documentation. All branch staff members follow this standardized pathway to maximize conversion yields.",
      "tip": "Click any step card anytime to jump straight into the corresponding operational surface.",
      "position": "bottom"
    },
    {
      "url": "/dashboard/index.php",
      "target": "#widget-action-calls",
      "fallback": "#widget-metric-cards",
      "module": "Admissions Cockpit",
      "section": "Daily Priority Queue",
      "title": "Today's Action Call List & 1-Click Outreach",
      "badge": "Outreach Engine",
      "icon": "fa-phone-volume",
      "description": "Active candidates scheduled for consultation today across the company. Features instant 1-click WhatsApp Web messaging with pre-filled candidate greetings, direct telephone dialing, and mandatory post-call outcome logging.",
      "tip": "Supervise this table to ensure counselors maintain 100% daily follow-up compliance.",
      "position": "top"
    },
    {
      "url": "/dashboard/index.php",
      "target": "#widget-action-calls a[href*=\"wa.me\"]",
      "fallback": "#widget-action-calls",
      "module": "Admissions Cockpit",
      "section": "WhatsApp Outreach",
      "title": "1-Click WhatsApp Candidate Messaging",
      "badge": "Candidate Outreach",
      "icon": "fa-brands fa-whatsapp",
      "description": "Click the green WhatsApp icon to launch WhatsApp Web with a personalized pre-filled greeting. Enables counselors to connect with candidates instantly without typing phone numbers.",
      "tip": "WhatsApp follow-ups achieve significantly higher response rates than email consultations.",
      "position": "top"
    },
    {
      "url": "/dashboard/index.php",
      "target": "#widget-action-calls button",
      "fallback": "#widget-action-calls",
      "module": "Admissions Cockpit",
      "section": "Outcome Compliance",
      "title": "Mandatory: Log Consultation Outcome",
      "badge": "Compliance Logging",
      "icon": "fa-check",
      "description": "Immediately after every conversation, click Log Outcome. Record what was discussed, select the interaction title, and set the next follow-up date (Tomorrow, +2 Days, Next Week).",
      "tip": "Logging an outcome automatically clears completed items from the active queue and logs an audit trail.",
      "position": "top"
    },
    {
      "url": "/dashboard/index.php",
      "target": "#widget-metric-cards",
      "fallback": ".content-area",
      "module": "Admissions Cockpit",
      "section": "Executive Analytics",
      "title": "Institutional KPI Cards & Conversion Metrics",
      "badge": "Real-Time Telemetry",
      "icon": "fa-chart-line",
      "description": "Live performance metrics tracking Total Active Student Leads, Invoiced Clients (with conversion percentage rate), Total Collected Revenue, and Outstanding Receivables across all university campuses.",
      "tip": "Revenue and balance figures are computed directly from MySQL ledgers and update instantly upon payment receipt.",
      "position": "top"
    },
    {
      "url": "/dashboard/index.php",
      "target": "#widget-task-queues",
      "fallback": ".content-area",
      "module": "Admissions Cockpit",
      "section": "SLA Task Enforcement",
      "title": "4 Daily Operational Task Queues",
      "badge": "Zero-Lead-Abandonment",
      "icon": "fa-list-check",
      "description": "Four prioritized queues: (1) Scheduled Follow-ups (Today's Calls), (2) Pending Follow-ups (Overdue Calls), (3) Today Leads (Fresh inquiries assigned today), and (4) Pending Leads (Uncontacted older inquiries). Guarantees zero lead abandonment across the company.",
      "tip": "A red badge in the Overdue queue indicates delayed consultations that require managerial intervention.",
      "position": "top"
    },
    {
      "url": "/dashboard/index.php",
      "target": "#widget-pipeline-chart",
      "fallback": "#widget-recent-leads",
      "module": "Admissions Cockpit",
      "section": "Funnel Analytics",
      "title": "6-Stage Admission Pipeline Funnel",
      "badge": "Stage Progression",
      "icon": "fa-diagram-project",
      "description": "Interactive analytics tracking student distribution across the 6 official admission stages: ENQUIRY -> CUSTOMER -> APPLICATION -> OFFER RECEIVED -> TUITION FEES PAID -> ENROLLED.",
      "tip": "Click \"Kanban ->\" anytime to open the visual drag-and-drop board.",
      "position": "top"
    },
    {
      "url": "/dashboard/index.php",
      "target": "#widget-branch-chart",
      "fallback": "#widget-recent-leads",
      "module": "Admissions Cockpit",
      "section": "Campus Yields",
      "title": "Regional Branch Intake Distribution",
      "badge": "Multi-Campus Telemetry",
      "icon": "fa-chart-pie",
      "description": "Doughnut chart comparing student recruitment yield and revenue collection across regional branches (Main Admissions Division vs Global Advisory Division).",
      "tip": "Identify high-performing regional hubs and reallocate marketing budgets accordingly.",
      "position": "top"
    },
    {
      "url": "/dashboard/index.php",
      "target": "#widget-recent-leads",
      "fallback": ".content-area",
      "module": "Admissions Cockpit",
      "section": "Recent Inquiries",
      "title": "Recent Student Inquiries & Activity Feed",
      "badge": "Live Activity",
      "icon": "fa-clock-rotate-left",
      "description": "Recent student registrations across all divisions with tracking codes, target countries, and assigned counselors. Alongside is the live Audit Feed recording user logins, status modifications, and stage advancements.",
      "tip": "Click \"Dossier ->\" on any student row to inspect their complete academic profile.",
      "position": "top"
    },
    {
      "url": "/leads/index.php",
      "target": ".page-header",
      "fallback": "#main-wrapper",
      "module": "Student Leads",
      "section": "Directory Master",
      "title": "Student Leads Directory & Global Repository",
      "badge": "Central Database",
      "icon": "fa-user-graduate",
      "description": "Welcome to the central Student Leads repository! Here you manage all prospective candidate inquiries across all stages. Notice the action buttons on the top right: Export CSV (strictly restricted from counselors, permitted for administrators) and Bulk Reassign.",
      "tip": "As Super Admin, you have global visibility across all branches and counselor caseloads.",
      "position": "bottom"
    },
    {
      "url": "/leads/index.php",
      "target": "div[style*=\"overflow-x:auto\"]",
      "fallback": ".page-header",
      "module": "Student Leads",
      "section": "Stage Filter Tabs",
      "title": "6 Admission Stage Filter Tabs",
      "badge": "One-Click Filtering",
      "icon": "fa-tags",
      "description": "Instantly filter your entire lead pool by stage. Click \"All Stages\" for global view, or isolate candidates in Enquiry, Customer, Application, Offer Received, Tuition Fees Paid, or Enrolled with live count badges.",
      "tip": "The numbers inside each pill reflect live database counts matching your current filter scope.",
      "position": "bottom"
    },
    {
      "url": "/leads/index.php",
      "target": "input[name=\"search\"]",
      "fallback": ".card",
      "module": "Student Leads",
      "section": "Deep Discovery",
      "title": "Universal Candidate Search Bar",
      "badge": "Fast Lookup",
      "icon": "fa-magnifying-glass",
      "description": "Search prospective students in real time by student name, tracking ID (e.g. EDR-2026-0001), phone number, email address, or previous college/university name.",
      "tip": "Type any partial keyword and press Enter or click Apply to execute.",
      "position": "bottom"
    },
    {
      "url": "/leads/index.php",
      "target": "select[name=\"priority\"]",
      "fallback": ".card form",
      "module": "Student Leads",
      "section": "Priority Filter",
      "title": "Lead Priority Filter Dropdown",
      "badge": "Urgency Triage",
      "icon": "fa-fire",
      "description": "Filter leads by urgency: High Priority (urgent intake), Moderate Priority (standard inquiries), or Low Priority (future term prospects).",
      "tip": "Encourage counselors to attend to High Priority leads before lunch every day.",
      "position": "bottom"
    },
    {
      "url": "/leads/index.php",
      "target": "select[name=\"source\"]",
      "fallback": ".card form",
      "module": "Student Leads",
      "section": "Acquisition Channels",
      "title": "Lead Acquisition Source Filter",
      "badge": "Marketing Attribution",
      "icon": "fa-bullhorn",
      "description": "Filter candidates by origin: Meta Ads, Google Ads, Website Form, Walk-in, College Fair Data, or Referral. Helps measure marketing campaign performance.",
      "tip": "Combine source filtering with stage filtering to evaluate channel conversion rates.",
      "position": "bottom"
    },
    {
      "url": "/leads/index.php",
      "target": "select[name=\"branch_id\"]",
      "fallback": ".card form",
      "module": "Student Leads",
      "section": "Branch Scope",
      "title": "Campus & Branch Filter Dropdown",
      "badge": "Campus Filter",
      "icon": "fa-building",
      "description": "Switch between Main Admissions Division, Global Advisory Division, or All Branches. Restricts the directory table to show only students belonging to the selected campus.",
      "tip": "Super Admin can inspect any branch; counselors are strictly restricted to their own.",
      "position": "bottom"
    },
    {
      "url": "/leads/index.php",
      "target": "select[name=\"counselor_id\"]",
      "fallback": ".card form",
      "module": "Student Leads",
      "section": "Staff Caseloads",
      "title": "Counselor Caseload Selector",
      "badge": "Caseload Audit",
      "icon": "fa-users",
      "description": "Filter student records by individual counselor. The number beside each counselor indicates their active student count, making it easy to identify overloaded staff.",
      "tip": "Select any counselor to review their follow-up compliance and student progression.",
      "position": "bottom"
    },
    {
      "url": "/leads/index.php",
      "target": "a[href*=\"export=csv\"]",
      "fallback": ".page-header",
      "module": "Student Leads",
      "section": "Data Export",
      "title": "Privileged CSV Data Export Button",
      "badge": "Security Governance",
      "icon": "fa-file-csv",
      "description": "Export authorized student data rosters into formatted CSV spreadsheets. Role permissions strictly block counselors from exporting, ensuring student database privacy.",
      "tip": "Exports respect all active filters applied in the search bar above.",
      "position": "bottom"
    },
    {
      "url": "/leads/index.php",
      "target": "button[onclick*=\"openBulkReassignModal\"]",
      "fallback": ".page-header",
      "module": "Student Leads",
      "section": "Caseload Balancing",
      "title": "Bulk Reassign Leads Between Staff",
      "badge": "Workload Rebalancing",
      "icon": "fa-users-gear",
      "description": "Rebalance student caseloads when a counselor goes on leave or shifts territories. Select candidate checkboxes in the table and reassign them in bulk with automated notifications.",
      "tip": "Reassigning leads transfers all follow-up tasks and history seamlessly to the new counselor.",
      "position": "bottom"
    },
    {
      "url": "/leads/index.php",
      "target": "table.data-table",
      "fallback": ".card",
      "module": "Student Leads",
      "section": "Data Grid",
      "title": "Lead Data Table & Action Menu",
      "badge": "Comprehensive Dossier",
      "icon": "fa-table-list",
      "description": "Structured student records displaying tracking Code, Name, Phone (with 1-click WhatsApp dialer), Target Study Program & Country, Stage status, Priority badge, assigned Counselor, and Branch. Click \"Dossier ->\" on any student to open their complete academic dossier.",
      "tip": "Select checkboxes on the left to perform bulk operations such as reassigning multiple leads at once.",
      "position": "top"
    },
    {
      "url": "/leads/add.php",
      "target": ".page-header",
      "fallback": "#main-wrapper",
      "module": "Register Student Lead",
      "section": "Form Overview",
      "title": "Student Registration Master Interface",
      "badge": "Applicant Intake",
      "icon": "fa-user-plus",
      "description": "Register prospective student leads with complete academic and counseling metadata. Designed for high operational speed: essential contact fields are mandatory, while academic and test scores can be completed now or updated later in the student dossier.",
      "tip": "Click \"1-Click Fast Add\" in the top right to open the rapid 15-second intake modal anytime.",
      "position": "bottom"
    },
    {
      "url": "/leads/add.php",
      "target": "input[name=\"full_name\"]",
      "fallback": ".card:first-of-type",
      "module": "Register Student Lead",
      "section": "Essential Details",
      "title": "Candidate Name & Normalized Phone",
      "badge": "Mandatory Fields",
      "icon": "fa-id-card",
      "description": "Enter candidate Full Name, Phone/WhatsApp number, and Email Address. Phone numbers are automatically sanitized and normalized to international format (E.164) with automatic duplicate validation preventing duplicate student entries.",
      "tip": "If a duplicate phone number is detected in active leads, the system blocks insertion with an alert.",
      "position": "bottom"
    },
    {
      "url": "/leads/add.php",
      "target": "select[name=\"preferred_country\"]",
      "fallback": ".card:first-of-type",
      "module": "Register Student Lead",
      "section": "Study Destination",
      "title": "Target Country & Academic Intake Term",
      "badge": "Study Preferences",
      "icon": "fa-earth-americas",
      "description": "Select the student's desired destination country (UK, Canada, Australia, USA, Ireland, Germany, New Zealand) and target intake session (e.g. September 2026, January 2027) to synchronize application deadlines.",
      "tip": "Selecting target country helps managers route the candidate to country-specialist counselors.",
      "position": "bottom"
    },
    {
      "url": "/leads/add.php",
      "target": "select[name=\"branch_id\"], select[name=\"counselor_id\"]",
      "fallback": ".card:first-of-type",
      "module": "Register Student Lead",
      "section": "Caseload Routing",
      "title": "Branch & Counselor Allocation",
      "badge": "Institutional Hierarchy",
      "icon": "fa-users-gear",
      "description": "Assign the candidate to an operational campus division and designated counselor. The counselor will immediately receive a notification in their task queue, and the lead will appear in their daily action list.",
      "tip": "Super Admin can assign to any branch; managers are scoped to their own campus.",
      "position": "bottom"
    },
    {
      "url": "/leads/add.php",
      "target": "details:nth-of-type(1)",
      "fallback": ".card:nth-of-type(2)",
      "module": "Register Student Lead",
      "section": "Academic & Tests",
      "title": "Academic History & English Proficiency (IELTS/PTE)",
      "badge": "Qualification Profile",
      "icon": "fa-graduation-cap",
      "description": "Expandable section to record student educational history: Highest Qualification, College Name, Passing Year, CGPA/Percentage, Active Backlog counts, Work Experience, and standardized English scores (IELTS, PTE, TOEFL, Duolingo) with individual band breakdowns.",
      "tip": "This section is optional during initial intake and can be filled when the student submits transcripts.",
      "position": "bottom"
    },
    {
      "url": "/leads/add.php",
      "target": "textarea[name=\"notes\"]",
      "fallback": "button[type=\"submit\"]",
      "module": "Register Student Lead",
      "section": "Counselor Notes",
      "title": "Initial Counseling Remarks & Budget",
      "badge": "Consultation Notes",
      "icon": "fa-pen-to-square",
      "description": "Record initial counseling remarks: student career aspirations, university preferences, budget constraints, and pending document requirements.",
      "tip": "Initial notes appear on the dashboard action call list to prepare counselors before subsequent calls.",
      "position": "top"
    },
    {
      "url": "/leads/add.php",
      "target": "button[type=\"submit\"], .btn-primary",
      "fallback": ".card:last-of-type",
      "module": "Register Student Lead",
      "section": "Submission",
      "title": "Register Lead & Trigger Stage History",
      "badge": "Data Persistence",
      "icon": "fa-circle-check",
      "description": "Click \"Register Student Lead\" to execute a database transaction that: (1) creates the lead record, (2) generates the unique EDR tracking code, (3) logs initial stage history to \"ENQUIRY\", and (4) notifies the assigned counselor.",
      "tip": "Upon submission, you are automatically redirected to the student's complete academic dossier.",
      "position": "top"
    },
    {
      "url": "/leads/view.php",
      "target": ".card:first-of-type",
      "fallback": "#main-wrapper",
      "module": "Student Dossier & Profile",
      "section": "360° Profile Overview",
      "title": "Student Dossier & 360-Degree Profile",
      "badge": "Comprehensive Dossier",
      "icon": "fa-address-card",
      "description": "Welcome to the 360-Degree Student Dossier! Every candidate's academic credentials, contact information, passport details, test scorecards, consultation notes, document uploads, and billing history are unified here on a single comprehensive operational surface.",
      "tip": "Use the quick action buttons on top to log calls, advance admission stages, or issue fee invoices.",
      "position": "bottom"
    },
    {
      "url": "/leads/view.php",
      "target": "button[onclick*=\"openAdvanceStageModal\"], button:has(.fa-stairs)",
      "fallback": ".card:first-of-type",
      "module": "Student Dossier & Profile",
      "section": "Milestone Advancement",
      "title": "Advance Milestone Action Button",
      "badge": "Stage Transition",
      "icon": "fa-forward-step",
      "description": "Click \"Advance Stage\" to progress this student through the 6 standardized consultancy milestones (Enquiry -> Customer -> Application -> Offer Received -> Tuition Fees Paid -> Enrolled). Prompts for consultation remarks and automatically logs a timestamped milestone audit record.",
      "tip": "Advancing to \"Customer\" triggers client billing readiness in the Finance module.",
      "position": "bottom"
    },
    {
      "url": "/leads/view.php",
      "target": "button[onclick*=\"openLogCallModal\"], button:has(.fa-phone-volume)",
      "fallback": ".card:first-of-type",
      "module": "Student Dossier & Profile",
      "section": "Interaction Logging",
      "title": "Log Consultation Call & Note",
      "badge": "Counseling Log",
      "icon": "fa-phone",
      "description": "Record telephone calls, WhatsApp discussions, and in-person counseling sessions directly into the student history. Tag discussion categories (e.g. Visa Guidance, University Selection, Fee Payment) and schedule the next follow-up date.",
      "tip": "Setting a next follow-up date automatically populates the counselor daily tasks queue.",
      "position": "bottom"
    },
    {
      "url": "/leads/view.php",
      "target": ".card:first-of-type",
      "fallback": ".content-area",
      "module": "Student Dossier & Profile",
      "section": "Academic Credentials",
      "title": "Academic Scorecard & English Tests",
      "badge": "Scorecard Verification",
      "icon": "fa-graduation-cap",
      "description": "Inspect the applicant's educational qualification, college background, graduation percentage, backlogs count, and standardized test scores (IELTS/PTE bands: Listening, Reading, Writing, Speaking).",
      "tip": "Counselors reference this scorecard when matching students to university eligibility criteria.",
      "position": "top"
    },
    {
      "url": "/leads/view.php",
      "target": "div[style*=\"border-top\"], .card:last-of-type",
      "fallback": ".content-area",
      "module": "Student Dossier & Profile",
      "section": "Dossier History",
      "title": "Interaction History & Audit Trail",
      "badge": "Chronological Audit",
      "icon": "fa-timeline",
      "description": "Chronological history of every interaction logged with this student: counselor notes, follow-up dates, milestone transitions, and staff signatures. Ensures full accountability across team handovers.",
      "tip": "When reassigning leads, the incoming counselor reviews this chronological timeline.",
      "position": "top"
    },
    {
      "url": "/tasks/index.php",
      "target": ".page-header",
      "fallback": "#main-wrapper",
      "module": "Daily Follow-ups",
      "section": "SLA Engine",
      "title": "Daily Operational Work Queues",
      "badge": "Follow-Up Compliance",
      "icon": "fa-list-check",
      "description": "The daily operational workbench for counselors and managers. Features 4 strict SLA queues designed to ensure 0% lead abandonment: Scheduled Follow-ups, Pending Overdue Calls, Today's Fresh Leads, and Older Uncontacted Leads.",
      "tip": "Counselors should clear all Overdue and Scheduled calls before beginning new prospect consultations.",
      "position": "bottom"
    },
    {
      "url": "/tasks/index.php",
      "target": "a[href*=\"queue=pending_followup\"], .card:nth-of-type(2)",
      "fallback": ".card",
      "module": "Daily Follow-ups",
      "section": "Overdue Alerting",
      "title": "Pending Follow-ups (Overdue Calls Queue)",
      "badge": "SLA Alert",
      "icon": "fa-triangle-exclamation",
      "description": "Highlights appointments that have exceeded their scheduled date without resolution. Displays overdue lag days in red to pinpoint neglected inquiries.",
      "tip": "Managers monitor this queue daily to maintain zero delayed inquiries across the team.",
      "position": "top"
    },
    {
      "url": "/tasks/index.php",
      "target": "a[href*=\"queue=today_leads\"], .card:nth-of-type(3)",
      "fallback": ".card",
      "module": "Daily Follow-ups",
      "section": "Fresh Leads Queue",
      "title": "Today's Assigned Leads Queue",
      "badge": "Immediate Action",
      "icon": "fa-bolt",
      "description": "New student inquiries assigned to staff today that haven't been contacted yet. Fast initial outreach (under 15 minutes) dramatically improves candidate trust and admission conversions.",
      "tip": "Call fresh leads immediately upon assignment to establish rapport before competitors.",
      "position": "top"
    },
    {
      "url": "/tasks/index.php",
      "target": "table.data-table",
      "fallback": ".card",
      "module": "Daily Follow-ups",
      "section": "Outreach & Logging",
      "title": "Action List with 1-Click WhatsApp & Outcome Logging",
      "badge": "Instant Outreach",
      "icon": "fa-phone-volume",
      "description": "Contact candidates via direct telephone call or 1-click WhatsApp Web. Immediately after the conversation, click \"Log Outcome\" to record consultation notes, select the discussion title, and set the next follow-up date.",
      "tip": "Logging an outcome automatically moves completed tasks out of the active queue.",
      "position": "top"
    },
    {
      "url": "/customers/index.php",
      "target": ".page-header",
      "fallback": "#main-wrapper",
      "module": "Enrolled Clients",
      "section": "Customer Ledger",
      "title": "Enrolled Clients (Invoiced Customers)",
      "badge": "Converted Applicants",
      "icon": "fa-certificate",
      "description": "Candidates who have formally engaged institutional services and received official invoices. Displays complete billing metrics: Total Invoiced, Total Collected Revenue, and Outstanding Receivables.",
      "tip": "Filter by Payment Status (Paid, Partial, Unpaid) to manage fee recovery schedules.",
      "position": "bottom"
    },
    {
      "url": "/customers/index.php",
      "target": "select[name=\"payment_status\"]",
      "fallback": ".card form",
      "module": "Enrolled Clients",
      "section": "Payment Filters",
      "title": "Payment Status Filter & Client Ledgers",
      "badge": "Billing Reconciliation",
      "icon": "fa-money-bill-transfer",
      "description": "Filter clients by billing settlement: Fully Paid, Partial Payment, or Unpaid Balances. Direct links allow downloading receipts or recording fee installments.",
      "tip": "Review partial payments before university tuition remittance deadlines.",
      "position": "bottom"
    },
    {
      "url": "/customers/index.php",
      "target": "table.data-table",
      "fallback": ".card",
      "module": "Enrolled Clients",
      "section": "Clients Roster",
      "title": "Enrolled Clients Data Table",
      "badge": "Converted Clients",
      "icon": "fa-table-list",
      "description": "Detailed client directory with Customer Code, Student Name, Contact Number, Enrolled Country & Program, Total Invoiced Amount, Amount Paid, and Balance Due. Click \"View Profile\" or \"Invoices\" for full financial statements.",
      "tip": "Generate installment payment receipts directly from the action menu on the right.",
      "position": "top"
    },
    {
      "url": "/data/web_leads.php",
      "target": ".page-header",
      "fallback": "#main-wrapper",
      "module": "Digital Web Leads",
      "section": "Digital Ads Triage",
      "title": "Digital Web & Social Inbound Triage",
      "badge": "Lead Allocation",
      "icon": "fa-bullseye",
      "description": "Prospective inquiries captured via website contact forms, Meta Facebook/Instagram Ads, and Google Ads stage here before entering active caseloads. Inspect applicant course preferences and allocate to branch counselors with duplicate phone checks.",
      "tip": "Rapid lead triage (<15 minutes from ad click) yields the highest university conversion rates.",
      "position": "bottom"
    },
    {
      "url": "/data/web_leads.php",
      "target": "table.data-table",
      "fallback": ".card",
      "module": "Digital Web Leads",
      "section": "Inbound Verification",
      "title": "Inbound Lead Verification & Allocation Action",
      "badge": "Caseload Routing",
      "icon": "fa-share-nodes",
      "description": "Review applicant study interests, intake terms, and source campaign. Click \"Allocate ->\" to assign the lead to a branch counselor with duplicate phone protection.",
      "tip": "Allocated leads immediately appear in the assigned counselor's Today Leads queue.",
      "position": "top"
    },
    {
      "url": "/data/web_leads.php",
      "target": "select[name=\"status\"]",
      "fallback": ".card form",
      "module": "Digital Web Leads",
      "section": "Triage Status",
      "title": "Triage Status Filter (Pending vs Allocated)",
      "badge": "Queue Status",
      "icon": "fa-filter",
      "description": "Filter leads by triage lifecycle: Pending (fresh inbound awaiting review) or Allocated (already distributed to counseling staff). Prevents accidental double assignments.",
      "tip": "Managers should ensure the Pending queue is cleared completely twice daily.",
      "position": "bottom"
    },
    {
      "url": "/data/college_data.php",
      "target": ".page-header",
      "fallback": "#main-wrapper",
      "module": "College Data Pool",
      "section": "Campus Fair Ingestion",
      "title": "College Data Pool & Education Fair Rosters",
      "badge": "Bulk Prospect Ingestion",
      "icon": "fa-building-columns",
      "description": "Repository for contact lists gathered during university campus fairs, school outreach, and education expos. Preview student qualification records and convert qualified fair attendees into active CRM leads with 1 click.",
      "tip": "Use \"Import College List\" in the sidebar to upload CSV or Excel files with duplicate phone filtering.",
      "position": "bottom"
    },
    {
      "url": "/data/college_data.php",
      "target": "table.data-table",
      "fallback": ".card",
      "module": "College Data Pool",
      "section": "Lead Promotion",
      "title": "1-Click Convert to Active Inquiry",
      "badge": "Prospect Conversion",
      "icon": "fa-user-check",
      "description": "Click \"Convert ->\" on any student row to promote them from raw campus fair data into an active CRM lead with an assigned counselor and tracking code.",
      "tip": "Filter by degree or college name to convert qualified student batches together.",
      "position": "top"
    },
    {
      "url": "/data/college_data.php",
      "target": "select[name=\"degree\"], select[name=\"college_id\"]",
      "fallback": ".card form",
      "module": "College Data Pool",
      "section": "Data Filters",
      "title": "Degree & College Institution Filters",
      "badge": "Targeted Campaigns",
      "icon": "fa-filter",
      "description": "Filter student rosters by degree qualification (B.Tech, B.Com, B.Sc) or originating college campus to target specialized postgraduate university promotions.",
      "tip": "Export or convert targeted engineering batches for STEM master degree programs abroad.",
      "position": "bottom"
    },
    {
      "url": "/data/import_college_data.php",
      "target": ".page-header",
      "fallback": "#main-wrapper",
      "module": "Import College List",
      "section": "Bulk Ingestion Engine",
      "title": "Import College Student Rosters & CSV Dropzone",
      "badge": "Batch Data Ingestion",
      "icon": "fa-file-import",
      "description": "Upload massive spreadsheets gathered during university fairs, career seminars, or partnering college events. Supports CSV and Excel files with automated duplicate phone deduplication and phone format normalization.",
      "tip": "Download the standard CSV template before uploading to ensure column header alignment.",
      "position": "bottom"
    },
    {
      "url": "/data/import_college_data.php",
      "target": "input[name=\"csv_file\"], .dropzone",
      "fallback": "form",
      "module": "Import College List",
      "section": "File Upload",
      "title": "CSV File Dropzone & Paste Box",
      "badge": "File Ingestion",
      "icon": "fa-cloud-arrow-up",
      "description": "Select your CSV spreadsheet file or paste raw delimited text directly into the box. The parser automatically validates student names, phone numbers, emails, degrees, and passing years.",
      "tip": "Large lists of 5,000+ records are processed in seconds with progress logging.",
      "position": "bottom"
    },
    {
      "url": "/data/import_college_data.php",
      "target": "button[type=\"submit\"], .btn-primary",
      "fallback": ".card",
      "module": "Import College List",
      "section": "Execution",
      "title": "Execute Import & Deduplicate Action",
      "badge": "Batch Processing",
      "icon": "fa-play",
      "description": "Click \"Execute Import & Deduplicate\" to insert valid new prospects into the College Data Pool while automatically skipping any existing phone numbers already present in the CRM.",
      "tip": "A post-import summary reports total records scanned, inserted, and duplicates skipped.",
      "position": "top"
    },
    {
      "url": "/finance/create_invoice.php",
      "target": ".page-header",
      "fallback": "#main-wrapper",
      "module": "Generate Invoice",
      "section": "Invoice Builder",
      "title": "Multi-Line Item Service Invoice Builder",
      "badge": "Official Billing",
      "icon": "fa-receipt",
      "description": "Create customized institutional invoices: select an active student lead, add multiple billable service line items (e.g. University Application Fee, Visa Filing, Document Attestation), specify quantities and rates, and apply discounts with automatic 18% GST calculation.",
      "tip": "All issued invoices automatically update student balance ledgers and executive revenue cards.",
      "position": "bottom"
    },
    {
      "url": "/finance/create_invoice.php",
      "target": "select[name=\"lead_id\"]",
      "fallback": ".card",
      "module": "Generate Invoice",
      "section": "Student Selection",
      "title": "Select Student Lead for Invoicing",
      "badge": "Account Selection",
      "icon": "fa-user-tag",
      "description": "Choose the candidate being invoiced. Once selected, their name, tracking code, and branch are linked to the billing document.",
      "tip": "Search by student name or tracking code in the dropdown picker.",
      "position": "bottom"
    },
    {
      "url": "/finance/create_invoice.php",
      "target": "button[onclick*=\"addItemRow\"]",
      "fallback": "table",
      "module": "Generate Invoice",
      "section": "Line Items Builder",
      "title": "Multi-Line Items Builder with 18% GST",
      "badge": "Itemized Billing",
      "icon": "fa-plus",
      "description": "Add multiple service line items with descriptions, quantities, and rates. The system dynamically computes subtotal, applies discounts, and adds 18% GST to calculate the grand total.",
      "tip": "Click \"+ Add Service Line Item\" to bill multiple services (e.g. Consultation + Visa Filing) on a single invoice.",
      "position": "top"
    },
    {
      "url": "/finance/create_invoice.php",
      "target": "button[type=\"submit\"], .btn-primary",
      "fallback": ".card",
      "module": "Generate Invoice",
      "section": "Issue Invoice",
      "title": "Finalize & Issue Official Invoice",
      "badge": "Tax Invoice",
      "icon": "fa-print",
      "description": "Click \"Issue Invoice\" to generate the immutable invoice number, post billing amounts to the student balance ledger, and generate the downloadable client PDF.",
      "tip": "Clients automatically transition to formal \"Customer\" status upon invoice issuance.",
      "position": "top"
    },
    {
      "url": "/documents/general.php",
      "target": ".page-header",
      "fallback": "#main-wrapper",
      "module": "Country Checklists & Guidelines",
      "section": "Visa SOP Guidelines",
      "title": "Institutional Visa Guidelines & Country Checklists",
      "badge": "Knowledge Base",
      "icon": "fa-passport",
      "description": "Official repository of country-specific visa requirements, financial affidavit formats, and statement of purpose (SOP) guidelines for UK Tier 4, Canada SDS, Australia Subclass 500, and USA F-1 visas.",
      "tip": "Share official checklist PDFs with students to avoid visa application rejections.",
      "position": "bottom"
    },
    {
      "url": "/documents/general.php",
      "target": "button[onclick*=\"openUploadModal\"], button:has(.fa-upload), a[href*=\"upload\"]",
      "fallback": ".page-header",
      "module": "Country Checklists & Guidelines",
      "section": "Guideline Upload",
      "title": "Upload Official Guideline or Checklist PDF",
      "badge": "Staff SOPs",
      "icon": "fa-cloud-arrow-up",
      "description": "Super Admins and Compliance Officers can publish updated embassy guidelines, checklist templates, or university fee schedules for staff reference.",
      "tip": "Keep embassy visa financial requirement PDFs updated ahead of intake seasons.",
      "position": "bottom"
    },
    {
      "url": "/documents/general.php",
      "target": "div[style*=\"grid-template-columns\"], .card",
      "fallback": ".content-area",
      "module": "Country Checklists & Guidelines",
      "section": "Country Hubs",
      "title": "Country Visa Category Hubs",
      "badge": "Global Destinations",
      "icon": "fa-earth-americas",
      "description": "Select country category pills to filter documents for United Kingdom, Canada, Australia, United States, Ireland, and Germany. View or download guidelines with one click.",
      "tip": "Counselors consult these guidelines during student visa mock interviews.",
      "position": "top"
    },
    {
      "url": "/organization/users.php",
      "target": ".page-header",
      "fallback": "#main-wrapper",
      "module": "Staff & Team",
      "section": "Staff Directory",
      "title": "Staff, Counselors & User Management",
      "badge": "Identity & Access",
      "icon": "fa-users",
      "description": "Manage institutional user accounts, counseling staff, branch managers, and administrative credentials. Regulate active account status, assign staff to specific campus branches, and supervise active student caseload counts.",
      "tip": "Use \"Add Staff Member\" in the top right to onboard new employees with designated roles.",
      "position": "bottom"
    },
    {
      "url": "/organization/users.php",
      "target": "select[name=\"role_id\"], select[name=\"branch_id\"]",
      "fallback": ".card form",
      "module": "Staff & Team",
      "section": "Staff Filters",
      "title": "Filter Staff by Role & Campus Division",
      "badge": "Directory Scoping",
      "icon": "fa-filter",
      "description": "Quickly isolate counselors assigned to specific regional branches or review all managers across the organization.",
      "tip": "Filter by Admissions Counselor to review student caseload distribution across staff.",
      "position": "bottom"
    },
    {
      "url": "/organization/users.php",
      "target": "button[onclick*=\"openUserModal\"], button[onclick*=\"addUser\"], .btn-primary",
      "fallback": ".page-header",
      "module": "Staff & Team",
      "section": "Staff Creation",
      "title": "Add New Staff Account Modal",
      "badge": "User Provisioning",
      "icon": "fa-user-plus",
      "description": "Provision new staff accounts: enter Full Name, Official Email, Phone, Password, Role (Super Admin, Manager, Counselor), and assigned Branch Campus.",
      "tip": "New counselors immediately appear in lead allocation dropdowns across the CRM.",
      "position": "bottom"
    },
    {
      "url": "/organization/users.php",
      "target": "table.data-table",
      "fallback": ".card",
      "module": "Staff & Team",
      "section": "Staff Table",
      "title": "Staff Roster & Caseload Monitoring",
      "badge": "Caseload Audit",
      "icon": "fa-table-list",
      "description": "Directory table displaying Staff Name, Email, Assigned Branch, Role badge, Active Student Caseload count, and Account Status. Click \"Edit\" to modify permissions or reset passwords.",
      "tip": "Ensure no single counselor is assigned more than 150 active leads simultaneously.",
      "position": "top"
    },
    {
      "url": "/archive/index.php",
      "target": ".page-header",
      "fallback": "#main-wrapper",
      "module": "Archive Repository",
      "section": "Compliance Repository",
      "title": "Archive Repository & Soft-Delete Safeguard",
      "badge": "Zero-Data-Loss",
      "icon": "fa-box-archive",
      "description": "The safety net of the university CRM. Archived records (inactive leads, duplicate college contacts) are preserved here with soft-delete protection, preventing accidental data loss.",
      "tip": "Records can be restored to active status anytime with a single click.",
      "position": "bottom"
    },
    {
      "url": "/archive/index.php",
      "target": "div[style*=\"display:flex; gap:8px\"], .nav-tabs",
      "fallback": ".page-header",
      "module": "Archive Repository",
      "section": "Entity Tabs",
      "title": "Archived Entity Categorization Tabs",
      "badge": "Entity Categorization",
      "icon": "fa-folder-tree",
      "description": "Switch between Archived Student Leads, Archived College Data, Archived Web Leads, and Archived Customers with individual item count badges.",
      "tip": "Review archived student inquiries before each new intake to identify re-engagements.",
      "position": "bottom"
    },
    {
      "url": "/archive/index.php",
      "target": "button[onclick*=\"restore\"], a[href*=\"restore\"]",
      "fallback": "table.data-table",
      "module": "Archive Repository",
      "section": "Restore Action",
      "title": "1-Click Restore to Active CRM Status",
      "badge": "Safe Recovery",
      "icon": "fa-rotate-left",
      "description": "Click \"Restore\" on any archived candidate to return them directly into active counseling workflows with their complete prior history intact.",
      "tip": "Restoring an inquiry reassigns it to the counselor with all original notes preserved.",
      "position": "top"
    },
    {
      "url": "/archive/index.php",
      "target": "table.data-table",
      "fallback": ".card",
      "module": "Archive Repository",
      "section": "Permanent Deletion",
      "title": "GDPR/DPDP Permanent Deletion Protocol",
      "badge": "Privacy Compliance",
      "icon": "fa-trash-can",
      "description": "Only Super Admins can permanently purge records from the archive. Requires secondary confirmation and logs a permanent deletion record in compliance audit tables.",
      "tip": "Exercise caution with permanent deletion, as purged records cannot be recovered.",
      "position": "top"
    },
    {
      "url": "/notifications/index.php",
      "target": ".page-header",
      "fallback": "#main-wrapper",
      "module": "Notification Center",
      "section": "Alert Stream",
      "title": "Real-Time Notification & Action Center",
      "badge": "Live Operational Alerts",
      "icon": "fa-bell",
      "description": "Central notifications hub for real-time operational alerts: fresh web lead assignments, upcoming consultation calls, overdue follow-up warnings, and invoice payments.",
      "tip": "Check notifications first thing every morning to stay aligned with daily priorities.",
      "position": "bottom"
    },
    {
      "url": "/notifications/index.php",
      "target": "div[style*=\"display:flex; gap:8px\"], button:has(.fa-check-double), a[href*=\"mark_all\"]",
      "fallback": ".page-header",
      "module": "Notification Center",
      "section": "Filter Tabs",
      "title": "Not Attended vs Attended Alert Filters",
      "badge": "Alert Triage",
      "icon": "fa-filter",
      "description": "Filter between Not Attended (pending action) and Attended (completed/read) alerts. Click \"Mark All Attended\" to clear your badge counter in 1 click.",
      "tip": "Keep your Not Attended counter at zero to ensure no student inquiries slip through.",
      "position": "bottom"
    },
    {
      "url": "/notifications/index.php",
      "target": ".card, table.data-table",
      "fallback": ".content-area",
      "module": "Notification Center",
      "section": "Alert Stream",
      "title": "Actionable Alerts Stream & Deep Links",
      "badge": "One-Click Resolution",
      "icon": "fa-bolt",
      "description": "Each notification features a direct link to the relevant student dossier, follow-up task, or billing invoice. Click the link to jump directly into action.",
      "tip": "Clicking an alert link marks it as attended and takes you right to the student dossier.",
      "position": "top"
    }
  ],
  "counselor": [
    {
      "url": "/dashboard/index.php",
      "target": "#top-navbar",
      "fallback": ".navbar-right",
      "module": "Admissions Cockpit",
      "section": "Global Command Header",
      "title": "Institutional Cockpit & System Navigation",
      "badge": "Admissions Counselor • Student Advisory",
      "icon": "fa-crown",
      "description": "Welcome to your complete interactive master training walkthrough! As Super Admin, you govern all 16 CRM modules across all university branches. From this top command header, you have immediate access to the 1-Click Role Switcher, real-time alert notifications, and interactive system guidance anytime.",
      "tip": "Click the Guide button anytime on any screen to launch interactive training or refresh workflows.",
      "position": "bottom"
    },
    {
      "url": "/dashboard/index.php",
      "target": "#role-switcher-btn",
      "fallback": "#top-navbar",
      "module": "Admissions Cockpit",
      "section": "Persona Simulation",
      "title": "Instant Role Switcher Dropdown",
      "badge": "Admissions Counselor • Student Advisory",
      "icon": "fa-shuffle",
      "description": "Test, audit, and verify system behavior across all staff personas with a single click: Dr. Arthur Vance (Super Admin), Marcus Sterling (Manager), David Miller (Counselor), or Sarah Jenkins (Advisor). Validates role permissions, caseload boundaries, and screen access in real time.",
      "tip": "Switch to Admissions Counselor anytime to verify exactly what assigned staff see on their screens.",
      "position": "bottom"
    },
    {
      "url": "/dashboard/index.php",
      "target": "#btn-quick-add-lead",
      "fallback": ".page-header",
      "module": "Admissions Cockpit",
      "section": "Rapid Lead Capture",
      "title": "15-Second Quick Lead Capture Modal",
      "badge": "Admissions Counselor • Student Advisory",
      "icon": "fa-bolt",
      "description": "Rapidly intake walk-in students or phone inquiries without navigating away from your dashboard. Automatically generates a unique tracking code (EDR-YYYY-XXXX), normalizes phone numbers to prevent duplicates, and routes the student directly to an assigned counselor.",
      "tip": "Enter candidate name, phone number, study country, and next follow-up in under 15 seconds.",
      "position": "bottom"
    },
    {
      "url": "/dashboard/index.php",
      "target": "#btn-generate-invoice",
      "fallback": "#widget-4step-workflow",
      "module": "Admissions Cockpit",
      "section": "Financial Quick Action",
      "title": "Instant Service Invoicing Button",
      "badge": "Admissions Counselor • Student Advisory",
      "icon": "fa-file-invoice-dollar",
      "description": "Generate official institutional consultancy service invoices, university application processing fees, or visa handling charges. Connects directly to the student ledger with automatic 18% GST calculation and downloadable client PDF receipts.",
      "tip": "Click here whenever an enrolled applicant makes a fee payment or requires an institutional bill.",
      "position": "bottom"
    },
    {
      "url": "/dashboard/index.php",
      "target": "#widget-4step-workflow",
      "fallback": ".content-area",
      "module": "Admissions Cockpit",
      "section": "Process Lifecycle",
      "title": "Standard 4-Step Daily Admissions Workflow",
      "badge": "Admissions Counselor • Student Advisory",
      "icon": "fa-route",
      "description": "The standardized sequence enforced across all campuses: (1) Capture Inquiry -> (2) Call & WhatsApp Outreach -> (3) Advance Milestones -> (4) Invoicing & Fee Documentation. All branch staff members follow this standardized pathway to maximize conversion yields.",
      "tip": "Click any step card anytime to jump straight into the corresponding operational surface.",
      "position": "bottom"
    },
    {
      "url": "/dashboard/index.php",
      "target": "#widget-action-calls",
      "fallback": "#widget-metric-cards",
      "module": "Admissions Cockpit",
      "section": "Daily Priority Queue",
      "title": "Today's Action Call List & 1-Click Outreach",
      "badge": "Admissions Counselor • Student Advisory",
      "icon": "fa-phone-volume",
      "description": "Active candidates scheduled for consultation today across the company. Features instant 1-click WhatsApp Web messaging with pre-filled candidate greetings, direct telephone dialing, and mandatory post-call outcome logging.",
      "tip": "Supervise this table to ensure counselors maintain 100% daily follow-up compliance.",
      "position": "top"
    },
    {
      "url": "/dashboard/index.php",
      "target": "#widget-action-calls a[href*=\"wa.me\"]",
      "fallback": "#widget-action-calls",
      "module": "Admissions Cockpit",
      "section": "WhatsApp Outreach",
      "title": "1-Click WhatsApp Candidate Messaging",
      "badge": "Admissions Counselor • Student Advisory",
      "icon": "fa-brands fa-whatsapp",
      "description": "Click the green WhatsApp icon to launch WhatsApp Web with a personalized pre-filled greeting. Enables counselors to connect with candidates instantly without typing phone numbers.",
      "tip": "WhatsApp follow-ups achieve significantly higher response rates than email consultations.",
      "position": "top"
    },
    {
      "url": "/dashboard/index.php",
      "target": "#widget-action-calls button",
      "fallback": "#widget-action-calls",
      "module": "Admissions Cockpit",
      "section": "Outcome Compliance",
      "title": "Mandatory: Log Consultation Outcome",
      "badge": "Admissions Counselor • Student Advisory",
      "icon": "fa-check",
      "description": "Immediately after every conversation, click Log Outcome. Record what was discussed, select the interaction title, and set the next follow-up date (Tomorrow, +2 Days, Next Week).",
      "tip": "Logging an outcome automatically clears completed items from the active queue and logs an audit trail.",
      "position": "top"
    },
    {
      "url": "/dashboard/index.php",
      "target": "#widget-metric-cards",
      "fallback": ".content-area",
      "module": "Admissions Cockpit",
      "section": "Executive Analytics",
      "title": "Institutional KPI Cards & Conversion Metrics",
      "badge": "Admissions Counselor • Student Advisory",
      "icon": "fa-chart-line",
      "description": "Live performance metrics tracking Total Active Student Leads, Invoiced Clients (with conversion percentage rate), Total Collected Revenue, and Outstanding Receivables across all university campuses.",
      "tip": "Revenue and balance figures are computed directly from MySQL ledgers and update instantly upon payment receipt.",
      "position": "top"
    },
    {
      "url": "/dashboard/index.php",
      "target": "#widget-task-queues",
      "fallback": ".content-area",
      "module": "Admissions Cockpit",
      "section": "SLA Task Enforcement",
      "title": "4 Daily Operational Task Queues",
      "badge": "Admissions Counselor • Student Advisory",
      "icon": "fa-list-check",
      "description": "Four prioritized queues: (1) Scheduled Follow-ups (Today's Calls), (2) Pending Follow-ups (Overdue Calls), (3) Today Leads (Fresh inquiries assigned today), and (4) Pending Leads (Uncontacted older inquiries). Guarantees zero lead abandonment across the company.",
      "tip": "A red badge in the Overdue queue indicates delayed consultations that require managerial intervention.",
      "position": "top"
    },
    {
      "url": "/dashboard/index.php",
      "target": "#widget-pipeline-chart",
      "fallback": "#widget-recent-leads",
      "module": "Admissions Cockpit",
      "section": "Funnel Analytics",
      "title": "6-Stage Admission Pipeline Funnel",
      "badge": "Admissions Counselor • Student Advisory",
      "icon": "fa-diagram-project",
      "description": "Interactive analytics tracking student distribution across the 6 official admission stages: ENQUIRY -> CUSTOMER -> APPLICATION -> OFFER RECEIVED -> TUITION FEES PAID -> ENROLLED.",
      "tip": "Click \"Kanban ->\" anytime to open the visual drag-and-drop board.",
      "position": "top"
    },
    {
      "url": "/dashboard/index.php",
      "target": "#widget-branch-chart",
      "fallback": "#widget-recent-leads",
      "module": "Admissions Cockpit",
      "section": "Campus Yields",
      "title": "Regional Branch Intake Distribution",
      "badge": "Admissions Counselor • Student Advisory",
      "icon": "fa-chart-pie",
      "description": "Doughnut chart comparing student recruitment yield and revenue collection across regional branches (Main Admissions Division vs Global Advisory Division).",
      "tip": "Identify high-performing regional hubs and reallocate marketing budgets accordingly.",
      "position": "top"
    },
    {
      "url": "/dashboard/index.php",
      "target": "#widget-recent-leads",
      "fallback": ".content-area",
      "module": "Admissions Cockpit",
      "section": "Recent Inquiries",
      "title": "Recent Student Inquiries & Activity Feed",
      "badge": "Admissions Counselor • Student Advisory",
      "icon": "fa-clock-rotate-left",
      "description": "Recent student registrations across all divisions with tracking codes, target countries, and assigned counselors. Alongside is the live Audit Feed recording user logins, status modifications, and stage advancements.",
      "tip": "Click \"Dossier ->\" on any student row to inspect their complete academic profile.",
      "position": "top"
    },
    {
      "url": "/leads/index.php",
      "target": ".page-header",
      "fallback": "#main-wrapper",
      "module": "Student Leads",
      "section": "Directory Master",
      "title": "Student Leads Directory & Global Repository",
      "badge": "Admissions Counselor • Student Advisory",
      "icon": "fa-user-graduate",
      "description": "Welcome to the central Student Leads repository! Here you manage all prospective candidate inquiries across all stages. Notice the action buttons on the top right: Export CSV (strictly restricted from counselors, permitted for administrators) and Bulk Reassign.",
      "tip": "As Super Admin, you have global visibility across all branches and counselor caseloads.",
      "position": "bottom"
    },
    {
      "url": "/leads/index.php",
      "target": "div[style*=\"overflow-x:auto\"]",
      "fallback": ".page-header",
      "module": "Student Leads",
      "section": "Stage Filter Tabs",
      "title": "6 Admission Stage Filter Tabs",
      "badge": "Admissions Counselor • Student Advisory",
      "icon": "fa-tags",
      "description": "Instantly filter your entire lead pool by stage. Click \"All Stages\" for global view, or isolate candidates in Enquiry, Customer, Application, Offer Received, Tuition Fees Paid, or Enrolled with live count badges.",
      "tip": "The numbers inside each pill reflect live database counts matching your current filter scope.",
      "position": "bottom"
    },
    {
      "url": "/leads/index.php",
      "target": "input[name=\"search\"]",
      "fallback": ".card",
      "module": "Student Leads",
      "section": "Deep Discovery",
      "title": "Universal Candidate Search Bar",
      "badge": "Admissions Counselor • Student Advisory",
      "icon": "fa-magnifying-glass",
      "description": "Search prospective students in real time by student name, tracking ID (e.g. EDR-2026-0001), phone number, email address, or previous college/university name.",
      "tip": "Type any partial keyword and press Enter or click Apply to execute.",
      "position": "bottom"
    },
    {
      "url": "/leads/index.php",
      "target": "select[name=\"priority\"]",
      "fallback": ".card form",
      "module": "Student Leads",
      "section": "Priority Filter",
      "title": "Lead Priority Filter Dropdown",
      "badge": "Admissions Counselor • Student Advisory",
      "icon": "fa-fire",
      "description": "Filter leads by urgency: High Priority (urgent intake), Moderate Priority (standard inquiries), or Low Priority (future term prospects).",
      "tip": "Encourage counselors to attend to High Priority leads before lunch every day.",
      "position": "bottom"
    },
    {
      "url": "/leads/index.php",
      "target": "select[name=\"source\"]",
      "fallback": ".card form",
      "module": "Student Leads",
      "section": "Acquisition Channels",
      "title": "Lead Acquisition Source Filter",
      "badge": "Admissions Counselor • Student Advisory",
      "icon": "fa-bullhorn",
      "description": "Filter candidates by origin: Meta Ads, Google Ads, Website Form, Walk-in, College Fair Data, or Referral. Helps measure marketing campaign performance.",
      "tip": "Combine source filtering with stage filtering to evaluate channel conversion rates.",
      "position": "bottom"
    },
    {
      "url": "/leads/index.php",
      "target": "select[name=\"branch_id\"]",
      "fallback": ".card form",
      "module": "Student Leads",
      "section": "Branch Scope",
      "title": "Campus & Branch Filter Dropdown",
      "badge": "Admissions Counselor • Student Advisory",
      "icon": "fa-building",
      "description": "Switch between Main Admissions Division, Global Advisory Division, or All Branches. Restricts the directory table to show only students belonging to the selected campus.",
      "tip": "Super Admin can inspect any branch; counselors are strictly restricted to their own.",
      "position": "bottom"
    },
    {
      "url": "/leads/index.php",
      "target": "select[name=\"counselor_id\"]",
      "fallback": ".card form",
      "module": "Student Leads",
      "section": "Staff Caseloads",
      "title": "Counselor Caseload Selector",
      "badge": "Admissions Counselor • Student Advisory",
      "icon": "fa-users",
      "description": "Filter student records by individual counselor. The number beside each counselor indicates their active student count, making it easy to identify overloaded staff.",
      "tip": "Select any counselor to review their follow-up compliance and student progression.",
      "position": "bottom"
    },
    {
      "url": "/leads/index.php",
      "target": "a[href*=\"export=csv\"]",
      "fallback": ".page-header",
      "module": "Student Leads",
      "section": "Data Export",
      "title": "Privileged CSV Data Export Button",
      "badge": "Admissions Counselor • Student Advisory",
      "icon": "fa-file-csv",
      "description": "Export authorized student data rosters into formatted CSV spreadsheets. Role permissions strictly block counselors from exporting, ensuring student database privacy.",
      "tip": "Exports respect all active filters applied in the search bar above.",
      "position": "bottom"
    },
    {
      "url": "/leads/index.php",
      "target": "button[onclick*=\"openBulkReassignModal\"]",
      "fallback": ".page-header",
      "module": "Student Leads",
      "section": "Caseload Balancing",
      "title": "Bulk Reassign Leads Between Staff",
      "badge": "Admissions Counselor • Student Advisory",
      "icon": "fa-users-gear",
      "description": "Rebalance student caseloads when a counselor goes on leave or shifts territories. Select candidate checkboxes in the table and reassign them in bulk with automated notifications.",
      "tip": "Reassigning leads transfers all follow-up tasks and history seamlessly to the new counselor.",
      "position": "bottom"
    },
    {
      "url": "/leads/index.php",
      "target": "table.data-table",
      "fallback": ".card",
      "module": "Student Leads",
      "section": "Data Grid",
      "title": "Lead Data Table & Action Menu",
      "badge": "Admissions Counselor • Student Advisory",
      "icon": "fa-table-list",
      "description": "Structured student records displaying tracking Code, Name, Phone (with 1-click WhatsApp dialer), Target Study Program & Country, Stage status, Priority badge, assigned Counselor, and Branch. Click \"Dossier ->\" on any student to open their complete academic dossier.",
      "tip": "Select checkboxes on the left to perform bulk operations such as reassigning multiple leads at once.",
      "position": "top"
    },
    {
      "url": "/leads/add.php",
      "target": ".page-header",
      "fallback": "#main-wrapper",
      "module": "Register Student Lead",
      "section": "Form Overview",
      "title": "Student Registration Master Interface",
      "badge": "Admissions Counselor • Student Advisory",
      "icon": "fa-user-plus",
      "description": "Register prospective student leads with complete academic and counseling metadata. Designed for high operational speed: essential contact fields are mandatory, while academic and test scores can be completed now or updated later in the student dossier.",
      "tip": "Click \"1-Click Fast Add\" in the top right to open the rapid 15-second intake modal anytime.",
      "position": "bottom"
    },
    {
      "url": "/leads/add.php",
      "target": "input[name=\"full_name\"]",
      "fallback": ".card:first-of-type",
      "module": "Register Student Lead",
      "section": "Essential Details",
      "title": "Candidate Name & Normalized Phone",
      "badge": "Admissions Counselor • Student Advisory",
      "icon": "fa-id-card",
      "description": "Enter candidate Full Name, Phone/WhatsApp number, and Email Address. Phone numbers are automatically sanitized and normalized to international format (E.164) with automatic duplicate validation preventing duplicate student entries.",
      "tip": "If a duplicate phone number is detected in active leads, the system blocks insertion with an alert.",
      "position": "bottom"
    },
    {
      "url": "/leads/add.php",
      "target": "select[name=\"preferred_country\"]",
      "fallback": ".card:first-of-type",
      "module": "Register Student Lead",
      "section": "Study Destination",
      "title": "Target Country & Academic Intake Term",
      "badge": "Admissions Counselor • Student Advisory",
      "icon": "fa-earth-americas",
      "description": "Select the student's desired destination country (UK, Canada, Australia, USA, Ireland, Germany, New Zealand) and target intake session (e.g. September 2026, January 2027) to synchronize application deadlines.",
      "tip": "Selecting target country helps managers route the candidate to country-specialist counselors.",
      "position": "bottom"
    },
    {
      "url": "/leads/add.php",
      "target": "select[name=\"branch_id\"], select[name=\"counselor_id\"]",
      "fallback": ".card:first-of-type",
      "module": "Register Student Lead",
      "section": "Caseload Routing",
      "title": "Branch & Counselor Allocation",
      "badge": "Admissions Counselor • Student Advisory",
      "icon": "fa-users-gear",
      "description": "Assign the candidate to an operational campus division and designated counselor. The counselor will immediately receive a notification in their task queue, and the lead will appear in their daily action list.",
      "tip": "Super Admin can assign to any branch; managers are scoped to their own campus.",
      "position": "bottom"
    },
    {
      "url": "/leads/add.php",
      "target": "details:nth-of-type(1)",
      "fallback": ".card:nth-of-type(2)",
      "module": "Register Student Lead",
      "section": "Academic & Tests",
      "title": "Academic History & English Proficiency (IELTS/PTE)",
      "badge": "Admissions Counselor • Student Advisory",
      "icon": "fa-graduation-cap",
      "description": "Expandable section to record student educational history: Highest Qualification, College Name, Passing Year, CGPA/Percentage, Active Backlog counts, Work Experience, and standardized English scores (IELTS, PTE, TOEFL, Duolingo) with individual band breakdowns.",
      "tip": "This section is optional during initial intake and can be filled when the student submits transcripts.",
      "position": "bottom"
    },
    {
      "url": "/leads/add.php",
      "target": "textarea[name=\"notes\"]",
      "fallback": "button[type=\"submit\"]",
      "module": "Register Student Lead",
      "section": "Counselor Notes",
      "title": "Initial Counseling Remarks & Budget",
      "badge": "Admissions Counselor • Student Advisory",
      "icon": "fa-pen-to-square",
      "description": "Record initial counseling remarks: student career aspirations, university preferences, budget constraints, and pending document requirements.",
      "tip": "Initial notes appear on the dashboard action call list to prepare counselors before subsequent calls.",
      "position": "top"
    },
    {
      "url": "/leads/add.php",
      "target": "button[type=\"submit\"], .btn-primary",
      "fallback": ".card:last-of-type",
      "module": "Register Student Lead",
      "section": "Submission",
      "title": "Register Lead & Trigger Stage History",
      "badge": "Admissions Counselor • Student Advisory",
      "icon": "fa-circle-check",
      "description": "Click \"Register Student Lead\" to execute a database transaction that: (1) creates the lead record, (2) generates the unique EDR tracking code, (3) logs initial stage history to \"ENQUIRY\", and (4) notifies the assigned counselor.",
      "tip": "Upon submission, you are automatically redirected to the student's complete academic dossier.",
      "position": "top"
    },
    {
      "url": "/leads/view.php",
      "target": ".card:first-of-type",
      "fallback": "#main-wrapper",
      "module": "Student Dossier & Profile",
      "section": "360° Profile Overview",
      "title": "Student Dossier & 360-Degree Profile",
      "badge": "Admissions Counselor • Student Advisory",
      "icon": "fa-address-card",
      "description": "Welcome to the 360-Degree Student Dossier! Every candidate's academic credentials, contact information, passport details, test scorecards, consultation notes, document uploads, and billing history are unified here on a single comprehensive operational surface.",
      "tip": "Use the quick action buttons on top to log calls, advance admission stages, or issue fee invoices.",
      "position": "bottom"
    },
    {
      "url": "/leads/view.php",
      "target": "button[onclick*=\"openAdvanceStageModal\"], button:has(.fa-stairs)",
      "fallback": ".card:first-of-type",
      "module": "Student Dossier & Profile",
      "section": "Milestone Advancement",
      "title": "Advance Milestone Action Button",
      "badge": "Admissions Counselor • Student Advisory",
      "icon": "fa-forward-step",
      "description": "Click \"Advance Stage\" to progress this student through the 6 standardized consultancy milestones (Enquiry -> Customer -> Application -> Offer Received -> Tuition Fees Paid -> Enrolled). Prompts for consultation remarks and automatically logs a timestamped milestone audit record.",
      "tip": "Advancing to \"Customer\" triggers client billing readiness in the Finance module.",
      "position": "bottom"
    },
    {
      "url": "/leads/view.php",
      "target": "button[onclick*=\"openLogCallModal\"], button:has(.fa-phone-volume)",
      "fallback": ".card:first-of-type",
      "module": "Student Dossier & Profile",
      "section": "Interaction Logging",
      "title": "Log Consultation Call & Note",
      "badge": "Admissions Counselor • Student Advisory",
      "icon": "fa-phone",
      "description": "Record telephone calls, WhatsApp discussions, and in-person counseling sessions directly into the student history. Tag discussion categories (e.g. Visa Guidance, University Selection, Fee Payment) and schedule the next follow-up date.",
      "tip": "Setting a next follow-up date automatically populates the counselor daily tasks queue.",
      "position": "bottom"
    },
    {
      "url": "/leads/view.php",
      "target": ".card:first-of-type",
      "fallback": ".content-area",
      "module": "Student Dossier & Profile",
      "section": "Academic Credentials",
      "title": "Academic Scorecard & English Tests",
      "badge": "Admissions Counselor • Student Advisory",
      "icon": "fa-graduation-cap",
      "description": "Inspect the applicant's educational qualification, college background, graduation percentage, backlogs count, and standardized test scores (IELTS/PTE bands: Listening, Reading, Writing, Speaking).",
      "tip": "Counselors reference this scorecard when matching students to university eligibility criteria.",
      "position": "top"
    },
    {
      "url": "/leads/view.php",
      "target": "div[style*=\"border-top\"], .card:last-of-type",
      "fallback": ".content-area",
      "module": "Student Dossier & Profile",
      "section": "Dossier History",
      "title": "Interaction History & Audit Trail",
      "badge": "Admissions Counselor • Student Advisory",
      "icon": "fa-timeline",
      "description": "Chronological history of every interaction logged with this student: counselor notes, follow-up dates, milestone transitions, and staff signatures. Ensures full accountability across team handovers.",
      "tip": "When reassigning leads, the incoming counselor reviews this chronological timeline.",
      "position": "top"
    },
    {
      "url": "/tasks/index.php",
      "target": ".page-header",
      "fallback": "#main-wrapper",
      "module": "Daily Follow-ups",
      "section": "SLA Engine",
      "title": "Daily Operational Work Queues",
      "badge": "Admissions Counselor • Student Advisory",
      "icon": "fa-list-check",
      "description": "The daily operational workbench for counselors and managers. Features 4 strict SLA queues designed to ensure 0% lead abandonment: Scheduled Follow-ups, Pending Overdue Calls, Today's Fresh Leads, and Older Uncontacted Leads.",
      "tip": "Counselors should clear all Overdue and Scheduled calls before beginning new prospect consultations.",
      "position": "bottom"
    },
    {
      "url": "/tasks/index.php",
      "target": "a[href*=\"queue=pending_followup\"], .card:nth-of-type(2)",
      "fallback": ".card",
      "module": "Daily Follow-ups",
      "section": "Overdue Alerting",
      "title": "Pending Follow-ups (Overdue Calls Queue)",
      "badge": "Admissions Counselor • Student Advisory",
      "icon": "fa-triangle-exclamation",
      "description": "Highlights appointments that have exceeded their scheduled date without resolution. Displays overdue lag days in red to pinpoint neglected inquiries.",
      "tip": "Managers monitor this queue daily to maintain zero delayed inquiries across the team.",
      "position": "top"
    },
    {
      "url": "/tasks/index.php",
      "target": "a[href*=\"queue=today_leads\"], .card:nth-of-type(3)",
      "fallback": ".card",
      "module": "Daily Follow-ups",
      "section": "Fresh Leads Queue",
      "title": "Today's Assigned Leads Queue",
      "badge": "Admissions Counselor • Student Advisory",
      "icon": "fa-bolt",
      "description": "New student inquiries assigned to staff today that haven't been contacted yet. Fast initial outreach (under 15 minutes) dramatically improves candidate trust and admission conversions.",
      "tip": "Call fresh leads immediately upon assignment to establish rapport before competitors.",
      "position": "top"
    },
    {
      "url": "/tasks/index.php",
      "target": "table.data-table",
      "fallback": ".card",
      "module": "Daily Follow-ups",
      "section": "Outreach & Logging",
      "title": "Action List with 1-Click WhatsApp & Outcome Logging",
      "badge": "Admissions Counselor • Student Advisory",
      "icon": "fa-phone-volume",
      "description": "Contact candidates via direct telephone call or 1-click WhatsApp Web. Immediately after the conversation, click \"Log Outcome\" to record consultation notes, select the discussion title, and set the next follow-up date.",
      "tip": "Logging an outcome automatically moves completed tasks out of the active queue.",
      "position": "top"
    },
    {
      "url": "/customers/index.php",
      "target": ".page-header",
      "fallback": "#main-wrapper",
      "module": "Enrolled Clients",
      "section": "Customer Ledger",
      "title": "Enrolled Clients (Invoiced Customers)",
      "badge": "Admissions Counselor • Student Advisory",
      "icon": "fa-certificate",
      "description": "Candidates who have formally engaged institutional services and received official invoices. Displays complete billing metrics: Total Invoiced, Total Collected Revenue, and Outstanding Receivables.",
      "tip": "Filter by Payment Status (Paid, Partial, Unpaid) to manage fee recovery schedules.",
      "position": "bottom"
    },
    {
      "url": "/customers/index.php",
      "target": "select[name=\"payment_status\"]",
      "fallback": ".card form",
      "module": "Enrolled Clients",
      "section": "Payment Filters",
      "title": "Payment Status Filter & Client Ledgers",
      "badge": "Admissions Counselor • Student Advisory",
      "icon": "fa-money-bill-transfer",
      "description": "Filter clients by billing settlement: Fully Paid, Partial Payment, or Unpaid Balances. Direct links allow downloading receipts or recording fee installments.",
      "tip": "Review partial payments before university tuition remittance deadlines.",
      "position": "bottom"
    },
    {
      "url": "/customers/index.php",
      "target": "table.data-table",
      "fallback": ".card",
      "module": "Enrolled Clients",
      "section": "Clients Roster",
      "title": "Enrolled Clients Data Table",
      "badge": "Admissions Counselor • Student Advisory",
      "icon": "fa-table-list",
      "description": "Detailed client directory with Customer Code, Student Name, Contact Number, Enrolled Country & Program, Total Invoiced Amount, Amount Paid, and Balance Due. Click \"View Profile\" or \"Invoices\" for full financial statements.",
      "tip": "Generate installment payment receipts directly from the action menu on the right.",
      "position": "top"
    },
    {
      "url": "/documents/general.php",
      "target": ".page-header",
      "fallback": "#main-wrapper",
      "module": "Country Checklists & Guidelines",
      "section": "Visa SOP Guidelines",
      "title": "Institutional Visa Guidelines & Country Checklists",
      "badge": "Admissions Counselor • Student Advisory",
      "icon": "fa-passport",
      "description": "Official repository of country-specific visa requirements, financial affidavit formats, and statement of purpose (SOP) guidelines for UK Tier 4, Canada SDS, Australia Subclass 500, and USA F-1 visas.",
      "tip": "Share official checklist PDFs with students to avoid visa application rejections.",
      "position": "bottom"
    },
    {
      "url": "/documents/general.php",
      "target": "button[onclick*=\"openUploadModal\"], button:has(.fa-upload), a[href*=\"upload\"]",
      "fallback": ".page-header",
      "module": "Country Checklists & Guidelines",
      "section": "Guideline Upload",
      "title": "Upload Official Guideline or Checklist PDF",
      "badge": "Admissions Counselor • Student Advisory",
      "icon": "fa-cloud-arrow-up",
      "description": "Super Admins and Compliance Officers can publish updated embassy guidelines, checklist templates, or university fee schedules for staff reference.",
      "tip": "Keep embassy visa financial requirement PDFs updated ahead of intake seasons.",
      "position": "bottom"
    },
    {
      "url": "/documents/general.php",
      "target": "div[style*=\"grid-template-columns\"], .card",
      "fallback": ".content-area",
      "module": "Country Checklists & Guidelines",
      "section": "Country Hubs",
      "title": "Country Visa Category Hubs",
      "badge": "Admissions Counselor • Student Advisory",
      "icon": "fa-earth-americas",
      "description": "Select country category pills to filter documents for United Kingdom, Canada, Australia, United States, Ireland, and Germany. View or download guidelines with one click.",
      "tip": "Counselors consult these guidelines during student visa mock interviews.",
      "position": "top"
    },
    {
      "url": "/notifications/index.php",
      "target": ".page-header",
      "fallback": "#main-wrapper",
      "module": "Notification Center",
      "section": "Alert Stream",
      "title": "Real-Time Notification & Action Center",
      "badge": "Admissions Counselor • Student Advisory",
      "icon": "fa-bell",
      "description": "Central notifications hub for real-time operational alerts: fresh web lead assignments, upcoming consultation calls, overdue follow-up warnings, and invoice payments.",
      "tip": "Check notifications first thing every morning to stay aligned with daily priorities.",
      "position": "bottom"
    },
    {
      "url": "/notifications/index.php",
      "target": "div[style*=\"display:flex; gap:8px\"], button:has(.fa-check-double), a[href*=\"mark_all\"]",
      "fallback": ".page-header",
      "module": "Notification Center",
      "section": "Filter Tabs",
      "title": "Not Attended vs Attended Alert Filters",
      "badge": "Admissions Counselor • Student Advisory",
      "icon": "fa-filter",
      "description": "Filter between Not Attended (pending action) and Attended (completed/read) alerts. Click \"Mark All Attended\" to clear your badge counter in 1 click.",
      "tip": "Keep your Not Attended counter at zero to ensure no student inquiries slip through.",
      "position": "bottom"
    },
    {
      "url": "/notifications/index.php",
      "target": ".card, table.data-table",
      "fallback": ".content-area",
      "module": "Notification Center",
      "section": "Alert Stream",
      "title": "Actionable Alerts Stream & Deep Links",
      "badge": "Admissions Counselor • Student Advisory",
      "icon": "fa-bolt",
      "description": "Each notification features a direct link to the relevant student dossier, follow-up task, or billing invoice. Click the link to jump directly into action.",
      "tip": "Clicking an alert link marks it as attended and takes you right to the student dossier.",
      "position": "top"
    }
  ]
};

  // State Management
  let activeRoleKey = 'super_admin';
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
    if (user.is_super_admin || user.role_name === 'super_admin' || user.role_id == 1) {
      return 'super_admin';
    }
    if (user.is_branch_manager || user.role_name === 'manager' || user.role_id == 2) {
      return 'manager';
    }
    return 'counselor';
  }

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

    // 3. Bind click events to Guide buttons across the DOM
    const guideBtns = document.querySelectorAll('#btn-guide-tutorial, #btn-dashboard-guide, .btn-guide-trigger');
    guideBtns.forEach(btn => {
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        window.startInteractiveTutorial();
      });
    });
  });

})();
