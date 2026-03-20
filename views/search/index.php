<?php
require_once '../../config/db.php';
require_once '../../includes/functions.php';
require_once '../../models/User.php';
require_once '../../models/Post.php';

requireLogin();

$keyword = isset($_GET['q']) ? sanitizeInput($_GET['q']) : '';
$tab = isset($_GET['tab']) && in_array($_GET['tab'], ['users', 'posts']) ? $_GET['tab'] : 'users';

$users = [];
$posts = [];

if ($keyword !== '') {
    if ($tab === 'users') {
        $userModel = new User($pdo);
        $users = $userModel->searchUsers($keyword);
    }
    else {
        $postModel = new Post($pdo);
        $posts = $postModel->searchPosts($_SESSION['user_id'], $keyword);
    }
}

include '../layouts/header.php';
?>

<div class="search-header mb-4">
    <h1 style="font-size: 1.875rem; font-weight: 700; margin-bottom: 0.25rem;">Search</h1>
    <p class="text-muted" style="font-size: 0.9375rem;">Find users and posts across the platform</p>
</div>

<div class="card mb-4">
    <form action="index.php" method="GET" class="flex gap-2">
        <input type="hidden" name="tab" value="<?= htmlspecialchars($tab)?>">
        <div class="flex-grow-1" style="flex: 1;">
            <div style="position: relative;">
                <i class="fa-solid fa-magnifying-glass"
                    style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                <input type="text" name="q" value="<?= htmlspecialchars($keyword)?>"
                    placeholder="Search for users or posts..." class="form-control"
                    style="padding-left: 2.5rem; width: 100%; box-sizing: border-box; border: 1px solid var(--border-color); border-radius: var(--radius); padding-top: 0.75rem; padding-bottom: 0.75rem;">
            </div>
        </div>
        <button type="submit" class="btn btn-primary px-4">Search</button>
    </form>
</div>

<!-- Tabs -->
<div class="flex gap-4 mb-4 border-b" style="border-bottom: 1px solid var(--border-color);">
    <a href="?q=<?= urlencode($keyword)?>&tab=users"
        class="pb-2 <?= $tab === 'users' ? 'active filter-btn' : 'text-muted'?>"
        style="text-decoration: none; <?= $tab === 'users' ? 'border-bottom: 2px solid var(--primary); color: var(--primary); font-weight: 600;' : ''?>">
        <i class="fa-solid fa-users mr-2"></i> Users
    </a>
    <a href="?q=<?= urlencode($keyword)?>&tab=posts"
        class="pb-2 <?= $tab === 'posts' ? 'active filter-btn' : 'text-muted'?>"
        style="text-decoration: none; <?= $tab === 'posts' ? 'border-bottom: 2px solid var(--primary); color: var(--primary); font-weight: 600;' : ''?>">
        <i class="fa-solid fa-file-lines mr-2"></i> Posts
    </a>
</div>

<!-- Results -->
<div class="search-results">
    <?php if ($keyword === ''): ?>
    <div class="text-center py-5">
        <i class="fa-solid fa-magnifying-glass mb-3" style="font-size: 3rem; color: var(--bg-tertiary);"></i>
        <p class="text-muted">Enter a keyword to start searching.</p>
    </div>
    <?php
else: ?>

    <?php if ($tab === 'users'): ?>
    <?php if (empty($users)): ?>
    <div class="text-center py-5">
        <p class="text-muted">No users found matching "
            <?= htmlspecialchars($keyword)?>".
        </p>
    </div>
    <?php
        else: ?>
    <div class="blocks-grid-large">
        <?php foreach ($users as $user): ?>
        <div class="card p-3 flex items-center gap-3">
            <a href="<?= BASE_URL?>views/user/profile.php?id=<?= $user['id']?>">
                <img src="<?= BASE_URL?>public/images/avatars/<?= htmlspecialchars($user['avatar'] ?: 'user.jpg')?>"
                    class="avatar avatar-md" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;"
                    onerror="this.src='<?= BASE_URL?>public/images/avatars/user.jpg'; this.onerror=null;">
            </a>
            <div class="flex-grow-1" style="overflow: hidden; flex: 1;">
                <a href="<?= BASE_URL?>views/user/profile.php?id=<?= $user['id']?>"
                    style="text-decoration: none; color: inherit;">
                    <h4 class="mb-1"
                        style="font-size: 1rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        <?= htmlspecialchars($user['username'])?>
                    </h4>
                </a>
                <?php if ($user['bio']): ?>
                <p class="text-muted"
                    style="font-size: 0.8125rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    <?= htmlspecialchars($user['bio'])?>
                </p>
                <?php
                endif; ?>
            </div>
        </div>
        <?php
            endforeach; ?>
    </div>
    <?php
        endif; ?>
    <?php
    endif; ?>

    <?php if ($tab === 'posts'): ?>
    <?php if (empty($posts)): ?>
    <div class="text-center py-5">
        <p class="text-muted">No posts found matching "
            <?= htmlspecialchars($keyword)?>".
        </p>
    </div>
    <?php
        else: ?>
    <?php
            $postModel = current(empty($posts) ? [] : [new Post($pdo)]);
