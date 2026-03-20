<?php
require_once '../../config/db.php';
require_once '../../includes/functions.php';
require_once '../../models/User.php';
require_once '../../models/Message.php';

requireLogin();

$user_id = $_GET['user_id'] ?? null;
$messageModel = new Message($pdo);
$recentConversations = $messageModel->getRecentConversations($_SESSION['user_id']);

$activeChatName = 'Global Chat';
if ($user_id) {
    $userModel = new User($pdo);
    $otherUser = $userModel->getUserById($user_id);
    if ($otherUser) {
        $activeChatName = $otherUser['username'];
    }
    else {
        $user_id = null; // invalid user
    }
}

include '../layouts/header.php';
?>

<div class="card mb-3 flex items-center"
    style="flex-direction: row; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem;">
    <div>
        <h2 class="mb-1">Chats</h2>
        <p class="text-muted" style="margin:0; font-size:0.9rem;">Connect globally or message users directly.</p>
    </div>
    <a href="views/chat/group.php" class="btn btn-secondary">
        <i class="fa-solid fa-users"></i> Group Chats
    </a>
</div>

<style>
    .chat-layout {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        min-height: 400px;
    }

    .chat-sidebar {
        width: 100%;
        flex-shrink: 0;
    }

    .chat-window {
        flex: 1;
        flex-direction: column;
        display: flex;
    }

    @media (min-width: 768px) {
        .chat-layout {
            flex-direction: row;
            gap: 1.5rem;
        }

        .chat-sidebar {
            width: 250px;
        }
    }
</style>

<!-- Custom chat CSS -->
<link rel="stylesheet" href="public/css/chat.css">

<div class="chat-layout">
    <!-- Chat Sidebar -->
    <div class="chat-sidebar">
        <div class="chat-sidebar-header flex items-center justify-between">
            <span>Chats</span>
            <a href="views/chat/group.php" class="btn btn-sm btn-secondary"
                style="border-radius: 50%; width: 36px; height: 36px; padding: 0;" title="Group Chats">
                <i class="fa-solid fa-users"></i>
            </a>
        </div>
        <div class="chat-list">
            <!-- Group Chats Link -->
            <a href="views/chat/group.php" class="chat-list-item">
                <div class="avatar"
                    style="background: #222222; display: flex; align-items: center; justify-content: center; color: #ffffff;">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div class="chat-list-item-content">
                    <div class="chat-list-item-name">Group Chats</div>
                    <div class="chat-list-item-preview">Up to 50 members</div>
                </div>
            </a>
            <!-- Global Chat Item -->
            <a href="views/chat/index.php" class="chat-list-item <?=!$user_id ? 'active' : ''?>">
                <div class="avatar"
                    style="background: #ffffff; display: flex; align-items: center; justify-content: center; color: #000;">
                    <i class="fa-solid fa-globe"></i>
                </div>
                <div class="chat-list-item-content">
                    <div class="chat-list-item-name">Global Chat</div>
                    <div class="chat-list-item-preview">Public Lobby</div>
                </div>
            </a>
            <?php foreach ($recentConversations as $conv): ?>
            <a href="views/chat/index.php?user_id=<?= $conv['id']?>"
                class="chat-list-item <?=($user_id == $conv['id']) ? 'active' : ''?>">
                <img src="public/images/avatars/<?= htmlspecialchars($conv['avatar'] ?: 'user.jpg')?>"
                    class="avatar">
                <div class="chat-list-item-content">
                    <div class="chat-list-item-name">
                        <?= htmlspecialchars($conv['username'])?>
                    </div>
                    <div class="chat-list-item-preview">Direct Message</div>
                </div>
            </a>
            <?php
endforeach; ?>
        </div>
    </div>

    <!-- Chat Window -->
    <div class="chat-window">
        <div class="chat-header">
            <?php if ($user_id): ?>
            <img src="public/images/avatars/<?= htmlspecialchars($otherUser['avatar'] ?: 'user.jpg')?>"
                class="avatar">
            <div class="flex flex-col">
                <strong style="font-size: 1.1rem;">
                    <?= htmlspecialchars($activeChatName)?>
                </strong>
                <!-- Can add status indicator here later -->
            </div>
            <?php
else: ?>
            <div class="avatar"
                style="background: #ffffff; display: flex; align-items: center; justify-content: center; color: #000;">
                <i class="fa-solid fa-globe"></i>
            </div>
            <strong style="font-size: 1.1rem;">
                <?= htmlspecialchars($activeChatName)?>
            </strong>
            <?php
