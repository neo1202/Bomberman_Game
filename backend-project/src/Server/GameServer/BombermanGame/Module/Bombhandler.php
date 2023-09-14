<?php

namespace MyApp\Server\GameServer\BombermanGame\Module;

use MyApp\Config\Config;
use MyApp\Enum\Enum;
use MyApp\Module\JsonLoader;
use Swoole\Timer;

/*
專門用來處理炸彈爆炸時的物件
*/
class ExplosionResult
{
    public $dead_people = [];
    public $destroyed_cells = []; // 被炸的格子的座標列表
    public $appear_game_items = []; //炸出來的道具
    public $explode_effect_range = []; // 炸彈火焰的座標列表
    public $damaged_arr = [];
}
class Bombhandler
{
    private $proxy_fd;
    private $gs;              //game server
    private $players;
    private $map;
    private $game_player_fds;

    public function __construct(&$players, &$map, &$gs, $fd, $game_player_fds)
    {
        $this->players = $players;
        $this->map = $map;
        $this->gs = $gs;
        $this->proxy_fd = $fd;
        $this->game_player_fds = $game_player_fds;
    }

    public function explodeActionPlusJsonReturn($bomb, $explosion_results)
    {
        $explode_info = JsonLoader::Load('explode.json');
        $explode_info['bombID'] = $bomb->getBombId();
        $explode_info['deads'] = $explosion_results->dead_people;
        $explode_info['damagedPlayers'] = $explosion_results->damaged_arr;
        $explode_info['destroy'] = $explosion_results->destroyed_cells;
        $explode_info['gameItem'] = $explosion_results->appear_game_items;
        $explode_info['explodeEffectRange'] = $explosion_results->explode_effect_range;
        $explode_info['playerInfo'] = $this->getExplodePlayerInfo();
        $explode_info['client_fds'] = $this->game_player_fds;
        $to_proxy_data = 'EXPLODE ' . json_encode($explode_info) . "\r\n";
        $this->gs->send($this->proxy_fd, $to_proxy_data);
        echo "EXPLODE Data sent to proxy server by bombhandler: {$to_proxy_data}\n";
    }

    public function handleAfterBombDamageWithReturn(&$bomb, $explode_effect_range)
    {
        $damaged_arr = [];
        $dead_arr = [];
        $bomb_owner = $bomb->getOwner();
        $bomb_dmg = $bomb->getPowerDamage();
        foreach ($this->players as &$onePlayer) {
            if (!$bomb->isPlayerAlreadyDamaged($onePlayer) && $onePlayer->getAlive()) { //如果這個玩家還沒被這顆炸彈炸過且他還活著才接下去判斷
                [$pcol, $prow] = $this->calculatePersonGrid($onePlayer->getPosition()['x'], $onePlayer->getPosition()['y']);
                // 跑一遍炸彈範圍, 看有沒有包含pcol,prow
                $isInArea = false;
                for ($i = $explode_effect_range['left'][1]; $i <= $explode_effect_range['right'][1]; $i++) {
                    if ([$pcol, $prow] == [$i, $explode_effect_range['left'][0]]) { //炸到了
                        //echo "(handleAfterBombDamageWithReturn火焰餘威)此人在這個左到右範圍內\n";
                        $isInArea = true;
                    }
                }
                for ($i = $explode_effect_range['top'][0]; $i <= $explode_effect_range['bottom'][0]; $i++) {
                    if ([$prow, $pcol] == [$i, $explode_effect_range['top'][1]]) { //炸到了
                        //echo "(handleAfterBombDamageWithReturn火焰餘威)此人在這個上到下範圍內\n";
                        $isInArea = true;
                    }
                }
                if ($isInArea) {
                    echo "\n爆炸後火焰餘威範圍內[$prow, $pcol]檢查到Player_" . $onePlayer->getPlayerId() . ' is damaged by Player_' . $bomb_owner->getPlayerId() . "的炸彈\n";
                    $bomb->markPlayerAsDamaged($onePlayer);
                    $onePlayer->modifyCurrentHealth($bomb_dmg, $bomb_owner); //更改此人血量，考量到護甲與成就榜、賺錢
                    $damaged_arr[] = [
                        'fd' => $onePlayer->getPlayerId(),
                        'current_health' => $onePlayer->getAttribute()['current_health'],
                        'remaining_shield' => $onePlayer->getAttribute()['passive']['shield']
                    ];
                    if ($onePlayer->getAttribute()['current_health'] <= 0) {
                        $dead_arr[] = $onePlayer->getPlayerId();
                    }
                    $onePlayer->showCurrentAttribute();
                }
            }
        }
        if (!empty($damaged_arr)) { //代表有人被炸到了
            //回傳到proxy
            echo "檢查火焰餘威所殺到的dead_arr\n";
            print_r($dead_arr);
            $msg = [
                'damagedPlayers' => $damaged_arr,
                'deadPlayers' => $dead_arr,
                'client_fds' => $this->game_player_fds,
            ];
            $to_proxy_data = 'FLAME_SPREAD_PLAYER ' . json_encode($msg) . "\r\n";
            $this->gs->send($this->proxy_fd, $to_proxy_data);
            echo "FLAME_SPREAD_PLAYER sent to proxy server by bombhandler: {$to_proxy_data}\n";
        }
    }

