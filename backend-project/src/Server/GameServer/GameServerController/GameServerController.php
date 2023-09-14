<?php

namespace MyApp\Server\GameServer\GameServerController;

use MyApp\Server\GameServer\BombermanGame\Module\Bomberman;
use MyApp\Server\GameServer\BombermanGame\Module\GamePlayer;
use MyApp\Enum\Enum;
use MyApp\Module\PackageParser;

class GameServerController
{
    public function handleData(&$game_server, &$proxy_fd, &$game_player_dictionary, &$rooms, $data)
    {
        [$action, $message] = PackageParser::parseCommand($data);
        $message = json_decode($message);
        switch ($action) {
            case 'START':
                $this->handleGameStart($game_server, $proxy_fd, $game_player_dictionary, $rooms, $message);
                break;
            case 'MOVE':
                $this->handleMove($game_server, $proxy_fd, $game_player_dictionary, $rooms, $message);
                break;
            case 'BOMB':
                $this->handleBomb($game_server, $proxy_fd, $game_player_dictionary, $rooms, $message);
                break;
            case 'LOADSCENE':
                $this->handleLoadScene($game_server, $proxy_fd, $game_player_dictionary, $rooms, $message);
                break;
            case 'GAME_CONDITION':
                $this->handleGameCondition($game_server, $proxy_fd, $game_player_dictionary, $rooms, $message);
                break;
            case 'PLACE_BOMB':
                $this->handleBomb($game_server, $proxy_fd, $game_player_dictionary, $rooms, $message);
                break;
            case 'BUY':
                $this->handleBuy($game_server, $proxy_fd, $game_player_dictionary, $rooms, $message);
                break;
            case 'TOUCH_ITEM':
                $this->handleTouchItem($game_server, $proxy_fd, $game_player_dictionary, $rooms, $message);
                break;
            case 'ACHIEVEMENT':
                $this->handleAchievement($game_server, $proxy_fd, $game_player_dictionary, $rooms, $message);
                break;
            case 'PLAYERS_CONDITION':
                $this->handlePlayersCondition($game_server, $proxy_fd, $game_player_dictionary, $rooms, $message);
                break;
            case 'ACHIEVEMENT_DONE':
                $this->handleAchievementDone($game_server, $proxy_fd, $game_player_dictionary, $rooms, $message);
                break;
            case 'CLOSE':
                $this->handleClose($game_server, $proxy_fd, $game_player_dictionary, $rooms, $message);
                break;
            default:
                return;
        }
    }

    public function handleClose(&$game_server, &$proxy_fd, &$game_player_dictionary, &$rooms, $message)
    {
        $fd = $message->client_fd;
        $room_id = $game_player_dictionary[$fd]->getInWhichRoom();
        $rooms[$room_id]->removePlayer($fd);
        /*send message to client*/
        $client_fds = $rooms[$room_id]->getGamePlayerFds();
        $msg1 = [
            'fd' => $fd,
            'client_fds' => $client_fds,
        ];
        $to_proxy_data_1 = 'LEAVE ' . json_encode($msg1) . "\r\n";
        $game_server->send($proxy_fd, $to_proxy_data_1);
        echo "Data sent to proxy server : {$to_proxy_data_1}\n";
        /*send message to lobby*/
        $action = 'GAME_PLAYER_DISCONNECT';
        $msg2 = [
            'room_id' => $room_id,
            'client_fd' => $fd,
        ];
        $to_proxy_data_2 = $action . ' ' . json_encode($msg2) . "\r\n";
        $game_server->send($proxy_fd, $to_proxy_data_2);
        echo "Data sent to proxy server : {$to_proxy_data_2}\n";
        if ($rooms[$room_id]->getPlayerInGameCount() === 0 && count($rooms[$room_id]->getPlayers())) {
            if (isset($rooms[$room_id])) {
                unset($rooms[$room_id]);
            }
        }
        if (isset($game_player_dictionary[$fd])) {
            unset($game_player_dictionary[$fd]);
        }
    }

