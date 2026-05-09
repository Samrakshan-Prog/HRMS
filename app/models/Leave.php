<?php

class LeaveModel
{
    protected mysqli $conn;
    protected string $table = 'phphr_leaves';

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function getActiveEmployees(): array
    {
        $sql = "SELECT id, employee_code, first_name, last_name
                FROM phphr_employees
                WHERE status = 1
                ORDER BY first_name ASC";

        $result = $this->conn->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function all(?int $employeeId = null): array
    {
        $sql = "SELECT l.*,
                       e.first_name, e.last_name, e.employee_code
                FROM {$this->table} l
                LEFT JOIN phphr_employees e ON e.id = l.employee_id";

        if ($employeeId !== null) {
            $sql .= " WHERE l.employee_id = " . (int)$employeeId;
        }

        $sql .= " ORDER BY l.applied_at DESC";
        $result = $this->conn->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function find($id)
    {
        $stmt = $this->conn->prepare(
            "SELECT l.*, e.employee_code, e.first_name, e.last_name
             FROM {$this->table} l
             LEFT JOIN phphr_employees e ON e.id = l.employee_id
             WHERE l.id = ?"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function create($data)
    {
        $sql = "INSERT INTO {$this->table}
                (employee_id, leave_type, start_date, end_date, reason, status)
                VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            'isssss',
            $data['employee_id'],
            $data['leave_type'],
            $data['start_date'],
            $data['end_date'],
            $data['reason'],
            $data['status']
        );

        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function update($id, $data)
    {
        $sql = "UPDATE {$this->table}
                SET employee_id=?, leave_type=?, start_date=?, end_date=?, reason=?, status=?
                WHERE id=?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            'isssssi',
            $data['employee_id'],
            $data['leave_type'],
            $data['start_date'],
            $data['end_date'],
            $data['reason'],
            $data['status'],
            $id
        );

        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function delete($id)
    {
        $stmt = $this->conn->prepare(
            "DELETE FROM {$this->table} WHERE id=?"
        );
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function overlapExists(int $employeeId, string $startDate, string $endDate, int $ignoreId = 0): bool
    {
        $sql = "SELECT id
                FROM {$this->table}
                WHERE employee_id = ?
                  AND status IN ('pending', 'approved')
                  AND start_date <= ?
                  AND end_date >= ?";
        if ($ignoreId > 0) {
            $sql .= " AND id <> ?";
        }
        $sql .= " LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        if ($ignoreId > 0) {
            $stmt->bind_param('issi', $employeeId, $endDate, $startDate, $ignoreId);
        } else {
            $stmt->bind_param('iss', $employeeId, $endDate, $startDate);
        }
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return !empty($row);
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
}
