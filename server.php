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
    static private $users = [];

    static function init($url)
    {
        $worker = new Worker($url);

        $worker->onConnect = function ($connection) use (&$users) {
            $connection->onWebSocketConnect = function ($connection) use (&$users) {
                SocketServer::$users[$connection->id] = new User($connection);

                echo 'New connection: ' . $connection->id . PHP_EOL;
            };
        };

        $worker->onClose = function ($connection) use (&$users) {

            if (!isset(SocketServer::$users[$connection->id])) {
                return;
            }

            SocketServer::sendMessagesAll(SocketServer::$users[$connection->id]->name . ' disconnected');

            unset(SocketServer::$users[$connection->id]);
        };

        $worker->onMessage = function ($connection, $message) use (&$users) {
            echo 'New message: ' . $message . PHP_EOL;

            $jsonData = json_decode($message, true);

            $messageType = isset($jsonData['messageType']) ? $jsonData['messageType'] : '';
            $name = isset($jsonData['name']) ? $jsonData['name'] : '';
            $messageText = isset($jsonData['message']) ? $jsonData['message'] : '';

            if ($messageType == 'register') {
                foreach (SocketServer::$users as $user) {
                    if ($user->connection->id == $connection->id) {
                        $user->name = $name;
                        SocketServer::sendMessagesAll($name . ' connected');
                    }
                }
            }

            if ($messageType == 'mailAll' and $messageText != '') {
                SocketServer::sendMessagesWithout($messageText, $connection->id);
            }
        };

        Worker::runAll();
    }

    static function sendMessagesAll($message)
    {
        $jsonData = '{"typeMessage": "info", "message": "' . $message . '"}';

        foreach (SocketServer::$users as $user) {
            $user->connection->send($jsonData);

            echo 'Message sent to ' . $user->connection->id;
        }
    }

    static function sendMessagesWithout($message, $id)
    {
        $jsonData = '{"typeMessage": "mailAll", "name": "' . SocketServer::$users[$id]->name . '", "message": "' . $message . '"}';

        foreach (SocketServer::$users as $user) {
            if ($user->connection->id != $id) {
                $user->connection->send($jsonData);
            }
        }
    }
}

SocketServer::init("websocket://0.0.0.0:27800");