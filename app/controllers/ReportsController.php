<?php

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Authz.php';

class ReportsController extends Controller
{
    public function __construct(mysqli $conn)
    {
        parent::__construct($conn);
        require_roles($conn, ['admin', 'hr', 'finance']);
    }

    public function index(): void
    {
        $attendanceCount = (int)($this->conn->query("SELECT COUNT(*) c FROM phphr_attendance")->fetch_assoc()['c'] ?? 0);
        $payrollCount = (int)($this->conn->query("SELECT COUNT(*) c FROM phphr_payroll")->fetch_assoc()['c'] ?? 0);
        $loanCount = (int)($this->conn->query("SELECT COUNT(*) c FROM phphr_loans")->fetch_assoc()['c'] ?? 0);
        $repaymentCount = (int)($this->conn->query("SELECT COUNT(*) c FROM phphr_loan_repayments")->fetch_assoc()['c'] ?? 0);

        $this->view('reports/index', compact('attendanceCount', 'payrollCount', 'loanCount', 'repaymentCount'));
    }

    public function export(): void
    {
        $type = $_GET['type'] ?? '';
        $map = [
            'attendance' => [
                'sql' => "SELECT a.id, e.employee_code, CONCAT(e.first_name,' ',e.last_name) AS employee_name, a.attendance_date, a.check_in, a.check_out, a.status
                          FROM phphr_attendance a
                          INNER JOIN phphr_employees e ON e.id = a.employee_id
                          ORDER BY a.attendance_date DESC",
                'file' => 'attendance_report.csv'
            ],
            'payroll' => [
                'sql' => "SELECT p.id, e.employee_code, CONCAT(e.first_name,' ',e.last_name) AS employee_name, p.salary_month, p.basic_salary, p.allowances, p.deductions, p.net_salary
                          FROM phphr_payroll p
                          INNER JOIN phphr_employees e ON e.id = p.employee_id
                          ORDER BY p.generated_at DESC",
                'file' => 'payroll_report.csv'
            ],
            'loans' => [
                'sql' => "SELECT l.id, e.employee_code, CONCAT(e.first_name,' ',e.last_name) AS employee_name, l.requested_amount, l.approved_amount, l.interest_rate, l.tenure_months, l.status, l.request_date
                          FROM phphr_loans l
                          INNER JOIN phphr_employees e ON e.id = l.employee_id
                          ORDER BY l.created_at DESC",
                'file' => 'loan_report.csv'
            ],
            'repayments' => [
                'sql' => "SELECT r.id, r.loan_id, e.employee_code, CONCAT(e.first_name,' ',e.last_name) AS employee_name, r.due_date, r.paid_date, r.amount_due, r.amount_paid, r.payment_status
                          FROM phphr_loan_repayments r
                          INNER JOIN phphr_loans l ON l.id = r.loan_id
                          INNER JOIN phphr_employees e ON e.id = l.employee_id
                          ORDER BY r.due_date DESC",
                'file' => 'repayment_report.csv'
            ]
        ];

        if (!isset($map[$type])) {
            http_response_code(400);
            echo 'Invalid report type.';
            return;
        }

        $rows = $this->conn->query($map[$type]['sql']);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $map[$type]['file'] . '"');

        $out = fopen('php://output', 'w');
        $first = true;

        if ($rows) {
            while ($row = $rows->fetch_assoc()) {
                if ($first) {
                    fputcsv($out, array_keys($row));
                    $first = false;
                }
                fputcsv($out, $row);
            }
        }

        fclose($out);
        exit;
    }
}
