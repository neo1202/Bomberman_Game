<?php

namespace MyApp\Server\LobbyServer\Module;

use MyApp\Config\Config;
use MyApp\Server\LobbyServer\LobbyServerController\LobbyServerController;
use Swoole\Timer;
use MyApp\Enum\Enum;

class Room
{
    private $room_id;
    private $have_game_started;
    private $player_and_status_in_room;
    private $game;
    private $leader; //房主可以開遊戲
    private $lobby_server;
    private $lobby_server_controller;

    // 產生亂數id
    public function __construct($myServer)
    {
        $this->lobby_server = $myServer;
        $this->lobby_server_controller = new LobbyServerController();
        $this->room_id = uniqid();
        $this->player_and_status_in_room = []; // [[player1,false],  [player2, true],...,[player4, false]] ready?
        $this->have_game_started = false;
        $this->leader = null;
        echo "Constructing room... my roomId is {$this->room_id}\n";
    }
    public function __destruct()
    {
        echo "(destruct)Room Object destroyed!, id_" . $this->room_id . "\n";
    }

    public function resetRoomGameEnd($proxy_server_fd) //遊戲結束後人都回到房間，設定為沒準備 第一個人傳這個函式才會被啟動
    {
        if ($this->have_game_started) {
            $this->have_game_started = false;
            foreach ($this->player_and_status_in_room as &$one_player) {
                $one_player[1] = 0;
            }
            // 第一個人回房後 設定20秒後如果人還沒準備or還沒從遊戲中回房間就踢掉
            // Timer::after(20000, function () use ($proxy_server_fd) { //use ()
            //     if ($this->have_game_started == false) { //房間還沒開始才需要傳
            //         echo Enum::TEXT_COLOR['RED'] . "\n\n\n20秒了房間還沒開始, 嘗試踢人中(Room)\n\n\n" . Enum::TEXT_COLOR['RESET'];
            //         foreach ($this->player_and_status_in_room as &$one_player) {
            //             if (($one_player[1] == 0 && $one_player[0]!=$this->leader) || !$one_player[0]->getInRoomNotInGame()) { //還沒準備或還沒回房 踢
            //                 $this->removePlayerFromRoom($one_player[0]);
            //                 $this->lobby_server_controller->checkRoomFourToThree($this, $this->lobby_server->not_full_rooms, $this->lobby_server->full_rooms);
            //                 //回傳最後的ROOM
            //                 echo '踢了一個玩家_' . $one_player[0]->getPlayerId() . "!\n";
            //                 //print_r($this->player_and_status_in_room);
            //             }
            //         }
            //         echo "踢完人了，固定回傳最後房間訊息\n\n";
            //         //print_r($this->player_and_status_in_room);
            //         $transmission = $this->lobby_server_controller->proxyCmdROOM($this->tellAllRoomPlayerStatus(), $this->getAllPlayerFd()); //傳給client房間資訊
            //         $this->lobby_server->send($proxy_server_fd, $transmission);
            //     }
            // });
        }
    }

    public function getHaveGameStarted()
    {
        return $this->have_game_started;
    }

    public function getRoomId()
    {
        return $this->room_id;
    }

    public function getLeader()
    {
        return $this->leader;
    }

    public function getAllPlayerFd()
    {
        $all_id = [];
        foreach ($this->player_and_status_in_room as $one_player) {
            $all_id[] = $one_player[0]->getPlayerId();
        }
        return $all_id;
    }

    // 如果房長離開，也要換房長
    public function assignRoomLeader(&$which_player)
    {
        $this->leader = $which_player;
    }

    //有人想開啟遊戲 確認可否開
    public function tryStartGame(&$which_player)
    {
        if (($this->countPlayersInRoom()==$this->countPlayersReady()) && $which_player == $this->leader) {
            $this->startGame();
            $this->have_game_started = true;
            return 1;
        } else {
            echo "Room not all set, CANNOT start the game\n";
            return 0;
        }
    }

