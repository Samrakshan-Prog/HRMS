<?php

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Authz.php';
require_once __DIR__ . '/../models/Leave.php';
require_once __DIR__ . '/../models/Notification.php';

class LeaveController extends Controller
{
    protected LeaveModel $leave;
    protected Notification $notification;

    public function __construct($conn)
    {
        parent::__construct($conn);
        $this->leave = new LeaveModel($conn);
        $this->notification = new Notification($conn);
        require_roles($conn, ['admin', 'hr', 'employee']);
    }

    public function index()
    {
        $role = current_user_role($this->conn);
        $employeeId = $role === 'employee' ? current_employee_id($this->conn) : null;
        $records = $this->leave->all($employeeId);

        $successMsg = $_SESSION['leave_success'] ?? '';
        $errorMsg = $_SESSION['leave_error'] ?? '';
        unset($_SESSION['leave_success'], $_SESSION['leave_error']);

        $this->view('leaves/index', [
            'records' => $records,
            'successMsg' => $successMsg,
            'errorMsg' => $errorMsg,
            'role' => $role
        ]);
    }

    public function create()
    {
        $role = current_user_role($this->conn);
        $employeeId = $role === 'employee' ? (int)(current_employee_id($this->conn) ?? 0) : 0;

        if ($role === 'employee' && $employeeId <= 0) {
            $_SESSION['leave_error'] = 'Your login is not linked to an employee profile.';
            return $this->redirect('leaves.php?a=index');
        }

        $errorMsg = $_SESSION['add_leave_error'] ?? '';
        unset($_SESSION['add_leave_error']);

        $employees = $role === 'employee' ? [] : $this->leave->getActiveEmployees();

        $this->view('leaves/create', [
            'errorMsg' => $errorMsg,
            'employees' => $employees,
            'role' => $role,
            'employeeId' => $employeeId
        ]);
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->redirect('leaves.php?a=create');
        }

