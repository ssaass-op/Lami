<?php
header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../includes/functions.php';
if (!is_logged_in() || ($_SESSION['user_status'] ?? '') !== 'verified') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized action.']);
    exit();
}
$current_user = $_SESSION['user_id'];
$chat_with = isset($_GET['chat_with']) ? intval($_GET['chat_with']) : 0;
$last_id = isset($_GET['last_id']) ? intval($_GET['last_id']) : 0;
if ($chat_with <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Recipient ID is required.']);
    exit();
}
if (!is_connected($current_user, $chat_with)) {
    echo json_encode(['status' => 'error', 'message' => 'You can only message accepted connections.']);
    exit();
}
try {
    $db = DB::getConnection();
    $stmt = $db->prepare("
        UPDATE messages SET is_read = 1 
        WHERE sender_id = :chat_with AND receiver_id = :my_id AND is_read = 0
    ");
    $stmt->execute(['chat_with' => $chat_with, 'my_id' => $current_user]);
    $stmt = $db->prepare("
        SELECT id, sender_id, receiver_id, message, created_at 
        FROM messages 
        WHERE ((sender_id = :my_id1 AND receiver_id = :chat_with1) 
           OR (sender_id = :chat_with2 AND receiver_id = :my_id2))
          AND id > :last_id 
        ORDER BY id ASC
    ");
    $stmt->execute([
        'my_id1' => $current_user,
        'chat_with1' => $chat_with,
        'chat_with2' => $chat_with,
        'my_id2' => $current_user,
        'last_id' => $last_id
    ]);
    $messages = $stmt->fetchAll();
    echo json_encode([
        'status' => 'success',
        'messages' => $messages
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
