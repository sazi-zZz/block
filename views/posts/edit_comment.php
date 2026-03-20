<?php
require_once '../../config/db.php';
require_once '../../includes/functions.php';
require_once '../../models/Post.php';

requireLogin();

$id = $_GET['id'] ?? null;
if (!$id)
    redirect('index.php');

$postModel = new Post($pdo);
$comment = $postModel->getCommentById($id);

if (!$comment)
    redirect('index.php');

// Verify authorship
if ($comment['user_id'] != $_SESSION['user_id']) {
    redirect('views/posts/view.php?id=' . $comment['post_id']);
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = sanitizeInput($_POST['content']);

    $media = $comment['media']; // keep existing by default
    $uploadError = null;

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        if ($_FILES['image']['size'] > 2 * 1024 * 1024) {
            $uploadError = "Image must be less than 2MB.";
        }
        else {
            $mime_type = $_FILES['image']['type'];

            if (in_array($mime_type, ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'])) {
                $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                if (empty($extension)) {
                    $extension = explode('/', $mime_type)[1];
                }
                $new_media = uniqid() . '.' . $extension;
                $target_dir = __DIR__ . '/../../public/images/comment_media/';
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_dir . $new_media)) {
                    // Deleted old media
                    if ($media && file_exists($target_dir . $media)) {
                        unlink($target_dir . $media);
                    }
                    $media = $new_media;
                }
                else {
                    $uploadError = "Failed to save file to disk.";
                }
            }
            else {
                $uploadError = "Only images and GIFs are allowed. Detected type: " . htmlspecialchars($mime_type);
            }
        }
    }

    if ($uploadError) {
        $error = $uploadError;
    }
    else if (empty($content) && empty($media)) {
        $error = "Content or image is required.";
    }
    else {
        if ($postModel->updateComment($id, $content, $media)) {
            $success = "Comment updated!";
            $comment = $postModel->getCommentById($id);
        }
        else {
            $error = "Failed to update comment.";
        }
    }
}

include '../layouts/header.php';
?>

<div class="card max-w-lg mx-auto" style="max-width: 600px; margin: 0 auto;">
    <div class="flex justify-between items-center mb-3">
        <h2>Edit Comment</h2>
        <a href="views/posts/view.php?id=<?= $comment['post_id']?>" class="btn btn-secondary text-sm">Cancel</a>
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
            <label>Comment</label>
            <textarea name="content" class="js-char-limit" data-limit="1000"
                rows="4"><?= htmlspecialchars($comment['content'])?></textarea>
        </div>

        <div class="form-group mb-3">
            <label>Attach Image/GIF (max 2MB)</label>
            <input type="file" name="image" accept="image/png, image/jpeg, image/gif, image/webp" class="form-control"
                onchange="previewImage(this)">
            <small class="text-muted" style="display: block; margin-top: 0.25rem; font-size: 0.8rem;">Maximum file size:
                2MB. Supported formats: JPEG, PNG, GIF, WebP.</small>
            <p class="text-xs text-muted mt-1">Leave empty to keep current media.</p>
            <div id="image-preview" class="mt-2" style="display: <?= $comment['media'] ? 'block' : 'none'?>;">
                <img src="public/images/comment_media/<?= htmlspecialchars($comment['media'])?>"
                    style="max-height: 200px; border-radius: var(--radius); border: 1px solid var(--border-color);">
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block mt-2">Save Changes</button>
    </form>
</div>

<script>
    function previewImage(input) {
        const previewDiv = document.getElementById('image-preview');
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

            if (!allowedTypes.includes(file.type)) {
                alert("Invalid file type. Only standard images are allowed.");
                input.value = '';
                previewDiv.style.display = 'none';
                return;
            }

            if (file.size > 2 * 1024 * 1024) {
                alert("Image must be less than 2MB");
                input.value = '';
                previewDiv.style.display = 'none';
                return;
            }
            const reader = new FileReader();
            reader.onload = function (e) {
                previewDiv.style.display = 'block';
                previewDiv.innerHTML = '<img src="' + e.target.result + '" style="max-height: 200px; border-radius: var(--radius); border: 1px solid var(--border-color);">';
            };
            reader.readAsDataURL(file);
        }
    }
</script>

<?php include '../layouts/footer.php'; ?>