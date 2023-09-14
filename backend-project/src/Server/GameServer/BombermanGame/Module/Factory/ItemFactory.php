<?php

namespace MyApp\Server\GameServer\BombermanGame\Module\Factory;

use MyApp\Server\GameServer\BombermanGame\Module\Item;

class ItemFactory
{
    public static function ItemGenerate(string $item, $argv = null)
    {
        switch($item) {
            case 'BOMB':
                return new Item\Bomb($argv['x'], $argv['y'], $argv['owner'], $argv['bomb_handler']);
            case 'BOMBSUPPLIES':
                return new Item\BombSupplies($argv['x'], $argv['y']);
            case 'FIRE':
                return new Item\Fire($argv['x'], $argv['y']);
            case 'SHIELD':
                return new Item\Shield($argv['x'], $argv['y']);
            case 'SNEAKER':
                return new Item\Sneaker($argv['x'], $argv['y']);
            case 'POWER':
                return new Item\Power($argv['x'], $argv['y']);
            case 'COIN':
                return new Item\Coin($argv['x'], $argv['y']);
            default:
                echo"no such item !! \n";
                return null;
        }

    }

}
