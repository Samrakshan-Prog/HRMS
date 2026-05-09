<?php

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Authz.php';
require_once __DIR__ . '/../models/Membership.php';

class MembershipController extends Controller
{
    protected Membership $membership;

    public function __construct(mysqli $conn)
    {
        parent::__construct($conn);
        require_roles($conn, ['admin', 'hr', 'employee']);
        $this->membership = new Membership($conn);
    }

    public function index(): void
    {
        $role = current_user_role($this->conn);
        $employeeId = $role === 'employee' ? current_employee_id($this->conn) : null;
        $records = $this->membership->listAll($employeeId);

        $successMsg = $_SESSION['mem_success'] ?? '';
        $errorMsg = $_SESSION['mem_error'] ?? '';
        unset($_SESSION['mem_success'], $_SESSION['mem_error']);

        $this->view('membership/index', compact('records', 'successMsg', 'errorMsg', 'role'));
    }

    public function create(): void
    {
        require_roles($this->conn, ['admin', 'hr']);
        $employees = $this->membership->employees();
        $this->view('membership/create', compact('employees'));
    }

    public function store(): void
    {
        require_roles($this->conn, ['admin', 'hr']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('membership.php?a=create');
        }

        if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
            $_SESSION['mem_error'] = 'Security token expired. Please try again.';
            $this->redirect('membership.php?a=create');
        }

        $data = [
            'employee_id' => (int)($_POST['employee_id'] ?? 0),
            'membership_no' => trim($_POST['membership_no'] ?? ''),
            'membership_type' => trim($_POST['membership_type'] ?? ''),
            'start_date' => $_POST['start_date'] ?? date('Y-m-d'),
            'end_date' => ($_POST['end_date'] ?? '') ?: null,
            'status' => $_POST['status'] ?? 'active',
            'approved_by' => (int)$_SESSION['user_id'],
            'notes' => trim($_POST['notes'] ?? '')
        ];

        if ($data['employee_id'] <= 0 || $data['membership_no'] === '' || $data['membership_type'] === '') {
            $_SESSION['mem_error'] = 'Employee, membership number and type are required.';
            $this->redirect('membership.php?a=create');
        }

        if ($this->membership->create($data)) {
            $_SESSION['mem_success'] = 'Membership record saved.';
            $this->redirect('membership.php?a=index');
        }

        $_SESSION['mem_error'] = 'Error while saving membership record.';
        $this->redirect('membership.php?a=create');
    }

    public function delete(): void
    {
        require_roles($this->conn, ['admin', 'hr']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_is_valid($_POST['csrf_token'] ?? null)) {
            $_SESSION['mem_error'] = 'Security token expired. Please try again.';
            $this->redirect('membership.php?a=index');
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0 && $this->membership->delete($id)) {
            $_SESSION['mem_success'] = 'Membership record deleted.';
        } else {
            $_SESSION['mem_error'] = 'Unable to delete membership record.';
        }
        $this->redirect('membership.php?a=index');
    }
}
