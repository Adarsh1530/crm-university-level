# University-Level Student CRM – Client Demo Version

A modern, responsive, enterprise-grade **University-Level Student CRM (Admissions & Recruitment Cloud)** prototype designed for institutional demonstrations, university admissions boards, and client evaluation.

---

## 1. System Overview & Technology Stack

The CRM is built strictly using core web technologies without heavy frontend frameworks, ensuring rapid local deployment on any standard PHP/MySQL stack such as XAMPP:

- **HTML5 & CSS3**: Modern administrative layout, light professional theme with deep navy blue (`#0f172a`, `#1e3a8a`), clean whites, subtle grey borders (`#e2e8f0`), accessible status pills, and responsive breakpoints (desktop, tablet, mobile).
- **JavaScript**: Interactive UI controls, modal management, tab switching, real-time keyboard shortcuts (`Ctrl+K`), and dynamic data filtering.
- **Chart.js**: Rich interactive analytics for lead conversion funnels, admission stage progress, communication channels, and counsellor performance comparison.
- **PHP 8 (Backend)**: Object-oriented PDO database layer with prepared statements, session-based authentication, password hashing (`password_hash`), CSRF protection, and role-based access control.
- **MySQL / MariaDB**: Fully normalized relational schema with 14 tables, foreign keys, timestamps, indexes, and comprehensive realistic sample data.

---

## 2. Local Setup Instructions (XAMPP Environment)

### Step 1: Ensure Apache and MySQL are running
1. Open the **XAMPP Control Panel**.
2. Click **Start** for **Apache** and **MySQL**.

### Step 2: Location
The project is already deployed at:
```
C:\xampp\htdocs\CRM - UNIVERSITY - LEVEL
```

### Step 3: Database Import (phpMyAdmin)
The database name is `university_crm_demo`. The schema and demo data file is located at:
```
database/university_crm_demo.sql
```
To import via phpMyAdmin:
1. Open your browser and navigate to `http://localhost/phpmyadmin/`.
2. Click on the **Import** tab.
3. Choose the file `C:\xampp\htdocs\CRM - UNIVERSITY - LEVEL\database\university_crm_demo.sql`.
4. Click **Import** (or **Go**).

*(Note: The database has already been pre-imported and tested for this demo).*

### Step 4: Access the Application
Open your web browser and navigate to:
```
http://localhost/CRM%20-%20UNIVERSITY%20-%20LEVEL/
```
or
```
http://localhost/CRM - UNIVERSITY - LEVEL/
```

---

## 3. Demo Login Credentials & Persona Switcher

You can log in manually using any of the accounts below, or use the **1-Click Demo Profile buttons** directly on the login screen or via the **"Switch Persona"** menu in the top navigation bar.

| Role | Name | Email | Password | Permissions Scope |
| :--- | :--- | :--- | :--- | :--- |
| **Super Admin** | Dr. Arthur Vance | `superadmin@university.edu` | `admin123` | Full access, user creation, all logs & data |
| **Administrator** | Elena Rostova | `admin@university.edu` | `admin123` | Institutional management & reports |
| **Admissions Manager** | Marcus Sterling | `manager@university.edu` | `admin123` | Staff monitoring, supervision, stage audits |
| **Team Leader** | Priya Sharma | `teamlead@university.edu` | `admin123` | Triage, lead assignment, team targets |
| **Counsellor** | David Miller | `counsellor1@university.edu` | `admin123` | Lead follow-ups, student profile handling |
| **STEM Counsellor** | Sarah Jenkins | `counsellor2@university.edu` | `admin123` | STEM programs & inquiry management |
| **Advisor** | Alex Chen | `counsellor3@university.edu` | `admin123` | International candidate advising |

> **Client Demo Feature:** When signed in, click the **"Switch Persona"** pill in the top navigation bar to instantaneously experience how the CRM adapts to different roles without having to log out.

---

