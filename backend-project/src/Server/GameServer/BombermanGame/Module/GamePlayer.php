<?php

namespace MyApp\Server\GameServer\BombermanGame\Module;

use MyApp\Enum\Enum;
use MyApp\Config\Config;
use MyApp\Server\LobbyServer\Module\Player;
use MyApp\Server\GameServer\BombermanGame\Module\Item\Bomb;
use MyApp\Server\GameServer\BombermanGame\Module\Achievements;
use MyApp\Server\GameServer\BombermanGame\Module\Attribute;

class GamePlayer extends Player
{
    private $bomb_planted;  //玩家已下的炸彈數
    private $my_bombs_record_on_map; //紀錄我下的所有的地圖上現有炸彈
    private $attribute;     //玩家擁有的能力值
    private $alive;
    private $coordinate;
    private $current_money;
    private $has_load_scene;
    private $achievements;
    private $immune = false;
    private $round_end_trigger_buff = [];   //用來存回合結束時需要啟動道具的編號  round_end_trigger_buff[4] = true, and so on...

    public function __construct($fd, $in_which_room)
    {
        //@param string $playerId = fd
        parent::__construct($fd, $in_which_room);
        $this->achievements = new Achievements();
        $this->attribute = new Attribute();
        $this->current_money = 0;
        $this->my_bombs_record_on_map = [];
    }

    public function getMyBombsRecordOnMap()
    {
        return $this->my_bombs_record_on_map;
    }

    public function modifyBombsRecordOnMap($status, $bomb) //status=false代表要把array中的此炸彈移除代表他已經炸了
    {
        if ($status == true) { //新紀錄一顆炸彈
            $this->my_bombs_record_on_map[] = $bomb;
        } elseif ($status == false) { //把現有炸彈紀錄移除
            foreach ($this->my_bombs_record_on_map as $key => &$one_bomb) {
                if ($one_bomb === $bomb) {
                    if (isset($this->my_bombs_record_on_map[$key])) {
                        unset($this->my_bombs_record_on_map[$key]);
                    }
                    $this->my_bombs_record_on_map = array_values($this->my_bombs_record_on_map);
                    //echo "Bomb {$bomb->getBombId()} detonated(已爆炸且從玩家的bomb list移除).\n";
                    break;
                }
            }
        } else {
            echo "奇怪的modifyBombsRecordOnMap情況出現\n";
        }
    }

    public function getCurrentMoney()
    {
        return $this->current_money;
    }

    public function earnMoney($money)
    {
        if ($money > 0) {
            $this->achievements->increaseTotalEarnedMoney($money);
        }
        $this->current_money += $money;
    }

    public function getHasLoadScene()
    {
        return $this->has_load_scene;
    }

    public function setHasLoadScene(bool $bool)
    {
        $this->has_load_scene = $bool;
    }

    public function getAchievements()
    {
        return $this->achievements;
    }

    public function returnAchievementsData()
    {
        return $this->achievements->getAchievementData();
    }

    public function getBombPlanted()
    {
        return $this->bomb_planted;
    }

    public function getAttribute()
    {
        $attribute = $this->attribute->getCurrentAttribute();
        $attribute['money'] = $this->current_money;
        return $attribute;
    }

    public function showCurrentAttribute()
    {
        return $this->attribute->showCurrentAttributeOnTerminal();
    }

    public function setAttribute($attribute)
    {
        $this->attribute->setCurrentAttribute($attribute);
    }

    public function resetAttribute()
    {
        $this->attribute->resetAttribute();
    }

    public function getUniqueAttribute()
    {
        return $this->attribute->getUnique();
    }

    public function setUniqueAttribute($attribute)
    {
        $this->attribute->setUnique($attribute);
    }

    public function applyUniqueAttribute()
    {
        $this->attribute->applyUnique();
    }

    public function modifyBombPlanted($int_num)
    {
        $this->bomb_planted += $int_num;
        if ($int_num > 0) {
            $this->getAchievements()->increaseToTalPlaceBomb();
            //echo "我(Player_$this->player_id)這輩子總共放了" . $this->getAchievements()->getToTalPlaceBomb() . "個炸彈\n";
        }
    }

    //更改玩家座標
    public function move($x, $y)
    {
        $this->coordinate = ['x' => $x, 'y' => $y];
    }

    public function displayInfo()
    {
        echo 'New GamePlayer Object, fd_' . $this->player_id . ', in Room_: ' . $this->in_which_room . "\n";
    }

    public function modifyCurrentHealth($health_change, &$from_which_player)
    {
        $curr_attribute = $this->getAttribute();
        $armor = $curr_attribute['passive']['armor'];
        $real_dmg = round($health_change * $armor); //四捨五入傷害
        $from_which_player->getAchievements()->increaseTotalDealDamage($real_dmg); //炸在盾牌、炸在自己身上都算是成就榜傷害
        echo "(GamePlayer)原本要受到的傷害: $health_change, 實際減傷後受到傷害: $real_dmg\n";
        if ($curr_attribute['passive']['shield'] > 0) {
            $curr_attribute['passive']['shield'] -= 1;
            echo Enum::TEXT_COLOR['YELLOW'] . "你使用了一次性抵擋傷害盾牌，他幫你減少了 $real_dmg 點傷害\n" . Enum::TEXT_COLOR['RESET'];
            $real_dmg = 0;
        }
        if ($curr_attribute['current_health'] <= $real_dmg) { //死了
            $curr_attribute['current_health'] = 0;
            $this->modifyAlive(false, $from_which_player);
        } else { //沒被炸死
            $curr_attribute['current_health'] -= $real_dmg;
        }
        echo 'SETTING NEW CURRENT ATTRIBUTE';
        print_r($curr_attribute);
        $this->setAttribute($curr_attribute);
    }

    public function modifyAlive($status, &$killer_player = null) //殺人的人會得到金錢獎勵，被殺的人金錢會減半
    {
        if ($status == false) { //基本上只會到死狀態？除非下一輪
            if ($this->getPlayerId() != $killer_player->getPlayerId()) { //是其他人殺了我
                echo Enum::TEXT_COLOR['RED'] . "被其他人殺掉了 可憐那\n" . Enum::TEXT_COLOR['RESET'];
                $killer_player->getAchievements()->increaseKilledPlayers();
                $killer_player->earnMoney(CONFIG::MONEY_SETTING['kill_reward']);
            } else {
                echo Enum::TEXT_COLOR['RED'] . "你炸死自己了 可憐那\n" . Enum::TEXT_COLOR['RESET'];
            }
            echo '(GamePlayer modifyAlive)我(player_' . $this->getPlayerId() . ")死了，原本的錢: $this->current_money, 之後變成:";
            $this->current_money = $this->current_money / 2;
            echo "$this->current_money \n";
        }

        $this->alive = $status;
    }

    public function getAlive()
    {
        return $this->alive;
    }

    public function getPosition()
    {
        return $this->coordinate;
    }

    public function getRoundEndTriggerBuff()
    {
        return $this->round_end_trigger_buff;
    }

    public function setRoundEndTriggerBuff($round_end_trigger_buff)
    {
        $this->round_end_trigger_buff = $round_end_trigger_buff;
    }
}
