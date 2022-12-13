# PHP-WebSocket-chat
This is OOP implementation of WebSocket chat made in PHP with [`Workerman`](https://github.com/walkor/Workerman).
Chat has a simple frontend made in JS + HTML + CSS and can be improved in the future.
All settings are configured for local launch. Chat support public messages, notifications about user activity, XSS defence and can reconnect itself if the connection was lost.

## Usage

Deploy this project to any web-server with PHP >= 7.4 and run WebSocket server

    $ php server.php start

If you have problem check your web-server for WebSocket support

    $ php test.php

Open `index.html` in your browser
