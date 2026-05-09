<?php

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Authz.php';
require_once __DIR__ . '/../models/Employee.php';

class EmployeesController extends Controller
{
    protected Employee $employee;

    public function __construct($conn)
    {
        parent::__construct($conn);
        $this->employee = new Employee($conn);
        require_roles($conn, ['admin', 'hr']);
    }

    public function index()
    {
        $employees = $this->employee->all();

        $successMsg = $_SESSION['employee_success'] ?? '';
        $errorMsg = $_SESSION['employee_error'] ?? '';
        unset($_SESSION['employee_success'], $_SESSION['employee_error']);

        $this->view('employees/index', [
            'employees' => $employees,
            'successMsg' => $successMsg,
            'errorMsg' => $errorMsg
        ]);
    }

    public function create()
    {
        $errorMsg = $_SESSION['add_employee_error'] ?? '';
        $successMsg = $_SESSION['add_employee_success'] ?? '';
        $old = $_SESSION['add_employee_old'] ?? [];

        unset(
            $_SESSION['add_employee_error'],
            $_SESSION['add_employee_success'],
            $_SESSION['add_employee_old']
        );

        $this->view('employees/create', [
            'errorMsg' => $errorMsg,
            'successMsg' => $successMsg,
            'old' => $old
        ]);
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->redirect('employees.php?a=create');
        }

        if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
            $_SESSION['add_employee_error'] = 'Security token expired. Please try again.';
            return $this->redirect('employees.php?a=create');
        }

        $data = $this->employeeDataFromRequest();
        $account = $this->accountDataFromRequest();

        $_SESSION['add_employee_old'] = array_merge($data, [
            'username' => $account['username'],
            'email' => $account['email']
        ]);

        $error = $this->validateEmployeePayload($data, $account, 0, true);
        if ($error !== null) {
            $_SESSION['add_employee_error'] = $error;
            return $this->redirect('employees.php?a=create');
        }

        $account['password_hash'] = password_hash($account['password'], PASSWORD_DEFAULT);

        if ($this->employee->createWithUser($data, $account)) {
            $_SESSION['add_employee_success'] = 'Employee and login account added successfully.';
            $_SESSION['add_employee_old'] = [];
            return $this->redirect('employees.php?a=create');
        }

        $_SESSION['add_employee_error'] = $this->employee->getLastError() ?: 'Error adding employee.';
        return $this->redirect('employees.php?a=create');
    }

    public function edit()
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            return $this->redirect('employees.php?a=index');
        }

        $employee = $this->employee->find($id);
        if (!$employee) {
            return $this->redirect('employees.php?a=index');
        }

        $errorMsg = $_SESSION['edit_employee_error'] ?? '';
        $successMsg = $_SESSION['edit_employee_success'] ?? '';
        unset($_SESSION['edit_employee_error'], $_SESSION['edit_employee_success']);

        $this->view('employees/edit', [
            'employee' => $employee,
            'errorMsg' => $errorMsg,
            'successMsg' => $successMsg
        ]);
    }

    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->redirect('employees.php?a=index');
        }

        if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
            $_SESSION['employee_error'] = 'Security token expired. Please try again.';
            return $this->redirect('employees.php?a=index');
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            return $this->redirect('employees.php?a=index');
        }

        $existing = $this->employee->find($id);
        if (!$existing) {
            $_SESSION['employee_error'] = 'Employee not found.';
            return $this->redirect('employees.php?a=index');
        }

        $data = $this->employeeDataFromRequest();
        $account = $this->accountDataFromRequest();

        $error = $this->validateEmployeePayload(
            $data,
            $account,
            (int)($existing['user_id'] ?? 0),
            (int)($existing['user_id'] ?? 0) <= 0
        );
        if ($error !== null) {
            $_SESSION['edit_employee_error'] = $error;
            return $this->redirect('employees.php?a=edit&id=' . $id);
        }

        $account['password_hash'] = $account['password'] !== ''
            ? password_hash($account['password'], PASSWORD_DEFAULT)
            : '';

        if ($this->employee->updateWithUser($id, $data, $account)) {
            $_SESSION['edit_employee_success'] = 'Employee and login account updated successfully.';
        } else {
            $_SESSION['edit_employee_error'] = $this->employee->getLastError() ?: 'Error updating employee.';
        }

        return $this->redirect('employees.php?a=edit&id=' . $id);
    }

    public function delete()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_is_valid($_POST['csrf_token'] ?? null)) {
            $_SESSION['employee_error'] = 'Security token expired. Please try again.';
            return $this->redirect('employees.php?a=index');
        }

        $id = (int)($_POST['id'] ?? 0);

        if ($id > 0 && $this->employee->delete($id)) {
            $_SESSION['employee_success'] = 'Employee deleted successfully.';
        } else {
            $_SESSION['employee_error'] = 'Error deleting employee.';
        }

        return $this->redirect('employees.php?a=index');
    }

    protected function employeeDataFromRequest(): array
    {
        return [
            'user_id' => 0,
            'employee_code' => '',
            'first_name' => trim($_POST['first_name'] ?? ''),
            'last_name' => trim($_POST['last_name'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'department' => trim($_POST['department'] ?? ''),
            'designation' => trim($_POST['designation'] ?? ''),
            'date_of_joining' => $_POST['date_of_joining'] ?? '',
            'salary' => (float)($_POST['salary'] ?? 0),
            'status' => (int)($_POST['status'] ?? 1),
        ];
    }

    protected function accountDataFromRequest(): array
    {
        return [
            'username' => trim($_POST['username'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'password' => trim($_POST['password'] ?? ''),
            'confirm_password' => trim($_POST['confirm_password'] ?? ''),
        ];
    }

    protected function validateEmployeePayload(array $data, array $account, int $existingUserId = 0, bool $passwordRequired = false): ?string
    {
        if ($data['first_name'] === '' || $data['last_name'] === '') {
            return 'First name and last name are required.';
        }

        if ($data['department'] === '' || $data['designation'] === '') {
            return 'Department and designation are required.';
        }

        if ($data['date_of_joining'] === '') {
            return 'Date of joining is required.';
        }

        if ($data['salary'] < 0) {
            return 'Salary must be zero or greater.';
        }

        if ($account['username'] === '' || $account['email'] === '') {
            return 'Login username and email are required.';
        }

        if (!filter_var($account['email'], FILTER_VALIDATE_EMAIL)) {
            return 'Please provide a valid login email address.';
        }

        if ($this->employee->usernameExists($account['username'], $existingUserId)) {
            return 'This username is already in use.';
        }

        if ($this->employee->emailExists($account['email'], $existingUserId)) {
            return 'This email is already in use.';
        }

        if ($passwordRequired && $account['password'] === '') {
            return 'Password is required for the employee login.';
        }

        if ($account['password'] !== '' || $account['confirm_password'] !== '') {
            if (strlen($account['password']) < 6) {
                return 'Password must be at least 6 characters long.';
            }
            if ($account['password'] !== $account['confirm_password']) {
                return 'Password and confirm password do not match.';
            }
        }

        return null;
    }
}
