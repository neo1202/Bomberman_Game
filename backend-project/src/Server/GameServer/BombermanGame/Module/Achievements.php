<?php

namespace MyApp\Server\GameServer\BombermanGame\Module;

class Achievements
{
    private $total_place_bomb;
    private $total_deal_damage;
    private $destroyed_buildings;
    private $killed_players;
    private $total_earned_money;

    public function __construct()
    {
        $this->total_place_bomb = 0;
        $this->total_deal_damage = 0;
        $this->destroyed_buildings = 0;
        $this->killed_players = 0;
        $this->total_earned_money = 0;
    }
    public function increaseDestroyedBuildings()
    {
        $this->destroyed_buildings++;
    }

    public function increaseKilledPlayers()
    {
        $this->killed_players++;
    }

    public function increaseTotalEarnedMoney($amount)
    {
        $this->total_earned_money += $amount;
    }
    public function increaseToTalPlaceBomb()
    {
        $this->total_place_bomb++;
    }
    public function increaseTotalDealDamage($amount)
    {
        $this->total_deal_damage += $amount;
    }
    public function getAchievementData()
    {
        return [
            'totalPlaceBomb' => $this->total_place_bomb,
            'totalDealDamage' => $this->total_deal_damage,
            'destroyedBuildings' => $this->destroyed_buildings,
            'killedPlayers' => $this->killed_players,
            'totalEarnedMoney' => $this->total_earned_money
        ];
    }
    public function getToTalPlaceBomb()
    {
        return $this->total_place_bomb;
    }
}
