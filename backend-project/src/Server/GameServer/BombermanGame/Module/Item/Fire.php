<?php

namespace MyApp\Server\GameServer\BombermanGame\Module\Item;

use MyApp\Server\GameServer\BombermanGame\Module\Item;

class Fire extends Item
{
    public function __construct(int $x, int $y)
    {
        parent::__construct($x, $y);
        $this->name = "FIRE";
    }

    public function modifyPlayerAttributes($player)
    {
        $attribute = $player->getAttribute();
        $attribute['passive']['bomb_range'] += 1;
        $player->setAttribute($attribute);
    }

}
