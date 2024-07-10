<?php

namespace Moloni\Helpers;

use Exception;
use Moloni\Curl;
use Moloni\Storage;
use Moloni\Models\Logs;

class Debug
{
    public static function saveAPIRequests($message = '')
    {
        if (empty($message)) {
            $message = 'Moloni API callstack';
        }

        try {
            $link = self::saveFile(Curl::getLogs());

            $data = ['link' => $link];
        } catch (Exception $e) {
            $data = ['message' => $e->getMessage()];
        }

        Storage::$LOGGER->debug($message, $data);
    }

    public static function deleteAllLogs()
    {
        $dir = MOLONI_DIR . '/logs';

        if (!is_dir($dir)) {
            return;
        }

        $objects = scandir($dir);

        if (empty($objects)) {
            return;
        }

        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                wp_delete_file($dir . "/" . $object);
            }
        }

        Logs::removeDebugLogs();
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

        $fileName = time() . '_' . wp_rand(0, 1000) . '.log';
        $fileFullPath = $concurrentDirectory . '/' . $fileName;

        $logFile = fopen($fileFullPath, 'ab');

        fwrite($logFile, base64_encode(wp_json_encode($data)));
        fclose($logFile);

        return MOLONI_PLUGIN_URL . '/logs/' . $fileName;
    }
}
