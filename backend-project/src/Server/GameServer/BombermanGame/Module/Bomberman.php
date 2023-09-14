<?php

namespace MyApp\Server\GameServer\BombermanGame\Module;

use MyApp\Server\GameServer\BombermanGame\Module\Map;
use MyApp\Server\GameServer\BombermanGame\Module\GamePlayer;
use MyApp\Server\GameServer\BombermanGame\Module\Bombhandler;
use MyApp\Server\GameServer\BombermanGame\Module\ItemEffectHandler;
use MyApp\Server\GameServer\BombermanGame\Module\Item\Bomb;
use MyApp\Server\GameServer\BombermanGame\Module\Factory\ItemFactory;
use MyApp\Module\JsonLoader;
use MyApp\Config\Config;
use MyApp\Enum\Enum;
use Swoole\Timer;
use Swoole\Event;
use Swoole\Coroutine as Co;

use function Swoole\Coroutine\run;

class Bomberman
{
    private $proxy_fd;
    private $gs; //game server
    private $players;
    public $map;
    private $bomb_handler;
    private $item_effect_handler;
    private $game_player_fds;
    private $round;
    private $load_scene_count;
    private $load_duration;
    private $buy_duration;
    private $ready_duration;
    private $game_duration;
    private $room_state;
    private $player_in_game_count;
    private $room_id;
    //---Description---//
    //$room_state -> 房間狀態，對應的有如下：
    //1. idle (loadscene 之前)
    //2. load (下 loadscene 之後的三秒期間)
    //3. shop (商店10秒期間)
    //4. ready (準備3秒期間)
    //5. playing
    //
    //---Description---//

    public function __construct(&$players, $gs, $fd, $game_player_fds, $room_id)
    {
        $this->players = $players;
        $this->gs = $gs;
        $this->proxy_fd = $fd;
        $this->game_player_fds = $game_player_fds;
        $this->room_id = $room_id;
        $this->round = 0;
        $this->room_state = 'idle';
        $this->player_in_game_count = count($this->players);
        $this->gameStart();
    }

    public function getPlayers()
    {
        return $this->players;
    }

    public function removePlayer($player_fd)
    {
        echo "player_fd : $player_fd\n";
        for ($i = 0; $i < count($this->players); $i++) {
            if ($this->players[$i]->getPlayerId() === $player_fd) {
                if (isset($this->players[$i])) {
                    unset($this->players[$i]);
                }
                break;
            }
        }
        $this->players = array_values($this->players);
        for ($i = 0; $i < count($this->game_player_fds); $i++) {
            if ($this->game_player_fds[$i] === $player_fd) {
                if (isset($this->game_player_fds[$i])) {
                    unset($this->game_player_fds[$i]);
                }
                break;
            }
        }
        $this->game_player_fds = array_values($this->game_player_fds);
        $player_number = count($this->players);
        echo 'Number of player in this room : ' . $player_number . "\n";
        echo "Remaining player fd : \n";
        foreach ($this->game_player_fds as $fd) {
            echo "$fd\n";
        }
        $this->decrementPlayerCount();
    }

    public function decrementPlayerCount()
    {
        $this->player_in_game_count -= 1 ;
    }

    public function getPlayerInGameCount()
    {
        return $this->player_in_game_count;
    }

    public function showRoomState($interval) //debug 用的
    {
        $timer = \Swoole\Timer::tick($interval, function () use (&$timer, $interval) {
            $room_state = $this->room_state;
            echo 'This room state : ' . $room_state . "\n";
        });
    }

    public function getRoomState()
    {
        return $this->room_state;
    }

    public function setRoomState(string $state)
    {
        $this->room_state = $state;
    }

    public function getPlayersCondition()
    {
        $player_condition = [];
        foreach ($this->players as $player) {
            $fd = $player->getPlayerId();
            $attribute = $player->getAttribute();
            $player_condition[$fd] = $attribute;
        }
        return $player_condition;
    }

    public function getGamePlayerFds()
    {
        return $this->game_player_fds;
    }

