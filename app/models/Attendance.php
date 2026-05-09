<?php
	
class Attendance
{
    protected $conn;
    protected $table = 'phphr_attendance';

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    // =========================
    // GET ALL ATTENDANCE RECORDS
    // =========================
    public function all()
    {
        $sql = "SELECT a.*,
                       e.first_name,
                       e.last_name,
                       e.employee_code
                FROM {$this->table} a
                LEFT JOIN phphr_employees e ON e.id = a.employee_id
                ORDER BY a.attendance_date DESC";

        $result = $this->conn->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function allByEmployee(int $employeeId): array
    {
        $stmt = $this->conn->prepare(
            "SELECT a.*, e.first_name, e.last_name, e.employee_code
             FROM {$this->table} a
             LEFT JOIN phphr_employees e ON e.id = a.employee_id
             WHERE a.employee_id = ?
             ORDER BY a.attendance_date DESC"
        );
        $stmt->bind_param('i', $employeeId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    // =========================
    // FIND SINGLE RECORD
    // =========================
    public function find($id)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM {$this->table} WHERE id = ?"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // =========================
    // CREATE ATTENDANCE
    // =========================
    public function create($data)
    {
        $sql = "INSERT INTO {$this->table}
                (employee_id, attendance_date, check_in, check_out, status)
                VALUES (?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            'issss',
            $data['employee_id'],
            $data['attendance_date'],
            $data['check_in'],
            $data['check_out'],
            $data['status']
        );

        return $stmt->execute();
    }

    // =========================
    // UPDATE ATTENDANCE
    // =========================
    public function update($id, $data)
    {
        $sql = "UPDATE {$this->table}
                SET employee_id = ?, attendance_date = ?, check_in = ?, check_out = ?, status = ?
                WHERE id = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            'issssi',
            $data['employee_id'],
            $data['attendance_date'],
            $data['check_in'],
            $data['check_out'],
            $data['status'],
            $id
        );

        return $stmt->execute();
    }

    // =========================
    // DELETE ATTENDANCE
    // =========================
    public function delete($id)
    {
        $stmt = $this->conn->prepare(
            "DELETE FROM {$this->table} WHERE id = ?"
        );
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }

    public function todayByEmployee(int $employeeId): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM {$this->table}
             WHERE employee_id = ? AND attendance_date = CURDATE()
             LIMIT 1"
        );
        $stmt->bind_param('i', $employeeId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function punchIn(int $employeeId): bool
    {
        $existing = $this->todayByEmployee($employeeId);
        if ($existing) {
            if (!empty($existing['check_in'])) {
                return false;
            }
            $stmt = $this->conn->prepare(
                "UPDATE {$this->table}
                 SET check_in = CURTIME(), status = 'present'
                 WHERE id = ?"
            );
            $id = (int)$existing['id'];
            $stmt->bind_param('i', $id);
            $ok = $stmt->execute();
            $stmt->close();
            return $ok;
        }

        $stmt = $this->conn->prepare(
            "INSERT INTO {$this->table} (employee_id, attendance_date, check_in, status)
             VALUES (?, CURDATE(), CURTIME(), 'present')"
        );
        $stmt->bind_param('i', $employeeId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function punchOut(int $employeeId): bool
    {
        $existing = $this->todayByEmployee($employeeId);
        if (!$existing || empty($existing['check_in']) || !empty($existing['check_out'])) {
            return false;
        }

        $stmt = $this->conn->prepare(
            "UPDATE {$this->table} SET check_out = CURTIME() WHERE id = ?"
        );
        $id = (int)$existing['id'];
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}

