<?php
/**
 * Database Generator for University-Level Student CRM Demo
 * Generates database/university_crm_demo.sql with complete schema and realistic demo data.
 */

$sql = <<<SQL
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

SQL;

// Realistic Demo Data Lists
$firstNames = ['Liam', 'Olivia', 'Noah', 'Emma', 'Oliver', 'Ava', 'Elijah', 'Charlotte', 'William', 'Sophia', 'James', 'Amelia', 'Benjamin', 'Isabella', 'Lucas', 'Mia', 'Henry', 'Evelyn', 'Alexander', 'Harper', 'Sebastian', 'Luna', 'Jack', 'Camila', 'Daniel', 'Gianna', 'Matthew', 'Elizabeth', 'Aiden', 'Eleanor', 'Samuel', 'Ella', 'David', 'Abigail', 'Joseph', 'Sofia', 'Carter', 'Avery', 'Owen', 'Scarlett', 'Wyatt', 'Emily', 'John', 'Aria', 'Luke', 'Penelope', 'Asher', 'Chloe', 'Leo', 'Layla'];
$lastNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez', 'Hernandez', 'Lopez', 'Gonzalez', 'Wilson', 'Anderson', 'Thomas', 'Taylor', 'Moore', 'Jackson', 'Martin', 'Lee', 'Perez', 'Thompson', 'White', 'Harris', 'Sanchez', 'Clark', 'Ramirez', 'Lewis', 'Robinson', 'Walker', 'Young', 'Allen', 'King', 'Wright', 'Scott', 'Torres', 'Nguyen', 'Hill', 'Flores', 'Green', 'Adams', 'Nelson', 'Baker', 'Hall', 'Rivera', 'Campbell', 'Mitchell', 'Carter', 'Roberts'];

$countries = [
    ['United Kingdom', 'England', 'London'],
    ['Canada', 'Ontario', 'Toronto'],
    ['Australia', 'Victoria', 'Melbourne'],
    ['United States', 'California', 'San Francisco'],
    ['India', 'Maharashtra', 'Mumbai'],
    ['Singapore', 'Central', 'Singapore'],
    ['Germany', 'Bavaria', 'Munich'],
    ['Ireland', 'Leinster', 'Dublin'],
    ['United Arab Emirates', 'Dubai', 'Dubai'],
    ['Nigeria', 'Lagos', 'Lagos'],
    ['Malaysia', 'Selangor', 'Kuala Lumpur'],
    ['France', 'Ile-de-France', 'Paris'],
    ['Brazil', 'Sao Paulo', 'Sao Paulo'],
    ['Japan', 'Kanto', 'Tokyo'],
    ['South Africa', 'Gauteng', 'Johannesburg'],
];

$qualifications = [
    'High School Diploma (IB/A-Levels)',
    'Bachelor of Science in Computer Science',
    'Bachelor of Business Administration',
    'Higher Secondary Certificate (Grade 12)',
    'Bachelor of Engineering (Mechanical)',
    'Associate Degree in Information Technology',
    'Bachelor of Arts in Economics',
    'B.Sc. in Biotechnology'
];

$intakes = ['Fall 2026', 'Spring 2027', 'Fall 2027', 'Summer 2026'];
$leadSources = ['Website Enquiry', 'Education Fair Dubai', 'Google Ads Campaign', 'Instagram Campaign', 'Agent Referral', 'Direct Walk-in', 'LinkedIn Higher Ed', 'University Webinar'];
$priorities = ['High', 'Moderate', 'Low'];
$staffIds = [4, 5, 6, 7];

// Generate 50 Students
$studentInserts = [];
$now = new DateTime('2026-11-02');

