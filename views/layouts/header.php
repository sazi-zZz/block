<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#080808">

    <!-- Primary Meta Tags -->
    <title>BLOCKNET - Interest-Based Communities</title>
    <meta name="title" content="BLOCKNET - Interest-Based Communities">
    <meta name="description"
        content="BLOCKNET is a community-interest-based discussion platform for intellectual people. Connect with like-minded individuals, share ideas, and engage in meaningful discussions.">
    <meta name="keywords"
        content="blocknet, community, forum, discussion, intellectual, interest-based, social network, knowledge sharing">
    <meta name="author" content="BLOCKNET">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= BASE_URL?>/">
    <meta property="og:title" content="BLOCKNET - Interest-Based Communities">
    <meta property="og:description"
        content="BLOCKNET is a community-interest-based discussion platform for intellectual people. Connect with like-minded individuals, share ideas, and engage in meaningful discussions.">
    <meta property="og:image" content="<?= BASE_URL?>/public/seoImage/blocknetSEO.jpg">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?= BASE_URL?>/">
    <meta property="twitter:title" content="BLOCKNET - Interest-Based Communities">
    <meta property="twitter:description"
        content="BLOCKNET is a community-interest-based discussion platform for intellectual people. Connect with like-minded individuals, share ideas, and engage in meaningful discussions.">
    <meta property="twitter:image" content="<?= BASE_URL?>/public/seoImage/blocknetSEO.jpg">

    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL?>/public/css/style.css">
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= BASE_URL?>/public/Block.png">
</head>

