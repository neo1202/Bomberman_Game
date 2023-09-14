<?php

namespace MyApp\Server\GameServer\BombermanGame\Module;

use MyApp\Enum\Enum;

/*
代表地圖上面的單元格
上面存有item block 物件
*/
class Cell
{
    private $coordinate;
    private $item;
    private $block;

    public function __construct(int $x, int $y, $block = null, $item = null)
    {
        $this->coordinate = ['x' => $x, 'y' => $y];
        $this->block = $block;
        $this->item = $item;
    }
    public function modifyInnerItem($obj)
    {
        $this->item = $obj;
    }

    public function getPosition()
    {
        return $this->coordinate;
    }

    /*
    回傳名稱順序： 道具 > 區塊
    1. 如果單元格上有道具，回傳道具名稱
    2. 如果單元格上沒有道具，回傳區塊名稱
    */
    public function getName()
    {

        if($this->item != null) {
            return $this->item->getName();
        } elseif ($this->block != null) {
            return $this->block->getName();
        } else {
            echo "發生錯誤，單元格中沒有道具也沒有區塊\n";
        }

    }


    public function destroyWood()
    {
        $item = $this->block->destroy(); //破壞後block回傳裡面有的item
        if ($item != null) {
            $this->modifyInnerItem($item);
            return $item->getName();
        } else {
            return null;
        }
    }

    public function getItem()
    {
        if ($this->item != null) {
            return $this->item;
        } else {
            echo $this->coordinate['x'] . $this->coordinate['y'] . "Cell->getItem(), 但這邊沒有item\n";
            return null;
        }
    }



}