for ($i = 1; $i <= 50; $i++) {
    $code = sprintf('STU-2026-%03d', $i);
    $fn = $firstNames[($i - 1) % count($firstNames)];
    $ln = $lastNames[($i + 3) % count($lastNames)];
    $name = "$fn $ln";
    $cleanFn = strtolower(preg_replace('/[^a-zA-Z]/', '', $fn));
    $cleanLn = strtolower(preg_replace('/[^a-zA-Z]/', '', $ln));
    $email = "{$cleanFn}.{$cleanLn}{$i}@example.com";
    $mob = "+1 (555) " . rand(200, 999) . "-" . rand(1000, 9999);
    $wa = $mob;
    $dobYear = rand(1998, 2007);
    $dob = sprintf('%04d-%02d-%02d', $dobYear, rand(1, 12), rand(1, 28));
    $gender = ($i % 2 == 0) ? 'Female' : 'Male';
    $geo = $countries[($i - 1) % count($countries)];
    $country = $geo[0];
    $state = $geo[1];
    $city = $geo[2];
    $qual = $qualifications[($i - 1) % count($qualifications)];
    
    $uniId = ($i % 8) + 1;
    $courseId = ($i % 10) + 1;
    $intake = $intakes[($i - 1) % count($intakes)];
    
    // Enquiry date between 1 and 120 days ago
    $enqDaysAgo = rand(2, 120);
    $enqDate = (clone $now)->modify("-{$enqDaysAgo} days")->format('Y-m-d');
    
    $source = $leadSources[($i - 1) % count($leadSources)];
    $staff = $staffIds[($i - 1) % count($staffIds)];
    
    // Priority distribution
    if ($i % 3 == 0) $prio = 'High';
    elseif ($i % 3 == 1) $prio = 'Moderate';
    else $prio = 'Low';
    
    // Stage distribution (stages 1 to 8)
    // Make realistic progression:
    if ($i <= 8) {
        $stageId = 8; // Confirmed
        $admStatus = 'Confirmed';
        $pts = rand(800, 1500);
    } elseif ($i <= 16) {
        $stageId = 7; // Application Processing
        $admStatus = 'In Progress';
        $pts = rand(500, 900);
    } elseif ($i <= 24) {
        $stageId = 6; // Documents Submitted
        $admStatus = 'In Progress';
        $pts = rand(400, 750);
    } elseif ($i <= 32) {
        $stageId = 5; // Application Started
        $admStatus = 'In Progress';
        $pts = rand(200, 500);
    } elseif ($i <= 38) {
        $stageId = 4; // Course Selection
        $admStatus = 'In Progress';
        $pts = rand(100, 300);
    } elseif ($i <= 42) {
        $stageId = 3; // Counselling
        $admStatus = 'In Progress';
        $pts = rand(50, 150);
    } elseif ($i <= 46) {
        $stageId = 2; // Initial Contact
        $admStatus = ($i == 45) ? 'On Hold' : 'In Progress';
        $pts = 50;
    } else {
        $stageId = 1; // Enquiry Received
        $admStatus = ($i == 50) ? 'Admission Lost' : 'In Progress';
        $pts = 0;
    }
    
    $notes = addslashes("Prospective student interested in study opportunities for {$intake}. Contacted via {$source}. Initial documentation reviewed.");
    
    $studentInserts[] = "('$code', '$name', '$email', '$mob', '$wa', '$dob', '$gender', '$country', '$state', '$city', '$qual', $uniId, $courseId, NULL, NULL, '$intake', '$enqDate', '$source', $staff, '$prio', $stageId, '$admStatus', $pts, '$notes')";
}

$sql .= "\n\nINSERT INTO `students` (`student_code`, `full_name`, `email`, `mobile_number`, `whatsapp_number`, `date_of_birth`, `gender`, `country`, `state`, `city`, `academic_qualification`, `university_id`, `course_id`, `interested_university_text`, `interested_course_text`, `preferred_intake`, `enquiry_date`, `lead_source`, `assigned_staff_id`, `priority_level`, `stage_id`, `admission_status`, `points_balance`, `additional_notes`) VALUES\n" . implode(",\n", $studentInserts) . ";\n";

// Generate 30 Leads
$leadStatuses = ['New Lead', 'Contacted', 'Interested', 'Not Interested', 'Follow-up Required', 'Application Started', 'Converted', 'Lost'];
$leadInserts = [];
$coursesList = ['Computer Science', 'Data Analytics', 'Global MBA', 'Biomedical Engineering', 'Cybersecurity', 'Civil Engineering', 'Fintech & Blockchain', 'Public Policy'];

for ($i = 1; $i <= 30; $i++) {
    $code = sprintf('LEAD-2026-%03d', $i);
    $fn = $firstNames[($i + 15) % count($firstNames)];
    $ln = $lastNames[($i + 10) % count($lastNames)];
    $name = "$fn $ln";
    $cleanFn = strtolower(preg_replace('/[^a-zA-Z]/', '', $fn));
    $cleanLn = strtolower(preg_replace('/[^a-zA-Z]/', '', $ln));
    $email = "lead.{$cleanFn}.{$cleanLn}{$i}@prospects.org";
    $mob = "+1 (555) " . rand(300, 899) . "-" . rand(1000, 9999);
    $course = $coursesList[($i - 1) % count($coursesList)];
    $geo = $countries[($i + 2) % count($countries)];
    $country = $geo[0];
    $source = $leadSources[($i + 1) % count($leadSources)];
    $status = $leadStatuses[($i - 1) % count($leadStatuses)];
    $prio = $priorities[($i - 1) % count($priorities)];
    $staff = $staffIds[($i - 1) % count($staffIds)];
    $convertedStudentId = ($status === 'Converted') ? rand(1, 15) : 'NULL';
    $notes = addslashes("Inquired about entry criteria and scholarship provisions during {$source}. Requires prompt follow-up.");
    
    $leadInserts[] = "('$code', '$name', '$email', '$mob', '$course', '$country', '$source', '$status', '$prio', $staff, $convertedStudentId, '$notes')";
}

