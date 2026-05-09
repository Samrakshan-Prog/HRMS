<?php

class Notification
{
    protected mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function listForUser(int $userId): array
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM phphr_notifications WHERE user_id = ? ORDER BY created_at DESC"
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function unreadCount(int $userId): int
    {
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) AS c FROM phphr_notifications WHERE user_id = ? AND is_read = 0"
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (int)($row['c'] ?? 0);
    }

    public function create(int $userId, string $title, string $body, string $link = ''): bool
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO phphr_notifications (user_id, title, body, link) VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param('isss', $userId, $title, $body, $link);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function markAllRead(int $userId): bool
    {
        $stmt = $this->conn->prepare("UPDATE phphr_notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->bind_param('i', $userId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function markRead(int $id, int $userId): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE phphr_notifications SET is_read = 1 WHERE id = ? AND user_id = ?"
        );
        $stmt->bind_param('ii', $id, $userId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}