## 4. Key Functional Modules Walkthrough

### 1. Main Dashboard (`/dashboard/index.php`)
- **10 Dynamic KPI Cards**: Total Students, Total Leads, New Enquiries, High/Moderate/Low Priority Leads, Today's Follow-ups, Overdue Follow-ups, Pending Tasks, Confirmed Admissions.
- **4 Chart.js Visualizations**:
  1. *Lead Conversion & Status Breakdown* (Doughnut Chart)
  2. *Students by Admission Stage* (Horizontal Bar Chart)
  3. *Follow-up Communication Modes* (Bar Chart)
  4. *Staff Caseload & Conversion Comparison* (Grouped Bar Chart)
- **Counsellor Overview Table**: Workload, completion counts, and conversion percentages.
- **Recent Registrations**: Quick access to newly enrolled student profiles.
- **Urgent Follow-up Reminders**: Direct 1-click completion from dashboard.
- **Live Institutional Activity Trail**: Audit log stream of actions.

### 2. Student Management (`/students/`)
- **Directory (`index.php`)**: Advanced multi-facet filtering by Course, Country, Staff, Priority, and Stage.
- **Add Student (`add.php`)**: Comprehensive 20-field registration form with auto-generated ID (`STU-2026-xxx`), academic preferences, intake, source, and 50 welcome reward points.
- **Edit Student (`edit.php`)**: Full profile update capability with automatic stage change logging.
- **Student Profile 360° View (`view.php`)**:
  - Interactive **8-Stage Visual Stepper** with percentage completion bar.
  - Tabbed interfaces: *Profile & Academics*, *Stage Transition History*, *Follow-up Logs*, *Checklist Tasks*, *Points & Benefits Ledger*, and *System Audit Stream*.
  - Modal shortcuts to *Advance Stage*, *Log Follow-up*, or *Assign Task*.

### 3. Lead Management (`/leads/`)
- **Pipeline & Inquiries (`index.php`)**: Track prospects across 8 lead statuses (*New Lead*, *Contacted*, *Interested*, *Follow-up Required*, *Application Started*, *Converted*, *Lost*).
- **One-Click Lead Conversion (`convert.php`)**: Converts a prospective lead into an enrolled student record, auto-creates their student ID, credits onboarding points, transfers notes, and links the lead record.

### 4. Follow-up Management (`/followups/`)
- Dedicated follow-up control center categorized by **Today's**, **Overdue**, **Upcoming**, **Completed**, and **Rescheduled**.
- Filter by channel (*Phone Call*, *WhatsApp*, *Email*, *Direct Meeting*), priority, and staff.
- Quick **Complete Follow-up** modal to record student responses and schedule subsequent touchpoints.
- Reschedule tool with reason logging.

### 5. Admission Stage Tracking (`/admissions/`)
- Visual **8-Stage Kanban Pipeline Board** (*Enquiry Received*, *Initial Contact*, *Counselling*, *Course Selection*, *Application Started*, *Documents Submitted*, *Application Processing*, *Admission Confirmed*).
- Status disposition tracking: *In Progress*, *Confirmed*, *On Hold*, *Rejected*, *Admission Lost*.
- One-click stage advancement with automatic stage history audit.

### 6. Staff Management & Staff Monitoring (`/staff/`)
- **Staff Roster (`index.php`)**: Role-based access control, active/inactive account toggles.
- **Executive Staff Monitoring Console (`monitoring.php`)**: Supervision matrix displaying total students assigned, leads handled, follow-ups completed, pending/overdue inquiries, confirmed admissions, and automated workload index rating (*Balanced*, *Moderate*, *Heavy Load*).

### 7. Task Management (`/tasks/`)
- Action checklist categorized by *Document Collection*, *Student Follow-up*, *Application Completion*, *Admission Verification*, and *General Task*.
- Due date tracking with automatic overdue flagging and 1-click completion.

