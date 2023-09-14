<?php

namespace MyApp\Server\ProxyServer;

use DateTimeZone;
use MyApp\Server\ProxyServer\ProxyServerController\ProxyServerController;
use MyApp\Enum\Enum;
use MyApp\Config\Config;
use MyApp\Module\LogSetting;
use MyApp\Module\PackageParser;
use Swoole\Coroutine as Co;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

use function Swoole\Coroutine\run;

class ProxyServer
{
    private $proxy_server;
    private $proxy_server_controller;
    private $client_lobby_server;
    private $client_game_server;
    private $client_dictionary; //紀錄每一個玩家目前練到哪一個 server
    private $logger;

    public function __construct()
    {
        $this->loggerInitialize();
        $this->proxy_server = new \Swoole\WebSocket\Server(Config::LOCAL_IP['PROXYSERVER'], Config::LOCAL_PORT['PROXYSERVER'], SWOOLE_BASE);
        $this->proxy_server_controller = new ProxyServerController();
        //todo 設定檔寫在config
        $this->proxy_server->set(Config::PROXY_SERVER_SETTING);
        $this->proxy_server->on('Open', [$this, 'onOpen']);
        $this->proxy_server->on('WorkerStart', [$this, 'onWorkerStart']);
        $this->proxy_server->on('Message', [$this, 'onMessage']);
        $this->proxy_server->on('Close', [$this, 'onClose']);
        $this->proxy_server->start();
    }

    public function onOpen($server, $request)
    {
        $fd = $request->fd;
        echo "server: handshake success with client {$fd}\n";
        $this->proxy_server_controller->handleConnect($this->client_lobby_server, $fd);
        $this->client_dictionary[$fd] = 'lobby';
    }

    public function onWorkerStart($server, $worker_id)
    {
        echo "Websocket server listening on port 5000\n";
        $this->client_lobby_server = new \Swoole\Coroutine\Client(SWOOLE_SOCK_TCP);
        $this->client_game_server = new \Swoole\Coroutine\Client(SWOOLE_SOCK_TCP);
        $this->client_lobby_server->connect(...$this->proxy_server_controller->parseIpPort(Config::SERVER_IP['LOBBYSERVER']));
        echo "Connecting to lobby server... \n";
        $this->client_game_server->connect(...$this->proxy_server_controller->parseIpPort(Config::SERVER_IP['GAMESERVER']));
        echo "Connecting to game server... \n";
        $this->receiveLobbyResponse();
        $this->receiveGameResponse();
    }

    public function onMessage($server, $frame)
    {
        $pattern = '/^[^\s]+ \{.*\}$/';
        $fd = $frame->fd;
        $data = $frame->data;
        echo Enum::TEXT_COLOR['YELLOW'] . "Received data from client {$fd} : {$data}\n" . Enum::TEXT_COLOR['RESET'];
        $record_msg = "Received data from client {$fd} : {$data}\n";
        go(function () use ($record_msg) {
            $this->logger['receive_logger']->info($record_msg);
        });
        $packages = PackageParser::tcpPackageSplit($data);
        foreach ($packages as $data) {
            if (preg_match($pattern, $data)) {
                $this->proxy_server_controller->handleClientData($this->proxy_server, $this->client_lobby_server, $this->client_game_server, $this->client_dictionary, $fd, $data, $this->logger);
            } else {
                echo "cannot accept command !\n";
            }
        }
    }

    public function onClose($server, $fd)
    {
        echo "client {$fd} left the server\n";
        $this->proxy_server_controller->handleClose($this->proxy_server, $this->client_lobby_server, $this->client_game_server, $this->client_dictionary, $fd);
    }

    public function receiveLobbyResponse()
    {
        go(function () {
            while (true) {
                $data = $this->client_lobby_server->recv();
                go(function () use ($data) {
                    if ($data) {
                        $packages = PackageParser::tcpPackageSplit($data);
                        foreach ($packages as $data) {
                            echo Enum::TEXT_COLOR['GREEN'] . "receive data from lobby server : {$data}" . PHP_EOL . Enum::TEXT_COLOR['RESET'];
                            $this->proxy_server_controller->handleLobbyResponse($this->proxy_server, $this->client_lobby_server, $this->client_game_server, $this->client_dictionary, $data, $this->logger);
                        }
                    }
                });
            }
        });
    }

    public function receiveGameResponse()
    {
        go(function () {
            while (true) {
                $data = $this->client_game_server->recv();
                go(function () use ($data) {
                    if ($data) {
                        $packages = PackageParser::tcpPackageSplit($data);
                        foreach ($packages as $data) {
                            if (!str_contains($data, 'GAME_TIME') && !str_contains($data, 'UPDATE_POSITION')) {
                                echo Enum::TEXT_COLOR['BLUE'] . "receive data from game server : {$data}" . PHP_EOL . Enum::TEXT_COLOR['RESET'];
                            }
                            $this->proxy_server_controller->handleGameResponse($this->proxy_server, $this->client_lobby_server, $this->client_game_server, $this->client_dictionary, $data, $this->logger);
                        }
                    }
                });
            }
        });
    }

    public function startReconnectGameServer()
    {
        $reconnect_interval = 4000; // 5 seconds
        \Swoole\Timer::tick($reconnect_interval, function () {
            echo 'Try connecting to game server...\n';
            if ($this->client_game_server === null || !$this->client_game_server->isConnected()) {
                $this->client_game_server->connect(...$this->proxy_server_controller->parseIpPort(Config::SERVER_IP['GAMESERVER']));
                if ($this->client_game_server->isConnected()) {
                    echo "Successfully connected to game server ! \n";
                }
            }
        });
    }

    public function startReconnectLobbyServer()
    {
        $reconnect_interval = 4000; // 5 seconds
        \Swoole\Timer::tick($reconnect_interval, function () {
            echo 'Try connecting to game server...\n';
            if ($this->client_lobby_server === null || !$this->client_lobby_server->isConnected()) {
                $this->client_lobby_server->connect(...$this->proxy_server_controller->parseIpPort(Config::SERVER_IP['GAMESERVER']));
                if ($this->client_lobby_server->isConnected()) {
                    echo "Successfully connected to lobby server ! \n";
                }
            }
        });
    }

    //初始化一些Logger設定
    public function loggerInitialize()
    {
        $this->logger['receive_logger'] = new Logger('RECEIVE_FROM_CLIENT');
        $this->logger['send_logger'] = new Logger('SEND_TO_CLIENT');

        $stream_handler = new StreamHandler(LogSetting::getLogFile());
        $this->logger['receive_logger']->pushHandler($stream_handler);
        $this->logger['receive_logger']->setTimezone(new DateTimeZone('Asia/Taipei'));
        $this->logger['send_logger']->pushHandler($stream_handler);
        $this->logger['send_logger']->setTimezone(new DateTimeZone('Asia/Taipei'));
    }
}
