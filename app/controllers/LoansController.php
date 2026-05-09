<?php

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Authz.php';
require_once __DIR__ . '/../models/Loan.php';
require_once __DIR__ . '/../models/LoanRepayment.php';
require_once __DIR__ . '/../models/Notification.php';

class LoansController extends Controller
{
    protected Loan $loan;
    protected LoanRepayment $repayment;
    protected Notification $notification;

    public function __construct(mysqli $conn)
    {
        parent::__construct($conn);
        require_roles($conn, ['admin', 'hr', 'finance', 'employee']);
        $this->loan = new Loan($conn);
        $this->repayment = new LoanRepayment($conn);
        $this->notification = new Notification($conn);
    }

    public function index(): void
    {
        $role = current_user_role($this->conn);
        $employeeId = $role === 'employee' ? current_employee_id($this->conn) : null;

        $records = $this->loan->listAll($employeeId);
        $stats = $this->loan->stats($employeeId);
        $successMsg = $_SESSION['loan_success'] ?? '';
        $errorMsg = $_SESSION['loan_error'] ?? '';
        unset($_SESSION['loan_success'], $_SESSION['loan_error']);

        $this->view('loans/index', compact('records', 'stats', 'successMsg', 'errorMsg', 'role'));
    }

    public function create(): void
    {
        $role = current_user_role($this->conn);
        $employees = $this->loan->employees();
        $employeeId = $role === 'employee' ? current_employee_id($this->conn) : null;
        $this->view('loans/create', compact('employees', 'role', 'employeeId'));
    }

    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('loans.php?a=create');
        }

        if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
            $_SESSION['loan_error'] = 'Security token expired. Please try again.';
            $this->redirect('loans.php?a=create');
        }

        $role = current_user_role($this->conn);
        $employeeId = (int)($_POST['employee_id'] ?? 0);
        if ($role === 'employee') {
            $employeeId = (int)(current_employee_id($this->conn) ?? 0);
        }

        $requested = round((float)($_POST['requested_amount'] ?? 0), 2);
        $interest = round((float)($_POST['interest_rate'] ?? 0), 2);
        $tenure = (int)($_POST['tenure_months'] ?? 0);
        $approved = round((float)($_POST['approved_amount'] ?? 0), 2);

        if ($requested <= 0 || $tenure <= 0 || $employeeId <= 0) {
            $_SESSION['loan_error'] = 'Employee, requested amount and tenure are required.';
            $this->redirect('loans.php?a=create');
        }

        $status = $this->normalizeLoanStatus($_POST['status'] ?? 'pending', $role);
        $approvedDate = $status === 'approved' ? ($_POST['approved_date'] ?? date('Y-m-d')) : null;

        $emi = $this->calculateEmi($approved > 0 ? $approved : $requested, $interest, $tenure);
        $data = [
            'employee_id' => $employeeId,
            'requested_amount' => $requested,
            'approved_amount' => $status === 'approved' ? ($approved > 0 ? $approved : $requested) : null,
            'interest_rate' => $interest,
            'tenure_months' => $tenure,
            'emi_amount' => $emi,
            'purpose' => trim($_POST['purpose'] ?? ''),
            'request_date' => $_POST['request_date'] ?? date('Y-m-d'),
            'approved_date' => $approvedDate,
            'status' => $status,
            'remarks' => trim($_POST['remarks'] ?? ''),
            'created_by' => (int)$_SESSION['user_id']
        ];

        if ($this->loan->create($data)) {
            $loanId = (int)$this->conn->insert_id;
            $_SESSION['loan_success'] = 'Loan request saved successfully.';

            if ($status === 'approved') {
                $scheduleError = $this->syncRepaymentSchedule($loanId, 'approved', 'pending');
                if ($scheduleError !== null) {
                    $_SESSION['loan_error'] = $scheduleError;
                } else {
                    $_SESSION['loan_success'] .= ' Repayment schedule generated automatically.';
                    $this->notifyEmployee(
                        $employeeId,
                        'Repayment Schedule Generated',
                        'Your approved loan now has an automatic repayment schedule.',
                        'repayments.php'
                    );
                }
            }

            if ($role === 'employee' || $status === 'pending') {
                $this->notifyRoles(
                    ['admin', 'hr', 'finance'],
                    'New Loan Request',
                    'A new loan request has been submitted and requires review.',
                    'loans.php'
                );
            }

            if ($role === 'employee') {
                $this->notifyEmployee(
                    $employeeId,
                    'Loan Request Submitted',
                    'Your loan request has been submitted and is currently pending review.',
                    'loans.php'
                );
            } elseif ($status === 'approved') {
                $this->notifyEmployee(
                    $employeeId,
                    'Loan Approved',
                    'A loan was created and approved for you.',
                    'loans.php'
                );
            } elseif ($status === 'rejected') {
                $this->notifyEmployee(
                    $employeeId,
                    'Loan Rejected',
                    'A loan request was created and marked as rejected.',
                    'loans.php'
                );
            }
        } else {
            $_SESSION['loan_error'] = 'Error while saving loan request.';
        }

        $this->redirect('loans.php?a=index');
    }

    public function edit(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $record = $this->loan->find($id);
        if (!$record) {
            $_SESSION['loan_error'] = 'Loan record not found.';
            $this->redirect('loans.php?a=index');
        }

        $role = current_user_role($this->conn);
        if ($role === 'employee') {
            $employeeId = (int)(current_employee_id($this->conn) ?? 0);
            if ((int)$record['employee_id'] !== $employeeId) {
                http_response_code(403);
                echo 'Forbidden.';
                exit;
            }
        }

        $employees = $this->loan->employees();
        $this->view('loans/edit', compact('record', 'employees', 'role'));
    }

    public function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('loans.php?a=index');
        }

        if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
            $_SESSION['loan_error'] = 'Security token expired. Please try again.';
            $this->redirect('loans.php?a=index');
        }

        $id = (int)($_POST['id'] ?? 0);
        $existing = $this->loan->find($id);
        if (!$existing) {
            $_SESSION['loan_error'] = 'Loan record not found.';
            $this->redirect('loans.php?a=index');
        }

        $role = current_user_role($this->conn);
        $employeeId = (int)($_POST['employee_id'] ?? 0);
        if ($role === 'employee') {
            $employeeId = (int)(current_employee_id($this->conn) ?? 0);
            if ((int)$existing['employee_id'] !== $employeeId) {
                http_response_code(403);
                echo 'Forbidden.';
                exit;
            }
        }

        $requested = round((float)($_POST['requested_amount'] ?? 0), 2);
        $approved = round((float)($_POST['approved_amount'] ?? 0), 2);
        $interest = round((float)($_POST['interest_rate'] ?? 0), 2);
        $tenure = (int)($_POST['tenure_months'] ?? 0);

        $status = $role === 'employee'
            ? (string)$existing['status']
            : $this->normalizeLoanStatus($_POST['status'] ?? 'pending', $role, (string)$existing['status']);

        $approvedDate = $status === 'approved'
            ? ($_POST['approved_date'] ?? ($existing['approved_date'] ?: date('Y-m-d')))
            : null;

        $principal = $approved > 0 ? $approved : $requested;
        $emi = $this->calculateEmi($principal, $interest, $tenure);

        $data = [
            'employee_id' => $employeeId,
            'requested_amount' => $requested,
            'approved_amount' => $status === 'approved' ? $principal : null,
            'interest_rate' => $interest,
            'tenure_months' => $tenure,
            'emi_amount' => $emi,
            'purpose' => trim($_POST['purpose'] ?? ''),
            'request_date' => $_POST['request_date'] ?? date('Y-m-d'),
            'approved_date' => $approvedDate,
            'status' => $status,
            'remarks' => trim($_POST['remarks'] ?? '')
        ];

        if (in_array((string)$existing['status'], ['approved', 'closed'], true) && in_array($status, ['pending', 'rejected'], true)) {
            if (!$this->repayment->canClearScheduleForLoan($id)) {
                $_SESSION['loan_error'] = $this->repayment->getLastError() ?: 'This loan already has repayments and cannot be moved back automatically.';
                $this->redirect('loans.php?a=index');
            }
        }

        if ($this->loan->update($id, $data)) {
            $_SESSION['loan_success'] = 'Loan request updated.';

            $oldStatus = (string)$existing['status'];
            $newStatus = (string)$data['status'];

            $scheduleError = $this->syncRepaymentSchedule($id, $newStatus, $oldStatus);
            if ($scheduleError !== null) {
                $_SESSION['loan_error'] = $scheduleError;
            } elseif ($newStatus === 'approved') {
                $_SESSION['loan_success'] .= ' Repayment schedule is synced with the latest approval details.';
            }

            $this->notifyRoles(
                ['admin', 'hr', 'finance'],
                'Loan Updated',
                'Loan #' . $id . ' has been updated.',
                'loans.php?a=edit&id=' . $id
            );

            if ($oldStatus !== $newStatus) {
                $this->loan->addStatusHistory(
                    $id,
                    $oldStatus,
                    $newStatus,
                    (int)$_SESSION['user_id'],
                    'Status changed via loan update'
                );

                $this->notifyEmployee(
                    (int)$existing['employee_id'],
                    'Loan Status Updated',
                    'Your loan #' . $id . ' status changed from ' . $oldStatus . ' to ' . $newStatus . '.',
                    'loans.php?a=edit&id=' . $id
                );

                $this->notifyRoles(
                    ['admin', 'hr', 'finance'],
                    'Loan Status Transition',
                    'Loan #' . $id . ' moved from ' . $oldStatus . ' to ' . $newStatus . '.',
                    'loans.php?a=edit&id=' . $id
                );
            }
        } else {
            $_SESSION['loan_error'] = 'Error while updating loan request.';
        }

        $this->redirect('loans.php?a=index');
    }

    public function delete(): void
    {
        require_roles($this->conn, ['admin', 'hr', 'finance']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_is_valid($_POST['csrf_token'] ?? null)) {
            $_SESSION['loan_error'] = 'Security token expired. Please try again.';
            $this->redirect('loans.php?a=index');
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0 && $this->loan->delete($id)) {
            $_SESSION['loan_success'] = 'Loan record deleted.';
        } else {
            $_SESSION['loan_error'] = 'Unable to delete loan record.';
        }

        $this->redirect('loans.php?a=index');
    }

    protected function calculateEmi(float $principal, float $interest, int $tenure): float
    {
        if ($tenure <= 0) {
            return 0.0;
        }

        $monthlyRate = ($interest / 12) / 100;
        if ($monthlyRate > 0) {
            return round(
                $principal * $monthlyRate * pow(1 + $monthlyRate, $tenure) / (pow(1 + $monthlyRate, $tenure) - 1),
                2
            );
        }

        return round($principal / $tenure, 2);
    }

    protected function normalizeLoanStatus(string $status, string $role, string $currentStatus = 'pending'): string
    {
        if ($role === 'employee') {
            return 'pending';
        }

        if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
            return $status;
        }

        if ($status === 'closed' && $currentStatus === 'closed') {
            return 'closed';
        }

        return 'pending';
    }

    protected function syncRepaymentSchedule(int $loanId, string $newStatus, string $oldStatus): ?string
    {
        if ($newStatus === 'approved') {
            $loan = $this->loan->find($loanId);
            if ($loan && !$this->repayment->syncScheduleForLoan($loan)) {
                return $this->repayment->getLastError() ?: 'Repayment schedule could not be synced.';
            }
        }

        if (in_array($oldStatus, ['approved', 'closed'], true) && in_array($newStatus, ['pending', 'rejected'], true)) {
            if (!$this->repayment->clearScheduleForLoan($loanId)) {
                return $this->repayment->getLastError() ?: 'Repayment schedule could not be cleared.';
            }
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

    protected function notifyEmployee(int $employeeId, string $title, string $body, string $link = ''): void
    {
        $userId = $this->loan->employeeUserId($employeeId);
        if ($userId && $userId > 0) {
            $this->notification->create($userId, $title, $body, $link);
        }
    }
}
