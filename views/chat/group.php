<?php
require_once '../../config/db.php';
require_once '../../includes/functions.php';
require_once '../../models/GroupChat.php';

requireLogin();

$me = $_SESSION['user_id'];
$groupModel = new GroupChat($pdo);

$group_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$group = $group_id ? $groupModel->getGroup($group_id, $me) : null;

if ($group_id && !$group) {
    // Not a member or group doesn't exist
    header('Location: /views/chat/index.php');
    exit;
}

include '../layouts/header.php';
?>

<link rel="stylesheet" href="public/css/chat.css">
<style>
    /* ── Group Chat overrides for chat.css ─────────────────────────────────────────────────── */
    .gchat-group-photo {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
        background: linear-gradient(135deg, var(--primary), #7c3aed);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        color: #fff;
        flex-shrink: 0;
        overflow: hidden;
    }

    .gchat-group-photo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 50%;
    }

    .gchat-member-item {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        padding: 0.45rem 0.6rem;
        border-radius: var(--radius);
        transition: background 0.15s;
    }

    .gchat-member-item:hover {
        background: var(--bg-tertiary);
    }

    .gchat-member-item .kick-btn {
        margin-left: auto;
        font-size: 0.75rem;
        padding: 0.2rem 0.55rem;
        opacity: 0;
        transition: opacity 0.15s;
    }

    .gchat-member-item:hover .kick-btn {
        opacity: 1;
    }

    .search-results-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: var(--radius);
        z-index: 100;
        max-height: 200px;
        overflow-y: auto;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.18);
    }

    .search-result-item {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        padding: 0.5rem 0.75rem;
        cursor: pointer;
        transition: background 0.12s;
    }

    .search-result-item:hover {
        background: var(--bg-tertiary);
    }

    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.55);
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(4px);
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.25s;
    }

    .modal-overlay.active {
        opacity: 1;
        pointer-events: all;
    }

    .modal-box {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: calc(var(--radius)*2);
        padding: 2rem;
        width: 100%;
        max-width: 440px;
        transform: translateY(20px);
        transition: transform 0.25s;
    }

    .modal-overlay.active .modal-box {
        transform: translateY(0);
    }

    .gchat-placeholder {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100%;
        gap: 1rem;
        color: var(--text-muted);
        padding: 2rem;
        text-align: center;
    }

    .gchat-placeholder .big-icon {
        font-size: 3rem;
        opacity: 0.4;
    }
</style>

<div class="card mb-3 flex items-center"
    style="flex-direction: row; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem;">
    <div>
        <h2 class="mb-1">💬 Group Chats</h2>
        <p class="text-muted" style="margin:0; font-size:0.9rem;">Create groups and chat with up to 50 members.</p>
    </div>
    <div class="flex gap-2">
        <a href="views/chat/index.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> DMs</a>
        <button class="btn btn-secondary" id="open-create-modal-btn">
            <i class="fa-solid fa-plus"></i> New Group
        </button>
    </div>
</div>

<div id="gchat-layout" class="gchat-layout <?= $group ? 'show-chat' : '' ?>">
    <!-- Left: group list -->
    <div class="card gchat-sidebar p-0">
        <div style="padding: 0.75rem 1rem; border-bottom: 1px solid var(--border-color);">
            <strong>My Groups</strong>
        </div>
        <div id="group-list" style="padding: 0.5rem; overflow-y: auto; flex: 1;">
            <?php
$myGroups = $groupModel->getUserGroups($me);
if (empty($myGroups)): ?>
            <p class="text-muted" style="padding: 0.75rem; font-size: 0.85rem;">No groups yet. Create one!</p>
            <?php
else:
    foreach ($myGroups as $g):
        $active = ($group_id == $g['id']) ? 'active' : '';
        $photoSrc = $g['photo']
            ? 'public/images/group_photos/' . htmlspecialchars($g['photo'])
            : null;
?>
            <a href="views/chat/group.php?id=<?= $g['id']?>" class="group-list-item <?= $active?>">
                <div class="gchat-group-photo">
                    <?php if ($photoSrc): ?>
                    <img src="<?= $photoSrc?>" alt="group">
                    <?php
        else: ?>
                    <i class="fa-solid fa-users"></i>
                    <?php
        endif; ?>
                </div>
                <div style="min-width:0;">
                    <div
                        style="font-weight:600; font-size:0.9rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                        <?= htmlspecialchars($g['name'])?>
                    </div>
                    <small class="text-muted">
                        <?= $g['member_count']?>/50 members
                    </small>
                </div>
                <?php if ($g['is_creator']): ?>
                <span title="You created this group"
                    style="margin-left:auto; color: var(--primary); font-size: 0.7rem;"><i
                        class="fa-solid fa-crown"></i></span>
                <?php
        endif; ?>
            </a>
            <?php
    endforeach;
