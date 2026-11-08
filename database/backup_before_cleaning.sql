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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` VALUES (1,1,NULL,NULL,'System Setup','Initial enterprise configuration and branch locations initialized.','127.0.0.1','2026-11-03 11:11:24'),(2,2,1,1,'Lead Assigned','Assigned lead LED-2026-0001 (Adithya R. Nair) to counselor David Miller.','127.0.0.1','2026-11-03 11:11:24'),(3,4,1,1,'Follow-up Interaction','Logged consultation outcome: Information Shared.','127.0.0.1','2026-11-03 11:11:24'),(4,2,2,1,'Invoice Generated','Generated formal service invoice INV-2026-0001 for ₹38,940.00. Lead converted to Customer.','127.0.0.1','2026-11-03 11:11:24'),(5,2,2,1,'Payment Recorded','Recorded partial payment of ₹20,000.00 via UPI.','127.0.0.1','2026-11-03 11:11:24'),(6,5,7,1,'Stage Advanced','Advanced student Sandra Varghese to Stage 6: ENROLLED.','127.0.0.1','2026-11-03 11:11:24');
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
INSERT INTO `branches` VALUES (1,'Kochi','KOC','Kochi','Kerala','India','+91 484 288 9001','kochi@edroots.com','Edroots Tower, MG Road, Kochi',2,'Active','2026-11-03 11:11:24','2026-11-03 11:11:24'),(2,'Calicut','CLT','Calicut','Kerala','India','+91 495 277 8001','calicut@edroots.com','Skyline Plaza, Mavoor Road, Calicut',3,'Active','2026-11-03 11:11:24','2026-11-03 11:11:24'),(3,'Kannur','KNR','Kannur','Kerala','India','+91 497 270 7001','kannur@edroots.com','Fort Road Commercial Centre, Kannur',NULL,'Active','2026-11-03 11:11:24',NULL),(4,'Kasaragod','KSD','Kasaragod','Kerala','India','+91 499 423 6001','kasaragod@edroots.com','Bank Road Junction, Kasaragod',NULL,'Active','2026-11-03 11:11:24',NULL),(5,'Kanhangad','KHD','Kanhangad','Kerala','India','+91 467 220 5001','kanhangad@edroots.com','Main Street Mall, Kanhangad',NULL,'Active','2026-11-03 11:11:24',NULL),(6,'Perinthalmanna','PMNA','Perinthalmanna','Kerala','India','+91 493 322 4001','perinthalmanna@edroots.com','Bypass Junction, Perinthalmanna',NULL,'Active','2026-11-03 11:11:24',NULL),(7,'Trivandrum','TVM','Trivandrum','Kerala','India','+91 471 233 3001','trivandrum@edroots.com','Statue Junction, MG Road, Trivandrum',NULL,'Active','2026-11-03 11:11:24',NULL),(8,'Mangalore','MNG','Mangalore','Karnataka','India','+91 824 244 2001','mangalore@edroots.com','KSR Road, Hampankatta, Mangalore',NULL,'Active','2026-11-03 11:11:24',NULL),(9,'London','LDN','London','Greater London','United Kingdom','+44 20 7946 0991','london@edroots.com','120 Fenchurch Street, London EC3M 5BA',NULL,'Active','2026-11-03 11:11:24',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `college_data`
--

LOCK TABLES `college_data` WRITE;
/*!40000 ALTER TABLE `college_data` DISABLE KEYS */;
INSERT INTO `college_data` VALUES (1,'Rohan Mathew','+91 98471 11001','+91 98471 11001','9847111001','rohan.mathew@gmail.com','St. Alberts College, Ernakulam','B.Sc Computer Science',NULL,2024,2024,'Kochi','Kochi','Interested in UK Masters in Cybersecurity','Campus Seminar 2024',NULL,NULL,1,'NO',0,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'2026-11-03 11:11:24'),(2,'Sneha George','+91 98471 11002','+91 98471 11002','9847111002','sneha.george@gmail.com','Sacred Heart College, Thevara','B.Com Finance',NULL,2025,2025,'Kochi','Kochi','Evaluating Canada & Ireland Business Analytics','Academic Fair Kochi',NULL,NULL,1,'NO',0,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'2026-11-03 11:11:24'),(3,'Abhishek Menon','+91 98471 11003','+91 98471 11003','9847111003','abhishek.menon@gmail.com','Farook College, Kozhikode','BBA International Business',NULL,2024,2024,'Calicut','Calicut','Needs scholarship guidance for Australia','Campus Expo Calicut',NULL,NULL,2,'NO',0,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'2026-11-03 11:11:24'),(4,'Meera Krishnan','+91 98471 11004','+91 98471 11004','9847111004','meera.k@gmail.com','Devagiri College, Calicut','B.Sc Biotechnology',NULL,2025,2025,'Calicut','Calicut','Interested in Germany / Ireland Life Sciences','Campus Expo Calicut',NULL,NULL,2,'NO',0,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'2026-11-03 11:11:24'),(5,'Arjun Pillai','+91 98471 11005','+91 98471 11005','9847111005','arjun.pillai@gmail.com','Rajagiri School of Engineering, Kochi','B.Tech Mechanical',NULL,2024,2024,'Kochi','Kochi','Targeting UK Automotive Engineering','College Seminar 2024',NULL,NULL,1,'NO',0,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'2026-11-03 11:11:24'),(6,'Fathima Nizar','+91 98471 11006','+91 98471 11006','9847111006','fathima.nizar@gmail.com','MES College, Mampad','B.Com Computer Applications',NULL,2024,2024,'Malappuram','Malappuram','Interested in UK MBA programs','Educational Expo',NULL,NULL,2,'NO',0,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'2026-11-03 11:11:24'),(7,'Gokul Das','+91 98471 11007','+91 98471 11007','9847111007','gokul.das@gmail.com','Govt Engineering College, Thrissur','B.Tech Civil',NULL,2023,2023,'Thrissur','Thrissur','Targeting New Zealand Construction Management','Campus Fair',NULL,NULL,1,'NO',0,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'2026-11-03 11:11:24');
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
INSERT INTO `companies` VALUES (1,'Edroots Global Education Pvt. Ltd.','admissions@edroots.com','+91 484 288 9000','https://edroots.com','Edroots Tower, MG Road, Kochi, Kerala 682016, India',NULL,'2026-11-03 11:11:24');
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
INSERT INTO `courses` VALUES (1,1,'B.Sc. in Computer Science','Undergraduate','3 Years','$34,000 / year'),(2,1,'Master of Business Administration (MBA)','Postgraduate','1 Year','$48,000 / year'),(3,2,'M.Sc. in Data Science & Machine Learning','Postgraduate','2 Years','$39,500 / year'),(4,2,'B.Eng. in Robotics & Mechatronics','Undergraduate','4 Years','$36,000 / year'),(5,3,'Master of International Relations','Postgraduate','2 Years','$31,000 / year'),(6,3,'B.Sc. in Biomedical Science','Undergraduate','3 Years','$33,000 / year'),(7,4,'M.Sc. in Cybersecurity & Cloud Systems','Postgraduate','1.5 Years','$35,000 / year'),(8,5,'M.Sc. in Automotive & Aerospace Engineering','Postgraduate','2 Years','Ôé¼1,500 / sem'),(9,6,'B.S. in Electrical Engineering & Computer Science','Undergraduate','4 Years','$44,000 / year'),(10,7,'Master of Public Health (MPH)','Postgraduate','1 Year','Ôé¼22,000 / year');
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `deletion_logs`
--

LOCK TABLES `deletion_logs` WRITE;
/*!40000 ALTER TABLE `deletion_logs` DISABLE KEYS */;
INSERT INTO `deletion_logs` VALUES (1,1,'web_lead',99,'Spam Form Bot Submission (9800000000)','Confirmed marketing bot spam submission from suspicious IP range.','127.0.0.1','2026-11-03 11:11:24');
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
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `followups`
--

LOCK TABLES `followups` WRITE;
/*!40000 ALTER TABLE `followups` DISABLE KEYS */;
INSERT INTO `followups` VALUES (1,1,'2026-11-03','2026-11-03','10:00:00','Completed','2026-11-03 11:11:24',4,'10:30:00',2,'Phone Call',7,'Candidate visited office; reviewed Bristol course curriculum and English waiver requirements. Scheduled next discussion for SOP drafts.',NULL,3,'September 2026','2026-11-03',4,'2026-11-03 11:11:24'),(2,4,'2026-10-29','2026-10-29','10:00:00','Completed','2026-11-03 11:11:24',4,'14:00:00',1,'Phone Call',7,'Tried contacting candidate on mobile; phone rang continuously with no response. Re-scheduled outreach.',NULL,1,'September 2026','2026-11-01',4,'2026-11-03 11:11:24'),(3,7,'2026-11-03','2026-11-03','10:00:00','Completed','2026-11-03 11:11:24',5,'11:15:00',5,'Phone Call',7,'Discussed pre-departure briefing, health surcharge, and biometric appointment scheduling in Kochi VFS centre.',NULL,6,'September 2026','2026-11-03',5,'2026-11-03 11:11:24'),(4,8,'2026-10-30','2026-10-30','10:00:00','Completed','2026-11-03 11:11:24',5,'16:00:00',3,'Phone Call',7,'Candidate promised to email final semester marksheets and degree certificate. Still pending.',NULL,2,'September 2026','2026-11-02',5,'2026-11-03 11:11:24'),(5,10,'2026-11-03','2026-11-03','10:00:00','Completed','2026-11-03 11:11:24',6,'15:30:00',2,'Phone Call',7,'Sent Leeds course fee structure and accommodation options. Follow-up today to confirm application submission.',NULL,2,'September 2026','2026-11-03',6,'2026-11-03 11:11:24'),(6,12,'2026-11-03','2026-11-03','10:00:00','Completed','2026-11-03 11:11:24',7,'12:00:00',4,'Phone Call',7,'UCC offer letter received; preparing CAS deposit document pack.',NULL,4,'September 2026','2026-11-03',7,'2026-11-03 11:11:24'),(7,13,'2026-10-28','2026-10-28','10:00:00','Completed','2026-11-03 11:11:24',7,'11:00:00',1,'Phone Call',7,'Line busy; sent WhatsApp follow-up reminder.',NULL,3,'September 2026','2026-10-31',7,'2026-11-03 11:11:24'),(8,1,'2026-11-03','2026-11-03','11:00:00','Pending','2026-11-03 11:42:29',4,'11:00:00',2,'Phone Call',7,'Scheduled follow-up for today',NULL,3,NULL,'2026-11-06',4,'2026-11-03 11:42:29'),(9,7,'2026-11-03','2026-11-03','11:00:00','Pending','2026-11-03 11:42:29',5,'11:00:00',2,'Phone Call',7,'Scheduled follow-up for today',NULL,6,NULL,'2026-11-06',5,'2026-11-03 11:42:29'),(10,10,'2026-11-03','2026-11-03','11:00:00','Pending','2026-11-03 11:42:29',6,'11:00:00',2,'Phone Call',7,'Scheduled follow-up for today',NULL,2,NULL,'2026-11-06',6,'2026-11-03 11:42:29'),(11,4,'2026-11-01','2026-11-01','14:00:00','Pending','2026-11-03 11:42:29',4,'14:00:00',1,'Phone Call',7,'Overdue appointment',NULL,1,NULL,'2026-11-03',4,'2026-11-03 11:42:29'),(12,8,'2026-11-01','2026-11-01','14:00:00','Pending','2026-11-03 11:42:29',5,'14:00:00',1,'Phone Call',7,'Overdue appointment',NULL,2,NULL,'2026-11-03',5,'2026-11-03 11:42:29');
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `general_documents`
--

LOCK TABLES `general_documents` WRITE;
/*!40000 ALTER TABLE `general_documents` DISABLE KEYS */;
INSERT INTO `general_documents` VALUES (1,'UK University Admissions Master Handbook 2026/27','University Prospectuses','United Kingdom',NULL,'assets/docs/uk_admissions_handbook_2026.pdf','uk_admissions_handbook_2026.pdf',3450000,1,'2026-11-03 11:11:24'),(2,'Ireland Graduate Route & Stayback Visa Guidelines','Country Guidelines','Ireland',NULL,'assets/docs/ireland_visa_guidelines.pdf','ireland_visa_guidelines.pdf',1820000,1,'2026-11-03 11:11:24'),(3,'Australia Subclass 500 Genuine Student Guidelines','Country Guidelines','Australia',NULL,'assets/docs/australia_subclass_500_guide.pdf','australia_subclass_500_guide.pdf',2140000,1,'2026-11-03 11:11:24'),(4,'Edroots SOP & Statement of Purpose Master Template','Training Documents','Global',NULL,'assets/docs/edroots_sop_master_template.pdf','edroots_sop_master_template.pdf',940000,2,'2026-11-03 11:11:24');
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoice_items`
--

