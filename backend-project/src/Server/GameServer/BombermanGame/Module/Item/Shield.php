<?php

namespace MyApp\Server\GameServer\BombermanGame\Module\Item;

use MyApp\Server\GameServer\BombermanGame\Module\Item;

class Shield extends Item
{
    public function __construct(int $x, int $y)
    {
        parent::__construct($x, $y);
        $this->name = "SHIELD";
    }

    public function modifyPlayerAttributes($player)
    {
        $attribute = $player->getAttribute();
        $attribute['passive']['shield'] += 1;
        $player->setAttribute($attribute);
    }

}
