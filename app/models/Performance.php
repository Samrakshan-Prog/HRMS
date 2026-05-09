<?php

class Performance
{
    protected mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function listAll(?int $employeeId = null): array
    {
        $sql = "SELECT p.*, e.employee_code, e.first_name, e.last_name, u.full_name AS reviewer_name
                FROM phphr_employee_performance p
                INNER JOIN phphr_employees e ON e.id = p.employee_id
                LEFT JOIN phphr_users u ON u.id = p.reviewer_user_id";
        if ($employeeId) {
            $sql .= " WHERE p.employee_id = " . (int)$employeeId;
        }
        $sql .= " ORDER BY p.created_at DESC";

        $result = $this->conn->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function create(array $data): bool
    {
        $sql = "INSERT INTO phphr_employee_performance
                (employee_id, evaluation_month, test_score, attendance_score, overall_score, grade,
                 promotion_status, promoted_designation, promotion_effective_date, promotion_note,
                 reviewer_user_id, remarks)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            'isdddsssssis',
            $data['employee_id'],
            $data['evaluation_month'],
            $data['test_score'],
            $data['attendance_score'],
            $data['overall_score'],
            $data['grade'],
            $data['promotion_status'],
            $data['promoted_designation'],
            $data['promotion_effective_date'],
            $data['promotion_note'],
            $data['reviewer_user_id'],
            $data['remarks']
        );
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->conn->prepare('DELETE FROM phphr_employee_performance WHERE id = ?');
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function find(int $id): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT p.*, e.employee_code, e.first_name, e.last_name, e.designation
             FROM phphr_employee_performance p
             INNER JOIN phphr_employees e ON e.id = p.employee_id
             WHERE p.id = ?
             LIMIT 1"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function updatePromotion(int $id, string $status, ?string $newDesignation, ?string $effectiveDate, ?string $note): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE phphr_employee_performance
             SET promotion_status = ?, promoted_designation = ?, promotion_effective_date = ?, promotion_note = ?
             WHERE id = ?"
        );
        $stmt->bind_param('ssssi', $status, $newDesignation, $effectiveDate, $note, $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function applyPromotionToEmployee(int $employeeId, string $newDesignation): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE phphr_employees SET designation = ? WHERE id = ?"
        );
        $stmt->bind_param('si', $newDesignation, $employeeId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function insertPromotionHistory(array $data): bool
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO phphr_promotion_history
             (employee_id, performance_id, old_designation, new_designation, effective_date, approved_by, note)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            'iisssis',
            $data['employee_id'],
            $data['performance_id'],
            $data['old_designation'],
            $data['new_designation'],
            $data['effective_date'],
            $data['approved_by'],
            $data['note']
        );
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function employees(): array
    {
        $result = $this->conn->query(
            "SELECT id, employee_code, first_name, last_name
             FROM phphr_employees
             ORDER BY first_name ASC"
        );
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
}