LOCK TABLES `invoice_items` WRITE;
/*!40000 ALTER TABLE `invoice_items` DISABLE KEYS */;
INSERT INTO `invoice_items` VALUES (1,1,'University Application & SOP Drafting Service','University Application & SOP Drafting Service',1,15000.00,0.00,1000.00,18.00,2520.00,16520.00,0.00),(2,1,'Visa Documentation & CAS Compliance Verification','Visa Documentation & CAS Compliance Verification',1,20000.00,0.00,1000.00,18.00,3420.00,22420.00,0.00),(3,2,'Deakin University Direct Nursing Processing Fee','Deakin University Direct Nursing Processing Fee',1,25000.00,0.00,0.00,18.00,4500.00,29500.00,0.00),(4,3,'UK University Placement & Pre-Departure Service','UK University Placement & Pre-Departure Service',1,30000.00,0.00,0.00,18.00,5400.00,35400.00,0.00),(5,4,'Ireland Medical College Consulting & Visa Filing','Ireland Medical College Consulting & Visa Filing',1,30000.00,0.00,3000.00,18.00,4860.00,31860.00,0.00);
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoices`
--

LOCK TABLES `invoices` WRITE;
/*!40000 ALTER TABLE `invoices` DISABLE KEYS */;
INSERT INTO `invoices` VALUES (1,'INV-2026-0001',2,1,'2026-02-15','2026-02-15','2026-03-01','House No. 12, Panampilly Nagar, Kochi, Kerala',35000.00,2000.00,0.00,5940.00,18.00,0.00,38940.00,20000.00,0.00,18940.00,0.00,'Partially Paid','Active','UK & Ireland Premium Admissions & Visa Processing Package','Due within 15 days of issue.','HDFC Bank - Edroots Education Consultancy Ltd.\nAccount: 50200088991122 | IFSC: HDFC0000240 | Kochi Branch',2,'2026-11-03 11:11:24','2026-11-03 11:42:29'),(2,'INV-2026-0002',3,1,'2026-02-10','2026-02-10','2026-02-25','Rose Villa, Kakkanad, Kochi, Kerala',25000.00,0.00,0.00,4500.00,18.00,0.00,29500.00,29500.00,0.00,0.00,0.00,'Paid','Paid','Australia Fast-Track Nursing Package','Due within 15 days of issue.','HDFC Bank - Edroots Education Consultancy Ltd.\nAccount: 50200088991122 | IFSC: HDFC0000240 | Kochi Branch',2,'2026-11-03 11:11:24','2026-11-03 11:42:29'),(3,'INV-2026-0003',7,1,'2026-01-20','2026-01-20','2026-02-05','Hill View, Thrikkakara, Kochi, Kerala',30000.00,0.00,0.00,5400.00,18.00,0.00,35400.00,35400.00,0.00,0.00,0.00,'Paid','Paid','King’s College London Enrolment Fee','Due within 15 days of issue.','HDFC Bank - Edroots Education Consultancy Ltd.\nAccount: 50200088991122 | IFSC: HDFC0000240 | Kochi Branch',2,'2026-11-03 11:11:24','2026-11-03 11:42:29'),(4,'INV-2026-0004',12,2,'2026-02-18','2026-02-18','2026-03-05','Sreevalsam, Medical College PO, Calicut, Kerala',30000.00,3000.00,0.00,4860.00,18.00,0.00,31860.00,15000.00,0.00,16860.00,0.00,'Partially Paid','Active','Ireland UCC Masters Guidance Fee','Due within 15 days of issue.','HDFC Bank - Edroots Education Consultancy Ltd.\nAccount: 50200088991122 | IFSC: HDFC0000240 | Kochi Branch',3,'2026-11-03 11:11:24','2026-11-03 11:42:29');
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
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leads`
--

LOCK TABLES `leads` WRITE;
/*!40000 ALTER TABLE `leads` DISABLE KEYS */;
INSERT INTO `leads` VALUES (1,'LED-2026-0001','Adithya R. Nair','9847333001','+91 98473 33001','9847333001','+91 484 255 1101',NULL,'Other','adithya.nair@gmail.com','Flat 4B, Skyline Riverdale, Tripunithura','Kochi','Kerala','India',1,4,'B.Tech Computer Science','Model Engineering College, Kochi','Model Engineering College, Kochi',2024,2024,NULL,0,'Yes','N8923412','2031-08-15','ABCDE1234F','Yes','No','No','None',NULL,NULL,NULL,NULL,NULL,0.0,NULL,7.5,8.0,7.5,7.0,7.5,NULL,NULL,NULL,NULL,NULL,'UK','UK','University of Bristol','MSc Advanced Computer Science','MSc Advanced Computer Science',3,'September 2026',2026,'September','High','Moderate','WALK IN','College Data','Direct Inquiry',3,'ENQUIRY','Active',0,NULL,NULL,0.00,0.00,0.00,NULL,NULL,'2026-11-02 06:41:24','2026-11-03',2,'2026-11-02','2026-11-03 11:42:29',1,0,NULL,NULL,NULL,NULL,NULL,'2026-11-03 11:11:24','2026-11-03 12:09:15'),(2,'LED-2026-0002','Benjamin Paul','9847333002','+91 98473 33002','9847333002',NULL,NULL,'Other','benjamin.paul@gmail.com','House No. 12, Panampilly Nagar','Kochi','Kerala','India',1,4,'B.Com Finance & Taxation','St. Teresa’s College, Ernakulam','St. Teresa’s College, Ernakulam',2024,2024,NULL,0,'Yes','P5432109','2030-05-20','BCDEF2345G','Yes','No','No','None',NULL,NULL,NULL,NULL,NULL,0.0,NULL,7.0,7.5,7.0,6.5,7.0,NULL,NULL,NULL,NULL,NULL,'Ireland','Ireland','Trinity College Dublin','MSc Finance & Investment','MSc Finance & Investment',3,'September 2026',2026,'September','High','Moderate','TELE','Facebook','Direct Inquiry',4,'ENQUIRY','Active',1,'CUST-2026-001','2026-10-29 06:41:24',38940.00,20000.00,18940.00,NULL,NULL,'2026-10-31 06:41:24','2026-11-06',0,'2026-10-31','2026-11-03 11:42:29',1,0,NULL,NULL,NULL,NULL,NULL,'2026-11-03 11:11:24','2026-11-03 11:42:29'),(3,'LED-2026-0003','Diya Lakshmi','9847333003','+91 98473 33003','9847333003',NULL,NULL,'Other','diya.lakshmi@gmail.com','Rose Villa, Kakkanad','Kochi','Kerala','India',1,4,'B.Sc Nursing','Amrita Institute of Medical Sciences','Amrita Institute of Medical Sciences',2023,2023,NULL,0,'Yes','M1234987','2029-11-12','CDEFG3456H','No','Yes','No','None',NULL,NULL,NULL,NULL,NULL,0.0,NULL,NULL,NULL,NULL,NULL,NULL,68,70,67,65,70,'Australia','Australia','Deakin University','Master of Nursing Practice','Master of Nursing Practice',1,'January 2026',2026,'September','High','Moderate','WALK IN','Walk-in','Direct Inquiry',5,'ENQUIRY','Active',1,'CUST-2026-002','2026-10-24 06:41:24',29500.00,29500.00,0.00,NULL,NULL,'2026-11-01 06:41:24','2026-11-07',0,'2026-11-01','2026-11-03 11:42:29',1,0,NULL,NULL,NULL,NULL,NULL,'2026-11-03 11:11:24','2026-11-03 11:42:29'),(4,'LED-2026-0004','Joel Mathew','9847333004','+91 98473 33004','9847333004',NULL,NULL,'Other','joel.mathew@gmail.com','Grace Bhavan, Aluva','Kochi','Kerala','India',1,4,'B.Tech Mechanical Engineering','Mar Athanasius College of Engineering','Mar Athanasius College of Engineering',2024,2024,NULL,0,'Yes','R7654321','2032-01-10','DEFGH4567I','Yes','No','No','None',NULL,NULL,NULL,NULL,NULL,0.0,NULL,6.5,7.0,6.5,6.0,6.5,NULL,NULL,NULL,NULL,NULL,'UK','UK','University of Birmingham','MSc Robotics and Automation','MSc Robotics and Automation',3,'September 2026',2026,'September','Moderate','Moderate','TELE-WALK IN','Website Direct','Direct Inquiry',1,'ENQUIRY','Active',0,NULL,NULL,0.00,0.00,0.00,NULL,NULL,'2026-10-29 06:41:24','2026-11-01',2,'2026-10-29','2026-11-03 11:42:29',1,0,NULL,NULL,NULL,NULL,NULL,'2026-11-03 11:11:24','2026-11-03 12:09:15'),(5,'LED-2026-0005','Kavya Suresh','9847333005','+91 98473 33005','9847333005',NULL,NULL,'Other','kavya.suresh@gmail.com','Anugraha, Vyttila','Kochi','Kerala','India',1,4,'BBA','SCMS Cochin School of Business','SCMS Cochin School of Business',2025,2025,NULL,0,'Applied',NULL,NULL,NULL,'Awaiting Result','No','No','None',NULL,NULL,NULL,NULL,NULL,0.0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Canada','Canada','Concordia University','Master of Management','Master of Management',4,'January 2027',2026,'September','Moderate','Moderate','TELE','Google Ads','Direct Inquiry',1,'ENQUIRY','Active',0,NULL,NULL,0.00,0.00,0.00,NULL,NULL,NULL,'2026-11-03',0,NULL,'2026-11-03 11:42:29',0,0,NULL,NULL,NULL,NULL,NULL,'2026-11-03 11:11:24','2026-11-03 11:42:29'),(6,'LED-2026-0006','Madhav Mohan','9847333006','+91 98473 33006','9847333006',NULL,NULL,'Other','madhav.mohan@gmail.com','Krishna Kripa, Edappally','Kochi','Kerala','India',1,4,'B.Sc Physics','Maharajas College, Ernakulam','Maharajas College, Ernakulam',2024,2024,NULL,0,'No',NULL,NULL,NULL,'No','No','No','None',NULL,NULL,NULL,NULL,NULL,0.0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Germany','Germany','TU Munich','MSc Applied Geophysics','MSc Applied Geophysics',3,'September 2026',2026,'September','Low','Moderate','TELE','Direct Reference','Direct Inquiry',1,'ENQUIRY','Active',0,NULL,NULL,0.00,0.00,0.00,NULL,NULL,NULL,NULL,0,NULL,'2026-11-03 11:42:29',0,0,NULL,NULL,NULL,NULL,NULL,'2026-11-03 11:11:24','2026-11-03 11:42:29'),(7,'LED-2026-0007','Sandra Varghese','9847333007','+91 98473 33007','9847333007',NULL,NULL,'Other','sandra.v@gmail.com','Hill View, Thrikkakara','Kochi','Kerala','India',1,5,'B.Sc Psychology','St. Teresa’s College','St. Teresa’s College',2024,2024,NULL,0,'Yes','K9876543','2031-03-14','EFGHI5678J','Yes','No','No','None',NULL,NULL,NULL,NULL,NULL,0.0,NULL,7.5,8.0,7.5,7.0,7.5,NULL,NULL,NULL,NULL,NULL,'UK','UK','King’s College London','MSc Clinical Psychology','MSc Clinical Psychology',3,'September 2026',2026,'September','High','Moderate','WALK IN','Walk-in','Direct Inquiry',6,'ENQUIRY','Active',1,'CUST-2026-003','2026-10-14 06:41:24',35400.00,35400.00,0.00,NULL,NULL,'2026-11-02 06:41:24','2026-11-03',2,'2026-11-02','2026-11-03 11:42:29',1,0,NULL,NULL,NULL,NULL,NULL,'2026-11-03 11:11:24','2026-11-03 12:09:15'),(8,'LED-2026-0008','Vishnu Prasad','9847333008','+91 98473 33008','9847333008',NULL,NULL,'Other','vishnu.prasad@gmail.com','Pournami, Kaloor','Kochi','Kerala','India',1,5,'B.Tech Electronics','CUSAT, Kochi','CUSAT, Kochi',2023,2023,NULL,0,'Yes','T3456789','2030-07-22','FGHIJ6789K','No','Yes','No','None',NULL,NULL,NULL,NULL,NULL,0.0,NULL,NULL,NULL,NULL,NULL,NULL,72,75,70,72,71,'Ireland','Ireland','University College Dublin','ME Electronic Systems','ME Electronic Systems',3,'September 2026',2026,'September','High','Moderate','TELE','Facebook','Direct Inquiry',2,'ENQUIRY','Active',0,NULL,NULL,0.00,0.00,0.00,NULL,NULL,'2026-10-30 06:41:24','2026-11-02',2,'2026-10-30','2026-11-03 11:42:29',1,0,NULL,NULL,NULL,NULL,NULL,'2026-11-03 11:11:24','2026-11-03 12:09:15'),(9,'LED-2026-0009','Nandana S.','9847333009','+91 98473 33009','9847333009',NULL,NULL,'Other','nandana.s@gmail.com','Surabhi, Fort Kochi','Kochi','Kerala','India',1,5,'B.Arch','College of Architecture, Trivandrum','College of Architecture, Trivandrum',2024,2024,NULL,0,'Yes','Y4567890','2032-09-09','GHIJK7890L','Awaiting Result','No','No','None',NULL,NULL,NULL,NULL,NULL,0.0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'UK','UK','University of Edinburgh','MSc Urban Environmental Design','MSc Urban Environmental Design',4,'January 2027',2026,'September','Moderate','Moderate','WALK IN','College Data','Direct Inquiry',1,'ENQUIRY','Active',0,NULL,NULL,0.00,0.00,0.00,NULL,NULL,NULL,'2026-11-03',0,NULL,'2026-11-03 11:42:29',0,0,NULL,NULL,NULL,NULL,NULL,'2026-11-03 11:11:24','2026-11-03 11:42:29'),(10,'LED-2026-0010','Muhammed Nihal','9847333010','+91 98473 33010','9847333010',NULL,NULL,'Other','nihal.m@gmail.com','Al Madeena, Mavoor Road','Calicut','Kerala','India',2,6,'B.Com Foreign Trade','Farook College, Calicut','Farook College, Calicut',2024,2024,NULL,0,'Yes','W2345678','2031-10-30','HIJKL8901M','Yes','No','No','None',NULL,NULL,NULL,NULL,NULL,0.0,NULL,7.0,7.5,7.0,6.5,7.0,NULL,NULL,NULL,NULL,NULL,'UK','UK','University of Leeds','MSc International Business','MSc International Business',3,'September 2026',2026,'September','High','Moderate','WALK IN','Walk-in','Direct Inquiry',2,'ENQUIRY','Active',0,NULL,NULL,0.00,0.00,0.00,NULL,NULL,'2026-11-01 06:41:24','2026-11-03',2,'2026-11-01','2026-11-03 11:42:29',1,0,NULL,NULL,NULL,NULL,NULL,'2026-11-03 11:11:24','2026-11-03 12:09:15'),(11,'LED-2026-0011','Devika Nambiar','9847333011','+91 98473 33011','9847333011',NULL,NULL,'Other','devika.nambiar@gmail.com','Thejas, Civil Station','Calicut','Kerala','India',2,6,'B.Tech Food Technology','Kerala Agricultural University','Kerala Agricultural University',2024,2024,NULL,0,'Yes','E9871234','2029-04-18','IJKLM9012N','Yes','No','No','None',NULL,NULL,NULL,NULL,NULL,0.0,NULL,6.5,7.0,6.5,6.5,6.5,NULL,NULL,NULL,NULL,NULL,'Australia','Australia','RMIT University','Master of Food Science','Master of Food Science',3,'September 2026',2026,'September','Moderate','Moderate','TELE','Google Ads','Direct Inquiry',1,'ENQUIRY','Active',0,NULL,NULL,0.00,0.00,0.00,NULL,NULL,NULL,NULL,0,NULL,'2026-11-03 11:42:29',0,0,NULL,NULL,NULL,NULL,NULL,'2026-11-03 11:11:24','2026-11-03 11:42:29'),(12,'LED-2026-0012','Aiswarya Raj','9847333012','+91 98473 33012','9847333012',NULL,NULL,'Other','aiswarya.raj@gmail.com','Sreevalsam, Medical College PO','Calicut','Kerala','India',2,7,'Pharm.D','Calicut Medical College','Calicut Medical College',2023,2023,NULL,0,'Yes','Q1237890','2030-12-05','JKLMN0123O','Yes','No','No','None',NULL,NULL,NULL,NULL,NULL,0.0,NULL,7.5,8.0,7.5,7.0,7.5,NULL,NULL,NULL,NULL,NULL,'Ireland','Ireland','University College Cork','MSc Clinical Pharmacy','MSc Clinical Pharmacy',3,'September 2026',2026,'September','High','Moderate','WALK IN','Direct Reference','Direct Inquiry',4,'ENQUIRY','Active',1,'CUST-2026-004','2026-10-19 06:41:24',31860.00,15000.00,16860.00,NULL,NULL,'2026-11-02 06:41:24','2026-11-03',1,'2026-11-02','2026-11-03 11:42:29',1,0,NULL,NULL,NULL,NULL,NULL,'2026-11-03 11:11:24','2026-11-03 11:42:29'),(13,'LED-2026-0013','Gautham Sankar','9847333013','+91 98473 33013','9847333013',NULL,NULL,'Other','gautham.s@gmail.com','Nirupama, Palayam','Calicut','Kerala','India',2,7,'B.Sc Statistics','Zamorin’s Guruvayurappan College','Zamorin’s Guruvayurappan College',2024,2024,NULL,0,'Yes','S8765432','2031-06-15','KLMNO1234P','No','Yes','No','None',NULL,NULL,NULL,NULL,NULL,0.0,NULL,NULL,NULL,NULL,NULL,NULL,66,68,65,62,69,'UK','UK','Queen Mary University of London','MSc Business Analytics','MSc Business Analytics',3,'September 2026',2026,'September','Moderate','Moderate','TELE','Website Direct','Direct Inquiry',3,'ENQUIRY','Active',0,NULL,NULL,0.00,0.00,0.00,NULL,NULL,'2026-10-28 06:41:24','2026-10-31',1,'2026-10-28','2026-11-03 11:42:29',1,0,NULL,NULL,NULL,NULL,NULL,'2026-11-03 11:11:24','2026-11-03 11:42:29'),(14,'LED-2026-0014','Hariprasad K.','9847333014','+91 98473 33014','9847333014',NULL,NULL,'Other','hari.k@gmail.com','Harisree, Vennala','Kochi','Kerala','India',1,NULL,'B.Tech Civil','SCMS School of Engineering','SCMS School of Engineering',2024,2024,NULL,0,'Yes','B3451234','2030-02-28','LMNOP2345Q','No','No','No','None',NULL,NULL,NULL,NULL,NULL,0.0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Canada','Canada','University of Waterloo','MEng Civil Engineering','MEng Civil Engineering',4,'January 2027',2026,'September','Moderate','Moderate','TELE','Facebook','Direct Inquiry',1,'ENQUIRY','Active',0,NULL,NULL,0.00,0.00,0.00,NULL,NULL,NULL,NULL,0,NULL,'2026-11-03 11:42:29',0,0,NULL,NULL,NULL,NULL,NULL,'2026-11-03 11:11:24','2026-11-03 11:42:29'),(15,'LED-2026-0015','Sruthi Balakrishnan','9847333015','+91 98473 33015','9847333015',NULL,NULL,'Other','sruthi.b@gmail.com','Nalukettu, Thondayad','Calicut','Kerala','India',2,NULL,'B.Sc Mathematics','Providence Women’s College','Providence Women’s College',2024,2024,NULL,0,'No',NULL,NULL,NULL,'No','No','No','None',NULL,NULL,NULL,NULL,NULL,0.0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'UK','UK','University of Southampton','MSc Actuarial Science','MSc Actuarial Science',3,'September 2026',2026,'September','Low','Moderate','WALK IN','Walk-in','Direct Inquiry',1,'ENQUIRY','Active',0,NULL,NULL,0.00,0.00,0.00,NULL,NULL,NULL,NULL,0,NULL,'2026-11-03 11:42:29',0,0,NULL,NULL,NULL,NULL,NULL,'2026-11-03 11:11:24','2026-11-03 11:42:29'),(16,'LED-2026-0099','Siddharth V.','9847333099','+91 98473 33099','9847333099',NULL,NULL,'Other','siddharth.v@gmail.com',NULL,NULL,NULL,'India',1,4,NULL,NULL,NULL,NULL,NULL,NULL,0,'No',NULL,NULL,NULL,'No','No','No','None',NULL,NULL,NULL,NULL,NULL,0.0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Canada','Canada',NULL,'MBA','MBA',NULL,NULL,2026,'September','Low','Moderate','TELE','Walk-in','Direct Inquiry',1,'ENQUIRY','Lost',0,NULL,NULL,0.00,0.00,0.00,NULL,NULL,NULL,NULL,0,NULL,'2026-11-03 11:42:29',0,1,'2026-11-03 11:11:24',2,'Student decided to take domestic campus placement.','Student decided to take domestic campus placement.',NULL,'2026-11-03 11:11:24','2026-11-03 12:14:50');
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,4,1,'New Lead Assigned: Adithya R. Nair','Adithya R. Nair (LED-2026-0001) has been assigned to you for UK MSc Computer Science.','lead_assigned','leads/view.php?id=1','Not Attended','2026-11-03 11:11:24'),(2,4,1,'Follow-up Due Today: Adithya R. Nair','Scheduled Bristol curriculum consultation due at 10:30 AM.','followup_due','leads/view.php?id=1','Not Attended','2026-11-03 11:11:24'),(3,5,1,'Visa Milestone: Sandra Varghese Enrolled','Student visa confirmed for King’s College London departure.','stage_updated','leads/view.php?id=7','Not Attended','2026-11-03 11:11:24'),(4,2,1,'Invoice INV-2026-0001 Payment Logged','₹20,000 payment received from Benjamin Paul via UPI.','payment_received','finance/invoices.php','Not Attended','2026-11-03 11:11:24'),(5,1,NULL,'New Website Inquiries Received','4 prospective student leads awaiting triage in digital staging queue.','web_lead','data/web_leads.php','Not Attended','2026-11-03 11:11:24');
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (1,'RCT-2026-0001',1,2,1,'2026-02-16',20000.00,0.00,'Online / UPI','Bank Transfer','UPI/260216098231','Initial installment paid via GPay',2,2,'2026-11-03 11:11:24'),(2,'RCT-2026-0002',2,3,1,'2026-02-10',29500.00,0.00,'Bank Transfer','Bank Transfer','NEFT/HDFC26021008891','Full payment received in branch bank account',2,2,'2026-11-03 11:11:24'),(3,'RCT-2026-0003',3,7,1,'2026-01-20',35400.00,0.00,'Online / UPI','Bank Transfer','UPI/260120011882','Full package clearance',2,2,'2026-11-03 11:11:24'),(4,'RCT-2026-0004',4,12,2,'2026-02-18',15000.00,0.00,'Cash','Bank Transfer','RCPT/CLT/260218-01','Cash received at Calicut branch reception',3,3,'2026-11-03 11:11:24');
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
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `points_transactions`
--

