<?php

namespace Moloni\Activators;

class Updater
{
    public function __construct()
    {
        $this->updateTableNames();
        $this->createLogSyncTable();
    }

    /**
     * Check if we need to upgrade the table name to add the wp_ prefix
     *
     * @return Updater
     */
    private function updateTableNames()
    {
        global $wpdb;

        $moloniAPI = $wpdb->prefix . 'moloni_api';
        $moloniAPIConfig = $wpdb->prefix . 'moloni_api_config';

        $query = $wpdb->prepare('SHOW TABLES LIKE %s', 'moloni_api');

        // If we still have the old table names, lets update them
        if ($wpdb->get_var($query) === 'moloni_api') {
            $renameQuery = 'RENAME TABLE %s TO %s ;';
            $wpdb->query(sprintf($renameQuery, 'moloni_api', $moloniAPI));
            $wpdb->query(sprintf($renameQuery, 'moloni_api_config', $moloniAPIConfig));
        }

        return $this;
    }

    /**
     * Check if we need to create the new table (new from 3.0.88)
     *
     * @return void
     */
    private function createLogSyncTable(): void
    {
        global $wpdb;

        $targetTable = $wpdb->prefix . 'moloni_sync_logs';

        $query = $wpdb->prepare('SHOW TABLES LIKE %s', $targetTable);

        if (empty($wpdb->get_var($query))) {
            $wpdb->query(
                "CREATE TABLE `" . $wpdb->prefix . "moloni_sync_logs` (
			    log_id int NOT null AUTO_INCREMENT,
                type_id int NOT null,
                entity_id int NOT null,
                sync_date varchar(250) CHARACTER SET utf8 NOT null,
			    PRIMARY KEY (`log_id`)
            ) DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;"
            );
        }
    }
}