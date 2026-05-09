<?php

class Message
{
    protected mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function listAll(?int $employeeId = null): array
    {
        $sql = "SELECT m.*, e.employee_code, e.first_name, e.last_name,
                       su.full_name AS sender_name,
                       ru.full_name AS receiver_name
                FROM phphr_messages m
                INNER JOIN phphr_employees e ON e.id = m.employee_id
                INNER JOIN phphr_users su ON su.id = m.sender_user_id
                LEFT JOIN phphr_users ru ON ru.id = m.receiver_user_id";

        if ($employeeId) {
            $sql .= " WHERE m.employee_id = " . (int)$employeeId;
        }

        $sql .= " ORDER BY m.created_at DESC";

        $result = $this->conn->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function create(array $data): bool
    {
        $sql = "INSERT INTO phphr_messages
                (employee_id, sender_user_id, receiver_user_id, context_type, context_id, subject, message)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            'iiisiss',
            $data['employee_id'],
            $data['sender_user_id'],
            $data['receiver_user_id'],
            $data['context_type'],
            $data['context_id'],
            $data['subject'],
            $data['message']
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

    public function users(): array
    {
        $result = $this->conn->query(
            "SELECT id, full_name, email FROM phphr_users ORDER BY full_name ASC"
        );
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
}
