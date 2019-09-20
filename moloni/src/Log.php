<?php
namespace Moloni;

class Log
{

    private static $fileName = false;

    public static function write($message)
    {
        if (!is_dir(MOLONI_DIR . '/logs')) {
            mkdir(MOLONI_DIR . '/logs');
        }

        $fileName = self::$fileName ? self::$fileName . '.log' : date("Ymd") . '.log';
        $logFile = fopen(MOLONI_DIR . '/logs/' . $fileName, 'a');
        fwrite($logFile, '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL);
    }

    public static function setFileName($name)
    {
        if (!empty($name)) {
            self::$fileName = $name;
        }
    }

}