        if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
            $_SESSION['add_leave_error'] = 'Security token expired. Please try again.';
            return $this->redirect('leaves.php?a=create');
        }

        $role = current_user_role($this->conn);
        $employeeId = $role === 'employee'
            ? (int)(current_employee_id($this->conn) ?? 0)
            : (int)($_POST['employee_id'] ?? 0);

        $data = [
            'employee_id' => $employeeId,
            'leave_type' => $_POST['leave_type'] ?? '',
            'start_date' => $_POST['start_date'] ?? '',
            'end_date' => $_POST['end_date'] ?? '',
            'reason' => trim($_POST['reason'] ?? ''),
            'status' => $role === 'employee' ? 'pending' : ($_POST['status'] ?? 'pending')
        ];

        $error = $this->validateLeavePayload($data);
        if ($error !== null) {
            $_SESSION['add_leave_error'] = $error;
            return $this->redirect('leaves.php?a=create');
        }

        if ($this->leave->overlapExists($data['employee_id'], $data['start_date'], $data['end_date'])) {
            $_SESSION['add_leave_error'] = 'An overlapping leave request already exists for this employee.';
            return $this->redirect('leaves.php?a=create');
        }

        if ($this->leave->create($data)) {
            $_SESSION['leave_success'] = $role === 'employee'
                ? 'Leave request submitted for approval.'
                : 'Leave request saved successfully.';

            if ($role === 'employee') {
                $this->notifyRoles(
                    ['admin', 'hr'],
                    'New Leave Request',
                    'A new leave request has been submitted and needs review.',
                    'leaves.php'
                );
            } elseif (in_array($data['status'], ['approved', 'rejected'], true)) {
                $this->notifyEmployee(
                    $data['employee_id'],
                    'Leave Request ' . ucfirst($data['status']),
                    'Your leave request has been marked as ' . $data['status'] . '.',
                    'leaves.php'
                );
            }

            return $this->redirect('leaves.php?a=index');
        }

        $_SESSION['leave_error'] = 'Error applying leave.';
        return $this->redirect('leaves.php?a=index');
    }

    public function edit()
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            return $this->redirect('leaves.php?a=index');
        }

        $leave = $this->leave->find($id);
        if (!$leave) {
            return $this->redirect('leaves.php?a=index');
        }

        $role = current_user_role($this->conn);
        $employeeId = $role === 'employee' ? (int)(current_employee_id($this->conn) ?? 0) : 0;

        if ($role === 'employee') {
            if ($employeeId <= 0 || (int)$leave['employee_id'] !== $employeeId) {
                http_response_code(403);
                echo 'Forbidden.';
                exit;
            }

            if ($leave['status'] !== 'pending') {
                $_SESSION['leave_error'] = 'Only pending leave requests can be edited by employees.';
                return $this->redirect('leaves.php?a=index');
            }
        }

        $employees = $role === 'employee' ? [] : $this->leave->getActiveEmployees();

        $errorMsg = $_SESSION['edit_leave_error'] ?? '';
        $successMsg = $_SESSION['edit_leave_success'] ?? '';
        unset($_SESSION['edit_leave_error'], $_SESSION['edit_leave_success']);

        $this->view('leaves/edit', [
            'leave' => $leave,
            'employees' => $employees,
            'errorMsg' => $errorMsg,
            'successMsg' => $successMsg,
            'role' => $role,
            'employeeId' => $employeeId
        ]);
    }

    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->redirect('leaves.php?a=index');
        }

        if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
            $_SESSION['leave_error'] = 'Security token expired. Please try again.';
            return $this->redirect('leaves.php?a=index');
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            return $this->redirect('leaves.php?a=index');
        }

        $existing = $this->leave->find($id);
        if (!$existing) {
            $_SESSION['leave_error'] = 'Leave request not found.';
            return $this->redirect('leaves.php?a=index');
        }

        $role = current_user_role($this->conn);
        $employeeId = $role === 'employee'
            ? (int)(current_employee_id($this->conn) ?? 0)
            : (int)($_POST['employee_id'] ?? 0);

        if ($role === 'employee') {
            if ($employeeId <= 0 || (int)$existing['employee_id'] !== $employeeId) {
                http_response_code(403);
                echo 'Forbidden.';
                exit;
            }

            if ($existing['status'] !== 'pending') {
                $_SESSION['edit_leave_error'] = 'Only pending leave requests can be edited.';
                return $this->redirect('leaves.php?a=edit&id=' . $id);
            }
        }

        $data = [
            'employee_id' => $employeeId,
            'leave_type' => $_POST['leave_type'] ?? '',
            'start_date' => $_POST['start_date'] ?? '',
            'end_date' => $_POST['end_date'] ?? '',
            'reason' => trim($_POST['reason'] ?? ''),
            'status' => $role === 'employee' ? 'pending' : ($_POST['status'] ?? 'pending')
        ];

        $error = $this->validateLeavePayload($data);
        if ($error !== null) {
            $_SESSION['edit_leave_error'] = $error;
            return $this->redirect('leaves.php?a=edit&id=' . $id);
        }

        if ($this->leave->overlapExists($data['employee_id'], $data['start_date'], $data['end_date'], $id)) {
            $_SESSION['edit_leave_error'] = 'An overlapping leave request already exists for this employee.';
            return $this->redirect('leaves.php?a=edit&id=' . $id);
        }

        if ($this->leave->update($id, $data)) {
            $_SESSION['edit_leave_success'] = 'Leave updated successfully.';

            $oldStatus = (string)$existing['status'];
            $newStatus = (string)$data['status'];
            if ($role !== 'employee' && $oldStatus !== $newStatus) {
                $this->notifyEmployee(
                    $data['employee_id'],
                    'Leave Request ' . ucfirst($newStatus),
                    'Your leave request status changed from ' . $oldStatus . ' to ' . $newStatus . '.',
                    'leaves.php'
                );
            }
        } else {
            $_SESSION['edit_leave_error'] = 'Error updating leave.';
        }

        return $this->redirect('leaves.php?a=edit&id=' . $id);
    }

    public function delete()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_is_valid($_POST['csrf_token'] ?? null)) {
            $_SESSION['leave_error'] = 'Security token expired. Please try again.';
            return $this->redirect('leaves.php?a=index');
        }

        $id = (int)($_POST['id'] ?? 0);
        $record = $this->leave->find($id);
        if (!$record) {
            $_SESSION['leave_error'] = 'Leave request not found.';
            return $this->redirect('leaves.php?a=index');
        }

        $role = current_user_role($this->conn);
        if ($role === 'employee') {
            $employeeId = (int)(current_employee_id($this->conn) ?? 0);
            if ($employeeId <= 0 || (int)$record['employee_id'] !== $employeeId || $record['status'] !== 'pending') {
                http_response_code(403);
                echo 'Forbidden.';
                exit;
            }
        }

        if ($id > 0 && $this->leave->delete($id)) {
            $_SESSION['leave_success'] = 'Leave deleted successfully.';
        } else {
            $_SESSION['leave_error'] = 'Error deleting leave.';
        }

        return $this->redirect('leaves.php?a=index');
    }

    protected function validateLeavePayload(array $data): ?string
    {
        if ($data['employee_id'] <= 0) {
            return 'Employee is required.';
        }

        if ($data['start_date'] === '' || $data['end_date'] === '') {
            return 'Start date and end date are required.';
        }

        if ($data['start_date'] > $data['end_date']) {
            return 'Start date cannot be after end date.';
        }

        if (!in_array($data['leave_type'], ['casual', 'sick', 'paid', 'unpaid'], true)) {
            return 'Please select a valid leave type.';
        }

        return null;
    }

    protected function notifyRoles(array $roles, string $title, string $body, string $link = ''): void
    {
        $safeRoles = array_map([$this->conn, 'real_escape_string'], $roles);
        if (count($safeRoles) === 0) {
            return;
        }

        $in = "'" . implode("','", $safeRoles) . "'";
        $sql = "SELECT DISTINCT ur.user_id
                FROM phphr_user_roles ur
                INNER JOIN phphr_roles r ON r.id = ur.role_id
                WHERE r.role_key IN ({$in})";
        $result = $this->conn->query($sql);
        if (!$result) {
            return;
        }

        while ($row = $result->fetch_assoc()) {
            $this->notification->create((int)$row['user_id'], $title, $body, $link);
        }
    }

    protected function notifyEmployee(int $employeeId, string $title, string $body, string $link = ''): void
    {
        $userId = $this->leave->employeeUserId($employeeId);
        if ($userId) {
            $this->notification->create($userId, $title, $body, $link);
        }
    }
}