    public function handleAchievementDone(&$game_server, &$proxy_fd, &$game_player_dictionary, &$rooms, $message)
    {
        $fd = $message->client_fd;
        $room_id = $game_player_dictionary[$fd]->getInWhichRoom();
        if ($rooms[$room_id]->getRoomState() === 'idle') {
            $action = 'ROOM_GAME_END';
            $msg = [
                'room_id' => $room_id,
                'client_fd' => $fd,
            ];
            $to_proxy_data = $action . ' ' . json_encode($msg) . "\r\n";
            $game_server->send($proxy_fd, $to_proxy_data);
            echo "Data sent to proxy server : {$to_proxy_data}\n";
            $rooms[$room_id]->removePlayer($fd);
            if ($rooms[$room_id]->getPlayerInGameCount() === 0 && count($rooms[$room_id]->getPlayers()) === 0) {
                if (isset($rooms[$room_id])) {
                    unset($rooms[$room_id]);
                }
            }
            if (isset($game_player_dictionary[$fd])) {
                unset($game_player_dictionary[$fd]);
            }
        }
    }

    public function handleBuy(&$game_server, &$proxy_fd, &$game_player_dictionary, &$rooms, $message)
    {
        $fd = $message->client_fd;
        $item = $message->item;
        $room_id = $game_player_dictionary[$fd]->getInWhichRoom();
        if ($rooms[$room_id]->getRoomState() === 'shop') {
            $response = $rooms[$room_id]->buyItem($game_player_dictionary[$fd], $item);
            $action = 'BUY_STATE';
            $to_proxy_data = $action . ' ' . json_encode($response) . "\r\n";
            $game_server->send($proxy_fd, $to_proxy_data);
            echo "Data sent to proxy server : {$to_proxy_data}\n";
        }
    }

    public function handleGameStart(&$game_server, &$proxy_fd, &$game_player_dictionary, &$rooms, $message)
    {
        $players = $message->players;
        $room_id = $message->room_id;
        $client_fds = [];
        $game_players = [];
        foreach ($players as $player) {
            $name = $player->name;
            $fd = $player->fd;
            $game_player = new GamePlayer($fd, $room_id);
            $game_player->setName($name);
            $game_player_dictionary[$fd] = $game_player;
            array_push($game_players, $game_player);
            array_push($client_fds, $fd);
        }
        $bomberman = new Bomberman($game_players, $game_server, $proxy_fd, $client_fds, $room_id);
        $rooms[$room_id] = $bomberman;
        $action = 'START';
        $msg = [
            'message' => 'Start',
            'client_fds' => $client_fds
        ];
        $to_proxy_data = $action . ' ' . json_encode($msg) . "\r\n";
        $game_server->send($proxy_fd, $to_proxy_data);
        echo "Data sent to proxy server : {$to_proxy_data}\n";
    }

    public function handleMove(&$game_server, &$proxy_fd, &$game_player_dictionary, &$rooms, &$message)
    {
        $fd = $message->client_fd;
        $position = $message->position;
        $room_id = $game_player_dictionary[$fd]->getInWhichRoom();
        if ($rooms[$room_id]->getRoomState() === 'playing') {
            $rooms[$room_id]->updatePlayerPosition($game_player_dictionary[$fd], $position->x, $position->y); //把 bomberman 物件裡面的玩家位置更新
            $client_fds = $rooms[$room_id]->getGamePlayerFds();
            $action = 'UPDATE_POSITION';
            $message = [
                'fd' => $fd,
                'position' => $position,
                'client_fds' => $client_fds
            ];
            $to_proxy_data = $action . ' ' . json_encode($message) . "\r\n";
            $game_server->send($proxy_fd, $to_proxy_data);
            //echo "Data sent to proxy server : {$to_proxy_data}\n";
        }
    }

    public function handleAchievement(&$game_server, &$proxy_fd, &$game_player_dictionary, &$rooms, $message)
    {
        $fd = $message->client_fd;
        $position = $message->position;
        $room_id = $game_player_dictionary[$fd]->getInWhichRoom();
        $rooms[$room_id]->getPlayersAchievementData($game_player_dictionary[$fd], $position->x, $position->y); //把 bomberman 物件裡面的玩家位置更新
        $client_fds = $rooms[$room_id]->getGamePlayerFds();
        $action = 'UPDATE_POSITION';
        $message = [
            'fd' => $fd,
            'position' => $position,
            'client_fds' => $client_fds
        ];
        $to_proxy_data = $action . ' ' . json_encode($message) . "\r\n";
        $game_server->send($proxy_fd, $to_proxy_data);
        echo "Data sent to proxy server : {$to_proxy_data}\n";
    }

