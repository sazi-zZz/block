<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'models/Post.php';

requireLogin();

$postModel = new Post($pdo);

// ── Feed Filters ──────────────────────────────────────────────
$allowed_sorts = ['random', 'newest', 'oldest', 'popular', 'least_popular'];
$sort = in_array($_GET['sort'] ?? '', $allowed_sorts) ? $_GET['sort'] : 'random';
$date_from = !empty($_GET['date_from']) ? date('Y-m-d', strtotime($_GET['date_from'])) : null;
$date_to = !empty($_GET['date_to']) ? date('Y-m-d', strtotime($_GET['date_to'])) : null;
if ($date_from && $date_to && $date_from > $date_to) {
    [$date_from, $date_to] = [$date_to, $date_from];
}
$joined_blocks = !empty($_GET['joined_blocks']) && $_GET['joined_blocks'] === '1';

// Generate a random seed on page load for consistent randomization during pagination
if ($sort === 'random') {
    $_SESSION['feed_random_seed'] = rand(10000, 99999);
}
$seed = $_SESSION['feed_random_seed'] ?? null;

$feed = $postModel->getFeed($_SESSION['user_id'], null, $sort, $date_from, $date_to, null, 10, 0, $seed, $joined_blocks);
$filtersActive = ($sort !== 'random') || $date_from || $date_to || $joined_blocks;

include 'views/layouts/header.php';
?>

<div class="discovery-header flex justify-between items-center mb-4">
    <div>
        <h1 style="font-size: 1.875rem; font-weight: 700; margin-bottom: 0.25rem;">Home</h1>
        <p class="text-muted" style="font-size: 0.9375rem;">Your feed</p>
    </div>
</div>

<div class="feed-section">

    <!-- ── Feed Header Row ── -->
    <div class="flex items-center justify-between mb-3">
        <div class="flex items-center gap-2">
            <i class="fa-solid fa-bolt" style="color: var(--primary);"></i>
            <h3 style="font-size: 1.25rem; font-weight: 700; margin:0;">Recent Feed</h3>
            <?php if ($filtersActive): ?>
            <span class="feed-filter-active-dot" title="Filters active"></span>
            <?php
endif; ?>
            <?php if (!empty($feed)): ?>
            <span style="font-size: 0.75rem; color: var(--gray-500); font-weight: 500; margin-left: 0.125rem;">
                &nbsp;
                <?= count($feed)?> post
                <?= count($feed) !== 1 ? 's' : ''?>
            </span>
            <?php
endif; ?>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?= BASE_URL?>views/posts/create.php" class="btn btn-primary btn-sm"
                style="border-radius: 12px; font-size: 0.8125rem; padding: 0.5rem 0.875rem; white-space: nowrap;">
                <i class="fa-solid fa-pen-nib mr-1"></i> Write Post
            </a>
            <button id="feedFilterToggle" class="feed-filter-toggle-btn <?= $filtersActive ? 'active' : ''?>"
                aria-expanded="<?= $filtersActive ? 'true' : 'false'?>" aria-controls="feedFilterPanel">
                <i class="fa-solid fa-sliders"></i>
                <span>Filters</span>
                <?php if ($filtersActive): ?>
                <span class="feed-filter-badge"></span>
                <?php
endif; ?>
            </button>
        </div>
    </div>

    <!-- ── Filter Panel ── -->
    <div id="feedFilterPanel" class="feed-filter-panel <?= $filtersActive ? 'open' : ''?>">
        <form method="GET" action="" id="feedFilterForm">

            <!-- Sort Section -->
            <div class="feed-filter-group">
                <p class="feed-filter-label">
                    <i class="fa-solid fa-arrow-up-wide-short"></i> Sort By
                </p>
                <div class="feed-sort-pills">
                    <?php
$sortOptions = [
    'random' => ['label' => 'Random', 'icon' => 'fa-shuffle'],
    'newest' => ['label' => 'Newest First', 'icon' => 'fa-arrow-down-short-wide'],
    'oldest' => ['label' => 'Oldest First', 'icon' => 'fa-arrow-up-short-wide'],
    'popular' => ['label' => 'Most Popular', 'icon' => 'fa-fire'],
    'least_popular' => ['label' => 'Least Popular', 'icon' => 'fa-snowflake'],
];
foreach ($sortOptions as $key => $opt): ?>
                    <button type="button"
                        onclick="document.getElementById('hiddenSortInput').value='<?= $key?>'; document.getElementById('feedFilterForm').submit();"
                        class="feed-sort-pill <?= $sort === $key ? 'active' : ''?>">
                        <i class="fa-solid <?= $opt['icon']?>"></i>
                        <?= $opt['label']?>
                    </button>
                    <?php
