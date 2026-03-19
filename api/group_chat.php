<?php
require_once '../config/db.php';
require_once '../includes/functions.php';
require_once '../models/GroupChat.php';
require_once '../models/User.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$me = $_SESSION['user_id'];
$groupModel = new GroupChat($pdo);
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ─── GET actions ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    // Get messages for a group
    if ($action === 'messages') {
        $group_id = (int)($_GET['group_id'] ?? 0);
        if (!$group_id) {
            echo json_encode(['error' => 'Missing group_id']);
            exit;
        }

        $messages = $groupModel->getMessages($group_id, $me);
        foreach ($messages as &$msg) {
            $msg['is_mine'] = ($msg['sender_id'] == $me);
            $msg['time_ago'] = timeElapsedString($msg['created_at']);
            $msg['exact_time'] = date('M j, Y, g:i A', strtotime($msg['created_at']));
        }
        echo json_encode($messages);
        exit;
    }

    // Get members list for a group
    if ($action === 'members') {
        $group_id = (int)($_GET['group_id'] ?? 0);
        if (!$group_id) {
            echo json_encode(['error' => 'Missing group_id']);
            exit;
        }

        if (!$groupModel->isMember($group_id, $me)) {
            echo json_encode(['error' => 'Not a member']);
            exit;
        }
        echo json_encode($groupModel->getMembers($group_id));
        exit;
    }

    // Search users to add (mutual followers of creator, not already members)
    if ($action === 'search_users') {
        $group_id = (int)($_GET['group_id'] ?? 0);
        $query = sanitizeInput($_GET['q'] ?? '');
        if (!$group_id || strlen($query) < 1) {
            echo json_encode([]);
            exit;
        }
        if (!$groupModel->isCreator($group_id, $me)) {
            echo json_encode(['error' => 'Only creator can search users to add']);
            exit;
        }
        $q = '%' . $query . '%';
        // Only return users who mutually follow the creator AND are not already members
        $stmt = $pdo->prepare("
            SELECT u.id, u.username, u.avatar
            FROM users u
            WHERE u.username LIKE ?
              AND u.id != ?
              AND u.id NOT IN (
                  SELECT user_id FROM group_chat_members WHERE group_id = ?
              )
              AND EXISTS (
                  SELECT 1 FROM followers
                  WHERE follower_id = ? AND following_id = u.id
              )
              AND EXISTS (
                  SELECT 1 FROM followers
                  WHERE follower_id = u.id AND following_id = ?
              )
            LIMIT 10
        ");
        $stmt->execute([$q, $me, $group_id, $me, $me]);
        echo json_encode($stmt->fetchAll());
        exit;
    }

    echo json_encode(['error' => 'Unknown action']);
    exit;
}

// ─── POST actions ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Create group
    if ($action === 'create') {
        $name = sanitizeInput($_POST['name'] ?? '');
        if (strlen($name) < 1 || strlen($name) > 100) {
            echo json_encode(['error' => 'Group name must be 1–100 characters.']);
            exit;
        }

        $photo = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $photo = uploadMedia($_FILES['photo'], 'group_photos');
            if ($photo === 'SIZE_EXCEEDED') {
                echo json_encode(['error' => 'Photo must be under 2 MB.']);
                exit;
            }
            if (!$photo) {
                echo json_encode(['error' => 'Invalid photo format.']);
                exit;
            }
        }

        $group_id = $groupModel->createGroup($me, $name, $photo);
        if ($group_id) {
            echo json_encode(['success' => true, 'group_id' => $group_id]);
        }
        else {
            echo json_encode(['error' => 'Failed to create group.']);
        }
        exit;
    }

    // Update group photo
    if ($action === 'update_photo') {
        $group_id = (int)($_POST['group_id'] ?? 0);
        if (!$group_id) {
            echo json_encode(['error' => 'Missing group_id']);
            exit;
        }

        if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['error' => 'No photo uploaded.']);
            exit;
        }

        $photo = uploadMedia($_FILES['photo'], 'group_photos');
        if ($photo === 'SIZE_EXCEEDED') {
            echo json_encode(['error' => 'Photo must be under 2 MB.']);
            exit;
        }
        if (!$photo) {
            echo json_encode(['error' => 'Invalid photo format.']);
            exit;
        }

        if ($groupModel->updateGroupPhoto($group_id, $me, $photo)) {
            echo json_encode(['success' => true, 'photo' => $photo]);
        }
        else {
            echo json_encode(['error' => 'Failed to update photo. Are you the creator?']);
        }
        exit;
    }

    // Update group name
    if ($action === 'update_name') {
        $group_id = (int)($_POST['group_id'] ?? 0);
        $name = sanitizeInput($_POST['name'] ?? '');

        if (!$group_id) {
            echo json_encode(['error' => 'Missing group_id']);
            exit;
        }

        if (strlen($name) < 1 || strlen($name) > 100) {
            echo json_encode(['error' => 'Group name must be 1–100 characters.']);
            exit;
        }

        if ($groupModel->updateGroupName($group_id, $me, $name)) {
            echo json_encode(['success' => true, 'name' => $name]);
        }
        else {
            echo json_encode(['error' => 'Failed to update group name. Are you the creator?']);
        }
        exit;
    }

    // Add member
    if ($action === 'add_member') {
        $group_id = (int)($_POST['group_id'] ?? 0);
        $user_id = (int)($_POST['user_id'] ?? 0);
        if (!$group_id || !$user_id) {
            echo json_encode(['error' => 'Missing params']);
            exit;
        }
        echo json_encode($groupModel->addMember($group_id, $me, $user_id));
        exit;
    }

    // Kick member
    if ($action === 'kick_member') {
        $group_id = (int)($_POST['group_id'] ?? 0);
        $user_id = (int)($_POST['user_id'] ?? 0);
        if (!$group_id || !$user_id) {
            echo json_encode(['error' => 'Missing params']);
            exit;
        }
        echo json_encode($groupModel->kickMember($group_id, $me, $user_id));
        exit;
    }

    // Send message
    if ($action === 'send_message') {
        $group_id = (int)($_POST['group_id'] ?? 0);
        $content = sanitizeInput($_POST['content'] ?? '');
        if (!$group_id || $content === '') {
            echo json_encode(['error' => 'Empty message']);
            exit;
        }
        if ($groupModel->sendMessage($group_id, $me, $content)) {
            echo json_encode(['success' => true]);
        }
        else {
            echo json_encode(['error' => 'Failed to send message or not a member.']);
        }
        exit;
    }

    // Leave group (any member except creator)
    if ($action === 'leave_group') {
        $group_id = (int)($_POST['group_id'] ?? 0);
        if (!$group_id) {
            echo json_encode(['error' => 'Missing group_id']);
            exit;
        }
        echo json_encode($groupModel->leaveGroup($group_id, $me));
        exit;
    }

    // Delete group (creator only – cascades everything)
    if ($action === 'delete_group') {
        $group_id = (int)($_POST['group_id'] ?? 0);
        if (!$group_id) {
            echo json_encode(['error' => 'Missing group_id']);
            exit;
        }
        echo json_encode($groupModel->deleteGroup($group_id, $me));
        exit;
    }

    echo json_encode(['error' => 'Unknown action']);
    exit;
}

echo json_encode(['error' => 'Invalid request method']);
?>