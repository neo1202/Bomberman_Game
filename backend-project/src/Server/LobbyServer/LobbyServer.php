<?php

namespace MyApp\Server\LobbyServer;

use Swoole\Coroutine;

use MyApp\Config\Config;
use MyApp\Enum\Enum;
use MyApp\Module\PackageParser;
use MyApp\Server\LobbyServer\Module\Player;
use MyApp\Server\GameServer\BombermanGame\Module\GamePlayer;
use MyApp\Server\LobbyServer\Module\Room;
use MyApp\Server\LobbyServer\LobbyServerController\LobbyServerController;

class LobbyServer
{
    public $proxy_server_fd;
    public $lobby_server;
    public $lobby_server_controller;
    public $not_full_rooms; // 人數未滿的房間
    public $full_rooms; // 人數已滿的房間
    public $room_dictionary; //roomId和room object的對應
    public $player_dictionary; //playerId和player object的對應

    public function __construct()
    {
        echo "hell yeah, constructing Lobby Server...\n";
        $this->proxy_server_fd = null;
        $this->not_full_rooms = [];
        $this->full_rooms = [];
        $this->room_dictionary = [];
        $this->player_dictionary = [];
        $this->lobby_server = new \Swoole\Server(Config::LOCAL_IP['LOBBYSERVER'], Config::LOCAL_PORT['LOBBYSERVER']);
        $this->lobby_server->set(Config::LOBBY_SERVER_SETTING);
        $this->lobby_server_controller = new LobbyServerController();
        $this->lobby_server->on('Start', [$this, 'onStart']);
        $this->lobby_server->on('Connect', [$this, 'onConnect']);
        $this->lobby_server->on('Receive', [$this, 'onReceive']);
        $this->lobby_server->on('Close', [$this, 'onClose']);
        $this->lobby_server->start();
    }

    public function onStart($server)
    {
        echo 'LobbyServer Ready on ' . Config::SERVER_IP['LOBBYSERVER'] . PHP_EOL;
    }

    public function onConnect($server, $fd)
    {
        echo "LobbyServer 已經連接到 ProxyServer.\n";
        $this->proxy_server_fd = $fd;
    }

