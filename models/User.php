<?php

class User
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function register($username, $email, $password)
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        try {
            return $stmt->execute([$username, $email, $hash]);
        }
        catch (PDOException $e) {
            return false;
        }
    }

    public function login($username_or_email, $password)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username_or_email, $username_or_email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    }

    public function getUserById($id)
    {
        $stmt = $this->pdo->prepare("SELECT id, username, email, bio, avatar, cover_photo, created_at FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function countFollowers($user_id)
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM followers WHERE following_id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetchColumn();
    }

    public function countFollowing($user_id)
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM followers WHERE follower_id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetchColumn();
    }

    public function follow($follower_id, $following_id)
    {
        if ($follower_id == $following_id)
            return false;
        $stmt = $this->pdo->prepare("INSERT IGNORE INTO followers (follower_id, following_id) VALUES (?, ?)");
        return $stmt->execute([$follower_id, $following_id]);
    }

    public function unfollow($follower_id, $following_id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM followers WHERE follower_id = ? AND following_id = ?");
        return $stmt->execute([$follower_id, $following_id]);
    }

    public function isFollowing($follower_id, $following_id)
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM followers WHERE follower_id = ? AND following_id = ?");
        $stmt->execute([$follower_id, $following_id]);
        return $stmt->fetchColumn() > 0;
    }

    public function updateProfile($user_id, $bio, $avatar = null, $cover_photo = null)
    {
        $query = "UPDATE users SET bio = ?";
        $params = [$bio];

        if ($avatar) {
            $query .= ", avatar = ?";
            $params[] = $avatar;
        }

        if ($cover_photo) {
            $query .= ", cover_photo = ?";
            $params[] = $cover_photo;
        }

        $query .= " WHERE id = ?";
        $params[] = $user_id;

        $stmt = $this->pdo->prepare($query);
        return $stmt->execute($params);
    }

    public function getFollowers($user_id)
    {
        $stmt = $this->pdo->prepare("
            SELECT users.id, users.username, users.avatar, users.bio 
            FROM followers 
            JOIN users ON followers.follower_id = users.id 
            WHERE followers.following_id = ?
            ORDER BY followers.created_at DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }

    public function getFollowing($user_id)
    {
        $stmt = $this->pdo->prepare("
            SELECT users.id, users.username, users.avatar, users.bio 
            FROM followers 
            JOIN users ON followers.following_id = users.id 
            WHERE followers.follower_id = ?
            ORDER BY followers.created_at DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }

    public function searchUsers($keyword)
    {
        $stmt = $this->pdo->prepare("
            SELECT id, username, avatar, bio 
            FROM users 
            WHERE username LIKE ? OR bio LIKE ? 
            ORDER BY created_at DESC 
            LIMIT 50
        ");
        $term = "%$keyword%";
        $stmt->execute([$term, $term]);
        return $stmt->fetchAll();
    }

    public function findByEmail($email)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public function setResetToken($user_id, $token, $expires)
    {
        $stmt = $this->pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
        return $stmt->execute([$token, $expires, $user_id]);
    }

    public function findByResetToken($token)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_expires > NOW()");
        $stmt->execute([$token]);
        return $stmt->fetch();
    }

    public function updatePassword($user_id, $newPassword)
    {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
        return $stmt->execute([$hash, $user_id]);
    }
}
?>