$sql .= "\n\nINSERT INTO `leads` (`lead_code`, `full_name`, `email`, `mobile_number`, `interested_course`, `interested_country`, `lead_source`, `status`, `priority`, `assigned_staff_id`, `converted_student_id`, `notes`) VALUES\n" . implode(",\n", $leadInserts) . ";\n";

// Generate 35 Follow-ups
$commsTypes = ['Phone Call', 'WhatsApp', 'Email', 'Direct Meeting'];
$followupInserts = [];
$todayStr = '2026-11-02';

for ($i = 1; $i <= 35; $i++) {
    $studentId = rand(1, 50);
    $leadId = ($i % 4 == 0) ? rand(1, 30) : 'NULL';
    $staff = $staffIds[($i - 1) % count($staffIds)];
    $comms = $commsTypes[($i - 1) % count($commsTypes)];
    $prio = $priorities[($i - 1) % count($priorities)];
    
    // Status distribution: Today (Pending), Overdue, Upcoming, Completed
    if ($i <= 8) {
        // Today's Follow-ups
        $fDate = $todayStr;
        $fTime = sprintf('%02d:%02d:00', rand(9, 17), rand(0, 59));
        $status = 'Pending';
        $completedAt = 'NULL';
        $nextDate = "'2026-11-09'";
        $resp = addslashes("Scheduled call to discuss visa checklist and scholarship deadline.");
    } elseif ($i <= 16) {
        // Overdue Follow-ups (past dates, still pending)
        $pastDays = rand(1, 7);
        $fDate = (clone $now)->modify("-{$pastDays} days")->format('Y-m-d');
        $fTime = sprintf('%02d:%02d:00', rand(10, 16), rand(0, 59));
        $status = 'Overdue';
        $completedAt = 'NULL';
        $nextDate = "'$todayStr'";
        $resp = addslashes("Attempted contact, phone switched off. Left WhatsApp voice note.");
    } elseif ($i <= 26) {
        // Completed Follow-ups
        $pastDays = rand(2, 20);
        $fDate = (clone $now)->modify("-{$pastDays} days")->format('Y-m-d');
        $fTime = sprintf('%02d:%02d:00', rand(10, 16), rand(0, 59));
        $status = 'Completed';
        $completedAt = "'$fDate $fTime'";
        $nextDate = "'2026-11-10'";
        $resp = addslashes("Detailed 25-minute discussion. Student confirmed readiness to submit IELTS scorecard.");
    } else {
        // Upcoming Follow-ups
        $futDays = rand(1, 10);
        $fDate = (clone $now)->modify("+{$futDays} days")->format('Y-m-d');
        $fTime = sprintf('%02d:%02d:00', rand(9, 16), rand(0, 59));
        $status = 'Pending';
        $completedAt = 'NULL';
        $nextDate = 'NULL';
        $resp = addslashes("Booked direct video meeting to finalize course selection and portfolio review.");
    }
    
    $fNotes = addslashes("Follow-up session #{$i} via {$comms}. Focus on application progression.");
    
    $leadVal = ($leadId === 'NULL') ? 'NULL' : $leadId;
    $followupInserts[] = "($studentId, $leadVal, $staff, '$fDate', '$fTime', '$prio', '$comms', '$fNotes', '$resp', $nextDate, '$status', $completedAt)";
}

$sql .= "\n\nINSERT INTO `followups` (`student_id`, `lead_id`, `assigned_staff_id`, `followup_date`, `followup_time`, `priority`, `communication_type`, `notes`, `student_response`, `next_followup_date`, `status`, `completed_at`) VALUES\n" . implode(",\n", $followupInserts) . ";\n";