    public function onReceive($server, $fd, $reactor_id, $data) //收到fd=proxy_fd
    {
        $packages = PackageParser::tcpPackageSplit($data);
        foreach($packages as $data) {
            [$action, $json_data] = PackageParser::parseCommand($data);
            echo "action: $action, json_data: $json_data \n";
            $json_data = json_decode($json_data, true);
            $client_fd = $json_data['client_fd'];
            if (array_key_exists($client_fd, $this->player_dictionary)) { //如果當前已有該user
                echo "該user存在於玩家字典\n";
                $current_player = $this->player_dictionary[$client_fd];
            } else {
                echo "該user不存在於玩家字典\n";
            }
            switch ($action) {
                case 'CONNECT':
                    // 建立player物件，建立dictionary對應，
                    echo "客戶端id: {$client_fd} 連接到大廳.\n";
                    $new_player = new Player($client_fd);
                    $this->lobby_server_controller->addPlayerToDict($new_player, $this->player_dictionary);
                    $transmission = $this->lobby_server_controller->proxyCmdCONNECT($client_fd, $client_fd);
                    $this->lobby_server->send($fd, $transmission);
                    break;
                case 'CLOSE': //Proxy告知我某人離線了從房間 如果沒有從房間離開那就直接從大廳移除就好不用傳給proxy
                    echo "(CLOSE)客户端id: {$client_fd}離線.\n";
                    if ($this->player_dictionary[$client_fd]->getInWhichRoom()) { //如果他有在房間
                        $current_room = $this->room_dictionary[$this->player_dictionary[$client_fd]->getInWhichRoom()];
                        $current_room->removePlayerFromRoom($this->player_dictionary[$client_fd]);
                        $this->lobby_server_controller->checkRoomFourToThree($current_room, $this->not_full_rooms, $this->full_rooms);
                        [$transmission, $no_people_in_this_room] = $this->lobby_server_controller->proxyCmdCLOSE($this->player_dictionary[$client_fd], $current_room);
                        if (!$no_people_in_this_room) { //如果還有人才要回傳給proxy\client //告訴同房間其他人誰走了，新房主是誰
                            echo "這邊還有人，所以得通知房內其他人\n";
                            $this->lobby_server->send($fd, $transmission);
                        }
                    }
                    //最後把他從整體(大廳)玩家字典移除
                    $this->lobby_server_controller->removePlayerFromDict($client_fd, $this->player_dictionary);
                    break;
                case 'BOMBER':
                    echo "Enter Bomberman Room\n";
                    $current_player->setName($json_data['name']);
                    if ($current_player->getInWhichRoom() == null) {//從沒有滿的房間找，沒有就創一個房間
                        $this->lobby_server_controller->assignRoomToPlayer($current_player, $this->not_full_rooms, $this->full_rooms, $this->room_dictionary, $this->lobby_server);
                    }
                    $current_player_room = $current_player->getInWhichRoom();
                    $room_player_status_str = $this->room_dictionary[$current_player_room]->tellAllRoomPlayerStatus();
                    $room_player_fds = $this->room_dictionary[$current_player_room]->getAllPlayerFd();
                    $transmission = $this->lobby_server_controller->proxyCmdROOM($room_player_status_str, $room_player_fds);
                    $this->lobby_server->send($fd, $transmission);
                    break;
                case 'READY':
                case 'UNREADY':
                    if (($current_player->getInWhichRoom() != null) && $current_player != $this->room_dictionary[$current_player->getInWhichRoom()]->getLeader()) {
                        echo "okay modify ready/unready status\n";
                        $players_all_ready = $this->room_dictionary[$current_player->getInWhichRoom()]->setPlayerReadyStatus($current_player, $action);
                        $room_player_fds = $this->room_dictionary[$current_player->getInWhichRoom()]->getAllPlayerFd();
                        $transmission = $this->lobby_server_controller->proxyCmdREADYorUNREADY($action, $current_player, $room_player_fds);
                        $this->lobby_server->send($fd, $transmission);
                        if ($players_all_ready) { //主動傳送可以開始的通知
                            Coroutine::sleep(0.05); //0.1秒後嘗試
                            $transmission = $this->lobby_server_controller->proxyCmdALLREADY($this->room_dictionary[$current_player->getInWhichRoom()], $room_player_fds);
                            $this->lobby_server->send($fd, $transmission);
                        }
                    } else {
                        echo "此人不在房間 or 此人是房主無法調整準備狀態\n";
                    }
                    break;
                case 'START': //有人想開始遊戲
                    echo "(LobbyServer)(case START)Someone want to start the game\n";
                    if ($this->room_dictionary[$current_player->getInWhichRoom()]->tryStartGame($current_player)) { //回傳1代表可開始
                        $room_player_name_fd_str = $this->room_dictionary[$current_player->getInWhichRoom()]->tellAllRoomPlayerNameFd();
                        $room_player_fds = $this->room_dictionary[$current_player->getInWhichRoom()]->getAllPlayerFd();
                        $transmission = $this->lobby_server_controller->proxyCmdSTART($room_player_name_fd_str, $room_player_fds, $this->room_dictionary[$current_player->getInWhichRoom()]);
                        $this->lobby_server->send($fd, $transmission);
                    }
                    break;
                case 'ROOM_GAME_END': //房間遊戲結束
                    $current_room_id = $json_data['room_id'];
                    $back_to_room_player_fd = $client_fd;
                    $this->player_dictionary[$back_to_room_player_fd]->setInRoomNotInGame(true);
                    $this->room_dictionary[$current_room_id]->resetRoomGameEnd($fd);
                    $room_player_status_str = $this->room_dictionary[$current_room_id]->tellAllRoomPlayerStatus();
                    $room_player_fds = $this->room_dictionary[$current_room_id]->getAllPlayerFd();
                    $transmission = $this->lobby_server_controller->proxyCmdROOM($room_player_status_str, $room_player_fds); //傳給client房間資訊
                    $this->lobby_server->send($fd, $transmission);
                    break;
                case 'GAME_PLAYER_DISCONNECT': //玩家中途退出遊戲 從大廳字典移除且從player_dictionary移除
                    echo "(GAMEPLAYERDISCONNECT)有人離開了房間從遊戲\n";
                    $current_room_id = $json_data['room_id'];
                    $current_room = $this->room_dictionary[$current_room_id];
                    $current_room->removePlayerFromRoom($this->player_dictionary[$client_fd]);
                    $this->lobby_server_controller->checkRoomFourToThree($current_room, $this->not_full_rooms, $this->full_rooms);
                    $room_player_status_str = $this->room_dictionary[$current_room_id]->tellAllRoomPlayerStatus();
                    $room_player_fds = $this->room_dictionary[$current_room_id]->getAllPlayerFd();
                    $transmission = $this->lobby_server_controller->proxyCmdROOM($room_player_status_str, $room_player_fds); //傳給client房間資訊
                    $this->lobby_server->send($fd, $transmission);
                    $this->lobby_server_controller->removePlayerFromDict($client_fd, $this->player_dictionary);
                    break;
            }
        }
    }

    public function onClose($server, $fd) //退出房間與退出大廳dictionary
    {
        echo "LobbyServer 與 ProxyServer 斷線.\n";
    }

}
