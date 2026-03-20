<?php
require_once '../../config/db.php';
require_once '../../includes/functions.php';
require_once '../../models/User.php';
require_once '../../models/Post.php';
require_once '../../models/Block.php';
require_once '../../models/Notification.php';

requireLogin();

$id = $_GET['id'] ?? $_SESSION['user_id'];
$userModel = new User($pdo);
$profileUser = $userModel->getUserById($id);

if (!$profileUser)
    redirect('index.php');

$followers = $userModel->countFollowers($id);
$following = $userModel->countFollowing($id);

$tab = $_GET['tab'] ?? 'posts';

if ($tab === 'posts') {
    $postModel = new Post($pdo);
    $stmt = $pdo->prepare("
        SELECT posts.*, users.username, users.avatar, blocks.name as block_name,
               (SELECT COUNT(*) FROM comments WHERE post_id = posts.id) as comment_count,
               (SELECT COUNT(*) FROM likes WHERE post_id = posts.id) as like_count,
               rp.title as repost_title, rp.content as repost_content, rp.image as repost_image, rpu.username as repost_username, rpb.name as repost_block_name, rp.id as rp_id, rpu.id as rp_user_id, rpb.id as rp_block_id
        FROM posts
        JOIN users ON posts.user_id = users.id
        JOIN blocks ON posts.block_id = blocks.id
        LEFT JOIN posts rp ON posts.repost_id = rp.id
        LEFT JOIN users rpu ON rp.user_id = rpu.id
        LEFT JOIN blocks rpb ON rp.block_id = rpb.id
        WHERE posts.user_id = :profile_id
        AND (
            posts.privacy = 'public'
            OR posts.user_id = :viewer
            OR (posts.privacy = 'followers' AND EXISTS (SELECT 1 FROM followers WHERE follower_id = :viewer AND following_id = posts.user_id))
            OR (posts.privacy = 'following' AND EXISTS (SELECT 1 FROM followers WHERE follower_id = posts.user_id AND following_id = :viewer))
            OR (posts.privacy = 'followers_and_following' AND (
                EXISTS (SELECT 1 FROM followers WHERE follower_id = :viewer AND following_id = posts.user_id)
                OR EXISTS (SELECT 1 FROM followers WHERE follower_id = posts.user_id AND following_id = :viewer)
            ))
        )
        ORDER BY posts.created_at DESC
    ");
    $stmt->execute(['profile_id' => $id, 'viewer' => $_SESSION['user_id']]);
    $userPosts = $stmt->fetchAll();
}
elseif ($tab === 'followers') {
    $followersList = $userModel->getFollowers($id);
}
elseif ($tab === 'following') {
    $followingList = $userModel->getFollowing($id);
}
elseif ($tab === 'blocks') {
    $blockModel = new Block($pdo);
    $userBlocks = $blockModel->getUserBlocks($id);
}
elseif ($tab === 'created_blocks') {
    $blockModel = new Block($pdo);
    $createdBlocks = $blockModel->getCreatedBlocks($id);
}

$isOwnProfile = ($id == $_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'follow') {
            if ($userModel->follow($_SESSION['user_id'], $id)) {
                $notificationModel = new Notification($pdo);
                $username = $_SESSION['username'] ?? 'Someone';
                $notificationModel->create($id, 'follow', $_SESSION['user_id'], "$username followed you.");
            }
        }
        elseif ($_POST['action'] === 'unfollow') {
            $userModel->unfollow($_SESSION['user_id'], $id);
        }
    }
    redirect('views/user/profile.php?id=' . $id . '&tab=' . $tab);
}

$isFollowing = !$isOwnProfile ? $userModel->isFollowing($_SESSION['user_id'], $id) : false;

include '../layouts/header.php';
?>

