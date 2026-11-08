-- University-Level Student CRM - Demo Database
-- Compatible with MySQL / MariaDB and phpMyAdmin

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `university_crm_demo` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `university_crm_demo`;

-- --------------------------------------------------------
-- Table structure for roles
-- --------------------------------------------------------
DROP TABLE IF EXISTS `user_permissions`;
DROP TABLE IF EXISTS `role_permissions`;
DROP TABLE IF EXISTS `activity_logs`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `points_transactions`;
DROP TABLE IF EXISTS `benefits`;
DROP TABLE IF EXISTS `tasks`;
DROP TABLE IF EXISTS `student_stage_history`;
DROP TABLE IF EXISTS `followups`;
DROP TABLE IF EXISTS `leads`;
DROP TABLE IF EXISTS `students`;
DROP TABLE IF EXISTS `admission_stages`;
DROP TABLE IF EXISTS `courses`;
DROP TABLE IF EXISTS `universities`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `roles`;

CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL UNIQUE,
  `label` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`id`, `name`, `label`, `description`) VALUES
(1, 'super_admin', 'Super Admin', 'Full administrative system control and configuration'),
(2, 'admin', 'Administrator', 'University admissions administrator with wide privileges'),
(3, 'manager', 'Admissions Manager', 'Manages admissions teams, workflows, and monitors staff'),
(4, 'team_leader', 'Team Leader', 'Supervises counsellors, assigns leads, tracks targets'),
(5, 'counsellor', 'Admissions Counsellor', 'Direct student counselling, follow-ups, and lead conversion');

-- --------------------------------------------------------
-- Table structure for users
-- --------------------------------------------------------
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `avatar` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_users_roles` (`role_id`),
  CONSTRAINT `fk_users_roles` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Passwords are all: admin123
INSERT INTO `users` (`id`, `role_id`, `name`, `email`, `password`, `phone`, `designation`, `status`) VALUES
(1, 1, 'Dr. Arthur Vance', 'superadmin@university.edu', '$2y$10$7LmPzF4w26ggsv38M4EZGukseqQdFIvURNAadlOsaNrv2ui2sID/O', '+1 (555) 019-2831', 'Chief Admissions Officer', 'Active'),
(2, 2, 'Elena Rostova', 'admin@university.edu', '$2y$10$7LmPzF4w26ggsv38M4EZGukseqQdFIvURNAadlOsaNrv2ui2sID/O', '+1 (555) 014-9921', 'Senior CRM Administrator', 'Active'),
(3, 3, 'Marcus Sterling', 'manager@university.edu', '$2y$10$7LmPzF4w26ggsv38M4EZGukseqQdFIvURNAadlOsaNrv2ui2sID/O', '+1 (555) 017-3342', 'Global Admissions Manager', 'Active'),
(4, 4, 'Priya Sharma', 'teamlead@university.edu', '$2y$10$7LmPzF4w26ggsv38M4EZGukseqQdFIvURNAadlOsaNrv2ui2sID/O', '+1 (555) 018-8745', 'Counselling Team Lead', 'Active'),
(5, 5, 'David Miller', 'counsellor1@university.edu', '$2y$10$7LmPzF4w26ggsv38M4EZGukseqQdFIvURNAadlOsaNrv2ui2sID/O', '+1 (555) 012-7789', 'Senior Admissions Counsellor', 'Active'),
(6, 5, 'Sarah Jenkins', 'counsellor2@university.edu', '$2y$10$7LmPzF4w26ggsv38M4EZGukseqQdFIvURNAadlOsaNrv2ui2sID/O', '+1 (555) 016-5541', 'STEM Admissions Counsellor', 'Active'),
(7, 5, 'Alex Chen', 'counsellor3@university.edu', '$2y$10$7LmPzF4w26ggsv38M4EZGukseqQdFIvURNAadlOsaNrv2ui2sID/O', '+1 (555) 013-4412', 'International Student Advisor', 'Active');

-- --------------------------------------------------------
-- Table structure for universities
-- --------------------------------------------------------
CREATE TABLE `universities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `country` varchar(100) NOT NULL,
  `city` varchar(100) DEFAULT NULL,
  `ranking` varchar(50) DEFAULT NULL,
  `website` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `universities` (`id`, `name`, `country`, `city`, `ranking`, `website`) VALUES
(1, 'Oxford Global University', 'United Kingdom', 'Oxford', 'QS World #4', 'oxford.ac.uk'),
(2, 'University of Toronto', 'Canada', 'Toronto', 'QS World #18', 'utoronto.ca'),
(3, 'University of Melbourne', 'Australia', 'Melbourne', 'QS World #27', 'unimelb.edu.au'),
(4, 'National University of Singapore', 'Singapore', 'Singapore', 'QS World #11', 'nus.edu.sg'),
(5, 'Technical University of Munich', 'Germany', 'Munich', 'QS World #37', 'tum.de'),
(6, 'University of California, Berkeley', 'United States', 'Berkeley', 'QS World #10', 'berkeley.edu'),
(7, 'Trinity College Dublin', 'Ireland', 'Dublin', 'QS World #81', 'tcd.ie'),
(8, 'Imperial College London', 'United Kingdom', 'London', 'QS World #6', 'imperial.ac.uk');

-- --------------------------------------------------------
-- Table structure for courses
-- --------------------------------------------------------
CREATE TABLE `courses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `university_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `level` varchar(50) NOT NULL,
  `duration` varchar(50) NOT NULL,
  `annual_fee` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_courses_universities` (`university_id`),
  CONSTRAINT `fk_courses_universities` FOREIGN KEY (`university_id`) REFERENCES `universities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `courses` (`id`, `university_id`, `title`, `level`, `duration`, `annual_fee`) VALUES
(1, 1, 'B.Sc. in Computer Science', 'Undergraduate', '3 Years', '$34,000 / year'),
(2, 1, 'Master of Business Administration (MBA)', 'Postgraduate', '1 Year', '$48,000 / year'),
(3, 2, 'M.Sc. in Data Science & Machine Learning', 'Postgraduate', '2 Years', '$39,500 / year'),
(4, 2, 'B.Eng. in Robotics & Mechatronics', 'Undergraduate', '4 Years', '$36,000 / year'),
(5, 3, 'Master of International Relations', 'Postgraduate', '2 Years', '$31,000 / year'),
(6, 3, 'B.Sc. in Biomedical Science', 'Undergraduate', '3 Years', '$33,000 / year'),
(7, 4, 'M.Sc. in Cybersecurity & Cloud Systems', 'Postgraduate', '1.5 Years', '$35,000 / year'),
(8, 5, 'M.Sc. in Automotive & Aerospace Engineering', 'Postgraduate', '2 Years', '€1,500 / sem'),
(9, 6, 'B.S. in Electrical Engineering & Computer Science', 'Undergraduate', '4 Years', '$44,000 / year'),
(10, 7, 'Master of Public Health (MPH)', 'Postgraduate', '1 Year', '€22,000 / year');

-- --------------------------------------------------------
-- Table structure for admission_stages
-- --------------------------------------------------------
CREATE TABLE `admission_stages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `step_number` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `color_code` varchar(30) DEFAULT 'blue',
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `admission_stages` (`id`, `step_number`, `name`, `color_code`, `description`) VALUES
(1, 1, 'Enquiry Received', 'slate', 'Initial student inquiry registered via form, agent, or event'),
(2, 2, 'Initial Contact', 'blue', 'First direct phone or message contact initiated with applicant'),
(3, 3, 'Counselling', 'indigo', 'One-on-one academic, career, and eligibility counselling completed'),
(4, 4, 'Course Selection', 'purple', 'Course, university, and entry requirements finalized'),
(5, 5, 'Application Started', 'amber', 'Official application form and portal registration in progress'),
(6, 6, 'Documents Submitted', 'teal', 'Transcripts, SOP, LOR, and passport documents received & verified'),
(7, 7, 'Application Processing', 'orange', 'University admissions committee reviewing application file'),
(8, 8, 'Admission Confirmed', 'emerald', 'Official unconditional offer letter issued & acceptance confirmed');

-- --------------------------------------------------------
-- Table structure for students
-- --------------------------------------------------------
CREATE TABLE `students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_code` varchar(50) NOT NULL UNIQUE,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `mobile_number` varchar(30) NOT NULL,
  `whatsapp_number` varchar(30) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT 'Male',
  `country` varchar(100) NOT NULL,
  `state` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `academic_qualification` varchar(150) DEFAULT NULL,
  `university_id` int(11) DEFAULT NULL,
  `course_id` int(11) DEFAULT NULL,
  `interested_university_text` varchar(200) DEFAULT NULL,
  `interested_course_text` varchar(200) DEFAULT NULL,
  `preferred_intake` varchar(50) DEFAULT NULL,
  `enquiry_date` date NOT NULL,
  `lead_source` varchar(100) NOT NULL,
  `assigned_staff_id` int(11) DEFAULT NULL,
  `priority_level` enum('High','Moderate','Low') DEFAULT 'Moderate',
  `stage_id` int(11) NOT NULL DEFAULT 1,
  `admission_status` enum('In Progress','On Hold','Rejected','Admission Lost','Confirmed') DEFAULT 'In Progress',
  `points_balance` int(11) DEFAULT 0,
  `additional_notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_students_users` (`assigned_staff_id`),
  KEY `fk_students_stages` (`stage_id`),
  KEY `fk_students_courses` (`course_id`),
  KEY `fk_students_universities` (`university_id`),
  CONSTRAINT `fk_students_courses` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_students_stages` FOREIGN KEY (`stage_id`) REFERENCES `admission_stages` (`id`),
  CONSTRAINT `fk_students_universities` FOREIGN KEY (`university_id`) REFERENCES `universities` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_students_users` FOREIGN KEY (`assigned_staff_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for leads
-- --------------------------------------------------------
CREATE TABLE `leads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lead_code` varchar(50) NOT NULL UNIQUE,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `mobile_number` varchar(30) NOT NULL,
  `interested_course` varchar(150) DEFAULT NULL,
  `interested_country` varchar(100) DEFAULT NULL,
  `lead_source` varchar(100) DEFAULT NULL,
  `status` enum('New Lead','Contacted','Interested','Not Interested','Follow-up Required','Application Started','Converted','Lost') DEFAULT 'New Lead',
  `priority` enum('High','Moderate','Low') DEFAULT 'Moderate',
  `assigned_staff_id` int(11) DEFAULT NULL,
  `converted_student_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_leads_staff` (`assigned_staff_id`),
  KEY `fk_leads_converted_student` (`converted_student_id`),
  CONSTRAINT `fk_leads_converted_student` FOREIGN KEY (`converted_student_id`) REFERENCES `students` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_leads_staff` FOREIGN KEY (`assigned_staff_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for followups
-- --------------------------------------------------------
CREATE TABLE `followups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL,
  `lead_id` int(11) DEFAULT NULL,
  `assigned_staff_id` int(11) NOT NULL,
  `followup_date` date NOT NULL,
  `followup_time` time NOT NULL,
  `priority` enum('High','Moderate','Low') DEFAULT 'Moderate',
  `communication_type` enum('Phone Call','WhatsApp','Email','Direct Meeting') DEFAULT 'Phone Call',
  `notes` text DEFAULT NULL,
  `student_response` text DEFAULT NULL,
  `next_followup_date` date DEFAULT NULL,
  `status` enum('Pending','Completed','Overdue','Rescheduled') DEFAULT 'Pending',
  `completed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_followups_students` (`student_id`),
  KEY `fk_followups_leads` (`lead_id`),
  KEY `fk_followups_users` (`assigned_staff_id`),
  CONSTRAINT `fk_followups_leads` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_followups_students` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_followups_users` FOREIGN KEY (`assigned_staff_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for student_stage_history
-- --------------------------------------------------------
CREATE TABLE `student_stage_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `stage_id` int(11) NOT NULL,
  `changed_by_user_id` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_ssh_students` (`student_id`),
  KEY `fk_ssh_stages` (`stage_id`),
  KEY `fk_ssh_users` (`changed_by_user_id`),
  CONSTRAINT `fk_ssh_stages` FOREIGN KEY (`stage_id`) REFERENCES `admission_stages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ssh_students` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ssh_users` FOREIGN KEY (`changed_by_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for tasks
-- --------------------------------------------------------
CREATE TABLE `tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `category` enum('Student Follow-up','Document Collection','Application Completion','Admission Verification','General Task') DEFAULT 'General Task',
  `assigned_staff_id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `due_date` date NOT NULL,
  `priority` enum('High','Moderate','Low') DEFAULT 'Moderate',
  `status` enum('Pending','In Progress','Completed','Overdue') DEFAULT 'Pending',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_tasks_staff` (`assigned_staff_id`),
  KEY `fk_tasks_students` (`student_id`),
  CONSTRAINT `fk_tasks_staff` FOREIGN KEY (`assigned_staff_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tasks_students` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for benefits
-- --------------------------------------------------------
CREATE TABLE `benefits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(150) NOT NULL,
  `points_required` int(11) NOT NULL,
  `applicable_for` enum('student','staff','both') DEFAULT 'both',
  `description` text DEFAULT NULL,
  `terms` text DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `redemption_count` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `benefits` (`id`, `title`, `points_required`, `applicable_for`, `description`, `terms`, `status`, `redemption_count`) VALUES
