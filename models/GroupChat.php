<?php

class GroupChat
{
    private $pdo;
    const MAX_MEMBERS = 50;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Create a new group chat. The creator is auto-added as first member.
     */
    public function createGroup($creator_id, $name, $photo = null)
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO group_chats (name, photo, creator_id) VALUES (?, ?, ?)"
            );
            $stmt->execute([$name, $photo, $creator_id]);
            $group_id = $this->pdo->lastInsertId();

            // Auto-add creator as first member
            $stmt2 = $this->pdo->prepare(
                "INSERT INTO group_chat_members (group_id, user_id) VALUES (?, ?)"
            );
            $stmt2->execute([$group_id, $creator_id]);

            $this->pdo->commit();
            return $group_id;
        }
        catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    /**
     * Update group photo (only creator can do this).
     */
    public function updateGroupPhoto($group_id, $creator_id, $photo)
    {
        $stmt = $this->pdo->prepare(
            "UPDATE group_chats SET photo = ? WHERE id = ? AND creator_id = ?"
        );
        return $stmt->execute([$photo, $group_id, $creator_id]);
    }

    /**
     * Update group name (only creator can do this).
     */
    public function updateGroupName($group_id, $creator_id, $name)
    {
        $stmt = $this->pdo->prepare(
            "UPDATE group_chats SET name = ? WHERE id = ? AND creator_id = ?"
        );
        return $stmt->execute([$name, $group_id, $creator_id]);
    }

    /**
     * Add a member to the group (only creator; max 50 members).
     */
    public function addMember($group_id, $creator_id, $user_id)
    {
        // Verify caller is creator
        if (!$this->isCreator($group_id, $creator_id)) {
            return ['error' => 'Only the group creator can add members.'];
        }

        // Check capacity
        $count = $this->getMemberCount($group_id);
        if ($count >= self::MAX_MEMBERS) {
            return ['error' => 'Group is full (max ' . self::MAX_MEMBERS . ' members).'];
        }

        try {
            $stmt = $this->pdo->prepare(
                "INSERT IGNORE INTO group_chat_members (group_id, user_id) VALUES (?, ?)"
            );
            $stmt->execute([$group_id, $user_id]);
            return ['success' => true];
        }
        catch (Exception $e) {
            return ['error' => 'Could not add member.'];
        }
    }

    /**
     * Kick a member from the group (only creator; cannot kick themselves).
     */
    public function kickMember($group_id, $creator_id, $user_id)
    {
        if (!$this->isCreator($group_id, $creator_id)) {
            return ['error' => 'Only the group creator can remove members.'];
        }
        if ($creator_id == $user_id) {
            return ['error' => 'Creator cannot remove themselves.'];
        }

        $stmt = $this->pdo->prepare(
            "DELETE FROM group_chat_members WHERE group_id = ? AND user_id = ?"
        );
        $stmt->execute([$group_id, $user_id]);
        return ['success' => true];
    }

    /**
     * Get all groups the user is a member of.
     */
    public function getUserGroups($user_id)
    {
        $stmt = $this->pdo->prepare("
            SELECT gc.*, 
                   (SELECT COUNT(*) FROM group_chat_members WHERE group_id = gc.id) AS member_count,
                   (gc.creator_id = ?) AS is_creator
            FROM group_chats gc
            INNER JOIN group_chat_members gcm ON gcm.group_id = gc.id
            WHERE gcm.user_id = ?
            ORDER BY gc.created_at DESC
        ");
        $stmt->execute([$user_id, $user_id]);
        return $stmt->fetchAll();
    }

    /**
     * Get a single group (if user is member).
     */
    public function getGroup($group_id, $user_id)
    {
        $stmt = $this->pdo->prepare("
            SELECT gc.*, 
                   (SELECT COUNT(*) FROM group_chat_members WHERE group_id = gc.id) AS member_count,
                   (gc.creator_id = ?) AS is_creator
            FROM group_chats gc
            INNER JOIN group_chat_members gcm ON gcm.group_id = gc.id AND gcm.user_id = ?
            WHERE gc.id = ?
        ");
        $stmt->execute([$user_id, $user_id, $group_id]);
        return $stmt->fetch();
    }

    /**
     * Get all members of a group.
     */
    public function getMembers($group_id)
    {
        $stmt = $this->pdo->prepare("
            SELECT u.id, u.username, u.avatar, gcm.joined_at,
                   (gc.creator_id = u.id) AS is_creator
            FROM group_chat_members gcm
            JOIN users u ON u.id = gcm.user_id
            JOIN group_chats gc ON gc.id = gcm.group_id
            WHERE gcm.group_id = ?
            ORDER BY is_creator DESC, gcm.joined_at ASC
        ");
        $stmt->execute([$group_id]);
        return $stmt->fetchAll();
    }

    /**
     * Send a group message.
     */
    public function sendMessage($group_id, $sender_id, $content)
    {
        // Must be a member
        if (!$this->isMember($group_id, $sender_id)) {
            return false;
        }
        $stmt = $this->pdo->prepare(
            "INSERT INTO group_messages (group_id, sender_id, content) VALUES (?, ?, ?)"
        );
        return $stmt->execute([$group_id, $sender_id, $content]);
    }

    /**
     * Get recent messages for a group.
     */
    public function getMessages($group_id, $user_id, $limit = 50)
    {
        if (!$this->isMember($group_id, $user_id)) {
            return [];
        }
        $stmt = $this->pdo->prepare("
            SELECT gm.*, u.username, u.avatar
            FROM group_messages gm
            JOIN users u ON u.id = gm.sender_id
            WHERE gm.group_id = ?
            ORDER BY gm.created_at DESC
            LIMIT ?
        ");
        $stmt->bindParam(1, $group_id, PDO::PARAM_INT);
        $stmt->bindParam(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $messages = $stmt->fetchAll();
        return array_reverse($messages);
    }

    /**
     * Leave a group (members only; creator cannot leave — must delete instead).
     */
    public function leaveGroup($group_id, $user_id)
    {
        if ($this->isCreator($group_id, $user_id)) {
            return ['error' => 'You are the creator. Delete the group instead of leaving.'];
        }
        if (!$this->isMember($group_id, $user_id)) {
            return ['error' => 'You are not a member of this group.'];
        }
        $stmt = $this->pdo->prepare(
            "DELETE FROM group_chat_members WHERE group_id = ? AND user_id = ?"
        );
        $stmt->execute([$group_id, $user_id]);
        return ['success' => true];
    }

    /**
     * Delete a group entirely (creator only). Cascades members + messages via FK.
     */
    public function deleteGroup($group_id, $creator_id)
    {
        if (!$this->isCreator($group_id, $creator_id)) {
            return ['error' => 'Only the group creator can delete this group.'];
        }
        $stmt = $this->pdo->prepare("DELETE FROM group_chats WHERE id = ? AND creator_id = ?");
        $stmt->execute([$group_id, $creator_id]);
        return ['success' => true];
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    public function isCreator($group_id, $user_id)
    {
        $stmt = $this->pdo->prepare(
            "SELECT 1 FROM group_chats WHERE id = ? AND creator_id = ?"
        );
        $stmt->execute([$group_id, $user_id]);
        return (bool)$stmt->fetch();
    }

    public function isMember($group_id, $user_id)
    {
        $stmt = $this->pdo->prepare(
            "SELECT 1 FROM group_chat_members WHERE group_id = ? AND user_id = ?"
        );
        $stmt->execute([$group_id, $user_id]);
        return (bool)$stmt->fetch();
    }

    public function getMemberCount($group_id)
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM group_chat_members WHERE group_id = ?"
        );
        $stmt->execute([$group_id]);
        return (int)$stmt->fetchColumn();
    }
}
?>