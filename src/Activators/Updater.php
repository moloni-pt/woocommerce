<?php

namespace Moloni\Activators;

use WP_Site;

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
     * @return void
     */
    private function updateTableNames(): void
    {
        global $wpdb;

        $query = $wpdb->prepare('SHOW TABLES LIKE %s', 'moloni_api');

        // If we still have the old table names, lets update them
        if ($wpdb->get_var($query) === 'moloni_api') {
            if (is_multisite()) {
                /** @var WP_Site[] $sites */
                $sites = get_sites();
                $first = true;

                foreach ($sites as $site) {
                    if (!$first) {
                        Install::initializeSite($site);
                        continue;
                    }

                    $first = false;
                    $prefix = $wpdb->get_blog_prefix($site->id);

                    $this
                        ->runModification('moloni_api', $prefix . 'moloni_api')
                        ->runModification('moloni_api_config', $prefix . 'moloni_api_config');
                }
            } else {
                $this
                    ->runModification('moloni_api', $wpdb->prefix . 'moloni_api')
                    ->runModification('moloni_api_config', $wpdb->prefix . 'moloni_api_config');
            }
        }
    }

    private function runModification(string $oldName, string $newName): Updater
    {
        global $wpdb;

        $wpdb->query(sprintf('RENAME TABLE %s TO %s ;', $oldName, $newName));

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