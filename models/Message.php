<?php

class Message
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getGlobalMessages($limit = 50)
    {
        $stmt = $this->pdo->prepare("
            SELECT messages.*, users.username, users.avatar 
            FROM messages 
            JOIN users ON messages.sender_id = users.id 
            WHERE messages.block_id IS NULL AND messages.receiver_id IS NULL
            ORDER BY messages.created_at DESC
            LIMIT ?
        ");
        $stmt->bindParam(1, $limit, PDO::PARAM_INT);
        $stmt->execute();

        $messages = $stmt->fetchAll();
        return array_reverse($messages); // Return older first for chat view
    }

    public function createGlobalMessage($sender_id, $content, $media = null)
    {
        $stmt = $this->pdo->prepare("INSERT INTO messages (sender_id, content, media) VALUES (?, ?, ?)");
        return $stmt->execute([$sender_id, $content, $media]);
    }
    public function getPrivateMessages($user_id, $other_user_id, $limit = 50)
    {
        $stmt = $this->pdo->prepare("
            SELECT messages.*, users.username, users.avatar 
            FROM messages 
            JOIN users ON messages.sender_id = users.id 
            WHERE (messages.sender_id = ? AND messages.receiver_id = ?) 
               OR (messages.sender_id = ? AND messages.receiver_id = ?)
            ORDER BY messages.created_at DESC
            LIMIT ?
        ");
        $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
        $stmt->bindParam(2, $other_user_id, PDO::PARAM_INT);
        $stmt->bindParam(3, $other_user_id, PDO::PARAM_INT);
        $stmt->bindParam(4, $user_id, PDO::PARAM_INT);
        $stmt->bindParam(5, $limit, PDO::PARAM_INT);
        $stmt->execute();

        $messages = $stmt->fetchAll();
        return array_reverse($messages);
    }

    public function createPrivateMessage($sender_id, $receiver_id, $content, $media = null)
    {
        $stmt = $this->pdo->prepare("INSERT INTO messages (sender_id, receiver_id, content, media) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$sender_id, $receiver_id, $content, $media]);
    }

    public function getRecentConversations($user_id)
    {
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT u.id, u.username, u.avatar 
            FROM users u
            JOIN messages m ON (u.id = m.sender_id OR u.id = m.receiver_id)
            WHERE (m.sender_id = ? OR m.receiver_id = ?) AND u.id != ?
            ORDER BY m.created_at DESC
        ");
        $stmt->execute([$user_id, $user_id, $user_id]);
        return $stmt->fetchAll();
    }
}
?>