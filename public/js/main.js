document.addEventListener('DOMContentLoaded', () => {
    // Basic init code for BLOCKNET app
    console.log("BLOCKNET Platform initialised");

    // Start notification polling if logged in
    startNotificationPolling();

    // Setup auto-pausing for videos that scroll out of view
    setupVideoAutoPause();
});

let lastNotifId = 0;
let lastMsgId = 0;

function setupVideoAutoPause() {
    // Pause videos when they are less than 10% visible on the screen
    const videoObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            const video = entry.target;
            if (!entry.isIntersecting) {
                if (!video.paused) {
                    video.pause();
                }
            }
        });
    }, { threshold: 0.1 });

    // Observe all videos currently on the page
    document.querySelectorAll('video').forEach(video => {
        videoObserver.observe(video);
    });

    // Also watch for dynamically added videos (e.g., from feed_load_more or chat)
    const mutationObserver = new MutationObserver(mutations => {
        mutations.forEach(mutation => {
            mutation.addedNodes.forEach(node => {
                if (node.nodeType === 1) { // ELEMENT_NODE
                    if (node.tagName === 'VIDEO') {
                        videoObserver.observe(node);
                    } else if (node.querySelectorAll) {
                        node.querySelectorAll('video').forEach(vid => videoObserver.observe(vid));
                    }
                }
            });
        });
    });

    mutationObserver.observe(document.body, { childList: true, subtree: true });
}

function playNotificationSound() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        const gainNode = ctx.createGain();

        osc.connect(gainNode);
        gainNode.connect(ctx.destination);

        osc.type = 'sine';
        osc.frequency.setValueAtTime(880, ctx.currentTime);
        osc.frequency.setValueAtTime(1200, ctx.currentTime + 0.05); // quick chirp

        gainNode.gain.setValueAtTime(0.1, ctx.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.1);

        osc.start();
        osc.stop(ctx.currentTime + 0.1);
    } catch (e) {
        console.error("Audio block or failed", e);
    }
}

function updateBadge(links, count) {
    links.forEach(link => {
        let badge = link.querySelector('.notif-badge');
        if (count > 0) {
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'notif-badge';
                badge.style.cssText = 'background: var(--danger); color: white; border-radius: 50%; min-width: 18px; height: 18px; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: bold; position: absolute; top: -5px; right: -5px; line-height: 1; padding: 0 4px;';

                // On desktop links, might need to wrap the icon to position badge properly if padding is large
                let icon = link.querySelector('i');
                if (icon) {
                    if (window.getComputedStyle(icon).position === 'static') {
                        icon.style.position = 'relative';
                    }
                    icon.appendChild(badge);
                } else {
                    if (window.getComputedStyle(link).position === 'static') {
                        link.style.position = 'relative';
                    }
                    link.appendChild(badge);
                }
            }
            badge.textContent = count > 99 ? '99+' : count;
        } else if (badge) {
            badge.remove();
        }
    });
}

function startNotificationPolling() {
    const pollServer = () => {
        fetch('/block/api/notifications_poll.php?last_notif_id=' + lastNotifId + '&last_msg_id=' + lastMsgId)
            .then(res => res.json())
            .then(data => {
                if (data.error) return; // not logged in

                if (data.has_new_notification || data.has_new_message) {
                    playNotificationSound();
                }

                if (data.has_new_notification && data.new_notifications) {
                    data.new_notifications.forEach(n => {
                        let title = 'New Notification';
                        let link = '/block/views/notifications/index.php';
                        if (n.type === 'like' || n.type === 'comment') {
                            title = n.type === 'like' ? 'New Like' : 'New Comment';
                            link = '/block/views/posts/view.php?id=' + n.source_id;
                        } else if (n.type === 'follow') {
                            title = 'New Follower';
                            link = '/block/views/user/profile.php?id=' + n.source_id;
                        } else if (n.type === 'join') {
                            title = 'New Member';
                            link = '/block/views/blocks/view.php?id=' + n.source_id;
                        }
                        showToast(title, n.content, link);
                    });
                }

                if (data.has_new_message && data.new_messages) {
                    data.new_messages.forEach(m => {
                        showToast('New Message from ' + (m.sender_name || 'Someone'), m.content, '/block/views/chat/index.php?user_id=' + m.sender_id);
                    });
                }

                if (data.max_notif_id) lastNotifId = data.max_notif_id;
                if (data.max_msg_id) lastMsgId = data.max_msg_id;

                const notifLinks = document.querySelectorAll('a[href*="/views/notifications/index.php"]');
                const chatLinks = document.querySelectorAll('a[href*="/views/chat/index.php"]');

                // If currently on notifications page, force remove badge visually without waiting for server response
                if (window.location.pathname.includes('/views/notifications/')) {
                    updateBadge(notifLinks, 0);
                } else {
                    updateBadge(notifLinks, data.unread_notifications);
                }
                updateBadge(chatLinks, data.unread_messages);
            })
            .catch(err => console.error(err));
    };

    pollServer(); // Initial fetch
    setInterval(pollServer, 5000); // 5 sec interval
}

