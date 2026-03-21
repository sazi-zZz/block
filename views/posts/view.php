<?php
require_once '../../config/db.php';
require_once '../../includes/functions.php';
require_once '../../models/Post.php';
require_once '../../models/Notification.php';
require_once '../../models/Block.php';

requireLogin();

$id = $_GET['id'] ?? null;
if (!$id)
    redirect(BASE_URL . 'index.php');

$postModel = new Post($pdo);
$post = $postModel->getById($id);

if (!$post)
    redirect(BASE_URL . 'index.php');

if ($post['privacy'] === 'block_only' && $post['user_id'] != $_SESSION['user_id']) {
    $blockModel = new Block($pdo);
    if (!$blockModel->isMember($post['block_id'], $_SESSION['user_id'])) {
        redirect(BASE_URL . 'index.php');
    }
}

$isLiked = $postModel->isLiked($id, $_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'like') {
            if ($isLiked) {
                $postModel->unlike($id, $_SESSION['user_id']);
            }
            else {
                $postModel->like($id, $_SESSION['user_id']);
            }
        }
        elseif ($_POST['action'] === 'comment') {
            $content = sanitizeInput($_POST['content']);
            $parent_id = isset($_POST['parent_id']) && !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;

            $media = null;
            $uploadError = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                if ($_FILES['image']['size'] > 2 * 1024 * 1024) {
                    $uploadError = "Image must be less than 2MB.";
                }
                else {
                    $mime_type = $_FILES['image']['type'];

                    if (in_array($mime_type, ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'])) {
                        $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                        // Make sure extension is safe fallback
                        if (empty($extension)) {
                            $extension = explode('/', $mime_type)[1];
                        }
                        $media = uniqid() . '.' . $extension;
                        $target_dir = __DIR__ . '/../../public/images/comment_media/';
                        if (!is_dir($target_dir)) {
                            mkdir($target_dir, 0777, true);
                        }
                        if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_dir . $media)) {
                            $uploadError = "Failed to save file to disk.";
                        }
                    }
                    else {
                        $uploadError = "Only images and GIFs are allowed. Detected type: " . htmlspecialchars($mime_type);
                    }
                }
            }

            if ($uploadError) {
                $_SESSION['error'] = $uploadError;
            }
            else if (!empty($content) || $media) {
                $postModel->addComment($id, $_SESSION['user_id'], $content, $parent_id, $media);
                $notificationModel = new Notification($pdo);
                $username = $_SESSION['username'] ?? 'Someone';
                if ($parent_id) {
                    $parentComment = $postModel->getCommentById($parent_id);
                    if ($parentComment && $parentComment['user_id'] != $_SESSION['user_id']) {
                        $notificationModel->create($parentComment['user_id'], 'comment', $id, "$username replied to your comment.");
                    }
                }
                else {
                    if ($post['user_id'] != $_SESSION['user_id']) {
                        $notificationModel->create($post['user_id'], 'comment', $id, "$username commented on your post.");
                    }
                }
            }
        }
        elseif ($_POST['action'] === 'delete') {
            if ($post['user_id'] == $_SESSION['user_id']) {
                $block_id = $post['block_id'];
                $postModel->delete($id);
                redirect(BASE_URL . 'views/blocks/view.php?id=' . $block_id);
            }
        }
    }
    // Refresh page
    redirect(BASE_URL . 'views/posts/view.php?id=' . $id);
}

$comments = $postModel->getComments($id);

// Simple comment tree (1 level deep)
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

include '../layouts/header.php';
?>

