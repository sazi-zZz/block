<?php
require_once '../../config/db.php';
require_once '../../includes/functions.php';
require_once '../../models/Block.php';
require_once '../../models/Post.php';
require_once '../../models/Notification.php';

requireLogin();

$id = $_GET['id'] ?? null;
if (!$id)
    redirect('index.php');

$blockModel = new Block($pdo);
$postModel = new Post($pdo);

$block = $blockModel->getById($id);
if (!$block)
    redirect('index.php');

$isMember = $blockModel->isMember($id, $_SESSION['user_id']);
$feed = $postModel->getFeed($_SESSION['user_id'], $id, 'newest', null, null, null, 20, 0);
$members = $blockModel->getMembers($id);

// Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'join') {
            if ($blockModel->addMember($id, $_SESSION['user_id'])) {
                $notificationModel = new Notification($pdo);
                $username = $_SESSION['username'] ?? 'Someone';
                if ($block['creator_id'] != $_SESSION['user_id']) {
                    $notificationModel->create($block['creator_id'], 'join', $id, "$username joined your block {$block['name']}.");
                }
            }
        }
        elseif ($_POST['action'] === 'leave') {
            // Creators cannot leave simply, check if creator
            if ($isMember['role'] !== 'creator') {
                $blockModel->removeMember($id, $_SESSION['user_id']);
            }
        }
        elseif ($_POST['action'] === 'delete') {
            if ($isMember && $isMember['role'] === 'creator') {
                $blockModel->delete($id);
                redirect('index.php');
            }
        }
    }
    redirect('views/blocks/view.php?id=' . $id);
}

include '../layouts/header.php';
?>

<div class="card mb-4 text-center block-header-card"
    style="padding: 2.5rem 1.5rem; overflow: hidden; position: relative;">
    <div
        style="position: absolute; top:0; left:0; width: 100%; height: 5px; background: linear-gradient(to right, var(--primary), var(--accent));">
    </div>

    <div class="avatar-glow mb-4 mx-auto" style="width: 100px; height: 100px;">
        <img src="<?= BASE_URL?>public/images/block_icons/<?= htmlspecialchars($block['icon'] ?: 'default_block.jpg')?>"
            class="avatar avatar-lg"
            style="width: 100px; height: 100px; border: 4px solid var(--bg-secondary); box-shadow: 0 0 20px rgba(255,255,255,0.06);"
            onerror="this.src='<?= BASE_URL?>public/images/block_icons/default_block.jpg'; this.onerror=null;">
    </div>

    <h1 style="font-size: 2.25rem; font-weight: 800; letter-spacing: -0.025em; margin-bottom: 0.5rem;">
        <?= htmlspecialchars($block['name'])?>
    </h1>

    <p class="text-muted mb-4"
        style="max-width: 600px; margin-left: auto; margin-right: auto; line-height: 1.6; font-size: 1.0625rem;">
        <?= nl2br(htmlspecialchars($block['description']))?>
    </p>

    <div class="flex flex-wrap justify-center items-center gap-4 mb-2">
        <button type="button" onclick="document.getElementById('membersModal').style.display='flex'"
            class="flex items-center gap-2 px-4 py-2 bg-tertiary rounded-full"
            style="background: var(--bg-tertiary); border: 1px solid var(--border-color); cursor: pointer; border-radius: 9999px; font-size: 0.875rem; color: inherit; transition: all 0.2s;"
            onmouseover="this.style.background='var(--border-color)'"
            onmouseout="this.style.background='var(--bg-tertiary)'">
            <i class="fa-solid fa-users text-primary"></i>
            <span style="font-weight: 600;">
                <?= count($members)?>
            </span>
            <span class="text-muted">Members</span>
        </button>

        <?php if ($isMember): ?>
        <div class="flex gap-2 items-center">
            <span class="text-xs uppercase tracking-wider font-bold"
                style="padding: 0.375rem 0.75rem; background: rgba(255,255,255,0.06); color: var(--white); border-radius: 6px; border: 1px solid rgba(255,255,255,0.12);">
                <?= htmlspecialchars($isMember['role'])?>
            </span>

            <?php if ($isMember['role'] !== 'creator'): ?>
            <form method="POST" style="display:inline;">
                <input type="hidden" name="action" value="leave">
                <button type="submit" class="btn btn-secondary btn-sm" style="border-radius: 9999px;">Leave</button>
            </form>
            <?php
    else: ?>
            <div class="flex gap-2">
                <a href="<?= BASE_URL?>views/blocks/edit.php?id=<?= $id?>" class="btn btn-secondary btn-sm"
                    style="border-radius: 9999px;">
                    <i class="fa-solid fa-gear mr-1"></i> Edit
                </a>
                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this block?');"
                    style="display:inline;">
                    <input type="hidden" name="action" value="delete">
                    <button type="submit" class="btn btn-danger btn-sm" style="border-radius: 9999px;">Delete</button>
                </form>
            </div>
            <?php
    endif; ?>
        </div>
        <?php