endforeach; ?>
                </div>
            </div>

            <div class="feed-filter-separator"></div>

            <!-- Joined Blocks Filter -->
            <div class="feed-filter-group">
                <p class="feed-filter-label">
                    <i class="fa-solid fa-cubes"></i> Content Source
                </p>
                <div class="feed-sort-pills">
                    <button type="button"
                        onclick="document.getElementById('hiddenJoinedBlocks').value=document.getElementById('hiddenJoinedBlocks').value==='1'?'0':'1'; document.getElementById('feedFilterForm').submit();"
                        class="feed-sort-pill <?= $joined_blocks ? 'active' : ''?>">
                        <i class="fa-solid fa-people-group"></i>
                        Posts From Blocks I Joined
                        <?php if ($joined_blocks): ?>
                        <i class="fa-solid fa-check" style="margin-left: 0.25rem; font-size: 0.75rem;"></i>
                        <?php
endif; ?>
                    </button>
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
                        <input type="date" id="date_from" name="date_from" class="feed-date-input"
                            value="<?= htmlspecialchars($date_from ?? '')?>" max="<?= date('Y-m-d')?>">
                    </div>
                    <span class="feed-date-sep"><i class="fa-solid fa-arrow-right"></i></span>
                    <div class="feed-date-field">
                        <label for="date_to" class="feed-date-label">To</label>
                        <input type="date" id="date_to" name="date_to" class="feed-date-input"
                            value="<?= htmlspecialchars($date_to ?? '')?>" max="<?= date('Y-m-d')?>">
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm feed-date-apply">
                        <i class="fa-solid fa-check"></i> Apply
                    </button>
                </div>
            </div>

            <input type="hidden" name="sort" id="hiddenSortInput" value="<?= htmlspecialchars($sort)?>">
            <input type="hidden" name="joined_blocks" id="hiddenJoinedBlocks" value="<?= $joined_blocks ? '1' : '0'?>">

            <div class="feed-filter-footer">
                <?php if ($filtersActive): ?>
                <a href="<?= BASE_URL?>" class="feed-filter-reset">
                    <i class="fa-solid fa-rotate-left"></i> Clear all filters
                </a>
                <?php
else: ?>
                <span style="font-size: 0.78rem; color: var(--gray-600);">
                    <i class="fa-solid fa-circle-info" style="margin-right: 0.25rem;"></i>
                    Popularity = total likes + comments
                </span>
                <?php
endif; ?>
            </div>
        </form>
    </div>

    <?php if ($filtersActive): ?>
    <!-- ── Active filter chips ── -->
    <div class="feed-active-chips">
        <?php if ($sort !== 'random'): ?>
        <?php $chipQuery = array_filter(['date_from' => $date_from, 'date_to' => $date_to, 'joined_blocks' => $joined_blocks ? '1' : null]); ?>
        <a href="?<?= http_build_query($chipQuery)?>" class="feed-chip" title="Remove sort filter">
            <i class="fa-solid <?= $sortOptions[$sort]['icon']?>"></i>
            <?= htmlspecialchars($sortOptions[$sort]['label'])?>
            <i class="fa-solid fa-xmark"></i>
        </a>
        <?php
    endif; ?>
        <?php if ($date_from || $date_to): ?>
        <?php $chipQuery2 = array_filter(['sort' => $sort !== 'random' ? $sort : null, 'joined_blocks' => $joined_blocks ? '1' : null]); ?>
        <a href="?<?= http_build_query($chipQuery2)?>" class="feed-chip" title="Remove date filter">
            <i class="fa-solid fa-calendar-days"></i>
            <?= $date_from ? htmlspecialchars(date('M j, Y', strtotime($date_from))) : '…'?>
            &rarr;
            <?= $date_to ? htmlspecialchars(date('M j, Y', strtotime($date_to))) : '…'?>
            <i class="fa-solid fa-xmark"></i>
        </a>
        <?php
    endif; ?>
        <?php if ($joined_blocks): ?>
        <?php $chipQuery3 = array_filter(['sort' => $sort !== 'random' ? $sort : null, 'date_from' => $date_from, 'date_to' => $date_to]); ?>
        <a href="?<?= http_build_query($chipQuery3)?>" class="feed-chip" title="Remove joined-blocks filter">
            <i class="fa-solid fa-people-group"></i>
            Joined Blocks
            <i class="fa-solid fa-xmark"></i>
        </a>
        <?php
    endif; ?>
    </div>
    <?php
endif; ?>

    <div id="live-feed-container">
        <?php foreach ($feed as $post): ?>
        <?php include 'views/posts/_post_card_feed.php'; ?>
        <?php
endforeach; ?>
    </div>

    <?php if (count($feed) >= 10): ?>
    <div id="load-more-section" class="text-center mt-4 mb-5"
        style="display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
        <button type="button" id="loadMoreBtn" class="btn btn-secondary" style="font-weight: 600;">
            <i class="fa-solid fa-arrow-down mr-2"></i> Load More
        </button>
        <div
            style="display: flex; align-items: center; background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 8px; overflow: hidden;">
            <input type="number" id="loadMoreLimit" value="10" min="1" max="50"
                style="width: 50px; background: transparent; border: none; color: var(--text-color); padding: 0.5rem; text-align: center; outline: none; font-size: 0.9rem;">
            <span class="text-muted" style="padding-right: 0.75rem; font-size: 0.85rem; font-weight: 500;">posts</span>
        </div>
    </div>
    <?php
