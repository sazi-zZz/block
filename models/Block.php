<?php

class Block
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function create($creator_id, $name, $description, $tags, $icon, $privacy = 'public')
    {
        $stmt = $this->pdo->prepare("INSERT INTO blocks (creator_id, name, description, tags, icon, privacy) VALUES (?, ?, ?, ?, ?, ?)");
        try {
            $stmt->execute([$creator_id, $name, $description, $tags, $icon, $privacy]);
            return $this->pdo->lastInsertId();
        }
        catch (PDOException $e) {
            return false;
        }
    }

    public function getById($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM blocks WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getAll($viewer_id = null)
    {
        $query = "
            SELECT * FROM blocks 
            WHERE (
                privacy = 'public'
                OR creator_id = :viewer
                OR (privacy = 'followers' AND EXISTS (SELECT 1 FROM followers WHERE follower_id = :viewer AND following_id = blocks.creator_id))
                OR (privacy = 'following' AND EXISTS (SELECT 1 FROM followers WHERE follower_id = blocks.creator_id AND following_id = :viewer))
                OR (privacy = 'followers_and_following' AND (
                    EXISTS (SELECT 1 FROM followers WHERE follower_id = :viewer AND following_id = blocks.creator_id)
                    OR EXISTS (SELECT 1 FROM followers WHERE follower_id = blocks.creator_id AND following_id = :viewer)
                ))
            )
            ORDER BY created_at DESC
        ";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute(['viewer' => $viewer_id]);
        return $stmt->fetchAll();
    }

    public function getAllFiltered($viewer_id = null, $sort = 'newest', $search = '', $dateFrom = '', $dateTo = '', $filterJoined = false)
    {
        $orderSql = match ($sort) {
                'oldest' => 'b.created_at ASC',
                'most_users' => 'member_count DESC, b.created_at DESC',
                'least_users' => 'member_count ASC, b.created_at DESC',
                default => 'b.created_at DESC', // newest
            };

        $whereClauses = [];
        $params = ['viewer' => $viewer_id];

        if (!empty($search)) {
            $whereClauses[] = '(b.name LIKE :search OR b.tags LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        if (!empty($dateFrom)) {
            $whereClauses[] = 'b.created_at >= :date_from';
            $params['date_from'] = $dateFrom . ' 00:00:00';
        }

        if (!empty($dateTo)) {
            $whereClauses[] = 'b.created_at <= :date_to';
            $params['date_to'] = $dateTo . ' 23:59:59';
        }

        if ($filterJoined) {
            $whereClauses[] = 'EXISTS (SELECT 1 FROM block_members bm2 WHERE bm2.block_id = b.id AND bm2.user_id = :viewer)';
        }

        $whereExtra = !empty($whereClauses) ? 'AND ' . implode(' AND ', $whereClauses) : '';

        $query = "
            SELECT b.*,
                   COUNT(bm.user_id) AS member_count
            FROM blocks b
            LEFT JOIN block_members bm ON bm.block_id = b.id
            WHERE (
                b.privacy = 'public'
                OR b.creator_id = :viewer
                OR (b.privacy = 'followers' AND EXISTS (SELECT 1 FROM followers WHERE follower_id = :viewer AND following_id = b.creator_id))
                OR (b.privacy = 'following' AND EXISTS (SELECT 1 FROM followers WHERE follower_id = b.creator_id AND following_id = :viewer))
                OR (b.privacy = 'followers_and_following' AND (
                    EXISTS (SELECT 1 FROM followers WHERE follower_id = :viewer AND following_id = b.creator_id)
                    OR EXISTS (SELECT 1 FROM followers WHERE follower_id = b.creator_id AND following_id = :viewer)
                ))
            )
            $whereExtra
            GROUP BY b.id
            ORDER BY $orderSql
        ";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function search($query, $viewer_id = null)
    {
        $sql = "
            SELECT * FROM blocks 
            WHERE (name LIKE :search OR tags LIKE :search)
            AND (
                privacy = 'public'
                OR creator_id = :viewer
                OR (privacy = 'followers' AND EXISTS (SELECT 1 FROM followers WHERE follower_id = :viewer AND following_id = blocks.creator_id))
                OR (privacy = 'following' AND EXISTS (SELECT 1 FROM followers WHERE follower_id = blocks.creator_id AND following_id = :viewer))
                OR (privacy = 'followers_and_following' AND (
                    EXISTS (SELECT 1 FROM followers WHERE follower_id = :viewer AND following_id = blocks.creator_id)
                    OR EXISTS (SELECT 1 FROM followers WHERE follower_id = blocks.creator_id AND following_id = :viewer)
                ))
            )
            ORDER BY created_at DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $search = "%$query%";
        $stmt->execute(['search' => $search, 'viewer' => $viewer_id]);
        return $stmt->fetchAll();
    }

    public function addMember($block_id, $user_id, $role = 'member')
    {
        $stmt = $this->pdo->prepare("INSERT IGNORE INTO block_members (block_id, user_id, role) VALUES (?, ?, ?)");
        return $stmt->execute([$block_id, $user_id, $role]);
    }

    public function removeMember($block_id, $user_id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM block_members WHERE block_id = ? AND user_id = ?");
        return $stmt->execute([$block_id, $user_id]);
    }

    public function getMembers($block_id)
    {
        $stmt = $this->pdo->prepare("
            SELECT users.id, users.username, users.avatar, block_members.role 
            FROM block_members 
            JOIN users ON block_members.user_id = users.id 
            WHERE block_members.block_id = ?
            ORDER BY 
                CASE block_members.role WHEN 'creator' THEN 0 WHEN 'admin' THEN 1 ELSE 2 END ASC,
                users.username ASC
        ");
        $stmt->execute([$block_id]);
        return $stmt->fetchAll();
    }

    public function isMember($block_id, $user_id)
    {
        $stmt = $this->pdo->prepare("SELECT role FROM block_members WHERE block_id = ? AND user_id = ?");
        $stmt->execute([$block_id, $user_id]);
        return $stmt->fetch();
    }

    public function getUserBlocks($user_id)
    {
        $stmt = $this->pdo->prepare("
            SELECT blocks.* FROM blocks
            JOIN block_members ON blocks.id = block_members.block_id
            WHERE block_members.user_id = ?
            ORDER BY blocks.name ASC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }

    public function update($id, $name, $description, $tags, $icon, $privacy = 'public')
    {
        if ($icon) {
            $stmt = $this->pdo->prepare("UPDATE blocks SET name = ?, description = ?, tags = ?, icon = ?, privacy = ? WHERE id = ?");
            return $stmt->execute([$name, $description, $tags, $icon, $privacy, $id]);
        }
        else {
            $stmt = $this->pdo->prepare("UPDATE blocks SET name = ?, description = ?, tags = ?, privacy = ? WHERE id = ?");
            return $stmt->execute([$name, $description, $tags, $privacy, $id]);
        }
    }

    public function getCreatedBlocks($user_id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM blocks WHERE creator_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }

    public function searchCreatedBlocks($user_id, $query = '', $order = 'newest', $dateFrom = '', $dateTo = '')
    {
        $orderSql = match ($order) {
                'oldest' => 'b.created_at ASC',
                'most_users' => 'member_count DESC, b.created_at DESC',
                'least_users' => 'member_count ASC, b.created_at DESC',
                default => 'b.created_at DESC', // newest
            };

        $params = [$user_id];
        $whereClauses = [];

        if ($query) {
            $whereClauses[] = '(b.name LIKE ? OR b.description LIKE ? OR b.tags LIKE ?)';
            $like = "%$query%";
            array_push($params, $like, $like, $like);
        }

        if (!empty($dateFrom)) {
            $whereClauses[] = 'b.created_at >= ?';
            $params[] = $dateFrom . ' 00:00:00';
        }

        if (!empty($dateTo)) {
            $whereClauses[] = 'b.created_at <= ?';
            $params[] = $dateTo . ' 23:59:59';
        }

        $whereExtra = !empty($whereClauses) ? 'AND ' . implode(' AND ', $whereClauses) : '';

        $stmt = $this->pdo->prepare("
            SELECT b.*, COUNT(bm.user_id) AS member_count
            FROM blocks b
            LEFT JOIN block_members bm ON bm.block_id = b.id
            WHERE b.creator_id = ?
            $whereExtra
            GROUP BY b.id
            ORDER BY $orderSql
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function delete($id)
    {
        // Get the block to delete its icon
        $block = $this->getById($id);

        // 1. Delete all post images and comment media within this block
        $stmt = $this->pdo->prepare("SELECT id, image FROM posts WHERE block_id = ?");
        $stmt->execute([$id]);
        $posts = $stmt->fetchAll();

        foreach ($posts as $post) {
            // Delete post image
            if ($post['image']) {
                $imagePath = __DIR__ . '/../public/images/post_images/' . $post['image'];
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            // Delete comment media for each post
            $cStmt = $this->pdo->prepare("SELECT media FROM comments WHERE post_id = ?");
            $cStmt->execute([$post['id']]);
            $comments = $cStmt->fetchAll();
            foreach ($comments as $comment) {
                if ($comment['media']) {
                    $mediaPath = __DIR__ . '/../public/images/comment_media/' . $comment['media'];
                    if (file_exists($mediaPath)) {
                        unlink($mediaPath);
                    }
                }
            }
        }

        // 2. Delete chat media within this block
        $mStmt = $this->pdo->prepare("SELECT media FROM messages WHERE block_id = ? AND media IS NOT NULL");
        $mStmt->execute([$id]);
        $messages = $mStmt->fetchAll();
        foreach ($messages as $msg) {
            $mediaPath = __DIR__ . '/../public/images/chat_media/' . $msg['media'];
            if (file_exists($mediaPath)) {
                unlink($mediaPath);
            }
        }

        // 3. Delete block icon if it's not default
        if ($block && $block['icon'] && $block['icon'] !== 'default_block.jpg') {
            $iconPath = __DIR__ . '/../public/images/block_icons/' . $block['icon'];
            if (file_exists($iconPath)) {
                unlink($iconPath);
            }
        }

        $stmt = $this->pdo->prepare("DELETE FROM blocks WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
?>