    public function checkSurvivor()
    {
        $survivor_fd = [];
        foreach ($this->players as $player) {
            if ($player->getAlive()) {
                array_push($survivor_fd, $player->getPlayerId());
            }
        }
        return $survivor_fd;
    }

    public function checkRoundEnd()
    {
        $survivor_fd = $this->checkSurvivor();
        if (count($survivor_fd) <= 1) {
            return true;
        } else {
            return false;
        }
    }

    public function calculateNextRound()
    {
        $next_round = $this->round + 1;
        if ($next_round > Config::BOMBERMAN_SETTING['MAX_ROUND']) {
            $next_round = 0;
        }
        return $next_round;
    }

    public function sendRoundEnd($times_up)
    {
        $next_round = $this->calculateNextRound();
        if (count($this->players) <= 1) {
            $next_round = 0;
        }
        $survivor_fd = $this->checkSurvivor();
        $msg = [
            'survivorFd' => $survivor_fd,
            'timesUp' => $times_up,
            'nextRound' => $next_round,
            'client_fds' => $this->game_player_fds,
        ];
        $to_proxy_data = 'ROUND_END ' . json_encode($msg) . "\r\n";
        $this->gs->send($this->proxy_fd, $to_proxy_data);
        echo "Data sent to proxy server : {$to_proxy_data}\n";
    }

    public function startBuffer($interval, $duration)
    {
        $timer = \Swoole\Timer::tick($interval, function () use (&$timer, $interval, &$duration) {
            if ($duration >= 0) {
                $duration -= $interval;
            } else {
                \Swoole\Timer::clear($timer);
                $this->randomizeUniqueItem();
                $this->startBuyTimer(1000);
            }
        });
    }

    public function startLoadTimer($interval)
    {
        if ($this->round !== 1) {
            $this->room_state = 'load';
            $timer = \Swoole\Timer::tick($interval, function () use (&$timer, $interval) {
                if ($this->load_duration >= 0) {
                    if ($this->load_duration % 1000 === 0) {
                        $msg = [
                            'time' => $this->load_duration,
                            'client_fds' => $this->game_player_fds,
                        ];
                        $to_proxy_data = 'LOAD_TIME ' . json_encode($msg) . "\r\n";
                        $this->gs->send($this->proxy_fd, $to_proxy_data);
                    }
                    $this->load_duration -= $interval;
                } else {
                    \Swoole\Timer::clear($timer);
                    $this->randomizeUniqueItem();
                    $this->startBuyTimer(1000);
                }
            });
        } else {
            $this->startBuffer(500, 2000);
        }
    }

    public function handleLoadScene()
    {
        $this->load_scene_count += 1;
        if ($this->round === 1) {
            if ($this->load_scene_count === count($this->players)) {
                $this->startLoadTimer(100);
            }
        } else {
            if ($this->load_scene_count === 1) {
                $this->startLoadTimer(100);
            }
        }
    }

    public function startBuyTimer($interval)
    {
        $this->room_state = 'shop';
        $timer = $this->gs->tick($interval, function () use (&$timer, $interval) {
            if ($this->buy_duration >= 0) {
                $msg = [
                    'time' => $this->buy_duration,
                    'client_fds' => $this->game_player_fds,
                ];
                $to_proxy_data = 'BUY_TIME ' . json_encode($msg) . "\r\n";
                $this->gs->send($this->proxy_fd, $to_proxy_data);
                echo "Data sent to proxy server : {$to_proxy_data}\n";
                $this->buy_duration -= $interval;
            } else {
                $this->gs->clearTimer($timer);
                $player_info = [];
                foreach ($this->players as $player) {
                    $fd = $player->getPlayerId();
                    $player_info[$fd] = $player->getAttribute();
                }
                $msg = [
                    'player_attribute' => $player_info,
                    'client_fds' => $this->game_player_fds,
                ];
                $to_proxy_data = 'PLAYER_ATTRIBUTE ' . json_encode($msg) . "\r\n";
                $this->gs->send($this->proxy_fd, $to_proxy_data);
                echo "Data sent to proxy server : {$to_proxy_data}\n";
                $this->startReadyTimer(1000);
                //買完道具後顯示玩家的屬性
                $this->showPlayerAttribute();
            }
        });
    }