### 8. Points & Benefits Management (`/points/`)
- Student motivation mechanism and counsellor incentive bonuses.
- Reward catalog: *Application Fee Waiver*, *Early Bird Scholarship Voucher*, *Free IELTS/TOEFL Masterclass*, *Airport Welcome Pack*, *Staff Excellence Incentive*.
- Points ledger tracking credits and debits, with redemption authorization.

### 9. Notification Center (`/notifications/`)
- Real-time alerts for new enquiries, overdue follow-ups, document verifications, and confirmed offers.
- Top navigation bell icon with dynamic unread counter badge and dropdown preview.
- Dedicated notification center with unread filtering, mark-as-read, and cleanup tools.

### 10. Reports & Analytics (`/reports/`)
- Analytical intelligence with period filters (*All Time*, *Last 30 Days*, *Last 90 Days*, *Past Year*).
- Visual charts: Course Distribution, Top Countries of Origin, Stage Funnel Velocity, Follow-up Health.
- **1-Click CSV Data Exports** for Students, Leads, Follow-ups, and Staff KPIs.

### 11. Global Search (`/search.php`)
- Omnipresent search bar in top navigation with keyboard shortcut (`Ctrl+K` or `/`).
- Multi-entity querying across student names, student IDs, phone numbers, emails, course titles, universities, and staff names.

### 12. Dynamic Role & Permission Management System (`/staff/permissions.php`)
- **Super Admin Centralized Control:**
  - Dedicated permission matrix console accessible exclusively to Super Admin (`Dr. Arthur Vance`).
  - Full control to enable or disable granular permissions across **all 11 CRM modules**:
    `Main Dashboard`, `Students`, `Leads & Enquiries`, `Follow-ups`, `Admission Stages`, `Staff & Users`, `Staff Monitoring`, `Task Management`, `Points & Benefits`, `Reports & Analytics`, `Notifications`.
- **7-Dimension Granular Permission Matrix:**
  - `View` – Access pages, browse listings, view entity profiles.
  - `Add` – Create new students, leads, follow-ups, tasks, points.
  - `Edit` – Modify existing records, update notes, change dates.
  - `Delete` – Permanently delete records (protected with confirmation).
  - `Assign` – Assign or reassign students and leads to staff members.
  - `Approve` – Convert leads to student profiles, advance admission stages, authorize benefit redemptions.
  - `Export` – Download CSV reports and spreadsheet extracts.
- **Bulk Productivity Controls:**
  - **Select All Permissions** – Grants full privileges across all modules in 1 click.
  - **Remove All Permissions** – Clears all checkboxes in 1 click.
  - **View All Only** – Grants read-only visibility across the CRM.
  - **Toggle Column** – Quickly check/uncheck an entire action column (e.g. Export, Delete).
  - **Toggle Row** – Check/uncheck all actions for a specific module.
  - **Reset to Institutional Defaults** – Instantly restores recommended role baselines.
- **Role-Wise Configuration & Individual User Overrides:**
  - **Role Level:** Configure global permissions for *Admissions Manager*, *Team Leader*, and *Counsellor*.
  - **User Override Level:** Super Admin can grant or revoke custom permissions for a specific individual (e.g., granting David Miller CSV Export capability while keeping all other counsellors restricted).
  - **Revert to Role Defaults:** 1-click button to clear individual user overrides and revert them back to their role's policy.
- **Dynamic End-to-End Enforcement:**
  - **Dynamic Navigation:** Unauthorized sidebar links and sections are automatically hidden.
  - **Action Button Suppression:** Unauthorized action buttons (e.g. *Delete*, *Convert*, *Advance Stage*, *Export CSV*, *Add Student*) are hidden in tables and profile pages.
  - **Strict Backend Protection:** Server-side route validation (`require_permission($module, $action)`) validates every request. If a user tries to access a restricted URL directly, access is immediately blocked with a clear notice.
  - **Super Admin Immunity:** Super Admin privileges are non-revocable (`is_super_admin()` bypass) and cannot be modified by any operator.
  - **Instant Live Reflection:** Changes take effect immediately without requiring users to log out or restart.
  - **Security Audit Logging:** All permission modifications and resets are automatically logged in `activity_logs`.

