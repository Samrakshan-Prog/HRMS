<?php
/* 
=======================================================================
 TF â€” Open Source HR Management Software
 Version: 3.0
 License: MIT Open Source License
 Developed & Maintained By: TF (https://www.tf.com)
 
 Description:
 TF is a Human Resource Management (HRM) platform available in
 two deployment models: TF Open-Source, a free, self-hosted
 edition for essential HR operations, and TF Cloud, a fully
 managed, enterprise-grade HRMS. TF is designed to help
 organizations manage Employees, Attendance, Leaves, Payroll,
 and core HR workflows with simplicity, security, and speed.

 Core Modules Included (Open-Source Edition):
 - Employee Management
 - Attendance Tracking
 - Leave Management
 - Payroll Management
 - HR Dashboard
 - Secure Login

 This software is open for modification and extension.
 You are free to customize, improve, and commercially use the
 Open-Source edition without licensing fees, provided this
 copyright notice remains preserved.

 Website:
 https://www.tf.com

 Live Demo:
 https://www.tf.com/hr-software-demo/

 Download:
 https://www.tf.com/download-hr-software/

 Community Contribution:
 Developers are welcome to contribute improvements, bug fixes,
 and enhancements to the Open-Source edition. Please visit our
 website for documentation, updates, and community support.

 Last Update: 14-12-2025
=======================================================================
*/
	
// app/config/config.php - basic application configuration

define('APP_NAME', 'Dynamic Interaction Portal for HR and Employee Credit Repay');
define('APP_VERSION', '8.0.3');
define('company_name', 'Thirumurugan Finance');
define('company_logo', './media/logo-white.png');
define('company_logo_home', './media/logo.png');




// Base URL helper (adjust if deploying in subfolder)
if (!defined('BASE_URL')) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path   = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    define('BASE_URL', $scheme . '://' . $host . $path . '/');
}