endif; ?>

    <?php if (empty($feed)): ?>
    <div class="card text-center py-5">
        <i class="fa-solid fa-bolt mb-3" style="font-size: 3rem; color: var(--bg-tertiary);"></i>
        <p class="text-muted">No posts in your feed yet.</p>
        <a href="<?= BASE_URL?>views/blocks/index.php" class="btn btn-secondary mt-3">Explore Communities</a>
    </div>
    <?php
endif; ?>

</div>

<?php include 'views/layouts/footer.php'; ?>

<script>
    (function () {
        var btn = document.getElementById("feedFilterToggle");
        var panel = document.getElementById("feedFilterPanel");
        if (btn && panel) {
            var isOpen = panel.classList.contains("open");
            btn.setAttribute("aria-expanded", isOpen ? "true" : "false");
            if (isOpen) btn.classList.add("active");
            btn.addEventListener("click", function () {
                var exp = this.getAttribute("aria-expanded") === "true";
                if (exp) {
                    panel.classList.remove("open");
                    this.setAttribute("aria-expanded", "false");
                    this.classList.remove("active");
                } else {
                    panel.classList.add("open");
                    this.setAttribute("aria-expanded", "true");
                    this.classList.add("active");
                }
            });
        }

        var fromIn = document.getElementById("date_from");
        var toIn = document.getElementById("date_to");
        if (fromIn && toIn) {
            fromIn.addEventListener("change", function () {
                if (!toIn.value) toIn.value = new Date().toISOString().split("T")[0];
                if (toIn.value < this.value) toIn.value = this.value;
                toIn.min = this.value;
            });
            toIn.addEventListener("change", function () {
                if (fromIn.value && this.value < fromIn.value) fromIn.value = this.value;
                fromIn.max = this.value;
            });
        }

        var lastPostEl = document.querySelector(".feed-post");
        var lastPostId = lastPostEl ? parseInt(lastPostEl.getAttribute("data-post-id"), 10) : 0;

        setInterval(function () {
            var si = document.getElementById("hiddenSortInput");
            var dfi = document.getElementById("date_from");
            var dti = document.getElementById("date_to");
            if ((!si || si.value === "newest") && (!dfi || !dfi.value) && (!dti || !dti.value)) {
                fetch("api/feed_live.php?since_id=" + lastPostId, { credentials: "same-origin" })
                    .then(function (r) { return r.text(); })
                    .then(function (html) {
                        html = html.trim();
                        if (html.length > 0) {
                            var c = document.getElementById("live-feed-container");
                            if (c) {
                                c.insertAdjacentHTML("afterbegin", html);
                                var nf = document.querySelector(".feed-post");
                                if (nf) lastPostId = parseInt(nf.getAttribute("data-post-id"), 10);
                            }
                        }
                    })
                    .catch(function (e) { console.error("Poll error:", e); });
            }
        }, 10000);

        var lmBtn = document.getElementById("loadMoreBtn");
        if (lmBtn) {
            var offset = 10;
            lmBtn.addEventListener("click", function () {
                var li = document.getElementById("loadMoreLimit");
                var limit = li ? (parseInt(li.value, 10) || 10) : 10;

                var si = document.getElementById("hiddenSortInput");
                var sort = si ? si.value : "random";
                var dfi = document.getElementById("date_from");
                var dti = document.getElementById("date_to");
                var date_from = dfi ? dfi.value : "";
                var date_to = dti ? dti.value : "";
                var jb = document.getElementById("hiddenJoinedBlocks");
                var joined_blocks = jb ? jb.value : "0";

                var params = new URLSearchParams({
                    offset: offset,
                    limit: limit,
                    sort: sort,
                    date_from: date_from,
                    date_to: date_to,
                    joined_blocks: joined_blocks
                });

                var self = this;
                self.disabled = true;
                self.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading…';

                fetch("api/feed_load_more.php?" + params.toString(), { credentials: "same-origin" })
                    .then(function (r) { return r.text(); })
                    .then(function (html) {
                        html = html.trim();
                        var c = document.getElementById("live-feed-container");
                        if (c && html.length > 0) {
                            c.insertAdjacentHTML("beforeend", html);
                            offset += limit;
                        }
                        self.disabled = false;
                        self.innerHTML = '<i class="fa-solid fa-arrow-down mr-2"></i> Load More';
                        if (html.length === 0) {
                            var sec = document.getElementById("load-more-section");
                            if (sec) {
                                sec.innerHTML = '<p class="text-muted" style="font-size:0.9rem;">No more posts to load.</p>';
                            }
                        }
                    })
                    .catch(function (e) {
                        console.error("Load more error:", e);
                        self.disabled = false;
                        self.innerHTML = '<i class="fa-solid fa-arrow-down mr-2"></i> Load More';
                    });
            });
        }
    })();
</script>