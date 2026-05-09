<?php

class LoanRepayment
{
    protected mysqli $conn;
    protected string $lastError = '';

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function getLastError(): string
    {
        return $this->lastError;
    }

    public function refreshStatuses(?int $loanId = null): void
    {
        $sql = "UPDATE phphr_loan_repayments
                SET payment_status = CASE
                    WHEN amount_due > 0 AND amount_paid >= amount_due THEN 'paid'
                    WHEN amount_paid > 0 THEN 'partial'
                    WHEN due_date < CURDATE() THEN 'overdue'
                    ELSE 'pending'
                END";

        if ($loanId !== null) {
            $sql .= " WHERE loan_id = " . (int)$loanId;
        }

        $this->conn->query($sql);
    }

    public function listAll(?int $employeeId = null): array
    {
        $this->refreshStatuses();

        $sql = "SELECT r.*, l.employee_id, e.employee_code, e.first_name, e.last_name
                FROM phphr_loan_repayments r
                INNER JOIN phphr_loans l ON l.id = r.loan_id
                INNER JOIN phphr_employees e ON e.id = l.employee_id";
        if ($employeeId) {
            $sql .= " WHERE l.employee_id = " . (int)$employeeId;
        }
        $sql .= " ORDER BY r.due_date DESC, r.id DESC";

        $result = $this->conn->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function find(int $id): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT r.*, l.employee_id
             FROM phphr_loan_repayments r
             INNER JOIN phphr_loans l ON l.id = r.loan_id
             WHERE r.id = ? LIMIT 1"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row ?: null;
    }

    public function create(array $data): bool
    {
        $data = $this->normalizeRepaymentData($data);
        $sql = "INSERT INTO phphr_loan_repayments
                (loan_id, due_date, paid_date, amount_due, amount_paid, payment_status, payment_mode, reference_no, remarks)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            'issddssss',
            $data['loan_id'],
            $data['due_date'],
            $data['paid_date'],
            $data['amount_due'],
            $data['amount_paid'],
            $data['payment_status'],
            $data['payment_mode'],
            $data['reference_no'],
            $data['remarks']
        );
        $ok = $stmt->execute();
        $this->lastError = $ok ? '' : $stmt->error;
        $stmt->close();

        if ($ok) {
            $this->recalculateLoanStatus((int)$data['loan_id']);
        }

