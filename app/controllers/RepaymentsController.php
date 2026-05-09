<?php

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Authz.php';
require_once __DIR__ . '/../models/LoanRepayment.php';
require_once __DIR__ . '/../models/Notification.php';

class RepaymentsController extends Controller
{
    protected LoanRepayment $repayment;
    protected Notification $notification;

    public function __construct(mysqli $conn)
    {
        parent::__construct($conn);
        require_roles($conn, ['admin', 'hr', 'finance', 'employee']);
        $this->repayment = new LoanRepayment($conn);
        $this->notification = new Notification($conn);
    }

    public function index(): void
    {
        $role = current_user_role($this->conn);
        $employeeId = $role === 'employee' ? current_employee_id($this->conn) : null;

        $records = $this->repayment->listAll($employeeId);
        $successMsg = $_SESSION['repay_success'] ?? '';
        $errorMsg = $_SESSION['repay_error'] ?? '';
        unset($_SESSION['repay_success'], $_SESSION['repay_error']);

        $this->view('repayments/index', compact('records', 'successMsg', 'errorMsg', 'role'));
    }

    public function create(): void
    {
        require_roles($this->conn, ['admin', 'hr', 'finance']);

        $loans = $this->repayment->loans();
        $role = current_user_role($this->conn);

        $this->view('repayments/create', compact('loans', 'role'));
    }

    public function store(): void
    {
        require_roles($this->conn, ['admin', 'hr', 'finance']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('repayments.php?a=create');
        }

        if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
            $_SESSION['repay_error'] = 'Security token expired. Please try again.';
            $this->redirect('repayments.php?a=create');
        }

        $data = [
            'loan_id' => (int)($_POST['loan_id'] ?? 0),
            'due_date' => $_POST['due_date'] ?? '',
            'paid_date' => ($_POST['paid_date'] ?? '') ?: null,
            'amount_due' => (float)($_POST['amount_due'] ?? 0),
            'amount_paid' => (float)($_POST['amount_paid'] ?? 0),
            'payment_mode' => ($_POST['payment_mode'] ?? '') ?: null,
            'reference_no' => trim($_POST['reference_no'] ?? ''),
            'remarks' => trim($_POST['remarks'] ?? '')
        ];

        $error = $this->validateRepaymentPayload($data);
        if ($error !== null) {
            $_SESSION['repay_error'] = $error;
            $this->redirect('repayments.php?a=create');
        }

        if ($this->repayment->create($data)) {
            $_SESSION['repay_success'] = 'Repayment entry saved.';
            $this->notifyRoles(
                ['admin', 'hr', 'finance'],
                'New Repayment Entry',
                'A loan repayment entry was added.',
                'repayments.php'
            );
            $this->notifyLoanEmployee(
                (int)$data['loan_id'],
                'Repayment Schedule Added',
                'A repayment entry has been added for your loan.',
                'repayments.php'
            );
        } else {
            $_SESSION['repay_error'] = $this->repayment->getLastError() ?: 'Error while saving repayment.';
        }

        $this->redirect('repayments.php?a=index');
    }

    public function edit(): void
    {
        require_roles($this->conn, ['admin', 'hr', 'finance']);

        $this->repayment->refreshStatuses();
        $id = (int)($_GET['id'] ?? 0);
        $record = $this->repayment->find($id);
        if (!$record) {
            $_SESSION['repay_error'] = 'Repayment record not found.';
            $this->redirect('repayments.php?a=index');
        }

        $loans = $this->repayment->loans();
        $role = current_user_role($this->conn);
        $this->view('repayments/edit', compact('record', 'loans', 'role'));
    }

    public function update(): void
    {
        require_roles($this->conn, ['admin', 'hr', 'finance']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('repayments.php?a=index');
        }

        if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
            $_SESSION['repay_error'] = 'Security token expired. Please try again.';
            $this->redirect('repayments.php?a=index');
        }

        $this->repayment->refreshStatuses();
        $id = (int)($_POST['id'] ?? 0);
        $record = $this->repayment->find($id);
        if (!$record) {
            $_SESSION['repay_error'] = 'Repayment record not found.';
            $this->redirect('repayments.php?a=index');
        }

        $data = [
            'loan_id' => (int)($_POST['loan_id'] ?? 0),
            'due_date' => $_POST['due_date'] ?? '',
            'paid_date' => ($_POST['paid_date'] ?? '') ?: null,
            'amount_due' => (float)($_POST['amount_due'] ?? 0),
            'amount_paid' => (float)($_POST['amount_paid'] ?? 0),
            'payment_mode' => ($_POST['payment_mode'] ?? '') ?: null,
            'reference_no' => trim($_POST['reference_no'] ?? ''),
            'remarks' => trim($_POST['remarks'] ?? '')
        ];

        $error = $this->validateRepaymentPayload($data);
        if ($error !== null) {
            $_SESSION['repay_error'] = $error;
            $this->redirect('repayments.php?a=edit&id=' . $id);
        }

        if ($this->repayment->update($id, $data)) {
            $_SESSION['repay_success'] = 'Repayment entry updated.';
            $updated = $this->repayment->find($id);
            $oldStatus = (string)$record['payment_status'];
            $newStatus = (string)($updated['payment_status'] ?? $oldStatus);

            $this->notifyRoles(
                ['admin', 'hr', 'finance'],
                'Repayment Updated',
                'Repayment #' . $id . ' was updated.',
                'repayments.php?a=edit&id=' . $id
            );

            if ($oldStatus !== $newStatus) {
                $this->notifyRoles(
                    ['admin', 'hr', 'finance'],
                    'Repayment Status Transition',
                    'Repayment #' . $id . ' moved from ' . $oldStatus . ' to ' . $newStatus . '.',
                    'repayments.php?a=edit&id=' . $id
                );
                $this->notifyLoanEmployee(
                    (int)$data['loan_id'],
                    'Repayment Status Updated',
                    'Your repayment #' . $id . ' status changed from ' . $oldStatus . ' to ' . $newStatus . '.',
                    'repayments.php?a=edit&id=' . $id
                );
            }
        } else {
            $_SESSION['repay_error'] = $this->repayment->getLastError() ?: 'Error while updating repayment.';
        }

        $this->redirect('repayments.php?a=index');
    }

    public function delete(): void
    {
        require_roles($this->conn, ['admin', 'hr', 'finance']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_is_valid($_POST['csrf_token'] ?? null)) {
            $_SESSION['repay_error'] = 'Security token expired. Please try again.';
            $this->redirect('repayments.php?a=index');
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0 && $this->repayment->delete($id)) {
            $_SESSION['repay_success'] = 'Repayment entry deleted.';
        } else {
            $_SESSION['repay_error'] = $this->repayment->getLastError() ?: 'Unable to delete repayment entry.';
        }
        $this->redirect('repayments.php?a=index');
    }

    protected function validateRepaymentPayload(array $data): ?string
    {
        if ($data['loan_id'] <= 0) {
            return 'Loan is required.';
        }

        if ($data['due_date'] === '') {
            return 'Due date is required.';
        }

        if ($data['amount_due'] <= 0) {
            return 'Amount due must be greater than zero.';
        }

        if ($data['amount_paid'] < 0) {
            return 'Amount paid cannot be negative.';
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
            $uid = (int)$row['user_id'];
            $this->notification->create($uid, $title, $body, $link);
        }
    }

    protected function notifyLoanEmployee(int $loanId, string $title, string $body, string $link = ''): void
    {
        $userId = $this->repayment->employeeUserIdByLoan($loanId);
        if ($userId && $userId > 0) {
            $this->notification->create($userId, $title, $body, $link);
        }
    }
}
