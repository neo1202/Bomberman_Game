<?php

namespace MyApp\Server\GameServer\BombermanGame\Module;

use MyApp\Config\Config;
use MyApp\Enum\Enum;

class Attribute
{
    private $unique; //不會被 reset
    private $current_attribute;

    public function __construct()
    {
        $this->unique = Config::$unique_attribute;
        $this->current_attribute = $this->unique;
        $this->current_attribute['current_health'] = $this->current_attribute['passive']['max_health'];
    }

    public function resetAttribute() //每一輪把基本屬性 reset, unique 屬性留著
    {
        $this->applyUnique();
        $this->current_attribute['current_health'] = $this->current_attribute['passive']['max_health'];
    }

    public function getCurrentAttribute()
    {
        return $this->current_attribute;
    }

    public function setCurrentAttribute($current_attribute)
    {
        $this->current_attribute = $current_attribute;
    }

    public function getUnique()
    {
        return $this->unique;
    }

    public function setUnique($unique_attribute)
    {
        $this->unique = $unique_attribute;
    }

    public function applyUnique()
    {
        $this->current_attribute = $this->unique;
        $this->current_attribute['current_health'] = $this->current_attribute['passive']['max_health'];
    }

    public function showCurrentAttributeOnTerminal()
    {
        //print_r($this->current_attribute);
    }

}
