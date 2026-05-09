<?php

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Authz.php';
require_once __DIR__ . '/../models/Message.php';
require_once __DIR__ . '/../models/Notification.php';

class MessagesController extends Controller
{
    protected Message $message;
    protected Notification $notification;

    public function __construct(mysqli $conn)
    {
        parent::__construct($conn);
        require_roles($conn, ['admin', 'hr', 'finance', 'employee']);
        $this->message = new Message($conn);
        $this->notification = new Notification($conn);
    }

    public function index(): void
    {
        $role = current_user_role($this->conn);
        $employeeId = $role === 'employee' ? current_employee_id($this->conn) : null;

        $records = $this->message->listAll($employeeId);
        $successMsg = $_SESSION['msg_success'] ?? '';
        $errorMsg = $_SESSION['msg_error'] ?? '';
        unset($_SESSION['msg_success'], $_SESSION['msg_error']);

        $this->view('messages/index', compact('records', 'successMsg', 'errorMsg', 'role'));
    }

    public function create(): void
    {
        $role = current_user_role($this->conn);
        $employeeId = $role === 'employee' ? current_employee_id($this->conn) : null;

        $employees = $this->message->employees();
        $users = $this->message->users();
        $this->view('messages/create', compact('employees', 'users', 'role', 'employeeId'));
    }

    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('messages.php?a=create');
        }

        if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
            $_SESSION['msg_error'] = 'Security token expired. Please try again.';
            $this->redirect('messages.php?a=create');
        }

        $role = current_user_role($this->conn);
        $employeeId = (int)($_POST['employee_id'] ?? 0);
        if ($role === 'employee') {
            $employeeId = (int)(current_employee_id($this->conn) ?? 0);
        }

        $data = [
            'employee_id' => $employeeId,
            'sender_user_id' => (int)$_SESSION['user_id'],
            'receiver_user_id' => (int)($_POST['receiver_user_id'] ?? 0) ?: null,
            'context_type' => $_POST['context_type'] ?? 'general',
            'context_id' => (int)($_POST['context_id'] ?? 0) ?: null,
            'subject' => trim($_POST['subject'] ?? ''),
            'message' => trim($_POST['message'] ?? '')
        ];

        if ($data['employee_id'] <= 0 || $data['subject'] === '' || $data['message'] === '') {
            $_SESSION['msg_error'] = 'Employee, subject and message are required.';
            $this->redirect('messages.php?a=create');
        }

        if ($this->message->create($data)) {
            $_SESSION['msg_success'] = 'Message sent successfully.';
            if (!empty($data['receiver_user_id'])) {
                $this->notification->create(
                    (int)$data['receiver_user_id'],
                    'New Message',
                    'You received a new communication: ' . $data['subject'],
                    'messages.php'
                );
            } else {
                $this->notifyBroadcastRecipients(
                    (int)$_SESSION['user_id'],
                    'New Broadcast Message',
                    'A new general communication was posted: ' . $data['subject'],
                    'messages.php'
                );
            }
        } else {
            $_SESSION['msg_error'] = 'Error while sending message.';
        }

        $this->redirect('messages.php?a=index');
    }

    protected function notifyBroadcastRecipients(int $senderUserId, string $title, string $body, string $link = ''): void
    {
        $result = $this->conn->query(
            "SELECT id FROM phphr_users WHERE status = 1 AND id <> " . $senderUserId
        );
        if (!$result) {
            return;
        }

        while ($row = $result->fetch_assoc()) {
            $this->notification->create((int)$row['id'], $title, $body, $link);
        }
    }
}
