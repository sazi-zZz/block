<?php
require_once '../../config/db.php';
require_once '../../includes/functions.php';
require_once '../../models/Block.php';

requireLogin();

$blockModel = new Block($pdo);

// Sanitize inputs
$search = trim($_GET['q'] ?? '');
$order = in_array($_GET['order'] ?? '', ['newest', 'oldest', 'most_users', 'least_users'])
    ? $_GET['order'] : 'newest';
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');

// Basic date sanity: ensure from <= to
if ($dateFrom && $dateTo && $dateFrom > $dateTo) {
    [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
}

$blocks = $blockModel->searchCreatedBlocks($_SESSION['user_id'], $search, $order, $dateFrom, $dateTo);
$total = count($blocks);
$hasFilters = $search || $dateFrom || $dateTo;

include '../layouts/header.php';
?>

<!-- Page Header -->
<div class="flex items-center justify-between mb-4" style="gap: 1rem; flex-wrap: wrap;">
    <div>
        <a href="<?= BASE_URL?>index.php"
            style="display: inline-flex; align-items: center; gap: 0.5rem; font-size: 0.8125rem; color: var(--gray-500); margin-bottom: 0.5rem; text-decoration: none;"
            onmouseover="this.style.color='var(--white)'" onmouseout="this.style.color='var(--gray-500)'">
            <i class="fa-solid fa-arrow-left" style="font-size: 0.7rem;"></i> Back to Home
        </a>
        <h1 style="font-size: 1.625rem; font-weight: 800; letter-spacing: -0.025em; margin: 0;">My Blocks</h1>
        <p class="text-muted" style="font-size: 0.875rem; margin-top: 0.25rem;">
            <strong style="color: var(--white);">
                <?= $total?>
            </strong>
            block
            <?= $total !== 1 ? 's' : ''?>
            <?php if ($hasFilters): ?><span style="color: var(--gray-600);"> — filtered</span>
            <?php
endif; ?>
        </p>
    </div>
    <a href="<?= BASE_URL?>views/blocks/create.php" class="btn btn-primary"
        style="border-radius: 999px; white-space: nowrap;">
        <i class="fa-solid fa-plus"></i> New Block
    </a>
</div>

<!-- Search & Filter Bar -->
<form method="GET" action="" id="filter-form">
    <div class="card" style="padding: 1.125rem 1.25rem; margin-bottom: 1.5rem;">

        <!-- Row 1: Search -->
        <div style="display: flex; gap: 0.75rem; margin-bottom: 0; align-items: center;">
            <div style="flex: 1; position: relative;">
                <i class="fa-solid fa-magnifying-glass"
                    style="position: absolute; left: 0.875rem; top: 50%; transform: translateY(-50%); color: var(--gray-500); font-size: 0.85rem; pointer-events: none;"></i>
                <input type="text" name="q" id="search-input" value="<?= htmlspecialchars($search)?>"
                    placeholder="Search by name, description or tags…"
                    style="padding-left: 2.5rem; border-radius: 999px; font-size: 0.9rem;" autocomplete="off">
            </div>
            <button type="submit" class="btn btn-primary"
                style="border-radius: 999px; white-space: nowrap;">Search</button>
            <button type="button" id="blocksFilterToggle"
                class="feed-filter-toggle-btn <?=($order !== 'newest' || $dateFrom || $dateTo) ? 'active' : ''?>"
                aria-expanded="<?=($order !== 'newest' || $dateFrom || $dateTo) ? 'true' : 'false'?>">
                <i class="fa-solid fa-sliders"></i>
                <span style="display: none;">Filters</span>
                <?php if ($order !== 'newest' || $dateFrom || $dateTo): ?>
                <span class="feed-filter-badge"></span>
                <?php
endif; ?>
            </button>
        </div>

        <!-- Filter Panel -->
        <div id="blocksFilterPanel"
            class="feed-filter-panel <?=($order !== 'newest' || $dateFrom || $dateTo) ? 'open' : ''?>"
            style="margin-top: 1rem; margin-bottom: 0;">
            <!-- Sort Section -->
            <div class="feed-filter-group">
                <p class="feed-filter-label">
                    <i class="fa-solid fa-arrow-up-wide-short"></i> Sort By
                </p>
                <div class="feed-sort-pills">
                    <?php
$sortOptions = [
    'newest' => ['icon' => 'fa-clock-rotate-left', 'label' => 'Newest First'],
    'oldest' => ['icon' => 'fa-clock-rotate-right', 'label' => 'Oldest First'],
    'most_users' => ['icon' => 'fa-users', 'label' => 'Most Members'],
    'least_users' => ['icon' => 'fa-user-minus', 'label' => 'Least Members'],
];
foreach ($sortOptions as $key => $opt):
    $isActive = ($order === $key);
?>
                    <button type="button"
                        onclick="document.getElementById('hiddenSortInput').value='<?= $key?>'; document.getElementById('filter-form').submit();"
                        class="feed-sort-pill <?= $isActive ? 'active' : ''?>">
                        <i class="fa-solid <?= $opt['icon']?>"></i>
                        <?= $opt['label']?>
                    </button>
                    <?php
endforeach; ?>
                </div>
            </div>

            <div class="feed-filter-separator"></div>

            <!-- Date Range Section -->
            <div class="feed-filter-group">
                <p class="feed-filter-label">
                    <i class="fa-solid fa-calendar-days"></i> Date Range
                </p>
                <div class="feed-date-range">
                    <div class="feed-date-field">
                        <label for="date_from" class="feed-date-label">From</label>
                        <input type="date" name="date_from" id="date_from" value="<?= htmlspecialchars($dateFrom)?>"
                            max="<?= date('Y-m-d')?>" class="feed-date-input">
                    </div>
                    <span class="feed-date-sep"><i class="fa-solid fa-arrow-right"></i></span>
                    <div class="feed-date-field">
                        <label for="date_to" class="feed-date-label">To</label>
                        <input type="date" name="date_to" id="date_to" value="<?= htmlspecialchars($dateTo)?>"
                            max="<?= date('Y-m-d')?>" class="feed-date-input">
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm feed-date-apply">
                        <i class="fa-solid fa-check"></i> Apply
                    </button>
                </div>
            </div>

            <input type="hidden" name="order" id="hiddenSortInput" value="<?= htmlspecialchars($order)?>">

            <!-- Footer row -->
            <div class="feed-filter-footer">
                <a href="?q=<?= urlencode($search)?>" class="feed-filter-reset">
                    <i class="fa-solid fa-rotate-left"></i> Clear sorting & dates
                </a>
            </div>
        </div>

        <!-- Active filter chips -->
        <?php if ($hasFilters): ?>
        <div class="feed-active-chips" style="margin-top: 1rem; margin-bottom: 0;">
            <span
                style="font-size: 0.78rem; color: var(--gray-500); font-weight: 600; display: inline-flex; align-items: center; margin-right: 0.25rem;">Active
                filters:</span>
            <?php if ($search): ?>
            <a href="?order=<?= urlencode($order)?>&date_from=<?= urlencode($dateFrom)?>&date_to=<?= urlencode($dateTo)?>"
                class="feed-chip" title="Remove search filter">
                <i class="fa-solid fa-magnifying-glass"></i>
                "
                <?= htmlspecialchars($search)?>"
                <i class="fa-solid fa-xmark"></i>
            </a>
            <?php
    endif; ?>
            <?php if ($order !== 'newest'): ?>
            <a href="?q=<?= urlencode($search)?>&date_from=<?= urlencode($dateFrom)?>&date_to=<?= urlencode($dateTo)?>"
                class="feed-chip" title="Remove sort filter">
                <i class="fa-solid <?= $sortOptions[$order]['icon']?>"></i>
                <?= htmlspecialchars($sortOptions[$order]['label'])?>
                <i class="fa-solid fa-xmark"></i>
            </a>
            <?php
    endif; ?>
            <?php if ($dateFrom || $dateTo): ?>
            <a href="?order=<?= urlencode($order)?>&q=<?= urlencode($search)?>" class="feed-chip"
                title="Remove date filter">
                <i class="fa-solid fa-calendar-days"></i>
                <?= $dateFrom ? date('M j, Y', strtotime($dateFrom)) : '…'?> &rarr;
                <?= $dateTo ? date('M j, Y', strtotime($dateTo)) : '…'?>
                <i class="fa-solid fa-xmark"></i>
            </a>
            <?php
    endif; ?>
            <a href="?" class="feed-filter-reset" style="margin-left: auto;">
                <i class="fa-solid fa-xmark"></i> Clear all
            </a>
        </div>
        <?php
endif; ?>

    </div>
</form>

<!-- Blocks Grid -->
<?php if (!empty($blocks)): ?>
<div class="blocks-grid">
    <?php foreach ($blocks as $block): ?>
    <div class="card block-card-detailed"
        style="margin-bottom: 0; padding: 1.5rem; display: flex; flex-direction: column; align-items: center; text-align: center; position: relative;">

        <!-- Edit shortcut -->
        <a href="<?= BASE_URL?>views/blocks/edit.php?id=<?= $block['id']?>"
            style="position: absolute; top: 0.75rem; right: 0.75rem; color: var(--gray-600); font-size: 0.85rem; padding: 0.25rem 0.5rem; border-radius: 6px; transition: all 0.2s;"
            title="Edit block"
            onmouseover="this.style.background='rgba(255,255,255,0.07)'; this.style.color='var(--white)'"
            onmouseout="this.style.background='transparent'; this.style.color='var(--gray-600)'">
            <i class="fa-solid fa-gear"></i>
        </a>

        <a href="<?= BASE_URL?>views/blocks/view.php?id=<?= $block['id']?>"
            style="display: flex; flex-direction: column; align-items: center; text-decoration: none; color: inherit; width: 100%;">
            <div class="avatar-glow mb-3">
                <img src="<?= BASE_URL?>public/images/block_icons/<?= htmlspecialchars($block['icon'] ?: 'default_block.jpg')?>"
                    class="avatar avatar-lg" style="border: 2px solid var(--border-color);"
                    onerror="this.src='<?= BASE_URL?>public/images/block_icons/default_block.jpg'; this.onerror=null;">
            </div>
            <h4 style="font-weight: 700; font-size: 0.9375rem; margin-bottom: 0.375rem; word-break: break-word;">
                <?= htmlspecialchars($block['name'])?>
            </h4>
            <?php if ($block['tags']): ?>
            <div style="margin-bottom: 0.5rem;">
                <span class="tag-pill">
                    <?= htmlspecialchars($block['tags'])?>
                </span>
            </div>
            <?php
        endif; ?>
            <p class="text-muted" style="font-size: 0.8rem; line-height: 1.4; margin-bottom: 0.75rem;">
                <?= htmlspecialchars(substr($block['description'], 0, 80))?>
                <?= strlen($block['description']) > 80 ? '…' : ''?>
            </p>
        </a>

        <!-- Footer: member count + created date -->
        <div
            style="font-size: 0.72rem; color: var(--gray-600); margin-top: auto; padding-top: 0.875rem; width: 100%; border-top: 1px solid var(--border-color); display: flex; align-items: center; justify-content: center; gap: 1rem;">
            <span style="display: flex; align-items: center; gap: 0.3rem;">
                <i class="fa-solid fa-users" style="font-size: 0.65rem;"></i>
                <?= number_format($block['member_count'])?> member
                <?= $block['member_count'] != 1 ? 's' : ''?>
            </span>
            <span style="display: flex; align-items: center; gap: 0.3rem;">
                <i class="fa-regular fa-calendar" style="font-size: 0.65rem;"></i>
                <?= date('M j, Y', strtotime($block['created_at']))?>
            </span>
        </div>
    </div>
    <?php
    endforeach; ?>
</div>

<?php
else: ?>
<!-- Empty state -->
<div class="card" style="text-align: center; padding: 4rem 2rem;">
    <?php if ($hasFilters): ?>
    <i class="fa-solid fa-filter-circle-xmark"
        style="font-size: 2.5rem; color: var(--gray-700); margin-bottom: 1rem; display: block;"></i>
    <h3 style="font-weight: 700; margin-bottom: 0.5rem;">No results found</h3>
    <p class="text-muted" style="margin-bottom: 1.5rem;">
        No blocks match your current filters.
    </p>
    <a href="?order=<?= urlencode($order)?>" class="btn btn-secondary" style="border-radius: 999px;">
        Clear all filters
    </a>
    <?php
    else: ?>
    <i class="fa-solid fa-shapes"
        style="font-size: 2.5rem; color: var(--gray-700); margin-bottom: 1rem; display: block;"></i>
    <h3 style="font-weight: 700; margin-bottom: 0.5rem;">No blocks yet</h3>
    <p class="text-muted" style="margin-bottom: 1.5rem;">You haven't created any blocks. Start your first community!</p>
    <a href="<?= BASE_URL?>views/blocks/create.php" class="btn btn-primary" style="border-radius: 999px;">
        <i class="fa-solid fa-plus"></i> Create your first block
    </a>
    <?php
    endif; ?>
</div>
<?php
endif; ?>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const btn = document.getElementById('blocksFilterToggle');
        const panel = document.getElementById('blocksFilterPanel');
        if (btn && panel) {
            btn.addEventListener('click', function () {
                const expanded = this.getAttribute('aria-expanded') === 'true';
                if (expanded) {
                    panel.classList.remove('open');
                    this.setAttribute('aria-expanded', 'false');
                    this.classList.remove('active');
                } else {
                    panel.classList.add('open');
                    this.setAttribute('aria-expanded', 'true');
                    this.classList.add('active');
                }
            });
        }

        const searchInput = document.getElementById('search-input');
        if (searchInput) {
            searchInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    document.getElementById('filter-form').submit();
                }
            });
        }

        // Auto-swap dates if from > to on blur
        const dateFrom = document.getElementById('date_from');
        const dateTo = document.getElementById('date_to');
        if (dateFrom && dateTo) {
            const syncDates = function () {
                if (dateFrom.value && dateTo.value && dateFrom.value > dateTo.value) {
                    [dateFrom.value, dateTo.value] = [dateTo.value, dateFrom.value];
                }
                dateTo.max = new Date().toISOString().slice(0, 10);
                dateFrom.max = dateTo.value || new Date().toISOString().slice(0, 10);
            };
            dateFrom.addEventListener('change', syncDates);
            dateTo.addEventListener('change', syncDates);
        }
    });
</script>

<?php include '../layouts/footer.php'; ?>