    public function startGame()
    {
        echo "YEAH!!!The Game is about to start. Player fds in this game:\n";
        print_r(array_column($this->player_and_status_in_room, 0));
    }

    //加入player進房間，預設是已準備好
    public function addPlayerInRoom(&$player_object, $status)
    {
        $this->player_and_status_in_room[] = [$player_object, $status];
        // 指定第一個進來Room的這個人為Room Leader
        if ($this->countPlayersInRoom() ==1) {
            echo "這間房只有一個人目前\n";
            $this->assignRoomLeader($player_object);
        }
        echo "player number in this room_{$this->room_id}: " . count($this->player_and_status_in_room) . "\n";
    }

    public function removePlayerFromRoom(&$player_to_remove) //傳入playerobject
    {
        //echo "(Room)正在移除玩家\n";
        foreach ($this->player_and_status_in_room as $key => &$one_player) {
            if ($one_player[0] === $player_to_remove) {
                //echo "找到人要被移除了\n";
                if (isset($this->player_and_status_in_room[$key])) {
                    unset($this->player_and_status_in_room[$key]); // 通过unset移除指定元素
                    $this->player_and_status_in_room = array_values($this->player_and_status_in_room);
                }
                echo '(Room->removePlayerFromRoom)我成功把它移除了, player_' . $key+1 . "!\n"; // 表示成功找到并移除玩家
                if ($this->leader == $player_to_remove) {
                    echo "hell no, 房長離開房間啦, 指派下一個人當房長\n";
                    if ($this->countPlayersInRoom() > 0) {
                        $this->assignRoomLeader($this->player_and_status_in_room[0][0]);
                    } else {
                        $this->leader = null;
                        echo "房主離開 但這間房沒有人了\n";
                    }
                }
                break;
            }
        }
    }

    public function isFull()
    {
        return count($this->player_and_status_in_room) === 4;
    }

    public function countPlayersInRoom()
    {
        return count($this->player_and_status_in_room);
    }

    // 如果數到房長時，直接忽略他的準備狀態，把他算成已經準備好了
    public function countPlayersReady() // 計算此房間多少人準備開始了
    {
        $cnt = 0;
        foreach ($this->player_and_status_in_room as $one_player) {
            if ($one_player[0] == $this->leader) {
                $cnt += 1;
                echo "算到房長了。房長默認已準備\n";
            } elseif ($one_player[1]) {
                $cnt += 1;
            }
        }
        echo "How many people is ready for the game in this room: $cnt\n";
        return $cnt;
    }

    // 當使用者在房間內按下準備\不準備
    public function setPlayerReadyStatus(&$which_player, $status)
    {
        foreach ($this->player_and_status_in_room as $index => &$one_player) {
            if ($one_player[0] === $which_player) {
                $who = $index + 1; //player1~4
                if ($status == 'READY') {
                    echo "I(Player_{$who}) am ready\n";
                    $one_player[1] = true;
                } else {
                    echo "I(Player_{$who}) am not ready\n";
                    $one_player[1] = false;
                }
            }
        }
        if ($this->countPlayersInRoom() == $this->countPlayersReady()) {
            echo "\ncan start the GAME\n";
            return 1;
        } else {
            return 0;
        }
    }

    // 回傳這個房內玩家所有資訊包含準備&房主
    public function tellAllRoomPlayerStatus()
    {
        $roomPlayers = [];
        foreach ($this->player_and_status_in_room as $player) {
            $playerData = [
                'name' => $player[0]->getName(),
                'fd' => $player[0]->getPlayerId(),
                'isLeader' => $player[0] === $this->leader,
                'isReady' => $player[1],
            ];
            $roomPlayers[] = $playerData;
        }
        return $roomPlayers;
    }

    public function tellAllRoomPlayerNameFd()
    {
        $roomPlayers = [];
        foreach ($this->player_and_status_in_room as $player) {
            $playerData = [
                'name' => $player[0]->getName(),
                'fd' => $player[0]->getPlayerId(),
            ];
            $roomPlayers[] = $playerData;
        }
        return $roomPlayers;
    }
}
