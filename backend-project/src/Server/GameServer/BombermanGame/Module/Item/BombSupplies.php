<?php

namespace MyApp\Server\GameServer\BombermanGame\Module\Item;

use MyApp\Server\GameServer\BombermanGame\Module\Item;
use MyApp\Server\GameServer\BombermanGame\Module\GamePlayer;

class BombSupplies extends Item
{
    public function __construct(int $x, int $y)
    {
        parent::__construct($x, $y);
        $this->name = "BOMBSUPPLIES";
    }

    public function modifyPlayerAttributes($player)
    {
        $attribute = $player->getAttribute();
        $attribute['passive']['bomb_limit'] += 1;
        $player->setAttribute($attribute);
    }

}