(1, 'Application Fee Waiver ($100 USD)', 500, 'student', 'Direct waiver credited towards international application portal processing fee.', 'Applicable once per applicant for partner universities.', 'Active', 14),
(2, 'Early Bird Scholarship Voucher ($500)', 1000, 'student', 'Tuition grant awarded for applicants completing stage 6 documents prior to priority deadline.', 'Valid for full-time degree programs.', 'Active', 6),
(3, 'Free IELTS / TOEFL Prep Masterclass', 400, 'student', 'Comprehensive 4-week online coaching package including 5 graded mock tests.', 'Login credentials dispatched within 24 hours of redemption.', 'Active', 22),
(4, 'Complimentary Airport Pickup & Welcome Pack', 300, 'student', 'Chauffeured arrival pickup at international destination airport + local SIM kit.', 'Must submit travel itinerary 14 days in advance.', 'Active', 8),
(5, 'Fast-Track Visa Document Audit Service', 750, 'student', 'Priority one-on-one audit by certified international student immigration counselor.', 'Covers financial, academic, and statement reviews.', 'Active', 11),
(6, 'Staff Excellence Incentive ($250 Bonus)', 2500, 'staff', 'Quarterly performance reward credited to staff payroll for exceeding conversion milestones.', 'Approved by Admissions Manager.', 'Active', 4),
(7, 'Staff Professional Development Allowance ($150)', 1500, 'staff', 'Reimbursement grant for education fairs, CRM workshops, and seminars.', 'Proof of attendance receipt required.', 'Active', 3);

-- --------------------------------------------------------
-- Table structure for points_transactions
-- --------------------------------------------------------
CREATE TABLE `points_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `target_type` enum('student','staff') NOT NULL,
  `target_id` int(11) NOT NULL,
  `points` int(11) NOT NULL,
  `type` enum('Earned','Redeemed') NOT NULL,
  `category` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_pt_users` (`created_by`),
  CONSTRAINT `fk_pt_users` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for notifications
-- --------------------------------------------------------
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(50) NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_notifications_users` (`user_id`),
  CONSTRAINT `fk_notifications_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for activity_logs
-- --------------------------------------------------------
CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `student_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT '127.0.0.1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_activity_users` (`user_id`),
  KEY `fk_activity_students` (`student_id`),
  CONSTRAINT `fk_activity_students` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_activity_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for role_permissions
-- --------------------------------------------------------
CREATE TABLE `role_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `module` varchar(50) NOT NULL,
  `can_view` tinyint(1) NOT NULL DEFAULT 0,
  `can_add` tinyint(1) NOT NULL DEFAULT 0,
  `can_edit` tinyint(1) NOT NULL DEFAULT 0,
  `can_delete` tinyint(1) NOT NULL DEFAULT 0,
  `can_assign` tinyint(1) NOT NULL DEFAULT 0,
  `can_approve` tinyint(1) NOT NULL DEFAULT 0,
  `can_export` tinyint(1) NOT NULL DEFAULT 0,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_role_module` (`role_id`, `module`),
  KEY `fk_rp_roles` (`role_id`),
  CONSTRAINT `fk_rp_roles` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for user_permissions (Individual Overrides)