// Generate Tasks (25 tasks)
$taskCategories = ['Student Follow-up', 'Document Collection', 'Application Completion', 'Admission Verification', 'General Task'];
$taskTitles = [
    'Verify high school transcripts and certified translations',
    'Follow up on pending financial sponsor affidavit',
    'Submit CAS / I-20 university issuance request',
    'Conduct preliminary mock visa interview',
    'Collect updated English proficiency test scores',
    'Review personal statement and letter of recommendation draft',
    'Schedule virtual appointment with Department Dean',
    'Send conditional admission offer package to student email',
    'Audit scholarship qualification credentials',
    'Upload certified passport bio-page copy to admissions portal'
];

$taskInserts = [];
for ($i = 1; $i <= 25; $i++) {
    $title = $taskTitles[($i - 1) % count($taskTitles)];
    $cat = $taskCategories[($i - 1) % count($taskCategories)];
    $staff = $staffIds[($i - 1) % count($staffIds)];
    $studentId = rand(1, 50);
    $prio = $priorities[($i - 1) % count($priorities)];
    
    if ($i <= 6) {
        $dueDate = $todayStr;
        $status = 'In Progress';
    } elseif ($i <= 12) {
        $dueDate = (clone $now)->modify("-".rand(1, 4)." days")->format('Y-m-d');
        $status = 'Overdue';
    } elseif ($i <= 18) {
        $dueDate = (clone $now)->modify("+".rand(1, 14)." days")->format('Y-m-d');
        $status = 'Pending';
    } else {
        $dueDate = (clone $now)->modify("-".rand(3, 15)." days")->format('Y-m-d');
        $status = 'Completed';
    }
    
    $desc = addslashes("Comprehensive action required for {$title} as part of admissions workflow cycle.");
    $taskInserts[] = "('$title', '$desc', '$cat', $staff, $studentId, '$dueDate', '$prio', '$status')";
}

$sql .= "\n\nINSERT INTO `tasks` (`task_title`, `description`, `category`, `assigned_staff_id`, `student_id`, `due_date`, `priority`, `status`) VALUES\n" . implode(",\n", $taskInserts) . ";\n";

// Generate Stage History for top students
$stageHistoryInserts = [];
for ($sid = 1; $sid <= 20; $sid++) {
    // each student transitioned through several stages
    $maxStage = ($sid <= 8) ? 8 : (($sid <= 16) ? 7 : 6);
    for ($st = 1; $st <= $maxStage; $st++) {
        $daysBack = (8 - $st) * 8 + rand(1, 5);
        $histDate = (clone $now)->modify("-{$daysBack} days")->format('Y-m-d H:i:s');
        $staff = $staffIds[$sid % count($staffIds)];
        $hNote = addslashes("Applicant successfully advanced to stage {$st}. Requirements fulfilled and verified.");
        $stageHistoryInserts[] = "($sid, $st, $staff, '$hNote', '$histDate')";
    }
}
$sql .= "\n\nINSERT INTO `student_stage_history` (`student_id`, `stage_id`, `changed_by_user_id`, `notes`, `created_at`) VALUES\n" . implode(",\n", $stageHistoryInserts) . ";\n";

// Generate Points Transactions (25 entries)
$pointsInserts = [];
$pointReasons = [
    ['Early Application Submission', 200, 'Earned', 'student'],
    ['High School Merit Honor Distinction', 300, 'Earned', 'student'],
    ['Referred Prospective Peer (Application Started)', 250, 'Earned', 'student'],
    ['Complete Document Submission within 7 Days', 150, 'Earned', 'student'],
    ['Application Fee Waiver Redeemed', 500, 'Redeemed', 'student'],
    ['IELTS Prep Masterclass Pass Redeemed', 400, 'Redeemed', 'student'],
    ['Monthly Counsellor Conversion Target Reached', 500, 'Earned', 'staff'],
    ['Overdue Task Resolution Record', 300, 'Earned', 'staff'],
    ['Staff Performance Bonus Redeemed', 2500, 'Redeemed', 'staff']
];

for ($i = 1; $i <= 25; $i++) {
    $pr = $pointReasons[($i - 1) % count($pointReasons)];
    $targetType = $pr[3];
    $targetId = ($targetType === 'student') ? rand(1, 30) : $staffIds[($i - 1) % count($staffIds)];
    $pts = $pr[1];
    $type = $pr[2];
    $cat = $pr[0];
    $desc = addslashes("Points transaction logged under {$cat}.");
    $daysAgo = rand(1, 45);
    $ptDate = (clone $now)->modify("-{$daysAgo} days")->format('Y-m-d H:i:s');
    $pointsInserts[] = "('$targetType', $targetId, $pts, '$type', '$cat', '$desc', 1, '$ptDate')";
}
$sql .= "\n\nINSERT INTO `points_transactions` (`target_type`, `target_id`, `points`, `type`, `category`, `description`, `created_by`, `created_at`) VALUES\n" . implode(",\n", $pointsInserts) . ";\n";