endif; ?>
        </div>
    </div>

    <!-- Right: chat window -->
    <div class="card gchat-window p-0">
        <?php if (!$group): ?>
        <div class="gchat-placeholder">
            <div class="big-icon"><i class="fa-solid fa-comments"></i></div>
            <h3>Select a Group</h3>
            <p>Choose a group from the left or create a new one to start chatting.</p>
            <button class="btn btn-primary" id="open-create-modal-btn2">
                <i class="fa-solid fa-plus"></i> New Group
            </button>
        </div>
        <?php
else: ?>
        <!-- Header -->
        <div class="gchat-header">
            <button class="mobile-back-btn" onclick="document.getElementById('gchat-layout').classList.remove('show-chat')">
                <i class="fa-solid fa-chevron-left"></i>
            </button>
            <div class="gchat-group-photo">
                <?php if ($group['photo']): ?>
                <img src="public/images/group_photos/<?= htmlspecialchars($group['photo'])?>" alt="group photo"
                    id="group-photo-img">
                <?php
    else: ?>
                <i class="fa-solid fa-users" id="group-photo-placeholder"></i>
                <?php
    endif; ?>
            </div>
            <div style="flex:1; min-width:0;">
                <div style="font-weight:700; font-size:1rem; display:flex; align-items:center; gap:0.5rem;"
                    id="group-name-display">
                    <span id="group-name-text">
                        <?= htmlspecialchars($group['name'])?>
                    </span>
                    <?php if ($group['is_creator']): ?>
                    <button class="btn btn-secondary btn-sm" onclick="editGroupName()"
                        style="padding: 0.15rem 0.4rem; font-size: 0.8rem; background: transparent; border: none; color: var(--text-muted); cursor: pointer;"
                        title="Edit Group Name">
                        <i class="fa-solid fa-pen"></i>
                    </button>
                    <?php
    endif; ?>
                </div>
                <small class="text-muted">
                    <?= $group['member_count']?>/50 members
                </small>
            </div>
            <div class="flex gap-2" style="position:relative; align-items:center;">
                <?php if ($group['is_creator']): ?>
                <button class="btn btn-secondary" title="Change Group Photo"
                    onclick="document.getElementById('photo-upload-input').click()" style="padding: 0.4rem 0.7rem;">
                    <i class="fa-solid fa-camera"></i>
                </button>
                <input type="file" id="photo-upload-input" accept="image/*,image/gif" style="display:none;"
                    onchange="uploadGroupPhoto(this)">
                <!-- Upload feedback bar -->
                <div id="photo-upload-status" style="display:none;"></div>
                <button class="btn btn-secondary" title="Add Member" onclick="openAddMemberModal()"
                    style="padding: 0.4rem 0.7rem;">
                    <i class="fa-solid fa-user-plus"></i>
                </button>
                <button class="btn btn-secondary" title="Delete Group" onclick="openModal('delete-group-modal')"
                    style="padding: 0.4rem 0.7rem; color: var(--danger);">
                    <i class="fa-solid fa-trash"></i>
                </button>
                <?php
    else: ?>
                <button class="btn btn-secondary" title="Leave Group" onclick="openModal('leave-group-modal')"
                    style="padding: 0.4rem 0.7rem; color: var(--danger);">
                    <i class="fa-solid fa-right-from-bracket"></i>
                </button>
                <?php
    endif; ?>
                <button class="btn btn-secondary" title="Members" onclick="toggleMembersPanel()"
                    style="padding: 0.4rem 0.7rem;">
                    <i class="fa-solid fa-users"></i>
                </button>
            </div>
        </div>

        <div style="display:flex; flex:1; overflow:hidden; min-height:0;">
            <!-- Messages -->
            <div style="flex:1; display:flex; flex-direction:column; min-width:0;">
                <div id="gchat-messages" class="gchat-messages">
                    <div class="text-center text-muted" id="messages-loading">Loading messages...</div>
                </div>
                <div class="gchat-input-row">
                    <input type="text" id="gchat-input" placeholder="Type a message..."
                        style="flex:1; padding: 0.65rem 1rem; border: 1px solid var(--border-color); border-radius: var(--radius); background: var(--bg-tertiary); color: var(--text-color);"
                        autocomplete="off">
                    <div style="cursor:pointer; font-size:1.2rem; display:flex; align-items:center;"
                        onclick="toggleEmojiPicker('gchat-input')">😀</div>
                    <button class="btn btn-primary" id="gchat-send-btn" style="padding: 0.65rem 1rem;">
                        <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </div>
            </div>

            <!-- Members panel (collapsible) -->
            <div id="members-panel"
                style="width:0; overflow:hidden; border-left: 0 solid var(--border-color); transition: width 0.3s, border 0.3s;">
                <div style="padding: 0.75rem; min-width: 200px;">
                    <div style="font-weight:600; margin-bottom:0.75rem; font-size:0.9rem;">
                        <i class="fa-solid fa-users"></i> Members
                        <span id="members-count" style="color:var(--text-muted); font-weight:400;"></span>
                    </div>
                    <div id="members-list"></div>
                </div>
            </div>
        </div>
        <?php
