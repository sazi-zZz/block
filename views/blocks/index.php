<?php
require_once '../../config/db.php';
require_once '../../includes/functions.php';
require_once '../../models/Block.php';

requireLogin();

$blockModel = new Block($pdo);

// Sanitize inputs
$sort = in_array($_GET['sort'] ?? '', ['newest', 'oldest', 'most_users', 'least_users'])
    ? $_GET['sort'] : 'newest';
$search = trim($_GET['q'] ?? '');
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');
$filterJoined = isset($_GET['joined']) && $_GET['joined'] == '1';

// Basic date sanity: ensure from <= to
if ($dateFrom && $dateTo && $dateFrom > $dateTo) {
    [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
}

$blocks = $blockModel->getAllFiltered($_SESSION['user_id'], $sort, $search, $dateFrom, $dateTo, $filterJoined);
$hasFilters = $search || $dateFrom || $dateTo || $filterJoined;

include '../layouts/header.php';
?>

<div class="discovery-header flex justify-between items-center mb-5">
    <div>
        <h1 style="font-size: 1.875rem; font-weight: 700; margin-bottom: 0.25rem;">Communities</h1>
        <p class="text-muted" style="font-size: 0.9375rem;">Join blocks that match your interests</p>
    </div>
    <a href="<?= BASE_URL?>views/blocks/create.php" class="btn btn-primary"
        style="padding: 0.625rem 1.25rem; border-radius: 12px;">
        <i class="fa-solid fa-plus mr-2"></i> Create Block
    </a>
</div>

<!-- Search + Filter Bar -->
<div class="card mb-5" style="padding: 1.125rem 1.25rem;">
    <form method="GET" action="index.php" id="explore-form">

        <!-- Row 1: Search -->
        <div style="display: flex; gap: 0.625rem; margin-bottom: 0;">
            <div style="position: relative; flex: 1;">
                <i class="fa-solid fa-magnifying-glass"
                    style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                <input type="text" name="q" id="search-input" placeholder="Search communities by name or tags..."
                    value="<?= htmlspecialchars($search)?>" style="padding-left: 2.75rem;">
            </div>
            <button type="submit" class="btn btn-primary" style="white-space: nowrap;">Search</button>
            <button type="button" id="blocksFilterToggle"
                class="feed-filter-toggle-btn <?=($sort !== 'newest' || $dateFrom || $dateTo || $filterJoined) ? 'active' : ''?>"
                aria-expanded="<?=($sort !== 'newest' || $dateFrom || $dateTo || $filterJoined) ? 'true' : 'false'?>">
                <i class="fa-solid fa-sliders"></i>
                <span style="display: none;">Filters</span>
                <?php if ($sort !== 'newest' || $dateFrom || $dateTo || $filterJoined): ?>
                <span class="feed-filter-badge"></span>
                <?php
endif; ?>
            </button>
        </div>

        <!-- Filter Panel -->
        <div id="blocksFilterPanel"
            class="feed-filter-panel <?=($sort !== 'newest' || $dateFrom || $dateTo || $filterJoined) ? 'open' : ''?>"
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
    $isActive = ($sort === $key);
?>
                    <button type="button"
                        onclick="document.getElementById('hiddenSortInput').value='<?= $key?>'; document.getElementById('explore-form').submit();"
                        class="feed-sort-pill <?= $isActive ? 'active' : ''?>">
                        <i class="fa-solid <?= $opt['icon']?>"></i>
                        <?= $opt['label']?>
                    </button>
                    <?php
endforeach; ?>
                </div>
            </div>

            <div class="feed-filter-separator"></div>

            <!-- Additional Filters Section -->
            <div class="feed-filter-group">
                <p class="feed-filter-label">
                    <i class="fa-solid fa-filter"></i> Filters
                </p>
                <div class="feed-sort-pills">
                    <button type="button"
                        onclick="document.getElementById('hiddenJoinedInput').value='<?= $filterJoined ? '0' : '1'?>'; document.getElementById('explore-form').submit();"
                        class="feed-sort-pill <?= $filterJoined ? 'active' : ''?>">
                        <i class="fa-solid fa-user-check"></i>
                        Blocks Joined
                    </button>
                    <input type="hidden" name="joined" id="hiddenJoinedInput" value="<?= $filterJoined ? '1' : '0'?>">
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

            <input type="hidden" name="sort" id="hiddenSortInput" value="<?= htmlspecialchars($sort)?>">

            <!-- Footer row -->
            <div class="feed-filter-footer">
                <a href="index.php?q=<?= urlencode($search)?>&joined=<?= $filterJoined ? '1' : '0'?>"
                    class="feed-filter-reset">
                    <i class="fa-solid fa-rotate-left"></i> Clear sorting & dates
                </a>
            </div>
        </div>

        <!-- Active filters summary + clear -->
        <?php if ($hasFilters): ?>
        <div class="feed-active-chips" style="margin-top: 1rem; margin-bottom: 0;">
            <span
                style="font-size: 0.78rem; color: var(--gray-500); font-weight: 600; display: inline-flex; align-items: center; margin-right: 0.25rem;">Active
                filters:</span>
            <?php if ($search): ?>
            <a href="?sort=<?= urlencode($sort)?>&date_from=<?= urlencode($dateFrom)?>&date_to=<?= urlencode($dateTo)?>&joined=<?= $filterJoined ? '1' : '0'?>"
                class="feed-chip" title="Remove search filter">
                <i class="fa-solid fa-magnifying-glass"></i>
                "
                <?= htmlspecialchars($search)?>"
                <i class="fa-solid fa-xmark"></i>
            </a>
            <?php
    endif; ?>
            <?php if ($sort !== 'newest'): ?>
            <a href="?q=<?= urlencode($search)?>&date_from=<?= urlencode($dateFrom)?>&date_to=<?= urlencode($dateTo)?>&joined=<?= $filterJoined ? '1' : '0'?>"
                class="feed-chip" title="Remove sort filter">
                <i class="fa-solid <?= $sortOptions[$sort]['icon']?>"></i>
                <?= htmlspecialchars($sortOptions[$sort]['label'])?>
                <i class="fa-solid fa-xmark"></i>
            </a>
            <?php
    endif; ?>
            <?php if ($dateFrom || $dateTo): ?>
            <a href="?sort=<?= urlencode($sort)?>&q=<?= urlencode($search)?>&joined=<?= $filterJoined ? '1' : '0'?>"
                class="feed-chip" title="Remove date filter">
                <i class="fa-solid fa-calendar-days"></i>
                <?= $dateFrom ? date('M j, Y', strtotime($dateFrom)) : '…'?> &rarr;
                <?= $dateTo ? date('M j, Y', strtotime($dateTo)) : '…'?>
                <i class="fa-solid fa-xmark"></i>
            </a>
            <?php
    endif; ?>
            <?php if ($filterJoined): ?>
            <a href="?sort=<?= urlencode($sort)?>&q=<?= urlencode($search)?>&date_from=<?= urlencode($dateFrom)?>&date_to=<?= urlencode($dateTo)?>"
                class="feed-chip" title="Remove blocks joined filter">
                <i class="fa-solid fa-user-check"></i>
                Blocks Joined
                <i class="fa-solid fa-xmark"></i>
            </a>
            <?php
    endif; ?>
            <a href="index.php" class="feed-filter-reset" style="margin-left: auto;">
                <i class="fa-solid fa-xmark"></i> Clear all
            </a>
        </div>
        <?php
endif; ?>

    </form>
</div>

<!-- Results count -->
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem;">
    <p class="text-muted" style="font-size: 0.875rem;">
        <strong style="color: var(--white);">
            <?= count($blocks)?>
        </strong>
        communit
        <?= count($blocks) !== 1 ? 'ies' : 'y'?>
        <?php if ($hasFilters): ?><span style="color: var(--gray-600);"> — filtered</span>
        <?php
endif; ?>
    </p>
</div>

<!-- Block Grid -->
<div class="blocks-grid-large">
    <?php foreach ($blocks as $block): ?>
    <a href="<?= BASE_URL?>views/blocks/view.php?id=<?= $block['id']?>" class="card block-card-detailed mb-0"
        style="text-align: center; text-decoration: none; color: inherit; padding: 2rem; display: flex; flex-direction: column; align-items: center;">
        <div class="avatar-glow mb-4">
            <img src="<?= BASE_URL?>public/images/block_icons/<?= htmlspecialchars($block['icon'] ?: 'default_block.jpg')?>"
                class="avatar avatar-lg" alt="Block Icon" style="border: 3px solid var(--primary); padding: 2px;"
                onerror="this.src='<?= BASE_URL?>public/images/block_icons/default_block.jpg'; this.onerror=null;">
        </div>
        <h4 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem;">
            <?= htmlspecialchars($block['name'])?>
        </h4>
        <div class="text-xs uppercase tracking-widest font-bold mb-3"
            style="color: var(--gray-400); background: rgba(255,255,255,0.05); padding: 0.25rem 0.75rem; border-radius: 999px; border: 1px solid var(--border-color);">
            <?= htmlspecialchars($block['tags'])?>
        </div>
        <p class="text-muted" style="font-size: 0.875rem; line-height: 1.5; margin-bottom: 0.75rem;">
            <?= htmlspecialchars(substr($block['description'], 0, 80))?>
            <?= strlen($block['description']) > 80 ? '...' : ''?>
        </p>
        <div
            style="display: flex; align-items: center; gap: 1rem; color: var(--gray-500); font-size: 0.78rem; font-weight: 600; margin-top: auto; padding-top: 0.75rem; width: 100%; border-top: 1px solid var(--border-color); justify-content: center;">
            <span><i class="fa-solid fa-users" style="margin-right: 0.3rem;"></i>
                <?= number_format($block['member_count'])?> member
                <?= $block['member_count'] != 1 ? 's' : ''?>
            </span>
            <span><i class="fa-regular fa-calendar" style="margin-right: 0.3rem;"></i>
                <?= date('M j, Y', strtotime($block['created_at']))?>
            </span>
        </div>
    </a>
    <?php
endforeach; ?>

    <?php if (empty($blocks)): ?>
    <div class="card text-center py-5" style="grid-column: 1 / -1;">
        <i class="fa-solid fa-shapes mb-3" style="font-size: 3rem; color: var(--bg-tertiary);"></i>
        <p class="text-muted">
            <?= $hasFilters ? 'No blocks match your filters.' : 'No communities yet.'?>
        </p>
        <?php if ($hasFilters): ?>
        <a href="index.php" class="btn btn-secondary mt-3">Clear Filters</a>
        <?php
    endif; ?>
    </div>
    <?php
endif; ?>
</div>

<?php include '../layouts/footer.php'; ?>

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