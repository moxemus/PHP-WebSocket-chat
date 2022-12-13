<?php

require_once __DIR__ . '/vendor/autoload.php';

use Workerman\Worker;

class User
{
    public $connection;
    public $name;

    public function __construct($connection)
    {
        $this->connection = $connection;
    }
}

class SocketServer
{
    static private array $users = [];

    static function init(string $url)
    {
        $worker = new Worker($url);

        $worker->onConnect = function ($connection) use (&$users) {
            $connection->onWebSocketConnect = function ($connection) use (&$users) {
                SocketServer::$users[$connection->id] = new User($connection);
            };
        };

        $worker->onClose = function ($connection) use (&$users) {

            if (!isset(SocketServer::$users[$connection->id])) return;

            SocketServer::sendMessagesAll(SocketServer::$users[$connection->id]->name . ' disconnected');

            unset(SocketServer::$users[$connection->id]);
        };

        $worker->onMessage = function ($connection, $message) use (&$users) {
            $jsonData = json_decode($message, true);

            $name        = $jsonData['name'] ?? '';
            $messageType = $jsonData['messageType'] ?? '';
            $messageText = $jsonData['message'] ?? '';

            if ($messageType == 'register') {
                foreach (SocketServer::$users as $user) {
                    if ($user->connection->id == $connection->id) {
                        $user->name = $name;
                        SocketServer::sendMessagesAll($name . ' connected');
                    }
                }
            }

            if ($messageType == 'mailAll' && !empty($messageText)) {
                SocketServer::sendMessageFrom($messageText, $connection->id);
            }
        };

        Worker::runAll();
    }

    static function sendMessagesAll(string $message)
    {
        $jsonData = json_encode([
            'typeMessage' => 'mailAll',
            'message'     => $message
        ]);

        foreach (SocketServer::$users as $user)
        {
            $user->connection->send($jsonData);
        }
    }

    static function sendMessageFrom(string $message, int $id)
    {
        $jsonData = json_encode([
            'typeMessage' => 'mailAll',
            'name'        => SocketServer::$users[$id]->name,
            'message'     => $message
        ]);

        foreach (SocketServer::$users as $user)
        {
            ($user->connection->id != $id) && $user->connection->send($jsonData);
        }
    }
}

SocketServer::init("websocket://0.0.0.0:27800");