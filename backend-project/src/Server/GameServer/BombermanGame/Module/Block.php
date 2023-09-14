<?php

namespace MyApp\Server\GameServer\BombermanGame\Module;

use MyApp\Enum\Enum;
use MyApp\Config\Config;
use MyApp\Server\GameServer\BombermanGame\Module\Factory\ItemFactory;

//todo
class Block
{
    private $coordinate;
    private $name;
    private $destroyable;
    private $is_destroyed;
    private $content;

    public function __construct(int $x, int $y, string $name)
    {
        $this->coordinate = ['x' => $x, 'y' => $y];
        $this->name = $name;
        $this->destroyable = Enum::DESTROYABLE[$this->name];
        if($this->destroyable !== false) {
            $this->is_destroyed = false;
        } else {
            $this->is_destroyed = true;
        }
        $this->generateItem();
    }

    public function getPosition()
    {
        return $this->coordinate;
    }

    public function isDestroyable()
    {
        return $this->destroyable;
    }


    public function getName()
    {
        return $this->name;
    }

    //區塊被破壞時，區塊名稱變成road
    public function destroy()
    {
        $this->is_destroyed = true;
        $this->destroyable = false;
        $this->name = "ROAD";

        $item = $this->getContent();
        //區塊被破壞後，道具已經掉出至單元格，所以content設為null
        $this->content = null;

        return $item;


    }

    public function getContent() //取 block 裏面的 item
    {
        return $this->content;
    }

    //在區塊中隨機生成Item
    public function generateItem()
    {

        $have_content = false;

        //可以被摧壞代表裡面可能蘊含道具
        if($this->destroyable === true) {


            $threshold = rand(1, 1000);
            $cumulative_probability = 0;

            foreach(Config::ITEM_GENERATE_PROBABILITY as $item_name => $prob) {
                if($item_name === "NULL") {
                    break;
                }

                $cumulative_probability += $prob * 1000;
                if($cumulative_probability >= $threshold) {
                    $this->content = ItemFactory::ItemGenerate($item_name, $this->coordinate);
                    $have_content = true;
                    break;
                }

            }

        }
        if(!$have_content) {
            $this->content = null;
        }
    }

}