endif; ?>
        </div>
        <div id="chat-messages" class="chat-messages">
            <div class="text-center text-muted" style="margin-top: auto; margin-bottom: auto;">Loading messages...</div>
        </div>
        <div class="chat-input-area">
            <div id="chat-attachment-preview"
                style="display:none; font-size: 0.8rem; color: var(--primary); margin-bottom: 0.5rem; padding: 0.5rem; background: var(--bg-tertiary); border-radius: var(--radius);">
            </div>
            <div id="chat-upload-progress"
                style="display:none; color: var(--text-color); font-size: 0.85rem; margin-bottom: 0.5rem;">
                <i class="fa-solid fa-spinner fa-spin"></i> Uploading, please wait...
            </div>
            <form id="chat-form" class="chat-form">
                <?php if ($user_id): ?>
                <div class="flex gap-1 items-center" style="margin-right: 10px;">
                    <button type="button" class="chat-action-btn"
                        onclick="document.getElementById('chat-file-input').click()" title="Attach File(s)">
                        <i class="fa-solid fa-circle-plus"></i>
                    </button>
                    <button type="button" class="chat-action-btn"
                        onclick="document.getElementById('chat-folder-input').click()" title="Attach Folder"
                        style="display: none;">
                        <i class="fa-solid fa-folder-open"></i>
                    </button>
                    <input type="file" id="chat-file-input" name="chat_file[]" multiple style="display:none;"
                        onchange="updateChatPreview()">
                    <input type="file" id="chat-folder-input" name="chat_folder[]" multiple webkitdirectory directory
                        style="display:none;" onchange="updateChatPreview()">
                </div>
                <?php
