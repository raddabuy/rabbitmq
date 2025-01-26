<?php

declare(strict_types=1);

require 'vendor/autoload.php';

require_once('Api.php');
require_once('Database.php');
require_once('Models/Post.php');
require_once('Repositories/PostRepository.php');
require_once('Services/QueueService.php');

use Api\Api;
use Api\Database;
use Api\Services\QueueService;

class PostApi extends Api
{
    public function create($request)
    {
        $decoded = $this->checkAuth();

        $userId = $decoded->id;
        $text = $this->postRequest['text'] ?? null;

        if (empty($text)) {
            return $this->response(['message' => 'Text is required.'], 422);
        }

        $pdo = (new Database())->getConnection();

        $date = new \Datetime();
        $formattedDate = $date->format('Y-m-d G:i:s');
        $stmt = $pdo->prepare("INSERT INTO posts(user_id, text, created_at) VALUES (?, ?, ?) RETURNING id");

        if ($stmt->execute([$userId, $text, $formattedDate])) {
            $postId = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
            $stmt->execute([$postId['id']]);
            $post = $stmt->fetch(PDO::FETCH_ASSOC);

            $queueService = new QueueService();
            $queueService->publish($userId, $post);

            return $this->response('Post is added successfully', 200);
        } else {
            return $this->response('Failed to add post', 500);
        }
    }
}