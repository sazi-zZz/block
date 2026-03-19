<?php
require_once '../config/db.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$post_ids_str = $_GET['post_ids'] ?? '';
if (!$post_ids_str) {
    echo json_encode(['success' => true, 'posts' => []]);
    exit;
}

$post_ids = array_filter(array_map('intval', explode(',', $post_ids_str)));
if (empty($post_ids)) {
    echo json_encode(['success' => true, 'posts' => []]);
    exit;
}

// Generate placeholders
$placeholders = implode(',', array_fill(0, count($post_ids), '?'));

// Check for valid posts and fetch current likes/comments count, content and properties
$stmt = $pdo->prepare("
    SELECT id, title, content, image,
           (SELECT COUNT(*) FROM comments WHERE post_id = posts.id) as comment_count,
           (SELECT COUNT(*) FROM likes WHERE post_id = posts.id) as like_count,
           (SELECT 1 FROM likes WHERE post_id = posts.id AND user_id = ?) as is_liked
    FROM posts 
    WHERE id IN ($placeholders)
");

$params = array_merge([$_SESSION['user_id']], $post_ids);
$stmt->execute($params);
$posts = $stmt->fetchAll();

$response = [];
foreach ($posts as $p) {
    $response[$p['id']] = [
        'title' => $p['title'],
        'content' => nl2br(htmlspecialchars(substr($p['content'], 0, 180))) . (strlen($p['content']) > 180 ? '...' : ''),
        'full_content' => nl2br(htmlspecialchars($p['content'])), // For full post view
        'image' => $p['image'],
        'comment_count' => $p['comment_count'],
        'like_count' => $p['like_count'],
        'is_liked' => (bool)$p['is_liked']
    ];
}

header('Content-Type: application/json');
echo json_encode(['success' => true, 'posts' => $response]);