---

## 5. Database Schema & Tables

The `university_crm_demo` database contains 16 structured tables:
1. `roles`: Role definitions and administrative descriptions.
2. `users`: CRM operators with bcrypt password hashes.
3. `role_permissions`: Dynamic module-action permissions matrix per role.
4. `user_permissions`: Individual user permission overrides.
5. `universities`: Partner and destination universities.
6. `courses`: Academic programs, degree levels, and annual fee schedules.
7. `admission_stages`: 8 workflow pipeline milestones.
8. `students`: 50+ rich applicant profiles with complete attributes.
9. `leads`: 30+ prospective inquiries with marketing attribution.
10. `followups`: 35+ scheduled and completed communication consultations.
11. `student_stage_history`: Chronological audit trail of student stage movements.
12. `tasks`: 25+ operational checklist assignments.
13. `benefits`: Reward and scholarship redemption catalog.
14. `points_transactions`: Transactional points ledger.
15. `notifications`: System alerts and reminder broadcasts.
16. `activity_logs`: Complete security and operational audit trail.

---

## 6. Directory Structure

```
university-crm/ (C:\xampp\htdocs\CRM - UNIVERSITY - LEVEL\)
├── index.php                      # Root router
├── login.php                      # Enterprise sign-in page with 1-click demo logins
├── logout.php                     # Session termination
├── search.php                     # Global multi-entity search
├── switch_role.php                # Demo role persona switcher
├── config/
│   ├── database.php               # PDO database connection
│   └── helpers.php                # URL resolution, badges, CSRF, formatting
├── includes/
│   ├── auth.php                   # Authentication & dynamic RBAC permissions engine
│   ├── header.php                 # Top navigation, search, notifications, persona switcher
│   ├── sidebar.php                # Dynamic collapsible sidebar with permission filtering
│   └── footer.php                 # Footer bar & script imports
├── dashboard/
│   └── index.php                  # Executive dashboard with KPI cards & Chart.js charts
├── students/
│   ├── index.php                  # Student directory & multi-facet filtering (permission protected)
│   ├── add.php                    # 20-field registration form (permission protected)
│   ├── edit.php                   # Profile modification (permission protected)
│   ├── view.php                   # 360-degree profile with 8-stage stepper & tabs
│   └── delete.php                 # Safe student removal (permission protected)
├── leads/
│   ├── index.php                  # Lead directory & status tabs (permission protected)
│   ├── add.php                    # Lead creation form (permission protected)
│   ├── edit.php                   # Lead modification (permission protected)
│   ├── view.php                   # Lead detail view & follow-up history
│   ├── convert.php                # One-click lead to student conversion (permission protected)
│   └── delete.php                 # Lead deletion (permission protected)
├── followups/
│   ├── index.php                  # Follow-up hub (permission protected)
│   ├── add.php                    # Follow-up scheduling form (permission protected)
│   ├── edit.php                   # Follow-up edit form (permission protected)
│   ├── complete.php               # Mark completed with student response (permission protected)
│   └── reschedule.php             # Move date/time with reason logging (permission protected)
├── admissions/
│   ├── index.php                  # 8-Stage visual Kanban pipeline board & table (permission protected)
│   └── update_stage.php           # Stage advance controller & milestone points (permission protected)
├── staff/
│   ├── index.php                  # Staff roster & management (permission protected)
│   ├── add.php                    # Add staff account (permission protected)
│   ├── edit.php                   # Modify staff details (permission protected)
│   ├── monitoring.php             # Executive staff supervision matrix (permission protected)
│   ├── permissions.php            # Super Admin Dynamic Role & Permissions Matrix console
│   └── toggle_status.php          # Activate / Deactivate staff (permission protected)
├── tasks/
│   ├── index.php                  # Task management & filter tabs (permission protected)
│   ├── add.php                    # Add task (permission protected)
│   ├── edit.php                   # Edit task (permission protected)
│   └── status.php                 # One-click task status update (permission protected)
├── points/
│   ├── index.php                  # Points & benefits catalog & balance leaderboard (permission protected)
│   ├── add.php                    # Award / log points (permission protected)
│   └── redeem.php                 # Claim benefit & debit points (permission protected)
├── notifications/
│   ├── index.php                  # Notification center & filters (permission protected)
│   └── mark_read.php              # Mark read / clear notifications (permission protected)
├── reports/
│   ├── index.php                  # Analytics charts & performance tables (permission protected)
│   └── export.php                 # CSV exporter for students, leads, follow-ups, staff (permission protected)
├── assets/
│   ├── css/
│   │   └── style.css              # Master enterprise CRM stylesheet with permission matrix styling
│   └── js/
│       └── main.js                # Core UI interactions, modals, tabs, search shortcut
└── database/
    ├── university_crm_demo.sql    # Complete SQL schema with role & user permissions tables
    ├── migrate_permissions.php    # Standalone permission migration script
    └── seed.php                   # Database generator / seeder
```

