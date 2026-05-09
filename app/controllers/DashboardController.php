<?php

require_once __DIR__ . '/../core/Authz.php';
require_once __DIR__ . '/../core/Security.php';

class DashboardController
{
    protected mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;

        if (empty($_SESSION['user_id'])) {
            header('Location: login.php');
            exit;
        }
    }

    protected function getSingleInt(string $sql): int
    {
        $result = $this->conn->query($sql);
        if (!$result) {
            return 0;
        }

        $row = $result->fetch_row();
        return isset($row[0]) ? (int)$row[0] : 0;
    }

    protected function getSingleFloat(string $sql): float
    {
        $result = $this->conn->query($sql);
        if (!$result) {
            return 0.0;
        }

        $row = $result->fetch_row();
        return isset($row[0]) ? (float)$row[0] : 0.0;
    }

    public function index(): void
    {
        $role = current_user_role($this->conn);

        if (in_array($role, ['admin', 'hr'], true)) {
            $data = [
                'totalEmployees' => $this->getSingleInt("SELECT COUNT(*) FROM phphr_employees"),
                'activeEmployees' => $this->getSingleInt("SELECT COUNT(*) FROM phphr_employees WHERE status = 1"),
                'todayAttendance' => $this->getSingleInt("SELECT COUNT(*) FROM phphr_attendance WHERE attendance_date = CURDATE()"),
                'pendingLeaves' => $this->getSingleInt("SELECT COUNT(*) FROM phphr_leaves WHERE status = 'pending'"),
                'pendingLoans' => $this->getSingleInt("SELECT COUNT(*) FROM phphr_loans WHERE status = 'pending'"),
                'overdueRepayments' => $this->getSingleInt("SELECT COUNT(*) FROM phphr_loan_repayments WHERE payment_status = 'overdue'")
            ];
            extract($data);
            include __DIR__ . '/../views/dashboard/admin.php';
            return;
        }

        if ($role === 'finance') {
            $monthlyPayroll = $this->getSingleFloat("SELECT COALESCE(SUM(net_salary),0) FROM phphr_payroll WHERE DATE_FORMAT(generated_at, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')");
            $monthlyLoanRecovery = $this->getSingleFloat("SELECT COALESCE(SUM(amount_paid),0) FROM phphr_loan_repayments WHERE DATE_FORMAT(COALESCE(paid_date, due_date), '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')");

            $pendingLoans = $this->getSingleInt("SELECT COUNT(*) FROM phphr_loans WHERE status = 'pending'");
            $overdueRepayments = $this->getSingleInt("SELECT COUNT(*) FROM phphr_loan_repayments WHERE payment_status = 'overdue'");
            include __DIR__ . '/../views/dashboard/finance.php';
            return;
        }

        $employeeId = (int)(current_employee_id($this->conn) ?? 0);
        $myLoans = 0;
        $myPendingLoans = 0;
        $myOverdue = 0;
        $myAttendanceThisMonth = 0;

        if ($employeeId > 0) {
            $myLoans = $this->getSingleInt("SELECT COUNT(*) FROM phphr_loans WHERE employee_id = {$employeeId}");
            $myPendingLoans = $this->getSingleInt("SELECT COUNT(*) FROM phphr_loans WHERE employee_id = {$employeeId} AND status = 'pending'");
            $myOverdue = $this->getSingleInt("SELECT COUNT(*) FROM phphr_loan_repayments r INNER JOIN phphr_loans l ON l.id = r.loan_id WHERE l.employee_id = {$employeeId} AND r.payment_status = 'overdue'");
            $myAttendanceThisMonth = $this->getSingleInt("SELECT COUNT(*) FROM phphr_attendance WHERE employee_id = {$employeeId} AND DATE_FORMAT(attendance_date, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')");
        }

        include __DIR__ . '/../views/dashboard/employee.php';
    }
}
