<?php

class Loan
{
    protected mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function listAll(?int $employeeId = null): array
    {
        $sql = "SELECT l.*, e.employee_code, e.first_name, e.last_name
                FROM phphr_loans l
                INNER JOIN phphr_employees e ON e.id = l.employee_id";

        if ($employeeId) {
            $sql .= " WHERE l.employee_id = " . (int)$employeeId;
        }

        $sql .= " ORDER BY l.created_at DESC";

        $result = $this->conn->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function find(int $id): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT l.*, e.employee_code, e.first_name, e.last_name
             FROM phphr_loans l
             INNER JOIN phphr_employees e ON e.id = l.employee_id
             WHERE l.id = ? LIMIT 1"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row ?: null;
    }

    public function create(array $data): bool
    {
        $sql = "INSERT INTO phphr_loans
                (employee_id, requested_amount, approved_amount, interest_rate, tenure_months, emi_amount,
                 purpose, request_date, approved_date, status, remarks, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            'idddidsssssi',
            $data['employee_id'],
            $data['requested_amount'],
            $data['approved_amount'],
            $data['interest_rate'],
            $data['tenure_months'],
            $data['emi_amount'],
            $data['purpose'],
            $data['request_date'],
            $data['approved_date'],
            $data['status'],
            $data['remarks'],
            $data['created_by']
        );
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE phphr_loans
                SET employee_id=?, requested_amount=?, approved_amount=?, interest_rate=?, tenure_months=?,
                    emi_amount=?, purpose=?, request_date=?, approved_date=?, status=?, remarks=?
                WHERE id=?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            'idddidsssssi',
            $data['employee_id'],
            $data['requested_amount'],
            $data['approved_amount'],
            $data['interest_rate'],
            $data['tenure_months'],
            $data['emi_amount'],
            $data['purpose'],
            $data['request_date'],
            $data['approved_date'],
            $data['status'],
            $data['remarks'],
            $id
        );
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM phphr_loans WHERE id = ?");
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function employees(): array
    {
        $result = $this->conn->query(
            "SELECT id, employee_code, first_name, last_name
             FROM phphr_employees
             WHERE status = 1
             ORDER BY first_name ASC"
        );
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function stats(?int $employeeId = null): array
    {
        $sql = "SELECT
                    COUNT(*) AS total_loans,
                    SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) AS pending_loans,
                    SUM(CASE WHEN status='approved' THEN 1 ELSE 0 END) AS approved_loans,
                    COALESCE(SUM(CASE WHEN status IN ('approved','closed') THEN approved_amount ELSE 0 END),0) AS approved_amount
                FROM phphr_loans";
        if ($employeeId) {
            $sql .= " WHERE employee_id = " . (int)$employeeId;
        }
        $row = $this->conn->query($sql)->fetch_assoc();
        return $row ?: [];
    }

    public function employeeUserId(int $employeeId): ?int
    {
        $stmt = $this->conn->prepare(
            "SELECT user_id FROM phphr_employees WHERE id = ? LIMIT 1"
        );
        $stmt->bind_param('i', $employeeId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return isset($row['user_id']) ? (int)$row['user_id'] : null;
    }

    public function addStatusHistory(int $loanId, ?string $oldStatus, string $newStatus, int $changedBy, string $note = ''): bool
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO phphr_loan_status_history (loan_id, old_status, new_status, changed_by, note)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('issis', $loanId, $oldStatus, $newStatus, $changedBy, $note);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}