LOCK TABLES `points_transactions` WRITE;
/*!40000 ALTER TABLE `points_transactions` DISABLE KEYS */;
INSERT INTO `points_transactions` VALUES (1,'student',4,200,'Earned','Early Application Submission','Points transaction logged under Early Application Submission.',1,'2026-09-28 00:00:00'),(2,'student',5,300,'Earned','High School Merit Honor Distinction','Points transaction logged under High School Merit Honor Distinction.',1,'2026-10-15 00:00:00'),(3,'student',17,250,'Earned','Referred Prospective Peer (Application Started)','Points transaction logged under Referred Prospective Peer (Application Started).',1,'2026-09-22 00:00:00'),(4,'student',27,150,'Earned','Complete Document Submission within 7 Days','Points transaction logged under Complete Document Submission within 7 Days.',1,'2026-09-27 00:00:00'),(5,'student',12,500,'Redeemed','Application Fee Waiver Redeemed','Points transaction logged under Application Fee Waiver Redeemed.',1,'2026-10-25 00:00:00'),(6,'student',14,400,'Redeemed','IELTS Prep Masterclass Pass Redeemed','Points transaction logged under IELTS Prep Masterclass Pass Redeemed.',1,'2026-09-28 00:00:00'),(7,'staff',6,500,'Earned','Monthly Counsellor Conversion Target Reached','Points transaction logged under Monthly Counsellor Conversion Target Reached.',1,'2026-10-12 00:00:00'),(8,'staff',7,300,'Earned','Overdue Task Resolution Record','Points transaction logged under Overdue Task Resolution Record.',1,'2026-10-12 00:00:00'),(9,'staff',4,2500,'Redeemed','Staff Performance Bonus Redeemed','Points transaction logged under Staff Performance Bonus Redeemed.',1,'2026-10-13 00:00:00'),(10,'student',22,200,'Earned','Early Application Submission','Points transaction logged under Early Application Submission.',1,'2026-09-18 00:00:00'),(11,'student',2,300,'Earned','High School Merit Honor Distinction','Points transaction logged under High School Merit Honor Distinction.',1,'2026-09-29 00:00:00'),(12,'student',7,250,'Earned','Referred Prospective Peer (Application Started)','Points transaction logged under Referred Prospective Peer (Application Started).',1,'2026-10-06 00:00:00'),(13,'student',2,150,'Earned','Complete Document Submission within 7 Days','Points transaction logged under Complete Document Submission within 7 Days.',1,'2026-10-20 00:00:00'),(14,'student',1,500,'Redeemed','Application Fee Waiver Redeemed','Points transaction logged under Application Fee Waiver Redeemed.',1,'2026-10-30 00:00:00'),(15,'student',3,400,'Redeemed','IELTS Prep Masterclass Pass Redeemed','Points transaction logged under IELTS Prep Masterclass Pass Redeemed.',1,'2026-10-28 00:00:00'),(16,'staff',7,500,'Earned','Monthly Counsellor Conversion Target Reached','Points transaction logged under Monthly Counsellor Conversion Target Reached.',1,'2026-10-06 00:00:00'),(17,'staff',4,300,'Earned','Overdue Task Resolution Record','Points transaction logged under Overdue Task Resolution Record.',1,'2026-10-03 00:00:00'),(18,'staff',5,2500,'Redeemed','Staff Performance Bonus Redeemed','Points transaction logged under Staff Performance Bonus Redeemed.',1,'2026-11-01 00:00:00'),(19,'student',30,200,'Earned','Early Application Submission','Points transaction logged under Early Application Submission.',1,'2026-10-26 00:00:00'),(20,'student',21,300,'Earned','High School Merit Honor Distinction','Points transaction logged under High School Merit Honor Distinction.',1,'2026-10-04 00:00:00'),(21,'student',13,250,'Earned','Referred Prospective Peer (Application Started)','Points transaction logged under Referred Prospective Peer (Application Started).',1,'2026-10-20 00:00:00'),(22,'student',23,150,'Earned','Complete Document Submission within 7 Days','Points transaction logged under Complete Document Submission within 7 Days.',1,'2026-10-15 00:00:00'),(23,'student',23,500,'Redeemed','Application Fee Waiver Redeemed','Points transaction logged under Application Fee Waiver Redeemed.',1,'2026-10-26 00:00:00'),(24,'student',8,400,'Redeemed','IELTS Prep Masterclass Pass Redeemed','Points transaction logged under IELTS Prep Masterclass Pass Redeemed.',1,'2026-10-06 00:00:00'),(25,'staff',4,500,'Earned','Monthly Counsellor Conversion Target Reached','Points transaction logged under Monthly Counsellor Conversion Target Reached.',1,'2026-11-01 00:00:00');
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
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_permissions`
--

LOCK TABLES `role_permissions` WRITE;
/*!40000 ALTER TABLE `role_permissions` DISABLE KEYS */;
INSERT INTO `role_permissions` VALUES (1,1,'dashboard',1,1,1,1,1,1,1,1,1,1),(2,2,'dashboard',1,1,1,0,0,0,0,0,0,0),(3,3,'dashboard',1,0,0,0,0,0,0,0,0,0),(4,1,'college_data',1,1,1,1,1,1,1,1,1,1),(5,2,'college_data',1,1,1,0,1,0,0,1,1,1),(6,3,'college_data',0,0,0,0,0,0,0,0,0,0),(7,1,'web_leads',1,1,1,1,1,1,1,1,1,1),(8,2,'web_leads',1,1,1,1,1,0,0,0,0,0),(9,3,'web_leads',0,0,0,0,0,0,0,0,0,0),(10,1,'leads',1,1,1,1,1,1,1,1,1,1),(11,2,'leads',1,1,1,0,1,1,1,1,1,1),(12,3,'leads',1,1,1,0,0,0,0,0,0,0),(13,1,'customers',1,1,1,1,1,1,1,1,1,1),(14,2,'customers',1,1,1,0,0,0,0,1,1,1),(15,3,'customers',1,0,0,0,0,0,0,0,0,0),(16,1,'admissions',1,1,1,1,1,1,1,1,1,1),(17,2,'admissions',1,1,1,0,0,0,1,0,0,0),(18,3,'admissions',1,0,1,0,0,0,0,0,0,0),(19,1,'tasks',1,1,1,1,1,1,1,1,1,1),(20,2,'tasks',1,1,1,0,1,1,0,0,0,0),(21,3,'tasks',1,1,1,0,0,0,0,0,0,0),(22,1,'invoices',1,1,1,1,1,1,1,1,1,1),(23,2,'invoices',1,1,1,0,0,0,1,1,0,0),(24,3,'invoices',0,0,0,0,0,0,0,0,0,0),(25,1,'payments',1,1,1,1,1,1,1,1,1,1),(26,2,'payments',1,1,1,0,0,0,0,0,0,0),(27,3,'payments',0,0,0,0,0,0,0,0,0,0),(28,1,'documents',1,1,1,1,1,1,1,1,1,1),(29,2,'documents',1,1,1,0,0,0,0,0,0,0),(30,3,'documents',1,1,1,0,0,0,0,0,0,0),(31,1,'branches',1,1,1,1,1,1,1,1,1,1),(32,2,'branches',0,1,1,0,0,0,0,0,0,0),(33,3,'branches',0,0,0,0,0,0,0,0,0,0),(34,1,'staff',1,1,1,1,1,1,1,1,1,1),(35,2,'staff',1,1,1,0,0,0,0,0,0,0),(36,3,'staff',0,0,0,0,0,0,0,0,0,0),(37,1,'roles',1,1,1,1,1,1,1,1,1,1),(38,2,'roles',0,1,1,0,0,0,0,0,0,0),(39,3,'roles',0,0,0,0,0,0,0,0,0,0),(40,1,'reports',1,1,1,1,1,1,1,1,1,1),(41,2,'reports',1,1,1,0,0,0,0,1,0,0),(42,3,'reports',0,0,0,0,0,0,0,0,0,0),(43,1,'archive',1,1,1,1,1,1,1,1,1,1),(44,2,'archive',1,1,1,0,0,0,0,0,0,0),(45,3,'archive',0,0,0,0,0,0,0,0,0,0),(46,1,'notifications',1,1,1,1,1,1,1,1,1,1),(47,2,'notifications',1,1,1,0,0,0,0,0,0,0),(48,3,'notifications',1,0,0,0,0,0,0,0,0,0),(49,1,'settings',1,1,1,1,1,1,1,1,1,1),(50,2,'settings',0,1,1,0,0,0,0,0,0,0),(51,3,'settings',0,0,0,0,0,0,0,0,0,0);
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_documents`
--

