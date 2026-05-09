<?php
	
// app/controllers/ChangePasswordController.php
require_once __DIR__ . '/../core/Authz.php';
require_once __DIR__ . '/../core/Security.php';

class ChangePasswordController
{
    protected $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
        require_roles($conn, ['admin', 'hr', 'finance', 'employee']);
    }

    public function index()
    {
        $success = $_SESSION['success'] ?? null;
        $error   = $_SESSION['error'] ?? null;
        unset($_SESSION['success'], $_SESSION['error']);

        include __DIR__ . '/../views/change_password/index.php';
    }

    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: change_password.php");
            exit;
        }

        if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
            $_SESSION['error'] = "Security token expired. Please try again.";
            header("Location: change_password.php");
            exit;
        }

        $current_pass = trim($_POST['current_password'] ?? '');
        $new_pass     = trim($_POST['new_password'] ?? '');
        $confirm_pass = trim($_POST['confirm_password'] ?? '');
        $user_id      = $_SESSION['user_id'];

        if ($current_pass === '' || $new_pass === '' || $confirm_pass === '') {
            $_SESSION['error'] = "All fields are required.";
            header("Location: change_password.php");
            exit;
        }

        if ($new_pass !== $confirm_pass) {
            $_SESSION['error'] = "New passwords do not match.";
            header("Location: change_password.php");
            exit;
        }

        // get current hash
        $sql  = "SELECT password_hash FROM phphr_users WHERE id = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user   = $result->fetch_assoc();
        $stmt->close();

        if (!$user || !password_verify($current_pass, $user['password_hash'])) {
            $_SESSION['error'] = "Current password is incorrect.";
            header("Location: change_password.php");
            exit;
        }

        // update new password
        $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
        $sql = "UPDATE phphr_users SET password_hash = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $new_hash, $user_id);
        $stmt->execute();
        $stmt->close();

        $_SESSION['success'] = "Password changed successfully.";
        header("Location: change_password.php");
        exit;
    }
}
