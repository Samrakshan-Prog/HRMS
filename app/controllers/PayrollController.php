<?php

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Authz.php';
require_once __DIR__ . '/../models/Payroll.php';
require_once __DIR__ . '/../models/Employee.php';
require_once __DIR__ . '/../models/Notification.php';

class PayrollController extends Controller
{
    protected Payroll $payroll;
    protected Employee $employee;
    protected Notification $notification;

    public function __construct(mysqli $conn)
    {
        parent::__construct($conn);
        require_roles($conn, ['admin', 'hr', 'finance', 'employee']);

        $this->payroll = new Payroll($conn);
        $this->employee = new Employee($conn);
        $this->notification = new Notification($conn);
    }

    public function index(): void
    {
        $records = $this->payroll->all();
        $role = current_user_role($this->conn);
        if ($role === 'employee') {
            $employeeId = (int)(current_employee_id($this->conn) ?? 0);
            $records = array_values(array_filter($records, static function ($row) use ($employeeId) {
                return (int)$row['employee_id'] === $employeeId;
            }));
        }

        $successMsg = $_SESSION['payroll_success'] ?? '';
        $errorMsg = $_SESSION['payroll_error'] ?? '';
        unset($_SESSION['payroll_success'], $_SESSION['payroll_error']);

        $this->view('payroll/index', compact('records', 'successMsg', 'errorMsg', 'role'));
    }

    public function create(): void
    {
        require_roles($this->conn, ['admin', 'hr', 'finance']);
        $errorMsg = $_SESSION['add_payroll_error'] ?? '';
        unset($_SESSION['add_payroll_error']);

        $employees = $this->employee->allActive();
        $this->view('payroll/create', compact('errorMsg', 'employees'));
    }

    private function calculatePayrollData(array $input): array
    {
        $employeeId = (int)($input['employee_id'] ?? 0);
        $salaryMonth = (string)($input['salary_month'] ?? date('Y-m'));

        $basic = (float)($input['basic_salary'] ?? 0);
        $allowances = (float)($input['allowances'] ?? 0);
        $bonus = (float)($input['bonus'] ?? 0);
        $otherDeductions = (float)($input['deductions'] ?? 0);

        if ($basic <= 0 && $employeeId > 0) {
            $emp = $this->payroll->employeeById($employeeId);
            if ($emp) {
                $basic = (float)$emp['salary'];
            }
        }

        $monthStats = $this->payroll->monthStatsForPayroll($employeeId, $salaryMonth);
        $daysInMonth = max((int)$monthStats['days_in_month'], 1);
        $leaveDays = round((float)$monthStats['leave_days'], 2);
        $loanEmi = (float)$monthStats['loan_emi_due'];

        $dailyRate = $basic / $daysInMonth;
        $leaveDeduction = round($dailyRate * $leaveDays, 2);

        $gross = $basic + $allowances + $bonus;
        $totalDeductions = $leaveDeduction + $loanEmi + $otherDeductions;
        $net = round($gross - $totalDeductions, 2);
        if ($net < 0) {
            $net = 0;
        }

        return [
            'employee_id' => $employeeId,
            'salary_month' => $salaryMonth,
            'basic_salary' => $basic,
            'allowances' => $allowances,
            'bonus' => $bonus,
            'leave_days' => $leaveDays,
            'leave_deduction' => $leaveDeduction,
            'loan_emi_deduction' => $loanEmi,
            'deductions' => $otherDeductions,
            'total_deductions' => $totalDeductions,
            'net_salary' => $net,
            'gross_salary' => $gross
        ];
    }

    public function preview(): void
    {
        require_roles($this->conn, ['admin', 'hr', 'finance']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Method not allowed';
            return;
        }

        if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
            http_response_code(419);
            echo 'Invalid CSRF token';
            return;
        }

