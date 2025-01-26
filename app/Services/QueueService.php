<?php

namespace Api\Services;

require 'vendor/autoload.php';

use Api\Database;
use Api\Models\Post;
use Dotenv\Dotenv;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class QueueService
{
    public $host;
    public $port;
    public $user;
    public $pass;
    public $exchange;

    function __construct() {
        $dotenv = Dotenv::createImmutable(__DIR__);
        $dotenv->load();

        $this->host = $_ENV['RABBITMQ_HOST'];

        $this->port = $_ENV['RABBITMQ_PORT'];
        $this->user = $_ENV['RABBITMQ_USER'];
        $this->pass = $_ENV['RABBITMQ_PASSWORD'];
        $this->exchange = 'posts';
    }

    public function publish($userId, Post $post) {
        $connection = new AMQPStreamConnection($this->host, $this->port, $this->user, $this->pass);
        $channel = $connection->channel();

        $channel->exchange_declare($this->exchange, 'direct', false, false, false);

        $dataArray = [
            'postId' => $post->getId(),
            'postText' => $post->getText(),
            'authorId' => $post->getUserId()
        ];

        $data = json_encode($dataArray, JSON_UNESCAPED_UNICODE);

        $msg = new AMQPMessage($data);

        $pdo = (new Database())->getConnection();

        $stmt = $pdo->prepare("SELECT * FROM friends WHERE user_id = ?");
        $stmt->execute([$userId]);
        $userFriends = $stmt->fetch(PDO::FETCH_ASSOC);

        foreach ($userFriends as $userFriend) {
            $routingKey = 'user_id_' . $userFriend['id'];

            $channel->basic_publish($msg, $this->exchange, $routingKey);
            echo ' [x] Sent ', $routingKey, ':', $data, "\n";
        }

        $channel->close();
        $connection->close();
    }
}