    public function startReadyTimer($interval)
    {
        $this->room_state = 'ready';
        $timer = \Swoole\Timer::tick($interval, function () use (&$timer, $interval) {
            if ($this->ready_duration >= 0) {
                $msg = [
                    'time' => $this->ready_duration,
                    'client_fds' => $this->game_player_fds,
                ];
                $to_proxy_data = 'READY_TIME ' . json_encode($msg) . "\r\n";
                $this->gs->send($this->proxy_fd, $to_proxy_data);
                $this->ready_duration -= $interval;
            } else {
                \Swoole\Timer::clear($timer);
                $this->startGameTimer(200);
            }
        });
    }

    public function startGameTimer($interval)
    {
        $this->room_state = 'playing';
        $timer = \Swoole\Timer::tick($interval, function () use (&$timer, $interval) {
            if ($this->game_duration >= 0) {
                if ($this->game_duration % 1000 === 0) {
                    $msg = [
                        'time' => $this->game_duration,
                        'client_fds' => $this->game_player_fds,
                    ];
                    $to_proxy_data = 'GAME_TIME ' . json_encode($msg) . "\r\n";
                    $this->gs->send($this->proxy_fd, $to_proxy_data);
                }
                $this->game_duration -= $interval;
                $is_round_end = $this->checkRoundEnd();
                if ($is_round_end) {
                    $this->room_state = 'idle';
                    $this->sendRoundEnd(false);
                    $this->handleRoundEnd();
                    \Swoole\Timer::clear($timer);
                    if ($this->calculateNextRound() !== 0 && count($this->players) > 1) {
                        $this->gameStart();
                    } else {
                        echo "我知道遊戲全部結束了, 準備傳送總結算成就資料\n"; //這邊處理總結算
                        $this->sendFinalAchievement();
                    }
                }
            } else {
                $this->room_state = 'idle';
                $this->sendRoundEnd(true);
                $this->handleRoundEnd();
                \Swoole\Timer::clear($timer);
                if ($this->calculateNextRound() !== 0 && count($this->players) > 1) {
                    $this->gameStart();
                } else {
                    echo "我知道遊戲全部結束了, 準備傳送總結算成就資料\n"; //這邊處理總結算
                    $this->sendFinalAchievement();
                }
            }
        });
    }

    private function sendFinalAchievement()
    {
        $achievement_info = [];
        $arr = $this->calculateFinalAchievement();
        echo "收到的overall final achievement回傳:\n";
        print_r($arr);
        $achievement_info['final_achievement_info'] = $arr;
        $achievement_info['client_fds'] = $this->game_player_fds;
        $to_proxy_data = 'FINAL_ACHIEVEMENT ' . json_encode($achievement_info) . "\r\n";
        $this->gs->send($this->proxy_fd, $to_proxy_data);
        echo "!!!!!Final Achievement!!!!! sent to proxy server : {$to_proxy_data}\n";
    }

