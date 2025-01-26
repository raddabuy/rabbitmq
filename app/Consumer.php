<?php

    use Api\Database;
    use Dotenv\Dotenv;
    use PhpAmqpLib\Connection\AMQPStreamConnection;

    $connection = new AMQPStreamConnection($this->host, $this->port, $this->user, $this->pass);
    $channel = $connection->channel();

    $channel->exchange_declare($this->exchange, 'direct', false, false, false);

    list($queueName, ,) = $channel->queue_declare('', false, false, true, false);
    $channel->queue_bind($queueName, $this->exchange);

    $callback = function ($msg) {
        $post = json_decode($msg->body);
        $routingKey = $msg->delivery_info['routing_key'];
        $pdo = (new Database())->getConnection();

        $date = new \Datetime();
        $formattedDate = $date->format('Y-m-d G:i:s');
        $stmt = $pdo->prepare("INSERT INTO user_feeds(from_user_id, to_user_id, text, created_at) VALUES (?, ?, ?, ?)");

        $userId = str_replace('user_id_', $routingKey);

        if ($stmt->execute([$post['authorId'], $userId, $post['postText'], $formattedDate])) {

            $dotenv = Dotenv::createImmutable(__DIR__);
            $dotenv->load();

            $instance = stream_socket_client('tcp://localhost:' . $_ENV['WEBSOCKET_HOST'] . $_ENV['ASYNC_API']);
            fwrite($instance, json_encode(['user' => $userId, 'message' => $msg->body])  . "\n");

            echo ' [x] Feed updated successfully for user ' . $routingKey, "\n";
        }
    };

    $channel->basic_consume($queueName, '', false, true, false, false, $callback);

    try {
        $channel->consume();
    } catch (\Exception $exception) {
        echo $exception->getMessage();
    }

    $channel->close();
    $connection->close();