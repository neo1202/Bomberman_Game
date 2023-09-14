<?php

namespace MyApp\Server\GameServer\BombermanGame\Module\Item;

use MyApp\Server\GameServer\BombermanGame\Module\Item;
use MyApp\Server\GameServer\BombermanGame\Module\GamePlayer;
use MyApp\Config\Config;
use Swoole\Timer;
use Swoole\Event;

use function Swoole\Coroutine\run;

class Bomb extends Item
{
    private $bomb_id;
    public $bomb_timer;
    public $owner;
    public $range; //炸彈爆炸的範圍
    public $power; //炸彈傷害
    public $exploded;
    private $already_damaged_players; //如炸過誰，不能再炸到他了
    public $bomb_handler;

    public function __construct(int $x, int $y, GamePlayer &$player, &$bomb_handler)
    {
        parent::__construct($x, $y);
        $this->bomb_id = uniqid();
        $this->bomb_timer = Config::$bomb_timing['bomb_duration'];
        $this->name = "BOMB";
        $this->owner = $player;
        $this->exploded = false;
        $this->bomb_handler = $bomb_handler;
        $this->setRageRange();
        $this->setPowerDamage();
        $this->ownerRecordBomb();
        $this->already_damaged_players = [];
    }
    public function __destruct()
    {
        echo "(destruct)Bomb Object destroyed!, id_" . $this->bomb_id . "\n";
    }
    private function ownerRecordBomb() //讓owner紀錄他這顆炸彈
    {
        $this->owner->modifyBombsRecordOnMap(true, $this);
    }
    public function setBombToAlreadyExploded() //統一在這修改炸彈成為以爆炸狀態
    {
        $this->exploded = true;
        $this->owner->modifyBombsRecordOnMap(false, $this);
    }
    public function isPlayerAlreadyDamaged($player)
    {
        // 檢查玩家是否已經受傷害
        return in_array($player, $this->already_damaged_players);
    }
    public function markPlayerAsDamaged($player)
    {
        // 將玩家標記為已受傷害
        $this->already_damaged_players[] = $player;
    }
    public function getBombId()
    {
        return $this->bomb_id;
    }
    public function getOwner()
    {
        return $this->owner;
    }
    public function modifyPlayerAttributes($player)
    {
        echo "hahaha\n";
        return;
    }
    private function setPowerDamage()
    {
        $this->power = $this->owner->getAttribute()['passive']['bomb_power'];
    }
    public function getPowerDamage()
    {
        return $this->power;
    }
    //根據owner的power來決定炸彈爆炸的範圍
    private function setRageRange()
    {
        $this->range = $this->owner->getAttribute()['passive']['bomb_range'];
    }
    public function getRageRange()
    {
        return $this->range;
    }
}