<div class="card mb-3 main-post-view" data-post-id="<?= $post['id']?>">
    <div class="flex items-center mb-3">
        <a href="<?= BASE_URL?>views/user/profile.php?id=<?= $post['user_id']?>">
            <img src="<?= BASE_URL?>public/images/avatars/<?= htmlspecialchars($post['avatar'] ?: 'user.jpg')?>"
                class="avatar avatar-sm" style="margin-right: 0.75rem;"
                onerror="this.src='<?= BASE_URL?>public/images/avatars/user.jpg'; this.onerror=null;">
        </a>
        <div>
            <strong>
                <?= htmlspecialchars($post['username'])?>
            </strong>
            <div style="font-size: 0.75rem;" class="text-muted">
                in <a href="<?= BASE_URL?>views/blocks/view.php?id=<?= $post['block_id']?>">
                    <?= htmlspecialchars($post['block_name'])?>
                </a> •
                <?= getDisplayTime($post['created_at'])?>
            </div>
        </div>
        <?php if ($post['user_id'] == $_SESSION['user_id']): ?>
        <div style="margin-left: auto;" class="flex gap-2">
            <a href="<?= BASE_URL?>views/posts/edit.php?id=<?= $id?>" class="btn btn-secondary text-sm"
                style="padding: 0.25rem 0.75rem;">Edit</a>
            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this post?');"
                style="display:inline;">
                <input type="hidden" name="action" value="delete">
                <button type="submit" class="btn btn-danger text-sm" style="padding: 0.25rem 0.75rem;">Delete</button>
            </form>
        </div>
        <?php
endif; ?>
    </div>

    <div class="flex justify-between items-center mb-3">
        <h2>
            <?= htmlspecialchars($post['title'])?>
        </h2>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
    <div class="mb-3"
        style="padding: 1rem; background: var(--danger); color: white; border-radius: var(--radius); font-size: 0.9rem;">
        <?= htmlspecialchars($_SESSION['error'])?>
    </div>
    <?php unset($_SESSION['error']);
endif; ?>

    <div class="post-content mb-3" style="line-height: 1.6;">
        <?= nl2br(htmlspecialchars($post['content']))?>
    </div>

    <?php if (!empty($post['ai_percentage']) && (int)$post['ai_percentage'] > 0): ?>
    <div
        style="font-size: 0.8rem; color: var(--gray-500); margin-bottom: 1rem; display: inline-flex; align-items: center; background: var(--bg-secondary); padding: 0.2rem 0.6rem; border-radius: 12px; border: 1px solid var(--border-color);">
        <i class="fa-solid fa-robot" style="margin-right: 0.35rem;"></i>
        <?= $post['ai_percentage']?>% AI Content
    </div>
    <?php
endif; ?>

    <?php if ($post['image']): ?>
    <?php if (isVideo($post['image'])): ?>
    <video src="<?= BASE_URL?>public/images/post_images/<?= htmlspecialchars($post['image'])?>" controls
        style="max-width: 100%; border-radius: 8px; margin-bottom: 1.5rem;"></video>
    <?php
    else: ?>
    <img src="<?= BASE_URL?>public/images/post_images/<?= htmlspecialchars($post['image'])?>"
        style="max-width: 100%; border-radius: 8px; margin-bottom: 1.5rem;">
    <?php
    endif; ?>
    <?php
endif; ?>
    <?php if (!empty($post['rp_id'])): ?>
    <!-- Repost Card -->
    <div class="card p-3 mb-3 bg-darker"
        style="border: 1px solid var(--border-color); border-radius: var(--radius); pointer-events: auto;">
        <a href="<?= BASE_URL?>views/posts/view.php?id=<?= $post['rp_id']?>"
            style="text-decoration:none; color:inherit; display:block;">
            <div class="flex items-center mb-2">
                <img src="<?= BASE_URL?>public/images/avatars/<?= htmlspecialchars($post['repost_avatar'] ?? 'user.jpg')?>"
                    class="avatar avatar-sm" style="margin-right: 0.5rem; width: 24px; height: 24px;" alt=""
                    onerror="this.src='<?= BASE_URL?>public/images/avatars/user.jpg'; this.onerror=null;">
                <span style="font-size: 0.85rem; font-weight: 500;">
                    <?= htmlspecialchars($post['repost_username'] ?? 'Unknown User')?>
                </span>
                <span class="text-muted" style="font-size: 0.8rem; margin-left: 0.5rem;">in
                    <?= htmlspecialchars($post['repost_block_name'] ?? 'Unknown Block')?>
                </span>
            </div>
            <h5 style="font-size: 1.1rem; font-weight: 600; margin-bottom: 0.5rem;">
                <?= htmlspecialchars($post['repost_title'])?>
            </h5>
            <p class="text-muted" style="font-size: 0.9rem; line-height: 1.5; margin-bottom: 0;">
                <?= nl2br(htmlspecialchars(substr($post['repost_content'], 0, 150)))?>
                <?= strlen($post['repost_content']) > 150 ? '...' : ''?>
            </p>
        </a>
    </div>
    <?php
