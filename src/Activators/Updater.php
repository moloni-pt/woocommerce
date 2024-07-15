<?php

namespace Moloni\Activators;

use WP_Site;

class Updater
{
    public function __construct()
    {
        $this->updateTableNames();
        $this->createLogSyncTableIfMissing();
        $this->createLogTableIfMissing();
    }

    /**
     * Check if we need to upgrade the table name to add the wp_ prefix
     *
     * @return void
     */
    private function updateTableNames(): void
    {
        global $wpdb;

        $query = $wpdb->prepare('SHOW TABLES LIKE %s', 'moloni_api');

        // If we still have the old table names, lets update them
        if ($wpdb->get_var($query) === 'moloni_api') {
            if (is_multisite() && function_exists('get_sites')) {
                /** @var WP_Site[] $sites */
                $sites = get_sites();

                foreach ($sites as $site) {
                    $prefix = $wpdb->get_blog_prefix($site->id);

                    $wpdb->query("RENAME TABLE moloni_api TO {$prefix}moloni_api");
                    $wpdb->query("RENAME TABLE moloni_api_config TO {$prefix}moloni_api_config");
                }
            } else {
                $prefix = $wpdb->get_blog_prefix();

                $wpdb->query("RENAME TABLE moloni_api TO {$prefix}moloni_api");
                $wpdb->query("RENAME TABLE moloni_api_config TO {$prefix}moloni_api_config");
            }
        }
    }

    /**
     * Check if we need to create the new table (new from 3.0.88)
     *
     * @return void
     */
    private function createLogSyncTableIfMissing(): void
    {
        global $wpdb;

        if (is_multisite() && function_exists('get_sites')) {
            /** @var WP_Site[] $sites */
            $sites = get_sites();

            foreach ($sites as $site) {
                $this->runCreateLogSync($wpdb->get_blog_prefix($site->id));
            }
        } else {
            $this->runCreateLogSync($wpdb->get_blog_prefix());
        }
    }

    /**
     * Check if we need to create the new table (new from 3.0.89)
     *
     * @return void
     */
    private function createLogTableIfMissing(): void
    {
        global $wpdb;

        if (is_multisite() && function_exists('get_sites')) {
            /** @var WP_Site[] $sites */
            $sites = get_sites();

            foreach ($sites as $site) {
                $this->runCreateLog($wpdb->get_blog_prefix($site->id));
            }
        } else {
            $this->runCreateLog($wpdb->get_blog_prefix());
        }
    }

    //          Auxiliary          //

    /**
     * Create log table, if missing
     *
     * @param string $prefix Database prefix
     *
     * @return void
     */
    private function runCreateLog(string $prefix): void
    {
        global $wpdb;

        $wpdb->query(
            "CREATE TABLE IF NOT EXISTS `" . $prefix . "moloni_logs` (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                log_level VARCHAR(100) NULL,
                company_id INT,
                message TEXT,
                context TEXT,
                created_at TIMESTAMP default CURRENT_TIMESTAMP
            ) DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;"
        );
    }

    /**
     * Create log sync table, if missing
     *
     * @param string $prefix Database prefix
     *
     * @return void
     */
    private function runCreateLogSync(string $prefix): void
    {
        global $wpdb;

        $wpdb->query(
            "CREATE TABLE IF NOT EXISTS `" . $prefix . "moloni_sync_logs` (
			    log_id INT NOT NULL AUTO_INCREMENT,
                type_id INT NOT NULL,
                entity_id INT NOT NULL,
                sync_date VARCHAR(250) CHARACTER SET utf8 NOT NULL,
			    PRIMARY KEY (`log_id`)
            ) DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;"
        );
    }
}
