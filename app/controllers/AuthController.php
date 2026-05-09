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


class AuthController
{
    protected $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    // GET /login.php or /login.php?a=login
    public function login()
    {
        if (!empty($_SESSION['user_id'])) {
            header('Location: dashboard.php');
            exit;
        }

        // simple CSRF token
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $error = $_GET['error'] ?? null;

        include __DIR__ . '/../views/auth/login.php';
    }

    // POST /login.php?a=doLogin
    public function doLogin()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: login.php');
            exit;
        }

        // CSRF check
        if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
            header('Location: login.php?error=invalid_csrf');
            exit;
        }

        // honeypot check
        if (!empty($_POST['website'])) {
            // bot
            header('Location: login.php?error=unknown');
            exit;
        }

        $email    = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            header('Location: login.php?error=missing_fields');
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header('Location: login.php?error=invalid_email');
            exit;
        }

        // yahan apna users table ka query lagao
        $sql  = "SELECT id, full_name, password_hash, status FROM phphr_users WHERE email = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            header('Location: login.php?error=db_error');
            exit;
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user   = $result->fetch_assoc();
        $stmt->close();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            header('Location: login.php?error=invalid_login');
            exit;
        }

        if ((int)($user['status'] ?? 0) !== 1) {
            header('Location: login.php?error=inactive_account');
            exit;
        }

        // login success
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_name'] = $user['full_name'] ?? 'User';

        // role load (fallback to admin for legacy DB before migration)
        $roleStmt = $this->conn->prepare(
            "SELECT r.role_key
             FROM phphr_user_roles ur
             INNER JOIN phphr_roles r ON r.id = ur.role_id
             WHERE ur.user_id = ?
             ORDER BY ur.id ASC
             LIMIT 1"
        );
        if ($roleStmt) {
            $uid = (int)$user['id'];
            $roleStmt->bind_param("i", $uid);
            $roleStmt->execute();
            $roleRow = $roleStmt->get_result()->fetch_assoc();
            $roleStmt->close();
            $_SESSION['user_role'] = $roleRow['role_key'] ?? 'admin';
        } else {
            $_SESSION['user_role'] = 'admin';
        }

        header('Location: dashboard.php');
        exit;
    }

    // optional: logout action
    public function logout()
    {
        $_SESSION = [];
        session_destroy();
        header('Location: index.php');
        exit;
    }
	

	
}