?>
    <div class="feed-section">
        <?php foreach ($posts as $post): ?>
        <div class="card feed-post">
            <div class="flex items-center justify-between mb-3">
                <div style="display:flex; align-items:center; min-width:0;">
                    <a href="<?= BASE_URL?>views/user/profile.php?id=<?= $post['user_id']?>"
                        style="text-decoration:none; color:inherit; flex-shrink:0;">
                        <img src="<?= BASE_URL?>public/images/avatars/<?= htmlspecialchars($post['avatar'] ?: 'user.jpg')?>"
                            class="avatar avatar-sm"
                            style="margin-right: 0.75rem; border: 2px solid var(--border-color);"
                            onerror="this.src='<?= BASE_URL?>public/images/avatars/user.jpg'; this.onerror=null;">
                    </a>
                    <div style="min-width:0;">
                        <a href="<?= BASE_URL?>views/user/profile.php?id=<?= $post['user_id']?>"
                            style="text-decoration:none; color:inherit;">
                            <div style="font-weight: 600; font-size: 0.9375rem;">
                                <?= htmlspecialchars($post['username'])?>
                            </div>
                        </a>
                        <div style="font-size: 0.75rem;" class="text-muted">
                            in <a href="<?= BASE_URL?>views/blocks/view.php?id=<?= $post['block_id']?>"
                                style="font-weight: 500; color: var(--primary); text-decoration: none;"
                                onmouseover="this.style.textDecoration='underline'"
                                onmouseout="this.style.textDecoration='none'">
                                <?= htmlspecialchars($post['block_name'])?>
                            </a> •
                            <?= getDisplayTime($post['created_at'])?>
                        </div>
                    </div>
                </div>
            </div>

            <a href="<?= BASE_URL?>views/posts/view.php?id=<?= $post['id']?>"
                style="text-decoration:none; color:inherit;">
                <h4 class="mb-2" style="font-size: 1.125rem; font-weight: 700; line-height: 1.3;">
                    <?= htmlspecialchars($post['title'])?>
                </h4>
                <p class="mb-3 text-muted" style="font-size: 0.9375rem; line-height: 1.5;">
                    <?= nl2br(htmlspecialchars(substr($post['content'], 0, 180)))?>
                    <?= strlen($post['content']) > 180 ? '...' : ''?>
                </p>

                <?php if ($post['image']): ?>
                <div class="post-media mb-3"
                    style="border-radius: var(--radius); overflow: hidden; border: 1px solid var(--border-color);">
                    <?php if (isVideo($post['image'])): ?>
                    <video src="<?= BASE_URL?>public/images/post_images/<?= htmlspecialchars($post['image'])?>"
                        controls style="width: 100%; display: block;"></video>
                    <?php
                    else: ?>
                    <img src="<?= BASE_URL?>public/images/post_images/<?= htmlspecialchars($post['image'])?>"
                        style="width: 100%; display: block; max-height: 500px; object-fit: cover;">
                    <?php
                    endif; ?>
                </div>
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
                                class="avatar avatar-sm" style="margin-right: 0.5rem; width: 20px; height: 20px;" alt=""
                                onerror="this.src='<?= BASE_URL?>public/images/avatars/user.jpg'; this.onerror=null;">
                            <span style="font-size: 0.8rem; font-weight: 500;">
                                <?= htmlspecialchars($post['repost_username'] ?? 'Unknown User')?>
                            </span>
                            <span class="text-muted" style="font-size: 0.75rem; margin-left: 0.5rem;">in
                                <?= htmlspecialchars($post['repost_block_name'] ?? 'Unknown Block')?>
                            </span>
                        </div>
                        <h5 style="font-size: 1rem; font-weight: 600; margin-bottom: 0.25rem;">
                            <?= htmlspecialchars($post['repost_title'])?>
                        </h5>
                        <p class="text-muted" style="font-size: 0.85rem; line-height: 1.4; margin-bottom: 0;">
                            <?= nl2br(htmlspecialchars(substr($post['repost_content'], 0, 100)))?>
                            <?= strlen($post['repost_content']) > 100 ? '...' : ''?>
                        </p>
                    </a>
                </div>
                <?php
                endif; ?>
            </a>

            <?php $isLiked = isset($postModel) ? $postModel->isLiked($post['id'], $_SESSION['user_id']) : false; ?>
            <div class="flex items-center gap-4 pt-3 mt-1" style="border-top: 1px solid var(--border-color);">
                <button onclick="toggleLike(this, <?= $post['id']?>)"
                    class="post-action-btn <?= $isLiked ? 'liked' : ''?>"
                    style="background:none; border:none; cursor:pointer; color: <?= $isLiked ? 'var(--danger)' : 'var(--text-muted)'?>; padding:0.5rem; display:flex; align-items:center; gap:0.5rem; transition: all 0.2s; border-radius: 8px;">
                    <i class="<?= $isLiked ? 'fa-solid' : 'fa-regular'?> fa-heart" style="font-size: 1.1rem;"></i>
                    <span class="like-count" style="font-weight: 600;">
                        <?= $post['like_count']?>
                    </span>
                </button>
                <a href="<?= BASE_URL?>views/posts/view.php?id=<?= $post['id']?>" class="post-action-btn"
                    style="text-decoration:none; color:var(--text-muted); padding:0.5rem; display:flex; align-items:center; gap:0.5rem; transition: all 0.2s; border-radius: 8px;">
                    <i class="fa-regular fa-comment" style="font-size: 1.1rem;"></i>
                    <span style="font-weight: 600;">
                        <?= $post['comment_count']?>
                    </span>
                </a>
                <a href="<?= BASE_URL?>views/posts/create.php?repost_id=<?= $post['id']?>" class="post-action-btn"
                    title="Repost"
                    style="background:none; border:none; cursor:pointer; color: var(--text-muted); padding:0.5rem; margin-left: auto; text-decoration:none; display:flex; align-items:center;">
                    <i class="fa-solid fa-retweet" style="font-size: 1.1rem;"></i>
                </a>
            </div>
        </div>
        <?php
            endforeach; ?>
    </div>
    <?php
        endif; ?>
    <?php
    endif; ?>

    <?php
endif; ?>
</div>

<?php include '../layouts/footer.php'; ?>