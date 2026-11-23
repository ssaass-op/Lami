<?php
require_once '../includes/auth_check.php';
require_once '../config/db.php';
require_once '../includes/functions.php';
if (!has_role(['admin', 'moderator'])) {
    die("Access Denied: Administrative privileges required.");
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "admin/dashboard.php");
    exit();
}
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    die("Security check failure. Please reload the dashboard.");
}
$admin_id = $_SESSION['user_id'];
$target_id = isset($_POST['target_id']) ? intval($_POST['target_id']) : 0;
$action = $_POST['action'] ?? '';
if ($target_id <= 0 || empty($action) || $target_id === $admin_id) {
    header("Location: " . BASE_URL . "admin/dashboard.php");
    exit();
}
$db = DB::getConnection();
try {
    $db->beginTransaction();
    $log_action = "";
    $update_users = false;
    $update_fields = [];
    if ($action === 'approve') {
        $update_users = true;
        $update_fields = ['status' => 'verified'];
        $log_action = "Approved identity verification & set status to Verified";
    } elseif ($action === 'suspend') {
        $update_users = true;
        $update_fields = ['status' => 'suspended'];
        $log_action = "Suspended user account";
    } elseif ($action === 'make_moderator') {
        if (!has_role('admin')) {
            throw new Exception("Unauthorized: Only administrators can adjust user roles.");
        }
        $update_users = true;
        $update_fields = ['role' => 'moderator'];
        $log_action = "Promoted user role to Moderator";
    } elseif ($action === 'make_standard') {
        if (!has_role('admin')) {
            throw new Exception("Unauthorized: Only administrators can adjust user roles.");
        }
        $update_users = true;
        $update_fields = ['role' => 'standard'];
        $log_action = "Demoted user role to Standard";
    }
    if ($update_users && !empty($update_fields)) {
        $fields_sql = [];
        foreach ($update_fields as $col => $val) {
            $fields_sql[] = "`$col` = :$col";
        }
        $sql = "UPDATE users SET " . implode(", ", $fields_sql) . " WHERE id = :id";
        $stmt = $db->prepare($sql);
        $bind_params = array_merge($update_fields, ['id' => $target_id]);
        $stmt->execute($bind_params);
        $stmt = $db->prepare("
            INSERT INTO audit_logs (admin_id, action, target_user_id) 
            VALUES (:admin_id, :action, :target_id)
        ");
        $stmt->execute([
            'admin_id' => $admin_id,
            'action' => $log_action,
            'target_id' => $target_id
        ]);
        $db->commit();
    } else {
        $db->rollBack();
    }
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    die("Verification Action Failed: " . htmlspecialchars($e->getMessage()));
}
header("Location: " . BASE_URL . "admin/dashboard.php");
exit();
