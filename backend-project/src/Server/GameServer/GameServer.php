<?php

namespace MyApp\Server\GameServer;

use MyApp\Server\GameServer\BombermanGame\Module\GamePlayer;
use MyApp\Server\GameServer\GameServerController\GameServerController;
use MyApp\Server\GameServer\Room;
use MyApp\Config\Config;
use MyApp\Enum\Enum;
use MyApp\Module\PackageParser;

class GameServer
{
    private $game_server;
    private $proxy_fd;
    private $game_server_controller;
    private $game_player_dictionary = [];
    private $rooms = [];

    public function __construct()
    {
        $this->game_server = new \Swoole\Server(Config::LOCAL_IP['GAMESERVER'], Config::LOCAL_PORT['GAMESERVER']);
        $this->game_server_controller = new GameServerController();
        $this->game_server->set(Config::GAME_SERVER_SETTING);
        $this->game_server->on('Start', [$this, 'onStart']);
        $this->game_server->on('WorkerStart', [$this, 'onWorkerStart']);
        $this->game_server->on('Connect', [$this, 'onConnect']);
        $this->game_server->on('Receive', [$this, 'onReceive']);
        $this->game_server->on('Close', [$this, 'onClose']);
        $this->game_server->start();
    }

    public function onStart($server)
    {
        echo 'Listening on port 6000' . PHP_EOL;
    }

    public function onWorkerStart($server)
    {
        echo "Worker Starts\n";
    }

    public function onConnect($server, $fd)
    {
        echo Enum::TEXT_COLOR['YELLOW'] . "proxy server connected \n" . Enum::TEXT_COLOR['RESET'];
        $this->proxy_fd = $fd;
    }

    public function onReceive($server, $fd, $reactor_id, $data)
    {
        //echo Enum::TEXT_COLOR["YELLOW"] . "Received data from proxy server : {$data}\n" . Enum::TEXT_COLOR["RESET"];
        $packages = PackageParser::tcpPackageSplit($data);
        $pattern = '/^[^\s]+ \{.*\}$/';
        foreach ($packages as $data) {
            if (preg_match($pattern, $data)) {
                $this->game_server_controller->handleData($this->game_server, $this->proxy_fd, $this->game_player_dictionary, $this->rooms, $data);
            } else {
                echo "Cannot accept command !\n";
            }
        }
    }

    public function onClose($server, $fd)
    {
        echo Enum::TEXT_COLOR['RED'] . "proxy server left the server\n" . Enum::TEXT_COLOR['RESET'];
    }

    public function cleanRoom()
    {
        $timer = $this->game_server->tick(10000, function () {
            echo "clean Room function activates !\n";
            if (count($this->rooms) > 0) {
                foreach ($this->rooms as $room_id => $bomberman) {
                    if (count($this->rooms[$room_id]->getPlayers()) === 0 && $this->rooms[$room_id]->getPlayerInGameCount() === 0) {
                        if (isset($this->rooms[$room_id])) {
                            unset($this->rooms[$room_id]);
                        }
                    }
                }
            }
        });
    }
}