<style>
    /* ── Profile Page — Mobile Responsive ─────────────────────────── */

    .profile-avatar {
        display: block;
        width: 100px;
        height: 100px;
        border-radius: 50%;
        object-fit: cover;
        margin: 0 auto 1rem auto;
        border: 4px solid var(--bg-card);
        background: var(--bg-card);
    }

    @media (min-width: 480px) {
        .profile-avatar {
            width: 130px;
            height: 130px;
        }
    }

    .profile-cover {
        width: 100%;
        height: 160px;
        object-fit: cover;
        background: #1a1a1a;
    }

    @media (min-width: 768px) {
        .profile-cover {
            height: 220px;
        }
    }

    .profile-stats {
        display: flex;
        justify-content: center;
        gap: 2.5rem;
        margin: 0.75rem 0 1.25rem;
        flex-wrap: wrap;
    }

    .profile-stat-link {
        text-decoration: none;
        color: inherit;
        text-align: center;
        line-height: 1.3;
        transition: opacity 0.15s;
    }

    .profile-stat-link:hover {
        opacity: 0.8;
    }

    .profile-stat-link strong {
        display: block;
        font-size: 1.25rem;
        font-weight: 700;
    }

    .profile-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        justify-content: center;
        margin-top: 1rem;
    }

    .profile-actions .btn {
        flex: 1 1 130px;
        max-width: 200px;
        min-width: 110px;
    }

    /* Tab pills */
    .profile-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
        justify-content: center;
        margin-bottom: 1rem;
    }

    .profile-tabs .btn {
        flex: 1 1 auto;
        min-width: 0;
    }

    @media (max-width: 400px) {
        .profile-tabs .btn {
            font-size: 0.7rem;
            padding: 0.3rem 0.5rem;
        }

        .profile-tabs .btn i {
            display: none;
        }
    }

    /* User list items (followers / following) */
    .user-list-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem;
        border-radius: var(--radius);
        text-decoration: none;
        color: inherit;
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        margin-bottom: 0.5rem;
        transition: border-color 0.2s, background 0.2s;
    }

    .user-list-item:hover {
        background: var(--bg-tertiary);
        border-color: var(--border-hover);
    }

    .user-list-item .info {
        min-width: 0;
        flex: 1;
    }

    .user-list-item h5 {
        margin: 0;
        font-size: 0.95rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .user-list-item small {
        color: var(--text-muted);
        font-size: 0.8rem;
        display: block;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
</style>

<!-- Profile Header -->
<div class="card mb-3 p-0 overflow-hidden">
    <div style="position: relative;">
        <img src="public/images/cover_photos/<?= htmlspecialchars($profileUser['cover_photo'] ?: 'default-cover.jpg')?>"
            class="profile-cover" onerror="this.src='public/images/cover_photos/default-cover.jpg'; this.onerror=null;"
            alt="Cover Photo">
    </div>

    <div style="margin-top: -65px; padding: 0 1.25rem 1.5rem; position: relative; z-index: 2;">
        <img src="public/images/avatars/<?= htmlspecialchars($profileUser['avatar'] ?: 'user.jpg')?>"
            class="profile-avatar mx-auto" onerror="this.src='public/images/avatars/user.jpg'; this.onerror=null;"
            alt="<?= htmlspecialchars($profileUser['username'])?>">

        <div class="text-center">

            <h2 style="font-size: clamp(1.2rem, 4vw, 1.75rem); margin-bottom: 0.25rem;">
                <?= htmlspecialchars($profileUser['username'])?>
            </h2>
            <p class="text-muted" style="max-width: 420px; margin: 0 auto; font-size: 0.9rem;">
                <?= htmlspecialchars($profileUser['bio'] ?? 'No bio available.')?>
            </p>
        </div>

    <!-- Stats -->
    <div class="profile-stats">
        <a href="?id=<?= $id?>&tab=followers" class="profile-stat-link">
            <strong>
                <?= $followers?>
            </strong>
            <span class="text-muted">Followers</span>
        </a>
        <a href="?id=<?= $id?>&tab=following" class="profile-stat-link">
            <strong>
                <?= $following?>
            </strong>
            <span class="text-muted">Following</span>
        </a>
    </div>

    <!-- Action Buttons -->
    <div class="profile-actions">
        <?php if ($isOwnProfile): ?>
        <a href="views/user/edit.php" class="btn btn-secondary">
            <i class="fa-solid fa-pen"></i> Edit Profile
        </a>
        <a href="views/auth/logout.php" class="btn btn-danger mobile-only">
            <i class="fa-solid fa-right-from-bracket"></i> Logout
        </a>
        <?php
else: ?>
        <form method="POST" style="display:contents;">
            <?php if ($isFollowing): ?>
            <input type="hidden" name="action" value="unfollow">
            <button type="submit" class="btn btn-secondary">Unfollow</button>
            <?php
    else: ?>
            <input type="hidden" name="action" value="follow">
            <button type="submit" class="btn btn-primary">Follow</button>
            <?php
    endif; ?>
        </form>
        <a href="views/chat/index.php?user_id=<?= $id?>" class="btn btn-secondary">
            <i class="fa-solid fa-message"></i> Message
        </a>
        <?php
endif; ?>
    </div>
</div>

<!-- Tab Navigation -->
<div class="profile-tabs mb-3">
    <a href="?id=<?= $id?>&tab=posts" class="btn btn-sm <?= $tab === 'posts' ? 'btn-primary' : 'btn-secondary'?>">
        <i class="fa-solid fa-file-lines"></i> Posts
    </a>
    <a href="?id=<?= $id?>&tab=blocks" class="btn btn-sm <?= $tab === 'blocks' ? 'btn-primary' : 'btn-secondary'?>">
        <i class="fa-solid fa-shapes"></i> Joined
    </a>
    <a href="?id=<?= $id?>&tab=created_blocks"
        class="btn btn-sm <?= $tab === 'created_blocks' ? 'btn-primary' : 'btn-secondary'?>">
        <i class="fa-solid fa-hammer"></i> Created
    </a>
</div>

<!-- Tab Content -->
<div class="card">
    <?php if ($tab === 'posts'): ?>
    <h3 class="mb-3">Posts by
        <?= htmlspecialchars($profileUser['username'])?>
    </h3>
    <div id="live-feed-container">
        <?php foreach ($userPosts as $post): ?>
        <?php include '../posts/_post_card_feed.php'; ?>
        <?php
    endforeach; ?>
    </div>
    <?php if (empty($userPosts)): ?>
    <p class="text-muted text-center pt-2">No posts yet.</p>
    <?php
    endif; ?>

    <?php
elseif ($tab === 'followers'): ?>
    <h3 class="mb-3">Followers</h3>
    <?php foreach ($followersList as $user): ?>
    <a href="?id=<?= $user['id']?>" class="user-list-item">
        <img src="public/images/avatars/<?= htmlspecialchars($user['avatar'] ?: 'user.jpg')?>"
            class="avatar avatar-sm" onerror="this.src='public/images/avatars/user.jpg'; this.onerror=null;">
        <div class="info">
            <h5>
                <?= htmlspecialchars($user['username'])?>
            </h5>
            <small>
                <?= htmlspecialchars(substr($user['bio'] ?? '', 0, 60))?>
            </small>
        </div>
    </a>
    <?php
    endforeach; ?>
    <?php if (empty($followersList)): ?>
    <p class="text-muted text-center pt-2">No followers yet.</p>
    <?php
    endif; ?>

    <?php
elseif ($tab === 'following'): ?>
    <h3 class="mb-3">Following</h3>
    <?php foreach ($followingList as $user): ?>
    <a href="?id=<?= $user['id']?>" class="user-list-item">
        <img src="public/images/avatars/<?= htmlspecialchars($user['avatar'] ?: 'user.jpg')?>"
            class="avatar avatar-sm" onerror="this.src='public/images/avatars/user.jpg'; this.onerror=null;">
        <div class="info">
            <h5>
                <?= htmlspecialchars($user['username'])?>
            </h5>
            <small>
                <?= htmlspecialchars(substr($user['bio'] ?? '', 0, 60))?>
            </small>
        </div>
    </a>
    <?php
    endforeach; ?>
    <?php if (empty($followingList)): ?>
    <p class="text-muted text-center pt-2">Not following anyone yet.</p>
    <?php
    endif; ?>

    <?php
elseif ($tab === 'blocks'): ?>
    <h3 class="mb-3">Blocks Joined</h3>
    <div class="blocks-grid">
        <?php foreach ($userBlocks as $block): ?>
        <a href="views/blocks/view.php?id=<?= $block['id']?>" class="card bg-secondary"
            style="margin-bottom: 0; text-align: center; text-decoration: none; color: inherit;">
            <img src="public/images/block_icons/<?= htmlspecialchars($block['icon'] ?: 'default_block.jpg')?>"
                class="avatar avatar-lg mb-2 mx-auto" alt="Block Icon" style="display:block;">
            <h4>
                <?= htmlspecialchars($block['name'])?>
            </h4>
        </a>
        <?php
    endforeach; ?>
    </div>
    <?php if (empty($userBlocks)): ?>
    <p class="text-muted text-center pt-2">Has not joined any blocks yet.</p>
    <?php
    endif; ?>

    <?php
elseif ($tab === 'created_blocks'): ?>
    <h3 class="mb-3">Blocks Created by
        <?= htmlspecialchars($profileUser['username'])?>
    </h3>
    <div class="blocks-grid">
        <?php foreach ($createdBlocks as $block): ?>
        <a href="views/blocks/view.php?id=<?= $block['id']?>" class="card bg-secondary"
            style="margin-bottom: 0; text-align: center; text-decoration: none; color: inherit;">
            <img src="public/images/block_icons/<?= htmlspecialchars($block['icon'] ?: 'default_block.jpg')?>"
                class="avatar avatar-lg mb-2 mx-auto" alt="Block Icon" style="display:block;">
            <h4>
                <?= htmlspecialchars($block['name'])?>
            </h4>
        </a>
        <?php
    endforeach; ?>
    </div>
    <?php if (empty($createdBlocks)): ?>
    <p class="text-muted text-center pt-2">Has not created any blocks yet.</p>
    <?php
    endif; ?>

    <?php
endif; ?>
</div>

<?php include '../layouts/footer.php'; ?>

<script>
    (function () {
        let lastPostEl = document.querySelector('.feed-post');
        let lastPostId = lastPostEl ? parseInt(lastPostEl.getAttribute('data-post-id')) : 0;
        const profileUserId = <?= json_encode($id)?>;

        setInterval(() => {
            fetch(`<?= BASE_URL?>api/feed_live.php?since_id=${lastPostId}&user_id=${profileUserId}`)
                .then(res => res.text())
                .then(html => {
                    html = html.trim();
                    if (html.length > 0) {
                        const container = document.getElementById('live-feed-container');
                        if (container) {
                            container.insertAdjacentHTML('afterbegin', html);
                            const newFirst = document.querySelector('.feed-post');
                            if (newFirst) {