<?php
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Workerman\Worker;

$users = [];

$ws_worker = new Worker("websocket://0.0.0.0:8000");

$ws_worker->onWorkerStart = function() use (&$users)
{
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    $inner_tcp_worker = new Worker("tcp://localhost:" . $_ENV['WEBSOCKET_HOST'] . $_ENV['ASYNC_API']);

    $inner_tcp_worker->onMessage = function($connection, $data) use (&$users) {
        $data = json_decode($data);

        if (isset($users[$data->user])) {
            $conn = $users[$data->user];
            $conn->send($data->message);
        }
    };
    $inner_tcp_worker->listen();
};

$ws_worker->onConnect = function($connection) use (&$users)
{
    $connection->onWebSocketConnect = function($connection) use (&$users)
    {
        $token = $_GET['token'];
        $secretKey = $_ENV['JWT_SECRET'];

        $decoded = JWT::decode($token, new Key($secretKey, 'HS256'));

        if (isset($decoded->id) && $decoded->id === $_GET['user']) {
            $users[$_GET['user']] = $connection;

        }

        echo "Connection refused\n";
    };
};

$ws_worker->onClose = function($connection) use(&$users)
{
    $user = array_search($connection, $users);
    unset($users[$user]);
};

Worker::runAll();
