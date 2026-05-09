<?php

class Membership
{
    protected mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function listAll(?int $employeeId = null): array
    {
        $sql = "SELECT m.*, e.employee_code, e.first_name, e.last_name, u.full_name AS approver_name
                FROM phphr_employee_membership m
                INNER JOIN phphr_employees e ON e.id = m.employee_id
                LEFT JOIN phphr_users u ON u.id = m.approved_by";
        if ($employeeId) {
            $sql .= " WHERE m.employee_id = " . (int)$employeeId;
        }
        $sql .= " ORDER BY m.created_at DESC";

        $result = $this->conn->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function create(array $data): bool
    {
        $sql = "INSERT INTO phphr_employee_membership
                (employee_id, membership_no, membership_type, start_date, end_date, status, approved_by, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            'isssssis',
            $data['employee_id'],
            $data['membership_no'],
            $data['membership_type'],
            $data['start_date'],
            $data['end_date'],
            $data['status'],
            $data['approved_by'],
            $data['notes']
        );
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->conn->prepare('DELETE FROM phphr_employee_membership WHERE id = ?');
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
             ORDER BY first_name ASC"
        );
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
}
