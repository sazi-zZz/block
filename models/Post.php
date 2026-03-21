<?php

class Post
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Estimates 0-100% probability that the text is AI-generated.
     * Uses 7 weighted heuristic signals drawn from AI-detection research.
     * No external API needed - runs entirely offline and instantly.
     */
    private function detectAIPercentage($text)
    {
        $text = trim($text);
        if (empty($text) || strlen($text) < 60) {
            return 0;
        }

        // Tokenise
        $lower = strtolower($text);
        $words = preg_split('/\s+/', $lower, -1, PREG_SPLIT_NO_EMPTY);
        $wordCount = count($words);
        if ($wordCount < 10)
            return 0;

        $sentences = preg_split('/(?<=[.!?])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $sentenceCount = max(1, count($sentences));

        // Signal 1 - Low Type-Token Ratio (AI reuses vocabulary more)
        $ttr = count(array_unique($words)) / $wordCount;
        $ttrScore = max(0, min(1, (0.65 - $ttr) / 0.40));

        // Signal 2 - Uniform sentence length (AI lengths cluster tightly)
        $sentLengths = array_map('str_word_count', $sentences);
        $avgLen = array_sum($sentLengths) / $sentenceCount;
        $variance = 0;
        foreach ($sentLengths as $l) {
            $variance += ($l - $avgLen) ** 2;
        }
        $uniformityScore = max(0, min(1, (8 - sqrt($variance / $sentenceCount)) / 8));

        // Signal 3 - Typical AI discourse markers / openers
        $aiPhrases = [
            'in conclusion', 'in summary', 'it is important to note',
            'it is worth noting', 'it should be noted', 'as an ai',
            'as a language model', 'delve into', 'delving into',
            "it's important to", "it's crucial to", 'pivotal',
            'groundbreaking', 'transformative', 'a testament to',
            'first and foremost', 'this comprehensive', 'tailored to',
        ];
        $phraseHits = 0;
        foreach ($aiPhrases as $p) {
            $phraseHits += substr_count($lower, $p);
        }
        $phraseScore = min(1, $phraseHits / 3);

        // Signal 4 - Comma density (AI loves enumerated lists)
        $commaScore = min(1, (substr_count($text, ',') / $wordCount) / 0.18);

        // Signal 5 - Hedge-word density
        $hedges = [
            'furthermore', 'however', 'moreover', 'nevertheless',
            'accordingly', 'consequently', 'therefore', 'thus',
            'subsequently', 'notably', 'undeniably', 'evidently',
            'ultimately', 'overall', 'essentially', 'generally',
        ];
        $hedgeHits = 0;
        foreach ($hedges as $h) {
            $hedgeHits += substr_count($lower, $h);
        }
        $hedgeScore = min(1, $hedgeHits / max(1, $wordCount / 50));

        // Signal 6 - Transition-phrase density
        $transitions = [
            'in addition', 'on the other hand', 'as a result',
            'in contrast', 'for example', 'such as', 'in particular',
            'to summarize', 'in other words', 'that being said',
        ];
        $transHits = 0;
        foreach ($transitions as $t) {
            $transHits += substr_count($lower, $t);
        }
        $transScore = min(1, $transHits / max(1, $wordCount / 60));

        // Signal 7 - Average word length (AI favours formal vocabulary)
        $avgWordLen = array_sum(array_map('strlen', $words)) / $wordCount;
        $wordLenScore = max(0, min(1, ($avgWordLen - 4.5) / 2.5));

        // Weighted combination
        $score = (
            $ttrScore * 0.20 +
            $uniformityScore * 0.20 +
            $phraseScore * 0.20 +
            $commaScore * 0.10 +
            $hedgeScore * 0.12 +
            $transScore * 0.10 +
            $wordLenScore * 0.08
            );

        return (int)round(min(100, max(0, $score * 100)));
    }

    public function create($block_id, $user_id, $title, $content, $image = null, $privacy = 'public', $repost_id = null)
    {
        $ai_percentage = $this->detectAIPercentage($title . ' ' . $content);
        $stmt = $this->pdo->prepare("INSERT INTO posts (block_id, user_id, title, content, image, privacy, repost_id, ai_percentage) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$block_id, $user_id, $title, $content, $image, $privacy, $repost_id, $ai_percentage]);
    }

    public function getFeed($viewer_id, $block_id = null, $sort = 'newest', $date_from = null, $date_to = null, $since_id = null, $limit = null, $offset = 0, $seed = null, $joined_blocks = false)
    {
        $query = "
            SELECT posts.*, users.username, users.avatar, blocks.name as block_name,
                   (SELECT COUNT(*) FROM comments WHERE post_id = posts.id) as comment_count,
                   (SELECT COUNT(*) FROM likes WHERE post_id = posts.id) as like_count,
                   ((SELECT COUNT(*) FROM likes WHERE post_id = posts.id) + (SELECT COUNT(*) FROM comments WHERE post_id = posts.id)) as popularity_score,
                   rp.title as repost_title, rp.content as repost_content, rp.image as repost_image, rpu.username as repost_username, rpu.avatar as repost_avatar, rpb.name as repost_block_name, rp.id as rp_id, rpu.id as rp_user_id, rpb.id as rp_block_id
            FROM posts
            JOIN users ON posts.user_id = users.id
            JOIN blocks ON posts.block_id = blocks.id
            LEFT JOIN posts rp ON posts.repost_id = rp.id
            LEFT JOIN users rpu ON rp.user_id = rpu.id
            LEFT JOIN blocks rpb ON rp.block_id = rpb.id
            WHERE (
                posts.privacy = 'public'
                OR posts.user_id = :viewer
                OR (posts.privacy = 'followers' AND EXISTS (SELECT 1 FROM followers WHERE follower_id = :viewer AND following_id = posts.user_id))
                OR (posts.privacy = 'following' AND EXISTS (SELECT 1 FROM followers WHERE follower_id = posts.user_id AND following_id = :viewer))
                OR (posts.privacy = 'followers_and_following' AND (
                    EXISTS (SELECT 1 FROM followers WHERE follower_id = :viewer AND following_id = posts.user_id)
                    OR EXISTS (SELECT 1 FROM followers WHERE follower_id = posts.user_id AND following_id = :viewer)
                ))
                OR (posts.privacy = 'block_only' AND EXISTS (SELECT 1 FROM block_members WHERE block_id = posts.block_id AND user_id = :viewer))
            )
        ";

        $params = ['viewer' => $viewer_id];

        if ($block_id) {
            $query .= " AND posts.block_id = :block_id";
            $params['block_id'] = $block_id;
        }

        if ($joined_blocks) {
            $query .= " AND EXISTS (SELECT 1 FROM block_members WHERE block_id = posts.block_id AND user_id = :viewer_jb)";
            $params['viewer_jb'] = $viewer_id;
        }

        if ($date_from) {
            $query .= " AND DATE(posts.created_at) >= :date_from";
            $params['date_from'] = $date_from;
        }

        if ($date_to) {
            $query .= " AND DATE(posts.created_at) <= :date_to";
            $params['date_to'] = $date_to;
        }

        if ($since_id) {
            $query .= " AND posts.id > :since_id";
            $params['since_id'] = $since_id;
        }

        switch ($sort) {
            case 'oldest':
                $query .= " ORDER BY posts.created_at ASC";
                break;
            case 'popular':
                $query .= " ORDER BY popularity_score DESC, posts.created_at DESC";
                break;
            case 'least_popular':
                $query .= " ORDER BY popularity_score ASC, posts.created_at DESC";
                break;
            case 'random':
                if ($seed !== null) {
                    $query .= " ORDER BY RAND(:seed)";
                    $params['seed'] = (int)$seed;
                }
                else {
                    $query .= " ORDER BY RAND()";
                }
                break;
            case 'newest':
            default:
                $query .= " ORDER BY posts.created_at DESC";
                break;
        }

        if ($limit !== null) {
            $query .= " LIMIT :limit OFFSET :offset";
            // Need to bind these explicitly as parameters below might be bound via execute array
            $stmt = $this->pdo->prepare($query);

            // Re-bind other params manually so we can bind limits as INT
            foreach ($params as $key => $val) {
                $stmt->bindValue(":$key", $val);
            }
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        }

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function getById($id)
    {
        $stmt = $this->pdo->prepare("
            SELECT posts.*, users.username, users.avatar, blocks.name as block_name,
                   (SELECT COUNT(*) FROM comments WHERE post_id = posts.id) as comment_count,
                   (SELECT COUNT(*) FROM likes WHERE post_id = posts.id) as like_count,
                   rp.title as repost_title, rp.content as repost_content, rp.image as repost_image, rpu.username as repost_username, rpu.avatar as repost_avatar, rpb.name as repost_block_name, rp.id as rp_id, rpu.id as rp_user_id, rpb.id as rp_block_id
            FROM posts
            JOIN users ON posts.user_id = users.id
            JOIN blocks ON posts.block_id = blocks.id
            LEFT JOIN posts rp ON posts.repost_id = rp.id
            LEFT JOIN users rpu ON rp.user_id = rpu.id
            LEFT JOIN blocks rpb ON rp.block_id = rpb.id
            WHERE posts.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function like($post_id, $user_id)
    {
        $stmt = $this->pdo->prepare("INSERT IGNORE INTO likes (post_id, user_id) VALUES (?, ?)");
        return $stmt->execute([$post_id, $user_id]);
    }

    public function unlike($post_id, $user_id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM likes WHERE post_id = ? AND user_id = ?");
        return $stmt->execute([$post_id, $user_id]);
    }

    public function isLiked($post_id, $user_id)
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ? AND user_id = ?");
        $stmt->execute([$post_id, $user_id]);
        return $stmt->fetchColumn() > 0;
    }

    public function addComment($post_id, $user_id, $content, $parent_id = null, $media = null)
    {
        $ai_percentage = $this->detectAIPercentage($content);
        $stmt = $this->pdo->prepare("INSERT INTO comments (post_id, user_id, content, parent_id, media, ai_percentage) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$post_id, $user_id, $content, $parent_id, $media, $ai_percentage])) {
            return $this->pdo->lastInsertId();
        }
        return false;
    }

    public function getComments($post_id)
    {
        $stmt = $this->pdo->prepare("
            SELECT comments.*, users.username, users.avatar
            FROM comments
            JOIN users ON comments.user_id = users.id
            WHERE comments.post_id = ?
            ORDER BY comments.created_at ASC
        ");
        $stmt->execute([$post_id]);
        return $stmt->fetchAll();
    }

    public function update($id, $title, $content, $image = null, $privacy = 'public')
    {
        $ai_percentage = $this->detectAIPercentage($title . ' ' . $content);
        if ($image) {
            $stmt = $this->pdo->prepare("UPDATE posts SET title = ?, content = ?, image = ?, privacy = ?, ai_percentage = ? WHERE id = ?");
            return $stmt->execute([$title, $content, $image, $privacy, $ai_percentage, $id]);
        }
        else {
            $stmt = $this->pdo->prepare("UPDATE posts SET title = ?, content = ?, privacy = ?, ai_percentage = ? WHERE id = ?");
            return $stmt->execute([$title, $content, $privacy, $ai_percentage, $id]);
        }
    }

    public function getCommentById($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM comments WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function updateComment($id, $content, $media = null)
    {
        $ai_percentage = $this->detectAIPercentage($content);
        if ($media) {
            $stmt = $this->pdo->prepare("UPDATE comments SET content = ?, media = ?, ai_percentage = ? WHERE id = ?");
            return $stmt->execute([$content, $media, $ai_percentage, $id]);
        }
        else {
            $stmt = $this->pdo->prepare("UPDATE comments SET content = ?, ai_percentage = ? WHERE id = ?");
            return $stmt->execute([$content, $ai_percentage, $id]);
        }
    }

    public function deleteComment($id)
    {
        $comment = $this->getCommentById($id);
        if ($comment && $comment['media']) {
            $mediaPath = __DIR__ . '/../public/images/comment_media/' . $comment['media'];
            if (file_exists($mediaPath)) {
                unlink($mediaPath);
            }
        }
        $stmt = $this->pdo->prepare("DELETE FROM comments WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function delete($id)
    {
        // First get the image path to delete it from filesystem
        $post = $this->getById($id);
        if ($post && $post['image']) {
            $imagePath = __DIR__ . '/../public/images/post_images/' . $post['image'];
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }

        // Also delete comment media if any
        $comments = $this->getComments($id);
        foreach ($comments as $comment) {
            if ($comment['media']) {
                $mediaPath = __DIR__ . '/../public/images/comment_media/' . $comment['media'];
                if (file_exists($mediaPath)) {
                    unlink($mediaPath);
                }
            }
        }

        $stmt = $this->pdo->prepare("DELETE FROM posts WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function searchPosts($viewer_id, $keyword)
    {
        $query = "
            SELECT posts.*, users.username, users.avatar, blocks.name as block_name,
                   (SELECT COUNT(*) FROM comments WHERE post_id = posts.id) as comment_count,
                   (SELECT COUNT(*) FROM likes WHERE post_id = posts.id) as like_count,
                   rp.title as repost_title, rp.content as repost_content, rp.image as repost_image, rpu.username as repost_username, rpu.avatar as repost_avatar, rpb.name as repost_block_name, rp.id as rp_id, rpu.id as rp_user_id, rpb.id as rp_block_id
            FROM posts
            JOIN users ON posts.user_id = users.id
            JOIN blocks ON posts.block_id = blocks.id
            LEFT JOIN posts rp ON posts.repost_id = rp.id
            LEFT JOIN users rpu ON rp.user_id = rpu.id
            LEFT JOIN blocks rpb ON rp.block_id = rpb.id
            WHERE (posts.title LIKE :keyword OR posts.content LIKE :keyword)
            AND (
                posts.privacy = 'public'
                OR posts.user_id = :viewer
                OR (posts.privacy = 'followers' AND EXISTS (SELECT 1 FROM followers WHERE follower_id = :viewer AND following_id = posts.user_id))
                OR (posts.privacy = 'following' AND EXISTS (SELECT 1 FROM followers WHERE follower_id = posts.user_id AND following_id = :viewer))
                OR (posts.privacy = 'followers_and_following' AND (
                    EXISTS (SELECT 1 FROM followers WHERE follower_id = :viewer AND following_id = posts.user_id)
                    OR EXISTS (SELECT 1 FROM followers WHERE follower_id = posts.user_id AND following_id = :viewer)
                ))
                OR (posts.privacy = 'block_only' AND EXISTS (SELECT 1 FROM block_members WHERE block_id = posts.block_id AND user_id = :viewer))
            )
            ORDER BY posts.created_at DESC
            LIMIT 50
        ";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute(['keyword' => "%$keyword%", 'viewer' => $viewer_id]);
        return $stmt->fetchAll();
    }
}
?>