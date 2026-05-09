<?php
// app/includes/auth_check.php - ensure authenticated role session
require_once __DIR__ . '/../core/Authz.php';
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
