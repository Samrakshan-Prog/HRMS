<?php
session_start();

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/controllers/ReportsController.php';

$action = $_GET['a'] ?? 'index';
$controller = new ReportsController($conn);

if (!method_exists($controller, $action)) {
    $action = 'index';
}

$controller->$action();
