<?php

namespace MyApp\Server\GameServer\BombermanGame\Module;

abstract class Item
{
    protected $coordinate;
    protected $name;
    protected $id;

    public function __construct(int $x, int $y)
    {
        $this->coordinate = ['x' => $x, 'y' => $y];
        $this->id = spl_object_id($this);           // 如果建構式是由子類呼叫，則$this代表的是子類的物件
    }

    public function __destruct()
    {
        echo $this->name . " has been destroyed\n";
    }

    public function getPosition()
    {
        return $this->coordinate;
    }

    public function getName()
    {
        return $this->name;
    }

    abstract public function modifyPlayerAttributes($player);

}
