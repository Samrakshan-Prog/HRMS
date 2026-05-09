<?php

// app/models/Employee.php

class Employee
{
    protected mysqli $conn;
    protected string $table = 'phphr_employees';
    protected string $lastError = '';

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function getLastError(): string
    {
        return $this->lastError;
    }

    public function all(): array
    {
        $sql = "SELECT e.*, u.username, u.email, u.status AS login_status
                FROM {$this->table} e
                LEFT JOIN phphr_users u ON u.id = e.user_id
                ORDER BY e.created_at DESC";
        $result = $this->conn->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function find($id)
    {
        $stmt = $this->conn->prepare(
            "SELECT e.*, u.username, u.email, u.status AS login_status
             FROM {$this->table} e
             LEFT JOIN phphr_users u ON u.id = e.user_id
             WHERE e.id = ?"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function usernameExists(string $username, int $ignoreUserId = 0): bool
    {
        $sql = "SELECT id FROM phphr_users WHERE username = ?";
        if ($ignoreUserId > 0) {
            $sql .= " AND id <> ?";
        }
        $sql .= " LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        if ($ignoreUserId > 0) {
            $stmt->bind_param('si', $username, $ignoreUserId);
        } else {
            $stmt->bind_param('s', $username);
        }
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return !empty($row);
    }

    public function emailExists(string $email, int $ignoreUserId = 0): bool
    {
        $sql = "SELECT id FROM phphr_users WHERE email = ?";
        if ($ignoreUserId > 0) {
            $sql .= " AND id <> ?";
        }
        $sql .= " LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        if ($ignoreUserId > 0) {
            $stmt->bind_param('si', $email, $ignoreUserId);
        } else {
            $stmt->bind_param('s', $email);
        }
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return !empty($row);
    }

    public function create(array $data): bool
    {
        $employeeCode = $data['employee_code'] ?: $this->generateEmployeeCode();
        $sql = "INSERT INTO {$this->table}
            (user_id, employee_code, first_name, last_name, phone,
             department, designation, date_of_joining, salary, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            'isssssssdi',
            $data['user_id'],
            $employeeCode,
            $data['first_name'],
            $data['last_name'],
            $data['phone'],
            $data['department'],
            $data['designation'],
            $data['date_of_joining'],
            $data['salary'],
            $data['status']
        );

        $ok = $stmt->execute();
        $this->lastError = $ok ? '' : $stmt->error;
        $stmt->close();
        return $ok;
    }

    public function createWithUser(array $data, array $account): bool
    {
        $this->lastError = '';
        $this->conn->begin_transaction();

        try {
            $userId = $this->insertUserAccount($account, $data);
            $data['user_id'] = $userId;
            if (empty($data['employee_code'])) {
                $data['employee_code'] = $this->generateEmployeeCode();
            }

            if (!$this->create($data)) {
                throw new RuntimeException($this->lastError ?: 'Unable to create employee.');
            }

            $this->assignEmployeeRole($userId);
            $this->conn->commit();
            return true;
        } catch (Throwable $e) {
            $this->conn->rollback();
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function update($id, array $data): bool
    {
        $sql = "UPDATE {$this->table}
                SET first_name=?, last_name=?, phone=?, department=?,
                    designation=?, date_of_joining=?, salary=?, status=?
                WHERE id=?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            'ssssssdii',
            $data['first_name'],
            $data['last_name'],
            $data['phone'],
            $data['department'],
            $data['designation'],
            $data['date_of_joining'],
            $data['salary'],
            $data['status'],
            $id
        );

        $ok = $stmt->execute();
        $this->lastError = $ok ? '' : $stmt->error;
        $stmt->close();
        return $ok;
    }

    public function updateWithUser(int $id, array $data, array $account): bool
    {
        $this->lastError = '';
        $existing = $this->find($id);
        if (!$existing) {
            $this->lastError = 'Employee not found.';
            return false;
        }

        $this->conn->begin_transaction();

        try {
            $userId = (int)($existing['user_id'] ?? 0);
            if ($userId > 0) {
                $this->updateUserAccount($userId, $account, $data);
            } else {
                if (empty($account['password_hash'])) {
                    throw new RuntimeException('Password is required when creating a login for this employee.');
                }
                $userId = $this->insertUserAccount($account, $data);
                $linkStmt = $this->conn->prepare("UPDATE {$this->table} SET user_id = ? WHERE id = ?");
                $linkStmt->bind_param('ii', $userId, $id);
                if (!$linkStmt->execute()) {
                    $error = $linkStmt->error;
                    $linkStmt->close();
                    throw new RuntimeException($error ?: 'Unable to link employee login account.');
                }
                $linkStmt->close();
                $this->assignEmployeeRole($userId);
            }

            if (!$this->update($id, $data)) {
                throw new RuntimeException($this->lastError ?: 'Unable to update employee.');
            }

            $this->conn->commit();
            return true;
        } catch (Throwable $e) {
            $this->conn->rollback();
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function allActive(): array
    {
        $sql = "SELECT id, employee_code, first_name, last_name
                FROM phphr_employees
                WHERE status = 1
                ORDER BY first_name ASC";

        $result = $this->conn->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function delete($id): bool
    {
        $this->lastError = '';
        $existing = $this->find($id);
        if (!$existing) {
            $this->lastError = 'Employee not found.';
            return false;
        }

        $this->conn->begin_transaction();

        try {
            $stmt = $this->conn->prepare(
                "DELETE FROM {$this->table} WHERE id = ?"
            );
            $stmt->bind_param('i', $id);
            if (!$stmt->execute()) {
                $error = $stmt->error;
                $stmt->close();
                throw new RuntimeException($error ?: 'Unable to delete employee.');
            }
            $stmt->close();

            $userId = (int)($existing['user_id'] ?? 0);
            if ($userId > 0) {
                $userStmt = $this->conn->prepare(
                    "UPDATE phphr_users SET status = 0 WHERE id = ?"
                );
                $userStmt->bind_param('i', $userId);
                if (!$userStmt->execute()) {
                    $error = $userStmt->error;
                    $userStmt->close();
                    throw new RuntimeException($error ?: 'Unable to disable employee login account.');
                }
                $userStmt->close();
            }

            $this->conn->commit();
            return true;
        } catch (Throwable $e) {
            $this->conn->rollback();
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    protected function generateEmployeeCode(): string
    {
        do {
            $code = 'EMP-' . random_int(1000, 9999);
            $stmt = $this->conn->prepare("SELECT id FROM {$this->table} WHERE employee_code = ? LIMIT 1");
            $stmt->bind_param('s', $code);
            $stmt->execute();
            $exists = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        } while ($exists);

        return $code;
    }

    protected function insertUserAccount(array $account, array $employee): int
    {
        $fullName = trim($employee['first_name'] . ' ' . $employee['last_name']);
        $stmt = $this->conn->prepare(
            "INSERT INTO phphr_users (username, email, password_hash, full_name, status)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            'ssssi',
            $account['username'],
            $account['email'],
            $account['password_hash'],
            $fullName,
            $employee['status']
        );
        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            throw new RuntimeException($error ?: 'Unable to create login account.');
        }
        $userId = (int)$this->conn->insert_id;
        $stmt->close();

        return $userId;
    }

    protected function updateUserAccount(int $userId, array $account, array $employee): void
    {
        $fullName = trim($employee['first_name'] . ' ' . $employee['last_name']);

        if (!empty($account['password_hash'])) {
            $stmt = $this->conn->prepare(
                "UPDATE phphr_users
                 SET username = ?, email = ?, full_name = ?, status = ?, password_hash = ?
                 WHERE id = ?"
            );
            $stmt->bind_param(
                'sssisi',
                $account['username'],
                $account['email'],
                $fullName,
                $employee['status'],
                $account['password_hash'],
                $userId
            );
        } else {
            $stmt = $this->conn->prepare(
                "UPDATE phphr_users
                 SET username = ?, email = ?, full_name = ?, status = ?
                 WHERE id = ?"
            );
            $stmt->bind_param(
                'sssii',
                $account['username'],
                $account['email'],
                $fullName,
                $employee['status'],
                $userId
            );
        }

        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            throw new RuntimeException($error ?: 'Unable to update login account.');
        }
        $stmt->close();
    }

    protected function assignEmployeeRole(int $userId): void
    {
        $roleStmt = $this->conn->prepare(
            "SELECT id FROM phphr_roles WHERE role_key = 'employee' LIMIT 1"
        );
        $roleStmt->execute();
        $roleRow = $roleStmt->get_result()->fetch_assoc();
        $roleStmt->close();

        $roleId = (int)($roleRow['id'] ?? 0);
        if ($roleId <= 0) {
            throw new RuntimeException('Employee role is missing in the database.');
        }

        $assignStmt = $this->conn->prepare(
            "INSERT IGNORE INTO phphr_user_roles (user_id, role_id) VALUES (?, ?)"
        );
        $assignStmt->bind_param('ii', $userId, $roleId);
        if (!$assignStmt->execute()) {
            $error = $assignStmt->error;
            $assignStmt->close();
            throw new RuntimeException($error ?: 'Unable to assign employee role.');
        }
        $assignStmt->close();
    }
}
