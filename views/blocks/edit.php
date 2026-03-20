<?php
require_once '../../config/db.php';
require_once '../../includes/functions.php';
require_once '../../models/Block.php';

requireLogin();

$id = $_GET['id'] ?? null;
if (!$id)
    redirect('index.php');

$blockModel = new Block($pdo);
$block = $blockModel->getById($id);

if (!$block)
    redirect('index.php');

// Must be creator to edit
$isMember = $blockModel->isMember($id, $_SESSION['user_id']);
if (!$isMember || $isMember['role'] !== 'creator') {
    redirect('views/blocks/view.php?id=' . $id);
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name']);
    $description = sanitizeInput($_POST['description']);
    $tags = sanitizeInput($_POST['tags']);
    $privacy = $_POST['privacy'] ?? 'public';

    $icon = null;
    if (!empty($_FILES['icon']['name'])) {
        $icon = uploadMedia($_FILES['icon'], 'block_icons');
        if ($icon === 'SIZE_EXCEEDED') {
            $error = 'Block icon must be less than 2MB. Please choose a smaller image.';
            $icon = null;
        }
    }

    if (empty($name) || empty($description)) {
        $error = "Name and description are required.";
    }
    elseif (!$error) {
        if ($blockModel->update($id, $name, $description, $tags, $icon, $privacy)) {
            $success = "Block updated successfully.";
            $block = $blockModel->getById($id); // Reload
        }
        else {
            $error = "Failed to update block.";
        }
    }
}

include '../layouts/header.php';
?>

<div class="card max-w-lg mx-auto" style="max-width: 600px; margin: 0 auto;">
    <div class="flex justify-between items-center mb-3">
        <h2>Edit Block</h2>
        <a href="views/blocks/view.php?id=<?= $id?>" class="btn btn-secondary text-sm">Cancel</a>
    </div>

    <?php if ($error): ?>
    <p class="text-error mb-2" style="color: var(--danger);">
        <?= htmlspecialchars($error)?>
    </p>
    <?php
endif; ?>
    <?php if ($success): ?>
    <p class="text-success mb-2" style="color: var(--success);">
        <?= htmlspecialchars($success)?>
    </p>
    <?php
endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-group text-center">
            <img src="public/images/block_icons/<?= htmlspecialchars($block['icon'])?>"
                class="avatar avatar-lg mx-auto mb-2" style="display: block; margin: 0 auto;">
        </div>

        <div class="form-group">
            <label>Block Name</label>
            <input type="text" name="name" class="js-char-limit" data-limit="37" required
                value="<?= htmlspecialchars($block['name'])?>">
        </div>
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" class="js-char-limit" data-limit="1000" rows="4"
                required><?= htmlspecialchars($block['description'])?></textarea>
        </div>
        <div class="form-group">
            <label>Tags (comma separated)</label>
            <input type="text" name="tags" class="js-char-limit" data-limit="60"
                value="<?= htmlspecialchars($block['tags'] ?? '')?>">
        </div>
        <div class="form-group">
            <label>Privacy</label>
            <select name="privacy">
                <option value="public" <?=$block['privacy']=='public' ? 'selected' : ''?>>Public</option>
                <option value="private" <?=$block['privacy']=='private' ? 'selected' : ''?>>To me</option>
                <option value="followers" <?=$block['privacy']=='followers' ? 'selected' : ''?>>To Followers</option>
                <option value="following" <?=$block['privacy']=='following' ? 'selected' : ''?>>To Followings</option>
                <option value="followers_and_following" <?=$block['privacy']=='followers_and_following' ? 'selected'
                    : ''?>>To Followers and Followings</option>
            </select>
        </div>
        <div class="form-group">
            <label>Update Icon <span class="text-muted" style="font-size:0.8rem;">(Image/GIF, max 2MB)</span></label>
            <input type="file" name="icon" id="icon-input" accept="image/jpeg,image/png,image/gif,image/webp"
                onchange="validateIcon(this)">
            <small class="text-muted" style="display: block; margin-top: 0.25rem; font-size: 0.8rem;">Maximum file size:
                2MB. Supported formats: JPEG, PNG, GIF, WebP.</small>
            <p id="icon-size-warning" style="display:none; color:var(--danger); font-size:0.85rem; margin-top:0.25rem;">
                ⚠️ File is too large. Maximum allowed size is 2MB.</p>
            <p id="icon-type-warning" style="display:none; color:var(--danger); font-size:0.85rem; margin-top:0.25rem;">
                ⚠️ Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed.</p>
        </div>
        <button type="submit" class="btn btn-primary btn-block mt-2">Save Changes</button>
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