else: ?>
        <form method="POST" style="display:inline;">
            <input type="hidden" name="action" value="join">
            <button type="submit" class="btn btn-primary"
                style="padding: 0.625rem 2rem; border-radius: 9999px; font-weight: 700;">
                Join Community
            </button>
        </form>
        <?php
endif; ?>
    </div>
</div>

<?php if ($isMember): ?>
<div class="feed-section">
    <div class="flex justify-between items-center mb-5">
        <div class="flex items-center gap-2">
            <i class="fa-solid fa-list-ul text-primary"></i>
            <h3 style="font-size: 1.25rem; font-weight: 700;">Block Feed</h3>
        </div>
        <a href="<?= BASE_URL?>views/posts/create.php?block_id=<?= $id?>" class="btn btn-primary"
            style="padding: 0.625rem 1.25rem; border-radius: 12px; font-size: 0.9375rem;">
            <i class="fa-solid fa-pen-nib mr-2"></i> Write Post
        </a>
    </div>

    <div class="posts-list">
        <div id="live-feed-container">
            <?php foreach ($feed as $post): ?>
            <?php include '../posts/_post_card_feed.php'; ?>
            <?php
    endforeach; ?>
        </div>

        <?php if (empty($feed)): ?>
        <div class="card text-center py-5">
            <i class="fa-solid fa-comment-slash mb-3" style="font-size: 2.5rem; color: var(--bg-tertiary);"></i>
            <p class="text-muted">No posts in this block yet. Be the first to start the conversation!</p>
        </div>
        <?php
    endif; ?>
    </div>
</div>
<?php
else: ?>
<div class="card text-center py-5 px-4">
    <div
        style="width: 64px; height: 64px; background: rgba(255,255,255,0.05); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
        <i class="fa-solid fa-lock text-primary" style="font-size: 1.5rem;"></i>
    </div>
    <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 0.75rem;">Join this Community</h3>
    <p class="text-muted mb-0">Join this block to view its exclusive content and participate in discussions.</p>
</div>
<?php
endif; ?>