endif; ?>

    <div class="flex gap-4 border-t pt-2" style="border-top: 1px solid var(--border-color); padding-top: 1rem;">
        <form method="POST" style="display:inline;">
            <input type="hidden" name="action" value="like">
            <button type="submit" class="post-action-btn <?= $isLiked ? 'liked' : ''?>"
                style="background:none; border:none; cursor:pointer; color: <?= $isLiked ? 'var(--danger)' : 'var(--text-muted)'?>; font-size:1rem; display: flex; align-items:center; gap: 0.5rem;">
                <i class="<?= $isLiked ? 'fa-solid' : 'fa-regular'?> fa-heart"></i>
                <span class="like-count">
                    <?= $post['like_count']?>
                </span>
            </button>
        </form>
        <a class="post-action-btn"
            style="color: var(--text-muted); font-size:1rem; display: flex; align-items:center; gap: 0.5rem;">
            <i class="fa-regular fa-comment"></i>
            <span>
                <?= count($comments)?>
            </span>
        </a>
        <a href="<?= BASE_URL?>views/posts/create.php?repost_id=<?= $post['id']?>" title="Repost"
            style="color: var(--text-muted); font-size:1rem; display: flex; align-items:center; gap: 0.5rem; text-decoration:none; margin-left: auto;">
            <i class="fa-solid fa-retweet"></i> Repost
        </a>
    </div>
</div>

