<?php
/**
 * CRM Automatic Data & Schema Synchronizer
 * Establishes clean, authentic Core CRM records aligned 1:1 with Client Requirements:
 * 1. System Identity: Core CRM (Customer Relationship Management)
 * 2. Multi-Role Hierarchy: Super Admin (1), Team Leader (2), Counselor / Advisor (3), Operations / Accessor (4)
 * 3. Regional Geographic Segmentation: Region Numbers (REG-01..), Main Regions, Zone Types (Center vs Outer Zone)
 * 4. Lead Classification & Prospectivity: Hot, Warm, Cold
 * 5. Call Data Integration: Centralized call logs table
 * 6. Phone Cross-Checking: Digit verification and business records
 */

function sync_institutional_data(PDO $pdo) {
    try {
        // 1. Purge legacy placeholder documents and college data pool
        $pdo->exec("DELETE FROM general_documents");
        $pdo->exec("DELETE FROM college_data");
    } catch (Exception $delEx) {}

    // Check if synced already in this session
    if (!empty($_SESSION['crm_core_synced_v7'])) {
        return;
    }

    try {
        // Ensure new schema columns exist on `leads` table
        $leadCols = $pdo->query("DESCRIBE leads")->fetchAll(PDO::FETCH_COLUMN);

        $colsToAdd = [
            'region_number' => "ALTER TABLE leads ADD COLUMN region_number VARCHAR(30) DEFAULT 'REG-01'",
            'main_region' => "ALTER TABLE leads ADD COLUMN main_region VARCHAR(100) DEFAULT 'Central Zone'",
            'sub_area' => "ALTER TABLE leads ADD COLUMN sub_area VARCHAR(100) DEFAULT 'Downtown Central'",
            'zone_type' => "ALTER TABLE leads ADD COLUMN zone_type ENUM('Center', 'Sub-area', 'Outer Zone') DEFAULT 'Center'",
            'is_phone_verified' => "ALTER TABLE leads ADD COLUMN is_phone_verified TINYINT(1) DEFAULT 0",
            'phone_verified_at' => "ALTER TABLE leads ADD COLUMN phone_verified_at DATETIME NULL",
            'phone_verified_by' => "ALTER TABLE leads ADD COLUMN phone_verified_by INT(11) NULL",
            'business_name' => "ALTER TABLE leads ADD COLUMN business_name VARCHAR(150) NULL",
            'record_completeness' => "ALTER TABLE leads ADD COLUMN record_completeness INT DEFAULT 85",
            'active_status' => "ALTER TABLE leads ADD COLUMN active_status VARCHAR(50) DEFAULT 'New Lead'",
            'is_long_cycle' => "ALTER TABLE leads ADD COLUMN is_long_cycle TINYINT(1) DEFAULT 0",
        ];

        foreach ($colsToAdd as $col => $sql) {
            if (!in_array($col, $leadCols)) {
                try {
                    $pdo->exec($sql);
                } catch (Exception $e) {}
            }
        }

        // Create call_logs table if not exists
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS call_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                lead_id INT NOT NULL,
                caller_id INT NULL,
                call_direction ENUM('Inbound', 'Outbound') DEFAULT 'Outbound',
                call_status ENUM('Connected', 'Busy', 'No Answer', 'Left Voicemail', 'Switched Off') DEFAULT 'Connected',
                duration_seconds INT DEFAULT 0,
                phone_dialed VARCHAR(30) NOT NULL,
                notes TEXT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX (lead_id),
                INDEX (caller_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // 2. Roles Definition
        $roles = [
            1 => ['super_admin', 'Super Admin', 'Full administrative authority and multi-role switching across all CRM operations.'],
            2 => ['team_leader', 'Team Leader', 'Regional supervisor with lead review, pipeline density monitoring, and assignment oversight.'],
            3 => ['counselor', 'Counselor / Advisor', 'Client consultant managing qualified leads, scheduled consultations, and conversions.'],
            4 => ['operations', 'Operations / Accessor', 'Lead ingestion, social media leads triage, phone number cross-checking, and counselor routing.']
        ];

        $stmtRole = $pdo->prepare("
            INSERT INTO roles (id, name, label, description)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE name = VALUES(name), label = VALUES(label), description = VALUES(description)
        ");
        foreach ($roles as $rId => $r) {
            $stmtRole->execute([$rId, $r[0], $r[1], $r[2]]);
        }

        // Update role permissions for roles 2 and 4
        $modules = ['leads', 'customers', 'tasks', 'followups', 'web_leads', 'dashboard', 'reports', 'notifications'];
        $stmtPerm = $pdo->prepare("
            INSERT INTO role_permissions (role_id, module, can_view, can_add, can_edit, can_delete, can_assign, can_reassign, can_approve, can_export, can_archive, can_restore)
            VALUES (?, ?, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1)
            ON DUPLICATE KEY UPDATE can_view = 1, can_add = 1, can_edit = 1, can_delete = 1, can_assign = 1, can_reassign = 1, can_approve = 1, can_export = 1
        ");
        foreach ([2, 4] as $rId) {
            foreach ($modules as $m) {
                $stmtPerm->execute([$rId, $m]);
            }
        }

        $defaultPassword = password_hash('123', PASSWORD_BCRYPT);

        // 3. Company Profile
        $pdo->exec("
            UPDATE companies 
            SET name = 'Core CRM - Customer Relationship Management Platform', 
                email = 'contact@crm.internal', 
                phone = '+91 484 288 9000', 
                website = 'https://crm-university-level.vercel.app', 
                address = 'Suite 400, Central Business Hub, Financial District' 
            WHERE id = 1
        ");

        // 4. Staff Personas & Credentials (Demo Password '123' for all)
        // 1. Dr. Arthur Vance -> Super Admin (1)
        // 2. Sarah Jenkins -> Team Leader (2)
        // 3. Michael Chang -> Counselor / Advisor (3)
        // 4. Emily Watson -> Operations / Accessor (4)
        $staffUsers = [
            1 => [1, 1, null, 'Dr. Arthur Vance', 'superadmin', 'superadmin@unicrm.edu', '+91 484 288 9000', 'Super Admin & Operations Director'],
            2 => [1, 2, null, 'Sarah Jenkins', 'admissionmanager', 'admissionmanager@unicrm.edu', '+91 9074 134910', 'Regional Team Leader (Central Zone)'],
            3 => [1, 3, 2, 'Michael Chang', 'academiccounselor', 'academiccounselor@unicrm.edu', '+91 9633 371111', 'Senior Counselor / Advisor'],
            4 => [1, 4, 2, 'Emily Watson', 'admissionadvisor', 'admissionadvisor@unicrm.edu', '+91 8547 597476', 'Operations / Accessor Specialist'],
            5 => [2, 3, 2, 'David Miller', 'davidmiller', 'david.miller@unicrm.edu', '+91 9895 716710', 'Counselor / Advisor (North Zone)'],
            6 => [3, 3, 2, 'Jessica Taylor', 'jessicataylor', 'jessica.taylor@unicrm.edu', '+91 9947 921306', 'Counselor / Advisor (South Zone)']
        ];

        // Check if username column exists
        $userCols = $pdo->query("SHOW COLUMNS FROM users LIKE 'username'")->fetchAll();
        $hasUsernameCol = !empty($userCols);

        if ($hasUsernameCol) {
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
        } else {
            $stmtUser = $pdo->prepare("
                INSERT INTO users (id, branch_id, role_id, manager_id, name, email, password, phone, designation, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active')
                ON DUPLICATE KEY UPDATE 
                    branch_id = VALUES(branch_id), role_id = VALUES(role_id), manager_id = VALUES(manager_id),
                    name = VALUES(name), email = VALUES(email), password = VALUES(password),
                    phone = VALUES(phone), designation = VALUES(designation), status = 'Active'
            ");
            foreach ($staffUsers as $id => $u) {
                $stmtUser->execute([$id, $u[0], $u[1], $u[2], $u[3], $u[5], $defaultPassword, $u[6], $u[7]]);
            }
        }

        // 5. Inject / Update Client Required Lead Records
        // Record 1: Rahul Nair (8787946896) -> lead id 1
        // Record 2: Priya Varma (9633756812) -> lead id 2
        try {
            $stmtUpd1 = $pdo->prepare("
                UPDATE leads 
                SET lead_code = 'LED-2026-0087', full_name = 'Rahul Nair', phone = '8787946896', mobile_number = '+91 87879 46896', phone_normalized = '8787946896',
                    email = 'rahul.nair@nairglobal.com', branch_id = 1, assigned_counselor_id = 3, region_number = 'REG-01', main_region = 'Central Zone',
                    sub_area = 'Downtown Central', zone_type = 'Center', is_phone_verified = 1, phone_verified_at = NOW(), phone_verified_by = 4,
                    business_name = 'Nair Global Enterprises', record_completeness = 95, active_status = 'Prospect Qualified',
                    prospectivity = 'Hot', priority = 'High', source = 'Social Media (Meta Ads)', is_long_cycle = 0,
                    notes = 'Qualified high-prospect enterprise account in central hub. Cross-checked number 8-7-8-7-9-4-6-8-9-6.'
                WHERE id = 1
            ");
            $stmtUpd1->execute();

            $stmtUpd2 = $pdo->prepare("
                UPDATE leads 
                SET lead_code = 'LED-2026-0096', full_name = 'Priya Varma', phone = '9633756812', mobile_number = '+91 96337 56812', phone_normalized = '9633756812',
                    email = 'priya.varma@varmaconsulting.com', branch_id = 1, assigned_counselor_id = 4, region_number = 'REG-02', main_region = 'Central Zone',
                    sub_area = 'High Street Commercial', zone_type = 'Center', is_phone_verified = 0, phone_verified_at = NULL, phone_verified_by = NULL,
                    business_name = 'Varma Consulting Group', record_completeness = 88, active_status = 'New Lead',
                    prospectivity = 'Warm', priority = 'Moderate', source = 'Social Media (Instagram)', is_long_cycle = 0,
                    notes = 'Inbound social media inquiry. Awaiting phone number cross-check verification for 9633756812.'
                WHERE id = 2
            ");
            $stmtUpd2->execute();
        } catch (Exception $eUpdClient) {}

        // Update other existing leads with region numbers and business names
        $regions = [
            3 => ['REG-02', 'Central Zone', 'High Street', 'Center', 'Patel Logistics Ltd', 88, 'Prospect Qualified', 'Hot', 0],
            4 => ['REG-03', 'North Zone', 'Industrial Estate', 'Sub-area', 'Iyer Electronics', 85, 'Follow-up Scheduled', 'Warm', 0],
            5 => ['REG-03', 'North Zone', 'Park Avenue', 'Sub-area', 'Kim Trading Co', 80, 'Contacted', 'Warm', 1],
            6 => ['REG-04', 'South Zone', 'Port Terminal', 'Outer Zone', 'Al-Mansoor Trading', 90, 'Converted', 'Hot', 0],
            7 => ['REG-01', 'Central Zone', 'Commercial Arcade', 'Center', 'Krishnan Agro Foods', 75, 'New Lead', 'Warm', 0],
            8 => ['REG-04', 'South Zone', 'Coastal Road', 'Outer Zone', 'O\'Connor Marine Services', 70, 'New Lead', 'Cold', 1],
            9 => ['REG-02', 'Central Zone', 'Civic Center', 'Center', 'Nair Healthcare Services', 82, 'Follow-up Scheduled', 'Warm', 0],
            10 => ['REG-03', 'North Zone', 'Tech Corridor', 'Sub-area', 'Mendez Engineering Works', 78, 'Contacted', 'Warm', 0],
        ];

        try {
            $stmtUpdLead = $pdo->prepare("
                UPDATE leads 
                SET region_number = ?, main_region = ?, sub_area = ?, zone_type = ?, 
                    business_name = ?, record_completeness = ?, active_status = ?, prospectivity = ?, is_long_cycle = ?
                WHERE id = ?
            ");
            foreach ($regions as $lId => $rInfo) {
                $stmtUpdLead->execute([$rInfo[0], $rInfo[1], $rInfo[2], $rInfo[3], $rInfo[4], $rInfo[5], $rInfo[6], $rInfo[7], $rInfo[8], $lId]);
            }
        } catch (Exception $eReg) {}

        // 6. Seed Call Logs for Centralized Call Data Integration
        try {
            $callCount = (int)$pdo->query("SELECT COUNT(*) FROM call_logs")->fetchColumn();
            if ($callCount === 0) {
                $sampleCalls = [
                    [1, 3, 'Outbound', 'Connected', 245, '8787946896', 'Discussed regional business expansion and scheduled comprehensive consultation. Verified contact number digit-by-digit.'],
                    [1, 4, 'Inbound', 'Connected', 180, '8787946896', 'Inbound inquiry from Rahul Nair regarding priority consultation availability.'],
                    [2, 4, 'Outbound', 'Connected', 120, '9633756812', 'Initial contact on Instagram social lead. Verified owner identity for Priya Varma; follow-up booked.'],
                    [7, 4, 'Outbound', 'No Answer', 0, '9961044556', 'Attempted initial follow-up; phone rang with no answer. Added to pending backlog queue.'],
                    [5, 4, 'Outbound', 'Connected', 310, '9061099001', 'Long-cycle review call regarding updated project proposal.'],
                    [4, 3, 'Outbound', 'Connected', 420, '9745077889', 'Consultation call with Ananya Iyer. Account escalated and qualified for final agreement.']
                ];

                $stmtCall = $pdo->prepare("
                    INSERT INTO call_logs (lead_id, caller_id, call_direction, call_status, duration_seconds, phone_dialed, notes, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL 1 HOUR))
                ");
                foreach ($sampleCalls as $sc) {
                    $stmtCall->execute($sc);
                }
            }
        } catch (Exception $eCalls) {}

        // 7. Seed Social Media Leads (Meta Ads, Instagram, Google Ads, TikTok Ads)
        $pdo->exec("
            INSERT INTO web_leads (id, student_name, full_name, mobile_number, phone, phone_normalized, email, city_state, target_course, target_university, target_country, nearest_branch_id, branch_id, source_campaign, source, status)
            VALUES 
            (1, 'Tariq Aziz', 'Tariq Aziz', '+91 95448 20601', '9544820601', '9544820601', 'tariq.aziz@gmail.com', 'Central Zone, Downtown', 'Enterprise Account', 'Regional Hub', 'India', 1, 1, 'Google Ads - Central Region Expansion', 'Google Ads', 'Pending'),
            (2, 'Chloe Dupont', 'Chloe Dupont', '+91 95448 20602', '9544820602', '9544820602', 'chloe.dupont@gmail.com', 'North Zone, Downtown', 'Business Services', 'North Branch', 'India', 2, 2, 'Meta Ads - Regional Business Retainer', 'Meta Ads', 'Pending'),
            (3, 'Pranav Nair', 'Pranav Nair', '+91 91894 39201', '9189439201', '9189439201', 'pranav.nair@gmail.com', 'Central Zone, High Street', 'Consulting Package', 'Central Hub', 'India', 3, 3, 'Instagram Campaign - Growth 2026', 'Instagram', 'Pending')
            ON DUPLICATE KEY UPDATE source_campaign = VALUES(source_campaign), source = VALUES(source)
        ");

        // Mark session as synced
        $_SESSION['crm_core_synced_v6'] = true;
    } catch (Exception $e) {
        error_log('CRM DB Sync Error: ' . $e->getMessage());
    }
}