LOCK TABLES `student_documents` WRITE;
/*!40000 ALTER TABLE `student_documents` DISABLE KEYS */;
INSERT INTO `student_documents` VALUES (1,1,'Identity','Passport Front & Back Copy','Passport Front & Back Copy','assets/uploads/docs/adithya_passport.pdf','adithya_passport.pdf','adithya_passport.pdf',1200000,4,'2026-11-03 11:11:24'),(2,1,'Academic','B.Tech Degree Consolidated Marksheet','B.Tech Degree Consolidated Marksheet','assets/uploads/docs/adithya_btech_marks.pdf','adithya_btech_marks.pdf','adithya_btech_marks.pdf',2500000,4,'2026-11-03 11:11:24'),(3,1,'English Proficiency','IELTS Official TRF Scorecard (7.5 Band)','IELTS Official TRF Scorecard (7.5 Band)','assets/uploads/docs/adithya_ielts_trf.pdf','adithya_ielts_trf.pdf','adithya_ielts_trf.pdf',980000,4,'2026-11-03 11:11:24'),(4,2,'Identity','Passport Copy','Passport Copy','assets/uploads/docs/benjamin_passport.pdf','benjamin_passport.pdf','benjamin_passport.pdf',1100000,4,'2026-11-03 11:11:24'),(5,2,'Financial & Visa','Trinity College Dublin Offer Letter','Trinity College Dublin Offer Letter','assets/uploads/docs/tcd_offer_letter.pdf','tcd_offer_letter.pdf','tcd_offer_letter.pdf',840000,4,'2026-11-03 11:11:24'),(6,7,'Financial & Visa','UK Visa Vignette & CAS Letter','UK Visa Vignette & CAS Letter','assets/uploads/docs/sandra_cas_letter.pdf','sandra_cas_letter.pdf','sandra_cas_letter.pdf',1450000,5,'2026-11-03 11:11:24');
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
) ENGINE=InnoDB AUTO_INCREMENT=152 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_stage_history`
--

LOCK TABLES `student_stage_history` WRITE;
/*!40000 ALTER TABLE `student_stage_history` DISABLE KEYS */;
INSERT INTO `student_stage_history` VALUES (1,1,1,5,'Applicant successfully advanced to stage 1. Requirements fulfilled and verified.','2026-09-06 00:00:00'),(2,1,2,5,'Applicant successfully advanced to stage 2. Requirements fulfilled and verified.','2026-09-11 00:00:00'),(3,1,3,5,'Applicant successfully advanced to stage 3. Requirements fulfilled and verified.','2026-09-21 00:00:00'),(4,1,4,5,'Applicant successfully advanced to stage 4. Requirements fulfilled and verified.','2026-09-29 00:00:00'),(5,1,5,5,'Applicant successfully advanced to stage 5. Requirements fulfilled and verified.','2026-10-07 00:00:00'),(6,1,6,5,'Applicant successfully advanced to stage 6. Requirements fulfilled and verified.','2026-10-16 00:00:00'),(7,1,7,5,'Applicant successfully advanced to stage 7. Requirements fulfilled and verified.','2026-10-24 00:00:00'),(8,1,8,5,'Applicant successfully advanced to stage 8. Requirements fulfilled and verified.','2026-10-31 00:00:00'),(9,2,1,6,'Applicant successfully advanced to stage 1. Requirements fulfilled and verified.','2026-09-05 00:00:00'),(10,2,2,6,'Applicant successfully advanced to stage 2. Requirements fulfilled and verified.','2026-09-12 00:00:00'),(11,2,3,6,'Applicant successfully advanced to stage 3. Requirements fulfilled and verified.','2026-09-22 00:00:00'),(12,2,4,6,'Applicant successfully advanced to stage 4. Requirements fulfilled and verified.','2026-09-27 00:00:00'),(13,2,5,6,'Applicant successfully advanced to stage 5. Requirements fulfilled and verified.','2026-10-07 00:00:00'),(14,2,6,6,'Applicant successfully advanced to stage 6. Requirements fulfilled and verified.','2026-10-16 00:00:00'),(15,2,7,6,'Applicant successfully advanced to stage 7. Requirements fulfilled and verified.','2026-10-20 00:00:00'),(16,2,8,6,'Applicant successfully advanced to stage 8. Requirements fulfilled and verified.','2026-10-30 00:00:00'),(17,3,1,7,'Applicant successfully advanced to stage 1. Requirements fulfilled and verified.','2026-09-06 00:00:00'),(18,3,2,7,'Applicant successfully advanced to stage 2. Requirements fulfilled and verified.','2026-09-11 00:00:00'),(19,3,3,7,'Applicant successfully advanced to stage 3. Requirements fulfilled and verified.','2026-09-21 00:00:00'),(20,3,4,7,'Applicant successfully advanced to stage 4. Requirements fulfilled and verified.','2026-09-30 00:00:00'),(21,3,5,7,'Applicant successfully advanced to stage 5. Requirements fulfilled and verified.','2026-10-07 00:00:00'),(22,3,6,7,'Applicant successfully advanced to stage 6. Requirements fulfilled and verified.','2026-10-13 00:00:00'),(23,3,7,7,'Applicant successfully advanced to stage 7. Requirements fulfilled and verified.','2026-10-23 00:00:00'),(24,3,8,7,'Applicant successfully advanced to stage 8. Requirements fulfilled and verified.','2026-10-29 00:00:00'),(25,4,1,4,'Applicant successfully advanced to stage 1. Requirements fulfilled and verified.','2026-09-04 00:00:00'),(26,4,2,4,'Applicant successfully advanced to stage 2. Requirements fulfilled and verified.','2026-09-10 00:00:00'),(27,4,3,4,'Applicant successfully advanced to stage 3. Requirements fulfilled and verified.','2026-09-19 00:00:00'),(28,4,4,4,'Applicant successfully advanced to stage 4. Requirements fulfilled and verified.','2026-09-28 00:00:00'),(29,4,5,4,'Applicant successfully advanced to stage 5. Requirements fulfilled and verified.','2026-10-07 00:00:00'),(30,4,6,4,'Applicant successfully advanced to stage 6. Requirements fulfilled and verified.','2026-10-14 00:00:00'),(31,4,7,4,'Applicant successfully advanced to stage 7. Requirements fulfilled and verified.','2026-10-24 00:00:00'),(32,4,8,4,'Applicant successfully advanced to stage 8. Requirements fulfilled and verified.','2026-10-30 00:00:00'),(33,5,1,5,'Applicant successfully advanced to stage 1. Requirements fulfilled and verified.','2026-09-05 00:00:00'),(34,5,2,5,'Applicant successfully advanced to stage 2. Requirements fulfilled and verified.','2026-09-11 00:00:00'),(35,5,3,5,'Applicant successfully advanced to stage 3. Requirements fulfilled and verified.','2026-09-20 00:00:00'),(36,5,4,5,'Applicant successfully advanced to stage 4. Requirements fulfilled and verified.','2026-09-30 00:00:00'),(37,5,5,5,'Applicant successfully advanced to stage 5. Requirements fulfilled and verified.','2026-10-06 00:00:00'),(38,5,6,5,'Applicant successfully advanced to stage 6. Requirements fulfilled and verified.','2026-10-12 00:00:00'),(39,5,7,5,'Applicant successfully advanced to stage 7. Requirements fulfilled and verified.','2026-10-23 00:00:00'),(40,5,8,5,'Applicant successfully advanced to stage 8. Requirements fulfilled and verified.','2026-10-29 00:00:00'),(41,6,1,6,'Applicant successfully advanced to stage 1. Requirements fulfilled and verified.','2026-09-06 00:00:00'),(42,6,2,6,'Applicant successfully advanced to stage 2. Requirements fulfilled and verified.','2026-09-14 00:00:00'),(43,6,3,6,'Applicant successfully advanced to stage 3. Requirements fulfilled and verified.','2026-09-22 00:00:00'),(44,6,4,6,'Applicant successfully advanced to stage 4. Requirements fulfilled and verified.','2026-09-27 00:00:00'),(45,6,5,6,'Applicant successfully advanced to stage 5. Requirements fulfilled and verified.','2026-10-08 00:00:00'),(46,6,6,6,'Applicant successfully advanced to stage 6. Requirements fulfilled and verified.','2026-10-16 00:00:00'),(47,6,7,6,'Applicant successfully advanced to stage 7. Requirements fulfilled and verified.','2026-10-24 00:00:00'),(48,6,8,6,'Applicant successfully advanced to stage 8. Requirements fulfilled and verified.','2026-11-01 00:00:00'),(49,7,1,7,'Applicant successfully advanced to stage 1. Requirements fulfilled and verified.','2026-09-05 00:00:00'),(50,7,2,7,'Applicant successfully advanced to stage 2. Requirements fulfilled and verified.','2026-09-10 00:00:00'),(51,7,3,7,'Applicant successfully advanced to stage 3. Requirements fulfilled and verified.','2026-09-18 00:00:00'),(52,7,4,7,'Applicant successfully advanced to stage 4. Requirements fulfilled and verified.','2026-09-28 00:00:00'),(53,7,5,7,'Applicant successfully advanced to stage 5. Requirements fulfilled and verified.','2026-10-06 00:00:00'),(54,7,6,7,'Applicant successfully advanced to stage 6. Requirements fulfilled and verified.','2026-10-16 00:00:00'),(55,7,7,7,'Applicant successfully advanced to stage 7. Requirements fulfilled and verified.','2026-10-24 00:00:00'),(56,7,8,7,'Applicant successfully advanced to stage 8. Requirements fulfilled and verified.','2026-10-29 00:00:00'),(57,8,1,4,'Applicant successfully advanced to stage 1. Requirements fulfilled and verified.','2026-09-02 00:00:00'),(58,8,2,4,'Applicant successfully advanced to stage 2. Requirements fulfilled and verified.','2026-09-14 00:00:00'),(59,8,3,4,'Applicant successfully advanced to stage 3. Requirements fulfilled and verified.','2026-09-18 00:00:00'),(60,8,4,4,'Applicant successfully advanced to stage 4. Requirements fulfilled and verified.','2026-09-27 00:00:00'),(61,8,5,4,'Applicant successfully advanced to stage 5. Requirements fulfilled and verified.','2026-10-04 00:00:00'),(62,8,6,4,'Applicant successfully advanced to stage 6. Requirements fulfilled and verified.','2026-10-16 00:00:00'),(63,8,7,4,'Applicant successfully advanced to stage 7. Requirements fulfilled and verified.','2026-10-22 00:00:00'),(64,8,8,4,'Applicant successfully advanced to stage 8. Requirements fulfilled and verified.','2026-11-01 00:00:00'),(65,9,1,5,'Applicant successfully advanced to stage 1. Requirements fulfilled and verified.','2026-09-05 00:00:00'),(66,9,2,5,'Applicant successfully advanced to stage 2. Requirements fulfilled and verified.','2026-09-13 00:00:00'),(67,9,3,5,'Applicant successfully advanced to stage 3. Requirements fulfilled and verified.','2026-09-18 00:00:00'),(68,9,4,5,'Applicant successfully advanced to stage 4. Requirements fulfilled and verified.','2026-09-26 00:00:00'),(69,9,5,5,'Applicant successfully advanced to stage 5. Requirements fulfilled and verified.','2026-10-08 00:00:00'),(70,9,6,5,'Applicant successfully advanced to stage 6. Requirements fulfilled and verified.','2026-10-14 00:00:00'),(71,9,7,5,'Applicant successfully advanced to stage 7. Requirements fulfilled and verified.','2026-10-22 00:00:00'),(72,10,1,6,'Applicant successfully advanced to stage 1. Requirements fulfilled and verified.','2026-09-05 00:00:00'),(73,10,2,6,'Applicant successfully advanced to stage 2. Requirements fulfilled and verified.','2026-09-13 00:00:00'),(74,10,3,6,'Applicant successfully advanced to stage 3. Requirements fulfilled and verified.','2026-09-18 00:00:00'),(75,10,4,6,'Applicant successfully advanced to stage 4. Requirements fulfilled and verified.','2026-09-28 00:00:00'),(76,10,5,6,'Applicant successfully advanced to stage 5. Requirements fulfilled and verified.','2026-10-07 00:00:00'),(77,10,6,6,'Applicant successfully advanced to stage 6. Requirements fulfilled and verified.','2026-10-12 00:00:00'),(78,10,7,6,'Applicant successfully advanced to stage 7. Requirements fulfilled and verified.','2026-10-21 00:00:00'),(79,11,1,7,'Applicant successfully advanced to stage 1. Requirements fulfilled and verified.','2026-09-05 00:00:00'),(80,11,2,7,'Applicant successfully advanced to stage 2. Requirements fulfilled and verified.','2026-09-14 00:00:00'),(81,11,3,7,'Applicant successfully advanced to stage 3. Requirements fulfilled and verified.','2026-09-20 00:00:00'),(82,11,4,7,'Applicant successfully advanced to stage 4. Requirements fulfilled and verified.','2026-09-28 00:00:00'),(83,11,5,7,'Applicant successfully advanced to stage 5. Requirements fulfilled and verified.','2026-10-06 00:00:00'),(84,11,6,7,'Applicant successfully advanced to stage 6. Requirements fulfilled and verified.','2026-10-15 00:00:00'),(85,11,7,7,'Applicant successfully advanced to stage 7. Requirements fulfilled and verified.','2026-10-20 00:00:00'),(86,12,1,4,'Applicant successfully advanced to stage 1. Requirements fulfilled and verified.','2026-09-06 00:00:00'),(87,12,2,4,'Applicant successfully advanced to stage 2. Requirements fulfilled and verified.','2026-09-12 00:00:00'),(88,12,3,4,'Applicant successfully advanced to stage 3. Requirements fulfilled and verified.','2026-09-19 00:00:00'),(89,12,4,4,'Applicant successfully advanced to stage 4. Requirements fulfilled and verified.','2026-09-30 00:00:00'),(90,12,5,4,'Applicant successfully advanced to stage 5. Requirements fulfilled and verified.','2026-10-04 00:00:00'),(91,12,6,4,'Applicant successfully advanced to stage 6. Requirements fulfilled and verified.','2026-10-13 00:00:00'),(92,12,7,4,'Applicant successfully advanced to stage 7. Requirements fulfilled and verified.','2026-10-21 00:00:00'),(93,13,1,5,'Applicant successfully advanced to stage 1. Requirements fulfilled and verified.','2026-09-02 00:00:00'),(94,13,2,5,'Applicant successfully advanced to stage 2. Requirements fulfilled and verified.','2026-09-13 00:00:00'),(95,13,3,5,'Applicant successfully advanced to stage 3. Requirements fulfilled and verified.','2026-09-20 00:00:00'),(96,13,4,5,'Applicant successfully advanced to stage 4. Requirements fulfilled and verified.','2026-09-30 00:00:00'),(97,13,5,5,'Applicant successfully advanced to stage 5. Requirements fulfilled and verified.','2026-10-05 00:00:00'),(98,13,6,5,'Applicant successfully advanced to stage 6. Requirements fulfilled and verified.','2026-10-16 00:00:00'),(99,13,7,5,'Applicant successfully advanced to stage 7. Requirements fulfilled and verified.','2026-10-21 00:00:00'),(100,14,1,6,'Applicant successfully advanced to stage 1. Requirements fulfilled and verified.','2026-09-06 00:00:00'),(101,14,2,6,'Applicant successfully advanced to stage 2. Requirements fulfilled and verified.','2026-09-14 00:00:00'),(102,14,3,6,'Applicant successfully advanced to stage 3. Requirements fulfilled and verified.','2026-09-20 00:00:00'),(103,14,4,6,'Applicant successfully advanced to stage 4. Requirements fulfilled and verified.','2026-09-30 00:00:00'),(104,14,5,6,'Applicant successfully advanced to stage 5. Requirements fulfilled and verified.','2026-10-07 00:00:00'),(105,14,6,6,'Applicant successfully advanced to stage 6. Requirements fulfilled and verified.','2026-10-13 00:00:00'),(106,14,7,6,'Applicant successfully advanced to stage 7. Requirements fulfilled and verified.','2026-10-20 00:00:00'),(107,15,1,7,'Applicant successfully advanced to stage 1. Requirements fulfilled and verified.','2026-09-06 00:00:00'),(108,15,2,7,'Applicant successfully advanced to stage 2. Requirements fulfilled and verified.','2026-09-13 00:00:00'),(109,15,3,7,'Applicant successfully advanced to stage 3. Requirements fulfilled and verified.','2026-09-18 00:00:00'),(110,15,4,7,'Applicant successfully advanced to stage 4. Requirements fulfilled and verified.','2026-09-30 00:00:00'),(111,15,5,7,'Applicant successfully advanced to stage 5. Requirements fulfilled and verified.','2026-10-06 00:00:00'),(112,15,6,7,'Applicant successfully advanced to stage 6. Requirements fulfilled and verified.','2026-10-16 00:00:00'),(113,15,7,7,'Applicant successfully advanced to stage 7. Requirements fulfilled and verified.','2026-10-22 00:00:00'),(114,16,1,4,'Applicant successfully advanced to stage 1. Requirements fulfilled and verified.','2026-09-02 00:00:00'),(115,16,2,4,'Applicant successfully advanced to stage 2. Requirements fulfilled and verified.','2026-09-13 00:00:00'),(116,16,3,4,'Applicant successfully advanced to stage 3. Requirements fulfilled and verified.','2026-09-21 00:00:00'),(117,16,4,4,'Applicant successfully advanced to stage 4. Requirements fulfilled and verified.','2026-09-29 00:00:00'),(118,16,5,4,'Applicant successfully advanced to stage 5. Requirements fulfilled and verified.','2026-10-08 00:00:00'),(119,16,6,4,'Applicant successfully advanced to stage 6. Requirements fulfilled and verified.','2026-10-15 00:00:00'),(120,16,7,4,'Applicant successfully advanced to stage 7. Requirements fulfilled and verified.','2026-10-21 00:00:00'),(121,17,1,5,'Applicant successfully advanced to stage 1. Requirements fulfilled and verified.','2026-09-06 00:00:00'),(122,17,2,5,'Applicant successfully advanced to stage 2. Requirements fulfilled and verified.','2026-09-14 00:00:00'),(123,17,3,5,'Applicant successfully advanced to stage 3. Requirements fulfilled and verified.','2026-09-22 00:00:00'),(124,17,4,5,'Applicant successfully advanced to stage 4. Requirements fulfilled and verified.','2026-09-27 00:00:00'),(125,17,5,5,'Applicant successfully advanced to stage 5. Requirements fulfilled and verified.','2026-10-08 00:00:00'),(126,17,6,5,'Applicant successfully advanced to stage 6. Requirements fulfilled and verified.','2026-10-12 00:00:00'),(127,18,1,6,'Applicant successfully advanced to stage 1. Requirements fulfilled and verified.','2026-09-02 00:00:00'),(128,18,2,6,'Applicant successfully advanced to stage 2. Requirements fulfilled and verified.','2026-09-11 00:00:00'),(129,18,3,6,'Applicant successfully advanced to stage 3. Requirements fulfilled and verified.','2026-09-20 00:00:00'),(130,18,4,6,'Applicant successfully advanced to stage 4. Requirements fulfilled and verified.','2026-09-28 00:00:00'),(131,18,5,6,'Applicant successfully advanced to stage 5. Requirements fulfilled and verified.','2026-10-06 00:00:00'),(132,18,6,6,'Applicant successfully advanced to stage 6. Requirements fulfilled and verified.','2026-10-15 00:00:00'),(133,19,1,7,'Applicant successfully advanced to stage 1. Requirements fulfilled and verified.','2026-09-04 00:00:00'),(134,19,2,7,'Applicant successfully advanced to stage 2. Requirements fulfilled and verified.','2026-09-14 00:00:00'),(135,19,3,7,'Applicant successfully advanced to stage 3. Requirements fulfilled and verified.','2026-09-20 00:00:00'),(136,19,4,7,'Applicant successfully advanced to stage 4. Requirements fulfilled and verified.','2026-09-26 00:00:00'),(137,19,5,7,'Applicant successfully advanced to stage 5. Requirements fulfilled and verified.','2026-10-04 00:00:00'),(138,19,6,7,'Applicant successfully advanced to stage 6. Requirements fulfilled and verified.','2026-10-14 00:00:00'),(139,20,1,4,'Applicant successfully advanced to stage 1. Requirements fulfilled and verified.','2026-09-05 00:00:00'),(140,20,2,4,'Applicant successfully advanced to stage 2. Requirements fulfilled and verified.','2026-09-12 00:00:00'),(141,20,3,4,'Applicant successfully advanced to stage 3. Requirements fulfilled and verified.','2026-09-18 00:00:00'),(142,20,4,4,'Applicant successfully advanced to stage 4. Requirements fulfilled and verified.','2026-09-27 00:00:00'),(143,20,5,4,'Applicant successfully advanced to stage 5. Requirements fulfilled and verified.','2026-10-05 00:00:00'),(144,20,6,4,'Applicant successfully advanced to stage 6. Requirements fulfilled and verified.','2026-10-16 00:00:00');
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
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `students`
--

LOCK TABLES `students` WRITE;
/*!40000 ALTER TABLE `students` DISABLE KEYS */;
INSERT INTO `students` VALUES (1,'STU-2026-001','Liam Jones','liam.jones1@example.com','+1 (555) 590-1887','+1 (555) 590-1887','2004-12-02','Male','United Kingdom','England','London','High School Diploma (IB/A-Levels)',2,2,NULL,NULL,'Fall 2026','2026-10-13','Website Enquiry',4,'Moderate',8,'Confirmed',1337,'Prospective student interested in study opportunities for Fall 2026. Contacted via Website Enquiry. Initial documentation reviewed.','2026-11-02 06:25:36','2026-11-02 06:25:36'),(2,'STU-2026-002','Olivia Garcia','olivia.garcia2@example.com','+1 (555) 821-4752','+1 (555) 821-4752','1999-02-08','Female','Canada','Ontario','Toronto','Bachelor of Science in Computer Science',3,3,NULL,NULL,'Spring 2027','2026-10-29','Education Fair Dubai',5,'Low',8,'Confirmed',922,'Prospective student interested in study opportunities for Spring 2027. Contacted via Education Fair Dubai. Initial documentation reviewed.','2026-11-02 06:25:36','2026-11-02 06:25:36'),(3,'STU-2026-003','Noah Miller','noah.miller3@example.com','+1 (555) 350-2446','+1 (555) 350-2446','2002-04-10','Male','Australia','Victoria','Melbourne','Bachelor of Business Administration',4,4,NULL,NULL,'Fall 2027','2026-07-14','Google Ads Campaign',6,'High',8,'Confirmed',937,'Prospective student interested in study opportunities for Fall 2027. Contacted via Google Ads Campaign. Initial documentation reviewed.','2026-11-02 06:25:36','2026-11-02 06:25:36'),(4,'STU-2026-004','Emma Davis','emma.davis4@example.com','+1 (555) 231-9048','+1 (555) 231-9048','2006-12-22','Female','United States','California','San Francisco','Higher Secondary Certificate (Grade 12)',5,5,NULL,NULL,'Summer 2026','2026-10-29','Instagram Campaign',7,'Moderate',8,'Confirmed',1314,'Prospective student interested in study opportunities for Summer 2026. Contacted via Instagram Campaign. Initial documentation reviewed.','2026-11-02 06:25:36','2026-11-02 06:25:36'),(5,'STU-2026-005','Oliver Rodriguez','oliver.rodriguez5@example.com','+1 (555) 209-6802','+1 (555) 209-6802','2005-01-18','Male','India','Maharashtra','Mumbai','Bachelor of Engineering (Mechanical)',6,6,NULL,NULL,'Fall 2026','2026-08-13','Agent Referral',4,'Low',8,'Confirmed',1078,'Prospective student interested in study opportunities for Fall 2026. Contacted via Agent Referral. Initial documentation reviewed.','2026-11-02 06:25:36','2026-11-02 06:25:36'),(6,'STU-2026-006','Ava Martinez','ava.martinez6@example.com','+1 (555) 992-7202','+1 (555) 992-7202','2005-03-27','Female','Singapore','Central','Singapore','Associate Degree in Information Technology',7,7,NULL,NULL,'Spring 2027','2026-07-07','Direct Walk-in',5,'High',8,'Confirmed',929,'Prospective student interested in study opportunities for Spring 2027. Contacted via Direct Walk-in. Initial documentation reviewed.','2026-11-02 06:25:36','2026-11-02 06:25:36'),(7,'STU-2026-007','Elijah Hernandez','elijah.hernandez7@example.com','+1 (555) 259-2727','+1 (555) 259-2727','1998-04-21','Male','Germany','Bavaria','Munich','Bachelor of Arts in Economics',8,8,NULL,NULL,'Fall 2027','2026-10-16','LinkedIn Higher Ed',6,'Moderate',8,'Confirmed',1280,'Prospective student interested in study opportunities for Fall 2027. Contacted via LinkedIn Higher Ed. Initial documentation reviewed.','2026-11-02 06:25:36','2026-11-02 06:25:36'),(8,'STU-2026-008','Charlotte Lopez','charlotte.lopez8@example.com','+1 (555) 655-1839','+1 (555) 655-1839','2004-08-27','Female','Ireland','Leinster','Dublin','B.Sc. in Biotechnology',1,9,NULL,NULL,'Summer 2026','2026-10-28','University Webinar',7,'Low',8,'Confirmed',1083,'Prospective student interested in study opportunities for Summer 2026. Contacted via University Webinar. Initial documentation reviewed.','2026-11-02 06:25:36','2026-11-02 06:25:36'),(9,'STU-2026-009','William Gonzalez','william.gonzalez9@example.com','+1 (555) 534-8354','+1 (555) 534-8354','2003-11-14','Male','United Arab Emirates','Dubai','Dubai','High School Diploma (IB/A-Levels)',2,10,NULL,NULL,'Fall 2026','2026-10-01','Website Enquiry',4,'High',7,'In Progress',728,'Prospective student interested in study opportunities for Fall 2026. Contacted via Website Enquiry. Initial documentation reviewed.','2026-11-02 06:25:36','2026-11-02 06:25:36'),(10,'STU-2026-010','Sophia Wilson','sophia.wilson10@example.com','+1 (555) 885-3364','+1 (555) 885-3364','2007-08-06','Female','Nigeria','Lagos','Lagos','Bachelor of Science in Computer Science',3,1,NULL,NULL,'Spring 2027','2026-07-12','Education Fair Dubai',5,'Moderate',7,'In Progress',605,'Prospective student interested in study opportunities for Spring 2027. Contacted via Education Fair Dubai. Initial documentation reviewed.','2026-11-02 06:25:36','2026-11-02 06:25:36'),(11,'STU-2026-011','James Anderson','james.anderson11@example.com','+1 (555) 762-1076','+1 (555) 762-1076','2001-12-11','Male','Malaysia','Selangor','Kuala Lumpur','Bachelor of Business Administration',4,2,NULL,NULL,'Fall 2027','2026-08-31','Google Ads Campaign',6,'Low',7,'In Progress',627,'Prospective student interested in study opportunities for Fall 2027. Contacted via Google Ads Campaign. Initial documentation reviewed.','2026-11-02 06:25:36','2026-11-02 06:25:36'),(12,'STU-2026-012','Amelia Thomas','amelia.thomas12@example.com','+1 (555) 331-9524','+1 (555) 331-9524','2003-09-13','Female','France','Ile-de-France','Paris','Higher Secondary Certificate (Grade 12)',5,3,NULL,NULL,'Summer 2026','2026-08-15','Instagram Campaign',7,'High',7,'In Progress',649,'Prospective student interested in study opportunities for Summer 2026. Contacted via Instagram Campaign. Initial documentation reviewed.','2026-11-02 06:25:36','2026-11-02 06:25:36'),(13,'STU-2026-013','Benjamin Taylor','benjamin.taylor13@example.com','+1 (555) 273-8657','+1 (555) 273-8657','2007-02-07','Male','Brazil','Sao Paulo','Sao Paulo','Bachelor of Engineering (Mechanical)',6,4,NULL,NULL,'Fall 2026','2026-09-14','Agent Referral',4,'Moderate',7,'In Progress',751,'Prospective student interested in study opportunities for Fall 2026. Contacted via Agent Referral. Initial documentation reviewed.','2026-11-02 06:25:36','2026-11-02 06:25:36'),(14,'STU-2026-014','Isabella Moore','isabella.moore14@example.com','+1 (555) 202-5578','+1 (555) 202-5578','2000-08-15','Female','Japan','Kanto','Tokyo','Associate Degree in Information Technology',7,5,NULL,NULL,'Spring 2027','2026-07-05','Direct Walk-in',5,'Low',7,'In Progress',716,'Prospective student interested in study opportunities for Spring 2027. Contacted via Direct Walk-in. Initial documentation reviewed.','2026-11-02 06:25:36','2026-11-02 06:25:36'),(15,'STU-2026-015','Lucas Jackson','lucas.jackson15@example.com','+1 (555) 492-8571','+1 (555) 492-8571','2000-02-25','Male','South Africa','Gauteng','Johannesburg','Bachelor of Arts in Economics',8,6,NULL,NULL,'Fall 2027','2026-09-25','LinkedIn Higher Ed',6,'High',7,'In Progress',530,'Prospective student interested in study opportunities for Fall 2027. Contacted via LinkedIn Higher Ed. Initial documentation reviewed.','2026-11-02 06:25:36','2026-11-02 06:25:36'),(16,'STU-2026-016','Mia Martin','mia.martin16@example.com','+1 (555) 894-7650','+1 (555) 894-7650','2002-06-19','Female','United Kingdom','England','London','B.Sc. in Biotechnology',1,7,NULL,NULL,'Summer 2026','2026-10-03','University Webinar',7,'Moderate',7,'In Progress',839,'Prospective student interested in study opportunities for Summer 2026. Contacted via University Webinar. Initial documentation reviewed.','2026-11-02 06:25:36','2026-11-02 06:25:36'),(17,'STU-2026-017','Henry Lee','henry.lee17@example.com','+1 (555) 779-5357','+1 (555) 779-5357','2000-12-13','Male','Canada','Ontario','Toronto','High School Diploma (IB/A-Levels)',2,8,NULL,NULL,'Fall 2026','2026-07-22','Website Enquiry',4,'Low',6,'In Progress',444,'Prospective student interested in study opportunities for Fall 2026. Contacted via Website Enquiry. Initial documentation reviewed.','2026-11-02 06:25:36','2026-11-02 06:25:36'),(18,'STU-2026-018','Evelyn Perez','evelyn.perez18@example.com','+1 (555) 794-4847','+1 (555) 794-4847','2005-05-13','Female','Australia','Victoria','Melbourne','Bachelor of Science in Computer Science',3,9,NULL,NULL,'Spring 2027','2026-09-06','Education Fair Dubai',5,'High',6,'In Progress',426,'Prospective student interested in study opportunities for Spring 2027. Contacted via Education Fair Dubai. Initial documentation reviewed.','2026-11-02 06:25:36','2026-11-02 06:25:36'),(19,'STU-2026-019','Alexander Thompson','alexander.thompson19@example.com','+1 (555) 926-4477','+1 (555) 926-4477','2007-03-12','Male','United States','California','San Francisco','Bachelor of Business Administration',4,10,NULL,NULL,'Fall 2027','2026-10-06','Google Ads Campaign',6,'Moderate',6,'In Progress',403,'Prospective student interested in study opportunities for Fall 2027. Contacted via Google Ads Campaign. Initial documentation reviewed.','2026-11-02 06:25:36','2026-11-02 06:25:36'),(20,'STU-2026-020','Harper White','harper.white20@example.com','+1 (555) 381-1489','+1 (555) 381-1489','2007-02-10','Female','India','Maharashtra','Mumbai','Higher Secondary Certificate (Grade 12)',5,1,NULL,NULL,'Summer 2026','2026-08-20','Instagram Campaign',7,'Low',6,'In Progress',520,'Prospective student interested in study opportunities for Summer 2026. Contacted via Instagram Campaign. Initial documentation reviewed.','2026-11-02 06:25:36','2026-11-02 06:25:36'),(21,'STU-2026-021','Sebastian Harris','sebastian.harris21@example.com','+1 (555) 944-5060','+1 (555) 944-5060','2002-10-09','Male','Singapore','Central','Singapore','Bachelor of Engineering (Mechanical)',6,2,NULL,NULL,'Fall 2026','2026-09-28','Agent Referral',4,'High',6,'In Progress',563,'Prospective student interested in study opportunities for Fall 2026. Contacted via Agent Referral. Initial documentation reviewed.','2026-11-02 06:25:36','2026-11-02 06:25:36'),(22,'STU-2026-022','Luna Sanchez','luna.sanchez22@example.com','+1 (555) 627-5469','+1 (555) 627-5469','2007-06-01','Female','Germany','Bavaria','Munich','Associate Degree in Information Technology',7,3,NULL,NULL,'Spring 2027','2026-08-14','Direct Walk-in',5,'Moderate',6,'In Progress',458,'Prospective student interested in study opportunities for Spring 2027. Contacted via Direct Walk-in. Initial documentation reviewed.','2026-11-02 06:25:36','2026-11-02 06:25:36'),(23,'STU-2026-023','Jack Clark','jack.clark23@example.com','+1 (555) 246-2459','+1 (555) 246-2459','2006-07-12','Male','Ireland','Leinster','Dublin','Bachelor of Arts in Economics',8,4,NULL,NULL,'Fall 2027','2026-07-31','LinkedIn Higher Ed',6,'Low',6,'In Progress',653,'Prospective student interested in study opportunities for Fall 2027. Contacted via LinkedIn Higher Ed. Initial documentation reviewed.','2026-11-02 06:25:36','2026-11-02 06:25:36'),(24,'STU-2026-024','Camila Ramirez','camila.ramirez24@example.com','+1 (555) 683-7750','+1 (555) 683-7750','2005-01-22','Female','United Arab Emirates','Dubai','Dubai','B.Sc. in Biotechnology',1,5,NULL,NULL,'Summer 2026','2026-09-05','University Webinar',7,'High',6,'In Progress',570,'Prospective student interested in study opportunities for Summer 2026. Contacted via University Webinar. Initial documentation reviewed.','2026-11-02 06:25:36','2026-11-02 06:25:36'),(25,'STU-2026-025','Daniel Lewis','daniel.lewis25@example.com','+1 (555) 355-7617','+1 (555) 355-7617','2006-09-18','Male','Nigeria','Lagos','Lagos','High School Diploma (IB/A-Levels)',2,6,NULL,NULL,'Fall 2026','2026-09-19','Website Enquiry',4,'Moderate',5,'In Progress',327,'Prospective student interested in study opportunities for Fall 2026. Contacted via Website Enquiry. Initial documentation reviewed.','2026-11-02 06:25:36','2026-11-02 06:25:36'),(26,'STU-2026-026','Gianna Robinson','gianna.robinson26@example.com','+1 (555) 378-1763','+1 (555) 378-1763','1999-06-04','Female','Malaysia','Selangor','Kuala Lumpur','Bachelor of Science in Computer Science',3,7,NULL,NULL,'Spring 2027','2026-07-17','Education Fair Dubai',5,'Low',5,'In Progress',311,'Prospective student interested in study opportunities for Spring 2027. Contacted via Education Fair Dubai. Initial documentation reviewed.','2026-11-02 06:25:36','2026-11-02 06:25:36'),(27,'STU-2026-027','Matthew Walker','matthew.walker27@example.com','+1 (555) 261-5376','+1 (555) 261-5376','2001-08-07','Male','France','Ile-de-France','Paris','Bachelor of Business Administration',4,8,NULL,NULL,'Fall 2027','2026-09-19','Google Ads Campaign',6,'High',5,'In Progress',384,'Prospective student interested in study opportunities for Fall 2027. Contacted via Google Ads Campaign. Initial documentation reviewed.','2026-11-02 06:25:36','2026-11-02 06:25:36'),(28,'STU-2026-028','Elizabeth Young','elizabeth.young28@example.com','+1 (555) 623-2312','+1 (555) 623-2312','2001-05-12','Female','Brazil','Sao Paulo','Sao Paulo','Higher Secondary Certificate (Grade 12)',5,9,NULL,NULL,'Summer 2026','2026-08-01','Instagram Campaign',7,'Moderate',5,'In Progress',268,'Prospective student interested in study opportunities for Summer 2026. Contacted via Instagram Campaign. Initial documentation reviewed.','2026-11-02 06:25:36','2026-11-02 06:25:36'),(29,'STU-2026-029','Aiden Allen','aiden.allen29@example.com','+1 (555) 362-8243','+1 (555) 362-8243','2003-02-24','Male','Japan','Kanto','Tokyo','Bachelor of Engineering (Mechanical)',6,10,NULL,NULL,'Fall 2026','2026-10-31','Agent Referral',4,'Low',5,'In Progress',435,'Prospective student interested in study opportunities for Fall 2026. Contacted via Agent Referral. Initial documentation reviewed.','2026-11-02 06:25:36','2026-11-02 06:25:36'),(30,'STU-2026-030','Eleanor King','eleanor.king30@example.com','+1 (555) 729-9057','+1 (555) 729-9057','2005-07-01','Female','South Africa','Gauteng','Johannesburg','Associate Degree in Information Technology',7,1,NULL,NULL,'Spring 2027','2026-08-08','Direct Walk-in',5,'High',5,'In Progress',239,'Prospective student interested in study opportunities for Spring 2027. Contacted via Direct Walk-in. Initial documentation reviewed.','2026-11-02 06:25:36','2026-11-02 06:25:36'),(31,'STU-2026-031','Samuel Wright','samuel.wright31@example.com','+1 (555) 501-9849','+1 (555) 501-9849','1999-03-21','Male','United Kingdom','England','London','Bachelor of Arts in Economics',8,2,NULL,NULL,'Fall 2027','2026-07-07','LinkedIn Higher Ed',6,'Moderate',5,'In Progress',275,'Prospective student interested in study opportunities for Fall 2027. Contacted via LinkedIn Higher Ed. Initial documentation reviewed.','2026-11-02 06:25:36','2026-11-02 06:25:36'),(32,'STU-2026-032','Ella Scott','ella.scott32@example.com','+1 (555) 732-9417','+1 (555) 732-9417','2001-08-20','Female','Canada','Ontario','Toronto','B.Sc. in Biotechnology',1,3,NULL,NULL,'Summer 2026','2026-08-20','University Webinar',7,'Low',5,'In Progress',447,'Prospective student interested in study opportunities for Summer 2026. Contacted via University Webinar. Initial documentation reviewed.','2026-11-02 06:25:36','2026-11-02 06:25:36'),(33,'STU-2026-033','David Torres','david.torres33@example.com','+1 (555) 811-7333','+1 (555) 811-7333','2000-01-15','Male','Australia','Victoria','Melbourne','High School Diploma (IB/A-Levels)',2,4,NULL,NULL,'Fall 2026','2026-07-16','Website Enquiry',4,'High',4,'In Progress',253,'Prospective student interested in study opportunities for Fall 2026. Contacted via Website Enquiry. Initial documentation reviewed.','2026-11-02 06:25:36','2026-11-02 06:25:36'),(34,'STU-2026-034','Abigail Nguyen','abigail.nguyen34@example.com','+1 (555) 214-9221','+1 (555) 214-9221','1998-06-08','Female','United States','California','San Francisco','Bachelor of Science in Computer Science',3,5,NULL,NULL,'Spring 2027','2026-07-30','Education Fair Dubai',5,'Moderate',4,'In Progress',127,'Prospective student interested in study opportunities for Spring 2027. Contacted via Education Fair Dubai. Initial documentation reviewed.','2026-11-02 06:25:36','2026-11-02 06:25:36'),(35,'STU-2026-035','Joseph Hill','joseph.hill35@example.com','+1 (555) 758-8518','+1 (555) 758-8518','2003-03-17','Male','India','Maharashtra','Mumbai','Bachelor of Business Administration',4,6,NULL,NULL,'Fall 2027','2026-09-13','Google Ads Campaign',6,'Low',4,'In Progress',197,'Prospective student interested in study opportunities for Fall 2027. Contacted via Google Ads Campaign. Initial documentation reviewed.','2026-11-02 06:25:36','2026-11-02 06:25:36'),(36,'STU-2026-036','Sofia Flores','sofia.flores36@example.com','+1 (555) 271-8262','+1 (555) 271-8262','2006-03-16','Female','Singapore','Central','Singapore','Higher Secondary Certificate (Grade 12)',5,7,NULL,NULL,'Summer 2026','2026-08-04','Instagram Campaign',7,'High',4,'In Progress',243,'Prospective student interested in study opportunities for Summer 2026. Contacted via Instagram Campaign. Initial documentation reviewed.','2026-11-02 06:25:36','2026-11-02 06:25:36'),(37,'STU-2026-037','Carter Green','carter.green37@example.com','+1 (555) 432-8075','+1 (555) 432-8075','2001-12-25','Male','Germany','Bavaria','Munich','Bachelor of Engineering (Mechanical)',6,8,NULL,NULL,'Fall 2026','2026-10-20','Agent Referral',4,'Moderate',4,'In Progress',142,'Prospective student interested in study opportunities for Fall 2026. Contacted via Agent Referral. Initial documentation reviewed.','2026-11-02 06:25:36','2026-11-02 06:25:36'),(38,'STU-2026-038','Avery Adams','avery.adams38@example.com','+1 (555) 572-7891','+1 (555) 572-7891','2006-07-16','Female','Ireland','Leinster','Dublin','Associate Degree in Information Technology',7,9,NULL,NULL,'Spring 2027','2026-08-14','Direct Walk-in',5,'Low',4,'In Progress',112,'Prospective student interested in study opportunities for Spring 2027. Contacted via Direct Walk-in. Initial documentation reviewed.','2026-11-02 06:25:36','2026-11-02 06:25:36'),(39,'STU-2026-039','Owen Nelson','owen.nelson39@example.com','+1 (555) 850-4360','+1 (555) 850-4360','2004-01-06','Male','United Arab Emirates','Dubai','Dubai','Bachelor of Arts in Economics',8,10,NULL,NULL,'Fall 2027','2026-10-16','LinkedIn Higher Ed',6,'High',3,'In Progress',134,'Prospective student interested in study opportunities for Fall 2027. Contacted via LinkedIn Higher Ed. Initial documentation reviewed.','2026-11-02 06:25:36','2026-11-02 06:25:36'),(40,'STU-2026-040','Scarlett Baker','scarlett.baker40@example.com','+1 (555) 357-7683','+1 (555) 357-7683','2005-12-02','Female','Nigeria','Lagos','Lagos','B.Sc. in Biotechnology',1,1,NULL,NULL,'Summer 2026','2026-09-05','University Webinar',7,'Moderate',3,'In Progress',109,'Prospective student interested in study opportunities for Summer 2026. Contacted via University Webinar. Initial documentation reviewed.','2026-11-02 06:25:36','2026-11-02 06:25:36'),(41,'STU-2026-041','Wyatt Hall','wyatt.hall41@example.com','+1 (555) 289-8621','+1 (555) 289-8621','1998-01-09','Male','Malaysia','Selangor','Kuala Lumpur','High School Diploma (IB/A-Levels)',2,2,NULL,NULL,'Fall 2026','2026-07-23','Website Enquiry',4,'Low',3,'In Progress',88,'Prospective student interested in study opportunities for Fall 2026. Contacted via Website Enquiry. Initial documentation reviewed.','2026-11-02 06:25:36','2026-11-02 06:25:36'),(42,'STU-2026-042','Emily Rivera','emily.rivera42@example.com','+1 (555) 222-7637','+1 (555) 222-7637','1999-01-15','Female','France','Ile-de-France','Paris','Bachelor of Science in Computer Science',3,3,NULL,NULL,'Spring 2027','2026-09-06','Education Fair Dubai',5,'High',3,'In Progress',123,'Prospective student interested in study opportunities for Spring 2027. Contacted via Education Fair Dubai. Initial documentation reviewed.','2026-11-02 06:25:36','2026-11-02 06:25:36'),(43,'STU-2026-043','John Campbell','john.campbell43@example.com','+1 (555) 558-4682','+1 (555) 558-4682','2001-11-16','Male','Brazil','Sao Paulo','Sao Paulo','Bachelor of Business Administration',4,4,NULL,NULL,'Fall 2027','2026-10-05','Google Ads Campaign',6,'Moderate',2,'In Progress',50,'Prospective student interested in study opportunities for Fall 2027. Contacted via Google Ads Campaign. Initial documentation reviewed.','2026-11-02 06:25:36','2026-11-02 06:25:36'),(44,'STU-2026-044','Aria Mitchell','aria.mitchell44@example.com','+1 (555) 200-6803','+1 (555) 200-6803','2007-06-05','Female','Japan','Kanto','Tokyo','Higher Secondary Certificate (Grade 12)',5,5,NULL,NULL,'Summer 2026','2026-10-22','Instagram Campaign',7,'Low',2,'In Progress',50,'Prospective student interested in study opportunities for Summer 2026. Contacted via Instagram Campaign. Initial documentation reviewed.','2026-11-02 06:25:36','2026-11-02 06:25:36'),(45,'STU-2026-045','Luke Carter','luke.carter45@example.com','+1 (555) 878-7668','+1 (555) 878-7668','2002-02-13','Male','South Africa','Gauteng','Johannesburg','Bachelor of Engineering (Mechanical)',6,6,NULL,NULL,'Fall 2026','2026-10-17','Agent Referral',4,'High',2,'On Hold',50,'Prospective student interested in study opportunities for Fall 2026. Contacted via Agent Referral. Initial documentation reviewed.','2026-11-02 06:25:36','2026-11-02 06:25:36'),(46,'STU-2026-046','Penelope Roberts','penelope.roberts46@example.com','+1 (555) 645-7166','+1 (555) 645-7166','2003-04-28','Female','United Kingdom','England','London','Associate Degree in Information Technology',7,7,NULL,NULL,'Spring 2027','2026-10-30','Direct Walk-in',5,'Moderate',2,'In Progress',50,'Prospective student interested in study opportunities for Spring 2027. Contacted via Direct Walk-in. Initial documentation reviewed.','2026-11-02 06:25:36','2026-11-02 06:25:36'),(47,'STU-2026-047','Asher Smith','asher.smith47@example.com','+1 (555) 938-7868','+1 (555) 938-7868','2004-03-08','Male','Canada','Ontario','Toronto','Bachelor of Arts in Economics',8,8,NULL,NULL,'Fall 2027','2026-08-22','LinkedIn Higher Ed',6,'Low',1,'In Progress',0,'Prospective student interested in study opportunities for Fall 2027. Contacted via LinkedIn Higher Ed. Initial documentation reviewed.','2026-11-02 06:25:36','2026-11-02 06:25:36'),(48,'STU-2026-048','Chloe Johnson','chloe.johnson48@example.com','+1 (555) 200-9839','+1 (555) 200-9839','2001-07-05','Female','Australia','Victoria','Melbourne','B.Sc. in Biotechnology',1,9,NULL,NULL,'Summer 2026','2026-09-25','University Webinar',7,'High',1,'In Progress',0,'Prospective student interested in study opportunities for Summer 2026. Contacted via University Webinar. Initial documentation reviewed.','2026-11-02 06:25:36','2026-11-02 06:25:36'),(49,'STU-2026-049','Leo Williams','leo.williams49@example.com','+1 (555) 364-5929','+1 (555) 364-5929','2003-05-26','Male','United States','California','San Francisco','High School Diploma (IB/A-Levels)',2,10,NULL,NULL,'Fall 2026','2026-09-14','Website Enquiry',4,'Moderate',1,'In Progress',0,'Prospective student interested in study opportunities for Fall 2026. Contacted via Website Enquiry. Initial documentation reviewed.','2026-11-02 06:25:36','2026-11-02 06:25:36'),(50,'STU-2026-050','Layla Brown','layla.brown50@example.com','+1 (555) 548-2690','+1 (555) 548-2690','2002-06-15','Female','India','Maharashtra','Mumbai','Bachelor of Science in Computer Science',3,1,NULL,NULL,'Spring 2027','2026-09-27','Education Fair Dubai',5,'Low',1,'Admission Lost',0,'Prospective student interested in study opportunities for Spring 2027. Contacted via Education Fair Dubai. Initial documentation reviewed.','2026-11-02 06:25:36','2026-11-02 06:25:36');
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
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tasks`
--

