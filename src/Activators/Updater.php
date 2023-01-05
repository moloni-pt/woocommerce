<?php

namespace Moloni\Activators;

use WP_Site;

class Updater
{
    public function __construct()
    {
        $this->updateTableNames();
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
}