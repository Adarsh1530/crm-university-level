<?php
/**
 * EdrootsCRM Database Migration & Seeder
 * Complete System Replacement & Database Setup
 */

require_once __DIR__ . '/../config/database.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "========================================================\n";
echo "STARTING EDROOTS-CRM DATABASE SETUP & SEEDING\n";
echo "========================================================\n";

try {
    // Disable foreign key checks during schema creation
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    // 1. Companies Table
    $pdo->exec("DROP TABLE IF EXISTS companies");
    $pdo->exec("
        CREATE TABLE companies (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(150),
            phone VARCHAR(50),
            website VARCHAR(150),
            address TEXT,
            logo VARCHAR(255),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "  [OK] Created table: companies\n";

    // 2. Branches / Locations Table
    $pdo->exec("DROP TABLE IF EXISTS branches");
    $pdo->exec("
        CREATE TABLE branches (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            code VARCHAR(20) NOT NULL UNIQUE,
            city VARCHAR(100) NOT NULL,
            state VARCHAR(100) NOT NULL DEFAULT 'Kerala',
            country VARCHAR(100) NOT NULL DEFAULT 'India',
            phone VARCHAR(50),
            email VARCHAR(150),
            address TEXT,
            manager_id INT NULL,
            status ENUM('Active', 'Inactive') DEFAULT 'Active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "  [OK] Created table: branches\n";

    // 3. Roles Table
    $pdo->exec("DROP TABLE IF EXISTS roles");
    $pdo->exec("
        CREATE TABLE roles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL UNIQUE,
            label VARCHAR(100) NOT NULL,
            description TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "  [OK] Created table: roles\n";

    // 4. Users Table (Hierarchical: Company -> Branch -> Branch Manager -> Counselors)
    $pdo->exec("DROP TABLE IF EXISTS users");
    $pdo->exec("
        CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            branch_id INT NULL,
            role_id INT NOT NULL,
            manager_id INT NULL,
            name VARCHAR(150) NOT NULL,
            email VARCHAR(150) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            phone VARCHAR(50),
            designation VARCHAR(100),
            avatar VARCHAR(255),
            status ENUM('Active', 'Inactive') DEFAULT 'Active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_branch (branch_id),
            INDEX idx_role (role_id),
            INDEX idx_manager (manager_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "  [OK] Created table: users\n";

    // 5. Role Permissions Table
    $pdo->exec("DROP TABLE IF EXISTS role_permissions");
    $pdo->exec("
        CREATE TABLE role_permissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            role_id INT NOT NULL,
            module VARCHAR(50) NOT NULL,
            can_view TINYINT(1) DEFAULT 0,
            can_add TINYINT(1) DEFAULT 0,
            can_edit TINYINT(1) DEFAULT 0,
            can_delete TINYINT(1) DEFAULT 0,
            can_assign TINYINT(1) DEFAULT 0,
            can_reassign TINYINT(1) DEFAULT 0,
            can_approve TINYINT(1) DEFAULT 0,
            can_export TINYINT(1) DEFAULT 0,
            can_archive TINYINT(1) DEFAULT 0,
            can_restore TINYINT(1) DEFAULT 0,
            UNIQUE KEY uq_role_module (role_id, module)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "  [OK] Created table: role_permissions\n";

    // 6. Interaction Titles (with Administrator-Configured Max Duration constraint)
    $pdo->exec("DROP TABLE IF EXISTS interaction_titles");
    $pdo->exec("
        CREATE TABLE interaction_titles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(100) NOT NULL UNIQUE,
            max_duration_days INT NOT NULL DEFAULT 7,
            description VARCHAR(255),
            is_active TINYINT(1) DEFAULT 1,
            sort_order INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "  [OK] Created table: interaction_titles\n";

    // 7. Intake Cycles Table
    $pdo->exec("DROP TABLE IF EXISTS intake_cycles");
    $pdo->exec("
        CREATE TABLE intake_cycles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            year INT NOT NULL,
            term VARCHAR(50) NOT NULL,
            is_active TINYINT(1) DEFAULT 1,
            sort_order INT DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "  [OK] Created table: intake_cycles\n";

    // 8. Admission Stages (Exactly Six Standard Stages)
    $pdo->exec("DROP TABLE IF EXISTS admission_stages");
    $pdo->exec("
        CREATE TABLE admission_stages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            step_number INT NOT NULL UNIQUE,
            name VARCHAR(100) NOT NULL UNIQUE,
            code VARCHAR(50) NOT NULL UNIQUE,
            description TEXT,
            color_code VARCHAR(20) DEFAULT '#5955D1'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "  [OK] Created table: admission_stages\n";

    // 9. College Data Imports (Track bulk Excel/CSV batches)
    $pdo->exec("DROP TABLE IF EXISTS college_data_imports");
    $pdo->exec("
        CREATE TABLE college_data_imports (
            id INT AUTO_INCREMENT PRIMARY KEY,
            filename VARCHAR(255) NOT NULL,
            total_rows INT DEFAULT 0,
            imported_rows INT DEFAULT 0,
            duplicate_rows INT DEFAULT 0,
            failed_rows INT DEFAULT 0,
            imported_by INT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "  [OK] Created table: college_data_imports\n";

    // 10. Raw College Data Pool
    $pdo->exec("DROP TABLE IF EXISTS college_data");
    $pdo->exec("
        CREATE TABLE college_data (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_name VARCHAR(150) NOT NULL,
            mobile_number VARCHAR(50) NOT NULL,
            phone_normalized VARCHAR(20) NOT NULL,
            email VARCHAR(150),
            college_name VARCHAR(200) NOT NULL,
            degree VARCHAR(100),
            passout_year INT,
            location VARCHAR(100),
            comments TEXT,
            data_source VARCHAR(100) DEFAULT 'Campus Seminar',
            import_batch_id INT NULL,
            branch_id INT NULL,
            is_converted ENUM('NO', 'YES') DEFAULT 'NO',
            converted_lead_id INT NULL,
            converted_by INT NULL,
            converted_at DATETIME NULL,
            is_archived TINYINT(1) DEFAULT 0,
            archived_at DATETIME NULL,
            archived_by INT NULL,
            archive_reason VARCHAR(255),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_phone (phone_normalized),
            INDEX idx_converted (is_converted),
            INDEX idx_archived (is_archived),
            INDEX idx_branch (branch_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "  [OK] Created table: college_data\n";

    // 11. Digital Lead Staging Queue (Website, Facebook, Google Ads)
    $pdo->exec("DROP TABLE IF EXISTS web_leads");
    $pdo->exec("
        CREATE TABLE web_leads (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_name VARCHAR(150) NOT NULL,
            mobile_number VARCHAR(50) NOT NULL,
            phone_normalized VARCHAR(20) NOT NULL,
            email VARCHAR(150),
            city_state VARCHAR(150),
            target_course VARCHAR(150),
            target_university VARCHAR(150),
            target_country VARCHAR(100),
            nearest_branch_id INT NULL,
            source_campaign VARCHAR(150),
            status ENUM('Staged', 'Added_to_Leads', 'Spam_Deleted') DEFAULT 'Staged',
            assigned_counselor_id INT NULL,
            assigned_branch_id INT NULL,
            converted_lead_id INT NULL,
            converted_by INT NULL,
            converted_at DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_phone (phone_normalized),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "  [OK] Created table: web_leads\n";

    // 12. Active Leads Table (Comprehensive Profile Architecture)
    $pdo->exec("DROP TABLE IF EXISTS leads");
    $pdo->exec("
        CREATE TABLE leads (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lead_code VARCHAR(30) NOT NULL UNIQUE,
            full_name VARCHAR(150) NOT NULL,
            mobile_number VARCHAR(50) NOT NULL,
            phone_normalized VARCHAR(20) NOT NULL,
            alternate_phone VARCHAR(50),
            email VARCHAR(150),
            permanent_address TEXT,
            city VARCHAR(100),
            state VARCHAR(100),
            branch_id INT NOT NULL,
            assigned_counselor_id INT NULL,
            
            -- Academic Background
            highest_qualification VARCHAR(100),
            passing_college VARCHAR(200),
            passout_year INT,
            
            -- Eligibility Information
            has_passport ENUM('Yes', 'No', 'Applied') DEFAULT 'No',
            passport_number VARCHAR(50),
            passport_expiry DATE NULL,
            pan_number VARCHAR(30),
            has_ielts ENUM('Yes', 'No', 'Awaiting Result', 'Not Attempted') DEFAULT 'No',
            has_pte ENUM('Yes', 'No', 'Awaiting Result', 'Not Attempted') DEFAULT 'No',
            has_toefl ENUM('Yes', 'No', 'Awaiting Result', 'Not Attempted') DEFAULT 'No',
            ielts_overall DECIMAL(3,1) NULL,
            ielts_listening DECIMAL(3,1) NULL,
            ielts_reading DECIMAL(3,1) NULL,
            ielts_writing DECIMAL(3,1) NULL,
            ielts_speaking DECIMAL(3,1) NULL,
            pte_overall INT NULL,
            pte_listening INT NULL,
            pte_reading INT NULL,
            pte_writing INT NULL,
            pte_speaking INT NULL,
            
            -- Target Preferences
            target_country VARCHAR(100),
            target_university VARCHAR(200),
            target_course VARCHAR(200),
            target_intake_id INT NULL,
            target_intake_text VARCHAR(100),
            
            -- Lead Categorization
            prospectivity ENUM('High', 'Moderate', 'Low') DEFAULT 'Moderate',
            enquiry_type ENUM('WALK IN', 'TELE', 'TELE-WALK IN') DEFAULT 'TELE',
            lead_source VARCHAR(100) DEFAULT 'Direct Inquiry',
            
            -- Six Standard Admission Pipeline Stages
            stage_id INT NOT NULL DEFAULT 1,
            secondary_status ENUM('Active', 'On Hold', 'Rejected', 'Lost') DEFAULT 'Active',
            
            -- Formal Customer Classification (triggered upon service invoice generation)
            is_customer TINYINT(1) DEFAULT 0,
            customer_code VARCHAR(30) NULL,
            converted_to_customer_at DATETIME NULL,
            
            -- Linkages
            college_data_id INT NULL,
            web_lead_id INT NULL,
            
            -- Follow-up tracking
            last_followup_at DATETIME NULL,
            next_followup_date DATE NULL,
            first_followup_logged TINYINT(1) DEFAULT 0,
            
            -- Archive
            is_archived TINYINT(1) DEFAULT 0,
            archived_at DATETIME NULL,
            archived_by INT NULL,
            archive_reason VARCHAR(255),
            
            -- Audit
            created_by INT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX idx_phone (phone_normalized),
            INDEX idx_branch (branch_id),
            INDEX idx_counselor (assigned_counselor_id),
            INDEX idx_stage (stage_id),
            INDEX idx_customer (is_customer),
            INDEX idx_archived (is_archived),
            INDEX idx_next_fu (next_followup_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "  [OK] Created table: leads\n";

    // 13. Lead Stage History (Immutable progression audit)
    $pdo->exec("DROP TABLE IF EXISTS lead_stage_history");
    $pdo->exec("
        CREATE TABLE lead_stage_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lead_id INT NOT NULL,
            previous_stage_id INT NULL,
            stage_id INT NOT NULL,
            secondary_status VARCHAR(50),
            changed_by INT NOT NULL,
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_lead (lead_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "  [OK] Created table: lead_stage_history\n";

    // 14. Follow-up Interactions Table
    $pdo->exec("DROP TABLE IF EXISTS followups");
    $pdo->exec("
        CREATE TABLE followups (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lead_id INT NOT NULL,
            followup_date DATE NOT NULL,
            followup_time TIME NOT NULL,
            interaction_title_id INT NOT NULL,
            notes TEXT NOT NULL,
            document_attachment VARCHAR(255),
            stage_id INT NOT NULL,
            target_intake VARCHAR(100),
            next_followup_date DATE NOT NULL,
            counselor_id INT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_lead (lead_id),
            INDEX idx_counselor (counselor_id),
            INDEX idx_fu_date (followup_date),
            INDEX idx_next_date (next_followup_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "  [OK] Created table: followups\n";

    // 15. Invoices Table (Generates Customer classification)
    $pdo->exec("DROP TABLE IF EXISTS invoices");
    $pdo->exec("
        CREATE TABLE invoices (
            id INT AUTO_INCREMENT PRIMARY KEY,
            invoice_number VARCHAR(50) NOT NULL UNIQUE,
            lead_id INT NOT NULL,
            branch_id INT NOT NULL,
            invoice_date DATE NOT NULL,
            due_date DATE NOT NULL,
            billing_address TEXT,
            subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            discount_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            tax_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            grand_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            paid_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            balance_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            status ENUM('Draft', 'Active', 'Paid', 'Cancelled') DEFAULT 'Active',
            notes TEXT,
            created_by INT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_lead (lead_id),
            INDEX idx_branch (branch_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "  [OK] Created table: invoices\n";

    // 16. Invoice Line Items
    $pdo->exec("DROP TABLE IF EXISTS invoice_items");
    $pdo->exec("
        CREATE TABLE invoice_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            invoice_id INT NOT NULL,
            service_description VARCHAR(255) NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            unit_rate DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            tax_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00,
            tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            INDEX idx_invoice (invoice_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "  [OK] Created table: invoice_items\n";

    // 17. Invoice Payments Table (Split / Installment Payments)
    $pdo->exec("DROP TABLE IF EXISTS payments");
    $pdo->exec("
        CREATE TABLE payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            invoice_id INT NOT NULL,
            lead_id INT NOT NULL,
            branch_id INT NOT NULL,
            payment_date DATE NOT NULL,
            amount_paid DECIMAL(12,2) NOT NULL,
            payment_mode ENUM('Cash', 'Bank Transfer', 'Online / UPI') NOT NULL,
            transaction_reference VARCHAR(100),
            notes TEXT,
            received_by INT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_invoice (invoice_id),
            INDEX idx_lead (lead_id),
            INDEX idx_branch (branch_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "  [OK] Created table: payments\n";

    // 18. General Documents Repository (Company-wide guidelines, prospectuses)
    $pdo->exec("DROP TABLE IF EXISTS general_documents");
    $pdo->exec("
        CREATE TABLE general_documents (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(200) NOT NULL,
            category VARCHAR(100) NOT NULL,
            country VARCHAR(100),
            file_path VARCHAR(255) NOT NULL,
            file_name VARCHAR(255) NOT NULL,
            file_size INT DEFAULT 0,
            uploaded_by INT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "  [OK] Created table: general_documents\n";

    // 19. Student Academic Dossiers (Per-student categorized attachments)
    $pdo->exec("DROP TABLE IF EXISTS student_documents");
    $pdo->exec("
        CREATE TABLE student_documents (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lead_id INT NOT NULL,
            category ENUM('Identity', 'Academic', 'English Proficiency', 'Financial & Visa', 'Other') NOT NULL,
            document_name VARCHAR(150) NOT NULL,
            file_path VARCHAR(255) NOT NULL,
            file_name VARCHAR(255) NOT NULL,
            file_size INT DEFAULT 0,
            uploaded_by INT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_lead (lead_id),
            INDEX idx_cat (category)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "  [OK] Created table: student_documents\n";

    // 20. Event-Driven Notifications (Attended / Not Attended states)
    $pdo->exec("DROP TABLE IF EXISTS notifications");
    $pdo->exec("
        CREATE TABLE notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            branch_id INT NULL,
            title VARCHAR(200) NOT NULL,
            message TEXT NOT NULL,
            type VARCHAR(50) NOT NULL DEFAULT 'info',
            link VARCHAR(255),
            status ENUM('Not Attended', 'Attended') DEFAULT 'Not Attended',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user (user_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "  [OK] Created table: notifications\n";

    // 21. Immutable Chronological Activity Logs
    $pdo->exec("DROP TABLE IF EXISTS activity_logs");
    $pdo->exec("
        CREATE TABLE activity_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            lead_id INT NULL,
            branch_id INT NULL,
            action VARCHAR(100) NOT NULL,
            details TEXT NOT NULL,
            ip_address VARCHAR(50),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user (user_id),
            INDEX idx_lead (lead_id),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "  [OK] Created table: activity_logs\n";

    // 22. Permanent Deletion Logs (with Typed Justification)
    $pdo->exec("DROP TABLE IF EXISTS deletion_logs");
    $pdo->exec("
        CREATE TABLE deletion_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            record_type VARCHAR(50) NOT NULL,
            record_id INT NOT NULL,
            record_identifier VARCHAR(150),
            justification TEXT NOT NULL,
            ip_address VARCHAR(50),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "  [OK] Created table: deletion_logs\n";

    // Enable foreign keys
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "\n========================================================\n";
    echo "ALL 22 SYSTEM REPLACEMENT TABLES CREATED CLEANLY!\n";
    echo "========================================================\n";

} catch (Exception $e) {
    die("FATAL DB MIGRATION ERROR: " . $e->getMessage() . "\n");
}
