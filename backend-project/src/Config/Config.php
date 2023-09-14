<?php

//composer dump-autoload -o
//can generate autoload.php

namespace MyApp\Config;

use function PHPSTORM_META\map;

class Config
{
    public const PROXY_SERVER_SETTING = [
        'open_eof_split' => true,
        'package_eof' => "\r\n",
    ];

    public const LOBBY_SERVER_SETTING = [
        'worker_num' => 1,
        'max_request' => 200000,
        'open_eof_split' => true,
        'package_eof' => "\r\n",
    ];

    public const GAME_SERVER_SETTING = [
        'worker_num' => 1,
        'open_eof_split' => true,
        'package_eof' => "\r\n",
    ];

    public const MONEY_SETTING = [
        'kill_reward' => 1500,
        'coin_item_reward' => 200,
        'each_round_free_money' => [
            1 => 1000,
            2 => 1500,
            3 => 2000,
            4 => 2500,
            5 => 3000
        ]
    ];
    // // max_health/speed/armor/bomb_power直接在unique attribute, 然後合併時直接用=合併而不是+到一般attribute

    public static $unique_attribute = [
        'passive' => [
            'max_health' => 100,
            'speed' => 1,
            'armor' => 1.0, //測試用
            'shield' => 0,
            'bomb_range' => 1,
            'bomb_power' => 50,
            'bomb_limit' => 2,
            'land_mine' => 0,
        ],
        'active' => [
            // 'architecture_1' => 0,
            // 'kick_bomb' => 0,
            // 'fire_attack' => 0,
            'ice_attack' => 0,
            'triple_coins' => 0,
            'shoot_bullet' => 0,
            'remote_trigger_bomb' => 0,
            'flash' => 0,
            'pass_through' => 0,
            'immune_star' => 0,

            // 'architecture_2' => 0,
            'time_stop' => 0,
            'god_punish' => 0,
            'revive' => 0,
        ],
    ];


    //炸彈幾秒後會爆炸
    public static $bomb_timing = [
        'bomb_duration' => 2000,
        'bomb_trigger_bomb_delay' => 200,
        'bomb_fire_lasting' => 500
    ];
    public const MAP_INFO = [
        2 => ['width' => 16,
            'height' => 16,
            'cell_pixel' => 32],
        1 => ['width' => 16,
            'height' => 16,
            'cell_pixel' => 32],
        3 => ['width' => 16,
            'height' => 16,
            'cell_pixel' => 32],
    ];


    //---------------CHANGE IP HERE---------------//
    public const SERVER_IP = [
        'PROXYSERVER' => '10.0.0.225:5000',
        'GAMESERVER' => '10.0.0.225:6000',
        'LOBBYSERVER' => '10.0.0.225:7000'
    ];
    public const LOCAL_IP = [
        'PROXYSERVER' => '10.0.0.225',
        'GAMESERVER' => '10.0.0.225',
        'LOBBYSERVER' => '10.0.0.225'
    ];
    public const LOCAL_PORT = [
        'PROXYSERVER' => 5000,
        'GAMESERVER' => 6000,
        'LOBBYSERVER' => 7000
    ];

    public const ITEM_GENERATE_PROBABILITY = [
        'BOMBSUPPLIES' => 0.11,      // 16% chance of generating a BOMBSUPPLIES
        'FIRE' => 0.16,
        'SHIELD' => 0.05,
        'SNEAKER' => 0.08,
        'POWER'  => 0.06,
        'COIN' => 0.04,
        'NULL' => 0.5
    ];

    public const BOMBERMAN_SETTING = [
        'MAX_ROUND' => 3
    ];