LOCK TABLES `tasks` WRITE;
/*!40000 ALTER TABLE `tasks` DISABLE KEYS */;
INSERT INTO `tasks` VALUES (1,'Verify high school transcripts and certified translations','Comprehensive action required for Verify high school transcripts and certified translations as part of admissions workflow cycle.','Student Follow-up',4,6,'2026-11-02','High','In Progress','2026-11-02 06:25:36','2026-11-02 06:25:36'),(2,'Follow up on pending financial sponsor affidavit','Comprehensive action required for Follow up on pending financial sponsor affidavit as part of admissions workflow cycle.','Document Collection',5,21,'2026-11-02','Moderate','In Progress','2026-11-02 06:25:36','2026-11-02 06:25:36'),(3,'Submit CAS / I-20 university issuance request','Comprehensive action required for Submit CAS / I-20 university issuance request as part of admissions workflow cycle.','Application Completion',6,16,'2026-11-02','Low','In Progress','2026-11-02 06:25:36','2026-11-02 06:25:36'),(4,'Conduct preliminary mock visa interview','Comprehensive action required for Conduct preliminary mock visa interview as part of admissions workflow cycle.','Admission Verification',7,36,'2026-11-02','High','In Progress','2026-11-02 06:25:36','2026-11-02 06:25:36'),(5,'Collect updated English proficiency test scores','Comprehensive action required for Collect updated English proficiency test scores as part of admissions workflow cycle.','General Task',4,31,'2026-11-02','Moderate','In Progress','2026-11-02 06:25:36','2026-11-02 06:25:36'),(6,'Review personal statement and letter of recommendation draft','Comprehensive action required for Review personal statement and letter of recommendation draft as part of admissions workflow cycle.','Student Follow-up',5,6,'2026-11-02','Low','In Progress','2026-11-02 06:25:36','2026-11-02 06:25:36'),(7,'Schedule virtual appointment with Department Dean','Comprehensive action required for Schedule virtual appointment with Department Dean as part of admissions workflow cycle.','Document Collection',6,49,'2026-10-30','High','Overdue','2026-11-02 06:25:36','2026-11-02 06:25:36'),(8,'Send conditional admission offer package to student email','Comprehensive action required for Send conditional admission offer package to student email as part of admissions workflow cycle.','Application Completion',7,34,'2026-10-30','Moderate','Overdue','2026-11-02 06:25:36','2026-11-02 06:25:36'),(9,'Audit scholarship qualification credentials','Comprehensive action required for Audit scholarship qualification credentials as part of admissions workflow cycle.','Admission Verification',4,37,'2026-10-30','Low','Overdue','2026-11-02 06:25:36','2026-11-02 06:25:36'),(10,'Upload certified passport bio-page copy to admissions portal','Comprehensive action required for Upload certified passport bio-page copy to admissions portal as part of admissions workflow cycle.','General Task',5,47,'2026-10-31','High','Overdue','2026-11-02 06:25:36','2026-11-02 06:25:36'),(11,'Verify high school transcripts and certified translations','Comprehensive action required for Verify high school transcripts and certified translations as part of admissions workflow cycle.','Student Follow-up',6,41,'2026-10-31','Moderate','Overdue','2026-11-02 06:25:36','2026-11-02 06:25:36'),(12,'Follow up on pending financial sponsor affidavit','Comprehensive action required for Follow up on pending financial sponsor affidavit as part of admissions workflow cycle.','Document Collection',7,33,'2026-10-29','Low','Overdue','2026-11-02 06:25:36','2026-11-02 06:25:36'),(13,'Submit CAS / I-20 university issuance request','Comprehensive action required for Submit CAS / I-20 university issuance request as part of admissions workflow cycle.','Application Completion',4,47,'2026-11-13','High','Pending','2026-11-02 06:25:36','2026-11-02 06:25:36'),(14,'Conduct preliminary mock visa interview','Comprehensive action required for Conduct preliminary mock visa interview as part of admissions workflow cycle.','Admission Verification',5,40,'2026-11-13','Moderate','Pending','2026-11-02 06:25:36','2026-11-02 06:25:36'),(15,'Collect updated English proficiency test scores','Comprehensive action required for Collect updated English proficiency test scores as part of admissions workflow cycle.','General Task',6,49,'2026-11-04','Low','Pending','2026-11-02 06:25:36','2026-11-02 06:25:36'),(16,'Review personal statement and letter of recommendation draft','Comprehensive action required for Review personal statement and letter of recommendation draft as part of admissions workflow cycle.','Student Follow-up',7,46,'2026-11-07','High','Pending','2026-11-02 06:25:36','2026-11-02 06:25:36'),(17,'Schedule virtual appointment with Department Dean','Comprehensive action required for Schedule virtual appointment with Department Dean as part of admissions workflow cycle.','Document Collection',4,14,'2026-11-10','Moderate','Pending','2026-11-02 06:25:36','2026-11-02 06:25:36'),(18,'Send conditional admission offer package to student email','Comprehensive action required for Send conditional admission offer package to student email as part of admissions workflow cycle.','Application Completion',5,25,'2026-11-09','Low','Pending','2026-11-02 06:25:36','2026-11-02 06:25:36'),(19,'Audit scholarship qualification credentials','Comprehensive action required for Audit scholarship qualification credentials as part of admissions workflow cycle.','Admission Verification',6,40,'2026-10-29','High','Completed','2026-11-02 06:25:36','2026-11-02 06:25:36'),(20,'Upload certified passport bio-page copy to admissions portal','Comprehensive action required for Upload certified passport bio-page copy to admissions portal as part of admissions workflow cycle.','General Task',7,33,'2026-10-20','Moderate','Completed','2026-11-02 06:25:36','2026-11-02 06:25:36'),(21,'Verify high school transcripts and certified translations','Comprehensive action required for Verify high school transcripts and certified translations as part of admissions workflow cycle.','Student Follow-up',4,23,'2026-10-22','Low','Completed','2026-11-02 06:25:36','2026-11-02 06:25:36'),(22,'Follow up on pending financial sponsor affidavit','Comprehensive action required for Follow up on pending financial sponsor affidavit as part of admissions workflow cycle.','Document Collection',5,10,'2026-10-20','High','Completed','2026-11-02 06:25:36','2026-11-02 06:25:36'),(23,'Submit CAS / I-20 university issuance request','Comprehensive action required for Submit CAS / I-20 university issuance request as part of admissions workflow cycle.','Application Completion',6,25,'2026-10-27','Moderate','Completed','2026-11-02 06:25:36','2026-11-02 06:25:36'),(24,'Conduct preliminary mock visa interview','Comprehensive action required for Conduct preliminary mock visa interview as part of admissions workflow cycle.','Admission Verification',7,24,'2026-10-29','Low','Completed','2026-11-02 06:25:36','2026-11-02 06:25:36'),(25,'Collect updated English proficiency test scores','Comprehensive action required for Collect updated English proficiency test scores as part of admissions workflow cycle.','General Task',4,41,'2026-10-25','High','Completed','2026-11-02 06:25:36','2026-11-02 06:25:36');
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
INSERT INTO `universities` VALUES (1,'Oxford Global University','United Kingdom','Oxford','QS World #4','oxford.ac.uk'),(2,'University of Toronto','Canada','Toronto','QS World #18','utoronto.ca'),(3,'University of Melbourne','Australia','Melbourne','QS World #27','unimelb.edu.au'),(4,'National University of Singapore','Singapore','Singapore','QS World #11','nus.edu.sg'),(5,'Technical University of Munich','Germany','Munich','QS World #37','tum.de'),(6,'University of California, Berkeley','United States','Berkeley','QS World #10','berkeley.edu'),(7,'Trinity College Dublin','Ireland','Dublin','QS World #81','tcd.ie'),(8,'Imperial College London','United Kingdom','London','QS World #6','imperial.ac.uk');
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
INSERT INTO `users` VALUES (1,NULL,1,NULL,NULL,'Dr. Arthur Vance','superadmin@university.edu','$2y$10$MowjzN729btxidk5wJI3z.sMr4y5VbuFGhCP2VAkrsgTmqAJkbvz2','+91 98470 12345','Chief Executive & Admissions Director',NULL,'Active','2026-11-03 11:11:24',NULL),(2,1,2,NULL,NULL,'Marcus Sterling','manager@university.edu','$2y$10$MowjzN729btxidk5wJI3z.sMr4y5VbuFGhCP2VAkrsgTmqAJkbvz2','+91 98470 23456','Admissions Manager',NULL,'Active','2026-11-03 11:11:24','2026-11-03 14:43:01'),(3,2,3,2,2,'Priya Sharma','priya@university.edu','$2y$10$MowjzN729btxidk5wJI3z.sMr4y5VbuFGhCP2VAkrsgTmqAJkbvz2','+91 98470 34567','Senior Admissions Counselor',NULL,'Active','2026-11-03 11:11:24','2026-11-03 14:43:01'),(4,1,3,2,2,'David Miller','david@university.edu','$2y$10$MowjzN729btxidk5wJI3z.sMr4y5VbuFGhCP2VAkrsgTmqAJkbvz2','+91 98470 45678','Admissions Counselor',NULL,'Active','2026-11-03 11:11:24','2026-11-03 14:43:01'),(5,1,3,2,2,'Sarah Jenkins','sarah@university.edu','$2y$10$MowjzN729btxidk5wJI3z.sMr4y5VbuFGhCP2VAkrsgTmqAJkbvz2','+91 98470 56789','Academic Advisor',NULL,'Active','2026-11-03 11:11:24','2026-11-03 14:43:01'),(6,2,3,2,2,'Alex Chen','alex@university.edu','$2y$10$MowjzN729btxidk5wJI3z.sMr4y5VbuFGhCP2VAkrsgTmqAJkbvz2','+91 98470 67890','Admissions Counselor',NULL,'Active','2026-11-03 11:11:24','2026-11-03 14:43:01'),(7,2,3,2,2,'Ananya Nair','ananya@university.edu','$2y$10$MowjzN729btxidk5wJI3z.sMr4y5VbuFGhCP2VAkrsgTmqAJkbvz2','+91 98470 78901','Admissions Counselor',NULL,'Active','2026-11-03 11:11:24','2026-11-03 14:43:01');
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `web_leads`
--

