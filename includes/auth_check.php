<?php
require_once __DIR__ . '/../config/db.php';   
require_once __DIR__ . '/functions.php';
if (!is_logged_in()) {
    header("Location: " . BASE_URL . "login.php");
    exit();
}
$db = DB::getConnection();
$stmt = $db->prepare("SELECT status, role FROM users WHERE id = :id");
$stmt->execute(['id' => $_SESSION['user_id']]);
$user_db = $stmt->fetch();
if (!$user_db) {
    session_destroy();
    header("Location: " . BASE_URL . "login.php");
    exit();
}
$_SESSION['user_status'] = $user_db['status'];
$_SESSION['user_role']   = $user_db['role'];
$current_page = basename($_SERVER['PHP_SELF']);
$allowed_pending = ['pending-status.php', 'logout.php'];
if ($_SESSION['user_status'] !== 'verified' && !in_array($current_page, $allowed_pending)) {
    header("Location: " . BASE_URL . "pending-status.php");
    exit();
}
