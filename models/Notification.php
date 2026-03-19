<?php

class Notification
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function create($user_id, $type, $source_id, $message)
    {
        $stmt = $this->pdo->prepare("INSERT INTO notifications (user_id, type, source_id, message) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$user_id, $type, $source_id, $message]);
    }

    public function getUserNotifications($user_id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }

    public function countUnread($user_id)
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        return $stmt->fetchColumn();
    }

    public function markAllAsRead($user_id)
    {
        $stmt = $this->pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        return $stmt->execute([$user_id]);
    }
}
?>