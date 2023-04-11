<?php

namespace Moloni\Activators;

use WP_Site;

class Updater
{
    public function __construct()
    {
        $this->updateTableNames();
        $this->createLogSyncTableIfMissing();
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

                    $this
                        ->runModification('moloni_api', $prefix . 'moloni_api')
                        ->runModification('moloni_api_config', $prefix . 'moloni_api_config');
                }
            } else {
                $prefix = $wpdb->get_blog_prefix();

                $this
                    ->runModification('moloni_api', $prefix . 'moloni_api')
                    ->runModification('moloni_api_config', $prefix . 'moloni_api_config');
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

    //          Auxiliary          //

    /**
     * Alters old table name
     *
     * @param string $oldName Old table name
     * @param string $newName New table name
     *
     * @return Updater
     */
    private function runModification(string $oldName, string $newName): Updater
    {
        global $wpdb;

        $wpdb->query(sprintf('RENAME TABLE %s TO %s ;', $oldName, $newName));

        return $this;
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
			    log_id int NOT null AUTO_INCREMENT,
                type_id int NOT null,
                entity_id int NOT null,
                sync_date varchar(250) CHARACTER SET utf8 NOT null,
			    PRIMARY KEY (`log_id`)
            ) DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;"
        );
    }
}