<div class="card">
    <h3 class="mb-3">Comments</h3>

    <form method="POST" class="mb-3" enctype="multipart/form-data">
        <input type="hidden" name="action" value="comment">
        
        <div style="margin-bottom: 0.75rem;">
            <textarea id="comment-input" name="content" class="js-char-limit" data-limit="1000"
                placeholder="Add a comment..." style="width: 100%; min-height: 80px; resize: vertical; border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 0.75rem;"></textarea>
        </div>

        <div class="flex items-center justify-between mt-2">
            <div class="flex items-center gap-3">
                <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('comment-image').click()"
                    style="padding: 0.4rem 0.6rem; font-size: 1.1rem;" title="Attach Image"><i class="fa-regular fa-image"></i></button>
                <div class="emoji-picker" style="cursor: pointer; font-size: 1.3rem;"
                    onclick="toggleEmojiPicker('comment-input')" title="Emoji">😀</div>
            </div>
            <button type="submit" class="btn btn-primary btn-sm" style="padding: 0.4rem 1.25rem;">Post</button>
        </div>

        <input type="file" id="comment-image" name="image" accept="image/png, image/jpeg, image/gif, image/webp"
            style="display:none;" onchange="previewCommentImage(this, 'comment-image-preview')">
        <small class="text-muted" style="display: block; margin-top: 0.5rem; font-size: 0.75rem;">Maximum file size:
            2MB. Supported formats: JPEG, PNG, GIF, WebP.</small>
        <div id="comment-image-preview" class="mt-2 text-sm text-primary" style="display:none;"></div>
    </form>

    <div class="comments-list">
        <?php foreach ($main_comments as $comment): ?>
        <div class="comment mb-3" style="border-bottom: 1px solid var(--border-color); padding-bottom: 1rem;">
            <div class="flex items-center mb-1">
                <a href="<?= BASE_URL?>views/user/profile.php?id=<?= $comment['user_id']?>"
                    style="text-decoration:none; color:inherit; display:flex; align-items:center;">
                    <img src="<?= BASE_URL?>public/images/avatars/<?= htmlspecialchars($comment['avatar'] ?: 'user.jpg')?>"
                        class="avatar" style="width:28px; height:28px; margin-right: 0.5rem;"
                        onerror="this.src='<?= BASE_URL?>public/images/avatars/user.jpg'; this.onerror=null;">
                    <strong>
                        <?= htmlspecialchars($comment['username'])?>
                    </strong>
                </a>
                <span class="text-muted" style="margin-left: 0.5rem; font-size: 0.75rem;"
                    title="<?= date('M j, Y, g:i A', strtotime($comment['created_at']))?>">
                    <?= timeElapsedString($comment['created_at'])?>
                </span>
            </div>
            <div class="comment-body mb-2">
                <?php if (!empty($comment['content'])): ?>
                <div class="comment-content pl-2" style="font-size: 0.95rem;">
                    <?= nl2br(htmlspecialchars($comment['content']))?>
                </div>
                <?php
    endif; ?>
                <?php if (!empty($comment['ai_percentage']) && (int)$comment['ai_percentage'] > 0): ?>
                <div class="pl-2 mt-1">
                    <span
                        style="font-size: 0.7rem; color: var(--gray-500); display: inline-flex; align-items: center; background: var(--bg-secondary); padding: 0.1rem 0.4rem; border-radius: 8px; border: 1px solid var(--border-color);">
                        <i class="fa-solid fa-robot" style="margin-right: 0.25rem; font-size: 0.65rem;"></i>
                        <?= $comment['ai_percentage']?>% AI Content
                    </span>
                </div>
                <?php
    endif; ?>
                <?php if (!empty($comment['media'])): ?>
                <div class="pl-2 mt-2">
                    <img src="<?= BASE_URL?>public/images/comment_media/<?= htmlspecialchars($comment['media'])?>"
                        style="max-height: 200px; border-radius: 8px; border: 1px solid var(--border-color);">
                </div>
                <?php
    endif; ?>
            </div>
            <div class="flex gap-3 items-center">
                <button class="text-sm text-primary" style="background:none; border:none; cursor:pointer; padding:0;"
                    onclick="toggleReplyForm(<?= $comment['id']?>)">Reply</button>
                <?php if ($comment['user_id'] == $_SESSION['user_id']): ?>
                <a href="<?= BASE_URL?>views/posts/edit_comment.php?id=<?= $comment['id']?>"
                    class="text-sm text-muted">Edit</a>
                <?php
    endif; ?>
            </div>

            <!-- Reply Form (Hidden by default) -->
            <div id="reply-form-<?= $comment['id']?>" class="mt-2 pl-4" style="display:none;">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="comment">
                    <input type="hidden" name="parent_id" value="<?= $comment['id']?>">
                    
                    <div style="margin-bottom: 0.5rem;">
                        <textarea id="reply-input-<?= $comment['id']?>" name="content"
                            class="text-sm js-char-limit" data-limit="1000" placeholder="Write a reply..."
                            style="width: 100%; border-radius: var(--radius-sm); padding: 0.5rem; min-height: 50px; resize: vertical; border: 1px solid var(--border-color);"></textarea>
                    </div>
                    
                    <div class="flex items-center justify-between mt-2">
                        <div class="flex items-center gap-2">
                            <button type="button" class="btn btn-secondary btn-sm"
                                onclick="document.getElementById('reply-image-<?= $comment['id']?>').click()" style="padding: 0.3rem 0.5rem; font-size: 1rem;"><i
                                    class="fa-regular fa-image"></i></button>
                            <div class="emoji-picker" style="cursor: pointer; font-size: 1.2rem;"
                                onclick="toggleEmojiPicker('reply-input-<?= $comment['id']?>')">😀</div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm" style="padding: 0.3rem 1rem;">Reply</button>
                    </div>

                    <input type="file" id="reply-image-<?= $comment['id']?>" name="image"
                        accept="image/png, image/jpeg, image/gif, image/webp" style="display:none;"
                        onchange="previewCommentImage(this, 'reply-image-preview-<?= $comment['id']?>')">
                    <small class="text-muted" style="display: block; margin-top: 0.25rem; font-size: 0.75rem;">Max 2MB. Supported: JPEG, PNG, GIF, WebP.</small>
                    <div id="reply-image-preview-<?= $comment['id']?>" class="mt-1 text-xs text-primary"
                        style="display:none;"></div>
                </form>
            </div>

            <!-- Nested Replies -->
            <?php if (isset($replies[$comment['id']])): ?>
            <div class="replies-container mt-2 pl-4" style="border-left: 2px solid var(--border-color);">
                <?php foreach ($replies[$comment['id']] as $reply): ?>
                <div class="reply mb-2">
                    <div class="flex items-center mb-1">
                        <a href="<?= BASE_URL?>views/user/profile.php?id=<?= $reply['user_id']?>"
                            style="text-decoration:none; color:inherit; display:flex; align-items:center;">
                            <img src="<?= BASE_URL?>public/images/avatars/<?= htmlspecialchars($reply['avatar'] ?: 'user.jpg')?>"
                                class="avatar" style="width:20px; height:20px; margin-right: 0.5rem;"
                                onerror="this.src='<?= BASE_URL?>public/images/avatars/user.jpg'; this.onerror=null;">
                            <strong style="font-size: 0.85rem;">
                                <?= htmlspecialchars($reply['username'])?>
                            </strong>
                        </a>
                        <span class="text-muted" style="margin-left: 0.5rem; font-size: 0.7rem;"
                            title="<?= date('M j, Y, g:i A', strtotime($reply['created_at']))?>">
                            <?= timeElapsedString($reply['created_at'])?>
                        </span>
                    </div>
                    <div class="reply-content pl-2" style="font-size: 0.9rem;">
                        <?php if (!empty($reply['content'])): ?>
                        <?= nl2br(htmlspecialchars($reply['content']))?>
                        <?php
            endif; ?>
                        <?php if (!empty($reply['ai_percentage']) && (int)$reply['ai_percentage'] > 0): ?>
                        <div class="mt-1">
                            <span
                                style="font-size: 0.7rem; color: var(--gray-500); display: inline-flex; align-items: center; background: var(--bg-secondary); padding: 0.1rem 0.4rem; border-radius: 8px; border: 1px solid var(--border-color);">
                                <i class="fa-solid fa-robot" style="margin-right: 0.25rem; font-size: 0.65rem;"></i>
                                <?= $reply['ai_percentage']?>% AI Content
                            </span>
                        </div>
                        <?php
            endif; ?>
                        <?php if (!empty($reply['media'])): ?>
                        <div class="mt-2">
                            <img src="<?= BASE_URL?>public/images/comment_media/<?= htmlspecialchars($reply['media'])?>"
                                style="max-height: 150px; border-radius: 8px; border: 1px solid var(--border-color);">
                        </div>
                        <?php
            endif; ?>
                    </div>
                    <div class="flex gap-3 items-center mt-1">
                        <button class="text-xs text-primary"
                            style="background:none; border:none; cursor:pointer; padding:0;"
                            onclick="toggleReplyForm(<?= $comment['id']?>, '<?= htmlspecialchars($reply['username'])?>')">Reply</button>
                        <?php if ($reply['user_id'] == $_SESSION['user_id']): ?>
                        <a href="<?= BASE_URL?>views/posts/edit_comment.php?id=<?= $reply['id']?>"                            class="text-xs text-muted">Edit</a>
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
        <?php if (empty($comments)): ?>
        <p class="text-muted">No comments yet. Be the first!</p>
        <?php
