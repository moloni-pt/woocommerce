<?php

namespace Moloni\Helpers;

class SyncLogs
{
    /**
     * Validity of each log in seconds
     *
     * @var int
     */
    private static $logValidity = 20;

    //          Publics          //

    /**
     * Adds a new log
     *
     * @param int $typeId
     * @param int $entityId
     *
     * @return void
     */
    public static function addTimeout(int $typeId, int $entityId): void
    {
        global $wpdb;

        $wpdb->insert(
            $wpdb->get_blog_prefix() . 'moloni_sync_logs',
            [
                'type_id' => $typeId,
                'entity_id' => $entityId,
                'sync_date' => time() + self::$logValidity,
            ]
        );
    }

    /**
     * Procedure to check if an entity has been synced recently
     *
     * @param int $typeId
     * @param int $entityId
     *
     * @return bool
     */
    public static function hasTimeout(int $typeId, int $entityId): bool
    {
        /** Delete old logs before checking entry */
        self::removeExpiredTimeouts();

        return self::checkIfExists($typeId, $entityId);
    }

    /**
     * Remove expired timeouts
     *
     * @return void
     */
    public static function removeTimeouts(): void
    {
        self::removeExpiredTimeouts();
    }

    //          Privates          //

    /**
     * Checks for a log entry
     *
     * @param int $typeId
     * @param int $entityId
     *
     * @return bool
     */
    private static function checkIfExists(int $typeId, int $entityId): bool
    {
        global $wpdb;

        $query = "SELECT COUNT(*) FROM `" . $wpdb->get_blog_prefix() . "moloni_sync_logs` 
            where `type_id` = " . $typeId . " AND `entity_id` =" . $entityId;

        $queryResult = $wpdb->get_row($query, ARRAY_A);

        return (int)$queryResult['COUNT(*)'] > 0;
    }

    /**
     * Deletes logs that have more than defined seconds (default 20)
     *
     * @return void
     */
    private static function removeExpiredTimeouts(): void
    {
        global $wpdb;

        $wpdb->query("DELETE FROM `" . $wpdb->get_blog_prefix() . "moloni_sync_logs` WHERE sync_date < " . time());
    }
}