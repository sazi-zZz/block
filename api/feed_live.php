<?php
require_once '../config/db.php';
require_once '../includes/functions.php';
require_once '../models/Block.php';
require_once '../models/Post.php';

if (!isLoggedIn()) {
    exit;
}

$postModel = new Post($pdo);
$since_id = isset($_GET['since_id']) ? (int)$_GET['since_id'] : 0;
$block_id = isset($_GET['block_id']) ? (int)$_GET['block_id'] : null;
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;

if ($since_id <= 0) {
    exit;
}

$feed = [];

if ($user_id) {
    $stmt = $pdo->prepare("
        SELECT posts.*, users.username, users.avatar, blocks.name as block_name,
               posts.ai_percentage,
               (SELECT COUNT(*) FROM comments WHERE post_id = posts.id) as comment_count,
               (SELECT COUNT(*) FROM likes WHERE post_id = posts.id) as like_count,
               rp.title as repost_title, rp.content as repost_content, rp.image as repost_image, rpu.username as repost_username, rpu.avatar as repost_avatar, rpb.name as repost_block_name, rp.id as rp_id, rpu.id as rp_user_id, rpb.id as rp_block_id
        FROM posts
        JOIN users ON posts.user_id = users.id
        JOIN blocks ON posts.block_id = blocks.id
        LEFT JOIN posts rp ON posts.repost_id = rp.id
        LEFT JOIN users rpu ON rp.user_id = rpu.id
        LEFT JOIN blocks rpb ON rp.block_id = rpb.id
        WHERE posts.user_id = :profile_id
        AND posts.id > :since_id
        AND (
            posts.privacy = 'public'
            OR posts.user_id = :viewer
            OR (posts.privacy = 'followers' AND EXISTS (SELECT 1 FROM followers WHERE follower_id = :viewer AND following_id = posts.user_id))
            OR (posts.privacy = 'following' AND EXISTS (SELECT 1 FROM followers WHERE follower_id = posts.user_id AND following_id = :viewer))
            OR (posts.privacy = 'followers_and_following' AND (
                EXISTS (SELECT 1 FROM followers WHERE follower_id = :viewer AND following_id = posts.user_id)
                OR EXISTS (SELECT 1 FROM followers WHERE follower_id = posts.user_id AND following_id = :viewer)
            ))
        )
        ORDER BY posts.created_at DESC
    ");
    $stmt->execute(['profile_id' => $user_id, 'viewer' => $_SESSION['user_id'], 'since_id' => $since_id]);
    $feed = $stmt->fetchAll();
}
else {
    // Fetch newest posts passing since_id and block_id (if any)
    $feed = $postModel->getFeed($_SESSION['user_id'], $block_id, 'newest', null, null, $since_id);
}

foreach ($feed as $post) {
    include '../views/posts/_post_card_feed.php';
}
?>