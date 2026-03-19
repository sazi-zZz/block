<?php
require_once '../config/db.php';
require_once '../includes/functions.php';
require_once '../models/Block.php';
require_once '../models/Post.php';

if (!isLoggedIn()) {
    header('HTTP/1.1 401 Unauthorized');
    exit;
}

$postModel = new Post($pdo);

$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 10;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$sort = $_GET['sort'] ?? 'random';
$date_from = !empty($_GET['date_from']) ? $_GET['date_from'] : null;
$date_to = !empty($_GET['date_to']) ? $_GET['date_to'] : null;
$block_id = isset($_GET['block_id']) ? (int)$_GET['block_id'] : null;
$seed = $_SESSION['feed_random_seed'] ?? null;
$joined_blocks = !empty($_GET['joined_blocks']) && $_GET['joined_blocks'] === '1';

$feed = $postModel->getFeed(
    $_SESSION['user_id'],
    $block_id,
    $sort,
    $date_from,
    $date_to,
    null,
    $limit,
    $offset,
    $seed,
    $joined_blocks
);

foreach ($feed as $post) {
    include __DIR__ . '/../views/posts/_post_card_feed.php';
}