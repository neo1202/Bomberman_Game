<?php

namespace MyApp\Server\LobbyServer\LobbyServerController;

use MyApp\Server\LobbyServer\Module\Player;
use MyApp\Server\LobbyServer\Module\Room;
use MyApp\Module\JsonLoader;

class LobbyServerController
{
    public function addOne($number)
    {
        return $number+1;
    }

    // 建立playerfd與此player物件的對應字典
    public function addPlayerToDict($player_object, &$player_dictionary)
    {
        $player_dictionary[$player_object->getPlayerId()] = $player_object;
    }

    // 從大廳的玩家對應中刪除這個玩家
    public function removePlayerFromDict($player_id, &$player_dictionary)
    {
        if (isset($player_dictionary[$player_id])) {
            unset($player_dictionary[$player_id]);
            echo "Player with id $player_id has been removed from the Lobby dictionary.\n";
        } else {
            echo "Player with id $player_id not found in the Lobby dictionary.\n";
        }
    }

    // 有個大廳中的player想加入bomberman game 給他房間
    // 同時創造新房間的dict對應也在這
    public function assignRoomToPlayer(&$player_object, &$not_full_rooms, &$full_rooms, &$room_dictionary, &$server)
    {
        $found_available_room = null;
        foreach ($not_full_rooms as $key => &$one_room) {
            echo "find one not full room:" . $one_room->getRoomId() . "\n";
            if (!$one_room->getHaveGameStarted()) {
                $found_available_room = $one_room;
                echo "successfully find an available room!\n";
                break;
            }
        }
        if ($found_available_room == null) {
            echo "BRUH there's not any available room\n";
            $new_room = new Room($server);
            array_unshift($not_full_rooms, $new_room);
            $room_dictionary[$not_full_rooms[0]->getRoomId()] = $not_full_rooms[0];
            $found_available_room = $not_full_rooms[0];
        }
        // 把玩家本身的inWhichRoom更改
        $player_object->goToRoom($found_available_room->getRoomId());
        echo "I(player_{$player_object->getPlayerId()}) am in room_{$player_object->getInWhichRoom()}!\n";
        // 把Room的屬性中加入這個玩家且狀態not準備
        $found_available_room->addPlayerInRoom($player_object, false);

        if ($found_available_room->isFull()) { // 如果加了這個人以後滿了，放到已滿房間
            $full_rooms[] = $found_available_room;
            $index = array_search($found_available_room, $not_full_rooms);
            if ($index !== false) {
                if (isset($not_full_rooms[$index])) {
                    unset($not_full_rooms[$index]);
                }
            }
            $not_full_rooms = array_values($not_full_rooms);
            echo "\n\n\nahhh room is full!";
        }
    }

    // 負責對應滿人的房間以及未滿的房間
    public function checkRoomFull(&$current_room, &$not_full_rooms, &$full_rooms)
    {
        if ($current_room->countPlayersInRoom() == 4) {
            echo "The room is now FULL!!!!!!!\n\n";
            $this->moveFromArrayToArray($current_room, $not_full_rooms, $full_rooms);
        }
    }

    public function checkRoomFourToThree(&$current_room, &$not_full_rooms, &$full_rooms)
    {
        if ($current_room->countPlayersInRoom() == 3) {
            echo "The room is NOT full now(剩三人)!!!!!!!\n\n";
            $this->moveFromArrayToArray($current_room, $full_rooms, $not_full_rooms);
        }
    }