    public function afterBombExplodeFireTick(&$bomb, $explosion_results)
    { //https://github.com/deminy/swoole-by-examples/blob/master/examples/timer/timer.php
        $fire_lasting = CONFIG::$bomb_timing['bomb_fire_lasting']; //450ms
        $timer = $this->gs->tick(15, function () use (&$timer, &$fire_lasting, $bomb, $explosion_results) { //15ms, 1 tick
            if ($fire_lasting >= 0) {
                $this->handleAfterBombDamageWithReturn($bomb, $explosion_results->explode_effect_range);
                $fire_lasting -= 15;
            } else {
                $this->gs->clearTimer($timer);
            }
        });
    }

    public function bombExplode(&$bomb)
    {
        $explosion_results = new ExplosionResult();
        
        $who = $this->knowPlayerByObj($bomb->getOwner());
        $who->modifyBombPlanted(-1);
        $this->destroyMapCellSeeResult($bomb, $explosion_results); //直接修改explosion_results
        //echo "炸彈爆炸後地圖:\n";
        //$this->map->drawMap();
        $this->explodeActionPlusJsonReturn($bomb, $explosion_results); //在這邊主動回傳給proxy
        $this->afterBombExplodeFireTick($bomb, $explosion_results);
    }

    public function destroyMapCellSeeResult(&$bomb, &$explosion_results)
    {
        $map_height = $this->map->getHeight();
        $map_width = $this->map->getWidth();
        //(5,2) -> [2,5] -> hori=5, vert=2
        $start_hori = $bomb->getPosition()['x']; //5
        $start_vert = $bomb->getPosition()['y']; //2
        $max_range = $bomb->getRageRange();
        $offsets = [
            [-1, 0, 'top'],    // 上方
            [1, 0, 'bottom'],  // 下方
            [0, -1, 'left'],   // 左方
            [0, 1, 'right']    // 右方
        ];
        $this->map->map[$start_vert][$start_hori]->modifyInnerItem(null); //本來就只是一顆炸彈->炸沒了
        $farthest_points = []; //用來儲存上下左右四個點的位置 如"explodeEffectRange":{left:[0,0], right:[2,0], top:[1,1], bottom:[0,0]}
        foreach ($offsets as $offset) {
            //TODO
            $farthest_points[$offset[2]] = [$start_vert, $start_hori]; //初始此方向就是炸彈一開始的位置
            $row = $start_vert + $offset[0];
            $col = $start_hori + $offset[1];
            $range = 0;
            while ($range < $max_range && $row >= 0 && $row < $map_height && $col >= 0 && $col < $map_width) {
                switch ($this->map->map[$row][$col]->getName()) {
                    case 'ROAD':
                        $farthest_points[$offset[2]] = [$row, $col];
                        break;
                    case 'STONE': // 遇到不可破壞的地形，停止延伸
                        break 2; //跳出兩層迴圈
                    case 'WOOD':
                        $explosion_results->destroyed_cells[] = [$row, $col];
                        $appear_item_name = $this->map->map[$row][$col]->destroyWood();
                        if ($appear_item_name) {
                            $explosion_results->appear_game_items[] = [
                                'place' => [$row, $col],
                                'item' => $appear_item_name
                            ];
                        }
                        $bomb->getOwner()->getAchievements()->increaseDestroyedBuildings();
                        break 2;
                    case 'BOMB': //連續爆炸
                        echo "爆炸時撞到了某個炸彈\n";
                        $farthest_points[$offset[2]] = [$row, $col];
                        $target_bomb = $this->map->map[$row][$col]->getItem();
                        Timer::after(CONFIG::$bomb_timing['bomb_trigger_bomb_delay'], function () use (&$target_bomb) {
                            try {
                                if (!$target_bomb->exploded) {
                                    echo "\n跑完300ms, 炸彈被波及提前爆了\n";
                                    $target_bomb->bomb_handler->bombExplode($target_bomb);
                                    $target_bomb->setBombToAlreadyExploded();
                                }
                            } catch (\Throwable $e) {
                                // 在這裡處理可能的異常情況，例如 $target_bomb 為 null 或其他錯誤
                                echo "\n發生錯誤, 這個炸彈可能消失了,", $e->getMessage(), "\n";
                            }
                        });
                        break;
                    default: //可能遇到道具了
                        echo '爆炸時撞到了一格道具: ' . $this->map->map[$row][$col]->getName();
                        $farthest_points[$offset[2]] = [$row, $col];
                        break;
                }
                $row += $offset[0];
                $col += $offset[1];
                $range++;
            }
        }
        $explosion_results->explode_effect_range = $farthest_points;
        $this->checkEffectRangePeople($explosion_results, $bomb);
    }