function showToast(title, message, link = null) {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.style.cssText = 'position: fixed; bottom: 20px; right: 20px; z-index: 9999; display: flex; flex-direction: column-reverse; gap: 10px;';
        document.body.appendChild(container);
    }

    const toast = document.createElement(link ? 'a' : 'div');
    if (link) toast.href = link;

    toast.style.cssText = 'background: var(--bg-secondary); border-left: 4px solid var(--primary); padding: 15px 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); min-width: 250px; transform: translateX(120%); transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275), opacity 0.3s ease; display:flex; flex-direction:column; gap:4px; text-decoration:none; color:inherit;';

    // Add hover effect for clickable toasts
    if (link) {
        toast.style.cursor = 'pointer';
        toast.onmouseenter = () => toast.style.opacity = '0.9';
        toast.onmouseleave = () => toast.style.opacity = '1';
    }

    toast.innerHTML = `
        <strong style="color: var(--text-color); font-size: 0.95rem;">${title}</strong>
        <span style="color: var(--text-muted); font-size: 0.85rem;">${message}</span>
    `;

    container.appendChild(toast);

    // trigger animation
    setTimeout(() => {
        toast.style.transform = 'translateX(0)';
    }, 10);

    // remove after 5s
    setTimeout(() => {
        toast.style.transform = 'translateX(120%)';
        setTimeout(() => toast.remove(), 300);
    }, 5000);
}

window.toggleLike = function (button, postId) {
    const formData = new FormData();
    formData.append('post_id', postId);

    fetch('/block/api/like.php', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const icon = button.querySelector('i');
                const countSpan = button.querySelector('.like-count');

                countSpan.textContent = data.count;

                if (data.is_liked) {
                    icon.classList.remove('fa-regular');
                    icon.classList.add('fa-solid');
                    button.classList.add('text-danger');
                    button.style.color = 'var(--danger)';
                } else {
                    icon.classList.remove('fa-solid');
                    icon.classList.add('fa-regular');
                    button.classList.remove('text-danger');
                    button.style.color = 'inherit';
                }
            } else {
                console.error('Like action failed:', data.error);
            }
        })
        .catch(err => console.error('Error in like action:', err));
};
window.toggleEmojiPicker = function (targetInputId) {
    const targetInput = document.getElementById(targetInputId) || document.getElementsByName(targetInputId)[0];
    if (!targetInput) return;

    // Create picker if not exists
    let picker = document.getElementById('simple-emoji-picker');
    if (picker) {
        picker.remove();
        return;
    }

    picker = document.createElement('div');
    picker.id = 'simple-emoji-picker';
    picker.style.position = 'fixed';
    picker.style.bottom = '80px';
    picker.style.right = '20px';
    picker.style.background = 'var(--bg-secondary)';
    picker.style.border = '1px solid var(--border-color)';
    picker.style.borderRadius = 'var(--radius)';
    picker.style.padding = '10px';
    picker.style.display = 'grid';
    picker.style.gridTemplateColumns = 'repeat(5, 1fr)';
    picker.style.gap = '5px';
    picker.style.zIndex = '1000';
    picker.style.maxHeight = '200px';
    picker.style.overflowY = 'auto';
    picker.style.boxShadow = '0 4px 10px rgba(0,0,0,0.3)';

    const emojis = ['😀', '😂', '😍', '😎', '🤔', '😢', '🔥', '👍', '👎', '❤️', '👏', '🙌', '🎉', '✨', '🚀', '🌈', '🍦', '🍕', '💻', '🎮'];

    emojis.forEach(emoji => {
        const btn = document.createElement('button');
        btn.textContent = emoji;
        btn.style.background = 'none';
        btn.style.border = 'none';
        btn.style.fontSize = '1.5rem';
        btn.style.cursor = 'pointer';
        btn.onclick = () => {
            targetInput.value += emoji;
            picker.remove();
            targetInput.focus();
        };
        picker.appendChild(btn);
    });

    document.body.appendChild(picker);

    // Close on click outside
    setTimeout(() => {
        const closePicker = (e) => {
            if (!picker.contains(e.target)) {
                picker.remove();
                document.removeEventListener('click', closePicker);
            }
        };
        document.addEventListener('click', closePicker);
    }, 0);
};