LOCK TABLES `web_leads` WRITE;
/*!40000 ALTER TABLE `web_leads` DISABLE KEYS */;
INSERT INTO `web_leads` VALUES (1,'Nikhil Varma','Nikhil Varma','+91 98472 22001','+91 98472 22001','9847222001','nikhil.varma@outlook.com','Kochi, Kerala','MSc Data Science','University of Manchester','September 2026','UK',1,1,'Facebook Lead Ads - Fall 2026',NULL,'Facebook Lead Ads - Fall 2026','Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-03 11:11:24'),(2,'Anjali Nair','Anjali Nair','+91 98472 22002','+91 98472 22002','9847222002','anjali.nair99@gmail.com','Calicut, Kerala','Master of Public Health','Monash University','September 2026','Australia',2,2,'Google Ads - Australia Healthcare',NULL,'Google Ads - Australia Healthcare','Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-03 11:11:24'),(3,'Kiran Joseph','Kiran Joseph','+91 98472 22003','+91 98472 22003','9847222003','kiran.joseph@yahoo.com','Kottayam, Kerala','MBA Global Banking','Dublin City University','September 2026','Ireland',1,1,'Website Contact Form',NULL,'Website Contact Form','Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-03 11:11:24'),(4,'Reshma Ravindran','Reshma Ravindran','+91 98472 22004','+91 98472 22004','9847222004','reshma.r@gmail.com','Kannur, Kerala','MSc Artificial Intelligence','University of Leeds','September 2026','UK',3,3,'Instagram Ad Campaign',NULL,'Instagram Ad Campaign','Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-03 11:11:24');
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

-- Dump completed on 2026-11-07 11:45:13
