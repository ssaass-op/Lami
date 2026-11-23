<?php
header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../includes/functions.php';
if (!is_logged_in() || ($_SESSION['user_status'] ?? '') !== 'verified') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized action.']);
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method. Only POST is allowed.']);
    exit();
}
$current_user = $_SESSION['user_id'];
$receiver_id = isset($_POST['receiver_id']) ? intval($_POST['receiver_id']) : 0;
$message = trim($_POST['message'] ?? '');
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    echo json_encode(['status' => 'error', 'message' => 'Security check failure. Please reload the chat.']);
    exit();
}
if ($receiver_id <= 0 || empty($message)) {
    echo json_encode(['status' => 'error', 'message' => 'Message and recipient are required.']);
    exit();
}
if (!is_connected($current_user, $receiver_id)) {
    echo json_encode(['status' => 'error', 'message' => 'You can only message accepted connections.']);
    exit();
}
try {
    $db = DB::getConnection();
    $stmt = $db->prepare("
        INSERT INTO messages (sender_id, receiver_id, message, is_read) 
        VALUES (:sender, :receiver, :message, 0)
    ");
    $stmt->execute([
        'sender' => $current_user,
        'receiver' => $receiver_id,
        'message' => $message
    ]);
    echo json_encode([
        'status' => 'success',
        'message_id' => $db->lastInsertId(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