        return $ok;
    }

    public function update(int $id, array $data): bool
    {
        $data = $this->normalizeRepaymentData($data);
        $sql = "UPDATE phphr_loan_repayments
                SET loan_id=?, due_date=?, paid_date=?, amount_due=?, amount_paid=?, payment_status=?, payment_mode=?, reference_no=?, remarks=?
                WHERE id=?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            'issddssssi',
            $data['loan_id'],
            $data['due_date'],
            $data['paid_date'],
            $data['amount_due'],
            $data['amount_paid'],
            $data['payment_status'],
            $data['payment_mode'],
            $data['reference_no'],
            $data['remarks'],
            $id
        );
        $ok = $stmt->execute();
        $this->lastError = $ok ? '' : $stmt->error;
        $stmt->close();

        if ($ok) {
            $this->recalculateLoanStatus((int)$data['loan_id']);
        }

        return $ok;
    }

    public function delete(int $id): bool
    {
        $loanId = 0;
        $record = $this->find($id);
        if ($record) {
            $loanId = (int)$record['loan_id'];
        }

        $stmt = $this->conn->prepare("DELETE FROM phphr_loan_repayments WHERE id = ?");
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $this->lastError = $ok ? '' : $stmt->error;
        $stmt->close();

        if ($ok && $loanId > 0) {
            $this->recalculateLoanStatus($loanId);
        }

        return $ok;
    }

    public function loans(?int $employeeId = null): array
    {
        $this->refreshStatuses();

        $sql = "SELECT l.id, l.status, l.approved_amount, l.requested_amount, l.emi_amount,
                       e.employee_code, e.first_name, e.last_name
                FROM phphr_loans l
                INNER JOIN phphr_employees e ON e.id = l.employee_id
                WHERE l.status IN ('approved','closed')";
        if ($employeeId) {
            $sql .= " AND l.employee_id = " . (int)$employeeId;
        }
        $sql .= " ORDER BY l.id DESC";

        $result = $this->conn->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function employeeUserIdByLoan(int $loanId): ?int
    {
        $stmt = $this->conn->prepare(
            "SELECT e.user_id
             FROM phphr_loans l
             INNER JOIN phphr_employees e ON e.id = l.employee_id
             WHERE l.id = ?
             LIMIT 1"
        );
        $stmt->bind_param('i', $loanId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return isset($row['user_id']) ? (int)$row['user_id'] : null;
    }

    public function syncScheduleForLoan(array $loan): bool
    {
        $this->lastError = '';
        $loanId = (int)($loan['id'] ?? 0);
        if ($loanId <= 0) {
            $this->lastError = 'Loan not found for repayment schedule generation.';
            return false;
        }

        $tenure = max((int)($loan['tenure_months'] ?? 0), 0);
        if ($tenure <= 0) {
            $this->lastError = 'Loan tenure must be greater than zero to generate a repayment schedule.';
            return false;
        }

        $summaryStmt = $this->conn->prepare(
            "SELECT COUNT(*) AS total_rows,
                    SUM(CASE WHEN amount_paid > 0 THEN 1 ELSE 0 END) AS paid_rows
             FROM phphr_loan_repayments
             WHERE loan_id = ?"
        );
        $summaryStmt->bind_param('i', $loanId);
        $summaryStmt->execute();
        $summary = $summaryStmt->get_result()->fetch_assoc();
        $summaryStmt->close();

        if ((int)($summary['paid_rows'] ?? 0) > 0) {
            $this->lastError = 'Repayment schedule already has payments, so it was not regenerated automatically.';
            return false;
        }

        $approvedAmount = (float)($loan['approved_amount'] ?? 0);
        if ($approvedAmount <= 0) {
            $approvedAmount = (float)($loan['requested_amount'] ?? 0);
        }

        $emiAmount = (float)($loan['emi_amount'] ?? 0);
        if ($emiAmount <= 0 && $tenure > 0) {
            $emiAmount = $approvedAmount / $tenure;
        }

        $baseDue = round($emiAmount, 2);
        $scheduleStart = (string)($loan['approved_date'] ?: $loan['request_date'] ?: date('Y-m-d'));
        $totalScheduled = round($baseDue * $tenure, 2);

        $this->conn->begin_transaction();

        try {
            $deleteStmt = $this->conn->prepare("DELETE FROM phphr_loan_repayments WHERE loan_id = ?");
            $deleteStmt->bind_param('i', $loanId);
            if (!$deleteStmt->execute()) {
                $error = $deleteStmt->error;
                $deleteStmt->close();
                throw new RuntimeException($error ?: 'Unable to reset existing repayment schedule.');
            }
            $deleteStmt->close();

            $insertStmt = $this->conn->prepare(
                "INSERT INTO phphr_loan_repayments
                 (loan_id, due_date, paid_date, amount_due, amount_paid, payment_status, payment_mode, reference_no, remarks)
                 VALUES (?, ?, NULL, ?, 0, ?, NULL, NULL, ?)"
            );

            $runningTotal = 0.0;
            for ($i = 1; $i <= $tenure; $i++) {
                $dueDate = date('Y-m-d', strtotime($scheduleStart . ' +' . $i . ' month'));
                $amountDue = $i === $tenure
                    ? round($totalScheduled - $runningTotal, 2)
                    : $baseDue;

                $runningTotal += $amountDue;
                $status = $dueDate < date('Y-m-d') ? 'overdue' : 'pending';
                $remarks = 'Auto-generated from loan approval';

                $insertStmt->bind_param('isdss', $loanId, $dueDate, $amountDue, $status, $remarks);
                if (!$insertStmt->execute()) {
                    $error = $insertStmt->error;
                    $insertStmt->close();
                    throw new RuntimeException($error ?: 'Unable to create repayment schedule.');
                }
            }

            $insertStmt->close();
            $this->conn->commit();
            $this->recalculateLoanStatus($loanId);
            return true;
        } catch (Throwable $e) {
            $this->conn->rollback();
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function clearScheduleForLoan(int $loanId): bool
    {
        if (!$this->canClearScheduleForLoan($loanId)) {
            return false;
        }

        $stmt = $this->conn->prepare("DELETE FROM phphr_loan_repayments WHERE loan_id = ?");
        $stmt->bind_param('i', $loanId);
        $ok = $stmt->execute();
        $this->lastError = $ok ? '' : $stmt->error;
        $stmt->close();

        return $ok;
    }

    public function canClearScheduleForLoan(int $loanId): bool
    {
        $this->lastError = '';

        $summaryStmt = $this->conn->prepare(
            "SELECT SUM(CASE WHEN amount_paid > 0 THEN 1 ELSE 0 END) AS paid_rows
             FROM phphr_loan_repayments
             WHERE loan_id = ?"
        );
        $summaryStmt->bind_param('i', $loanId);
        $summaryStmt->execute();
        $summary = $summaryStmt->get_result()->fetch_assoc();
        $summaryStmt->close();

        if ((int)($summary['paid_rows'] ?? 0) > 0) {
            $this->lastError = 'Repayment schedule already has payments, so it was not removed automatically.';
            return false;
        }

        return true;
    }

    protected function normalizeRepaymentData(array $data): array
    {
        $data['amount_due'] = round(max((float)($data['amount_due'] ?? 0), 0), 2);
        $data['amount_paid'] = round(max((float)($data['amount_paid'] ?? 0), 0), 2);

        if ($data['amount_due'] > 0 && $data['amount_paid'] > $data['amount_due']) {
            $data['amount_paid'] = $data['amount_due'];
        }

        if ($data['amount_paid'] > 0 && empty($data['paid_date'])) {
            $data['paid_date'] = date('Y-m-d');
        }

        if ($data['amount_paid'] <= 0) {
            $data['paid_date'] = null;
        }

        $data['payment_status'] = $this->derivePaymentStatus($data);
        return $data;
    }

    protected function derivePaymentStatus(array $data): string
    {
        $amountDue = (float)($data['amount_due'] ?? 0);
        $amountPaid = (float)($data['amount_paid'] ?? 0);
        $dueDate = (string)($data['due_date'] ?? '');

        if ($amountDue > 0 && $amountPaid >= $amountDue) {
            return 'paid';
        }

        if ($amountPaid > 0) {
            return 'partial';
        }

        if ($dueDate !== '' && $dueDate < date('Y-m-d')) {
            return 'overdue';
        }

        return 'pending';
    }

    protected function recalculateLoanStatus(int $loanId): void
    {
        $this->refreshStatuses($loanId);

        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) AS total_rows,
                    SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) AS paid_rows
             FROM phphr_loan_repayments
             WHERE loan_id = ?"
        );
        $stmt->bind_param('i', $loanId);
        $stmt->execute();
        $summary = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $totalRows = (int)($summary['total_rows'] ?? 0);
        $paidRows = (int)($summary['paid_rows'] ?? 0);

        if ($totalRows > 0 && $paidRows === $totalRows) {
            $loanStmt = $this->conn->prepare(
                "UPDATE phphr_loans SET status = 'closed' WHERE id = ? AND status IN ('approved', 'closed')"
            );
        } else {
            $loanStmt = $this->conn->prepare(
                "UPDATE phphr_loans SET status = 'approved' WHERE id = ? AND status = 'closed'"
            );
        }

        $loanStmt->bind_param('i', $loanId);
        $loanStmt->execute();
        $loanStmt->close();
    }
}
