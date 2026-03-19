<?php
require_once '../../config/db.php';
require_once '../../includes/functions.php';
require_once '../../models/User.php';

requireLogin();

$userModel = new User($pdo);
$profileUser = $userModel->getUserById($_SESSION['user_id']);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bio = sanitizeInput($_POST['bio']);
    $avatarName = null;
    $coverName = null;

    if (mb_strlen($bio) > 200) {
        $error = 'Bio must not exceed 200 characters.';
    }

    // Avatar upload handling
    if (!$error && !empty($_FILES['avatar']['name'])) {
        $avatarName = uploadMedia($_FILES['avatar'], 'avatars');
        if ($avatarName === 'SIZE_EXCEEDED') {
            $error = 'Profile picture must be less than 2MB.';
            $avatarName = null;
        }
        elseif (!$avatarName) {
            $error = 'Error uploading profile picture.';
        }
    }

    // Cover photo upload handling
    if (!$error && !empty($_FILES['cover_photo']['name'])) {
        $coverName = uploadMedia($_FILES['cover_photo'], 'cover_photos');
        if ($coverName === 'SIZE_EXCEEDED') {
            $error = 'Cover photo must be less than 2MB.';
            $coverName = null;
        }
        elseif (!$coverName) {
            $error = 'Error uploading cover photo.';
        }
    }

    if (!$error) {
        if ($userModel->updateProfile($_SESSION['user_id'], $bio, $avatarName, $coverName)) {
            $success = 'Profile updated successfully!';
            // Reload user data
            $profileUser = $userModel->getUserById($_SESSION['user_id']);
        }
        else {
            $error = 'Failed to update profile.';
        }
    }
}

include '../layouts/header.php';
?>

<div class="card max-w-lg mx-auto" style="max-width: 600px; margin: 0 auto;">
    <h2 class="mb-3">Edit Profile</h2>

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
        <div class="form-group mb-4" style="position: relative;">
            <label>Cover Photo</label>
            <div style="height: 120px; border-radius: 8px; overflow: hidden; background: #1a1a1a; margin-bottom: 0.5rem;">
                <img id="cover-preview"
                    src="/block/public/images/cover_photos/<?= htmlspecialchars($profileUser['cover_photo'] ?: 'default-cover.jpg')?>"
                    style="width: 100%; height: 100%; object-fit: cover;"
                    onerror="this.src='/block/public/images/cover_photos/default-cover.jpg';">
            </div>
            <input type="file" name="cover_photo" accept="image/*"
                onchange="previewImage(this, 'cover-preview')">
        </div>

        <div class="form-group text-center mb-4">
            <label style="display: block; text-align: left;">Profile Picture</label>
            <img id="avatar-preview"
                src="/block/public/images/avatars/<?= htmlspecialchars($profileUser['avatar'] ?: 'user.jpg')?>"
                class="avatar avatar-lg mx-auto mb-2"
                style="display: block; margin: 0 auto; object-fit: cover; border-radius: 50%; width: 100px; height: 100px; border: 3px solid var(--border-color);"
                onerror="this.src='/block/public/images/avatars/user.jpg';">
            <input type="file" name="avatar" accept="image/*"
                onchange="previewImage(this, 'avatar-preview')">
        </div>
            <small class="text-muted" style="display: block; margin-top: 0.25rem; font-size: 0.8rem;">Maximum file size:
                2MB. Supported formats: JPEG, PNG, GIF, WebP.</small>
            <p id="avatar-size-warning"
                style="display:none; color:var(--danger); font-size:0.85rem; margin-top:0.25rem;">⚠️ File is too large.
                Maximum allowed size is 2MB.</p>
            <p id="avatar-type-warning"
                style="display:none; color:var(--danger); font-size:0.85rem; margin-top:0.25rem;">⚠️ Invalid file type.
                Only JPEG, PNG, GIF, and WebP are allowed.</p>
        </div>

        <div class="form-group">
            <label>Bio</label>
            <textarea name="bio" rows="4" id="bio-input" class="js-char-limit" data-limit="200"
                placeholder="Tell us about yourself..."><?= htmlspecialchars($profileUser['bio'] ?? '')?></textarea>
        </div>

        <button type="submit" id="update-btn" class="btn btn-primary btn-block mt-2">Update Profile</button>
        <a href="/block/views/user/profile.php" class="btn btn-secondary btn-block mt-2"
            style="text-align:center;">Cancel</a>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
    });

    function previewImage(input, previewId) {
        const preview = document.getElementById(previewId);
        const maxSize = 2 * 1024 * 1024;

        if (input.files && input.files[0]) {
            if (input.files[0].size > maxSize) {
                alert('File is too large. Max 2MB allowed.');
                input.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = function (e) {
                preview.src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>

<?php include '../layouts/footer.php'; ?>