    private function calculateFinalAchievement()
    {
        $achievement_array = [
            '炸彈供應商' => [null, -1],
            '火力覆蓋' => [null, -1],
            '地形破壞王' => [null, -1],
            '殺神' => [null, -1],
            '富可敵國' => [null, -1]
        ]; // 前面紀錄是哪個player, 後面是他對應的分數
        foreach ($this->players as &$player) {
            $player_id = $player->getPlayerId();
            $one_player_info = $player->returnAchievementsData();
            if ($one_player_info['totalPlaceBomb'] > $achievement_array['炸彈供應商'][1]) {
                $achievement_array['炸彈供應商'][0] = $player_id;
                $achievement_array['炸彈供應商'][1] = $one_player_info['totalPlaceBomb'];
            }
            if ($one_player_info['totalDealDamage'] > $achievement_array['火力覆蓋'][1]) {
                $achievement_array['火力覆蓋'][0] = $player_id;
                $achievement_array['火力覆蓋'][1] = $one_player_info['totalDealDamage'];
            }
            if ($one_player_info['destroyedBuildings'] > $achievement_array['地形破壞王'][1]) {
                $achievement_array['地形破壞王'][0] = $player_id;
                $achievement_array['地形破壞王'][1] = $one_player_info['destroyedBuildings'];
            }
            if ($one_player_info['killedPlayers'] > $achievement_array['殺神'][1]) {
                $achievement_array['殺神'][0] = $player_id;
                $achievement_array['殺神'][1] = $one_player_info['killedPlayers'];
            }
            if ($one_player_info['totalEarnedMoney'] > $achievement_array['富可敵國'][1]) {
                $achievement_array['富可敵國'][0] = $player_id;
                $achievement_array['富可敵國'][1] = $one_player_info['totalEarnedMoney'];
            }
        }
        
        $complete_info = [];
        foreach ($this->players as &$player) {
            $player_id = $player->getPlayerId();
            $my_info = [
                'fd' => $player_id,
                'name' => $player->getName(),
                'achievement' => [],
            ];
            foreach ($achievement_array as $achievement_name => &$achievement_info) {
                if ($achievement_info[0] === $player_id) {
                    $my_info['achievement'][] = $achievement_name;
                }
            }
            if (empty($my_info['achievement'])) {
                echo "我什麼成就都沒有得到\n";
                $my_info['achievement'][] = '無名小卒';
            }
            echo "我(player_$player_id)的成就：\n";
            $complete_info[] = $my_info;
        }
        return $complete_info;
    }

    public function getPlayersAchievementData()
    {
        $achievement_info = [];
        foreach ($this->players as &$player) {
            $one_player_info = $player->returnAchievementsData();
            $one_player_info['fd'] = $player->getPlayerId();
            $one_player_info['current_money'] = $player->getCurrentMoney();
            $achievement_info[] = $one_player_info;
        }
        return $achievement_info;
    }

    public function sendPlayersAchievementData()
    {
        $achievement_info = [];
        $achievement_info['playerInfo'] = $this->getPlayersAchievementData();
        $achievement_info['client_fds'] = $this->game_player_fds;
        $to_proxy_data = 'ACHIEVEMENT_DATA ' . json_encode($achievement_info) . "\r\n";
        $this->gs->send($this->proxy_fd, $to_proxy_data);
        echo "Achievement Data sent to proxy server : {$to_proxy_data}\n";
    }

    public function buyItem($player, $item)
    {
        $fd = $player->getPlayerId();
        if ($this->buy_duration < 0) {
            $msg = [
                'buySuccess' => false,
                'money' => $player->getCurrentMoney(),
                'client_fds' => [$fd],
            ];
            echo "The shopping time has ended, you can't buy any item \n";
            return $msg;
        } else {
            try {
                $item_price = Enum::UNIQUE_ITEM_PRICE[$item];
                if ($player->getCurrentMoney() < $item_price) {
                    $msg = [
                        'buySuccess' => false,
                        'money' => $player->getCurrentMoney(),
                        'client_fds' => [$fd],
                    ];
                    echo "Player's money is not enough, can't buy the stuff \n";
                    return $msg;
                } else {
                    $player->earnMoney(-$item_price);
                    //modify Player attribute by applying unique item's effect
                    $this->item_effect_handler->applyUniqueItem($player, $item);
                    $msg = [
                        'buySuccess' => true,
                        'money' => $player->getCurrentMoney(),
                        'client_fds' => [$fd],
                    ];
                    return $msg;
                }
            } catch (\Throwable $th) {
                echo $th->getMessage() . PHP_EOL;
            }
        }
    }

