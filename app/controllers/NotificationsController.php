<?php

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Authz.php';
require_once __DIR__ . '/../models/Notification.php';

class NotificationsController extends Controller
{
    protected Notification $notification;

    public function __construct(mysqli $conn)
    {
        parent::__construct($conn);
        require_roles($conn, ['admin', 'hr', 'finance', 'employee']);
        $this->notification = new Notification($conn);
    }

    public function index(): void
    {
        $userId = (int)$_SESSION['user_id'];
        $records = $this->notification->listForUser($userId);
        $this->view('notifications/index', compact('records'));
    }

    public function readAll(): void
    {
        $userId = (int)$_SESSION['user_id'];
        $this->notification->markAllRead($userId);
        $this->redirect('notifications.php?a=index');
    }

    public function open(): void
    {
        $userId = (int)$_SESSION['user_id'];
        $id = (int)($_GET['id'] ?? 0);
        $link = trim($_GET['link'] ?? '');

        if ($id > 0) {
            $this->notification->markRead($id, $userId);
        }

        $this->redirect($link !== '' ? $link : 'notifications.php?a=index');
    }
}
