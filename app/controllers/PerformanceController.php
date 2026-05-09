<?php

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Authz.php';
require_once __DIR__ . '/../models/Performance.php';

class PerformanceController extends Controller
{
    protected Performance $performance;

    public function __construct(mysqli $conn)
    {
        parent::__construct($conn);
        require_roles($conn, ['admin', 'hr', 'employee']);
        $this->performance = new Performance($conn);
    }

    public function index(): void
    {
        $role = current_user_role($this->conn);
        $employeeId = $role === 'employee' ? current_employee_id($this->conn) : null;

        $records = $this->performance->listAll($employeeId);
        $successMsg = $_SESSION['perf_success'] ?? '';
        $errorMsg = $_SESSION['perf_error'] ?? '';
        unset($_SESSION['perf_success'], $_SESSION['perf_error']);

        $this->view('performance/index', compact('records', 'successMsg', 'errorMsg', 'role'));
    }

    public function create(): void
    {
        require_roles($this->conn, ['admin', 'hr']);
        $employees = $this->performance->employees();
        $this->view('performance/create', compact('employees'));
    }

    public function store(): void
    {
        require_roles($this->conn, ['admin', 'hr']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('performance.php?a=create');
        }

        if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
            $_SESSION['perf_error'] = 'Security token expired. Please try again.';
            $this->redirect('performance.php?a=create');
        }

        $test = (float)($_POST['test_score'] ?? 0);
        $attendance = (float)($_POST['attendance_score'] ?? 0);
        $overall = round(($test * 0.7) + ($attendance * 0.3), 2);

        $grade = 'D';
        if ($overall >= 85) {
            $grade = 'A';
        } elseif ($overall >= 70) {
            $grade = 'B';
        } elseif ($overall >= 50) {
            $grade = 'C';
        }

        $recommended = !empty($_POST['recommended_designation']) ? 'recommended' : 'none';

        $data = [
            'employee_id' => (int)($_POST['employee_id'] ?? 0),
            'evaluation_month' => $_POST['evaluation_month'] ?? date('F Y'),
            'test_score' => $test,
            'attendance_score' => $attendance,
            'overall_score' => $overall,
            'grade' => $grade,
            'promotion_status' => $recommended,
            'promoted_designation' => trim($_POST['recommended_designation'] ?? '') ?: null,
            'promotion_effective_date' => ($_POST['recommended_effective_date'] ?? '') ?: null,
            'promotion_note' => trim($_POST['promotion_note'] ?? '') ?: null,
            'reviewer_user_id' => (int)$_SESSION['user_id'],
            'remarks' => trim($_POST['remarks'] ?? '')
        ];

        if ($data['employee_id'] <= 0) {
            $_SESSION['perf_error'] = 'Employee is required.';
            $this->redirect('performance.php?a=create');
        }

        if ($this->performance->create($data)) {
            $_SESSION['perf_success'] = 'Performance record saved.';
            $this->redirect('performance.php?a=index');
        }

        $_SESSION['perf_error'] = 'Error while saving performance record.';
        $this->redirect('performance.php?a=create');
    }

    public function promote(): void
    {
        require_roles($this->conn, ['admin', 'hr']);
        $id = (int)($_GET['id'] ?? 0);
        $record = $this->performance->find($id);
        if (!$record) {
            $_SESSION['perf_error'] = 'Performance record not found.';
            $this->redirect('performance.php?a=index');
        }

        $this->view('performance/promote', compact('record'));
    }

    public function approvePromotion(): void
    {
        require_roles($this->conn, ['admin', 'hr']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('performance.php?a=index');
        }

        if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
            $_SESSION['perf_error'] = 'Security token expired. Please try again.';
            $this->redirect('performance.php?a=index');
        }

        $id = (int)($_POST['id'] ?? 0);
        $record = $this->performance->find($id);
        if (!$record) {
            $_SESSION['perf_error'] = 'Performance record not found.';
            $this->redirect('performance.php?a=index');
        }

        $action = $_POST['action_type'] ?? 'reject';
        $note = trim($_POST['decision_note'] ?? '');

        if ($action === 'approve') {
            $newDesignation = trim($_POST['promoted_designation'] ?? '');
            $effectiveDate = $_POST['promotion_effective_date'] ?? date('Y-m-d');

            if ($newDesignation === '') {
                $_SESSION['perf_error'] = 'New designation is required for approval.';
                $this->redirect('performance.php?a=promote&id=' . $id);
            }

            $ok1 = $this->performance->updatePromotion($id, 'approved', $newDesignation, $effectiveDate, $note ?: 'Promotion approved');
            $ok2 = $this->performance->applyPromotionToEmployee((int)$record['employee_id'], $newDesignation);
            $ok3 = $this->performance->insertPromotionHistory([
                'employee_id' => (int)$record['employee_id'],
                'performance_id' => $id,
                'old_designation' => (string)($record['designation'] ?? ''),
                'new_designation' => $newDesignation,
                'effective_date' => $effectiveDate,
                'approved_by' => (int)$_SESSION['user_id'],
                'note' => $note
            ]);

            if ($ok1 && $ok2 && $ok3) {
                $_SESSION['perf_success'] = 'Promotion approved and employee designation updated.';
            } else {
                $_SESSION['perf_error'] = 'Failed to complete promotion approval.';
            }
        } else {
            if ($this->performance->updatePromotion($id, 'rejected', null, null, $note ?: 'Promotion rejected')) {
                $_SESSION['perf_success'] = 'Promotion request rejected.';
            } else {
                $_SESSION['perf_error'] = 'Failed to reject promotion request.';
            }
        }

        $this->redirect('performance.php?a=index');
    }

    public function delete(): void
    {
        require_roles($this->conn, ['admin', 'hr']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_is_valid($_POST['csrf_token'] ?? null)) {
            $_SESSION['perf_error'] = 'Security token expired. Please try again.';
            $this->redirect('performance.php?a=index');
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0 && $this->performance->delete($id)) {
            $_SESSION['perf_success'] = 'Performance record deleted.';
        } else {
            $_SESSION['perf_error'] = 'Unable to delete performance record.';
        }
        $this->redirect('performance.php?a=index');
    }
}
