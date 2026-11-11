<?php
/**
 * EdrootsCRM Comprehensive Seed Data
 * Seeds organization, branches, users, permissions, leads, invoices, follow-ups, and documents
 */

require_once __DIR__ . '/../config/database.php';

echo "========================================================\n";
echo "SEEDING EDROOTS-CRM ENTERPRISE DATA\n";
echo "========================================================\n";

$pdo->beginTransaction();

try {
    // 1. Company
    $pdo->exec("
        INSERT INTO companies (name, email, phone, website, address)
        VALUES ('Edroots Global Education Pvt. Ltd.', 'admissions@edroots.com', '+91 484 288 9000', 'https://edroots.com', 'Edroots Tower, MG Road, Kochi, Kerala 682016, India')
    ");
    $companyId = $pdo->lastInsertId();
    echo "  [OK] Company seeded\n";

    // 2. Branches
    $branchesData = [
        ['Kochi', 'KOC', 'Kochi', 'Kerala', 'India', '+91 484 288 9001', 'kochi@edroots.com', 'Edroots Tower, MG Road, Kochi'],
        ['Calicut', 'CLT', 'Calicut', 'Kerala', 'India', '+91 495 277 8001', 'calicut@edroots.com', 'Skyline Plaza, Mavoor Road, Calicut'],
        ['Kannur', 'KNR', 'Kannur', 'Kerala', 'India', '+91 497 270 7001', 'kannur@edroots.com', 'Fort Road Commercial Centre, Kannur'],
        ['Kasaragod', 'KSD', 'Kasaragod', 'Kerala', 'India', '+91 499 423 6001', 'kasaragod@edroots.com', 'Bank Road Junction, Kasaragod'],
        ['Kanhangad', 'KHD', 'Kanhangad', 'Kerala', 'India', '+91 467 220 5001', 'kanhangad@edroots.com', 'Main Street Mall, Kanhangad'],
        ['Perinthalmanna', 'PMNA', 'Perinthalmanna', 'Kerala', 'India', '+91 493 322 4001', 'perinthalmanna@edroots.com', 'Bypass Junction, Perinthalmanna'],
        ['Trivandrum', 'TVM', 'Trivandrum', 'Kerala', 'India', '+91 471 233 3001', 'trivandrum@edroots.com', 'Statue Junction, MG Road, Trivandrum'],
        ['Mangalore', 'MNG', 'Mangalore', 'Karnataka', 'India', '+91 824 244 2001', 'mangalore@edroots.com', 'KSR Road, Hampankatta, Mangalore'],
        ['London', 'LDN', 'London', 'Greater London', 'United Kingdom', '+44 20 7946 0991', 'london@edroots.com', '120 Fenchurch Street, London EC3M 5BA']
    ];

    $insBranch = $pdo->prepare("INSERT INTO branches (name, code, city, state, country, phone, email, address, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Active')");
    $branchMap = [];
    foreach ($branchesData as $b) {
        $insBranch->execute($b);
        $branchMap[$b[1]] = $pdo->lastInsertId();
    }
    echo "  [OK] 9 Company branches seeded\n";

    // 3. Roles
    $rolesData = [
        ['super_admin', 'Super Admin', 'Full administrative authority across all company branches and modules.'],
        ['manager', 'Admissions Manager', 'Manages admissions operations, counselors, lead assignments, and reporting.'],
        ['counselor', 'Counselor / Academic Advisor', 'Strictly isolated access to assigned student leads, follow-ups, and tasks.']
    ];
    $insRole = $pdo->prepare("INSERT INTO roles (name, label, description) VALUES (?, ?, ?)");
    $roleMap = [];
    foreach ($rolesData as $r) {
        $insRole->execute($r);
        $roleMap[$r[0]] = $pdo->lastInsertId();
    }
    echo "  [OK] 3 Roles seeded\n";

    // 4. Role Permissions
    $modules = [
        'dashboard', 'college_data', 'web_leads', 'leads', 'customers',
        'admissions', 'tasks', 'invoices', 'payments', 'documents',
        'branches', 'staff', 'roles', 'reports', 'archive', 'notifications', 'settings'
    ];

    $insPerm = $pdo->prepare("
        INSERT INTO role_permissions 
        (role_id, module, can_view, can_add, can_edit, can_delete, can_assign, can_reassign, can_approve, can_export, can_archive, can_restore)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($modules as $mod) {
        // Super Admin gets 1 for all
        $insPerm->execute([$roleMap['super_admin'], $mod, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1]);

        // Branch Manager permissions
        $mgrCanAssign = in_array($mod, ['leads', 'tasks', 'web_leads', 'college_data']) ? 1 : 0;
        $mgrCanReassign = in_array($mod, ['leads', 'tasks']) ? 1 : 0;
        $mgrCanExport = in_array($mod, ['leads', 'customers', 'college_data', 'reports', 'invoices']) ? 1 : 0;
        $mgrCanDelete = in_array($mod, ['web_leads']) ? 1 : 0;
        $mgrCanArchive = in_array($mod, ['leads', 'college_data', 'customers']) ? 1 : 0;
        $mgrCanRestore = in_array($mod, ['leads', 'college_data', 'customers']) ? 1 : 0;
        $mgrCanApprove = in_array($mod, ['admissions', 'leads', 'invoices']) ? 1 : 0;
        $mgrCanView = in_array($mod, ['branches', 'roles', 'settings']) ? 0 : 1;
        $insPerm->execute([$roleMap['manager'], $mod, $mgrCanView, 1, 1, $mgrCanDelete, $mgrCanAssign, $mgrCanReassign, $mgrCanApprove, $mgrCanExport, $mgrCanArchive, $mgrCanRestore]);

        // Counselor permissions: strict isolation, NO assign, NO reassign, NO export, NO delete, NO archive, NO restore
        $cslCanView = in_array($mod, ['dashboard', 'leads', 'customers', 'admissions', 'tasks', 'documents', 'notifications']) ? 1 : 0;
        $cslCanAdd = in_array($mod, ['leads', 'tasks', 'documents']) ? 1 : 0;
        $cslCanEdit = in_array($mod, ['leads', 'tasks', 'admissions', 'documents']) ? 1 : 0;
        $insPerm->execute([$roleMap['counselor'], $mod, $cslCanView, $cslCanAdd, $cslCanEdit, 0, 0, 0, 0, 0, 0, 0]);
    }
    echo "  [OK] Module permissions seeded for all roles\n";

    // 5. Interaction Titles
    $interactionTitles = [
        ['Call Not Connected', 2, 'Candidate unreachable or call disconnected; rapid re-attempt required.', 1],
        ['Information Shared', 5, 'Shared university brochures, fee structure, and eligibility requirements.', 2],
        ['Documents Pending', 7, 'Awaiting academic transcripts, passport copy, or English score card.', 3],
        ['Application Initiated', 10, 'SOP drafting in progress or application portal submitted.', 4],
        ['Visa Filing', 14, 'CAS received; financial sponsorship verification and visa filing underway.', 5],
        ['Need to Archive', 1, 'Candidate postponed intake, disqualified, or unresponsive.', 6]
    ];
    $insTitle = $pdo->prepare("INSERT INTO interaction_titles (title, max_duration_days, description, sort_order) VALUES (?, ?, ?, ?)");
    $titleMap = [];
    foreach ($interactionTitles as $it) {
        $insTitle->execute($it);
        $titleMap[$it[0]] = $pdo->lastInsertId();
    }
    echo "  [OK] Interaction titles with max duration rules seeded\n";

    // 6. Intake Cycles
    $intakes = [
        ['January 2026', 2026, 'January', 1],
        ['May 2026', 2026, 'May', 2],
        ['September 2026', 2026, 'September', 3],
        ['January 2027', 2027, 'January', 4],
        ['May 2027', 2027, 'May', 5],
        ['September 2027', 2027, 'September', 6]
    ];
    $insIntake = $pdo->prepare("INSERT INTO intake_cycles (name, year, term, sort_order) VALUES (?, ?, ?, ?)");
    $intakeMap = [];
    foreach ($intakes as $in) {
        $insIntake->execute($in);
        $intakeMap[$in[0]] = $pdo->lastInsertId();
    }
    echo "  [OK] Configurable intake cycles seeded\n";

    // 7. Six Standard Admission Stages
    $stages = [
        [1, 'ENQUIRY', 'enquiry', 'Fresh lead created, initial outreach, course brochure dispatch pending.', '#5955D1'],
        [2, 'CUSTOMER', 'customer', 'Student responded, genuine interest established, active counseling underway.', '#009966'],
        [3, 'APPLICATION', 'application', 'Passport and academic transcripts submitted, SOP and university application initiated/submitted.', '#2563EB'],
        [4, 'OFFER RECEIVED', 'offer_received', 'Conditional or unconditional university offer received.', '#F5A70D'],
        [5, 'TUITION FEES PAID', 'fees_paid', 'Initial tuition deposit or CAS processing fee paid.', '#7008E7'],
        [6, 'ENROLLED', 'enrolled', 'Visa issued, departure scheduled, university enrollment confirmed.', '#009966']
    ];
    $insStage = $pdo->prepare("INSERT INTO admission_stages (step_number, name, code, description, color_code) VALUES (?, ?, ?, ?, ?)");
    $stageMap = [];
    foreach ($stages as $st) {
        $insStage->execute($st);
        $stageMap[$st[1]] = $pdo->lastInsertId();
    }
    echo "  [OK] Exactly 6 standard admission stages seeded\n";

    // 8. Users
    $passwordHash = password_hash('admin123', PASSWORD_BCRYPT);
    $usersData = [
        // Super Admin
        [null, $roleMap['super_admin'], null, 'Noufal Peediyakkal', 'noufal@edroots.in', $passwordHash, '+91 4933 224333', 'Super Admin & Case Manager'],
        // Admissions Manager (Single Manager)
        [$branchMap['PMNA'], $roleMap['manager'], null, 'Vidya Harish', 'vidya@edroots.in', $passwordHash, '+91 9074 134910', 'Admissions & Branch Head'],
        // Admissions Counselors & Advisors (All reporting to Vidya Harish)
        [$branchMap['KHD'], $roleMap['counselor'], 2, 'Athira Ashok', 'athira@edroots.in', $passwordHash, '+91 8547 597476', 'Senior Academic Counselor'],
        [$branchMap['KOC'], $roleMap['counselor'], 2, 'Syamily S', 'syamily@edroots.in', $passwordHash, '+91 9633 371111', 'Admissions Advisor & Counselor'],
        [$branchMap['CLT'], $roleMap['counselor'], 2, 'Ambili CO', 'ambili@edroots.in', $passwordHash, '+91 9895 716710', 'Academic Counselor'],
        [$branchMap['KOC'], $roleMap['counselor'], 2, 'Sharanya M P', 'sharanya@edroots.in', $passwordHash, '+91 9947 921306', 'Academic Counselor'],
        [$branchMap['CLT'], $roleMap['counselor'], 2, 'Soumya Ajith', 'soumya@edroots.in', $passwordHash, '+91 9061 253555', 'Academic Counselor']
    ];

    $insUser = $pdo->prepare("INSERT INTO users (branch_id, role_id, manager_id, name, email, password, phone, designation, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Active')");
    $userMap = [];
    foreach ($usersData as $u) {
        $insUser->execute($u);
        $userMap[$u[3]] = $pdo->lastInsertId();
    }

    // Update branches with manager IDs
    $pdo->prepare("UPDATE branches SET manager_id = ? WHERE code IN ('PMNA', 'KOC', 'CLT')")->execute([$userMap['Vidya Harish']]);
    echo "  [OK] 7 Organizational users seeded with single Admissions Manager hierarchy\n";

    // 9. Raw College Data Pool
    $collegeDataRows = [
        ['Rohan Mathew', '+91 98471 11001', '9847111001', 'rohan.mathew@gmail.com', 'St. Alberts College, Ernakulam', 'B.Sc Computer Science', 2024, 'Kochi', 'Interested in UK Masters in Cybersecurity', 'Campus Seminar 2024', $branchMap['KOC'], 'NO', null, null, null],
        ['Sneha George', '+91 98471 11002', '9847111002', 'sneha.george@gmail.com', 'Sacred Heart College, Thevara', 'B.Com Finance', 2025, 'Kochi', 'Evaluating Canada & Ireland Business Analytics', 'Academic Fair Kochi', $branchMap['KOC'], 'NO', null, null, null],
        ['Abhishek Menon', '+91 98471 11003', '9847111003', 'abhishek.menon@gmail.com', 'Farook College, Kozhikode', 'BBA International Business', 2024, 'Calicut', 'Needs scholarship guidance for Australia', 'Campus Expo Calicut', $branchMap['CLT'], 'NO', null, null, null],
        ['Meera Krishnan', '+91 98471 11004', '9847111004', 'meera.k@gmail.com', 'Devagiri College, Calicut', 'B.Sc Biotechnology', 2025, 'Calicut', 'Interested in Germany / Ireland Life Sciences', 'Campus Expo Calicut', $branchMap['CLT'], 'NO', null, null, null],
        ['Arjun Pillai', '+91 98471 11005', '9847111005', 'arjun.pillai@gmail.com', 'Rajagiri School of Engineering, Kochi', 'B.Tech Mechanical', 2024, 'Kochi', 'Targeting UK Automotive Engineering', 'College Seminar 2024', $branchMap['KOC'], 'NO', null, null, null],
        ['Fathima Nizar', '+91 98471 11006', '9847111006', 'fathima.nizar@gmail.com', 'MES College, Mampad', 'B.Com Computer Applications', 2024, 'Malappuram', 'Interested in UK MBA programs', 'Educational Expo', $branchMap['CLT'], 'NO', null, null, null],
        ['Gokul Das', '+91 98471 11007', '9847111007', 'gokul.das@gmail.com', 'Govt Engineering College, Thrissur', 'B.Tech Civil', 2023, 'Thrissur', 'Targeting New Zealand Construction Management', 'Campus Fair', $branchMap['KOC'], 'NO', null, null, null]
    ];

    $insCollege = $pdo->prepare("
        INSERT INTO college_data 
        (student_name, mobile_number, phone_normalized, email, college_name, degree, passout_year, location, comments, data_source, branch_id, is_converted, converted_lead_id, converted_by, converted_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    foreach ($collegeDataRows as $cd) {
        $insCollege->execute($cd);
    }
    echo "  [OK] 7 Raw College Data records seeded\n";

    // 10. Digital Lead Staging Queue (Website, Facebook, Google Ads)
    $webLeadsData = [
        ['Nikhil Varma', '+91 98472 22001', '9847222001', 'nikhil.varma@outlook.com', 'Kochi, Kerala', 'MSc Data Science', 'University of Manchester', 'UK', $branchMap['KOC'], 'Facebook Lead Ads - Fall 2026', 'Staged'],
        ['Anjali Nair', '+91 98472 22002', '9847222002', 'anjali.nair99@gmail.com', 'Calicut, Kerala', 'Master of Public Health', 'Monash University', 'Australia', $branchMap['CLT'], 'Google Ads - Australia Healthcare', 'Staged'],
        ['Kiran Joseph', '+91 98472 22003', '9847222003', 'kiran.joseph@yahoo.com', 'Kottayam, Kerala', 'MBA Global Banking', 'Dublin City University', 'Ireland', $branchMap['KOC'], 'Website Contact Form', 'Staged'],
        ['Reshma Ravindran', '+91 98472 22004', '9847222004', 'reshma.r@gmail.com', 'Kannur, Kerala', 'MSc Artificial Intelligence', 'University of Leeds', 'UK', $branchMap['KNR'], 'Instagram Ad Campaign', 'Staged']
    ];

    $insWebLead = $pdo->prepare("
        INSERT INTO web_leads 
        (student_name, mobile_number, phone_normalized, email, city_state, target_course, target_university, target_country, nearest_branch_id, source_campaign, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    foreach ($webLeadsData as $wl) {
        $insWebLead->execute($wl);
    }
    echo "  [OK] 4 Staged digital leads seeded\n";

    // 11. Active Leads Table
    $leadsData = [
        // Kochi Leads (Assigned to David Miller)
        [
            'LED-2026-0001', 'Adithya R. Nair', '+91 98473 33001', '9847333001', '+91 484 255 1101', 'adithya.nair@gmail.com',
            'Flat 4B, Skyline Riverdale, Tripunithura', 'Kochi', 'Kerala', $branchMap['KOC'], $userMap['David Miller'],
            'B.Tech Computer Science', 'Model Engineering College, Kochi', 2024,
            'Yes', 'N8923412', '2031-08-15', 'ABCDE1234F',
            'Yes', 'No', 'No', 7.5, 8.0, 7.5, 7.0, 7.5, null, null, null, null, null,
            'UK', 'University of Bristol', 'MSc Advanced Computer Science', $intakeMap['September 2026'], 'September 2026',
            'High', 'WALK IN', 'College Data', $stageMap['APPLICATION'], 'Active',
            0, null, null,
            date('Y-m-d H:i:s', strtotime('-1 day')), date('Y-m-d'), 1
        ],
        [
            'LED-2026-0002', 'Benjamin Paul', '+91 98473 33002', '9847333002', null, 'benjamin.paul@gmail.com',
            'House No. 12, Panampilly Nagar', 'Kochi', 'Kerala', $branchMap['KOC'], $userMap['David Miller'],
            'B.Com Finance & Taxation', 'St. Teresa’s College, Ernakulam', 2024,
            'Yes', 'P5432109', '2030-05-20', 'BCDEF2345G',
            'Yes', 'No', 'No', 7.0, 7.5, 7.0, 6.5, 7.0, null, null, null, null, null,
            'Ireland', 'Trinity College Dublin', 'MSc Finance & Investment', $intakeMap['September 2026'], 'September 2026',
            'High', 'TELE', 'Facebook', $stageMap['OFFER RECEIVED'], 'Active',
            1, 'CUST-2026-001', date('Y-m-d H:i:s', strtotime('-5 days')),
            date('Y-m-d H:i:s', strtotime('-3 days')), date('Y-m-d', strtotime('+3 days')), 1
        ],
        [
            'LED-2026-0003', 'Diya Lakshmi', '+91 98473 33003', '9847333003', null, 'diya.lakshmi@gmail.com',
            'Rose Villa, Kakkanad', 'Kochi', 'Kerala', $branchMap['KOC'], $userMap['David Miller'],
            'B.Sc Nursing', 'Amrita Institute of Medical Sciences', 2023,
            'Yes', 'M1234987', '2029-11-12', 'CDEFG3456H',
            'No', 'Yes', 'No', null, null, null, null, null, 68, 70, 67, 65, 70,
            'Australia', 'Deakin University', 'Master of Nursing Practice', $intakeMap['February 2026'] ?? $intakeMap['January 2026'], 'January 2026',
            'High', 'WALK IN', 'Walk-in', $stageMap['TUITION FEES PAID'], 'Active',
            1, 'CUST-2026-002', date('Y-m-d H:i:s', strtotime('-10 days')),
            date('Y-m-d H:i:s', strtotime('-2 days')), date('Y-m-d', strtotime('+4 days')), 1
        ],
        [
            'LED-2026-0004', 'Joel Mathew', '+91 98473 33004', '9847333004', null, 'joel.mathew@gmail.com',
            'Grace Bhavan, Aluva', 'Kochi', 'Kerala', $branchMap['KOC'], $userMap['David Miller'],
            'B.Tech Mechanical Engineering', 'Mar Athanasius College of Engineering', 2024,
            'Yes', 'R7654321', '2032-01-10', 'DEFGH4567I',
            'Yes', 'No', 'No', 6.5, 7.0, 6.5, 6.0, 6.5, null, null, null, null, null,
            'UK', 'University of Birmingham', 'MSc Robotics and Automation', $intakeMap['September 2026'], 'September 2026',
            'Moderate', 'TELE-WALK IN', 'Website Direct', $stageMap['ENQUIRY'], 'Active',
            0, null, null,
            date('Y-m-d H:i:s', strtotime('-5 days')), date('Y-m-d', strtotime('-2 days')), 1 // Overdue follow-up!
        ],
        [
            'LED-2026-0005', 'Kavya Suresh', '+91 98473 33005', '9847333005', null, 'kavya.suresh@gmail.com',
            'Anugraha, Vyttila', 'Kochi', 'Kerala', $branchMap['KOC'], $userMap['David Miller'],
            'BBA', 'SCMS Cochin School of Business', 2025,
            'Applied', null, null, null,
            'Awaiting Result', 'No', 'No', null, null, null, null, null, null, null, null, null, null,
            'Canada', 'Concordia University', 'Master of Management', $intakeMap['January 2027'], 'January 2027',
            'Moderate', 'TELE', 'Google Ads', $stageMap['ENQUIRY'], 'Active',
            0, null, null,
            null, date('Y-m-d'), 0 // Assigned today, no follow-up yet -> Today Leads!
        ],
        [
            'LED-2026-0006', 'Madhav Mohan', '+91 98473 33006', '9847333006', null, 'madhav.mohan@gmail.com',
            'Krishna Kripa, Edappally', 'Kochi', 'Kerala', $branchMap['KOC'], $userMap['David Miller'],
            'B.Sc Physics', 'Maharajas College, Ernakulam', 2024,
            'No', null, null, null,
            'No', 'No', 'No', null, null, null, null, null, null, null, null, null, null,
            'Germany', 'TU Munich', 'MSc Applied Geophysics', $intakeMap['September 2026'], 'September 2026',
            'Low', 'TELE', 'Direct Reference', $stageMap['ENQUIRY'], 'Active',
            0, null, null,
            null, null, 0 // Assigned 4 days ago, never followed up -> Pending Leads!
        ],

        // Kochi Leads (Assigned to Sarah Jenkins)
        [
            'LED-2026-0007', 'Sandra Varghese', '+91 98473 33007', '9847333007', null, 'sandra.v@gmail.com',
            'Hill View, Thrikkakara', 'Kochi', 'Kerala', $branchMap['KOC'], $userMap['Sarah Jenkins'],
            'B.Sc Psychology', 'St. Teresa’s College', 2024,
            'Yes', 'K9876543', '2031-03-14', 'EFGHI5678J',
            'Yes', 'No', 'No', 7.5, 8.0, 7.5, 7.0, 7.5, null, null, null, null, null,
            'UK', 'King’s College London', 'MSc Clinical Psychology', $intakeMap['September 2026'], 'September 2026',
            'High', 'WALK IN', 'Walk-in', $stageMap['ENROLLED'], 'Active',
            1, 'CUST-2026-003', date('Y-m-d H:i:s', strtotime('-20 days')),
            date('Y-m-d H:i:s', strtotime('-1 day')), date('Y-m-d'), 1 // Scheduled today!
        ],
        [
            'LED-2026-0008', 'Vishnu Prasad', '+91 98473 33008', '9847333008', null, 'vishnu.prasad@gmail.com',
            'Pournami, Kaloor', 'Kochi', 'Kerala', $branchMap['KOC'], $userMap['Sarah Jenkins'],
            'B.Tech Electronics', 'CUSAT, Kochi', 2023,
            'Yes', 'T3456789', '2030-07-22', 'FGHIJ6789K',
            'No', 'Yes', 'No', null, null, null, null, null, 72, 75, 70, 72, 71,
            'Ireland', 'University College Dublin', 'ME Electronic Systems', $intakeMap['September 2026'], 'September 2026',
            'High', 'TELE', 'Facebook', $stageMap['CUSTOMER'], 'Active',
            0, null, null,
            date('Y-m-d H:i:s', strtotime('-4 days')), date('Y-m-d', strtotime('-1 day')), 1 // Overdue follow-up!
        ],
        [
            'LED-2026-0009', 'Nandana S.', '+91 98473 33009', '9847333009', null, 'nandana.s@gmail.com',
            'Surabhi, Fort Kochi', 'Kochi', 'Kerala', $branchMap['KOC'], $userMap['Sarah Jenkins'],
            'B.Arch', 'College of Architecture, Trivandrum', 2024,
            'Yes', 'Y4567890', '2032-09-09', 'GHIJK7890L',
            'Awaiting Result', 'No', 'No', null, null, null, null, null, null, null, null, null, null,
            'UK', 'University of Edinburgh', 'MSc Urban Environmental Design', $intakeMap['January 2027'], 'January 2027',
            'Moderate', 'WALK IN', 'College Data', $stageMap['ENQUIRY'], 'Active',
            0, null, null,
            null, date('Y-m-d'), 0 // Today Leads!
        ],

        // Calicut Leads (Assigned to Alex Chen)
        [
            'LED-2026-0010', 'Muhammed Nihal', '+91 98473 33010', '9847333010', null, 'nihal.m@gmail.com',
            'Al Madeena, Mavoor Road', 'Calicut', 'Kerala', $branchMap['CLT'], $userMap['Alex Chen'],
            'B.Com Foreign Trade', 'Farook College, Calicut', 2024,
            'Yes', 'W2345678', '2031-10-30', 'HIJKL8901M',
            'Yes', 'No', 'No', 7.0, 7.5, 7.0, 6.5, 7.0, null, null, null, null, null,
            'UK', 'University of Leeds', 'MSc International Business', $intakeMap['September 2026'], 'September 2026',
            'High', 'WALK IN', 'Walk-in', $stageMap['CUSTOMER'], 'Active',
            0, null, null,
            date('Y-m-d H:i:s', strtotime('-2 days')), date('Y-m-d'), 1 // Scheduled today!
        ],
        [
            'LED-2026-0011', 'Devika Nambiar', '+91 98473 33011', '9847333011', null, 'devika.nambiar@gmail.com',
            'Thejas, Civil Station', 'Calicut', 'Kerala', $branchMap['CLT'], $userMap['Alex Chen'],
            'B.Tech Food Technology', 'Kerala Agricultural University', 2024,
            'Yes', 'E9871234', '2029-04-18', 'IJKLM9012N',
            'Yes', 'No', 'No', 6.5, 7.0, 6.5, 6.5, 6.5, null, null, null, null, null,
            'Australia', 'RMIT University', 'Master of Food Science', $intakeMap['September 2026'], 'September 2026',
            'Moderate', 'TELE', 'Google Ads', $stageMap['ENQUIRY'], 'Active',
            0, null, null,
            null, null, 0 // Pending Leads!
        ],

        // Calicut Leads (Assigned to Ananya Nair)
        [
            'LED-2026-0012', 'Aiswarya Raj', '+91 98473 33012', '9847333012', null, 'aiswarya.raj@gmail.com',
            'Sreevalsam, Medical College PO', 'Calicut', 'Kerala', $branchMap['CLT'], $userMap['Ananya Nair'],
            'Pharm.D', 'Calicut Medical College', 2023,
            'Yes', 'Q1237890', '2030-12-05', 'JKLMN0123O',
            'Yes', 'No', 'No', 7.5, 8.0, 7.5, 7.0, 7.5, null, null, null, null, null,
            'Ireland', 'University College Cork', 'MSc Clinical Pharmacy', $intakeMap['September 2026'], 'September 2026',
            'High', 'WALK IN', 'Direct Reference', $stageMap['OFFER RECEIVED'], 'Active',
            1, 'CUST-2026-004', date('Y-m-d H:i:s', strtotime('-15 days')),
            date('Y-m-d H:i:s', strtotime('-1 day')), date('Y-m-d'), 1 // Scheduled today!
        ],
        [
            'LED-2026-0013', 'Gautham Sankar', '+91 98473 33013', '9847333013', null, 'gautham.s@gmail.com',
            'Nirupama, Palayam', 'Calicut', 'Kerala', $branchMap['CLT'], $userMap['Ananya Nair'],
            'B.Sc Statistics', 'Zamorin’s Guruvayurappan College', 2024,
            'Yes', 'S8765432', '2031-06-15', 'KLMNO1234P',
            'No', 'Yes', 'No', null, null, null, null, null, 66, 68, 65, 62, 69,
            'UK', 'Queen Mary University of London', 'MSc Business Analytics', $intakeMap['September 2026'], 'September 2026',
            'Moderate', 'TELE', 'Website Direct', $stageMap['APPLICATION'], 'Active',
            0, null, null,
            date('Y-m-d H:i:s', strtotime('-6 days')), date('Y-m-d', strtotime('-3 days')), 1 // Overdue!
        ],

        // Unassigned Leads (for Branch Managers to allocate)
        [
            'LED-2026-0014', 'Hariprasad K.', '+91 98473 33014', '9847333014', null, 'hari.k@gmail.com',
            'Harisree, Vennala', 'Kochi', 'Kerala', $branchMap['KOC'], null, // Unassigned Kochi
            'B.Tech Civil', 'SCMS School of Engineering', 2024,
            'Yes', 'B3451234', '2030-02-28', 'LMNOP2345Q',
            'No', 'No', 'No', null, null, null, null, null, null, null, null, null, null,
            'Canada', 'University of Waterloo', 'MEng Civil Engineering', $intakeMap['January 2027'], 'January 2027',
            'Moderate', 'TELE', 'Facebook', $stageMap['ENQUIRY'], 'Active',
            0, null, null,
            null, null, 0
        ],
        [
            'LED-2026-0015', 'Sruthi Balakrishnan', '+91 98473 33015', '9847333015', null, 'sruthi.b@gmail.com',
            'Nalukettu, Thondayad', 'Calicut', 'Kerala', $branchMap['CLT'], null, // Unassigned Calicut
            'B.Sc Mathematics', 'Providence Women’s College', 2024,
            'No', null, null, null,
            'No', 'No', 'No', null, null, null, null, null, null, null, null, null, null,
            'UK', 'University of Southampton', 'MSc Actuarial Science', $intakeMap['September 2026'], 'September 2026',
            'Low', 'WALK IN', 'Walk-in', $stageMap['ENQUIRY'], 'Active',
            0, null, null,
            null, null, 0
        ]
    ];

    $insLead = $pdo->prepare("
        INSERT INTO leads 
        (lead_code, full_name, mobile_number, phone_normalized, alternate_phone, email,
         permanent_address, city, state, branch_id, assigned_counselor_id,
         highest_qualification, passing_college, passout_year,
         has_passport, passport_number, passport_expiry, pan_number,
         has_ielts, has_pte, has_toefl,
         ielts_overall, ielts_listening, ielts_reading, ielts_writing, ielts_speaking,
         pte_overall, pte_listening, pte_reading, pte_writing, pte_speaking,
         target_country, target_university, target_course, target_intake_id, target_intake_text,
         prospectivity, enquiry_type, lead_source, stage_id, secondary_status,
         is_customer, customer_code, converted_to_customer_at,
         last_followup_at, next_followup_date, first_followup_logged)
        VALUES 
        (?, ?, ?, ?, ?, ?,
         ?, ?, ?, ?, ?,
         ?, ?, ?,
         ?, ?, ?, ?,
         ?, ?, ?,
         ?, ?, ?, ?, ?,
         ?, ?, ?, ?, ?,
         ?, ?, ?, ?, ?,
         ?, ?, ?, ?, ?,
         ?, ?, ?,
         ?, ?, ?)
    ");

    $leadIdMap = [];
    foreach ($leadsData as $ld) {
        $insLead->execute($ld);
        $leadIdMap[$ld[0]] = $pdo->lastInsertId();
    }
    echo "  [OK] 15 Active Leads seeded across branches, stages, and counselor queues\n";

    // 12. Follow-ups
    $followupsData = [
        // Today's Follow-up for Adithya Nair (David Miller)
        [$leadIdMap['LED-2026-0001'], date('Y-m-d'), '10:30:00', $titleMap['Information Shared'], 'Candidate visited office; reviewed Bristol course curriculum and English waiver requirements. Scheduled next discussion for SOP drafts.', null, $stageMap['APPLICATION'], 'September 2026', date('Y-m-d'), $userMap['David Miller']],
        // Overdue Follow-up for Joel Mathew (David Miller)
        [$leadIdMap['LED-2026-0004'], date('Y-m-d', strtotime('-5 days')), '14:00:00', $titleMap['Call Not Connected'], 'Tried contacting candidate on mobile; phone rang continuously with no response. Re-scheduled outreach.', null, $stageMap['ENQUIRY'], 'September 2026', date('Y-m-d', strtotime('-2 days')), $userMap['David Miller']],
        // Today's Follow-up for Sandra Varghese (Sarah Jenkins)
        [$leadIdMap['LED-2026-0007'], date('Y-m-d'), '11:15:00', $titleMap['Visa Filing'], 'Discussed pre-departure briefing, health surcharge, and biometric appointment scheduling in Kochi VFS centre.', null, $stageMap['ENROLLED'], 'September 2026', date('Y-m-d'), $userMap['Sarah Jenkins']],
        // Overdue Follow-up for Vishnu Prasad (Sarah Jenkins)
        [$leadIdMap['LED-2026-0008'], date('Y-m-d', strtotime('-4 days')), '16:00:00', $titleMap['Documents Pending'], 'Candidate promised to email final semester marksheets and degree certificate. Still pending.', null, $stageMap['CUSTOMER'], 'September 2026', date('Y-m-d', strtotime('-1 day')), $userMap['Sarah Jenkins']],
        // Today's Follow-up for Muhammed Nihal (Alex Chen)
        [$leadIdMap['LED-2026-0010'], date('Y-m-d'), '15:30:00', $titleMap['Information Shared'], 'Sent Leeds course fee structure and accommodation options. Follow-up today to confirm application submission.', null, $stageMap['CUSTOMER'], 'September 2026', date('Y-m-d'), $userMap['Alex Chen']],
        // Today's Follow-up for Aiswarya Raj (Ananya Nair)
        [$leadIdMap['LED-2026-0012'], date('Y-m-d'), '12:00:00', $titleMap['Application Initiated'], 'UCC offer letter received; preparing CAS deposit document pack.', null, $stageMap['OFFER RECEIVED'], 'September 2026', date('Y-m-d'), $userMap['Ananya Nair']],
        // Overdue Follow-up for Gautham Sankar (Ananya Nair)
        [$leadIdMap['LED-2026-0013'], date('Y-m-d', strtotime('-6 days')), '11:00:00', $titleMap['Call Not Connected'], 'Line busy; sent WhatsApp follow-up reminder.', null, $stageMap['APPLICATION'], 'September 2026', date('Y-m-d', strtotime('-3 days')), $userMap['Ananya Nair']]
    ];

    $insFu = $pdo->prepare("
        INSERT INTO followups 
        (lead_id, followup_date, followup_time, interaction_title_id, notes, document_attachment, stage_id, target_intake, next_followup_date, counselor_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    foreach ($followupsData as $fu) {
        $insFu->execute($fu);
    }
    echo "  [OK] Follow-up interactions seeded\n";

    // 13. Invoices & Payments (Generates Customer classifications)
    // Invoice 1: Benjamin Paul (Kochi)
    $pdo->prepare("
        INSERT INTO invoices 
        (invoice_number, lead_id, branch_id, invoice_date, due_date, billing_address, subtotal, discount_total, tax_total, grand_total, paid_total, balance_total, status, notes, created_by)
        VALUES 
        ('INV-2026-0001', ?, ?, '2026-02-15', '2026-03-01', 'House No. 12, Panampilly Nagar, Kochi, Kerala', 35000.00, 2000.00, 5940.00, 38940.00, 20000.00, 18940.00, 'Active', 'UK & Ireland Premium Admissions & Visa Processing Package', ?)
    ")->execute([$leadIdMap['LED-2026-0002'], $branchMap['KOC'], $userMap['Marcus Sterling']]);
    $inv1Id = $pdo->lastInsertId();

    $pdo->prepare("
        INSERT INTO invoice_items 
        (invoice_id, service_description, quantity, unit_rate, discount_amount, tax_percent, tax_amount, subtotal)
        VALUES 
        (?, 'University Application & SOP Drafting Service', 1, 15000.00, 1000.00, 18.00, 2520.00, 16520.00),
        (?, 'Visa Documentation & CAS Compliance Verification', 1, 20000.00, 1000.00, 18.00, 3420.00, 22420.00)
    ")->execute([$inv1Id, $inv1Id]);

    $pdo->prepare("
        INSERT INTO payments 
        (invoice_id, lead_id, branch_id, payment_date, amount_paid, payment_mode, transaction_reference, notes, received_by)
        VALUES 
        (?, ?, ?, '2026-02-16', 20000.00, 'Online / UPI', 'UPI/260216098231', 'Initial installment paid via GPay', ?)
    ")->execute([$inv1Id, $leadIdMap['LED-2026-0002'], $branchMap['KOC'], $userMap['Marcus Sterling']]);

    // Invoice 2: Diya Lakshmi (Kochi - Fully Paid)
    $pdo->prepare("
        INSERT INTO invoices 
        (invoice_number, lead_id, branch_id, invoice_date, due_date, billing_address, subtotal, discount_total, tax_total, grand_total, paid_total, balance_total, status, notes, created_by)
        VALUES 
        ('INV-2026-0002', ?, ?, '2026-02-10', '2026-02-25', 'Rose Villa, Kakkanad, Kochi, Kerala', 25000.00, 0.00, 4500.00, 29500.00, 29500.00, 0.00, 'Paid', 'Australia Fast-Track Nursing Package', ?)
    ")->execute([$leadIdMap['LED-2026-0003'], $branchMap['KOC'], $userMap['Marcus Sterling']]);
    $inv2Id = $pdo->lastInsertId();

    $pdo->prepare("
        INSERT INTO invoice_items 
        (invoice_id, service_description, quantity, unit_rate, discount_amount, tax_percent, tax_amount, subtotal)
        VALUES 
        (?, 'Deakin University Direct Nursing Processing Fee', 1, 25000.00, 0.00, 18.00, 4500.00, 29500.00)
    ")->execute([$inv2Id]);

    $pdo->prepare("
        INSERT INTO payments 
        (invoice_id, lead_id, branch_id, payment_date, amount_paid, payment_mode, transaction_reference, notes, received_by)
        VALUES 
        (?, ?, ?, '2026-02-10', 29500.00, 'Bank Transfer', 'NEFT/HDFC26021008891', 'Full payment received in branch bank account', ?)
    ")->execute([$inv2Id, $leadIdMap['LED-2026-0003'], $branchMap['KOC'], $userMap['Marcus Sterling']]);

    // Invoice 3: Sandra Varghese (Kochi)
    $pdo->prepare("
        INSERT INTO invoices 
        (invoice_number, lead_id, branch_id, invoice_date, due_date, billing_address, subtotal, discount_total, tax_total, grand_total, paid_total, balance_total, status, notes, created_by)
        VALUES 
        ('INV-2026-0003', ?, ?, '2026-01-20', '2026-02-05', 'Hill View, Thrikkakara, Kochi, Kerala', 30000.00, 0.00, 5400.00, 35400.00, 35400.00, 0.00, 'Paid', 'King’s College London Enrolment Fee', ?)
    ")->execute([$leadIdMap['LED-2026-0007'], $branchMap['KOC'], $userMap['Marcus Sterling']]);
    $inv3Id = $pdo->lastInsertId();

    $pdo->prepare("
        INSERT INTO invoice_items 
        (invoice_id, service_description, quantity, unit_rate, discount_amount, tax_percent, tax_amount, subtotal)
        VALUES 
        (?, 'UK University Placement & Pre-Departure Service', 1, 30000.00, 0.00, 18.00, 5400.00, 35400.00)
    ")->execute([$inv3Id]);

    $pdo->prepare("
        INSERT INTO payments 
        (invoice_id, lead_id, branch_id, payment_date, amount_paid, payment_mode, transaction_reference, notes, received_by)
        VALUES 
        (?, ?, ?, '2026-01-20', 35400.00, 'Online / UPI', 'UPI/260120011882', 'Full package clearance', ?)
    ")->execute([$inv3Id, $leadIdMap['LED-2026-0007'], $branchMap['KOC'], $userMap['Marcus Sterling']]);

    // Invoice 4: Aiswarya Raj (Calicut)
    $pdo->prepare("
        INSERT INTO invoices 
        (invoice_number, lead_id, branch_id, invoice_date, due_date, billing_address, subtotal, discount_total, tax_total, grand_total, paid_total, balance_total, status, notes, created_by)
        VALUES 
        ('INV-2026-0004', ?, ?, '2026-02-18', '2026-03-05', 'Sreevalsam, Medical College PO, Calicut, Kerala', 30000.00, 3000.00, 4860.00, 31860.00, 15000.00, 16860.00, 'Active', 'Ireland UCC Masters Guidance Fee', ?)
    ")->execute([$leadIdMap['LED-2026-0012'], $branchMap['CLT'], $userMap['Priya Sharma']]);
    $inv4Id = $pdo->lastInsertId();

    $pdo->prepare("
        INSERT INTO invoice_items 
        (invoice_id, service_description, quantity, unit_rate, discount_amount, tax_percent, tax_amount, subtotal)
        VALUES 
        (?, 'Ireland Medical College Consulting & Visa Filing', 1, 30000.00, 3000.00, 18.00, 4860.00, 31860.00)
    ")->execute([$inv4Id]);

    $pdo->prepare("
        INSERT INTO payments 
        (invoice_id, lead_id, branch_id, payment_date, amount_paid, payment_mode, transaction_reference, notes, received_by)
        VALUES 
        (?, ?, ?, '2026-02-18', 15000.00, 'Cash', 'RCPT/CLT/260218-01', 'Cash received at Calicut branch reception', ?)
    ")->execute([$inv4Id, $leadIdMap['LED-2026-0012'], $branchMap['CLT'], $userMap['Priya Sharma']]);

    echo "  [OK] 4 Invoices & Payments seeded (generating Customers classification)\n";

    // 14. General Documents Repository
    $genDocs = [
        ['Edroots CRM - Official User Guide & Training Manual', 'Training Documents', 'Global', 'assets/docs/edroots_crm_user_manual.pdf', 'edroots_crm_user_manual.pdf', 3450000, $userMap['Noufal Peediyakkal']],
        ['UK University Admissions Master Handbook & CAS Guide', 'University Prospectuses', 'United Kingdom', 'assets/docs/uk_admissions_handbook_2026.pdf', 'uk_admissions_handbook_2026.pdf', 1820000, $userMap['Noufal Peediyakkal']],
        ['Canada SDS Visa Guidelines & Document Checklist', 'Country Guidelines', 'Canada', 'assets/docs/canada_sds_visa_guide.pdf', 'canada_sds_visa_guide.pdf', 2140000, $userMap['Noufal Peediyakkal']],
        ['Edroots SOP & Statement of Purpose Master Template', 'Training Documents', 'Global', 'assets/docs/edroots_sop_master_template.pdf', 'edroots_sop_master_template.pdf', 940000, $userMap['Vidya Harish']]
    ];
    $insGenDoc = $pdo->prepare("INSERT INTO general_documents (title, category, country, file_path, file_name, file_size, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($genDocs as $gd) {
        $insGenDoc->execute($gd);
    }
    echo "  [OK] 4 General Reference Documents seeded\n";

    // 15. Student Academic Dossiers
    $studentDocs = [
        [$leadIdMap['LED-2026-0001'], 'Identity', 'Passport Front & Back Copy', 'assets/uploads/docs/adithya_passport.pdf', 'adithya_passport.pdf', 1200000, $userMap['Athira Ashok']],
        [$leadIdMap['LED-2026-0001'], 'Academic', 'B.Tech Degree Consolidated Marksheet', 'assets/uploads/docs/adithya_btech_marks.pdf', 'adithya_btech_marks.pdf', 2500000, $userMap['Athira Ashok']],
        [$leadIdMap['LED-2026-0001'], 'English Proficiency', 'IELTS Official TRF Scorecard (7.5 Band)', 'assets/uploads/docs/adithya_ielts_trf.pdf', 'adithya_ielts_trf.pdf', 980000, $userMap['Athira Ashok']],
        [$leadIdMap['LED-2026-0002'], 'Identity', 'Passport Copy', 'assets/uploads/docs/benjamin_passport.pdf', 'benjamin_passport.pdf', 1100000, $userMap['Athira Ashok']],
        [$leadIdMap['LED-2026-0002'], 'Financial & Visa', 'Trinity College Dublin Offer Letter', 'assets/uploads/docs/tcd_offer_letter.pdf', 'tcd_offer_letter.pdf', 840000, $userMap['Athira Ashok']],
        [$leadIdMap['LED-2026-0007'], 'Financial & Visa', 'UK Visa Vignette & CAS Letter', 'assets/uploads/docs/sandra_cas_letter.pdf', 'sandra_cas_letter.pdf', 1450000, $userMap['Syamily S']]
    ];
    $insStuDoc = $pdo->prepare("INSERT INTO student_documents (lead_id, category, document_name, file_path, file_name, file_size, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($studentDocs as $sd) {
        $insStuDoc->execute($sd);
    }
    echo "  [OK] 6 Student Academic Dossier items seeded\n";

    // 16. Notifications (Attended / Not Attended states)
    $notifs = [
        [$userMap['Athira Ashok'], $branchMap['KOC'], 'New Lead Assigned: Adithya R. Nair', 'Adithya R. Nair (LED-2026-0001) has been assigned to you for UK MSc Computer Science.', 'lead_assigned', 'leads/view.php?id=' . $leadIdMap['LED-2026-0001'], 'Not Attended'],
        [$userMap['Athira Ashok'], $branchMap['KOC'], 'Follow-up Due Today: Adithya R. Nair', 'Scheduled Bristol curriculum consultation due at 10:30 AM.', 'followup_due', 'leads/view.php?id=' . $leadIdMap['LED-2026-0001'], 'Not Attended'],
        [$userMap['Syamily S'], $branchMap['KOC'], 'Visa Milestone: Sandra Varghese Enrolled', 'Student visa confirmed for King’s College London departure.', 'stage_updated', 'leads/view.php?id=' . $leadIdMap['LED-2026-0007'], 'Not Attended'],
        [$userMap['Vidya Harish'], $branchMap['KOC'], 'Invoice INV-2026-0001 Payment Logged', '₹20,000 payment received from Benjamin Paul via UPI.', 'payment_received', 'finance/invoices.php', 'Not Attended'],
        [$userMap['Noufal Peediyakkal'], null, 'New Website Inquiries Received', '4 prospective student leads awaiting triage in digital staging queue.', 'web_lead', 'data/web_leads.php', 'Not Attended']
    ];
    $insNotif = $pdo->prepare("INSERT INTO notifications (user_id, branch_id, title, message, type, link, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($notifs as $n) {
        $insNotif->execute($n);
    }
    echo "  [OK] Event-driven notifications seeded\n";

    // 17. Activity Logs
    $logs = [
        [$userMap['Noufal Peediyakkal'], null, null, 'System Setup', 'Initial enterprise configuration and branch locations initialized.', '127.0.0.1'],
        [$userMap['Vidya Harish'], $leadIdMap['LED-2026-0001'], $branchMap['KOC'], 'Lead Assigned', 'Assigned lead LED-2026-0001 (Adithya R. Nair) to counselor Athira Ashok.', '127.0.0.1'],
        [$userMap['Athira Ashok'], $leadIdMap['LED-2026-0001'], $branchMap['KOC'], 'Follow-up Interaction', 'Logged consultation outcome: Information Shared.', '127.0.0.1'],
        [$userMap['Vidya Harish'], $leadIdMap['LED-2026-0002'], $branchMap['KOC'], 'Invoice Generated', 'Generated formal service invoice INV-2026-0001 for ₹38,940.00. Lead converted to Customer.', '127.0.0.1'],
        [$userMap['Vidya Harish'], $leadIdMap['LED-2026-0002'], $branchMap['KOC'], 'Payment Recorded', 'Recorded partial payment of ₹20,000.00 via UPI.', '127.0.0.1'],
        [$userMap['Syamily S'], $leadIdMap['LED-2026-0007'], $branchMap['KOC'], 'Stage Advanced', 'Advanced student Sandra Varghese to Stage 6: ENROLLED.', '127.0.0.1']
    ];
    $insLog = $pdo->prepare("INSERT INTO activity_logs (user_id, lead_id, branch_id, action, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($logs as $l) {
        $insLog->execute($l);
    }
    echo "  [OK] Activity audit trails seeded\n";

    // 18. Sample Archived Record & Deletion Log
    // Create one archived lead
    $pdo->prepare("
        INSERT INTO leads 
        (lead_code, full_name, mobile_number, phone_normalized, email, branch_id, assigned_counselor_id, target_country, target_course, prospectivity, enquiry_type, lead_source, stage_id, secondary_status, is_archived, archived_at, archived_by, archive_reason)
        VALUES 
        ('LED-2026-0099', 'Siddharth V.', '+91 98473 33099', '9847333099', 'siddharth.v@gmail.com', ?, ?, 'Canada', 'MBA', 'Low', 'TELE', 'Walk-in', 1, 'Lost', 1, NOW(), ?, 'Student decided to take domestic campus placement.')
    ")->execute([$branchMap['KOC'], $userMap['Athira Ashok'], $userMap['Vidya Harish']]);

    // Deletion log
    $pdo->prepare("
        INSERT INTO deletion_logs 
        (user_id, record_type, record_id, record_identifier, justification, ip_address)
        VALUES 
        (?, 'web_lead', 99, 'Spam Form Bot Submission (9800000000)', 'Confirmed marketing bot spam submission from suspicious IP range.', '127.0.0.1')
    ")->execute([$userMap['Noufal Peediyakkal']]);
    echo "  [OK] Archived records & deletion logs seeded\n";

    $pdo->commit();

    echo "\n========================================================\n";
    echo "SUCCESS: ALL EDROOTS-CRM DATA SEEDED COMPLETELY!\n";
    echo "========================================================\n";

} catch (Exception $e) {
    $pdo->rollBack();
    die("FATAL SEEDING ERROR: " . $e->getMessage() . "\n");
}
