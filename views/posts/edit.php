<?php
require_once '../../config/db.php';
require_once '../../includes/functions.php';
require_once '../../models/Post.php';

requireLogin();

$id = $_GET['id'] ?? null;
if (!$id)
    redirect('/block/index.php');

$postModel = new Post($pdo);
$post = $postModel->getById($id);

if (!$post)
    redirect('/block/index.php');

// Verify authorship
if ($post['user_id'] != $_SESSION['user_id']) {
    redirect('/block/views/posts/view.php?id=' . $id);
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitizeInput($_POST['title']);
    $content = sanitizeInput($_POST['content']);
    $privacy = $_POST['privacy'] ?? 'public';

    $image = null;
    if (!empty($_FILES['image']['name'])) {
        $image = uploadMedia($_FILES['image'], 'post_images');
    }

    if (mb_strlen($title) > 120) {
        $error = "Title exceeds the character limit of 120.";
    }
    elseif (mb_strlen($content) > 3000) {
        $error = "Content exceeds the character limit of 3000.";
    }
    elseif (empty($title) || empty($content)) {
        $error = "Title and content are required.";
    }
    elseif ($image === 'SIZE_EXCEEDED') {
        $error = "Media size exceeds the limit of 2MB.";
    }
    else {
        if ($postModel->update($id, $title, $content, $image, $privacy)) {
            $success = "Post updated successfully!";
            $post = $postModel->getById($id);
        }
        else {
            $error = "Failed to update post.";
        }
    }
}

include '../layouts/header.php';
?>

<div class="card max-w-lg mx-auto" style="max-width: 600px; margin: 0 auto;">
    <div class="flex justify-between items-center mb-3">
        <h2>Edit Post</h2>
        <a href="/block/views/posts/view.php?id=<?= $id?>" class="btn btn-secondary text-sm">Cancel</a>
    </div>

    <?php if ($error): ?>
    <p class="text-error mb-2" style="color: var(--danger);">
        <?= htmlspecialchars($error)?>
    </p>
    <?php
endif; ?>
    <?php if ($success): ?>
    <p class="mb-2" style="color: var(--success);">
        <?= htmlspecialchars($success)?>
    </p>
    <?php
endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Title</label>
            <input type="text" name="title" class="js-char-limit" data-limit="120" required
                value="<?= htmlspecialchars($post['title'])?>">
        </div>

        <div class="form-group">
            <label>Content</label>
            <textarea name="content" class="js-char-limit" data-limit="3000" rows="6"
                required><?= htmlspecialchars($post['content'])?></textarea>
        </div>

        <?php if ($post['image']): ?>
        <div class="mb-2">
            <label>Current Media</label>
            <?php if (isVideo($post['image'])): ?>
            <video src="/block/public/images/post_images/<?= htmlspecialchars($post['image'])?>" controls
                style="max-width: 100%; border-radius: 8px; display:block; margin-top:0.5rem;"></video>
            <?php
    else: ?>
            <img src="/block/public/images/post_images/<?= htmlspecialchars($post['image'])?>"
                style="max-width: 100%; border-radius: 8px; display:block; margin-top:0.5rem;">
            <?php
    endif; ?>
        </div>
        <?php
endif; ?>

        <div class="form-group">
            <label>Privacy</label>
            <select name="privacy">
                <option value="public" <?= $post['privacy'] == 'public' ? 'selected' : '' ?>>Public</option>
                <option value="private" <?= $post['privacy'] == 'private' ? 'selected' : '' ?>>To me</option>
                <option value="followers" <?= $post['privacy'] == 'followers' ? 'selected' : '' ?>>To Followers</option>
                <option value="following" <?= $post['privacy'] == 'following' ? 'selected' : '' ?>>To Followings</option>
                <option value="followers_and_following" <?= $post['privacy'] == 'followers_and_following' ? 'selected' : ''
    ?>>To Followers and Followings</option>
                <option value="block_only" <?= $post['privacy'] == 'block_only' ? 'selected' : '' ?>>To this Block</option>
            </select>
        </div>

        <div class="form-group">
            <label>Upload New Media (optional)</label>
            <input type="file" name="image" accept="image/*,video/*" id="post-media-upload">
            <small class="text-muted" style="display: block; margin-top: 0.25rem; font-size: 0.8rem;">Maximum file size:
                20MB. Supported formats: JPEG, PNG, GIF, WebP, MP4, WebM, OGG.</small>
            <small id="media-error" class="text-error" style="color: var(--danger); display: none;">Media size must be
                less than 20MB.</small>
            <small id="media-type-error" class="text-error" style="color: var(--danger); display: none;">Invalid file
                type. Only standard images and videos are allowed.</small>
        </div>

        <button type="submit" class="btn btn-primary btn-block mt-2" id="submit-post-btn">Save Changes</button>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const uploadInput = document.getElementById('post-media-upload');
        const mediaError = document.getElementById('media-error');

        const mediaTypeError = document.getElementById('media-type-error');

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