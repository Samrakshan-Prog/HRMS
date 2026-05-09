<?php

session_start();

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';

$action = $_GET['a'] ?? 'login';

$controller = new AuthController($conn);

if (!method_exists($controller, $action)) {
    $action = 'login';
}

$controller->$action();
