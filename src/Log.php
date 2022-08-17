<?php

namespace Moloni;

use RuntimeException;

class Log
{

    private static $fileName = false;

    public static function write($message)
    {
        try {
            if (!is_dir(MOLONI_DIR . '/logs') && !mkdir($concurrentDirectory = MOLONI_DIR . '/logs') && !is_dir($concurrentDirectory)) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
            }

            $fileName = (Storage::$MOLONI_COMPANY_ID ?: '000')
                . (self::$fileName ?: date('Ymd'))
                . '.log';

            $logFile = fopen(MOLONI_DIR . '/logs/' . $fileName, 'ab');
            fwrite($logFile, '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL);
        } catch (RuntimeException $exception) {

        }
    }

    public static function getFileUrl()
    {
        $fileName = (Storage::$MOLONI_COMPANY_ID ?: '000')
            . (self::$fileName ? self::$fileName . '.log' : date('Ymd'))
            . '.log';

        return MOLONI_PLUGIN_URL . '/logs/' . $fileName;
    }

    public static function removeLogs()
    {
        $logFiles = glob(MOLONI_DIR . '/logs/*.log');
        if (!empty($logFiles) && is_array($logFiles)) {
            $deleteSince = strtotime(date('Y-m-d'));
            foreach ($logFiles as $file) {
                if (filemtime($file) < $deleteSince) {
                    unlink($file);
                }
            }
        }

    }

    public static function setFileName($name)
    {
        if (!empty($name)) {
            self::$fileName = $name;
        }
    }

}