endif; ?>
    </div>
</div>

<?php if ($group): ?>
<!-- Add Member Modal -->
<div class="modal-overlay" id="add-member-modal">
    <div class="modal-box">
        <h3 style="margin-bottom:0.85rem;"><i class="fa-solid fa-user-plus"></i> Add Member</h3>

        <!-- Mutual-follow notice -->
        <div
            style="display:flex; align-items:flex-start; gap:0.6rem; background:var(--bg-tertiary); border:1px solid var(--border-color); border-left: 3px solid var(--primary); border-radius:var(--radius); padding:0.65rem 0.85rem; margin-bottom:1rem;">
            <i class="fa-solid fa-circle-info" style="color:var(--primary); margin-top:0.1rem; flex-shrink:0;"></i>
            <span style="font-size:0.82rem; line-height:1.5; color:var(--text-muted);">
                Only users who <strong style="color:var(--text-color);">mutually follow each other</strong> with you can
                be added to the group — both you must follow them <em>and</em> they must follow you back.
            </span>
        </div>

        <div style="position:relative;">
            <input type="text" id="add-member-search" placeholder="Search mutual followers..."
                style="width:100%; padding: 0.6rem 0.85rem; border: 1px solid var(--border-color); border-radius: var(--radius); background: var(--bg-tertiary); color: var(--text-color);"
                oninput="searchUsersToAdd(this.value)">
            <div id="add-member-results" class="search-results-dropdown" style="display:none;"></div>
        </div>
        <p id="add-member-no-results"
            style="display:none; font-size:0.82rem; color:var(--text-muted); margin-top:0.6rem;">
            <i class="fa-solid fa-magnifying-glass"></i> No mutual followers found with that name, or they are already
            in the group.
        </p>
        <div style="margin-top:1.25rem; display:flex; gap:0.75rem; justify-content:flex-end;">
            <button class="btn btn-secondary" onclick="closeModal('add-member-modal')">Cancel</button>
        </div>
    </div>
</div>

<!-- Leave Group Confirmation Modal -->
<div class="modal-overlay" id="leave-group-modal">
    <div class="modal-box">
        <h3 style="margin-bottom:0.75rem;"><i class="fa-solid fa-right-from-bracket" style="color:var(--danger);"></i>
            Leave Group</h3>
        <p class="text-muted" style="margin-bottom:1.5rem; font-size:0.9rem;">Are you sure you want to leave <strong>
                <?= htmlspecialchars($group['name'])?>
            </strong>? You will need to be added back by the creator.</p>
        <div style="display:flex; gap:0.75rem; justify-content:flex-end;">
            <button class="btn btn-secondary" onclick="closeModal('leave-group-modal')">Cancel</button>
            <button class="btn btn-primary" id="leave-group-confirm-btn"
                style="background: var(--danger); border-color: var(--danger);" onclick="confirmLeaveGroup()">
                <i class="fa-solid fa-right-from-bracket"></i> Leave Group
            </button>
        </div>
    </div>
</div>