    public function moveFromArrayToArray(&$room_to_move, &$from_where_rooms, &$to_where_rooms)
    {
        $search_room_id = $room_to_move->getRoomId();
        echo "這間房為id_$search_room_id, 要被從滿的房移動到沒滿的房\n";
        $index = null;
        foreach ($from_where_rooms as $key => &$one_room) {
            echo "滿的房間: " . $one_room->getRoomId() . "\n";
            if ($one_room->getRoomId() == $search_room_id) {
                $index = $key;
                echo "在第$key 間房找到要移到未滿房間的房\n";
            }
        }
        foreach ($to_where_rooms as $key => &$one_room) {
            echo "沒滿的房間: " . $one_room->getRoomId() . "\n";
        }

        if ($index !== null) {
            echo "找到了房间，将它从 not_full_rooms 数组中移除并添加到 full_rooms 中\n";
            $removed_room = array_splice($from_where_rooms, $index, 1);
            $to_where_rooms[] = $removed_room[0];
        } else {
            // 没有找到指定的房间
            echo "Room with roomId $search_room_id not found in array.\n";
        }
        echo "移動以後:\n";
        foreach ($from_where_rooms as $key => &$one_room) {
            echo "滿的房間: " . $one_room->getRoomId() . "\n";
        }
        foreach ($to_where_rooms as $key => &$one_room) {
            echo "沒滿的房間: " . $one_room->getRoomId() . "\n";
        }
    }

    
    public function parseCommand($data)
    {
        $data = trim($data, '"');
        list($action, $jsonPart) = explode(' ', $data, 2);
        $action = strtoupper(trim($action));
        echo "receive data, action:$action, jsonPart:$jsonPart\n";
        $json_data = json_decode($jsonPart, true);
        return [$action, $json_data];
    }

    public function proxyCmdCONNECT($fd, $client_fd)
    {
        $return_data = [
            'fd' => $fd,
            'client_fds' => [$client_fd]
        ];
        $return_json = json_encode($return_data);
        $transmission = 'CONNECT ' . $return_json . "\r\n";
        return $transmission;
    }

    public function proxyCmdROOM($room_player_status_str, $room_player_fds)
    {
        $return_data = [
            'players' => $room_player_status_str,
            'client_fds' => $room_player_fds
        ];
        $return_json = json_encode($return_data);
        $transmission = 'ROOM ' . $return_json . "\r\n";
        echo "My Room Data: $transmission\n";
        return $transmission;
    }

    public function proxyCmdREADYorUNREADY($action, $player, $room_player_fds)
    {
        if ($action === 'READY') {
            $isReady = true;
        } elseif ($action === 'UNREADY') {
            $isReady = false;
        }
        $return_data = [
            'name' => $player->getName(),
            'fd' => $player->getPlayerId(),
            'isReady' => $isReady,
            'client_fds' => $room_player_fds
        ];
        $return_json = json_encode($return_data);
        $transmission = $action . ' ' . $return_json . "\r\n";
        echo "Ready?: $transmission\n";
        return $transmission;
    }

    public function proxyCmdSTART($room_player_name_fd_str, $room_player_fds, $room) //收到loadscene回傳gamestart給proxy
    {
        $return_data = [
            'players' => $room_player_name_fd_str,
            'client_fds' => $room_player_fds,
            'room_id' => $room->getRoomId()
        ];
        $return_json = json_encode($return_data);
        $transmission = 'START ' . $return_json . "\r\n";
        echo "My START Data: $transmission\n";
        return $transmission;
    }

    public function proxyCmdALLREADY($room, $room_player_fds)
    {
        $return_data = [
            'message' => 'allReady',
            'leader' => $room->getLeader()->getPlayerId(),
            'client_fds' => $room_player_fds
        ];
        $return_json = json_encode($return_data);
        $transmission = 'ALLREADY ' . $return_json . "\r\n";
        echo "My ALLREADY Data: $transmission\n";
        return $transmission;
    }

    /*  離開房間三種可能：
        1. 離開後房間沒人
        2. 離開後房主換人 / 原本就不是房主
    */
    public function proxyCmdCLOSE(&$which_player, &$room = null)
    {
        if ($room) { //leader回傳playerID, 但如果房間沒人了(no leader)回傳-1
            $return_data = [
                'players' => $room->tellAllRoomPlayerStatus(),
                'leader' => ($room->getLeader() !== null) ? $room->getLeader()->getPlayerId() : -1,
                'client_fds' => $room->getAllPlayerFd(),
                'room_id' => $room->getRoomId()
            ];
            if ($room->getLeader() !== null) {
                $status = false; //這裡還有人，告訴proxy
            } else {
                $status = true; //離開後房間沒人，不用告訴
            }
        }
      

        $return_json = json_encode($return_data);
        $transmission = 'ROOM ' . $return_json . "\r\n";
        echo "(Lobby)My CLOSE(ROOM) Data Away from room: $transmission\n";
        return [$transmission, $status];
    }
}
