<?php

function current_user_role(mysqli $conn): string
{
    if (!empty($_SESSION['user_role'])) {
        return (string)$_SESSION['user_role'];
    }

    $userId = (int)($_SESSION['user_id'] ?? 0);
    if ($userId <= 0) {
        return 'guest';
    }

    $sql = "SELECT r.role_key
            FROM phphr_user_roles ur
            INNER JOIN phphr_roles r ON r.id = ur.role_id
            WHERE ur.user_id = ?
            ORDER BY ur.id ASC
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return 'employee';
    }

    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $role = $row['role_key'] ?? 'employee';
    $_SESSION['user_role'] = $role;

    return $role;
}

function user_can(string $role, array $allowed): bool
{
    return in_array($role, $allowed, true);
}

function require_roles(mysqli $conn, array $allowed): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }

    $role = current_user_role($conn);
    if (!user_can($role, $allowed)) {
        http_response_code(403);
        echo 'Forbidden: insufficient role permission.';
        exit;
    }
}

function current_employee_id(mysqli $conn): ?int
{
    $userId = (int)($_SESSION['user_id'] ?? 0);
    if ($userId <= 0) {
        return null;
    }

    $stmt = $conn->prepare('SELECT id FROM phphr_employees WHERE user_id = ? LIMIT 1');
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return isset($row['id']) ? (int)$row['id'] : null;
}
