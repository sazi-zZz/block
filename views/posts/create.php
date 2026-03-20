<?php
require_once '../../config/db.php';
require_once '../../includes/functions.php';
require_once '../../models/Block.php';
require_once '../../models/Post.php';

requireLogin();

$blockModel = new Block($pdo);
$userBlocks = $blockModel->getUserBlocks($_SESSION['user_id']);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $block_id = $_POST['block_id'];
    $title = sanitizeInput($_POST['title']);
    $content = sanitizeInput($_POST['content']);
    $repost_id = $_POST['repost_id'] ?? null;
    $privacy = $_POST['privacy'] ?? 'public';
    $image = uploadMedia($_FILES['image'] ?? null, 'post_images', null, 20);

    // Verify user is in block
    $isMember = $blockModel->isMember($block_id, $_SESSION['user_id']);

    if (!$isMember) {
        $error = "You must be a member of the block to post in it.";
    }
    elseif (mb_strlen($title) > 120) {
        $error = "Title exceeds the character limit of 120.";
    }
    elseif (mb_strlen($content) > 3000) {
        $error = "Content exceeds the character limit of 3000.";
    }
    elseif (empty($title) || empty($content)) {
        $error = "Title and content are required.";
    }
    elseif ($image === 'SIZE_EXCEEDED') {
        $error = "Media size exceeds the limit of 20MB.";
    }
    else {
        $postModel = new Post($pdo);
        if ($postModel->create($block_id, $_SESSION['user_id'], $title, $content, $image, $privacy, $repost_id)) {
            redirect('views/blocks/view.php?id=' . $block_id);
        }
        else {
            $error = "Failed to create post.";
        }
    }
}

$selected_block_id = $_GET['block_id'] ?? null;
$repost_id = $_GET['repost_id'] ?? ($_POST['repost_id'] ?? null);
$repost_data = null;

if ($repost_id) {
    if (!isset($postModel))
        $postModel = new Post($pdo);
    $repost_data = $postModel->getById($repost_id);
}

include '../layouts/header.php';
?>

<div class="card max-w-lg mx-auto" style="max-width: 600px; margin: 0 auto;">
    <h2 class="mb-3">Create a Post</h2>

    <?php if ($error): ?>
    <p class="text-error mb-2" style="color: var(--danger);">
        <?= htmlspecialchars($error)?>
    </p>
    <?php
endif; ?>

    <?php if (empty($userBlocks)): ?>
    <p class="text-muted">You must join a block before you can create a post!</p>
    <a href="views/blocks/index.php" class="btn btn-primary mt-2">Explore Blocks</a>
    <?php
else: ?>
    <form method="POST" enctype="multipart/form-data">
        <?php if ($repost_id && $repost_data): ?>
        <input type="hidden" name="repost_id" value="<?= htmlspecialchars($repost_id)?>">
        <div class="card bg-darker p-3 mb-3"
            style="border-left: 3px solid var(--primary); border-radius: var(--radius);">
            <span class="text-muted" style="font-size: 0.8rem; display: block; margin-bottom: 0.5rem;">
                <i class="fa-solid fa-retweet"></i> Reposting <strong style="color:var(--white);">
                    <?= htmlspecialchars($repost_data['title'])?>
                </strong> by
                <?= htmlspecialchars($repost_data['username'])?>
            </span>
            <p style="font-size: 0.9rem; margin-bottom: 0; color: var(--gray-400);">
                <?= nl2br(htmlspecialchars(substr($repost_data['content'], 0, 100)))?>
                <?= strlen($repost_data['content']) > 100 ? '...' : ''?>
            </p>
        </div>
        <?php
    endif; ?>
        <div class="form-group">
            <label>Select Block</label>
            <select name="block_id" required>
                <?php foreach ($userBlocks as $block): ?>
                <option value="<?= $block['id']?>" <?=($selected_block_id==$block['id']) ? 'selected' : ''?>>
                    <?= htmlspecialchars($block['name'])?>
                </option>
                <?php
    endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Title</label>
            <input type="text" name="title" class="js-char-limit" data-limit="120" required
                placeholder="Catchy title...">
        </div>
        <div class="form-group">
            <label>Content</label>
            <textarea name="content" class="js-char-limit" data-limit="3000" rows="6" required
                placeholder="What's on your mind?"></textarea>
        </div>
        <div class="form-group">
            <label>Privacy</label>
            <select name="privacy">
                <option value="public">Public</option>
                <option value="private">To me</option>
                <option value="followers">To Followers</option>
                <option value="following">To Followings</option>
                <option value="followers_and_following">To Followers and Followings</option>
                <option value="block_only">To this Block</option>
            </select>
        </div>
        <div class="form-group">
            <label>Upload Media (Image/Video)</label>
            <input type="file" name="image" accept="image/*,video/*" id="post-media-upload">
            <small class="text-muted" style="display: block; margin-top: 0.25rem; font-size: 0.8rem;">Maximum file size:
                20MB. Supported formats: JPEG, PNG, GIF, WebP, MP4, WebM, OGG.</small>
            <small id="media-error" class="text-error" style="color: var(--danger); display: none;">Media size must be
                less than 20MB.</small>
            <small id="media-type-error" class="text-error" style="color: var(--danger); display: none;">Invalid file
                type. Only standard images and videos are allowed.</small>
        </div>
        <button type="submit" class="btn btn-primary btn-block mt-2" id="submit-post-btn">Publish Post</button>
    </form>
    <?php
endif; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const uploadInput = document.getElementById('post-media-upload');
        const mediaError = document.getElementById('media-error');
        const mediaTypeError = document.getElementById('media-type-error');
        const submitBtn = document.getElementById('submit-post-btn');

        if (uploadInput) {
            uploadInput.addEventListener('change', function () {
                mediaError.style.display = 'none';
                if (mediaTypeError) mediaTypeError.style.display = 'none';

                if (this.files && this.files[0]) {
                    const file = this.files[0];
                    const fileSize = file.size;
                    const maxSize = 20 * 1024 * 1024; // 20MB
                    const allowedTypes = [
                        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
                        'video/mp4', 'video/webm', 'video/ogg'
                    ];

                    if (!allowedTypes.includes(file.type)) {
                        if (mediaTypeError) mediaTypeError.style.display = 'block';
                        this.value = ''; // Clear selection
                        return;
                    }

                    if (fileSize > maxSize) {
                        mediaError.style.display = 'block';
                        this.value = ''; // Clear selection
                    }
                }
            });
        }
    });
</script>

<?php include '../layouts/footer.php'; ?>