    /*
    以下兩個function會得知炸彈火力涵蓋哪些區域，以及是這是誰的炸彈
    然後計算範圍內有誰在裡面，有沒有被這顆炸彈炸過了，計算傷害以及死亡沒
    不只第一次爆炸時用到，轟炸後的450毫秒內, 每15毫秒會check一次(tick)... 好吧 這邊另外寫
    */
    public function checkEffectRangePeople(&$explosion_results, &$bomb)
    {
        // echo "\n\n(Bombhandler) checking people in bomb range ...\n左右範圍: ";
        for ($i = $explosion_results->explode_effect_range['left'][1]; $i <= $explosion_results->explode_effect_range['right'][1]; $i++) {
            // echo "[" . $explosion_results->explode_effect_range['left'][0] . "," . $i . "] ";
            $this->checkDamageToPlayer($explosion_results->explode_effect_range['left'][0], $i, $explosion_results, $bomb);
        }
        // echo "\n上下範圍: ";
        for ($i = $explosion_results->explode_effect_range['top'][0]; $i <= $explosion_results->explode_effect_range['bottom'][0]; $i++) {
            // echo "[" . $i . "," . $explosion_results->explode_effect_range['top'][1] . "] ";
            $this->checkDamageToPlayer($i, $explosion_results->explode_effect_range['top'][1], $explosion_results, $bomb);
        }
        // echo "\nend of checking explode range\n";
    }

    public function checkDamageToPlayer($row, $col, &$explosion_results, &$bomb)
    {
        $bomb_owner = $bomb->getOwner();
        $bomb_dmg = $bomb->getPowerDamage();
        foreach ($this->players as &$onePlayer) {
            if (!$bomb->isPlayerAlreadyDamaged($onePlayer) && $onePlayer->getAlive()) {
                [$pcol, $prow] = $this->calculatePersonGrid($onePlayer->getPosition()['x'], $onePlayer->getPosition()['y']);
                if ($row == $prow && $col == $pcol) { //炸到了
                    echo "\n爆炸範圍內[$prow, $pcol]立刻檢查到Player_" . $onePlayer->getPlayerId() . ' is damaged by Player_' . $bomb_owner->getPlayerId() . "的炸彈\n";
                    $bomb->markPlayerAsDamaged($onePlayer);
                    $onePlayer->modifyCurrentHealth($bomb_dmg, $bomb_owner); //更改此人血量，考量到護甲與成就榜、賺錢\一次性盾牌
                    if ($onePlayer->getAttribute()['current_health'] <= 0) { //被炸死了
                        //echo Enum::TEXT_COLOR['RED'] . "!!!!!!\n\n有人被炸死了!!!\n\n\n!!!\n" . Enum::TEXT_COLOR['RESET'];
                        $explosion_results->dead_people[] = $onePlayer->getPlayerId();
                    }
                    $explosion_results->damaged_arr[] = [
                        'fd' => $onePlayer->getPlayerId(),
                        'current_health' => $onePlayer->getAttribute()['current_health']
                    ];
                }
            }
        }
    }

    public function getExplodePlayerInfo()
    {
        $info = [];
        foreach ($this->players as &$player) {
            $info[] = [
                'fd' => $player->getPlayerId(),
                'bomb_num' => $player->getAttribute()['passive']['bomb_limit'] - $player->getBombPlanted(),
                'shield_num' => $player->getAttribute()['passive']['shield']
            ];
        }
        return $info;
    }

    private function knowPlayerByObj($playerObj)
    {
        foreach ($this->players as &$player) {
            if ($player == $playerObj) {
                echo 'find player_' . $player->getPlayerId() . "'s bomb explode!\n";
                return $player;
            }
        }
        return null;
    }

    public function calculatePersonGrid($x, $y)
    {
        $width = $this->map->getWidth();
        $height = $this->map->getHeight();
        $base_pixel = $this->map->getPixel();
        $x_shift = $width / 2 * $base_pixel;
        $y_shift = -($height / 2 * $base_pixel);
        $x += $x_shift;
        $y = -($y + $y_shift);
        return [floor($x / $base_pixel), floor($y / $base_pixel)];
    }
}
