<?php

namespace MyApp\Server\GameServer\BombermanGame\Module\Item;

use MyApp\Server\GameServer\BombermanGame\Module\Item;

class Power extends Item
{
    public function __construct(int $x, int $y)
    {
        parent::__construct($x, $y);
        $this->name = "POWER";
    }

    public function modifyPlayerAttributes($player)
    {
        $attribute = $player->getAttribute();
        $attribute['passive']['bomb_power'] *= 1.1;
        $player->setAttribute($attribute);
    }

}