endif; ?>
    </div>
</div>

<script>
    function toggleReplyForm(commentId, username = null) {
        const form = document.getElementById('reply-form-' + commentId);
        const input = form.querySelector('input[name="content"]');

        if (form.style.display === 'none' || (username && !input.value.includes('@' + username))) {
            form.style.display = 'block';
            if (username) {
                input.value = '@' + username + ' ';
            } else {
                input.value = '';
            }
            input.focus();
        } else {
            form.style.display = 'none';
        }
    }

    function toggleEmojiPicker(inputId) {
        const input = document.getElementById(inputId);
        const emoji = '😀'; // Simple for now
        input.value += emoji;
        input.focus();
    }

    function previewCommentImage(input, previewId) {
        const preview = document.getElementById(previewId);
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

            if (!allowedTypes.includes(file.type)) {
                alert("Invalid file type. Only standard images are allowed.");
                input.value = '';
                preview.style.display = 'none';
                return;
            }

            if (file.size > 2 * 1024 * 1024) {
                alert("Image must be less than 2MB");
                input.value = '';
                preview.style.display = 'none';
                return;
            }
            preview.style.display = 'block';
            preview.textContent = "Selected: " + file.name;
        } else {
            preview.style.display = 'none';
        }
    }
</script>

<?php include '../layouts/footer.php'; ?>