    public function randomizeUniqueItem()
    {
        $basic_item = Enum::UNIQUE_ITEM['BASIC'];
        $rare_item = Enum::UNIQUE_ITEM['RARE'];
        $epic_item = Enum::UNIQUE_ITEM['EPIC'];

        foreach ($this->players as $player) {
            $item = [];
            $price = [];
            shuffle($basic_item);
            shuffle($rare_item);
            shuffle($epic_item);

            switch ($this->round) {
                case 1:
                    array_push($item, $basic_item[0]);
                    array_push($price, Enum::UNIQUE_ITEM_PRICE[$basic_item[0]]);
                    array_push($item, $basic_item[1]);
                    array_push($price, Enum::UNIQUE_ITEM_PRICE[$basic_item[1]]);
                    array_push($item, $rare_item[0]);
                    array_push($price, Enum::UNIQUE_ITEM_PRICE[$rare_item[0]]);
                    break;
                case 2:
                    array_push($item, $basic_item[0]);
                    array_push($price, Enum::UNIQUE_ITEM_PRICE[$basic_item[0]]);
                    array_push($item, $rare_item[0]);
                    array_push($price, Enum::UNIQUE_ITEM_PRICE[$rare_item[0]]);
                    array_push($item, $epic_item[0]);
                    array_push($price, Enum::UNIQUE_ITEM_PRICE[$epic_item[0]]);
                    break;
                case 3:
                    array_push($item, $rare_item[0]);
                    array_push($price, Enum::UNIQUE_ITEM_PRICE[$rare_item[0]]);
                    array_push($item, $epic_item[0]);
                    array_push($price, Enum::UNIQUE_ITEM_PRICE[$epic_item[0]]);
                    array_push($item, $epic_item[1]);
                    array_push($price, Enum::UNIQUE_ITEM_PRICE[$epic_item[1]]);
                    break;
                default:
                    echo "no such round !\n";
                    break;
            }
            $fd = $player->getPlayerId();
            $money = $player->getCurrentMoney();
            $shop_info = JsonLoader::Load('Shop.json');
            $shop_info['item'] = $item;
            $shop_info['price'] = $price;
            $shop_info['money'] = $money;
            $shop_info['client_fds'] = [$fd];

            $to_proxy_data = 'SHOP ' . json_encode($shop_info) . "\r\n";
            $this->gs->send($this->proxy_fd, $to_proxy_data);
            echo "Data sent to proxy server : {$to_proxy_data}\n";
        }
    }

    //回傳遊戲初始資訊
    public function getGameInfo()
    {
        echo "\nBelow is GameInfo:\n";
        $game_info = JsonLoader::Load('Map.json');

        $game_info['playerinfo'] = [];
        foreach ($this->players as &$player) {
            $position = $player->getPosition();
            $player_id = $player->getPlayerId();

            $player_info = [];
            $player_info['fd'] = $player_id;
            $player_info['position'] = ['x' => $position['x'], 'y' => $position['y']];
            array_push($game_info['playerinfo'], $player_info);
        }

        $map_info = $this->map->getMapInfo();
        $game_info['map']['width'] = $map_info['map']['width'];
        $game_info['map']['height'] = $map_info['map']['height'];
        $game_info['map']['grid'] = $map_info['map']['grid'];

        return $game_info;
    }

    public function sendGameInfo()
    {
        $game_info = $this->getGameInfo();
        $game_info['client_fds'] = $this->game_player_fds;
        $action = 'MAP';
        $to_proxy_data = $action . ' ' . json_encode($game_info) . "\r\n";
        $this->gs->send($this->proxy_fd, $to_proxy_data);
        echo "Data sent to proxy server : {$to_proxy_data}\n";
    }

    public function getPlayerPositionInfo(&$player)
    {
        echo "\nBelow is PlayerPosition Info:\n";
        $position = $player->getPosition();

        $position_info = JsonLoader::Load('Update.json');
        $position_info['fd'] = $player->getPlayerId();
        $position_info['position']['x'] = $position['x'];
        $position_info['position']['y'] = $position['y'];

        $position_info = json_encode($position_info);
        echo $position_info;
        return($position_info);
    }

    public function updatePlayerPosition($player, $x, $y)
    {
        $player->move($x, $y);
    }

