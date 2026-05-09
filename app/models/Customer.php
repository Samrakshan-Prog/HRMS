<?php

class Customer
{
    protected mysqli $conn;
    protected string $table = 'phphr_customers';

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function all(): array
    {
        $result = $this->conn->query("SHOW TABLES LIKE '{$this->table}'");
        if (!$result || $result->num_rows === 0) {
            return [];
        }

        $rows = $this->conn->query("SELECT * FROM {$this->table} ORDER BY id DESC");
        return $rows ? $rows->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function find(int $id): ?array
    {
        $result = $this->conn->query("SHOW TABLES LIKE '{$this->table}'");
        if (!$result || $result->num_rows === 0) {
            return null;
        }

        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function emailExists(string $email): bool
    {
        $result = $this->conn->query("SHOW TABLES LIKE '{$this->table}'");
        if (!$result || $result->num_rows === 0) {
            return false;
        }

        $stmt = $this->conn->prepare("SELECT id FROM {$this->table} WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return !empty($row);
    }

    public function create(array $data): bool
    {
        $result = $this->conn->query("SHOW TABLES LIKE '{$this->table}'");
        if (!$result || $result->num_rows === 0) {
            return false;
        }

        $sql = "INSERT INTO {$this->table}
                (customer_code, name, email, phone, company_name, company_reg_no, company_address, country, role, employees, user_status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            'sssssssssss',
            $data['customer_code'],
            $data['name'],
            $data['email'],
            $data['phone'],
            $data['company_name'],
            $data['company_reg_no'],
            $data['company_address'],
            $data['country'],
            $data['role'],
            $data['employees'],
            $data['user_status']
        );
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function update(int $id, array $data): bool
    {
        $result = $this->conn->query("SHOW TABLES LIKE '{$this->table}'");
        if (!$result || $result->num_rows === 0) {
            return false;
        }

        $sql = "UPDATE {$this->table}
                SET name=?, email=?, phone=?, company_name=?, company_reg_no=?, company_address=?, country=?, role=?, employees=?, user_status=?
                WHERE id=?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            'ssssssssssi',
            $data['name'],
            $data['email'],
            $data['phone'],
            $data['company_name'],
            $data['company_reg_no'],
            $data['company_address'],
            $data['country'],
            $data['role'],
            $data['employees'],
            $data['user_status'],
            $id
        );
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function delete(int $id): bool
    {
        $result = $this->conn->query("SHOW TABLES LIKE '{$this->table}'");
        if (!$result || $result->num_rows === 0) {
            return false;
        }

        $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE id = ?");
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}
