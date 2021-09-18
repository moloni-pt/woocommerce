<?php

namespace Moloni\Activators;

class Updater
{
    public function __construct()
    {
        $this->updateTableNames();
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
}