<?php

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Authz.php';
require_once __DIR__ . '/../models/Attendance.php';
require_once __DIR__ . '/../models/Employee.php';

class AttendanceController extends Controller
{
    protected Attendance $attendance;
    protected Employee $employee;

    public function __construct(mysqli $conn)
    {
        parent::__construct($conn);
        require_roles($conn, ['admin', 'hr', 'employee']);

        $this->attendance = new Attendance($conn);
        $this->employee = new Employee($conn);
    }

    public function index(): void
    {
        $role = current_user_role($this->conn);
        $employeeId = $role === 'employee' ? (int)(current_employee_id($this->conn) ?? 0) : null;

        $records = $employeeId ? $this->attendance->allByEmployee($employeeId) : $this->attendance->all();
        $today = $employeeId ? $this->attendance->todayByEmployee($employeeId) : null;

        $successMsg = $_SESSION['attendance_success'] ?? '';
        $errorMsg = $_SESSION['attendance_error'] ?? '';
        unset($_SESSION['attendance_success'], $_SESSION['attendance_error']);

        $this->view('attendance/index', compact('records', 'successMsg', 'errorMsg', 'role', 'today'));
    }

    public function create(): void
    {
        require_roles($this->conn, ['admin', 'hr']);

        $errorMsg = $_SESSION['add_attendance_error'] ?? '';
        unset($_SESSION['add_attendance_error']);

        $employees = $this->employee->allActive();

        $this->view('attendance/create', compact('errorMsg', 'employees'));
    }

    public function store(): void
    {
        require_roles($this->conn, ['admin', 'hr']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('attendance.php?a=create');
        }

        if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
            $_SESSION['add_attendance_error'] = 'Security token expired. Please try again.';
            $this->redirect('attendance.php?a=create');
        }

        $data = [
            'employee_id' => (int)($_POST['employee_id'] ?? 0),
            'attendance_date' => $_POST['attendance_date'] ?? '',
            'check_in' => $_POST['check_in'] ?: null,
            'check_out' => $_POST['check_out'] ?: null,
            'status' => $_POST['status'] ?? 'present'
        ];

        if ($data['employee_id'] <= 0 || $data['attendance_date'] === '') {
            $_SESSION['add_attendance_error'] = 'Employee and date are required.';
            $this->redirect('attendance.php?a=create');
        }

        if ($this->attendance->create($data)) {
            $_SESSION['attendance_success'] = 'Attendance added successfully.';
        } else {
            $_SESSION['attendance_error'] = 'Error adding attendance.';
        }

        $this->redirect('attendance.php?a=index');
    }

    public function punchIn(): void
    {
        require_roles($this->conn, ['employee']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_is_valid($_POST['csrf_token'] ?? null)) {
            $_SESSION['attendance_error'] = 'Security token expired. Please try again.';
            $this->redirect('attendance.php?a=index');
        }

        $employeeId = (int)(current_employee_id($this->conn) ?? 0);
        if ($employeeId <= 0) {
            $_SESSION['attendance_error'] = 'Employee profile is not mapped to this login.';
            $this->redirect('attendance.php?a=index');
        }

        if ($this->attendance->punchIn($employeeId)) {
            $_SESSION['attendance_success'] = 'Punch-in marked successfully.';
        } else {
            $_SESSION['attendance_error'] = 'Punch-in failed or already marked for today.';
        }

        $this->redirect('attendance.php?a=index');
    }

    public function punchOut(): void
    {
        require_roles($this->conn, ['employee']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_is_valid($_POST['csrf_token'] ?? null)) {
            $_SESSION['attendance_error'] = 'Security token expired. Please try again.';
            $this->redirect('attendance.php?a=index');
        }

        $employeeId = (int)(current_employee_id($this->conn) ?? 0);
        if ($employeeId <= 0) {
            $_SESSION['attendance_error'] = 'Employee profile is not mapped to this login.';
            $this->redirect('attendance.php?a=index');
        }

        if ($this->attendance->punchOut($employeeId)) {
            $_SESSION['attendance_success'] = 'Punch-out marked successfully.';
        } else {
            $_SESSION['attendance_error'] = 'Punch-out failed. Ensure punch-in is done first and not already punched out.';
        }

        $this->redirect('attendance.php?a=index');
    }

    public function edit(): void
    {
        require_roles($this->conn, ['admin', 'hr']);

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->redirect('attendance.php?a=index');
        }

        $attendance = $this->attendance->find($id);
        if (!$attendance) {
            $this->redirect('attendance.php?a=index');
        }

        $employees = $this->employee->allActive();

        $errorMsg = $_SESSION['edit_attendance_error'] ?? '';
        $successMsg = $_SESSION['edit_attendance_success'] ?? '';
        unset($_SESSION['edit_attendance_error'], $_SESSION['edit_attendance_success']);

        $this->view('attendance/edit', compact('attendance', 'employees', 'errorMsg', 'successMsg'));
    }

    public function update(): void
    {
        require_roles($this->conn, ['admin', 'hr']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('attendance.php?a=index');
        }

        if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
            $_SESSION['attendance_error'] = 'Security token expired. Please try again.';
            $this->redirect('attendance.php?a=index');
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $this->redirect('attendance.php?a=index');
        }

        $data = [
            'employee_id' => (int)$_POST['employee_id'],
            'attendance_date' => $_POST['attendance_date'],
            'check_in' => $_POST['check_in'] ?: null,
            'check_out' => $_POST['check_out'] ?: null,
            'status' => $_POST['status']
        ];

        if ($this->attendance->update($id, $data)) {
            $_SESSION['edit_attendance_success'] = 'Attendance updated successfully.';
        } else {
            $_SESSION['edit_attendance_error'] = 'Error updating attendance.';
        }

        $this->redirect('attendance.php?a=edit&id=' . $id);
    }

    public function delete(): void
    {
        require_roles($this->conn, ['admin', 'hr']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_is_valid($_POST['csrf_token'] ?? null)) {
            $_SESSION['attendance_error'] = 'Security token expired. Please try again.';
            $this->redirect('attendance.php?a=index');
        }

        $id = (int)($_POST['id'] ?? 0);

        if ($id > 0 && $this->attendance->delete($id)) {
            $_SESSION['attendance_success'] = 'Attendance deleted successfully.';
        } else {
            $_SESSION['attendance_error'] = 'Error deleting attendance.';
        }

        $this->redirect('attendance.php?a=index');
    }
}
