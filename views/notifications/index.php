<?php
require_once '../../config/db.php';
require_once '../../includes/functions.php';
require_once '../../models/Notification.php';

requireLogin();

$notificationModel = new Notification($pdo);

$notifications = $notificationModel->getUserNotifications($_SESSION['user_id']);
$notificationModel->markAllAsRead($_SESSION['user_id']);

include '../layouts/header.php';
?>

<div class="card max-w-lg mx-auto" style="max-width: 600px; margin: 0 auto;">
    <div class="flex justify-between items-center mb-3">
        <h2>Notifications</h2>
    </div>

    <div class="notifications-list">
        <?php foreach ($notifications as $notif): ?>
        <?php
    $notifUrl = '#';
    if ($notif['type'] === 'like' || $notif['type'] === 'comment') {
        $notifUrl = 'views/posts/view.php?id=' . $notif['source_id'];
    }
    elseif ($notif['type'] === 'follow') {
        $notifUrl = 'views/user/profile.php?id=' . $notif['source_id'];
    }
    elseif ($notif['type'] === 'join') {
        $notifUrl = 'views/blocks/view.php?id=' . $notif['source_id'];
    }
?>
        <a href="<?= $notifUrl?>" class="notification-item flex gap-3 p-3 mb-2"
            style="background: <?= $notif['is_read'] ? 'var(--bg-secondary)' : 'var(--bg-tertiary)'?>; border-radius: var(--radius); text-decoration: none; color: inherit; display: flex; align-items: center; transition: background 0.2s;">
            <div class="icon" style="flex-shrink: 0; color: var(--primary);">
                <?php if ($notif['type'] === 'like'): ?>
                <i class="fa-solid fa-heart"></i>
                <?php
    elseif ($notif['type'] === 'comment'): ?>
                <i class="fa-solid fa-comment"></i>
                <?php
    elseif ($notif['type'] === 'follow'): ?>
                <i class="fa-solid fa-user-plus"></i>
                <?php
    else: ?>
                <i class="fa-solid fa-bell"></i>
                <?php
    endif; ?>
            </div>
            <div style="flex: 1;">
                <p class="mb-1" style="color: var(--text-color);">
                    <?= htmlspecialchars($notif['message'])?>
                </p>
                <small class="text-muted">
                    <?= timeElapsedString($notif['created_at'])?>
                </small>
            </div>
        </a>
        <?php
endforeach; ?>
        <?php if (empty($notifications)): ?>
        <p class="text-muted text-center pt-3 mt-3">You have no notifications right now.</p>
        <?php
endif; ?>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>