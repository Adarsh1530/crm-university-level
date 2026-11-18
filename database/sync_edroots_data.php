<?php
/**
 * UniCRM Automatic Institutional Data Synchronizer
 * Establishes a clean, modern, professional enterprise admissions CRM dataset:
 * - Professional Staff Personas: Dr. Arthur Vance, Sarah Jenkins, Michael Chang, Emily Watson, etc.
 * - Multi-Branch Hierarchy: Kochi, Bangalore, Calicut, Kannur, Mangalore, Chennai, Hyderabad, London, Trivandrum
 * - Prestigious Partner Universities: University of Manchester, University of Toronto, Monash University, TUM, etc.
 * - Realistic Global Student Leads across all 6 Admission Stages
 * - Operational Task Queues, Inbound Web Leads & Training SOPs
 */

function sync_institutional_data(PDO $pdo) {
    if (!empty($_SESSION['unicrm_fresh_clean_synced_v1'])) {
        return;
    }

    try {
        // Fast test: Check if Dr. Arthur Vance exists as user 1
        $userCheck = $pdo->query("SELECT name FROM users WHERE id = 1 LIMIT 1");
        $user1 = $userCheck ? $userCheck->fetch() : null;
        $hasArthur = $user1 && $user1['name'] === 'Dr. Arthur Vance';

        // Check if Aarav Sharma exists as lead 1
        $leadCheck = $pdo->query("SELECT id FROM leads WHERE full_name = 'Aarav Sharma' LIMIT 1");
        $hasAarav = $leadCheck && $leadCheck->fetch();

        if ($hasArthur && $hasAarav) {
            $_SESSION['unicrm_fresh_clean_synced_v1'] = true;
            return;
        }

        $defaultPassword = password_hash('123', PASSWORD_BCRYPT);

        // 1. Company Profile
        $pdo->exec("
            UPDATE companies 
            SET name = 'UniCRM Global Admissions Cloud', 
                email = 'admissions@unicrm.edu', 
                phone = '+91 484 288 9000', 
                website = 'https://crm-university-level.vercel.app', 
                address = 'Level 5, Nexus Executive Tower, InfoPark Expressway, Kochi, Kerala - 682042' 
            WHERE id = 1
        ");

        // 2. Branches
        $branches = [
            1 => ['Kochi Campus Hub', 'KOC', 'Kochi', 'Kerala', 'India', '+91 484 288 9001', 'kochi@unicrm.edu', 'Level 5, Nexus Executive Tower, InfoPark Expressway, Kochi'],
            2 => ['Bangalore Tech Hub', 'BLR', 'Bangalore', 'Karnataka', 'India', '+91 80 4123 4567', 'bangalore@unicrm.edu', 'Prestige Meridian, MG Road, Bangalore'],
            3 => ['Calicut Central Hub', 'CLT', 'Calicut', 'Kerala', 'India', '+91 495 277 8001', 'calicut@unicrm.edu', 'Skyline Business Centre, Mavoor Road, Calicut'],
            4 => ['Kannur Regional Hub', 'KNR', 'Kannur', 'Kerala', 'India', '+91 497 270 7001', 'kannur@unicrm.edu', 'Fort Road Commercial Centre, Kannur'],
            5 => ['Mangalore Education Hub', 'MNG', 'Mangalore', 'Karnataka', 'India', '+91 824 244 2001', 'mangalore@unicrm.edu', 'KSR Road, Hampankatta, Mangalore'],
            6 => ['Chennai City Centre', 'MAA', 'Chennai', 'Tamil Nadu', 'India', '+91 44 2822 3001', 'chennai@unicrm.edu', 'Anna Salai Business Tower, Chennai'],
            7 => ['Hyderabad Hub', 'HYD', 'Hyderabad', 'Telangana', 'India', '+91 40 6677 8001', 'hyderabad@unicrm.edu', 'Cyber Towers, Hitec City, Hyderabad'],
            8 => ['London International Campus', 'LDN', 'London', 'Greater London', 'United Kingdom', '+44 20 7946 0991', 'london@unicrm.edu', '120 Fenchurch Street, London EC3M 5BA'],
            9 => ['Trivandrum Capital Hub', 'TVM', 'Thiruvananthapuram', 'Kerala', 'India', '+91 471 233 3001', 'trivandrum@unicrm.edu', 'Statue Junction, MG Road, Thiruvananthapuram']
        ];

        $stmtBranch = $pdo->prepare("
            INSERT INTO branches (id, name, code, city, state, country, phone, email, address, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active')
            ON DUPLICATE KEY UPDATE 
                name = VALUES(name), code = VALUES(code), city = VALUES(city), 
                state = VALUES(state), country = VALUES(country), phone = VALUES(phone), 
                email = VALUES(email), address = VALUES(address)
        ");
        foreach ($branches as $id => $b) {
            $stmtBranch->execute([$id, $b[0], $b[1], $b[2], $b[3], $b[4], $b[5], $b[6], $b[7]]);
        }

        // 3. Staff Personas & Credentials
        // Login Credentials:
        // 1. Super Admin: superadmin / 123 (Dr. Arthur Vance)
        // 2. Admission Manager: admissionmanager / 123 (Sarah Jenkins)
        // 3. Academic Counselor: academiccounselor / 123 (Michael Chang)
        // 4. Admission Advisor: admissionadvisor / 123 (Emily Watson)
        $staffUsers = [
            1 => [1, 1, null, 'Dr. Arthur Vance', 'superadmin', 'superadmin@unicrm.edu', '+91 484 288 9000', 'Global Admissions Director & Super Admin'],
            2 => [1, 2, null, 'Sarah Jenkins', 'admissionmanager', 'admissionmanager@unicrm.edu', '+91 9074 134910', 'Regional Admissions Manager (Kochi Hub)'],
            3 => [1, 3, 2, 'Michael Chang', 'academiccounselor', 'academiccounselor@unicrm.edu', '+91 9633 371111', 'Senior Academic Counselor'],
            4 => [1, 4, 2, 'Emily Watson', 'admissionadvisor', 'admissionadvisor@unicrm.edu', '+91 8547 597476', 'Admissions Advisor & Intake Specialist'],
            5 => [2, 3, 2, 'David Miller', 'davidmiller', 'david.miller@unicrm.edu', '+91 9895 716710', 'Academic Counselor (Bangalore)'],
            6 => [3, 3, 2, 'Jessica Taylor', 'jessicataylor', 'jessica.taylor@unicrm.edu', '+91 9947 921306', 'Academic Counselor (Calicut)'],
            7 => [4, 3, 2, 'Daniel Roberts', 'danielroberts', 'daniel.roberts@unicrm.edu', '+91 9061 253555', 'Academic Counselor (Kannur)'],
            8 => [5, 3, 2, 'Rachel Green', 'rachelgreen', 'rachel.green@unicrm.edu', '+91 8330 087748', 'Academic Counselor (Mangalore)'],
            9 => [8, 3, 2, 'James Wilson', 'jameswilson', 'james.wilson@unicrm.edu', '+44 20 7946 0991', 'International Officer (London)'],
            10 => [9, 3, 2, 'Olivia Brown', 'oliviabrown', 'olivia.brown@unicrm.edu', '+91 7511 140594', 'Academic Counselor (Trivandrum)']
        ];

        $stmtUser = $pdo->prepare("
            INSERT INTO users (id, branch_id, role_id, manager_id, name, username, email, password, phone, designation, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active')
            ON DUPLICATE KEY UPDATE 
                branch_id = VALUES(branch_id), role_id = VALUES(role_id), manager_id = VALUES(manager_id),
                name = VALUES(name), username = VALUES(username), email = VALUES(email), password = VALUES(password),
                phone = VALUES(phone), designation = VALUES(designation), status = 'Active'
        ");
        foreach ($staffUsers as $id => $u) {
            $stmtUser->execute([$id, $u[0], $u[1], $u[2], $u[3], $u[4], $u[5], $defaultPassword, $u[6], $u[7]]);
        }

        // 4. Recognized Global Partner Universities
        $unis = [
            1 => ['University of Manchester', 'United Kingdom', 'Manchester', 'Russell Group Global #32', 'manchester.ac.uk'],
            2 => ['University of Toronto', 'Canada', 'Toronto, Ontario', 'QS World #21, Canada #1', 'utoronto.ca'],
            3 => ['Monash University', 'Australia', 'Melbourne, Victoria', 'Group of Eight Global #42', 'monash.edu'],
            4 => ['Technical University of Munich (TUM)', 'Germany', 'Munich, Bavaria', 'QS World #28, Germany #1', 'tum.de'],
            5 => ['Trinity College Dublin', 'Ireland', 'Dublin', 'QS World #81, Ireland #1', 'tcd.ie'],
            6 => ['King\'s College London', 'United Kingdom', 'London', 'Russell Group Global #40', 'kcl.ac.uk'],
            7 => ['McGill University', 'Canada', 'Montreal, Quebec', 'Top Global Research University', 'mcgill.ca'],
            8 => ['University of Melbourne', 'Australia', 'Melbourne, Victoria', 'Global Top #33 University', 'unimelb.edu.au'],
            9 => ['University of Edinburgh', 'United Kingdom', 'Edinburgh', 'Russell Group Global #22', 'ed.ac.uk'],
            10 => ['RWTH Aachen University', 'Germany', 'Aachen', 'Leading German Technical University', 'rwth-aachen.de'],
            11 => ['University of Greenwich', 'United Kingdom', 'London', 'London Modern University', 'gre.ac.uk'],
            12 => ['Dublin City University (DCU)', 'Ireland', 'Dublin', 'QS Top 50 Global Young University', 'dcu.ie']
        ];

        $stmtUni = $pdo->prepare("
            INSERT INTO universities (id, name, country, city, ranking, website)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                name = VALUES(name), country = VALUES(country), city = VALUES(city), 
                ranking = VALUES(ranking), website = VALUES(website)
        ");
        foreach ($unis as $id => $un) {
            $stmtUni->execute([$id, $un[0], $un[1], $un[2], $un[3], $un[4]]);
        }

        // 5. Clean, Professional, Authentic Student Leads across 6 Admission Stages
        $leads = [
            // Stage 6: Enrolled Client 1
            [
                'id' => 1, 'lead_code' => 'LED-2026-0001', 'full_name' => 'Aarav Sharma', 'phone' => '9845011223', 'mobile_number' => '+91 98450 11223', 'email' => 'aarav.sharma@gmail.com',
                'branch_id' => 1, 'counselor_id' => 3, 'degree' => 'B.Tech Computer Science', 'college' => 'Model Engineering College, Kochi',
                'country' => 'United Kingdom', 'course' => 'MSc Advanced Computer Science',
                'priority' => 'High', 'source' => 'Direct Reference', 'stage' => 'ENROLLED', 'secondary_status' => 'Active',
                'notes' => 'Visa approved and enrolled at University of Manchester.', 'is_customer' => 1, 'is_archived' => 0, 'created_at' => '2026-01-10 10:15:00'
            ],
            // Stage 6: Enrolled Client 2
            [
                'id' => 2, 'lead_code' => 'LED-2026-0002', 'full_name' => 'Sophia Martinez', 'phone' => '9895033445', 'mobile_number' => '+91 98950 33445', 'email' => 'sophia.martinez@outlook.com',
                'branch_id' => 1, 'counselor_id' => 3, 'degree' => 'B.Sc Data Science', 'college' => 'Rajagiri College of Social Sciences',
                'country' => 'Canada', 'course' => 'Master of Data Analytics',
                'priority' => 'High', 'source' => 'Website', 'stage' => 'ENROLLED', 'secondary_status' => 'Active',
                'notes' => 'Study permit granted. Full fees remitted for University of Toronto.', 'is_customer' => 1, 'is_archived' => 0, 'created_at' => '2026-01-12 11:30:00'
            ],
            // Stage 5: Tuition Fees Paid
            [
                'id' => 3, 'lead_code' => 'LED-2026-0003', 'full_name' => 'Rohan Patel', 'phone' => '9447055667', 'mobile_number' => '+91 94470 55667', 'email' => 'rohan.patel@gmail.com',
                'branch_id' => 1, 'counselor_id' => 4, 'degree' => 'B.Tech Information Technology', 'college' => 'CUSAT School of Engineering',
                'country' => 'Australia', 'course' => 'Master of Cybersecurity',
                'priority' => 'High', 'source' => 'Education Fair', 'stage' => 'TUITION FEES PAID', 'secondary_status' => 'Active',
                'notes' => 'Initial tuition deposit paid to Monash University. Preparing visa filing.', 'is_customer' => 1, 'is_archived' => 0, 'created_at' => '2026-01-18 14:00:00'
            ],
            // Stage 4: Offer Received
            [
                'id' => 4, 'lead_code' => 'LED-2026-0004', 'full_name' => 'Ananya Iyer', 'phone' => '9745077889', 'mobile_number' => '+91 97450 77889', 'email' => 'ananya.iyer@yahoo.com',
                'branch_id' => 1, 'counselor_id' => 3, 'degree' => 'B.Tech Electrical & Electronics', 'college' => 'NIT Calicut',
                'country' => 'Germany', 'course' => 'MSc Renewable Energy Systems',
                'priority' => 'High', 'source' => 'Direct Reference', 'stage' => 'OFFER RECEIVED', 'secondary_status' => 'Active',
                'notes' => 'Unconditional admission offer received from TUM Munich.', 'is_customer' => 0, 'is_archived' => 0, 'created_at' => '2026-01-22 09:45:00'
            ],
            // Stage 3: Application Submitted
            [
                'id' => 5, 'lead_code' => 'LED-2026-0005', 'full_name' => 'David Kim', 'phone' => '9061099001', 'mobile_number' => '+91 90610 99001', 'email' => 'david.kim@gmail.com',
                'branch_id' => 1, 'counselor_id' => 4, 'degree' => 'BBA International Management', 'college' => 'Loyola College',
                'country' => 'Ireland', 'course' => 'MSc International Business',
                'priority' => 'Moderate', 'source' => 'Walk-in', 'stage' => 'APPLICATION', 'secondary_status' => 'Active',
                'notes' => 'Application dossier dispatched to Trinity College Dublin.', 'is_customer' => 0, 'is_archived' => 0, 'created_at' => '2026-01-25 16:20:00'
            ],
            // Stage 2: Customer (Formally Invoiced)
            [
                'id' => 6, 'lead_code' => 'LED-2026-0006', 'full_name' => 'Fatima Al-Mansoor', 'phone' => '9846022334', 'mobile_number' => '+91 98460 22334', 'email' => 'fatima.mansoor@gmail.com',
                'branch_id' => 1, 'counselor_id' => 3, 'degree' => 'B.Sc Healthcare Administration', 'college' => 'Amrita Institute of Medical Sciences',
                'country' => 'United Kingdom', 'course' => 'MBA Global Healthcare',
                'priority' => 'High', 'source' => 'Website', 'stage' => 'CUSTOMER', 'secondary_status' => 'Active',
                'notes' => 'Consultancy agreement signed and initial retainer invoiced.', 'is_customer' => 1, 'is_archived' => 0, 'created_at' => '2026-01-28 10:00:00'
            ],
            // Stage 1: Active Enquiries
            [
                'id' => 7, 'lead_code' => 'LED-2026-0007', 'full_name' => 'Kavya Krishnan', 'phone' => '9961044556', 'mobile_number' => '+91 99610 44556', 'email' => 'kavya.krishnan@gmail.com',
                'branch_id' => 1, 'counselor_id' => 4, 'degree' => 'B.Com Finance', 'college' => 'Sacred Heart College, Thevara',
                'country' => 'Canada', 'course' => 'Post-Graduate Project Management',
                'priority' => 'Moderate', 'source' => 'Social Media', 'stage' => 'ENQUIRY', 'secondary_status' => 'Active',
                'notes' => 'Discussed SDS eligibility criteria and IELTS test dates.', 'is_customer' => 0, 'is_archived' => 0, 'created_at' => '2026-02-01 11:00:00'
            ],
            [
                'id' => 8, 'lead_code' => 'LED-2026-0008', 'full_name' => 'Liam O\'Connor', 'phone' => '9544066778', 'mobile_number' => '+91 95440 66778', 'email' => 'liam.oconnor@gmail.com',
                'branch_id' => 2, 'counselor_id' => 5, 'degree' => '12th Grade CBSE (Science)', 'college' => 'National Public School, Bangalore',
                'country' => 'Australia', 'course' => 'Bachelor of Artificial Intelligence',
                'priority' => 'Low', 'source' => 'Walk-in', 'stage' => 'ENQUIRY', 'secondary_status' => 'Active',
                'notes' => 'Reviewing undergraduate entry requirements for Melbourne and Sydney.', 'is_customer' => 0, 'is_archived' => 0, 'created_at' => '2026-02-03 14:00:00'
            ],
            [
                'id' => 9, 'lead_code' => 'LED-2026-0009', 'full_name' => 'Pooja Nair', 'phone' => '9633088990', 'mobile_number' => '+91 96330 88990', 'email' => 'pooja.nair@gmail.com',
                'branch_id' => 3, 'counselor_id' => 6, 'degree' => 'B.Com Professional', 'college' => 'Farook College, Kozhikode',
                'country' => 'United Kingdom', 'course' => 'MSc Finance & Investment',
                'priority' => 'Moderate', 'source' => 'Social Media', 'stage' => 'ENQUIRY', 'secondary_status' => 'Active',
                'notes' => 'Course brochures shared for Edinburgh, Warwick, and Bristol.', 'is_customer' => 0, 'is_archived' => 0, 'created_at' => '2026-02-05 09:15:00'
            ],
            [
                'id' => 10, 'lead_code' => 'LED-2026-0010', 'full_name' => 'Carlos Mendez', 'phone' => '9400011223', 'mobile_number' => '+91 94000 11223', 'email' => 'carlos.mendez@gmail.com',
                'branch_id' => 4, 'counselor_id' => 7, 'degree' => 'B.Tech Mechanical Engineering', 'college' => 'Govt Engineering College, Kannur',
                'country' => 'Germany', 'course' => 'Master of Automotive Engineering',
                'priority' => 'Low', 'source' => 'Direct Reference', 'stage' => 'ENQUIRY', 'secondary_status' => 'Active',
                'notes' => 'Inquiry regarding English-taught master programs and blocked account.', 'is_customer' => 0, 'is_archived' => 0, 'created_at' => '2026-02-07 15:30:00'
            ],
            // Archived Student
            [
                'id' => 11, 'lead_code' => 'LED-2026-0011', 'full_name' => 'Maya Lin', 'phone' => '9947022334', 'mobile_number' => '+91 99470 22334', 'email' => 'maya.lin@gmail.com',
                'branch_id' => 1, 'counselor_id' => 3, 'degree' => 'B.Sc Mathematics & Economics', 'college' => 'St. Stephen\'s College',
                'country' => 'United States', 'course' => 'Master of Financial Engineering',
                'priority' => 'Low', 'source' => 'Walk-in', 'stage' => 'ENQUIRY', 'secondary_status' => 'Active',
                'notes' => 'Deferred intake to subsequent academic cycle due to personal scheduling.', 'is_customer' => 0, 'is_archived' => 1, 'created_at' => '2026-01-05 09:30:00'
            ]
        ];

        $stmtLead = $pdo->prepare("
            INSERT INTO leads (
                id, lead_code, full_name, mobile_number, phone, phone_normalized, email, branch_id, assigned_counselor_id,
                stage, secondary_status, priority, source, preferred_country, preferred_course,
                highest_qualification, college_name, notes, is_customer, is_archived, created_by, created_at, assigned_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, 1, ?, ?
            ) ON DUPLICATE KEY UPDATE 
                lead_code = VALUES(lead_code), full_name = VALUES(full_name), mobile_number = VALUES(mobile_number),
                phone = VALUES(phone), phone_normalized = VALUES(phone_normalized), email = VALUES(email),
                branch_id = VALUES(branch_id), assigned_counselor_id = VALUES(assigned_counselor_id),
                stage = VALUES(stage), secondary_status = VALUES(secondary_status), priority = VALUES(priority),
                source = VALUES(source), preferred_country = VALUES(preferred_country), preferred_course = VALUES(preferred_course),
                highest_qualification = VALUES(highest_qualification), college_name = VALUES(college_name), notes = VALUES(notes),
                is_customer = VALUES(is_customer), is_archived = VALUES(is_archived)
        ");

        foreach ($leads as $l) {
            $stmtLead->execute([
                $l['id'], $l['lead_code'], $l['full_name'], $l['mobile_number'], $l['phone'], $l['phone'], $l['email'],
                $l['branch_id'], $l['counselor_id'], $l['stage'], $l['secondary_status'],
                $l['priority'], $l['source'], $l['country'], $l['course'],
                $l['degree'], $l['college'], $l['notes'], $l['is_customer'], $l['is_archived'],
                $l['created_at'], $l['created_at']
            ]);
        }

        // 6. College Data Pool (Local University Outreach Inbound)
        $collegeEntries = [
            [1, 'Meera Nambiar', '+91 98957 10001', '9895710001', 'meera.n@gmail.com', 'MES College', 'B.Com Finance', 1, 'Kochi', 'NO', 1],
            [2, 'Rahul Pillai', '+91 98957 10002', '9895710002', 'rahul.p@gmail.com', 'Farook College', 'BBA International Business', 3, 'Calicut', 'NO', 1],
            [3, 'Sherin Varghese', '+91 98957 10003', '9895710003', 'sherin.v@gmail.com', 'St. Teresa\'s College', 'B.Sc Computer Science', 1, 'Kochi', 'NO', 1],
            [4, 'Aditya Menon', '+91 98957 10004', '9895710004', 'aditya.m@gmail.com', 'Govt Engineering College', 'B.Tech Mechanical', 1, 'Thrissur', 'NO', 1],
            [5, 'Janaki Devi', '+91 98957 10005', '9895710005', 'janaki.d@gmail.com', 'Sacred Heart College', 'B.Com Taxation', 1, 'Kochi', 'NO', 1]
        ];

        $stmtCol = $pdo->prepare("
            INSERT INTO college_data (id, student_name, mobile_number, phone_normalized, email, college_name, degree, branch_id, location, is_converted, converted_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                student_name = VALUES(student_name), mobile_number = VALUES(mobile_number),
                phone_normalized = VALUES(phone_normalized), email = VALUES(email),
                college_name = VALUES(college_name), degree = VALUES(degree),
                branch_id = VALUES(branch_id), location = VALUES(location)
        ");
        foreach ($collegeEntries as $ce) {
            $stmtCol->execute($ce);
        }

        // 7. Digital Inbound Campaign Leads (Meta Ads & Google Ads)
        $webLeads = [
            [1, 'Tariq Aziz', 'Tariq Aziz', '+91 95448 20601', '9544820601', '9544820601', 'tariq.aziz@gmail.com', 'Kochi, Kerala', 'MSc Data Engineering', 'University of Manchester', 'United Kingdom', 1, 1, 'Google Ads - UK Computing', 'Google Ads', 'Unassigned'],
            [2, 'Chloe Dupont', 'Chloe Dupont', '+91 95448 20602', '9544820602', '9544820602', 'chloe.dupont@gmail.com', 'Bangalore, Karnataka', 'Master of Data Analytics', 'University of Toronto', 'Canada', 2, 2, 'Meta Ads - Canada Postgrad', 'Meta Ads', 'Unassigned'],
            [3, 'Pranav Nair', 'Pranav Nair', '+91 91894 39201', '9189439201', '9189439201', 'pranav.nair@gmail.com', 'Calicut, Kerala', 'MBA Global Healthcare', 'King\'s College London', 'United Kingdom', 3, 3, 'Google Ads - UK Healthcare', 'Google Ads', 'Unassigned'],
            [4, 'Aditya Varma', 'Aditya Varma', '+91 86066 62961', '8606662961', '8606662961', 'aditya.varma@gmail.com', 'Trivandrum, Kerala', 'MSc International Business', 'Trinity College Dublin', 'Ireland', 9, 9, 'Meta Ads - Ireland Business', 'Meta Ads', 'Unassigned']
        ];

        $stmtWl = $pdo->prepare("
            INSERT INTO web_leads (id, student_name, full_name, mobile_number, phone, phone_normalized, email, city_state, target_course, target_university, target_country, nearest_branch_id, branch_id, source_campaign, source, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                student_name = VALUES(student_name), full_name = VALUES(full_name),
                mobile_number = VALUES(mobile_number), phone = VALUES(phone), phone_normalized = VALUES(phone_normalized),
                email = VALUES(email), city_state = VALUES(city_state), target_course = VALUES(target_course), target_university = VALUES(target_university),
                target_country = VALUES(target_country), nearest_branch_id = VALUES(nearest_branch_id), branch_id = VALUES(branch_id),
                source_campaign = VALUES(source_campaign), source = VALUES(source), status = VALUES(status)
        ");
        foreach ($webLeads as $wl) {
            $stmtWl->execute($wl);
        }

        // 8. General Training Documents (Institutional SOPs)
        $pdo->exec("
            INSERT INTO general_documents (id, title, category, country, description, file_path, file_name, file_size, uploaded_by)
            VALUES 
            (1, 'UniCRM - Standard Operating Procedures & Operations Playbook', 'General Guidelines', 'Global', 'Comprehensive operations manual and step-by-step counselor onboarding guide.', 'assets/docs/unicrm_operations_manual.pdf', 'unicrm_sop_playbook.pdf', 2393736, 1),
            (2, 'UK University Admissions Process & CAS Guidelines', 'Partner University Lists', 'United Kingdom', 'Comprehensive UK university entry requirements, CAS issuing timelines, and visa interview guidelines.', 'assets/docs/unicrm_operations_manual.pdf', 'uk_admissions_handbook_2026.pdf', 1820000, 1),
            (3, 'Canada Student Direct Stream (SDS) Checklist', 'Visa Checklists', 'Canada', 'Checklist for Canadian study permit under SDS pathway including GIC and medical proof.', 'assets/docs/unicrm_operations_manual.pdf', 'canada_sds_visa_guide.pdf', 2140000, 2),
            (4, 'Global University SOP & Statement of Purpose Master Framework', 'SOP Guidelines & Templates', 'Global', 'Standardized structure and writing prompt for university statement of purpose.', 'assets/docs/unicrm_operations_manual.pdf', 'university_sop_master_framework.pdf', 940000, 1)
            ON DUPLICATE KEY UPDATE 
                title = VALUES(title), category = VALUES(category), country = VALUES(country), 
                description = VALUES(description), file_path = VALUES(file_path), file_name = VALUES(file_name)
        ");

        // 9. Follow-up Interaction Queues
        try {
            $pdo->exec("DELETE FROM followups WHERE id > 0");
            $pdo->exec("
                INSERT INTO followups (lead_id, followup_date, followup_time, interaction_title_id, notes, stage_id, next_followup_date, counselor_id, status)
                VALUES 
                (7, CURDATE(), '10:30:00', 1, 'Follow-up consultation for Kavya Krishnan (Canada Project Management)', 1, DATE_ADD(CURDATE(), INTERVAL 3 DAY), 4, 'Pending'),
                (5, CURDATE(), '11:45:00', 2, 'Review course brochures and fee structure with David Kim (Trinity College Dublin)', 1, DATE_ADD(CURDATE(), INTERVAL 2 DAY), 4, 'Pending'),
                (4, CURDATE(), '14:00:00', 1, 'Connect with Ananya Iyer regarding TUM offer letter acceptance', 1, DATE_ADD(CURDATE(), INTERVAL 4 DAY), 3, 'Pending'),
                (8, CURDATE(), '15:30:00', 2, 'Course options discussion for Liam O\'Connor (University of Melbourne)', 1, DATE_ADD(CURDATE(), INTERVAL 5 DAY), 5, 'Pending')
            ");
        } catch (Exception $fEx) {
            // Silently continue
        }

        // 10. Clean Financial Records (Invoices & Payments)
        try {
            // Delete old demo invoice 6262 if exists
            $pdo->exec("DELETE FROM payments WHERE invoice_id IN (1, 2, 6262)");
            $pdo->exec("DELETE FROM invoice_items WHERE invoice_id IN (1, 2, 6262)");
            $pdo->exec("DELETE FROM invoices WHERE id IN (1, 2, 6262)");

            // Invoice 1: Aarav Sharma (Partially Paid)
            $pdo->prepare("
                INSERT INTO invoices (
                    id, invoice_number, lead_id, branch_id, invoice_date, issue_date, due_date,
                    subtotal, discount_total, discount_amount, tax_total, tax_percentage, tax_amount, grand_total,
                    paid_total, paid_amount, balance_total, balance_due, payment_status, status, payment_terms, bank_details,
                    notes, created_by
                ) VALUES (
                    1, 'INV-2026-0001', 1, 1, '2026-01-15', '2026-01-15', '2026-01-30',
                    35000.00, 0.00, 0.00, 6300.00, 18.00, 6300.00, 41300.00,
                    20000.00, 20000.00, 21300.00, 21300.00, 'Partially Paid', 'Active', 'Due in 15 days.', 'State Bank of India - Nexus Global Education Consultancy',
                    'UK Admissions Processing Retainer', 2
                )
            ")->execute();

            $pdo->prepare("
                INSERT INTO invoice_items (invoice_id, service_description, description, quantity, unit_rate, unit_price, line_total, subtotal)
                VALUES 
                (1, 'Comprehensive University Admission Consultation & Processing', 'Comprehensive University Admission Consultation & Processing', 1, 20000.00, 20000.00, 20000.00, 20000.00),
                (1, 'International Visa Filing & Compliance Dossier Review', 'International Visa Filing & Compliance Dossier Review', 1, 15000.00, 15000.00, 15000.00, 15000.00)
            ")->execute();

            $pdo->prepare("
                INSERT INTO payments (
                    receipt_number, invoice_id, lead_id, branch_id, payment_date,
                    amount_paid, amount, payment_mode, payment_method, transaction_reference,
                    notes, received_by, recorded_by
                ) VALUES (
                    'RCT-2026-0001', 1, 1, 1, '2026-01-16',
                    20000.00, 20000.00, 'Online / UPI', 'Online / UPI', 'UPI/260116091244',
                    'Initial installment paid via UPI', 2, 2
                )
            ")->execute();

            // Invoice 2: Sophia Martinez (Fully Paid)
            $pdo->prepare("
                INSERT INTO invoices (
                    id, invoice_number, lead_id, branch_id, invoice_date, issue_date, due_date,
                    subtotal, discount_total, discount_amount, tax_total, tax_percentage, tax_amount, grand_total,
                    paid_total, paid_amount, balance_total, balance_due, payment_status, status, payment_terms, bank_details,
                    notes, created_by
                ) VALUES (
                    2, 'INV-2026-0002', 2, 1, '2026-01-20', '2026-01-20', '2026-02-05',
                    25000.00, 0.00, 0.00, 4500.00, 18.00, 4500.00, 29500.00,
                    29500.00, 29500.00, 0.00, 0.00, 'Paid', 'Active', 'Due in 15 days.', 'State Bank of India - Nexus Global Education Consultancy',
                    'Canada Admissions & Visa Support Package', 2
                )
            ")->execute();

            $pdo->prepare("
                INSERT INTO invoice_items (invoice_id, service_description, description, quantity, unit_rate, unit_price, line_total, subtotal)
                VALUES 
                (2, 'Fast-Track Canadian University Admissions & Pre-Departure Liaison', 'Fast-Track Canadian University Admissions & Pre-Departure Liaison', 1, 25000.00, 25000.00, 25000.00, 25000.00)
            ")->execute();

            $pdo->prepare("
                INSERT INTO payments (
                    receipt_number, invoice_id, lead_id, branch_id, payment_date,
                    amount_paid, amount, payment_mode, payment_method, transaction_reference,
                    notes, received_by, recorded_by
                ) VALUES (
                    'RCT-2026-0002', 2, 2, 1, '2026-01-22',
                    29500.00, 29500.00, 'Bank Transfer', 'Bank Transfer', 'NEFT/260122881923',
                    'Full consultancy payment received via NEFT', 2, 2
                )
            ")->execute();

            // Update leads financial totals
            $pdo->exec("UPDATE leads SET invoiced_total = 41300.00, paid_total = 20000.00, balance_total = 21300.00 WHERE id = 1");
            $pdo->exec("UPDATE leads SET invoiced_total = 29500.00, paid_total = 29500.00, balance_total = 0.00 WHERE id = 2");
            $pdo->exec("UPDATE leads SET invoiced_total = 0.00, paid_total = 0.00, balance_total = 0.00 WHERE id NOT IN (1, 2)");
        } catch (Exception $invEx) {
            error_log('Invoice sync error: ' . $invEx->getMessage());
        }

        $_SESSION['unicrm_fresh_clean_synced_v1'] = true;
    } catch (Exception $e) {
        error_log('UniCRM DB Sync Notice: ' . $e->getMessage());
    }
}
