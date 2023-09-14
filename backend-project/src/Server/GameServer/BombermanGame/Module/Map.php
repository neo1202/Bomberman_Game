<?php

namespace MyApp\Server\GameServer\BombermanGame\Module;

use MyApp\Server\GameServer\BombermanGame\Module\Cell;
use MyApp\Server\GameServer\BombermanGame\Module\Factory\MapFactory;
use MyApp\Enum\Enum;
use MyApp\Config\Config;
use MyApp\Module\JsonLoader;
use MyApp\Server\GameServer\BombermanGame\Module\Factory\ItemFactory;

class Map
{
    //public 想辦法改成private
    public $map;
    private $width;
    private $height;
    private $cell_pixel;

    public function __construct(int $width, int $height, $cell_pixel, $game_round)
    {
        $this->width = $width;
        $this->height = $height;
        $this->cell_pixel = $cell_pixel;
        //根據回合數創建對應的地圖
        $this->map = $this->autoGenerateMap($game_round);
    }

    public function autoGenerateMap($game_round)
    {
        if ($game_round === 2) {
            $map_array = Config::MAP[$game_round];
            $size = count($map_array);
            for ($i = 0; $i < $size; $i++) {
                if ($i % 2 === 1) {
                    continue;
                } else {
                    for ($j = 0; $j < $size; $j++) {
                        $map_array[$i][$j] = $this->randomGenerateBlock($game_round);
                    }
                }
            }
            $map_array[0][0] = 0;
            $map_array[0][1] = 0;
            $map_array[1][0] = 0;

            $map_array[0][$size-1] = 0;
            $map_array[0][$size-2] = 0;
            $map_array[1][$size-1] = 0;

            $map_array[$size-1][0] = 0;
            $map_array[$size-1][1] = 0;
            $map_array[$size-2][0] = 0;

            $map_array[$size-1][$size-1] = 0;
            $map_array[$size-1][$size-2] = 0;
            $map_array[$size-2][$size-1] = 0;
            return MapFactory::MapGenerate($map_array);
        } elseif ($game_round === 3) {
            $map_array = Config::MAP[$game_round];
            $size = count($map_array);
            for ($i = 0; $i < $size; $i++) {
                for ($j = 0; $j < $size; $j++) {
                    if ($map_array[$i][$j] !== 2) {
                        $map_array[$i][$j] = $this->randomGenerateBlock($game_round);
                    }
                }
            }
            $map_array[0][0] = 0;
            $map_array[0][1] = 0;
            $map_array[1][0] = 0;

            $map_array[0][$size-1] = 0;
            $map_array[0][$size-2] = 0;
            $map_array[1][$size-1] = 0;

            $map_array[$size-1][0] = 0;
            $map_array[$size-1][1] = 0;
            $map_array[$size-2][0] = 0;

            $map_array[$size-1][$size-1] = 0;
            $map_array[$size-1][$size-2] = 0;
            $map_array[$size-2][$size-1] = 0;
            return MapFactory::MapGenerate($map_array);
        } else {
            return MapFactory::MapGenerate(Config::MAP[$game_round]);
        }
    }

    public function randomGenerateBlock($round)
    {
        if ($round === 2) {
            $probabilities = [
                0 => 0.4,
                1 => 0.6
            ];
        } else {
            $probabilities = [
                0 => 0.3,
                1 => 0.7
            ];
        }
        $value = null;
        $randFloat = mt_rand() / mt_getrandmax();
        foreach ($probabilities as $num => $probability) {
            if ($randFloat < $probability) {
                $value = $num;
                break;
            } else {
                $randFloat -= $probability;
            }
        }
        return $value;
    }

   
    public function checkValidBombPlant($pos_1, $pos_2) //pos_1代表陣列的第一個位子，也就是y的數值
    {
        $cell = $this->map[$pos_1][$pos_2];
        if ($cell != null) {
            if ($cell->getName() == 'ROAD') {
                echo "想放炸彈在ROAD上, ok\n";
                return 1;
            } else {
                echo '不可以放炸彈在' . $cell->getName() . "上\n";
                return 0;
            }
        } else {
            echo '(Map)(checkValidBombPlant)這個cell是Null\n';
            return 0;
        }
    }

    // 如果前面已經判定可以放炸彈，代表這個格子是個Block中的ROAD，所以可以直接改成item->bomb
    public function modifyMap($obj, $pos_1, $pos_2)
    {
        $cell = $this->map[$pos_1][$pos_2];
        $cell->modifyInnerItem($obj);
        
    }

    private function convertToItemNum($obj) //傳入obj, 從Enum拿他對應的數字
    {
        $item_num = Enum::ITEM[$obj->getName()];
        echo "This object is ITEM number_$item_num\n";
        return $item_num;
    }

    public function getWidth()
    {
        return $this->width;
    }

    public function getHeight()
    {
        return $this->height;
    }

    public function getPixel()
    {
        return $this->cell_pixel;
    }

    //在terminal畫出地圖，方便後續測試
    public function drawMap()
    {
        echo'  ';
        for ($i=0 ; $i<$this->width ; $i++) {
            echo ' ' . $i . ' ';
        }
        echo"\n-----------------------------------------------------\n";

        for ($i=0 ; $i<$this->height ; $i++) {
            if ($i<10) {
                echo ' ';
            }
            echo $i . '|';
            for ($j=0 ; $j<$this->width ; $j++) {
                if ($j>9) {
                    echo ' ';
                }
                if (in_array($this->map[$i][$j]->getName(), array_keys(Enum::BLOCK_NAME_TO_LABEL))) {
                    echo Enum::BLOCK_NAME_TO_LABEL[$this->map[$i][$j]->getName()] . '  ';
                } else {
                    echo Enum::ITEM[$this->map[$i][$j]->getName()] . ' ';
                }
            }
            echo"\n";
        }
        echo "\n";
    }

    // 回傳地圖長寬，與單元格資訊
    public function getMapInfo()
    {
        $info = [];
        $info['map']['width'] = $this->width;
        $info['map']['height'] = $this->height;
        $info['map']['grid'] = [];

        for ($i=0 ; $i<$this->height ; $i++) {
            $info['map']['grid'][$i] = [];
            for ($j=0 ; $j<$this->width ; $j++) {
                $info['map']['grid'][$i][$j] = Enum::BLOCK_NAME_TO_LABEL[$this->map[$i][$j]->getName()];
            }
        }
        return($info);
    }

    //回傳地圖單元格
    public function getCell($x, $y)
    {
        if ($x >= $this->width || $y >= $this->height) {
            echo"Cell index out of range !!\n";
            return null;
        }
        return $this->map[$x][$y];
    }
}