-- --------------------------------------------------------
CREATE TABLE `user_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `module` varchar(50) NOT NULL,
  `can_view` tinyint(1) NOT NULL DEFAULT 0,
  `can_add` tinyint(1) NOT NULL DEFAULT 0,
  `can_edit` tinyint(1) NOT NULL DEFAULT 0,
  `can_delete` tinyint(1) NOT NULL DEFAULT 0,
  `can_assign` tinyint(1) NOT NULL DEFAULT 0,
  `can_approve` tinyint(1) NOT NULL DEFAULT 0,
  `can_export` tinyint(1) NOT NULL DEFAULT 0,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_module` (`user_id`, `module`),
  KEY `fk_up_users` (`user_id`),
  CONSTRAINT `fk_up_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default role permissions (Admin, Manager, Team Leader, Counsellor)
INSERT INTO `role_permissions` (`role_id`, `module`, `can_view`, `can_add`, `can_edit`, `can_delete`, `can_assign`, `can_approve`, `can_export`) VALUES
(2, 'dashboard', 1, 1, 1, 1, 1, 1, 1),
(2, 'students', 1, 1, 1, 1, 1, 1, 1),
(2, 'leads', 1, 1, 1, 1, 1, 1, 1),
(2, 'followups', 1, 1, 1, 1, 1, 1, 1),
(2, 'admissions', 1, 1, 1, 1, 1, 1, 1),
(2, 'staff', 1, 1, 1, 1, 1, 1, 1),
(2, 'staff_monitoring', 1, 1, 1, 1, 1, 1, 1),
(2, 'tasks', 1, 1, 1, 1, 1, 1, 1),
(2, 'points', 1, 1, 1, 1, 1, 1, 1),
(2, 'reports', 1, 1, 1, 1, 1, 1, 1),
(2, 'notifications', 1, 1, 1, 1, 1, 1, 1),
(3, 'dashboard', 1, 0, 0, 0, 0, 0, 1),
(3, 'students', 1, 1, 1, 1, 1, 1, 1),
(3, 'leads', 1, 1, 1, 1, 1, 1, 1),
(3, 'followups', 1, 1, 1, 1, 1, 1, 1),
(3, 'admissions', 1, 1, 1, 0, 1, 1, 1),
(3, 'staff', 1, 0, 0, 0, 0, 0, 0),
(3, 'staff_monitoring', 1, 0, 1, 0, 1, 1, 1),
(3, 'tasks', 1, 1, 1, 1, 1, 1, 1),
(3, 'points', 1, 1, 1, 0, 1, 1, 1),
(3, 'reports', 1, 0, 0, 0, 0, 0, 1),
(3, 'notifications', 1, 0, 1, 1, 0, 0, 0),
(4, 'dashboard', 1, 0, 0, 0, 0, 0, 0),
(4, 'students', 1, 1, 1, 0, 1, 1, 1),
(4, 'leads', 1, 1, 1, 0, 1, 1, 1),
(4, 'followups', 1, 1, 1, 0, 1, 1, 1),
(4, 'admissions', 1, 1, 1, 0, 1, 1, 0),
(4, 'staff', 0, 0, 0, 0, 0, 0, 0),
(4, 'staff_monitoring', 1, 0, 0, 0, 0, 0, 0),
(4, 'tasks', 1, 1, 1, 0, 1, 1, 0),
(4, 'points', 1, 1, 0, 0, 0, 0, 0),
(4, 'reports', 1, 0, 0, 0, 0, 0, 0),
(4, 'notifications', 1, 0, 1, 0, 0, 0, 0),
(5, 'dashboard', 1, 0, 0, 0, 0, 0, 0),
(5, 'students', 1, 1, 1, 0, 0, 0, 0),
(5, 'leads', 1, 1, 1, 0, 0, 0, 0),
(5, 'followups', 1, 1, 1, 0, 0, 0, 0),
(5, 'admissions', 1, 0, 1, 0, 0, 0, 0),
(5, 'staff', 0, 0, 0, 0, 0, 0, 0),
(5, 'staff_monitoring', 0, 0, 0, 0, 0, 0, 0),
(5, 'tasks', 1, 1, 1, 0, 0, 0, 0),
(5, 'points', 1, 0, 0, 0, 0, 0, 0),
(5, 'reports', 0, 0, 0, 0, 0, 0, 0),
(5, 'notifications', 1, 0, 1, 0, 0, 0, 0);


INSERT INTO `students` (`student_code`, `full_name`, `email`, `mobile_number`, `whatsapp_number`, `date_of_birth`, `gender`, `country`, `state`, `city`, `academic_qualification`, `university_id`, `course_id`, `interested_university_text`, `interested_course_text`, `preferred_intake`, `enquiry_date`, `lead_source`, `assigned_staff_id`, `priority_level`, `stage_id`, `admission_status`, `points_balance`, `additional_notes`) VALUES
('STU-2026-001', 'Liam Jones', 'liam.jones1@example.com', '+1 (555) 587-3590', '+1 (555) 587-3590', '2002-02-23', 'Male', 'United Kingdom', 'England', 'London', 'High School Diploma (IB/A-Levels)', 2, 2, NULL, NULL, 'Fall 2026', '2026-09-23', 'Website Enquiry', 4, 'Moderate', 8, 'Confirmed', 1112, 'Prospective student interested in study opportunities for Fall 2026. Contacted via Website Enquiry. Initial documentation reviewed.'),
('STU-2026-002', 'Olivia Garcia', 'olivia.garcia2@example.com', '+1 (555) 465-2656', '+1 (555) 465-2656', '2001-06-06', 'Female', 'Canada', 'Ontario', 'Toronto', 'Bachelor of Science in Computer Science', 3, 3, NULL, NULL, 'Spring 2027', '2026-08-25', 'Education Fair Dubai', 5, 'Low', 8, 'Confirmed', 1089, 'Prospective student interested in study opportunities for Spring 2027. Contacted via Education Fair Dubai. Initial documentation reviewed.'),
('STU-2026-003', 'Noah Miller', 'noah.miller3@example.com', '+1 (555) 488-4131', '+1 (555) 488-4131', '1998-04-27', 'Male', 'Australia', 'Victoria', 'Melbourne', 'Bachelor of Business Administration', 4, 4, NULL, NULL, 'Fall 2027', '2026-10-14', 'Google Ads Campaign', 6, 'High', 8, 'Confirmed', 1227, 'Prospective student interested in study opportunities for Fall 2027. Contacted via Google Ads Campaign. Initial documentation reviewed.'),
('STU-2026-004', 'Emma Davis', 'emma.davis4@example.com', '+1 (555) 605-4664', '+1 (555) 605-4664', '2006-09-22', 'Female', 'United States', 'California', 'San Francisco', 'Higher Secondary Certificate (Grade 12)', 5, 5, NULL, NULL, 'Summer 2026', '2026-10-01', 'Instagram Campaign', 7, 'Moderate', 8, 'Confirmed', 1197, 'Prospective student interested in study opportunities for Summer 2026. Contacted via Instagram Campaign. Initial documentation reviewed.'),
('STU-2026-005', 'Oliver Rodriguez', 'oliver.rodriguez5@example.com', '+1 (555) 344-1662', '+1 (555) 344-1662', '2001-06-18', 'Male', 'India', 'Maharashtra', 'Mumbai', 'Bachelor of Engineering (Mechanical)', 6, 6, NULL, NULL, 'Fall 2026', '2026-09-20', 'Agent Referral', 4, 'Low', 8, 'Confirmed', 1411, 'Prospective student interested in study opportunities for Fall 2026. Contacted via Agent Referral. Initial documentation reviewed.'),
('STU-2026-006', 'Ava Martinez', 'ava.martinez6@example.com', '+1 (555) 429-7549', '+1 (555) 429-7549', '1999-02-07', 'Female', 'Singapore', 'Central', 'Singapore', 'Associate Degree in Information Technology', 7, 7, NULL, NULL, 'Spring 2027', '2026-07-13', 'Direct Walk-in', 5, 'High', 8, 'Confirmed', 1440, 'Prospective student interested in study opportunities for Spring 2027. Contacted via Direct Walk-in. Initial documentation reviewed.'),
('STU-2026-007', 'Elijah Hernandez', 'elijah.hernandez7@example.com', '+1 (555) 438-8047', '+1 (555) 438-8047', '2001-01-11', 'Male', 'Germany', 'Bavaria', 'Munich', 'Bachelor of Arts in Economics', 8, 8, NULL, NULL, 'Fall 2027', '2026-08-22', 'LinkedIn Higher Ed', 6, 'Moderate', 8, 'Confirmed', 1032, 'Prospective student interested in study opportunities for Fall 2027. Contacted via LinkedIn Higher Ed. Initial documentation reviewed.'),
('STU-2026-008', 'Charlotte Lopez', 'charlotte.lopez8@example.com', '+1 (555) 374-2903', '+1 (555) 374-2903', '2005-07-09', 'Female', 'Ireland', 'Leinster', 'Dublin', 'B.Sc. in Biotechnology', 1, 9, NULL, NULL, 'Summer 2026', '2026-10-03', 'University Webinar', 7, 'Low', 8, 'Confirmed', 1167, 'Prospective student interested in study opportunities for Summer 2026. Contacted via University Webinar. Initial documentation reviewed.'),
('STU-2026-009', 'William Gonzalez', 'william.gonzalez9@example.com', '+1 (555) 762-8189', '+1 (555) 762-8189', '1999-12-27', 'Male', 'United Arab Emirates', 'Dubai', 'Dubai', 'High School Diploma (IB/A-Levels)', 2, 10, NULL, NULL, 'Fall 2026', '2026-07-14', 'Website Enquiry', 4, 'High', 7, 'In Progress', 809, 'Prospective student interested in study opportunities for Fall 2026. Contacted via Website Enquiry. Initial documentation reviewed.'),
('STU-2026-010', 'Sophia Wilson', 'sophia.wilson10@example.com', '+1 (555) 847-4918', '+1 (555) 847-4918', '2003-03-04', 'Female', 'Nigeria', 'Lagos', 'Lagos', 'Bachelor of Science in Computer Science', 3, 1, NULL, NULL, 'Spring 2027', '2026-08-27', 'Education Fair Dubai', 5, 'Moderate', 7, 'In Progress', 853, 'Prospective student interested in study opportunities for Spring 2027. Contacted via Education Fair Dubai. Initial documentation reviewed.'),
('STU-2026-011', 'James Anderson', 'james.anderson11@example.com', '+1 (555) 799-7732', '+1 (555) 799-7732', '2005-01-12', 'Male', 'Malaysia', 'Selangor', 'Kuala Lumpur', 'Bachelor of Business Administration', 4, 2, NULL, NULL, 'Fall 2027', '2026-10-28', 'Google Ads Campaign', 6, 'Low', 7, 'In Progress', 514, 'Prospective student interested in study opportunities for Fall 2027. Contacted via Google Ads Campaign. Initial documentation reviewed.'),
('STU-2026-012', 'Amelia Thomas', 'amelia.thomas12@example.com', '+1 (555) 387-1056', '+1 (555) 387-1056', '2004-12-17', 'Female', 'France', 'Ile-de-France', 'Paris', 'Higher Secondary Certificate (Grade 12)', 5, 3, NULL, NULL, 'Summer 2026', '2026-07-06', 'Instagram Campaign', 7, 'High', 7, 'In Progress', 525, 'Prospective student interested in study opportunities for Summer 2026. Contacted via Instagram Campaign. Initial documentation reviewed.'),
('STU-2026-013', 'Benjamin Taylor', 'benjamin.taylor13@example.com', '+1 (555) 886-9636', '+1 (555) 886-9636', '2003-01-27', 'Male', 'Brazil', 'Sao Paulo', 'Sao Paulo', 'Bachelor of Engineering (Mechanical)', 6, 4, NULL, NULL, 'Fall 2026', '2026-07-21', 'Agent Referral', 4, 'Moderate', 7, 'In Progress', 778, 'Prospective student interested in study opportunities for Fall 2026. Contacted via Agent Referral. Initial documentation reviewed.'),
('STU-2026-014', 'Isabella Moore', 'isabella.moore14@example.com', '+1 (555) 898-3457', '+1 (555) 898-3457', '2004-02-02', 'Female', 'Japan', 'Kanto', 'Tokyo', 'Associate Degree in Information Technology', 7, 5, NULL, NULL, 'Spring 2027', '2026-10-09', 'Direct Walk-in', 5, 'Low', 7, 'In Progress', 682, 'Prospective student interested in study opportunities for Spring 2027. Contacted via Direct Walk-in. Initial documentation reviewed.'),
('STU-2026-015', 'Lucas Jackson', 'lucas.jackson15@example.com', '+1 (555) 949-6153', '+1 (555) 949-6153', '2005-12-18', 'Male', 'South Africa', 'Gauteng', 'Johannesburg', 'Bachelor of Arts in Economics', 8, 6, NULL, NULL, 'Fall 2027', '2026-08-22', 'LinkedIn Higher Ed', 6, 'High', 7, 'In Progress', 843, 'Prospective student interested in study opportunities for Fall 2027. Contacted via LinkedIn Higher Ed. Initial documentation reviewed.'),
('STU-2026-016', 'Mia Martin', 'mia.martin16@example.com', '+1 (555) 295-2407', '+1 (555) 295-2407', '1998-08-11', 'Female', 'United Kingdom', 'England', 'London', 'B.Sc. in Biotechnology', 1, 7, NULL, NULL, 'Summer 2026', '2026-08-04', 'University Webinar', 7, 'Moderate', 7, 'In Progress', 614, 'Prospective student interested in study opportunities for Summer 2026. Contacted via University Webinar. Initial documentation reviewed.'),
('STU-2026-017', 'Henry Lee', 'henry.lee17@example.com', '+1 (555) 571-6620', '+1 (555) 571-6620', '2006-05-18', 'Male', 'Canada', 'Ontario', 'Toronto', 'High School Diploma (IB/A-Levels)', 2, 8, NULL, NULL, 'Fall 2026', '2026-08-05', 'Website Enquiry', 4, 'Low', 6, 'In Progress', 613, 'Prospective student interested in study opportunities for Fall 2026. Contacted via Website Enquiry. Initial documentation reviewed.'),
('STU-2026-018', 'Evelyn Perez', 'evelyn.perez18@example.com', '+1 (555) 610-2260', '+1 (555) 610-2260', '2007-04-01', 'Female', 'Australia', 'Victoria', 'Melbourne', 'Bachelor of Science in Computer Science', 3, 9, NULL, NULL, 'Spring 2027', '2026-09-28', 'Education Fair Dubai', 5, 'High', 6, 'In Progress', 721, 'Prospective student interested in study opportunities for Spring 2027. Contacted via Education Fair Dubai. Initial documentation reviewed.'),
('STU-2026-019', 'Alexander Thompson', 'alexander.thompson19@example.com', '+1 (555) 726-8119', '+1 (555) 726-8119', '2004-12-27', 'Male', 'United States', 'California', 'San Francisco', 'Bachelor of Business Administration', 4, 10, NULL, NULL, 'Fall 2027', '2026-07-30', 'Google Ads Campaign', 6, 'Moderate', 6, 'In Progress', 658, 'Prospective student interested in study opportunities for Fall 2027. Contacted via Google Ads Campaign. Initial documentation reviewed.'),
('STU-2026-020', 'Harper White', 'harper.white20@example.com', '+1 (555) 334-1707', '+1 (555) 334-1707', '2000-01-17', 'Female', 'India', 'Maharashtra', 'Mumbai', 'Higher Secondary Certificate (Grade 12)', 5, 1, NULL, NULL, 'Summer 2026', '2026-09-06', 'Instagram Campaign', 7, 'Low', 6, 'In Progress', 712, 'Prospective student interested in study opportunities for Summer 2026. Contacted via Instagram Campaign. Initial documentation reviewed.'),
('STU-2026-021', 'Sebastian Harris', 'sebastian.harris21@example.com', '+1 (555) 845-5723', '+1 (555) 845-5723', '1999-10-25', 'Male', 'Singapore', 'Central', 'Singapore', 'Bachelor of Engineering (Mechanical)', 6, 2, NULL, NULL, 'Fall 2026', '2026-08-04', 'Agent Referral', 4, 'High', 6, 'In Progress', 600, 'Prospective student interested in study opportunities for Fall 2026. Contacted via Agent Referral. Initial documentation reviewed.'),
('STU-2026-022', 'Luna Sanchez', 'luna.sanchez22@example.com', '+1 (555) 881-1710', '+1 (555) 881-1710', '1998-06-08', 'Female', 'Germany', 'Bavaria', 'Munich', 'Associate Degree in Information Technology', 7, 3, NULL, NULL, 'Spring 2027', '2026-09-24', 'Direct Walk-in', 5, 'Moderate', 6, 'In Progress', 647, 'Prospective student interested in study opportunities for Spring 2027. Contacted via Direct Walk-in. Initial documentation reviewed.'),
('STU-2026-023', 'Jack Clark', 'jack.clark23@example.com', '+1 (555) 600-1636', '+1 (555) 600-1636', '2007-05-04', 'Male', 'Ireland', 'Leinster', 'Dublin', 'Bachelor of Arts in Economics', 8, 4, NULL, NULL, 'Fall 2027', '2026-07-21', 'LinkedIn Higher Ed', 6, 'Low', 6, 'In Progress', 736, 'Prospective student interested in study opportunities for Fall 2027. Contacted via LinkedIn Higher Ed. Initial documentation reviewed.'),
('STU-2026-024', 'Camila Ramirez', 'camila.ramirez24@example.com', '+1 (555) 338-3973', '+1 (555) 338-3973', '2003-01-17', 'Female', 'United Arab Emirates', 'Dubai', 'Dubai', 'B.Sc. in Biotechnology', 1, 5, NULL, NULL, 'Summer 2026', '2026-09-01', 'University Webinar', 7, 'High', 6, 'In Progress', 703, 'Prospective student interested in study opportunities for Summer 2026. Contacted via University Webinar. Initial documentation reviewed.'),
('STU-2026-025', 'Daniel Lewis', 'daniel.lewis25@example.com', '+1 (555) 587-9864', '+1 (555) 587-9864', '2001-05-24', 'Male', 'Nigeria', 'Lagos', 'Lagos', 'High School Diploma (IB/A-Levels)', 2, 6, NULL, NULL, 'Fall 2026', '2026-07-13', 'Website Enquiry', 4, 'Moderate', 5, 'In Progress', 262, 'Prospective student interested in study opportunities for Fall 2026. Contacted via Website Enquiry. Initial documentation reviewed.'),
('STU-2026-026', 'Gianna Robinson', 'gianna.robinson26@example.com', '+1 (555) 374-7651', '+1 (555) 374-7651', '1998-02-27', 'Female', 'Malaysia', 'Selangor', 'Kuala Lumpur', 'Bachelor of Science in Computer Science', 3, 7, NULL, NULL, 'Spring 2027', '2026-07-22', 'Education Fair Dubai', 5, 'Low', 5, 'In Progress', 461, 'Prospective student interested in study opportunities for Spring 2027. Contacted via Education Fair Dubai. Initial documentation reviewed.'),
('STU-2026-027', 'Matthew Walker', 'matthew.walker27@example.com', '+1 (555) 437-5022', '+1 (555) 437-5022', '2006-10-04', 'Male', 'France', 'Ile-de-France', 'Paris', 'Bachelor of Business Administration', 4, 8, NULL, NULL, 'Fall 2027', '2026-10-23', 'Google Ads Campaign', 6, 'High', 5, 'In Progress', 328, 'Prospective student interested in study opportunities for Fall 2027. Contacted via Google Ads Campaign. Initial documentation reviewed.'),
('STU-2026-028', 'Elizabeth Young', 'elizabeth.young28@example.com', '+1 (555) 263-2060', '+1 (555) 263-2060', '1999-06-11', 'Female', 'Brazil', 'Sao Paulo', 'Sao Paulo', 'Higher Secondary Certificate (Grade 12)', 5, 9, NULL, NULL, 'Summer 2026', '2026-07-06', 'Instagram Campaign', 7, 'Moderate', 5, 'In Progress', 370, 'Prospective student interested in study opportunities for Summer 2026. Contacted via Instagram Campaign. Initial documentation reviewed.'),
('STU-2026-029', 'Aiden Allen', 'aiden.allen29@example.com', '+1 (555) 713-9207', '+1 (555) 713-9207', '1999-04-17', 'Male', 'Japan', 'Kanto', 'Tokyo', 'Bachelor of Engineering (Mechanical)', 6, 10, NULL, NULL, 'Fall 2026', '2026-07-19', 'Agent Referral', 4, 'Low', 5, 'In Progress', 333, 'Prospective student interested in study opportunities for Fall 2026. Contacted via Agent Referral. Initial documentation reviewed.'),
('STU-2026-030', 'Eleanor King', 'eleanor.king30@example.com', '+1 (555) 455-6428', '+1 (555) 455-6428', '2003-10-17', 'Female', 'South Africa', 'Gauteng', 'Johannesburg', 'Associate Degree in Information Technology', 7, 1, NULL, NULL, 'Spring 2027', '2026-07-23', 'Direct Walk-in', 5, 'High', 5, 'In Progress', 402, 'Prospective student interested in study opportunities for Spring 2027. Contacted via Direct Walk-in. Initial documentation reviewed.'),
('STU-2026-031', 'Samuel Wright', 'samuel.wright31@example.com', '+1 (555) 912-4611', '+1 (555) 912-4611', '2006-10-19', 'Male', 'United Kingdom', 'England', 'London', 'Bachelor of Arts in Economics', 8, 2, NULL, NULL, 'Fall 2027', '2026-08-12', 'LinkedIn Higher Ed', 6, 'Moderate', 5, 'In Progress', 360, 'Prospective student interested in study opportunities for Fall 2027. Contacted via LinkedIn Higher Ed. Initial documentation reviewed.'),
('STU-2026-032', 'Ella Scott', 'ella.scott32@example.com', '+1 (555) 883-2152', '+1 (555) 883-2152', '2005-12-13', 'Female', 'Canada', 'Ontario', 'Toronto', 'B.Sc. in Biotechnology', 1, 3, NULL, NULL, 'Summer 2026', '2026-07-10', 'University Webinar', 7, 'Low', 5, 'In Progress', 303, 'Prospective student interested in study opportunities for Summer 2026. Contacted via University Webinar. Initial documentation reviewed.'),
('STU-2026-033', 'David Torres', 'david.torres33@example.com', '+1 (555) 794-1129', '+1 (555) 794-1129', '1998-08-02', 'Male', 'Australia', 'Victoria', 'Melbourne', 'High School Diploma (IB/A-Levels)', 2, 4, NULL, NULL, 'Fall 2026', '2026-08-02', 'Website Enquiry', 4, 'High', 4, 'In Progress', 280, 'Prospective student interested in study opportunities for Fall 2026. Contacted via Website Enquiry. Initial documentation reviewed.'),
('STU-2026-034', 'Abigail Nguyen', 'abigail.nguyen34@example.com', '+1 (555) 511-1069', '+1 (555) 511-1069', '2007-11-27', 'Female', 'United States', 'California', 'San Francisco', 'Bachelor of Science in Computer Science', 3, 5, NULL, NULL, 'Spring 2027', '2026-07-30', 'Education Fair Dubai', 5, 'Moderate', 4, 'In Progress', 147, 'Prospective student interested in study opportunities for Spring 2027. Contacted via Education Fair Dubai. Initial documentation reviewed.'),
('STU-2026-035', 'Joseph Hill', 'joseph.hill35@example.com', '+1 (555) 599-1018', '+1 (555) 599-1018', '2000-08-11', 'Male', 'India', 'Maharashtra', 'Mumbai', 'Bachelor of Business Administration', 4, 6, NULL, NULL, 'Fall 2027', '2026-09-02', 'Google Ads Campaign', 6, 'Low', 4, 'In Progress', 101, 'Prospective student interested in study opportunities for Fall 2027. Contacted via Google Ads Campaign. Initial documentation reviewed.'),
('STU-2026-036', 'Sofia Flores', 'sofia.flores36@example.com', '+1 (555) 200-4264', '+1 (555) 200-4264', '2003-07-04', 'Female', 'Singapore', 'Central', 'Singapore', 'Higher Secondary Certificate (Grade 12)', 5, 7, NULL, NULL, 'Summer 2026', '2026-07-08', 'Instagram Campaign', 7, 'High', 4, 'In Progress', 261, 'Prospective student interested in study opportunities for Summer 2026. Contacted via Instagram Campaign. Initial documentation reviewed.'),
('STU-2026-037', 'Carter Green', 'carter.green37@example.com', '+1 (555) 354-8835', '+1 (555) 354-8835', '2003-07-18', 'Male', 'Germany', 'Bavaria', 'Munich', 'Bachelor of Engineering (Mechanical)', 6, 8, NULL, NULL, 'Fall 2026', '2026-10-15', 'Agent Referral', 4, 'Moderate', 4, 'In Progress', 253, 'Prospective student interested in study opportunities for Fall 2026. Contacted via Agent Referral. Initial documentation reviewed.'),
('STU-2026-038', 'Avery Adams', 'avery.adams38@example.com', '+1 (555) 458-2026', '+1 (555) 458-2026', '2003-07-26', 'Female', 'Ireland', 'Leinster', 'Dublin', 'Associate Degree in Information Technology', 7, 9, NULL, NULL, 'Spring 2027', '2026-09-13', 'Direct Walk-in', 5, 'Low', 4, 'In Progress', 121, 'Prospective student interested in study opportunities for Spring 2027. Contacted via Direct Walk-in. Initial documentation reviewed.'),
('STU-2026-039', 'Owen Nelson', 'owen.nelson39@example.com', '+1 (555) 732-8051', '+1 (555) 732-8051', '2002-02-24', 'Male', 'United Arab Emirates', 'Dubai', 'Dubai', 'Bachelor of Arts in Economics', 8, 10, NULL, NULL, 'Fall 2027', '2026-07-05', 'LinkedIn Higher Ed', 6, 'High', 3, 'In Progress', 119, 'Prospective student interested in study opportunities for Fall 2027. Contacted via LinkedIn Higher Ed. Initial documentation reviewed.'),
('STU-2026-040', 'Scarlett Baker', 'scarlett.baker40@example.com', '+1 (555) 838-8516', '+1 (555) 838-8516', '2006-12-10', 'Female', 'Nigeria', 'Lagos', 'Lagos', 'B.Sc. in Biotechnology', 1, 1, NULL, NULL, 'Summer 2026', '2026-10-14', 'University Webinar', 7, 'Moderate', 3, 'In Progress', 139, 'Prospective student interested in study opportunities for Summer 2026. Contacted via University Webinar. Initial documentation reviewed.'),
('STU-2026-041', 'Wyatt Hall', 'wyatt.hall41@example.com', '+1 (555) 892-4823', '+1 (555) 892-4823', '2007-11-08', 'Male', 'Malaysia', 'Selangor', 'Kuala Lumpur', 'High School Diploma (IB/A-Levels)', 2, 2, NULL, NULL, 'Fall 2026', '2026-10-05', 'Website Enquiry', 4, 'Low', 3, 'In Progress', 70, 'Prospective student interested in study opportunities for Fall 2026. Contacted via Website Enquiry. Initial documentation reviewed.'),
('STU-2026-042', 'Emily Rivera', 'emily.rivera42@example.com', '+1 (555) 555-7896', '+1 (555) 555-7896', '2004-02-15', 'Female', 'France', 'Ile-de-France', 'Paris', 'Bachelor of Science in Computer Science', 3, 3, NULL, NULL, 'Spring 2027', '2026-07-08', 'Education Fair Dubai', 5, 'High', 3, 'In Progress', 145, 'Prospective student interested in study opportunities for Spring 2027. Contacted via Education Fair Dubai. Initial documentation reviewed.'),
('STU-2026-043', 'John Campbell', 'john.campbell43@example.com', '+1 (555) 449-3477', '+1 (555) 449-3477', '2001-03-24', 'Male', 'Brazil', 'Sao Paulo', 'Sao Paulo', 'Bachelor of Business Administration', 4, 4, NULL, NULL, 'Fall 2027', '2026-07-09', 'Google Ads Campaign', 6, 'Moderate', 2, 'In Progress', 50, 'Prospective student interested in study opportunities for Fall 2027. Contacted via Google Ads Campaign. Initial documentation reviewed.'),
('STU-2026-044', 'Aria Mitchell', 'aria.mitchell44@example.com', '+1 (555) 800-4865', '+1 (555) 800-4865', '2003-10-26', 'Female', 'Japan', 'Kanto', 'Tokyo', 'Higher Secondary Certificate (Grade 12)', 5, 5, NULL, NULL, 'Summer 2026', '2026-07-09', 'Instagram Campaign', 7, 'Low', 2, 'In Progress', 50, 'Prospective student interested in study opportunities for Summer 2026. Contacted via Instagram Campaign. Initial documentation reviewed.'),
('STU-2026-045', 'Luke Carter', 'luke.carter45@example.com', '+1 (555) 496-8184', '+1 (555) 496-8184', '2000-06-18', 'Male', 'South Africa', 'Gauteng', 'Johannesburg', 'Bachelor of Engineering (Mechanical)', 6, 6, NULL, NULL, 'Fall 2026', '2026-07-17', 'Agent Referral', 4, 'High', 2, 'On Hold', 50, 'Prospective student interested in study opportunities for Fall 2026. Contacted via Agent Referral. Initial documentation reviewed.'),
('STU-2026-046', 'Penelope Roberts', 'penelope.roberts46@example.com', '+1 (555) 765-5413', '+1 (555) 765-5413', '2005-06-21', 'Female', 'United Kingdom', 'England', 'London', 'Associate Degree in Information Technology', 7, 7, NULL, NULL, 'Spring 2027', '2026-09-28', 'Direct Walk-in', 5, 'Moderate', 2, 'In Progress', 50, 'Prospective student interested in study opportunities for Spring 2027. Contacted via Direct Walk-in. Initial documentation reviewed.'),
('STU-2026-047', 'Asher Smith', 'asher.smith47@example.com', '+1 (555) 510-6451', '+1 (555) 510-6451', '2007-02-07', 'Male', 'Canada', 'Ontario', 'Toronto', 'Bachelor of Arts in Economics', 8, 8, NULL, NULL, 'Fall 2027', '2026-08-13', 'LinkedIn Higher Ed', 6, 'Low', 1, 'In Progress', 0, 'Prospective student interested in study opportunities for Fall 2027. Contacted via LinkedIn Higher Ed. Initial documentation reviewed.'),
('STU-2026-048', 'Chloe Johnson', 'chloe.johnson48@example.com', '+1 (555) 237-6049', '+1 (555) 237-6049', '2004-02-08', 'Female', 'Australia', 'Victoria', 'Melbourne', 'B.Sc. in Biotechnology', 1, 9, NULL, NULL, 'Summer 2026', '2026-07-15', 'University Webinar', 7, 'High', 1, 'In Progress', 0, 'Prospective student interested in study opportunities for Summer 2026. Contacted via University Webinar. Initial documentation reviewed.'),
('STU-2026-049', 'Leo Williams', 'leo.williams49@example.com', '+1 (555) 373-9775', '+1 (555) 373-9775', '1998-05-15', 'Male', 'United States', 'California', 'San Francisco', 'High School Diploma (IB/A-Levels)', 2, 10, NULL, NULL, 'Fall 2026', '2026-07-11', 'Website Enquiry', 4, 'Moderate', 1, 'In Progress', 0, 'Prospective student interested in study opportunities for Fall 2026. Contacted via Website Enquiry. Initial documentation reviewed.'),
('STU-2026-050', 'Layla Brown', 'layla.brown50@example.com', '+1 (555) 806-8182', '+1 (555) 806-8182', '1998-07-21', 'Female', 'India', 'Maharashtra', 'Mumbai', 'Bachelor of Science in Computer Science', 3, 1, NULL, NULL, 'Spring 2027', '2026-08-11', 'Education Fair Dubai', 5, 'Low', 1, 'Admission Lost', 0, 'Prospective student interested in study opportunities for Spring 2027. Contacted via Education Fair Dubai. Initial documentation reviewed.');


INSERT INTO `leads` (`lead_code`, `full_name`, `email`, `mobile_number`, `interested_course`, `interested_country`, `lead_source`, `status`, `priority`, `assigned_staff_id`, `converted_student_id`, `notes`) VALUES
('LEAD-2026-001', 'Henry Lopez', 'lead.henry.lopez1@prospects.org', '+1 (555) 604-4440', 'Computer Science', 'United States', 'Google Ads Campaign', 'New Lead', 'High', 4, NULL, 'Inquired about entry criteria and scholarship provisions during Google Ads Campaign. Requires prompt follow-up.'),
('LEAD-2026-002', 'Evelyn Gonzalez', 'lead.evelyn.gonzalez2@prospects.org', '+1 (555) 332-5851', 'Data Analytics', 'India', 'Instagram Campaign', 'Contacted', 'Moderate', 5, NULL, 'Inquired about entry criteria and scholarship provisions during Instagram Campaign. Requires prompt follow-up.'),
('LEAD-2026-003', 'Alexander Wilson', 'lead.alexander.wilson3@prospects.org', '+1 (555) 347-8728', 'Global MBA', 'Singapore', 'Agent Referral', 'Interested', 'Low', 6, NULL, 'Inquired about entry criteria and scholarship provisions during Agent Referral. Requires prompt follow-up.'),
('LEAD-2026-004', 'Harper Anderson', 'lead.harper.anderson4@prospects.org', '+1 (555) 587-5980', 'Biomedical Engineering', 'Germany', 'Direct Walk-in', 'Not Interested', 'High', 7, NULL, 'Inquired about entry criteria and scholarship provisions during Direct Walk-in. Requires prompt follow-up.'),
('LEAD-2026-005', 'Sebastian Thomas', 'lead.sebastian.thomas5@prospects.org', '+1 (555) 800-3104', 'Cybersecurity', 'Ireland', 'LinkedIn Higher Ed', 'Follow-up Required', 'Moderate', 4, NULL, 'Inquired about entry criteria and scholarship provisions during LinkedIn Higher Ed. Requires prompt follow-up.'),
('LEAD-2026-006', 'Luna Taylor', 'lead.luna.taylor6@prospects.org', '+1 (555) 715-1524', 'Civil Engineering', 'United Arab Emirates', 'University Webinar', 'Application Started', 'Low', 5, NULL, 'Inquired about entry criteria and scholarship provisions during University Webinar. Requires prompt follow-up.'),
('LEAD-2026-007', 'Jack Moore', 'lead.jack.moore7@prospects.org', '+1 (555) 576-2162', 'Fintech & Blockchain', 'Nigeria', 'Website Enquiry', 'Converted', 'High', 6, 7, 'Inquired about entry criteria and scholarship provisions during Website Enquiry. Requires prompt follow-up.'),
('LEAD-2026-008', 'Camila Jackson', 'lead.camila.jackson8@prospects.org', '+1 (555) 707-7981', 'Public Policy', 'Malaysia', 'Education Fair Dubai', 'Lost', 'Moderate', 7, NULL, 'Inquired about entry criteria and scholarship provisions during Education Fair Dubai. Requires prompt follow-up.'),
('LEAD-2026-009', 'Daniel Martin', 'lead.daniel.martin9@prospects.org', '+1 (555) 340-6956', 'Computer Science', 'France', 'Google Ads Campaign', 'New Lead', 'Low', 4, NULL, 'Inquired about entry criteria and scholarship provisions during Google Ads Campaign. Requires prompt follow-up.'),
('LEAD-2026-010', 'Gianna Lee', 'lead.gianna.lee10@prospects.org', '+1 (555) 633-6623', 'Data Analytics', 'Brazil', 'Instagram Campaign', 'Contacted', 'High', 5, NULL, 'Inquired about entry criteria and scholarship provisions during Instagram Campaign. Requires prompt follow-up.'),
('LEAD-2026-011', 'Matthew Perez', 'lead.matthew.perez11@prospects.org', '+1 (555) 660-2936', 'Global MBA', 'Japan', 'Agent Referral', 'Interested', 'Moderate', 6, NULL, 'Inquired about entry criteria and scholarship provisions during Agent Referral. Requires prompt follow-up.'),
('LEAD-2026-012', 'Elizabeth Thompson', 'lead.elizabeth.thompson12@prospects.org', '+1 (555) 831-7440', 'Biomedical Engineering', 'South Africa', 'Direct Walk-in', 'Not Interested', 'Low', 7, NULL, 'Inquired about entry criteria and scholarship provisions during Direct Walk-in. Requires prompt follow-up.'),
('LEAD-2026-013', 'Aiden White', 'lead.aiden.white13@prospects.org', '+1 (555) 863-8305', 'Cybersecurity', 'United Kingdom', 'LinkedIn Higher Ed', 'Follow-up Required', 'High', 4, NULL, 'Inquired about entry criteria and scholarship provisions during LinkedIn Higher Ed. Requires prompt follow-up.'),
('LEAD-2026-014', 'Eleanor Harris', 'lead.eleanor.harris14@prospects.org', '+1 (555) 857-3997', 'Civil Engineering', 'Canada', 'University Webinar', 'Application Started', 'Moderate', 5, NULL, 'Inquired about entry criteria and scholarship provisions during University Webinar. Requires prompt follow-up.'),
('LEAD-2026-015', 'Samuel Sanchez', 'lead.samuel.sanchez15@prospects.org', '+1 (555) 512-7451', 'Fintech & Blockchain', 'Australia', 'Website Enquiry', 'Converted', 'Low', 6, 6, 'Inquired about entry criteria and scholarship provisions during Website Enquiry. Requires prompt follow-up.'),
('LEAD-2026-016', 'Ella Clark', 'lead.ella.clark16@prospects.org', '+1 (555) 335-8681', 'Public Policy', 'United States', 'Education Fair Dubai', 'Lost', 'High', 7, NULL, 'Inquired about entry criteria and scholarship provisions during Education Fair Dubai. Requires prompt follow-up.'),
('LEAD-2026-017', 'David Ramirez', 'lead.david.ramirez17@prospects.org', '+1 (555) 890-3259', 'Computer Science', 'India', 'Google Ads Campaign', 'New Lead', 'Moderate', 4, NULL, 'Inquired about entry criteria and scholarship provisions during Google Ads Campaign. Requires prompt follow-up.'),
('LEAD-2026-018', 'Abigail Lewis', 'lead.abigail.lewis18@prospects.org', '+1 (555) 814-2884', 'Data Analytics', 'Singapore', 'Instagram Campaign', 'Contacted', 'Low', 5, NULL, 'Inquired about entry criteria and scholarship provisions during Instagram Campaign. Requires prompt follow-up.'),
('LEAD-2026-019', 'Joseph Robinson', 'lead.joseph.robinson19@prospects.org', '+1 (555) 449-4472', 'Global MBA', 'Germany', 'Agent Referral', 'Interested', 'High', 6, NULL, 'Inquired about entry criteria and scholarship provisions during Agent Referral. Requires prompt follow-up.'),
('LEAD-2026-020', 'Sofia Walker', 'lead.sofia.walker20@prospects.org', '+1 (555) 838-6697', 'Biomedical Engineering', 'Ireland', 'Direct Walk-in', 'Not Interested', 'Moderate', 7, NULL, 'Inquired about entry criteria and scholarship provisions during Direct Walk-in. Requires prompt follow-up.'),
('LEAD-2026-021', 'Carter Young', 'lead.carter.young21@prospects.org', '+1 (555) 861-1099', 'Cybersecurity', 'United Arab Emirates', 'LinkedIn Higher Ed', 'Follow-up Required', 'Low', 4, NULL, 'Inquired about entry criteria and scholarship provisions during LinkedIn Higher Ed. Requires prompt follow-up.'),
('LEAD-2026-022', 'Avery Allen', 'lead.avery.allen22@prospects.org', '+1 (555) 376-2491', 'Civil Engineering', 'Nigeria', 'University Webinar', 'Application Started', 'High', 5, NULL, 'Inquired about entry criteria and scholarship provisions during University Webinar. Requires prompt follow-up.'),
('LEAD-2026-023', 'Owen King', 'lead.owen.king23@prospects.org', '+1 (555) 384-8651', 'Fintech & Blockchain', 'Malaysia', 'Website Enquiry', 'Converted', 'Moderate', 6, 2, 'Inquired about entry criteria and scholarship provisions during Website Enquiry. Requires prompt follow-up.'),
('LEAD-2026-024', 'Scarlett Wright', 'lead.scarlett.wright24@prospects.org', '+1 (555) 722-3652', 'Public Policy', 'France', 'Education Fair Dubai', 'Lost', 'Low', 7, NULL, 'Inquired about entry criteria and scholarship provisions during Education Fair Dubai. Requires prompt follow-up.'),
('LEAD-2026-025', 'Wyatt Scott', 'lead.wyatt.scott25@prospects.org', '+1 (555) 321-2462', 'Computer Science', 'Brazil', 'Google Ads Campaign', 'New Lead', 'High', 4, NULL, 'Inquired about entry criteria and scholarship provisions during Google Ads Campaign. Requires prompt follow-up.'),
('LEAD-2026-026', 'Emily Torres', 'lead.emily.torres26@prospects.org', '+1 (555) 764-6176', 'Data Analytics', 'Japan', 'Instagram Campaign', 'Contacted', 'Moderate', 5, NULL, 'Inquired about entry criteria and scholarship provisions during Instagram Campaign. Requires prompt follow-up.'),
('LEAD-2026-027', 'John Nguyen', 'lead.john.nguyen27@prospects.org', '+1 (555) 579-1332', 'Global MBA', 'South Africa', 'Agent Referral', 'Interested', 'Low', 6, NULL, 'Inquired about entry criteria and scholarship provisions during Agent Referral. Requires prompt follow-up.'),
('LEAD-2026-028', 'Aria Hill', 'lead.aria.hill28@prospects.org', '+1 (555) 514-8233', 'Biomedical Engineering', 'United Kingdom', 'Direct Walk-in', 'Not Interested', 'High', 7, NULL, 'Inquired about entry criteria and scholarship provisions during Direct Walk-in. Requires prompt follow-up.'),
('LEAD-2026-029', 'Luke Flores', 'lead.luke.flores29@prospects.org', '+1 (555) 454-6445', 'Cybersecurity', 'Canada', 'LinkedIn Higher Ed', 'Follow-up Required', 'Moderate', 4, NULL, 'Inquired about entry criteria and scholarship provisions during LinkedIn Higher Ed. Requires prompt follow-up.'),
('LEAD-2026-030', 'Penelope Green', 'lead.penelope.green30@prospects.org', '+1 (555) 333-5281', 'Civil Engineering', 'Australia', 'University Webinar', 'Application Started', 'Low', 5, NULL, 'Inquired about entry criteria and scholarship provisions during University Webinar. Requires prompt follow-up.');


INSERT INTO `followups` (`student_id`, `lead_id`, `assigned_staff_id`, `followup_date`, `followup_time`, `priority`, `communication_type`, `notes`, `student_response`, `next_followup_date`, `status`, `completed_at`) VALUES
(40, NULL, 4, '2026-11-02', '09:32:00', 'High', 'Phone Call', 'Follow-up session #1 via Phone Call. Focus on application progression.', 'Scheduled call to discuss visa checklist and scholarship deadline.', '2026-11-09', 'Pending', NULL),
(25, NULL, 5, '2026-11-02', '16:12:00', 'Moderate', 'WhatsApp', 'Follow-up session #2 via WhatsApp. Focus on application progression.', 'Scheduled call to discuss visa checklist and scholarship deadline.', '2026-11-09', 'Pending', NULL),
(13, NULL, 6, '2026-11-02', '10:14:00', 'Low', 'Email', 'Follow-up session #3 via Email. Focus on application progression.', 'Scheduled call to discuss visa checklist and scholarship deadline.', '2026-11-09', 'Pending', NULL),
(49, 3, 7, '2026-11-02', '13:54:00', 'High', 'Direct Meeting', 'Follow-up session #4 via Direct Meeting. Focus on application progression.', 'Scheduled call to discuss visa checklist and scholarship deadline.', '2026-11-09', 'Pending', NULL),
(4, NULL, 4, '2026-11-02', '12:19:00', 'Moderate', 'Phone Call', 'Follow-up session #5 via Phone Call. Focus on application progression.', 'Scheduled call to discuss visa checklist and scholarship deadline.', '2026-11-09', 'Pending', NULL),
(18, NULL, 5, '2026-11-02', '14:52:00', 'Low', 'WhatsApp', 'Follow-up session #6 via WhatsApp. Focus on application progression.', 'Scheduled call to discuss visa checklist and scholarship deadline.', '2026-11-09', 'Pending', NULL),
(43, NULL, 6, '2026-11-02', '16:12:00', 'High', 'Email', 'Follow-up session #7 via Email. Focus on application progression.', 'Scheduled call to discuss visa checklist and scholarship deadline.', '2026-11-09', 'Pending', NULL),
(37, 1, 7, '2026-11-02', '17:19:00', 'Moderate', 'Direct Meeting', 'Follow-up session #8 via Direct Meeting. Focus on application progression.', 'Scheduled call to discuss visa checklist and scholarship deadline.', '2026-11-09', 'Pending', NULL),
(6, NULL, 4, '2026-10-26', '14:51:00', 'Low', 'Phone Call', 'Follow-up session #9 via Phone Call. Focus on application progression.', 'Attempted contact, phone switched off. Left WhatsApp voice note.', '2026-11-02', 'Overdue', NULL),
(41, NULL, 5, '2026-10-29', '13:41:00', 'High', 'WhatsApp', 'Follow-up session #10 via WhatsApp. Focus on application progression.', 'Attempted contact, phone switched off. Left WhatsApp voice note.', '2026-11-02', 'Overdue', NULL),
(49, NULL, 6, '2026-10-29', '11:36:00', 'Moderate', 'Email', 'Follow-up session #11 via Email. Focus on application progression.', 'Attempted contact, phone switched off. Left WhatsApp voice note.', '2026-11-02', 'Overdue', NULL),
(8, 15, 7, '2026-10-31', '11:03:00', 'Low', 'Direct Meeting', 'Follow-up session #12 via Direct Meeting. Focus on application progression.', 'Attempted contact, phone switched off. Left WhatsApp voice note.', '2026-11-02', 'Overdue', NULL),
(2, NULL, 4, '2026-10-27', '12:08:00', 'High', 'Phone Call', 'Follow-up session #13 via Phone Call. Focus on application progression.', 'Attempted contact, phone switched off. Left WhatsApp voice note.', '2026-11-02', 'Overdue', NULL),
(38, NULL, 5, '2026-11-01', '13:18:00', 'Moderate', 'WhatsApp', 'Follow-up session #14 via WhatsApp. Focus on application progression.', 'Attempted contact, phone switched off. Left WhatsApp voice note.', '2026-11-02', 'Overdue', NULL),
(24, NULL, 6, '2026-10-30', '10:42:00', 'Low', 'Email', 'Follow-up session #15 via Email. Focus on application progression.', 'Attempted contact, phone switched off. Left WhatsApp voice note.', '2026-11-02', 'Overdue', NULL),
(24, 5, 7, '2026-10-30', '16:51:00', 'High', 'Direct Meeting', 'Follow-up session #16 via Direct Meeting. Focus on application progression.', 'Attempted contact, phone switched off. Left WhatsApp voice note.', '2026-11-02', 'Overdue', NULL),
(50, NULL, 4, '2026-10-30', '15:42:00', 'Moderate', 'Phone Call', 'Follow-up session #17 via Phone Call. Focus on application progression.', 'Detailed 25-minute discussion. Student confirmed readiness to submit IELTS scorecard.', '2026-11-10', 'Completed', '2026-10-30 15:42:00'),
(26, NULL, 5, '2026-10-16', '11:13:00', 'Low', 'WhatsApp', 'Follow-up session #18 via WhatsApp. Focus on application progression.', 'Detailed 25-minute discussion. Student confirmed readiness to submit IELTS scorecard.', '2026-11-10', 'Completed', '2026-10-16 11:13:00'),
(20, NULL, 6, '2026-10-21', '15:48:00', 'High', 'Email', 'Follow-up session #19 via Email. Focus on application progression.', 'Detailed 25-minute discussion. Student confirmed readiness to submit IELTS scorecard.', '2026-11-10', 'Completed', '2026-10-21 15:48:00'),
(31, 16, 7, '2026-10-15', '14:07:00', 'Moderate', 'Direct Meeting', 'Follow-up session #20 via Direct Meeting. Focus on application progression.', 'Detailed 25-minute discussion. Student confirmed readiness to submit IELTS scorecard.', '2026-11-10', 'Completed', '2026-10-15 14:07:00'),
(14, NULL, 4, '2026-10-27', '15:10:00', 'Low', 'Phone Call', 'Follow-up session #21 via Phone Call. Focus on application progression.', 'Detailed 25-minute discussion. Student confirmed readiness to submit IELTS scorecard.', '2026-11-10', 'Completed', '2026-10-27 15:10:00'),
(20, NULL, 5, '2026-10-26', '12:44:00', 'High', 'WhatsApp', 'Follow-up session #22 via WhatsApp. Focus on application progression.', 'Detailed 25-minute discussion. Student confirmed readiness to submit IELTS scorecard.', '2026-11-10', 'Completed', '2026-10-26 12:44:00'),
(39, NULL, 6, '2026-10-26', '14:51:00', 'Moderate', 'Email', 'Follow-up session #23 via Email. Focus on application progression.', 'Detailed 25-minute discussion. Student confirmed readiness to submit IELTS scorecard.', '2026-11-10', 'Completed', '2026-10-26 14:51:00'),
(26, 29, 7, '2026-10-15', '10:43:00', 'Low', 'Direct Meeting', 'Follow-up session #24 via Direct Meeting. Focus on application progression.', 'Detailed 25-minute discussion. Student confirmed readiness to submit IELTS scorecard.', '2026-11-10', 'Completed', '2026-10-15 10:43:00'),
(2, NULL, 4, '2026-10-14', '14:01:00', 'High', 'Phone Call', 'Follow-up session #25 via Phone Call. Focus on application progression.', 'Detailed 25-minute discussion. Student confirmed readiness to submit IELTS scorecard.', '2026-11-10', 'Completed', '2026-10-14 14:01:00'),
(41, NULL, 5, '2026-10-22', '11:27:00', 'Moderate', 'WhatsApp', 'Follow-up session #26 via WhatsApp. Focus on application progression.', 'Detailed 25-minute discussion. Student confirmed readiness to submit IELTS scorecard.', '2026-11-10', 'Completed', '2026-10-22 11:27:00'),
(36, NULL, 6, '2026-11-04', '14:40:00', 'Low', 'Email', 'Follow-up session #27 via Email. Focus on application progression.', 'Booked direct video meeting to finalize course selection and portfolio review.', NULL, 'Pending', NULL),
(33, 26, 7, '2026-11-11', '10:21:00', 'High', 'Direct Meeting', 'Follow-up session #28 via Direct Meeting. Focus on application progression.', 'Booked direct video meeting to finalize course selection and portfolio review.', NULL, 'Pending', NULL),
(9, NULL, 4, '2026-11-06', '16:13:00', 'Moderate', 'Phone Call', 'Follow-up session #29 via Phone Call. Focus on application progression.', 'Booked direct video meeting to finalize course selection and portfolio review.', NULL, 'Pending', NULL),
(41, NULL, 5, '2026-11-03', '12:51:00', 'Low', 'WhatsApp', 'Follow-up session #30 via WhatsApp. Focus on application progression.', 'Booked direct video meeting to finalize course selection and portfolio review.', NULL, 'Pending', NULL),
(4, NULL, 6, '2026-11-11', '15:01:00', 'High', 'Email', 'Follow-up session #31 via Email. Focus on application progression.', 'Booked direct video meeting to finalize course selection and portfolio review.', NULL, 'Pending', NULL),
(10, 19, 7, '2026-11-06', '10:52:00', 'Moderate', 'Direct Meeting', 'Follow-up session #32 via Direct Meeting. Focus on application progression.', 'Booked direct video meeting to finalize course selection and portfolio review.', NULL, 'Pending', NULL),
(46, NULL, 4, '2026-11-09', '15:51:00', 'Low', 'Phone Call', 'Follow-up session #33 via Phone Call. Focus on application progression.', 'Booked direct video meeting to finalize course selection and portfolio review.', NULL, 'Pending', NULL),
(35, NULL, 5, '2026-11-07', '16:36:00', 'High', 'WhatsApp', 'Follow-up session #34 via WhatsApp. Focus on application progression.', 'Booked direct video meeting to finalize course selection and portfolio review.', NULL, 'Pending', NULL),
(8, NULL, 6, '2026-11-05', '14:17:00', 'Moderate', 'Email', 'Follow-up session #35 via Email. Focus on application progression.', 'Booked direct video meeting to finalize course selection and portfolio review.', NULL, 'Pending', NULL);


INSERT INTO `tasks` (`task_title`, `description`, `category`, `assigned_staff_id`, `student_id`, `due_date`, `priority`, `status`) VALUES
('Verify high school transcripts and certified translations', 'Comprehensive action required for Verify high school transcripts and certified translations as part of admissions workflow cycle.', 'Student Follow-up', 4, 22, '2026-11-02', 'High', 'In Progress'),
('Follow up on pending financial sponsor affidavit', 'Comprehensive action required for Follow up on pending financial sponsor affidavit as part of admissions workflow cycle.', 'Document Collection', 5, 6, '2026-11-02', 'Moderate', 'In Progress'),
('Submit CAS / I-20 university issuance request', 'Comprehensive action required for Submit CAS / I-20 university issuance request as part of admissions workflow cycle.', 'Application Completion', 6, 20, '2026-11-02', 'Low', 'In Progress'),
('Conduct preliminary mock visa interview', 'Comprehensive action required for Conduct preliminary mock visa interview as part of admissions workflow cycle.', 'Admission Verification', 7, 38, '2026-11-02', 'High', 'In Progress'),
('Collect updated English proficiency test scores', 'Comprehensive action required for Collect updated English proficiency test scores as part of admissions workflow cycle.', 'General Task', 4, 22, '2026-11-02', 'Moderate', 'In Progress'),
('Review personal statement and letter of recommendation draft', 'Comprehensive action required for Review personal statement and letter of recommendation draft as part of admissions workflow cycle.', 'Student Follow-up', 5, 21, '2026-11-02', 'Low', 'In Progress'),
('Schedule virtual appointment with Department Dean', 'Comprehensive action required for Schedule virtual appointment with Department Dean as part of admissions workflow cycle.', 'Document Collection', 6, 12, '2026-11-01', 'High', 'Overdue'),
('Send conditional admission offer package to student email', 'Comprehensive action required for Send conditional admission offer package to student email as part of admissions workflow cycle.', 'Application Completion', 7, 41, '2026-11-01', 'Moderate', 'Overdue'),
('Audit scholarship qualification credentials', 'Comprehensive action required for Audit scholarship qualification credentials as part of admissions workflow cycle.', 'Admission Verification', 4, 38, '2026-10-31', 'Low', 'Overdue'),
('Upload certified passport bio-page copy to admissions portal', 'Comprehensive action required for Upload certified passport bio-page copy to admissions portal as part of admissions workflow cycle.', 'General Task', 5, 14, '2026-11-01', 'High', 'Overdue'),
('Verify high school transcripts and certified translations', 'Comprehensive action required for Verify high school transcripts and certified translations as part of admissions workflow cycle.', 'Student Follow-up', 6, 38, '2026-10-29', 'Moderate', 'Overdue'),
('Follow up on pending financial sponsor affidavit', 'Comprehensive action required for Follow up on pending financial sponsor affidavit as part of admissions workflow cycle.', 'Document Collection', 7, 15, '2026-10-30', 'Low', 'Overdue'),
('Submit CAS / I-20 university issuance request', 'Comprehensive action required for Submit CAS / I-20 university issuance request as part of admissions workflow cycle.', 'Application Completion', 4, 42, '2026-11-03', 'High', 'Pending'),
('Conduct preliminary mock visa interview', 'Comprehensive action required for Conduct preliminary mock visa interview as part of admissions workflow cycle.', 'Admission Verification', 5, 42, '2026-11-11', 'Moderate', 'Pending'),
('Collect updated English proficiency test scores', 'Comprehensive action required for Collect updated English proficiency test scores as part of admissions workflow cycle.', 'General Task', 6, 4, '2026-11-06', 'Low', 'Pending'),
('Review personal statement and letter of recommendation draft', 'Comprehensive action required for Review personal statement and letter of recommendation draft as part of admissions workflow cycle.', 'Student Follow-up', 7, 30, '2026-11-09', 'High', 'Pending'),
('Schedule virtual appointment with Department Dean', 'Comprehensive action required for Schedule virtual appointment with Department Dean as part of admissions workflow cycle.', 'Document Collection', 4, 32, '2026-11-12', 'Moderate', 'Pending'),
('Send conditional admission offer package to student email', 'Comprehensive action required for Send conditional admission offer package to student email as part of admissions workflow cycle.', 'Application Completion', 5, 29, '2026-11-15', 'Low', 'Pending'),
('Audit scholarship qualification credentials', 'Comprehensive action required for Audit scholarship qualification credentials as part of admissions workflow cycle.', 'Admission Verification', 6, 18, '2026-10-20', 'High', 'Completed'),
('Upload certified passport bio-page copy to admissions portal', 'Comprehensive action required for Upload certified passport bio-page copy to admissions portal as part of admissions workflow cycle.', 'General Task', 7, 48, '2026-10-28', 'Moderate', 'Completed'),
('Verify high school transcripts and certified translations', 'Comprehensive action required for Verify high school transcripts and certified translations as part of admissions workflow cycle.', 'Student Follow-up', 4, 22, '2026-10-26', 'Low', 'Completed'),
('Follow up on pending financial sponsor affidavit', 'Comprehensive action required for Follow up on pending financial sponsor affidavit as part of admissions workflow cycle.', 'Document Collection', 5, 1, '2026-10-29', 'High', 'Completed'),
('Submit CAS / I-20 university issuance request', 'Comprehensive action required for Submit CAS / I-20 university issuance request as part of admissions workflow cycle.', 'Application Completion', 6, 50, '2026-10-26', 'Moderate', 'Completed'),
('Conduct preliminary mock visa interview', 'Comprehensive action required for Conduct preliminary mock visa interview as part of admissions workflow cycle.', 'Admission Verification', 7, 2, '2026-10-22', 'Low', 'Completed'),
('Collect updated English proficiency test scores', 'Comprehensive action required for Collect updated English proficiency test scores as part of admissions workflow cycle.', 'General Task', 4, 9, '2026-10-30', 'High', 'Completed');


INSERT INTO `student_stage_history` (`student_id`, `stage_id`, `changed_by_user_id`, `notes`, `created_at`) VALUES
(1, 1, 5, 'Applicant successfully advanced to stage 1. Requirements fulfilled and verified.', '2026-09-06 00:00:00'),
(1, 2, 5, 'Applicant successfully advanced to stage 2. Requirements fulfilled and verified.', '2026-09-11 00:00:00'),
(1, 3, 5, 'Applicant successfully advanced to stage 3. Requirements fulfilled and verified.', '2026-09-21 00:00:00'),
(1, 4, 5, 'Applicant successfully advanced to stage 4. Requirements fulfilled and verified.', '2026-09-29 00:00:00'),
(1, 5, 5, 'Applicant successfully advanced to stage 5. Requirements fulfilled and verified.', '2026-10-04 00:00:00'),
(1, 6, 5, 'Applicant successfully advanced to stage 6. Requirements fulfilled and verified.', '2026-10-15 00:00:00'),
(1, 7, 5, 'Applicant successfully advanced to stage 7. Requirements fulfilled and verified.', '2026-10-23 00:00:00'),
(1, 8, 5, 'Applicant successfully advanced to stage 8. Requirements fulfilled and verified.', '2026-10-31 00:00:00'),
(2, 1, 6, 'Applicant successfully advanced to stage 1. Requirements fulfilled and verified.', '2026-09-06 00:00:00'),
(2, 2, 6, 'Applicant successfully advanced to stage 2. Requirements fulfilled and verified.', '2026-09-11 00:00:00'),
(2, 3, 6, 'Applicant successfully advanced to stage 3. Requirements fulfilled and verified.', '2026-09-19 00:00:00'),
(2, 4, 6, 'Applicant successfully advanced to stage 4. Requirements fulfilled and verified.', '2026-09-30 00:00:00'),
(2, 5, 6, 'Applicant successfully advanced to stage 5. Requirements fulfilled and verified.', '2026-10-07 00:00:00'),
(2, 6, 6, 'Applicant successfully advanced to stage 6. Requirements fulfilled and verified.', '2026-10-13 00:00:00'),
(2, 7, 6, 'Applicant successfully advanced to stage 7. Requirements fulfilled and verified.', '2026-10-22 00:00:00'),
(2, 8, 6, 'Applicant successfully advanced to stage 8. Requirements fulfilled and verified.', '2026-10-29 00:00:00'),
(3, 1, 7, 'Applicant successfully advanced to stage 1. Requirements fulfilled and verified.', '2026-09-06 00:00:00'),
(3, 2, 7, 'Applicant successfully advanced to stage 2. Requirements fulfilled and verified.', '2026-09-12 00:00:00'),
(3, 3, 7, 'Applicant successfully advanced to stage 3. Requirements fulfilled and verified.', '2026-09-20 00:00:00'),
(3, 4, 7, 'Applicant successfully advanced to stage 4. Requirements fulfilled and verified.', '2026-09-26 00:00:00'),
(3, 5, 7, 'Applicant successfully advanced to stage 5. Requirements fulfilled and verified.', '2026-10-07 00:00:00'),
(3, 6, 7, 'Applicant successfully advanced to stage 6. Requirements fulfilled and verified.', '2026-10-12 00:00:00'),
(3, 7, 7, 'Applicant successfully advanced to stage 7. Requirements fulfilled and verified.', '2026-10-20 00:00:00'),
(3, 8, 7, 'Applicant successfully advanced to stage 8. Requirements fulfilled and verified.', '2026-11-01 00:00:00'),
(4, 1, 4, 'Applicant successfully advanced to stage 1. Requirements fulfilled and verified.', '2026-09-03 00:00:00'),
(4, 2, 4, 'Applicant successfully advanced to stage 2. Requirements fulfilled and verified.', '2026-09-12 00:00:00'),
(4, 3, 4, 'Applicant successfully advanced to stage 3. Requirements fulfilled and verified.', '2026-09-21 00:00:00'),
(4, 4, 4, 'Applicant successfully advanced to stage 4. Requirements fulfilled and verified.', '2026-09-28 00:00:00'),
(4, 5, 4, 'Applicant successfully advanced to stage 5. Requirements fulfilled and verified.', '2026-10-05 00:00:00'),
(4, 6, 4, 'Applicant successfully advanced to stage 6. Requirements fulfilled and verified.', '2026-10-16 00:00:00'),
(4, 7, 4, 'Applicant successfully advanced to stage 7. Requirements fulfilled and verified.', '2026-10-23 00:00:00'),
(4, 8, 4, 'Applicant successfully advanced to stage 8. Requirements fulfilled and verified.', '2026-11-01 00:00:00'),
(5, 1, 5, 'Applicant successfully advanced to stage 1. Requirements fulfilled and verified.', '2026-09-05 00:00:00'),
(5, 2, 5, 'Applicant successfully advanced to stage 2. Requirements fulfilled and verified.', '2026-09-14 00:00:00'),
(5, 3, 5, 'Applicant successfully advanced to stage 3. Requirements fulfilled and verified.', '2026-09-18 00:00:00'),
(5, 4, 5, 'Applicant successfully advanced to stage 4. Requirements fulfilled and verified.', '2026-09-28 00:00:00'),
(5, 5, 5, 'Applicant successfully advanced to stage 5. Requirements fulfilled and verified.', '2026-10-08 00:00:00'),
(5, 6, 5, 'Applicant successfully advanced to stage 6. Requirements fulfilled and verified.', '2026-10-15 00:00:00'),
(5, 7, 5, 'Applicant successfully advanced to stage 7. Requirements fulfilled and verified.', '2026-10-24 00:00:00'),
(5, 8, 5, 'Applicant successfully advanced to stage 8. Requirements fulfilled and verified.', '2026-10-31 00:00:00'),
(6, 1, 6, 'Applicant successfully advanced to stage 1. Requirements fulfilled and verified.', '2026-09-04 00:00:00'),
(6, 2, 6, 'Applicant successfully advanced to stage 2. Requirements fulfilled and verified.', '2026-09-10 00:00:00'),
(6, 3, 6, 'Applicant successfully advanced to stage 3. Requirements fulfilled and verified.', '2026-09-18 00:00:00'),
(6, 4, 6, 'Applicant successfully advanced to stage 4. Requirements fulfilled and verified.', '2026-09-26 00:00:00'),
(6, 5, 6, 'Applicant successfully advanced to stage 5. Requirements fulfilled and verified.', '2026-10-06 00:00:00'),
(6, 6, 6, 'Applicant successfully advanced to stage 6. Requirements fulfilled and verified.', '2026-10-14 00:00:00'),
(6, 7, 6, 'Applicant successfully advanced to stage 7. Requirements fulfilled and verified.', '2026-10-22 00:00:00'),
(6, 8, 6, 'Applicant successfully advanced to stage 8. Requirements fulfilled and verified.', '2026-10-31 00:00:00'),
(7, 1, 7, 'Applicant successfully advanced to stage 1. Requirements fulfilled and verified.', '2026-09-05 00:00:00'),
(7, 2, 7, 'Applicant successfully advanced to stage 2. Requirements fulfilled and verified.', '2026-09-12 00:00:00'),
(7, 3, 7, 'Applicant successfully advanced to stage 3. Requirements fulfilled and verified.', '2026-09-21 00:00:00'),
(7, 4, 7, 'Applicant successfully advanced to stage 4. Requirements fulfilled and verified.', '2026-09-27 00:00:00'),
(7, 5, 7, 'Applicant successfully advanced to stage 5. Requirements fulfilled and verified.', '2026-10-05 00:00:00'),
(7, 6, 7, 'Applicant successfully advanced to stage 6. Requirements fulfilled and verified.', '2026-10-12 00:00:00'),
(7, 7, 7, 'Applicant successfully advanced to stage 7. Requirements fulfilled and verified.', '2026-10-23 00:00:00'),
(7, 8, 7, 'Applicant successfully advanced to stage 8. Requirements fulfilled and verified.', '2026-10-31 00:00:00'),
(8, 1, 4, 'Applicant successfully advanced to stage 1. Requirements fulfilled and verified.', '2026-09-03 00:00:00'),
(8, 2, 4, 'Applicant successfully advanced to stage 2. Requirements fulfilled and verified.', '2026-09-13 00:00:00'),
(8, 3, 4, 'Applicant successfully advanced to stage 3. Requirements fulfilled and verified.', '2026-09-22 00:00:00'),
(8, 4, 4, 'Applicant successfully advanced to stage 4. Requirements fulfilled and verified.', '2026-09-27 00:00:00'),
(8, 5, 4, 'Applicant successfully advanced to stage 5. Requirements fulfilled and verified.', '2026-10-08 00:00:00'),
(8, 6, 4, 'Applicant successfully advanced to stage 6. Requirements fulfilled and verified.', '2026-10-13 00:00:00'),
(8, 7, 4, 'Applicant successfully advanced to stage 7. Requirements fulfilled and verified.', '2026-10-23 00:00:00'),
(8, 8, 4, 'Applicant successfully advanced to stage 8. Requirements fulfilled and verified.', '2026-10-30 00:00:00'),
(9, 1, 5, 'Applicant successfully advanced to stage 1. Requirements fulfilled and verified.', '2026-09-06 00:00:00'),
(9, 2, 5, 'Applicant successfully advanced to stage 2. Requirements fulfilled and verified.', '2026-09-10 00:00:00'),
(9, 3, 5, 'Applicant successfully advanced to stage 3. Requirements fulfilled and verified.', '2026-09-21 00:00:00'),
(9, 4, 5, 'Applicant successfully advanced to stage 4. Requirements fulfilled and verified.', '2026-09-29 00:00:00'),
(9, 5, 5, 'Applicant successfully advanced to stage 5. Requirements fulfilled and verified.', '2026-10-08 00:00:00'),
(9, 6, 5, 'Applicant successfully advanced to stage 6. Requirements fulfilled and verified.', '2026-10-15 00:00:00'),
(9, 7, 5, 'Applicant successfully advanced to stage 7. Requirements fulfilled and verified.', '2026-10-24 00:00:00'),
(10, 1, 6, 'Applicant successfully advanced to stage 1. Requirements fulfilled and verified.', '2026-09-05 00:00:00'),
(10, 2, 6, 'Applicant successfully advanced to stage 2. Requirements fulfilled and verified.', '2026-09-14 00:00:00'),
(10, 3, 6, 'Applicant successfully advanced to stage 3. Requirements fulfilled and verified.', '2026-09-20 00:00:00'),
(10, 4, 6, 'Applicant successfully advanced to stage 4. Requirements fulfilled and verified.', '2026-09-30 00:00:00'),
(10, 5, 6, 'Applicant successfully advanced to stage 5. Requirements fulfilled and verified.', '2026-10-04 00:00:00'),
(10, 6, 6, 'Applicant successfully advanced to stage 6. Requirements fulfilled and verified.', '2026-10-12 00:00:00'),
(10, 7, 6, 'Applicant successfully advanced to stage 7. Requirements fulfilled and verified.', '2026-10-23 00:00:00'),
(11, 1, 7, 'Applicant successfully advanced to stage 1. Requirements fulfilled and verified.', '2026-09-04 00:00:00'),
(11, 2, 7, 'Applicant successfully advanced to stage 2. Requirements fulfilled and verified.', '2026-09-12 00:00:00'),
(11, 3, 7, 'Applicant successfully advanced to stage 3. Requirements fulfilled and verified.', '2026-09-18 00:00:00'),
(11, 4, 7, 'Applicant successfully advanced to stage 4. Requirements fulfilled and verified.', '2026-09-28 00:00:00'),
(11, 5, 7, 'Applicant successfully advanced to stage 5. Requirements fulfilled and verified.', '2026-10-07 00:00:00'),
(11, 6, 7, 'Applicant successfully advanced to stage 6. Requirements fulfilled and verified.', '2026-10-15 00:00:00'),
(11, 7, 7, 'Applicant successfully advanced to stage 7. Requirements fulfilled and verified.', '2026-10-21 00:00:00'),
(12, 1, 4, 'Applicant successfully advanced to stage 1. Requirements fulfilled and verified.', '2026-09-06 00:00:00'),
(12, 2, 4, 'Applicant successfully advanced to stage 2. Requirements fulfilled and verified.', '2026-09-14 00:00:00'),
(12, 3, 4, 'Applicant successfully advanced to stage 3. Requirements fulfilled and verified.', '2026-09-21 00:00:00'),
(12, 4, 4, 'Applicant successfully advanced to stage 4. Requirements fulfilled and verified.', '2026-09-29 00:00:00'),
(12, 5, 4, 'Applicant successfully advanced to stage 5. Requirements fulfilled and verified.', '2026-10-05 00:00:00'),
(12, 6, 4, 'Applicant successfully advanced to stage 6. Requirements fulfilled and verified.', '2026-10-12 00:00:00'),
(12, 7, 4, 'Applicant successfully advanced to stage 7. Requirements fulfilled and verified.', '2026-10-22 00:00:00'),
(13, 1, 5, 'Applicant successfully advanced to stage 1. Requirements fulfilled and verified.', '2026-09-04 00:00:00'),
(13, 2, 5, 'Applicant successfully advanced to stage 2. Requirements fulfilled and verified.', '2026-09-12 00:00:00'),
(13, 3, 5, 'Applicant successfully advanced to stage 3. Requirements fulfilled and verified.', '2026-09-18 00:00:00'),
(13, 4, 5, 'Applicant successfully advanced to stage 4. Requirements fulfilled and verified.', '2026-09-29 00:00:00'),
(13, 5, 5, 'Applicant successfully advanced to stage 5. Requirements fulfilled and verified.', '2026-10-07 00:00:00'),
(13, 6, 5, 'Applicant successfully advanced to stage 6. Requirements fulfilled and verified.', '2026-10-15 00:00:00'),
(13, 7, 5, 'Applicant successfully advanced to stage 7. Requirements fulfilled and verified.', '2026-10-20 00:00:00'),
(14, 1, 6, 'Applicant successfully advanced to stage 1. Requirements fulfilled and verified.', '2026-09-02 00:00:00'),
(14, 2, 6, 'Applicant successfully advanced to stage 2. Requirements fulfilled and verified.', '2026-09-12 00:00:00'),
(14, 3, 6, 'Applicant successfully advanced to stage 3. Requirements fulfilled and verified.', '2026-09-19 00:00:00'),
(14, 4, 6, 'Applicant successfully advanced to stage 4. Requirements fulfilled and verified.', '2026-09-29 00:00:00'),
(14, 5, 6, 'Applicant successfully advanced to stage 5. Requirements fulfilled and verified.', '2026-10-04 00:00:00'),
(14, 6, 6, 'Applicant successfully advanced to stage 6. Requirements fulfilled and verified.', '2026-10-13 00:00:00'),
(14, 7, 6, 'Applicant successfully advanced to stage 7. Requirements fulfilled and verified.', '2026-10-20 00:00:00'),
(15, 1, 7, 'Applicant successfully advanced to stage 1. Requirements fulfilled and verified.', '2026-09-03 00:00:00'),
(15, 2, 7, 'Applicant successfully advanced to stage 2. Requirements fulfilled and verified.', '2026-09-14 00:00:00'),
(15, 3, 7, 'Applicant successfully advanced to stage 3. Requirements fulfilled and verified.', '2026-09-18 00:00:00'),
(15, 4, 7, 'Applicant successfully advanced to stage 4. Requirements fulfilled and verified.', '2026-09-29 00:00:00'),
(15, 5, 7, 'Applicant successfully advanced to stage 5. Requirements fulfilled and verified.', '2026-10-06 00:00:00'),
(15, 6, 7, 'Applicant successfully advanced to stage 6. Requirements fulfilled and verified.', '2026-10-14 00:00:00'),
(15, 7, 7, 'Applicant successfully advanced to stage 7. Requirements fulfilled and verified.', '2026-10-23 00:00:00'),
(16, 1, 4, 'Applicant successfully advanced to stage 1. Requirements fulfilled and verified.', '2026-09-02 00:00:00'),
(16, 2, 4, 'Applicant successfully advanced to stage 2. Requirements fulfilled and verified.', '2026-09-10 00:00:00'),
(16, 3, 4, 'Applicant successfully advanced to stage 3. Requirements fulfilled and verified.', '2026-09-19 00:00:00'),
(16, 4, 4, 'Applicant successfully advanced to stage 4. Requirements fulfilled and verified.', '2026-09-29 00:00:00'),
(16, 5, 4, 'Applicant successfully advanced to stage 5. Requirements fulfilled and verified.', '2026-10-05 00:00:00'),
(16, 6, 4, 'Applicant successfully advanced to stage 6. Requirements fulfilled and verified.', '2026-10-16 00:00:00'),
(16, 7, 4, 'Applicant successfully advanced to stage 7. Requirements fulfilled and verified.', '2026-10-24 00:00:00'),
(17, 1, 5, 'Applicant successfully advanced to stage 1. Requirements fulfilled and verified.', '2026-09-03 00:00:00'),
(17, 2, 5, 'Applicant successfully advanced to stage 2. Requirements fulfilled and verified.', '2026-09-13 00:00:00'),
(17, 3, 5, 'Applicant successfully advanced to stage 3. Requirements fulfilled and verified.', '2026-09-21 00:00:00'),
(17, 4, 5, 'Applicant successfully advanced to stage 4. Requirements fulfilled and verified.', '2026-09-30 00:00:00'),
(17, 5, 5, 'Applicant successfully advanced to stage 5. Requirements fulfilled and verified.', '2026-10-07 00:00:00'),
(17, 6, 5, 'Applicant successfully advanced to stage 6. Requirements fulfilled and verified.', '2026-10-15 00:00:00'),
(18, 1, 6, 'Applicant successfully advanced to stage 1. Requirements fulfilled and verified.', '2026-09-04 00:00:00'),
(18, 2, 6, 'Applicant successfully advanced to stage 2. Requirements fulfilled and verified.', '2026-09-10 00:00:00'),
(18, 3, 6, 'Applicant successfully advanced to stage 3. Requirements fulfilled and verified.', '2026-09-19 00:00:00'),
(18, 4, 6, 'Applicant successfully advanced to stage 4. Requirements fulfilled and verified.', '2026-09-26 00:00:00'),
(18, 5, 6, 'Applicant successfully advanced to stage 5. Requirements fulfilled and verified.', '2026-10-07 00:00:00'),
(18, 6, 6, 'Applicant successfully advanced to stage 6. Requirements fulfilled and verified.', '2026-10-13 00:00:00'),
(19, 1, 7, 'Applicant successfully advanced to stage 1. Requirements fulfilled and verified.', '2026-09-02 00:00:00'),
(19, 2, 7, 'Applicant successfully advanced to stage 2. Requirements fulfilled and verified.', '2026-09-10 00:00:00'),
(19, 3, 7, 'Applicant successfully advanced to stage 3. Requirements fulfilled and verified.', '2026-09-19 00:00:00'),
(19, 4, 7, 'Applicant successfully advanced to stage 4. Requirements fulfilled and verified.', '2026-09-29 00:00:00'),
(19, 5, 7, 'Applicant successfully advanced to stage 5. Requirements fulfilled and verified.', '2026-10-06 00:00:00'),
(19, 6, 7, 'Applicant successfully advanced to stage 6. Requirements fulfilled and verified.', '2026-10-16 00:00:00'),
(20, 1, 4, 'Applicant successfully advanced to stage 1. Requirements fulfilled and verified.', '2026-09-02 00:00:00'),
(20, 2, 4, 'Applicant successfully advanced to stage 2. Requirements fulfilled and verified.', '2026-09-11 00:00:00'),
(20, 3, 4, 'Applicant successfully advanced to stage 3. Requirements fulfilled and verified.', '2026-09-22 00:00:00'),
(20, 4, 4, 'Applicant successfully advanced to stage 4. Requirements fulfilled and verified.', '2026-09-30 00:00:00'),
(20, 5, 4, 'Applicant successfully advanced to stage 5. Requirements fulfilled and verified.', '2026-10-05 00:00:00'),
(20, 6, 4, 'Applicant successfully advanced to stage 6. Requirements fulfilled and verified.', '2026-10-15 00:00:00');


INSERT INTO `points_transactions` (`target_type`, `target_id`, `points`, `type`, `category`, `description`, `created_by`, `created_at`) VALUES
('student', 13, 200, 'Earned', 'Early Application Submission', 'Points transaction logged under Early Application Submission.', 1, '2026-10-04 00:00:00'),
('student', 29, 300, 'Earned', 'High School Merit Honor Distinction', 'Points transaction logged under High School Merit Honor Distinction.', 1, '2026-10-31 00:00:00'),
('student', 17, 250, 'Earned', 'Referred Prospective Peer (Application Started)', 'Points transaction logged under Referred Prospective Peer (Application Started).', 1, '2026-09-30 00:00:00'),
('student', 10, 150, 'Earned', 'Complete Document Submission within 7 Days', 'Points transaction logged under Complete Document Submission within 7 Days.', 1, '2026-10-25 00:00:00'),
('student', 11, 500, 'Redeemed', 'Application Fee Waiver Redeemed', 'Points transaction logged under Application Fee Waiver Redeemed.', 1, '2026-10-30 00:00:00'),
('student', 4, 400, 'Redeemed', 'IELTS Prep Masterclass Pass Redeemed', 'Points transaction logged under IELTS Prep Masterclass Pass Redeemed.', 1, '2026-10-26 00:00:00'),
('staff', 6, 500, 'Earned', 'Monthly Counsellor Conversion Target Reached', 'Points transaction logged under Monthly Counsellor Conversion Target Reached.', 1, '2026-09-22 00:00:00'),
('staff', 7, 300, 'Earned', 'Overdue Task Resolution Record', 'Points transaction logged under Overdue Task Resolution Record.', 1, '2026-10-01 00:00:00'),
('staff', 4, 2500, 'Redeemed', 'Staff Performance Bonus Redeemed', 'Points transaction logged under Staff Performance Bonus Redeemed.', 1, '2026-09-26 00:00:00'),
('student', 25, 200, 'Earned', 'Early Application Submission', 'Points transaction logged under Early Application Submission.', 1, '2026-09-18 00:00:00'),
('student', 30, 300, 'Earned', 'High School Merit Honor Distinction', 'Points transaction logged under High School Merit Honor Distinction.', 1, '2026-10-26 00:00:00'),
('student', 14, 250, 'Earned', 'Referred Prospective Peer (Application Started)', 'Points transaction logged under Referred Prospective Peer (Application Started).', 1, '2026-10-22 00:00:00'),
('student', 15, 150, 'Earned', 'Complete Document Submission within 7 Days', 'Points transaction logged under Complete Document Submission within 7 Days.', 1, '2026-09-29 00:00:00'),
('student', 22, 500, 'Redeemed', 'Application Fee Waiver Redeemed', 'Points transaction logged under Application Fee Waiver Redeemed.', 1, '2026-09-25 00:00:00'),
('student', 16, 400, 'Redeemed', 'IELTS Prep Masterclass Pass Redeemed', 'Points transaction logged under IELTS Prep Masterclass Pass Redeemed.', 1, '2026-09-30 00:00:00'),
('staff', 7, 500, 'Earned', 'Monthly Counsellor Conversion Target Reached', 'Points transaction logged under Monthly Counsellor Conversion Target Reached.', 1, '2026-10-20 00:00:00'),
('staff', 4, 300, 'Earned', 'Overdue Task Resolution Record', 'Points transaction logged under Overdue Task Resolution Record.', 1, '2026-10-15 00:00:00'),
('staff', 5, 2500, 'Redeemed', 'Staff Performance Bonus Redeemed', 'Points transaction logged under Staff Performance Bonus Redeemed.', 1, '2026-10-12 00:00:00'),
('student', 15, 200, 'Earned', 'Early Application Submission', 'Points transaction logged under Early Application Submission.', 1, '2026-10-22 00:00:00'),
('student', 1, 300, 'Earned', 'High School Merit Honor Distinction', 'Points transaction logged under High School Merit Honor Distinction.', 1, '2026-09-30 00:00:00'),
('student', 10, 250, 'Earned', 'Referred Prospective Peer (Application Started)', 'Points transaction logged under Referred Prospective Peer (Application Started).', 1, '2026-10-03 00:00:00'),
('student', 21, 150, 'Earned', 'Complete Document Submission within 7 Days', 'Points transaction logged under Complete Document Submission within 7 Days.', 1, '2026-11-01 00:00:00'),
('student', 8, 500, 'Redeemed', 'Application Fee Waiver Redeemed', 'Points transaction logged under Application Fee Waiver Redeemed.', 1, '2026-10-30 00:00:00'),
('student', 23, 400, 'Redeemed', 'IELTS Prep Masterclass Pass Redeemed', 'Points transaction logged under IELTS Prep Masterclass Pass Redeemed.', 1, '2026-09-26 00:00:00'),
('staff', 4, 500, 'Earned', 'Monthly Counsellor Conversion Target Reached', 'Points transaction logged under Monthly Counsellor Conversion Target Reached.', 1, '2026-10-25 00:00:00');


INSERT INTO `notifications` (`user_id`, `title`, `message`, `type`, `link`, `is_read`, `created_at`) VALUES
(NULL, 'New Student Enquiry Registered', 'Applicant Liam Smith registered for B.Sc. Computer Science at Oxford Global University.', 'enquiry', 'students/view.php?id=1', 0, '2026-11-01 21:00:00'),
(5, 'Follow-up Due Today', 'High priority follow-up with Ananya Patel scheduled for 11:30 AM.', 'followup_due', 'followups/index.php?filter=today', 0, '2026-11-01 18:00:00'),
(6, 'Admission Confirmed!', 'Congratulations! Official unconditional offer accepted for Carlos Mendez.', 'admission_confirmed', 'students/view.php?id=5', 0, '2026-11-01 15:00:00'),
(NULL, 'Follow-up Overdue Warning', 'Follow-up for Noah Williams was scheduled 2 days ago and requires immediate action.', 'followup_overdue', 'followups/index.php?filter=overdue', 0, '2026-11-01 12:00:00'),
(4, 'Documents Submitted', 'Elena Rostova verified full transcript packet for student STU-2026-012.', 'stage_updated', 'students/view.php?id=12', 1, '2026-11-01 09:00:00'),
(5, 'New Lead Assigned', '3 new prospective student leads from Dubai Education Fair assigned to your queue.', 'lead_assigned', 'leads/index.php', 0, '2026-11-01 06:00:00'),
(NULL, 'Pending Task Reminder', 'Task \"Verify high school transcripts\" is due by close of business today.', 'task_reminder', 'tasks/index.php?filter=today', 0, '2026-11-01 03:00:00'),
(7, 'Points Milestone Reached', 'Student STU-2026-004 accumulated 1,100 reward points and unlocked Early Bird Scholarship.', 'points', 'points/index.php', 1, '2026-11-01 00:00:00'),
(4, 'New Student Enquiry Registered', 'Applicant Liam Smith registered for B.Sc. Computer Science at Oxford Global University.', 'enquiry', 'students/view.php?id=1', 0, '2026-10-31 21:00:00'),
(NULL, 'Follow-up Due Today', 'High priority follow-up with Ananya Patel scheduled for 11:30 AM.', 'followup_due', 'followups/index.php?filter=today', 0, '2026-10-31 18:00:00'),
(6, 'Admission Confirmed!', 'Congratulations! Official unconditional offer accepted for Carlos Mendez.', 'admission_confirmed', 'students/view.php?id=5', 1, '2026-10-31 15:00:00'),
(7, 'Follow-up Overdue Warning', 'Follow-up for Noah Williams was scheduled 2 days ago and requires immediate action.', 'followup_overdue', 'followups/index.php?filter=overdue', 1, '2026-10-31 12:00:00'),
(NULL, 'Documents Submitted', 'Elena Rostova verified full transcript packet for student STU-2026-012.', 'stage_updated', 'students/view.php?id=12', 1, '2026-10-31 09:00:00'),
(5, 'New Lead Assigned', '3 new prospective student leads from Dubai Education Fair assigned to your queue.', 'lead_assigned', 'leads/index.php', 1, '2026-10-31 06:00:00'),
(6, 'Pending Task Reminder', 'Task \"Verify high school transcripts\" is due by close of business today.', 'task_reminder', 'tasks/index.php?filter=today', 1, '2026-10-31 03:00:00'),
(NULL, 'Points Milestone Reached', 'Student STU-2026-004 accumulated 1,100 reward points and unlocked Early Bird Scholarship.', 'points', 'points/index.php', 1, '2026-10-31 00:00:00');


INSERT INTO `activity_logs` (`user_id`, `student_id`, `action`, `details`, `ip_address`, `created_at`) VALUES
(4, 45, 'Student Registered', 'New student profile STU-2026-001 created via web enquiry.', '127.0.0.1', '2026-11-01 22:00:00'),
(5, 6, 'Lead Converted', 'Lead LEAD-2026-007 converted to active student registration.', '127.0.0.1', '2026-11-01 20:00:00'),
(6, 22, 'Admission Stage Updated', 'Student STU-2026-005 promoted from \"Application Processing\" to \"Admission Confirmed\".', '127.0.0.1', '2026-11-01 18:00:00'),
(7, 47, 'Follow-up Logged', 'Completed 20-minute phone call consultation regarding curriculum and fees.', '127.0.0.1', '2026-11-01 16:00:00'),
(4, 2, 'Task Completed', 'Marked \"Verify high school transcripts\" as Completed.', '127.0.0.1', '2026-11-01 14:00:00'),
(5, 28, 'Points Awarded', 'Awarded 250 points for friend referral to admissions program.', '127.0.0.1', '2026-11-01 12:00:00'),
(6, 11, 'Lead Reassigned', 'Assigned lead LEAD-2026-014 to David Miller.', '127.0.0.1', '2026-11-01 10:00:00'),
(7, 36, 'Student Profile Updated', 'Updated contact information and passport expiry details.', '127.0.0.1', '2026-11-01 08:00:00'),
(4, 9, 'Student Registered', 'New student profile STU-2026-001 created via web enquiry.', '127.0.0.1', '2026-11-01 06:00:00'),
(5, 11, 'Lead Converted', 'Lead LEAD-2026-007 converted to active student registration.', '127.0.0.1', '2026-11-01 04:00:00'),
(6, 4, 'Admission Stage Updated', 'Student STU-2026-005 promoted from \"Application Processing\" to \"Admission Confirmed\".', '127.0.0.1', '2026-11-01 02:00:00'),
(7, 7, 'Follow-up Logged', 'Completed 20-minute phone call consultation regarding curriculum and fees.', '127.0.0.1', '2026-11-01 00:00:00'),
(4, 38, 'Task Completed', 'Marked \"Verify high school transcripts\" as Completed.', '127.0.0.1', '2026-10-31 22:00:00'),
(5, 9, 'Points Awarded', 'Awarded 250 points for friend referral to admissions program.', '127.0.0.1', '2026-10-31 20:00:00'),
(6, 2, 'Lead Reassigned', 'Assigned lead LEAD-2026-014 to David Miller.', '127.0.0.1', '2026-10-31 18:00:00'),
(7, 49, 'Student Profile Updated', 'Updated contact information and passport expiry details.', '127.0.0.1', '2026-10-31 16:00:00'),
(4, 17, 'Student Registered', 'New student profile STU-2026-001 created via web enquiry.', '127.0.0.1', '2026-10-31 14:00:00'),
(5, 4, 'Lead Converted', 'Lead LEAD-2026-007 converted to active student registration.', '127.0.0.1', '2026-10-31 12:00:00'),
(6, 41, 'Admission Stage Updated', 'Student STU-2026-005 promoted from \"Application Processing\" to \"Admission Confirmed\".', '127.0.0.1', '2026-10-31 10:00:00'),
(7, 14, 'Follow-up Logged', 'Completed 20-minute phone call consultation regarding curriculum and fees.', '127.0.0.1', '2026-10-31 08:00:00'),
(4, 41, 'Task Completed', 'Marked \"Verify high school transcripts\" as Completed.', '127.0.0.1', '2026-10-31 06:00:00'),
(5, 45, 'Points Awarded', 'Awarded 250 points for friend referral to admissions program.', '127.0.0.1', '2026-10-31 04:00:00'),
(6, 34, 'Lead Reassigned', 'Assigned lead LEAD-2026-014 to David Miller.', '127.0.0.1', '2026-10-31 02:00:00'),
(7, 23, 'Student Profile Updated', 'Updated contact information and passport expiry details.', '127.0.0.1', '2026-10-31 00:00:00'),
(4, 49, 'Student Registered', 'New student profile STU-2026-001 created via web enquiry.', '127.0.0.1', '2026-10-30 22:00:00'),
(5, 31, 'Lead Converted', 'Lead LEAD-2026-007 converted to active student registration.', '127.0.0.1', '2026-10-30 20:00:00'),
(6, 49, 'Admission Stage Updated', 'Student STU-2026-005 promoted from \"Application Processing\" to \"Admission Confirmed\".', '127.0.0.1', '2026-10-30 18:00:00'),
(7, 38, 'Follow-up Logged', 'Completed 20-minute phone call consultation regarding curriculum and fees.', '127.0.0.1', '2026-10-30 16:00:00'),
(4, 41, 'Task Completed', 'Marked \"Verify high school transcripts\" as Completed.', '127.0.0.1', '2026-10-30 14:00:00'),
(5, 7, 'Points Awarded', 'Awarded 250 points for friend referral to admissions program.', '127.0.0.1', '2026-10-30 12:00:00');

COMMIT;
