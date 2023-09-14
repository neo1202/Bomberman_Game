<?php

namespace MyApp\Server\GameServer\BombermanGame\Module\Factory;

use MyApp\Server\GameServer\BombermanGame\Module\Cell;
use MyApp\Server\GameServer\BombermanGame\Module\Block;
use MyApp\Enum\Enum;

class MapFactory
{
    /*
    根據給入的地圖資訊來產生地圖
    */
    public static function MapGenerate(array $map_info)
    {
        $width = count($map_info[0]);
        $height = count($map_info);
        $map = [];

        for ($i=0 ; $i<$height ; $i++) {
            $map[$i] = [];
            for ($j=0 ; $j<$width ; $j++) {
                $map[$i][$j] = new Cell($i, $j, new Block($i, $j, Enum::CELL_LABEL_TO_NAME[$map_info[$i][$j]]));
            }
        }

        return $map;
    }
}