<!-- Delete Group Confirmation Modal -->
<div class="modal-overlay" id="delete-group-modal">
    <div class="modal-box">
        <h3 style="margin-bottom:0.75rem;"><i class="fa-solid fa-trash" style="color:var(--danger);"></i> Delete Group
        </h3>
        <p class="text-muted" style="margin-bottom:0.5rem; font-size:0.9rem;">Are you sure you want to permanently
            delete <strong>
                <?= htmlspecialchars($group['name'])?>
            </strong>?</p>
        <p style="color:var(--danger); font-size:0.85rem; margin-bottom:1.5rem;"><i
                class="fa-solid fa-triangle-exclamation"></i> This will remove the group, all messages, and all members.
            This cannot be undone.</p>
        <div style="display:flex; gap:0.75rem; justify-content:flex-end;">
            <button class="btn btn-secondary" onclick="closeModal('delete-group-modal')">Cancel</button>
            <button class="btn btn-primary" id="delete-group-confirm-btn"
                style="background: var(--danger); border-color: var(--danger);" onclick="confirmDeleteGroup()">
                <i class="fa-solid fa-trash"></i> Delete Group
            </button>
        </div>
    </div>
</div>
<?php
endif; ?>

<!-- Create Group Modal -->
<div class="modal-overlay" id="create-group-modal">
    <div class="modal-box">
        <h3 style="margin-bottom:1rem;"><i class="fa-solid fa-plus"></i> Create New Group</h3>
        <form id="create-group-form" enctype="multipart/form-data">
            <div style="margin-bottom:1rem;">
                <label style="display:block; font-weight:600; margin-bottom:0.4rem;">Group Name *</label>
                <input type="text" name="name" id="new-group-name" class="js-char-limit" data-limit="100" required
                    placeholder="e.g. Study Buddies"
                    style="width:100%; padding: 0.6rem 0.85rem; border: 1px solid var(--border-color); border-radius: var(--radius); background: var(--bg-tertiary); color: var(--text-color);">
            </div>
            <div style="margin-bottom:1.5rem;">
                <label style="display:block; font-weight:600; margin-bottom:0.4rem;">Group Photo (optional, max 2
                    MB)</label>
                <input type="file" name="photo" id="new-group-photo" accept="image/*,image/gif"
                    style="width:100%; padding: 0.4rem; border: 1px solid var(--border-color); border-radius: var(--radius); background: var(--bg-tertiary); color: var(--text-color);">
                <small class="text-muted" style="display: block; margin-top: 0.25rem; font-size: 0.8rem;">Maximum file
                    size: 2MB. Supported formats: JPEG, PNG, GIF, WebP.</small>
                <p id="new-group-photo-warn"
                    style="display:none; margin-top:0.4rem; font-size:0.82rem; color:var(--danger);"><i
                        class="fa-solid fa-triangle-exclamation"></i> Photo exceeds 2 MB limit. Please choose a smaller
                    file.</p>
                <p id="new-group-photo-type-warn"
                    style="display:none; margin-top:0.4rem; font-size:0.82rem; color:var(--danger);"><i
                        class="fa-solid fa-triangle-exclamation"></i> Invalid file type. Only standard images are
                    allowed.</p>
            </div>
            <div style="display:flex; gap:0.75rem; justify-content:flex-end;">
                <button type="button" class="btn btn-secondary"
                    onclick="closeModal('create-group-modal')">Cancel</button>
                <button type="submit" class="btn btn-primary" id="create-group-submit-btn">
                    <i class="fa-solid fa-plus"></i> Create Group
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        const GROUP_ID = <?= json_encode($group ? (int)$group['id'] : null)?>;
        const IS_CREATOR = <?= json_encode($group ? (bool)$group['is_creator'] : false)?>;
        const ME = <?= json_encode($me)?>;

        // ── Helpers ──────────────────────────────────────────────────────────────
        const esc = s => (s ?? '').toString()
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');

        const showToastMsg = (title, msg) => {
            if (typeof showToast === 'function') showToast(title, msg, null);
            else alert(title + ': ' + msg);
        };

        // ── Modal helpers ─────────────────────────────────────────────────────────
        window.openModal = id => document.getElementById(id).classList.add('active');
        window.closeModal = id => document.getElementById(id).classList.remove('active');

        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', e => {
                if (e.target === overlay) overlay.classList.remove('active');
            });
        });

        // ── Create Group ──────────────────────────────────────────────────────────
        const openCreateBtns = ['open-create-modal-btn', 'open-create-modal-btn2'];
        openCreateBtns.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.addEventListener('click', () => openModal('create-group-modal'));
        });

        const createForm = document.getElementById('create-group-form');
        if (createForm) {
            // Client-side 2 MB and type guard on photo field
            document.getElementById('new-group-photo').addEventListener('change', function () {
                const warn = document.getElementById('new-group-photo-warn');
                const typeWarn = document.getElementById('new-group-photo-type-warn');
                const submitBtn = document.getElementById('create-group-submit-btn');

                warn.style.display = 'none';
                typeWarn.style.display = 'none';
                submitBtn.disabled = false;

                if (this.files[0]) {
                    const file = this.files[0];
                    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

                    if (!allowedTypes.includes(file.type)) {
                        typeWarn.style.display = 'block';
                        submitBtn.disabled = true;
                        this.value = ''; // clear the invalid file
                        return;
                    }

                    if (file.size > 2 * 1024 * 1024) {
                        warn.style.display = 'block';
                        submitBtn.disabled = true;
                        this.value = ''; // clear the invalid file
                    }
                }
            });

            createForm.addEventListener('submit', async e => {
                e.preventDefault();
                const btn = document.getElementById('create-group-submit-btn');
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Creating...';

                const fd = new FormData(createForm);
                fd.append('action', 'create');
                const res = await fetch('api/group_chat.php', { method: 'POST', body: fd });
                const data = await res.json();
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-plus"></i> Create Group';

                if (data.success) {
                    window.location.href = `/views/chat/group.php?id=${data.group_id}`;
                } else {
                    showToastMsg('Error', data.error || 'Failed to create group.');
                }
            });
        }

        if (!GROUP_ID) return; // No active group – stop here

        // ── Messages ──────────────────────────────────────────────────────────────
        const messagesDiv = document.getElementById('gchat-messages');
        let isScrolledToBottom = true;

        messagesDiv.addEventListener('scroll', () => {
            isScrolledToBottom = messagesDiv.scrollHeight - messagesDiv.clientHeight <= messagesDiv.scrollTop + 10;
        });

        const scrollToBottom = () => {
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
            isScrolledToBottom = true;
        };

        const renderMessage = msg => {
            const isMine = msg.is_mine;
            const avatar = msg.avatar
                ? `/public/images/avatars/${msg.avatar}`
                : 'public/images/avatars/user.jpg';

            if (isMine) {
                return `
            <div class="message flex gap-2 chat-bubble-sent" style="align-self:flex-end; flex-direction:row-reverse; max-width:80%;">
                <div style="background:#111111; color:#ffffff; padding:0.625rem 1rem; border-radius:18px 18px 4px 18px; font-size:0.9375rem; line-height:1.5;">
                    ${esc(msg.content)}
                    <small style="display:block; font-size:0.65rem; opacity:0.6; margin-top:4px; text-align:right;" title="${esc(msg.exact_time)}">${esc(msg.time_ago)}</small>
                </div>
            </div>`;
            }
            return `
        <div class="message flex gap-2 chat-bubble-received" style="max-width:80%;">
            <a href="views/user/profile.php?id=${msg.sender_id}">
                <img src="${avatar}" class="avatar avatar-sm flex-shrink-0" style="width:32px; height:32px; object-fit:cover;" onerror="this.src='public/images/avatars/user.jpg'; this.onerror=null;">
            </a>
            <div style="background:var(--bg-tertiary); padding:0.625rem 1rem; border-radius:18px 18px 18px 4px; border:1px solid var(--border-color); font-size:0.9375rem; line-height:1.5;">
                <strong style="display:block; font-size:0.78rem; color:var(--text-muted); margin-bottom:2px;">
                    <a href="views/user/profile.php?id=${msg.sender_id}" style="text-decoration:none; color:inherit;">${esc(msg.username)}</a>
                </strong>
                ${esc(msg.content)}
                <small style="display:block; font-size:0.65rem; color:var(--text-muted); margin-top:4px;" title="${esc(msg.exact_time)}">${esc(msg.time_ago)}</small>
            </div>
        </div>`;
        };

        const fetchMessages = () => {
            fetch(`/api/group_chat.php?action=messages&group_id=${GROUP_ID}`)
                .then(r => r.json())
                .then(data => {
                    if (!Array.isArray(data)) return;
                    messagesDiv.innerHTML = data.map(renderMessage).join('') || '<div class="text-center text-muted" style="margin-top:2rem;">No messages yet. Say hello! 👋</div>';
                    if (isScrolledToBottom) scrollToBottom();
                });
        };

        fetchMessages();
        setInterval(fetchMessages, 3000);

        // ── Send Message ──────────────────────────────────────────────────────────
        const sendBtn = document.getElementById('gchat-send-btn');
        const inputEl = document.getElementById('gchat-input');

        const sendMessage = async () => {
            const content = inputEl.value.trim();
            if (!content) return;
            sendBtn.disabled = true;
            const fd = new FormData();
            fd.append('action', 'send_message');
            fd.append('group_id', GROUP_ID);
            fd.append('content', content);
            const res = await fetch('api/group_chat.php', { method: 'POST', body: fd });
            const data = await res.json();
            sendBtn.disabled = false;
            if (data.success) {
                inputEl.value = '';
                isScrolledToBottom = true;
                if (typeof playNotificationSound === 'function') playNotificationSound();
                fetchMessages();
            } else {
                showToastMsg('Error', data.error || 'Failed to send.');
            }
        };

        sendBtn.addEventListener('click', sendMessage);
        inputEl.addEventListener('keydown', e => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); } });

        // ── Members Panel ─────────────────────────────────────────────────────────
        let membersOpen = false;
        const membersPanel = document.getElementById('members-panel');

        window.toggleMembersPanel = () => {
            membersOpen = !membersOpen;
            if (membersOpen) {
                membersPanel.style.width = '210px';
                membersPanel.style.borderLeftWidth = '1px';
                loadMembers();
            } else {
                membersPanel.style.width = '0';
                membersPanel.style.borderLeftWidth = '0';
            }
        };

        const loadMembers = async () => {
            const res = await fetch(`/api/group_chat.php?action=members&group_id=${GROUP_ID}`);
            const data = await res.json();
            if (!Array.isArray(data)) return;
            const countEl = document.getElementById('members-count');
            if (countEl) countEl.textContent = ` (${data.length}/50)`;
            document.getElementById('members-list').innerHTML = data.map(m => {
                const avatar = m.avatar
                    ? `/public/images/avatars/${m.avatar}`
                    : 'public/images/avatars/user.jpg';
                const crownBadge = m.is_creator ? '&nbsp;<i class="fa-solid fa-crown" style="color:gold; font-size:0.75rem;" title="Group Creator"></i>' : '';
                const kickBtn = (IS_CREATOR && !m.is_creator && m.id != ME)
                    ? `<button class="btn btn-secondary kick-btn" style="color:var(--danger); font-size:0.72rem; padding:0.2rem 0.45rem;" onclick="kickMember(${m.id}, \`${esc(m.username)}\`)"><i class="fa-solid fa-user-xmark"></i></button>`
                    : '';
                return `
            <div class="gchat-member-item">
                <img src="${avatar}" class="avatar avatar-sm" style="width:28px; height:28px; object-fit:cover; flex-shrink:0;" onerror="this.src='public/images/avatars/user.jpg'; this.onerror=null;">
                <a href="views/user/profile.php?id=${m.id}" style="font-size:0.85rem; text-decoration:none; color:inherit; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; flex:1;">${esc(m.username)}${crownBadge}</a>
                ${kickBtn}
            </div>`;
            }).join('');
        };

        window.kickMember = async (userId, username) => {
            if (!confirm(`Remove ${username} from this group?`)) return;
            const fd = new FormData();
            fd.append('action', 'kick_member');
            fd.append('group_id', GROUP_ID);
            fd.append('user_id', userId);
            const res = await fetch('api/group_chat.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) { showToastMsg('Done', `${username} was removed.`); loadMembers(); }
            else showToastMsg('Error', data.error || 'Failed.');
        };

        // ── Add Member ────────────────────────────────────────────────────────────
        let searchTimer;

        window.openAddMemberModal = () => openModal('add-member-modal');

        window.searchUsersToAdd = query => {
            clearTimeout(searchTimer);
            const resultsEl = document.getElementById('add-member-results');
            const noResultsEl = document.getElementById('add-member-no-results');
            if (query.length < 1) {
                resultsEl.style.display = 'none';
                noResultsEl.style.display = 'none';
                return;
            }
            searchTimer = setTimeout(async () => {
                const res = await fetch(`/api/group_chat.php?action=search_users&group_id=${GROUP_ID}&q=${encodeURIComponent(query)}`);
                const data = await res.json();
                if (!Array.isArray(data) || data.length === 0) {
                    resultsEl.style.display = 'none';
                    noResultsEl.style.display = 'block';
                    return;
                }
                noResultsEl.style.display = 'none';
                resultsEl.innerHTML = data.map(u => {
                    const avatar = u.avatar
                        ? `/public/images/avatars/${u.avatar}`
                        : 'public/images/avatars/user.jpg';
                    return `<div class="search-result-item" onclick="addMember(${u.id}, \`${esc(u.username)}\`)">
                    <img src="${avatar}" class="avatar avatar-sm" style="width:28px;height:28px;object-fit:cover;" onerror="this.src='public/images/avatars/user.jpg'; this.onerror=null;">
                    <span>${esc(u.username)}</span>
                </div>`;
                }).join('');
                resultsEl.style.display = 'block';
            }, 280);
        };

        window.addMember = async (userId, username) => {
            document.getElementById('add-member-results').style.display = 'none';
            document.getElementById('add-member-search').value = '';
            const fd = new FormData();
            fd.append('action', 'add_member');
            fd.append('group_id', GROUP_ID);
            fd.append('user_id', userId);
            const res = await fetch('api/group_chat.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                showToastMsg('Added!', `${username} was added to the group.`);
                if (membersOpen) loadMembers();
            } else {
                showToastMsg('Error', data.error || 'Failed.');
            }
        };

        // ── Upload Group Photo ────────────────────────────────────────────────────
        const MAX_PHOTO_BYTES = 2 * 1024 * 1024; // 2 MB

        window.uploadGroupPhoto = async input => {
            const file = input.files[0];
            const statusDiv = document.getElementById('photo-upload-status');
            const cameraBtn = input.previousElementSibling; // the camera <button>
            if (!file) return;

            // ── Client-side size and type guard ────────────────────────────────────────────
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

            if (!allowedTypes.includes(file.type)) {
                input.value = ''; // clear immediately – no upload
                if (statusDiv) {
                    statusDiv.style.cssText = [
                        'display:block',
                        'position:absolute',
                        'top:100%',
                        'left:0',
                        'right:0',
                        'margin-top:0.4rem',
                        'padding:0.45rem 0.75rem',
                        'background:rgba(255,59,48,0.12)',
                        'border:1px solid rgba(255,59,48,0.35)',
                        'border-radius:var(--radius)',
                        'font-size:0.8rem',
                        'color:var(--danger)',
                        'white-space:nowrap',
                        'z-index:50',
                    ].join(';');
                    statusDiv.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Invalid file type. Images only.';
                    setTimeout(() => { statusDiv.style.display = 'none'; statusDiv.innerHTML = ''; }, 4000);
                } else {
                    showToastMsg('Invalid file', 'Only standard images are allowed.');
                }
                return;
            }

            if (file.size > MAX_PHOTO_BYTES) {
                input.value = ''; // clear immediately – no upload
                if (statusDiv) {
                    statusDiv.style.cssText = [
                        'display:block',
                        'position:absolute',
                        'top:100%',
                        'left:0',
                        'right:0',
                        'margin-top:0.4rem',
                        'padding:0.45rem 0.75rem',
                        'background:rgba(255,59,48,0.12)',
                        'border:1px solid rgba(255,59,48,0.35)',
                        'border-radius:var(--radius)',
                        'font-size:0.8rem',
                        'color:var(--danger)',
                        'white-space:nowrap',
                        'z-index:50',
                    ].join(';');
                    statusDiv.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Photo exceeds 2 MB limit. Please choose a smaller file.';
                    setTimeout(() => { statusDiv.style.display = 'none'; statusDiv.innerHTML = ''; }, 4000);
                } else {
                    showToastMsg('File too large', 'Photo must be under 2 MB.');
                }
                return;
            }

            // ── Show loading state on camera button ───────────────────────────────
            const origIcon = cameraBtn ? cameraBtn.innerHTML : null;
            if (cameraBtn) {
                cameraBtn.disabled = true;
                cameraBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
            }
            if (statusDiv) {
                statusDiv.style.cssText = 'display:block; font-size:0.8rem; color:var(--text-muted); margin-top:0.35rem; white-space:nowrap;';
                statusDiv.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Uploading...';
            }

            const fd = new FormData();
            fd.append('action', 'update_photo');
            fd.append('group_id', GROUP_ID);
            fd.append('photo', file);

            try {
                const res = await fetch('api/group_chat.php', { method: 'POST', body: fd });
                const data = await res.json();

                if (data.success) {
                    // Smoothly swap photo in the header
                    const photoContainer = document.querySelector('.gchat-header .gchat-group-photo');
                    if (photoContainer) {
                        const newImg = document.createElement('img');
                        newImg.src = `/public/images/group_photos/${data.photo}?t=${Date.now()}`;
                        newImg.alt = 'group photo';
                        newImg.style.cssText = 'width:100%;height:100%;object-fit:cover;border-radius:50%;opacity:0;transition:opacity 0.3s ease;';
                        photoContainer.innerHTML = '';
                        photoContainer.appendChild(newImg);
                        // Fade in once loaded
                        newImg.onload = () => { newImg.style.opacity = '1'; };
                    }
                    // Also refresh the sidebar thumbnail for this group (if present)
                    const sidebarItem = document.querySelector(`.group-list-item.active .gchat-group-photo`);
                    if (sidebarItem) {
                        sidebarItem.innerHTML = `<img src="public/images/group_photos/${data.photo}?t=${Date.now()}" alt="group" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">`;
                    }
                    if (statusDiv) {
                        statusDiv.style.cssText = 'display:block; font-size:0.8rem; color:var(--success,#34c759); margin-top:0.35rem; white-space:nowrap;';
                        statusDiv.innerHTML = '<i class="fa-solid fa-check-circle"></i> Photo updated!';
                        setTimeout(() => { statusDiv.style.display = 'none'; statusDiv.innerHTML = ''; }, 3000);
                    } else {
                        showToastMsg('Done', 'Group photo updated!');
                    }
                } else {
                    if (statusDiv) {
                        statusDiv.style.cssText = 'display:block; font-size:0.8rem; color:var(--danger); margin-top:0.35rem; white-space:nowrap;';
                        statusDiv.innerHTML = `<i class="fa-solid fa-circle-xmark"></i> ${data.error || 'Upload failed.'}`;
                        setTimeout(() => { statusDiv.style.display = 'none'; statusDiv.innerHTML = ''; }, 4000);
                    } else {
                        showToastMsg('Error', data.error || 'Failed to update photo.');
                    }
                }
            } catch (err) {
                showToastMsg('Error', 'Network error during upload.');
            } finally {
                if (cameraBtn) {
                    cameraBtn.disabled = false;
                    if (origIcon) cameraBtn.innerHTML = origIcon;
                }
                input.value = ''; // reset so same file can be re-selected
            }
        };


        // ── Leave Group ───────────────────────────────────────────────────────────
        window.confirmLeaveGroup = async () => {
            const btn = document.getElementById('leave-group-confirm-btn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Leaving...';
            const fd = new FormData();
            fd.append('action', 'leave_group');
            fd.append('group_id', GROUP_ID);
            const res = await fetch('api/group_chat.php', { method: 'POST', body: fd });
            const data = await res.json();
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-right-from-bracket"></i> Leave Group';
            if (data.success) {
                window.location.href = 'views/chat/group.php';
            } else {
                closeModal('leave-group-modal');
                showToastMsg('Error', data.error || 'Failed to leave group.');
            }
        };

        // ── Delete Group ──────────────────────────────────────────────────────────
        window.confirmDeleteGroup = async () => {
            const btn = document.getElementById('delete-group-confirm-btn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Deleting...';
            const fd = new FormData();
            fd.append('action', 'delete_group');
            fd.append('group_id', GROUP_ID);
            const res = await fetch('api/group_chat.php', { method: 'POST', body: fd });
            const data = await res.json();
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-trash"></i> Delete Group';
            if (data.success) {
                window.location.href = 'views/chat/group.php';
            } else {
                closeModal('delete-group-modal');
                showToastMsg('Error', data.error || 'Failed to delete group.');
            }
        };

        // ── Edit Group Name ───────────────────────────────────────────────────────
        window.editGroupName = async () => {
            const currentName = document.getElementById('group-name-text').textContent;
            const newName = prompt("Enter new group name:", currentName);
            if (newName !== null && newName.trim() !== "" && newName.trim() !== currentName) {
                if (newName.trim().length > 100) {
                    showToastMsg('Error', 'Group name must be under 100 characters.');
                    return;
                }
                const fd = new FormData();
                fd.append('action', 'update_name');
                fd.append('group_id', GROUP_ID);
                fd.append('name', newName.trim());
                const res = await fetch('api/group_chat.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    document.getElementById('group-name-text').textContent = data.name;
                    // Update in left sidebar if found
                    document.querySelectorAll('.group-list-item.active').forEach(item => {
                        const nameDiv = item.querySelector('.gchat-group-photo').nextElementSibling.querySelector('div');
                        if (nameDiv) nameDiv.textContent = data.name;
                    });
                    showToastMsg('Success', 'Group name updated.');
                } else {
                    showToastMsg('Error', data.error || 'Failed to update group name.');
                }
            }
        };
    })();
</script>

<?php include '../layouts/footer.php'; ?>