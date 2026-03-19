<?php
require_once '../config/db.php';
require_once '../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$last_notif_id = isset($_GET['last_notif_id']) ? (int)$_GET['last_notif_id'] : 0;
$last_msg_id = isset($_GET['last_msg_id']) ? (int)$_GET['last_msg_id'] : 0;

$response = [
    'has_new_notification' => false,
    'has_new_message' => false,
    'unread_notifications' => 0,
    'unread_messages' => 0,
    'max_notif_id' => $last_notif_id,
    'max_msg_id' => $last_msg_id,
    'new_notifications' => [],
    'new_messages' => []
];

// Unread counts
$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$user_id]);
$response['unread_notifications'] = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
$stmt->execute([$user_id]);
$response['unread_messages'] = $stmt->fetchColumn();


// Get max IDs
$stmt = $pdo->prepare("SELECT MAX(id) FROM notifications WHERE user_id = ?");
$stmt->execute([$user_id]);
$max_notif_id = $stmt->fetchColumn() ?: 0;
$response['max_notif_id'] = $max_notif_id;

$stmt = $pdo->prepare("SELECT MAX(id) FROM messages WHERE receiver_id = ?");
$stmt->execute([$user_id]);
$max_msg_id = $stmt->fetchColumn() ?: 0;
$response['max_msg_id'] = $max_msg_id;


// Get new items if we aren't initialising (last_id > 0)
if ($last_notif_id > 0 && $max_notif_id > $last_notif_id) {
    $stmt = $pdo->prepare("SELECT type, message as content, source_id FROM notifications WHERE user_id = ? AND id > ? ORDER BY id ASC");
    $stmt->execute([$user_id, $last_notif_id]);
    $new_notifs = $stmt->fetchAll();
    if (!empty($new_notifs)) {
        $response['has_new_notification'] = true;
        $response['new_notifications'] = $new_notifs;
    }
}

if ($last_msg_id > 0 && $max_msg_id > $last_msg_id) {
    $stmt = $pdo->prepare("
        SELECT m.content, u.username as sender_name, m.sender_id 
        FROM messages m 
        JOIN users u ON m.sender_id = u.id 
        WHERE m.receiver_id = ? AND m.id > ? 
        ORDER BY m.id ASC
    ");
    $stmt->execute([$user_id, $last_msg_id]);
    $new_msgs = $stmt->fetchAll();
    if (!empty($new_msgs)) {
        $response['has_new_message'] = true;
        $response['new_messages'] = $new_msgs;
    }
}

echo json_encode($response);