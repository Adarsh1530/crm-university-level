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
    if (!empty($_SESSION['edroots_manual_synced_v7'])) {
        return;
    }

    try {
        // Fast test: Check if Nileena exists in leads
        $leadCheck = $pdo->query("SELECT id FROM leads WHERE full_name = 'Nileena' LIMIT 1");
        $hasNileena = $leadCheck && $leadCheck->fetch();

        // Check if Noufal Peediyakkal exists as user 1
        $userCheck = $pdo->query("SELECT name FROM users WHERE id = 1 LIMIT 1");
        $user1 = $userCheck ? $userCheck->fetch() : null;
        $hasNoufal = $user1 && $user1['name'] === 'Noufal Peediyakkal';

        if ($hasNileena && $hasNoufal) {
            $_SESSION['edroots_manual_synced_v7'] = true;
            return;
        }

        $defaultPassword = password_hash('admin123', PASSWORD_BCRYPT);

        // 1. Company Profile
        $pdo->exec("
            UPDATE companies 
            SET name = 'Edroots International', 
                email = 'info@edroots.in', 
                phone = '+91 4933-224333', 
                website = 'https://edroots.in', 
                address = '3rd floor, Al Hazmi Tower, Behind SBT, Pattambi Road, Perinthalmanna' 
            WHERE id = 1
        ");

        // 2. Branches
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

        // 3. Staff Personas
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
            10 => [4, 3, 2, 'Reshmitha P', 'reshmitha@edroots.in', '+91 7511 140594', 'Academic Counselor (Kannur)']
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

        // 4. Partner Universities
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

        // 5. Authentic Student Leads from PDF User Manual
        $leads = [
            // Reg.# 54879: Nileena (Lead dossier in Page 14)
            [
                'id' => 1, 'lead_code' => 'LED-54879', 'full_name' => 'Nileena', 'phone' => '9074134910', 'mobile_number' => '+91 90741 34910', 'email' => 'nileena.54879@gmail.com',
                'branch_id' => 2, 'counselor_id' => 2, 'degree' => 'B.Com-2017', 'college' => 'Perinthalmanna Arts & Science',
                'country' => 'United Kingdom', 'course' => 'MSc International Business',
                'priority' => 'Moderate', 'source' => 'Ex Student', 'stage' => 'ENQUIRY', 'secondary_status' => 'Active',
                'notes' => 'Lead - Information shared', 'is_customer' => 0, 'is_archived' => 0, 'created_at' => '2021-08-09 11:25:00'
            ],
            // Reg.# 54376: Mohammed Ameen M K (Page 10)
            [
                'id' => 2, 'lead_code' => 'LED-54376', 'full_name' => 'Mohammed Ameen M K', 'phone' => '9633371111', 'mobile_number' => '+91 96333 71111', 'email' => 'ameen.mk@gmail.com',
                'branch_id' => 1, 'counselor_id' => 4, 'degree' => 'B.Tech Computer Science', 'college' => 'Model Engineering College, Kochi',
                'country' => 'United Kingdom', 'course' => 'MSc Advanced Computer Science',
                'priority' => 'Low', 'source' => 'Direct Reference', 'stage' => 'ENQUIRY', 'secondary_status' => 'Active',
                'notes' => 'Stage1- Enquiry', 'is_customer' => 0, 'is_archived' => 0, 'created_at' => '2021-08-05 10:15:00'
            ],
            // Reg.# 54375: SNEHA THOMAS (Page 10)
            [
                'id' => 3, 'lead_code' => 'LED-54375', 'full_name' => 'SNEHA THOMAS', 'phone' => '7511140594', 'mobile_number' => '+91 75111 40594', 'email' => 'sneha.thomas@gmail.com',
                'branch_id' => 4, 'counselor_id' => 10, 'degree' => 'B.Sc Nursing', 'college' => 'Lourdes College of Nursing',
                'country' => 'United Kingdom', 'course' => 'MSc Global Healthcare Management',
                'priority' => 'Low', 'source' => 'Social Media', 'stage' => 'ENQUIRY', 'secondary_status' => 'Active',
                'notes' => 'Stage1- Enquiry', 'is_customer' => 0, 'is_archived' => 0, 'created_at' => '2021-08-05 11:30:00'
            ],
            // Reg.# 54451: ALBERT JOSEPH (Page 9)
            [
                'id' => 4, 'lead_code' => 'LED-54451', 'full_name' => 'ALBERT JOSEPH', 'phone' => '8547597476', 'mobile_number' => '+91 85475 97476', 'email' => 'albert.joseph@gmail.com',
                'branch_id' => 7, 'counselor_id' => 3, 'degree' => 'B.Tech Mechanical', 'college' => 'LBS College of Engineering, Kasaragod',
                'country' => 'United Kingdom', 'course' => 'MSc Mechanical Engineering',
                'priority' => 'Low', 'source' => 'Walk-in', 'stage' => 'ENQUIRY', 'secondary_status' => 'Active',
                'notes' => 'Stage1- Enquiry', 'is_customer' => 0, 'is_archived' => 0, 'created_at' => '2021-08-05 14:00:00'
            ],
            // Reg.# 54089: VISHAKH (Page 9)
            [
                'id' => 5, 'lead_code' => 'LED-54089', 'full_name' => 'VISHAKH', 'phone' => '8547930281', 'mobile_number' => '+91 85479 30281', 'email' => 'vishakh.k@gmail.com',
                'branch_id' => 7, 'counselor_id' => 3, 'degree' => 'BBA Marketing', 'college' => 'Nehru Arts and Science College, Kanhangad',
                'country' => 'Canada', 'course' => 'Postgraduate Certificate in Project Management',
                'priority' => 'Low', 'source' => 'Social Media', 'stage' => 'ENQUIRY', 'secondary_status' => 'Active',
                'notes' => 'Lead - Course Option shared', 'is_customer' => 0, 'is_archived' => 0, 'created_at' => '2021-08-03 09:45:00'
            ],
            // Reg.# 54048: YADUKRISHNAN (Page 9)
            [
                'id' => 6, 'lead_code' => 'LED-54048', 'full_name' => 'YADUKRISHNAN', 'phone' => '9400538097', 'mobile_number' => '+91 94005 38097', 'email' => 'yadukrishnan@gmail.com',
                'branch_id' => 7, 'counselor_id' => 3, 'degree' => 'B.Com Finance', 'college' => 'Kannur University Campus',
                'country' => 'United Kingdom', 'course' => 'MSc Accounting and Finance',
                'priority' => 'Moderate', 'source' => 'Ex Student', 'stage' => 'ENQUIRY', 'secondary_status' => 'Active',
                'notes' => 'Lead - Course Option shared', 'is_customer' => 0, 'is_archived' => 0, 'created_at' => '2021-08-03 16:20:00'
            ],
            // Reg.# 54628: REVATHY (Page 12, 19)
            [
                'id' => 7, 'lead_code' => 'LED-54628', 'full_name' => 'REVATHY', 'phone' => '7907663590', 'mobile_number' => '+91 79076 63590', 'email' => 'revathy.clt@gmail.com',
                'branch_id' => 3, 'counselor_id' => 3, 'degree' => 'B.Sc Biotechnology', 'college' => 'Farook College, Kozhikode',
                'country' => 'United Kingdom', 'course' => 'MSc Biotechnology and Enterprise',
                'priority' => 'Low', 'source' => 'Walk-in', 'stage' => 'ENQUIRY', 'secondary_status' => 'Active',
                'notes' => 'Lead - Call not connected', 'is_customer' => 0, 'is_archived' => 0, 'created_at' => '2021-08-06 10:00:00'
            ],
            // Customer: Test- Shareef Customer (Page 17 - Invoice INV-6262)
            [
                'id' => 8, 'lead_code' => 'LED-54629', 'full_name' => 'Test- Shareef Customer', 'phone' => '9895716711', 'mobile_number' => '+91 98957 16711', 'email' => 'shareef.customer@gmail.com',
                'branch_id' => 2, 'counselor_id' => 5, 'degree' => 'B.Tech Civil', 'college' => 'MES College of Engineering, Kuttippuram',
                'country' => 'United Kingdom', 'course' => 'MSc Construction Management',
                'priority' => 'High', 'source' => 'Direct Reference', 'stage' => 'CUSTOMER', 'secondary_status' => 'Active',
                'notes' => 'Invoiced & Converted to Customer', 'is_customer' => 1, 'is_archived' => 0, 'created_at' => '2021-08-09 11:00:00'
            ],
            // Customer: Fawas (Page 19, 29)
            [
                'id' => 9, 'lead_code' => 'LED-54630', 'full_name' => 'Fawas', 'phone' => '9947921306', 'mobile_number' => '+91 99479 21306', 'email' => 'fawas.kochi@gmail.com',
                'branch_id' => 1, 'counselor_id' => 6, 'degree' => 'B.Sc Computer Science', 'college' => 'Rajagiri College of Social Sciences',
                'country' => 'United Kingdom', 'course' => 'MSc Data Science',
                'priority' => 'High', 'source' => 'Website', 'stage' => 'CUSTOMER', 'secondary_status' => 'Active',
                'notes' => 'Customer - Documents Pending', 'is_customer' => 1, 'is_archived' => 0, 'created_at' => '2021-08-09 12:30:00'
            ],
            // Reg.# 54636: Test- User-Manohar (Page 15, 33)
            [
                'id' => 10, 'lead_code' => 'LED-54636', 'full_name' => 'Test- User-Manohar', 'phone' => '9895716725', 'mobile_number' => '+91 98957 16725', 'email' => 'manohar.test@gmail.com',
                'branch_id' => 2, 'counselor_id' => 5, 'degree' => 'B.Com Computer Applications', 'college' => 'Al Salama Arts College',
                'country' => 'Canada', 'course' => 'PG Diploma in Business Analysis',
                'priority' => 'Low', 'source' => 'Social Media', 'stage' => 'ENQUIRY', 'secondary_status' => 'Active',
                'notes' => 'Lead - Call not connected', 'is_customer' => 0, 'is_archived' => 0, 'created_at' => '2021-08-09 10:15:00'
            ],
            // Archived Student: Salish (Page 26)
            [
                'id' => 11, 'lead_code' => 'LED-54430', 'full_name' => 'Salish', 'phone' => '9947502125', 'mobile_number' => '+91 99475 02125', 'email' => 'salish.pmna@gmail.com',
                'branch_id' => 2, 'counselor_id' => 4, 'degree' => 'B.Sc Chemistry', 'college' => 'Govt College Malappuram',
                'country' => 'United Kingdom', 'course' => 'MSc Analytical Chemistry',
                'priority' => 'Low', 'source' => 'Walk-in', 'stage' => 'ENQUIRY', 'secondary_status' => 'Active',
                'notes' => 'Archived lead for future intake', 'is_customer' => 0, 'is_archived' => 1, 'created_at' => '2021-08-05 09:30:00'
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

        // 6. College Data Pool (Page 22)
        $collegeEntries = [
            [1, 'Rafeena p', '+91 98957 10001', '9895710001', 'rafeena.p@gmail.com', 'MES Mampad College', 'B.Com Finance', 2, 'Malappuram', 'NO', 1],
            [2, 'Reenu ck', '+91 98957 10002', '9895710002', 'reenu.ck@gmail.com', 'Farook College, Kozhikode', 'BBA International Business', 3, 'Calicut', 'NO', 1],
            [3, 'Shameer Husain', '+91 98957 10003', '9895710003', 'shameer.h@gmail.com', 'St. Alberts College, Ernakulam', 'B.Sc Computer Science', 1, 'Kochi', 'NO', 1],
            [4, 'Manu Krishan', '+91 98957 10004', '9895710004', 'manu.k@gmail.com', 'Govt Engineering College, Thrissur', 'B.Tech Mechanical', 1, 'Thrissur', 'NO', 1],
            [5, 'Janaki Devi', '+91 98957 10005', '9895710005', 'janaki.d@gmail.com', 'Sacred Heart College, Thevara', 'B.Com Taxation', 1, 'Kochi', 'NO', 1]
        ];

        $stmtCol = $pdo->prepare("
            INSERT INTO college_data (id, student_name, mobile_number, phone_normalized, email, college_name, degree, branch_id, location, is_converted, converted_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                student_name = VALUES(student_name), mobile_number = VALUES(mobile_number),
                phone_normalized = VALUES(phone_normalized),
                college_name = VALUES(college_name), degree = VALUES(degree), location = VALUES(location)
        ");
        foreach ($collegeEntries as $ce) {
            $stmtCol->execute($ce);
        }

        // 7. Website & Social Media Leads (Page 38)
        $webLeads = [
            [1, 'Shibin thomas', '+91 97458 55900', '9745855900', 'shibinvs98@gmail.com', 'Wayanad, Kerala', 'MSc Data Analytics', 'Coventry University', 'UK', 5, 'UK Jan22 FB Leads', 'Pending'],
            [2, 'Thasleer.k', '+91 86066 62961', '8606662961', 'thasleerk@gmail.com', 'Iritty, Kannur', 'MSc International Business', 'University of Greenwich', 'UK', 4, 'UK Jan22 FB Leads', 'Pending'],
            [3, 'prajith', '+91 89439 88581', '8943988581', 'prajithhari.p@gmail.com', 'Thiruvananthapuram, Kerala', 'MBA Global Healthcare', 'University of East London', 'UK', 1, 'UK Jan22 FB Leads', 'Pending'],
            [4, 'JyoThis Peter', '+91 95448 20602', '9544820602', 'jyothisjyothu@gmail.com', 'Chavakkad, Thrissur', 'PG Certificate Project Management', 'Fanshawe College, Ontario', 'Canada', 2, 'Canada Jan/May 22 FB leads', 'Pending'],
            [5, 'Chaithanya Santhosh', '+91 95444 28086', '9544428086', 'chaithanyacm25@gmail.com', 'Nemmara, Palakkad', 'Master of Management', 'Concordia University, Montreal', 'Canada', 2, 'Canada Jan/May 22 FB leads', 'Pending']
        ];

        $stmtWl = $pdo->prepare("
            INSERT INTO web_leads (id, student_name, mobile_number, phone_normalized, email, city_state, target_course, target_university, target_country, nearest_branch_id, source_campaign, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                student_name = VALUES(student_name), mobile_number = VALUES(mobile_number), phone_normalized = VALUES(phone_normalized),
                email = VALUES(email), city_state = VALUES(city_state), target_course = VALUES(target_course), target_university = VALUES(target_university)
        ");
        foreach ($webLeads as $wl) {
            $stmtWl->execute($wl);
        }

        // 8. General Training Documents (PDF Section 4.3 & 10.0)
        $pdo->exec("
            INSERT INTO general_documents (id, title, category, country, description, file_path, file_name, file_size, uploaded_by)
            VALUES 
            (1, 'Edroots CRM - Official User Guide & Training Manual', 'General Guidelines', 'Global', 'Official user manual and step-by-step operating guidelines for Edroots CRM.', 'assets/docs/edroots_crm_user_manual.pdf', 'edroots_crm_user_manual.pdf', 2393736, 1),
            (2, 'UK University Admissions Process & CAS Guidelines', 'Partner University Lists', 'United Kingdom', 'Comprehensive UK university entry requirements, CAS issuing timelines, and visa interview guidelines.', 'assets/docs/edroots_crm_user_manual.pdf', 'uk_admissions_handbook_2026.pdf', 1820000, 1),
            (3, 'Canada Student Direct Stream (SDS) Checklist', 'Visa Checklists', 'Canada', 'Checklist for Canadian study permit under SDS pathway including GIC and medical proof.', 'assets/docs/edroots_crm_user_manual.pdf', 'canada_sds_visa_guide.pdf', 2140000, 2),
            (4, 'Edroots SOP & Statement of Purpose Master Template', 'SOP Guidelines & Templates', 'Global', 'Standardized structure and writing prompt for university statement of purpose.', 'assets/docs/edroots_crm_user_manual.pdf', 'edroots_sop_master_template.pdf', 940000, 1)
            ON DUPLICATE KEY UPDATE 
                title = VALUES(title), category = VALUES(category), country = VALUES(country), 
                description = VALUES(description), file_path = VALUES(file_path), file_name = VALUES(file_name)
        ");

        // 9. Follow-ups
        try {
            $pdo->exec("
                INSERT INTO followups (lead_id, followup_date, followup_time, interaction_title_id, notes, stage_id, next_followup_date, counselor_id, status)
                VALUES 
                (7, CURDATE(), '10:30:00', 1, 'Follow-up consultation for Revathy (UK Biotechnology)', 1, DATE_ADD(CURDATE(), INTERVAL 3 DAY), 3, 'Pending'),
                (1, CURDATE(), '11:45:00', 2, 'Review course brochures and fee structure with Nileena', 1, DATE_ADD(CURDATE(), INTERVAL 2 DAY), 2, 'Pending'),
                (4, CURDATE(), '14:00:00', 1, 'Connect with Albert Joseph regarding East London mechanical modules', 1, DATE_ADD(CURDATE(), INTERVAL 4 DAY), 3, 'Pending'),
                (5, CURDATE(), '15:30:00', 2, 'Course options discussion for Vishakh (Fanshawe College)', 1, DATE_ADD(CURDATE(), INTERVAL 5 DAY), 3, 'Pending')
            ");
        } catch (Exception $fEx) {
            // Silently continue
        }

        // 10. Official Invoice INV-6262 for Shareef Customer (Page 17)
        try {
            $pdo->exec("
                INSERT INTO invoices (id, invoice_number, lead_id, company_id, branch_id, subtotal, tax_rate, tax_amount, discount_amount, total_amount, paid_amount, balance_due, invoice_date, due_date, status, created_by)
                VALUES (6262, 'INV-6262', 8, 1, 2, 1000.00, 10.00, 100.00, 0.00, 1100.00, 110.00, 990.00, '2021-08-09', '2021-08-10', 'Partially Paid', 2)
                ON DUPLICATE KEY UPDATE 
                    invoice_number = 'INV-6262', total_amount = 1100.00, paid_amount = 110.00, balance_due = 990.00, status = 'Partially Paid'
            ");
        } catch (Exception $invEx) {
            error_log('Invoice sync error: ' . $invEx->getMessage());
        }

        $_SESSION['edroots_manual_synced_v7'] = true;
    } catch (Exception $e) {
        error_log('Edroots DB Sync Notice: ' . $e->getMessage());
    }
}