// Generate Notifications (15 notifications)
$notifTemplates = [
    ['New Student Enquiry Registered', 'Applicant Liam Smith registered for B.Sc. Computer Science at Oxford Global University.', 'enquiry', 'students/view.php?id=1', 0],
    ['Follow-up Due Today', 'High priority follow-up with Ananya Patel scheduled for 11:30 AM.', 'followup_due', 'followups/index.php?filter=today', 0],
    ['Admission Confirmed!', 'Congratulations! Official unconditional offer accepted for Carlos Mendez.', 'admission_confirmed', 'students/view.php?id=5', 0],
    ['Follow-up Overdue Warning', 'Follow-up for Noah Williams was scheduled 2 days ago and requires immediate action.', 'followup_overdue', 'followups/index.php?filter=overdue', 0],
    ['Documents Submitted', 'Elena Rostova verified full transcript packet for student STU-2026-012.', 'stage_updated', 'students/view.php?id=12', 1],
    ['New Lead Assigned', '3 new prospective student leads from Dubai Education Fair assigned to your queue.', 'lead_assigned', 'leads/index.php', 0],
    ['Pending Task Reminder', 'Task "Verify high school transcripts" is due by close of business today.', 'task_reminder', 'tasks/index.php?filter=today', 0],
    ['Points Milestone Reached', 'Student STU-2026-004 accumulated 1,100 reward points and unlocked Early Bird Scholarship.', 'points', 'points/index.php', 1],
];

$notifInserts = [];
for ($i = 0; $i < 16; $i++) {
    $tpl = $notifTemplates[$i % count($notifTemplates)];
    $uid = ($i % 3 == 0) ? 'NULL' : $staffIds[$i % count($staffIds)];
    $title = addslashes($tpl[0]);
    $msg = addslashes($tpl[1]);
    $type = $tpl[2];
    $link = $tpl[3];
    $isRead = ($i >= 10) ? 1 : $tpl[4];
    $hoursAgo = ($i + 1) * 3;
    $nDate = (clone $now)->modify("-{$hoursAgo} hours")->format('Y-m-d H:i:s');
    $notifInserts[] = "($uid, '$title', '$msg', '$type', '$link', $isRead, '$nDate')";
}
$sql .= "\n\nINSERT INTO `notifications` (`user_id`, `title`, `message`, `type`, `link`, `is_read`, `created_at`) VALUES\n" . implode(",\n", $notifInserts) . ";\n";

// Generate Activity Logs (30 records)
$activityTemplates = [
    ['Student Registered', 'New student profile STU-2026-001 created via web enquiry.'],
    ['Lead Converted', 'Lead LEAD-2026-007 converted to active student registration.'],
    ['Admission Stage Updated', 'Student STU-2026-005 promoted from "Application Processing" to "Admission Confirmed".'],
    ['Follow-up Logged', 'Completed 20-minute phone call consultation regarding curriculum and fees.'],
    ['Task Completed', 'Marked "Verify high school transcripts" as Completed.'],
    ['Points Awarded', 'Awarded 250 points for friend referral to admissions program.'],
    ['Lead Reassigned', 'Assigned lead LEAD-2026-014 to David Miller.'],
    ['Student Profile Updated', 'Updated contact information and passport expiry details.'],
];

$actInserts = [];
for ($i = 1; $i <= 30; $i++) {
    $act = $activityTemplates[($i - 1) % count($activityTemplates)];
    $uId = $staffIds[($i - 1) % count($staffIds)];
    $sId = rand(1, 50);
    $action = addslashes($act[0]);
    $details = addslashes($act[1]);
    $hoursAgo = ($i * 2);
    $aDate = (clone $now)->modify("-{$hoursAgo} hours")->format('Y-m-d H:i:s');
    $actInserts[] = "($uId, $sId, '$action', '$details', '127.0.0.1', '$aDate')";
}
$sql .= "\n\nINSERT INTO `activity_logs` (`user_id`, `student_id`, `action`, `details`, `ip_address`, `created_at`) VALUES\n" . implode(",\n", $actInserts) . ";\n";

$sql .= "\nCOMMIT;\n";

file_put_contents(__DIR__ . '/university_crm_demo.sql', $sql);
echo "Successfully generated database/university_crm_demo.sql (" . strlen($sql) . " bytes)\n";
