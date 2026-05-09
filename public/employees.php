<?php

session_start();

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/controllers/EmployeesController.php';

$action = $_GET['a'] ?? 'index';

$controller = new EmployeesController($conn);

if (!method_exists($controller, $action)) {
    $action = 'index';
}

$controller->$action();

