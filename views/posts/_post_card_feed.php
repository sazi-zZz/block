<div class="card feed-post" data-post-id="<?= $post['id']?>">
    <div class="flex items-center justify-between mb-3">
        <div style="display:flex; align-items:center; min-width:0;">
            <a href="<?= BASE_URL?>views/user/profile.php?id=<?= $post['user_id']?>"
                style="text-decoration:none; color:inherit; display:flex; align-items:center; flex-shrink:0;">
                <img src="<?= BASE_URL?>public/images/avatars/<?= htmlspecialchars($post['avatar'] ?: 'user.jpg')?>"
                    class="avatar avatar-sm" style="margin-right: 0.75rem; border: 2px solid var(--border-color);"
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
        <button class="text-muted" style="background: none; border: none; cursor: pointer;">
            <i class="fa-solid fa-ellipsis"></i>
        </button>
    </div>

    <a href="<?= BASE_URL?>views/posts/view.php?id=<?= $post['id']?>" style="text-decoration:none; color:inherit;">
        <h4 class="mb-2" style="font-size: 1.125rem; font-weight: 700; line-height: 1.3;">
            <?= htmlspecialchars($post['title'])?>
        </h4>
        <p class="mb-3 text-muted" style="font-size: 0.9375rem; line-height: 1.5;">
            <?= nl2br(htmlspecialchars(substr($post['content'], 0, 180)))?>
            <?= strlen($post['content']) > 180 ? '...' : ''?>
        </p>

        <?php if (!empty($post['ai_percentage']) && (int)$post['ai_percentage'] > 0): ?>
        <div
            style="font-size: 0.75rem; color: var(--gray-500); margin-bottom: 0.75rem; display: inline-flex; align-items: center; background: var(--bg-secondary); padding: 0.15rem 0.5rem; border-radius: 12px; border: 1px solid var(--border-color);">
            <i class="fa-solid fa-robot" style="margin-right: 0.35rem; font-size: 0.7rem;"></i>
            <?= $post['ai_percentage']?>% AI Content
        </div>
        <?php
endif; ?>

        <?php if ($post['image']): ?>
        <div class="post-media mb-3"
            style="border-radius: var(--radius); overflow: hidden; border: 1px solid var(--border-color);">
            <?php if (isVideo($post['image'])): ?>
            <video src="<?= BASE_URL?>public/images/post_images/<?= htmlspecialchars($post['image'])?>" controls
                style="width: 100%; display: block;"></video>
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

    <?php $isLiked = $postModel->isLiked($post['id'], $_SESSION['user_id']); ?>
    <div class="flex items-center gap-4 pt-3 mt-1" style="border-top: 1px solid var(--border-color);">
        <button onclick="toggleLike(this, <?= $post['id']?>)" class="post-action-btn <?= $isLiked ? 'liked' : ''?>"
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