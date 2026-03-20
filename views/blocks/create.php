<?php
require_once '../../config/db.php';
require_once '../../includes/functions.php';
require_once '../../models/Block.php';

requireLogin();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name']);
    $description = sanitizeInput($_POST['description']);
    $tags = sanitizeInput($_POST['tags']);
    $privacy = $_POST['privacy'] ?? 'public';

    // Icon upload handling
    $icon = uploadMedia($_FILES['icon'] ?? null, 'block_icons', 'default_block.jpg');
    if ($icon === 'SIZE_EXCEEDED') {
        $error = 'Block icon must be less than 2MB. Please choose a smaller image.';
        $icon = 'default_block.jpg';
    }

    $blockModel = new Block($pdo);
    if (empty($name) || empty($description)) {
        $error = "Name and description are required.";
    }
    elseif (!$error) {
        $blockId = $blockModel->create($_SESSION['user_id'], $name, $description, $tags, $icon, $privacy);
        if ($blockId) {
            // Auto member and creator roles handled manually for now
            $blockModel->addMember($blockId, $_SESSION['user_id'], 'creator');
            redirect('views/blocks/view.php?id=' . $blockId);
        }
        else {
            $error = "Failed to create block. Name might be taken.";
        }
    }
}

include '../layouts/header.php';
?>

<div class="card max-w-lg mx-auto" style="max-width: 600px; margin: 0 auto;">
    <h2 class="mb-3">Create a new Block</h2>

    <?php if ($error): ?>
    <p class="text-error mb-2" style="color: var(--danger);">
        <?= htmlspecialchars($error)?>
    </p>
    <?php
endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Block Name</label>
            <input type="text" name="name" class="js-char-limit" data-limit="37" required
                placeholder="For example: Anime Fans">
        </div>
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" class="js-char-limit" data-limit="1000" rows="4" required
                placeholder="What is this block about?"></textarea>
        </div>
        <div class="form-group">
            <label>Tags (comma separated)</label>
            <input type="text" name="tags" class="js-char-limit" data-limit="60" placeholder="anime, manga, discussion">
        </div>
        <div class="form-group">
            <label>Privacy</label>
            <select name="privacy">
                <option value="public">Public</option>
                <option value="private">To me</option>
                <option value="followers">To Followers</option>
                <option value="following">To Followings</option>
                <option value="followers_and_following">To Followers and Followings</option>
            </select>
        </div>
        <div class="form-group">
            <label>Icon <span class="text-muted" style="font-size:0.8rem;">(Image/GIF, max 2MB)</span></label>
            <input type="file" name="icon" id="icon-input" accept="image/jpeg,image/png,image/gif,image/webp"
                onchange="validateIcon(this)">
            <small class="text-muted" style="display: block; margin-top: 0.25rem; font-size: 0.8rem;">Maximum file size:
                2MB. Supported formats: JPEG, PNG, GIF, WebP.</small>
            <p id="icon-size-warning" style="display:none; color:var(--danger); font-size:0.85rem; margin-top:0.25rem;">
                ⚠️ File is too large. Maximum allowed size is 2MB.</p>
            <p id="icon-type-warning" style="display:none; color:var(--danger); font-size:0.85rem; margin-top:0.25rem;">
                ⚠️ Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed.</p>
        </div>
        <button type="submit" class="btn btn-primary btn-block mt-2">Create Block</button>
    </form>
</div>

<script>
    function validateIcon(input) {
        const sizeWarning = document.getElementById('icon-size-warning');
        const typeWarning = document.getElementById('icon-type-warning');
        const maxSize = 2 * 1024 * 1024; // 2 MB
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        sizeWarning.style.display = 'none';
        typeWarning.style.display = 'none';

        if (input.files && input.files[0]) {
            const file = input.files[0];
            if (!allowedTypes.includes(file.type)) {
                typeWarning.style.display = 'block';
                input.value = '';
                return;
            }
            if (file.size > maxSize) {
                sizeWarning.style.display = 'block';
                input.value = '';
                return;
            }
        }
    }
</script>

<?php include '../layouts/footer.php'; ?>