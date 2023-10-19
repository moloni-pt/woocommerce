<?php

namespace Moloni\Helpers;

use Exception;
use Moloni\Curl;
use Moloni\Storage;

class Debug
{
    public static function saveDebugAPIRequests($message = '')
    {
        if (empty($message)) {
            return;
        }

        try {
            $link = self::saveFile(Curl::getLogs());

            $data = ['link' => $link];
        } catch (Exception $e) {
            $data = ['message' => $e->getMessage()];
        }

        Storage::$LOGGER->debug($message, $data);
    }

    /**
     * Save debug log in file and returns public URL
     *
     * @throws Exception
     */
    private static function saveFile($data): string
    {
        if (empty($data)) {
            $data = [];
        }

        $concurrentDirectory = MOLONI_DIR . '/logs';

        if (!is_dir($concurrentDirectory) && !mkdir($concurrentDirectory) && !is_dir($concurrentDirectory)) {
            throw new Exception(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }

        $fileName = time() . '_' . rand(0, 1000) . '.log';
        $fileFullPath = $concurrentDirectory . '/' . $fileName;

        $logFile = fopen($fileFullPath, 'ab');

        fwrite($logFile, json_encode($data));
        fclose($logFile);

        return MOLONI_PLUGIN_URL . '/logs/' . $fileName;
    }
}
