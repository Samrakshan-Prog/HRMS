-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: phphr_free
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
-- Table structure for table `phphr_attendance`
--

DROP TABLE IF EXISTS `phphr_attendance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `phphr_attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `check_in` time DEFAULT NULL,
  `check_out` time DEFAULT NULL,
  `status` enum('present','absent','late','half_day') DEFAULT 'present',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_attendance_emp_date` (`employee_id`,`attendance_date`),
  KEY `idx_attendance_employee` (`employee_id`),
  CONSTRAINT `fk_attendance_employee` FOREIGN KEY (`employee_id`) REFERENCES `phphr_employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phphr_attendance`
--

LOCK TABLES `phphr_attendance` WRITE;
/*!40000 ALTER TABLE `phphr_attendance` DISABLE KEYS */;
INSERT INTO `phphr_attendance` VALUES (1,2,'2026-02-12','23:17:48','23:17:51','present','2026-02-12 17:47:48'),(2,1,'2026-02-16','12:16:07','12:16:10','present','2026-02-16 06:46:07'),(3,1,'2026-03-16','23:58:05','23:58:07','present','2026-03-16 18:28:05');
/*!40000 ALTER TABLE `phphr_attendance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `phphr_employee_membership`
--

DROP TABLE IF EXISTS `phphr_employee_membership`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `phphr_employee_membership` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `membership_no` varchar(100) NOT NULL,
  `membership_type` varchar(100) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('active','expired','suspended') NOT NULL DEFAULT 'active',
  `approved_by` int(10) unsigned DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_membership_no` (`membership_no`),
  KEY `idx_membership_employee` (`employee_id`),
  KEY `idx_membership_approved_by` (`approved_by`),
  CONSTRAINT `fk_membership_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `phphr_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_membership_employee` FOREIGN KEY (`employee_id`) REFERENCES `phphr_employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phphr_employee_membership`
--

