<?php

require_once __DIR__ . '/Security.php';

class Controller
{
    protected $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    protected function view(string $path, array $data = [])
    {
        extract($data);
        require __DIR__ . '/../views/' . $path . '.php';
    }

    protected function redirect(string $url)
    {
        header("Location: {$url}");
        exit;
    }
}
