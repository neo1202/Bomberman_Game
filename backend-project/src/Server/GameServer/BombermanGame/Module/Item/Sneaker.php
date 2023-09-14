<?php

namespace MyApp\Server\GameServer\BombermanGame\Module\Item;

use MyApp\Server\GameServer\BombermanGame\Module\Item;

class Sneaker extends Item
{
    public function __construct(int $x, int $y)
    {
        parent::__construct($x, $y);
        $this->name = "SNEAKER";
    }

    public function modifyPlayerAttributes($player)
    {
        $attribute = $player->getAttribute();
        if($attribute['passive']['speed'] * 0.2 < 0.1) {
            $attribute['passive']['speed'] += 0.1;
        } elseif ($attribute['passive']['speed'] >= 2) {
            $attribute['passive']['speed'] += 0.05;
        } else {
            $attribute['passive']['speed'] *= 1.15;
        }
        $player->setAttribute($attribute);
    }


}
