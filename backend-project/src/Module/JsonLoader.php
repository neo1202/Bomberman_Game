<?php

namespace MyApp\Module;

/*
統一由這個Class處理Json檔的載入
*/
class JsonLoader
{
    //以array方式來讀取Json檔
    public static function Load(string $file_name)
    {
        $parent_dir = dirname(__DIR__);
        $file_path = $parent_dir . "/Json/" . $file_name;
        $file = file_get_contents($file_path);
        $file = json_decode($file, true);
        return ($file);
    }

}
