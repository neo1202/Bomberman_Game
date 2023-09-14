<?php

namespace MyApp\Server\GameServer\BombermanGame\Module\Item;

use MyApp\Server\GameServer\BombermanGame\Module\Item;
use MyApp\Server\GameServer\BombermanGame\Module\GamePlayer;
use MyApp\Config\Config;

class Coin extends Item
{
    public function __construct(int $x, int $y)
    {
        parent::__construct($x, $y);
        $this->name = "COIN";
    }

    public function modifyPlayerAttributes($player)
    {
        $player->earnMoney(Config::MONEY_SETTING['coin_item_reward']);
    }

}
