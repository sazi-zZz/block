<?php
require_once '../../config/db.php';
require_once '../../includes/functions.php';
require_once '../../models/Post.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $comment_id = $_POST['comment_id'] ?? null;
    $post_id = $_POST['post_id'] ?? null;

    if ($comment_id && $post_id) {
        $postModel = new Post($pdo);
        $comment = $postModel->getCommentById($comment_id);

        // Only author of the comment or post can delete it (we'll just check comment author for now)
        if ($comment && $comment['user_id'] == $_SESSION['user_id']) {
            if ($postModel->deleteComment($comment_id)) {
                $_SESSION['success_msg'] = "Comment deleted successfully.";
            } else {
                $_SESSION['error_msg'] = "Failed to delete comment.";
            }
        } else {
            $_SESSION['error_msg'] = "Unauthorized access.";
        }
    }
    
    redirect(BASE_URL . 'views/posts/view.php?id=' . $post_id . '#comments-section');
} else {
    redirect(BASE_URL . 'index.php');
}
?>