    public function handleBomb(&$game_server, &$proxy_fd, &$game_player_dictionary, &$rooms, &$message)
    {
        $fd = $message->client_fd;
        $position = $message->position;
        $room_id = $game_player_dictionary[$fd]->getInWhichRoom();
        if ($rooms[$room_id]->getRoomState() === 'playing') {
            $client_fds = $rooms[$room_id]->getGamePlayerFds();
            $message = $rooms[$room_id]->bombPlant($game_player_dictionary[$fd], $position);
            if ($message !== null) {
                $action = 'BOMB';
                $to_proxy_data = $action . ' ' . $message . "\r\n";
                $game_server->send($proxy_fd, $to_proxy_data);
                echo "Data sent to proxy server : {$to_proxy_data}\n";
            }
        }
    }

    public function handleGameCondition(&$game_server, &$proxy_fd, &$game_player_dictionary, &$rooms, &$message)
    {
        $fd = $message->client_fd;
        $room_id = $game_player_dictionary[$fd]->getInWhichRoom();
        $game_info = $rooms[$room_id]->getGameInfo();
        $game_info['client_fds'] = [$fd];
        $action = 'GAME_CONDITION';
        $to_proxy_data = $action . ' ' . json_encode($game_info) . "\r\n";
        $game_server->send($proxy_fd, $to_proxy_data);
        echo "Data sent to proxy server : {$to_proxy_data}\n";
    }

    public function handlePlayersCondition(&$game_server, &$proxy_fd, &$game_player_dictionary, &$rooms, &$message)
    {
        $fd = $message->client_fd;
        $room_id = $game_player_dictionary[$fd]->getInWhichRoom();
        $players_condition = $rooms[$room_id]->getPlayersCondition();
        $players_condition['client_fds'] = [$fd];
        $action = 'PLAYERS_CONDITION';
        $to_proxy_data = $action . ' ' . json_encode($players_condition) . "\r\n";
        $game_server->send($proxy_fd, $to_proxy_data);
        echo "Data sent to proxy server : {$to_proxy_data}\n";
    }

    public function handleLoadScene(&$game_server, &$proxy_fd, &$game_player_dictionary, &$rooms, &$message)
    {
        echo "我要處理LoadScene 收到proxy loadscene\n";
        $fd = $message->client_fd;
        $room_id = $game_player_dictionary[$fd]->getInWhichRoom();
        $game_info = $rooms[$room_id]->getGameInfo();
        $game_info['client_fds'] = [$fd];
        $action = 'MAP';
        $to_proxy_data = $action . ' ' . json_encode($game_info) . "\r\n";
        $game_server->send($proxy_fd, $to_proxy_data);
        echo "Data sent to proxy server : {$to_proxy_data}\n";
        if ($game_player_dictionary[$fd]->getHasLoadScene() !== true) {
            $game_player_dictionary[$fd]->setHasLoadScene(true);
            $rooms[$room_id]->handleLoadScene();
        }
    }

    //處理道具被玩家撿到的情況
    public function handleTouchItem($game_server, $proxy_fd, $game_player_dictionary, $rooms, $message)
    {
        $fd = $message->client_fd;
        //item_coordinate : 網格座標
        $item_coordinate = $message->itemplace;
        $room_id = $game_player_dictionary[$fd]->getInWhichRoom();
        if ($rooms[$room_id]->getRoomState() === 'playing') {
            $message = $rooms[$room_id]->ItemPick($game_player_dictionary[$fd], $item_coordinate[0], $item_coordinate[1]);

            if ($message !== null) {
                $action = 'ITEM_PICKUP';
                $to_proxy_data = $action . ' ' . $message . "\r\n";
                $game_server->send($proxy_fd, $to_proxy_data);
                echo "Data sent to proxy server : {$to_proxy_data}\n";
            }
        }
    }
}