        $data = $this->calculatePayrollData($_POST);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public function store(): void
    {
        require_roles($this->conn, ['admin', 'hr', 'finance']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('payroll.php?a=create');
        }

        if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
            $_SESSION['payroll_error'] = 'Security token expired. Please try again.';
            $this->redirect('payroll.php?a=create');
        }

        $data = $this->calculatePayrollData($_POST);
        if ($data['employee_id'] <= 0 || $data['salary_month'] === '') {
            $_SESSION['add_payroll_error'] = 'Employee and salary month are required.';
            $this->redirect('payroll.php?a=create');
        }

        if ($this->payroll->create($data)) {
            $_SESSION['payroll_success'] = 'Payroll generated successfully with auto EMI and leave deductions.';
            $this->notifyEmployeePayroll((int)$data['employee_id'], 'Payroll Generated', 'A new payroll record is available for your review.');
            $this->redirect('payroll.php?a=index');
        }

        $_SESSION['payroll_error'] = 'Error generating payroll.';
        $this->redirect('payroll.php?a=index');
    }

    public function edit(): void
    {
        require_roles($this->conn, ['admin', 'hr', 'finance']);

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->redirect('payroll.php?a=index');
        }

        $payroll = $this->payroll->find($id);
        if (!$payroll) {
            $this->redirect('payroll.php?a=index');
        }

        $employees = $this->employee->allActive();
        $errorMsg = $_SESSION['edit_payroll_error'] ?? '';
        $successMsg = $_SESSION['edit_payroll_success'] ?? '';
        unset($_SESSION['edit_payroll_error'], $_SESSION['edit_payroll_success']);

        $this->view('payroll/edit', compact('payroll', 'employees', 'errorMsg', 'successMsg'));
    }

    public function update(): void
    {
        require_roles($this->conn, ['admin', 'hr', 'finance']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('payroll.php?a=index');
        }

        if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
            $_SESSION['payroll_error'] = 'Security token expired. Please try again.';
            $this->redirect('payroll.php?a=index');
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $this->redirect('payroll.php?a=index');
        }

        $data = $this->calculatePayrollData($_POST);

        if ($this->payroll->update($id, $data)) {
            $_SESSION['edit_payroll_success'] = 'Payroll updated successfully.';
            $this->notifyEmployeePayroll((int)$data['employee_id'], 'Payroll Updated', 'Your payroll record was updated.');
        } else {
            $_SESSION['edit_payroll_error'] = 'Error updating payroll.';
        }

        $this->redirect('payroll.php?a=edit&id=' . $id);
    }

    public function payslip(): void
    {
        require_roles($this->conn, ['admin', 'hr', 'finance', 'employee']);

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->redirect('payroll.php?a=index');
        }

        $payroll = $this->payroll->find($id);
        if (!$payroll) {
            $this->redirect('payroll.php?a=index');
        }

        $role = current_user_role($this->conn);
        if ($role === 'employee') {
            $employeeId = (int)(current_employee_id($this->conn) ?? 0);
            if ((int)$payroll['employee_id'] !== $employeeId) {
                http_response_code(403);
                echo 'Forbidden.';
                exit;
            }
        }

        $this->view('payroll/payslip', compact('payroll'));
    }

    public function delete(): void
    {
        require_roles($this->conn, ['admin', 'hr', 'finance']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_is_valid($_POST['csrf_token'] ?? null)) {
            $_SESSION['payroll_error'] = 'Security token expired. Please try again.';
            $this->redirect('payroll.php?a=index');
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0 && $this->payroll->delete($id)) {
            $_SESSION['payroll_success'] = 'Payroll deleted successfully.';
        } else {
            $_SESSION['payroll_error'] = 'Error deleting payroll.';
        }

        $this->redirect('payroll.php?a=index');
    }

    protected function notifyEmployeePayroll(int $employeeId, string $title, string $body): void
    {
        $employee = $this->employee->find($employeeId);
        $userId = (int)($employee['user_id'] ?? 0);
        if ($userId > 0) {
            $this->notification->create($userId, $title, $body, 'payroll.php');
        }
    }
}