<body>

    <div class="app-layout">
        <?php if (isLoggedIn()): ?>
        <?php
    // Retrieve user data once for use in both mobile and desktop menus
    if (isset($pdo)) {
        $stmt = $pdo->prepare("SELECT username, avatar FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $headerUser = $stmt->fetch();
        $header_avatar = $headerUser['avatar'] ?? 'user.jpg';
        $header_username = $headerUser['username'] ?? $_SESSION['username'];
    }
    else {
        $header_avatar = $_SESSION['user_avatar'] ?? 'user.jpg';
        $header_username = $_SESSION['username'] ?? 'User';
    }
    $header_avatarPath = BASE_URL . '/public/images/avatars/' . $header_avatar;
?>

        <!-- Mobile Top Header -->
        <header class="mobile-header">
            <a href="<?= BASE_URL?>/index.php" class="logo-text">
                <img src="<?= BASE_URL?>/public/Block.png" alt="BLOCKNET" class="logo-icon"
                    style="width: 1.75rem; height: 1.75rem; object-fit: contain;">
                <span
                    style="font-family: 'Space Grotesk', sans-serif; font-weight: 800; letter-spacing: 0.08em;">BLOCKNET</span>
            </a>
            <div class="flex items-center gap-3">
                <a href="<?= BASE_URL?>/views/notifications/index.php" class="nav-icon-link">
                    <i class="fa-solid fa-bell"></i>
                </a>
                <a href="<?= BASE_URL?>/views/user/profile.php" class="mobile-profile-link">
                    <img src="<?= $header_avatarPath?>" class="avatar avatar-sm"
                        style="width: 32px; height: 32px; object-fit: cover; border-radius: 50%;"
                        onerror="this.src='<?= BASE_URL?>/public/images/avatars/user.jpg'; this.onerror=null;">
                </a>
            </div>
        </header>

        <nav class="sidebar">
            <div class="sidebar-logo">
                <a href="<?= BASE_URL?>/index.php" class="logo-text">
                    <img src="<?= BASE_URL?>/public/Block.png" alt="BLOCKNET" class="logo-icon"
                        style="width: 1.75rem; height: 1.75rem; object-fit: contain;">
                    <span
                        style="font-family: 'Space Grotesk', sans-serif; font-weight: 800; letter-spacing: 0.08em;">BLOCKNET</span>
                </a>
            </div>
            <div class="nav-links">
                <a href="<?= BASE_URL?>/index.php"
                    class="nav-link <?= $_SERVER['PHP_SELF'] == BASE_URL . '/index.php' || $_SERVER['PHP_SELF'] == '/index.php' ? 'active' : ''?>">
                    <i class="fa-solid fa-house"></i>
                    <span>Home</span>
                </a>
                <a href="<?= BASE_URL?>/views/search/index.php"
                    class="nav-link <?= strpos($_SERVER['PHP_SELF'], '/search/') !== false ? 'active' : ''?>">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <span>Search</span>
                </a>
                <a href="<?= BASE_URL?>/views/blocks/index.php"
                    class="nav-link <?= strpos($_SERVER['PHP_SELF'], '/blocks/') !== false && strpos($_SERVER['PHP_SELF'], '/my-blocks.php') === false ? 'active' : ''?>">
                    <i class="fa-solid fa-shapes"></i>
                    <span>Blocks</span>
                </a>
                <a href="<?= BASE_URL?>/views/posts/create.php" class="nav-link nav-link-highlight">
                    <i class="fa-solid fa-circle-plus" style="font-size: 1.5rem;"></i>
                    <span>Post</span>
                </a>
                <a href="<?= BASE_URL?>/views/blocks/my-blocks.php"
                    class="nav-link <?= strpos($_SERVER['PHP_SELF'], '/my-blocks.php') !== false ? 'active' : ''?>">
                    <i class="fa-solid fa-layer-group"></i>
                    <span>My B.</span>
                </a>
                <a href="<?= BASE_URL?>/views/chat/index.php"
                    class="nav-link <?= strpos($_SERVER['PHP_SELF'], '/chat/') !== false ? 'active' : ''?>">
                    <i class="fa-solid fa-comments"></i>
                    <span>Chat</span>
                </a>
                <a href="<?= BASE_URL?>/views/user/profile.php"
                    class="nav-link mobile-only <?= strpos($_SERVER['PHP_SELF'], '/user/') !== false ? 'active' : ''?>">
                    <i class="fa-solid fa-user"></i>
                    <span>Profile</span>
                </a>
                <a href="<?= BASE_URL?>/views/notifications/index.php"
                    class="nav-link desktop-only <?= strpos($_SERVER['PHP_SELF'], '/notifications/') !== false ? 'active' : ''?>">
                    <i class="fa-solid fa-bell"></i>
                    <span>Alerts</span>
                </a>
            </div>
            <div class="sidebar-user desktop-only"
                style="margin-top: auto; padding-top: 1rem; border-top: 1px solid var(--border-color);">
                <div class="profile-card">
                    <a href="<?= BASE_URL?>/views/user/profile.php" class="flex items-center"
                        style="text-decoration: none; color: inherit; gap: 0.75rem;">
                        <div style="position: relative; flex-shrink: 0;">
                            <?php if ($header_avatar): ?>
                            <img src="<?= $header_avatarPath?>" class="avatar avatar-sm"
                                style="border: 1px solid rgba(255,255,255,0.12);"
                                onerror="this.src='<?= BASE_URL?>/public/images/avatars/user.jpg';">
                            <?php
    else: ?>
                            <i class="fa-solid fa-circle-user" style="font-size: 32px; color: var(--gray-500);"></i>
                            <?php
    endif; ?>
                        </div>
                        <div class="flex flex-col" style="min-width: 0;">
                            <span
                                style="font-weight: 600; font-size: 0.875rem; color: var(--white); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                <?= htmlspecialchars($header_username)?>
                            </span>
                            <span
                                style="font-size: 0.7rem; color: var(--gray-500); text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600;">Profile</span>
                        </div>
                    </a>
                    <a href="<?= BASE_URL?>/views/auth/logout.php" class="flex items-center gap-2 hover-text-danger"
                        style="text-decoration: none; color: var(--gray-500); font-size: 0.8125rem; padding: 0.5rem 0; margin-top: 0.75rem; border-top: 1px solid var(--border-color);">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        <span>Sign out</span>
                    </a>
                </div>
            </div>
        </nav>
        <?php
endif; ?>
        <div class="content-wrapper">