<?php
session_start();

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/controllers/RepaymentsController.php';

$action = $_GET['a'] ?? 'index';
$controller = new RepaymentsController($conn);

if (!method_exists($controller, $action)) {
    $action = 'index';
}

$controller->$action();