---

## 7. How to Demonstrate Dynamic Permissions Live

Follow these simple steps during client demos to showcase the dynamic permission system:

### Demo Scenario 1: Restricting Counsellor from Tasks or Admissions
1. Sign in as **Dr. Arthur Vance** (Super Admin) or use **Switch Persona &rarr; Super Admin**.
2. Go to **Role Permissions** in the sidebar (or navigate to `/staff/permissions.php`).
3. Click on the **Counsellor** tab.
4. Uncheck **View** for **Task Management** (or **Admission Stages**).
5. Click **"Save & Apply Changes Now"**.
6. Switch persona to **David Miller** (Counsellor) via the top nav pill.
7. **Notice:**
   - **Task Management** has completely vanished from David's sidebar!
   - If David enters `http://localhost/CRM%20-%20UNIVERSITY%20-%20LEVEL/tasks/index.php` directly in the URL bar, the backend instantly rejects access with a red alert banner: *"Access Denied: You do not have permission to View in the tasks module."*

### Demo Scenario 2: Revoking Delete Rights from Admissions Manager
1. In the **Role Permissions** console, select **Admissions Manager**.
2. Uncheck **Delete** on **Student Management** and click Save.
3. Switch persona to **Marcus Sterling** (Admissions Manager).
4. Navigate to **Students**.
5. **Notice:** The red trash can Delete buttons are no longer rendered anywhere on the table or student profiles. Direct URL deletion is also permanently blocked.

### Demo Scenario 3: Granting a Special User Override
1. In the **Role Permissions** console, switch to the **Individual User Overrides** tab.
2. Select **David Miller** (Counsellor) from the dropdown.
3. Notice David initially inherits his role's baseline permissions.
4. Check **View** and **Export** under **Reports & Analytics** specifically for David.
5. Click **"Save Individual Overrides"**.
6. Switch persona to **David Miller**.
7. **Notice:** Reports & Analytics now appears in David's sidebar and he can view analytics charts and export CSV reports, whereas other counsellors (e.g. Sarah Jenkins) remain restricted!
8. Click **"Reset to Role Defaults"** at any time to instantly revert David's account back to standard counsellor permissions.

---

## 7. Client Demonstration Notes

This prototype is prepared specifically for demonstration and client approval:
- **All numbers, charts, and metrics are live** and dynamically queried from the demo database.
- **Every button, tab, modal, filter, search bar, and action is functional**.
- **Data entered into forms is saved to MySQL** in real time.
- Workflows, permissions, fields, and visual styles are cleanly structured so adjustments can easily be made following client feedback.
