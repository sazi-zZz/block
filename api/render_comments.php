<?php
require_once '../config/db.php';
require_once '../includes/functions.php';
require_once '../models/Post.php';

if (!isLoggedIn()) {
    exit;
}

$post_id = $_GET['post_id'] ?? 0;
if (!$post_id) {
    exit;
}

$postModel = new Post($pdo);
$comments = $postModel->getComments($post_id);

$main_comments = [];
$replies = [];
foreach ($comments as $c) {
    if ($c['parent_id']) {
        $replies[$c['parent_id']][] = $c;
    }
    else {
        $main_comments[] = $c;
    }
}

foreach ($main_comments as $comment): ?>
<div class="card mb-3 border-0 bg-secondary" style="border: 1px solid var(--border-color);">
    <div class="flex items-center mb-2">
        <img src="public/images/avatars/<?= htmlspecialchars($comment['avatar'] ?: 'user.jpg')?>"
            class="avatar avatar-sm" style="margin-right: 0.5rem;"
            onerror="this.src='public/images/avatars/user.jpg'; this.onerror=null;">
        <div style="flex:1;">
            <strong>
                <?= htmlspecialchars($comment['username'])?>
            </strong>
            <small class="text-muted ml-2">
                <?= getDisplayTime($comment['created_at'])?>
            </small>
        </div>
    </div>
    <div class="pl-2">
        <?php if (!empty($comment['content'])): ?>
        <?= nl2br(htmlspecialchars($comment['content']))?>
        <?php
    endif; ?>

        <?php if (!empty($comment['ai_percentage']) && (int)$comment['ai_percentage'] > 0): ?>
        <div class="mt-1">
            <span
                style="font-size: 0.7rem; color: var(--gray-500); display: inline-flex; align-items: center; background: var(--bg-tertiary); padding: 0.1rem 0.4rem; border-radius: 8px; border: 1px solid var(--border-color);">
                <i class="fa-solid fa-robot" style="margin-right: 0.25rem; font-size: 0.65rem;"></i>
                <?= $comment['ai_percentage']?>% AI Content
            </span>
        </div>
        <?php
    endif; ?>

        <?php if (!empty($comment['media'])): ?>
        <div class="mt-2">
            <img src="public/images/comment_media/<?= htmlspecialchars($comment['media'])?>"
                style="max-height: 200px; border-radius: 8px; border: 1px solid var(--border-color);">
        </div>
        <?php
    endif; ?>
    </div>

    <div class="flex gap-3 items-center mt-2 pl-2">
        <button class="text-sm text-primary" style="background:none; border:none; cursor:pointer; padding:0;"
            onclick="toggleReplyForm(<?= $comment['id']?>, '<?= htmlspecialchars($comment['username'])?>')">Reply</button>
        <?php if ($comment['user_id'] == $_SESSION['user_id']): ?>
        <a href="views/posts/edit_comment.php?id=<?= $comment['id']?>" class="text-sm text-muted">Edit</a>
        <?php
    endif; ?>
    </div>

    <form method="POST" id="reply-form-<?= $comment['id']?>" class="mt-3 pl-2" style="display:none;"
        enctype="multipart/form-data">
        <input type="hidden" name="action" value="reply">
        <input type="hidden" name="parent_id" value="<?= $comment['id']?>">
        <div class="flex gap-2">
            <input type="text" name="content" class="js-char-limit" data-limit="1000" placeholder="Write a reply..."
                style="flex: 1;">
            <button type="button" class="btn btn-secondary"
                onclick="document.getElementById('reply-image-<?= $comment['id']?>').click()"
                style="padding: 0.5rem;"><i class="fa-regular fa-image"></i></button>
            <button type="submit" class="btn btn-primary text-sm">Reply</button>
        </div>
        <input type="file" id="reply-image-<?= $comment['id']?>" name="image"
            accept="image/png, image/jpeg, image/gif, image/webp" style="display:none;"
            onchange="previewCommentImage(this, 'reply-image-preview-<?= $comment['id']?>')">
        <small class="text-muted" style="display: block; margin-top: 0.25rem; font-size: 0.75rem;">Maximum file size:
            2MB. Supported formats: JPEG, PNG, GIF, WebP.</small>
        <div id="reply-image-preview-<?= $comment['id']?>" class="mt-2 text-sm text-primary" style="display:none;">
        </div>
    </form>

    <?php if (isset($replies[$comment['id']])): ?>
    <div class="replies-list ml-4 mt-3 pl-3" style="border-left: 2px solid var(--border-color);">
        <?php foreach ($replies[$comment['id']] as $reply): ?>
        <div class="mb-3">
            <div class="flex items-center mb-1">
                <img src="public/images/avatars/<?= htmlspecialchars($reply['avatar'] ?: 'user.jpg')?>"
                    class="avatar avatar-sm" style="margin-right: 0.5rem; width: 20px; height: 20px;"
                    onerror="this.src='public/images/avatars/user.jpg'; this.onerror=null;">
                <div>
                    <span style="font-size: 0.9rem; font-weight: 600;">
                        <?= htmlspecialchars($reply['username'])?>
                    </span>
                    <small class="text-muted ml-2">
                        <?= getDisplayTime($reply['created_at'])?>
                    </small>
                </div>
            </div>
            <div class="reply-content pl-2" style="font-size: 0.9rem;">
                <?php if (!empty($reply['content'])): ?>
                <?= nl2br(htmlspecialchars($reply['content']))?>
                <?php
            endif; ?>
                <?php if (!empty($reply['ai_percentage']) && (int)$reply['ai_percentage'] > 0): ?>
                <div class="mt-1">
                    <span
                        style="font-size: 0.7rem; color: var(--gray-500); display: inline-flex; align-items: center; background: var(--bg-tertiary); padding: 0.1rem 0.4rem; border-radius: 8px; border: 1px solid var(--border-color);">
                        <i class="fa-solid fa-robot" style="margin-right: 0.25rem; font-size: 0.65rem;"></i>
                        <?= $reply['ai_percentage']?>% AI Content
                    </span>
                </div>
                <?php
            endif; ?>
                <?php if (!empty($reply['media'])): ?>
                <div class="mt-2">
                    <img src="public/images/comment_media/<?= htmlspecialchars($reply['media'])?>"
                        style="max-height: 150px; border-radius: 8px; border: 1px solid var(--border-color);">
                </div>
                <?php
            endif; ?>
            </div>
            <div class="flex gap-3 items-center mt-1">
                <button class="text-xs text-primary" style="background:none; border:none; cursor:pointer; padding:0;"
                    onclick="toggleReplyForm(<?= $comment['id']?>, '<?= htmlspecialchars($reply['username'])?>')">Reply</button>
                <?php if ($reply['user_id'] == $_SESSION['user_id']): ?>
                <a href="views/posts/edit_comment.php?id=<?= $reply['id']?>" class="text-xs text-muted">Edit</a>
                <?php
            endif; ?>
            </div>
        </div>
        <?php
        endforeach; ?>
    </div>
    <?php
    endif; ?>
</div>
<?php
endforeach; ?>

<?php if (empty($main_comments)): ?>
<p class="text-muted">No comments yet. Be the first!</p>
<?php
endif; ?>