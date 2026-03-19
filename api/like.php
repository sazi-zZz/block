<?php
require_once '../config/db.php';
require_once '../includes/functions.php';
require_once '../models/Post.php';
require_once '../models/Notification.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_id = $_POST['post_id'] ?? null;
    if (!$post_id) {
        echo json_encode(['error' => 'missing post_id']);
        exit;
    }

    $postModel = new Post($pdo);
    $isLiked = $postModel->isLiked($post_id, $_SESSION['user_id']);

    if ($isLiked) {
        $postModel->unlike($post_id, $_SESSION['user_id']);
        $new_status = false;
    }
    else {
        $postModel->like($post_id, $_SESSION['user_id']);
        $new_status = true;

        $post = $postModel->getById($post_id);
        if ($post && $post['user_id'] != $_SESSION['user_id']) {
            $notificationModel = new Notification($pdo);
            $username = $_SESSION['username'] ?? 'Someone';
            $notificationModel->create(
                $post['user_id'],
                'like',
                $post_id,
                $username . ' liked your post.'
            );
        }
    }

    // Get new count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ?");
    $stmt->execute([$post_id]);
    $count = $stmt->fetchColumn();

    echo json_encode(['success' => true, 'is_liked' => $new_status, 'count' => $count]);
}