endif; ?>
                <input type="text" id="chat-input-content" class="chat-input" placeholder="Aa" autocomplete="off">
                <div class="emoji-picker"
                    style="cursor: pointer; font-size: 1.2rem; display: flex; align-items: center; margin: 0 10px;"
                    onclick="toggleEmojiPicker('chat-input-content')">😀</div>
                <button type="submit" id="chat-submit-btn" class="chat-action-btn"><i
                        class="fa-solid fa-paper-plane"></i></button>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const chatMessages = document.getElementById('chat-messages');
        const chatForm = document.getElementById('chat-form');
        const chatInput = document.getElementById('chat-input-content');
        const currentUserId = <?= json_encode($user_id)?>;

        let isScrolledToBottom = true;

        chatMessages.addEventListener('scroll', () => {
            isScrolledToBottom = chatMessages.scrollHeight - chatMessages.clientHeight <= chatMessages.scrollTop + 10;
        });

        const getMediaHtml = (media) => {
            if (!media) return '';
            const ext = media.split('.').pop().toLowerCase();
            const url = `/public/files/chat_uploads/${media}`;

            if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) {
                return `<div class="mt-2"><a href="${url}" target="_blank"><img src="${url}" style="max-height:200px; max-width: 100%; border-radius: 8px;"></a></div>`;
            } else if (['mp4', 'webm', 'ogg'].includes(ext)) {
                return `<div class="mt-2"><video src="${url}" controls style="max-height:200px; max-width:100%; border-radius: 8px;"></video></div>`;
            } else if (ext === 'zip') {
                return `<div class="mt-2 text-sm"><a href="${url}" download class="btn btn-sm btn-secondary" style="color:var(--text-color); padding: 0.25rem 0.5rem; background: rgba(0,0,0,0.2); border: none;"><i class="fa-solid fa-file-zipper"></i> Download Archive</a></div>`;
            } else {
                return `<div class="mt-2 text-sm"><a href="${url}" download class="btn btn-sm btn-secondary" style="color:var(--text-color); padding: 0.25rem 0.5rem; background: rgba(0,0,0,0.2); border: none;"><i class="fa-solid fa-download"></i> Download</a></div>`;
            }
        };

        const renderMessage = (msg) => {
            const isMine = msg.is_mine;
            const avatar = msg.avatar ? `/public/images/avatars/${msg.avatar}` : 'public/images/avatars/user.jpg';
            const mediaHtml = getMediaHtml(msg.media);
            const type = isMine ? 'sent' : 'received';
            const showName = !isMine && !currentUserId; // Only show names in global chat for others

            let nameHtml = '';
            if (showName) {
                nameHtml = `<div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 2px; margin-left: ${isMine ? '0' : '44px'}; ${isMine ? 'text-align: right;' : ''}">${escapeHtml(msg.username)}</div>`;
            }

            return `
                ${nameHtml}
                <div class="message ${type}">
                    ${!isMine ? `<img src="${avatar}" class="message-avatar" onerror="this.src='public/images/avatars/user.jpg'; this.onerror=null;">` : '<div style="width: 28px; margin: 0 8px;"></div>'}
                    <div class="message-bubble" title="${msg.exact_time}">
                        ${escapeHtml(msg.content)}
                        ${mediaHtml}
                    </div>
                </div>
            `;
        };

        const fetchMessages = () => {
            const url = currentUserId ? `/api/chat.php?user_id=${currentUserId}` : 'api/chat.php';

            fetch(url).then(res => res.json()).then(data => {
                if (Array.isArray(data)) {
                    // Backend already returns messages oldest-first (DESC + array_reverse)
                    chatMessages.innerHTML = data.map(renderMessage).join('');
                    if (isScrolledToBottom) {
                        scrollToBottom();
                    }
                }
            }).catch(err => console.error("Error fetching messages", err));
        };

        const scrollToBottom = () => {
            chatMessages.scrollTop = chatMessages.scrollHeight;
            isScrolledToBottom = true;
        };

        chatForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const content = chatInput.value;
            const fileInput = document.getElementById('chat-file-input');
            const folderInput = document.getElementById('chat-folder-input');
            const submitBtn = document.getElementById('chat-submit-btn');
            const progressDiv = document.getElementById('chat-upload-progress');

            let hasFiles = false;
            let totalSize = 0;

            if (fileInput && fileInput.files.length > 0) {
                hasFiles = true;
                for (let i = 0; i < fileInput.files.length; i++) totalSize += fileInput.files[i].size;
            }

            if (folderInput && folderInput.files.length > 0) {
                hasFiles = true;
                for (let i = 0; i < folderInput.files.length; i++) totalSize += folderInput.files[i].size;
            }

            if (!content && !hasFiles) return;

            if (totalSize > 100 * 1024 * 1024) {
                if (typeof showToast === 'function') {
                    showToast('Oops!', 'Total attachment size exceeds 100MB limit.', null);
                } else {
                    alert('Total attachment size exceeds 100MB limit.');
                }
                return;
            }

            const formData = new FormData();
            formData.append('content', content);

            if (currentUserId) {
                formData.append('user_id', currentUserId);

                if (fileInput && fileInput.files.length > 0) {
                    for (let i = 0; i < fileInput.files.length; i++) {
                        formData.append('chat_file[]', fileInput.files[i]);
                    }
                }

                if (folderInput && folderInput.files.length > 0) {
                    for (let i = 0; i < folderInput.files.length; i++) {
                        formData.append('chat_folder[]', folderInput.files[i]);
                        formData.append('folder_paths[]', folderInput.files[i].webkitRelativePath);
                    }
                }
            }

            if (submitBtn) submitBtn.disabled = true;
            if (progressDiv && hasFiles) progressDiv.style.display = 'block';

            fetch('api/chat.php', {
                method: 'POST',
                body: formData
            }).then(res => res.json()).then(data => {
                if (submitBtn) submitBtn.disabled = false;
                if (progressDiv) progressDiv.style.display = 'none';

                if (data.success) {
                    chatInput.value = '';

                    if (window.clearChatAttachments) {
                        window.clearChatAttachments(new Event('clear'));
                    }

                    chatInput.focus();
                    isScrolledToBottom = true;

                    if (typeof playNotificationSound === 'function') {
                        playNotificationSound();
                    }

                    fetchMessages();
                } else if (data.error) {
                    if (typeof showToast === 'function') showToast('Error', data.error, null);
                    else alert(data.error);
                }
            }).catch(err => {
                if (submitBtn) submitBtn.disabled = false;
                if (progressDiv) progressDiv.style.display = 'none';
                console.error("Upload error:", err);
            });
        });

        const escapeHtml = (unsafe) => {
            return (unsafe || '').toString()
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        };

        fetchMessages();
        setInterval(fetchMessages, 3000);
    });

    window.updateChatPreview = () => {
        const fileInput = document.getElementById('chat-file-input');
        const folderInput = document.getElementById('chat-folder-input');
        const preview = document.getElementById('chat-attachment-preview');
        const submitBtn = document.getElementById('chat-submit-btn');

        let text = [];
        let totalSize = 0;

        if (fileInput && fileInput.files.length > 0) {
            text.push(`${fileInput.files.length} file(s) selected`);
            for (let i = 0; i < fileInput.files.length; i++) totalSize += fileInput.files[i].size;
        }
        if (folderInput && folderInput.files.length > 0) {
            text.push(`Folder with ${folderInput.files.length} file(s) selected`);
            for (let i = 0; i < folderInput.files.length; i++) totalSize += folderInput.files[i].size;
        }

        const isTooLarge = totalSize > 100 * 1024 * 1024;

        if (text.length > 0) {
            preview.style.display = 'block';
            let html = '<strong><i class="fa-solid fa-paperclip"></i> Attached:</strong> ' + text.join(' and ') + ' <a href="#" onclick="clearChatAttachments(event)" style="color:var(--danger); margin-left:10px; font-weight:bold;">Clear</a>';
            if (isTooLarge) {
                html += '<br><span style="color:var(--danger); margin-top:0.25rem; display:block;"><i class="fa-solid fa-triangle-exclamation"></i> Warning: Total size exceeds 100MB limit. Please remove some files.</span>';
            }
            preview.innerHTML = html;
        } else {
            preview.style.display = 'none';
            preview.innerHTML = '';
        }

        if (submitBtn) {
            submitBtn.disabled = isTooLarge;
        }
    };

    window.clearChatAttachments = (e) => {
        if (e && e.preventDefault) e.preventDefault();
        const fileInput = document.getElementById('chat-file-input');
        const folderInput = document.getElementById('chat-folder-input');
        if (fileInput) fileInput.value = '';
        if (folderInput) folderInput.value = '';
        if (window.updateChatPreview) window.updateChatPreview();
    };
</script>

<?php include '../layouts/footer.php'; ?>