    //note : x,y是網格座標
    public function ItemPick($player, $x, $y)
    {
        $cell = $this->map->getCell($x, $y);

        //單元格合法
        if ($cell != null) {
            echo "準備從Bomberman->ItemPick拿cell, [$x, $y]\n";
            $item = $cell->getItem();
            if ($item != null) {
                echo Enum::TEXT_COLOR['YELLOW'] . "\n\n很好你吃到了一個" . $item->getName() . "\n\n" . Enum::TEXT_COLOR['RESET'];
                $cell->modifyInnerItem(null);
                $item->modifyPlayerAttributes($player);
                if (isset($item)) {
                    unset($item);
                }
                //顯示玩家更新後的屬性：
                $pick_info = JsonLoader::Load('Item.json');
                $pick_info['itemOwner'] = $player->getPlayerId();
                $pick_info['itemplace'] = [$x, $y];
                $pick_info['playerAttribute'] = [];

                foreach ($this->players as $player) {
                    $attribute_info = [];
                    $attribute_info['fd'] = $player->getPlayerId();
                    $attribute_info['attribute'] = $player->getAttribute();
                    array_push($pick_info['playerAttribute'], $attribute_info);
                }
                $pick_info['client_fds'] = $this->getGamePlayerFds();
                return json_encode($pick_info);
            }
        }
        return null;
    }

    public function bombPlantJsonReturn($bomb, $player, $pos_x, $pos_y)
    {
        $bomb_info = JsonLoader::Load('bomb.json');
        $bomb_info['bombID'] = $bomb->getBombId();
        $bomb_info['fd'] = $player->getPlayerId();
        $bomb_info['place'] = [$pos_y, $pos_x];
        $bomb_info['bomb_num'] = $player->getAttribute()['passive']['bomb_limit'] - $player->getBombPlanted();
        $bomb_info['client_fds'] = $this->getGamePlayerFds();
        $bomb_info = json_encode($bomb_info);
        echo 'BombPlant Info: ' . $bomb_info . "\n";
        return $bomb_info;
    }

    public function bombPlant(&$player, $coordinate) //收到playerObj, x,y
    {
        if ($player->getBombPlanted() < $player->getAttribute()['passive']['bomb_limit'] && $player->getAlive()) {
            $grid_position = $this->calculateFloatToMap($coordinate->x, $coordinate->y);
            $pos_x = $grid_position['x'];
            $pos_y = $grid_position['y'];
            if ($this->map->checkValidBombPlant($pos_y, $pos_x)) { //檢查地圖上的這個點是否能放炸彈
                $params = ['x' => $pos_x, 'y' => $pos_y, 'owner' => $player, 'bomb_handler' => $this->bomb_handler];
                $bomb = ItemFactory::ItemGenerate('BOMB', $params);
                
                Timer::after($bomb->bomb_timer, function () use (&$bomb) { 
                    if (!$bomb->exploded) {
                        echo "\n跑完2000ms, 炸彈爆了\n";
                        $bomb->bomb_handler->bombExplode($bomb);
                        $bomb->setBombToAlreadyExploded();
                    }
                });
               
                $player->modifyBombPlanted(1); //多一顆炸彈了
                $this->map->modifyMap($bomb, $pos_y, $pos_x);
                return $this->bombPlantJsonReturn($bomb, $player, $pos_x, $pos_y);
                
            } else {
                echo "你無法在地圖此處下炸彈\n";
            }
        } else {
            echo "你能放的炸彈已經滿了\n";
            return null;
        }
    }

