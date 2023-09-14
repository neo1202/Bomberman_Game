<?php

namespace MyApp\Server\GameServer\BombermanGame\Module;

use MyApp\Enum\Enum;

/*
處理user買到這個物件後他的處置
*/
class ItemEffectHandler
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

    // only call for purchase
    public function applyUniqueItem(&$player, $item)
    {
        $attribute = $player->getUniqueAttribute();
        echo $player->getName() . "(Player_" . $player->getPlayerId() . ") 買了道具: " . Enum::ITEM_LABEL_TO_NAME[$item] . "\n";
        switch ($item) {
            case 0: //MAX_HEALTH_1
                $attribute['passive']['max_health'] = round($attribute['passive']['max_health'] * 1.25); //最大生命值+25%
                break;
            case 1: //ARMOR_1
                $attribute['passive']['armor'] = round($attribute['passive']['armor'] * 0.8, 2); //減傷20%
                break;
            case 2: //BOMB_POWER_1
                $attribute['passive']['bomb_power'] = round($attribute['passive']['bomb_power'] * 1.2); //爆炸傷害20%
                break;
            case 3: //TRIPLE_COINS
                $attribute['active']['triple_coins'] += 1;
                $round_end_trigger_buff = $player->getRoundEndTriggerBuff();
                $round_end_trigger_buff[3] = true;
                $player->setRoundEndTriggerBuff($round_end_trigger_buff);
                break;
            case 10: //MAX_HEALTH_2
                $attribute['passive']['max_health'] = round($attribute['passive']['max_health'] * 1.5);
                break;
            case 11: //ARMOR_2
                $attribute['passive']['armor'] = round($attribute['passive']['armor'] * 0.65, 2);
                break;
            case 12: //BOMB_POWER_2
                $attribute['passive']['bomb_power'] = round($attribute['passive']['bomb_power'] * 1.45);
                break;
            case 13: //SPEED_LOWER_1
                $this->handleLowerOtherPlayerSpeed($player, 0.75);
                break;
            case 20: //MAX_HEALTH_3
                $attribute['passive']['max_health'] = round($attribute['passive']['max_health'] * 1.8);
                break;
            case 21: //ARMOR_3
                $attribute['passive']['armor'] = round($attribute['passive']['armor'] * 0.5, 2);
                break;
            case 22: //BOMB_POWER_3
                $attribute['passive']['bomb_power'] = round($attribute['passive']['bomb_power'] * 1.8);
                break;
            case 23: //SPEED_LOWER_2
                $this->handleLowerOtherPlayerSpeed($player, 0.50);
                break;
            default:
                return;
        }
        $player->setUniqueAttribute($attribute);
        $player->applyUniqueAttribute();
    }

    // 主動道具分為可永久使用但有cd(只要有數字永遠1), 次數限制無cd(12345)
    // 1. 如果這個主動道具數量<1 代表他不管怎樣都沒 回傳使用失敗
    // 2. 如果他可永久使用, 要額外判斷cd有沒有到(?
    // 之後可進入個別處理環節, 最後回傳使用成功, 同時回傳場上所有的playerAttribute
    public function useActiveItem(&$player, $item)
    {
        switch ($item) {
            case 3: //TRIPLE_COINS
                $this->handleTripleCoins($player);
                break;
            case 21: //遙控炸彈引爆
                $this->handleRemoteTriggerBomb($player);
                break;
            case 29: //其他人血量剩下1
                break;
            default:
                return;
        }

    }
   
    private function handleRemoteTriggerBomb(&$player)
    {
        foreach ($player->getMyBombsRecordOnMap() as &$one_bomb) {
            if (!$one_bomb->exploded) {
                echo "\n被遙控炸彈瞬間引爆, 炸彈提前爆了\n";
                $one_bomb->bomb_handler->bombExplode($target_bomb);
                $one_bomb->setBombToAlreadyExploded();
            }
        }
    }
    private function handleLowerOtherPlayerSpeed($owner_player, $ratio) //0.8,0.65
    {
        echo "確實有人購買此道具!!\n";
        // $speed_owner_fd = $player->getPlayerId();
        foreach ($this->players as &$player) {
            if ($player != $owner_player) {
                $attribute = $player->getUniqueAttribute();
                echo "<<<find player_" . $player->getPlayerId() . "'s speed lower!...origin:" . $attribute['passive']['speed'];
                $attribute['passive']['speed'] = round($attribute['passive']['speed'] * $ratio, 2);
                $player->setUniqueAttribute($attribute);
                $player->applyUniqueAttribute();
                echo ", To: " . $player->getUniqueAttribute()['passive']['speed'] . ">>>\n";
            } else {
                echo "(ItemEffectHandler)You are the speed item owner, immune from speed_lower\n";
            }
        }
    }


    private function handleTripleCoins($player)
    {
        $cur_attribute = $player->getAttribute();
        $uni_attribute = $player->getUniqueAttribute();
        if($uni_attribute['active']['triple_coins'] > 0) {

            //使用了該增益，因此把增益移除掉
            $uni_attribute['active']['triple_coins'] -= 1;
            $cur_attribute['active']['triple_coins'] -= 1;


            $player->setUniqueAttribute($uni_attribute);
            $player->setAttribute($cur_attribute);


            $round_end_trigger_buff = $player->getRoundEndTriggerBuff();
            unset($round_end_trigger_buff[3]);
            $player->setRoundEndTriggerBuff($round_end_trigger_buff);

            $coins = $player->getCurrentMoney();
            $player->earnMoney($coins * 2);
        }
    }

}
