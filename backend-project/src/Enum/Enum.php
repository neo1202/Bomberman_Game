<?php

//composer dump-autoload -o
//can generate autoload.php

namespace MyApp\Enum;

class Enum
{
    public const BLOCK_NAME_TO_LABEL = [
        'ROAD'  => 0,
        'WOOD' => 1,
        'STONE' => 2,
    ];

    public const CELL_LABEL_TO_NAME = [
        0  => 'ROAD',
        1  => 'WOOD',
        2  => 'STONE',
        20 => 'BOMB',
        30 => 'BOMBSUPPLIES',
        40 => 'FIRE',
        50 => 'SHIELD',
        60 => 'SNEAKER',
        70 => 'POWER',
        80 => 'COIN'
    ];

    public const PASSABLE = [
        'ROAD' =>  true,
        'WOOD' => false,
        'STONE' => false,
    ];

    public const DESTROYABLE = [
        'ROAD' =>  false,
        'WOOD' => true,
        'STONE' => false,
    ];

    public const ITEM = [
        'BOMB' => 20,
        'BOMBSUPPLIES' => 30,
        'FIRE' => 40,
        'SHIELD' => 50,
        'SNEAKER' => 60,
        'POWER' => 70,
        'COIN' => 80
    ];
    public const UNIQUE_ITEM = [ //每一關會3選1賣給玩家
        'BASIC'     => [0, 1, 2, 3],
        'RARE'      => [10, 11, 12, 13], //, 14, 15, 16, 17
        'EPIC'      => [20, 21, 22, 23],
        'LEGENDARY' => [29, 30]
    ];
    public const UNIQUE_ITEM_LABEL = [
        'MAX_HEALTH_1' => 0,
        'ARMOR_1' => 1,
        'BOMB_POWER_1' => 2,
        'TRIPLE_COINS' => 3,

        'MAX_HEALTH_2' => 10,
        'ARMOR_2' => 11,
        'BOMB_POWER_2' => 12,
        'SPEED_LOWER_1' => 13,

        'MAX_HEALTH_3' => 20,
        'ARMOR_3' => 21,
        'BOMB_POWER_3' => 22,
        'SPEED_LOWER_2' => 23,
    ];
    public const ITEM_LABEL_TO_NAME = [
        0 => 'MAX_HEALTH_1',
        1 => 'ARMOR_1',
        2 => 'BOMB_POWER_1',
        3 => 'TRIPLE_COINS',

        10 => 'MAX_HEALTH_2',
        11 => 'ARMOR_2',
        12 => 'BOMB_POWER_2',
        13 => 'SPEED_LOWER_1',

        20 => 'MAX_HEALTH_3',
        21 => 'ARMOR_3',
        22 => 'BOMB_POWER_3',
        23 => 'SPEED_LOWER_2',
    ];
    public const UNIQUE_ITEM_PRICE = [
        0 => 400,
        1 => 500,
        2 => 300,
        3 => 500,

        10 => 900,
        11 => 1500,
        12 => 700,
        13 => 1400,

        20 => 1300,
        21 => 2400,
        22 => 1100,
        23 => 2600,

        29 => 5000,
        30 => 5000,
    ];


    public const TEXT_COLOR = [
        'RESET' => "\033[0m",
        'RED' => "\033[31m",
        'GREEN' => "\033[32m",
        'BLUE' => "\033[34m",
        'YELLOW' => "\033[33m",
        'LIGHTBLUE' => "\033[36m"
    ];


}
