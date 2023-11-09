<?php

namespace Moloni\Helpers;

use Moloni\Enums\Boolean;
use Moloni\Storage;
use Psr\Log\AbstractLogger;

class Logger extends AbstractLogger
{
    public function error($message, array $context = [])
    {
        parent::error($message, $context);

        if (defined('MOLONI_DEBUG_MODE') && (int)MOLONI_DEBUG_MODE === Boolean::YES) {
            Debug::saveAPIRequests();
        }
    }

    public function log($level, $message, array $context = []): void
    {
        global $wpdb;

        $query = $wpdb->prepare(
            "INSERT INTO `" . $wpdb->get_blog_prefix() . "moloni_logs`(log_level, company_id, message, context, created_at) VALUES(%s, %d, %s, %s, %s)",
            $level,
            Storage::$MOLONI_COMPANY_ID ?? 0,
            $message,
            json_encode($context),
            date('Y-m-d H:i:s')
        );

        $wpdb->query($query);
    }
}
