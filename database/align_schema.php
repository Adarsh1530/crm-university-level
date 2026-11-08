<?php
/**
 * Schema Harmony & Migration Script
 * Ensures complete compatibility between relational tables and module attributes
 */

require_once __DIR__ . '/../config/database.php';

echo "=== ALIGNING SCHEMA FOR FULL HARMONY ===\n";

function add_col_if_missing($pdo, $table, $column, $definition) {
    try {
        $check = $pdo->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'")->fetch();
        if (!$check) {
            $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
            echo "  [ADD] Added column `{$column}` to `{$table}`\n";
        }
    } catch (Exception $e) {
        echo "  [WARN] {$table}.{$column}: " . $e->getMessage() . "\n";
    }
}

// 1. LEADS TABLE
add_col_if_missing($pdo, 'leads', 'phone', 'VARCHAR(50) NULL AFTER full_name');
add_col_if_missing($pdo, 'leads', 'stage', "VARCHAR(50) DEFAULT 'ENQUIRY' AFTER stage_id");
add_col_if_missing($pdo, 'leads', 'priority', "ENUM('High', 'Moderate', 'Low') DEFAULT 'Moderate' AFTER prospectivity");
add_col_if_missing($pdo, 'leads', 'source', "VARCHAR(100) DEFAULT 'Direct Inquiry' AFTER lead_source");
add_col_if_missing($pdo, 'leads', 'preferred_country', "VARCHAR(100) NULL AFTER target_country");
add_col_if_missing($pdo, 'leads', 'preferred_course', "VARCHAR(200) NULL AFTER target_course");
add_col_if_missing($pdo, 'leads', 'intake_year', "INT NULL DEFAULT 2026 AFTER target_intake_text");
add_col_if_missing($pdo, 'leads', 'intake_term', "VARCHAR(50) DEFAULT 'September' AFTER intake_year");
add_col_if_missing($pdo, 'leads', 'college_name', "VARCHAR(200) NULL AFTER passing_college");
add_col_if_missing($pdo, 'leads', 'year_of_passing', "INT NULL AFTER passout_year");
add_col_if_missing($pdo, 'leads', 'percentage', "DECIMAL(5,2) NULL AFTER year_of_passing");
add_col_if_missing($pdo, 'leads', 'backlogs_count', "INT DEFAULT 0 AFTER percentage");
add_col_if_missing($pdo, 'leads', 'english_test', "VARCHAR(50) DEFAULT 'None' AFTER has_toefl");
add_col_if_missing($pdo, 'leads', 'english_score', "VARCHAR(50) NULL AFTER english_test");
add_col_if_missing($pdo, 'leads', 'score_listening', "DECIMAL(4,1) NULL AFTER english_score");
add_col_if_missing($pdo, 'leads', 'score_reading', "DECIMAL(4,1) NULL AFTER score_listening");
add_col_if_missing($pdo, 'leads', 'score_writing', "DECIMAL(4,1) NULL AFTER score_reading");
add_col_if_missing($pdo, 'leads', 'score_speaking', "DECIMAL(4,1) NULL AFTER score_writing");
add_col_if_missing($pdo, 'leads', 'work_experience_years', "DECIMAL(4,1) DEFAULT 0.0 AFTER score_speaking");
add_col_if_missing($pdo, 'leads', 'invoiced_total', "DECIMAL(12,2) DEFAULT 0.00 AFTER converted_to_customer_at");
add_col_if_missing($pdo, 'leads', 'paid_total', "DECIMAL(12,2) DEFAULT 0.00 AFTER invoiced_total");
add_col_if_missing($pdo, 'leads', 'balance_total', "DECIMAL(12,2) DEFAULT 0.00 AFTER paid_total");
add_col_if_missing($pdo, 'leads', 'followup_count', "INT DEFAULT 0 AFTER next_followup_date");
add_col_if_missing($pdo, 'leads', 'last_followup_date', "DATE NULL AFTER followup_count");
add_col_if_missing($pdo, 'leads', 'assigned_at', "DATETIME DEFAULT CURRENT_TIMESTAMP AFTER last_followup_date");
add_col_if_missing($pdo, 'leads', 'notes', "TEXT NULL AFTER work_experience_years");
add_col_if_missing($pdo, 'leads', 'dob', "DATE NULL AFTER alternate_phone");
add_col_if_missing($pdo, 'leads', 'gender', "ENUM('Male', 'Female', 'Other') DEFAULT 'Other' AFTER dob");
add_col_if_missing($pdo, 'leads', 'country', "VARCHAR(100) DEFAULT 'India' AFTER state");