    /*
    流程 :
    1. 設定gs, proxy_fd, players等相關參數
    2. 根據回合數生成地圖
    3. 玩家基本資訊初始化
    4. bomb_handler初始化
    */
    public function gameStart()
    {
        echo Enum::TEXT_COLOR['YELLOW'] . "\n\n觸發gameStart\n\n" . Enum::TEXT_COLOR['RESET'];
        $this->room_state = 'idle';
        $this->load_scene_count = 0;
        $this->round += 1;
        $this->load_duration = Config::LOAD_DURATION;
        $this->buy_duration = Config::BUY_DURATION;
        $this->ready_duration = Config::READY_DURATION;
        $this->game_duration = Config::GAME_DURATION;

        $width = Config::MAP_INFO[$this->round]['width'];
        $height = Config::MAP_INFO[$this->round]['height'];
        $cell_pixel = Config::MAP_INFO[$this->round]['cell_pixel'];

        $this->map = new Map($width, $height, $cell_pixel, $this->round);

        $initialize_position = [
            ['x' => 32 * $width / 2 - 16,    'y' => 32 * $height / 2 - 16],
            ['x' => -(32 * $width / 2 - 16), 'y' => -(32 * $height / 2 - 16)],
            ['x' => 32 * $width / 2 - 16,    'y' => -(32 * $height / 2 - 16)],
            ['x' => -(32 * $width / 2 - 16), 'y' => 32 * $height / 2 - 16],
        ];

        /*
        1. 重新設定玩家屬性
        2. 設定玩家存活狀況
        3. 設定玩家是否載入場景
        4. 給予玩家每回合的金幣
        5. 玩家移動至起始位置
        */
        foreach ($this->players as $index => &$player) {
            $player->setInRoomNotInGame(false); //他進入遊戲了
            $player->resetAttribute();
            $player->modifyAlive(true);
            $player->setHasLoadScene(false);
            $player->earnMoney(CONFIG::MONEY_SETTING['each_round_free_money'][$this->round]);
            echo "\n\n我(" . $player->getPlayerId() . ')在初始化階段被移動到->' . $initialize_position[$index]['x'] . ', ' . $initialize_position[$index]['y'] . "\n\n";
            $player->move($initialize_position[$index]['x'], $initialize_position[$index]['y']);
        }
        $this->bomb_handler = new Bombhandler($this->players, $this->map, $this->gs, $this->proxy_fd, $this->game_player_fds);
        $this->item_effect_handler = new ItemEffectHandler($this->players, $this->map, $this->gs, $this->proxy_fd, $this->game_player_fds);
        foreach ($this->players as $index => &$player) {
            $this->getPlayerPositionInfo($player);
        }
    }

    //真實世界座標換算網格座標
    public function calculateFloatToMap($x, $y)
    {
        $width = $this->map->getWidth();
        $height = $this->map->getHeight();
        $base_pixel = $this->map->getPixel();
        $corresponding_map_position = ['x' => null, 'y' => null];
        $x_shift = $width / 2 * $base_pixel;
        $y_shift = -($height / 2 * $base_pixel);
        $x += $x_shift;
        $y = -($y + $y_shift);
        $corresponding_map_position['x'] = floor($x / $base_pixel);
        $corresponding_map_position['y'] = floor($y / $base_pixel);
        return $corresponding_map_position;
    }

    //在終端機顯示player屬性 方便後面debug
    public function showPlayerAttribute()
    {
        echo "-----------------player attribute-------------------\n\n";
        // foreach ($this->players as $player) {
        //     $attribute = $player->getAttribute();
        //     $name = $player->getName();

        //     echo $name . "的屬性 : \n";
        //     echo '金錢 : ' . $player->getCurrentMoney() . "\n";
        //     echo '被動';
        //     print_r($attribute['passive']);
        //     echo '主動';
        //     print_r($attribute['active']);
        //     echo "\n";
        //     print_r($attribute['current_health']);
        // }
    }

    //處理每回合結束需要做的事
    public function handleRoundEnd()
    {
        //每個玩家觸發回合結束時要使用的增益
        foreach ($this->players as $player) {
            $round_end_trigger_buff = $player->getRoundEndTriggerBuff();
            foreach ($round_end_trigger_buff as $buff_name => $is_own) {
                if ($is_own === true) {
                    $this->triggerAbility($player, $buff_name);
                }
            }
        }
        //傳送生涯數據成就
        $this->sendPlayersAchievementData();
    }

    //玩家啟動技能
    public function triggerAbility($player, $ability_label)
    {
        $this->item_effect_handler->useActiveItem($player, $ability_label);
    }

    public function __destruct()
    {
        echo "Bomberman room_id = {$this->room_id} has been destroyed!\n";
    }
}
