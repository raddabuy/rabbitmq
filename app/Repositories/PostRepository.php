<?php

namespace Api\Repositories;

require 'vendor/autoload.php';

require_once('Database.php');

use Api\Database;
use Api\Models\Post;
use PDO;
use PDOException;

class PostRepository
{
    const OFFSET = 0;
    const LIMIT = 1000;
    public function getUserPostFeed($userId)
    {
        $pdo = (new Database())->getConnection();

        try {
            $stmt = $pdo->prepare("SELECT p.id, p.text, p.user_id FROM posts p INNER JOIN friends fr ON fr.friend_id = p.user_id WHERE fr.user_id = ? ORDER BY created_at DESC OFFSET 0 LIMIT 1000");
            $stmt->execute([$userId]);
            $rowPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage());
        }

        $posts = [];

        foreach ($rowPosts as $row) {
            $post = new Post();

            $post->setId($row['id']);
            $post->setUserId($row['user_id']);
            $post->setText($row['text']);

            $posts[] = $post;
        }

        return $posts;
    }
}