LOCK TABLES `phphr_employee_membership` WRITE;
/*!40000 ALTER TABLE `phphr_employee_membership` DISABLE KEYS */;
/*!40000 ALTER TABLE `phphr_employee_membership` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `phphr_employee_performance`
--

DROP TABLE IF EXISTS `phphr_employee_performance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `phphr_employee_performance` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `evaluation_month` varchar(20) NOT NULL,
  `test_score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `attendance_score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `overall_score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `grade` varchar(20) DEFAULT NULL,
  `promotion_status` enum('none','recommended','approved','rejected') NOT NULL DEFAULT 'none',
  `promoted_designation` varchar(100) DEFAULT NULL,
  `promotion_effective_date` date DEFAULT NULL,
  `promotion_note` varchar(255) DEFAULT NULL,
  `reviewer_user_id` int(10) unsigned DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_perf_emp_month` (`employee_id`,`evaluation_month`),
  KEY `idx_perf_reviewer` (`reviewer_user_id`),
  CONSTRAINT `fk_perf_employee` FOREIGN KEY (`employee_id`) REFERENCES `phphr_employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_perf_reviewer` FOREIGN KEY (`reviewer_user_id`) REFERENCES `phphr_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phphr_employee_performance`
--

LOCK TABLES `phphr_employee_performance` WRITE;
/*!40000 ALTER TABLE `phphr_employee_performance` DISABLE KEYS */;
/*!40000 ALTER TABLE `phphr_employee_performance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `phphr_employees`
--

DROP TABLE IF EXISTS `phphr_employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `phphr_employees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `employee_code` varchar(50) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `department` varchar(200) DEFAULT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `date_of_joining` date DEFAULT NULL,
  `salary` decimal(10,2) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_employees_code` (`employee_code`),
  KEY `idx_employees_user` (`user_id`),
  CONSTRAINT `fk_employees_user` FOREIGN KEY (`user_id`) REFERENCES `phphr_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phphr_employees`
--

LOCK TABLES `phphr_employees` WRITE;
/*!40000 ALTER TABLE `phphr_employees` DISABLE KEYS */;
INSERT INTO `phphr_employees` VALUES (1,4,'EMP-901','Employee','One','9000000001','Operations','Executive','2025-01-10',32000.00,1,'2026-02-12 17:41:20'),(2,5,'EMP-902','Employee','Two','9000000002','Sales','Associate','2025-02-01',28000.00,1,'2026-02-12 17:41:20');
/*!40000 ALTER TABLE `phphr_employees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `phphr_leaves`
--

DROP TABLE IF EXISTS `phphr_leaves`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `phphr_leaves` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `leave_type` enum('casual','sick','paid','unpaid') DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_leaves_employee` (`employee_id`),
  CONSTRAINT `fk_leaves_employee` FOREIGN KEY (`employee_id`) REFERENCES `phphr_employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phphr_leaves`
--

LOCK TABLES `phphr_leaves` WRITE;
/*!40000 ALTER TABLE `phphr_leaves` DISABLE KEYS */;
/*!40000 ALTER TABLE `phphr_leaves` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `phphr_loan_repayments`
--

DROP TABLE IF EXISTS `phphr_loan_repayments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `phphr_loan_repayments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `loan_id` int(10) unsigned NOT NULL,
  `due_date` date NOT NULL,
  `paid_date` date DEFAULT NULL,
  `amount_due` decimal(12,2) NOT NULL,
  `amount_paid` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payment_status` enum('pending','paid','overdue','partial') NOT NULL DEFAULT 'pending',
  `payment_mode` enum('cash','bank_transfer','upi','salary_deduction') DEFAULT NULL,
  `reference_no` varchar(120) DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_repay_loan` (`loan_id`),
  KEY `idx_repay_due_date` (`due_date`),
  KEY `idx_repay_status` (`payment_status`),
  CONSTRAINT `fk_repay_loan` FOREIGN KEY (`loan_id`) REFERENCES `phphr_loans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phphr_loan_repayments`
--

LOCK TABLES `phphr_loan_repayments` WRITE;
/*!40000 ALTER TABLE `phphr_loan_repayments` DISABLE KEYS */;
/*!40000 ALTER TABLE `phphr_loan_repayments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `phphr_loan_status_history`
--

DROP TABLE IF EXISTS `phphr_loan_status_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `phphr_loan_status_history` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `loan_id` int(10) unsigned NOT NULL,
  `old_status` varchar(30) DEFAULT NULL,
  `new_status` varchar(30) NOT NULL,
  `changed_by` int(10) unsigned DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `note` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_history_loan` (`loan_id`),
  KEY `fk_history_user` (`changed_by`),
  CONSTRAINT `fk_history_loan` FOREIGN KEY (`loan_id`) REFERENCES `phphr_loans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_history_user` FOREIGN KEY (`changed_by`) REFERENCES `phphr_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phphr_loan_status_history`
--

LOCK TABLES `phphr_loan_status_history` WRITE;
/*!40000 ALTER TABLE `phphr_loan_status_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `phphr_loan_status_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `phphr_loans`
--

DROP TABLE IF EXISTS `phphr_loans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `phphr_loans` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `requested_amount` decimal(12,2) NOT NULL,
  `approved_amount` decimal(12,2) DEFAULT NULL,
  `interest_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `tenure_months` int(11) NOT NULL,
  `emi_amount` decimal(12,2) DEFAULT NULL,
  `purpose` varchar(255) DEFAULT NULL,
  `request_date` date NOT NULL,
  `approved_date` date DEFAULT NULL,
  `status` enum('pending','approved','rejected','closed') NOT NULL DEFAULT 'pending',
  `remarks` text DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_loans_employee` (`employee_id`),
  KEY `idx_loans_status` (`status`),
  KEY `idx_loans_created_by` (`created_by`),
  CONSTRAINT `fk_loans_created_by` FOREIGN KEY (`created_by`) REFERENCES `phphr_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_loans_employee` FOREIGN KEY (`employee_id`) REFERENCES `phphr_employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phphr_loans`
--

LOCK TABLES `phphr_loans` WRITE;
/*!40000 ALTER TABLE `phphr_loans` DISABLE KEYS */;
/*!40000 ALTER TABLE `phphr_loans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `phphr_messages`
--

DROP TABLE IF EXISTS `phphr_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `phphr_messages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `sender_user_id` int(10) unsigned NOT NULL,
  `receiver_user_id` int(10) unsigned DEFAULT NULL,
  `context_type` enum('general','loan','repayment','attendance','leave') NOT NULL DEFAULT 'general',
  `context_id` int(10) unsigned DEFAULT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_messages_employee` (`employee_id`),
  KEY `idx_messages_sender` (`sender_user_id`),
  KEY `idx_messages_receiver` (`receiver_user_id`),
  CONSTRAINT `fk_messages_employee` FOREIGN KEY (`employee_id`) REFERENCES `phphr_employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_messages_receiver` FOREIGN KEY (`receiver_user_id`) REFERENCES `phphr_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_messages_sender` FOREIGN KEY (`sender_user_id`) REFERENCES `phphr_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phphr_messages`
--

LOCK TABLES `phphr_messages` WRITE;
/*!40000 ALTER TABLE `phphr_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `phphr_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `phphr_notifications`
--

DROP TABLE IF EXISTS `phphr_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `phphr_notifications` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `title` varchar(160) NOT NULL,
  `body` varchar(255) NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_notify_user_read` (`user_id`,`is_read`),
  CONSTRAINT `fk_notify_user` FOREIGN KEY (`user_id`) REFERENCES `phphr_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phphr_notifications`
--

LOCK TABLES `phphr_notifications` WRITE;
/*!40000 ALTER TABLE `phphr_notifications` DISABLE KEYS */;
INSERT INTO `phphr_notifications` VALUES (1,1,'New Leave Request','A new leave request has been submitted and needs review.','leaves.php',0,'2026-03-16 18:32:22'),(2,2,'New Leave Request','A new leave request has been submitted and needs review.','leaves.php',0,'2026-03-16 18:32:22');
/*!40000 ALTER TABLE `phphr_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `phphr_payroll`
--

DROP TABLE IF EXISTS `phphr_payroll`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `phphr_payroll` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `salary_month` varchar(20) DEFAULT NULL,
  `basic_salary` decimal(10,2) DEFAULT NULL,
  `allowances` decimal(10,2) DEFAULT 0.00,
  `bonus` decimal(10,2) NOT NULL DEFAULT 0.00,
  `leave_days` int(11) NOT NULL DEFAULT 0,
  `leave_deduction` decimal(10,2) NOT NULL DEFAULT 0.00,
  `loan_emi_deduction` decimal(10,2) NOT NULL DEFAULT 0.00,
  `deductions` decimal(10,2) DEFAULT 0.00,
  `total_deductions` decimal(10,2) NOT NULL DEFAULT 0.00,
  `net_salary` decimal(10,2) DEFAULT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_payroll_employee` (`employee_id`),
  CONSTRAINT `fk_payroll_employee` FOREIGN KEY (`employee_id`) REFERENCES `phphr_employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phphr_payroll`
--

LOCK TABLES `phphr_payroll` WRITE;
/*!40000 ALTER TABLE `phphr_payroll` DISABLE KEYS */;
/*!40000 ALTER TABLE `phphr_payroll` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `phphr_promotion_history`
--

DROP TABLE IF EXISTS `phphr_promotion_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `phphr_promotion_history` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `performance_id` int(10) unsigned DEFAULT NULL,
  `old_designation` varchar(100) DEFAULT NULL,
  `new_designation` varchar(100) NOT NULL,
  `effective_date` date NOT NULL,
  `approved_by` int(10) unsigned DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_promo_employee` (`employee_id`),
  KEY `idx_promo_performance` (`performance_id`),
  KEY `fk_promo_user` (`approved_by`),
  CONSTRAINT `fk_promo_employee` FOREIGN KEY (`employee_id`) REFERENCES `phphr_employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_promo_performance` FOREIGN KEY (`performance_id`) REFERENCES `phphr_employee_performance` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_promo_user` FOREIGN KEY (`approved_by`) REFERENCES `phphr_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phphr_promotion_history`
--

LOCK TABLES `phphr_promotion_history` WRITE;
/*!40000 ALTER TABLE `phphr_promotion_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `phphr_promotion_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `phphr_roles`
--

DROP TABLE IF EXISTS `phphr_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `phphr_roles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `role_key` varchar(50) NOT NULL,
  `role_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_role_key` (`role_key`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phphr_roles`
--

LOCK TABLES `phphr_roles` WRITE;
/*!40000 ALTER TABLE `phphr_roles` DISABLE KEYS */;
INSERT INTO `phphr_roles` VALUES (1,'admin','Administrator','2026-02-12 17:41:20'),(2,'hr','HR Manager','2026-02-12 17:41:20'),(3,'finance','Finance Manager','2026-02-12 17:41:20'),(4,'employee','Employee','2026-02-12 17:41:20');
/*!40000 ALTER TABLE `phphr_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `phphr_user_roles`
--

DROP TABLE IF EXISTS `phphr_user_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `phphr_user_roles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `role_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_role` (`user_id`,`role_id`),
  KEY `idx_user_roles_user` (`user_id`),
  KEY `idx_user_roles_role` (`role_id`),
  CONSTRAINT `fk_user_roles_role` FOREIGN KEY (`role_id`) REFERENCES `phphr_roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_roles_user` FOREIGN KEY (`user_id`) REFERENCES `phphr_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phphr_user_roles`
--

LOCK TABLES `phphr_user_roles` WRITE;
/*!40000 ALTER TABLE `phphr_user_roles` DISABLE KEYS */;
INSERT INTO `phphr_user_roles` VALUES (1,1,1,'2026-02-12 17:41:20'),(2,4,4,'2026-02-12 17:41:20'),(3,5,4,'2026-02-12 17:41:20'),(4,3,3,'2026-02-12 17:41:20'),(5,2,2,'2026-02-12 17:41:20');
/*!40000 ALTER TABLE `phphr_user_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `phphr_users`
--

DROP TABLE IF EXISTS `phphr_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `phphr_users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(150) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_username` (`username`),
  UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phphr_users`
--

LOCK TABLES `phphr_users` WRITE;
/*!40000 ALTER TABLE `phphr_users` DISABLE KEYS */;
INSERT INTO `phphr_users` VALUES (1,'admin@tf.com','admin@tf.com','$2y$10$inLmpujTu.KiQESHoEQAC.lysqKG45IxtNWmNeQqrdABXgDCoQ7i6','TF Administrator',1,'2026-02-12 17:41:20'),(2,'hr.manager@tf.com','hr.manager@tf.com','$2y$10$7WAq93xONNCmPry1G0lwZuRQmBR58VKmpT6qa12I7TeB1Dh1X48h2','TF HR Manager',1,'2026-02-12 17:41:20'),(3,'finance.manager@tf.com','finance.manager@tf.com','$2y$10$LGRPKWjv99f0UJTJQWjtkuM/fHuGoa//c6Y.p2F7Wh9FDSHuUberK','TF Finance Manager',1,'2026-02-12 17:41:20'),(4,'employee.one@tf.com','employee.one@tf.com','$2y$10$Xq1ezZaZG9aE7p8HbAqf/.hzIKXOC.GXlU4CiIV9DHSQ57m7YJLtu','TF Employee One',1,'2026-02-12 17:41:20'),(5,'employee.two@tf.com','employee.two@tf.com','$2y$10$daH2wK7IlrdFzg5nFgFgiexlXV..6ep5OVLERadIEb8QXCYWXyQ1m','TF Employee Two',1,'2026-02-12 17:41:20');
/*!40000 ALTER TABLE `phphr_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'phphr_free'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-17  0:16:47
