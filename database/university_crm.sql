-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: university_crm_demo
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `lead_id` int(11) DEFAULT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `details` text NOT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_lead` (`lead_id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` VALUES (1,1,NULL,NULL,'System Initialized','Database cleaned and prepared for live production use.',NULL,'2026-11-07 11:49:30');
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admission_stages`
--

DROP TABLE IF EXISTS `admission_stages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admission_stages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `step_number` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `color_code` varchar(20) DEFAULT '#5955D1',
  `color` varchar(20) DEFAULT '#5955D1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `step_number` (`step_number`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admission_stages`
--

LOCK TABLES `admission_stages` WRITE;
/*!40000 ALTER TABLE `admission_stages` DISABLE KEYS */;
INSERT INTO `admission_stages` VALUES (1,1,'ENQUIRY','enquiry','Fresh lead created, initial outreach, course brochure dispatch pending.','#5955D1','#5955D1'),(2,2,'CUSTOMER','customer','Student responded, genuine interest established, active counseling underway.','#009966','#5955D1'),(3,3,'APPLICATION','application','Passport and academic transcripts submitted, SOP and university application initiated/submitted.','#2563EB','#5955D1'),(4,4,'OFFER RECEIVED','offer_received','Conditional or unconditional university offer received.','#F5A70D','#5955D1'),(5,5,'TUITION FEES PAID','fees_paid','Initial tuition deposit or CAS processing fee paid.','#7008E7','#5955D1'),(6,6,'ENROLLED','enrolled','Visa issued, departure scheduled, university enrollment confirmed.','#009966','#5955D1');
/*!40000 ALTER TABLE `admission_stages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `benefits`
--

DROP TABLE IF EXISTS `benefits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `benefits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(150) NOT NULL,
  `points_required` int(11) NOT NULL,
  `applicable_for` enum('student','staff','both') DEFAULT 'both',
  `description` text DEFAULT NULL,
  `terms` text DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `redemption_count` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `benefits`
--

LOCK TABLES `benefits` WRITE;
/*!40000 ALTER TABLE `benefits` DISABLE KEYS */;
INSERT INTO `benefits` VALUES (1,'Application Fee Waiver ($100 USD)',500,'student','Direct waiver credited towards international application portal processing fee.','Applicable once per applicant for partner universities.','Active',14,'2026-11-02 06:25:36'),(2,'Early Bird Scholarship Voucher ($500)',1000,'student','Tuition grant awarded for applicants completing stage 6 documents prior to priority deadline.','Valid for full-time degree programs.','Active',6,'2026-11-02 06:25:36'),(3,'Free IELTS / TOEFL Prep Masterclass',400,'student','Comprehensive 4-week online coaching package including 5 graded mock tests.','Login credentials dispatched within 24 hours of redemption.','Active',22,'2026-11-02 06:25:36'),(4,'Complimentary Airport Pickup & Welcome Pack',300,'student','Chauffeured arrival pickup at international destination airport + local SIM kit.','Must submit travel itinerary 14 days in advance.','Active',8,'2026-11-02 06:25:36'),(5,'Fast-Track Visa Document Audit Service',750,'student','Priority one-on-one audit by certified international student immigration counselor.','Covers financial, academic, and statement reviews.','Active',11,'2026-11-02 06:25:36'),(6,'Staff Excellence Incentive ($250 Bonus)',2500,'staff','Quarterly performance reward credited to staff payroll for exceeding conversion milestones.','Approved by Admissions Manager.','Active',4,'2026-11-02 06:25:36'),(7,'Staff Professional Development Allowance ($150)',1500,'staff','Reimbursement grant for education fairs, CRM workshops, and seminars.','Proof of attendance receipt required.','Active',3,'2026-11-02 06:25:36');
/*!40000 ALTER TABLE `benefits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `branches`
--

DROP TABLE IF EXISTS `branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `branches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) NOT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) NOT NULL DEFAULT 'Kerala',
  `country` varchar(100) NOT NULL DEFAULT 'India',
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `manager_id` int(11) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branches`
--

LOCK TABLES `branches` WRITE;
/*!40000 ALTER TABLE `branches` DISABLE KEYS */;
INSERT INTO `branches` VALUES (1,'Kochi','KOC','Kochi','Kerala','India','+91 484 288 9001','kochi@edroots.in','Edroots Tower, MG Road, Kochi',2,'Active','2026-11-03 11:11:24','2026-11-07 12:06:53'),(2,'Perinthalmanna','PMNA','Perinthalmanna','Kerala','India','+91 493 322 4333','pmna@edroots.in','3rd floor, Al Hazmi Tower, Behind SBT, Pattambi Road, Perinthalmanna',2,'Active','2026-11-03 11:11:24','2026-11-07 12:06:53'),(3,'Calicut','CLT','Calicut','Kerala','India','+91 495 277 8001','calicut@edroots.in','Skyline Plaza, Mavoor Road, Calicut',2,'Active','2026-11-03 11:11:24','2026-11-07 12:06:53'),(4,'Kannur','KNR','Kannur','Kerala','India','+91 497 270 7001','kannur@edroots.in','Fort Road Commercial Centre, Kannur',2,'Active','2026-11-03 11:11:24','2026-11-07 12:06:53'),(5,'Mangalore','MNG','Mangalore','Karnataka','India','+91 824 244 2001','mangalore@edroots.in','KSR Road, Hampankatta, Mangalore',2,'Active','2026-11-03 11:11:24','2026-11-07 12:06:53'),(6,'Kasaragod','KSD','Kasaragod','Kerala','India','+91 499 423 6001','kasaragod@edroots.in','Bank Road Junction, Kasaragod',2,'Active','2026-11-03 11:11:24','2026-11-07 12:06:53'),(7,'Kanhangad','KHD','Kanhangad','Kerala','India','+91 467 220 5001','kanhangad@edroots.in','Main Street Mall, Kanhangad',2,'Active','2026-11-03 11:11:24','2026-11-07 12:06:53'),(8,'London','LDN','London','Greater London','United Kingdom','+44 20 7946 0991','london@edroots.in','120 Fenchurch Street, London EC3M 5BA',2,'Active','2026-11-03 11:11:24','2026-11-07 12:06:53'),(9,'Trivandrum','TVM','Thiruvananthapuram','Kerala','India','+91 471 233 3001','trivandrum@edroots.in','Statue Junction, MG Road, Thiruvananthapuram',2,'Active','2026-11-03 11:11:24','2026-11-07 12:06:53');
/*!40000 ALTER TABLE `branches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `college_data`
--

DROP TABLE IF EXISTS `college_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `college_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_name` varchar(150) NOT NULL,
  `mobile_number` varchar(50) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `phone_normalized` varchar(20) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `college_name` varchar(200) NOT NULL,
  `degree` varchar(100) DEFAULT NULL,
  `stream` varchar(100) DEFAULT NULL,
  `passout_year` int(11) DEFAULT NULL,
  `graduation_year` int(11) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `data_source` varchar(100) DEFAULT 'Campus Seminar',
  `import_batch_id` int(11) DEFAULT NULL,
  `import_id` int(11) DEFAULT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `is_converted` enum('NO','YES') DEFAULT 'NO',
  `converted_to_lead` tinyint(1) DEFAULT 0,
  `converted_lead_id` int(11) DEFAULT NULL,
  `lead_id` int(11) DEFAULT NULL,
  `converted_by` int(11) DEFAULT NULL,
  `converted_at` datetime DEFAULT NULL,
  `is_archived` tinyint(1) DEFAULT 0,
  `archived_at` datetime DEFAULT NULL,
  `archived_by` int(11) DEFAULT NULL,
  `archive_reason` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_phone` (`phone_normalized`),
  KEY `idx_converted` (`is_converted`),
  KEY `idx_archived` (`is_archived`),
  KEY `idx_branch` (`branch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `college_data`
--

LOCK TABLES `college_data` WRITE;
/*!40000 ALTER TABLE `college_data` DISABLE KEYS */;
/*!40000 ALTER TABLE `college_data` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `college_data_imports`
--

DROP TABLE IF EXISTS `college_data_imports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `college_data_imports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `total_rows` int(11) DEFAULT 0,
  `imported_rows` int(11) DEFAULT 0,
  `duplicate_rows` int(11) DEFAULT 0,
  `failed_rows` int(11) DEFAULT 0,
  `imported_by` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `college_data_imports`
--

LOCK TABLES `college_data_imports` WRITE;
/*!40000 ALTER TABLE `college_data_imports` DISABLE KEYS */;
/*!40000 ALTER TABLE `college_data_imports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `companies`
--

DROP TABLE IF EXISTS `companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `companies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `website` varchar(150) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `companies`
--

LOCK TABLES `companies` WRITE;
/*!40000 ALTER TABLE `companies` DISABLE KEYS */;
INSERT INTO `companies` VALUES (1,'Edroots International','info@edroots.in','+91 4933-224333','https://edroots.in','3rd floor, Al Hazmi Tower, Behind SBT, Pattambi Road, Perinthalmanna',NULL,'2026-11-03 11:11:24');
/*!40000 ALTER TABLE `companies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `courses`
--

DROP TABLE IF EXISTS `courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `courses`
--

LOCK TABLES `courses` WRITE;
/*!40000 ALTER TABLE `courses` DISABLE KEYS */;
INSERT INTO `courses` VALUES (1,1,'B.Sc. in Computer Science','Undergraduate','3 Years','$34,000 / year'),(2,1,'Master of Business Administration (MBA)','Postgraduate','1 Year','$48,000 / year'),(3,2,'M.Sc. in Data Science & Machine Learning','Postgraduate','2 Years','$39,500 / year'),(4,2,'B.Eng. in Robotics & Mechatronics','Undergraduate','4 Years','$36,000 / year'),(5,3,'Master of International Relations','Postgraduate','2 Years','$31,000 / year'),(6,3,'B.Sc. in Biomedical Science','Undergraduate','3 Years','$33,000 / year'),(7,4,'M.Sc. in Cybersecurity & Cloud Systems','Postgraduate','1.5 Years','$35,000 / year'),(8,5,'M.Sc. in Automotive & Aerospace Engineering','Postgraduate','2 Years','├ö├⌐┬╝1,500 / sem'),(9,6,'B.S. in Electrical Engineering & Computer Science','Undergraduate','4 Years','$44,000 / year'),(10,7,'Master of Public Health (MPH)','Postgraduate','1 Year','├ö├⌐┬╝22,000 / year');
/*!40000 ALTER TABLE `courses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `deletion_logs`
--

DROP TABLE IF EXISTS `deletion_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `deletion_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `record_type` varchar(50) NOT NULL,
  `record_id` int(11) NOT NULL,
  `record_identifier` varchar(150) DEFAULT NULL,
  `justification` text NOT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `deletion_logs`
--

LOCK TABLES `deletion_logs` WRITE;
/*!40000 ALTER TABLE `deletion_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `deletion_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `followups`
--

DROP TABLE IF EXISTS `followups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `followups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lead_id` int(11) NOT NULL,
  `followup_date` date NOT NULL,
  `scheduled_date` date DEFAULT NULL,
  `scheduled_time` time DEFAULT '10:00:00',
  `status` enum('Pending','Completed','Overdue','Cancelled') DEFAULT 'Completed',
  `completed_at` datetime DEFAULT NULL,
  `completed_by` int(11) DEFAULT NULL,
  `followup_time` time NOT NULL,
  `interaction_title_id` int(11) NOT NULL,
  `interaction_type` varchar(50) DEFAULT 'Phone Call',
  `duration_days_allowed` int(11) DEFAULT 7,
  `notes` text NOT NULL,
  `document_attachment` varchar(255) DEFAULT NULL,
  `stage_id` int(11) NOT NULL,
  `target_intake` varchar(100) DEFAULT NULL,
  `next_followup_date` date NOT NULL,
  `counselor_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_lead` (`lead_id`),
  KEY `idx_counselor` (`counselor_id`),
  KEY `idx_fu_date` (`followup_date`),
  KEY `idx_next_date` (`next_followup_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `followups`
--

LOCK TABLES `followups` WRITE;
/*!40000 ALTER TABLE `followups` DISABLE KEYS */;
/*!40000 ALTER TABLE `followups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `general_documents`
--

DROP TABLE IF EXISTS `general_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `general_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `category` varchar(100) NOT NULL,
  `country` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_size` int(11) DEFAULT 0,
  `uploaded_by` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `general_documents`
--

LOCK TABLES `general_documents` WRITE;
/*!40000 ALTER TABLE `general_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `general_documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `intake_cycles`
--

DROP TABLE IF EXISTS `intake_cycles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `intake_cycles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `year` int(11) NOT NULL,
  `term` varchar(50) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `status` varchar(20) DEFAULT 'Active',
  `sort_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `intake_cycles`
--

LOCK TABLES `intake_cycles` WRITE;
/*!40000 ALTER TABLE `intake_cycles` DISABLE KEYS */;
INSERT INTO `intake_cycles` VALUES (1,'January 2026',2026,'January',1,'Active',1),(2,'May 2026',2026,'May',1,'Active',2),(3,'September 2026',2026,'September',1,'Active',3),(4,'January 2027',2027,'January',1,'Active',4),(5,'May 2027',2027,'May',1,'Active',5),(6,'September 2027',2027,'September',1,'Active',6);
/*!40000 ALTER TABLE `intake_cycles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `interaction_titles`
--

DROP TABLE IF EXISTS `interaction_titles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `interaction_titles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `max_duration_days` int(11) NOT NULL DEFAULT 7,
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `status` varchar(20) DEFAULT 'Active',
  `sort_order` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `title` (`title`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `interaction_titles`
--

LOCK TABLES `interaction_titles` WRITE;
/*!40000 ALTER TABLE `interaction_titles` DISABLE KEYS */;
INSERT INTO `interaction_titles` VALUES (1,'Call Not Connected',2,'Candidate unreachable or call disconnected; rapid re-attempt required.',1,'Active',1,'2026-11-03 11:11:24'),(2,'Information Shared',5,'Shared university brochures, fee structure, and eligibility requirements.',1,'Active',2,'2026-11-03 11:11:24'),(3,'Documents Pending',7,'Awaiting academic transcripts, passport copy, or English score card.',1,'Active',3,'2026-11-03 11:11:24'),(4,'Application Initiated',10,'SOP drafting in progress or application portal submitted.',1,'Active',4,'2026-11-03 11:11:24'),(5,'Visa Filing',14,'CAS received; financial sponsorship verification and visa filing underway.',1,'Active',5,'2026-11-03 11:11:24'),(6,'Need to Archive',1,'Candidate postponed intake, disqualified, or unresponsive.',1,'Active',6,'2026-11-03 11:11:24');
/*!40000 ALTER TABLE `interaction_titles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoice_items`
--

DROP TABLE IF EXISTS `invoice_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoice_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(11) NOT NULL,
  `service_description` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_rate` decimal(12,2) NOT NULL DEFAULT 0.00,
  `unit_price` decimal(12,2) DEFAULT 0.00,
  `discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(12,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `idx_invoice` (`invoice_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoice_items`
--

LOCK TABLES `invoice_items` WRITE;
/*!40000 ALTER TABLE `invoice_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `invoice_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoices`
--

DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(50) NOT NULL,
  `lead_id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `invoice_date` date NOT NULL,
  `issue_date` date DEFAULT NULL,
  `due_date` date NOT NULL,
  `billing_address` text DEFAULT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(12,2) DEFAULT 0.00,
  `tax_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax_percentage` decimal(5,2) DEFAULT 18.00,
  `tax_amount` decimal(12,2) DEFAULT 0.00,
  `grand_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `paid_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(12,2) DEFAULT 0.00,
  `balance_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `balance_due` decimal(12,2) DEFAULT 0.00,
  `payment_status` enum('Unpaid','Partially Paid','Paid') DEFAULT 'Unpaid',
  `status` enum('Draft','Active','Paid','Cancelled') DEFAULT 'Active',
  `notes` text DEFAULT NULL,
  `payment_terms` varchar(255) DEFAULT 'Due within 15 days of issue.',
  `bank_details` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_number` (`invoice_number`),
  KEY `idx_lead` (`lead_id`),
  KEY `idx_branch` (`branch_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoices`
--

LOCK TABLES `invoices` WRITE;
/*!40000 ALTER TABLE `invoices` DISABLE KEYS */;
/*!40000 ALTER TABLE `invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lead_stage_history`
--

DROP TABLE IF EXISTS `lead_stage_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lead_stage_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lead_id` int(11) NOT NULL,
  `previous_stage_id` int(11) DEFAULT NULL,
  `stage_id` int(11) NOT NULL,
  `secondary_status` varchar(50) DEFAULT NULL,
  `changed_by` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_lead` (`lead_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lead_stage_history`
--

LOCK TABLES `lead_stage_history` WRITE;
/*!40000 ALTER TABLE `lead_stage_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `lead_stage_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leads`
--

DROP TABLE IF EXISTS `leads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lead_code` varchar(30) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `mobile_number` varchar(50) NOT NULL,
  `phone_normalized` varchar(20) NOT NULL,
  `alternate_phone` varchar(50) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT 'Other',
  `email` varchar(150) DEFAULT NULL,
  `permanent_address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'India',
  `branch_id` int(11) NOT NULL,
  `assigned_counselor_id` int(11) DEFAULT NULL,
  `highest_qualification` varchar(100) DEFAULT NULL,
  `passing_college` varchar(200) DEFAULT NULL,
  `college_name` varchar(200) DEFAULT NULL,
  `passout_year` int(11) DEFAULT NULL,
  `year_of_passing` int(11) DEFAULT NULL,
  `percentage` decimal(5,2) DEFAULT NULL,
  `backlogs_count` int(11) DEFAULT 0,
  `has_passport` enum('Yes','No','Applied') DEFAULT 'No',
  `passport_number` varchar(50) DEFAULT NULL,
  `passport_expiry` date DEFAULT NULL,
  `pan_number` varchar(30) DEFAULT NULL,
  `has_ielts` enum('Yes','No','Awaiting Result','Not Attempted') DEFAULT 'No',
  `has_pte` enum('Yes','No','Awaiting Result','Not Attempted') DEFAULT 'No',
  `has_toefl` enum('Yes','No','Awaiting Result','Not Attempted') DEFAULT 'No',
  `english_test` varchar(50) DEFAULT 'None',
  `english_score` varchar(50) DEFAULT NULL,
  `score_listening` decimal(4,1) DEFAULT NULL,
  `score_reading` decimal(4,1) DEFAULT NULL,
  `score_writing` decimal(4,1) DEFAULT NULL,
  `score_speaking` decimal(4,1) DEFAULT NULL,
  `work_experience_years` decimal(4,1) DEFAULT 0.0,
  `notes` text DEFAULT NULL,
  `ielts_overall` decimal(3,1) DEFAULT NULL,
  `ielts_listening` decimal(3,1) DEFAULT NULL,
  `ielts_reading` decimal(3,1) DEFAULT NULL,
  `ielts_writing` decimal(3,1) DEFAULT NULL,
  `ielts_speaking` decimal(3,1) DEFAULT NULL,
  `pte_overall` int(11) DEFAULT NULL,
  `pte_listening` int(11) DEFAULT NULL,
  `pte_reading` int(11) DEFAULT NULL,
  `pte_writing` int(11) DEFAULT NULL,
  `pte_speaking` int(11) DEFAULT NULL,
  `target_country` varchar(100) DEFAULT NULL,
  `preferred_country` varchar(100) DEFAULT NULL,
  `target_university` varchar(200) DEFAULT NULL,
  `target_course` varchar(200) DEFAULT NULL,
  `preferred_course` varchar(200) DEFAULT NULL,
  `target_intake_id` int(11) DEFAULT NULL,
  `target_intake_text` varchar(100) DEFAULT NULL,
  `intake_year` int(11) DEFAULT 2026,
  `intake_term` varchar(50) DEFAULT 'September',
  `prospectivity` enum('High','Moderate','Low') DEFAULT 'Moderate',
  `priority` enum('High','Moderate','Low') DEFAULT 'Moderate',
  `enquiry_type` enum('WALK IN','TELE','TELE-WALK IN') DEFAULT 'TELE',
  `lead_source` varchar(100) DEFAULT 'Direct Inquiry',
  `source` varchar(100) DEFAULT 'Direct Inquiry',
  `stage_id` int(11) NOT NULL DEFAULT 1,
  `stage` varchar(50) DEFAULT 'ENQUIRY',
  `secondary_status` enum('Active','On Hold','Rejected','Lost') DEFAULT 'Active',
  `is_customer` tinyint(1) DEFAULT 0,
  `customer_code` varchar(30) DEFAULT NULL,
  `converted_to_customer_at` datetime DEFAULT NULL,
  `invoiced_total` decimal(12,2) DEFAULT 0.00,
  `paid_total` decimal(12,2) DEFAULT 0.00,
  `balance_total` decimal(12,2) DEFAULT 0.00,
  `college_data_id` int(11) DEFAULT NULL,
  `web_lead_id` int(11) DEFAULT NULL,
  `last_followup_at` datetime DEFAULT NULL,
  `next_followup_date` date DEFAULT NULL,
  `followup_count` int(11) DEFAULT 0,
  `last_followup_date` date DEFAULT NULL,
  `assigned_at` datetime DEFAULT current_timestamp(),
  `first_followup_logged` tinyint(1) DEFAULT 0,
  `is_archived` tinyint(1) DEFAULT 0,
  `archived_at` datetime DEFAULT NULL,
  `archived_by` int(11) DEFAULT NULL,
  `archive_reason` varchar(255) DEFAULT NULL,
  `archived_reason` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `lead_code` (`lead_code`),
  KEY `idx_phone` (`phone_normalized`),
  KEY `idx_branch` (`branch_id`),
  KEY `idx_counselor` (`assigned_counselor_id`),
  KEY `idx_stage` (`stage_id`),
  KEY `idx_customer` (`is_customer`),
  KEY `idx_archived` (`is_archived`),
  KEY `idx_next_fu` (`next_followup_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leads`
--

LOCK TABLES `leads` WRITE;
/*!40000 ALTER TABLE `leads` DISABLE KEYS */;
/*!40000 ALTER TABLE `leads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'info',
  `link` varchar(255) DEFAULT NULL,
  `status` enum('Not Attended','Attended') DEFAULT 'Not Attended',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `receipt_number` varchar(50) DEFAULT NULL,
  `invoice_id` int(11) NOT NULL,
  `lead_id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `payment_date` date NOT NULL,
  `amount_paid` decimal(12,2) NOT NULL,
  `amount` decimal(12,2) DEFAULT 0.00,
  `payment_mode` enum('Cash','Bank Transfer','Online / UPI') NOT NULL,
  `payment_method` varchar(50) DEFAULT 'Bank Transfer',
  `transaction_reference` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `received_by` int(11) NOT NULL,
  `recorded_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_invoice` (`invoice_id`),
  KEY `idx_lead` (`lead_id`),
  KEY `idx_branch` (`branch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `points_transactions`
--

DROP TABLE IF EXISTS `points_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `points_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `target_type` enum('student','staff') NOT NULL,
  `target_id` int(11) NOT NULL,
  `points` int(11) NOT NULL,
  `type` enum('Earned','Redeemed') NOT NULL,
  `category` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_pt_users` (`created_by`),
  CONSTRAINT `fk_pt_users` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `points_transactions`
--

LOCK TABLES `points_transactions` WRITE;
/*!40000 ALTER TABLE `points_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `points_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_permissions`
--

DROP TABLE IF EXISTS `role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `module` varchar(50) NOT NULL,
  `can_view` tinyint(1) DEFAULT 0,
  `can_add` tinyint(1) DEFAULT 0,
  `can_edit` tinyint(1) DEFAULT 0,
  `can_delete` tinyint(1) DEFAULT 0,
  `can_assign` tinyint(1) DEFAULT 0,
  `can_reassign` tinyint(1) DEFAULT 0,
  `can_approve` tinyint(1) DEFAULT 0,
  `can_export` tinyint(1) DEFAULT 0,
  `can_archive` tinyint(1) DEFAULT 0,
  `can_restore` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_role_module` (`role_id`,`module`)
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_permissions`
--

LOCK TABLES `role_permissions` WRITE;
/*!40000 ALTER TABLE `role_permissions` DISABLE KEYS */;
INSERT INTO `role_permissions` VALUES (1,1,'dashboard',1,1,1,1,1,1,1,1,1,1),(2,2,'dashboard',1,1,1,0,0,0,0,0,0,0),(3,3,'dashboard',1,0,0,0,0,0,0,0,0,0),(4,1,'college_data',1,1,1,1,1,1,1,1,1,1),(5,2,'college_data',1,1,1,0,1,0,0,1,1,1),(6,3,'college_data',0,0,0,0,0,0,0,0,0,0),(7,1,'web_leads',1,1,1,1,1,1,1,1,1,1),(8,2,'web_leads',1,1,1,1,1,0,0,0,0,0),(9,3,'web_leads',0,0,0,0,0,0,0,0,0,0),(10,1,'leads',1,1,1,1,1,1,1,1,1,1),(11,2,'leads',1,1,1,0,1,1,1,1,1,1),(12,3,'leads',1,1,1,0,0,0,0,0,0,0),(13,1,'customers',1,1,1,1,1,1,1,1,1,1),(14,2,'customers',1,1,1,0,0,0,0,1,1,1),(15,3,'customers',1,0,0,0,0,0,0,0,0,0),(16,1,'admissions',1,1,1,1,1,1,1,1,1,1),(17,2,'admissions',1,1,1,0,0,0,1,0,0,0),(18,3,'admissions',1,0,1,0,0,0,0,0,0,0),(19,1,'tasks',1,1,1,1,1,1,1,1,1,1),(20,2,'tasks',1,1,1,0,1,1,0,0,0,0),(21,3,'tasks',1,1,1,0,0,0,0,0,0,0),(22,1,'invoices',1,1,1,1,1,1,1,1,1,1),(23,2,'invoices',1,1,1,0,0,0,1,1,0,0),(24,3,'invoices',0,0,0,0,0,0,0,0,0,0),(25,1,'payments',1,1,1,1,1,1,1,1,1,1),(26,2,'payments',1,1,1,0,0,0,0,0,0,0),(27,3,'payments',0,0,0,0,0,0,0,0,0,0),(28,1,'documents',1,1,1,1,1,1,1,1,1,1),(29,2,'documents',1,1,1,0,0,0,0,0,0,0),(30,3,'documents',1,1,1,0,0,0,0,0,0,0),(31,1,'branches',1,1,1,1,1,1,1,1,1,1),(32,2,'branches',0,1,1,0,0,0,0,0,0,0),(33,3,'branches',0,0,0,0,0,0,0,0,0,0),(34,1,'staff',1,1,1,1,1,1,1,1,1,1),(35,2,'staff',1,1,1,0,0,0,0,0,0,0),(36,3,'staff',0,0,0,0,0,0,0,0,0,0),(37,1,'roles',1,1,1,1,1,1,1,1,1,1),(38,2,'roles',0,1,1,0,0,0,0,0,0,0),(39,3,'roles',0,0,0,0,0,0,0,0,0,0),(40,1,'reports',1,1,1,1,1,1,1,1,1,1),(41,2,'reports',1,1,1,0,0,0,0,1,0,0),(42,3,'reports',0,0,0,0,0,0,0,0,0,0),(43,1,'archive',1,1,1,1,1,1,1,1,1,1),(44,2,'archive',1,1,1,0,0,0,0,0,0,0),(45,3,'archive',0,0,0,0,0,0,0,0,0,0),(46,1,'notifications',1,1,1,1,1,1,1,1,1,1),(47,2,'notifications',1,1,1,0,0,0,0,0,0,0),(48,3,'notifications',1,0,0,0,0,0,0,0,0,0),(49,1,'settings',1,1,1,1,1,1,1,1,1,1),(50,2,'settings',0,1,1,0,0,0,0,0,0,0),(51,3,'settings',0,0,0,0,0,0,0,0,0,0),(52,1,'followups',1,1,1,1,1,1,1,1,1,1),(53,2,'followups',1,1,1,0,1,1,0,1,0,0),(54,3,'followups',1,1,1,0,0,0,0,0,0,0);
/*!40000 ALTER TABLE `role_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `label` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'super_admin','Super Admin','Full administrative authority across all company branches and modules.','2026-11-03 11:11:24'),(2,'manager','Admissions Manager','Manages team operations, counselors, lead assignments, and reporting.','2026-11-03 11:11:24'),(3,'counselor','Admissions Counselor','Strictly isolated access to assigned student leads, follow-ups, and tasks.','2026-11-03 11:11:24');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_documents`
--

DROP TABLE IF EXISTS `student_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lead_id` int(11) NOT NULL,
  `category` enum('Identity','Academic','English Proficiency','Financial & Visa','Other') NOT NULL,
  `document_name` varchar(150) NOT NULL,
  `title` varchar(150) DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `original_filename` varchar(255) DEFAULT NULL,
  `file_size` int(11) DEFAULT 0,
  `uploaded_by` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_lead` (`lead_id`),
  KEY `idx_cat` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_documents`
--

LOCK TABLES `student_documents` WRITE;
/*!40000 ALTER TABLE `student_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_stage_history`
--

DROP TABLE IF EXISTS `student_stage_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_stage_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `stage_id` int(11) NOT NULL,
  `changed_by_user_id` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_ssh_students` (`student_id`),
  KEY `fk_ssh_stages` (`stage_id`),
  KEY `fk_ssh_users` (`changed_by_user_id`),
  CONSTRAINT `fk_ssh_stages` FOREIGN KEY (`stage_id`) REFERENCES `admission_stages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ssh_students` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ssh_users` FOREIGN KEY (`changed_by_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_stage_history`
--

LOCK TABLES `student_stage_history` WRITE;
/*!40000 ALTER TABLE `student_stage_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_stage_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `students`
--

DROP TABLE IF EXISTS `students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_code` varchar(50) NOT NULL,
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
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_code` (`student_code`),
  KEY `fk_students_users` (`assigned_staff_id`),
  KEY `fk_students_stages` (`stage_id`),
  KEY `fk_students_courses` (`course_id`),
  KEY `fk_students_universities` (`university_id`),
  CONSTRAINT `fk_students_courses` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_students_stages` FOREIGN KEY (`stage_id`) REFERENCES `admission_stages` (`id`),
  CONSTRAINT `fk_students_universities` FOREIGN KEY (`university_id`) REFERENCES `universities` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_students_users` FOREIGN KEY (`assigned_staff_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `students`
--

LOCK TABLES `students` WRITE;
/*!40000 ALTER TABLE `students` DISABLE KEYS */;
/*!40000 ALTER TABLE `students` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tasks`
--

DROP TABLE IF EXISTS `tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_tasks_staff` (`assigned_staff_id`),
  KEY `fk_tasks_students` (`student_id`),
  CONSTRAINT `fk_tasks_staff` FOREIGN KEY (`assigned_staff_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tasks_students` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tasks`
--

LOCK TABLES `tasks` WRITE;
/*!40000 ALTER TABLE `tasks` DISABLE KEYS */;
/*!40000 ALTER TABLE `tasks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `universities`
--

DROP TABLE IF EXISTS `universities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `universities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `country` varchar(100) NOT NULL,
  `city` varchar(100) DEFAULT NULL,
  `ranking` varchar(50) DEFAULT NULL,
  `website` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `universities`
--

LOCK TABLES `universities` WRITE;
/*!40000 ALTER TABLE `universities` DISABLE KEYS */;
INSERT INTO `universities` VALUES (1,'University of Greenwich, London','United Kingdom','London','QS Top UK Modern Uni','gre.ac.uk'),(2,'University of Hertfordshire','United Kingdom','Hatfield','TEF Gold Rated','herts.ac.uk'),(3,'Coventry University','United Kingdom','Coventry','Top 30 UK University','coventry.ac.uk'),(4,'University of East London','United Kingdom','London','London Docklands Campus','uel.ac.uk'),(5,'Technical University of Munich (TUM)','Germany','Munich','QS World #37','tum.de'),(6,'Fanshawe College, Ontario','Canada','London, Ontario','SPP / DLI Partner College','fanshawec.ca'),(7,'Trinity College Dublin','Ireland','Dublin','QS World #81','tcd.ie'),(8,'Deakin University, Melbourne','Australia','Melbourne','QS World Top 1%','deakin.edu.au');
/*!40000 ALTER TABLE `universities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_permissions`
--

DROP TABLE IF EXISTS `user_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_module` (`user_id`,`module`),
  KEY `fk_up_users` (`user_id`),
  CONSTRAINT `fk_up_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_permissions`
--

LOCK TABLES `user_permissions` WRITE;
/*!40000 ALTER TABLE `user_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `branch_id` int(11) DEFAULT NULL,
  `role_id` int(11) NOT NULL,
  `manager_id` int(11) DEFAULT NULL,
  `reporting_manager_id` int(11) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_branch` (`branch_id`),
  KEY `idx_role` (`role_id`),
  KEY `idx_manager` (`manager_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,NULL,1,NULL,NULL,'Noufal Peediyakkal','noufal@edroots.in','$2y$10$MowjzN729btxidk5wJI3z.sMr4y5VbuFGhCP2VAkrsgTmqAJkbvz2','+91 4933 224333','Super Admin & Case Manager',NULL,'Active','2026-11-03 11:11:24',NULL),(2,2,2,NULL,NULL,'Vidya Harish','vidya@edroots.in','$2y$10$MowjzN729btxidk5wJI3z.sMr4y5VbuFGhCP2VAkrsgTmqAJkbvz2','+91 9074 134910','Admissions & Branch Head',NULL,'Active','2026-11-03 11:11:24','2026-11-03 14:43:01'),(3,7,3,2,2,'Athira Ashok','athira@edroots.in','$2y$10$MowjzN729btxidk5wJI3z.sMr4y5VbuFGhCP2VAkrsgTmqAJkbvz2','+91 8547 597476','Senior Academic Counselor',NULL,'Active','2026-11-03 11:11:24','2026-11-03 14:43:01'),(4,1,3,2,2,'Syamily S','syamily@edroots.in','$2y$10$MowjzN729btxidk5wJI3z.sMr4y5VbuFGhCP2VAkrsgTmqAJkbvz2','+91 9633 371111','Admissions Advisor & Counselor',NULL,'Active','2026-11-03 11:11:24','2026-11-03 14:43:01'),(5,3,3,2,2,'Ambili CO','ambili@edroots.in','$2y$10$MowjzN729btxidk5wJI3z.sMr4y5VbuFGhCP2VAkrsgTmqAJkbvz2','+91 9895 716710','Academic Counselor',NULL,'Active','2026-11-03 11:11:24','2026-11-03 14:43:01'),(6,1,3,2,2,'Sharanya M P','sharanya@edroots.in','$2y$10$MowjzN729btxidk5wJI3z.sMr4y5VbuFGhCP2VAkrsgTmqAJkbvz2','+91 9947 921306','Academic Counselor',NULL,'Active','2026-11-03 11:11:24','2026-11-03 14:43:01'),(7,3,3,2,2,'Soumya Ajith','soumya@edroots.in','$2y$10$MowjzN729btxidk5wJI3z.sMr4y5VbuFGhCP2VAkrsgTmqAJkbvz2','+91 9061 253555','Academic Counselor',NULL,'Active','2026-11-03 11:11:24','2026-11-03 14:43:01');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `web_leads`
--

DROP TABLE IF EXISTS `web_leads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `web_leads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_name` varchar(150) NOT NULL,
  `full_name` varchar(150) DEFAULT NULL,
  `mobile_number` varchar(50) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `phone_normalized` varchar(20) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `city_state` varchar(150) DEFAULT NULL,
  `target_course` varchar(150) DEFAULT NULL,
  `target_university` varchar(150) DEFAULT NULL,
  `intake` varchar(100) DEFAULT 'September 2026',
  `target_country` varchar(100) DEFAULT NULL,
  `nearest_branch_id` int(11) DEFAULT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `source_campaign` varchar(150) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `source` varchar(150) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `assigned_counselor_id` int(11) DEFAULT NULL,
  `assigned_branch_id` int(11) DEFAULT NULL,
  `converted_lead_id` int(11) DEFAULT NULL,
  `assigned_lead_id` int(11) DEFAULT NULL,
  `converted_by` int(11) DEFAULT NULL,
  `converted_at` datetime DEFAULT NULL,
  `assigned_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_phone` (`phone_normalized`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `web_leads`
--

LOCK TABLES `web_leads` WRITE;
/*!40000 ALTER TABLE `web_leads` DISABLE KEYS */;
/*!40000 ALTER TABLE `web_leads` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-07 12:15:03