// Sync leads columns
$pdo->exec("
    UPDATE leads l
    JOIN admission_stages s ON l.stage_id = s.id
    SET l.phone = COALESCE(l.phone, l.phone_normalized, l.mobile_number),
        l.stage = COALESCE(l.stage, s.code),
        l.priority = COALESCE(l.priority, l.prospectivity, 'Moderate'),
        l.source = COALESCE(l.source, l.lead_source, 'Direct Inquiry'),
        l.preferred_country = COALESCE(l.preferred_country, l.target_country, 'United Kingdom'),
        l.preferred_course = COALESCE(l.preferred_course, l.target_course, 'General'),
        l.college_name = COALESCE(l.college_name, l.passing_college),
        l.year_of_passing = COALESCE(l.year_of_passing, l.passout_year),
        l.last_followup_date = COALESCE(l.last_followup_date, DATE(l.last_followup_at)),
        l.assigned_at = COALESCE(l.assigned_at, l.created_at)
");

// Sync invoice balances to leads
$pdo->exec("
    UPDATE leads l
    SET l.invoiced_total = (SELECT COALESCE(SUM(grand_total), 0) FROM invoices WHERE lead_id = l.id),
        l.paid_total = (SELECT COALESCE(SUM(paid_total), 0) FROM invoices WHERE lead_id = l.id),
        l.balance_total = (SELECT COALESCE(SUM(balance_total), 0) FROM invoices WHERE lead_id = l.id)
");

// Sync followup counts
$pdo->exec("
    UPDATE leads l
    SET l.followup_count = (SELECT COUNT(*) FROM followups WHERE lead_id = l.id)
");

// 2. FOLLOWUPS TABLE
add_col_if_missing($pdo, 'followups', 'scheduled_date', "DATE NULL AFTER followup_date");
add_col_if_missing($pdo, 'followups', 'scheduled_time', "TIME DEFAULT '10:00:00' AFTER scheduled_date");
add_col_if_missing($pdo, 'followups', 'status', "ENUM('Pending', 'Completed', 'Overdue', 'Cancelled') DEFAULT 'Completed' AFTER scheduled_time");
add_col_if_missing($pdo, 'followups', 'completed_at', "DATETIME NULL AFTER status");
add_col_if_missing($pdo, 'followups', 'completed_by', "INT NULL AFTER completed_at");
add_col_if_missing($pdo, 'followups', 'interaction_type', "VARCHAR(50) DEFAULT 'Phone Call' AFTER interaction_title_id");
add_col_if_missing($pdo, 'followups', 'duration_days_allowed', "INT DEFAULT 7 AFTER interaction_type");

// Sync followups columns
$pdo->exec("
    UPDATE followups
    SET scheduled_date = COALESCE(scheduled_date, followup_date),
        scheduled_time = COALESCE(scheduled_time, followup_time),
        completed_at = COALESCE(completed_at, created_at),
        completed_by = COALESCE(completed_by, counselor_id)
");

// Ensure we have pending followups seeded for the 4 queues
// A. Scheduled for today
$pdo->exec("
    INSERT INTO followups (lead_id, followup_date, followup_time, scheduled_date, scheduled_time, interaction_title_id, notes, stage_id, next_followup_date, counselor_id, status)
    SELECT l.id, CURDATE(), '11:00:00', CURDATE(), '11:00:00', 2, 'Scheduled follow-up for today', l.stage_id, DATE_ADD(CURDATE(), INTERVAL 3 DAY), l.assigned_counselor_id, 'Pending'
    FROM leads l
    WHERE l.id IN (1, 7, 10)
    AND NOT EXISTS (SELECT 1 FROM followups WHERE lead_id = l.id AND scheduled_date = CURDATE() AND status = 'Pending')
");

// B. Overdue (scheduled 2 days ago)
$pdo->exec("
    INSERT INTO followups (lead_id, followup_date, followup_time, scheduled_date, scheduled_time, interaction_title_id, notes, stage_id, next_followup_date, counselor_id, status)
    SELECT l.id, DATE_SUB(CURDATE(), INTERVAL 2 DAY), '14:00:00', DATE_SUB(CURDATE(), INTERVAL 2 DAY), '14:00:00', 1, 'Overdue appointment', l.stage_id, CURDATE(), l.assigned_counselor_id, 'Pending'
    FROM leads l
    WHERE l.id IN (4, 8)
    AND NOT EXISTS (SELECT 1 FROM followups WHERE lead_id = l.id AND scheduled_date < CURDATE() AND status = 'Pending')
");

// 3. INVOICES TABLE
add_col_if_missing($pdo, 'invoices', 'issue_date', "DATE NULL AFTER invoice_date");
add_col_if_missing($pdo, 'invoices', 'tax_percentage', "DECIMAL(5,2) DEFAULT 18.00 AFTER tax_total");
add_col_if_missing($pdo, 'invoices', 'discount_amount', "DECIMAL(12,2) DEFAULT 0.00 AFTER discount_total");
add_col_if_missing($pdo, 'invoices', 'tax_amount', "DECIMAL(12,2) DEFAULT 0.00 AFTER tax_percentage");
add_col_if_missing($pdo, 'invoices', 'paid_amount', "DECIMAL(12,2) DEFAULT 0.00 AFTER paid_total");
add_col_if_missing($pdo, 'invoices', 'balance_due', "DECIMAL(12,2) DEFAULT 0.00 AFTER balance_total");
add_col_if_missing($pdo, 'invoices', 'payment_status', "ENUM('Unpaid', 'Partially Paid', 'Paid') DEFAULT 'Unpaid' AFTER balance_due");
add_col_if_missing($pdo, 'invoices', 'payment_terms', "VARCHAR(255) DEFAULT 'Due within 15 days of issue.' AFTER notes");
add_col_if_missing($pdo, 'invoices', 'bank_details', "TEXT NULL AFTER payment_terms");

// Sync invoices columns
$pdo->exec("
    UPDATE invoices
    SET issue_date = COALESCE(issue_date, invoice_date),
        discount_amount = COALESCE(discount_amount, discount_total, 0.00),
        tax_amount = COALESCE(tax_amount, tax_total, 0.00),
        paid_amount = COALESCE(paid_amount, paid_total, 0.00),
        balance_due = COALESCE(balance_due, balance_total, grand_total),
        payment_status = CASE 
            WHEN balance_total <= 0 THEN 'Paid'
            WHEN paid_total > 0 THEN 'Partially Paid'
            ELSE 'Unpaid'
        END,
        bank_details = COALESCE(bank_details, 'HDFC Bank - Edroots Education Consultancy Ltd.\nAccount: 50200088991122 | IFSC: HDFC0000240 | Kochi Branch')
");

// 4. INVOICE ITEMS TABLE
add_col_if_missing($pdo, 'invoice_items', 'description', "VARCHAR(255) NULL AFTER service_description");
add_col_if_missing($pdo, 'invoice_items', 'unit_price', "DECIMAL(12,2) DEFAULT 0.00 AFTER unit_rate");
add_col_if_missing($pdo, 'invoice_items', 'line_total', "DECIMAL(12,2) DEFAULT 0.00 AFTER subtotal");
$pdo->exec("
    UPDATE invoice_items
    SET description = COALESCE(description, service_description),
        unit_price = COALESCE(unit_price, unit_rate),
        line_total = COALESCE(line_total, subtotal)
");

// 5. PAYMENTS TABLE
add_col_if_missing($pdo, 'payments', 'receipt_number', "VARCHAR(50) NULL AFTER id");
add_col_if_missing($pdo, 'payments', 'amount', "DECIMAL(12,2) DEFAULT 0.00 AFTER amount_paid");
add_col_if_missing($pdo, 'payments', 'payment_method', "VARCHAR(50) DEFAULT 'Bank Transfer' AFTER payment_mode");
add_col_if_missing($pdo, 'payments', 'recorded_by', "INT NULL AFTER received_by");
$pdo->exec("
    UPDATE payments
    SET receipt_number = COALESCE(receipt_number, CONCAT('RCT-', YEAR(payment_date), '-', LPAD(id, 4, '0'))),
        amount = COALESCE(amount, amount_paid),
        payment_method = COALESCE(payment_method, payment_mode),
        recorded_by = COALESCE(recorded_by, received_by)
");

// 6. COLLEGE DATA TABLE
add_col_if_missing($pdo, 'college_data', 'converted_to_lead', "TINYINT(1) DEFAULT 0 AFTER is_converted");
add_col_if_missing($pdo, 'college_data', 'phone', "VARCHAR(50) NULL AFTER mobile_number");
add_col_if_missing($pdo, 'college_data', 'lead_id', "INT NULL AFTER converted_lead_id");
add_col_if_missing($pdo, 'college_data', 'city', "VARCHAR(100) NULL AFTER location");
add_col_if_missing($pdo, 'college_data', 'graduation_year', "INT NULL AFTER passout_year");
add_col_if_missing($pdo, 'college_data', 'stream', "VARCHAR(100) NULL AFTER degree");
add_col_if_missing($pdo, 'college_data', 'import_id', "INT NULL AFTER import_batch_id");
$pdo->exec("
    UPDATE college_data
    SET converted_to_lead = COALESCE(converted_to_lead, is_converted, 0),
        phone = COALESCE(phone, mobile_number, phone_normalized),
        mobile_number = COALESCE(mobile_number, phone),
        phone_normalized = COALESCE(phone_normalized, phone),
        lead_id = COALESCE(lead_id, converted_lead_id),
        converted_lead_id = COALESCE(converted_lead_id, lead_id),
        city = COALESCE(city, location),
        location = COALESCE(location, city),
        graduation_year = COALESCE(graduation_year, passout_year),
        passout_year = COALESCE(passout_year, graduation_year),
        import_id = COALESCE(import_id, import_batch_id),
        import_batch_id = COALESCE(import_batch_id, import_id)
");

// 7. NOTIFICATIONS TABLE
add_col_if_missing($pdo, 'notifications', 'link', "VARCHAR(255) NULL AFTER message");
$pdo->exec("
    UPDATE notifications
    SET link = COALESCE(link, 'tasks/index.php')
    WHERE link IS NULL
");

// 8. INTAKE CYCLES & INTERACTION TITLES STATUS COLUMN
add_col_if_missing($pdo, 'intake_cycles', 'status', "VARCHAR(20) DEFAULT 'Active' AFTER is_active");
$pdo->exec("UPDATE intake_cycles SET status = IF(is_active = 1, 'Active', 'Inactive')");

add_col_if_missing($pdo, 'interaction_titles', 'status', "VARCHAR(20) DEFAULT 'Active' AFTER is_active");
$pdo->exec("UPDATE interaction_titles SET status = IF(is_active = 1, 'Active', 'Inactive')");

// 9. WEB LEADS TABLE HARMONY
add_col_if_missing($pdo, 'web_leads', 'full_name', "VARCHAR(150) NULL AFTER student_name");
add_col_if_missing($pdo, 'web_leads', 'phone', "VARCHAR(50) NULL AFTER mobile_number");
add_col_if_missing($pdo, 'web_leads', 'source', "VARCHAR(150) NULL AFTER source_campaign");
add_col_if_missing($pdo, 'web_leads', 'branch_id', "INT NULL AFTER nearest_branch_id");
add_col_if_missing($pdo, 'web_leads', 'assigned_lead_id', "INT NULL AFTER converted_lead_id");
add_col_if_missing($pdo, 'web_leads', 'intake', "VARCHAR(100) DEFAULT 'September 2026' AFTER target_university");
add_col_if_missing($pdo, 'web_leads', 'notes', "TEXT NULL AFTER source_campaign");
add_col_if_missing($pdo, 'web_leads', 'assigned_at', "DATETIME NULL AFTER converted_at");

try {
    $pdo->exec("ALTER TABLE `web_leads` MODIFY COLUMN `status` VARCHAR(50) DEFAULT 'Pending'");
} catch (Exception $e) {
    // Ignore if already modified
}

$pdo->exec("
    UPDATE web_leads
    SET full_name = COALESCE(full_name, student_name),
        student_name = COALESCE(student_name, full_name),
        phone = COALESCE(phone, mobile_number, phone_normalized),
        mobile_number = COALESCE(mobile_number, phone),
        phone_normalized = COALESCE(phone_normalized, phone),
        source = COALESCE(source, source_campaign, 'Website Inbound'),
        source_campaign = COALESCE(source_campaign, source),
        branch_id = COALESCE(branch_id, nearest_branch_id, assigned_branch_id, 1),
        nearest_branch_id = COALESCE(nearest_branch_id, branch_id),
        assigned_lead_id = COALESCE(assigned_lead_id, converted_lead_id),
        converted_lead_id = COALESCE(converted_lead_id, assigned_lead_id),
        status = CASE 
            WHEN status IN ('Staged', 'Pending') THEN 'Pending'
            WHEN status IN ('Added_to_Leads', 'Assigned') THEN 'Assigned'
            WHEN status IN ('Spam_Deleted', 'Disqualified') THEN 'Disqualified'
            ELSE 'Pending'
        END
");

// 10. STUDENT DOCUMENTS HARMONY
add_col_if_missing($pdo, 'student_documents', 'title', "VARCHAR(150) NULL AFTER document_name");
add_col_if_missing($pdo, 'student_documents', 'original_filename', "VARCHAR(255) NULL AFTER file_name");
$pdo->exec("
    UPDATE student_documents 
    SET title = COALESCE(title, document_name),
        original_filename = COALESCE(original_filename, file_name)
");

// 11. USERS REPORTING MANAGER HARMONY
add_col_if_missing($pdo, 'users', 'reporting_manager_id', "INT NULL AFTER manager_id");
$pdo->exec("UPDATE users SET reporting_manager_id = COALESCE(reporting_manager_id, manager_id)");

// 12. LEADS ARCHIVE REASON HARMONY
add_col_if_missing($pdo, 'leads', 'archived_reason', "VARCHAR(255) NULL AFTER archive_reason");
$pdo->exec("UPDATE leads SET archived_reason = COALESCE(archived_reason, archive_reason)");

// 13. GENERAL DOCUMENTS DESCRIPTION
add_col_if_missing($pdo, 'general_documents', 'description', "TEXT NULL AFTER country");

// 14. ADMISSION STAGES COLOR HARMONY
add_col_if_missing($pdo, 'admission_stages', 'color', "VARCHAR(20) DEFAULT '#5955D1' AFTER color_code");
$pdo->exec("UPDATE admission_stages SET color = COALESCE(color, color_code, '#5955D1')");

echo "=== SCHEMA ALIGNMENT COMPLETE ===\n";
