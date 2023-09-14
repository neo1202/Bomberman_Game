<?php

namespace MyApp\Server\LobbyServer\Module;

class Player
{
    protected $name;
    protected $player_id;
    protected $in_which_room;
    protected $in_room_not_in_game;

    public function __construct($fd, $in_which_room = null)
    {
        $this->player_id = $fd;
        $this->in_which_room = $in_which_room;
        $this->name = "guest";
        $this->in_room_not_in_game = false; //拿來處理他在遊戲結束後是否還在房間
    }
    public function setInRoomNotInGame($status)
    {
        $this->in_room_not_in_game = $status;
    }
    public function getInRoomNotInGame()
    {
        return $this->in_room_not_in_game;
    }
    public function goToRoom($room_id)
    {
        $this->in_which_room = $room_id;
        $this->in_room_not_in_game = true;
    }
    public function setName($name = "Neo")
    {
        $this->name = $name;
    }
    public function getName()
    {
        return $this->name;
    }
    public function getPlayerId()
    {
        return $this->player_id;
    }
    public function getInWhichRoom()
    {
        return $this->in_which_room;
    }
    public function exitRoom($room_id)
    {
        $this->in_which_room = null;
        $this->in_room_not_in_game = false;
    }
    // public function modifyIsInGame(bool $yesOrNo) //可能不會用到，因為遊戲後玩家都只會發給gameserver
    // {
    //     $this->isInGame = $yesOrNo;
    //     $result = $yesOrNo ? 'true' : 'false';
    //     echo "Changing isInGame status for player_$this->playerId to $result\n";
    // }
    public function displayInfo()
    {
        echo "New Player Object, fd_" . $this->player_id . ", in Room_" . $this->in_which_room . "\n";
    }
}
