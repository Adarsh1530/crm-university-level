<?php
/**
 * EdrootsCRM Automatic Data & Persona Synchronizer
 * Aligns database records with the official EdrootsCRM User Manual specifications:
 * - Real Staff Personas: Noufal Peediyakkal, Vidya Harish, Athira Ashok, Syamily S, etc.
 * - Real Branches: Kochi, Perinthalmanna, Calicut, Kannur, Mangalore, Kasaragod, Kanhangad, London
 * - Real Partner Universities: University of Greenwich, University of Hertfordshire, Coventry University, etc.
 * - Real Student Leads & Inquiries from PDF Manual: Nileena, Mohammed Ameen M K, SNEHA THOMAS, ALBERT JOSEPH, VISHAKH, etc.
 * - Real Digital & College Pool: Shibin thomas, Thasleer.k, Rafeena p, Reenu ck, etc.
 */

function sync_edroots_database_data(PDO $pdo) {
    // Only run if not already validated in session, or when force check is requested
    if (!empty($_SESSION['edroots_manual_synced_v3'])) {
        return;
    }

    try {
        // Fast test: Check if Noufal Peediyakkal exists as user 1
        $checkStmt = $pdo->query("SELECT name FROM users WHERE id = 1 LIMIT 1");
        $user1 = $checkStmt ? $checkStmt->fetch() : null;

        if ($user1 && $user1['name'] === 'Noufal Peediyakkal') {
            $_SESSION['edroots_manual_synced_v3'] = true;
            return;
        }

        // Needs sync or update
        $defaultPassword = password_hash('admin123', PASSWORD_BCRYPT);

        // 1. Update / Ensure Company Profile
        $pdo->exec("
            UPDATE companies 
            SET name = 'Edroots International', 
                email = 'info@edroots.in', 
                phone = '+91 4933-224333', 
                website = 'https://edroots.in', 
                address = '3rd floor, Al Hazmi Tower, Behind SBT, Pattambi Road, Perinthalmanna' 
            WHERE id = 1
        ");

        // 2. Update / Align Branches to exact PDF manual locations
        $branches = [
            1 => ['Kochi', 'KOC', 'Kochi', 'Kerala', 'India', '+91 484 288 9001', 'kochi@edroots.in', 'Edroots Tower, MG Road, Kochi'],
            2 => ['Perinthalmanna', 'PMNA', 'Perinthalmanna', 'Kerala', 'India', '+91 493 322 4333', 'pmna@edroots.in', '3rd floor, Al Hazmi Tower, Behind SBT, Pattambi Road, Perinthalmanna'],
            3 => ['Calicut', 'CLT', 'Calicut', 'Kerala', 'India', '+91 495 277 8001', 'calicut@edroots.in', 'Skyline Plaza, Mavoor Road, Calicut'],
            4 => ['Kannur', 'KNR', 'Kannur', 'Kerala', 'India', '+91 497 270 7001', 'kannur@edroots.in', 'Fort Road Commercial Centre, Kannur'],
            5 => ['Mangalore', 'MNG', 'Mangalore', 'Karnataka', 'India', '+91 824 244 2001', 'mangalore@edroots.in', 'KSR Road, Hampankatta, Mangalore'],
            6 => ['Kasaragod', 'KSD', 'Kasaragod', 'Kerala', 'India', '+91 499 423 6001', 'kasaragod@edroots.in', 'Bank Road Junction, Kasaragod'],
            7 => ['Kanhangad', 'KHD', 'Kanhangad', 'Kerala', 'India', '+91 467 220 5001', 'kanhangad@edroots.in', 'Main Street Mall, Kanhangad'],
            8 => ['London', 'LDN', 'London', 'Greater London', 'United Kingdom', '+44 20 7946 0991', 'london@edroots.in', '120 Fenchurch Street, London EC3M 5BA'],
            9 => ['Trivandrum', 'TVM', 'Thiruvananthapuram', 'Kerala', 'India', '+91 471 233 3001', 'trivandrum@edroots.in', 'Statue Junction, MG Road, Thiruvananthapuram']
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

        // 3. Update / Align Users to PDF Manual Staff Personas
        $staffUsers = [
            1 => [null, 1, null, 'Noufal Peediyakkal', 'noufal@edroots.in', '+91 4933 224333', 'Super Admin & Case Manager'],
            2 => [2, 2, null, 'Vidya Harish', 'vidya@edroots.in', '+91 9074 134910', 'Admissions & Branch Head (Perinthalmanna)'],
            3 => [7, 3, 2, 'Athira Ashok', 'athira@edroots.in', '+91 8547 597476', 'Senior Academic Counselor (Kanhangad)'],
            4 => [1, 3, 2, 'Syamily S', 'syamily@edroots.in', '+91 9633 371111', 'Admissions Advisor & Counselor (Kochi)'],
            5 => [3, 3, 2, 'Ambili CO', 'ambili@edroots.in', '+91 9895 716710', 'Academic Counselor (Calicut)'],
            6 => [1, 3, 2, 'Sharanya M P', 'sharanya@edroots.in', '+91 9947 921306', 'Academic Counselor (Kochi)'],
            7 => [3, 3, 2, 'Soumya Ajith', 'soumya@edroots.in', '+91 9061 253555', 'Academic Counselor (Calicut)'],
            8 => [2, 3, 2, 'Shilpa Jeejo', 'shilpa@edroots.in', '+91 8330 087748', 'Academic Counselor (Perinthalmanna)'],
            9 => [5, 3, 2, 'Carol Pinto', 'carol@edroots.in', '+91 7760 577897', 'Academic Counselor (Mangalore)'],
            10 => [4, 3, 2, 'Reshmitha P', 'reshmitha@edroots.in', '+91 7511 140594', 'Academic Counselor (Kannur)'],
            11 => [2, 3, 2, 'Mridhula K', 'mridhula@edroots.in', '+91 9656 873578', 'Academic Counselor (Perinthalmanna)'],
            12 => [2, 3, 2, 'Sahadiya V N', 'sahadiya@edroots.in', '+91 8606 024114', 'Academic Counselor (Perinthalmanna)'],
            13 => [3, 3, 2, 'Neethi Gireesh', 'neethi@edroots.in', '+91 9947 788119', 'Academic Counselor (Calicut)'],
            14 => [1, 3, 2, 'Twinkle Judy', 'twinkle@edroots.in', '+91 8891 275935', 'Academic Counselor (Kochi)'],
            15 => [1, 3, 2, 'Bincy Xavier', 'bincy@edroots.in', '+91 8075 241649', 'Academic Counselor (Kochi)']
        ];

        $stmtUser = $pdo->prepare("
            INSERT INTO users (id, branch_id, role_id, manager_id, name, email, password, phone, designation, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active')
            ON DUPLICATE KEY UPDATE 
                branch_id = VALUES(branch_id), role_id = VALUES(role_id), manager_id = VALUES(manager_id),
                name = VALUES(name), email = VALUES(email), password = VALUES(password),
                phone = VALUES(phone), designation = VALUES(designation), status = 'Active'
        ");
        foreach ($staffUsers as $id => $u) {
            $stmtUser->execute([$id, $u[0], $u[1], $u[2], $u[3], $u[4], $defaultPassword, $u[5], $u[6]]);
        }

        // 4. Update Partner Universities to real destinations from PDF
        $unis = [
            1 => ['University of Greenwich, London', 'United Kingdom', 'London', 'QS Top UK Modern Uni', 'gre.ac.uk'],
            2 => ['University of Hertfordshire', 'United Kingdom', 'Hatfield', 'TEF Gold Rated', 'herts.ac.uk'],
            3 => ['Coventry University', 'United Kingdom', 'Coventry', 'Top 30 UK University', 'coventry.ac.uk'],
            4 => ['University of East London', 'United Kingdom', 'London', 'London Docklands Campus', 'uel.ac.uk'],
            5 => ['University of Birmingham', 'United Kingdom', 'Birmingham', 'Russell Group Global #84', 'bham.ac.uk'],
            6 => ['King\'s College London', 'United Kingdom', 'London', 'Russell Group Global #40', 'kcl.ac.uk'],
            7 => ['Fanshawe College, Ontario', 'Canada', 'London, Ontario', 'SPP / DLI Partner College', 'fanshawec.ca'],
            8 => ['Concordia University, Montreal', 'Canada', 'Montreal, Quebec', 'Top Comprehensive Canadian Uni', 'concordia.ca'],
            9 => ['Technical University of Munich (TUM)', 'Germany', 'Munich', 'QS World Top 35 University', 'tum.de'],
            10 => ['Deakin University, Melbourne', 'Australia', 'Melbourne, Victoria', 'Top 1% Global Universities', 'deakin.edu.au'],
            11 => ['Trinity College Dublin', 'Ireland', 'Dublin', 'QS World #81, Ireland\'s #1', 'tcd.ie'],
            12 => ['Dublin City University (DCU)', 'Ireland', 'Dublin', 'QS Top 50 Under 50', 'dcu.ie']
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

        // 5. Update / Seed Active Leads with authentic PDF students
        $leads = [
            // Reg.# 54879: Nileena (Lead dossier in Page 14)
            [
                'lead_code' => 'LED-54879', 'full_name' => 'Nileena', 'mobile_number' => '+91 90741 34910', 'phone_normalized' => '9074134910',
                'branch_id' => 2, 'counselor_id' => 2, 'degree' => 'B.Com-2017', 'college' => 'Perinthalmanna Arts & Science',
                'country' => 'UK', 'uni' => 'University of Greenwich, London', 'course' => 'MSc International Business',
                'prospectivity' => 'Moderate', 'enquiry_type' => 'TELE', 'source' => 'Ex Student', 'stage_id' => 1,
                'status_note' => 'Lead - Information shared', 'is_customer' => 0, 'created_at' => '2021-08-09 11:25:00'
            ],
            // Reg.# 54376: Mohammed Ameen M K (Page 10)
            [
                'lead_code' => 'LED-54376', 'full_name' => 'Mohammed Ameen M K', 'mobile_number' => '+91 96333 71111', 'phone_normalized' => '9633371111',
                'branch_id' => 1, 'counselor_id' => 4, 'degree' => 'B.Tech Computer Science', 'college' => 'Model Engineering College, Kochi',
                'country' => 'UK', 'uni' => 'University of Hertfordshire', 'course' => 'MSc Advanced Computer Science',
                'prospectivity' => 'Low', 'enquiry_type' => 'WALK IN', 'source' => 'Direct Reference', 'stage_id' => 1,
                'status_note' => 'Stage1- Enquiry', 'is_customer' => 0, 'created_at' => '2021-08-05 10:15:00'
            ],
            // Reg.# 54375: SNEHA THOMAS (Page 10)
            [
                'lead_code' => 'LED-54375', 'full_name' => 'SNEHA THOMAS', 'mobile_number' => '+91 75111 40594', 'phone_normalized' => '7511140594',
                'branch_id' => 4, 'counselor_id' => 10, 'degree' => 'B.Sc Nursing', 'college' => 'Lourdes College of Nursing',
                'country' => 'UK', 'uni' => 'Coventry University', 'course' => 'MSc Global Healthcare Management',
                'prospectivity' => 'Low', 'enquiry_type' => 'TELE', 'source' => 'UK Jan22 FB Leads', 'stage_id' => 1,
                'status_note' => 'Stage1- Enquiry', 'is_customer' => 0, 'created_at' => '2021-08-05 11:30:00'
            ],
            // Reg.# 54451: ALBERT JOSEPH (Page 9)
            [
                'lead_code' => 'LED-54451', 'full_name' => 'ALBERT JOSEPH', 'mobile_number' => '+91 85475 97476', 'phone_normalized' => '8547597476',
                'branch_id' => 7, 'counselor_id' => 3, 'degree' => 'B.Tech Mechanical', 'college' => 'LBS College of Engineering, Kasaragod',
                'country' => 'UK', 'uni' => 'University of East London', 'course' => 'MSc Mechanical Engineering',
                'prospectivity' => 'Low', 'enquiry_type' => 'WALK IN', 'source' => 'Walk-in', 'stage_id' => 1,
                'status_note' => 'Stage1- Enquiry', 'is_customer' => 0, 'created_at' => '2021-08-05 14:00:00'
            ],
            // Reg.# 54089: VISHAKH (Page 9)
            [
                'lead_code' => 'LED-54089', 'full_name' => 'VISHAKH', 'mobile_number' => '+91 85479 30281', 'phone_normalized' => '8547930281',
                'branch_id' => 7, 'counselor_id' => 3, 'degree' => 'BBA Marketing', 'college' => 'Nehru Arts and Science College, Kanhangad',
                'country' => 'Canada', 'uni' => 'Fanshawe College, Ontario', 'course' => 'Postgraduate Certificate in Project Management',
                'prospectivity' => 'Low', 'enquiry_type' => 'TELE', 'source' => 'Canada Jan/May 22 FB leads', 'stage_id' => 1,
                'status_note' => 'Lead - Course Option shared', 'is_customer' => 0, 'created_at' => '2021-08-03 09:45:00'
            ],
            // Reg.# 54048: YADUKRISHNAN (Page 9)
            [
                'lead_code' => 'LED-54048', 'full_name' => 'YADUKRISHNAN', 'mobile_number' => '+91 94005 38097', 'phone_normalized' => '9400538097',
                'branch_id' => 7, 'counselor_id' => 3, 'degree' => 'B.Com Finance', 'college' => 'Kannur University Campus',
                'country' => 'UK', 'uni' => 'University of Greenwich, London', 'course' => 'MSc Accounting and Finance',
                'prospectivity' => 'Moderate', 'enquiry_type' => 'WALK IN', 'source' => 'Ex Student Reference', 'stage_id' => 1,
                'status_note' => 'Lead - Course Option shared', 'is_customer' => 0, 'created_at' => '2021-08-03 16:20:00'
            ],
            // Reg.# 54628: REVATHY (Page 12, 19)
            [
                'lead_code' => 'LED-54628', 'full_name' => 'REVATHY', 'mobile_number' => '+91 79076 63590', 'phone_normalized' => '7907663590',
                'branch_id' => 3, 'counselor_id' => 3, 'degree' => 'B.Sc Biotechnology', 'college' => 'Farook College, Kozhikode',
                'country' => 'UK', 'uni' => 'University of Birmingham', 'course' => 'MSc Biotechnology and Enterprise',
                'prospectivity' => 'Low', 'enquiry_type' => 'TELE', 'source' => 'Reception Calicut', 'stage_id' => 1,
                'status_note' => 'Lead - Call not connected', 'is_customer' => 0, 'created_at' => '2021-08-06 10:00:00'
            ],
            // Customer: Test- Shareef Customer (Page 17 - Invoice INV-6262)
            [
                'lead_code' => 'LED-54629', 'full_name' => 'Test- Shareef Customer', 'mobile_number' => '+91 98957 16711', 'phone_normalized' => '9895716711',
                'branch_id' => 2, 'counselor_id' => 5, 'degree' => 'B.Tech Civil', 'college' => 'MES College of Engineering, Kuttippuram',
                'country' => 'UK', 'uni' => 'Coventry University', 'course' => 'MSc Construction Management',
                'prospectivity' => 'High', 'enquiry_type' => 'WALK IN', 'source' => 'Direct Reference', 'stage_id' => 2,
                'status_note' => 'Invoiced & Converted to Customer', 'is_customer' => 1, 'created_at' => '2021-08-09 11:00:00'
            ],
            // Customer: Fawas (Page 19, 29)
            [
                'lead_code' => 'LED-54630', 'full_name' => 'Fawas', 'mobile_number' => '+91 99479 21306', 'phone_normalized' => '9947921306',
                'branch_id' => 1, 'counselor_id' => 6, 'degree' => 'B.Sc Computer Science', 'college' => 'Rajagiri College of Social Sciences',
                'country' => 'UK', 'uni' => 'King\'s College London', 'course' => 'MSc Data Science',
                'prospectivity' => 'High', 'enquiry_type' => 'TELE', 'source' => 'Website Leads', 'stage_id' => 2,
                'status_note' => 'Customer - Documents Pending', 'is_customer' => 1, 'created_at' => '2021-08-09 12:30:00'
            ],
            // Reg.# 54636: Test- User-Manohar (Page 15, 33)
            [
                'lead_code' => 'LED-54636', 'full_name' => 'Test- User-Manohar', 'mobile_number' => '+91 98957 16725', 'phone_normalized' => '9895716725',
                'branch_id' => 2, 'counselor_id' => 5, 'degree' => 'B.Com Computer Applications', 'college' => 'Al Salama Arts College',
                'country' => 'Canada', 'uni' => 'Fanshawe College, Ontario', 'course' => 'PG Diploma in Business Analysis',
                'prospectivity' => 'Low', 'enquiry_type' => 'TELE', 'source' => 'Canada Jan/May 22 FB leads', 'stage_id' => 1,
                'status_note' => 'Lead - Call not connected', 'is_customer' => 0, 'created_at' => '2021-08-09 10:15:00'
            ]
        ];

        $stmtLead = $pdo->prepare("
            INSERT INTO leads (
                lead_code, full_name, mobile_number, phone_normalized, branch_id, assigned_counselor_id,
                highest_qualification, institution_name, target_country, target_university, target_course,
                prospectivity, enquiry_type, lead_source, stage_id, secondary_status,
                is_converted_customer, customer_code, created_at, last_contacted_at, next_followup_date, followup_count, is_archived
            ) VALUES (
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, 1, 0
            ) ON DUPLICATE KEY UPDATE 
                full_name = VALUES(full_name), mobile_number = VALUES(mobile_number), phone_normalized = VALUES(phone_normalized),
                branch_id = VALUES(branch_id), assigned_counselor_id = VALUES(assigned_counselor_id),
                highest_qualification = VALUES(highest_qualification), institution_name = VALUES(institution_name),
                target_country = VALUES(target_country), target_university = VALUES(target_university), target_course = VALUES(target_course),
                prospectivity = VALUES(prospectivity), enquiry_type = VALUES(enquiry_type), lead_source = VALUES(lead_source),
                stage_id = VALUES(stage_id), secondary_status = VALUES(secondary_status), is_converted_customer = VALUES(is_converted_customer)
        ");

        foreach ($leads as $l) {
            $custCode = $l['is_customer'] ? 'CUST-' . substr($l['lead_code'], 4) : null;
            $nextDate = date('Y-m-d', strtotime('+3 days'));
            $stmtLead->execute([
                $l['lead_code'], $l['full_name'], $l['mobile_number'], $l['phone_normalized'], $l['branch_id'], $l['counselor_id'],
                $l['degree'], $l['college'], $l['country'], $l['uni'], $l['course'],
                $l['prospectivity'], $l['enquiry_type'], $l['source'], $l['stage_id'], $l['status_note'],
                $l['is_customer'], $custCode, $l['created_at'], $l['created_at'], $nextDate
            ]);
        }

        // 6. Update / Seed College Data Pool (Page 22)
        $collegeEntries = [
            ['Rafeena p', '+91 98957 10001', '9895710001', 'rafeena.p@gmail.com', 'MES Mampad College', 'B.Com Finance', 2021, 'Malappuram', 'Campus Seminar', 'fb', 2, 'NO', 1],
            ['Reenu ck', '+91 98957 10002', '9895710002', 'reenu.ck@gmail.com', 'Farook College, Kozhikode', 'BBA International Business', 2021, 'Calicut', 'Campus Seminar', 'fb', 3, 'NO', 1],
            ['Shameer Husain', '+91 98957 10003', '9895710003', 'shameer.h@gmail.com', 'St. Alberts College, Ernakulam', 'B.Sc Computer Science', 2021, 'Kochi', 'Campus Seminar', 'fb', 1, 'NO', 1],
            ['Manu Krishan', '+91 98957 10004', '9895710004', 'manu.k@gmail.com', 'Govt Engineering College, Thrissur', 'B.Tech Mechanical', 2020, 'Thrissur', 'Campus Seminar', 'fb', 1, 'NO', 1],
            ['Janaki Devi', '+91 98957 10005', '9895710005', 'janaki.d@gmail.com', 'Sacred Heart College, Thevara', 'B.Com Taxation', 2021, 'Kochi', 'Campus Seminar', 'fb', 1, 'NO', 1]
        ];

        $stmtCol = $pdo->prepare("
            INSERT INTO college_data (student_name, mobile_number, phone_normalized, email, college_name, degree, passout_year, location, comments, data_source, branch_id, is_converted, converted_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                student_name = VALUES(student_name), college_name = VALUES(college_name), 
                degree = VALUES(degree), passout_year = VALUES(passout_year), location = VALUES(location)
        ");
        foreach ($collegeEntries as $ce) {
            $stmtCol->execute($ce);
        }

        // 7. Update / Seed Website & Social Media Leads (Page 38)
        $webLeads = [
            ['Shibin thomas', '+91 97458 55900', '9745855900', 'shibinvs98@gmail.com', 'Wayanad, Kerala', 'MSc Data Analytics', 'Coventry University', 'UK', 5, 'UK Jan22 FB Leads', 'Pending'],
            ['Thasleer.k', '+91 86066 62961', '8606662961', 'thasleerk@gmail.com', 'Iritty, Kannur', 'MSc International Business', 'University of Greenwich', 'UK', 4, 'UK Jan22 FB Leads', 'Pending'],
            ['prajith', '+91 89439 88581', '8943988581', 'prajithhari.p@gmail.com', 'Thiruvananthapuram, Kerala', 'MBA Global Healthcare', 'University of East London', 'UK', 1, 'UK Jan22 FB Leads', 'Pending'],
            ['JyoThis Peter', '+91 95448 20602', '9544820602', 'jyothisjyothu@gmail.com', 'Chavakkad, Thrissur', 'PG Certificate Project Management', 'Fanshawe College, Ontario', 'Canada', 2, 'Canada Jan/May 22 FB leads', 'Pending'],
            ['Chaithanya Santhosh', '+91 95444 28086', '9544428086', 'chaithanyacm25@gmail.com', 'Nemmara, Palakkad', 'Master of Management', 'Concordia University, Montreal', 'Canada', 2, 'Canada Jan/May 22 FB leads', 'Pending']
        ];

        $stmtWl = $pdo->prepare("
            INSERT INTO web_leads (student_name, mobile_number, phone_normalized, email, city_state, target_course, target_university, target_country, nearest_branch_id, source_campaign, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                student_name = VALUES(student_name), email = VALUES(email), city_state = VALUES(city_state),
                target_course = VALUES(target_course), target_university = VALUES(target_university)
        ");
        foreach ($webLeads as $wl) {
            $stmtWl->execute($wl);
        }

        // 8. Update / Seed Official Invoice INV-6262 for Shareef Customer (Page 17)
        try {
            $shareefStmt = $pdo->query("SELECT id FROM leads WHERE full_name = 'Test- Shareef Customer' LIMIT 1");
            $shareefId = $shareefStmt ? $shareefStmt->fetchColumn() : null;
            if ($shareefId) {
                $pdo->exec("
                    INSERT INTO invoices (id, invoice_number, lead_id, company_id, branch_id, subtotal, tax_rate, tax_amount, discount_amount, total_amount, paid_amount, balance_due, invoice_date, due_date, status, created_by)
                    VALUES (6262, 'INV-6262', {$shareefId}, 1, 2, 1000.00, 10.00, 100.00, 0.00, 1100.00, 110.00, 990.00, '2021-08-09', '2021-08-10', 'Partially Paid', 2)
                    ON DUPLICATE KEY UPDATE 
                        invoice_number = 'INV-6262', total_amount = 1100.00, paid_amount = 110.00, balance_due = 990.00, status = 'Partially Paid'
                ");
            }
        } catch (Exception $invEx) {
            // Silently continue if invoices table differs
        }

        // 9. Update / Seed General Training Documents (PDF Section 4.3 & 10.0)
        try {
            $pdo->exec("
                INSERT INTO general_documents (id, title, category, country, description, file_path, file_name, file_size, uploaded_by)
                VALUES 
                (1, 'Edroots CRM - Official User Guide & Training Manual', 'Training Documents', 'Global', 'Official user manual and step-by-step operating guidelines for Edroots CRM.', 'assets/docs/edroots_crm_user_manual.pdf', 'edroots_crm_user_manual.pdf', 2393736, 1),
                (2, 'UK University Admissions Master Handbook & CAS Guide', 'University Prospectuses', 'United Kingdom', 'Comprehensive UK university entry requirements, CAS issuing timelines, and visa interview guidelines.', 'assets/docs/edroots_crm_user_manual.pdf', 'uk_admissions_handbook_2026.pdf', 1820000, 1),
                (3, 'Canada Student Direct Stream (SDS) Checklist', 'Visa Checklists', 'Canada', 'Checklist for Canadian study permit under SDS pathway including GIC and medical proof.', 'assets/docs/edroots_crm_user_manual.pdf', 'canada_sds_visa_guide.pdf', 2140000, 2),
                (4, 'Edroots SOP & Statement of Purpose Master Template', 'SOP Guidelines & Templates', 'Global', 'Standardized structure and writing prompt for university statement of purpose.', 'assets/docs/edroots_crm_user_manual.pdf', 'edroots_sop_master_template.pdf', 940000, 1)
                ON DUPLICATE KEY UPDATE 
                    title = VALUES(title), category = VALUES(category), country = VALUES(country), 
                    description = VALUES(description), file_path = VALUES(file_path), file_name = VALUES(file_name)
            ");
        } catch (Exception $docEx) {
            // Silently continue
        }

        $_SESSION['edroots_manual_synced_v3'] = true;
    } catch (Exception $e) {
        // Log sync error for diagnosis without breaking request
        error_log('Edroots DB Sync Notice: ' . $e->getMessage());
    }
}
