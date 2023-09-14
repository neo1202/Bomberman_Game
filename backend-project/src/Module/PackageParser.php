<?php

namespace MyApp\Module;

/*
用來解析封包
*/
class PackageParser
{
    public static function parseCommand($data)
    {
        $data = trim($data, '"');
        list($action, $json_data) = explode(' ', $data, 2);          //最多分成兩部分
        $action = strtoupper(trim($action));
        return [$action, $json_data];
    }

    //用來處理tcp黏包，將拆分好的package以陣列形式回傳
    public static function tcpPackageSplit($data)
    {
        $data = trim($data);
        $data = explode("\r\n", $data);
        return $data;
    }

}