<!-- ── Members Modal ────────────────── -->
<div id="membersModal" class="modal-overlay" onclick="this.style.display='none'"
    style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(5px);">
    <div class="card" onclick="event.stopPropagation()"
        style="width: 90%; max-width: 480px; max-height: 80vh; display: flex; flex-direction: column; padding: 0; position: relative; animation: slideUpFade 0.3s ease forwards; background: var(--bg-secondary); border: 1px solid var(--border-color);">

        <!-- Header -->
        <div class="flex justify-between items-center"
            style="padding: 1.5rem; border-bottom: 1px solid var(--border-color);">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-users text-primary"></i>
                <h3 style="font-size: 1.25rem; font-weight: 700; margin: 0;">Members</h3>
                <span
                    style="font-size: 0.8rem; font-weight: 500; color: var(--gray-400); background: var(--bg-tertiary); padding: 0.1rem 0.6rem; border-radius: 999px;">
                    <?= count($members)?>
                </span>
            </div>
            <button onclick="document.getElementById('membersModal').style.display='none'" class="text-muted"
                style="background: none; border: none; cursor: pointer; font-size: 1.25rem; transition: color 0.2s;"
                onmouseover="this.style.color='var(--danger)'" onmouseout="this.style.color='var(--text-muted)'">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Search bar -->
        <div style="padding: 1rem 1.5rem; border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.2);">
            <div style="position: relative;">
                <i class="fa-solid fa-magnifying-glass"
                    style="position: absolute; left: 1.125rem; top: 50%; transform: translateY(-50%); color: var(--gray-500); font-size: 0.875rem; pointer-events: none;"></i>
                <input type="text" id="member-search" placeholder="Search members..."
                    oninput="filterMembers(this.value)"
                    style="width: 100%; padding: 0.75rem 1rem 0.75rem 2.75rem; font-size: 0.9375rem; border-radius: 999px; border: 1px solid var(--border-color); background: var(--bg-color); color: var(--text-color); outline: none; transition: border-color 0.2s;"
                    onfocus="this.style.borderColor='var(--primary)'"
                    onblur="this.style.borderColor='var(--border-color)'">
            </div>
        </div>

        <!-- Member list -->
        <div style="padding: 1rem 1.5rem; overflow-y: auto; flex: 1;">
            <div id="members-list" style="display: flex; flex-direction: column; gap: 0.5rem;">
                <?php foreach ($members as $member): ?>
                <a href="<?= BASE_URL?>views/user/profile.php?id=<?= $member['id']?>" class="member-row"
                    data-username="<?= strtolower(htmlspecialchars($member['username']))?>"
                    style="display: flex; align-items: center; gap: 0.875rem; padding: 0.625rem 0.75rem; border-radius: var(--radius-sm); text-decoration: none; color: inherit; transition: background 0.18s;">
                    <img src="<?= BASE_URL?>public/images/avatars/<?= htmlspecialchars($member['avatar'] ?? 'user.jpg')?>"
                        class="avatar avatar-sm" style="border: 1px solid var(--border-color); flex-shrink: 0;"
                        onerror="this.src='<?= BASE_URL?>public/images/avatars/user.jpg'; this.onerror=null;">
                    <span
                        style="font-weight: 600; font-size: 0.9375rem; flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        <?= htmlspecialchars($member['username'])?>
                    </span>
                    <span
                        style="font-size: 0.65rem; font-weight: 700; letter-spacing: 0.07em; text-transform: uppercase; padding: 0.2rem 0.6rem; border-radius: 999px; <?= $member['role'] === 'creator' ? 'background: rgba(255,255,255,0.12); color: var(--white); border: 1px solid rgba(255,255,255,0.2);' : 'background: rgba(255,255,255,0.04); color: var(--gray-400); border: 1px solid var(--border-color);'?>">
                        <?= htmlspecialchars($member['role'])?>
                    </span>
                </a>
                <?php
endforeach; ?>
            </div>

            <!-- No results state -->
            <div id="members-no-results"
                style="display: none; text-align: center; padding: 2rem 0; color: var(--gray-500);">
                <i class="fa-solid fa-user-slash" style="font-size: 1.5rem; margin-bottom: 0.5rem; display: block;"></i>
                No members found matching your search.
            </div>

            <?php if (empty($members)): ?>
            <div style="text-align: center; padding: 2rem 0; color: var(--gray-500);">
                <i class="fa-solid fa-users" style="font-size: 1.5rem; margin-bottom: 0.5rem; display: block;"></i>
                No members yet.
            </div>
            <?php
endif; ?>
        </div>
    </div>
</div>

<style>
    .member-row:hover {
        background: rgba(255, 255, 255, 0.04);
    }
</style>

<script>
    function filterMembers(query) {
        const rows = document.querySelectorAll('.member-row');
        const noResults = document.getElementById('members-no-results');
        const q = query.trim().toLowerCase();
        let visible = 0;

        rows.forEach(row => {
            const name = row.getAttribute('data-username') || '';
            const match = name.includes(q);
            row.style.display = match ? 'flex' : 'none';
            if (match) visible++;
        });

        noResults.style.display = (visible === 0 && q.length > 0) ? 'block' : 'none';
    }
</script>

<?php include '../layouts/footer.php'; ?>