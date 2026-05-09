<?php

	
class Payroll
{
    protected $conn;
    protected $table = 'phphr_payroll';

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    // ALL PAYROLLS
    public function all()
    {
        $sql = "SELECT p.*,
                       e.first_name, e.last_name, e.employee_code
                FROM {$this->table} p
                LEFT JOIN phphr_employees e ON e.id = p.employee_id
                ORDER BY p.generated_at DESC";

        $result = $this->conn->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    // FIND
    public function find($id)
    {
        $stmt = $this->conn->prepare(
            "SELECT p.*, e.employee_code, e.first_name, e.last_name, e.department, e.designation
             FROM {$this->table} p
             LEFT JOIN phphr_employees e ON e.id = p.employee_id
             WHERE p.id = ?"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function employeeById(int $employeeId): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT id, employee_code, first_name, last_name, salary, department, designation
             FROM phphr_employees
             WHERE id = ?
             LIMIT 1"
        );
        $stmt->bind_param('i', $employeeId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function monthStatsForPayroll(int $employeeId, string $salaryMonth): array
    {
        // salaryMonth expected YYYY-MM
        $month = preg_match('/^\d{4}-\d{2}$/', $salaryMonth) ? $salaryMonth : date('Y-m');

        $start = $month . '-01';
        $end = date('Y-m-t', strtotime($start));
        $daysInMonth = (int)date('t', strtotime($start));

        $attendanceMap = [];
        $attendanceStmt = $this->conn->prepare(
            "SELECT attendance_date, status
             FROM phphr_attendance
             WHERE employee_id = ?
               AND attendance_date BETWEEN ? AND ?"
        );
        $attendanceStmt->bind_param('iss', $employeeId, $start, $end);
        $attendanceStmt->execute();
        $attendanceRows = $attendanceStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $attendanceStmt->close();
        foreach ($attendanceRows as $row) {
            $attendanceMap[$row['attendance_date']] = $row['status'];
        }

        $leaveMap = [];
        $leaveStmt = $this->conn->prepare(
            "SELECT leave_type, start_date, end_date
             FROM phphr_leaves
             WHERE employee_id = ?
               AND status = 'approved'
               AND start_date <= ?
               AND end_date >= ?"
        );
        $leaveStmt->bind_param('iss', $employeeId, $end, $start);
        $leaveStmt->execute();
        $leaveRows = $leaveStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $leaveStmt->close();

        foreach ($leaveRows as $row) {
            $rangeStart = max($row['start_date'], $start);
            $rangeEnd = min($row['end_date'], $end);
            $cursor = $rangeStart;

            while ($cursor <= $rangeEnd) {
                $leaveMap[$cursor] = $row['leave_type'];
                $cursor = date('Y-m-d', strtotime($cursor . ' +1 day'));
            }
        }

        $absenceDays = 0.0;
        $cursor = $start;
        while ($cursor <= $end) {
            if (isset($leaveMap[$cursor])) {
                if ($leaveMap[$cursor] === 'unpaid') {
                    $absenceDays += 1;
                }
            } elseif (($attendanceMap[$cursor] ?? '') === 'absent') {
                $absenceDays += 1;
            } elseif (($attendanceMap[$cursor] ?? '') === 'half_day') {
                $absenceDays += 0.5;
            }

            $cursor = date('Y-m-d', strtotime($cursor . ' +1 day'));
        }

        $emiStmt = $this->conn->prepare(
            "SELECT COALESCE(SUM(GREATEST(amount_due - amount_paid, 0)), 0) AS emi_due
             FROM phphr_loan_repayments r
             INNER JOIN phphr_loans l ON l.id = r.loan_id
             WHERE l.employee_id = ?
               AND DATE_FORMAT(r.due_date, '%Y-%m') = ?
               AND r.payment_status IN ('pending', 'partial', 'overdue')"
        );
        $emiStmt->bind_param('is', $employeeId, $month);
        $emiStmt->execute();
        $emiRow = $emiStmt->get_result()->fetch_assoc();
        $emiStmt->close();

        return [
            'month' => $month,
            'days_in_month' => $daysInMonth,
            'leave_days' => $absenceDays,
            'loan_emi_due' => (float)($emiRow['emi_due'] ?? 0)
        ];
    }

    // CREATE
    public function create($data)
    {
        $sql = "INSERT INTO {$this->table}
                (employee_id, salary_month, basic_salary, allowances, bonus, leave_days,
                 leave_deduction, loan_emi_deduction, deductions, total_deductions, net_salary)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            'isddddddddd',
            $data['employee_id'],
            $data['salary_month'],
            $data['basic_salary'],
            $data['allowances'],
            $data['bonus'],
            $data['leave_days'],
            $data['leave_deduction'],
            $data['loan_emi_deduction'],
            $data['deductions'],
            $data['total_deductions'],
            $data['net_salary']
        );

        return $stmt->execute();
    }

    // UPDATE
    public function update($id, $data)
    {
        $sql = "UPDATE {$this->table}
                SET employee_id=?, salary_month=?, basic_salary=?, allowances=?, bonus=?, leave_days=?,
                    leave_deduction=?, loan_emi_deduction=?, deductions=?, total_deductions=?, net_salary=?
                WHERE id=?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            'isdddddddddi',
            $data['employee_id'],
            $data['salary_month'],
            $data['basic_salary'],
            $data['allowances'],
            $data['bonus'],
            $data['leave_days'],
            $data['leave_deduction'],
            $data['loan_emi_deduction'],
            $data['deductions'],
            $data['total_deductions'],
            $data['net_salary'],
            $id
        );

        return $stmt->execute();
    }

    // DELETE
    public function delete($id)
    {
        $stmt = $this->conn->prepare(
            "DELETE FROM {$this->table} WHERE id=?"
        );
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }
}
