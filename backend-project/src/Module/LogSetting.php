<?php

namespace MyApp\Module;

use DateTime;

class LogSetting
{
    public static function getLogFile()
    {
        $currentDate = (new DateTime())->format('Y-m-d');
        $log_file = __DIR__ . '/../../log/' . $currentDate . ".log";
        return $log_file;
    }
}