    /*
    'ROAD'  => 0,
    'WOOD' => 1,
    'STONE' => 2,
    */
    public const MAP = [2 =>  [[0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
        [0, 2, 0, 2, 0, 2, 0, 2, 0, 2, 0, 2, 0, 2, 0, 2],
        [0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0],
        [0, 2, 0, 2, 0, 2, 0, 2, 0, 2, 0, 2, 0, 2, 0, 2],
        [0, 0, 2, 0, 2, 0, 0, 0, 2, 0, 0, 0, 0, 0, 0, 0],
        [0, 2, 0, 2, 0, 2, 0, 2, 0, 2, 0, 2, 0, 2, 0, 2],
        [0, 0, 0, 0, 0, 0, 0, 0, 2, 0, 0, 0, 0, 0, 2, 0],
        [0, 2, 0, 2, 0, 2, 0, 2, 0, 2, 0, 2, 0, 2, 0, 2],
        [0, 2, 0, 2, 0, 2, 0, 2, 1, 2, 0, 2, 0, 2, 0, 2],
        [0, 2, 0, 2, 0, 2, 0, 2, 0, 2, 0, 2, 0, 2, 0, 2],
        [0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0],
        [0, 2, 0, 2, 0, 2, 0, 2, 0, 2, 0, 2, 0, 2, 0, 2],
        [0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0],
        [0, 2, 0, 2, 0, 2, 0, 2, 0, 2, 0, 2, 0, 2, 0, 2],
        [0, 0, 2, 0, 2, 0, 0, 0, 2, 0, 0, 0, 0, 0, 0, 0],
        [0, 2, 0, 2, 0, 2, 0, 2, 0, 2, 0, 2, 0, 2, 0, 2]],



        1=>[[0, 0, 1, 0, 1, 1, 1, 0, 1, 1, 1, 0, 1, 0, 0, 0],
            [0, 0, 1, 0, 2, 0, 2, 0, 2, 0, 2, 0, 0, 1, 0, 0],
            [0, 2, 2, 2, 2, 2, 2, 0, 2, 2, 2, 2, 2, 2, 2, 0],
            [0, 2, 1, 0, 1, 1, 1, 0, 1, 1, 1, 0, 1 ,0, 2, 0],
            [1, 2, 0, 1, 2, 2, 1, 0, 1, 1, 2, 1, 1, 1, 2, 1],
            [2, 2, 1, 1, 1, 1, 2, 1, 2, 1, 2, 1, 1, 0, 2, 2],
            [1, 2, 0, 1, 0, 2, 0, 2, 0, 2, 1, 0, 2, 2, 2, 1],
            [0, 0, 0, 1, 2, 1, 2, 0, 2, 1, 1, 2, 1, 0, 0, 0],
            [2, 2, 0, 1, 1, 2, 1, 2, 1, 2, 1, 2, 1, 1, 2, 1],
            [1, 2, 1, 1, 0, 1, 2, 0, 2, 1, 0, 1, 2, 1, 2, 2],
            [1, 2, 0, 1, 1, 2, 1, 2, 1, 2, 1, 1, 2, 0, 2, 1],
            [0, 2, 1, 0, 0, 1, 1, 0, 1, 1, 2, 0, 0, 1, 2, 0],
            [0, 2, 2, 2, 1, 2, 2, 1, 2, 2, 1, 1, 2, 2, 2, 0],
            [0, 2, 2, 2, 2, 2, 2, 0, 2, 2, 2, 2, 2, 2, 2, 0],
            [0, 1, 0, 1, 0, 0, 2, 0, 2, 0, 0, 0, 1, 0, 1, 0],
            [0, 0, 1, 0, 2, 1, 1, 1, 1, 1, 2, 0, 0, 1, 0, 0], ],


        3 =>   [[0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
            [0, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, 0],
            [0, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, 0],
            [0, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, 0],
            [0, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, 0],
            [0, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, 0],
            [0, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, 0],
            [0, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, 0],
            [0, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, 0],
            [0, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, 0],
            [0, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, 0],
            [0, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, 0],
            [0, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, 0],
            [0, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, 0],
            [0, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, 0],
            [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
        ]
    ];
    public const LOAD_DURATION = 3000;
    public const BUY_DURATION = 5000;
    public const READY_DURATION = 3000;
    public const GAME_DURATION = 90000;
}
